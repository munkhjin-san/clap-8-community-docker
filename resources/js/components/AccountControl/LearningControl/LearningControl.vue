<template>
    <div style="gap: 0;background: var(--background-color);height: 100%">  
         
        <Transition name="modalFade">                              
            <ThemeCreate
                v-if="createThemeWindow"
                @closeModal="closeThemeCreate"
                :editTarget="editThemeTarget"
            />
            
        </Transition>      
        <div style="height: 100%">
            <div class="lcontrol" v-if="route.name == 'learningcontrol'">
                <h4 style="padding: 20px;">テーマ</h4>
                <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap: 20px;padding: 0 20px;">
                    <div class="theme-item" v-for="theme in themeRecords">
                        <div @click="router.push({name: 'content', params: {themeId: theme.id}})" style="max-width: 90%;overflow: hidden;text-overflow: ellipsis;">
                            <div style="font-size: 20px;">{{ theme.title }}</div>
                            <div style="font-size: 12px;margin-top: 15px;color: gray;">
                                <span>グループディスカッション日付：{{ theme.discussion_date ? theme.discussion_date : '未設定' }}</span>
                            </div>
                            <div style="font-size: 12px;margin-top: 15px;color: gray;">
                                <span>アクティブ：{{ theme.active ? 'ON' : 'OFF' }}</span>
                            </div>
                            <div style="font-size: 12px;margin-top: 15px;color: gray;">
                                <span>アシスタントID：{{ theme.assistant_id }}</span>
                            </div>
                        </div>
                        <div style="position: absolute;right: 10px;top: 10px;">      
                            <ItemMenu :items="[
                                {title: '編集する', action: () => editTheme(theme)},
                                {title: '削除する', action: () => deleteThemeConfirm(theme.id)}
                            ]"/>            
                        </div>
                    </div>                    
                </div>
                <div @click="createThemeWindow = true" class="createBoardButton fileNewButton" title="新規作成" id="boardCreate">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;"><path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path></svg>
                </div>                
            </div>
            <router-view :themes="themeRecords"></router-view>
        </div>
        
    </div>
</template>
<script setup>
import { computed, onMounted, ref, inject, provide } from 'vue';
import ThemeCreate from './ThemeCreate.vue';
import { useMenuStore } from "@/store/menu";
import { useRoute, useRouter } from 'vue-router';
import ItemMenu from '@/components/Global/ItemMenu.vue'
    const router = useRouter()
    const route = useRoute()
    const menu = useMenuStore()
    const activeLesson = ref(null)
    const createThemeWindow = ref(false)
    const editThemeTarget = ref(null)
    const themeRecords = ref([])
    const { confirm } = inject('dialog')
    onMounted(() => {
        getThemes()
    })

    const editTheme = (theme) => {
        editThemeTarget.value = theme
        createThemeWindow.value = true
    }
    const deleteThemeConfirm = async(id) => {
        const answer = await confirm('削除しますか。')
        if(!answer) return
        deleteTheme(id)
    }

    const deleteTheme = (id) => {
        axios.delete(`/delete_learning_theme?id=${id}`).then(response => {
            getThemes(activeLesson.value)
        })
    }
    const closeThemeCreate = (flag) => {
        createThemeWindow.value = false
        if(flag){
            getThemes()
        }
    }
    const getThemes = async() => {
        themeRecords.value = await axios.get('/get_learning_themes').then(res => res.data)
        console.log(themeRecords.value)
    }
    provide('getThemes', getThemes)
</script>