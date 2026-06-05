<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'name', 'description', 'priority', 'status', 'deadline', 'assigned_to_id', 'estimated_hours', 'created_by_id'])]
class Task extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'estimated_hours' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    public function deadlineNotifications(): HasMany
    {
        return $this->hasMany(DeadlineNotification::class);
    }

    public function getTotalHoursLogged(): float
    {
        return $this->workLogs()->sum('hours_worked') ?? 0;
    }

    public function isOverdue(): bool
    {
        return now()->isAfter($this->deadline) && $this->status !== 'completed';
    }

    public function isOverdueOrUpcoming(): bool
    {
        return now()->diffInHours($this->deadline, false) <= 48;
    }
}
