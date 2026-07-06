import { createRouter, createWebHistory } from 'vue-router'
import { useBoardList } from '@/composables/board'
import { useModal } from '@/composables/modal'
import { routes } from '@/routes'
import { useFilePreview } from '@/store/filePreview'
import { useKeyboardStore } from '@/store/keyboardStore'
import { useMessageUsers } from '@/store/messageUsers'
import { useProjectUsers } from '@/store/projectUsers'
import { useResponsive } from '@/store/responsive'
import { useSideMenuView } from '@/store/sideMenuView'
import { useAuthUserStore } from '@/store/auth'

const router = createRouter({
    history: createWebHistory(),
    routes,
})

// Maps an app route name to the capability that gates it. A role without the capability
// is redirected away. Dashboard/board/settings are built-in (no entry here).
const ROUTE_CAPABILITY: Record<string, string> = {
    project: 'app.project',
    schedule: 'app.schedule',
    timesheet: 'app.timesheet',
    learning: 'app.learning',
    contact: 'app.contact',
    post: 'app.post',
}

router.beforeEach((to, from) => {
    void from

    const keyboardStore = useKeyboardStore()
    keyboardStore.setKeyboardHeight(0)

    const virtualKeyboardNavigator = navigator as Navigator & {
        virtualKeyboard?: {
            overlaysContent: boolean
        }
    }

    if (virtualKeyboardNavigator.virtualKeyboard) {
        virtualKeyboardNavigator.virtualKeyboard.overlaysContent = false
    }

    // Block navigation to an app the active role can't access (admin bypasses
    // via can()). Skipped until auth is loaded; the backend enforces regardless.
    const auth = useAuthUserStore()
    if (auth.id) {
        for (const matched of to.matched) {
            const capability = ROUTE_CAPABILITY[matched.name as string]
            if (capability && !auth.can(capability)) {
                return { name: 'board' }
            }
        }
    }
})

router.afterEach((to, from) => {
    const responsive = useResponsive()
    const sideMenuView = useSideMenuView()

    if (responsive.mobile) {
        sideMenuView.setSideMenuView(false)
    }

    void to
    cleanUp(from)
})

function cleanUp(from: { name: unknown }) {
    const { setEmoteUsers } = useModal()
    const messageUsers = useMessageUsers()
    const filePreview = useFilePreview()
    const projectUsers = useProjectUsers()
    const { setNextCursor } = useBoardList()

    setEmoteUsers([])
    projectUsers.setProjectUsers({
        active: false,
        userList: [],
        title: '',
    })
    messageUsers.setMessageUsers({
        active: false,
        userList: [],
        title: '',
    })
    filePreview.setFilePreview({
        active: false,
        files: [],
        source: null,
        source_board_id: null,
        index: 0,
        message: null,
    })

    const fromName = typeof from.name === 'string' ? from.name : null
    if (!['board', 'room'].includes(fromName ?? '')) {
        setNextCursor(null)
    }
}

router.onError((error, to) => {
    if (
        error.message.includes('Failed to fetch dynamically imported module') ||
        error.message.includes('Importing a module script failed')
    ) {
        window.location.assign(to.fullPath)
    }
})

export default router