<template>
    <main style="height: 100%;">      
        <div :style="{height: signActive ? 'calc(100% - 45px)' : '100%', position: 'relative', overflow: 'hidden'}" id="mainPdfParent">            
            <pdfjs-viewer-element
                class="viewer"
                viewer-path="/pdf-reader"
                locale="ja"
                :page="file.initialPage ? file.initialPage : 1"
                :phrase="true"
                zoom="page-width"
                pagemode="none"
                text-layer="off"
                :viewer-css-theme="viewerCssTheme">
            </pdfjs-viewer-element>
        </div>
        <SignAction
            v-if="signActive"
            :file="file"
            :viewer="viewer"
            :source="source"
            :mode="signMode ?? 'chat'"
            :can-sign="forceSign ? true : null"
            :save-handler="saveHandler ?? null"
        />
    </main>
</template>

<script setup lang="ts">
import { onMounted, ref, defineAsyncComponent, onUnmounted } from 'vue'
import 'pdfjs-viewer-element'
import { useAuthUserStore } from '@/store/auth'
import { FileRecord } from '@/interface/trayInterface'
import Error from '@/components/Global/Error.vue'
    const auth = useAuthUserStore()
    
    const SignAction = defineAsyncComponent({ loader: () => import('./SignAction.vue'), errorComponent: Error })
    // `forceSign` / `signMode` / `saveHandler` let another feature (learning
    // 誓約書) reuse this exact screen. Unset, everything behaves as chat did.
    const props = defineProps<{
        source: string,
        file: FileRecord,
        forceSign?: boolean,
        signMode?: string,
        saveHandler?: ((bytes: Uint8Array) => Promise<void>) | null,
    }>()
    const emit = defineEmits(['refresh'])
    const viewerCssTheme = ref('DARK')
    const signActive = ref(false)    

    onMounted(() => {
        init()
        // NB: Vue casts an absent Boolean prop to false, so `forceSign` can't be
        // used to detect "not provided". Branch on the (String) signMode instead,
        // which stays undefined for chat.
        if(props.signMode && props.signMode !== 'chat'){
            signActive.value = !!props.forceSign
            return
        }
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