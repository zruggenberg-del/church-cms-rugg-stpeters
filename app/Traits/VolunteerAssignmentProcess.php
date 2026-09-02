<?php

namespace App\Traits;

use App\Models\EventVolunteerAssignment;
use App\Models\EventVolunteerJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait VolunteerAssignmentProcess
{
    use VolunteerReminderProcess;

    public function eligibleVolunteerMembers(int $churchId, array $excludeUserIds = [])
    {
        return User::with('userprofile')
            ->where('church_id', $churchId)
            ->where('usergroup_id', 5)
            ->whereNotIn('id', $excludeUserIds)
            ->whereHas('userprofile', function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'active')
                        ->orWhereNull('status');
                })->where(function ($q) {
                    $q->where('membership_type', 'member')
                        ->orWhereNull('membership_type');
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function canUserSelfSignup(User $user, EventVolunteerJob $job): bool
    {
        $user->loadMissing('userprofile');
        $job->loadMissing('event');

        if (!$user->userprofile || !$user->userprofile->can_volunteer_self_signup) {
            return false;
        }

        if ($job->event && $job->event->enable_volunteer_signup === false) {
            return false;
        }

        if (!$job->allowsSelfSignup()) {
            return false;
        }

        if ($job->openSlots() <= 0) {
            return false;
        }

        return !EventVolunteerAssignment::where([
            ['event_volunteer_job_id', $job->id],
            ['user_id', $user->id],
        ])->whereIn('status', ['confirmed', 'pending'])->exists();
    }

    public function assignVolunteer(EventVolunteerJob $job, int $userId, ?int $assignedBy = null, string $status = 'confirmed', ?string $notes = null): EventVolunteerAssignment
    {
        if ($job->openSlots() <= 0) {
            throw new \RuntimeException('All volunteer slots for this job are filled.');
        }

        $existing = EventVolunteerAssignment::where([
            ['event_volunteer_job_id', $job->id],
            ['user_id', $userId],
        ])->whereIn('status', ['confirmed', 'pending'])->first();

        if ($existing) {
            throw new \RuntimeException('This member is already assigned to this job.');
        }

        $assignment = EventVolunteerAssignment::create([
            'event_volunteer_job_id' => $job->id,
            'user_id' => $userId,
            'status' => $status,
            'assigned_by' => $assignedBy ?? Auth::id(),
            'notes' => $notes,
        ]);

        $this->scheduleVolunteerReminders($assignment);

        return $assignment;
    }

    public function removeVolunteerAssignment(EventVolunteerAssignment $assignment): void
    {
        $this->cancelVolunteerReminders($assignment);
        $assignment->update(['status' => 'cancelled']);
    }
}
