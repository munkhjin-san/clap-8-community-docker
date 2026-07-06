import { useApi } from '@/composables/api'
import { useAuthUserStore } from '@/store/auth'

/** Pull the freshest user record into the auth store (used after 2FA / OTP / color changes). */
export function useSettingsUser() {
    const api = useApi()
    const auth = useAuthUserStore()
    const updateUser = async () => {
        const response = await api.post('/profile_get_update_user', { id: auth.id })
        if (response && Object.hasOwn(response, 'id')) {
            auth.setUser(response)
        }
    }
    return { updateUser }
}

/** yyyy/MM/dd HH:mm — shared by the device & passkey lists. */
export function formatDate(v: string | number | Date | null | undefined): string {
    if (!v) return '-'
    const d = new Date(v)
    if (isNaN(d.getTime())) return '-'
    const p = (n: number) => String(n).padStart(2, '0')
    return `${d.getFullYear()}/${p(d.getMonth() + 1)}/${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`
}
