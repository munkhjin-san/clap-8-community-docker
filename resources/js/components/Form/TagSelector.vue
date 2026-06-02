<template>
    <div class="bg-inherit">        
        <div ref="tagSelectorRef" class="relative bg-inherit">
            <div class="relative bg-inherit border border-solid border-[var(--primary-color)]">
                <v-combobox
                    chips
                    :items="comboboxItems"
                    :multiple="true"
                    closable-chips
                    :close-on-select="false"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    flat
                    tile
                    bg-color="var(--background-color)"
                    hide-details
                    hide-selected
                    :label="placeHolder"
                    :menu-props="{ scrollStrategy: 'close', maxWidth: tagSelectorRef ? tagSelectorRef.clientWidth : undefined}"
                    :model-value="selectedTag"
                    v-model:search="searchKeyword"
                    :no-filter="true"
                    item-title="text"
                    return-object
                    name="tag-selector"
                    class="tag-selector-combo"
                    @update:modelValue="updateSelectedTag"
                    @update:search="handleSearch"
                    @compositionstart="isComposing = true"
                    @compositionend="isComposing = false"
                    @keydown.capture="handleKeydown"
                >
                    <template v-slot:chip="{ props, item }">
                        <v-chip 
                            closable 
                            v-bind="props" 
                            v-if="item.raw" 
                            :text="item.raw.text" 
                            :close-icon="CloseIcon" 
                            rounded="0" 
                            density="compact"
                            size="small"
                        ></v-chip>
                    </template>
                    <template v-slot:item="{ props, item }">
                        <v-list-item class="tag-selected" v-bind="props" :text="item.raw.text" rounded="0" density="compact" :ripple="false" variant="flat" @click="item.raw.isCreate ? addCustomTag() : undefined"></v-list-item>
                    </template>
                    <template v-slot:no-data>
                        <div style="padding: 6px 0;">
                            <div v-if="!canCreateTag" style="font-size: 13px; opacity: 0.5; padding: 10px;">
                                タグを検索するにはキーワードを入力してください
                            </div>
                        </div>
                    </template>
                    <template v-slot:loader="{ isActive }">
                        <Transition name="modalFade">
                            <div v-if="isActive">
                                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                            </div> 
                        </Transition>
                    </template>
                </v-combobox>
            </div>        
        </div>
    </div> 
</template>
<script setup lang="ts">
import { computed, onMounted, ref, useTemplateRef } from 'vue';
import { debounce } from '@/utils/tools';
import { useApi } from '@/composables/api';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import 'styles/selector.css';

    type TagOption = {
        text: string,
        id: string | number
    }

    type ComboboxOption = TagOption & {
        isCreate?: boolean
    }

    const props = defineProps<{ placeHolder: string, specialTags?: string[], suggestion: string, condition?: any }>()
    const tagOptions = ref<TagOption[]>([])
    const selectedTag = defineModel<TagOption[]>({ default: [] })
    const superCounter = ref(0)
    const searchKeyword = ref('')
    const isComposing = ref(false)
    const api = useApi()
    const tagSelectorRef = useTemplateRef('tagSelectorRef')

    const normalizedSearch = computed(() => searchKeyword.value.trim().replace(/[＃#]/g, ''))
    const canCreateTag = computed(() => {
        if (!normalizedSearch.value) return false
        return !tagOptions.value.some(tag => tag.text === normalizedSearch.value)
            && !selectedTag.value.some(tag => tag.text === normalizedSearch.value)
    })
    const createOption = computed<ComboboxOption | null>(() => {
        if (!canCreateTag.value) return null

        return {
            text: `「${normalizedSearch.value}」を追加`,
            id: '__create__',
            isCreate: true,
        }
    })
    const comboboxItems = computed<ComboboxOption[]>(() => {
        return createOption.value ? [createOption.value, ...tagOptions.value] : tagOptions.value
    })

    onMounted(() => {
        superFetch()
    })      

    const search = debounce(async(key: string) => {
        const data = await api.post('/post_get_tags', {key: key, super: false, condition: props.condition})    
        tagOptions.value = data
        
    }, 350)

    const handleSearch = (keyword: string) => {
        searchKeyword.value = keyword
        search(keyword)
    }

    const handleKeydown = (event: KeyboardEvent) => {
        if (isImeComposing(event) && isImeControlKey(event)) {
            event.stopPropagation()
            return
        }

        if (event.key !== 'Enter') return
        if (!canCreateTag.value) return

        event.preventDefault()
        event.stopPropagation()
        addCustomTag()
    }

    const isImeComposing = (event: KeyboardEvent) => {
        return isComposing.value || event.isComposing || event.keyCode === 229
    }

    const isImeControlKey = (event: KeyboardEvent) => {
        return ['Enter', 'ArrowUp', 'ArrowDown'].includes(event.key)
    }

    const toTagOption = (value: TagOption | string) => {
        if (typeof value === 'string') {
            return {
                text: value.trim().replace(/[＃#]/g, ''),
                id: randomId(),
            }
        }

        return value
    }

    const normalizeSelectedTag = (values: Array<TagOption | string>) => {
        const map = new Map<string, TagOption>()

        for (const value of values) {
            const option = toTagOption(value)
            if ('isCreate' in option && option.isCreate) continue
            if (!option.text) continue
            if (!map.has(option.text)) map.set(option.text, option)
        }

        return Array.from(map.values())
    }

    const updateSelectedTag = (values: Array<TagOption | string>) => {
        if (values.some(value => typeof value !== 'string' && 'isCreate' in value && value.isCreate)) {
            addCustomTag()
            return
        }

        selectedTag.value = normalizeSelectedTag(values)
    }

    const addCustomTag = () => {
        if (!canCreateTag.value) return

        const nextTag = {
            text: normalizedSearch.value,
            id: randomId(),
        }

        tagOptions.value = [nextTag, ...tagOptions.value]
        selectedTag.value = [...selectedTag.value, nextTag]
        searchKeyword.value = ''
    }

    const superFetch = async () => {
        if(superCounter.value > 0) return
        const data = await api.post('/post_get_tags', 
            {
                key: '', 
                super: true, 
                special: props.specialTags && props.specialTags.length ? props.specialTags : [],
                condition: props.condition
            }
        )
        tagOptions.value = data
        superCounter.value ++   
    }
    const randomId = () => {
        return Math.random().toString(36).substring(5);
    }



</script>
<style lang="scss">
.selectorFocus{
    border: 1px solid var(--primary-color) !important;
}
.global-user-select{
    border: none !important;
    width: 100%;
}
.tag-selected {
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
@supports selector(:focus-visible) {
    .v-list-item:after {
        border: none !important;
        background: var(--bg2);
        border-radius: 0;
        color: var(--primary-color);
    }
    .v-list-item:focus-visible:after {
        opacity: 0.5;
    }
}
.v-field__loader{
    left: auto;
    right: 15px;
    width: fit-content;
    top: 20px;
}
</style>
