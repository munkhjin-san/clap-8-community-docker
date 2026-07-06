<template>
    <div class="admin-window">
        <FloatButton title="シフト種別を追加" @action="openModal">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>
        <header class="admin-control-toolbar">
            <p class="shift-note">ここで作成したシフト種別は、権限設定の各ロールに割り当てて利用できます。</p>
        </header>
        <div v-if="list.length" class="shift-list">
            <div class="shift-box" v-for="st in list" :key="st.id">
                <div class="shift-box-main">
                    <span class="shift-box-name">{{ st.name }}</span>
                    <span v-if="st.abbreviation" class="shift-box-abbr">{{ st.abbreviation }}</span>
                    <span class="shift-box-cat" :class="{ 'shift-box-cat--unset': !st.category }">{{ categoryLabel(st.category) }}</span>
                    <span v-if="st.full_day" class="shift-box-tag">終日</span>
                    <span v-if="st.active === false" class="shift-box-tag shift-box-tag--inactive">無効</span>
                </div>
                <div class="shift-box-menu">
                    <ItemMenu :items="[
                        {title: '編集する', action: () => edit(st)},
                        {title: '削除する', action: () => remove(st)},
                    ]"/>
                </div>
            </div>
        </div>
        <div v-else-if="fetch > 0" class="shift-empty">現在データはありません</div>
        <Transition name="modalFade">
            <ShiftTypeCreate v-if="create" :editTarget="editTarget" :categories="categories" @close="closeModal"/>
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import ShiftTypeCreate from './ShiftTypeCreate.vue';
import { useApi } from '@/composables/api';

type CategoryOption = { value: string; label: string; hours: boolean }
type ShiftType = { id: number; name: string; abbreviation?: string | null; value?: number | null; full_day?: boolean | null; category?: string | null; hours?: number | null; active?: boolean | null }

const api = useApi()
const fetch = ref(0)
const list = ref<ShiftType[]>([])
const categories = ref<CategoryOption[]>([])
const create = ref(false)
const editTarget = ref<ShiftType | null>(null)

onMounted(() => {
    getCategories()
    getShiftTypes()
})

const getCategories = async () => {
    categories.value = (await api.get('/community_context/shift_type_categories') ?? []) as CategoryOption[]
}

// Seeded 慶弔/転勤/ODA records keep fine sub-categories that the catalog no longer
// lists; show them under the unified 特別休暇 label.
const SPECIAL_LEAVE_SUBTYPES = ['special_leave_condolence', 'special_leave_transfer', 'special_leave_oda', 'comp_holiday']
const categoryLabel = (value?: string | null) => {
    if (value && SPECIAL_LEAVE_SUBTYPES.includes(value)) {
        return categories.value.find(c => c.value === 'special_leave')?.label ?? '特別休暇'
    }
    return categories.value.find(c => c.value === value)?.label ?? '未分類'
}

const getShiftTypes = async () => {
    list.value = (await api.get('/community_context/shift_types') ?? []) as ShiftType[]
    fetch.value++
}

const openModal = () => {
    editTarget.value = null
    create.value = true
}

const closeModal = (flag: boolean) => {
    if(flag){
        getShiftTypes()
    }
    create.value = false
    editTarget.value = null
}

const edit = (st: ShiftType) => {
    editTarget.value = st
    create.value = true
}

const remove = async (st: ShiftType) => {
    const data = await api.del(`/community_context/shift_types/${st.id}`, {}, {
        ask: `「${st.name}」を削除しますか？`,
        toast: 'シフト種別を削除しました',
    })
    if(data === null){
        return
    }
    getShiftTypes()
}
</script>
<style lang="scss" scoped>
.admin-control-toolbar {
    padding: 16px 20px 0;
}
.shift-note {
    font-size: 12px;
    color: var(--text-secondary, #888);
}
.shift-list {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: calc(100% - 60px);
    overflow: hidden auto;
}
.shift-box {
    background: var(--background-color);
    border: 1px solid var(--bg3);
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}
.shift-box-main {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
}
.shift-box-name { font-size: 14px; font-weight: 600; }
.shift-box-abbr { font-size: 12px; color: var(--text-secondary, #888); }
.shift-box-cat {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--accent-color, #e8f0fe);
    color: var(--accent-text, #1a56db);
}
.shift-box-cat--unset {
    background: var(--bg3);
    color: tomato;
}
.shift-box-tag--inactive {
    background: transparent;
    border: 1px solid tomato;
    color: tomato;
}
.shift-box-tag {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--text-secondary, #555);
}
.shift-box-menu { margin-left: auto; }
.shift-empty {
    height: 100%;
    width: 100%;
    text-align: center;
    justify-content: center;
    display: flex;
    align-items: center;
    color: gray;
}
</style>
