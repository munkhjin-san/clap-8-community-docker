<template>
    <div class="help-docs">
        <!-- Document selector (root) -->
        <template v-if="!activeDoc">
            <header class="help-docs-hero">
                <h1 class="help-docs-hero-title">ヘルプドキュメント</h1>
                <p class="help-docs-hero-lead">確認したいドキュメントを選んでください</p>
            </header>

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

        <!-- Selected document topic index -->
        <template v-else>
            <header class="help-docs-hero">
                <button type="button" class="help-docs-back" @click="backToSelector">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                    ドキュメント一覧
                </button>
                <h1 class="help-docs-hero-title">{{ activeDoc.title }}</h1>
                <p class="help-docs-hero-lead">{{ activeDoc.description }}</p>
                <div class="help-docs-search">
                    <input
                        v-model="query"
                        type="search"
                        class="help-docs-search-input"
                        placeholder="検索する（例：レコード、アクセス権、フォーム）"
                        aria-label="ヘルプを検索"
                    >
                    <button type="button" class="help-docs-search-btn" aria-label="検索" @click="focusSearch">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </button>
                </div>
            </header>

            <div class="help-docs-body">
                <p v-if="filteredTopics.length === 0" class="help-docs-empty">該当するヘルプが見つかりませんでした。</p>

                <div v-else class="help-docs-grid">
                    <div
                        v-for="(topic, index) in filteredTopics"
                        :key="index"
                        class="help-docs-card"
                        :class="{ open: isOpen(index), expandable: hasChildren(topic) }"
                    >
                        <button
                            type="button"
                            class="help-docs-card-main"
                            @click="onTopicClick(index, topic)"
                        >
                            <span class="help-docs-card-title">{{ topic.title }}</span>
                            <span v-if="hasChildren(topic)" class="help-docs-card-chevron" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            </span>
                        </button>
                        <ul v-if="hasChildren(topic) && isOpen(index)" class="help-docs-card-list">
                            <li v-for="(child, cIndex) in topic.children" :key="cIndex">
                                <button
                                    type="button"
                                    class="help-docs-card-link"
                                    :disabled="!child.to"
                                    @click="openArticle(child)"
                                >
                                    {{ child.title }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
    findHelpDocument,
    helpDocuments,
    type HelpDocArticle,
    type HelpDocTopic,
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
const openKeys = ref<Set<number>>(new Set())
const searchInputSelector = '.help-docs-search-input'

const docId = computed(() => {
    const raw = route.params.docId
    return typeof raw === 'string' ? raw : undefined
})

const activeDoc = computed(() => findHelpDocument(docId.value))

watch(docId, () => {
    query.value = ''
    openKeys.value = new Set()
    if (docId.value && !activeDoc.value) {
        router.replace({ path: '/help/documentation' })
    }
}, { immediate: true })

const hasChildren = (topic: HelpDocTopic) => Boolean(topic.children?.length)

const isOpen = (index: number) => openKeys.value.has(index)

const toggle = (index: number) => {
    const next = new Set(openKeys.value)
    if (next.has(index)) next.delete(index)
    else next.add(index)
    openKeys.value = next
}

const matches = (text: string, kw: string) => text.toLowerCase().includes(kw)

const filteredTopics = computed(() => {
    const doc = activeDoc.value
    if (!doc) return []
    const kw = query.value.trim().toLowerCase()
    if (!kw) return doc.topics
    return doc.topics.filter((topic) => {
        if (matches(topic.title, kw)) return true
        return topic.children?.some((child) => matches(child.title, kw))
    })
})

const openDocument = (id: string) => {
    router.push({ name: 'help-documentation', params: { docId: id } })
}

const backToSelector = () => {
    router.push({ path: '/help/documentation' })
}

const onTopicClick = (index: number, topic: HelpDocTopic) => {
    if (hasChildren(topic)) {
        toggle(index)
        return
    }
    openArticle(topic)
}

const openArticle = (item: HelpDocArticle | HelpDocTopic) => {
    if (!item.to) return
    if (item.to.startsWith('/')) router.push(item.to)
    else router.push({ name: item.to })
}

const focusSearch = () => {
    document.querySelector<HTMLInputElement>(searchInputSelector)?.focus()
}
</script>

<style scoped>
.help-docs {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    color: var(--primary-color);
}

.help-docs-hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 36px 24px 28px;
    background: var(--bg3);
    border-bottom: 1px solid var(--formBorder);
}

.help-docs-back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    align-self: flex-start;
    margin: 0 0 4px;
    padding: 4px 2px;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    font-size: 12px;
    opacity: 0.65;
    cursor: pointer;
}

.help-docs-back:hover {
    opacity: 1;
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

.help-docs-search {
    display: flex;
    width: min(640px, 100%);
    background: var(--background-color);
    border: 1px solid var(--formBorder);
    border-radius: 8px;
    overflow: hidden;
}

.help-docs-search-input {
    flex: 1;
    min-width: 0;
    height: 44px;
    padding: 0 14px;
    border: 0;
    outline: none;
    background: transparent;
    color: var(--primary-color);
    font-size: 14px;
}

.help-docs-search-input::placeholder {
    color: var(--primary-color);
    opacity: 0.4;
}

.help-docs-search-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    border: 0;
    border-left: 1px solid var(--formBorder);
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.15s, background 0.15s;
}

.help-docs-search-btn:hover {
    opacity: 1;
    background: var(--bg3);
}

.help-docs-body {
    flex: 1;
    width: min(960px, 100%);
    margin: 0 auto;
    padding: 32px 24px 48px;
}

.help-docs-empty {
    margin: 40px 0;
    text-align: center;
    font-size: 13px;
    opacity: 0.55;
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

.help-docs-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.help-docs-card {
    border: 1px solid var(--formBorder);
    border-radius: 8px;
    background: var(--background-color);
    overflow: hidden;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.help-docs-card:hover {
    border-color: color-mix(in srgb, var(--primary-color) 35%, var(--formBorder));
}

.help-docs-card.open {
    box-shadow: 0 1px 0 color-mix(in srgb, var(--primary-color) 8%, transparent);
}

.help-docs-card-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    width: 100%;
    min-height: 52px;
    padding: 12px 16px;
    border: 0;
    background: transparent;
    color: inherit;
    text-align: left;
    cursor: pointer;
}

.help-docs-card-title {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
}

.help-docs-card-chevron {
    display: flex;
    color: var(--primary-color);
    opacity: 0.45;
    transition: transform 0.15s, opacity 0.15s;
    flex-shrink: 0;
}

.help-docs-card.open .help-docs-card-chevron {
    transform: rotate(180deg);
    opacity: 0.75;
}

.help-docs-card-list {
    list-style: none;
    margin: 0;
    padding: 0 8px 10px;
    border-top: 1px solid var(--formBorder);
}

.help-docs-card-link {
    display: block;
    width: 100%;
    padding: 9px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--primary-color);
    font-size: 13px;
    text-align: left;
    line-height: 1.45;
    cursor: default;
    opacity: 0.75;
}

.help-docs-card-link:not(:disabled) {
    cursor: pointer;
    opacity: 0.9;
}

.help-docs-card-link:not(:disabled):hover {
    background: var(--bg3);
    opacity: 1;
}

.help-docs-card-link:disabled {
    cursor: default;
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

    .help-docs-doc-grid,
    .help-docs-grid {
        grid-template-columns: 1fr;
    }
}
</style>
