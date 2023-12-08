<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Laravel\Firebase\Facades\Firebase;
// use Kreait\Firebase\Factory;
// use App\Models\NativeUser;
class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $payload;
    /**
     * Create a new job instance.
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $beamsClient = new \Pusher\PushNotifications\PushNotifications(array(
            "instanceId" => config('app.pusher_instanceid'),
            "secretKey" => config('app.pusher_primary_key'),
        ));
        
        
        $publishResponse = $beamsClient->publishToUsers(
            $this->payload['members'],
            array("web" => array("notification" => array(
                    "title" => $this->payload['title'],
                    "body" => $this->payload['body'],
                    "deep_link" => $this->payload['link'],
                    "icon" => $this->payload['icon'],
                    "hide_notification_if_site_has_focus" => true,
                ),  
                "excluded_users" => array($this->payload['user_id']),
                "data" => array(
                    "badge"=> $this->payload['badge'],
                ))
            ));

            // $message = CloudMessage::new();
            // $message = $message->withNotification(['title' => $this->payload['user_name'], 'body' => $this->payload['message']]);
            // $targetsIds = $this->payload['members_int'];
            // $target_tokens = NativeUser::whereIn('user_id', $targetsIds)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();                
            // $send = Firebase::messaging()->sendMulticast($message, $target_tokens);
    }
}
