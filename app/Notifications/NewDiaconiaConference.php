<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDiaconiaConference extends Notification
{
    use Queueable;

    public $entry;

    /**
     * Create a new notification instance.
     */
    public function __construct($entry)
    {
        $this->entry = $entry;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'Coins', // Lucide icon name
            'title' => 'Nova Conferência Diaconal',
            'message' => 'Uma nova conferência foi submetida para revisão.',
            'action_url' => "/tesouraria/revisao/{$this->entry->id}",
            'entry_id' => $this->entry->id,
            'amount' => $this->entry->total_amount ?? 0,
        ];
    }
}
