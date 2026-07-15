<template>
    <div class="admin-window non-trainee-control">
        <NonParticipantTable
            :loading="loading"
            :has-access-members="Boolean(accessMembers.length)"
            :members="nonParticipants"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useLearningApi } from '@/composables/learningApi';
import type { User } from '@/interface/globalInterface';
import type { LearningTheme } from '@/types/learning';
import NonParticipantTable from './Participant/NonParticipantTable.vue';
import { collectLearningParticipantIds } from '@/utils/learningParticipants';

const props = defineProps<{
    theme: LearningTheme
}>();
const learningApi = useLearningApi();
const loading = ref(true);
const participatedUserIds = ref<Set<number>>(new Set());

const accessMembers = computed<User[]>(() => {
    return props.theme?.access_members ?? [];
});

const nonParticipants = computed(() => {
    return accessMembers.value.filter((member) => !participatedUserIds.value.has(member.id));
});

const fetchParticipants = async() => {
    if (!props.theme?.id) {
        participatedUserIds.value = new Set();
        loading.value = false;
        return;
    }

    loading.value = true;

    const [materialList, portfolioList] = await Promise.all([
        learningApi.getMaterialProgressList(props.theme.id),
        learningApi.getAdminPortfolios(props.theme.id),
    ]);

    participatedUserIds.value = collectLearningParticipantIds(materialList, portfolioList);
    loading.value = false;
};

onMounted(() => {
    fetchParticipants();
});

watch(
    () => props.theme?.id,
    (themeId) => {
        if (themeId) {
            fetchParticipants();
        }
    }
);
</script>

<style scoped>
.non-trainee-control{
    overflow: visible;
}
</style>
