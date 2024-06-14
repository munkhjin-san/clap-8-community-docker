<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    }
}
