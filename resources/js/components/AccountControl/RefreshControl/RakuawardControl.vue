<template>
    <div class="rakuaward-control">
        <section class="rakuaward-topbar">
            <div class="topbar-left">
                <MonthPickerNew v-model:year="year" v-model:month="month" left="0px" @setDate="onSetDate" />
                <span class="granted-counter">選択済み: {{ grantedCount }} / {{ limit }}</span>
                <button
                    type="button"
                    class="refund-all-button"
                    :disabled="!refundableCount || refunding || saving"
                    @click="refundRest"
                >
                    未選出を返金{{ refundableCount ? `（${refundableCount}件）` : '' }}
                </button>
            </div>
            <p class="topbar-note">上位5名に選ばれなかったノミネートは、チャージした金額がメンバーへ返金されます。</p>
        </section>

        <div class="rakuaward-list">
            <div v-if="loading" class="rakuaward-empty">読込中</div>
            <div v-else-if="!nominations.length" class="rakuaward-empty">この月の楽アワードノミネートはありません。</div>

            <article
                v-for="nomination in nominations"
                :key="nomination.id"
                :class="['rakuaward-card', { granted: nomination.granted, refunded: nomination.refunded }]"
            >
                <div class="card-main">
                    <div class="card-people">
                        <div class="people-block">
                            <span class="people-label">ノミネート者</span>
                            <div class="people-user" v-if="nomination.creator">
                                <UserPanel :user="nomination.creator" size="26" disable-instant imgClass="userNormalIcon" />
                                <span>{{ nomination.creator.name }}</span>
                            </div>
                        </div>
                        <svg class="people-arrow" viewBox="0 0 47 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
                        </svg>
                        <div class="people-block">
                            <span class="people-label">対象メンバー</span>
                            <div class="people-user" v-if="nomination.nominee">
                                <UserPanel :user="nomination.nominee" size="26" disable-instant imgClass="userNormalIcon" />
                                <span>{{ nomination.nominee.name }}</span>
                            </div>
                            <span v-else class="people-user muted">未設定</span>
                        </div>
                    </div>

                    <h3 class="card-title">{{ nomination.title }}</h3>
                    <p class="card-content">{{ nomination.content }}</p>
                    <p class="card-meta">{{ formatDate(nomination.created_at) }} ・ サポーター {{ nomination.supporter_count }}人</p>
                </div>

                <div class="card-side">
                    <div class="charged-amount">
                        <span>チャージ総額</span>
                        <strong>{{ formatCurrency(nomination.charged_amount) }}</strong>
                    </div>
                    <span v-if="nomination.granted" class="granted-badge">付与済み</span>
                    <span v-else-if="nomination.refunded" class="refunded-badge">返金済み</span>
                    <button
                        v-else
                        type="button"
                        class="grant-button"
                        :disabled="!canGrant(nomination) || saving"
                        @click="grant(nomination)"
                    >
                        リフレッシュへ付与
                    </button>
                    <p v-if="!nomination.granted && !nomination.refunded && nomination.charged_amount <= 0" class="side-note">チャージなし</p>
                    <p v-else-if="!nomination.granted && !nomination.refunded && grantedCount >= limit" class="side-note">今月の上限に達しました</p>
                </div>
            </article>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import { DateTime, MonthNumbers } from 'luxon';
import UserPanel from '@/components/Global/UserPanel.vue';
import MonthPickerNew from '@/components/Global/MonthPickerNew.vue';
import { useApi } from '@/composables/api';
import { User } from '@/interface/globalInterface';

type RakuawardNomination = {
    id: number;
    title: string;
    content: string;
    created_at: string | null;
    creator: User | null;
    nominee: User | null;
    charged_amount: number;
    supporter_count: number;
    granted: boolean;
    granted_at: string | null;
    refunded: boolean;
    refunded_at: string | null;
};

type RakuawardResponse = {
    year: number;
    month: number;
    limit: number;
    granted_count: number;
    refundable_count: number;
    nominations: RakuawardNomination[];
};

const api = useApi();
const loading = ref(false);
const saving = ref(false);
const refunding = ref(false);
const year = ref<number>(DateTime.now().year);
const month = ref<MonthNumbers>(DateTime.now().month);
const limit = ref(5);
const grantedCount = ref(0);
const refundableCount = ref(0);
const nominations = ref<RakuawardNomination[]>([]);

const fetchNominations = async () => {
    const data = await api.get('/refresh/rakuaward', {
        year: year.value,
        month: month.value,
    }, {
        loadingRef: loading,
        silent: true,
    }) as RakuawardResponse | null;

    if (!data) return;

    limit.value = data.limit;
    grantedCount.value = data.granted_count;
    refundableCount.value = data.refundable_count;
    nominations.value = data.nominations ?? [];
};

const onSetDate = (val: { year: number; month: MonthNumbers }) => {
    year.value = val.year;
    month.value = val.month;
    fetchNominations();
};

const canGrant = (nomination: RakuawardNomination) => {
    return !nomination.granted && nomination.charged_amount > 0 && grantedCount.value < limit.value;
};

const grant = async (nomination: RakuawardNomination) => {
    if (!canGrant(nomination)) return;

    const result = await api.post(`/refresh/rakuaward/${nomination.id}/grant`, null, {
        loadingRef: saving,
        ask: `${nomination.nominee?.name ?? '対象メンバー'}さんのリフレッシュに ${formatCurrency(nomination.charged_amount)} を付与しますか？`,
        toast: 'リフレッシュへ付与しました。',
    });

    if (!result) return;

    await fetchNominations();
};

const refundRest = async () => {
    if (!refundableCount.value) return;

    const result = await api.post('/refresh/rakuaward/refund', {
        year: year.value,
        month: month.value,
    }, {
        loadingRef: refunding,
        ask: `上位5名に選ばれなかった${refundableCount.value}件のチャージを、チャージしたメンバーへ返金します。よろしいですか？`,
        toast: 'チャージを返金しました。',
    });

    if (!result) return;

    await fetchNominations();
};

const formatCurrency = (value: number) => {
    return `${new Intl.NumberFormat('ja-JP').format(value ?? 0)}円`;
};

const formatDate = (value: string | null) => {
    if (!value) return '';
    const date = DateTime.fromISO(value);
    return date.isValid ? date.toFormat('yyyy/MM/dd') : '';
};

onMounted(() => {
    fetchNominations();
});
</script>

<style lang="scss" scoped>
.rakuaward-control {
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 0 18px 18px;
    color: var(--primary-color);
    overflow: auto;
}

.rakuaward-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.granted-counter {
    font-size: 13px;
    padding: 6px 10px;
    border-radius: 4px;
    background: var(--bg3);
}

.topbar-note {
    margin: 0;
    font-size: 11px;
    color: var(--text2);
}

.rakuaward-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.rakuaward-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 120px;
    border-radius: 8px;
    background: var(--bg3);
    color: var(--text2);
    font-size: 13px;
}

.rakuaward-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 200px;
    gap: 16px;
    padding: 14px 16px;
    border-radius: 8px;
    background: var(--background-color);
    box-shadow: inset 0 0 0 1px var(--formBorder);
}

.rakuaward-card.granted,
.rakuaward-card.refunded {
    opacity: 0.7;
}

.card-main {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.card-people {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.people-block {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.people-label {
    font-size: 10px;
    color: var(--text2);
}

.people-user {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
}

.people-user.muted {
    color: var(--text2);
}

.people-arrow {
    width: 26px;
    height: auto;
    fill: var(--text2);
    flex-shrink: 0;
}

.card-title {
    margin: 6px 0 0;
    font-size: 15px;
    line-height: 1.4;
}

.card-content {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: var(--text2);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-meta {
    margin: 2px 0 0;
    font-size: 11px;
    color: var(--text2);
}

.card-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    gap: 8px;
    text-align: right;
}

.charged-amount {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

.charged-amount span {
    font-size: 11px;
    color: var(--text2);
}

.charged-amount strong {
    font-size: 20px;
    line-height: 1;
}

.grant-button {
    height: 34px;
    padding: 0 14px;
    border-radius: 6px;
    border: 1px solid #4b4b4b;
    background: #4b4b4b;
    color: #fff;
    font-size: 12px;
    cursor: pointer;
    white-space: nowrap;
}

.grant-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.granted-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(55, 121, 104, 0.18);
    color: #4c957c;
}

.refunded-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(184, 74, 74, 0.14);
    color: #a33d3d;
}

.refund-all-button {
    height: 32px;
    padding: 0 12px;
    border-radius: 4px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 12px;
    cursor: pointer;
}

.refund-all-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.side-note {
    margin: 0;
    font-size: 10px;
    color: var(--text2);
}

@media screen and (max-width: 720px) {
    .rakuaward-control {
        padding: 0 12px 12px;
    }

    .rakuaward-card {
        grid-template-columns: 1fr;
    }

    .card-side {
        align-items: flex-start;
        text-align: left;
    }

    .charged-amount {
        align-items: flex-start;
    }
}
</style>
