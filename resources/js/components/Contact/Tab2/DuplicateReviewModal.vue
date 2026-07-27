<template>
    <Transition name="modalFade">
        <Modal v-if="open" @close="handleClose">
            <template #title>
                <p>重複レビュー</p>
            </template>
            <template #content>
                <div v-if="loading" class="py-6 text-center text-sm text-[gray]">
                    確認中です...
                </div>
                <div v-else>
                    <div v-if="!currentDuplicate" class="py-6 text-center text-sm text-[gray]">
                        現在、確認が必要な重複データはありません。
                    </div>
                    <div v-else class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-sm text-[var(--primary-color)]">
                                    重複候補 {{ activeIndexDisplay }} / {{ totalDuplicates }}
                                </p>
                                <p class="text-xs text-[gray]">
                                    一致は<span class="text-green-600">緑</span>、差異は<span class="text-rose-300">赤</span>、既存のみの情報は<span class="text-amber-300">橙</span>で表示されます。候補を選ぶか「重複ではない」を押してください。
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    class="px-3 py-1.5 text-x text-[var(--primary-color)] hover:bg-[var(--bg2)] disabled:opacity-40"
                                    :disabled="activeIndex <= 0"
                                    @click="goToPreviousDuplicate"
                                >
                                    前へ
                                </button>
                                <button
                                    class="px-3 py-1.5 text-xs text-[var(--primary-color)] hover:bg-[var(--bg2)] disabled:opacity-40"
                                    :disabled="activeIndex >= totalDuplicates - 1"
                                    @click="goToNextDuplicate"
                                >
                                    次へ
                                </button>
                            </div>
                        </div>
                        <div class="bg-[var(--background-color)]">
                            <div class="flex items-start justify-between gap-3 py-3">
                                <div>
                                    <p class="text-sm text-[var(--primary-color)] truncate">
                                        {{ currentContact.name || '名称未設定' }}（{{ currentContact.company_name || '会社名未設定' }}）
                                    </p>
                                    <p class="text-xs text-[gray]">
                                        追加予定のコンタクトを確認し、最適な統合先を選択してください。
                                    </p>
                                </div>
                                <div v-if="isResolvingCurrent" class="flex items-center gap-2 text-xs text-[var(--primary-color)]">
                                    <span class="h-3 w-3 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                    処理中...
                                </div>
                            </div>
                            <div class="py-4 grid md:grid-cols-2 gap-4">
                                <section class="space-y-2">
                                    <p class="text-xs text-[gray]">追加予定のコンタクト</p>
                                    <div class="py-3 text-sm text-[gray] space-y-2">
                                        <div
                                            v-if="currentContact.card_path"
                                            class="w-full overflow-hidden max-h-full h-96"
                                        >
                                            <img
                                                :src="`/cdn/${currentContact.card_path}`"
                                                alt="名刺画像（新規）"
                                                class="w-full h-full object-contain"
                                                loading="lazy"
                                            />
                                        </div>
                                        <dl class="grid grid-cols-[6rem_1fr] gap-x-3 gap-y-1">
                                            <dt>氏名</dt><dd class="text-[var(--primary-color)]">{{ currentContact.name || '---' }}</dd>
                                            <dt>会社</dt><dd class="text-[var(--primary-color)]">{{ currentContact.company_name || '---' }}</dd>
                                            <dt>役職</dt><dd class="text-[var(--primary-color)]">{{ currentContact.position || '---' }}</dd>
                                            <dt>メール</dt><dd class="truncate text-[var(--primary-color)]">{{ currentContact.email || '---' }}</dd>
                                            <dt>電話</dt><dd class="text-[var(--primary-color)]">{{ currentContact.phone || '---' }}</dd>
                                        </dl>
                                    </div>
                                </section>
                                <section>
                                    <p class="text-xs text-[gray] mb-1" :id="`label-${currentContactId}`">既存コンタクト候補</p>
                                    <div
                                        v-if="sortedCandidates.length"
                                        class="space-y-3"
                                        role="radiogroup"
                                        :aria-labelledby="`label-${currentContactId}`"
                                    >
                                        <label
                                            v-for="candidate in sortedCandidates"
                                            :key="candidate.id"
                                            :class="[
                                                'relative py-3 pl-10 flex gap-3 items-start text-sm cursor-pointer transition focus:outline-none focus:ring-1 focus:ring-[var(--primary-color)]',
                                                selectedCandidates[currentContactId] === candidate.id
                                                    ? 'border-[var(--primary-color)] bg-[var(--primary-color)]/10'
                                                    : 'border-gray-700 hover:border-gray-600'
                                            ]"
                                            @keydown.enter.prevent="selectedCandidates[currentContactId] = candidate.id"
                                            tabindex="0"
                                        >
                                            <input
                                                type="radio"
                                                class="sr-only"
                                                :name="`dup-${currentContactId}`"
                                                :value="candidate.id"
                                                v-model="selectedCandidates[currentContactId]"
                                                @click.stop
                                            />
                                            <span
                                                class="absolute top-3 left-3 inline-flex h-5 w-5 items-center justify-center rounded-full border"
                                                :class="selectedCandidates[currentContactId] === candidate.id ? 'border-[var(--primary-color)] bg-[var(--primary-color)] text-[var(--background-color)]' : 'border-gray-600 bg-gray-800 text-gray-400'"
                                            >
                                                <svg v-if="selectedCandidates[currentContactId] === candidate.id" version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" class="h-3 w-3" fill="currentColor">
                                                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                                </svg>
                                            </span>
                                            <div class="flex-1 space-y-2">
                                                <div
                                                    v-if="candidate.card_path"
                                                    class="mt-2 overflow-hidden max-h-full h-96"
                                                >
                                                    <img
                                                        :src="`/cdn/${candidate.card_path}`"
                                                        alt="名刺画像（既存）"
                                                        class="w-full h-full object-contain"
                                                        loading="lazy"
                                                    />
                                                </div>
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="space-y-1">
                                                        <p :class="['font-semibold', fieldValueClass('name', candidate, currentContact)]">
                                                            {{ candidate.name || '---' }}
                                                        </p>
                                                        <p :class="['text-sm', fieldValueClass('company_name', candidate, currentContact)]">
                                                            {{ candidate.company_name || '---' }}
                                                        </p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="text-xs text-[var(--primary-color)] hover:underline"
                                                        @click.stop="emitOpenCandidate(candidate.id)"
                                                    >
                                                        詳細を見る
                                                    </button>
                                                </div>
                                                <p
                                                    class="text-xs truncate"
                                                    :class="[fieldValueClass('email', candidate, currentContact)]"
                                                >
                                                    {{ candidate.email || '---' }}
                                                </p>
                                                <div class="flex flex-wrap gap-2 pt-2">
                                                    <span
                                                        v-for="badge in buildMatchBadges(candidate, currentContact)"
                                                        :key="`${candidate.id}-${badge.label}`"
                                                        class="text-[10px] px-2 py-1"
                                                        :class="badge.className"
                                                    >
                                                        {{ badge.label }}
                                                    </span>
                                                </div>
                                                
                                            </div>
                                        </label>
                                    </div>
                                    <div
                                        v-else
                                        class="border border-dashed border-gray-700 p-3 text-xs text-gray-400 flex items-center gap-2"
                                    >
                                        <svg width="16" height="16" viewBox="0 0 24 24" class="opacity-70"><path fill="currentColor" d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2"/></svg>
                                        該当する既存コンタクトは見つかりませんでした。
                                    </div>
                                </section>
                            </div>
                            <div class="flex flex-wrap justify-end gap-4 py-3">
                                <CommandButton 
                                    :buttons="[
                                        {
                                            title: '重複ではない', action: () => emitResolve('keep')
                                        },
                                        {
                                            title: '既存に統合', action: () => emitResolve('merge')
                                        }
                                    ]"
                                />
                                <!-- <button
                                    class="px-3 py-1.5 text-xs bg-gray-700 hover:bg-gray-600 text-gray-100 disabled:opacity-40"
                                    :disabled="isResolvingCurrent || !currentContactId"
                                    @click="emitResolve('keep')"
                                >
                                    重複ではない
                                </button>
                                <button
                                    class="px-3 py-1.5 text-xs bg-[var(--primary-color)] hover:brightness-110 text-[var(--background-color)] disabled:opacity-40 flex items-center gap-2"
                                    :disabled="isResolvingCurrent || !currentSelection || !currentContactId"
                                    @click="emitResolve('merge')"
                                >
                                    <span>既存に統合</span>
                                </button> -->
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Modal>
    </Transition>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import { DuplicateCandidateSummary, DuplicateSummary } from '@/interface/contactInterface';
import CommandButton from '@/components/Global/CommandButton.vue';

const props = defineProps<{
    open: boolean;
    duplicates: DuplicateSummary[];
    loading: boolean;
    resolvingId: number | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'resolve', payload: { contactId: number; action: 'keep' | 'merge'; targetId?: number | null }): void;
    (e: 'open-candidate', payload: number): void;
}>();

const activeIndex = ref(0);
const selectedCandidates = ref<Record<number, number | null>>({});

const normalizeForCompare = (value: unknown): string => {
    if (value === null || value === undefined) return '';
    return String(value).trim().toLowerCase();
};

const comparableFields = ['name', 'company_name', 'email', 'card_hash'] as const;
type CandidateComparableField = typeof comparableFields[number];

const candidateFieldLabels: Record<CandidateComparableField, string> = {
    name: '氏名',
    company_name: '会社',
    email: 'メール',
    card_hash: 'カード画像',
};

const candidateMatchClassMap: Record<'match' | 'mismatch' | 'candidateOnly' | 'missing', string> = {
    match: 'text-green-600',
    mismatch: 'text-rose-600',
    candidateOnly: 'text-amber-600',
    missing: 'text-gray-400',
};

const candidateBadgeClassMap: Record<'match' | 'mismatch' | 'candidateOnly' | 'missing', string> = {
    match: 'border-green-500/40 bg-green-500/10 text-green-600',
    mismatch: 'border-rose-500/40 bg-rose-500/10 text-rose-600',
    candidateOnly: 'border-amber-500/40 bg-amber-500/10 text-amber-600',
    missing: 'border-gray-600 bg-gray-700/40 text-gray-300',
};

const totalDuplicates = computed(() => props.duplicates.length);
const currentDuplicate = computed(() => props.duplicates[activeIndex.value] ?? null);
const currentContact = computed(() => currentDuplicate.value?.contact ?? {});
const currentContactId = computed(() => currentDuplicate.value?.contact.id ?? 0);
const currentSelection = computed(() => selectedCandidates.value[currentContactId.value] ?? null);
const activeIndexDisplay = computed(() => (totalDuplicates.value ? activeIndex.value + 1 : 0));

const candidateScore = (candidate: DuplicateCandidateSummary, contact: DuplicateSummary['contact']): number => {
    if (!contact) return 0;
    let score = 0;

    if (candidate.card_hash && contact.card_hash && candidate.card_hash === contact.card_hash) {
        score += 5;
    }
    if (candidate.email && contact.email && normalizeForCompare(candidate.email) === normalizeForCompare(contact.email)) {
        score += 3;
    }
    if (candidate.name && contact.name && normalizeForCompare(candidate.name) === normalizeForCompare(contact.name)) {
        score += 2;
    }
    if (candidate.company_name && contact.company_name && normalizeForCompare(candidate.company_name) === normalizeForCompare(contact.company_name)) {
        score += 1;
    }
    if (candidate.updated_at) {
        score += 0.1;
    }

    return score;
};

const sortCandidates = (duplicate: DuplicateSummary): DuplicateCandidateSummary[] => {
    if (!duplicate?.candidates?.length) return [];
    const base = duplicate.contact;
    return [...duplicate.candidates].sort((a, b) => candidateScore(b, base) - candidateScore(a, base));
};

const sortedCandidates = computed(() => {
    const dup = currentDuplicate.value;
    if (!dup) return [];
    return sortCandidates(dup);
});

const fieldMatchState = (
    field: CandidateComparableField,
    candidate: DuplicateCandidateSummary,
    contact: DuplicateSummary['contact'],
): 'match' | 'mismatch' | 'candidateOnly' | 'missing' => {
    const candidateValue = (candidate as DuplicateCandidateSummary)[field];
    const contactValue = (contact as Record<string, unknown>)[field];

    if (!candidateValue && !contactValue) {
        return 'missing';
    }
    if (candidateValue && !contactValue) {
        return 'candidateOnly';
    }
    if (!candidateValue && contactValue) {
        return 'missing';
    }

    return normalizeForCompare(candidateValue) === normalizeForCompare(contactValue)
        ? 'match'
        : 'mismatch';
};

const fieldValueClass = (
    field: CandidateComparableField,
    candidate: DuplicateCandidateSummary,
    contact: DuplicateSummary['contact'],
) => {
    const state = fieldMatchState(field, candidate, contact);
    return candidateMatchClassMap[state];
};

const buildMatchBadges = (
    candidate: DuplicateCandidateSummary,
    contact: DuplicateSummary['contact'],
): Array<{ label: string; state: 'match' | 'mismatch' | 'candidateOnly' | 'missing'; className: string }> => {
    return comparableFields
        .map(field => ({
            label: candidateFieldLabels[field],
            state: fieldMatchState(field, candidate, contact),
            hasValue: Boolean((candidate as DuplicateCandidateSummary)[field]) || Boolean((contact as Record<string, unknown>)[field]),
        }))
        .filter(item => item.hasValue)
        .map(({ label, state }) => ({ label, state, className: candidateBadgeClassMap[state] }));
};

watch(
    () => props.duplicates,
    (list) => {
        if (!list.length) {
            activeIndex.value = 0;
            selectedCandidates.value = {};
            return;
        }

        if (activeIndex.value >= list.length) {
            activeIndex.value = Math.max(0, list.length - 1);
        }

        const nextSelections: Record<number, number | null> = {};
        list.forEach(dup => {
            const contactId = dup.contact.id ?? 0;
            const sorted = sortCandidates(dup);
            const previous = selectedCandidates.value[contactId] ?? null;
            nextSelections[contactId] = sorted.some(candidate => candidate.id === previous)
                ? previous
                : sorted[0]?.id ?? null;
        });

        selectedCandidates.value = nextSelections;
    },
    { immediate: true, deep: false },
);

watch(activeIndex, () => {
    const dup = currentDuplicate.value;
    if (!dup) return;

    const contactId = dup.contact.id ?? 0;
    if (selectedCandidates.value[contactId] !== undefined) {
        return;
    }

    const sorted = sortCandidates(dup);
    selectedCandidates.value = {
        ...selectedCandidates.value,
        [contactId]: sorted[0]?.id ?? null,
    };
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            activeIndex.value = 0;
        }
    },
);

const isResolvingCurrent = computed(() => {
    const currentId = currentContactId.value;
    return currentId !== 0 && props.resolvingId === currentId;
});

const goToPreviousDuplicate = () => {
    if (activeIndex.value <= 0) return;
    activeIndex.value -= 1;
};

const goToNextDuplicate = () => {
    if (activeIndex.value >= totalDuplicates.value - 1) return;
    activeIndex.value += 1;
};

const emitResolve = (action: 'keep' | 'merge') => {
    const contactId = currentContactId.value;
    if (!contactId) return;

    if (action === 'merge' && !currentSelection.value) {
        return;
    }

    emit('resolve', {
        contactId,
        action,
        targetId: action === 'merge' ? currentSelection.value : null,
    });
};

const emitOpenCandidate = (id: number) => {
    emit('open-candidate', id);
};

const handleClose = () => {
    emit('close');
};
</script>
