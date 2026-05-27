<?php

namespace App\Services;

use Aws\Sns\SnsClient;
use Illuminate\Support\Facades\Log;

class EmergencySmsService
{
    public function send(string $message): bool
    {
        $sns = new SnsClient([
            'version' => 'latest',
            'region' => config('services.aws.region'),
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
        ]);

        try {
            $sns->publish([
                'TopicArn' => config('services.aws.sns_emergency_topic_arn'),
                'Message' => $message,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Emergency SMS failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}