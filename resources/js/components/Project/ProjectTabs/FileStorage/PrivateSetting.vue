<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import GroupSelector from '@/components/Form/GroupSelector.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { ref, computed, watch } from 'vue';
import { TaskUser, User } from '@/interface/globalInterface';
import UserPanel from '@/components/Global/UserPanel.vue';
import { useAuthUserStore } from '@/store/auth';
type Props = {
    node: { id: string, name: string }
    saving?: boolean,
    selectableUsers?: User[]
    owner?: User | TaskUser | null
}
const auth = useAuthUserStore()
const props = defineProps<Props>()

const members = defineModel<User[]>('members', { default: [] });
const publicly = defineModel<boolean>('publicly', { default: true });

const unmembers = computed<User[]>(() => {
  const allowIds = new Set(members.value.map(u => u.id));
  return filteredOptions.value.filter(u => !allowIds.has(u.id));
});

const filteredOptions = computed<User[]>(() => {
    const blockIds = new Set(
        [props.owner?.id, auth.activeUser.id].filter(Boolean)
    )
    return (props.selectableUsers || []).filter(user => !blockIds.has(user.id))
})


const denyModel = computed<User[]>({
  get: () => unmembers.value,                 // show derived list
  set: (next) => {
    // user tried to "select" unaccessible: interpret as removing from members
    const nextDenyIds = new Set(next.map(u => u.id));
    const keep = members.value.filter(u => !nextDenyIds.has(u.id));
    if (keep.length !== members.value.length) members.value = keep;
  }
});
const emit = defineEmits<{
    (e: 'close'): void,
    (e: 'save', payload: { members: User[]; publicly: boolean } ): void
}>()

watch(filteredOptions, (opts) => {
  const allowed = new Set(opts.map(u => u.id));
  const pruned = members.value.filter(u => allowed.has(u.id));
  if (pruned.length !== members.value.length) members.value = pruned;
});
watch(publicly, (val) => {
  if (val) members.value = filteredOptions.value || [];
});

function onSave () {
    emit('save', { members: members.value, publicly: publicly.value });
}
</script>
<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>「{{ node.name }}」アクセス権限</p>
        </template>
        <template #content>
            <div v-if="owner">
                <p class="form-title-small mb-4 form-title-active">所有者</p>
                <div class="flex flex-col gap-2 text-sm opacity-80">
                    <UserPanel :withName="true" :user="owner" size="25"/>
                    <span>所有者は常にアクセス可能です（選択不要）</span>
                </div>
            </div>
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']">公開アクセス</p>
            </div>
            <div class="selectSwitchArea" style="width: fit-content;">    
                <input type="checkbox" id="edit_all" v-model="publicly">
                <label for="edit_all" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer']"><span></span>
                    <div class="switch-toggle"></div>
                </label>
            </div>
            <div :class="['si-box', publicly ? 'opacity-75' : '']">
                <MemberSelector 
                    placeHolder="アクセス可能メンバー"
                    ref="storageMemberSelector"
                    :options="filteredOptions"
                    :multiple="true"
                    :closeOnSelect="false"
                    :disabled="!!publicly"
                    v-model="members"
                />
            </div>
            <div :class="['si-box', 'opacity-75']">
                <MemberSelector 
                    placeHolder="アクセス不可能メンバー"
                    rules="required"
                    ref="storageMemberSelector"
                    :options="unmembers"
                    :closeOnSelect="false"
                    :disabled="true"
                    v-model="denyModel"
                />
            </div>
            <div class="si-box">
                <LoaderButton :loading="!!saving" content="保存する" @triggered="onSave"/>
            </div>
        </template>
    </Modal>
</template>