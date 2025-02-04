<template>
    <div>
        <Transition name="modalFade">
            <div class="member-loader" v-if="initialLoader == 0">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="no-comment-text" v-if="initialLoader > 0 && !contacts.length">現在データはありません。</div>
        <ContactViewToggle v-if="!responsive.mobile" style="position: fixed" :type="viewType" @action="setViewType"/>
        <FloatButton :style="{position: 'fixed', bottom: auth.user?.footer_view && responsive.mobile ? '65px' : '20px'}" @action="createWindow = true" type="plus"/>
        <div class="flex gap-[10px] flex-wrap mb-[20px] ml-[20px]">
            <label :class="['text-[13px] bg-[var(--background-color)] select-none text-[var(--primary-color)] px-[8px] py-[5px] cursor-pointer', {'!bg-[var(--primary-color)] !text-[var(--background-color)]': type.id && selectedTypes.includes(type.id)}]" v-for="type in contactTypes">
                <input v-model="selectedTypes" type="checkbox" class="hidden" :value="type.id"/>
                {{ type.title }}
            </label>
        </div>
        <div>            
            <GridLayout 
                v-if="viewType == 'grid'" 
                :contacts="contactList"
            />
            <TableLayout 
                v-if="viewType == 'table'" 
                :contacts="contactList"
            />
        </div>
        <Transition name="modalFade">
            <ContactCreate
                v-if="createWindow"
                :edit-data="editData"
                :contact-types="contactTypes"
                @close="(flag) => closeCreate(flag)"
            />
        </Transition>
        <router-view v-slot="{ Component }">
            <transition name="modalFade">
                <component
                    v-if="activeContact && !editData"
                    :is="Component"
                    :contact="activeContact"
                    @edit="(contact:ContactRecord) => { editData = contact; createWindow = true}"
                    @delete="(id:number) => {deleteContact(id)}"
                ></component>
            </transition>
        </router-view>
    </div>
</template>
<script setup lang="ts">
import { useResponsive } from '@/store/responsive';
import FloatButton from '@/components/Global/FloatButton.vue';
import ContactCreate from './ContactCreate.vue';
import { inject, ref } from 'vue';
import { ContactRecord, ContactType } from '@/interface/contactInterface';
import { onMounted } from 'vue';
import axios from 'axios';
import GridLayout from './Grid/GridLayout.vue';
import TableLayout from './Table/TableLayout.vue';
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { DialogMethods } from '@/interface/globalInterface';
import ContactViewToggle from './ContactViewToggle.vue';
import { useAuthUserStore } from '@/store/auth';

const props = defineProps<{
    keyword: string
}>()
const router = useRouter()
const responsive = useResponsive()
const auth = useAuthUserStore()
const editData = ref<ContactRecord | null>(null)
const createWindow = ref(false)
const contacts = ref<ContactRecord[]>([])
const initialLoader = ref(0)
const viewType = ref('grid')
const contactTypes = ref<ContactType[]>([])
const selectedTypes = ref<number[]>([])
const route = useRoute()
const { confirm } = inject('dialog') as DialogMethods;

onMounted(() => {
    if(!responsive.mobile){
        viewType.value = localStorage.getItem('contactViewType') || 'grid'
    }    
    getContacts()
    getTypes()
})
const getTypes = async() => {
    contactTypes.value = await axios.get('/get_contact_types').then(res => res.data)
}
const activeContact = computed(() => {
  const contactId = Number(route.params.contactId);
  return contactId
    ? contacts.value.find(contact => contact.id === contactId) || null
    : null;
});
const contactList = computed(() => {  
    if(!props.keyword && !selectedTypes.value.length) return contacts.value
    return contacts.value.filter(contact => {
        const matchedCategory = selectedTypes.value.length ? (contact.contact_type_id && selectedTypes.value.includes(contact.contact_type_id)) : true
        const matchedKey =  Object.values(contact).some(val => 
            String(val).toLowerCase().includes(props.keyword)
        )
        return matchedCategory && matchedKey
    })
})

const getContacts = async() => {
    contacts.value = await axios.get('/contact_list').then(res => res.data)
    initialLoader.value++
}
const deleteContact = async(id:number) => {
    const confirmed = await confirm('コンタクトを削除しますか。')
    console.log(confirmed)
    if(!confirmed) return
    await axios.delete('/contact_item', {params: {id: id}})
    getContacts()
    router.push({name: 'tab2'})
}
const closeCreate = (flag:boolean) => {
    createWindow.value = false
    editData.value = null
    if(flag) {
        getContacts()
    }
}

const setViewType = () => {
    viewType.value = viewType.value == 'grid' ? 'table' : 'grid'
    localStorage.setItem('contactViewType', viewType.value)                    
}
</script>