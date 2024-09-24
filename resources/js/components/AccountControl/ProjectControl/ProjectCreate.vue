<template>
    <div class="overlay">
        <div class="chatCreate scrollable">
            <div class="recordFormTitle" style="display:flex;">
                <p>プロジェクト作成</p>
                <div class="cursor-pointer" @click="emit('close')" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div>
                <div>
                    <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">期間</p>
                    <div style="display:flex;position: relative;width:100%">
                        <ShortInput 
                            name="startDate" 
                            :rules="'required'"
                            :initialValue="dateStart"
                            customClass="date"
                            ref="startDateRef"
                            type="date"
                            v-model="dateStart"
                        />
                        <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div>
                        <ShortInput 
                            name="endDate" 
                            :rules="'required'"
                            :initialValue="dateEnd"
                            customClass="date"
                            ref="endDateRef"
                            type="date"
                            v-model="dateEnd"
                        />
                    </div>
                </div>
                <div class="si-box">
                    <ShortInput 
                        name="name"
                        v-model="name"
                        placeHolder="名前"
                    />
                </div>
                <div class="si-box">
                    <LongInput 
                        name="overview"
                        v-model="overview"
                        placeHolder="概要"
                    />
                </div>
                <div class="si-box">
                    <LongInput 
                        name="strategy"
                        v-model="strategy"
                        placeHolder="戦略"
                    />
                </div>
                <div class="si-box">
                    <LongInput 
                        name="kpi"
                        v-model="kpi"
                        placeHolder="KPI"
                    />
                </div>
                <div class="si-box">
                    <LongInput 
                        name="kgi"
                        v-model="kgi"
                        placeHolder="KGI"
                    />
                </div>
                <div class="si-box">
                    <MemberSelector 
                        name="director"
                        v-model="director"
                        :options="directorOptions"
                        :multiple="false"
                        placeHolder="取締役"
                    />
                </div>
                <div class="si-box">
                    <MemberSelector 
                        name="manager"
                        v-model="manager"
                        :options="managerOptions"
                        :multiple="false"
                        placeHolder="管理者"
                    />
                </div>
                <div class="si-box">
                    <MemberSelector 
                        name="member"
                        v-model="member"
                        placeHolder="メンバー"
                        :options="userList"
                        :closeOnSelect="false"
                        :multiple="true"
                    />
                </div>
                
                <div class="si-box">
                    <LoaderButton @triggered="createProject" :loading="loading" content="作成する"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { computed, ref } from 'vue';
import axios from 'axios';
import { User } from '@/interface/globalInterface';
const emit = defineEmits(['close', 'getProjects'])
const props = defineProps(['userList', 'editData'])
const name = ref(props.editData?.name ?? '')
const overview = ref(props.editData?.overview ?? '')
const strategy = ref(props.editData?.strategy ?? '')
const kpi = ref(props.editData?.kpi ?? '')
const kgi = ref(props.editData?.kgi ?? '')
const director = ref<User>(props.editData?.director ?? null)
const manager = ref<User>(props.editData?.manager?.[0] ?? null)
const member = ref<User[]>(props.editData?.members ?? [])
const loading = ref(false)
const dateStart = ref(props.editData?.date_start ?? '')
const dateEnd = ref(props.editData?.date_end ?? '')
const directorOptions = computed(() => {
    return props.userList.filter((user: { position_id: number; }) => user.position_id < 6 && user.position_id !== null)
})
const managerOptions = computed(() => {
    return props.userList.filter((user: { position_id: number; }) => user.position_id <= 6)
})

const createProject = async() => {
    console.log(manager.value)
    const params = {
        id: props.editData?.id,
        manager_ids: manager.value?.id,
        member_ids: member.value.map(ob => ob.id),
        params: {
           name: name.value,
           director_id: director.value?.id,
           date_start: dateStart.value,
           date_end: dateEnd.value,
           overview: overview.value,
           strategy: strategy.value,
           kgi: kgi.value,
           kpi: kpi.value,  
        }
    }
    loading.value = true
    try {
        await axios.post('/create_project', params)
        loading.value = false
        emit('close')
        emit('getProjects')
    } catch (e) {
        
    }
}

</script>