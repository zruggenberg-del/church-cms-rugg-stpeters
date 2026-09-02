<?php

namespace App\Traits;

use App\Models\ChurchDetail;
use App\Models\EventVolunteerAssignment;
use App\Models\Reminder;
use App\Models\User;
use Exception;
use Log;

trait VolunteerReminderProcess
{
    use ReminderProcess;

    public const ENTITY_NAME = 'App\\Models\\EventVolunteerAssignment';

    public const DEFAULT_INTERVALS = [2880, 1440, 120];

    public function resolveReminderIntervals($event): array
    {
        if (!empty($event->volunteer_reminder_intervals)) {
            return array_map('intval', (array) $event->volunteer_reminder_intervals);
        }

        $churchValue = ChurchDetail::where([
            ['church_id', $event->church_id],
            ['meta_key', 'volunteer_reminder_intervals'],
        ])->value('meta_value');

        if ($churchValue && $churchValue !== '-') {
            $decoded = json_decode($churchValue, true);
            if (is_array($decoded) && !empty($decoded)) {
                return array_map('intval', $decoded);
            }
        }

        return self::DEFAULT_INTERVALS;
    }

    public function scheduleVolunteerReminders(EventVolunteerAssignment $assignment): void
    {
        try {
            $assignment->load(['job.event', 'user.userprofile']);

            if (!$assignment->isActive()) {
                return;
            }

            $job = $assignment->job;
            $event = $job?->event;
            $user = $assignment->user;

            if (!$job || !$event || !$user) {
                return;
            }

            $this->cancelVolunteerReminders($assignment);

            $intervals = $this->resolveReminderIntervals($event);
            $eventStart = strtotime($event->start_date);
            $volunteerName = trim(($user->userprofile->firstname ?? '') . ' ' . ($user->userprofile->lastname ?? ''));
            if ($volunteerName === '') {
                $volunteerName = $user->name;
            }

            $data = [
                'event_id' => $event->id,
                'event_title' => $event->title,
                'job_title' => $job->title,
                'start_date' => $event->start_date,
                'location' => $event->location,
                'volunteer_name' => $volunteerName,
                'date' => date('Y-m-d', $eventStart),
            ];

            foreach ($intervals as $minutes) {
                $executedAt = date('Y-m-d', $eventStart - ((int) $minutes * 60));
                if ($executedAt < date('Y-m-d')) {
                    continue;
                }

                foreach (['mail', 'sms', 'notification'] as $via) {
                    $to = $this->volunteerReminderRecipient($user, $via);
                    if ($to === null) {
                        continue;
                    }

                    $this->createReminder(
                        $event->church_id,
                        env('MAIL_FROM_ADDRESS', 'noreply@localhost'),
                        $to,
                        'Volunteer Assignment Reminder',
                        'Volunteer reminder for ' . $event->title,
                        $assignment->id,
                        self::ENTITY_NAME,
                        $via,
                        $data,
                        $executedAt
                    );
                }
            }
        } catch (Exception $e) {
            Log::info('VolunteerReminderProcess@scheduleVolunteerReminders: ' . $e->getMessage());
        }
    }

    public function cancelVolunteerReminders(EventVolunteerAssignment $assignment): void
    {
        Reminder::where([
            ['entity_id', $assignment->id],
            ['entity_name', self::ENTITY_NAME],
            ['queue_status', 'queue'],
        ])->delete();
    }

    public function rescheduleEventVolunteerReminders(int $eventId): void
    {
        $assignments = EventVolunteerAssignment::whereHas('job', function ($query) use ($eventId) {
            $query->where('event_id', $eventId);
        })->whereIn('status', ['confirmed', 'pending'])->get();

        foreach ($assignments as $assignment) {
            $this->scheduleVolunteerReminders($assignment);
        }
    }

    protected function volunteerReminderRecipient(User $user, string $via): ?string
    {
        if ($via === 'mail') {
            return $user->email ?: null;
        }

        if ($via === 'sms') {
            return $user->mobile_no ?: null;
        }

        return $user->email ?: null;
    }
}
