<template>
    <Modal @close="closeModal(false)">
        <template #title>
            <p>{{ editTarget ? `${typeLabel}を編集する` : `${typeLabel}を追加する` }}</p>
        </template>

        <template #content>
            <div class="si-box">
                <ShortInput
                    ref="labelRef"
                    v-model="params.label"
                    :name="`calendarFacility${type}`"
                    :place-holder="`${typeLabel}名（必須）`"
                    rules="required"
                    type="text"
                    custom-class="full"
                />
            </div>

            <div class="si-box">
                <p class="form-title-small">利用状態</p>
                <div class="selectSwitchArea calendar-facility-modal__switch">
                    <input id="calendarFacilityActive" v-model="params.active" type="checkbox">
                    <label for="calendarFacilityActive" class="cursor-pointer">
                        <span></span>
                        <div class="switch-toggle"></div>
                    </label>
                </div>
            </div>

            <div class="si-box">
                <LoaderButton
                    :loading="loading"
                    content="保存する"
                    @triggered="save"
                />
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import ShortInput from '@/components/Form/ShortInput.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import { useApi } from '@/composables/api'
import type {
    CalendarFacilitySetting,
    CalendarFacilityType,
} from '@/interface/calendarFacilityInterface'

const props = defineProps<{
    type: CalendarFacilityType
    editTarget: CalendarFacilitySetting | null
}>()
const emit = defineEmits<{
    close: [refresh: boolean]
}>()

const api = useApi()
const loading = ref(false)
const labelRef = ref<InstanceType<typeof ShortInput> | null>(null)
const typeLabel = computed(() => props.type === 'room' ? '会議室' : '車両')
const params = reactive({
    label: props.editTarget?.label ?? '',
    active: props.editTarget?.active ?? true,
})

const closeModal = (refresh: boolean) => emit('close', refresh)

const save = async () => {
    const validation = await labelRef.value?.validate(false)
    if (!validation?.valid) return

    loading.value = true
    try {
        if (props.editTarget) {
            await api.put(
                `/admin/calendar-facilities/${props.editTarget.id}`,
                params,
                { toast: `${typeLabel.value}を保存しました` },
            )
        } else {
            await api.post(
                '/admin/calendar-facilities',
                { ...params, type: props.type },
                { toast: `${typeLabel.value}を追加しました` },
            )
        }
        closeModal(true)
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.calendar-facility-modal__switch {
    width: 84px;
    margin-top: 12px;
}

.calendar-facility-modal__switch label span::after {
    content: '無効';
    padding: 0 0 0 28px;
}

.calendar-facility-modal__switch input:checked + label span::after {
    content: '有効';
    padding: 0 28px 0 0;
}

.calendar-facility-modal__switch input:checked + label .switch-toggle {
    transform: translateX(50px);
}
</style>
