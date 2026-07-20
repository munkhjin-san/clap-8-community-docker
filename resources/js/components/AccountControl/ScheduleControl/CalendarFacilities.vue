<template>
    <div class="admin-window">
        <FloatButton :title="`${typeLabel}を追加`" @action="openModal(null)">
            <template #icon>
                <AddIcon size="15" fill="black" />
            </template>
        </FloatButton>

        <Transition name="modalFade">
            <div v-if="fetch === 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div v-if="facilities.length" class="calendar-facility-list">
            <div
                v-for="facility in facilities"
                :key="facility.id"
                class="calendar-facility-box mobile:bg-[var(--bg3)]"
            >
                <div class="calendar-facility-box__header">
                    <div>
                        <p class="calendar-facility-box__title">{{ facility.label }}</p>
                        <span :class="{ active: facility.active }">
                            {{ facility.active ? '利用可能' : '利用停止' }}
                        </span>
                    </div>

                    <ItemMenu :items="[
                        { title: '編集する', action: () => openModal(facility) },
                        { title: '削除する', action: () => remove(facility) },
                    ]" />
                </div>
            </div>
        </div>

        <div v-else-if="fetch > 0" class="calendar-facility-empty">
            現在データはありません
        </div>

        <Transition name="modalFade">
            <CalendarFacilityCreate
                v-if="modalOpen"
                :edit-target="editTarget"
                :type="type"
                @close="closeModal"
            />
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import FloatButton from '@/components/Global/FloatButton.vue'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import CalendarFacilityCreate from './CalendarFacilityCreate.vue'
import { useApi } from '@/composables/api'
import type {
    CalendarFacilitySetting,
    CalendarFacilityType,
} from '@/interface/calendarFacilityInterface'

const props = defineProps<{
    type: CalendarFacilityType
}>()

const api = useApi()
const fetch = ref(0)
const facilities = ref<CalendarFacilitySetting[]>([])
const modalOpen = ref(false)
const editTarget = ref<CalendarFacilitySetting | null>(null)
const typeLabel = computed(() => props.type === 'room' ? '会議室' : '車両')

const getFacilities = async () => {
    fetch.value = 0
    facilities.value = await api.get('/admin/calendar-facilities', {
        type: props.type,
    }) as CalendarFacilitySetting[]
    fetch.value++
}

const openModal = (facility: CalendarFacilitySetting | null) => {
    editTarget.value = facility
    modalOpen.value = true
}

const closeModal = (refresh: boolean) => {
    modalOpen.value = false
    editTarget.value = null
    if (refresh) getFacilities()
}

const remove = async (facility: CalendarFacilitySetting) => {
    const result = await api.del(`/admin/calendar-facilities/${facility.id}`, {}, {
        ask: `${facility.label}を削除しますか？`,
        toast: `${typeLabel.value}を削除しました`,
    })
    if (result !== null) getFacilities()
}

watch(
    () => props.type,
    () => {
        modalOpen.value = false
        editTarget.value = null
        void getFacilities()
    },
    { immediate: true },
)
</script>

<style scoped>
.calendar-facility-list {
    height: calc(100% - 40px);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    overflow: hidden auto;
}

.calendar-facility-box {
    padding: 22px;
    background: var(--background-color);
}

.calendar-facility-box__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
}

.calendar-facility-box__title {
    font-size: 15px;
    line-height: 1.5;
}

.calendar-facility-box__header span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding: 4px 9px;
    background: var(--bg3);
    color: gray;
    font-size: 12px;
    line-height: 1.4;
}

.calendar-facility-box__header span::before {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #a1a1aa;
    content: '';
}

.calendar-facility-box__header span.active {
    background: #edf8f0;
    color: #166534;
}

.calendar-facility-box__header span.active::before {
    background: #22a447;
}

.calendar-facility-empty {
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: gray;
}
</style>
