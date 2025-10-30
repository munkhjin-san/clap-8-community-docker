<template>
    <div>
        <div class="flex flex-col gap-4">
            <div v-for="office in offices" :key="office.id" class="p-4 border bg-[var(--background-color)] text-[14px] flex flex-col gap-3 mx-[20px]">
                <p class="font-bold mb-2">{{ office.name }}</p>
                <div class="flex items-center leading-normal gap-[15px] under960:flex-col under960:items-start" >
                    <p><span>住所：</span><span v-if="office.post_code_1 && office.post_code_2">〒{{ office.post_code_1 }} - {{ office.post_code_2 }}</span> {{ office.address }}</p>           
                    <CommandButton :buttons="[{title: 'コピー', action: () => copy(office, 'address')}]"/>
                </div>
                <div v-if="office.address" class="flex items-center leading-normal gap-[15px] under960:flex-col under960:items-start">
                    <a :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(office.address)}`" target="_blank" class="text-[var(--link-color)] underline">地図で見る</a>
                </div>
                <div v-if="office.tel" class="flex items-center leading-normal gap-[15px] under960:flex-col under960:items-start">
                    <p><span>電話番号：</span><a :href="`tel:${office.tel}`">{{ office.tel }}</a></p>
                    <CommandButton :buttons="[{title: 'コピー', action: () => copy(office, 'tel')}]"/>
                </div>
                <div class="flex items-center gap-[5px] cursor-pointer" @click="projectUsers.setProjectUsers({active: true, title: `${office.name}メンバー`, userList: office.employees})">
                    <div>メンバー：</div>
                    <div class="flex items-center gap-[5px]">
                        <div class="px-[6px] py-[6px] bg-[var(--bg3)] text-[13px]" v-for="member in office.employees.slice(0, 3)">
                            <div>{{ member.name }}</div>
                        </div>                        
                    </div>
                    <p>({{ office?.employees?.length }})</p>
                </div>
                
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { Office } from '@/interface/globalInterface';
import { onMounted, ref } from 'vue';
import CommandButton from '../Global/CommandButton.vue';
import { useDialog } from '@/composables/dialog';
import { useProjectUsers } from '@/store/projectUsers';
const offices = ref<Office[]>([])
const api = useApi()
const { toast } = useDialog()
onMounted(() => {
    getOfficeList();
})
const getOfficeList = async () => {
    const data = await api.get('/get_office_list');
    offices.value = data;
};
const projectUsers = useProjectUsers()
const copy = (office: Office, field: string) => {
    let text = '';
    if(field === 'address'){
        text = `〒${office.post_code_1} - ${office.post_code_2} ${office.address}`;
    }
    if(field === 'tel'){
        text = office.tel;
    }
    try{
        navigator.clipboard.writeText(text);
        toast('コピーしました');
    } catch (err) {
        toast('コピーに失敗しました');
    }
};
</script>