import { marked } from 'marked'
import DOMPurify from 'dompurify'

export const renderMarkdown = (value?: string | null) => {
    if (!value) return ''

    return DOMPurify.sanitize(marked(value) as string)
}
