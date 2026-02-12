<template>
	<div class="rounded-[10px] border border-[var(--formBorder)] bg-[var(--bg3)] p-[14px]">
		<div class="flex items-start gap-[10px]">
			<div class="min-w-0 flex-1">
				<div class="text-[16px] font-bold truncate">
					{{ role.title || '（無題）' }}
				</div>
				<div class="mt-4 text-[12px] whitespace-pre-line break-words leading-normal text-[gray]">
					{{ role.description || '' }}
				</div>
                <div v-if="role.member_limit != null" class="mt-[6px] text-[12px]">
                    上限: {{ role.member_limit }}人
                </div>
                <div v-if="role.work_conditions.length" class="mt-5 text-[11px] flex flex-wrap gap-2">
                    <div class="rounded bg-[var(--bg2)] px-2 py-1" v-for="condition in role.work_conditions">{{ condition }}</div>
                </div>
			</div>
			<div v-if="isAuthorized" class="shrink-0">
				<ItemMenu :items="[
					{ title: '編集', action: () => emit('edit', role) },
					{ title: '削除', action: () => emit('delete', role) },
				]" />
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { MemberRole } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';
import { computed } from 'vue';

const props = defineProps<{
	role: MemberRole
}>()

const emit = defineEmits<{
	edit: [role: MemberRole]
	delete: [role: MemberRole]
}>()

const auth = useAuthUserStore()
const isAuthorized = computed(() => {
	const activeUserId = auth.activeUser?.id
	return auth.hasPrivilage || (activeUserId != null && props.role.user_id === activeUserId)
})
</script>