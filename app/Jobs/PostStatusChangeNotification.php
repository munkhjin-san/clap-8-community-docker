<?php

namespace App\Jobs;

use App\Mail\PostStatusChangeEmail;
use App\Models\PostRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class PostStatusChangeNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public PostRecord $post;
    public array $excludeIds = [];

    /**
     * Create a new job instance.
     */
    public function __construct(PostRecord $post, array $excludeIds = [])
    {
        $this->post = $post;
        $this->excludeIds = $excludeIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // $post = $this->post->loadMissing([
        //     'user:id,name,email,',
        //     'to_users:id,name,email',
        // ]);

        // $emails = collect()
        //     ->push($post->user?->work_email ?: $post->user?->email)
        //     ->merge($post->to_users->map(fn ($u) => $u->work_email ?: $u->email))
        //     ->filter(fn ($email) => filled($email))
        //     ->unique()
        //     ->values()
        //     ->all();

        // if (!count($emails)) {
        //     return;
        // }

        $related = $this->post->to_users()->whereNotIn('users.id', $this->excludeIds)->whereNotNull('users.email')->get(['users.email']);
        $commenters = $this->post->comments()->whereNotIn('user_id', $this->excludeIds)->with('user:id,email')->get()->pluck('user')->unique('id');
        $supporters = $this->post->awards()->whereNotIn('users.id', $this->excludeIds)->whereNotNull('users.email')->get(['users.email']);
        // dd($supporters);
        $emails = collect()

            ->merge($related->pluck('email'))
            ->merge($commenters->pluck('email'))
            ->merge($supporters->pluck('email'))
            ->filter(fn ($email) => filled($email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if (!count($emails)) {
            return;
        }
        Mail::to([])
            ->bcc($emails)
            ->send(new PostStatusChangeEmail($this->post));
    }
}
