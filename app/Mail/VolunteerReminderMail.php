<?php

namespace App\Mail;

use App\Models\Mailtemplate;
use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VolunteerReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Reminder $queue;

    public function __construct(Reminder $queue)
    {
        $this->queue = $queue;
    }

    public function build()
    {
        $template = Mailtemplate::where([
            ['name', 'volunteer_reminder'],
            ['status', 'active'],
        ])->first();

        $data = $this->queue->data ?? [];
        $subject = $template?->subject ?? 'Volunteer Assignment Reminder';
        $mailContent = $template?->mail_content ?? 'Hi :volunteer_name,<br>You are scheduled to serve as :job_title for :event_title on :event_date at :event_location.';

        $replacements = [
            ':church_name' => optional($this->queue->church)->name ?? '',
            ':volunteer_name' => $data['volunteer_name'] ?? '',
            ':event_title' => $data['event_title'] ?? '',
            ':job_title' => $data['job_title'] ?? '',
            ':event_date' => $data['start_date'] ?? '',
            ':event_location' => $data['location'] ?? '',
            ':title' => $data['event_title'] ?? '',
            ':location' => $data['location'] ?? '',
            ':start_date' => $data['start_date'] ?? '',
        ];

        $mailContent = str_replace(array_keys($replacements), array_values($replacements), $mailContent);

        return $this->markdown('emails.mailcontent')
            ->subject($subject)
            ->with(['content' => $mailContent]);
    }
}
