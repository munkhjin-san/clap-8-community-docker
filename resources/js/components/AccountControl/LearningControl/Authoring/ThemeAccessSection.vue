<template>
    <div>
        <div class="si-box">
            <ItemSelector
                v-model="selectedForm"
                placeHolder="アンケート"
                :options="forms"
                label="title"
                :multiple="false"
                :clearable="true"
                :close-on-select="true"
            />
            <p class="form-helper theme-access-section__helper">
                テーマ完了後に受講者へ表示するカスタムアンケートを選択できます。
            </p>
        </div>
        <div class="si-box">
            <ItemSelector
                v-model="selectedPositions"
                placeHolder="アクセス可能役"
                :options="positions"
                label="name"
                :multiple="true"
                :clearable="true"
                :close-on-select="false"
            />
        </div>
        <div class="si-box">
            <MemberSelector
                v-model="selectedMembers"
                placeHolder="アクセス可能メンバー"
                :options="members"
                :multiple="true"
                :clearable="true"
                :close-on-select="false"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import ItemSelector from '@/components/Form/ItemSelector.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'
import type { User } from '@/interface/globalInterface'

export interface ThemeFormOption {
    id: number
    title: string
}

export interface ThemePositionOption {
    id: number
    name: string
    employees?: User[]
}

defineProps<{
    forms: ThemeFormOption[]
    positions: ThemePositionOption[]
    members: User[]
}>()

const selectedForm = defineModel<number | null>('selectedForm', { required: true })
const selectedPositions = defineModel<number[]>('selectedPositions', { required: true })
const selectedMembers = defineModel<User[]>('selectedMembers', { required: true })
</script>

<style scoped>
.theme-access-section__helper{
    margin-top: 5px;
    font-size: 12px;
    color: gray;
    line-height: normal;
}
</style>
