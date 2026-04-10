<?php

namespace App\Notifications;

use App\Models\EcclesiasticalEvent;
use App\Models\EventAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly EcclesiasticalEvent $event,
        private readonly EventAssignment $assignment
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nova escala na agenda eclesiastica',
            'message' => sprintf(
                'Voce foi escalado para %s (%s) em %s.',
                $this->assignment->role_name,
                $this->assignment->service_area,
                $this->event->start_at?->format('d/m/Y H:i')
            ),
            'event_id' => $this->event->id,
            'assignment_id' => $this->assignment->id,
            'service_area' => $this->assignment->service_area,
            'role_name' => $this->assignment->role_name,
            'event_start_at' => $this->event->start_at?->toIso8601String(),
            'event_end_at' => $this->event->end_at?->toIso8601String(),
            'event_title' => $this->event->title,
        ];
    }
}
