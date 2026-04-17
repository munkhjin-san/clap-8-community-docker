<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>物品作成</p>
        </template>
        <template #content>
            <div class="mt-6">
                <div class="si-box">
                    <p class="mb-[20px]">GL番号</p>                
                    <div style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap;">
                        <div @click="gl_exists = 0" :class="['ch-selector', { chSelected: gl_exists == 0}]">あり</div>
                        <div @click="gl_exists = 1" :class="['ch-selector', { chSelected: gl_exists == 1}]">なし</div>
                    </div>
                </div>
                <div class="si-box" v-if="gl_exists === 0">
                    <div class="flex items-center">
                        <p>GLー</p>
                        <ShortInput 
                            name="glNumber" 
                            place-holder="番号" 
                            :rules="'number'"
                            ref="glNumberRef"
                            type="text"
                            v-model="gl_number"
                        />
                    </div>
                    
                </div>
                <div class="si-box">                
                    <AssetTypePicker 
                        :placeHolder="'種類'"
                        v-model="item_name"
                        rules="required"
                        ref="itemNameRef"
                        :options="items.map(i => i.title)"
                    />  
                </div>
                <div class="si-box" v-if="selectedFields.length">
                    <div v-for="field in selectedFields" :key="field.id" class="mb-3">
                        <ShortInput
                            v-if="field.input_type !== 'longtext'"
                            ref="dynamicFieldRefs"
                            :name="`asset-field-${field.id}`"
                            :place-holder="field.placeholder ?? ''"
                            :rules="isFieldLocked(field) ? '' : (field.rules ?? '')"
                            custom-class="full"
                            :type="'text'"
                            :disabled="isFieldLocked(field)"
                            v-model="dynamicFieldValues[field.id]"
                        />
                        <LongInput
                            v-else
                            ref="dynamicFieldRefs"
                            :name="`asset-field-${field.id}`"
                            :place-holder="field.placeholder ?? ''"
                            :rules="isFieldLocked(field) ? '' : (field.rules ?? '')"
                            :disabled="isFieldLocked(field)"
                            v-model="dynamicFieldValues[field.id]"
                        />
                    </div>
                </div>
                <div class="si-box">
                    <LongInput 
                        name="specs"
                        :place-holder="'スペックや特徴など、詳細を入力してください。'"
                        v-model="specs"
                    />
                </div>
                
                <div class="si-box">
                    <div class="flex gap-1 text-sm mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="isExternal" type="radio" name="external-member" class="custom-f-radio" :value="false"/>
                            社内メンバー
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="isExternal" type="radio" name="external-member" class="custom-f-radio" :value="true"/>
                            社外メンバー
                        </label>
                    </div>
                    <div v-if="isExternal" class="mb-3">                
                        <ShortInput                         
                            name="externalUser"
                            :place-holder="'社外使用者名'"
                            rules="required"
                            v-model="externalUser"
                            ref="externalUserNameRef"
                        />
                    </div>
                    <MemberSelector 
                        :place-holder="isExternal ? '責任者' : '使用者'"
                        v-model="selectedUser"
                        :multiple="false"
                        :options="choosAbleMembers"
                        rules="required"
                        ref="memberSelectRef"
                    />
                    
                </div> <div class="si-box">
                    <ItemSelector
                        place-holder="使用場所"
                        v-model="officeId"
                        :clearable="true"
                        label="name"
                        :closeOnSelect="true"
                        :reduce="option => option.id"
                        :options="offices"
                        :multiple="false"
                        rules="required"
                        ref="officeSelectRef"
                    />
                </div>
                <div class="si-box">
                    <p class="mb-[10px]">ステータス</p>
                    <select class="optionPicker" style="max-width: 100%;" v-model="status">
                        <option v-for="(status, index) in AssetStatus" :value="status.value">{{ status.label }}</option>
                    </select>
                </div>
                <div class="si-box" v-if="auth.isAdmin">
                    <LoaderButton content="作成する" :loading="loading" @triggered="createAsset"/>
                </div>
                <div class="si-box" v-else>
                    <LoaderButton content="申請する" :loading="loading" @triggered="createAsset"/>
                </div>
            </div>
        </template>
    </Modal>    
</template>
<script setup lang="ts">
import MemberSelector from '@/components/Form/MemberSelector.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { Office, User } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
import { ref, useTemplateRef, onMounted, computed, watch } from 'vue';
import AssetStatus from 'assets/AssetStatus.json'
import LongInput from '../Form/LongInput.vue';
import AssetTypePicker from './AssetTypePicker.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { Asset } from '@/interface/assetInterface';
import ItemSelector from '../Form/ItemSelector.vue';
import { useAsset } from '@/composables/asset';
const emit = defineEmits<{
    close:[flag: boolean]
}>()

const props = defineProps<{
    editData: Asset | null,
    items: any[]
    offices: Office[]
}>()

const auth = useAuthUserStore()
const gl_exists = ref(0)
const loading = ref(false)



const gl_number = ref('')
const item_name = ref(props.editData?.item_name ?? '')
const classification = ref(props.editData?.classification ?? 2)
const value = ref(props.editData?.value ?? '')
const specs = ref(props.editData?.specs ?? '')
const status = ref(props.editData?.status ?? 1)
const externalUser = ref(props.editData?.external_user ?? '')
const isExternal = ref(props.editData?.external_user ? true : false)
const glNumberRef = useTemplateRef('glNumberRef')
const selectedUser = ref<User | null>(props.editData?.current_user ? props.editData?.current_user : auth.user ? auth.user : null )
const itemNameRef = useTemplateRef('itemNameRef')
const externalUserNameRef = useTemplateRef('externalUserNameRef')
const officeId = ref(props.editData?.office_id ?? null)

type AssetCategoryItemField = {
    id: number
    label: string | null
    input_type: 'shorttext' | 'longtext' | 'password'
    placeholder: string | null
    rules: string | null
    editable: boolean
}

type AssetCategoryItem = {
    id: number
    title: string
    fields: AssetCategoryItemField[]
}

const items = computed(() => (props.items ?? []) as AssetCategoryItem[])

const selectedItem = computed(() => {
    return items.value.find(i => i.title === item_name.value)
})

const selectedFields = computed(() => selectedItem.value?.fields ?? [])

const dynamicFieldValues = ref<Record<number, any>>({})
const dynamicFieldRefs = ref<any[]>([])

// A field is locked when editing an existing asset, editable === false, and the user is not an admin
const isFieldLocked = (field: AssetCategoryItemField): boolean => {
    if (!props.editData) return false
    if (auth.isAdmin) return false
    return field.editable === false
}


const api = useApi()
const { ping, ask } = useDialog()
const { userList } = useAsset()
onMounted(() => {
    // if (!props.editData) {
    //     projects.value = route.params.projectId ? Number(route.params.projectId) : null
    // }
    if(props.editData) {
        gl_number.value = padNumber(props.editData?.id)?.toString() ?? ''
    }

    // Prefill dynamic values from API-provided field_values (password is never prefilled)
    if (props.editData?.field_values && props.editData.field_values.length) {
        const map: Record<number, any> = {}
        for (const fv of props.editData.field_values) {
            if (fv.field?.input_type === 'password') continue
            map[fv.asset_category_item_field_id] = fv.value ?? ''
        }
        dynamicFieldValues.value = map
    }

})

watch(
    () => selectedItem.value?.id,
    () => {
        // Reset refs array when switching item
        dynamicFieldRefs.value = []

        // If editing and we already have field_values, keep what we loaded.
        if (props.editData?.field_values && props.editData.field_values.length) {
            return
        }

        // Otherwise reset values based on legacy fields (fallback mapping)
        const map: Record<number, any> = {}

        const firstField = selectedFields.value[0]
        const firstNonPasswordField = selectedFields.value.find(f => f.input_type !== 'password')

        const isAccountLike = selectedFields.value.some(f => f.input_type === 'password')

        if (!isAccountLike && firstField) {
            map[firstField.id] = props.editData?.model_number ?? ''
        }

        dynamicFieldValues.value = map
    }
)
 
const padNumber = (num: number | null) => {
    return num?.toString().padStart(5, "0")
}
const createAsset = async() => {   

    const validTargets: any[] = [itemNameRef.value]
    if (gl_exists.value === 0) {
        validTargets.push(glNumberRef.value)
    }        
    if (isExternal.value) {
        validTargets.push(externalUserNameRef.value)
    }

    // Only validate editable fields; locked fields are read-only and their rules are suppressed
    const editableFieldRefs = (dynamicFieldRefs.value ?? []).filter((_, i) => {
        const field = selectedFields.value[i]
        return field ? !isFieldLocked(field) : true
    })
    for (const target of editableFieldRefs) {
        validTargets.push(target)
    }

    let result = true
    for(const target of validTargets.filter(target => target !== null)){                
        const val = await target?.validate()
        const valid = val && val?.valid ? true : false
        result = result && valid
    }
    if (!result) {
        ping('必須項目を入力してください。')
        return
    }
    if(!auth.isAdmin){
        const confirmed = await ask('物品の作成を申請しますか？申請後は編集できません。')
        if(!confirmed.value) return
    }

    const selectedItemId = selectedItem.value?.id
    if (!selectedItemId) {
        ping('物品名（種類）を選択してください。')
        return
    }

    const field_values: Record<number, any> = {}
    for (const field of selectedFields.value) {
        field_values[field.id] = dynamicFieldValues.value[field.id] ?? ''
    }
    
    const params = {
        id: convertToHalfWidth(gl_number.value),
        params : {
            item_name: item_name.value,
            classification: classification.value,
            value: value.value,
            status: status.value,
            user_id: selectedUser.value?.id,
            specs: specs.value,
            external_user: isExternal.value ? externalUser.value : null,
            office_id: officeId.value ?? null,
            
        }
    }

    await api.post('/create_asset', {
        ...params,
        asset_category_item_id: selectedItemId,
        field_values,
    }, { toast: '保存しました。' })
    emit('close', true)

}

const choosAbleMembers = computed(() => {
    return userList.value
});

const convertToHalfWidth = (num: string) => {
    return num.normalize("NFKC")
}
</script>