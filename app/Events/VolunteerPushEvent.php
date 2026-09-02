<?php

namespace App\Events;

use App\Models\Reminder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VolunteerPushEvent
{
    use Dispatchable, SerializesModels;

    public Reminder $queue;

    public function __construct(Reminder $queue)
    {
        $this->queue = $queue;
    }
}
