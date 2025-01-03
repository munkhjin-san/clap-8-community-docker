<template>
    <div class="admin-window">
        <div class="h-full w-full overflow-auto p-[20px]">
            <div class="w-[calc(100%-40px)] flex flex-col gap-[20px]">
                <div v-for="form in forms" class="relative bg-[var(--background-color)] flex cursor-pointer">
                    <div @click="router.push({name: 'formDetail', params: {formId: form.id}})" class="p-[20px] w-full">{{ form.title }}</div>
                    <div class="absolute right-[10px] top-[10px]">
                        <ItemMenu :items="[
                            {title: '編集', action: () => {editData = form; openModal = true}},
                            {title: '削除', action: () => {deleteForm(form.id)}}
                        ]"/>
                    </div>
                </div>
            </div>

        </div>
        <FloatButton v-if="!selectedForm" @action="openModal = true" type="plus"/>
        <CustomFormCreate v-if="openModal" @close="closeCreate" :edit-data="editData"/>

        <router-view v-slot="{ Component }">
            <transition name="slideFromRight">
                <component :is="Component" 
                    v-if="selectedForm"
                    :form="selectedForm"
                />
            </transition>
        </router-view>
    </div>
</template>
<script setup lang="ts">
import { CustomForm } from '@/interface/customFormInterface';
import axios from 'axios';
import { computed, inject, ref } from 'vue';
import { onMounted } from 'vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import CustomFormCreate from './CustomFormCreate.vue';
import { useRoute } from 'vue-router';
import router from '@/router';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { DialogKey, DialogMethods } from '@/interface/keys';

const { confirm, info, notify } = inject('dialog') as DialogMethods;

onMounted(() => {
    getForms()
})
const route = useRoute()
const forms = ref<CustomForm[]>([])
const openModal = ref(false)
const editData = ref<CustomForm | null>(null)
const selectedForm = computed(() => {
    const selectedId = route.params?.formId ? Number(route.params.formId) : null
    
    return selectedId ? forms.value.find( f => f.id == selectedId) ?? null : null 
})
const getForms = async() => {
    forms.value = await axios.get('/get_custom_forms').then(res => res.data)
}
const closeCreate = (flag:boolean) => {
    editData.value = null
    openModal.value = false
    if(flag){
        getForms()
    }
}
const deleteForm = async(id: number) => {
    if(await confirm('削除しますか？')){
        await axios.delete('/delete_custom_form', {params: {id: id}})
        getForms()
    }
}
</script>