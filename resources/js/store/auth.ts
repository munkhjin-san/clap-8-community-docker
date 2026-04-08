import { User } from '@/interface/globalInterface'
import axios from 'axios'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

type LinkedUser = User & { pivot: { active: number } }
type AuthUser = User & { linked?: LinkedUser[] }

export const useAuthUserStore = defineStore('authUser', () => {
    const name = ref<string>('')
    const id = ref<number | null>(null)
    const user = ref<AuthUser>({} as AuthUser)
    const isPartner = ref<boolean>(false)
    const isRegistered = ref<boolean>(false)
    const isOnLeave = ref<boolean>(false)
    const linked = ref<LinkedUser[]>([])

    function setUser(payload: any) {
        id.value = payload?.id ?? null
        name.value = payload?.name ?? ''
        user.value = payload ?? null
        isPartner.value = payload?.partner_flag == 1
        isRegistered.value = payload?.position_id == 15
        isOnLeave.value = payload?.on_leave
        linked.value = payload?.linked ?? []
    }

    function setFooterView(payload: boolean) {
        if (!user.value) return
            ; (user.value as any).footer_view = payload
    }

    async function setActiveUser(payload: number) {
        const response = await axios
            .patch('/set_active_linked_account', { id: payload })
            .then((res) => res.data)
        setUser(response?.user)
    }

    const activeUser = computed((): AuthUser => {
        if (user.value?.linked) {
            const active = user.value.linked.find(
                (ob) => ob.pivot.active
            )
            return active ? active : user.value
        }
        return user.value
    })

    const hasPrivilage = computed((): boolean => {
        const positionId = activeUser.value?.position_id
        return (
            (typeof positionId === 'number' && positionId <= 6) ||
            activeUser.value?.id === 610 ||
            activeUser.value?.id === 608
        )
    })

    const isAdmin = computed((): boolean => !!(id.value && [608, 610].includes(activeUser.value?.id ?? 0))  )

    const isBoss = computed(() => activeUser.value && activeUser.value.position_id && activeUser.value.position_id < 6)

    const isPM = computed(() => activeUser.value && activeUser.value.position_id == 6)

    const isMentor = computed(() => activeUser.value && (activeUser.value.general_position && activeUser.value.general_position !== '一般社員') || isBoss.value)

    const isEmployee = computed(() => activeUser.value && (activeUser.value.positions && (activeUser.value.positions.sort_flag > 6 && activeUser.value.positions.sort_flag <= 14)))
    return {
        name,
        id,
        user,
        isPartner,
        isRegistered,
        isOnLeave,
        linked,
        setUser,
        setFooterView,
        setActiveUser,
        activeUser,
        hasPrivilage,
        isAdmin,
        isBoss,
        isPM,
        isMentor,
        isEmployee
    }
})
