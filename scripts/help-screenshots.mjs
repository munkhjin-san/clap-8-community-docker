/**
 * Help-docs screenshot generator.
 *
 * Drives the local dev server with the system Chrome (puppeteer-core) and captures the
 * documentation screenshots into public/images/help/app/. Re-run any time the UI changes:
 *
 *   node scripts/help-screenshots.mjs                       # everything
 *   SHOT_ONLY=records-csv,builder-view node scripts/...     # just these
 *
 * Requirements:
 *  - php artisan serve running on :8000 and vite dev server (or a built bundle)
 *  - APP_ENV=local (uses the /dev_screenshot_login/{user} route, local-only)
 *  - the docs demo app 備品購入申請 (created for the help docs; keep it around)
 *  - demo state for the portal badges, else those shots come out empty:
 *      · 対応待ち — the 承認待ち actions must name SHOT_USER explicitly in `eligible`
 *        (durable: already configured on the demo app; the icon hides at count 0)
 *      · 通知 — unread flow_notifications rows for SHOT_USER on the demo app. These are
 *        TRANSIENT: this very run clears some (opening the list/record marks them read),
 *        so re-seed before each run. Portal shots run first, so the badge still captures.
 */
import puppeteer from 'puppeteer-core'
import { mkdirSync } from 'node:fs'
import path from 'node:path'

const BASE = process.env.BASE_URL ?? 'http://localhost:8000'
const LOGIN_USER = process.env.SHOT_USER ?? '608'
const APP_ID = process.env.DOCS_APP_ID ?? '37' // 備品購入申請 (docs demo app)
const OUT = path.resolve(import.meta.dirname, '../public/images/help/app')
const CHROME = process.env.CHROME_BIN ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'

const sleep = (ms) => new Promise((r) => setTimeout(r, ms))

/** {file, url, waitFor, prep(page)} — prep runs after load, before the shot. */
const SHOTS = [
    { file: 'portal', url: '/apps', waitFor: '.fc-card' },
    {
        file: 'portal-menu', url: '/apps', waitFor: '.fc-card',
        prep: async (p) => {
            await p.click('::-p-xpath(//div[contains(@class,"fc-card")][.//div[contains(@class,"fc-card-name")][contains(text(),"備品購入申請")]]//div[contains(@class,"boardMenuContainer")])')
            await sleep(400)
        },
    },
    {
        // bell popup — needs unread events for SHOT_USER on the demo app (see the header note)
        file: 'portal-bell', url: '/apps', waitFor: '.fc-card',
        prep: async (p) => {
            await p.click('::-p-xpath(//div[contains(@class,"fc-card")][.//div[contains(@class,"fc-card-name")][contains(text(),"備品購入申請")]]//button[contains(@class,"fbell-btn")])')
            await p.waitForSelector('.fbell-menu')
            await sleep(500)
        },
    },
    {
        // 対応待ち popup — icon only renders when the user has explicit pending actions
        file: 'portal-pending', url: '/apps', waitFor: '.fc-card',
        prep: async (p) => {
            await p.click('::-p-xpath(//div[contains(@class,"fc-card")][.//div[contains(@class,"fc-card-name")][contains(text(),"備品購入申請")]]//button[contains(@class,"fpend-btn")])')
            await p.waitForSelector('.fpend-menu')
            await sleep(500)
        },
    },
    { file: 'records', url: `/apps/records/${APP_ID}`, waitFor: '.rv-row' },
    {
        file: 'records-filter', url: `/apps/records/${APP_ID}`, waitFor: '.rv-row',
        prep: async (p) => { await p.click('.rv-filterbtn'); await p.waitForSelector('.rf-logic'); await sleep(300) },
    },
    {
        file: 'records-csv', url: `/apps/records/${APP_ID}`, waitFor: '.rv-row',
        prep: async (p) => {
            await p.click('::-p-xpath(//div[contains(@class,"rv-actbtn")][.//span[contains(text(),"CSV")]])')
            await sleep(300)
            await p.click('::-p-text(CSV出力)')
            await p.waitForSelector('.ce-group')
            await sleep(300)
        },
    },
    { file: 'record-view', url: `/apps/records/${APP_ID}/edit/3`, waitFor: '.rd-act' },
    { file: 'record-new', url: `/apps/records/${APP_ID}/new`, waitFor: '.rd-canvas' },
    {
        file: 'lookup-open', url: `/apps/records/${APP_ID}/new`, waitFor: '.rd-canvas',
        prep: async (p) => {
            const input = await p.waitForSelector('::-p-xpath(//div[contains(@class,"rd-block")][.//label[contains(.,"営業所")]]//input[contains(@class,"fi-input")])')
            await input.click()
            await input.type('大阪', { delay: 40 })
            await p.waitForSelector('.fi-ref-opt')
            await sleep(400)
        },
    },
    {
        file: 'lookup-filled', url: `/apps/records/${APP_ID}/new`, waitFor: '.rd-canvas',
        prep: async (p) => {
            const input = await p.waitForSelector('::-p-xpath(//div[contains(@class,"rd-block")][.//label[contains(.,"営業所")]]//input[contains(@class,"fi-input")])')
            await input.click()
            await input.type('大阪', { delay: 40 })
            await p.waitForSelector('.fi-ref-opt')
            await p.click('.fi-ref-opt')
            await sleep(600)
        },
    },
    { file: 'builder-general', url: `/apps/builder/${APP_ID}/general`, waitFor: '::-p-text(アプリ名)' },
    { file: 'builder-form', url: `/apps/builder/${APP_ID}/form`, waitFor: '.field' },
    {
        file: 'builder-inspector', url: `/apps/builder/${APP_ID}/form`, waitFor: '.field',
        prep: async (p) => {
            await p.click('::-p-xpath(//div[contains(@class,"field")][.//span[contains(@class,"lbl")][contains(text(),"営業所")]])')
            await p.waitForSelector('.insp-col')
            await sleep(500)
        },
    },
    {
        // password field, read-only state: masked + the 表示 button (needs a stored value on #3)
        file: 'password-view', url: `/apps/records/${APP_ID}/edit/3`, waitFor: '.fi-pw-ro',
        prep: async (p) => {
            await p.$eval('.fi-pw-ro', (el) => el.closest('.rd-block').scrollIntoView({ block: 'center' }))
            await sleep(400)
        },
    },
    {
        // revealed state — the endpoint is permission-gated and writes an audit entry
        file: 'password-revealed', url: `/apps/records/${APP_ID}/edit/3`, waitFor: '.fi-pw-ro',
        prep: async (p) => {
            await p.$eval('.fi-pw-ro', (el) => el.closest('.rd-block').scrollIntoView({ block: 'center' }))
            await p.click('.fi-pw-ro .fi-pw-btn')
            await sleep(900)
        },
    },
    {
        // edit state: 設定済み / 変更 / クリア
        file: 'password-edit', url: `/apps/records/${APP_ID}/edit/3?edit=1`, waitFor: '.fi-pw',
        prep: async (p) => {
            await p.$eval('.fi-pw', (el) => el.closest('.rd-block').scrollIntoView({ block: 'center' }))
            await sleep(400)
        },
    },
    {
        // inspector: the encryption + who-can-reveal explanation
        file: 'password-settings', url: `/apps/builder/${APP_ID}/form`, waitFor: '.field',
        prep: async (p) => {
            await p.click('::-p-xpath(//div[contains(@class,"field")][.//span[contains(@class,"lbl")][contains(text(),"発注サイトのパスワード")]])')
            await p.waitForSelector('.insp-col')
            await sleep(500)
        },
    },
    {
        // app-list search suggest: the レコードから検索 row + matching apps (the grid stays unfiltered)
        file: 'portal-search', url: '/apps', waitFor: '.fc-card',
        prep: async (p) => {
            const input = await p.waitForSelector('input[name="postSearchBar"]')
            await input.click()
            await input.type('備品', { delay: 40 })
            await p.waitForSelector('.fc-suggest')
            await sleep(500)
        },
    },
    {
        // cross-app record search results. 'ACME' hits a lookup label in the demo app, so the shot
        // shows a value match rather than an app-name match
        file: 'record-search', url: '/apps', waitFor: '.fc-card',
        prep: async (p) => {
            const input = await p.waitForSelector('input[name="postSearchBar"]')
            await input.click()
            await input.type('ACME', { delay: 40 })
            await p.waitForSelector('.fc-sg-records')
            await p.click('.fc-sg-records')
            await p.waitForSelector('.rs-hit')
            await sleep(600)
        },
    },
    // ツール root: the grid of tool kinds
    { file: 'builder-tools', url: `/apps/builder/${APP_ID}/tools`, waitFor: '.tt-card' },
    {
        // the 集計スロット editor, opened on the demo app's existing slot
        file: 'slot-editor', url: `/apps/builder/${APP_ID}/tools/aggregation`, waitFor: '.tt-row',
        prep: async (p) => {
            await p.click('.tt-row .tt-btn')
            await p.waitForSelector('.se-item')
            await sleep(500)
        },
    },
    // record list with the slot strip (the demo app has 合計/平均 configured)
    { file: 'records-slot', url: `/apps/records/${APP_ID}`, waitFor: '.rv-slot' },
    { file: 'builder-status', url: `/apps/builder/${APP_ID}/status`, waitFor: '::-p-text(ステータス)' },
    { file: 'builder-view', url: `/apps/builder/${APP_ID}/view`, waitFor: '::-p-text(ビュー)' },
    { file: 'builder-permission', url: `/apps/builder/${APP_ID}/permission`, waitFor: '::-p-text(アクセス権)' },
    { file: 'builder-audit', url: `/apps/builder/${APP_ID}/audit`, waitFor: '::-p-text(監査ログ)' },
]

const main = async () => {
    mkdirSync(OUT, { recursive: true })
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        args: ['--window-size=1440,900'],
    })
    const page = await browser.newPage()
    await page.setViewport({ width: 1360, height: 850, deviceScaleFactor: 2 })
    // docs screenshots are always light theme (headless Chrome defaults prefers-color-scheme: dark).
    // app.ts theme codes: '1' = dark, '2' = light, '0'/absent = follow prefers-color-scheme.
    await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }])
    await page.evaluateOnNewDocument(() => localStorage.setItem('dark', '2'))

    // local-only session login (route exists only in APP_ENV=local)
    await page.goto(`${BASE}/dev_screenshot_login/${LOGIN_USER}`, { waitUntil: 'networkidle2' })

    // SHOT_ONLY=a,b limits the run. Use it when re-shooting after a UI change: portal-bell and
    // portal-pending depend on transient seeded state (see the header), so a blind full run can
    // overwrite good shots with empty ones.
    const only = (process.env.SHOT_ONLY ?? '').split(',').map((s) => s.trim()).filter(Boolean)
    const queue = only.length ? SHOTS.filter((s) => only.includes(s.file)) : SHOTS
    if (only.length) {
        const missing = only.filter((n) => !SHOTS.some((s) => s.file === n))
        if (missing.length) console.error(`unknown shot name(s): ${missing.join(', ')}`)
        console.log(`SHOT_ONLY -> ${queue.map((s) => s.file).join(', ')}`)
    }

    let fail = 0
    for (const shot of queue) {
        try {
            await page.goto(`${BASE}${shot.url}`, { waitUntil: 'networkidle2' })
            // the dashboard warning popup (未承認日報 etc.) floats over the toolbar and would
            // intercept clicks / dirty the shots — hide it AND its fixed wrapper (.w-outer),
            // which otherwise still swallows mouse events over the toolbar
            await page.addStyleTag({ content: '.warning,.w-outer{display:none!important}' })
            await page.waitForSelector(shot.waitFor, { timeout: 15000 })
            await sleep(600) // fonts / async data settle
            if (shot.prep) await shot.prep(page)
            await page.screenshot({ path: path.join(OUT, `${shot.file}.png`) })
            console.log(`ok   ${shot.file}.png`)
        } catch (e) {
            fail++
            console.error(`FAIL ${shot.file}: ${e.message}`)
        }
    }

    await browser.close()
    if (fail) process.exit(1)
}

main()
