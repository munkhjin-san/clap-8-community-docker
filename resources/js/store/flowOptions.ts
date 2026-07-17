import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useApi } from '@/composables/api'
import type { FlowOptionUser, FlowOptionPosition, FlowOptionProject } from '@/types/flow'

/**
 * Users/positions/projects for Flow's user/member/project pickers and reference labels — near-static
 * reference data (same for every app, every record) that every Flow screen (list, detail, builder)
 * used to re-fetch independently on every mount/record-open. Cached here for the session instead:
 * first caller fetches, everyone after reuses the same data (and the same in-flight request, if one
 * is already running) until a full page reload.
 */
export const useFlowOptionsStore = defineStore('flowOptions', () => {
    const api = useApi()
    const users = ref<FlowOptionUser[]>([])
    const positions = ref<FlowOptionPosition[]>([])
    const projects = ref<FlowOptionProject[]>([])
    const loaded = ref(false)
    let pending: Promise<void> | null = null

    const load = (): Promise<void> => {
        if (loaded.value) return Promise.resolve()
        if (pending) return pending

        pending = api.get('/flow_options').then((data: any) => {
            if (data) {
                users.value = data.users ?? []
                positions.value = data.positions ?? []
                projects.value = data.projects ?? []
                loaded.value = true
            }
        }).finally(() => { pending = null })

        return pending
    }

    return { users, positions, projects, loaded, load }
})
