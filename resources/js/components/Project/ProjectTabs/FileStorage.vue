<template>
    <div v-if="fileAccess" class="flex flex-col h-full overflow-y-auto bg-[var(--background-color)]" @drop="onDrop"
        @dragover="onDragOver">
        <!-- Toolbar -->
        <div class="sticky top-0 z-10 bg-[var(--background-color)]">
            <div class="flex items-center justify-between text-xs gap-2 p-4 relative">
                <div class="w-full md:w-auto">
                    <PostSearchBar customPlaceHolder="ファイル検索" className="newChatMemberSearch"
                        @search-start="(word) => { q = word }" />
                </div>
                <Transition name="slidePop">
                    <div v-if="selected.size > 1" class="w-fit flex items-center gap-3 h-[30px] px-3">
                        <div>
                            <p class="text-[12px]"><strong>{{ selected.size }}</strong> 個のアイテムが選択中</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="flex" @click="downloadSelected()">
                                <Download size="15" />
                            </button>
                            <button class="flex" @click="removeSelected()">
                                <Trash size="15" />
                            </button>
                            <button class="flex" @click="selected.clear()">
                                <CloseIcon size="10"/>
                            </button>
                        </div>
                        
                    </div>
                </Transition>
                <div class="ml-auto hidden md:flex inline-flex items-center gap-1 border border-white/10 bg-[var(--bg2)]/60 backdrop-blur supports-[backdrop-filter]:bg-[var(--bg2)]/40"
                    role="group" aria-label="View mode toggle">
                    <!-- GRID -->
                    <button :aria-pressed="isGrid" :class="[baseBtn, isGrid ? activeBtn : idleBtn]"
                        class="h-5 w-5 flex items-center justify-center" title="グリッド表示 (V)" @click="setView('grid')">
                        <Grid size="14" />
                    </button>

                    <!-- LIST -->
                    <button :aria-pressed="!isGrid" :class="[baseBtn, !isGrid ? activeBtn : idleBtn]"
                        class="h-5 w-5 flex items-center justify-center" title="リスト表示 (V)" @click="setView('list')">
                        <List size="14" />
                    </button>
                </div>
                
                
            </div>
            <!-- Progress -->
            <div v-if="progress > 0 && working" class="px-3 py-1 text-sm text-gray-600 fixed bottom-3 left-0 right-0 shadow-md bg-[var(--bg3)] mx-auto w-fit">
                <span>アップロード中…</span> 
                <span>{{ display }}</span>
                <span>%</span>
            </div>
            <div class="flex items-center justify-between p-2 bg-[var(--bg3)] mx-3 mb-3">
                <div class="text-sm text-gray-600 flex flex-wrap gap-1">
                    <div @click="router.back()" class="hidden under960:flex items-center mr-2" v-if="path.length > 1">
                        <Back size="12"/>
                    </div>
                    <template v-for="(p, i) in constructedPath" :key="p.id ?? 'root'">
                        <button class="hover:underline bg-[inherit] text-[12px]" @click="toBreadcrumb(p.id)">{{ p.name }}</button>
                        <span v-if="i < path.length - 1">/</span>
                    </template>
                </div>
                
            </div>
        </div>
        <!-- Breadcrumb -->


        <!-- Drop zone hint -->
        <!-- <div class="hidden md:flex mx-3 mb-2 p-2 rounded-lg border border-dashed text-xs text-gray-500">
            ここにファイルをドラッグ＆ドロップしてアップロード
        </div> -->

        <!-- Grid/List -->
        <div class="relative h-full pb-5" ref="grid" @click="onBackgroundClick">
            <div v-if="isGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 px-3 pb-2 under960:p-0 under960:py-3">
                <div v-for="n in filtered" :key="n.id"
                    class="group under960:text-[12px] under960:h-[40px] md:hover:bg-[var(--calendarBorder)] md:bg-[var(--bg3)] cursor-pointer select-none relative h-[50px] max-h-[50px] flex items-center px-[10px] under960:px-4"
                    :class="[{ 'md:bg-[var(--calendarBorder)]': selected.has(n.id) }, dropTarget === n.id ? 'ring-2 ring-blue-400' : '']" :data-item="true" :data-id="n.id"
                    draggable="true" @dragstart="onItemDragStart(n, $event)" @dragend="onItemDragEnd"
                    @dragover.stop.prevent="onItemDragOver(n, $event)" @dragleave="onItemDragLeave(n)" @drop.stop.prevent="onItemDrop(n, $event)"
                    @click.stop="toggleSelect(n, $event)" @touchstart="onTouchStart($event)" @touchmove="onTouchMove($event)" @touchend="onTouchEnd(n, $event)" @dblclick="onDblClick(n)">
                    <div class="flex items-center gap-3 w-full justify-between">
                        <div v-if="isImage(n)" class="max-w-[35px] max-h-[35px] min-w-[35px] min-h-[35px] flex items-center justify-center">
                            <img  
                                :src="driveThumbUrl(n.storage_path, 32, '222222')"
                                class="object-contain" 
                                loading="lazy" 
                                decoding="async" 
                            />
                        </div>
                        
                        <Folder v-else-if="n.type === 'folder'"/>
                        <FileIcon v-else :ext="n.ext" />

                        <div class="min-w-0 leading-tight">
                            <div class="truncate-2 font-medium text-sm whitespace-nowrap overflow-hidden text-ellipsis">{{ truncatedName(n.name, 25) }}</div>
                            <div class="text-[11px] text-gray-600">
                                <span v-if="n.type === 'file'">{{ fileSizeParser(n.size) }}</span>
                                <!-- <span>{{ DateParser(n.updated_at) }}</span> -->
                            </div>
                        </div>
                        <div class="ml-auto self-start" @click.stop @touchend.stop>
                            <ItemMenu :items="[
                                { title: '開く', action: () => { onDblClick(n) } },
                                { title: 'ダウンロード', action: () => { selected.clear(); selected.add(n.id); downloadSelected() } },
                                ...(n.type === 'file' ? [{ title: 'リンクをコピー', action: () => copyFileLink(n) }] : []),
                                ...(isManager || n.owner_id == auth.activeUser.id ? [{ title: '移動', action: () => { openMoveDialog(n.id) } }] : []),
                                ...(isManager || n.owner_id == auth.activeUser.id ? [{ title: 'アクセス権限', action: () => handleShareClick(n, true) }] : []),
                                ...(isManager || n.owner_id == auth.activeUser.id ? [{ title: '名前変更', action: () => { renameOne(n.id) } }] : []),
                                ...(isManager || n.owner_id == auth.activeUser.id ? [{ title: '削除', action: () => { removeSelected(n.id) } }] : []),
                            ]" />
                        </div>
                    </div> 
                    <!-- <div class="flex items-center mt-2" @click="openAccessibles(n)">
                        <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in accessibleUsers(n).slice(0,3)">  
                            <UserPanel :title="user.name" :disableInstant="true" size="15" :user="user" imgClass="userSmallIcon"/>                                         
                        </div>
                        <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="accessibleUsers(n).length > 3">...({{accessibleUsers(n).length}})</span>
                    </div> -->
                </div>
            </div>
            <div v-else class="px-4 pb-2">
                <!-- header -->
                <!-- <div
                    class="grid grid-cols-[auto_1fr_auto_auto_auto] items-center gap-3 px-3 py-2 text-xs uppercase opacity-60">
                    <div>種類</div>
                    <div>名前</div>
                    <div class="text-right">サイズ</div>
                    <div class="text-right">更新日</div>
                    <div>
                        <ItemMenu :items="[]" />
                    </div>
                </div> -->
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="text-[12px]">
                            <th class="text-left h-[40px] max-h-[40px] px-3 font-medium">名前</th>
                            <th class="text-left font-medium">アクセス権限</th>
                            <th class="text-left w-[80px] font-medium">サイズ</th>
                            <th class="text-left w-[140px] font-medium">更新日</th>
                            <th class="w-[30px]"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="n in filtered" :key="n.id"
                            class="relative hover:bg-[var(--bg3)] cursor-pointer select-none"
                            :class="[ selected.has(n.id) ? 'bg-[var(--bg3)]' : '', dropTarget === n.id ? 'ring-2 ring-blue-400' : '' ]" :data-item="true" :data-id="n.id"
                            draggable="true" @dragstart="onItemDragStart(n, $event)" @dragend="onItemDragEnd"
                            @dragover.stop.prevent="onItemDragOver(n, $event)" @dragleave="onItemDragLeave(n)" @drop.stop.prevent="onItemDrop(n, $event)"
                            @click.stop="toggleSelect(n, $event)" @dblclick="onDblClick(n)"
                            style="border-bottom: 1px solid var(--calendarBorder)"
                        >
                            <td class="h-[50px] max-h-[50px] px-3">
                                <div class="flex items-center gap-3">
                                    <div v-if="isImage(n)" class="max-w-[35px] max-h-[35px] min-w-[35px] min-h-[35px] flex items-center justify-center">
                                        <img 
                                            :src="driveThumbUrl(n.storage_path, 32, '222222')"
                                            class="w-8 h-8 object-contain rounded" 
                                            loading="lazy" 
                                        />
                                    </div>
                                    <Folder v-else-if="n.type === 'folder'"/>
                                    <FileIcon v-else :ext="n.ext" />

                                    <div class="min-w-0">
                                        <div class="truncate text-[13px] leading-normal whitespace-nowrap overflow-hidden text-ellipsis">{{ truncatedName(n.name, 30) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div @click="openAccessibles(n)">
                                    <div class="flex items-center" v-if="n.visibility === 'private'" >
                                        <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in accessibleUsers(n).slice(0,3)">  
                                            <UserPanel :title="user.name" :disableInstant="true" size="15" :user="user" imgClass="userSmallIcon"/>                                         
                                        </div>
                                        <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="accessibleUsers(n).length > 3">...({{accessibleUsers(n).length}})</span>
                                    </div>
                                    <span class="text-xs" v-else>公共</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-left text-[11px] tabular-nums">
                                    <span v-if="n.type === 'file'">{{ fileSizeParser(n.size) }}</span>
                                    <span v-else>--</span>
                                </div>
                            </td>
                            <td>
                                 <div class="text-left text-xs opacity-80">
                                    {{ DateParser(n.updated_at) }}
                                </div>
                            </td>
                            <td>
                                <div class="">
                                    <ItemMenu :items="[
                                        { title: '開く', action: () => { onDblClick(n) } },
                                        { title: 'ダウンロード', action: () => { selected.clear(); selected.add(n.id); downloadSelected() } },
                                        ...(n.type === 'file' ? [{ title: 'リンクをコピー', action: () => copyFileLink(n) }] : []),
                                        ...(isManager || n.owner_id == auth.activeUser.id ? [{ title: '移動', action: () => { openMoveDialog(n.id) } }] : []),
                                        ...(isManager || n.owner_id == auth.activeUser.id ? [{ title: 'アクセス権限', action: () => { handleShareClick(n, true) } }] : []),
                                        ...(isManager || n.owner_id == auth.activeUser.id ? [{ title: '名前変更', action: () => { renameOne(n.id) } }] : []),
                                        ...(isManager || n.owner_id == auth.activeUser.id ? [{ title: '削除', action: () => { removeSelected(n.id) } }] : []),
                                    ]" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
            </div>

            <div v-if="marquee.active" class="pointer-events-none absolute border-2 border-blue-400/70 bg-blue-400/10"
                :style="marqueeStyle">
            </div>
            <div v-if="working && items.length === 0" class="p-6 text-center text-gray-500">読み込み中…</div>
            <div v-else-if="!working && items.length === 0" class="p-6 text-center text-gray-400">現在アイテムはありません</div>
        </div>
        <PrivateSetting v-if="shareDlg.open" :node="shareDlg.node!" v-model:members="shareDlg.members"
            v-model:publicly="shareDlg.publicly" :selectableUsers="selectableUsers" :saving="shareDlg.saving"
            :owner="shareDlg.owner" @close="closeShare" @save="savePrivateSelection" />
        <AccessMembers v-if="viewAcls.open" :acsMembers="viewAcls.accessibles" :owner="viewAcls.owner" :unacsMembers="viewAcls.unaccessibles" @close="viewAcls.open = false"/>
        <Transition name="modalFade">
            <div id="storageCreationMenu" class="boxMenu boardMenuIcon viewSwitchMenu" v-if="menu.name == 'storageCreationMenu'">   
                <div class="boxMenuItems cursor-pointer" @click.stop="createFolder(); menu.close()">
                    <span>新しいフォルダ作成</span>
                </div>
                <div class="boxMenuItems" @click.stop="menu.close()">
                    <label class="w-full h-full cursor-pointer flex items-center">
                        <span>ファイルアップロード</span>
                        <input type="file" class="hidden" multiple @change="onPickFiles" />
                    </label>
                </div>
            </div>
        </Transition>
        <!-- Move Dialog -->
        <Transition name="modalFade">
            <div v-if="moveDlg.open" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center" @click.self="closeMoveDialog">
                <div class="bg-[var(--bg2)] w-[90vw] max-w-xl rounded shadow p-4">
                    <div class="text-sm mb-3">
                        <div class="flex items-center justify-between">
                            <div class="text-white/80">移動先を選択</div>
                            <button class="text-white/70 hover:text-white bg-[inherit]" @click="closeMoveDialog">✕</button>
                        </div>
                        <div class="mt-2 text-xs text-white/70 flex flex-wrap gap-1">
                            <template v-for="(c, i) in moveDlg.path" :key="c.id ?? 'root'">
                                <span v-if="i > 0">/</span>
                                <button class="underline bg-[inherit]" @click="moveBrowse(c.id)">{{ c.name }}</button>
                            </template>
                        </div>
                    </div>
                    <div class="max-h-[50vh] overflow-y-auto border border-white/10">
                        <div v-for="f in moveDlg.folders" :key="f.id" class="px-3 py-2 hover:bg-white/10 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Folder />
                                <button class="underline bg-[inherit]" @click="moveBrowse(f.id)">{{ f.name }}</button>
                            </div>
                            <button class="text-xs px-2 py-1 bg-white/10 hover:bg-white/20" @click="confirmMove(f.id)">ここへ</button>
                        </div>
                        <div v-if="moveDlg.folders.length === 0" class="px-3 py-6 text-center text-white/50 text-sm">フォルダがありません</div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="text-xs text-white/60">現在: {{ moveDlg.path.at(-1)?.name ?? 'ホーム' }}</div>
                        <div class="flex gap-2">
                            <button class="px-3 py-1 bg-white/10 hover:bg-white/20 text-sm" @click="confirmMove(null)">ホームへ移動</button>
                            <button class="l-button" @click="closeMoveDialog">キャンセル</button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
        <FloatButton title="新規作成" @click.stop="menu.setMenu({name: 'storageCreationMenu', id: 1})" class="!fixed">
            <template #icon>
                <AddIcon fill="black"/>
            </template>
        </FloatButton>
    </div>
    <div v-else class="bg-[var(--background-color)] h-full text-center justify-center flex items-center flex-col">
        <p>アクセス権限ありません。</p>
        <router-link class="l-button" style="margin: 30px 0 70px 0;" :to="{ name: 'overview' }">概要へ戻る</router-link>
    </div>
</template>
<!-- DriveExplorer.vue -->
<script setup lang="ts">
import { ref, computed, reactive, nextTick, useTemplateRef, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router';
import { useDialog } from '@/composables/dialog';
import { useFilePreview } from '@/store/filePreview';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import FileIcon from '@/components/Board/Mixed/FileIcon.vue';
import { useAuthUserStore } from '@/store/auth';
import PrivateSetting from './FileStorage/PrivateSetting.vue';
import { sharingApi } from '@/composables/sharing';
import { useApi } from '@/composables/api';
import axios from 'axios';
import { TaskUser, User } from '@/interface/globalInterface';
import Grid from '@/components/Icons/Grid.vue';
import List from '@/components/Icons/List.vue';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import { DateParser, fileSizeParser, truncatedName } from '@/utils/tools';
import Folder from '@/components/Icons/Folder.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import AccessMembers from './FileStorage/AccessMembers.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useMenuStore } from '@/store/menu';
import Download from '@/components/Icons/Download.vue';
import Trash from '@/components/Icons/Trash.vue';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import { useResponsive } from '@/store/responsive';
import Back from '@/components/Icons/Back.vue';
type Node = {
    id: string
    type: 'file' | 'folder'
    name: string
    size: number
    mime: string | null
    updated_at: string
    url?: string          // original image (or download)
    thumb_url?: string
    storage_path: string
    ext: string | null
    owner_id: number | null
    owner?: User | TaskUser | null
    visibility: 'private' | 'public'
    acls: {node_id: number, user_id: number}[] | []
}
type ViewMode = 'grid' | 'list'

const tsApi = useApi()
const props = defineProps<{
    selectedProject?: any | null,
    fileAccess?: boolean,
    parentId?: string | null
    userList: any[]
    mentionableUsers: any[]

}>()
const menu = useMenuStore()
// swap with your own api composable if needed
const api = {
    list: (parentId: string | null) => tsApi.get('/drive', { parent_id: parentId, project_id: projectId }),
    createFolder: (parentId: string | null, name: string) => tsApi.post('/drive/folders', { parent_id: parentId, name, project_id: projectId }, { toast: 'フォルダを作成しました' }),
    upload: (parentId: string | null, files: File[]) => {
        const fd = new FormData()
        fd.append('parent_id', parentId ?? '')
        fd.append('project_id', projectId ?? '')
        for (const f of files) fd.append('file[]', f)
        return tsApi.post('/drive/upload', fd, { toast: 'アップロードが完了しました' }, { onUploadProgress: e => { progress.value = Math.round((e.loaded / e.total!) * 100) } })
    },
    rename: (id: string, payload: { name: string, project_id: string | null }) => tsApi.patch(`/drive/${id}`, payload, { toast: '名前を変更しました' }),
    remove: (id: string) => tsApi.del(`/drive/${id}`, {}, { toast: '削除しました' }),
    // downloads:
    file: (id: string) =>
        axios.get(`/drive/files/${id}/download`, { responseType: 'blob', withCredentials: true }),

    folderZip: (id: string) =>
        axios.get(`/drive/folders/${id}/download.zip`, { responseType: 'blob', withCredentials: true }),

    multiZip: (ids: string[]) =>
        axios.post(`/drive/zip`, { ids, project_id: projectId }, { responseType: 'blob', withCredentials: true }),
    move: (ids: string[], dest_id: string | null) => tsApi.post('/drive/move', { ids, dest_id, project_id: projectId }, { toast: '移動しました' }),
}
const { ask, askInput, toast, ping } = useDialog()
const grid = useTemplateRef('grid');
const auth = useAuthUserStore();
const route = useRoute();
const parentId = ref<string | null>(props.parentId ?? null)
const lastLoadedId = ref<string | null>(null)
const path = ref<{ id: string | null, name: string }[]>([])
const items = ref<Node[]>([])
const selected = ref<Set<string>>(new Set<string>());
const working = ref(false)
const progress = ref(0)
const q = ref('')
const projectId = route.params.projectId as string | null
const router = useRouter()

const isMobile = computed(() => window.matchMedia('(max-width: 768px)').matches)
const viewMode = ref<ViewMode>(isMobile.value ? 'grid' : (localStorage.getItem('drive:viewMode') as ViewMode) || 'grid')
watch(viewMode, v => localStorage.setItem('drive:viewMode', v))
const isGrid = computed(() => viewMode.value === 'grid')
const filtered = computed(() => {
    const term = q.value.trim().toLowerCase()
    if (!term) return items.value
    return items.value.filter(i => i.name.toLowerCase().includes(term))
})
const filesOnly = computed(() => filtered.value.filter(i => isFile(i)))
const fileIndexById = computed(() => {
    const map = new Map<string, number>()
    filesOnly.value.forEach((f, idx) => map.set(f.id, idx))
    return map
})
const marquee = reactive({
    active: false,
    x1: 0, y1: 0,
    x2: 0, y2: 0,
    additive: false,          // Ctrl/Meta pressed to add to selection instead of replace
    baseline: new Set<string>(),
});
const dropTarget = ref<string | null>(null)
const isManager = computed(() => {
    if (!props.selectedProject) return false
    return props.selectedProject.manager?.some(manager => manager.id === auth.id) ? true : false
})
const selectableUsers = computed(() => {
    if (!props.selectedProject) return []
    return props.selectedProject.manager?.concat(props.selectedProject.members) || []
})

const marqueeStyle = computed(() => {
    const left = Math.min(marquee.x1, marquee.x2);
    const top = Math.min(marquee.y1, marquee.y2);
    const width = Math.abs(marquee.x1 - marquee.x2);
    const height = Math.abs(marquee.y1 - marquee.y2);
    return { left: `${left}px`, top: `${top}px`, width: `${width}px`, height: `${height}px` };
});
const viewAcls = ref({
    open: false,
    accessibles: [] as (User | TaskUser)[],
    unaccessibles: [] as (User | TaskUser)[],
    owner: null as (User | TaskUser) | null,
})


const openAccessibles = (n: Node) => {
    viewAcls.value.accessibles = accessibleUsers(n)
    const allowIds = new Set(viewAcls.value.accessibles.map(u => u.id));
    viewAcls.value.unaccessibles = (selectableUsers.value || []).filter(u => !allowIds.has(u.id) && u.id !== n.owner_id)
    viewAcls.value.owner = n.owner || null
    viewAcls.value.open = true
}
const accessibleUsers = (n: Node) => {
    if (!selectableUsers.value) return []
    const aclUsers = n.acls.map(acl => {
        const user = selectableUsers.value.find(member => member.id === acl.user_id)
        return user ? { ...user } : null
    }).filter(Boolean) as (User)[]
    return n.owner ? [n.owner, ...aclUsers.filter(u => u.id !== n.owner?.id)] : aclUsers 
}
const setView = (mode: ViewMode) => {
    if (viewMode.value !== mode) viewMode.value = mode
}
window.addEventListener('keydown', e => {
    if (e.key.toLowerCase() === 'v' && !e.metaKey && !e.ctrlKey && !e.altKey) {
        viewMode.value = isGrid.value ? 'list' : 'grid'
    }
})
const responsive = useResponsive()
const constructedPath = computed(() => {
    if(responsive.mobile) {
        return path.value.slice(-1)
    }
    return path.value
})
const onBackgroundClick = (e) => {
    const hitItem = e.target.closest('[data-item]');
    if (!hitItem) selected.value.clear();
}
const startMarquee = (e) => {
    if (e.target.closest('[data-item]')) return;
    if (!grid.value) return
    const rect = grid.value.getBoundingClientRect();
    if (!rect) return
    marquee.active = true;
    marquee.additive = e.ctrlKey || e.metaKey || e.shiftKey;
    marquee.baseline = new Set(selected.value);     // remember current selection

    marquee.x1 = marquee.x2 = e.clientX - rect.left + grid.value.scrollLeft;
    marquee.y1 = marquee.y2 = e.clientY - rect.top + grid.value.scrollTop;

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp, { once: true });

    // prevent text selection while dragging
    e.preventDefault();
}
const DRAG_SLOP = 4;
const onMove = (e: MouseEvent) => {
    if (!grid.value) return
    const rect = grid.value.getBoundingClientRect();
    marquee.x2 = e.clientX - rect.left + grid.value.scrollLeft;
    marquee.y2 = e.clientY - rect.top + grid.value.scrollTop;

    const w = Math.abs(marquee.x2 - marquee.x1);
    const h = Math.abs(marquee.y2 - marquee.y1);
    if (w < DRAG_SLOP && h < DRAG_SLOP) return;
    // live update selection
    applyMarqueeSelection();
}
const applyMarqueeSelection = () => {
    const r = normRect(marquee);
    if (!grid.value) return
    // build NodeList of all cards
    const cards = grid.value.querySelectorAll('[data-item]');
    // temp selection set we will commit at the end
    const temp = new Set(marquee.additive ? marquee.baseline : []);

    cards.forEach(card => {
        const idAttr = card.getAttribute('data-v-app') ? null : null; // ignore, just to show not used
        const itemId = getItemIdFromVNode(card);
        if (!itemId) return;

        const cr = card.getBoundingClientRect();
        // convert to grid-local coords
        if (!grid.value) return
        const gr = grid.value.getBoundingClientRect();
        const box = {
            left: cr.left - gr.left + grid.value.scrollLeft,
            top: cr.top - gr.top + grid.value.scrollTop,
            right: cr.right - gr.left + grid.value.scrollLeft,
            bottom: cr.bottom - gr.top + grid.value.scrollTop,
        };

        if (hitTest(r, box)) temp.add(itemId);

        else if (marquee.additive && marquee.baseline.has(itemId)) temp.add(itemId);
    });

    selected.value = temp;
}
const getItemIdFromVNode = (card) => {
    const raw = card.getAttribute('data-id');
    return raw ? Number.isNaN(+raw) ? raw : +raw : null;
}
const normRect = (m: any) => {
    const pad = 1.5;
    const left = Math.min(m.x1, m.x2) - pad;
    const top = Math.min(m.y1, m.y2) - pad;
    const right = Math.max(m.x1, m.x2) + pad;
    const bottom = Math.max(m.y1, m.y2) + pad;
    return { left, top, right, bottom };
}

const intersects = (a, b) => {
    return !(b.left > a.right || b.right < a.left || b.top > a.bottom || b.bottom < a.top);
}
const onUp = () => {
    window.removeEventListener('mousemove', onMove);
    marquee.active = false;
}
const load = async (id: string | null) => {
    working.value = true
    try {
        const data = await api.list(id)
        parentId.value = data.parent?.id ?? null
        path.value = data.path
        items.value = data.items
        selected.value.clear()
    } finally {
        working.value = false
    }
}

const SELECT_MODE: 'touch' | 'center' | 'ratio' | 'cover' = 'ratio';

const MIN_RATIO = 0.15;

function hitTest(a: { left: number; top: number; right: number; bottom: number },
    b: { left: number; top: number; right: number; bottom: number }) {
    switch (SELECT_MODE) {
        case 'touch':
            return !(b.left > a.right || b.right < a.left || b.top > a.bottom || b.bottom < a.top);

        case 'center': {
            const cx = (b.left + b.right) / 2;
            const cy = (b.top + b.bottom) / 2;
            return cx >= a.left && cx <= a.right && cy >= a.top && cy <= a.bottom;
        }

        case 'ratio': {
            const overlap = intersectionArea(a, b);
            const areaB = Math.max(1, (b.right - b.left) * (b.bottom - b.top));
            return overlap / areaB >= MIN_RATIO;
        }

        case 'cover':
            return a.left <= b.left && a.right >= b.right && a.top <= b.top && a.bottom >= b.bottom;
    }
}
const intersectionArea = (a: any, b: any) => {
    const w = Math.max(0, Math.min(a.right, b.right) - Math.max(a.left, b.left));
    const h = Math.max(0, Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top));
    return w * h;
}

const isFile = (n: Node) => {
    return n.type === 'file'
}
const isImage = (n: Node) => {
    return n.type === 'file' && !!n.mime && n.mime.startsWith('image/')
}
const fileMime = (n: Node) => {
    return n.mime?.split('/')[0] as string
}
const b64url = (s: string) => btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
const driveThumbUrl = (storage_path: string, size = 128, color = '222222') =>
    `/drive_thumbnail/${b64url(storage_path)}/${size}/${color.replace('#', '')}`
const startX = ref(0)
const startY = ref(0)
const isScrolling = ref(false)

const onTouchStart = (e: TouchEvent) => {
    if (e.touches.length !== 1) return
    const t = e.touches[0]
    startX.value = t.clientX
    startY.value = t.clientY
    isScrolling.value = false
}
const onTouchMove = (e: TouchEvent) => {
    if (e.touches.length !== 1) return
    const t = e.touches[0]
    const dx = Math.abs(t.clientX - startX.value)
    const dy = Math.abs(t.clientY - startY.value)
    if (dx > 10 || dy > 10) isScrolling.value = true
}
const onTouchEnd = (n: Node, e: TouchEvent) => {
    if (isScrolling.value) return
    onDblClick(n)
}
const toggleSelect = (n: Node, ev?: MouseEvent | PointerEvent ) => {
    if (ev?.metaKey || ev?.ctrlKey) {
        if (selected.value.has(n.id)) selected.value.delete(n.id)
        else selected.value.add(n.id)
    } else {
        // single select
        selected.value.clear()
        selected.value.add(n.id)
    }
}

const createFolder = async() => {
    const taken = new Set(items.value.map(i => i.name));
    const suggested = suggestNext('新しいフォルダ', taken);

    const { input, decision } = await askInput(
        'フォルダ名を入力',
        {
            value: '新しいフォルダ',
            required: true,
            selectBaseName: true,
            submitText: '作成',
            validate: v => {
                const name = v.trim();
                // if (taken.has(name)) return '同名のフォルダが存在します。';
                if (ILLEGAL.test(name)) return '使用できない文字が含まれています。';
                return null;
            }
        },
        { answers: [{ value: true, label: 'OK' }, { value: false, label: 'キャンセル' }] }
    );
    if (!decision.value || !input) return;

    working.value = true;
    try {
        await api.createFolder(parentId.value, input);
        await load(parentId.value);
    } finally {
        working.value = false;
    }
}

const ILLEGAL = /[\\/:*?"<>|]/;

const suggestNext = (base: string, taken: Set<string>) => {
    if (!taken.has(base)) return base;
    const esc = (s: string) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const re = new RegExp(`^${esc(base)} \\((\\d+)\\)$`);
    let maxN = 2;
    for (const name of taken) {
        const m = name.match(re);
        if (m) maxN = Math.max(maxN, Number(m[1]) + 1);
    }
    return `${base} (${maxN})`;
}
const onPickFiles = (ev: Event) => {
    const input = ev.target as HTMLInputElement
    if (!input.files?.length) return
    upload(Array.from(input.files))
    input.value = '' // reset
}

const upload = async(files: File[]) => {
    if (!files.length) return
    working.value = true
    progress.value = 0
    try {
        await api.upload(parentId.value, files)
        await load(parentId.value)
    } catch (e: any) {
        alert(e?.response?.data?.message || 'アップロードに失敗しました')
    } finally {
        working.value = false
        progress.value = 0
    }
}

// drag & drop
const onDrop = (e: DragEvent) => {
    e.preventDefault()
    const files = Array.from(e.dataTransfer?.files ?? [])
    upload(files)
}
const onDragOver = (e: DragEvent) => { e.preventDefault() }

// internal drag & drop (move)
const onItemDragStart = (n: Node, e: DragEvent) => {
    if (!selected.value.has(n.id)) {
        selected.value.clear();
        selected.value.add(n.id);
    }
    // permission check: all selected items must be movable by user
    if (!canMoveAllSelected()) {
        e.preventDefault();
        ping('移動権限がありません');
        return;
    }
    const ids = Array.from(selected.value);
    e.dataTransfer?.setData('application/x-drive-ids', JSON.stringify(ids));
    if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
}
const onItemDragEnd = () => { dropTarget.value = null }
const onItemDragOver = (n: Node, e: DragEvent) => {
    const data = e.dataTransfer?.getData('application/x-drive-ids');
    if (!data) return;
    if (n.type !== 'folder') return;
    dropTarget.value = n.id;
    e.preventDefault();
}
const onItemDragLeave = (n: Node) => {
    if (dropTarget.value === n.id) dropTarget.value = null;
}
const onItemDrop = async(n: Node, e: DragEvent) => {
    const data = e.dataTransfer?.getData('application/x-drive-ids');
    if (!data) return;
    if (n.type !== 'folder') return;
    try {
        const ids: string[] = JSON.parse(data);
        if (!ids.length) return;
        working.value = true;
        await api.move(ids, n.id);
        await load(parentId.value);
        selected.value.clear();
        toast?.('移動しました');
    } catch (err: any) {
        alert(err?.response?.data?.message || '移動に失敗しました');
    } finally {
        working.value = false;
        dropTarget.value = null;
    }
}

const renameOne = async(menu_id?: string) => {
    const id = menu_id || [...selected.value][0];
    if (!id) {
        ping('名前を変更するファイルを選択してください')
        return;
    }

    const node = items.value.find(i => i.id === id);
    if (!node) return;

    const taken = new Set(items.value.filter(i => i.id !== id).map(i => i.name));

    const { input, decision } = await askInput(
        '新しい名前',
        {
            value: node.name,
            required: true,
            selectBaseName: true,
            submitText: 'リネーム',
            validate: v => {
                const name = v.trim();
                // if (taken.has(name)) return '同名が存在します。';
                if (ILLEGAL.test(name)) return '使用できない文字が含まれています。';
                return null;
            }
        },
        { answers: [{ value: true, label: 'OK' }, { value: false, label: 'キャンセル' }] }
    );

    if (!decision.value || !input || input === node.name) return;
    const payload = { name: input, project_id: projectId };
    working.value = true;
    await api.rename(id, payload);
    await load(parentId.value);
    toast?.('名前を変更しました');
    working.value = false;
}


const removeSelected = async(menu_id?: string) => {
    if (selected.value.size === 0 && !menu_id) {
        ping('削除するファイルを選択してください')
        return
    }
    const answer = await ask('選択項目を削除しますか？')
    if (!answer.value) return
    working.value = true
    if (menu_id) await api.remove(menu_id)
    else for (const id of selected.value) { await api.remove(id) }
    await load(parentId.value)
    working.value = false
}
const filePreview = useFilePreview()
const previewFile = (index) => {
    let file_list = filtered.value.filter(i => i.type === 'file')
    const files = file_list.map(fileData => ({
        ...fileData,
        file_path: `/cdn/${fileData.storage_path}`,
        doc_path: `/${fileData.storage_path}`,
        mime_type: fileData.mime?.split('/')[0],
        extension: fileData.ext,
    }));

    const data = {
        active: true,
        files,
        source: 'storage',
        index: index,
        message: null,
    }
    filePreview.setFilePreview(data)
}
const navigateTo = (id: string | null) => {
    const parentId = id ?? undefined
    if (parentId === (currentId.value ?? undefined)) return
    router.push({ name: 'file-storage', params: { parentId } })
}
const toBreadcrumb = (id: string | null) => { navigateTo(id) }
const currentId = computed(() => route.params.parentId as string | null)
const previewId = computed(() => route.query.preview as string | undefined)

// Move dialog state and actions
const moveDlg = ref<{ open: boolean, path: {id: string|null, name: string}[], folders: Node[], browsing: string|null, pendingIds: string[]|null }>({ open: false, path: [], folders: [], browsing: null, pendingIds: null })
const canMoveNode = (n: Node) => (isManager.value || n.owner_id == auth.activeUser.id)
const canMoveAllSelected = () => {
    const ids = Array.from(selected.value)
    if (ids.length === 0) return false
    const byId = new Map(items.value.map(i => [i.id, i]))
    return ids.every(id => {
        const node = byId.get(id)
        return node ? canMoveNode(node) : false
    })
}
const openMoveDialog = (menu_id?: string) => {
    const ids = menu_id ? [menu_id] : Array.from(selected.value)
    if (menu_id) {
        const n = items.value.find(i => i.id === menu_id)
        if (n && !canMoveNode(n)) { ping('移動権限がありません'); return }
    } else if (!canMoveAllSelected()) { ping('移動権限がありません'); return }
    if (!ids.length) {
        ping('移動するアイテムを選択してください')
        return
    }
    moveDlg.value.pendingIds = ids
    moveDlg.value.open = true
    moveBrowse(null)
}
const closeMoveDialog = () => { moveDlg.value.open = false; moveDlg.value.pendingIds = null }
const moveBrowse = async(pid: string|null) => {
    try {
        const data = await api.list(pid)
        moveDlg.value.browsing = pid
        moveDlg.value.path = data.path
        moveDlg.value.folders = (data.items as Node[]).filter(i => i.type === 'folder')
    } catch (e) {}
}
const confirmMove = async(dest: string|null) => {
    if (!moveDlg.value.pendingIds?.length) return
    try {
        working.value = true
        await api.move(moveDlg.value.pendingIds, dest)
        await load(parentId.value)
        selected.value.clear()
        closeMoveDialog()
        toast?.('移動しました')
    } catch (err: any) {
        alert(err?.response?.data?.message || '移動に失敗しました')
    } finally { working.value = false }
}
watch([currentId, previewId], async ([pid, fid]) => {
    if(pid === lastLoadedId.value) return         // avoid double loads
    await load(pid ?? null)                                 // load is driven by the route
    lastLoadedId.value = pid
     
    if (fid) {
        if (!props.fileAccess) return ping?.('ファイルアクセス権限がありません')
         await nextTick()
         const idx = fileIndexById.value.get(fid)
         if (idx != null) {
            previewFile(idx)
         } else {
            toast?.('ファイルが見つかりません')
            router.replace({ name: 'file-storage', params: { parentId: pid }, query: {} })
         }
    }
}, { immediate: true, flush: 'post' })
const openNode = async(n: Node) => {
    if (n.type === 'folder') navigateTo(n.id)
}

const safeName = (name: string) => {
    return name.replace(ILLEGAL, '_');
}
const pickZipName = (selectedItems: Array<{ name: string; type: string }>) => {
    if (selectedItems.length === 1 && selectedItems[0].type === 'folder') {
        return `${safeName(selectedItems[0].name)}.zip`;
    }
    return `selected-${selectedItems.length}-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '')}.zip`;
}
const filenameFromDisposition = (h?: string | null) => {
    if (!h) return null;
    // RFC 5987 / basic filename=
    const mStar = /filename\*=(?:UTF-8'')?([^;]+)/i.exec(h);
    if (mStar) return decodeURIComponent(mStar[1].replace(/^"+|"+$/g, ''));
    const m = /filename="?([^"]+)"?/i.exec(h);
    return m ? m[1] : null;
}
const downloadViaFetch = async(url: string, filename?: string, init?: RequestInit) => {
    const res = await fetch(url, { credentials: 'include', ...init });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const cd = res.headers.get('Content-Disposition');
    const name = filename || filenameFromDisposition(cd) || 'download';
    const blob = await res.blob();
    const blobUrl = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(blobUrl);
}
const downloadSelected = async() => {
    const ids = [...selected.value]; // Set<string>
    if (ids.length === 0) {
        // toast?.('何も選択されていません');
        return;
    }
    const chosen = items.value.filter(i => ids.includes(i.id)); // [{id,name,type}]
    working.value = true;
    try {
        if (ids.length === 1) {
            const n = chosen[0];
            if (n.type === 'file') {
                // single file → download as-is
                const res = await api.file(n.id);
                saveBlobResponse(res, n.name);
            } else {
                const res = await api.folderZip(n.id);
                saveBlobResponse(res, `${safeName(n.name)}.zip`);
            }
            return
        }

        // multiple selection → zip them all server-side
        const zipName = pickZipName(chosen);
        const res = await api.multiZip(ids);
        saveBlobResponse(res, zipName);
    } catch (e: any) {
        const msg = e?.message || e?.response?.data?.message || 'ダウンロードに失敗しました';
        alert(msg);
    } finally {
        working.value = false;
    }
}

const saveBlobResponse = (res: import('axios').AxiosResponse<Blob>, fallback: string) => {
    const cd = res.headers['content-disposition'];
    const name = filenameFromDisposition(cd) || fallback || 'download';
    const url = URL.createObjectURL(res.data);
    const a = document.createElement('a');
    a.href = url; a.download = name;
    document.body.appendChild(a); a.click(); a.remove();
    URL.revokeObjectURL(url);
}
const shareDlg = ref({
    open: false,
    node: null as Node | null,
    members: [] as User[],
    cascade: false,
    saving: false,
    owner: null as User | TaskUser | null,
    publicly: true,
})

const closeShare = () => {
    shareDlg.value.open = false
    shareDlg.value.node = null
    shareDlg.value.members = []
    shareDlg.value.cascade = false
    shareDlg.value.owner = null
    shareDlg.value.publicly = true
}

const setVisibilityImmediate = async(n: Node, pub: boolean) => {
    try {
        shareDlg.value.saving = true
        await sharingApi.update(n.id, { visibility: pub ? 'public' : 'private', members: [] })
        toast?.(`共有設定を${pub ? '公開' : '非公開'}にしました`)
    } catch (e: any) {
        alert(e?.response?.data?.message || '共有設定の変更に失敗しました')
    } finally {
        shareDlg.value.saving = false
    }
}

const handleShareClick = async(n: Node, pub: boolean) => {
    // if (pub) {
    //   return setVisibilityImmediate(n, true)
    // }


    const cur = await sharingApi.get(n.id)
    shareDlg.value.node = n

    const projectMembers = cur.members.length === 0 ? (props.selectedProject.members ?? []) : cur.members

    shareDlg.value.members = [...new Set(projectMembers)] as User[]
    shareDlg.value.cascade = false
    shareDlg.value.open = true
    shareDlg.value.publicly = n.visibility === 'public'
    shareDlg.value.owner = n.owner || null

}

const savePrivateSelection = async({ members, publicly }: { members: User[], publicly: boolean }) => {
    if (!shareDlg.value.node) return
    shareDlg.value.saving = true
    const userIds = members.map(m => m.id)
    const visibility = publicly ? 'public' : 'private'
    const node = shareDlg.value.node
    await sharingApi.update(node.id, { visibility, members: userIds })
    toast?.('共有設定を保存しました')
    closeShare()
    load(parentId.value)
    shareDlg.value.saving = false

}
const onDblClick = (n: Node) => {
    if (isFile(n)) {
        const idx = fileIndexById.value.get(n.id)
        if (idx != null) previewFile(idx)
        return
    }
    openNode(n)
}
const absoluteHref = (r:any) => {
    const href = router.resolve(r).href
    return new URL(href, window.location.origin).toString()
}
const copy = async(text: string ) => {
    try {
        await navigator.clipboard.writeText(text)
        toast?.('リンクをコピーしました')
    } catch {
        const ta = document.createElement('textarea')
        ta.value = text
        document.body.appendChild(ta)
        ta.select()
        document.execCommand('copy')
        ta.remove()
        toast?.('リンクをコピーしました')
    }
}
const copyFolderLink = (folderId?: string) => {
    const url = absoluteHref({
        name: 'file-storage',
        params: { parentId: folderId ?? undefined},
        query: {}
    })
    copy(url)
}
const copyFileLink = (n: Node) => {
    const url = absoluteHref({
        name: 'file-preview',
        params: { fileId: n.id },
    })
    copy(url)
}
const baseBtn = 'p-1 sm:p-1 transition-all duration-150'
const idleBtn = 'text-white/60 hover:text-white hover:bg-white/10'
const activeBtn = 'text-white bg-[var(--bg2)] ring-1 ring-white/20 shadow-sm'

const display = ref(0);
let timer: number | null = null;



watch(
  () => progress.value,
  (newVal, oldVal) => {
    if(oldVal == undefined) return
    // create progress display with increment animation according to progress.value and write it into display
    if (newVal > oldVal) {
      display.value = oldVal;
      if (timer) clearInterval(timer);
      timer = window.setInterval(() => {
        if (display.value < newVal) {
          display.value += 1;
        } else {
          if (timer) {
            clearInterval(timer);
            timer = null;
          }
        }
      }, 20);
    } else {
      display.value = newVal;
    }
  },
  { immediate: true }
);
// onMounted(() => { load(parentId.value) })
</script>
<style scoped>
/* keep it minimal; Tailwind handles 99% */
/* put this in a global css loaded after Tailwind */
@media (hover: none) {
  /* disable that specific utility on touch */
  .hover\:bg-\[var\(--bg3\)\]:hover { background-color: transparent !important; }
}


.truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
