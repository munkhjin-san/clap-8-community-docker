<template>
    <div style="overflow:hidden; height: 100%; width: 100%;position: relative">
        <Transition name="modalFade">
            <div v-if="fetch === 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>
        <div class="admin-sub-c-bar">
            <div class="admin-work-header">
                <div class="receipt-title">領収書監査ログ</div>
            </div>
        </div>
        <div style="height:calc(100% - 70px);overflow: auto;" id="admin-table-container">
            <WorkReceiptAuditPanel :month="selectedDate" :users="users" />
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { DateTime } from 'luxon'
import { useApi } from '@/composables/api'
import WorkReceiptAuditPanel from './WorkReceiptAuditPanel.vue'

const api = useApi()
const users = ref([])
const fetch = ref(0)

const selectedDate = computed(() => {
    return ''
})

const loadUsers = async () => {
    const currentMonth = DateTime.now().toFormat('yyyy-MM')
    const data = await api.post('/get_admin_work', { month: currentMonth }, { silent: true })
    if (!data) return

    users.value = data.users ?? []
}

onMounted(async () => {
    await loadUsers()
    fetch.value++
})
</script>
<style scoped>
    .admin-work-header {
        display: flex;
        gap: 20px;
        align-items: center;
        width: 100%;
        justify-content: space-between;
    }

    .admin-month-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .receipt-title {
        font-size: 18px;
        font-weight: 700;
    }
</style>
