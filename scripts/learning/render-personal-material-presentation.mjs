import fs from 'node:fs/promises'
import process from 'node:process'
import pptxgen from 'pptxgenjs'

const outputPath = process.argv[2]

if (!outputPath) {
    throw new Error('Missing PPTX output path.')
}

let input = ''
for await (const chunk of process.stdin) input += chunk

const payload = JSON.parse(input)
const presentation = payload.presentation

const accentColor = String(payload.accentColor ?? '').replace(/^#/, '').toUpperCase()

if (!/^[0-9A-F]{6}$/.test(accentColor)) {
    throw new Error('Invalid presentation accent color.')
}

const palette = {
    background: 'F1F1F1',
    surface: 'FFFFFF',
    ink: '151515',
    muted: '686868',
    accent: accentColor,
    accentSoft: accentColor,
    contrast: '151515',
}
const pptx = new pptxgen()
pptx.layout = 'LAYOUT_WIDE'
pptx.author = 'MISO Learning'
pptx.company = 'MISO'
pptx.subject = '個人専用研修資料'
pptx.title = presentation.title
pptx.lang = 'ja-JP'
pptx.theme = {
    headFontFace: 'Noto Sans JP',
    bodyFontFace: 'Noto Sans JP',
    lang: 'ja-JP',
}
pptx.defineSlideMaster({
    title: 'MISO',
    background: { color: palette.background },
    objects: [
        {
            line: {
                x: 0.65,
                y: 7.1,
                w: 12.0,
                h: 0,
                line: { color: palette.accentSoft, width: 1 },
            },
        },
        {
            text: {
                text: 'MISO PERSONAL LEARNING',
                options: {
                    x: 0.68,
                    y: 7.13,
                    w: 4.0,
                    h: 0.18,
                    fontFace: 'Aptos',
                    fontSize: 8,
                    color: palette.muted,
                    charSpacing: 1.4,
                    margin: 0,
                },
            },
        },
    ],
    slideNumber: {
        x: 12.1,
        y: 7.08,
        w: 0.55,
        h: 0.22,
        color: palette.muted,
        fontFace: 'Aptos',
        fontSize: 9,
        align: 'right',
        margin: 0,
    },
})

const addText = (slide, text, options = {}) => {
    slide.addText(String(text ?? ''), {
        fontFace: 'Noto Sans JP',
        color: palette.ink,
        breakLine: false,
        margin: 0,
        valign: 'mid',
        fit: 'shrink',
        ...options,
    })
}

const addHeader = (slide, item) => {
    if (item.eyebrow) {
        addText(slide, item.eyebrow.toUpperCase(), {
            x: 0.75,
            y: 0.5,
            w: 5.6,
            h: 0.3,
            fontFace: 'Aptos',
            fontSize: 10,
            bold: true,
            color: palette.ink,
            charSpacing: 1.6,
        })
    }
    addText(slide, item.title, {
        x: 0.75,
        y: 0.82,
        w: 11.8,
        h: 0.8,
        fontSize: 27,
        bold: true,
        breakLine: true,
    })
}

const addBulletList = (slide, bullets, options = {}) => {
    const x = options.x ?? 1.05
    const y = options.y ?? 2.3
    const w = options.w ?? 11.0
    const fontSize = options.fontSize ?? 17
    const gap = options.gap ?? 0.72

    bullets.slice(0, 5).forEach((bullet, index) => {
        slide.addShape(pptx.ShapeType.ellipse, {
            x,
            y: y + index * gap + 0.12,
            w: 0.18,
            h: 0.18,
            line: { color: palette.accent, transparency: 100 },
            fill: { color: palette.accent },
        })
        addText(slide, bullet, {
            x: x + 0.38,
            y: y + index * gap,
            w: w - 0.38,
            h: 0.48,
            fontSize,
            breakLine: true,
        })
    })
}

const addCallout = (slide, text, options = {}) => {
    if (!text) return
    slide.addShape(pptx.ShapeType.roundRect, {
        x: options.x ?? 0.78,
        y: options.y ?? 5.7,
        w: options.w ?? 11.75,
        h: options.h ?? 0.82,
        rectRadius: 0.08,
        line: { color: palette.accentSoft, width: 1 },
        fill: { color: palette.accentSoft },
    })
    addText(slide, text, {
        x: (options.x ?? 0.78) + 0.3,
        y: (options.y ?? 5.7) + 0.08,
        w: (options.w ?? 11.75) - 0.6,
        h: (options.h ?? 0.82) - 0.16,
        fontSize: 15,
        bold: true,
        color: palette.ink,
        align: 'center',
        breakLine: true,
    })
}

const cover = pptx.addSlide('MISO')
cover.addShape(pptx.ShapeType.rect, {
    x: 0,
    y: 0,
    w: 4.5,
    h: 7.5,
    line: { color: palette.accent, transparency: 100 },
    fill: { color: palette.accent },
})
cover.addShape(pptx.ShapeType.arc, {
    x: 2.7,
    y: -1.2,
    w: 4.6,
    h: 4.6,
    adjustPoint: 0.25,
    rotate: 25,
    line: { color: palette.contrast, transparency: 100 },
    fill: { color: palette.contrast, transparency: 83 },
})
addText(cover, 'PERSONAL\\nLEARNING', {
    x: 0.75,
    y: 0.65,
    w: 2.9,
    h: 0.85,
    fontFace: 'Aptos Display',
    fontSize: 15,
    bold: true,
    color: palette.contrast,
    charSpacing: 2.4,
    breakLine: true,
})
addText(cover, presentation.title, {
    x: 5.15,
    y: 1.5,
    w: 7.25,
    h: 1.8,
    fontSize: 31,
    bold: true,
    breakLine: true,
})
addText(cover, presentation.subtitle, {
    x: 5.18,
    y: 3.55,
    w: 6.85,
    h: 0.7,
    fontSize: 17,
    color: palette.muted,
    breakLine: true,
})
addCallout(cover, presentation.summary, { x: 5.1, y: 4.7, w: 6.95, h: 1.12 })

presentation.slides.forEach((item) => {
    const slide = pptx.addSlide('MISO')
    addHeader(slide, item)

    if (item.layout === 'hero') {
        slide.addShape(pptx.ShapeType.rect, {
            x: 0.78,
            y: 1.95,
            w: 0.12,
            h: 2.8,
            line: { color: palette.accent, transparency: 100 },
            fill: { color: palette.accent },
        })
        addText(slide, item.body, {
            x: 1.22,
            y: 2.0,
            w: 10.7,
            h: 2.6,
            fontSize: 24,
            bold: true,
            breakLine: true,
        })
        addCallout(slide, item.callout)
        return
    }

    if (item.layout === 'comparison') {
        const midpoint = Math.max(1, Math.ceil(item.bullets.length / 2))
        const groups = [item.bullets.slice(0, midpoint), item.bullets.slice(midpoint)]
        groups.forEach((bullets, index) => {
            const x = index === 0 ? 0.78 : 6.78
            slide.addShape(pptx.ShapeType.roundRect, {
                x,
                y: 2.15,
                w: 5.75,
                h: 3.15,
                rectRadius: 0.08,
                line: { color: index === 0 ? palette.accentSoft : palette.accent, width: 1.2 },
                fill: { color: index === 0 ? palette.surface : palette.accentSoft },
            })
            addText(slide, index === 0 ? 'これまでの視点' : 'これからの視点', {
                x: x + 0.3,
                y: 2.42,
                w: 5.1,
                h: 0.35,
                fontSize: 13,
                bold: true,
                color: palette.ink,
            })
            addBulletList(slide, bullets, {
                x: x + 0.35,
                y: 2.98,
                w: 5.0,
                fontSize: 15,
                gap: 0.68,
            })
        })
        addCallout(slide, item.callout, { y: 5.65 })
        return
    }

    if (item.layout === 'action_plan') {
        item.bullets.slice(0, 4).forEach((bullet, index) => {
            const x = 0.78 + index * 3.02
            slide.addShape(pptx.ShapeType.roundRect, {
                x,
                y: 2.1,
                w: 2.72,
                h: 3.15,
                rectRadius: 0.08,
                line: { color: palette.accentSoft, width: 1 },
                fill: { color: palette.surface },
            })
            addText(slide, String(index + 1).padStart(2, '0'), {
                x: x + 0.25,
                y: 2.34,
                w: 0.65,
                h: 0.4,
                fontFace: 'Aptos Display',
                fontSize: 17,
                bold: true,
                color: palette.ink,
            })
            addText(slide, bullet, {
                x: x + 0.25,
                y: 3.0,
                w: 2.2,
                h: 1.75,
                fontSize: 16,
                bold: true,
                align: 'center',
                breakLine: true,
            })
        })
        addCallout(slide, item.callout, { y: 5.65 })
        return
    }

    if (item.layout === 'reflection') {
        addText(slide, item.callout || item.body, {
            x: 1.25,
            y: 2.1,
            w: 10.85,
            h: 1.75,
            fontSize: 25,
            bold: true,
            italic: true,
            align: 'center',
            color: palette.ink,
            breakLine: true,
        })
        addText(slide, item.body, {
            x: 1.5,
            y: 4.25,
            w: 10.35,
            h: 0.9,
            fontSize: 16,
            align: 'center',
            color: palette.muted,
            breakLine: true,
        })
        addBulletList(slide, item.bullets, { x: 2.0, y: 5.25, w: 9.2, fontSize: 14, gap: 0.48 })
        return
    }

    addText(slide, item.body, {
        x: 0.8,
        y: 1.75,
        w: 11.65,
        h: 0.68,
        fontSize: 16,
        color: palette.muted,
        breakLine: true,
    })
    addBulletList(slide, item.bullets, { y: 2.65 })
    addCallout(slide, item.callout)
})

const discussion = pptx.addSlide('MISO')
addHeader(discussion, {
    eyebrow: 'DISCUSSION',
    title: 'グループディスカッション用テーマ',
})
presentation.discussion_topics.forEach((topic, index) => {
    const y = 1.9 + index * 1.42
    discussion.addShape(pptx.ShapeType.roundRect, {
        x: 0.8,
        y,
        w: 11.75,
        h: 1.08,
        rectRadius: 0.08,
        line: { color: palette.accentSoft, width: 1 },
        fill: { color: index === 0 ? palette.accentSoft : palette.surface },
    })
    addText(discussion, String(index + 1), {
        x: 1.1,
        y: y + 0.23,
        w: 0.55,
        h: 0.55,
        fontFace: 'Aptos Display',
        fontSize: 20,
        bold: true,
        color: palette.ink,
        align: 'center',
    })
    addText(discussion, topic, {
        x: 1.85,
        y: y + 0.18,
        w: 10.1,
        h: 0.67,
        fontSize: 16,
        bold: true,
        breakLine: true,
    })
})

await pptx.writeFile({ fileName: outputPath })
