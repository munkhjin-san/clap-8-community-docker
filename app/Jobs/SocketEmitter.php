<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SocketEmitter implements ShouldQueue
{
    use Queueable;

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
        $bearer = config('socket.token');
        $res = Http::withHeaders([
            'Authorization' => 'Bearer ' . $bearer,
        ])->post(config('socket.url') . '/internal/emit', $this->payload);
        if($res->successful()){
            \Log::info('Successfully emitted socket event', [
                'payload' => $this->payload,
                'response' => $res->body(),
            ]);
        } else {
             \Log::error('Failed to emit socket event', [
                'payload' => $this->payload,
                'response' => $res->body(),
            ]);
        }
    }
}
