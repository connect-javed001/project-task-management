<?php

namespace App\Jobs;

use App\Models\DeadlineNotification;
use App\Models\Task;
use App\Notifications\DeadlineReminderNotification;
use App\Notifications\OverdueTaskNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDeadlinesAndNotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        // Get all incomplete tasks
        $tasks = Task::whereIn('status', ['todo', 'in_progress', 'in_review', 'blocked'])
            ->with(['assignedTo', 'project', 'project.assignedManager'])
            ->get();

        foreach ($tasks as $task) {
            if (!$task->assignedTo || $task->deadline === null) {
                continue;
            }

            $hoursUntilDeadline = $now->diffInHours($task->deadline, false);

            // Check if task is overdue
            if ($hoursUntilDeadline < 0) {
                $this->handleOverdue($task);
            } // Check for deadline reminders
            elseif ($hoursUntilDeadline <= 48 && $hoursUntilDeadline > 24) {
                $this->sendReminderIfNotSent($task, '48h');
            } elseif ($hoursUntilDeadline <= 24 && $hoursUntilDeadline > 12) {
                $this->sendReminderIfNotSent($task, '24h');
            } elseif ($hoursUntilDeadline <= 12 && $hoursUntilDeadline > 1) {
                $this->sendReminderIfNotSent($task, '12h');
            } elseif ($hoursUntilDeadline <= 1 && $hoursUntilDeadline > 0) {
                $this->sendReminderIfNotSent($task, '1h');
            }
        }
    }

    private function sendReminderIfNotSent(Task $task, string $type): void
    {
        $exists = DeadlineNotification::where('task_id', $task->id)
            ->where('employee_id', $task->assigned_to_id)
            ->where('notification_type', $type)
            ->exists();

        if (!$exists) {
            // Send notification to employee
            $task->assignedTo->notify(new DeadlineReminderNotification($task, $type));

            // Record notification
            DeadlineNotification::create([
                'task_id' => $task->id,
                'employee_id' => $task->assigned_to_id,
                'notification_type' => $type,
                'sent_at' => now(),
            ]);
        }
    }

    private function handleOverdue(Task $task): void
    {
        // Check if overdue notification already sent
        $overdueExists = DeadlineNotification::where('task_id', $task->id)
            ->where('employee_id', $task->assigned_to_id)
            ->where('notification_type', 'overdue')
            ->exists();

        if ($overdueExists) {
            return; // Already sent
        }

        // Notify employee
        $task->assignedTo->notify(new OverdueTaskNotification($task, 'employee'));

        // Notify manager if assigned
        if ($task->project->assignedManager) {
            $task->project->assignedManager->notify(new OverdueTaskNotification($task, 'manager'));
        }

        // Record notification
        DeadlineNotification::create([
            'task_id' => $task->id,
            'employee_id' => $task->assigned_to_id,
            'notification_type' => 'overdue',
            'sent_at' => now(),
        ]);
    }
}
