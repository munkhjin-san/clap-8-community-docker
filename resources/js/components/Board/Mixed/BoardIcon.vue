<template>
    <div>
        <template v-if="item.private_flag == 0">
            <v-img
                v-if="uploadedIcon"
                :draggable="false"
                loading="lazy"
                class="rounded-full"
                :src="uploadedIcon"
                :style="{
                    width: computedSize + 'px',
                    height: computedSize + 'px',
                    minWidth: computedSize + 'px',
                    minHeight: computedSize + 'px',
                }"
                rounded="circle"
            >
            <template v-slot:error>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" :width="`${computedSize}px`" :height="`${computedSize}px`">
                    <circle cx="15" cy="15" r="15" fill="var(--secondary-background)"/>
                </svg>
            </template>
            </v-img>
            <BoardDefaultIcon v-else :text="defaultText" :bg="item.icon_bg ?? '#000'" :size="computedSize" />
        </template>
        <UserPanel v-if="item.private_flag > 0 && correspondUser" :disableInstant="true" :user="correspondUser" :size="computedSize"/>
    </div>
</template>
<script setup lang="ts">
import { computed } from 'vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import BoardDefaultIcon from '@/components/Board/Mixed/BoardDefaultIcon.vue'
import { useAuthUserStore } from '@/store/auth'
import { Board } from '@/interface/globalInterface';
    const auth = useAuthUserStore()
    const props = defineProps<{
        item: Board
        size?: string
    }>()
    const correspondUser = computed(() => {
        if (props.item.private_flag === 1) {
            const user = props.item.board_to_users.find(obj => obj.user_id !== auth.activeUser.id);
            return user?.user || null;
        } else if (props.item.private_flag === 3) {
            const me = props.item.board_to_users.find(obj => obj.user_id === auth.activeUser.id);
            return me?.user || null;
        }
        return null;
    });
    const computedSize = computed(() => {
        return props.size ? Number(props.size) : 45 
    })
    // Uploaded icons still go through the server; default (letter) icons are now
    // rendered client-side as crisp, zero-request SVG by <BoardDefaultIcon/>.
    const uploadedIcon = computed(() => {
        return props.item.icon_path ? `/board_icon_thumbnail/${props.item.icon_path}` : ''
    })
    const defaultText = computed(() => props.item.icon_text || props.item.title || '')
</script>