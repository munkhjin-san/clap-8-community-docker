<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendEmergencyNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Backoff seconds for retries.
     *
     * @var array<int>
     */
    public array $backoff = [10, 60, 300];

    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly string $message)
    {
    }

    /**
     * Execute the job.
     *
     * @throws RequestException
     */
    public function handle(): void
    {
        $username = config('services.cuenote.username');
        $password = config('services.cuenote.password');
        $addressBookId = config('services.cuenote.address_book_id');
        $deliveryUrl = config('services.cuenote.delivery_url');

        if (!$username || !$password || !$addressBookId || !$deliveryUrl) {
            throw new RuntimeException('Cuenote SMS configuration is incomplete.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withBasicAuth((string) $username, (string) $password)
            ->timeout($this->timeout)
            ->post((string) $deliveryUrl, [
                'to' => [
                    'addressBookID' => (string) $addressBookId,
                ],
                'content' => [
                    'message' => $this->message,
                    'shortURI' => false,
                    'clickURI' => false,
                ],
            ]);

        $response->throw();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Emergency SMS notification failed', [
            'message_length' => mb_strlen($this->message),
            'exception' => $exception->getMessage(),
        ]);
    }
}
