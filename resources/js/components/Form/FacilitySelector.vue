<template>
    <div style="background:inherit;">
        <div ref="selectorRef" class="facility-selector-shell">
            <v-select
                v-model="selectedItems"
                :items="options"
                item-title="label"
                item-value="id"
                :item-props="facilityItemProps"
                :label="placeHolder"
                :loading="spinner"
                :menu-props="{ scrollStrategy: 'close', maxWidth: selectorRef ? selectorRef.clientWidth : undefined }"
                autocomplete="off"
                class="facilitySelector"
                clearable
                :clear-icon="CloseIcon"
                flat
                hide-details
                name="selected_items"
                tile
                @update:menu="handleMenuUpdate"
            >
                <template #selection="{ item }">
                    <v-chip
                        v-if="item.raw"
                        :close-icon="CloseIcon"
                        :text="item.raw.label"
                        class="facility-selector-chip"
                        closable
                        density="compact"
                        rounded="0"
                        size="small"
                        @click:close.stop="selectedItems = null"
                    ></v-chip>
                </template>
                <template #item="{ item, props }">
                    <v-list-item
                        v-bind="props"
                        :disabled="!item.raw.availablity"
                        :ripple="false"
                        :text="item.raw.label"
                        class="facility-selector-item"
                        density="compact"
                        rounded="0"
                        variant="flat"
                    ></v-list-item>
                </template>
                <template #no-data>
                    <div style="font-size: 14px;opacity: 0.8;padding:10px 0;">アイテムはありません。</div>
                </template>
                <template #loader="{ isActive }">
                    <Transition name="modalFade">
                        <div v-if="isActive">
                            <div class="spinner-nano" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                        </div>
                    </Transition>
                </template>
            </v-select>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, useTemplateRef, watch } from 'vue';
import { useApi } from '@/composables/api';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import type { RepeatDataType } from '@/interface/calendarInterface';
import 'styles/selector.css';

    type FacilityTarget = 'qualified_institution' | 'qualified_car' | 'zoom_value'

    type FacilityOption = {
        label: string
        id: string
        availablity: boolean
    }

    type FacilityItemProps = {
        title: string
        disabled: boolean
    }

    interface Props {
        placeHolder: string
        repeatSpan: RepeatDataType
        repetitionFlag: number
        target: FacilityTarget
        time_start: string
        time_end: string
        once_date: string
        facility?: unknown
        editId: number | string | null
        edit_all_record: boolean
    }

    const props = defineProps<Props>()
    const selectedItems = defineModel<string | null>()
    const options = ref<FacilityOption[]>([])
    const spinner = ref(false)
    const selectorRef = useTemplateRef<HTMLElement>('selectorRef')
    const api = useApi()
    const fetchedOnce = ref(false)
    let requestSerial = 0

    const getPossibleItems = async () => {
        const params = {
            editId: props.editId,
            target: props.target,
            repeat: props.repetitionFlag,
            repeat_span: props.repeatSpan,
            time_start: props.time_start,
            time_end: props.time_end,
            once_date: props.once_date,
            edit_repeat: props.edit_all_record,
        }

        if (!isValidTime(params.time_start) || !isValidTime(params.time_end)) return

        const serial = ++requestSerial
        const data = await api.post('/get_possible_facilities', params, {
            loadingRef: spinner,
        }) as FacilityOption[] | null

        if (serial !== requestSerial || !data) return

        options.value = data
        fetchedOnce.value = true
    }

    const handleMenuUpdate = (isOpen: boolean) => {
        if (!isOpen) return
        if (!fetchedOnce.value) getPossibleItems()
    }

    const facilityItemProps = (option: FacilityOption): FacilityItemProps => {
        return {
            title: option.label,
            disabled: !option.availablity,
        }
    }

    const isValidTime = (time: string) => {
        const timeRegex = /^\d{1,2}:\d{2}$/
        if (!timeRegex.test(time)) {
            return false
        }

        const [hour, minute] = time.split(':').map(Number)
        return hour >= 0 && hour <= 23 && minute >= 0 && minute <= 59
    }

    watch(
        () => [
            props.editId,
            props.target,
            props.repetitionFlag,
            props.repeatSpan,
            props.time_start,
            props.time_end,
            props.once_date,
            props.edit_all_record,
        ],
        () => {
            void getPossibleItems()
        },
        { deep: true, immediate: true },
    )
</script>
<style lang="scss">
.facility-selector-shell {
    background: inherit;
    border: 1px solid var(--primary-color);
    position: relative;
}

.facilitySelector {
    background: inherit !important;
    border: none !important;
    width: 100%;

    .v-field,
    .v-field__field,
    .v-field__input,
    .v-field__overlay {
        background: inherit !important;
        background-color: inherit !important;
    }

    .v-field__clearable {
        color: var(--primary-color);
    }

    .v-field__loader {
        left: auto;
        right: 15px;
        top: 20px;
        width: fit-content;
    }

    .facility-selector-chip {
        max-width: 100%;
    }
}

.facility-selector-item.v-list-item--disabled {
    background: inherit !important;
    color: inherit !important;
    opacity: 0.4;
}

@supports selector(:focus-visible) {
    .v-list-item:after {
        background: var(--bg2);
        border: none !important;
        border-radius: 0;
        color: var(--primary-color);
    }

    .v-list-item:focus-visible:after {
        opacity: 0.5;
    }
}
</style>
