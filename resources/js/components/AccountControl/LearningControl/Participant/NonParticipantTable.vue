<template>
    <div class="records-wrapper scrollable non-participant-table">
        <div v-if="loading" class="records-empty">読み込み中...</div>

        <div v-else-if="!hasAccessMembers" class="records-empty">
            アクセス可能メンバーが設定されていないため、全員が受講可能です。
        </div>

        <div v-else-if="!members.length" class="records-empty">
            アクセス可能メンバーの全員が受講済みです。
        </div>

        <div v-else class="records-table">
            <div class="records-header">
                <div class="header-row">
                    <div class="header-cell">未参加者</div>
                </div>
            </div>

            <div class="records-body">
                <div
                    v-for="member in members"
                    :key="member.id"
                    class="body-row"
                >
                    <div class="body-cell border-none">{{ member.name }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { User } from '@/interface/globalInterface'

defineProps<{
    loading: boolean
    hasAccessMembers: boolean
    members: User[]
}>()
</script>

<style scoped>
.non-participant-table{
    padding: 0 20px 20px;
}

.records-empty{
    color: var(--light-color);
    padding: 20px 0;
}

.body-cell{
    line-height: 2;
}
</style>
