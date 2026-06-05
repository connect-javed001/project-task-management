<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Task $task,
        private string $reminderType
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hours = match($this->reminderType) {
            '48h' => '48 hours',
            '24h' => '24 hours',
            '12h' => '12 hours',
            '1h' => '1 hour',
            default => 'soon'
        };

        return (new MailMessage)
            ->subject("Task Deadline Reminder: {$this->task->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your task '{$this->task->name}' is due in {$hours}.")
            ->line("Project: {$this->task->project->name}")
            ->line("Deadline: {$this->task->deadline->format('Y-m-d H:i')}")
            ->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Thank you for your attention!');
    }
}
