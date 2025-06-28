<template>
    <div class="w-full h-full relative" v-if="openedBoard">
        <div v-if="!loading && !formList.length" class="no-comment-text text-[12px]">
            現在はフォームはありません。
        </div>
        <div v-if="formList.length" class="flex flex-col gap-[10px] p-[10px]">
            <BoardFormItem
                v-for="form in formList"
                :key="form.id"
                :form="form"
                @edit-form="(data: CustomForm) => { editData = data; modalView = true }"
                @set-view-users="(form: CustomForm) => { viewUserOf = form }"
                @fill="fillingForm = form"
            />
        </div>
        <FloatButton @action="createForm">
            <template #icon>
                <AddIcon/>
            </template>
        </FloatButton>
        <Teleport to="body">
            <CustomFormCreate :edit-data="editData" v-if="modalView" range="board" :board="openedBoard"/>
        </Teleport>
        <Teleport to="body">
            <FormUsers
                v-if="viewUserOf"
                :form="viewUserOf"
                @close="viewUserOf = null"
            />
        </Teleport>
        <Teleport to="body">
            <BoardFormFill
                v-if="fillingForm"
                :form="fillingForm"
                @close="closeFill"
            />
        </Teleport>
    </div>

</template>
<script setup lang="ts">
import CustomFormCreate from '@/components/AccountControl/CustomForm/CustomFormCreate.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import { useApi } from '@/composables/api';
import { useBoardList } from '@/composables/board';
import { CustomForm } from '@/interface/customFormInterface';
import { onMounted, ref } from 'vue';
import BoardFormItem from './BoardFormItem.vue';
import FormUsers from './FormUsers.vue';
import BoardFormFill from './BoardFormFill.vue';
const api = useApi()
const { openedBoard } = useBoardList()
const formList = ref<CustomForm[]>([])
const loading = ref(true)
const editData = ref<CustomForm | null>(null)
const modalView = ref(false)
const viewUserOf = ref<CustomForm | null>(null)
const fillingForm = ref<CustomForm | null>(null)
onMounted(() => {
    getBoardForms()
})
const getBoardForms = async() => {
    if (!openedBoard.value) {
        return
    }
    const data = await api.get('/get_board_forms', {
        board_id: openedBoard.value.id
    }, { loadingRef: loading })
    formList.value = data

}
const createForm = () => {
    editData.value = null
    modalView.value = true
}
const closeFill = (flag: boolean) => {
    fillingForm.value = null
    if (flag) {
        getBoardForms()
    }
}
</script>