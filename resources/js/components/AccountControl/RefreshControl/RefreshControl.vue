<script lang="ts" setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const tabs = [
    {
        label: '申請レビュー',
        name: 'applications',
    },
    {
        label: '付与管理',
        name: 'management',
    },
];

const activeTab = computed(() => route.name as string);
</script>

<template>
    <div class="admin-window refresh-shell">
        <div class="refresh-topbar">
            <!-- <div class="topbar-copy">
                <h1>リフレッシュ</h1>
                <p>付与管理と利用申請確認</p>
            </div> -->

            <div class="sub-tab-container refresh-tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.name"
                    type="button"
                    :class="['sub-tab-item refresh-tab-item', { 'selected-sub-tab': activeTab === tab.name }]"
                    @click="router.push({ name: tab.name })"
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <div class="refresh-body">
            <RouterView />
        </div>
    </div>
</template>

<style lang="scss" scoped>
.refresh-shell {
    gap: 0;
}

.refresh-topbar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    margin: 14px 18px 10px;
}

.topbar-copy h1 {
    margin: 0;
    font-size: 24px;
    line-height: 1.2;
    color: var(--primary-color);
}

.topbar-copy p {
    margin: 4px 0 0;
    font-size: 12px;
    color: var(--text2);
}

.refresh-tabs {
    gap: 0;
    padding: 0 4px;
    background: transparent;
}

.refresh-tab-item {
    min-width: 120px;
    padding: 10px 14px;
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    background: transparent;
    color: var(--text2);
    font-size: 13px;
    transition: color 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
}

.refresh-tab-item.selected-sub-tab {
    color: var(--primary-color);
    border-bottom-color: #4b4b4b;
    background: transparent;
}

.refresh-tab-item:hover {
    color: var(--primary-color);
    background: rgba(0, 0, 0, 0.03);
}

.refresh-body {
    min-height: 0;
    flex: 1;
    overflow: hidden;
}

@media screen and (max-width: 720px) {
    .refresh-topbar {
        margin: 12px 12px 10px;
        flex-direction: column;
        align-items: stretch;
    }

    .refresh-tabs {
        overflow: auto;
        flex-wrap: nowrap;
        padding: 0;
    }

    .refresh-tab-item {
        min-width: 120px;
    }
}
</style>
