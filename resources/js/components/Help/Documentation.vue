<template>
    <div class="help-docs" :class="{ 'is-doc': activeDoc }">
        <!-- Document selector (root) -->
        <template v-if="!activeDoc">
            <div class="help-docs-body">
                <div class="help-docs-doc-grid">
                    <button
                        v-for="doc in helpDocuments"
                        :key="doc.id"
                        type="button"
                        class="help-docs-doc-card"
                        @click="openDocument(doc.id)"
                    >
                        <span class="help-docs-doc-icon" aria-hidden="true">
                            <AppDocIcon />
                        </span>
                        <span class="help-docs-doc-body">
                            <span class="help-docs-doc-title">{{ doc.title }}</span>
                            <span class="help-docs-doc-desc">{{ doc.description }}</span>
                        </span>
                        <span class="help-docs-doc-arrow" aria-hidden="true">›</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Selected document: orthodox docs layout — left topic nav, right article content -->
        <template v-else>
            <aside class="hd-side" :class="{ open: tocOpen }">
                <div class="hd-side-head">
                    <button type="button" class="hd-back" @click="backToSelector">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6" />
                        </svg>
                        ドキュメント一覧
                    </button>
                    <div class="hd-doc-name">
                        <span class="hd-doc-ico" aria-hidden="true"><AppDocIcon /></span>
                        {{ activeDoc.title }}
                    </div>
                    <div class="hd-search-wrap">
                        <svg class="hd-search-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                        <input
                            v-model="query"
                            type="search"
                            class="hd-search"
                            placeholder="トピックを検索"
                            aria-label="トピックを検索"
                        >
                    </div>
                </div>
                <nav class="hd-nav">
                    <div v-for="topic in filteredNav" :key="topic.id" class="hd-group">
                        <!-- leaf topic = its own article -->
                        <button
                            v-if="!topic.children?.length"
                            type="button"
                            class="hd-link hd-link-solo"
                            :class="{ on: currentId === topic.id }"
                            @click="selectArticle(topic.id)"
                        >{{ topic.title }}</button>
                        <template v-else>
                            <div class="hd-group-title">{{ topic.title }}</div>
                            <div class="hd-group-items">
                                <button
                                    v-for="article in topic.children"
                                    :key="article.id"
                                    type="button"
                                    class="hd-link"
                                    :class="{ on: currentId === article.id }"
                                    @click="selectArticle(article.id)"
                                >{{ article.title }}</button>
                            </div>
                        </template>
                    </div>
                    <p v-if="!filteredNav.length" class="hd-nav-empty">該当するトピックがありません</p>
                </nav>
            </aside>

            <!-- mobile: scrim behind the off-canvas nav -->
            <div v-if="tocOpen" class="hd-scrim" @click="tocOpen = false"></div>

            <main ref="mainEl" class="hd-main">
                <button type="button" class="hd-toc-btn" @click="tocOpen = true">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="14" y2="17"/></svg>
                    目次
                </button>

                <article v-if="current" :key="current.article.id" class="hd-article">
                    <div class="hd-crumb">
                        <span>{{ activeDoc.title }}</span>
                        <template v-if="current.topic.id !== current.article.id">
                            <span class="hd-crumb-sep">›</span>
                            <span>{{ current.topic.title }}</span>
                        </template>
                        <span class="hd-crumb-sep">›</span>
                        <span class="hd-crumb-here">{{ current.article.title }}</span>
                    </div>
                    <header class="hd-head">
                        <h1 class="hd-title">{{ current.article.title }}</h1>
                    </header>

                    <template v-if="current.article.body?.length">
                        <template v-for="(block, bi) in current.article.body" :key="bi">
                            <h2 v-if="block.type === 'h'" class="hd-h">{{ block.text }}</h2>
                            <p v-else-if="block.type === 'p'" class="hd-p">{{ block.text }}</p>
                            <ul v-else-if="block.type === 'ul'" class="hd-ul">
                                <li v-for="(item, ii) in block.items" :key="ii">{{ item }}</li>
                            </ul>
                            <div v-else-if="block.type === 'note'" class="hd-note">
                                <span class="hd-note-label">補足</span>
                                <span class="hd-note-text">{{ block.text }}</span>
                            </div>
                            <img v-else-if="block.type === 'img'" class="hd-img" :src="block.src" :alt="block.alt ?? ''">
                        </template>
                    </template>
                    <div v-else class="hd-placeholder">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" />
                            <path d="M13 2v7h7" />
                        </svg>
                        <span>このページは準備中です。</span>
                    </div>

                    <nav class="hd-pager">
                        <button v-if="prevArticle" type="button" class="hd-pager-btn" @click="selectArticle(prevArticle.article.id)">
                            <span class="hd-pager-dir">‹ 前へ</span>
                            <span class="hd-pager-title">{{ prevArticle.article.title }}</span>
                        </button>
                        <span v-else></span>
                        <button v-if="nextArticle" type="button" class="hd-pager-btn next" @click="selectArticle(nextArticle.article.id)">
                            <span class="hd-pager-dir">次へ ›</span>
                            <span class="hd-pager-title">{{ nextArticle.article.title }}</span>
                        </button>
                    </nav>
                </article>
            </main>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
    findHelpDocument,
    flattenHelpArticles,
    helpDocuments,
} from 'assets/help/help.documentation'

/** Same SVG as SideMenu「アプリ」route. */
const AppDocIcon = defineComponent({
    name: 'AppDocIcon',
    setup() {
        return () => h('svg', {
            xmlns: 'http://www.w3.org/2000/svg',
            width: '28',
            height: '26',
            viewBox: '0 0 31.91745 29.06039',
            fill: 'currentColor',
        }, [
            h('path', {
                d: 'M28.3784,17.98843c-.20726-.08255-.33136-.29179-.30513-.51334.00015-.0013.00031-.00259.00046-.00389.11717-1.04865.07001-2.14202-.09717-3.18336-.01592-.13641-.06282-.39613-.09503-.52807-.13395-.68726-.35798-1.41362-.64503-2.05267-.7143-1.65654-1.96391-3.01857-3.33762-4.13222-.60618-.51334-1.28103-.94207-1.99338-1.29526-.16739-.08299-.27298-.25718-.26677-.44391.00171-.05131.0026-.1027.00262-.15415.0312-5.06203-6.04909-7.5754-9.6318-4.01094-1.19352,1.2183-1.67982,2.72544-1.60494,4.17672.0098.18993-.09295.36899-.26364.45287-2.41726,1.18789-4.4532,3.24905-5.43454,5.7688-.38072,1.0287-.59306,2.12819-.68007,3.21511-.07692.71421-.0834,1.4407-.01855,2.16016.01961.21752-.11296.41762-.31805.4927-.73765.27006-1.4449.70875-2.0749,1.33555-3.03344,3.0964-1.51308,8.06074,2.23265,9.22363.54761.17002,1.26193.24856,1.77823.24856s1.42757-.13691,2.10011-.39261c.6542-.24025,1.24692-.61581,1.75162-1.08672.1649-.15386.41124-.17943.59944-.05514,1.75836,1.16122,3.87763,1.83354,5.97678,1.85014,1.33841.00112,2.65986-.34564,3.89833-.82311.69471-.25382,1.3644-.57726,1.99221-.96669.18274-.11335.41729-.08634.57655.05816.55962.50778,1.23125.90592,1.99741,1.14379.54761.17002,1.26193.24858,1.77823.24858s1.42757-.13693,2.10011-.39264c2.0663-.75879,3.52001-2.86679,3.52081-5.06865.01581-2.56442-1.53707-4.47409-3.53892-5.27141ZM14.13406,3.79077c1.00061-1.01888,2.77199-1.01956,3.77405-.00194,1.40332,1.37643.91387,3.51976-.57562,4.41865-.37792.22807-1.0684.36899-1.56143.33004-.49302-.03895-1.22249-.34905-1.72432-.88722-1.00922-1.04989-.97828-2.87006.08731-3.85953ZM6.93782,25.80937c-.37792.22807-1.0684.36899-1.56143.33004s-1.22249-.34905-1.72432-.88722c-1.00922-1.04989-.97828-2.87006.0873-3.85953,1.00061-1.01889,2.772-1.01956,3.77406-.00194,1.40332,1.37643.91387,3.51976-.57562,4.41865ZM17.61638,26.22666c-2.3407.19026-4.37003.00483-6.39274-.94253-.17855-.08363-.26789-.28481-.21003-.4733.15129-.49289.23287-1.00746.23306-1.52739.01856-3.01011-2.12406-5.11827-4.61738-5.58384-.18251-.03408-.31818-.1912-.31932-.37686-.00269-.4376.01612-.87546.05107-1.31233.15139-1.29947.64675-2.53644,1.25446-3.65903.3303-.62248.71762-1.25404,1.14565-1.81391.57047-.75263,1.25315-1.39724,2.01074-1.95342.1832-.1345.4392-.08509.56406.10481.66756,1.01526,1.66397,1.81971,2.90625,2.20539.54761.17,1.26193.24856,1.77823.24856s1.42757-.13693,2.10011-.39262c1.07203-.39367,1.97854-1.15087,2.60331-2.09564.12304-.18606.37319-.23905.55532-.11025.14108.09977.27942.20092.41091.29932.67272.51968,1.29582,1.11751,1.77687,1.82274.1448.18543.33305.50714.46283.70532.60668,1.01337,1.11522,2.10648,1.47063,3.2343.23882.86028.3603,1.74758.37825,2.63871.00397.19704-.13819.36504-.33316.39378-1.12138.16531-2.2256.68082-3.15996,1.61042-1.59399,1.62708-1.92912,3.76937-1.346,5.60535.05923.1865-.02734.38673-.20309.47276-.98149.48046-2.02634.82299-3.12007.89966ZM27.60848,25.78576c-.37793.22807-1.0684.36899-1.56143.33006-.49303-.03895-1.2225-.34907-1.72432-.88722-1.00922-1.04989-.97828-2.87008.0873-3.85953,1.00061-1.01888,2.77199-1.01956,3.77406-.00194,1.40332,1.37641.91387,3.51975-.57562,4.41863Z',
            }),
        ])
    },
})

const route = useRoute()
const router = useRouter()
const query = ref('')
const tocOpen = ref(false)
const mainEl = ref<HTMLElement | null>(null)

const docId = computed(() => {
    const raw = route.params.docId
    return typeof raw === 'string' ? raw : undefined
})
const articleId = computed(() => {
    const raw = route.params.articleId
    return typeof raw === 'string' ? raw : undefined
})

const activeDoc = computed(() => findHelpDocument(docId.value))
const flat = computed(() => (activeDoc.value ? flattenHelpArticles(activeDoc.value) : []))

// current article: the URL's articleId, else the document's first article
const current = computed(() => {
    if (!flat.value.length) return null
    return flat.value.find((f) => f.article.id === articleId.value) ?? flat.value[0]
})
const currentId = computed(() => current.value?.article.id ?? null)

const currentIndex = computed(() => flat.value.findIndex((f) => f.article.id === currentId.value))
const prevArticle = computed(() => (currentIndex.value > 0 ? flat.value[currentIndex.value - 1] : null))
const nextArticle = computed(() => (currentIndex.value >= 0 && currentIndex.value < flat.value.length - 1 ? flat.value[currentIndex.value + 1] : null))

watch(docId, () => {
    query.value = ''
    tocOpen.value = false
    if (docId.value && !activeDoc.value) {
        router.replace({ path: '/help/documentation' })
    }
}, { immediate: true })

// unknown article slug → drop back to the doc's first article (clean URL)
watch([articleId, activeDoc], () => {
    if (activeDoc.value && articleId.value && !flat.value.some((f) => f.article.id === articleId.value)) {
        router.replace({ name: 'help-documentation', params: { docId: docId.value } })
    }
}, { immediate: true })

// article changed → content pane back to top
watch(currentId, () => { mainEl.value?.scrollTo({ top: 0 }) })

const matches = (text: string, kw: string) => text.toLowerCase().includes(kw)

// sidebar nav filtered by the search box: a topic stays when it matches (all children kept)
// or when some children match (only those kept)
const filteredNav = computed(() => {
    const doc = activeDoc.value
    if (!doc) return []
    const kw = query.value.trim().toLowerCase()
    if (!kw) return doc.topics
    const out: typeof doc.topics = []
    for (const topic of doc.topics) {
        if (matches(topic.title, kw)) {
            out.push(topic)
            continue
        }
        const hits = topic.children?.filter((c) => matches(c.title, kw)) ?? []
        if (hits.length) out.push({ ...topic, children: hits })
    }
    return out
})

const openDocument = (id: string) => {
    router.push({ name: 'help-documentation', params: { docId: id } })
}

const backToSelector = () => {
    router.push({ path: '/help/documentation' })
}

const selectArticle = (id: string) => {
    tocOpen.value = false
    if (id === currentId.value) return
    router.push({ name: 'help-documentation', params: { docId: docId.value, articleId: id } })
}
</script>

<style scoped>
.help-docs {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    color: var(--primary-color);
}

/* doc view fills the container so the two panes scroll independently */
.help-docs.is-doc {
    flex-direction: row;
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

/* ---- selector (root) ---- */
.help-docs-hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 36px 24px 28px;
    background: var(--bg3);
    border-bottom: 1px solid var(--formBorder);
}

.help-docs-hero-title {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.01em;
    text-align: center;
}

.help-docs-hero-lead {
    margin: 0 0 6px;
    font-size: 13px;
    opacity: 0.65;
    text-align: center;
    max-width: 520px;
    line-height: 1.5;
}

.help-docs-body {
    flex: 1;
    margin: 0 auto;
    padding: 32px 24px 48px;
}

.help-docs-doc-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.help-docs-doc-card {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    padding: 18px 16px;
    border: 1px solid var(--formBorder);
    border-radius: 10px;
    background: var(--background-color);
    color: inherit;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.help-docs-doc-card:hover {
    border-color: color-mix(in srgb, var(--primary-color) 35%, var(--formBorder));
    box-shadow: 0 2px 10px color-mix(in srgb, var(--primary-color) 6%, transparent);
}

.help-docs-doc-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: var(--bg3);
    color: var(--primary-color);
    flex-shrink: 0;
}

.help-docs-doc-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    flex: 1;
}

.help-docs-doc-title {
    font-size: 16px;
    font-weight: 700;
}

.help-docs-doc-desc {
    font-size: 12px;
    line-height: 1.45;
    opacity: 0.6;
}

.help-docs-doc-arrow {
    font-size: 22px;
    opacity: 0.3;
    flex-shrink: 0;
}

/* ---- doc view: left nav ---- */
.hd-side {
    display: flex;
    flex-direction: column;
    width: 272px;
    flex-shrink: 0;
    min-height: 0;
    border-right: 1px solid var(--formBorder);
    /* soft panel tint so the nav reads as its own zone without going full-contrast */
    background: color-mix(in srgb, var(--bg3) 55%, var(--background-color));
}

.hd-side-head {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px 16px 14px;
    border-bottom: 1px solid var(--formBorder);
}

.hd-back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    align-self: flex-start;
    padding: 2px 0;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    font-size: 12px;
    opacity: 0.6;
    cursor: pointer;
}

.hd-back:hover {
    opacity: 1;
}

.hd-doc-name {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.3;
}

.hd-doc-ico {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    flex-shrink: 0;
}

.hd-doc-ico :deep(svg) {
    width: 17px;
    height: 16px;
}

.hd-search-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.hd-search-ico {
    position: absolute;
    left: 10px;
    opacity: 0.4;
    pointer-events: none;
}

.hd-search {
    box-sizing: border-box;
    width: 100%;
    height: 33px;
    padding: 0 10px 0 31px;
    border: 1px solid var(--formBorder);
    border-radius: 7px;
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 12.5px;
    outline: none;
    transition: border-color 0.15s;
}

.hd-search:focus {
    border-color: var(--primary-color);
}

.hd-search::placeholder {
    color: var(--primary-color);
    opacity: 0.4;
}

.hd-nav {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 14px 12px 28px;
}

.hd-group {
    margin-bottom: 14px;
}

.hd-group-title {
    padding: 4px 8px 6px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.07em;
    opacity: 0.55;
}

/* children hang off a thin rail under the group title (GitBook-style hierarchy) */
.hd-group-items {
    margin-left: 11px;
    padding-left: 7px;
    border-left: 1px solid var(--formBorder);
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.hd-link {
    position: relative;
    display: block;
    width: 100%;
    padding: 6px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--primary-color);
    font-size: 13px;
    line-height: 1.5;
    text-align: left;
    cursor: pointer;
    opacity: 0.72;
    transition: background 0.12s, opacity 0.12s;
}

.hd-link:hover {
    background: var(--bg3);
    opacity: 1;
}

.hd-link.on {
    background: var(--bg3);
    opacity: 1;
    font-weight: 600;
}

/* active accent bar — sits on the rail so the current page pops out of the list */
.hd-link.on::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 5px;
    bottom: 5px;
    width: 2px;
    border-radius: 2px;
    background: var(--primary-color);
}

/* leaf topics (own article): same weight as group titles so they scan as top-level */
.hd-link-solo {
    font-size: 13px;
    font-weight: 600;
    padding: 7px 8px;
    opacity: 0.85;
}

.hd-link-solo.on::before {
    left: 0;
}

.hd-link-solo.on {
    padding-left: 14px;
}

.hd-nav-empty {
    margin: 20px 8px;
    font-size: 12px;
    opacity: 0.5;
    text-align: center;
}

/* ---- doc view: content pane ---- */
.hd-main {
    flex: 1;
    min-width: 0;
    min-height: 0;
    overflow-y: auto;
    background: var(--background-color);
}

.hd-toc-btn {
    display: none;
}

.hd-article {
    max-width: 720px;
    padding: 36px 48px 72px;
    animation: hd-fade 0.18s ease;
}

@keyframes hd-fade {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: none; }
}

.hd-crumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
    margin-bottom: 18px;
    font-size: 12px;
    opacity: 0.55;
}

.hd-crumb-sep {
    font-size: 11px;
    opacity: 0.6;
}

.hd-crumb-here {
    opacity: 1;
}

.hd-head {
    margin-bottom: 26px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--formBorder);
}

.hd-title {
    margin: 0;
    font-size: 26px;
    font-weight: 700;
    line-height: 1.35;
    letter-spacing: 0.01em;
}

/* section headings: left accent bar (the standard Japanese docs idiom) */
.hd-h {
    margin: 38px 0 14px;
    padding: 2px 0 2px 12px;
    border-left: 3px solid var(--primary-color);
    font-size: 17px;
    font-weight: 600;
    line-height: 1.45;
}

.hd-p {
    margin: 0 0 16px;
    font-size: 14px;
    line-height: 2;
}

.hd-ul {
    margin: 0 0 16px;
    padding-left: 6px;
    font-size: 14px;
    line-height: 1.9;
    list-style: none;
}

.hd-ul li {
    position: relative;
    margin-bottom: 7px;
    padding-left: 18px;
}

/* square markers — quieter than the browser disc, matches the app's b/w tone */
.hd-ul li::before {
    content: '';
    position: absolute;
    left: 2px;
    top: 11px;
    width: 6px;
    height: 6px;
    border-radius: 2px;
    background: var(--primary-color);
    opacity: 0.35;
}

.hd-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 4px 0 18px;
    padding: 13px 16px;
    border: 1px solid var(--formBorder);
    border-radius: 8px;
    background: color-mix(in srgb, var(--bg3) 70%, var(--background-color));
    font-size: 13px;
    line-height: 1.8;
}

.hd-note-label {
    flex-shrink: 0;
    margin-top: 3px;
    padding: 1px 8px;
    border: 1px solid var(--formBorder);
    border-radius: 4px;
    background: var(--background-color);
    font-size: 11px;
    letter-spacing: 0.05em;
    opacity: 0.75;
    white-space: nowrap;
}

.hd-note-text {
    min-width: 0;
}

.hd-img {
    display: block;
    max-width: 100%;
    margin: 8px 0 20px;
    border: 1px solid var(--formBorder);
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.hd-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    margin: 12px 0 24px;
    padding: 48px 20px;
    border: 1px dashed var(--formBorder);
    border-radius: 10px;
    background: color-mix(in srgb, var(--bg3) 40%, var(--background-color));
    font-size: 13px;
    opacity: 0.55;
}

.hd-pager {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 52px;
    padding-top: 22px;
    border-top: 1px solid var(--formBorder);
}

.hd-pager-btn {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 0 1 46%;
    padding: 13px 16px;
    border: 1px solid var(--formBorder);
    border-radius: 9px;
    background: var(--background-color);
    color: var(--primary-color);
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}

.hd-pager-btn.next {
    text-align: right;
    margin-left: auto;
}

.hd-pager-btn:hover {
    background: var(--bg3);
    border-color: color-mix(in srgb, var(--primary-color) 40%, var(--formBorder));
}

.hd-pager-dir {
    font-size: 11px;
    opacity: 0.5;
}

.hd-pager-title {
    font-size: 13.5px;
    font-weight: 500;
    line-height: 1.45;
}

.hd-scrim {
    display: none;
}

@media (max-width: 720px) {
    .help-docs-hero {
        padding: 28px 16px 22px;
    }

    .help-docs-hero-title {
        font-size: 18px;
    }

    .help-docs-body {
        padding: 24px 16px 40px;
    }

    .help-docs-doc-grid {
        grid-template-columns: 1fr;
    }

    /* off-canvas nav */
    .hd-side {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 60;
        width: min(300px, 84vw);
        transform: translateX(-100%);
        transition: transform 0.2s ease;
        box-shadow: 4px 0 18px rgba(0, 0, 0, 0.14);
    }

    .hd-side.open {
        transform: translateX(0);
    }

    .hd-scrim {
        display: block;
        position: fixed;
        inset: 0;
        z-index: 55;
        background: rgba(0, 0, 0, 0.32);
    }

    .hd-toc-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 14px 16px 0;
        padding: 6px 12px;
        border: 1px solid var(--formBorder);
        border-radius: 6px;
        background: var(--background-color);
        color: var(--primary-color);
        font-size: 12.5px;
        cursor: pointer;
    }

    .hd-article {
        padding: 18px 16px 48px;
    }

    .hd-title {
        font-size: 20px;
    }
}
</style>
