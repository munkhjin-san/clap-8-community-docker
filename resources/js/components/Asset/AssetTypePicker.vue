<template>
    <div style="background:inherit;">        
        <div ref="tagSelectorRef" style="position:relative;background:inherit;">
            <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);">
                <v-autocomplete
                    chips
                    :items="searchResult"
                    :multiple="false"                    
                    closable-chips
                    flat
                    tile
                    bg-color="var(--background-color)"
                    clear-on-select
                    hide-details
                    hide-selected
                    hide-no-data
                    :label="placeHolder"
                    :menu-props="{ scrollStrategy: 'close', maxWidth: tagSelectorRef ? tagSelectorRef.clientWidth : undefined}"
                    @update:modelValue="update"
                    @update:search="search"
                    :model-value="selectedTag"
                    :no-filter="true"             
                    
                >
                    <template v-slot:chip="{ props, item }">
                        <v-chip closable v-bind="props" v-if="item.raw" :text="item.raw" :close-icon="CloseIcon" rounded="0" density="compact"></v-chip>
                    </template>
                    <template v-slot:item="{ props, item }">
                        <v-list-item v-bind="props" :text="item.raw" rounded="0" density="compact" :ripple="false" variant="flat"></v-list-item>                    
                    </template>
                    <template v-slot:loader="{ props, isActive}">
                        <Transition name="modalFade">
                            <div v-if="isActive">
                                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                            </div> 
                        </Transition>
                    </template>
                </v-autocomplete>                  
            </div>   
            <p v-if="error" class="i-error">{{error}}</p>         
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, watch, } from 'vue';
import 'styles/selector.css';
import { useDebouncedRef } from '@/utils/tools'
import CloseIcon from '@/components/Form/CloseIcon.vue';
import { validator } from '@/validation/validator';
import { useApi } from '@/composables/api';
    const props = defineProps<{
        placeHolder?: string
        modelValue: string
        rules?: string
        options: string[]
    }>()

    const error = ref('')
    const trigger = ref(false)
    const selectedTag = defineModel<string>()
    const tagSelectorRef = ref<HTMLElement | null>(null)
    const searching = ref(false)
    // onMounted(() => {
    //     superFetch()
    // })
    
    const searchKey = useDebouncedRef('')

    // watch(() => searchKey.value, (after) => {   
    //     console.log(after)
    //     after ? normalFetch(after) : superFetch()
       
        
    // })
    // const normalFetch = async (key) => {
    //     searching.value = true
    //     const data = await api.post('/get_asset_types', {key: key, super: false})
    //     tagOptions.value = []
    //     // data.forEach((element:string) => {
    //     //     tagOptions.value.push(element)
    //     // });
    //     searching.value = false   
    // }
    // const superFetch = async() => {
    //     const data = await api.post('/get_asset_types', {key: '', super: true,}) 
    //     // tagOptions.value = []
    //     // data.forEach((element:string) => {
    //     //     tagOptions.value.push(element)
    //     // });    
    // }

    const searchResult = computed(() => {
        if(!searchKey.value) return props.options
        return props.options.filter(tag => tag.toLocaleLowerCase().includes(searchKey.value.toLocaleLowerCase()))
    })

    const update = (p) => {
        selectedTag.value = p
        console.log(p)
    }
    const search = (newTag:string) => {
        searchKey.value = newTag
    }
    const validate = async (passive?:boolean) => {
        if(passive && !trigger.value) return

        const { isValid, errorMessage }= await validator(props.rules ? props.rules : '', selectedTag.value)
        error.value = errorMessage!
        trigger.value = true
        return {valid: isValid}
    };

    defineExpose({validate})

</script>
<style lang="scss">
.selectorFocus{
    border: 1px solid var(--primary-color) !important;
}
.global-user-select{
    border: none !important;
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