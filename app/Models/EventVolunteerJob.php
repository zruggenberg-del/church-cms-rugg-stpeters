<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventVolunteerJob extends Model
{
    protected $table = 'event_volunteer_jobs';

    protected $fillable = [
        'church_id',
        'event_id',
        'title',
        'description',
        'slots_needed',
        'allow_self_signup',
        'sort_order',
    ];

    protected $casts = [
        'allow_self_signup' => 'boolean',
        'slots_needed' => 'integer',
        'sort_order' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Events::class, 'event_id');
    }

    public function church()
    {
        return $this->belongsTo(Church::class, 'church_id');
    }

    public function assignments()
    {
        return $this->hasMany(EventVolunteerAssignment::class, 'event_volunteer_job_id');
    }

    public function activeAssignments()
    {
        return $this->hasMany(EventVolunteerAssignment::class, 'event_volunteer_job_id')
            ->whereIn('status', ['confirmed', 'pending']);
    }

    public function confirmedCount(): int
    {
        return $this->activeAssignments()->count();
    }

    public function openSlots(): int
    {
        return max(0, $this->slots_needed - $this->confirmedCount());
    }

    public function allowsSelfSignup(): bool
    {
        if ($this->allow_self_signup !== null) {
            return (bool) $this->allow_self_signup;
        }

        $event = $this->event;
        if ($event && $event->enable_volunteer_signup === false) {
            return false;
        }

        $default = ChurchDetail::where([
            ['church_id', $this->church_id],
            ['meta_key', 'volunteer_default_self_signup'],
        ])->value('meta_value');

        if ($default !== null && $default !== '-') {
            return filter_var($default, FILTER_VALIDATE_BOOLEAN);
        }

        return true;
    }
}
