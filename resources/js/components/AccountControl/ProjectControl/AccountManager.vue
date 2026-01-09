<template>
  <div>
    <div class="flex flex-col h-[calc(100vh-155px)] max-h-screen overflow-hidden p-4 space-y-6">
      <div class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-2">
          <label class="text-xs text-[var(--primary-color)]">プロジェクト</label>
          <select v-model="selectedProjectId" class="px-2 py-1 border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] text-[var(--primary-color)]" @change="load">
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }} ({{ p.is_new ? '新規' : '既存' }})</option>
          </select>
        </div>
        <button class="px-3 py-1 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] text-xs" @click="load">
          再読込
        </button>
        <button
          class="px-3 py-1 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] text-xs"
          @click="syncTemplate"
        >
          テンプレ再同期
        </button>
      </div>

      <div class="flex-1 min-h-0 border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] overflow-auto">
        <table class="w-full text-sm text-[var(--primary-color)]">
          <thead class="bg-[var(--background-color)] sticky top-0 z-10 border-b [border-bottom-style:solid] border-[var(--normalBorder)]">
            <tr>
              <th class="text-left px-3 py-2 w-24">コード</th>
              <th class="text-left px-3 py-2">名称</th>
            <th class="text-left px-3 py-2">深さ</th>
            <th class="text-left px-3 py-2">Path</th>
            <th class="text-left px-3 py-2">入力可</th>
            <th class="text-left px-3 py-2">計算</th>
            <th class="text-left px-3 py-2">有効</th>
            <th class="text-left px-3 py-2">並び順</th>
            <th class="text-left px-3 py-2">操作</th>
          </tr>
        </thead>
          <tbody v-if="accounts.length">
            <tr v-for="a in accounts" :key="a.id" class="border-b [border-bottom-style:solid] border-[var(--normalBorder)]">
              <td class="px-3 py-2">{{ a.code }}</td>
              <td class="px-3 py-2">{{ indent(a.depth) }}{{ a.name }}</td>
              <td class="px-3 py-2">{{ a.depth }}</td>
              <td class="px-3 py-2 font-mono text-xs">{{ a.path }}</td>
              <td class="px-3 py-2">
                <input type="checkbox" :checked="a.is_postable" @change="togglePostable(a)" />
              </td>
              <td class="px-3 py-2">
                <input type="checkbox" :checked="a.is_formula" @change="toggleFormula(a)" />
              </td>
              <td class="px-3 py-2">
                <input type="checkbox" :checked="a.is_active" @change="toggleActive(a)" />
              </td>
              <td class="px-3 py-2">{{ a.sort_order }}</td>
              <td class="px-3 py-2">
                <button class="text-xs px-2 py-1 border border-solid border-[var(--normalBorder)] mr-1" @click="promptRename(a)">名称変更</button>
                <button v-if="a.is_formula" class="text-xs px-2 py-1 border border-solid border-[var(--normalBorder)] mr-1" @click="promptFormula(a)">式編集</button>
                <button class="text-xs px-2 py-1 border border-solid border-[var(--normalBorder)] text-red-500" @click="deleteAccount(a)">削除</button>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr>
              <td
                colspan="9"
                class="py-16 text-center text-sm text-[color:var(--muted-text,#9ca3af)]"
              >
                科目が登録されていません。左上のプロジェクトを選択するか、科目を追加してください。
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <FloatButton title="科目追加" @action="openModal = true">
        <template #icon>
          <AddIcon size="15" fill="black"/>
        </template>
      </FloatButton>

      
    </div>
    <Modal v-if="openModal" @close="openModal = false">
        <template #title>
          <div class="text-[var(--primary-color)] font-semibold">新規科目</div>
        </template>
        <template #content>
          <div class="space-y-4 text-[var(--primary-color)]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-xs text-[var(--primary-color)]">親科目</label>
              <select v-model="form.parent_id" class="w-full px-2 py-1 border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] text-[var(--primary-color)]">
                <option :value="null">ルート</option>
                <option v-for="a in parentOptions" :key="a.id" :value="a.id">
                  {{ indent(a.depth) }}{{ a.code }} {{ a.name }}
                </option>
              </select>
            </div>
              <div>
                <label class="text-xs text-[var(--primary-color)]">コード</label>
                <input v-model="form.code" class="w-full px-2 py-1 border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] text-[var(--primary-color)]" />
              </div>
              <div>
                <label class="text-xs text-[var(--primary-color)]">名称</label>
                <input v-model="form.name" class="w-full px-2 py-1 border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] text-[var(--primary-color)]" />
              </div>
              <div class="flex items-center gap-2">
                <input id="postable" type="checkbox" v-model="form.is_postable" :disabled="form.is_formula" class="w-4 h-4" />
                <label for="postable" class="text-xs text-[var(--primary-color)]">入力可能（Postable）</label>
              </div>
              <div class="flex items-center gap-2">
                <input id="formula" type="checkbox" v-model="form.is_formula" class="w-4 h-4" />
                <label for="formula" class="text-xs text-[var(--primary-color)]">計算科目（式）</label>
              </div>
            </div>
            <div v-if="form.is_formula" class="space-y-2">
              <label class="text-xs text-[var(--primary-color)]">式（例: [7010]+[7020]、セクション合計は [6000/*]）</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="a in accounts"
                  :key="'ins-'+a.id"
                  class="px-2 py-1 border border-solid border-[var(--normalBorder)] text-xs"
                  @click.prevent="appendCode(a.code)"
                >
                  [{{ a.code }}]
                </button>
                <button class="px-2 py-1 border border-solid border-[var(--normalBorder)] text-xs" @click.prevent="appendOp('+')">+</button>
                <button class="px-2 py-1 border border-solid border-[var(--normalBorder)] text-xs" @click.prevent="appendOp('-')">-</button>
                <button class="px-2 py-1 border border-solid border-[var(--normalBorder)] text-xs" @click.prevent="appendOp('*')">×</button>
                <button class="px-2 py-1 border border-solid border-[var(--normalBorder)] text-xs" @click.prevent="appendOp('/')">÷</button>
                <button class="px-2 py-1 border border-solid border-[var(--normalBorder)] text-xs" @click.prevent="appendOp('(')">(</button>
                <button class="px-2 py-1 border border-solid border-[var(--normalBorder)] text-xs" @click.prevent="appendOp(')')">)</button>
              </div>
              <textarea v-model="form.formula" rows="3" class="w-full px-2 py-1 border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] text-[var(--primary-color)]"></textarea>
              <p class="text-[11px] text-[var(--primary-color)] opacity-80 mt-1">※ [CODE] または [CODE/*] を使用。存在しないコードは登録できません。</p>
            </div>
            <div class="flex justify-end gap-2">
              <button class="px-3 py-2 border border-solid border-[var(--normalBorder)]" @click="openModal = false">キャンセル</button>
              <button class="px-3 py-2 border border-solid border-[var(--normalBorder)] bg-[var(--bg3)]" @click="create">保存</button>
            </div>
          </div>
        </template>
      </Modal>
  </div>
  
</template>

<script setup lang="ts">
import { useApi } from '@/composables/api'
import { reactive, ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import FloatButton from '@/components/Global/FloatButton.vue'
import Modal from '@/components/Global/Modal.vue'
import { useDialog } from '@/composables/dialog'
import AddIcon from '@/components/Form/AddIcon.vue'

type Account = {
  id: number
  code: string
  name: string
  path: string
  depth: number
  is_postable: boolean
  is_formula: boolean
  is_active: boolean
  parent_id: number | null
  sort_order: number
  formula?: string | null
}

type ProjectItem = { id: number; name: string, is_new: number }

const props = defineProps<{
  projectId?: number
}>()

const api = useApi()
const route = useRoute()
const { toast, askInput, ask } = useDialog()
const openModal = ref(false)
const projects = ref<ProjectItem[]>([])
const selectedProjectId = ref<number | null>(null)
const accounts = ref<Account[]>([])
const parentOptions = computed(() => {
  // Sort by path to keep parent/child grouping (e.g., /4000/ before /4000/4010/)
  return [...accounts.value].sort((a, b) => a.path.localeCompare(b.path))
})
const form = reactive({
  parent_id: null as number | null,
  code: '',
  name: '',
  is_postable: true,
  is_formula: false,
  formula: '',
})

const currentProjectId = () => {
  if (selectedProjectId.value) return selectedProjectId.value
  if (props.projectId) return Number(props.projectId)
  if (route.params.projectId) return Number(route.params.projectId)
  return null
}

const loadProjects = async () => {
  // Uses existing projects endpoint; adjust if you have a dedicated admin list
  const data = await api.get('/get_projects')
  const rows = Array.isArray(data) ? data : []
  projects.value = rows.map((p: any) => ({ id: p.id, name: p.name, is_new: p.is_new }))
  if (!selectedProjectId.value && projects.value.length) {
    selectedProjectId.value = projects.value[0].id
  }
}

const load = async () => {
  const projectId = currentProjectId()
  if (!projectId) return
  const data = await api.get(`/projects/${projectId}/accounts`)
  accounts.value = data || []
  if (accounts.value.length === 0) {
    // keep root as selectable
    form.parent_id = null
  }
}

const syncTemplate = async () => {
  const projectId = currentProjectId()
  if (!projectId) return
  const ok = await ask('CoATemplates を再同期します。未登録の科目のみ追加します。よろしいですか？', {
    answers: [
      { value: true, label: '実行' },
      { value: false, label: 'キャンセル' },
    ],
  })
  if (!ok.value) return
  const res = await api.post(`/projects/${projectId}/accounts/sync-template`)
  toast(`テンプレ再同期: ${res?.added ?? 0}件追加`)
  await load()
}

const appendCode = (code: string) => {
  form.formula = `${form.formula || ''}[${code}]`
}
const appendOp = (op: string) => {
  form.formula = `${form.formula || ''}${op}`
}

const validateFormula = (formula: string): { ok: boolean; unknown: string[] } => {
  if (!formula) return { ok: false, unknown: [] }
  const codeRe = /\[([0-9]{4})(\/\*)?\]/g
  const unknown: string[] = []
  let m
  const codeSet = new Set(accounts.value.map(a => a.code))
  while ((m = codeRe.exec(formula)) !== null) {
    const c = m[1]
    if (!codeSet.has(c)) unknown.push(c)
  }
  return { ok: unknown.length === 0, unknown }
}

const create = async () => {
  const projectId = currentProjectId()
  if (!projectId) return
  if (!form.code.trim() || !form.name.trim()) return
  if (form.is_formula) {
    const check = validateFormula(form.formula || '')
    if (!check.ok) {
      toast(`不明なコードがあります: ${check.unknown.join(', ')}`)
      return
    }
  }
  await api.post(`/projects/${projectId}/accounts`, {
    parent_id: form.parent_id,
    code: form.code.trim(),
    name: form.name.trim(),
    is_postable: !!form.is_postable,
    is_formula: !!form.is_formula,
    formula: form.is_formula ? form.formula : null,
  })
  form.code = ''
  form.name = ''
  form.is_postable = true
  form.is_formula = false
  form.formula = ''
  openModal.value = false
  toast('科目を追加しました')
  await load()
}

const toggleActive = async (acct: Account) => {
  const projectId = currentProjectId()
  if (!projectId) return
  await api.put(`/projects/${projectId}/accounts/${acct.id}`, {
    is_active: !acct.is_active,
  })
  await load()
}

const togglePostable = async (acct: Account) => {
  const projectId = currentProjectId()
  if (!projectId) return
  await api.put(`/projects/${projectId}/accounts/${acct.id}`, {
    is_postable: !acct.is_postable,
  })
  await load()
}

const toggleFormula = async (acct: Account) => {
  const projectId = currentProjectId()
  if (!projectId) return
  await api.put(`/projects/${projectId}/accounts/${acct.id}`, {
    is_formula: !acct.is_formula,
    is_postable: acct.is_formula, // disable postable when turning on
  })
  await load()
}

const promptRename = async (acct: Account) => {
  const projectId = currentProjectId()
  if (!projectId) return
  const { input, decision } = await askInput('名称変更', {
    label: '新しい名称',
    required: true,
    placeholder: acct.name,
    value: acct.name,
  })
  if (!decision.value || !input || input.trim() === acct.name) return
  await api.put(`/projects/${projectId}/accounts/${acct.id}`, { name: input.trim() })
  toast('名称を更新しました')
  await load()
}

const promptFormula = async (acct: Account) => {
  const projectId = currentProjectId()
  if (!projectId) return
  const { input, decision } = await askInput('式編集', {
    label: '式（例: [7010]+[7020]、セクションは [6000/*]）',
    required: true,
    placeholder: '[7010]+[7020]',
    value: acct.formula || '',
  })
  if (!decision.value || input === null) return
  const formula = input.trim()
  const check = validateFormula(formula)
  if (!check.ok) {
    toast(`不明なコードがあります: ${check.unknown.join(', ')}`)
    return
  }
  await api.put(`/projects/${projectId}/accounts/${acct.id}`, {
    is_formula: true,
    is_postable: false,
    formula: formula.trim(),
  })
  toast('式を更新しました')
  await load()
}

const deleteAccount = async (acct: Account) => {
  const projectId = currentProjectId()
  if (!projectId) return
  if (!window.confirm(`「${acct.code} ${acct.name}」を削除しますか？関連する金額も削除されます。`)) return
  await api.del(`/projects/${projectId}/accounts/${acct.id}`)
  await load()
}

const indent = (depth: number) => '— '.repeat(Math.max(0, depth))

onMounted(async () => {
  await loadProjects()
  await load()
})
</script>
