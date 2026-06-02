<template>
    <div>
        <div
            class="member-selector-shell"
            :class="{ 'member-selector-disabled': disabled }"
            ref="selectorRef"
            @compositionstart="isComposing = true"
            @compositionend="isComposing = false"
            @keydown.capture="handleKeydown"
        >
            <v-autocomplete
                :model-value="qualifiedUsers"
                :chips="multiple"
                :clear-icon="CloseIcon"
                clear-on-select
                :closable-chips="multiple && !disabled"
                :close-on-select="effectiveCloseOnSelect"
                :disabled="disabled"
                :hide-selected="multiple"
                :item-props="userItemProps"
                :items="options"
                :label="placeHolder"
                :menu="menuOpen"
                :menu-props="{ maxWidth: selectorRef ? selectorRef.clientWidth : undefined }"
                :multiple="multiple"
                auto-select-first
                autocomplete="off"
                class="global-user-select"
                flat
                hide-details
                item-title="name"
                item-value="id"
                :name="name"
                return-object
                tile
                @update:focused="focus = $event"
                @update:menu="updateMenuState"
                @update:modelValue="updateQualifiedUsers"
            >
                <template #chip="{ props: chipProps, item }">
                    <v-chip
                        v-if="item.raw"
                        v-bind="chipProps"
                        :close-icon="CloseIcon"
                        class="member-selector-chip"
                        closable
                        density="compact"
                        rounded="0"
                        size="small"
                    >
                        <div class="member-selector-user">
                            <UserPanel :disableInstant="true" :user="item.raw" size="25"/>
                            <p>{{ item.raw.name }}</p>
                        </div>
                    </v-chip>
                </template>
                <template #selection="{ item }">
                    <div v-if="item.raw && !multiple" class="member-selector-user member-selector-single">
                        <UserPanel :disableInstant="true" :user="item.raw" size="25"/>
                        <p>{{ item.raw.name }}</p>
                    </div>
                </template>
                <template #item="{ item, props }">
                    <v-list-item
                        v-bind="props"
                        :disabled="isOptionDisabled(item.raw)"
                        :ripple="false"
                        class="member-selector-option"
                        density="compact"
                        rounded="0"
                        variant="flat"
                        @keydown.enter.capture="handleOptionEnterKeydown"
                    >
                        <template #title>
                            <div class="member-selector-user">
                                <UserPanel :disableInstant="true" :user="item.raw" size="25"/>
                                <p>{{ item.raw.name }}</p>
                            </div>
                        </template>
                    </v-list-item>
                </template>
                <template #no-data>
                    <div v-if="focus" class="text-[12px] text-[gray] p-3">メンバーが見つかりません。</div>
                </template>
            </v-autocomplete>
        </div>
        <p v-if="error" class="i-error">{{ error }}</p>
    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import { computed, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue';
import { validator } from '@/validation/validator'
import { useApi } from '@/composables/api';
import 'styles/selector.css';
import { User } from '@/interface/globalInterface';



type UserItemProps = {
    title: string
    disabled: boolean
}

interface Props {
    placeHolder?: string
    name?: string
    rules?: string
    path?: string
    limit?: number
    closeOnSelect?: boolean
    selectAll?: boolean
    multiple?: boolean
    options?: User[]
    exclude?: number[]
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    placeHolder: '',
    name: 'qualified_users',
    rules: '',
    path: '',
    limit: undefined,
    selectAll: false,
    multiple: true,
    options: undefined,
    exclude: () => [],
    disabled: false,
})
const error = ref('')
const trigger = ref(false)
defineEmits<{
    setUser: [users: User[] | User | null]
}>()
const options = ref<User[]>([])
const focus = ref(false)
const menuOpen = ref(false)
const lastKeyboardOptionIndex = ref<number | null>(null)
const pendingOptionIndex = ref<number | null>(null)
const isComposing = ref(false)
const api = useApi()
const qualifiedUsers = defineModel<User[] | User | null>()
const selectorRef = useTemplateRef<HTMLElement>('selectorRef')

const selectedList = computed<User[]>(() => {
    if (!qualifiedUsers.value) return []
    return Array.isArray(qualifiedUsers.value) ? qualifiedUsers.value : [qualifiedUsers.value]
})

const limitReached = computed(() => {
    return props.multiple && !!props.limit && selectedList.value.length >= props.limit
})

const effectiveCloseOnSelect = computed(() => {
    if (!props.multiple) return true

    return props.closeOnSelect ?? false
})

onMounted(() => {
    if (props.options) {
        options.value = props.options
    } else if (props.path) {
        getPossibleMembers()
    }
})

watch(() => props.options, () => {
    if (props.options) options.value = props.options
})

watch(() => props.path, () => {
    getPossibleMembers()
})

watch(() => props.selectAll, (after) => {
    if (!props.multiple) return

    qualifiedUsers.value = after ? options.value : []
})

const getPossibleMembers = async () => {
    if (!props.path) return

    const exclude = props.exclude && props.exclude.length ? props.exclude : []
    options.value = await api.post(`/${props.path}`, { exclude }) ?? []
}

const userItemProps = (user: User): UserItemProps => {
    return {
        title: user.name || 'ユーザー',
        disabled: isOptionDisabled(user),
    }
}

const isOptionDisabled = (user: User) => {
    if (!limitReached.value) return false
    return !selectedList.value.some(selected => selected.id === user.id)
}

const focusInput = async () => {
    await nextTick()
    selectorRef.value?.querySelector<HTMLInputElement>('input')?.focus({ preventScroll: true })
}

const getVisibleOptionElements = () => {
    return Array.from(document.querySelectorAll<HTMLElement>('.member-selector-option'))
        .filter(element => !element.classList.contains('v-list-item--disabled') && element.offsetParent !== null)
}

const rememberKeyboardOptionIndex = () => {
    const activeElement = document.activeElement
    const optionIndex = getVisibleOptionElements().findIndex(element => {
        return element === activeElement || element.contains(activeElement)
    })

    lastKeyboardOptionIndex.value = optionIndex >= 0 ? optionIndex : null
}

const rememberKeyboardOptionIndexFromEvent = (event: KeyboardEvent) => {
    const currentTarget = event.currentTarget
    if (!(currentTarget instanceof HTMLElement)) return

    const optionIndex = getVisibleOptionElements().findIndex(element => {
        return element === currentTarget || element.contains(currentTarget)
    })

    lastKeyboardOptionIndex.value = optionIndex >= 0 ? optionIndex : null
}

const handleKeydown = (event: KeyboardEvent) => {
    if (isImeComposing(event) && isImeControlKey(event)) {
        event.stopPropagation()
        return
    }

    if (event.key === 'ArrowDown') {
        focusPendingOption(event)
        return
    }

    if (event.key === 'Enter') {
        rememberKeyboardOptionIndex()
    }
}

const handleOptionEnterKeydown = (event: KeyboardEvent) => {
    if (isImeComposing(event)) {
        event.stopPropagation()
        return
    }

    rememberKeyboardOptionIndexFromEvent(event)
}

const isImeComposing = (event: KeyboardEvent) => {
    return isComposing.value || event.isComposing || event.keyCode === 229
}

const isImeControlKey = (event: KeyboardEvent) => {
    return ['Enter', 'ArrowUp', 'ArrowDown'].includes(event.key)
}

const focusPendingOption = (event: KeyboardEvent) => {
    if (pendingOptionIndex.value === null) return

    event.preventDefault()
    event.stopPropagation()

    const optionElements = getVisibleOptionElements()
    const targetIndex = Math.min(pendingOptionIndex.value, optionElements.length - 1)
    const target = targetIndex >= 0 ? optionElements[targetIndex] : null

    pendingOptionIndex.value = null

    if (target) {
        target.focus({ preventScroll: true })
    } else {
        focusInput()
    }
}

const closeMenu = async () => {
    await nextTick()
    menuOpen.value = false
}

const updateMenuState = (value: boolean) => {
    menuOpen.value = value

    if (!value) {
        pendingOptionIndex.value = null
        lastKeyboardOptionIndex.value = null
    }
}

const updateQualifiedUsers = (value: User[] | User | null) => {
    if (!props.multiple) {
        qualifiedUsers.value = value as User | null
        if (effectiveCloseOnSelect.value) closeMenu()
        return
    }

    const values = Array.isArray(value) ? value : value ? [value] : []
    const nextValues = props.limit ? values.slice(0, props.limit) : values
    qualifiedUsers.value = nextValues

    if (effectiveCloseOnSelect.value || (props.limit && nextValues.length >= props.limit)) {
        closeMenu()
    } else {
        pendingOptionIndex.value = lastKeyboardOptionIndex.value
        lastKeyboardOptionIndex.value = null
        focusInput()
    }
}

const validate = async (passive?: boolean) => {
    if (passive && !trigger.value) return

    const { isValid, errorMessage } = await validator(props.rules, qualifiedUsers.value)
    error.value = errorMessage || ''
    trigger.value = true
    return { valid: isValid }
}

const selectAll = (flag: boolean) => {
    if (!props.multiple) return

    qualifiedUsers.value = flag ? options.value : []
}

const selectBy = (list: User[]) => {
    if (!props.multiple) {
        const user = list[0] ?? null
        if (user && !options.value.some(option => option.id === user.id)) {
            options.value.push(user)
        }
        qualifiedUsers.value = user
        return
    }

    const selected = selectedList.value

    list.forEach(user => {
        const valid = options.value.some(option => option.id === user.id)
        if (!valid) {
            options.value.push(user)
        }

        const exists = selected.some(option => option.id === user.id)
        if (!exists) {
            selected.push(user)
        }
    })

    qualifiedUsers.value = props.limit ? selected.slice(0, props.limit) : selected
}

defineExpose({ validate, selectAll, selectBy, options })
</script>
<style lang="scss">
.member-selector-shell {
    background: inherit;
    border: 1px solid var(--primary-color);
    position: relative;
}

.member-selector-disabled {
    opacity: 0.75;
}

.global-user-select {
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

.member-selector-user {
    align-items: center;
    display: flex;
    font-size: 13px;
    gap: 10px;
    min-width: 0;
    padding: 5px 0;

    p {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.member-selector-single {
    margin-right: 5px;
}

.member-selector-chip {
    max-width: 100%;
}

.member-selector-option {
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

.member-selector-option:focus,
.member-selector-option:focus-visible,
.member-selector-option.v-list-item--active,
.member-selector-option.v-list-item--focus,
.member-selector-option.v-list-item--highlighted,
.member-selector-option[aria-selected="true"] {
    background: var(--bg2) !important;
}

.member-selector-option.v-list-item--disabled {
    background: inherit !important;
    color: inherit !important;
    opacity: 0.4;
}
</style>
