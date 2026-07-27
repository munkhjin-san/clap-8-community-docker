<template>
    <div class="help-container">
        <div class="help-container-inner">
            <div class="help-container-bar">
                <div class="admin-command-bar">
                    <div class="sub-tab-container">
                        <router-link
                            :to="{ name: 'tutorial' }"
                            class="sub-tab-item"
                            :class="{ 'selected-sub-tab': isTutorial }"
                        >
                            チュートリアル
                        </router-link>
                        <router-link
                            :to="{ name: 'help-documentation' }"
                            class="sub-tab-item"
                            :class="{ 'selected-sub-tab': isDocumentation }"
                        >
                            ドキュメント
                        </router-link>
                    </div>
                </div>
            </div>
            <div class="help-container-content">
                <router-view class="help-content" />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const isTutorial = computed(() => route.path.includes('/help/tutorial') || route.name === 'tutorial' || route.name === 'about-project')
const isDocumentation = computed(() => route.path.includes('/help/documentation') || route.name === 'help-documentation')
</script>
<style>
.help-container {
    display: flex;
    height: 100%;
    width: 100%;
    color: var(--primary-color);
    box-sizing: border-box;
}
.help-container-inner {
    display: flex;
    flex-direction: column;
    background: var(--background-color);
    width: 100%;
    min-height: 0;
    overflow: hidden;
}
.help-container-bar {
    flex-shrink: 0;
    padding: 12px 16px 0;
}
.help-container-content {
    flex: 1;
    min-height: 0;
    overflow: auto;
}
.help-content {
    min-height: 100%;
}
@media (max-width: 720px) {
    .help-container {
        padding: 0;
    }
}
</style>
