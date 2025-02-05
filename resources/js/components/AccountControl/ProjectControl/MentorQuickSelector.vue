<template>
<Modal @close="emit('close', false)">
    <template #title>
        <div>
            <p>{{ `${data.user?.name}メンター選択` }}</p>
            <p class="text-[gray] text-[13px] mt-[15px]">{{ data.date.name }}</p>
        </div>

    </template>
    <template #content>
        <div>
            <MemberSelector 
                placeHolder="メンター選択"
                v-model="mentor"
                :options="possibleMentors"
                :multiple="false"
                name="mentor"
            />
        </div>
        <div class="si-box">
            <LoaderButton content="保存する" @triggered="save" :loading="loading"/>
        </div>
    </template>
</Modal>
</template>
<script setup lang="ts">
import MemberSelector from '@/components/Form/MemberSelector.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { DialogMethods, User } from '@/interface/globalInterface';
import axios from 'axios';
import { computed, inject, ref } from 'vue';
const props = defineProps<{
    data: {
        view: boolean,
        user: User | null,
        mentorList: User[],
        selectedMentor: User | null
        date: any
        editId: number | null
    }
}>()
const emit = defineEmits<{
    close: [boolean]
}>()
const refresh = inject('refresh') as Function
const mentor = ref<User | null>(props.data.selectedMentor ?? null)
const loading = ref(false)
const { notify, info } = inject('dialog') as DialogMethods
const possibleMentors = computed(() => {
    if (props.data.user?.general_position === '一般職' || props.data.user?.general_position === null) return props.data.mentorList
    return props.data.mentorList.filter((mentor) => {
        if (props.data.user === null) return false
        return mentor.general_position > props.data.user?.general_position
    })
})
const save = async() => {
    loading.value = true
    const params = {
        attributes: {
            user_id: props.data?.user?.id,
            year: props.data.date.year,
            which_half: props.data.date.which_half,
        },
        params : {            
            mentor_id: mentor.value?.id ?? null,
        }
        
    }
    try {
        await axios.post('/save_evaluation_grade', params)
        emit('close', true)
        info('保存しました')
        refresh()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        loading.value = false
    }
}
</script>