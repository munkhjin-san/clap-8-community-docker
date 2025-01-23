<script setup lang="ts">
import UsersModal from '../Global/UsersModal.vue';
import UserPanel from '../Global/UserPanel.vue';
import { useSurveyUsers } from '@/store/surveyUsers';
const surveyUsers = useSurveyUsers()

</script>

<template>
    <UsersModal @close="surveyUsers.close()">
        <template #title>
            <p class="font-semibold">{{ surveyUsers.title }}</p>
        </template>
        <template #content>
            <div class="flex flex-col">
                <div v-for="user in surveyUsers.users" class="flex items-center p-[10px] hover:bg-[var(--bg3)]">
                    <UserPanel :user="user" size="30" with-name disable-instant/>
                    <div v-if="user.is_answered !== undefined" class="c-button ml-auto px-[7px]" :style="{background: user.is_answered ? 'green' : 'black', cursor: 'not-allowed'}">{{ user.is_answered ? '回答済み' : '未回答' }}</div>
                </div>                    
            </div>
        </template>
    </UsersModal>
</template>
<style scoped>
    .c-button{
        color: #fff;
        background-color: #000;
        font-size: 12px;
        line-height: 1.5;
        white-space: nowrap;
        height: 25px;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: center;
        width: fit-content;
        position: relative;
        user-select: none;
    }
    @media (max-width: 959px) {
        .c-button{
            height: 30px;
        }
        
    } 
</style>