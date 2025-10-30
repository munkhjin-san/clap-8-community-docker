<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Mail\GoalIssueMention;
use App\Models\ProjectGoal;
class SendGoalIssueMentionMail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */


    public function __construct(public array $addresses, public ProjectGoal $goal, public string $content)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach($this->addresses as $email){
            Mail::to($email)->send(new GoalIssueMention($this->goal, $this->content));
        }
        
    }
}
