export type MessageListSource = 'first_load' | 'infiniteLoader' | 'pusher' | 'refresh' | string | undefined

type RouteChatId = string | string[] | number | null | undefined

type BuildMessagePayloadArgs = {
    routeChatId: RouteChatId
    explicitChatId?: number | null
    source?: MessageListSource
    nextMessageCursor?: string | null
    unreadMessageId?: number | string | null
    unreadMessageCount?: number | null
}

export type MessagePayload = {
    record_id: number
    cursor?: string
    message_id?: number | string
}

const toBoardId = (value: RouteChatId): number | null => {
    if (Array.isArray(value)) {
        return toBoardId(value[0])
    }

    const id = Number(value)
    return Number.isFinite(id) && id > 0 ? id : null
}

export const resolveMessageBoardId = (routeChatId: RouteChatId, explicitChatId?: number | null): number | null => {
    return toBoardId(explicitChatId) ?? toBoardId(routeChatId)
}

export const shouldUseMessageCursor = (source?: MessageListSource, nextMessageCursor?: string | null): boolean => {
    return source === 'infiniteLoader' && !!nextMessageCursor
}

export const buildMessagePayload = ({
    routeChatId,
    explicitChatId,
    source,
    nextMessageCursor,
    unreadMessageId,
    unreadMessageCount = 0,
}: BuildMessagePayloadArgs): MessagePayload | null => {
    const boardId = resolveMessageBoardId(routeChatId, explicitChatId)
    if (!boardId) return null

    const payload: MessagePayload = {
        record_id: boardId,
    }

    if (shouldUseMessageCursor(source, nextMessageCursor)) {
        payload.cursor = nextMessageCursor as string
    }

    if (unreadMessageId && Number(unreadMessageCount) > 30 && !payload.cursor) {
        payload.message_id = unreadMessageId
    }

    return payload
}
