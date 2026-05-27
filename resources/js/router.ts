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

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from) => {
    void to
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