<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueTaskNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Task $task,
        private string $recipientRole = 'employee' // 'employee' or 'manager'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("OVERDUE: Task '{$this->task->name}'")
            ->greeting("Hello {$notifiable->name},");

        if ($this->recipientRole === 'employee') {
            $message->line("Your task '{$this->task->name}' is now OVERDUE.")
                ->line("Project: {$this->task->project->name}")
                ->line("Original Deadline: {$this->task->deadline->format('Y-m-d H:i')}")
                ->line("This task requires immediate attention.");
        } else {
            $message->line("The task '{$this->task->name}' assigned to {$this->task->assignedTo?->name} is now OVERDUE.")
                ->line("Project: {$this->task->project->name}")
                ->line("Original Deadline: {$this->task->deadline->format('Y-m-d H:i')}")
                ->line("Please follow up with the assigned employee.");
        }

        return $message->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Please update the task status as soon as possible.');
    }
}
