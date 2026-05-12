<?php

namespace App\Jobs;

use App\Jobs\SocketEmitter;
use App\Services\ChallengeSuggestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Throwable;

class GenerateLunchChallenge implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $userName,
        public string $dateKey,
    ) {
    }

    public function handle(ChallengeSuggestionService $challengeSuggestionService): void
    {
        $challengeCacheKey = $this->challengeCacheKey();

        if (Cache::has($challengeCacheKey)) {
            Cache::forget($this->pendingCacheKey());

            return;
        }

        try {
            $user = User::with([
                'post' => fn ($q) => $q
                    ->where('app_type', 2)
                    ->select(['id', 'user_id', 'title', 'content_rule', 'content_goal']),
                'portfolio' => fn ($q) => $q
                    ->with(['lesson_theme:id,title'])
                    ->select(['id', 'lesson_theme_id', 'user_id', 'public_title', 'public_content']),
            ])
            ->select(['id', 'name', 'motto', 'enjoy', 'awareness', 'intro'])
            ->findOrFail($this->userId);
            
            $challenge = $challengeSuggestionService->suggest($user, null, true);

            Cache::put($challengeCacheKey, $challenge, now()->endOfDay());

            SocketEmitter::dispatch([
                ['event' => "lunch_challenge:ready:{$this->userId}", 'data' => $challenge],
            ]);
        } catch (Throwable $throwable) {
            Log::error('Failed to generate lunch challenge.', [
                'user_id' => $this->userId,
                'date' => $this->dateKey,
                'message' => $throwable->getMessage(),
            ]);

            Cache::forget($this->pendingCacheKey());

            throw $throwable;
        }

        Cache::forget($this->pendingCacheKey());
    }

    private function challengeCacheKey(): string
    {
        return "lunch_challenge:payload:{$this->dateKey}:{$this->userId}";
    }

    private function pendingCacheKey(): string
    {
        return "lunch_challenge:pending:{$this->dateKey}:{$this->userId}";
    }
}
