<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EcclesiasticalEvent;
use App\Models\EventAssignment;
use App\Models\EventChangeLog;
use App\Models\User;
use App\Notifications\EventAssignmentNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcclesiasticalCalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EcclesiasticalEvent::class);

        $validated = $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
            'type' => 'nullable|in:culto,reuniao,ebd,evento,ensaio,atendimento,outro',
            'status' => 'nullable|in:draft,published,cancelled,completed',
            'view' => 'nullable|in:month,week,list',
            'ministry' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $start = isset($validated['start']) ? Carbon::parse($validated['start']) : now()->startOfMonth();
        $end = isset($validated['end']) ? Carbon::parse($validated['end']) : now()->endOfMonth();
        $perPage = (int) ($validated['per_page'] ?? 50);

        $query = EcclesiasticalEvent::query()
            ->with(['assignments.member:id,name', 'ebdClass:id,name'])
            ->where(function ($builder) use ($start, $end) {
                $builder->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($nested) use ($start, $end) {
                        $nested->where('start_at', '<=', $start)
                            ->where('end_at', '>=', $end);
                    });
            })
            ->orderBy('start_at');

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['ministry'])) {
            $query->where('ministry', $validated['ministry']);
        }

        if (($validated['view'] ?? 'month') === 'list') {
            return response()->json($query->paginate($perPage));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EcclesiasticalEvent::class);

        $validated = $this->validateEventPayload($request);
        $userId = $request->user()?->id;

        $this->assertLocationAvailability(
            location: $validated['location'] ?? null,
            startAt: Carbon::parse($validated['start_at']),
            endAt: Carbon::parse($validated['end_at'])
        );

        $event = DB::transaction(function () use ($validated, $userId) {
            $eventData = array_merge($validated, [
                'all_day' => $validated['all_day'] ?? false,
                'created_by' => $userId,
                'updated_by' => $userId,
                'recurrence_rule' => $validated['recurrence_rule'] ?? null,
            ]);

            $event = EcclesiasticalEvent::create($eventData);

            if (($validated['is_recurring'] ?? false) && !empty($validated['recurrence_rule'])) {
                $this->generateRecurringChildren($event, $validated['recurrence_rule'], $userId);
            }

            $this->logChange($event, $userId, 'created', ['new_values' => $event->only([
                'title',
                'type',
                'start_at',
                'end_at',
                'location',
                'status',
            ])]);

            return $event;
        });

        return response()->json($event->load(['assignments', 'ebdClass']), 201);
    }

    public function show(EcclesiasticalEvent $event): JsonResponse
    {
        $this->authorize('view', $event);

        $event->load([
            'assignments.member:id,name,email',
            'assignments.replacedByMember:id,name',
            'ebdClass:id,name',
            'changeLogs.user:id,name',
            'recurringChildren:id,parent_event_id,title,start_at,end_at,status',
        ]);

        return response()->json($event);
    }

    public function update(Request $request, EcclesiasticalEvent $event): JsonResponse
    {
        $this->authorize('update', $event);

        $validated = $this->validateEventPayload($request, true);
        $userId = $request->user()?->id;

        $startAt = isset($validated['start_at']) ? Carbon::parse($validated['start_at']) : $event->start_at;
        $endAt = isset($validated['end_at']) ? Carbon::parse($validated['end_at']) : $event->end_at;
        $location = $validated['location'] ?? $event->location;

        $this->assertLocationAvailability($location, $startAt, $endAt, $event->id);

        $previous = $event->only([
            'title',
            'type',
            'start_at',
            'end_at',
            'location',
            'status',
            'ministry',
            'audience',
        ]);

        $event->fill($validated);
        $event->updated_by = $userId;
        $event->save();

        $this->logChange($event, $userId, 'updated', [
            'old_values' => $previous,
            'new_values' => $event->only(array_keys($previous)),
        ]);

        return response()->json($event->fresh(['assignments.member', 'ebdClass']));
    }

    public function destroy(EcclesiasticalEvent $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $userId = auth()->id();
        $this->logChange($event, $userId, 'deleted', [
            'old_values' => $event->only(['title', 'type', 'start_at', 'end_at', 'location', 'status']),
        ]);

        $event->delete();

        return response()->json(['message' => 'Evento removido com sucesso.']);
    }

    public function publish(EcclesiasticalEvent $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->update(['status' => 'published', 'updated_by' => auth()->id()]);
        $this->logChange($event, auth()->id(), 'published', ['status' => 'published']);

        return response()->json(['message' => 'Evento publicado com sucesso.']);
    }

    public function cancel(Request $request, EcclesiasticalEvent $event): JsonResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $event->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);
        $this->logChange($event, auth()->id(), 'cancelled', [
            'reason' => $validated['reason'] ?? null,
            'status' => 'cancelled',
        ]);

        return response()->json(['message' => 'Evento cancelado com sucesso.']);
    }

    public function complete(EcclesiasticalEvent $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->update(['status' => 'completed', 'updated_by' => auth()->id()]);
        $this->logChange($event, auth()->id(), 'completed', ['status' => 'completed']);

        return response()->json(['message' => 'Evento marcado como concluido.']);
    }

    public function assignments(EcclesiasticalEvent $event): JsonResponse
    {
        $this->authorize('view', $event);

        $assignments = $event->assignments()
            ->with(['member:id,name,email', 'replacedByMember:id,name'])
            ->orderBy('service_area')
            ->orderBy('role_name')
            ->get();

        return response()->json($assignments);
    }

    public function addAssignment(Request $request, EcclesiasticalEvent $event): JsonResponse
    {
        $this->authorize('manageAssignments', $event);

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'service_area' => 'required|in:musica,recepcao,diaconia,midia,apoio,outro',
            'role_name' => 'required|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->assertMemberAvailability(
            memberId: (int) $validated['member_id'],
            startAt: $event->start_at,
            endAt: $event->end_at,
            ignoreEventId: $event->id
        );

        $assignment = EventAssignment::create([
            ...$validated,
            'event_id' => $event->id,
            'created_by' => auth()->id(),
        ]);

        $this->notifyAssignedMember($event, $assignment);
        $this->logChange($event, auth()->id(), 'assignment_created', [
            'assignment' => $assignment->only(['id', 'member_id', 'service_area', 'role_name', 'status']),
        ]);

        return response()->json($assignment->load('member:id,name,email'), 201);
    }

    public function updateAssignment(Request $request, EcclesiasticalEvent $event, EventAssignment $assignment): JsonResponse
    {
        $this->authorize('manageAssignments', $event);

        if ((int) $assignment->event_id !== (int) $event->id) {
            return response()->json(['message' => 'Escala nao pertence ao evento informado.'], 403);
        }

        $validated = $request->validate([
            'service_area' => 'sometimes|in:musica,recepcao,diaconia,midia,apoio,outro',
            'role_name' => 'sometimes|string|max:120',
            'status' => 'sometimes|in:pending,accepted,declined,replaced',
            'notes' => 'nullable|string|max:500',
            'replaced_by_member_id' => 'nullable|exists:members,id',
        ]);

        if (!empty($validated['replaced_by_member_id'])) {
            $validated['status'] = 'replaced';
        }

        $assignment->update($validated);

        $this->logChange($event, auth()->id(), 'assignment_updated', [
            'assignment_id' => $assignment->id,
            'changes' => $validated,
        ]);

        return response()->json($assignment->fresh(['member:id,name,email', 'replacedByMember:id,name']));
    }

    public function removeAssignment(EcclesiasticalEvent $event, EventAssignment $assignment): JsonResponse
    {
        $this->authorize('manageAssignments', $event);

        if ((int) $assignment->event_id !== (int) $event->id) {
            return response()->json(['message' => 'Escala nao pertence ao evento informado.'], 403);
        }

        $assignmentData = $assignment->only(['id', 'member_id', 'service_area', 'role_name', 'status']);
        $assignment->delete();

        $this->logChange($event, auth()->id(), 'assignment_deleted', ['assignment' => $assignmentData]);

        return response()->json(['message' => 'Escala removida com sucesso.']);
    }

    public function respondAssignment(Request $request, EventAssignment $assignment): JsonResponse
    {
        $this->authorize('respondAssignment', $assignment);

        $validated = $request->validate([
            'status' => 'required|in:accepted,declined',
            'notes' => 'nullable|string|max:500',
        ]);

        $assignment->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $assignment->notes,
            'responded_at' => now(),
        ]);

        $event = $assignment->event;
        if ($event) {
            $this->logChange($event, auth()->id(), 'assignment_response', [
                'assignment_id' => $assignment->id,
                'status' => $validated['status'],
            ]);
        }

        return response()->json([
            'message' => 'Resposta da escala registrada com sucesso.',
            'assignment' => $assignment->fresh('event:id,title,start_at,end_at'),
        ]);
    }

    public function myAssignments(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->member_id) {
            return response()->json([]);
        }

        $assignments = EventAssignment::query()
            ->where('member_id', $user->member_id)
            ->whereHas('event', function ($query) {
                $query->where('start_at', '>=', now()->subDays(7))
                    ->whereIn('status', ['draft', 'published']);
            })
            ->with('event:id,title,start_at,end_at,status,type,location,ministry')
            ->orderByDesc(
                EcclesiasticalEvent::select('start_at')
                    ->whereColumn('ecclesiastical_events.id', 'event_assignments.event_id')
            )
            ->paginate(30);

        return response()->json($assignments);
    }

    private function validateEventPayload(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'title' => $required.'|string|max:255',
            'type' => $required.'|in:culto,reuniao,ebd,evento,ensaio,atendimento,outro',
            'description' => 'nullable|string|max:5000',
            'start_at' => $required.'|date',
            'end_at' => $required.'|date|after:start_at',
            'all_day' => 'nullable|boolean',
            'location' => 'nullable|string|max:255',
            'ministry' => 'nullable|string|max:120',
            'audience' => 'nullable|string|max:120',
            'status' => 'nullable|in:draft,published,cancelled,completed',
            'ebd_class_id' => 'nullable|exists:sunday_school_classes,id',
            'is_recurring' => 'nullable|boolean',
            'recurrence_rule' => 'nullable|array',
            'recurrence_rule.frequency' => 'required_with:recurrence_rule|in:weekly,monthly',
            'recurrence_rule.interval' => 'nullable|integer|min:1|max:12',
            'recurrence_rule.count' => 'nullable|integer|min:1|max:52',
            'recurrence_rule.until' => 'nullable|date|after:start_at',
        ]);
    }

    private function assertLocationAvailability(?string $location, Carbon $startAt, Carbon $endAt, ?int $ignoreEventId = null): void
    {
        if (!$location) {
            return;
        }

        $query = EcclesiasticalEvent::query()
            ->where('location', $location)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where(function ($builder) use ($startAt, $endAt) {
                $builder->whereBetween('start_at', [$startAt, $endAt])
                    ->orWhereBetween('end_at', [$startAt, $endAt])
                    ->orWhere(function ($nested) use ($startAt, $endAt) {
                        $nested->where('start_at', '<=', $startAt)
                            ->where('end_at', '>=', $endAt);
                    });
            });

        if ($ignoreEventId) {
            $query->where('id', '!=', $ignoreEventId);
        }

        if ($query->exists()) {
            abort(422, 'Conflito de agenda: local ja reservado para este horario.');
        }
    }

    private function assertMemberAvailability(int $memberId, Carbon $startAt, Carbon $endAt, int $ignoreEventId): void
    {
        $hasConflict = EventAssignment::query()
            ->where('member_id', $memberId)
            ->whereHas('event', function ($query) use ($startAt, $endAt, $ignoreEventId) {
                $query->where('id', '!=', $ignoreEventId)
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->where(function ($builder) use ($startAt, $endAt) {
                        $builder->whereBetween('start_at', [$startAt, $endAt])
                            ->orWhereBetween('end_at', [$startAt, $endAt])
                            ->orWhere(function ($nested) use ($startAt, $endAt) {
                                $nested->where('start_at', '<=', $startAt)
                                    ->where('end_at', '>=', $endAt);
                            });
                    });
            })
            ->exists();

        if ($hasConflict) {
            abort(422, 'Conflito de escala: membro ja escalado em outro evento no mesmo horario.');
        }
    }

    private function generateRecurringChildren(EcclesiasticalEvent $parentEvent, array $rule, ?int $userId): void
    {
        $frequency = $rule['frequency'] ?? 'weekly';
        $interval = max(1, (int) ($rule['interval'] ?? 1));
        $count = max(1, (int) ($rule['count'] ?? 12));
        $maxByUntil = isset($rule['until']) ? Carbon::parse($rule['until']) : null;

        $start = $parentEvent->start_at->copy();
        $end = $parentEvent->end_at->copy();
        $durationSeconds = $end->diffInSeconds($start);

        for ($i = 1; $i < $count; $i++) {
            $nextStart = $start->copy();
            if ($frequency === 'monthly') {
                $nextStart->addMonthsNoOverflow($interval * $i);
            } else {
                $nextStart->addWeeks($interval * $i);
            }

            if ($maxByUntil && $nextStart->gt($maxByUntil)) {
                break;
            }

            EcclesiasticalEvent::create([
                'title' => $parentEvent->title,
                'type' => $parentEvent->type,
                'description' => $parentEvent->description,
                'start_at' => $nextStart,
                'end_at' => $nextStart->copy()->addSeconds($durationSeconds),
                'all_day' => $parentEvent->all_day,
                'location' => $parentEvent->location,
                'ministry' => $parentEvent->ministry,
                'audience' => $parentEvent->audience,
                'status' => $parentEvent->status,
                'is_recurring' => false,
                'parent_event_id' => $parentEvent->id,
                'ebd_class_id' => $parentEvent->ebd_class_id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
            'type' => 'nullable|in:culto,reuniao,ebd,evento,ensaio,atendimento,outro',
        ]);

        $start = isset($validated['start']) ? Carbon::parse($validated['start']) : now()->startOfMonth();
        $end = isset($validated['end']) ? Carbon::parse($validated['end']) : now()->addMonths(2)->endOfMonth();

        $events = EcclesiasticalEvent::query()
            ->select([
                'id', 'title', 'type', 'description', 'start_at', 'end_at', 
                'all_day', 'location', 'ministry', 'audience'
            ])
            ->where('status', 'published')
            ->where(function ($builder) use ($start, $end) {
                $builder->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end]);
            })
            ->orderBy('start_at')
            ->get();

        return response()->json($events);
    }

    private function logChange(EcclesiasticalEvent $event, ?int $userId, string $action, ?array $changes = null): void
    {
        EventChangeLog::create([
            'event_id' => $event->id,
            'user_id' => $userId,
            'action' => $action,
            'changes' => $changes,
        ]);
    }

    private function notifyAssignedMember(EcclesiasticalEvent $event, EventAssignment $assignment): void
    {
        $user = User::where('member_id', $assignment->member_id)->first();
        if (!$user) {
            return;
        }

        $user->notify(new EventAssignmentNotification($event, $assignment));
    }
}
