<template>
    <div class="refresh-applications">
        <section class="overview-panel">
            <div class="metric-grid">
                <article class="metric-card">
                    <span>総申請</span>
                    <strong>{{ posts.total }}</strong>
                </article>
                <article class="metric-card">
                    <span>表示中 未対応</span>
                    <strong>{{ visiblePendingCount }}</strong>
                </article>
                <article class="metric-card">
                    <span>表示中 金額</span>
                    <strong>{{ formatCurrency(visibleAmount) }}</strong>
                </article>
            </div>
        </section>

        <section class="control-panel">
            <label class="search-box">
                <input
                    v-model.trim="searchWord"
                    type="text"
                    placeholder="社員名 / タイトル / 内容"
                >
            </label>

            <div class="status-switch">
                <button
                    v-for="status in statusOptions"
                    :key="status.key"
                    type="button"
                    :class="['status-pill', { active: selectedStatus === status.key }]"
                    @click="selectedStatus = status.key"
                >
                    {{ status.label }}
                </button>
            </div>
        </section>

        <div class="queue-panel">
            <div v-if="loading" class="empty-state">
                <strong>読込中</strong>
            </div>

            <div v-else-if="!filteredPosts.length" class="empty-state">
                <strong>該当データなし</strong>
            </div>

            <div v-else class="application-list">
                <article
                    v-for="post in filteredPosts"
                    :key="post.id"
                    class="application-card"
                    :class="{ pending: post.status_flag === 0 }"
                >
                    <div class="card-top">
                        <div class="identity-block">
                            <UserPanel :user="post.user" size="30" disable-instant imgClass="userNormalIcon" />
                            <div>
                                <p class="employee-name">{{ post.user.name }}</p>
                                <p class="meta-row">
                                    {{ formatDate(post.created_at) }}
                                    <span class="dot">•</span>
                                    ID {{ post.id }}
                                </p>
                            </div>
                        </div>

                        <div class="amount-block">
                            <span :class="['status-badge', statusClass(post.status_flag)]">
                                {{ statusMap(post.status_flag) }}
                            </span>
                            <strong>{{ formatCurrency(post.refresh_amount) }}</strong>
                            <p v-if="approvalAmountLabel(post)" class="approval-note">{{ approvalAmountLabel(post) }}</p>
                        </div>
                    </div>

                    <div class="card-main">
                        <div class="main-copy">
                            <h3>{{ post.title || 'タイトル未入力' }}</h3>
                            <p class="content-preview">{{ contentPreview(post.content) }}</p>
                        </div>

                        <div class="info-grid">
                            <div class="info-box">
                                <span>写真</span>
                                <strong>{{ post.files.length }}</strong>
                            </div>
                            <div class="info-box">
                                <span>領収</span>
                                <strong>{{ post.receipts.length }}</strong>
                            </div>
                            <div class="info-box">
                                <span>状態</span>
                                <strong>{{ checklistLabel(post) }}</strong>
                            </div>
                            <div class="info-box">
                                <span>現在保有額</span>
                                <strong>{{ formatCurrency(post.current_balance) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button type="button" class="ghost-button" @click="toggleDetail(post.id)">
                            {{ openedDetails.includes(post.id) ? '閉じる' : '詳細' }}
                        </button>
                        <div class="inline-actions">
                            <button
                                v-if="post.status_flag === 0"
                                type="button"
                                class="primary-button"
                                :disabled="activeActionId === post.id"
                                @click="handleRefresh(post)"
                            >
                                要調整へ回す
                            </button>
                            <button
                                type="button"
                                class="danger-button"
                                :disabled="activeActionId === post.id"
                                @click="deleteRefresh(post.id)"
                            >
                                削除
                            </button>
                        </div>
                    </div>

                    <Transition name="slidePop">
                        <div v-if="openedDetails.includes(post.id)" class="detail-panel">
                            <div class="detail-column">
                                <p class="detail-label">内容</p>
                                <p class="detail-text">{{ post.content || '内容未入力' }}</p>
                            </div>

                            <div class="detail-column">
                                <div>
                                    <p class="detail-label">写真</p>
                                    <PostFiles v-if="post.files.length" :items="post.files" />
                                    <p v-else class="attachment-empty">なし</p>
                                </div>

                                <div>
                                    <p class="detail-label">領収</p>
                                    <PostFiles
                                        v-if="post.receipts.length"
                                        path="/post_receipts"
                                        :items="post.receipts"
                                    />
                                    <p v-else class="attachment-empty">なし</p>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </article>
            </div>
        </div>

        <PostSearchPager
            class="pager"
            style="margin: 0;"
            :possiblePage="posts.last_page"
            :activePath="posts.current_page"
            @setNavi="(index) => getRefreshPosts(posts.current_page + index)"
            @setActivePage="(index) => getRefreshPosts(index)"
        />
    </div>
</template>

<script lang="ts" setup>
import UserPanel from '@/components/Global/UserPanel.vue';
import PostSearchPager from '@/components/Post/PostSearchPager.vue';
import PostFiles from '@/components/Post/PostFiles.vue';
import { useApi } from '@/composables/api';
import { Post } from '@/interface/postInterface';
import { DateTime } from 'luxon';
import { computed, onMounted, ref, watch } from 'vue';

interface PostPagination {
    data: RefreshApplicationPost[];
    current_page: number;
    last_page: number;
    total: number;
}

interface RefreshApplicationPost extends Post {
    current_balance: number;
    approved_refresh_amount: number | null;
}

type RefreshStatus = 'all' | 'pending' | 'review' | 'approved';

const api = useApi();
const loading = ref(false);
const posts = ref<PostPagination>({
    data: [],
    current_page: 1,
    last_page: 0,
    total: 0,
});
const searchWord = ref('');
const selectedStatus = ref<RefreshStatus>('all');
const openedDetails = ref<number[]>([]);
const activeActionId = ref<number | null>(null);

const statusOptions: { key: RefreshStatus; label: string }[] = [
    { key: 'all', label: 'すべて' },
    { key: 'pending', label: '申請中' },
    { key: 'review', label: '確認待ち' },
    { key: 'approved', label: '対応済み' },
];

const numericAmount = (value: string | number | null | undefined) => {
    if (value == null) return 0;
    const normalized = Number(String(value).replace(/[^\d.-]/g, ''));
    return Number.isFinite(normalized) ? normalized : 0;
};

const formatCurrency = (value: string | number | null | undefined) => {
    return `${new Intl.NumberFormat('ja-JP').format(numericAmount(value))}円`;
};

const formatDate = (value: string) => {
    const date = DateTime.fromISO(value);
    return date.isValid ? date.toFormat('yyyy.MM.dd') : value;
};

const statusMap = (status: number) => {
    if (status === 0) return '申請中';
    if (status === 2) return '確認待ち';
    return '対応済み';
};

const statusClass = (status: number) => {
    if (status === 0) return 'pending';
    if (status === 2) return 'review';
    return 'approved';
};

const contentPreview = (content: string) => {
    if (!content) return '内容未入力';
    return content.length > 90 ? `${content.slice(0, 90)}...` : content;
};

const checklistLabel = (post: Post) => {
    if (!post.receipts.length) return '領収不足';
    if (!post.files.length) return '写真不足';
    return '確認可';
};

const approvalAmountLabel = (post: RefreshApplicationPost) => {
    if (post.approved_refresh_amount == null) return '';
    if (numericAmount(post.refresh_amount) === numericAmount(post.approved_refresh_amount)) return '';
    return `承認利用 ${formatCurrency(post.approved_refresh_amount)}`;
};

const filteredPosts = computed(() => {
    if (!searchWord.value) return posts.value.data;
    const keyword = searchWord.value.toLowerCase();

    return posts.value.data.filter((post) => {
        return [
            post.user.name,
            post.title,
            post.content,
            post.refresh_amount,
        ].some((value) => String(value ?? '').toLowerCase().includes(keyword));
    });
});

const visiblePendingCount = computed(() => {
    return filteredPosts.value.filter((post) => post.status_flag === 0).length;
});

const visibleAmount = computed(() => {
    return filteredPosts.value.reduce((sum, post) => {
        return sum + numericAmount(post.refresh_amount);
    }, 0);
});

const toggleDetail = (id: number) => {
    const index = openedDetails.value.findIndex((value) => value === id);
    if (index === -1) {
        openedDetails.value.push(id);
        return;
    }
    openedDetails.value.splice(index, 1);
};

const getRefreshPosts = async (page = 1) => {
    const status =
        selectedStatus.value === 'all'
            ? []
            : [selectedStatus.value === 'pending' ? 0 : selectedStatus.value === 'review' ? 2 : 1];

    const data = await api.get(
        '/refresh/posts',
        {
            status,
            page,
        },
        {
            loadingRef: loading,
        },
    );

    if (data) {
        posts.value = data;
        openedDetails.value = [];
    }
};

const handleRefresh = async (post: RefreshApplicationPost) => {
    activeActionId.value = post.id;
    try {
        await api.patch(`/refresh/posts/${post.id}/approve`, null, {
            ask: 'この申請を付与管理の確認待ちへ回しますか？',
            toast: '付与管理の確認待ちへ移動しました。',
        });
        await getRefreshPosts(posts.value.current_page);
    } catch (_error) {
        return;
    } finally {
        activeActionId.value = null;
    }
};

const deleteRefresh = async (id: number) => {
    activeActionId.value = id;
    try {
        await api.del(`/refresh/posts/${id}`, null, {
            ask: 'この申請を削除しますか？',
            toast: '申請を削除しました。',
        });
        await getRefreshPosts(posts.value.current_page);
    } finally {
        activeActionId.value = null;
    }
};

watch(selectedStatus, () => {
    getRefreshPosts();
});

onMounted(() => {
    getRefreshPosts();
});
</script>

<style lang="scss" scoped>
input {
    box-sizing: border-box !important;
}
.refresh-applications {
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 0 18px 18px;
    color: var(--primary-color);
}

.overview-panel {
    border-radius: 8px;
    background: var(--background-color);
    padding: 10px 12px;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.metric-card {
    padding: 10px 12px;
    border-radius: 6px;
    background: var(--bg3);
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.metric-card span {
    font-size: 11px;
    color: var(--text2);
}

.metric-card strong {
    font-size: 18px;
    line-height: 1;
}

.control-panel {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    padding: 10px 12px;
    border-radius: 8px;
    background: var(--background-color);
}

.search-box {
    flex: 1 1 240px;
}

.search-box input {
    width: 100%;
    height: 36px;
    border-radius: 6px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    padding: 0 12px;
}

.status-switch {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.status-pill {
    height: 32px;
    padding: 0 12px;
    border-radius: 4px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    cursor: pointer;
    font-size: 12px;
}

.status-pill.active {
    background: #4b4b4b;
    border-color: #4b4b4b;
    color: #fff;
}

.queue-panel {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding-right: 4px;
}

.application-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.application-card {
    background: var(--background-color);
    border-radius: 8px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.application-card.pending {
    box-shadow: inset 3px 0 0 #4b4b4b;
}

.card-top,
.card-main,
.card-actions {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.identity-block {
    display: flex;
    gap: 10px;
    align-items: center;
}

.employee-name {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
}

.meta-row {
    margin: 4px 0 0;
    color: var(--text2);
    font-size: 11px;
}

.dot {
    margin: 0 6px;
}

.amount-block {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: end;
}

.amount-block strong {
    font-size: 18px;
    line-height: 1;
}

.approval-note {
    margin: 0;
    font-size: 11px;
    color: var(--text2);
}

.status-badge {
    min-width: 78px;
    text-align: center;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
}

.status-badge.pending {
    background: rgba(106, 127, 173, 0.18);
    color: #7e98cf;
}

.status-badge.review {
    background: rgba(184, 140, 74, 0.12);
    color: #b07a22;
}

.status-badge.approved {
    background: rgba(68, 140, 98, 0.12);
    color: #2a6f45;
}

.main-copy {
    flex: 1;
    min-width: 0;
}

.card-main h3 {
    margin: 0 0 6px;
    font-size: 15px;
}

.content-preview {
    margin: 0;
    color: var(--text2);
    font-size: 13px;
    line-height: 1.6;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(88px, 1fr));
    gap: 6px;
    min-width: 300px;
}

.info-box {
    padding: 9px 10px;
    border-radius: 6px;
    background: var(--bg3);
    display: flex;
    flex-direction: column;
    gap: 4px;
    justify-content: center;
}

.info-box span {
    font-size: 11px;
    color: var(--text2);
}

.info-box strong {
    font-size: 14px;
}

.card-actions {
    align-items: center;
    border-top: 1px solid var(--formBorder);
    padding-top: 10px;
}

.inline-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.ghost-button,
.primary-button,
.danger-button {
    height: 34px;
    border-radius: 6px;
    padding: 0 12px;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 12px;
}

.ghost-button {
    background: transparent;
    border-color: var(--formBorder);
    color: var(--primary-color);
}

.primary-button {
    background: #4b4b4b;
    border-color: #4b4b4b;
    color: #fff;
}

.danger-button {
    background: rgba(184, 74, 74, 0.1);
    color: #a33d3d;
    border-color: rgba(184, 74, 74, 0.18);
}

.ghost-button:disabled,
.primary-button:disabled,
.danger-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.detail-panel {
    display: grid;
    grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
    gap: 14px;
    border-top: 1px solid var(--formBorder);
    padding-top: 12px;
}

.detail-column {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.detail-label {
    margin: 0 0 8px;
    font-size: 11px;
    color: var(--text2);
}

.detail-text {
    margin: 0;
    font-size: 13px;
    line-height: 1.7;
}

.attachment-empty {
    margin: 0;
    color: var(--text2);
    font-size: 12px;
}

.empty-state {
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: var(--background-color);
    border: 1px dashed var(--formBorder);
    color: var(--text2);
}

.pager {
    padding-bottom: 4px;
}

@media screen and (max-width: 1080px) {
    .metric-grid,
    .detail-panel,
    .card-main {
        grid-template-columns: 1fr;
        display: grid;
    }

    .info-grid {
        min-width: 0;
    }
}

@media screen and (max-width: 720px) {
    .refresh-applications {
        padding: 0 12px 12px;
    }

    .overview-panel,
    .control-panel,
    .application-card {
        padding: 10px;
    }

    .metric-grid,
    .info-grid {
        grid-template-columns: 1fr;
    }

    .card-top,
    .card-actions {
        flex-direction: column;
    }

    .amount-block {
        align-items: start;
    }

    .inline-actions {
        width: 100%;
    }

    .ghost-button,
    .primary-button,
    .danger-button {
        width: 100%;
    }
}
</style>
