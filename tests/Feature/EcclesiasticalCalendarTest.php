<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcclesiasticalCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function calendarManager(): User
    {
        $user = User::create([
            'name' => 'Calendar Manager',
            'email' => 'calendar+'.uniqid().'@example.com',
            'password' => bcrypt('password123!A'),
            'role' => 'viewer',
            'must_change_password' => false,
        ]);

        $role = Role::firstOrCreate(['name' => 'calendar-manager'], ['type' => 'system']);
        $permissionNames = [
            'calendar.events.index',
            'calendar.events.store',
            'calendar.events.show',
            'calendar.events.update',
            'calendar.events.destroy',
            'calendar.events.assignments.index',
            'calendar.events.assignments.store',
            'calendar.events.assignments.update',
            'calendar.events.assignments.destroy',
        ];

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName],
                [
                    'group' => 'Agenda Eclesiástica',
                    'description' => 'Permissão de teste para agenda eclesiástica',
                    'method' => 'GET',
                ]
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    public function test_it_creates_and_lists_ecclesiastical_events(): void
    {
        $user = $this->calendarManager();

        $response = $this->actingAs($user)->postJson('/api/calendar/events', [
            'title' => 'Culto de Domingo',
            'type' => 'culto',
            'start_at' => now()->addDays(1)->setTime(19, 0)->toDateTimeString(),
            'end_at' => now()->addDays(1)->setTime(21, 0)->toDateTimeString(),
            'location' => 'Templo Sede',
            'ministry' => 'Adoração',
            'audience' => 'Igreja Local',
            'status' => 'draft',
        ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Culto de Domingo');

        $list = $this->actingAs($user)->getJson('/api/calendar/events');
        $list->assertOk()
            ->assertJsonCount(1);
    }

    public function test_it_blocks_location_conflict(): void
    {
        $user = $this->calendarManager();
        $start = now()->addDays(2)->setTime(19, 0);
        $end = now()->addDays(2)->setTime(21, 0);

        $this->actingAs($user)->postJson('/api/calendar/events', [
            'title' => 'Culto A',
            'type' => 'culto',
            'start_at' => $start->toDateTimeString(),
            'end_at' => $end->toDateTimeString(),
            'location' => 'Templo Sede',
            'status' => 'published',
        ])->assertCreated();

        $conflict = $this->actingAs($user)->postJson('/api/calendar/events', [
            'title' => 'Reunião Conflitante',
            'type' => 'reuniao',
            'start_at' => $start->copy()->addMinutes(30)->toDateTimeString(),
            'end_at' => $end->copy()->addMinutes(30)->toDateTimeString(),
            'location' => 'Templo Sede',
            'status' => 'draft',
        ]);

        $conflict->assertStatus(422);
    }

    public function test_it_blocks_member_assignment_conflict(): void
    {
        $user = $this->calendarManager();
        $member = Member::create([
            'name' => 'Escalado Teste',
            'email' => 'escalado+'.uniqid().'@example.com',
            'status' => 'active',
        ]);

        $eventOne = $this->actingAs($user)->postJson('/api/calendar/events', [
            'title' => 'Culto Matutino',
            'type' => 'culto',
            'start_at' => now()->addDays(3)->setTime(9, 0)->toDateTimeString(),
            'end_at' => now()->addDays(3)->setTime(11, 0)->toDateTimeString(),
            'location' => 'Templo Sede',
            'status' => 'published',
        ])->json();

        $eventTwo = $this->actingAs($user)->postJson('/api/calendar/events', [
            'title' => 'Reunião Líderes',
            'type' => 'reuniao',
            'start_at' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
            'end_at' => now()->addDays(3)->setTime(12, 0)->toDateTimeString(),
            'location' => 'Sala 1',
            'status' => 'published',
        ])->json();

        $this->actingAs($user)->postJson("/api/calendar/events/{$eventOne['id']}/assignments", [
            'member_id' => $member->id,
            'service_area' => 'musica',
            'role_name' => 'Teclado',
        ])->assertCreated();

        $conflict = $this->actingAs($user)->postJson("/api/calendar/events/{$eventTwo['id']}/assignments", [
            'member_id' => $member->id,
            'service_area' => 'diaconia',
            'role_name' => 'Diácono de Plantão',
        ]);

        $conflict->assertStatus(422);
    }
}
