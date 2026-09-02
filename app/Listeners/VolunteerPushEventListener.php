<?php

namespace App\Listeners;

use App\Events\VolunteerPushEvent;
use App\Notifications\SendDeviceNotification;

class VolunteerPushEventListener
{
    public function handle(VolunteerPushEvent $event): void
    {
        $user = $event->queue->user;
        if (!$user || !$user->platform_token) {
            return;
        }

        $data = $event->queue->data ?? [];
        $payload = [
            'type' => 'Volunteer Reminder',
            'message' => sprintf(
                'Reminder: you are serving as %s for %s on %s.',
                $data['job_title'] ?? 'volunteer',
                $data['event_title'] ?? 'an event',
                $data['start_date'] ?? ''
            ),
            'id' => (string) ($data['event_id'] ?? ''),
        ];

        $user->notify(new SendDeviceNotification($payload, $user->platform_token));
    }
}
