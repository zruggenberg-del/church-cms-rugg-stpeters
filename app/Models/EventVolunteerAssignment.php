<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventVolunteerAssignment extends Model
{
    protected $table = 'event_volunteer_assignments';

    protected $fillable = [
        'event_volunteer_job_id',
        'user_id',
        'status',
        'assigned_by',
        'notes',
    ];

    public function job()
    {
        return $this->belongsTo(EventVolunteerJob::class, 'event_volunteer_job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['confirmed', 'pending'], true);
    }
}
