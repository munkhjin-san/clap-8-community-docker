<template>
    <div class="file-area-content">
        <div class="file-wrap" draggable="true" @dragover.prevent @dragstart.prevent="fileExportStart(file, message.record_id)" v-for="(file, index) in filteredFiles" :class="{ hasMessage: (message.message && message.message.length)}">   
            <div class="file-area-container" @click="previewFile(file, index)">
                <div class="flex-centered">             
                    <div style="max-width:65px;height:40px;display: flex;">                   
                        <img
                            style="max-width:100%;margin:auto;max-height:100%;" 
                            v-if="file.mime_type == 'image'"
                            class="list-image-mobile" 
                            :src="`/shared_thumbnail/${message.record_id}/${file.id}_${file.user_id}_${file.message_id}.${file.extension}`" 
                            loading="lazy"
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image'" style="position:relative;">
                        <FileIcon :ext="file.extension"/>
                    </div>
                    <div style="line-height: 1.5;overflow: hidden;;margin-left:5px;">
                        <p :title="file.name ? file.name : ''" class="shared-file-name">{{fileNameFilter(file)}}</p>                                               
                        <p style="font-size: 10px !important;" class="shared-file-name">{{fileSizeView(file.size )}}</p>                     
                        <p style="font-size: 12px !important;font-weight: 600;" v-if="file.sign_flag == 1 && canSign(file)" class="shared-file-name">サイン依頼</p>
                        <p style="font-size: 12px !important;font-weight: 600;" v-if="file.multiple_flag == 1 && (canSign(file) || file.user_id == auth.id)" class="shared-file-name">原本(確認用)</p>                   
                    </div>
                    <!-- <ItemMenu style="align-self: flex-start;" :items="shareMenuItems(file)" type="share" fit="boardListInner"/> -->
                    <ItemMenu style="align-self: flex-start;" :items="fileMenuItems(file)" fit="boardListInner"/>
                </div>   
                <div v-if="file.sign_flag == 1" class="flex mt-[10px] items-center justify-between gap-[5px] flex-wrap">
                    <div class="flex-centered" @click.stop="viewUsersList(file.signed_users, 'サイン完了メンバー')" v-if="file.signed_users">
                        <p class="cursor-pointer text-[12px]">サイン完了 {{ file.signed_users.length ? `(${file.signed_users.length})` : '(0)' }}</p>                                            
                    </div>
                    <div class="flex-centered" @click.stop="viewUsersList(file.unsigned_users, 'サイン未完了メンバー')" v-if="file.unsigned_users">
                        <p class="cursor-pointer text-[12px]">サイン未完了 {{ file.unsigned_users.length ? `(${file.unsigned_users.length})` : '(0)' }}</p>                                            
                    </div>
                </div>
            </div>                                             
        </div>
        <Teleport to="#override">
            <ConfirmWindow v-if="confirmWindow" :requestType="requestType" :message="signRequestData" :file="currentFile" @close="confirmWindow = false"/>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import {filesize} from "filesize";
import FileIcon from "../Mixed/FileIcon.vue";
import ConfirmWindow from "./ConfirmWindow.vue";
import { ref, computed, inject } from "vue";
import { useRouter } from "vue-router";
import { useFilePreview } from "@/store/filePreview";
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useMessageUsers } from '@/store/messageUsers'
import { useSharingDataStore } from '@/store/sharingData'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import { MenuList, Message, MessageFile } from "@/interface/globalInterface";
    const sharingData = useSharingDataStore()
    const messageUsers = useMessageUsers()    
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const props = defineProps<{
        list: MessageFile[]
        message: Message
        mIndex?: number | string
        unchecked?: boolean | undefined
    }>()
    const fileMenuLayer = ref(0)
    const confirmWindow = ref(false)
    const currentFile = ref(null)
    const signRequestData = ref<Message | null>(null)
    const requestType = ref('')
    const router = useRouter()
    const filePreview = useFilePreview()
    const file_index = ref(0)
    const filteredFiles = computed(() => {
        const filteredFiles:MessageFile[] = []
        for(let item of props.list){
            const unsignedUsers = item.unsigned_users || [];
            const signedUsers = item.signed_users || [];
            const includesUser = unsignedUsers.some(user => user.id === auth.activeUser.id) ||
            signedUsers.some(user => user.id === auth.activeUser.id) || item.user_id === auth.activeUser.id;

            if (item.sign_flag === 0 || item.sign_flag === null) {
                filteredFiles.push(item)
            }else if(item.multiple_flag === 1 && (!includesUser || item.user_id === auth.activeUser.id)){
                filteredFiles.push(item)
            }else if(item.sign_flag === 1 && item.multiple_flag === 0 && includesUser){
                filteredFiles.push(item)
            }else if(item.multiple_flag === 2 && item.user_id === auth.activeUser.id){
                filteredFiles.push(item)
            }
        }
        return filteredFiles
    })
    const fileMenuItems = (file) => {
        const list:MenuList[] = []; 
        function addItem(title, action) {
            list.push({ title, action });
        }
        addItem('ダウンロード', () => downloadFile(file))
        if(!file.sign_flag && file.user_id == auth.activeUser.id && file.extension == 'pdf'){
            addItem('サイン依頼', () => signRequest(file))
        }   
        
        const builtInApps = [
            {name: 'board', name_jp: 'チャット'}, 
            {name: 'knowledge', name_jp: 'ナレッジ'},
            {name: 'nice', name_jp: 'ナイス'},
            {name: 'challenge', name_jp: 'チャレンジ'},
            {name: 'schedule', name_jp: 'スケジュール'}
        ]
        
        const shareChildren: { title: string; action: () => void; }[] = [];
        const share = { title: 'シェア', action: () => false, children: shareChildren}
        builtInApps.forEach(app => {
            share.children.push({ title: app.name_jp, action: () => shareTo(app.name, file)})
        });
        if (!props.unchecked) {
            list.push(share)
        }
        
        return list
    }
    const downloadFile = (file) => {
        closeMenu()
        let src, name;               
        
        const path = file.board_id + '/' + file.id + '_' + file.user_id + '_' + file.message_id + '.' + file.extension        
        name = file.name
        src = '/cdn/shared_files/'+ path;               
        const link = document.createElement('a');
        link.href = src;
        link.download = '';
        link.setAttribute('download', name);
        document.body.appendChild(link);            
        link.click();  
        document.body.removeChild(link); 
    }
    const closeMenu = () => {
        menu.setMenu( {id: null, name: ''})
    }
    const signRequest = (file) => {
        signRequestData.value = props.message
        confirmWindow.value = true
        requestType.value = 'sign'
        currentFile.value = file
    }
    const shareTo = (to, file) => {
        const shareData = {
            active: true,
            title: '',
            text: '',
            files: [{path :`/cdn/shared_files/${props.message.record_id}/${file.id}_${file.user_id}_${file.message_id}.${file.extension}`, record: file}],
            from: 'message',
            to: to,
            drag: false,
            instruction: to == 'board' ? '送る先のチャットを選択してください' : null
        }
        sharingData.setSharingData(shareData)
        menu.setMenu( {id : null, name: ''})
        if(to !== 'board'){
            router.push({name: to})
        }
    }
    const canSign = (file:MessageFile) => {     
        const unsignedUsers = file.unsigned_users;
        if(unsignedUsers && (file.multiple_flag == 2 || file.multiple_flag == 0)){
            const includesUser = Object.values(unsignedUsers).some(user => user.id === auth.activeUser.id);
            return includesUser
        }
        return false
        
    }
    const viewUsersList = (users, title) => {
        const data = {
            active: true,
            userList: users,
            title: title
        }
        messageUsers.setMessageUsers(data)
        
    }
    const fileExportStart = (file, record_id) => {
        const shareData = {
            active: true,
            title: '',
            text: '',
            files: [{path :`/cdn/shared_files/${props.message.record_id}/${file.id}_${file.user_id}_${file.message_id}.${file.extension}`, record: file}],
            from: 'message',
            to: '',
            drag: true 
        }
        sharingData.setSharingData(shareData)
    }
    const multipleFile = (file, index) => {
        let select = <MessageFile[]>[]
        const unsignedUsers = file.unsigned_users || [];
        const signedUsers = file.signed_users || [];

        const includesUser = unsignedUsers.some(user => user.id === auth.activeUser.id) ||
        signedUsers.some(user => user.id === auth.activeUser.id);
        if (file.sign_flag === 0) {
            for(let item of filteredFiles.value){
                if(!item.original_file_id){
                    select.push(item)
                }
            }
            file_index.value = index
        }else if(file.multiple_flag === 1 && file.user_id === auth.activeUser.id){
            select.push(file)
            for (let item of props.list){
                if(item.multiple_flag === 2 && item.original_file_id === file.id){
                    select.push(item)
                }
            }
            file_index.value = 0
        }else if(file.sign_flag === 1 && file.multiple_flag === 0 && includesUser){
            select.push(file)
            file_index.value = 0
        }else if(file.multiple_flag === 2 && file.user_id === auth.activeUser.id){
            select.push(file)
            file_index.value = 0
        }else if(!includesUser || file.multiple_flag === 1){
            for(let item of filteredFiles.value){
                if(item.multiple_flag === 0 || item.multiple_flag === 1){
                    select.push(item)
                }
            }
            file_index.value = index
        }
        return select
    }
    const previewFile = (file, index) => {
        if(sharingData.active) return
        let selectedItem = multipleFile(file, index)
        let file_list = selectedItem
        const files = file_list.map(fileData => ({
            ...fileData,
            source_board_id: props.message.record_id,
            file_path: `/cdn/shared_files/${props.message.record_id}/${fileData.id}_${fileData.user_id}_${fileData.message_id}.${fileData.extension}`,
            doc_path: `/shared_files/${props.message.record_id}/${fileData.id}_${fileData.user_id}_${fileData.message_id}.${fileData.extension}`
        }));
        
        let target_data = selectedItem
        target_data['source_board_id'] = props.message.record_id
        const data = {
            active: true,
            files,
            target: target_data,
            source: 'message',
            index: file_index.value,
            message: props.message,
        }
        filePreview.setFilePreview(data)
    }
    const fileNameFilter = (file) => {
        return file.name;
    }
    const fileSizeView = (bytes) => {
        if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
        else return filesize(bytes, {standard: "jedec", round: 0});
    }
            
    
</script>
