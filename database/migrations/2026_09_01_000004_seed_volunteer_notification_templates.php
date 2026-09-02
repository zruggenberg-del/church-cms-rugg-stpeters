<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('mailtemplates')->where('name', 'volunteer_reminder')->exists()) {
            DB::table('mailtemplates')->insert([
                'name' => 'volunteer_reminder',
                'subject' => 'Volunteer Assignment Reminder',
                'mail_content' => 'Hi :volunteer_name,<br><br>
                    This is a reminder that you are scheduled to serve as <strong>:job_title</strong> for <strong>:event_title</strong>.<br><br>
                    Date: :event_date<br>
                    Location: :event_location<br><br>
                    Thank you for serving!<br>
                    :church_name',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!DB::table('sms_templates')->where('name', 'VolunteerAssignment')->exists()) {
            DB::table('sms_templates')->insert([
                'name' => 'VolunteerAssignment',
                'content' => 'Hi :volunteer_name, reminder: you are serving as :job_title for :event_title on :event_date at :event_location.',
                'status' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('mailtemplates')->where('name', 'volunteer_reminder')->delete();
        DB::table('sms_templates')->where('name', 'VolunteerAssignment')->delete();
    }
};
