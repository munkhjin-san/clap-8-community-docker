<?php

namespace App\Services\Support;

use App\Models\SupportConversation;
use App\Models\User;
use App\Services\Faq\OpenAiFaqSyncService;
use App\Services\Regulations\OpenAiRegulationSyncService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Generator;
use OpenAI;
use RuntimeException;
use Throwable;

class SupportAiChatService
{
    public const CONVERSATION_PREFIX = 'self-hosted:';

    public function __construct(
        private OpenAiRegulationSyncService $regulationSyncService,
        private OpenAiFaqSyncService $faqSyncService,
        private OpenAiFileSearchResponseParser $responseParser,
    ) {
    }

    public function conversationsFor(User $user): Collection
    {
        return SupportConversation::query()
            ->where('user_id', $user->id)
            ->where('conversation_id', 'like', self::CONVERSATION_PREFIX.'%')
            ->with(['items' => fn ($query) => $query->orderBy('created_at')])
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    public function send(User $user, string $message, ?int $conversationId = null): SupportConversation
    {
        $conversation = $this->appendUserMessage($user, $message, $conversationId);

        try {
            $response = $this->requestResponse($conversation, $user);
            $parsed = $this->responseParser->parse($response->json());
            $this->persistAssistantResponse($conversation, $parsed);
        } catch (Throwable $exception) {
            $this->reportFailure($exception, $conversation, $user);
            throw $exception;
        }

        return $this->loadConversation($conversation);
    }

    /**
     * Stream one assistant turn while keeping the database as the source of truth.
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function stream(
        User $user,
        string $message,
        ?int $conversationId = null,
    ): Generator {
        $conversation = $this->appendUserMessage($user, $message, $conversationId);

        yield [
            'type' => 'conversation',
            'conversation' => $this->loadConversation($conversation),
        ];

        try {
            $apiKey = $this->apiKey();
            $completedResponse = null;
            $searchingEmitted = false;
            $stream = OpenAI::client($apiKey)
                ->responses()
                ->createStreamed($this->responsePayload($conversation, $user));

            foreach ($stream as $event) {
                if (
                    ! $searchingEmitted
                    && str_starts_with($event->event, 'response.file_search_call.')
                ) {
                    $searchingEmitted = true;
                    yield ['type' => 'status', 'status' => 'searching'];
                }

                if ($event->event === 'response.output_text.delta') {
                    yield [
                        'type' => 'delta',
                        'delta' => $event->response->delta,
                    ];
                }

                if ($event->event === 'response.completed') {
                    $completedResponse = $event->response->toArray();
                }

                if (in_array($event->event, ['response.failed', 'response.incomplete'], true)) {
                    throw new RuntimeException('OpenAI did not complete the streamed response.');
                }
            }

            if (! is_array($completedResponse)) {
                throw new RuntimeException('OpenAI stream ended without a completed response.');
            }

            $parsed = $this->responseParser->parse($completedResponse);
            $this->persistAssistantResponse($conversation, $parsed);

            yield [
                'type' => 'done',
                'conversation' => $this->loadConversation($conversation),
            ];
        } catch (Throwable $exception) {
            $this->reportFailure($exception, $conversation, $user);
            throw $exception;
        }
    }

    public function delete(User $user, int $conversationId): void
    {
        $conversation = $this->findConversation($user, $conversationId);

        DB::transaction(function () use ($conversation) {
            $conversation->items()->delete();
            $conversation->delete();
        });
    }

    private function createConversation(User $user, string $message): SupportConversation
    {
        return SupportConversation::create([
            'user_id' => $user->id,
            'conversation_id' => self::CONVERSATION_PREFIX.Str::uuid(),
            'title' => Str::limit(preg_replace('/\s+/u', ' ', trim($message)), 40),
        ]);
    }

    private function findConversation(User $user, int $conversationId): SupportConversation
    {
        return SupportConversation::query()
            ->whereKey($conversationId)
            ->where('user_id', $user->id)
            ->where('conversation_id', 'like', self::CONVERSATION_PREFIX.'%')
            ->firstOrFail();
    }

    private function loadConversation(SupportConversation $conversation): SupportConversation
    {
        return $conversation->fresh([
            'items' => fn ($query) => $query->orderBy('created_at'),
        ]);
    }

    private function requestResponse(SupportConversation $conversation, User $user): Response
    {
        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout(180)
            ->post('https://api.openai.com/v1/responses', $this->responsePayload($conversation, $user));

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI request failed with status '.$response->status().': '.$response->body()
            );
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function responsePayload(SupportConversation $conversation, User $user): array
    {
        $regulationStoreId = $this->regulationSyncService->previousStoreId();
        $faqStoreId = $this->faqSyncService->previousStoreId();

        if (! $regulationStoreId) {
            throw new RuntimeException('The regulation vector store has not been synchronized.');
        }

        if (! $faqStoreId) {
            throw new RuntimeException('The FAQ vector store has not been synchronized.');
        }

        $messages = $conversation->items()
            ->whereIn('role', ['user', 'assistant'])
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($item) => [
                'role' => $item->role,
                'content' => (string) $item->message,
            ])
            ->all();

        $model = config('services.openai.support_chat_model', 'gpt-5.6-terra');
        $payload = [
            'model' => $model,
            'input' => $messages,
            'instructions' => $this->instructions(),
            'tools' => [[
                'type' => 'file_search',
                'vector_store_ids' => [$faqStoreId, $regulationStoreId],
                'max_num_results' => 12,
            ]],
            'include' => ['file_search_call.results'],
            'store' => false,
            'metadata' => [
                'feature' => 'support_ai_test',
                'local_conversation_id' => (string) $conversation->id,
            ],
            'safety_identifier' => hash_hmac('sha256', (string) $user->id, (string) config('app.key')),
        ];

        if (str_starts_with($model, 'gpt-5.6')) {
            $payload['reasoning'] = ['effort' => 'none'];
            $payload['text'] = ['verbosity' => 'medium'];
        }

        return $payload;
    }

    private function apiKey(): string
    {
        $apiKey = config('services.openai.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        return $apiKey;
    }

    private function appendUserMessage(
        User $user,
        string $message,
        ?int $conversationId,
    ): SupportConversation
    {
        $conversation = $conversationId
            ? $this->findConversation($user, $conversationId)
            : $this->createConversation($user, $message);

        $conversation->items()->create([
            'message' => $message,
            'role' => 'user',
        ]);
        $conversation->touch();

        return $conversation;
    }

    /**
     * @param array{reply: string, sources: array<int, string>, keywords: array<int, string>} $parsed
     */
    private function persistAssistantResponse(
        SupportConversation $conversation,
        array $parsed,
    ): void {
        if ($parsed['reply'] === '') {
            throw new RuntimeException('OpenAI returned an empty assistant response.');
        }

        $conversation->items()->create([
            'message' => $parsed['reply'],
            'role' => 'assistant',
            'source' => $parsed['sources'],
            'keywords' => $parsed['keywords'],
        ]);
        $conversation->touch();
    }

    private function reportFailure(
        Throwable $exception,
        SupportConversation $conversation,
        User $user,
    ): void {
        Log::error('Support AI test chat request failed.', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'exception' => $exception->getMessage(),
        ]);
    }

    private function instructions(): string
    {
        return <<<'PROMPT'
Role: You are MISO's internal support assistant for employees.

Goal: Answer the user's question in Japanese using the indexed FAQ knowledge and company regulations.

Success criteria:
- Use file_search before answering questions about company policy, employment rules, benefits, procedures, or internal operations.
- Search both available knowledge sources: operational FAQ records and formal regulation documents.
- Ground material claims in retrieved document content.
- Cite regulation evidence as "参考: 元PDFファイル名 p.ページ番号".
- Cite FAQ evidence as "参考: FAQ「FAQの質問」".
- Give a direct, practical answer and the next action when one is supported by the documents.

Constraints:
- Do not invent company rules or fill gaps with general knowledge.
- Treat retrieved Markdown metadata as navigation context; use the document body as evidence.
- When FAQ guidance conflicts with a formal regulation, follow the regulation and clearly state the conflict.
- If the indexed documents do not contain enough evidence, begin with "関連する社内ファイルが見つかりませんでした" and ask only for the smallest missing information or direct the user to the appropriate internal contact.
- Clearly distinguish an inference from a fact stated in a document.

Output: Japanese Markdown. Lead with the answer, then evidence and any necessary next step. Avoid generic disclaimers and repetition.

Stop rule: If the first retrieval provides sufficient evidence, answer without searching again. Otherwise narrow the answer rather than guessing.
PROMPT;
    }
}
