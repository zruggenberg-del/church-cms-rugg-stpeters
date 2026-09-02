<?php

namespace App\Listeners;

use App\Events\VolunteerReminderMailEvent;
use App\Mail\VolunteerReminderMail;
use Illuminate\Support\Facades\Mail;

class VolunteerReminderMailEventListener
{
    public function handle(VolunteerReminderMailEvent $event): void
    {
        Mail::to($event->queue->to)->queue(new VolunteerReminderMail($event->queue));
    }
}
