<template>
    <div>        
        <div class="bg-[var(--background-color)] mr-[20px] under960:ml-[20px] mt-[20px]">            
            <div class="w-[300px] under960:w-full mb-5 ml-5 relative">
                <PostSearchBar
                    className="newChatMemberSearch" 
                    :customPlaceHolder="'検索'"
                    @search-start="(key) => keyword = key"
                />                       
                <div v-if="keyword.length" class="absolute top 40px shadow-lg bg-[var(--background-color)] left-0 w-full z-[5] max-h-[60vh] overflow-y-auto">
                    <div class="p-3">
                        <button class="bg-[var(--bg3)] w-full py-2 rounded" @click="searchFromFiles">
                            規則・規定ファイルから検索
                        </button>
                        <div v-if="searching" class="spinner-micro mx-auto my-5"></div>
                        <div v-if="!searching && chunks.length">
                            <div class="text-[12px] text-[gray] my-3">検索結果:<strong>{{ chunks.length }}件</strong></div>
                            <div class="space-y-2">
                                <div v-for="(chunk, index) in chunks" :key="index" class="mb-3 p-2 border rounded">
                                    <p class="text-[12px] text-[gray] whitespace-break-spaces leading-normal" v-html="highlightKeyword(chunk.text)"></p>
                                    <div class="text-sm mt-1 p-2 bg-[var(--bg3)] rounded">
                                        <div @click="openTargetFile(chunk)" class="flex mt-2 cursor-pointer">
                                            <FileIcon ext="pdf"/>
                                            <div class="ml-2">
                                                <div class="text-[12px]">{{ chunk.title }}</div>      
                                                <div class="text-[gray] text-[11px]">{{ chunk.pageNumber ? `ページ ${chunk.pageNumber}` : '' }}</div>                                           
                                            </div>                                        
                                        </div>  
                                                                
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>         
            </div> 
            <p class="text-[11px] text-[gray] ml-5">全ての規則・規定ファイルからキーワードで検索できます</p>
            <div class="regulations-list">
                <RegulationItem 
                    v-for="regulation in filteredRegulations" 
                    :key="regulation.id"
                    :regulation="regulation"
                    @edit="editRegulation"
                    @delete="deleteRegulation"
                    :is-authorized="isAuthorizedAccount"
                />
                <div v-if="filteredRegulations.length === 0" class="empty-state">
                    <p>{{ keyword.length ? '該当する規則が見つかりません。' : '規則がまだ作成されていません。' }}</p>
                </div>
            </div>
        </div>
        
        <RegulationCreate 
            v-if="showCreateForm"
            :editTarget="editTarget"
            @close="handleCreateClose"
            
        />
        <FloatButton v-if="isAuthorizedAccount" @action="showCreateForm = true">
            <template #icon>
                <AddIcon size="15"/>
            </template>
        </FloatButton>
        

    </div>
</template>
<script setup lang="ts">
import FloatButton from '@/components/Global/FloatButton.vue';
import { useApi } from '@/composables/api';
import { computed, onMounted, ref, watch } from 'vue';
import RegulationItem from './RegulationItem.vue';
import RegulationCreate from './RegulationCreate.vue';
import { Regulation } from '@/interface/regulationInterface';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useAuthUserStore } from '@/store/auth';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import FileIcon from '@/components/Board/Mixed/FileIcon.vue';
import { useFilePreview } from '@/store/filePreview';

const props = defineProps<{
    tagList: any[],
    qaList: any[],
    chatBoxWindow: boolean
}>()

const emit = defineEmits<{
    setChatBoxWindow: [val: boolean]
}>()

const keyword = ref('')
const api = useApi();
const regulations = ref<Regulation[]>([]);
const chatBoxWindow = ref(false);
const showCreateForm = ref(false);
const editTarget = ref<Regulation | null>(null);
const auth = useAuthUserStore()
const searching = ref(false)
const filePreview = useFilePreview()
const currentKeyword = ref('')
const chunks = ref<{
    pageNumber: number | null
    title: string | null
    text: string | null
}[]>([])

const filteredRegulations = computed(() => {
    if(!keyword.value) return regulations.value;
    return regulations.value.filter(reg => reg?.title?.includes(keyword.value) || reg?.content?.includes(keyword.value))
})
const loadRegulations = async () => {

    const response = await api.get('/get_regulation_list');
    regulations.value = response

};

const isAuthorizedAccount = computed(() => {
    return auth.activeUser && auth.activeUser.id && [610, 608].includes(auth.activeUser.id) ? true : false;
})

const editRegulation = (regulation: Regulation) => {
    editTarget.value = regulation;
    showCreateForm.value = true;
};

const deleteRegulation = async (regulation: Regulation) => {

    await api.post(`/regulation_delete`, { id: regulation.id }, {
        toast: '削除しました。',
        ask: '本当に削除しますか？'
    });
    await loadRegulations();

};

const handleCreateClose = async (refreshNeeded: boolean) => {
    showCreateForm.value = false;
    editTarget.value = null;
    
    if (refreshNeeded) {
        await loadRegulations();
    }
};
const searchFromFiles = async () => {
    if(!keyword.value) return;
    currentKeyword.value = keyword.value;
    searching.value = true;
    const response = await api.get('/search_regulations_from_files', { keyword: keyword.value });
    chunks.value = response.chunks ?? [];
    searching.value = false;
}
onMounted(() => {
    loadRegulations();
});
const openTargetFile = (chunk: typeof chunks.value[0]) => {
    if(!chunk || !chunk.title) return;
    const foundFile = regulations.value.flatMap(r => r.regulation_files).find(f => f.name == chunk.title);
    if(foundFile) {
        const files = [{
            ...foundFile,
            file_path: `/cdn/regulation_files/${foundFile.path}.${foundFile.extension}`,
            doc_path: `/regulation_files/${foundFile.path}.${foundFile.extension}`,
            initialPage: chunk.pageNumber ? chunk.pageNumber : 1
        }];     
        
        const data = {
            active: true,
            files,
            source: 'calendar',
            index: 0,
            message: null,
            initialPage: chunk.pageNumber ? chunk.pageNumber : 1
        }
        filePreview.setFilePreview(data)
    }
}
const highlightKeyword = (text: string | null) => {
    if(!text || !currentKeyword.value) return text;
    const escapedKeyword = currentKeyword.value.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    const regex = new RegExp(`(${escapedKeyword})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}

watch(keyword, (newVal) => {
    if(!newVal) {
        chunks.value = [];
        currentKeyword.value = '';
    }
})
</script>
<style scoped>
.regulations-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 0 20px;
}

.regulations-header h2 {
    margin: 0;
    color: #333;
    font-size: 24px;
}

.create-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--primary-color, #007bff);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 10px 16px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.create-btn:hover {
    background: var(--primary-color-dark, #0056b3);
}

.regulations-list {
    padding: 0 20px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state p {
    margin: 0;
    font-size: 16px;
}
.ref-file-icon :deep(svg){
    width: 20px;
    height: 20px;
    margin-right: 4px;
}
@media screen and (max-width: 959px) {
    .regulations-list {
        padding: 0;
    }
}


</style>
