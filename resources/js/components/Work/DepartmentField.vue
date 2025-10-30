<template>
    <div class="overlay" @mousedown="emit('close')">
        <div class="chatCreate scrollable" @mousedown.stop style="min-width: 20%; width: fit-content; height: fit-content; min-height: 30%;">
            <div class="recordFormTitle">
                <p style="font-size: 18px;">{{DateTime.fromSQL(shiftForDepartment.shift_day).toFormat('M月d日')}}の部門作成</p>
                
                <div @click="emit('close')" class="cursor-pointer" style="display:flex;align-items: center;margin: auto 0 auto auto;">
                    <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
            <div class="si-box">
                <ItemSelector 
                    placeHolder="部門を選択"
                    :multiple="false"
                    :clearable="false"
                    :close-on-select="true"
                    :options="workGroupAsOptions.filteredgroups"
                    v-model="todayWorkGroup"
                />
                <!-- <p class="report-header">部門を選択</p>
                <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;width:100%;" v-model="todayWorkGroup">
                    <option v-for="group in workGroupAsOptions.filteredgroups" :value="group.id">{{ group.name }}</option>
                </select> -->
            </div>
            <div class="si-box" style="margin-top: auto;">
                <LoaderButton @triggered="departmentAdd" content="保存" :loading="loading"/>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, inject, onMounted, ref } from 'vue'
import { useAuthUserStore } from '@/store/auth'
import LoaderButton from '../Global/LoaderButton.vue'
import ItemSelector from '../Form/ItemSelector.vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { getWorkGroup } from '@/utils/workApi';
const workGroups = ref([])
const auth = useAuthUserStore()
const props = defineProps(['shiftForDepartment'])
const emit = defineEmits(['close'])
const loading = ref(false)
const api = useApi()

const workGroupAsOptions = computed(() => {
    let filteredgroups
    let mappedgroups
    if(auth.activeUser.id == 608 || auth.activeUser.id == 610){
        filteredgroups = workGroups.value
        .filter(group => group.members.some(member => member.id === props.shiftForDepartment.user_id))
        
        mappedgroups = filteredgroups.map(group => group.name);

    } else {
        filteredgroups = workGroups.value
        mappedgroups = workGroups.value.map(group => group.name)
    }
    return {filteredgroups, mappedgroups}
})
const todayWorkGroup = ref(workGroupAsOptions.value.filteredgroups[0]?.id ?? '')
const departmentAdd = async() => {
    const params = {
        id: props.shiftForDepartment.id,
        department_id: todayWorkGroup.value
    }
    await api.put('/shift_add_department', params, {
        loadingRef: loading
    })
    emit('close')

}
onMounted(async() => {
    workGroups.value = await getWorkGroup()
})
</script>