# Support AI Chat Migration

## Outcome

Add an application-owned support chat at `/dashboard/support/chat-test` while
keeping `/dashboard/support/chat` and its ChatKit Workflow integration unchanged.

The test implementation owns the user interface, conversation history, access
control, prompt, model selection, error handling, and source presentation.
OpenAI remains the inference provider and currently hosts the `file_search`
vector store.

## Current production path

```text
Vue Support shell
  -> ChatKit web component
  -> POST /chatkit/session
  -> OpenAI ChatKit session with OPENAI_CHATKIT_WORKFLOW_ID
  -> Agent Builder Workflow
  -> hosted file_search
```

The application receives only a ChatKit client secret. Conversation execution,
thread rendering, and Workflow behavior remain outside the application.

### Confirmed constraints

- The public OpenAI API exposes ChatKit session and thread endpoints.
- The public API does not expose a Workflow-definition export endpoint.
- `GET /v1/workflows/{workflow_id}` returns `404 Invalid URL`.
- The deployed Workflow ID can therefore be invoked but its graph cannot be
  recovered through the documented API.
- The current regulation vector store is healthy: 200 completed page documents,
  no pending or failed documents at the time of this review.

## Test path

```text
Vue SelfHostedChat
  -> authenticated MISO SSE endpoint
  -> SupportAiChatController
  -> SupportAiChatService
  -> local support_conversations / support_conversation_items
  -> streamed OpenAI Responses API
  -> hosted file_search over the existing FAQ and regulation vector stores
```

The local database is the conversation source of truth. The Responses request is
stateless (`store: false`) and replays a bounded recent local history. This avoids
depending on OpenAI Conversation or ChatKit thread retention.

The UI consumes typed server-sent events (`conversation`, `status`, `delta`,
`done`, and `error`). Text deltas are rendered as they arrive, while only the
completed answer and its retrieval metadata are persisted. The message feed
follows streamed content only while the reader remains near the bottom and
exposes a return-to-latest control after manual scroll-away.

The implementation intentionally uses the Responses API directly for the chat
transport. `laravel/ai` remains responsible for the existing vector-store/file
sync, but its current OpenAI response parser exposes URL citations only and does
not expose `file_search_call.results`. Direct parsing is required to preserve the
existing PDF filename and page-reference contract.

## Existing assets reused

- `support_conversations` and `support_conversation_items`
- `OpenAiRegulationSyncService`
- page-per-Markdown regulation ingestion
- `original_file_name` and `page` vector metadata
- authenticated dashboard/support shell
- sanitized Markdown renderer

Test conversations are isolated with a `self-hosted:` external-ID prefix, so
they do not collide with older experimental OpenAI conversation records.

The Vue implementation is divided into the shell, history sidebar, message
feed, composer, stream transport, and shared chat types. All chat styling uses
the application's existing monochrome CSS variables without feature-specific
accent colors.

## Model and prompt posture

- Default: `OPENAI_SUPPORT_CHAT_MODEL=gpt-5.6-terra`
- Reasoning: `none` for the latency/cost-sensitive support-chat role
- Retrieval: up to 12 combined file-search results across FAQ and regulations
- History: latest 30 local user/assistant messages
- Output: Japanese Markdown with `参考: ファイル名 p.ページ番号`
- Failure policy: do not replace missing company evidence with general knowledge

The model is configurable so production rollout can compare quality, latency,
and cost without a code deployment.

## Production hardening backlog

1. Add an explicit stop-generation control; navigation already aborts the
   browser request.
2. Record response ID, model, token usage, latency, and failure category per turn.
3. Add a retry action that does not duplicate the user message.
4. Add conversation pagination and retention policy.
5. Add admin-visible health for vector-store freshness and failed page syncs.
6. Build a fixed evaluation set from real support questions:
   - grounded answer correctness;
   - correct abstention when evidence is absent;
   - filename/page citation accuracy;
   - prompt-injection resistance in indexed documents;
   - latency and cost per successful answer.
7. Add a feature flag and staged audience before replacing ChatKit.
8. Decide whether “self-hosted” means application-owned orchestration only or
   also requires self-hosted retrieval/model infrastructure.

## Future provider independence

The Vue component talks only to MISO endpoints. A later retrieval migration can
replace OpenAI vector stores with PostgreSQL/pgvector or Qdrant behind the
service boundary. A later inference migration can add another Laravel AI
provider behind the same controller response shape.

Do not move retrieval into MySQL. This application currently uses MySQL/MariaDB,
while Laravel's native vector search targets PostgreSQL with pgvector or
MongoDB. If fully self-hosted RAG is required, PostgreSQL/pgvector as a dedicated
knowledge service or Qdrant is the cleaner migration than changing the primary
application database merely for chat.
