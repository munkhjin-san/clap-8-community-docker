<template>
    <div class="workButtons-wrapper">
        <HamBurger v-if="responsive.mobile"/>

        <button class="work-button pc" @click="emit('selectShift')">
            勤怠予定
        </button>
        <button class="work-button pc" @click="emit('approveShift')" v-if="auth.activeUser.position_id == 6 || auth.activeUser.id == 610 || auth.activeUser.id == 608">
            勤怠予定承認
        </button>
        <button class="work-button" :class="{'pc' : !auth.isRegistered}" @click="emit('confirmAttendance')">
            勤怠確定
        </button>
        <button class="work-button mobile" v-if="!auth.isRegistered" @click="modal = true">
            勤怠報告
        </button>
        <div class="work-modal" v-if="modal" @mousedown="modal = false">
            <div class="work-modal-inner" @mousedown.stop style="height: fit-content; width: calc(100% - 80px);">
                <div class="recordFormTitle">
                    <p style="font-size: 18px;">{{ `${selectedMonth + 1}月の勤怠報告` }}</p>
                    <div @click="modal = false" class="cursor-pointer" style="margin: auto 0 auto auto;">
                        <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <CommandButton 
                        :buttons="buttonCollection"
                    />
                </div>
            </div>
        </div>
        <div class="work-button" v-if="!auth.isRegistered" @click.stop="menu.setMenu( { id: 98, name: 'workMemberSelector'})">
            メンバー
        </div>
        <Transition name="modalFade">
            <WorkMembers 
                v-if="menu.id == 98 && menu.name == 'workMemberSelector'"
                :workUsers="flatworkGroups"
                :workGroups="workGroups"
                v-model:users="selectedUsersList"
                v-model:vehicles="selectedVehicles"
                customStyle="color: var(--primary-color);"
            />
        </Transition>
        
        <button class="work-button" @click="emit('todayScroll')">
            今日
        </button>
        <button class="work-button" @click="emit('toBottomScroll')">
            集計
        </button>
        
       
    </div>
</template>
<script setup lang="ts">
    import { ref, computed } from 'vue';
    import HamBurger from '../Global/HamBurger.vue'
    import CommandButton from '../Global/CommandButton.vue';
    import { useMenuStore } from "../../store/menu";
    import { useResponsive } from '../../store/responsive';
    import { useAuthUserStore } from '../../store/auth';
    import { User } from '@/interface/globalInterface';
    import WorkMembers from './WorkMembers.vue';
    const menu = useMenuStore()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    interface Props {
        workGroups: any;
        selectedMonth: number
    }
    const props = defineProps<Props>()
    const emit = defineEmits([
        'selectShift', 
        'confirmAttendance', 
        'todayScroll', 
        'toBottomScroll', 
        'approveShift',
    ])
    const modal = ref(false)
    const selectedUsersList = defineModel<any>('users')
    const selectedVehicles = defineModel('vehicles')
    const flatworkGroups = computed(() => {
        let groups : any
       
        groups = props.workGroups
        .flatMap(workGroup => [
            ...workGroup.members, // Add members
            ...workGroup.manager || [], // Add manager if it exists
        ])
        .filter(Boolean).reduce((acc: User[], member: User) => {
            if (!acc.some(m => m.id === member.id)) {
                acc.push(member);
            }
            return acc;
        }, [])

        
        const uniqueMemberObjects: User[] = groups.sort((a: User, b: User) => {
            if (a.id === auth.id) return -1;
            if (b.id === auth.id) return 1;
            return a.id - b.id;
        });
        return uniqueMemberObjects
    })
    const buttonCollection = computed(() => {
        const buttons: { action: () => void, order: number, title: string }[] = []
        buttons.push({
            title: '勤怠予定', 
            action: () => emit('selectShift'), 
            order: 1
        })

        buttons.push({
            title: '勤怠確定', 
            action: () => emit('confirmAttendance'),
            order: 3
        })
        if(auth.activeUser.position_id == 6 || auth.activeUser.id == 610 || auth.activeUser.id == 608){
            buttons.push({
                title: '勤怠予定承認', 
                action: () => emit('approveShift'),
                order: 2
            })
        }
        buttons.sort((a, b) => a.order - b.order);
        return buttons
    })
</script>
