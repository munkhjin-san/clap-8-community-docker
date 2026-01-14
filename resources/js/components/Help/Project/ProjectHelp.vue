<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import { useAuthUserStore } from '@/store/auth';
import { useTutorialStore } from '@/store/tutorial';
import { computed } from 'vue';
import { useRouter } from 'vue-router';
const router = useRouter();
const tutorialStore = useTutorialStore();
const auth = useAuthUserStore();
const hasPrivilage = computed(() => {
    return (auth.user?.position_id && auth.user?.position_id <= 6) || auth.activeUser.id === 610 || auth.activeUser.id === 608;
})
</script>

<template>
    <Modal @close="router.back()">
        <template #title>プロジェクト実績管理の使い方</template>
        <template #content>
            <div class="leading-normal">
                <div class="mb-5" v-if="hasPrivilage">
                    <h3>1. 実績管理機能付きプロジェクト作成</h3>
                    <div class="ml-4">
                        <p>実績管理機能には以下が含まれます：</p>
                        <ul>
                            <li>目標: メンバーごとの月次目標値を設定できます。設定した目標は、月・四半期・年間の実績画面で比較表示されます。</li>
                            <li>成果単位: 円, 件, 時間, カスタム単位（例：リード、契約数など）</li>
                        </ul>
                        <div class="jump-link" @click="tutorialStore.setTutorial({ active: true, name: ['project.create'] }), router.push({ name: 'project' })">プロジェクトを作成して見る</div>
                    </div>                    
                </div>
                <div class="mb-5">
                    <h3>{{ hasPrivilage ? 2 : 1 }}. 実績管理の確認</h3>
                    <div class="ml-4">
                        <ul>
                            <li>目標: プロジェクトの実績管理を確認することで、予算と成果の状況を把握できます。</li>
                            <li>まずプロジェクト一覧から対象のプロジェクトを選択してください。</li>
                            <li>プロジェクト詳細画面で「予算・実績」タブを選択すると、
                            収支確認や実績管理の内容を確認できます。</li>
                        </ul>
                        <div
                            class="jump-link"
                            @click="tutorialStore.setTutorial({ active: true, name: ['project.details', 'project.details.finance'] }), router.push({ name: 'project' })"
                        >
                            実績管理を確認してみる
                        </div>
                    </div>
                </div>
                <div class="mb-5">
                    <h3>{{ hasPrivilage ? 3 : 2 }}. 実績の入力</h3>
                    <div class="ml-4">
                    <ul>
                        <li>「実績を入力してみる」をクリックして、デイリーレポート（タイムシート）を開きます。</li>
                        <li>日報作成します。</li>
                        <li>実績管理が有効なプロジェクトを選択します。</li>
                        <li>成果（実績）、日付、メンバー（必要に応じて）を入力し、「保存」をクリックします。</li>
                    </ul>
                    <div
                        class="jump-link"
                        @click="tutorialStore.setTutorial({ active: true, name: ['timesheet.dailyreport'] }), router.push({ name: 'timesheet' })"
                    >
                        実績を入力してみる
                    </div>
                    </div>

                </div>
            </div>
        </template>
    </Modal>
</template>
<style scoped>
p{
    font-size: 14px;
}
</style>