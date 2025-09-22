<!-- DriveExplorer.vue -->
<script setup lang="ts">
import { ref, computed, onMounted, reactive, nextTick, useTemplateRef, watch } from 'vue'
import { useRoute } from 'vue-router';
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
type Node = {
  id: string
  type: 'file'|'folder'
  name: string
  size: number
  mime: string|null
  updated_at: string
  url?: string          // original image (or download)
  thumb_url?: string
  storage_path: string
  ext: string|null
  owner_id: number|null
  owner?: User|TaskUser|null
  visibility: 'private'|'public'
}
type ViewMode = 'grid' | 'list'

const tsApi = useApi()
const props = defineProps<{
  rootId?: string | null,
  selectedProject?: any | null,
  fileAccess?: boolean
}>()
// swap with your own api composable if needed
const api = {
  list: (parentId: string|null) => tsApi.get('/drive', { parent_id: parentId, project_id: projectId }),
  createFolder: (parentId: string|null, name: string) => tsApi.post('/drive/folders', { parent_id: parentId, name, project_id: projectId }),
  upload: (parentId: string|null, files: File[]) => {
    const fd = new FormData()
    fd.append('parent_id', parentId ?? '')
    fd.append('project_id', projectId ?? '')
    for (const f of files) fd.append('file[]', f)
    return tsApi.post('/drive/upload', fd, {}, { onUploadProgress: e => { progress.value = Math.round((e.loaded/e.total!)*100) } })
  },
  rename: (id: string, payload: {name: string, project_id: string | null}) => tsApi.patch(`/drive/${id}`, payload),
  remove: (id: string) => tsApi.del(`/drive/${id}`),
  // downloads:
  file: (id: string) =>
    axios.get(`/drive/files/${id}/download`, { responseType: 'blob', withCredentials: true }),

  folderZip: (id: string) =>
    axios.get(`/drive/folders/${id}/download.zip`, { responseType: 'blob', withCredentials: true }),

  multiZip: (ids: string[]) =>
    axios.post(`/drive/zip`, { ids, project_id: projectId }, { responseType: 'blob', withCredentials: true }),
  // sharing:
  setPublic: (params: { id: string, visibility: string, members: never[] }) => tsApi.put(`/drive/${params.id}/sharing`,  params)
}
const { ask, askInput, toast, ping } = useDialog()
const grid = useTemplateRef('grid');
const auth = useAuthUserStore();
const route = useRoute();
const parentId = ref<string|null>(props.rootId ?? null)
const path = ref<{id: string|null, name: string}[]>([])
const items = ref<Node[]>([])
const selected = ref<Set<string>>(new Set<string>());
const working = ref(false)
const progress = ref(0)
const q = ref('')
const projectId = route.params.projectId as string|null
const viewMode = ref<ViewMode>((localStorage.getItem('drive:viewMode') as ViewMode) || 'grid')
watch(viewMode, v => localStorage.setItem('drive:viewMode', v))
const isGrid = computed(() => viewMode.value === 'grid')
const isMobile = computed(() => window.matchMedia('(max-width: 768px)').matches)
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
function setView(mode: ViewMode) {
  if (viewMode.value !== mode) viewMode.value = mode
}
window.addEventListener('keydown', e => {
  if (e.key.toLowerCase() === 'v' && !e.metaKey && !e.ctrlKey && !e.altKey) {
    viewMode.value = isGrid.value ? 'list' : 'grid'
  }
})
function kb(size?: number) {
  if (!size && size !== 0) return ''
  return `${(size/1024).toFixed(1)} KB`
}
function onBackgroundClick (e) {
  const hitItem = e.target.closest('[data-item]');
  if (!hitItem) selected.value.clear();
}
function startMarquee (e) {
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
function onMove(e: MouseEvent) {
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
function applyMarqueeSelection() {
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
function getItemIdFromVNode(card) {
  const raw = card.getAttribute('data-id');
  return raw ? Number.isNaN(+raw) ? raw : +raw : null;
}
function normRect(m: any) {
  const pad = 1.5;
  const left = Math.min(m.x1, m.x2) - pad;
  const top = Math.min(m.y1, m.y2) - pad;
  const right = Math.max(m.x1, m.x2) + pad;
  const bottom = Math.max(m.y1, m.y2) + pad;
  return { left, top, right, bottom };
}

function intersects(a, b) {
  return !(b.left > a.right || b.right < a.left || b.top > a.bottom || b.bottom < a.top);
}
function onUp() {
  window.removeEventListener('mousemove', onMove);
  marquee.active = false;
}
async function load(id: string|null) {
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

function hitTest(a: {left:number; top:number; right:number; bottom:number},
                 b: {left:number; top:number; right:number; bottom:number}) {
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
function intersectionArea(a: any, b: any) {
  const w = Math.max(0, Math.min(a.right, b.right) - Math.max(a.left, b.left));
  const h = Math.max(0, Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top));
  return w * h;
}

function isFile(n: Node) {
  return n.type === 'file'
}
function isImage(n: Node) {
  return n.type === 'file' && !!n.mime && n.mime.startsWith('image/')
}
function fileMime(n: Node) {
  return n.mime?.split('/')[0] as string
}
const b64url = (s: string) => btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/,'')
const driveThumbUrl = (storage_path: string, size = 128, color = '222222') =>
  `/drive_thumbnail/${b64url(storage_path)}/${size}/${color.replace('#','')}`
function iconFor(n: Node) {
  if (n.type === 'folder') 
  return `<svg class="appIcon" width="32" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39 32">
            <path d="M38.918 8.499c-0.078-1.516-0.946-2.591-2.41-2.734-3.602-0.35-8.939-0.376-12.813-0.324-0.402 0-0.751-0.259-0.881-0.635-0.324-0.933-0.933-2.669-1.257-3.368-0.453-0.985-1.153-1.347-2.474-1.425-1.594-0.078-9.6 0.013-9.6 0.013h-5.377c-0.363 0.026-0.726 0.13-1.036 0.311s-0.596 0.428-0.803 0.725c-0.104 0.155-0.194 0.311-0.259 0.479l-0.052 0.13-0.013 0.104-0.039 0.117-1.244 3.628c-0.091 0.272-0.233 0.674-0.311 1.036-0.091 0.376-0.155 0.751-0.194 1.14-0.013 0.091-0.013 0.194-0.026 0.285v0.259l-0.026 0.492c-0.026 0.648-0.052 1.296-0.065 1.943-0.026 1.296-0.039 2.591-0.039 3.874 0.013 1.296 0.026 2.578 0.065 3.874 0.065 2.578 0.402 6.517 0.544 7.747s0.181 2.306 0.415 3.226c0.22 0.907 0.79 1.892 1.917 2.034 2.332 0.311 8.045 0.531 10.364 0.557 2.63 0.026 7.929 0.026 11.077-0.052 3.524-0.104 8.486-0.376 11.543-0.583 1.451-0.104 2.073-0.738 2.202-1.892 0.751-6.866 0.998-16.829 0.79-20.962zM22.892 5.441c-0.013 0.013 0 0 0 0zM19.291 29.474c-3.77 0.026-11.427-0.168-15.145-0.35-0.311-0.013-0.868-0.415-0.92-0.894-0.013-0.13-0.026-0.272-0.039-0.376l-0.155-1.879c-0.091-1.257-0.155-2.526-0.207-3.796s-0.078-2.539-0.104-3.809c-0.039-2.539-0.013-5.079 0.052-7.618 0.026-0.635 0.039-1.27 0.078-1.892l0.026-0.479 0.013-0.22c0-0.065 0.013-0.13 0.013-0.194 0.026-0.259 0.078-0.505 0.13-0.764 0.065-0.259 0.13-0.466 0.246-0.803l0.933-2.721 0.168-0.505c0.078-0.22 0.285-0.363 0.518-0.363l13.759 0.013c0.259 0 0.492 0.168 0.583 0.415l0.57 1.529 0.674 1.866c0.13 0.324 0.337 0.622 0.583 0.868s0.544 0.441 0.868 0.57c0.168 0.065 0.648 0.181 1.386 0.181 3.161-0.013 10.196 0.091 12.528 0.207 0.194 0.013 0.324 0.168 0.337 0.35 0.298 3.446 0.104 15.326-0.181 19.718-0.026 0.35-0.298 0.635-0.661 0.635-4.664 0.104-10.934 0.285-16.052 0.311z"></path>
          </svg>`
  if (n.mime?.startsWith('image/')) return '🖼️'
  if (n.mime?.startsWith('application')) return '📄'
  return '📦'
}

async function openNode(n: Node) {
  if (n.type === 'folder') await load(n.id)
}

function toggleSelect(n: Node, ev?: MouseEvent) {
  if (ev?.metaKey || ev?.ctrlKey) {
    if (selected.value.has(n.id)) selected.value.delete(n.id)
    else selected.value.add(n.id)
  } else {
    // single select
    selected.value.clear()
    selected.value.add(n.id)
  }
}

async function createFolder() {
  const taken = new Set(items.value.map(i => i.name));
  const suggested = suggestNext('新しいフォルダ', taken);

  const { input, decision } = await askInput(
    'フォルダ名を入力',
    {
      value: suggested,
      required: true,
      selectBaseName: true,
      submitText: '作成',
      validate: v => {
        const name = v.trim();
        if (taken.has(name)) return '同名のフォルダが存在します。';
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
    toast?.('フォルダを作成しました');
  } finally {
    working.value = false;
  }
}

const ILLEGAL = /[\\/:*?"<>|]/;

function suggestNext(base: string, taken: Set<string>) {
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
function onPickFiles(ev: Event) {
  const input = ev.target as HTMLInputElement
  if (!input.files?.length) return
  upload(Array.from(input.files))
  input.value = '' // reset
}

async function upload(files: File[]) {
  if (!files.length) return
  working.value = true
  progress.value = 0
  try {
    await api.upload(parentId.value, files)
    await load(parentId.value)
  } catch (e:any) {
    alert(e?.response?.data?.message || 'アップロードに失敗しました')
  } finally {
    working.value = false
    progress.value = 0
  }
}

// drag & drop
function onDrop(e: DragEvent) {
  e.preventDefault()
  const files = Array.from(e.dataTransfer?.files ?? [])
  upload(files)
}
function onDragOver(e: DragEvent) { e.preventDefault() }

async function renameOne(menu_id?: string) {
  const id = menu_id || [...selected.value][0];
  if (!id) 
  {
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
        if (taken.has(name)) return '同名が存在します。';
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


async function removeSelected(menu_id?: string) {
  if (selected.value.size === 0 && !menu_id){
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
function previewFile(index) {
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
function toBreadcrumb(id: string|null) { load(id) }

function safeName(name: string) {
  return name.replace(ILLEGAL, '_');
}
function pickZipName(selectedItems: Array<{name:string; type:string}>) {
  if (selectedItems.length === 1 && selectedItems[0].type === 'folder') {
    return `${safeName(selectedItems[0].name)}.zip`;
  }
  return `selected-${selectedItems.length}-${new Date().toISOString().slice(0,19).replace(/[:T]/g,'')}.zip`;
}
function filenameFromDisposition(h?: string | null) {
  if (!h) return null;
  // RFC 5987 / basic filename=
  const mStar = /filename\*=(?:UTF-8'')?([^;]+)/i.exec(h);
  if (mStar) return decodeURIComponent(mStar[1].replace(/^"+|"+$/g, ''));
  const m = /filename="?([^"]+)"?/i.exec(h);
  return m ? m[1] : null;
}
async function downloadViaFetch(url: string, filename?: string, init?: RequestInit) {
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
async function downloadSelected() {
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

function saveBlobResponse(res: import('axios').AxiosResponse<Blob>, fallback: string) {
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

function closeShare() {
  shareDlg.value.open = false
  shareDlg.value.node = null
  shareDlg.value.members = []
  shareDlg.value.cascade = false
  shareDlg.value.owner = null
  shareDlg.value.publicly = true
}

async function setVisibilityImmediate(n: Node, pub: boolean) {
  try {
    shareDlg.value.saving = true
    await sharingApi.update(n.id, {visibility: pub ? 'public' : 'private', members: []})
    toast?.(`共有設定を${pub ? '公開' : '非公開'}にしました`)
  } catch (e: any) {
    alert(e?.response?.data?.message || '共有設定の変更に失敗しました')
  } finally {
    shareDlg.value.saving = false
  }
}

async function handleShareClick(n: Node, pub: boolean) {
  // if (pub) {
  //   return setVisibilityImmediate(n, true)
  // }

  
  const cur = await sharingApi.get(n.id)
  shareDlg.value.node = n

  const projectMembers = cur.visibility === 'private' ? cur.members : (props.selectedProject.members ?? [])

  shareDlg.value.members = [...new Set(projectMembers)] as User[]
  shareDlg.value.cascade = false
  shareDlg.value.open = true
  shareDlg.value.publicly = n.visibility === 'public'
  shareDlg.value.owner = n.owner || null
  
}

async function savePrivateSelection({ members, publicly}: {members: User[], publicly: boolean}) {
  if (!shareDlg.value.node) return
    shareDlg.value.saving = true
    const userIds = members.map(m => m.id)
    const visibility = publicly ? 'public' : 'private'
    const node = shareDlg.value.node
    await sharingApi.update(node.id, {visibility, members: userIds})
    toast?.('共有設定を保存しました')
    closeShare()
    load(parentId.value)
    shareDlg.value.saving = false
  
}
function onDblClick(n: Node) {
  if (isFile(n)) {
    const idx = fileIndexById.value.get(n.id)
    if (idx != null) previewFile(idx)
    return
  }
  openNode(n)
}
const baseBtn   = 'p-2 sm:p-1.5 transition-all duration-150'
const idleBtn   = 'text-white/60 hover:text-white hover:bg-white/10'
const activeBtn = 'text-white bg-[var(--bg2)] ring-1 ring-white/20 shadow-sm'
onMounted(() => { load(parentId.value) })
</script>

<template>
  <div v-if="fileAccess" class="flex flex-col h-full bg-[var(--background-color)] p-4" @drop="onDrop" @dragover="onDragOver">
    <!-- Toolbar -->
    <div v-if="isMobile" class="flex items-center flex-wrap text-xs justify-between mb-2">
      <div class="w-full mb-3">
        <PostSearchBar customPlaceHolder="ファイル検索" className="newChatMemberSearch" @search-start="(word) => {q = word}"/>
      </div>
      <label class="px-3 h-9 cursor-pointer bg-[var(--bg3)] flex items-center">
        <span>アップロード</span>
        <input type="file" class="hidden" multiple @change="onPickFiles" />
      </label>
      <ItemMenu 
        v-if="isMobile"
        :items="[
              {title: '新規フォルダ', action: () => {createFolder()}},
              {title: 'ダウンロード', action: () => {downloadSelected()}},
              ...(isManager ? [{title: '名前変更', action: () => {renameOne()}}] : []),
              ...(isManager ? [{title: '削除', action: () => {removeSelected()}}] : []),
          ]"
      />
    </div>
    <div v-else class="flex items-center flex-wrap gap-2 p-2 border-b backdrop-blur text-xs">
      <button class="px-3 h-9 hover:bg-[var(--calendarBorder)] bg-[var(--bg3)]" @click="createFolder" :disabled="working">新規フォルダ</button>
      <label class="px-3 h-9 hover:bg-[var(--calendarBorder)] cursor-pointer bg-[var(--bg3)] flex items-center">
        <span>アップロード</span>
        <input type="file" class="hidden" multiple @change="onPickFiles" />
      </label>
      <button class="px-3 h-9 hover:bg-[var(--calendarBorder)] bg-[var(--bg3)]" @click="downloadSelected" :disabled="selected.size===0 || working">ダウンロード</button>
      <button class="px-3 h-9 hover:bg-[var(--calendarBorder)] bg-[var(--bg3)]" v-if="isManager" @click="renameOne()" :disabled="selected.size!==1 || working">名前変更</button>
      <button class="px-3 h-9 hover:bg-[var(--calendarBorder)] bg-[var(--bg3)] text-[tomato]" v-if="isManager" @click="removeSelected()" :disabled="selected.size===0 || working">削除</button>
      <div class="md:ml-auto">
        <PostSearchBar customPlaceHolder="ファイル検索" className="newChatMemberSearch" @search-start="(word) => {q = word}"/>
      </div>
    </div>

    <!-- Progress -->
    <div v-if="progress>0 && working" class="px-3 py-1 text-sm text-gray-600">アップロード中… {{ progress }}%</div>
      <div class="flex items-center justify-between px-3 py-2">
        <div class=" text-sm text-gray-600 flex flex-wrap gap-1">
          <template v-for="(p, i) in path" :key="p.id ?? 'root'">
            <button class="hover:underline text-sm" @click="toBreadcrumb(p.id)">{{ p.name }}</button>
            <span v-if="i < path.length-1">/</span>
          </template>
        </div>
        <div
            class="inline-flex items-center gap-1 rounded-xl border border-white/10 bg-[var(--bg2)]/60 backdrop-blur supports-[backdrop-filter]:bg-[var(--bg2)]/40 p-1"
            role="group"
            aria-label="View mode toggle"
            v-if="!isMobile"
          >
            <!-- GRID -->
            <button
              :aria-pressed="isGrid"
              :class="[baseBtn, isGrid ? activeBtn : idleBtn]"
              class="h-6 w-6 flex items-center justify-center"
              title="グリッド表示 (V)"
              @click="setView('grid')"
            >
              <Grid class="w-4 h-4" />
            </button>

            <!-- LIST -->
            <button
              :aria-pressed="!isGrid"
              :class="[baseBtn, !isGrid ? activeBtn : idleBtn]"
              class="h-6 w-6 flex items-center justify-center"
              title="リスト表示 (V)"
              @click="setView('list')"
            >
              <List class="w-4 h-4" />
            </button>
          </div>
    </div>
    <!-- Breadcrumb -->
    

    <!-- Drop zone hint -->
    <div class="hidden md:flex mx-3 mb-2 p-2 rounded-lg border border-dashed text-xs text-gray-500">
      ここにファイルをドラッグ＆ドロップしてアップロード
    </div>
    
    <!-- Grid/List -->
    <div class="relative h-full" ref="grid" @mousedown.left="startMarquee" @click="onBackgroundClick">
      <div v-if="isGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 p-3">
        <div v-for="n in filtered" :key="n.id"
            class="group p-3 hover:bg-[var(--calendarBorder)] bg-[var(--bg3)] cursor-pointer select-none relative"
            :class="{'ring-2 ring-blue-400': selected.has(n.id)}"
            :data-item="true"
            :data-id="n.id"
            @click.stop="toggleSelect(n, $event)"
            @dblclick="onDblClick(n)">
          <div class="flex items-center gap-3">
            <img v-if="isImage(n)"
              :src="driveThumbUrl(n.storage_path, 32, '222222')"
              class="object-cover"
              loading="lazy"
              decoding="async"
              />
            <!-- Else, fallback icon -->
            <FileIcon v-else-if="isFile(n) && n.mime?.startsWith('application')" :ext="n.ext"/>
            <span v-else class="text-2xl" v-html="iconFor(n)"></span>
            
            <div class="min-w-0 leading-tight">
              <div class="truncate-2 font-medium text-sm">{{ n.name }}</div>
              <div class="text-xs text-gray-600">
                <span v-if="n.type==='file'">{{ (n.size/1024).toFixed(1) }} KB</span>
                <span v-else>フォルダ</span> ・ {{ new Date(n.updated_at).toLocaleString() }}
              </div>
            </div>
          </div>
          <div class="absolute right-[10px] top-[10px]">
                <ItemMenu :items="[
                    {title: '開く', action: () => {onDblClick(n)}},
                    {title: 'ダウンロード', action: () => {selected.clear(); selected.add(n.id); downloadSelected()}},
                    ...(isManager || n.owner_id == auth.activeUser.id ? [{title: 'アクセス権限', action: () => handleShareClick(n, true)}] : []),
                    ...(isManager || n.owner_id == auth.activeUser.id ? [{title: '名前変更', action: () => {renameOne(n.id)}}] : []),
                    ...(isManager || n.owner_id == auth.activeUser.id ? [{title: '削除', action: () => {removeSelected(n.id)}}] : []),
                ]"/>
            </div>
        </div>
      </div>
      <div v-else class="p-3">
      <!-- header -->
      <div class="grid grid-cols-[auto_1fr_auto_auto_auto] items-center gap-3 px-3 py-2 text-xs uppercase opacity-60">
        <div>種類</div>
        <div>名前</div>
        <div class="text-right">サイズ</div>
        <div class="text-right">更新日</div>
        <div><ItemMenu :items="[]"/></div>
      </div>

      <div>
        <div v-for="n in filtered" :key="n.id"
            class="relative grid grid-cols-[auto_1fr_auto_auto_auto] items-center gap-3 px-3 py-3 hover:bg-[var(--calendarBorder)] bg-[var(--bg3)] cursor-pointer select-none"
            :class="{'bg-[var(--calendarBorder)]': selected.has(n.id)}"
            :data-item="true"
            :data-id="n.id"
            @click.stop="toggleSelect(n, $event)"
            @dblclick="onDblClick(n)">
          <!-- icon/preview -->
          <div class="w-8 h-8 flex items-center justify-center">
            <img v-if="isImage(n)" :src="driveThumbUrl(n.storage_path, 32, '222222')" class="w-8 h-8 object-cover rounded" loading="lazy" />
            <FileIcon v-else-if="isFile(n) && n.mime?.startsWith('application')" :ext="n.ext" />
            <span v-else class="text-xl" v-html="iconFor(n)"></span>
          </div>

          <!-- name -->
          <div class="min-w-0">
            <div class="truncate text-sm font-medium">{{ n.name }}</div>
            <div class="text-xs opacity-60" v-if="n.type==='folder'">フォルダ</div>
          </div>

          <!-- size -->
          <div class="text-right text-sm tabular-nums">
            <span v-if="n.type==='file'">{{ kb(n.size) }}</span>
          </div>

          <!-- updated -->
          <div class="text-right text-xs opacity-80">
            {{ new Date(n.updated_at).toLocaleString() }}
            
          </div>

          <!-- row menu -->
          <div class="">
            <ItemMenu :items="[
              {title: '開く', action: () => {onDblClick(n)}},
              {title: 'ダウンロード', action: () => {selected.clear(); selected.add(n.id); downloadSelected()}},
              ...(isManager || n.owner_id == auth.activeUser.id ? [{title: 'アクセス権限', action: () => {handleShareClick(n, true)}}] : []),
              ...(isManager || n.owner_id == auth.activeUser.id ? [{title: '名前変更', action: () => {renameOne(n.id)}}] : []),
              ...(isManager || n.owner_id == auth.activeUser.id ? [{title: '削除', action: () => {removeSelected(n.id)}}] : []),
            ]"/>
          </div>
        </div>
      </div>
    </div>

      <div v-if="marquee.active" class="pointer-events-none absolute border-2 border-blue-400/70 bg-blue-400/10" :style="marqueeStyle">
      </div>
      <div v-if="working && items.length===0" class="p-6 text-center text-gray-500">読み込み中…</div>
      <div v-else-if="!working && items.length===0" class="p-6 text-center text-gray-400">ここは空です</div>
    </div>
    <PrivateSetting 
      v-if="shareDlg.open"
      :node="shareDlg.node!"
      v-model:members="shareDlg.members"
      v-model:publicly="shareDlg.publicly"
      :selectableUsers="selectableUsers"
      :saving="shareDlg.saving"
      :owner="shareDlg.owner"
      @close="closeShare"
      @save="savePrivateSelection" 
    />

    
  </div>
  <div v-else class="bg-[var(--background-color)] h-full text-center justify-center flex items-center flex-col">
      <p>アクセス権限ありません。</p>
      <router-link class="l-button" style="margin: 30px 0 70px 0;" :to="{name : 'overview'}">概要へ戻る</router-link>
  </div>
</template>

<style scoped>
/* keep it minimal; Tailwind handles 99% */
@media (hover: none){
  .hover\:bg-white\/5:hover{ background: transparent !important; }
}
.truncate-2{
  display:-webkit-box;
  -webkit-line-clamp:2;
  line-clamp: 2;
  -webkit-box-orient:vertical;
  overflow:hidden;
}
</style>
