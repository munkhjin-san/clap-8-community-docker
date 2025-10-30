<template>
    <Modal @close="closeModal(false)">
        <template #title>
            <p>
                {{ editTarget ? `営業所を編集する` : `新しい営業所を作成する` }}
            </p>
        </template>
        <template #content>
            
            <div class="si-box">
                <ShortInput name="officeTitle" placeHolder="タイトルを入力（必須）" :rules="'required'" customClass="full"
                    ref="officeTitleRef" type="text" v-model="params.name" />
            </div>
            <div class="si-box">
                <p>郵便番号</p>
                <div class="flex items-center my-[20px]">
                    <div class="mr-[10px]">〒</div>
                    <div class="w-[30%] max-w-[70px]">
                        <ShortInput name="postCode1" :rules="'required'" customClass="!px-[5px] !py-[3px]" type="text" v-model="params.post_code_1" />
                    </div>
                    <div class="mx-[10px]">ー</div>
                    <div class="w-[30%] max-w-[70px]">
                        <ShortInput name="postCode2" :rules="'required'" customClass="!px-[5px] !py-[3px]" type="text" v-model="params.post_code_2" />
                    </div>
                </div>
                
                <LongInput :initialValue="params.address" ref="addressRef" placeHolder="住所" name="address" v-model="params.address" />
            </div>
            <div class="si-box">
                <ShortInput name="officeTitle" placeHolder="電話番号" customClass="full" type="text" v-model="params.tel" />
            </div>
            <div class="si-box">
                <ShortInput name="officeTitle" placeHolder="ファックス番号" customClass="full" type="text" v-model="params.fax" />
            </div>
            <div class="si-box">
                <MemberSelector
                    name="member"
                    v-model="params.employees"
                    placeHolder="メンバー"
                    path="board_possible_users"
                    :closeOnSelect="false"
                    :multiple="true"
                    
                />
            </div>
            <div class="si-box">
                <LoaderButton @triggered="save" :loading="loading" content="保存する" />
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { reactive, ref } from "vue";
import ShortInput from "@/components/Form/ShortInput.vue";
import LongInput from "@/components/Form/LongInput.vue";
import LoaderButton from "@/components/Global/LoaderButton.vue";
import Modal from "@/components/Global/Modal.vue";
import { useApi } from "@/composables/api";
import { Office } from "@/interface/globalInterface";
import MemberSelector from "@/components/Form/MemberSelector.vue";
const props = defineProps<{
    editTarget: Office | null;  
}>();
const emit = defineEmits(["close"]);
const params = reactive<Partial<Office> >({
    id: props.editTarget ? props.editTarget.id : undefined,
    name: props.editTarget ? props.editTarget.name : "",
    address: props.editTarget ? props.editTarget.address : "",
    fax: props.editTarget ? props.editTarget.fax : "",
    tel: props.editTarget ? props.editTarget.tel : "",
    employees: props.editTarget ? props.editTarget.employees : [],
    post_code_1: props.editTarget ? props.editTarget.post_code_1 : "",
    post_code_2: props.editTarget ? props.editTarget.post_code_2: ""
})
const loading = ref(false);
const officeTitleRef = ref<InstanceType<typeof ShortInput> | null>(null);
const api = useApi()
const closeModal = (flag: boolean) => {
    emit("close", flag);
};
const save = async () => {
    const valid = (await officeTitleRef.value?.validate()) || { valid: false };
    if (!valid.valid) return;
    
  
    const data = await api.post("/office_item", params, {
        toast: '営業所を保存しました',
    });
    emit("close", true);
    
};
</script>
