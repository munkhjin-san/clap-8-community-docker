<template>
    <div style="background:inherit;">
        <div ref="selectorRef" style="position:relative;background:inherit;">
            <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);">
                <v-autocomplete
                    chips
                    :items="options"
                    item-title="name"
                    item-value="id"
                    :multiple="true"
                    closable-chips
                    flat
                    tile
                    bg-color="var(--background-color)"
                    clear-on-select
                    hide-details
                    hide-selected
                    hide-no-data
                    eager
                    :label="placeHolder"
                    :menu-props="{ scrollStrategy: 'close', maxWidth: selectorRef ? selectorRef.clientWidth : undefined }"
                    :model-value="selected"
                    :no-filter="true"
                    :loading="searching"
                    @update:modelValue="update"
                    @update:search="onSearch"
                >
                    <template v-slot:chip="{ props, item }">
                        <v-chip closable v-bind="props" :text="item.raw.name" :close-icon="CloseIcon" rounded="0" density="compact"></v-chip>
                    </template>
                    <template v-slot:item="{ props, item }">
                        <v-list-item v-bind="props" :title="item.raw.name" rounded="0" density="compact" :ripple="false" variant="flat"></v-list-item>
                    </template>
                    <template v-slot:loader="{ isActive }">
                        <Transition name="modalFade">
                            <div v-if="isActive">
                                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                            </div>
                        </Transition>
                    </template>
                </v-autocomplete>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import 'styles/selector.css'
import CloseIcon from './CloseIcon.vue'
import { useApi } from '@/composables/api'
import { useDebouncedRef } from '@/utils/tools'

interface PartnerOption {
    id: number
    name: string
    available: boolean
}

defineProps<{ placeHolder?: string }>()

/** 取引先マスタのID配列。名称ではなくIDで持つ（改名しても紐付けが切れないため）。 */
const selected = defineModel<number[]>({ default: () => [] })

const api = useApi()
const options = ref<PartnerOption[]>([])
const searching = ref(false)
const selectorRef = ref<HTMLElement | null>(null)
const searchKey = useDebouncedRef('')

/**
 * 候補を取り直す。選択済みのIDも一緒に送って必ず候補へ含めてもらう
 * （検索語で絞り込んだ瞬間に、選択済みのチップの名前が消えるのを防ぐ）。
 */
const fetchOptions = async (keyword = '') => {
    searching.value = true
    try {
        const response = await api.get('/partner_record_options', {
            keyword: keyword || undefined,
            selected: selected.value ?? [],
        }, { cancel: true })
        options.value = response?.partners ?? []
    } catch {
        // メッセージは useApi が表示済み
    } finally {
        searching.value = false
    }
}

watch(() => searchKey.value, (keyword) => fetchOptions(keyword))

/*
 * 選択済みIDに対応する候補が手元に無ければ取り直す。
 * 子のonMountedは親より先に走るため、親が編集データからIDを入れるのは
 * 最初の取得より後になる。その分を埋めないとチップに名前ではなくIDが出る。
 */
watch(selected, (ids) => {
    const missing = (ids ?? []).filter(id => !options.value.some(option => option.id === id))
    if (missing.length) {
        fetchOptions(searchKey.value)
    }
}, { immediate: true })

const update = (ids: number[]) => {
    selected.value = ids
}

const onSearch = (keyword: string) => {
    searchKey.value = keyword
}

onMounted(() => fetchOptions())
</script>
