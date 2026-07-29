<template>
    <Transition name="modalFade">
        <div v-if="editorOpen" class="overlay" @mousedown="closeEditor">
            <div class="chatCreate scrollable" @mousedown.stop>
                <div class="recordFormTitle" style="display:flex">
                    <p>{{ editingGroup ? 'グループを編集する' : '新しいグループ作成する' }}</p>
                    <div class="cursor-pointer" @click="closeEditor" style="position:unset; margin:auto 0 auto auto">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div style="background: inherit;">
                    <div class="si-box">
                        <ShortInput
                            name="groupTitle"
                            placeHolder="タイトルを入力（必須）"
                            :rules="'required'"
                            customClass="full"
                            ref="groupTitle"
                            type="text"
                            v-model="title"
                        />
                    </div>
                    <div class="si-box">
                        <MemberSelector
                            :closeOnSelect="false"
                            placeHolder="メンバー選択"
                            rules="required"
                            v-model="editingUserList"
                            :multiple="true"
                            name="groupUsers"
                            ref="groupUsers"
                            path="calendar_more_users"
                        />
                    </div>
                    <p v-if="editingGroup" class="cmp__note">
                        既存メンバーの表示設定はそのまま残ります。追加したメンバーは表示ONで始まります。
                    </p>
                    <div style="margin-top: auto;padding-top: 30px;">
                        <LoaderButton @triggered="submit" :loading="loading" content="保存する"/>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <Transition name="modalFade">
        <div
            v-if="menu.parent == 'calendarMemberSelector'"
            id="calendarMemberSelector"
            class="calendarMemberSelector cmp"
            @click.stop="menu.name = ''"
        >
            <div class="cmp__tabs" role="tablist">
                <button
                    type="button"
                    role="tab"
                    class="cmp__tab"
                    :class="{ 'cmp__tab--active': activeTab === ALL_TAB }"
                    :aria-selected="activeTab === ALL_TAB"
                    @click="openTab(ALL_TAB)"
                >
                    <span class="cmp__tab-name">全員</span>
                    <!-- <span class="cmp__tab-count">{{ calendarMemberIds.size }}/{{ allMembers.length }}</span> -->
                </button>
                <button
                    v-for="group in groups"
                    :key="group.id"
                    type="button"
                    role="tab"
                    class="cmp__tab"
                    :class="{ 'cmp__tab--active': activeTab === group.id }"
                    :aria-selected="activeTab === group.id"
                    :title="group.name"
                    @click="openTab(group.id)"
                >
                    <span class="cmp__tab-name">{{ group.name }}</span>
                    <!-- <span class="cmp__tab-count">{{ selectedCount(group) }}/{{ group.users.length }}</span> -->
                </button>
                <button
                    type="button"
                    class="cmp__add"
                    title="グループ追加"
                    aria-label="グループ追加"
                    @click="createGroup"
                >
                    <AddIcon :size="10"/>
                </button>
            </div>

            <div class="cmp__toolbar">
                <PostSearchBar
                    :key="`cmp-search-${activeTab}`"
                    className="newChatMemberSearch"
                    :customPlaceHolder="displayedGroup ? 'グループ内を検索' : 'メンバー検索'"
                    @search-start="(word) => keyword = word"
                />
                <button v-if="displayedGroup" class="cmp__bulk" @click="bulkToggle">
                    {{ allOn ? 'すべて解除' : 'すべて選択' }}
                </button>
                <button v-else-if="extraUserIds.length" class="cmp__bulk" @click="bulkToggle">
                    追加分を解除
                </button>
                <template v-if="displayedGroup">
                    <button class="cmp__icon-button" title="グループを編集する" @click="editGroup(displayedGroup)">
                        <Edit :size="12"/>
                    </button>
                    <button class="cmp__icon-button" title="グループを削除する" @click="removeGroup(displayedGroup)">
                        <Trash :size="12"/>
                    </button>
                </template>
            </div>

            <div class="cmp__body">
                <TransitionGroup :name="chipTransition" tag="div" class="cmp__chips">
                    <template v-for="row in chipRows" :key="row.key">
                        <div v-if="row.kind === 'header'" class="cmp__section">
                            {{ row.label }}<span class="cmp__section-count">{{ row.count }}</span>
                        </div>
                        <button
                            v-else-if="row.kind === 'toggle'"
                            type="button"
                            class="cmp__more"
                            @click="showAllUnselected = !showAllUnselected"
                        >
                            {{ row.expanded ? '折りたたむ' : `すべて表示（他 ${row.count}人）` }}
                        </button>
                        <button
                            v-else
                            type="button"
                            class="cmp__chip"
                            :class="{ 'cmp__chip--on': row.on }"
                            :aria-pressed="row.on"
                            @click="toggleUser(row)"
                        >
                            <UserPanel
                                :disableInstant="true"
                                size="20"
                                :title="row.user!.name"
                                :user="row.user!"
                                imgClass="userMidIcon"
                            />
                            <span class="cmp__chip-name">{{ row.user!.name }}</span>
                            <span v-if="row.on" class="cmp__chip-remove" aria-hidden="true">×</span>
                        </button>
                    </template>
                </TransitionGroup>
                <div v-if="!pool.length" class="cmp__empty">
                    {{ keyword ? '該当するメンバーがいません。' : 'メンバーがいません。' }}
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, useTemplateRef } from 'vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import MemberSelector from '../Form/MemberSelector.vue'
import ShortInput from '../Form/ShortInput.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import AddIcon from '../Form/AddIcon.vue'
import PostSearchBar from '../Post/PostSearchBar.vue'
import Edit from '../Icons/Edit.vue'
import Trash from '../Icons/Trash.vue'
import { useMenuStore } from '@/store/menu'
import { useAuthUserStore } from '@/store/auth'
import { useCalendar } from '@/composables/calendar'
import { useApi } from '@/composables/api'
import { CalendarGroup, CalendarGroupUser } from '@/interface/calendarInterface'
import { User } from '@/interface/globalInterface'

const ALL_TAB = 'all' as const
type TabKey = typeof ALL_TAB | number

interface ChipRow {
    kind: 'header' | 'user' | 'toggle'
    key: string
    label?: string
    count?: number
    user?: User | CalendarGroupUser
    on?: boolean
    expanded?: boolean
}

/* 全ユーザータブの未選択は数百人になるので、最初は上位だけ見せる */
const UNSELECTED_PREVIEW = 10

const emit = defineEmits(['setActiveMembers', 'updated'])

const menu = useMenuStore()
const auth = useAuthUserStore()
const api = useApi()
const { myGroupData, getMyGroupData, extraUserIds } = useCalendar()

const activeTab = ref<TabKey>(ALL_TAB)
const keyword = ref('')
const showAllUnselected = ref(false)
const selfId = computed(() => auth.activeUser?.id)

/* 選択した順番。選択中リストは「後から選んだ人が末尾」になるようにする。
   解除したら記録を消すので、未選択リストでは元の位置に戻る。
   読み込み時点で既に選択済みの人は記録が無く、元の並び順のまま先頭側に残る。 */
const pickedOrder = ref(new Map<number, number>())
let pickCounter = 0
const markPicked = (id: number) => pickedOrder.value.set(id, ++pickCounter)
const unmarkPicked = (id: number) => pickedOrder.value.delete(id)
const loading = ref(false)
const editorOpen = ref(false)
const editingGroup = ref<CalendarGroup | null>(null)
const editingUserList = ref<User[]>([])
const title = ref('')
const groupTitle = useTemplateRef<any>('groupTitle')
const groupUsers = useTemplateRef<any>('groupUsers')

const groups = computed<CalendarGroup[]>(() => myGroupData.value?.my_groups ?? [])
const allMembers = computed<User[]>(() => myGroupData.value?.all_members ?? [])
/* サーバー側と同じ「選択中のグループは常に1つ」を前提にする */
const activeGroup = computed<CalendarGroup | undefined>(() => groups.value.find(g => g.selected) ?? groups.value[0])
/* 表示中のタブ。全ユーザータブのときは undefined */
const displayedGroup = computed<CalendarGroup | undefined>(() =>
    typeof activeTab.value === 'number' ? groups.value.find(g => g.id === activeTab.value) : undefined
)

const selectedCount = (group: CalendarGroup) =>
    group.users.filter(user => user.pivot?.selected_as_calendar_member).length

/* いまカレンダーに出ている人＝選択中グループの表示ONメンバー ＋ 追加ユーザー。
   全ユーザータブはこの集合をそのまま「選択中」として見せるので、
   グループ側で選んだメンバーも全ユーザータブで選択済みに見える。 */
const calendarMemberIds = computed<Set<number>>(() => {
    const ids = new Set<number>(extraUserIds.value)
    activeGroup.value?.users.forEach(user => {
        if (user.pivot?.selected_as_calendar_member) ids.add(user.id)
    })
    return ids
})

const isOn = (user: User | CalendarGroupUser) => displayedGroup.value
    ? !!(user as CalendarGroupUser).pivot?.selected_as_calendar_member
    : calendarMemberIds.value.has(user.id)

const pool = computed<(User | CalendarGroupUser)[]>(() => {
    const source = displayedGroup.value ? displayedGroup.value.users : allMembers.value
    const key = keyword.value.trim().toLowerCase()
    if (!key) return source
    return source.filter(user => user.name && user.name.toLowerCase().includes(key))
})

/* 選択中／未選択を1つの TransitionGroup に入れることで、
   チップがセクション間を「移動する」FLIP アニメーションになる */
const chipRows = computed<ChipRow[]>(() => {
    const on: (User | CalendarGroupUser)[] = []
    const off: (User | CalendarGroupUser)[] = []
    pool.value.forEach(user => (isOn(user) ? on : off).push(user))

    // 選んだ順に後ろへ。未記録（最初から選択済み）は 0 のままなので元の並びで前に残る
    if (on.length > 1) {
        on.sort((a, b) => (pickedOrder.value.get(a.id) ?? 0) - (pickedOrder.value.get(b.id) ?? 0))
    }

    const inAllTab = !displayedGroup.value
    let offList = off

    // 自分がまだ未選択なら未選択リストの先頭に出す
    if (inAllTab && selfId.value) {
        const index = offList.findIndex(user => user.id === selfId.value)
        if (index > 0) {
            offList = [offList[index], ...offList.slice(0, index), ...offList.slice(index + 1)]
        }
    }

    // 検索中は絞り込む意図があるので件数制限しない
    const collapsed = inAllTab && !showAllUnselected.value && !keyword.value.trim()
    const visibleOff = collapsed ? offList.slice(0, UNSELECTED_PREVIEW) : offList
    const hidden = offList.length - visibleOff.length

    const rows: ChipRow[] = []
    rows.push({ kind: 'header', key: 'header-on', label: '選択中', count: on.length })
    on.forEach(user => rows.push({ kind: 'user', key: `user-${user.id}`, user, on: true }))
    rows.push({ kind: 'header', key: 'header-off', label: '未選択', count: offList.length })
    visibleOff.forEach(user => rows.push({ kind: 'user', key: `user-${user.id}`, user, on: false }))
    if (inAllTab && (hidden > 0 || showAllUnselected.value) && offList.length > UNSELECTED_PREVIEW) {
        rows.push({ kind: 'toggle', key: 'toggle-unselected', count: hidden, expanded: !collapsed })
    }
    return rows
})

/* 全ユーザータブは数百件になり得るので、その場合は移動アニメーションを切る */
const chipTransition = computed(() => chipRows.value.length > 140 ? 'chip-flat' : 'chip')

const allOn = computed(() => {
    const group = displayedGroup.value
    if (!group || !group.users.length) return false
    return group.users.every(user => user.pivot?.selected_as_calendar_member)
})

const emitActiveMembers = () => {
    const seen = new Set<number>()
    const members: (CalendarGroupUser | User)[] = []
    activeGroup.value?.users.forEach(user => {
        if (user.pivot?.selected_as_calendar_member && !seen.has(user.id)) {
            seen.add(user.id)
            members.push(user)
        }
    })
    extraUserIds.value.forEach(id => {
        if (seen.has(id)) return
        const user = allMembers.value.find(member => member.id === id)
        if (user) {
            seen.add(id)
            members.push(user)
        }
    })
    emit('setActiveMembers', members)
}

/* トグルごとにカレンダーを取り直さないよう、まとめて1回だけ再取得する */
let refreshTimer: ReturnType<typeof setTimeout> | undefined
const scheduleRefresh = () => {
    clearTimeout(refreshTimer)
    refreshTimer = setTimeout(() => emit('updated'), 350)
}
onBeforeUnmount(() => clearTimeout(refreshTimer))

const applied = () => {
    emitActiveMembers()
    scheduleRefresh()
}

/* 最後に開いたタブを次回も開く。グループタブはサーバーの selected が正なので、
   localStorage に覚えておく必要があるのは「全ユーザー」を選んでいた場合だけ。 */
const TAB_STORAGE_KEY = 'calendar.memberPanel.activeTab'

const rememberTab = (tab: TabKey) => {
    try { localStorage.setItem(TAB_STORAGE_KEY, String(tab)) } catch (e) {}
}

const load = async (preferred?: TabKey) => {
    await getMyGroupData()
    const valid = preferred !== undefined
        && (preferred === ALL_TAB || groups.value.some(group => group.id === preferred))

    if (valid) {
        activeTab.value = preferred as TabKey
    } else {
        let stored: string | null = null
        try { stored = localStorage.getItem(TAB_STORAGE_KEY) } catch (e) {}
        activeTab.value = stored === ALL_TAB ? ALL_TAB : (activeGroup.value?.id ?? ALL_TAB)
    }

    rememberTab(activeTab.value)
    emitActiveMembers()
}

onMounted(() => load())

const openTab = async (tab: TabKey) => {
    if (activeTab.value !== tab) keyword.value = ''
    activeTab.value = tab
    showAllUnselected.value = false
    rememberTab(tab)
    if (tab === ALL_TAB) return

    const group = groups.value.find(item => item.id === tab)
    if (!group || group.selected) return

    const previous = groups.value.map(item => item.selected)
    groups.value.forEach(item => item.selected = item.id === tab ? 1 : 0)
    applied()
    try {
        await api.post('/select_my_group', { id: tab })
    } catch (e) {
        groups.value.forEach((item, index) => item.selected = previous[index])
        applied()
    }
}

const setMemberVisibility = async (group: CalendarGroup, member: CalendarGroupUser, next: number) => {
    const previous = member.pivot.selected_as_calendar_member
    member.pivot.selected_as_calendar_member = next
    next ? markPicked(member.id) : unmarkPicked(member.id)
    applied()
    try {
        await api.post('/update_selected_calendar_members', { group_id: group.id, user_id: member.id, value: next })
    } catch (e) {
        member.pivot.selected_as_calendar_member = previous
        previous ? markPicked(member.id) : unmarkPicked(member.id)
        applied()
    }
}

/* 追加ユーザーはサーバー保存。楽観的に反映して、失敗したら戻す。 */
const setExtraUser = async (memberId: number, add: boolean) => {
    const previous = [...extraUserIds.value]
    extraUserIds.value = add
        ? [...previous, memberId]
        : previous.filter(id => id !== memberId)
    add ? markPicked(memberId) : unmarkPicked(memberId)
    applied()
    try {
        await api.post('/update_calendar_extra_users', { member_id: memberId, value: add })
    } catch (e) {
        extraUserIds.value = previous
        add ? unmarkPicked(memberId) : markPicked(memberId)
        applied()
    }
}

/* 全ユーザータブでの切り替え。選択中グループのメンバーならそのグループの表示設定を、
   メンバー以外なら追加ユーザーの方を動かす。 */
const toggleCalendarMember = async (user: User) => {
    const group = activeGroup.value
    const member = group?.users.find(item => item.id === user.id)

    if (calendarMemberIds.value.has(user.id)) {
        const wasExtra = extraUserIds.value.includes(user.id)
        if (wasExtra) await setExtraUser(user.id, false)
        if (group && member?.pivot?.selected_as_calendar_member) {
            await setMemberVisibility(group, member, 0)
        } else if (!wasExtra) {
            applied()
        }
        return
    }

    if (group && member) {
        await setMemberVisibility(group, member, 1)
        return
    }
    await setExtraUser(user.id, true)
}

const toggleUser = async (row: ChipRow) => {
    const user = row.user
    if (!user) return

    const group = displayedGroup.value
    if (!group) {
        await toggleCalendarMember(user)
        return
    }
    await setMemberVisibility(group, user as CalendarGroupUser, (user as CalendarGroupUser).pivot.selected_as_calendar_member ? 0 : 1)
}

const bulkToggle = async () => {
    const group = displayedGroup.value
    if (!group) {
        if (!extraUserIds.value.length) return
        const previous = [...extraUserIds.value]
        extraUserIds.value = []
        previous.forEach(id => unmarkPicked(id))
        applied()
        try {
            await api.post('/update_calendar_extra_users', { member_id: -1, value: false })
        } catch (e) {
            extraUserIds.value = previous
            applied()
        }
        return
    }
    if (!group.users.length) return

    const next = allOn.value ? 0 : 1
    const previous = group.users.map(user => user.pivot.selected_as_calendar_member)
    group.users.forEach(user => user.pivot.selected_as_calendar_member = next)
    // 一括操作は元の並び順を保ちたいので、選択順の記録は消しておく
    group.users.forEach(user => unmarkPicked(user.id))
    applied()
    try {
        await api.post('/update_selected_calendar_members', { group_id: group.id, user_id: -1, value: next })
    } catch (e) {
        group.users.forEach((user, index) => user.pivot.selected_as_calendar_member = previous[index])
        applied()
    }
}

const createGroup = () => {
    editingGroup.value = null
    title.value = ''
    editingUserList.value = []
    editorOpen.value = true
}

const editGroup = (group: CalendarGroup) => {
    editingGroup.value = group
    title.value = group.name
    editingUserList.value = [...group.users]
    editorOpen.value = true
}

const closeEditor = () => {
    editorOpen.value = false
    editingGroup.value = null
    title.value = ''
    editingUserList.value = []
    loading.value = false
}

const validate = async () => {
    const targets = [groupTitle.value, groupUsers.value]
    let result = true
    for (const target of targets) {
        const validated = await target?.validate() || { valid: false }
        result = result && validated.valid
    }
    return result
}

const submit = async () => {
    loading.value = true
    if (!await validate()) {
        loading.value = false
        return
    }
    const params = {
        id: editingGroup.value ? editingGroup.value.id : null,
        title: title.value,
        users: editingUserList.value.map(user => user.id)
    }
    const saved = await api.post('/set_more_members', params, { toast: '保存しました。' })
    loading.value = false
    if (!saved) return

    closeEditor()
    keyword.value = ''
    await load(saved.id ?? ALL_TAB)
    emit('updated')
}

const removeGroup = async (group: CalendarGroup) => {
    const done = await api.post('/delete_my_group', { id: group.id }, { toast: '削除しました。', ask: `「${group.name}」を削除しますか。` })
    if (!done) return
    keyword.value = ''
    if (activeTab.value === group.id) activeTab.value = ALL_TAB
    await load()
    emit('updated')
}
</script>

<style lang="scss" scoped>
.cmp {
    width: 380px;
    max-width: calc(100vw - 30px);
    display: flex;
    flex-direction: column;
    color: var(--primary-color);
    font-size: 13px;
    /* タブ列が --bg3、選択中タブと本体が --background-color で繋がって見えるようにする */
    background: var(--background-color);
}
.cmp__tabs {
    display: flex;
    align-items: stretch;
    gap: 2px;
    padding: 10px 10px 0;
    background: var(--bg3);
    overflow: auto hidden;
    scrollbar-width: thin;
}
.cmp__tab {
    display: flex;
    align-items: center;
    gap: 7px;
    /* 潰れて読めなくなるより、帯を横スクロールさせる */
    flex: 0 0 auto;
    max-width: 240px;
    padding: 8px 10px;
    margin-bottom: -1px;
    background: transparent;
    border-bottom: none;
    color: var(--third-color);
    font: inherit;
    white-space: nowrap;
    cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.cmp__tab:hover {
    color: var(--primary-color);
}
.cmp__tab--active {
    background: var(--background-color);
    color: var(--primary-color);
    /* 本体と地続きに見せるため、下線を背景色で隠す */
    box-shadow: 0 1px 0 0 var(--background-color);
}
.cmp__tab-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    max-width: 20ch;
}
.cmp__tab-count {
    flex: 0 0 auto;
    font-size: 11px;
    color: var(--third-color);
}
/* タブが横に溢れてもスクロールで隠れないよう、右端に貼り付ける。
   margin-left:auto = 余白があるときは右寄せ、sticky = 溢れたら見える右端に固定 */
.cmp__add {
    position: sticky;
    right: 0;
    flex: 0 0 auto;
    align-self: center;
    margin-left: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    padding: 0;
    background: var(--background-color);
    border: 1px solid var(--calendarBorder);
    border-radius: 5px;
    cursor: pointer;
    fill: var(--primary-color);
    margin-top: -10px;
}
.cmp__toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 15px 0;

    /* PostSearchBar は自前の幅指定を持つので、ツールバー内で伸びるようにする */
    :deep(.newChatMemberSearch) {
        flex: 1 1 auto;
        min-width: 0;
    }
}
.cmp__bulk {
    flex: 0 0 auto;
    height: 30px;
    padding: 0 10px;
    background: transparent;
    border: 1px solid var(--formBorder);
    color: var(--primary-color);
    font-size: 12px;
    cursor: pointer;
    border-radius: 5px;
    &:hover {
        border-color: var(--primary-color);
    }
}
.cmp__icon-button {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background: transparent;
    border: 1px solid var(--formBorder);
    cursor: pointer;
    border-radius: 5px;
    &:hover {
        border-color: var(--primary-color);
    }
}
.cmp__body {
    padding: 0 15px 12px;
    max-height: 46vh;
    overflow-y: auto;
}
.cmp__chips {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    position: relative;
}
.cmp__section {
    flex: 0 0 100%;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 0 2px;
    color: var(--third-color);
    font-size: 12px;
}
.cmp__section-count {
    color: var(--third-color);
    opacity: .8;
}
/* 未選択リストの「すべて表示 / 折りたたむ」。チップと同じ行に流れるが、
   人ではないので枠も背景も持たせない */
.cmp__more {
    display: inline-flex;
    align-items: center;
    padding: 4px 2px;
    background: transparent;
    border: none;
    color: var(--third-color);
    font-family: inherit;
    font-size: 12px;
    cursor: pointer;

    &:hover {
        color: var(--primary-color);
    }
}
.cmp__chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 4px 10px 4px 4px;
    /* transparent なので未選択は枠なしに見え、--on / :hover で枠色だけが付く */
    border: 1px solid transparent;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--primary-color);
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    user-select: none;
    max-width: 100%;
    font-size: 12px;
}
.cmp__chip:hover {
    border-color: var(--formBorder);
}
.cmp__chip--on {
    border-color: var(--formBorder);
    color: var(--primary-color);
}
.cmp__chip-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cmp__chip-remove {
    font-size: 14px;
    line-height: 1;
    color: var(--third-color);
}
.cmp__empty {
    padding: 30px 0;
    text-align: center;
    color: var(--third-color);
}
.cmp__note {
    margin: 0;
    color: var(--third-color);
    font-size: 12px;
    line-height: 1.6;
}

/* チップの移動アニメーション（FLIP） */
.chip-move,
.chip-enter-active,
.chip-leave-active {
    transition: transform .34s cubic-bezier(.4, 0, .2, 1), opacity .22s ease;
}
.chip-enter-from,
.chip-leave-to {
    opacity: 0;
    transform: scale(.92);
}
.chip-leave-active {
    position: absolute;
}
</style>
