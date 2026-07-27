<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Support\SupportAiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SupportAiChatController extends Controller
{
    public function __construct(private SupportAiChatService $chatService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->chatService->conversationsFor($this->activeUser()));
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        try {
            $conversation = $this->chatService->send(
                $this->activeUser(),
                $validated['message'],
                $validated['conversation_id'] ?? null,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'AIの回答を取得できませんでした。時間をおいてもう一度お試しください。',
            ], 502);
        }

        return response()->json($conversation);
    }

    public function stream(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);
        $user = $this->activeUser();

        return response()->eventStream(function () use ($validated, $user) {
            try {
                foreach ($this->chatService->stream(
                    $user,
                    $validated['message'],
                    $validated['conversation_id'] ?? null,
                ) as $event) {
                    $eventName = $event['type'];
                    unset($event['type']);

                    yield new StreamedEvent($eventName, $event);
                }
            } catch (Throwable $exception) {
                report($exception);

                yield new StreamedEvent('error', [
                    'message' => 'AIの回答を取得できませんでした。時間をおいてもう一度お試しください。',
                ]);
            }
        }, [
            'Cache-Control' => 'no-cache, no-transform',
            'Content-Encoding' => 'none',
            'X-Accel-Buffering' => 'no',
        ], null);
    }

    public function destroy(int $conversation): JsonResponse
    {
        $this->chatService->delete($this->activeUser(), $conversation);

        return response()->json(['deleted' => true]);
    }

    private function activeUser(): User
    {
        // Double-account dropped (community_logic): act as the authenticated user only.
        return Auth::user();
    }
}
