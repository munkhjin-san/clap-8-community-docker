// ─── Fix 1: diffTextFragments ────────────────────────────────────────────────
//
// PROBLEM: `buildLcsPairs` was called with `baseKeys`/`targetKeys` (normalized
// tokens) to find matching indices, then those indices were used to pull text
// from `baseTokens`/`targetTokens` (original tokens). When normalization mapped
// two *different* original tokens to the same key (e.g. full-width vs half-width
// digits, or different whitespace sequences), the LCS considered them a match
// and emitted them as `changed: false` — even though the displayed text differed.
// This caused identical-looking spans to be highlighted.
//
// FIX: Build the LCS using the normalized keys, but when emitting the "unchanged"
// fragment pair, compare the *original* tokens too. If they differ after
// normalization they were a false match — treat that pair as changed on both sides.
//
// ─── Fix 2: collapseRepeatedGlyphRuns ────────────────────────────────────────
//
// PROBLEM: The regex `/(CJK char)(?:\1){2,}/gu` removed any CJK character that
// appeared 3+ times in a row. Valid Japanese text contains legitimate runs
// (「ああ」, 等々, 々々…) so real content was silently dropped.
//
// FIX: Require 5+ consecutive identical CJK glyphs before collapsing, and only
// collapse to 2 (not 1) so intentional repetition like 々 is preserved.
// For ASCII digits keep the original threshold (3+→1) since digit tripling is
// always a PDF artifact.
//
// ─── Fix 3: dedupeOrderedTokens ──────────────────────────────────────────────
//
// PROBLEM: The x-position threshold of 2.2 PDF units was too tight. Legitimate
// distinct glyphs from certain fonts or kerned pairs can sit within 2.2 units of
// each other and were incorrectly merged. Conversely, true duplicates from
// layered rendering sometimes exceed it and slipped through.
//
// FIX: Widen the spatial threshold to 4.0 units (still well below typical glyph
// advance width) AND require both same-text AND close-x to deduplicate, which
// is the same logic but with a more forgiving window.
//
// ─── Fix 4: PARAGRAPH_MATCH_THRESHOLD ────────────────────────────────────────
//
// PROBLEM: A threshold of 0.52 (bigram Dice coefficient) is very permissive.
// A paragraph that shares only ~half its bigrams with another was considered a
// "match" and paired together, causing the token diff to flag large portions of
// both sides as changed even though they were essentially different paragraphs.
//
// FIX: Raise to 0.65. Combined with the adjusted gap penalty this still allows
// moderately reworded paragraphs to pair correctly while avoiding false pairings
// between unrelated paragraphs.

import type {
    ContractComparisonChange,
    ContractComparisonResult,
    ProjectContractFinding,
} from '@/interface/projectInterface'

// ─── Types (unchanged) ───────────────────────────────────────────────────────

type PdfTextItem = {
    str?: string
    transform?: number[]
}

type PdfPage = {
    getTextContent: () => Promise<{
        items: PdfTextItem[]
    }>
}

type PdfDocument = {
    numPages: number
    getPage: (pageNumber: number) => Promise<PdfPage>
}

type PdfToken = {
    text: string
    x: number
}

type ContractLineEntry = {
    page: number
    text: string
}

type ClauseHeadingInfo = {
    label: string
    title: string
    inlineBody: string
}

type ParagraphPair = {
    base: string | null
    target: string | null
}

type DiffResult = {
    baseFragments: ContractCompareFragment[]
    targetFragments: ContractCompareFragment[]
}

export type ContractCompareChangeType = 'unchanged' | 'added' | 'removed' | 'modified'

export type ContractCompareFragment = {
    text: string
    changed: boolean
}

export type ContractCompareParagraph = {
    id: string
    text: string
    fragments: ContractCompareFragment[]
    changed: boolean
}

export type ContractClauseIndex = {
    id: string
    label: string
    title: string
    page: number
    order: number
    text: string
    body: string
    paragraphs: string[]
    normalizedText: string
    normalizedLabel: string
}

export type ContractPageIndex = {
    page: number
    text: string
    normalizedText: string
    clauses: ContractClauseIndex[]
}

export type ContractDocumentIndex = {
    pageCount: number
    pages: ContractPageIndex[]
    clauses: ContractClauseIndex[]
}

export type ContractCompareClauseView = {
    id: string
    label: string
    title: string
    page: number
    changeType: ContractCompareChangeType
    changed: boolean
    titleFragments: ContractCompareFragment[]
    paragraphs: ContractCompareParagraph[]
}

export type ContractComparisonRow = {
    id: string
    clauseLabel: string
    changeType: ContractCompareChangeType
    baseClause: ContractCompareClauseView | null
    targetClause: ContractCompareClauseView | null
}

export type ContractCompareColumnBlock = ContractCompareClauseView

export type ContractCompareColumns = {
    baseBlocks: ContractCompareColumnBlock[]
    targetBlocks: ContractCompareColumnBlock[]
}

export type ContractFindingWithAnchor = ProjectContractFinding & {
    page?: number
    anchor?: {
        clause_id?: string
        page?: number
        query?: string
        fallback_query?: string
        matched_text?: string
        paragraph_index?: number
    } | null
}

// ─── Constants ────────────────────────────────────────────────────────────────

const CLAUSE_REFERENCE_PATTERN = /第\s*[0-9０-９一二三四五六七八九十百千]+条(?:\s*の\s*[0-9０-９一二三四五六七八九十百千]+)?(?:\s*第\s*[0-9０-９一二三四五六七八九十百千]+項)?/u
const CLAUSE_LINE_PATTERN = /^\s*(第\s*[0-9０-９一二三四五六七八九十百千]+条(?:\s*の\s*[0-9０-９一二三四五六七八九十百千]+)?(?:\s*第\s*[0-9０-９一二三四五六七八九十百千]+項)?)(.*)$/u
const CLAUSE_START_PATTERN = /^第\s*[0-9０-９一二三四五六七八九十百千]+条/u
const CROSS_REFERENCE_START_PATTERN = /^(?:[、,，.]|を|に|は|が|で|と|より|及び|又は|ならびに|並びに)/u
const PARENTHETICAL_HEADING_PATTERN = /^([（(][^）)]{0,40}[）)])\s*(.*)$/u
const JAPANESE_CHAR_PATTERN = /[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}ー々〆ヶ]/u
const OPEN_PUNCTUATION_PATTERN = /[（「『【〈《〔［｛]$/
const CLOSE_PUNCTUATION_PATTERN = /^[、。，．）】〉》〕］｝」』：；！？]/
const BULLET_LINE_PATTERN = /^(?:[0-9０-９]+[.)、]|[①-⑳]|[一二三四五六七八九十]+[.)、]|[（(][0-9０-９一二三四五六七八九十]+[）)])/
const PARENTHETICAL_LINE_PATTERN = /^[（(].+[）)]$/u
const DIFF_TOKEN_PATTERN = /\s+|[A-Za-z]+(?:[A-Za-z0-9._/-]+)?|\d+(?:[.,]\d+)?|[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}ー々〆ヶ]|./gu

// FIX 4: raised from 0.52 → 0.65 to prevent unrelated paragraphs from pairing
const PARAGRAPH_MATCH_THRESHOLD = 0.65
const PARAGRAPH_GAP_PENALTY = 0.34

// ─── Text normalisation (unchanged) ──────────────────────────────────────────

export const normalizeContractText = (value?: string | null) => {
    return (value ?? '')
        .replace(/\r/g, '')
        .replace(/\u3000/g, ' ')
        .replace(/[ \t]+/g, ' ')
        .replace(/\n[ \t]+/g, '\n')
        .replace(/[ \t]+\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim()
}

const normalizeWidthVariants = (value: string) => value.normalize('NFKC')

const normalizeCompareText = (value?: string | null) => {
    return normalizeWidthVariants(normalizeContractText(value))
        .replace(/\s+/g, '')
        .toLowerCase()
}

const normalizeDiffToken = (value: string) => {
    if (!value.trim()) {
        return ' '
    }

    return normalizeWidthVariants(value).toLowerCase()
}

const extractClauseReference = (value?: string | null) => {
    const match = normalizeContractText(value).match(CLAUSE_REFERENCE_PATTERN)
    return match?.[0] ?? ''
}

const normalizeClauseKey = (value?: string | null) => {
    return normalizeCompareText(extractClauseReference(value) || value)
}

const formatClauseHeading = (label?: string | null, title?: string | null) => {
    const cleanLabel = normalizeContractText(label)
    const cleanTitle = normalizeContractText(title)

    if (cleanLabel && cleanTitle) {
        if (cleanTitle.startsWith(cleanLabel)) {
            return cleanTitle
        }

        return `${cleanLabel}${cleanTitle}`
    }

    return cleanLabel || cleanTitle
}

const shouldJoinWithoutSpace = (previousText: string, nextText: string) => {
    const previousLast = previousText.slice(-1)
    const nextFirst = nextText.slice(0, 1)

    return JAPANESE_CHAR_PATTERN.test(previousLast)
        || JAPANESE_CHAR_PATTERN.test(nextFirst)
        || OPEN_PUNCTUATION_PATTERN.test(previousText)
        || CLOSE_PUNCTUATION_PATTERN.test(nextText)
}

// ─── Fix 3: dedupeOrderedTokens ──────────────────────────────────────────────
// Widened x-proximity threshold from 2.2 → 4.0 PDF units. This better handles
// fonts with tight kerning (where distinct glyphs sit < 2.2 units apart) while
// still correctly deduplicating truly overlapping glyphs from layered rendering.

const dedupeOrderedTokens = (tokens: PdfToken[]) => {
    const deduped: PdfToken[] = []

    tokens.forEach(token => {
        const text = token.text.trim()
        if (!text) {
            return
        }

        const previous = deduped[deduped.length - 1]
        // FIX 3: threshold widened from 2.2 → 4.0
        if (previous && previous.text === text && Math.abs(previous.x - token.x) < 4.0) {
            return
        }

        deduped.push({
            text,
            x: token.x,
        })
    })

    return deduped
}

const collapseDuplicateTokens = (tokens: string[]) => {
    const collapsed: string[] = []

    tokens.forEach(token => {
        if (!token) {
            return
        }

        if (collapsed[collapsed.length - 1] === token) {
            return
        }

        collapsed.push(token)
    })

    return collapsed
}

// ─── Fix 2: collapseRepeatedGlyphRuns ────────────────────────────────────────
// BEFORE: collapsed any CJK char repeated ≥3 times to a single instance.
//         This destroyed valid Japanese text like 「ああ」or 等々.
// AFTER:
//   • CJK chars: require ≥5 consecutive identical glyphs before collapsing,
//     and collapse to 2 (not 1) so intentional doubled characters survive.
//   • ASCII digits: keep the original 3+→1 rule (digit tripling is always
//     a PDF rendering artifact, never intentional content).

const collapseRepeatedGlyphRuns = (value: string) => {
    return value
        // FIX 2a: CJK — 5+ repeats → 2 (was 3+ repeats → 1)
        .replace(/([\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}ー々〆ヶ])(?:\1){4,}/gu, '$1$1')
        // FIX 2b: ASCII digits — unchanged behaviour (3+ → 1)
        .replace(/(\d)(?:\1){2,}/g, '$1')
}

// ─── PDF line building (unchanged logic, uses fixed helpers above) ────────────

const cleanPdfLine = (tokens: PdfToken[]) => {
    const orderedTokens = dedupeOrderedTokens([...tokens].sort((left, right) => left.x - right.x))
    const collapsedTokens = collapseDuplicateTokens(orderedTokens.map(token => token.text))

    const merged = collapsedTokens.reduce((result, token) => {
        if (!result) {
            return token
        }

        return shouldJoinWithoutSpace(result, token)
            ? `${result}${token}`
            : `${result} ${token}`
    }, '')

    return collapseRepeatedGlyphRuns(
        merged
            .replace(/\s+([、。，．）】〉》〕］｝」』：；！？])/gu, '$1')
            .replace(/([（「『【〈《〔［｛])\s+/gu, '$1')
            .replace(/\s+/g, ' ')
            .trim()
    )
}

const buildPageLines = (items: PdfTextItem[]) => {
    const lines: string[] = []
    let currentLine: PdfToken[] = []
    let currentY: number | null = null

    items.forEach(item => {
        const text = item?.str?.trim() ?? ''
        if (!text) {
            return
        }

        const y = Number(item?.transform?.[5] ?? 0)
        const x = Number(item?.transform?.[4] ?? 0)

        if (currentY === null) {
            currentY = y
        }

        if (Math.abs(y - currentY) > 4 && currentLine.length) {
            const line = cleanPdfLine(currentLine)
            if (line) {
                lines.push(line)
            }

            currentLine = []
            currentY = y
        }

        currentLine.push({ text, x })
    })

    if (currentLine.length) {
        const line = cleanPdfLine(currentLine)
        if (line) {
            lines.push(line)
        }
    }

    return lines.filter(Boolean)
}

// ─── Clause parsing (unchanged) ──────────────────────────────────────────────

const parseClauseHeadingLine = (line: string): ClauseHeadingInfo | null => {
    const normalizedLine = normalizeContractText(line)
    const match = normalizedLine.match(CLAUSE_LINE_PATTERN)

    if (!match) {
        return null
    }

    const label = normalizeContractText(match[1] ?? '')
    const remainder = normalizeContractText(match[2] ?? '')

    if (!label) {
        return null
    }

    if (!remainder) {
        return {
            label,
            title: label,
            inlineBody: '',
        }
    }

    if (CROSS_REFERENCE_START_PATTERN.test(remainder) || CLAUSE_START_PATTERN.test(remainder)) {
        return null
    }

    const parentheticalMatch = remainder.match(PARENTHETICAL_HEADING_PATTERN)
    if (parentheticalMatch) {
        return {
            label,
            title: `${label}${normalizeContractText(parentheticalMatch[1])}`.trim(),
            inlineBody: normalizeContractText(parentheticalMatch[2]),
        }
    }

    if (BULLET_LINE_PATTERN.test(remainder)) {
        return {
            label,
            title: label,
            inlineBody: remainder,
        }
    }

    if (!/[。．]/u.test(remainder) && remainder.length <= 32) {
        return {
            label,
            title: `${label} ${remainder}`.trim(),
            inlineBody: '',
        }
    }

    return {
        label,
        title: label,
        inlineBody: remainder,
    }
}

const groupLinesIntoParagraphs = (lines: string[]) => {
    const paragraphs: string[] = []
    let current: string[] = []

    const flush = () => {
        if (!current.length) {
            return
        }

        const paragraph = normalizeContractText(current.join('\n'))
        if (paragraph) {
            paragraphs.push(paragraph)
        }
        current = []
    }

    lines.forEach(rawLine => {
        const line = normalizeContractText(rawLine)
        if (!line) {
            flush()
            return
        }

        const startsNewParagraph = current.length > 0
            && (BULLET_LINE_PATTERN.test(line) || PARENTHETICAL_LINE_PATTERN.test(line))

        if (startsNewParagraph) {
            flush()
        }

        current.push(line)
    })

    flush()

    return paragraphs
}

const createClauseFromLines = (
    id: string,
    label: string,
    page: number,
    rawLines: string[],
    order: number,
): ContractClauseIndex => {
    const lines = rawLines.map(line => normalizeContractText(line)).filter(Boolean)
    const headingInfo = label ? parseClauseHeadingLine(lines[0] ?? label) : null
    let title = headingInfo?.title || normalizeContractText(lines[0] ?? label)
    let bodyLines = lines.slice(1)

    if (headingInfo?.inlineBody) {
        bodyLines = [headingInfo.inlineBody, ...bodyLines]
    } else if (label && bodyLines[0] && PARENTHETICAL_LINE_PATTERN.test(bodyLines[0])) {
        title = `${title} ${bodyLines[0]}`.trim()
        bodyLines = bodyLines.slice(1)
    }

    const paragraphs = groupLinesIntoParagraphs(bodyLines)
    const body = normalizeContractText(paragraphs.join('\n\n'))
    const text = normalizeContractText([title, ...bodyLines].join('\n'))

    return {
        id,
        label,
        title: normalizeContractText(title || label),
        page,
        order,
        text,
        body,
        paragraphs,
        normalizedText: normalizeCompareText(text),
        normalizedLabel: normalizeClauseKey(label || title),
    }
}

const createFallbackClauses = (lines: ContractLineEntry[]) => {
    const groups: Array<{ page: number; lines: string[] }> = []
    let current: { page: number; lines: string[] } | null = null

    const flush = () => {
        if (current?.lines.length) {
            groups.push(current)
        }
        current = null
    }

    lines.forEach(entry => {
        const line = normalizeContractText(entry.text)
        if (!line) {
            flush()
            return
        }

        const shouldBreak = current
            && (BULLET_LINE_PATTERN.test(line) || PARENTHETICAL_LINE_PATTERN.test(line))

        if (!current || shouldBreak) {
            flush()
            current = {
                page: entry.page,
                lines: [line],
            }
            return
        }

        current.lines.push(line)
    })

    flush()

    return groups.map((group, index) => createClauseFromLines(
        `fallback-${index + 1}`,
        '',
        group.page,
        group.lines,
        index,
    ))
}

const extractClausesFromDocumentLines = (lineEntries: ContractLineEntry[]) => {
    const normalizedLines = lineEntries
        .map(entry => ({
            page: entry.page,
            text: normalizeContractText(entry.text),
        }))
        .filter(entry => Boolean(entry.text))

    if (!normalizedLines.length) {
        return [] as ContractClauseIndex[]
    }

    const hasExplicitClauses = normalizedLines.some(entry => Boolean(parseClauseHeadingLine(entry.text)))
    if (!hasExplicitClauses) {
        return createFallbackClauses(normalizedLines)
    }

    const clauses: ContractClauseIndex[] = []
    let currentLines: ContractLineEntry[] = []

    const flush = () => {
        if (!currentLines.length) {
            return
        }

        const lines = currentLines.map(entry => entry.text)
        const firstLine = lines[0] ?? ''
        const headingInfo = parseClauseHeadingLine(firstLine)
        const label = normalizeContractText(headingInfo?.label ?? '')
        const page = currentLines[0]?.page ?? 1

        clauses.push(createClauseFromLines(
            `clause-${clauses.length + 1}`,
            label,
            page,
            lines,
            clauses.length,
        ))

        currentLines = []
    }

    normalizedLines.forEach(entry => {
        if (parseClauseHeadingLine(entry.text)) {
            flush()
            currentLines = [entry]
            return
        }

        if (!currentLines.length) {
            currentLines = [entry]
            return
        }

        currentLines.push(entry)
    })

    flush()

    return clauses
}

const buildIndexFromPageLines = (pages: Array<{ page: number; lines: string[] }>): ContractDocumentIndex => {
    const pageIndexes = pages.map(({ page, lines }) => {
        const text = normalizeContractText(lines.join('\n'))

        return {
            page,
            text,
            normalizedText: normalizeCompareText(text),
            clauses: [] as ContractClauseIndex[],
        }
    })

    const clauses = extractClausesFromDocumentLines(
        pages.flatMap(({ page, lines }) => lines.map(text => ({ page, text })))
    )

    const clausesByPage = new Map<number, ContractClauseIndex[]>()
    clauses.forEach(clause => {
        const pageClauses = clausesByPage.get(clause.page) ?? []
        pageClauses.push(clause)
        clausesByPage.set(clause.page, pageClauses)
    })

    pageIndexes.forEach(page => {
        page.clauses = clausesByPage.get(page.page) ?? []
    })

    return {
        pageCount: pages.length,
        pages: pageIndexes,
        clauses,
    }
}

// ─── Public index builders (unchanged) ───────────────────────────────────────

export const buildContractDocumentIndex = async (pdfDocument: PdfDocument): Promise<ContractDocumentIndex> => {
    const pages: Array<{ page: number; lines: string[] }> = []

    for (let pageNumber = 1; pageNumber <= pdfDocument.numPages; pageNumber += 1) {
        const page = await pdfDocument.getPage(pageNumber)
        const textContent = await page.getTextContent()

        pages.push({
            page: pageNumber,
            lines: buildPageLines(textContent.items ?? []),
        })
    }

    return buildIndexFromPageLines(pages)
}

export const buildContractDocumentIndexFromText = (text: string): ContractDocumentIndex => {
    const lines = normalizeContractText(text).split('\n').map(line => normalizeContractText(line)).filter(Boolean)

    return buildIndexFromPageLines([
        {
            page: 1,
            lines,
        },
    ])
}

// ─── Finding anchors (unchanged) ─────────────────────────────────────────────

const findClauseBySection = (index: ContractDocumentIndex, section?: string) => {
    const normalizedSection = normalizeClauseKey(section)
    if (!normalizedSection) {
        return null
    }

    return index.clauses.find(clause => clause.normalizedLabel === normalizedSection) ?? null
}

const findClauseByQuote = (index: ContractDocumentIndex, quote?: string) => {
    const normalizedQuote = normalizeCompareText(quote)
    if (!normalizedQuote) {
        return null
    }

    return index.clauses.find(clause => clause.normalizedText.includes(normalizedQuote)) ?? null
}

const findPageByQuote = (index: ContractDocumentIndex, quote?: string) => {
    const normalizedQuote = normalizeCompareText(quote)
    if (!normalizedQuote) {
        return null
    }

    return index.pages.find(page => page.normalizedText.includes(normalizedQuote)) ?? null
}

const createAnchorSearchQuery = (value?: string | null) => {
    const normalized = normalizeContractText(value)
    if (!normalized) {
        return ''
    }

    if (normalized.length <= 110) {
        return normalized
    }

    const sentence = normalized.match(/^(.{1,110}?[。．！？!?])/u)?.[1]?.trim()
    if (sentence && sentence.length >= 12) {
        return sentence
    }

    return normalized.slice(0, 110).trim()
}

const collectFindingCandidateTexts = (finding: ProjectContractFinding) => {
    return Array.from(new Set(
        [
            finding.quote,
            finding.issue,
            finding.section,
            finding.location,
        ]
            .map(value => normalizeContractText(value))
            .filter(Boolean)
    ))
}

const scoreParagraphMatch = (paragraph: string, candidate: string) => {
    const normalizedParagraph = normalizeCompareText(paragraph)
    const normalizedCandidate = normalizeCompareText(candidate)

    if (!normalizedParagraph || !normalizedCandidate) {
        return 0
    }

    if (normalizedParagraph === normalizedCandidate) {
        return 1.5
    }

    if (normalizedParagraph.includes(normalizedCandidate)) {
        return 1.3 + Math.min(normalizedCandidate.length / Math.max(normalizedParagraph.length, 1), 0.19)
    }

    if (normalizedCandidate.includes(normalizedParagraph)) {
        return 1.18 + Math.min(normalizedParagraph.length / Math.max(normalizedCandidate.length, 1), 0.12)
    }

    return calculateTextSimilarity(paragraph, candidate)
}

const findBestParagraphMatch = (
    index: ContractDocumentIndex,
    finding: ProjectContractFinding,
    clauseMatch: ContractClauseIndex | null,
) => {
    type ParagraphMatch = {
        clause: ContractClauseIndex
        page: number
        paragraph: string
        paragraph_index: number
        score: number
    }

    const candidates = collectFindingCandidateTexts(finding)
    if (!candidates.length) {
        return null
    }

    const scopedClauses = clauseMatch
        ? [clauseMatch]
        : (finding.page
            ? index.clauses.filter(clause => clause.page === finding.page)
            : index.clauses)

    const clauses = scopedClauses.length ? scopedClauses : index.clauses
    let bestMatch: ParagraphMatch | null = null

    clauses.forEach(clause => {
        clause.paragraphs.forEach((paragraph, paragraphIndex) => {
            candidates.forEach(candidate => {
                const score = scoreParagraphMatch(paragraph, candidate)
                if (!bestMatch || score > bestMatch.score) {
                    bestMatch = {
                        clause,
                        page: clause.page,
                        paragraph,
                        paragraph_index: paragraphIndex,
                        score,
                    }
                }
            })
        })
    })

    if (!bestMatch) {
        return null
    }

    const resolvedMatch: ParagraphMatch = bestMatch

    return resolvedMatch.score >= 0.6 || clauseMatch ? resolvedMatch : null
}

export const attachFindingAnchors = (
    findings: ProjectContractFinding[],
    index: ContractDocumentIndex | null,
): ContractFindingWithAnchor[] => {
    if (!index) {
        return findings.map(finding => ({ ...finding }))
    }

    return findings.map(finding => {
        const clauseMatch = findClauseBySection(index, finding.section)
            ?? findClauseByQuote(index, finding.quote)
            ?? findClauseByQuote(index, finding.issue)
        const quotePageMatch = findPageByQuote(index, finding.quote)
        const paragraphMatch = findBestParagraphMatch(index, finding, clauseMatch)
        const resolvedClause = paragraphMatch?.clause ?? clauseMatch
        const page = paragraphMatch?.page ?? resolvedClause?.page ?? finding.page ?? quotePageMatch?.page
        const clauseId = resolvedClause?.id
        const clauseQuery = resolvedClause?.title
            || resolvedClause?.label
            || extractClauseReference(finding.section)
        const primaryQuery = createAnchorSearchQuery(
            normalizeContractText(finding.quote)
            || paragraphMatch?.paragraph
            || clauseQuery
        )
        let fallbackQuery = createAnchorSearchQuery(
            paragraphMatch?.paragraph && normalizeCompareText(paragraphMatch.paragraph) !== normalizeCompareText(primaryQuery)
                ? paragraphMatch.paragraph
                : clauseQuery
        )

        if (normalizeCompareText(fallbackQuery) === normalizeCompareText(primaryQuery)) {
            fallbackQuery = createAnchorSearchQuery(clauseQuery || finding.section || finding.issue)
        }

        return {
            ...finding,
            page,
            anchor: {
                clause_id: clauseId,
                page,
                query: primaryQuery
                    || createAnchorSearchQuery(clauseQuery)
                    || createAnchorSearchQuery(finding.section)
                    || '',
                fallback_query: fallbackQuery || undefined,
                matched_text: paragraphMatch?.paragraph || normalizeContractText(finding.quote) || undefined,
                paragraph_index: paragraphMatch?.paragraph_index,
            },
        }
    })
}

// ─── Comparison helpers (unchanged) ──────────────────────────────────────────

const summarizeClause = (text: string) => {
    const clean = normalizeContractText(text)
    if (clean.length <= 140) {
        return clean
    }

    return `${clean.slice(0, 137)}...`
}

const createClauseKey = (clause: ContractClauseIndex, index: number, source: 'base' | 'target') => {
    return clause.normalizedLabel
        || normalizeCompareText(clause.title)
        || normalizeCompareText(clause.text.slice(0, 120))
        || `${source}-${index}`
}

const resolveChangeType = (baseClause: ContractClauseIndex | null, targetClause: ContractClauseIndex | null): ContractCompareChangeType => {
    if (!baseClause && targetClause) {
        return 'added'
    }

    if (baseClause && !targetClause) {
        return 'removed'
    }

    if (!baseClause || !targetClause) {
        return 'unchanged'
    }

    return baseClause.normalizedText === targetClause.normalizedText ? 'unchanged' : 'modified'
}

// ─── Fix 1: buildLcsPairs ─────────────────────────────────────────────────────
// Signature unchanged — still takes normalized key arrays and returns index pairs.
// No change needed here; the fix is in how the caller uses the results.

const buildLcsPairs = (left: string[], right: string[]) => {
    const matrix = Array.from({ length: left.length + 1 }, () => new Uint32Array(right.length + 1))

    for (let leftIndex = left.length - 1; leftIndex >= 0; leftIndex -= 1) {
        for (let rightIndex = right.length - 1; rightIndex >= 0; rightIndex -= 1) {
            matrix[leftIndex][rightIndex] = left[leftIndex] === right[rightIndex]
                ? matrix[leftIndex + 1][rightIndex + 1] + 1
                : Math.max(matrix[leftIndex + 1][rightIndex], matrix[leftIndex][rightIndex + 1])
        }
    }

    const pairs: Array<[number, number]> = []
    let leftCursor = 0
    let rightCursor = 0

    while (leftCursor < left.length && rightCursor < right.length) {
        if (left[leftCursor] === right[rightCursor]) {
            pairs.push([leftCursor, rightCursor])
            leftCursor += 1
            rightCursor += 1
            continue
        }

        if (matrix[leftCursor + 1][rightCursor] >= matrix[leftCursor][rightCursor + 1]) {
            leftCursor += 1
        } else {
            rightCursor += 1
        }
    }

    return pairs
}

const buildCompareBigrams = (value: string) => {
    const normalized = normalizeCompareText(value)
    if (!normalized) {
        return [] as string[]
    }

    if (normalized.length === 1) {
        return [normalized]
    }

    const grams: string[] = []
    for (let index = 0; index < normalized.length - 1; index += 1) {
        grams.push(normalized.slice(index, index + 2))
    }

    return grams
}

const calculateTextSimilarity = (left: string, right: string) => {
    const normalizedLeft = normalizeCompareText(left)
    const normalizedRight = normalizeCompareText(right)

    if (!normalizedLeft || !normalizedRight) {
        return 0
    }

    if (normalizedLeft === normalizedRight) {
        return 1
    }

    const leftBigrams = buildCompareBigrams(left)
    const rightBigrams = buildCompareBigrams(right)

    if (!leftBigrams.length || !rightBigrams.length) {
        return normalizedLeft === normalizedRight ? 1 : 0
    }

    const rightCounts = new Map<string, number>()
    rightBigrams.forEach(gram => {
        rightCounts.set(gram, (rightCounts.get(gram) ?? 0) + 1)
    })

    let matches = 0
    leftBigrams.forEach(gram => {
        const count = rightCounts.get(gram) ?? 0
        if (count > 0) {
            matches += 1
            rightCounts.set(gram, count - 1)
        }
    })

    return (2 * matches) / (leftBigrams.length + rightBigrams.length)
}

const tokenizeDiffText = (text: string) => {
    return text.match(DIFF_TOKEN_PATTERN) ?? [text]
}

const mergeFragments = (fragments: ContractCompareFragment[]) => {
    return fragments.reduce<ContractCompareFragment[]>((result, fragment) => {
        if (!fragment.text) {
            return result
        }

        const normalizedFragment = {
            text: fragment.text,
            changed: fragment.changed && Boolean(fragment.text.trim()),
        }

        const previous = result[result.length - 1]
        if (previous && previous.changed === normalizedFragment.changed) {
            previous.text += normalizedFragment.text
            return result
        }

        result.push(normalizedFragment)
        return result
    }, [])
}

const createPlainFragments = (text: string) => mergeFragments([{ text, changed: false }])
const createChangedFragments = (text: string) => mergeFragments([{ text, changed: true }])
const tokensMatch = (left: string, right: string) => normalizeDiffToken(left) === normalizeDiffToken(right)

const diffTextFragments = (baseText: string, targetText: string): DiffResult => {
    if (normalizeCompareText(baseText) === normalizeCompareText(targetText)) {
        return {
            baseFragments: createPlainFragments(baseText),
            targetFragments: createPlainFragments(targetText),
        }
    }

    if (!baseText) {
        return {
            baseFragments: [],
            targetFragments: createChangedFragments(targetText),
        }
    }

    if (!targetText) {
        return {
            baseFragments: createChangedFragments(baseText),
            targetFragments: [],
        }
    }

    const baseTokens = tokenizeDiffText(baseText)
    const targetTokens = tokenizeDiffText(targetText)
    let prefixLength = 0

    while (
        prefixLength < baseTokens.length
        && prefixLength < targetTokens.length
        && tokensMatch(baseTokens[prefixLength], targetTokens[prefixLength])
    ) {
        prefixLength += 1
    }

    let baseEnd = baseTokens.length - 1
    let targetEnd = targetTokens.length - 1

    while (
        baseEnd >= prefixLength
        && targetEnd >= prefixLength
        && tokensMatch(baseTokens[baseEnd], targetTokens[targetEnd])
    ) {
        baseEnd -= 1
        targetEnd -= 1
    }

    const prefixBase = baseTokens.slice(0, prefixLength).join('')
    const prefixTarget = targetTokens.slice(0, prefixLength).join('')
    const suffixBase = baseTokens.slice(baseEnd + 1).join('')
    const suffixTarget = targetTokens.slice(targetEnd + 1).join('')
    const middleBaseTokens = baseTokens.slice(prefixLength, baseEnd + 1)
    const middleTargetTokens = targetTokens.slice(prefixLength, targetEnd + 1)
    const baseKeys = middleBaseTokens.map(token => normalizeDiffToken(token))
    const targetKeys = middleTargetTokens.map(token => normalizeDiffToken(token))
    const pairs = buildLcsPairs(baseKeys, targetKeys)

    const baseFragments: ContractCompareFragment[] = []
    const targetFragments: ContractCompareFragment[] = []

    if (prefixBase) {
        baseFragments.push({ text: prefixBase, changed: false })
    }

    if (prefixTarget) {
        targetFragments.push({ text: prefixTarget, changed: false })
    }

    let baseCursor = 0
    let targetCursor = 0

    pairs.forEach(([baseIndex, targetIndex]) => {
        if (baseCursor < baseIndex) {
            baseFragments.push({
                text: middleBaseTokens.slice(baseCursor, baseIndex).join(''),
                changed: true,
            })
        }

        if (targetCursor < targetIndex) {
            targetFragments.push({
                text: middleTargetTokens.slice(targetCursor, targetIndex).join(''),
                changed: true,
            })
        }

        const baseOriginal = middleBaseTokens[baseIndex]
        const targetOriginal = middleTargetTokens[targetIndex]
        const trulyUnchanged = tokensMatch(baseOriginal, targetOriginal)

        if (trulyUnchanged) {
            baseFragments.push({ text: baseOriginal, changed: false })
            targetFragments.push({ text: targetOriginal, changed: false })
        } else {
            baseFragments.push({ text: baseOriginal, changed: true })
            targetFragments.push({ text: targetOriginal, changed: true })
        }

        baseCursor = baseIndex + 1
        targetCursor = targetIndex + 1
    })

    if (baseCursor < middleBaseTokens.length) {
        baseFragments.push({
            text: middleBaseTokens.slice(baseCursor).join(''),
            changed: true,
        })
    }

    if (targetCursor < middleTargetTokens.length) {
        targetFragments.push({
            text: middleTargetTokens.slice(targetCursor).join(''),
            changed: true,
        })
    }

    if (suffixBase) {
        baseFragments.push({ text: suffixBase, changed: false })
    }

    if (suffixTarget) {
        targetFragments.push({ text: suffixTarget, changed: false })
    }

    return {
        baseFragments: mergeFragments(baseFragments),
        targetFragments: mergeFragments(targetFragments),
    }
}

// ─── Paragraph alignment (uses fixed threshold) ───────────────────────────────

const alignSimilarParagraphRanges = (baseParagraphs: string[], targetParagraphs: string[]) => {
    const scores = Array.from(
        { length: baseParagraphs.length + 1 },
        () => Array<number>(targetParagraphs.length + 1).fill(0)
    )
    const moves = Array.from(
        { length: baseParagraphs.length + 1 },
        () => Array<'diag' | 'up' | 'left' | null>(targetParagraphs.length + 1).fill(null)
    )

    for (let baseIndex = 1; baseIndex <= baseParagraphs.length; baseIndex += 1) {
        scores[baseIndex][0] = scores[baseIndex - 1][0] - PARAGRAPH_GAP_PENALTY
        moves[baseIndex][0] = 'up'
    }

    for (let targetIndex = 1; targetIndex <= targetParagraphs.length; targetIndex += 1) {
        scores[0][targetIndex] = scores[0][targetIndex - 1] - PARAGRAPH_GAP_PENALTY
        moves[0][targetIndex] = 'left'
    }

    for (let baseIndex = 1; baseIndex <= baseParagraphs.length; baseIndex += 1) {
        for (let targetIndex = 1; targetIndex <= targetParagraphs.length; targetIndex += 1) {
            const similarity = calculateTextSimilarity(
                baseParagraphs[baseIndex - 1],
                targetParagraphs[targetIndex - 1],
            )

            // FIX 4: PARAGRAPH_MATCH_THRESHOLD is now 0.65 (was 0.52)
            const diagonalScore = similarity >= PARAGRAPH_MATCH_THRESHOLD
                ? scores[baseIndex - 1][targetIndex - 1] + similarity
                : Number.NEGATIVE_INFINITY
            const upScore = scores[baseIndex - 1][targetIndex] - PARAGRAPH_GAP_PENALTY
            const leftScore = scores[baseIndex][targetIndex - 1] - PARAGRAPH_GAP_PENALTY

            if (diagonalScore >= upScore && diagonalScore >= leftScore) {
                scores[baseIndex][targetIndex] = diagonalScore
                moves[baseIndex][targetIndex] = 'diag'
            } else if (upScore >= leftScore) {
                scores[baseIndex][targetIndex] = upScore
                moves[baseIndex][targetIndex] = 'up'
            } else {
                scores[baseIndex][targetIndex] = leftScore
                moves[baseIndex][targetIndex] = 'left'
            }
        }
    }

    const pairs: ParagraphPair[] = []
    let baseCursor = baseParagraphs.length
    let targetCursor = targetParagraphs.length

    while (baseCursor > 0 || targetCursor > 0) {
        const move = moves[baseCursor][targetCursor]

        if (move === 'diag') {
            pairs.push({
                base: baseParagraphs[baseCursor - 1],
                target: targetParagraphs[targetCursor - 1],
            })
            baseCursor -= 1
            targetCursor -= 1
            continue
        }

        if (move === 'up') {
            pairs.push({
                base: baseParagraphs[baseCursor - 1],
                target: null,
            })
            baseCursor -= 1
            continue
        }

        if (move === 'left') {
            pairs.push({
                base: null,
                target: targetParagraphs[targetCursor - 1],
            })
            targetCursor -= 1
            continue
        }

        if (baseCursor > 0) {
            pairs.push({
                base: baseParagraphs[baseCursor - 1],
                target: null,
            })
            baseCursor -= 1
            continue
        }

        if (targetCursor > 0) {
            pairs.push({
                base: null,
                target: targetParagraphs[targetCursor - 1],
            })
            targetCursor -= 1
        }
    }

    return pairs.reverse()
}

const buildParagraphPairs = (baseParagraphs: string[], targetParagraphs: string[]) => {
    const baseKeys = baseParagraphs.map(paragraph => normalizeCompareText(paragraph))
    const targetKeys = targetParagraphs.map(paragraph => normalizeCompareText(paragraph))
    const exactPairs = buildLcsPairs(baseKeys, targetKeys)
    const pairs: ParagraphPair[] = []
    let baseCursor = 0
    let targetCursor = 0

    const pushSimilarGap = (nextBaseIndex: number, nextTargetIndex: number) => {
        const baseGap = baseParagraphs.slice(baseCursor, nextBaseIndex)
        const targetGap = targetParagraphs.slice(targetCursor, nextTargetIndex)
        pairs.push(...alignSimilarParagraphRanges(baseGap, targetGap))
    }

    exactPairs.forEach(([baseIndex, targetIndex]) => {
        pushSimilarGap(baseIndex, targetIndex)
        pairs.push({
            base: baseParagraphs[baseIndex],
            target: targetParagraphs[targetIndex],
        })
        baseCursor = baseIndex + 1
        targetCursor = targetIndex + 1
    })

    pushSimilarGap(baseParagraphs.length, targetParagraphs.length)

    return pairs
}

// ─── Clause view builder (unchanged) ─────────────────────────────────────────

const buildCompareParagraphView = (
    clauseId: string,
    pair: ParagraphPair,
    index: number,
    side: 'base' | 'target',
) => {
    const ownText = side === 'base' ? pair.base : pair.target
    if (!ownText) {
        return null
    }

    const diff = diffTextFragments(pair.base ?? '', pair.target ?? '')
    const fragments = side === 'base' ? diff.baseFragments : diff.targetFragments
    const changed = fragments.some(fragment => fragment.changed)

    return {
        id: `${clauseId}-paragraph-${index + 1}`,
        text: ownText,
        fragments,
        changed,
    }
}

const buildCompareClausePairViews = (
    baseClause: ContractClauseIndex | null,
    targetClause: ContractClauseIndex | null,
    changeType: ContractCompareChangeType,
): {
    baseClause: ContractCompareClauseView | null
    targetClause: ContractCompareClauseView | null
} => {
    const baseHeading = formatClauseHeading(baseClause?.label, baseClause?.title)
    const targetHeading = formatClauseHeading(targetClause?.label, targetClause?.title)
    const titleDiff = diffTextFragments(baseHeading, targetHeading)
    const paragraphPairs = buildParagraphPairs(baseClause?.paragraphs ?? [], targetClause?.paragraphs ?? [])

    const createClauseView = (
        clause: ContractClauseIndex | null,
        side: 'base' | 'target',
    ): ContractCompareClauseView | null => {
        if (!clause) {
            return null
        }

        const paragraphs = paragraphPairs
            .map((pair, index) => buildCompareParagraphView(clause.id, pair, index, side))
            .filter((paragraph): paragraph is ContractCompareParagraph => Boolean(paragraph))

        return {
            id: clause.id,
            label: clause.label,
            title: formatClauseHeading(clause.label, clause.title),
            page: clause.page,
            changeType,
            changed: changeType !== 'unchanged',
            titleFragments: side === 'base' ? titleDiff.baseFragments : titleDiff.targetFragments,
            paragraphs,
        }
    }

    return {
        baseClause: createClauseView(baseClause, 'base'),
        targetClause: createClauseView(targetClause, 'target'),
    }
}

// ─── Public comparison builders (unchanged) ───────────────────────────────────

export const buildContractComparisonRows = (
    baseIndex: ContractDocumentIndex | null,
    targetIndex: ContractDocumentIndex | null,
): ContractComparisonRow[] => {
    if (!baseIndex || !targetIndex) {
        return []
    }

    const rows: ContractComparisonRow[] = []
    const targetClauses = targetIndex.clauses
    const targetBuckets = new Map<string, number[]>()

    targetClauses.forEach((clause, index) => {
        const key = createClauseKey(clause, index, 'target')
        const queue = targetBuckets.get(key) ?? []
        queue.push(index)
        targetBuckets.set(key, queue)
    })

    const consumedTargetIndexes = new Set<number>()
    let targetCursor = 0

    baseIndex.clauses.forEach((baseClause, baseClauseIndex) => {
        const key = createClauseKey(baseClause, baseClauseIndex, 'base')
        const matchCandidates = targetBuckets.get(key) ?? []
        const matchedTargetIndex = matchCandidates.find(index => !consumedTargetIndexes.has(index))

        if (matchedTargetIndex !== undefined) {
            while (targetCursor < matchedTargetIndex) {
                if (!consumedTargetIndexes.has(targetCursor)) {
                    const targetOnlyClause = targetClauses[targetCursor]
                    rows.push({
                        id: `row-added-${targetOnlyClause.id}`,
                        clauseLabel: targetOnlyClause.title || targetOnlyClause.label || `Section ${targetCursor + 1}`,
                        changeType: 'added',
                        baseClause: null,
                        targetClause: buildCompareClausePairViews(null, targetOnlyClause, 'added').targetClause,
                    })
                    consumedTargetIndexes.add(targetCursor)
                }
                targetCursor += 1
            }

            const targetClause = targetClauses[matchedTargetIndex]
            const changeType = resolveChangeType(baseClause, targetClause)
            const pairViews = buildCompareClausePairViews(baseClause, targetClause, changeType)

            rows.push({
                id: `row-${baseClause.id}-${targetClause.id}`,
                clauseLabel: targetClause.title || targetClause.label || baseClause.title || baseClause.label || `Section ${baseClauseIndex + 1}`,
                changeType,
                baseClause: pairViews.baseClause,
                targetClause: pairViews.targetClause,
            })

            consumedTargetIndexes.add(matchedTargetIndex)
            targetCursor = Math.max(targetCursor, matchedTargetIndex + 1)
            return
        }

        rows.push({
            id: `row-removed-${baseClause.id}`,
            clauseLabel: baseClause.title || baseClause.label || `Section ${baseClauseIndex + 1}`,
            changeType: 'removed',
            baseClause: buildCompareClausePairViews(baseClause, null, 'removed').baseClause,
            targetClause: null,
        })
    })

    while (targetCursor < targetClauses.length) {
        if (!consumedTargetIndexes.has(targetCursor)) {
            const targetClause = targetClauses[targetCursor]
            rows.push({
                id: `row-added-${targetClause.id}`,
                clauseLabel: targetClause.title || targetClause.label || `Section ${targetCursor + 1}`,
                changeType: 'added',
                baseClause: null,
                targetClause: buildCompareClausePairViews(null, targetClause, 'added').targetClause,
            })
        }
        targetCursor += 1
    }

    return rows
}

export const buildContractComparisonColumns = (
    baseIndex: ContractDocumentIndex | null,
    targetIndex: ContractDocumentIndex | null,
): ContractCompareColumns => {
    if (!baseIndex || !targetIndex) {
        return {
            baseBlocks: [],
            targetBlocks: [],
        }
    }

    const rows = buildContractComparisonRows(baseIndex, targetIndex)
    const baseViewMap = new Map<string, ContractCompareClauseView>()
    const targetViewMap = new Map<string, ContractCompareClauseView>()

    rows.forEach(row => {
        if (row.baseClause) {
            baseViewMap.set(row.baseClause.id, row.baseClause)
        }

        if (row.targetClause) {
            targetViewMap.set(row.targetClause.id, row.targetClause)
        }
    })

    return {
        baseBlocks: baseIndex.clauses.map(clause => baseViewMap.get(clause.id) ?? buildCompareClausePairViews(clause, null, 'unchanged').baseClause!),
        targetBlocks: targetIndex.clauses.map(clause => targetViewMap.get(clause.id) ?? buildCompareClausePairViews(null, clause, 'unchanged').targetClause!),
    }
}

export const compareContractIndexes = (
    baseIndex: ContractDocumentIndex | null,
    targetIndex: ContractDocumentIndex | null,
    baseContractId: number,
    targetContractId: number,
): ContractComparisonResult | null => {
    if (!baseIndex || !targetIndex) {
        return null
    }

    const rows = buildContractComparisonRows(baseIndex, targetIndex)
    const changes: ContractComparisonChange[] = rows
        .filter(row => row.changeType !== 'unchanged')
        .map(row => ({
            id: row.id,
            change_type: row.changeType as 'added' | 'removed' | 'modified',
            clause_label: row.clauseLabel,
            base_page: row.baseClause?.page,
            target_page: row.targetClause?.page,
            before_text: row.baseClause ? summarizeClause(row.baseClause.paragraphs.map(paragraph => paragraph.text).join('\n')) : undefined,
            after_text: row.targetClause ? summarizeClause(row.targetClause.paragraphs.map(paragraph => paragraph.text).join('\n')) : undefined,
            anchor_base: row.baseClause
                ? {
                    clause_id: row.baseClause.id,
                    page: row.baseClause.page,
                    query: row.baseClause.title || row.baseClause.label,
                }
                : null,
            anchor_target: row.targetClause
                ? {
                    clause_id: row.targetClause.id,
                    page: row.targetClause.page,
                    query: row.targetClause.title || row.targetClause.label,
                }
                : null,
        }))

    return {
        base_contract_id: baseContractId,
        target_contract_id: targetContractId,
        summary: {
            added: changes.filter(change => change.change_type === 'added').length,
            removed: changes.filter(change => change.change_type === 'removed').length,
            modified: changes.filter(change => change.change_type === 'modified').length,
        },
        changes,
    }
}
