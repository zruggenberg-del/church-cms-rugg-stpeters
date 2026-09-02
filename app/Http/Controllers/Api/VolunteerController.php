<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventVolunteerAssignment;
use App\Models\EventVolunteerJob;
use App\Traits\VolunteerAssignmentProcess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolunteerController extends Controller
{
    use VolunteerAssignmentProcess;

    public function opportunities()
    {
        $user = Auth::user();
        $now = now();

        $jobs = EventVolunteerJob::with(['event', 'assignments'])
            ->where('church_id', $user->church_id)
            ->whereHas('event', function ($query) use ($now) {
                $query->where('start_date', '>=', $now);
            })
            ->orderBy('sort_order')
            ->get()
            ->filter(function (EventVolunteerJob $job) use ($user) {
                return $this->canUserSelfSignup($user, $job);
            })
            ->values()
            ->map(function (EventVolunteerJob $job) {
                return [
                    'job_id' => $job->id,
                    'title' => $job->title,
                    'description' => $job->description,
                    'slots_needed' => $job->slots_needed,
                    'open_slots' => $job->openSlots(),
                    'event' => [
                        'id' => $job->event->id,
                        'title' => $job->event->title,
                        'start_date' => $job->event->start_date,
                        'location' => $job->event->location,
                    ],
                ];
            });

        return response()->json(['data' => $jobs]);
    }

    public function myAssignments(Request $request)
    {
        $user = Auth::user();
        $upcoming = $request->boolean('upcoming', true);

        $query = EventVolunteerAssignment::with(['job.event'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'pending']);

        if ($upcoming) {
            $query->whereHas('job.event', function ($q) {
                $q->where('start_date', '>=', now());
            });
        } else {
            $query->whereHas('job.event', function ($q) {
                $q->where('start_date', '<', now());
            });
        }

        $assignments = $query->get()->map(function (EventVolunteerAssignment $assignment) {
            $job = $assignment->job;
            $event = $job?->event;

            return [
                'assignment_id' => $assignment->id,
                'status' => $assignment->status,
                'job_title' => $job?->title,
                'event' => $event ? [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => $event->start_date,
                    'location' => $event->location,
                ] : null,
            ];
        });

        return response()->json(['data' => $assignments]);
    }

    public function signup($job_id)
    {
        $user = Auth::user();

        $job = EventVolunteerJob::with('event')
            ->where('church_id', $user->church_id)
            ->findOrFail($job_id);

        if (!$this->canUserSelfSignup($user, $job)) {
            return response()->json(['message' => 'You are not eligible to sign up for this volunteer role.'], 422);
        }

        try {
            $assignment = $this->assignVolunteer($job, $user->id, $user->id, 'confirmed');

            return response()->json([
                'message' => 'You have been signed up successfully.',
                'assignment_id' => $assignment->id,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel($assignment_id)
    {
        $user = Auth::user();

        $assignment = EventVolunteerAssignment::with('job')
            ->where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->findOrFail($assignment_id);

        $this->removeVolunteerAssignment($assignment);

        return response()->json(['message' => 'Volunteer assignment cancelled.']);
    }
}
