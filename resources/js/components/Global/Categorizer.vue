<template>
    <div class="relative">
        <div @click.stop="menu.setMenu({parent: `categorizer_${type}`})" class="c-bar-button !text-[12px] whitespace-nowrap !px-[5px]">{{ selected }}</div>
        <Transition name="slidePop">
            <div :id="`categorizer_${type}`" v-if="menu.parent == `categorizer_${type}`" class="min-w-[200px] absolute top-[40px] right-[0px] z-[15] shadow-me p-[10px] bg-[var(--background-color)]">
                <div>
                    <div class="mb-[10px]">{{ type }}</div>
                    <div class="ml-[10px] flex flex-col gap-[10px] text-[13px] max-h-[40vh] overflow-y-auto">                        

                        <label :for="`u_all`" class="radio-button h-[30px]">
                            <input @change="emit('update')" v-model="selectedOption" class="fish-eye" type="radio" :id="`u_all`" name="u-select" :value="null"/>
                            <span class="custom-radio">
                                <svg class="checkmark-t" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32">
                                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                </svg>
                            </span>
                            <span class="label-text">すべて</span>
                        </label>

                        <label v-for="option in options" class="radio-button" :for="`u_${option.id.toString()}`">
                            <input @change="emit('update')" class="fish-eye" v-model="selectedOption" type="radio" :id="`u_${option.id.toString()}`" name="u-select" :value="option.id"/>
                            <span class="custom-radio">
                                <svg class="checkmark-t" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32">
                                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                </svg>
                            </span>
                            <span v-if="type === 'メンバー'" class="label-text"><UserPanel :user="option" size="25" disable-instant with-name :img-class="'userMidIcon'"/></span>
                            <span v-else class="label-text">{{ option.name }}</span>
                        </label>
                    </div>

                </div>      
            </div>
        </Transition>
    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import { computed, onMounted, ref } from 'vue';
import { useMenuStore } from '@/store/menu';
import { useApi } from '@/composables/api';
const props = defineProps<{
  selectableOptions?: any[]
  type: string
  path: string
}>()
const emit = defineEmits<{
    update: []
}>()
const options = ref<any[]>([])
const menu = useMenuStore()
const api = useApi()
const selectedOption = defineModel<number | null>()

const selected = computed(() => {
    const user = selectedOption.value ? options.value?.find( u => u.id == selectedOption.value) : null
    const name = user ? user.name : `${props.type} : すべて`
    return name       
})    
const getPossibleOptions = async() => {

    const response = await api.get(`/${props.path}`)
    options.value = response

}
onMounted(() => {
    if (props.path) {
        getPossibleOptions()
    } else if (props.selectableOptions) {
        options.value = props.selectableOptions
    }
})
</script>
<style scoped>
.radio-group {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.radio-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.radio-button input {
  display: none;
}

.custom-radio {
    width: 15px;
    height: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    transition: border-color 0.3s ease;
    position: relative;
}

.custom-radio .checkmark-t {
  display: none;
  fill: var(--primary-color);
}

.radio-button input:checked + .custom-radio {
  border-color: green;
}

.radio-button input:checked + .custom-radio .checkmark-t {
  display: block;
}

.label-text {
  font-size: 13px;
}
</style>