<template>
    <div>
        <div :class="['form-wrapper', {focused: (multiple ? modelValue.length : modelValue) || focus}]">
            <span style="z-index:5" :class="['form-plc', {'focused-plc': selectedItems || (Array.isArray(selectedItems) && selectedItems.length)}]">{{ placeHolder }}</span> 
            <drop-selector
                class="one-selector"
                :options="itemOptions"
                :reduce="reduce"
                :label="label"
                :components="{Deselect}"
                :clearable="clearable"
                :multiple="multiple"
                :closeOnSelect="closeOnSelect"
                @search="handleSearch"
                @search:focus="focus = true"
                @search:blur="focus = false"
                v-model="selectedItems"
            >
            <template #no-options="{ search, searching, loading }">
                <div style="font-size: 14px;opacity: 0.8;padding:10px 0;">アイテムはありません。</div>        
            </template>
            </drop-selector> 
            
            
        </div>
        <p v-if="error" class="i-error">{{error}}</p>
    </div>  
    
        
</template>
<script setup lang="ts">
import { markRaw, onMounted, ref, watch } from 'vue';
import { validator } from '@/validation/validator'
import axios from 'axios';
    interface Props{
        placeHolder?:string
        name?:string
        rules?:string;
        multiple?: boolean
        options?: any | any[]
        path?: string
        modelValue?: number | string
        clearable: boolean,
        label: string,
        reduce: (option: any) => any;
        closeOnSelect: boolean,
    }
    const props = withDefaults(defineProps<Props>(), {
        placeHolder: '',
        name: 'optionSelector',
        rules: '',
        multiple: true,
        options: [],
        path: '',
        clearable: true,
        label: 'name',
        reduce: (option: any) => option.id,
        closeOnSelect: false
    })
    const itemOptions = ref([])
    const error = ref('')
    const trigger = ref(false)
    const emit = defineEmits(['search'])
    const focus = ref(false)
    const Deselect = markRaw({
        template: `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>`
    })      
    const selectedItems = defineModel()
    watch(() => props.path, () => {
        getPossibleItems()
    })
    watch(() => props.options, () => {
        itemOptions.value = props.options
    })
    onMounted(() => {
        console.log(props.modelValue)
        if (props.options.length) {
            itemOptions.value = props.options
        } else if (props.path) {
            getPossibleItems()
        }
    })

    const getPossibleItems = async() =>{
        try {
            const response = await axios.get(`/${props.path}`)
            itemOptions.value = response.data
        } catch (e) {

        }
    }
    const validate = async (passive?:boolean) => {
        if(passive && !trigger.value) return

        const { isValid, errorMessage }= await validator(props.rules ? props.rules : '', selectedItems.value)
        error.value = errorMessage!
        trigger.value = true
        return {valid: isValid}
    };
    const handleSearch = (keyword: string) => {
        emit('search', keyword)
    }
    defineExpose({validate})

</script>
<style lang="scss">
    .one-selector {
        width: 100%;
        border: 1px solid var(--primary-color) !important;
    }
    .one-selector{
        .vs__actions {
            display: flex; 
            margin-right: 10px;
            padding: 0;
            align-items: center;
            margin-top: -10px;
        }
        .vs__clear{
            fill: var(--primary-color);
            svg{
                width: 10px;
                height: 10px;
            }
        }
    }
    .one-selector > .vs__dropdown-menu > .vs__dropdown-option{
        padding: 10px;
    }
    .vs__dropdown-option--disabled{
        background: inherit !important;
        color: inherit !important;
        opacity: 0.4;

    }
</style>