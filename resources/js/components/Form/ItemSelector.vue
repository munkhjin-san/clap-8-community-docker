<template>
    <div>
        <div ref="selectorRef" class="item-selector-shell">
            <v-autocomplete
                v-model="selectedItems"
                :chips="multiple"
                :clear-icon="CloseIcon"
                :closable-chips="multiple"
                :close-on-select="closeOnSelect"
                :hide-selected="multiple"
                :item-title="itemTitle"
                :item-value="itemValue"
                :items="itemOptions"
                :label="placeHolder"
                :menu-props="{ scrollStrategy: 'close', maxWidth: selectorRef ? selectorRef.clientWidth : undefined, contentClass: 'item-selector-menu' }"
                :multiple="multiple"
                auto-select-first
                autocomplete="off"
                class="one-selector"
                flat
                hide-details
                :name="name"
                tile
                @update:focused="focus = $event"
                @update:search="handleSearch"
            >
                <template #chip="{ props: chipProps, item }">
                    <v-chip
                        v-if="item.raw"
                        v-bind="chipProps"
                        :close-icon="CloseIcon"
                        :text="itemTitle(item.raw)"
                        closable
                        density="compact"
                        rounded="0"
                        size="small"
                    ></v-chip>
                </template>
                <template #item="{ item, props: itemProps }">
                    <v-list-item
                        v-bind="itemProps"
                        :ripple="false"
                        :text="itemTitle(item.raw)"
                        class="item-selector-option"
                        density="compact"
                        rounded="0"
                        variant="flat"
                    ></v-list-item>
                </template>
                <template #no-data>
                    <div class="text-[12px] text-[gray] p-3">アイテムはありません。</div>
                </template>
            </v-autocomplete>
        </div>
        <p v-if="error" class="i-error">{{ error }}</p>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref, useTemplateRef, watch } from 'vue';
import { validator } from '@/validation/validator'
import { useApi } from '@/composables/api';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import 'styles/selector.css';

    type ItemOption = Record<string, any> | string | number

    interface Props {
        placeHolder?: string
        name?: string
        rules?: string
        multiple?: boolean
        options?: ItemOption[]
        path?: string
        clearable: boolean
        label?: string
        reduce?: (option: any) => any
        closeOnSelect: boolean
    }

    const props = withDefaults(defineProps<Props>(), {
        placeHolder: '',
        name: 'optionSelector',
        rules: '',
        multiple: true,
        options: () => [],
        path: '',
        clearable: true,
        label: 'name',
        reduce: (option: any) => option?.id ?? option,
        closeOnSelect: false,
    })
    const itemOptions = ref<ItemOption[]>([])
    const error = ref('')
    const trigger = ref(false)
    const emit = defineEmits<{
        search: [keyword: string]
    }>()
    const focus = ref(false)
    const api = useApi()
    const selectedItems = defineModel<any>()
    const selectorRef = useTemplateRef<HTMLElement>('selectorRef')

    watch(() => props.path, () => {
        getPossibleItems()
    })
    watch(() => props.options, () => {
        itemOptions.value = props.options
    })
    onMounted(() => {
        if (props.options.length) {
            itemOptions.value = props.options
        } else if (props.path) {
            getPossibleItems()
        }
    })

    const itemTitle = (option: ItemOption) => {
        if (option === null || option === undefined) return ''
        if (typeof option !== 'object') return String(option)

        const value = option[props.label]
        return value === null || value === undefined ? '' : String(value)
    }

    const itemValue = (option: ItemOption) => {
        return props.reduce(option)
    }

    const getPossibleItems = async () => {
        if (!props.path) return

        const response = await api.get(`/${props.path}`)
        itemOptions.value = response ?? []
    }

    const validate = async (passive?: boolean) => {
        if (passive && !trigger.value) return

        const { isValid, errorMessage } = await validator(props.rules ? props.rules : '', selectedItems.value)
        error.value = errorMessage!
        trigger.value = true
        return { valid: isValid }
    }

    const handleSearch = (keyword: string) => {
        emit('search', keyword)
    }

    defineExpose({ validate })
</script>
<style lang="scss">
.item-selector-shell {
    background: inherit;
    border: 1px solid var(--primary-color);
    position: relative;
}

.one-selector {
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
}

.item-selector-option {
    color: var(--primary-color) !important;

    &:after {
        pointer-events: none;
        z-index: 0;
    }

    .v-list-item__content,
    .v-list-item-title {
        color: var(--primary-color) !important;
        position: relative;
        z-index: 1;
    }
}

.item-selector-option:focus,
.item-selector-option:focus-visible,
.item-selector-option.v-list-item--active,
.item-selector-option.v-list-item--focus,
.item-selector-option.v-list-item--highlighted,
.item-selector-option[aria-selected="true"] {
    background: var(--bg2) !important;
}

/* Dropdown menu is teleported out of this component to a Vuetify overlay (which
   renders under the light theme — surface = white), so force the app background
   on the overlay content + list. */
.item-selector-menu,
.item-selector-menu .v-sheet,
.item-selector-menu .v-list {
    background: var(--background-color) !important;
    background-color: var(--background-color) !important;
    color: var(--primary-color) !important;
}

/* The app sets a global `* { transition: all }` (theme-switch helper). On open
   that animates the list background from the light-theme surface (white) to the
   dark override above — the white flash. Kill the transition on the list/items so
   the color applies instantly. (Left off the overlay content, so Vuetify's open
   animation is preserved.) */
.item-selector-menu .v-sheet,
.item-selector-menu .v-list,
.item-selector-menu .v-list .v-list-item {
    transition: none !important;
}
</style>
