<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChurchDetail;
use App\Models\EventVolunteerAssignment;
use App\Models\EventVolunteerJob;
use App\Models\Events;
use App\Traits\Common;
use App\Traits\LogActivity;
use App\Traits\VolunteerAssignmentProcess;
use App\Traits\VolunteerReminderProcess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;

class EventVolunteerController extends Controller
{
    use LogActivity;
    use Common;
    use VolunteerAssignmentProcess;

    public function index($event_id)
    {
        $event = $this->findEvent($event_id);
        $jobs = EventVolunteerJob::where('event_id', $event->id)
            ->with(['assignments.user.userprofile'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $assignedUserIds = EventVolunteerAssignment::whereIn('event_volunteer_job_id', $jobs->pluck('id'))
            ->whereIn('status', ['confirmed', 'pending'])
            ->pluck('user_id')
            ->unique()
            ->all();

        $members = $this->eligibleVolunteerMembers($event->church_id, $assignedUserIds);
        $defaultIntervals = json_encode($this->resolveReminderIntervals($event));

        return view('admin.events.volunteers', compact('event', 'jobs', 'members', 'defaultIntervals'));
    }

    public function storeJob(Request $request, $event_id)
    {
        $event = $this->findEvent($event_id);
        $this->authorizeVolunteerManage();

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:2000',
            'slots_needed' => 'required|integer|min:1|max:100',
            'allow_self_signup' => 'nullable|boolean',
        ]);

        try {
            EventVolunteerJob::create([
                'church_id' => $event->church_id,
                'event_id' => $event->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'slots_needed' => $data['slots_needed'],
                'allow_self_signup' => $request->has('allow_self_signup') ? $request->boolean('allow_self_signup') : null,
                'sort_order' => (int) EventVolunteerJob::where('event_id', $event->id)->max('sort_order') + 1,
            ]);

            return back()->with('successmessage', 'Volunteer job added.');
        } catch (Exception $e) {
            Log::error('EventVolunteerController@storeJob: ' . $e->getMessage());
            return back()->with('failmessage', 'Could not add volunteer job.');
        }
    }

    public function updateJob(Request $request, $event_id, $job_id)
    {
        $event = $this->findEvent($event_id);
        $this->authorizeVolunteerManage();
        $job = $this->findJob($event, $job_id);

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:2000',
            'slots_needed' => 'required|integer|min:1|max:100',
            'allow_self_signup' => 'nullable|boolean',
        ]);

        try {
            $job->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'slots_needed' => $data['slots_needed'],
                'allow_self_signup' => $request->has('allow_self_signup') ? $request->boolean('allow_self_signup') : null,
            ]);

            return back()->with('successmessage', 'Volunteer job updated.');
        } catch (Exception $e) {
            Log::error('EventVolunteerController@updateJob: ' . $e->getMessage());
            return back()->with('failmessage', 'Could not update volunteer job.');
        }
    }

    public function destroyJob($event_id, $job_id)
    {
        $event = $this->findEvent($event_id);
        $this->authorizeVolunteerManage();
        $job = $this->findJob($event, $job_id);

        foreach ($job->assignments as $assignment) {
            $this->cancelVolunteerReminders($assignment);
        }

        $job->delete();

        return back()->with('successmessage', 'Volunteer job removed.');
    }

    public function storeAssignment(Request $request, $event_id, $job_id)
    {
        $event = $this->findEvent($event_id);
        $this->authorizeVolunteerManage();
        $job = $this->findJob($event, $job_id);

        $request->validate(['user_id' => 'required|exists:users,id']);

        try {
            $this->assignVolunteer($job, (int) $request->user_id, Auth::id());

            return back()->with('successmessage', 'Volunteer assigned.');
        } catch (Exception $e) {
            return back()->with('failmessage', $e->getMessage());
        }
    }

    public function removeAssignment($event_id, $job_id, $assignment_id)
    {
        $event = $this->findEvent($event_id);
        $this->authorizeVolunteerManage();
        $job = $this->findJob($event, $job_id);

        $assignment = EventVolunteerAssignment::where([
            ['id', $assignment_id],
            ['event_volunteer_job_id', $job->id],
        ])->firstOrFail();

        $this->removeVolunteerAssignment($assignment);

        return back()->with('successmessage', 'Volunteer removed.');
    }

    public function updateEventSettings(Request $request, $event_id)
    {
        $event = $this->findEvent($event_id);
        $this->authorizeVolunteerManage();

        $request->validate([
            'enable_volunteer_signup' => 'nullable|boolean',
            'volunteer_reminder_intervals' => 'nullable|string',
        ]);

        $intervals = null;
        if ($request->filled('volunteer_reminder_intervals')) {
            $parsed = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $request->input('volunteer_reminder_intervals')))));
            $intervals = !empty($parsed) ? $parsed : null;
        }

        $event->update([
            'enable_volunteer_signup' => $request->boolean('enable_volunteer_signup', true),
            'volunteer_reminder_intervals' => $intervals,
        ]);

        $this->rescheduleEventVolunteerReminders($event->id);

        return back()->with('successmessage', 'Volunteer settings updated.');
    }

    public function settings()
    {
        $churchId = Auth::user()->church_id;
        $details = ChurchDetail::where('church_id', $churchId)
            ->whereIn('meta_key', ['volunteer_reminder_intervals', 'volunteer_default_self_signup'])
            ->pluck('meta_value', 'meta_key')
            ->toArray();

        $intervalsRaw = $details['volunteer_reminder_intervals'] ?? json_encode(VolunteerReminderProcess::DEFAULT_INTERVALS);
        $decoded = json_decode($intervalsRaw, true);
        $intervals = is_array($decoded) ? implode(', ', $decoded) : $intervalsRaw;
        $defaultSelfSignup = ($details['volunteer_default_self_signup'] ?? 'true') !== 'false';

        return view('admin.settings.volunteer', compact('intervals', 'defaultSelfSignup'));
    }

    public function storeSettings(Request $request)
    {
        $churchId = Auth::user()->church_id;

        $request->validate([
            'volunteer_reminder_intervals' => 'required|string',
            'volunteer_default_self_signup' => 'nullable|boolean',
        ]);

        $parsed = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $request->input('volunteer_reminder_intervals')))));
        if (empty($parsed)) {
            return back()->with('failmessage', 'Enter at least one reminder interval in minutes.');
        }

        ChurchDetail::updateOrCreate(
            ['church_id' => $churchId, 'meta_key' => 'volunteer_reminder_intervals'],
            ['meta_value' => json_encode($parsed)]
        );

        ChurchDetail::updateOrCreate(
            ['church_id' => $churchId, 'meta_key' => 'volunteer_default_self_signup'],
            ['meta_value' => $request->boolean('volunteer_default_self_signup', true) ? 'true' : 'false']
        );

        return back()->with('successmessage', 'Volunteer settings saved.');
    }

    protected function findEvent($event_id): Events
    {
        return Events::where([
            ['church_id', Auth::user()->church_id],
            ['id', $event_id],
        ])->firstOrFail();
    }

    protected function findJob(Events $event, $job_id): EventVolunteerJob
    {
        return EventVolunteerJob::where([
            ['church_id', $event->church_id],
            ['event_id', $event->id],
            ['id', $job_id],
        ])->firstOrFail();
    }

    protected function authorizeVolunteerManage(): void
    {
        $user = Auth::user();
        if ($user->usergroup_id == 3 || $user->hasPermission('manage-event-volunteers') || $user->hasPermission('update-events')) {
            return;
        }

        abort(403);
    }
}
