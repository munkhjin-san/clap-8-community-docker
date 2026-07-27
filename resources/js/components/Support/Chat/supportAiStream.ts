import type { ChatStreamEvent } from './types'

interface StreamMessagePayload {
    message: string
    conversation_id: number | null
}

/**
 * Send a message and consume the application's server-sent chat stream.
 */
export async function streamSupportAiMessage(
    payload: StreamMessagePayload,
    onEvent: (event: ChatStreamEvent) => void,
    signal?: AbortSignal,
): Promise<void> {
    const csrfToken = document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content

    const response = await fetch('/support/ai-test/messages/stream', {
        method: 'POST',
        credentials: 'same-origin',
        signal,
        headers: {
            Accept: 'text/event-stream',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify(payload),
    })

    if (!response.ok) {
        const error = await response.json().catch(() => null)
        throw new Error(error?.message || `Chat request failed with status ${response.status}.`)
    }

    if (!response.body) {
        throw new Error('The chat stream did not include a response body.')
    }

    const reader = response.body.getReader()
    const decoder = new TextDecoder()
    let buffer = ''

    while (true) {
        const { value, done } = await reader.read()
        buffer += decoder.decode(value, { stream: !done }).replace(/\r\n/g, '\n')

        const frames = buffer.split('\n\n')
        buffer = frames.pop() || ''

        for (const frame of frames) {
            dispatchFrame(frame, onEvent)
        }

        if (done) {
            if (buffer.trim()) {
                dispatchFrame(buffer, onEvent)
            }
            break
        }
    }
}

/**
 * Parse one SSE frame emitted by Laravel's event stream response.
 */
function dispatchFrame(
    frame: string,
    onEvent: (event: ChatStreamEvent) => void,
): void {
    let eventName = 'message'
    const dataLines: string[] = []

    for (const line of frame.split('\n')) {
        if (line.startsWith('event:')) {
            eventName = line.slice(6).trim()
        } else if (line.startsWith('data:')) {
            dataLines.push(line.slice(5).trimStart())
        }
    }

    if (dataLines.length === 0) return

    const data = JSON.parse(dataLines.join('\n'))
    onEvent({ type: eventName, ...data } as ChatStreamEvent)
}
