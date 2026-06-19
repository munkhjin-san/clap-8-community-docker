<template>
    <div class="admin-window">
        <FloatButton @action="openModal">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
        <header class="admin-control-toolbar" aria-label="実績操作">
            <div class="admin-control-actions">
                <button type="button" class="admin-button" :disabled="fetch == 0" @click="exportCsv">
                    CSV出力
                </button>
            </div>
        </header>
        <div v-if="list.length" class="office-list">
            <div class="office-box mobile:bg-[var(--bg3)]" v-for="office in list">
                <p>営業所名: {{ office?.name }}</p>
                <p>住所: {{ office?.address }}</p>
                <p>電話番号: {{ office?.tel }}</p>
                <p>ファックス番号: {{ office?.fax }}</p>
                <div class="flex items-center gap-[5px] mt-[15px] cursor-pointer" @click="projectUsers.setProjectUsers({active: true, title: `${office.name}メンバー`, userList: office.employees})">
                    <div>メンバー：</div>
                    <div class="flex items-center gap-[5px]">
                        <div class="px-[6px] py-[2px] bg-[var(--bg3)] text-[13px]" v-for="member in office.employees.slice(0, 3)">
                            <div>{{ member.name }}</div>
                        </div>                        
                    </div>
                    <p>({{ office?.employees?.length }})</p>
                </div>
                <div style="position: absolute;right: 20px;top: 20px;">                                            
                    <ItemMenu :items="[
                        {title: '編集する', action: () => edit(office)},
                        {title: '削除する', action: () => remove(office)}
                    ]"/> 
                </div>
            </div>
        </div>
        <div v-else-if="fetch > 0" style="height: 100%;width: 100%;text-align: center;justify-content: center;display: flex;align-items: center;color: gray;">現在データはありません</div>
        <Transition name="modalFade">
            <AdminOfficeCreate v-if="create" :editTarget="editTarget" @close="closeModal"/>
        </Transition>
    </div>

</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AdminOfficeCreate from './AdminOfficeCreate.vue';
import { Office } from '@/interface/globalInterface';
import FloatButton from '@/components/Global/FloatButton.vue'
import ItemMenu from '@/components/Global/ItemMenu.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useProjectUsers } from '@/store/projectUsers';
import { mkConfig, generateCsv, download } from 'export-to-csv';
import { DateTime } from 'luxon';
import { Project } from '@/interface/projectInterface.js';
const fetch = ref(0)
const list = ref<Office[]>([])
const editTarget = ref<Office | null>(null)
const create = ref(false)
const api = useApi()
const { toast, ask, ping } = useDialog()
const projectUsers = useProjectUsers()
onMounted(() => {
    getOffices()
})

const getOffices = async() => {
    list.value = await api.get('/get_office_list')
    fetch.value ++
}
const openModal = () => {
    editTarget.value = null
    create.value = true
}
const closeModal = (flag: boolean) => {
    if(flag){
        getOffices()
    }
    create.value = false
    editTarget.value = null
}
const edit = (office: Office) => {
    editTarget.value = office
    create.value = true
}
const remove = async(office: Office) => {

    const data =await api.del(`/office_item?id=${office.id}`, {}, {
        ask: `本当に${office.name}を削除しますか？`,
        toast: '営業所を削除しました',
    })
    if(data === null){
        return;
    }
    getOffices()
    
}
const exportCsv = () => {
    if (!list.value.length) return;

    const rows = list.value.flatMap((office) => {
        if (!office.employees?.length) return [];

        return office.employees.map((employee) => ({
        '氏名': employee.name,
        '区分（社内・社外）': employee.position_id === 14 ? '社外' : '社内',
        'プロジェクト': employee.related_projects
            ?.map((project: Project) => project.name)
            .join(', ') ?? '',
        '営業所': office.name,
        '住所': office.address,
        '日付': DateTime.now().toLocaleString(),
        }));
    });

    if (!rows.length) return;

    const csvConfig = mkConfig({
        useKeysAsHeaders: true,
        filename: '勤務地リスト',
        useBom: true,
        replaceUndefinedWith: '',
    });

    const csv = generateCsv(csvConfig)(rows);
    download(csvConfig)(csv);
};
</script>
<style>
.office-list{
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    height: calc(100% - 40px);
    overflow: hidden auto;
}
.office-box{
    background: var(--background-color);
    padding: 20px;
    line-height: 2;
    font-size: 14px;
    position: relative;
}
</style>