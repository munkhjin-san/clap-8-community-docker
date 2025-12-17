<template>
    <main style="height: 100%;">      
        <div :style="{height: signActive ? 'calc(100% - 45px)' : '100%', position: 'relative', overflow: 'hidden'}" id="mainPdfParent">            
            <pdfjs-viewer-element
                class="viewer"
                viewer-path="/pdf-reader"
                locale="ja"
                page="1"
                :phrase="true"
                zoom="page-width"
                pagemode="none"
                text-layer="off"
                :viewer-css-theme="viewerCssTheme">
            </pdfjs-viewer-element>
        </div>
        <SignAction v-if="signActive" :file="file" :viewer="viewer" :source="source"/>
    </main>
</template>

<script setup lang="ts">
import { onMounted, ref, defineAsyncComponent, onUnmounted } from 'vue'
import 'pdfjs-viewer-element'
import { useAuthUserStore } from '../../../../store/auth'
import { File } from '../../../../interface/trayInterface';
    const auth = useAuthUserStore()
    
    const SignAction = defineAsyncComponent(() => import('./SignAction.vue'))
    const props = defineProps<{
        source: string, 
        file: File, 
    }>()
    const emit = defineEmits(['refresh'])
    const viewerCssTheme = ref('DARK')
    const signActive = ref(false)    

    onMounted(() => {
        init()
        const unsignedUsers = props.file.unsigned_users;
        if(unsignedUsers && (props.file.multiple_flag == 2 || props.file.multiple_flag == 0)){
            const includesUser = Object.values(unsignedUsers).some(user => user.id === auth.activeUser.id && user.pivot.cancel_flag === 0);
            signActive.value = includesUser
        } else {
            signActive.value = props.file.multiple_flag == 1 && props.file.user_id == auth.activeUser.id
        }
    })
    onUnmounted(() => {
        if(instance){
            instance.pdfDocument.destroy()
            instance = null
        }
    })

    let instance: any = null; 
    let viewerElement: any = null; 
    const init = async () => {
        viewerElement = document.querySelector('pdfjs-viewer-element')
        instance = await viewerElement.initialize()
        if(instance){
            instance.open({ url: props.source })
        }else{
            emit('refresh')
        }
                
    }     
    const viewer = () => {
        return instance.pdfViewer
    }   

</script>
<style>
    html, body {
        margin: 0;
        padding: 0;
    }
    .viewer {
        height: 100%
    }
    .cbar-command{
        padding: 5px;
        background: var(--bg3);
    }
</style>