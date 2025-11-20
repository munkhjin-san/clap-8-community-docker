<template>
    <div class="w-full h-full relative" v-if="openedBoard">
        <Transition name="modalFade">
            <div class="cal-month-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="h-full w-full overflow-y-auto" ref="container">
            <div v-if="!loading && !formList.length" class="no-comment-text text-[12px]">
                現在はフォームはありません。
            </div>
             <div v-if="formList.length" class="flex flex-col gap-[15px] p-[15px]">
                <BoardFormItem
                    v-for="form in formList"
                    :key="form.id"
                    :form="form"
                    @edit="(data: CustomForm) => { editData = data; modalView = true }"
                    @set-view-users="(form: CustomForm) => { viewUserOf = form }"
                    @set-view-answers="(form: CustomForm) => { viewAnswerOf = form }"
                    @delete="deleteForm"
                    @duplicate="duplicateForm"
                    @fill="fillingForm = form"
                />
            </div>
        </div>
       
        <FloatButton @action="createForm" :hide-on="container">
            <template #icon>
                <AddIcon/>
            </template>
        </FloatButton>
        <Teleport to="body">
            <CustomFormCreate
                :edit-data="editData" 
                v-if="modalView" 
                range="board" 
                :board="openedBoard"
                @close="(flag) => {modalView = false, flag && getBoardForms()}"
            />
        </Teleport>
        <Teleport to="body">
            <FormUsers
                v-if="viewUserOf"
                :form="viewUserOf"
                @close="viewUserOf = null"
                @edit-answer="(form, answerId) => { 
                    editAnswerId = answerId;
                    fillingForm = form;
                    viewUserOf = null;
                }"
            />
        </Teleport>
        <Teleport to="body">
            <BoardFormFill
                v-if="fillingForm"
                :form="fillingForm"
                :edit-id="editAnswerId"
                @close="closeFill"
            />
        </Teleport>
        <Teleport to=body>
            <BoardFormAnswersSummary 
                :form="viewAnswerOf" 
                v-if="viewAnswerOf"
                @close="viewAnswerOf = null"
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
import { onMounted, ref, useTemplateRef } from 'vue';
import BoardFormItem from './BoardFormItem.vue';
import FormUsers from './FormUsers.vue';
import BoardFormFill from './BoardFormFill.vue';
import BoardFormAnswersSummary from './BoardFormAnswersSummary.vue';
const api = useApi()
const { openedBoard } = useBoardList()
const formList = ref<CustomForm[]>([])
const loading = ref(true)
const editData = ref<CustomForm | null>(null)
const modalView = ref(false)
const viewUserOf = ref<CustomForm | null>(null)
const fillingForm = ref<CustomForm | null>(null)
const viewAnswerOf = ref<CustomForm | null>(null)
const container = useTemplateRef('container')
const initialLoader = ref(true)
const editAnswerId = ref<number | null>(null)
onMounted(() => {
    getBoardForms()
})
const getBoardForms = async() => {
    if (!openedBoard.value) {
        return
    }
    const data = await api.get('/get_board_forms', {
        board_id: openedBoard.value.id
    }, { loadingRef: loading, cancel: true })
    formList.value = data
    initialLoader.value = false

}
const createForm = () => {
    editData.value = null
    modalView.value = true
}
const closeFill = (flag: boolean) => {
    fillingForm.value = null
    editAnswerId.value = null
    if (flag) {
        getBoardForms()
    }
}
const deleteForm = async(form: CustomForm) => {

    const data = await api.del('/delete_custom_form', {id: form.id}, {
        ask: 'フォームを削除しますか？',
        toast: '削除しました。',
    })
    data && getBoardForms()
}
const duplicateForm = async(form: CustomForm) => {
    const data = await api.post('/duplicate_custom_form', {id: form.id}, {
        ask: '再利用しますか？',
    })
    data && getBoardForms()
    
}
</script>