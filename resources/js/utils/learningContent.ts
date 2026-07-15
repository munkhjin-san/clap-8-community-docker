import DOMPurify from 'dompurify'

const shortcodePattern = /\[\[(learning_video|learning_pdf)\s+src="([^"]+)"\s+\1\]\]/g

const safeLessonAssetSrc = (value: string) => {
    if (value.startsWith('/lesson_files/') || value.startsWith('/cdn/lesson_files/')) {
        return value.replace(/"/g, '&quot;')
    }

    return ''
}

export type LearningContentSegment =
    | { type: 'html'; html: string }
    | { type: 'video'; src: string }
    | { type: 'pdf'; src: string }

export const parseLearningContent = (value?: string | null): LearningContentSegment[] => {
    const content = value ?? ''
    const segments: LearningContentSegment[] = []
    let lastIndex = 0

    for (const match of content.matchAll(shortcodePattern)) {
        const index = match.index ?? 0
        const html = content.slice(lastIndex, index)
        if (html) {
            segments.push({
                type: 'html',
                html: DOMPurify.sanitize(html),
            })
        }

        const src = safeLessonAssetSrc(match[2] ?? '')
        if (src) {
            segments.push({
                type: match[1] === 'learning_video' ? 'video' : 'pdf',
                src,
            })
        }

        lastIndex = index + match[0].length
    }

    const remainingHtml = content.slice(lastIndex)
    if (remainingHtml) {
        segments.push({
            type: 'html',
            html: DOMPurify.sanitize(remainingHtml),
        })
    }

    return segments
}
