<?php
/**
 * Trait for processing common
 */
namespace App\Traits;

use App\Models\Smstemplate;
use App\Traits\MSG91;
use Exception;
use Log;

/**
 * Trait for SMS notification and messaging operations
 *
 * Provides functionality for:
 * - Sending SMS notifications for various events
 * - Sending group notification messages
 * - Sending password reset codes via SMS
 * - Sending private messages via SMS
 * - Template-based message composition
 *
 * @package App\Traits
 */
trait SmsProcess
{
	use MSG91;

    public function sendSmsNotification($to,$start_date,$location)
    {
        try
        {
       	    $template = Smstemplate::where([['name','Event'],['status','1']])->first();
       	    $content  = $template->content;

       	    $content = str_replace(":date",$start_date,$content);
            $content = str_replace(":location",$location,$content);

       		$sms = env('SMS_GATEWAY');

       		if($sms)
    		{
       			$this->sendSMS($content, $to);
       		}
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
        }
    }

    public function sendVolunteerSmsNotification($to, array $data)
    {
        try {
            $template = Smstemplate::where([['name', 'VolunteerAssignment'], ['status', '1']])->first();
            if (!$template) {
                $template = Smstemplate::where([['name', 'Event'], ['status', '1']])->first();
            }

            $content = $template->content;
            $content = str_replace(':volunteer_name', $data['volunteer_name'] ?? '', $content);
            $content = str_replace(':event_title', $data['event_title'] ?? '', $content);
            $content = str_replace(':job_title', $data['job_title'] ?? '', $content);
            $content = str_replace(':event_date', $data['date'] ?? ($data['start_date'] ?? ''), $content);
            $content = str_replace(':event_location', $data['location'] ?? '', $content);
            $content = str_replace(':date', $data['date'] ?? ($data['start_date'] ?? ''), $content);
            $content = str_replace(':location', $data['location'] ?? '', $content);

            $sms = env('SMS_GATEWAY');
            if ($sms) {
                $this->sendSMS($content, $to);
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());
        }
    }

    public function sendUserNotifyGroup($to,$message)
    {
        try
        {
            $template = Smstemplate::where([['name','permission_credentials'],['status','1']])->first();
            $content  = $template->content;

            $content = str_replace(":content",$message,$content);
            $sms = env('SMS_GATEWAY');

            if($sms)
            {
                $this->sendSMS($content, $to);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
        }
    }

    public function sendUserResetPassword($to,$message)
    {
        try
        {
            $template = Smstemplate::where([['name','reset_password'],['status','1']])->first();
            $content  = $template->content;

            $content = str_replace(":token",$message,$content);
            $sms = env('SMS_GATEWAY');

            if($sms)
            {
              $this->sendSMS($content, $to);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
        }
    }

    public function sendPrivateMessage($to,$message)
    {
        try
        {
            $template = Smstemplate::where([['name','send_sms'],['status',1]])->first();
            $content  = $template->content;

            $content = str_replace(":content",$message,$content);
            $sms = env('SMS_GATEWAY');

            if($sms)
            {
                $this->sendPrivateSMS($content, $to);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
        }
    }
}
