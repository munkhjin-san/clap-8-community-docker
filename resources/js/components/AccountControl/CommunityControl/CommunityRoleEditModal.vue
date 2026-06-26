<template>
    <Modal size="medium" :loader="loading" @close="emit('close')">
        <template #title>
            <p class="role-modal-title">{{ isCreate ? 'ロールを追加' : 'ロールを編集' }}</p>
        </template>
        <template #content>
            <div class="role-modal-field">
                <ShortInput
                    ref="nameRef"
                    name="role_name"
                    placeHolder="ロール名（必須）"
                    rules="required|max:255"
                    customClass="full"
                    :disabled="isAdmin"
                    v-model="draftName"
                />
                <p v-if="isAdmin" class="role-modal-hint">管理者ロールの名前は固定です。</p>
            </div>

            <div class="role-modal-field">
                <p class="role-modal-label">役職でメンバーを選択</p>
                <ItemSelector
                    placeHolder="役職を選ぶと、その役職のメンバーに置き換えます"
                    :multiple="false"
                    :clearable="true"
                    :options="positions"
                    :close-on-select="true"
                    v-model="selectedPosition"
                />
            </div>

            <div class="role-modal-field">
                <p class="role-modal-label">メンバー（{{ selectedMembers.length }}人）</p>
                <MemberSelector
                    placeHolder="メンバーを検索して追加"
                    :multiple="true"
                    :options="allUsers"
                    v-model="selectedMembers"
                />
                <p v-if="!isCreate" class="role-modal-hint">外したメンバーは「メンバー」ロールに戻ります。</p>
            </div>

            <div class="role-modal-actions">
                <LoaderButton content="保存する" :loading="saving" @triggered="save"/>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useApi } from '@/composables/api';
import type { User } from '@/interface/globalInterface';

type Position = { id: number; name: string }
type CommunityRole = {
    id: number
    key: string
    name: string
    capabilities: string[]
    members: User[]
}

const props = defineProps<{ role?: CommunityRole | null }>();
const emit = defineEmits<{ saved: [role: any, created: boolean]; close: [] }>();

const api = useApi();
const loading = ref(true);
const saving = ref(false);
const draftName = ref(props.role?.name ?? '');
const allUsers = ref<User[]>([]);
const positions = ref<Position[]>([]);
const selectedMembers = ref<User[]>([]);
const selectedPosition = ref<number | null>(null);
const nameRef = ref<InstanceType<typeof ShortInput> | null>(null);

const isCreate = computed(() => !props.role);
const isAdmin = computed(() => props.role?.key === 'admin');

onMounted(async () => {
    try {
        const { u, p } = await api.get('/get_controllable_users');
        allUsers.value = (u ?? []) as User[];
        positions.value = (p ?? []) as Position[];
        if (props.role) {
            const byId = new Map(allUsers.value.map(user => [user.id, user]));
            selectedMembers.value = (props.role.members ?? []).map(member => byId.get(member.id) ?? member);
        }
    } finally {
        loading.value = false;
    }
});

// Picking a position replaces the current selection with that position's members.
watch(selectedPosition, (positionId) => {
    if (positionId === null || positionId === undefined) return;
    selectedMembers.value = allUsers.value.filter(user => Number(user.position_id) === Number(positionId));
});

const save = async () => {
    if (saving.value) return;

    if (!isAdmin.value) {
        const nameValid = await nameRef.value?.validate();
        if (!nameValid?.valid) return;
    }

    saving.value = true;
    try {
        let roleId = props.role?.id;

        if (isCreate.value) {
            const created = await api.post('/community_context/roles', {
                name: draftName.value.trim(),
                capabilities: [],
            });
            roleId = created?.id;
        } else if (!isAdmin.value && draftName.value.trim() !== props.role?.name) {
            await api.patch(`/community_context/roles/${roleId}`, {
                name: draftName.value.trim(),
                capabilities: props.role?.capabilities ?? [],
            });
        }

        const updated = await api.patch(`/community_context/roles/${roleId}/members`, {
            user_ids: selectedMembers.value.map(user => user.id),
        }, { toast: 'ロールを保存しました' });

        emit('saved', updated, isCreate.value);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
.role-modal-title{ font-size: 16px; font-weight: 700; }
.role-modal-field{ margin-bottom: 22px; }
.role-modal-label{ font-size: 12px; font-weight: 700; color: gray; margin-bottom: 10px; }
.role-modal-hint{ font-size: 11px; color: gray; margin-top: 8px; }
.role-modal-actions{ display: flex; justify-content: flex-end; margin-top: 8px; }
</style>
