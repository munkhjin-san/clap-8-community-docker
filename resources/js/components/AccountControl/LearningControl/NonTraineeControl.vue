<template>
    <div class="admin-window" style="overflow: visible;">
        <div class="records-wrapper scrollable" style="padding: 0 20px 20px;">
            <div v-if="loading" class="records-empty">読み込み中...</div>

            <div v-else-if="!accessMembers.length" class="records-empty">
                アクセス可能メンバーが設定されていないため、全員が受講可能です。
            </div>

            <div v-else-if="!nonParticipants.length" class="records-empty">
                アクセス可能メンバーの全員が受講済みです。
            </div>

            <div v-else class="records-table">
                <div class="records-header">
                    <div class="header-row">
                        <div class="header-cell">未参加者</div>
                    </div>
                </div>

                <div class="records-body">
                    <div class="body-row" v-for="member in nonParticipants" :key="member.id">
                        <div class="body-cell border-none">{{ member.name }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useApi } from '@/composables/api';

const props = defineProps(['theme']);
const api = useApi();
const loading = ref(true);
const participatedUserIds = ref(new Set());

const accessMembers = computed(() => {
    return props.theme?.access_members ?? [];
});

const nonParticipants = computed(() => {
    return accessMembers.value.filter(member => !participatedUserIds.value.has(member.id));
});

const fetchParticipants = async() => {
    loading.value = true;

    const materialList = await api.get(`/get_material_list?lesson_theme_id=${props.theme.id}`);
    const portfolioList = await api.get(`/get_portfolios_list?theme_id=${props.theme.id}`);

    const ids = new Set();

    if (Array.isArray(materialList)) {
        materialList.forEach((item) => {
            if (item?.user?.id) {
                ids.add(item.user.id);
            }
        });
    } else if (materialList && typeof materialList === 'object') {
        Object.values(materialList).forEach((item) => {
            if (item?.user?.id) {
                ids.add(item.user.id);
            }
        });
    }

    if (Array.isArray(portfolioList)) {
        portfolioList.forEach((item) => {
            if (item?.user?.id) {
                ids.add(item.user.id);
            }
        });
    }

    participatedUserIds.value = ids;
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
.records-empty {
    padding: 20px 0;
    color: var(--light-color);
}

.body-cell {
    line-height: 2;
}
</style>
