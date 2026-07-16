<template>
    <div class="flex flex-col gap-[14px] max-w-[900px]">
        <p class="text-[12px] text-gray-500">
            条件に一致するレコードに対して、閲覧・編集・削除できる対象を指定します。上のセットから順に判定され、最初に一致したセットが適用されます。どのセットにも一致しないレコードはアプリ権限に従います。
        </p>

        <div v-for="(set, si) in def.recordPermissions" :key="si" class="flow-card">
            <div class="set-head">
                <span class="font-medium text-[13px]">セット {{ si + 1 }}</span>
                <div class="flow-seg ml-auto" title="複数条件のとき、すべての条件を満たす(AND)か、いずれかを満たす(OR)か">
                    <button :class="{ on: set.match_mode === 'all' }" @click="set.match_mode = 'all'">AND</button>
                    <button :class="{ on: set.match_mode === 'any' }" @click="set.match_mode = 'any'">OR</button>
                </div>
                <button class="rm" @click="def.recordPermissions.splice(si, 1)" title="セット削除"><CloseIcon size="10" /></button>
            </div>

            <div class="sub-sec">対象レコードの条件</div>
            <div v-for="(c, ci) in set.conditions" :key="ci" class="cond-row">
                <select v-model="c.source" class="custom-a-input !box-border cond-src" @change="c.values = []">
                    <option value="creator">作成者</option>
                    <option value="updater">更新者</option>
                    <option value="status">ステータス</option>
                    <option value="field">フィールド</option>
                </select>
                <select v-if="c.source === 'field'" v-model="c.field_id" class="custom-a-input !box-border" @change="c.values = []">
                    <option :value="null" disabled>項目</option>
                    <option v-for="f in conditionFields" :key="f.id" :value="f.id">{{ f.label }}</option>
                </select>
                <select v-model="c.operator" class="custom-a-input !box-border cond-op">
                    <option value="includes_any">いずれかを含む</option>
                    <option value="includes_all">すべて含む</option>
                    <option value="equals">等しい</option>
                    <option value="is_empty">空</option>
                    <option value="not_empty">空でない</option>
                </select>
                <template v-if="!['is_empty', 'not_empty'].includes(c.operator)">
                    <div v-if="valueMode(c) !== 'text'" class="rec-sel cond-val">
                        <ItemSelector :multiple="true" :options="candidates(c)" :reduce="(o: any) => o.value" label="label" v-model="c.values" :clearable="true" :close-on-select="false" place-holder="値を選択" />
                    </div>
                    <input v-else type="text" :value="(c.values || []).join(',')" @input="c.values = splitCsv(($event.target as HTMLInputElement).value)" class="custom-a-input !box-border cond-val" placeholder="値（カンマ区切り）">
                </template>
                <button class="rm" @click="set.conditions.splice(ci, 1)"><CloseIcon size="9" /></button>
            </div>
            <button class="flow-ghost-btn mt-[6px]" @click="set.conditions.push({ source: 'field', field_id: null, operator: 'includes_any', values: [] })">＋ 条件</button>

            <div class="sub-sec mt-[12px]">権限を付与する対象</div>
            <div v-for="(g, gi) in set.grants" :key="gi" class="grant-row">
                <span class="subj">{{ grantLabel(g) }}</span>
                <span v-for="p in PERMS" :key="p.k" class="flow-perm-flag" @click="(g as any)[p.k] = !(g as any)[p.k]">
                    <span class="flow-cbox" :class="{ on: (g as any)[p.k] }">
                        <svg v-if="(g as any)[p.k]" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,12 10,17 19,7"></polyline></svg>
                    </span>{{ p.l }}
                </span>
                <button class="rm" @click="set.grants.splice(gi, 1)"><CloseIcon size="9" /></button>
            </div>
            <div class="add-panel">
                <div class="addbar">
                    <div class="flow-seg">
                        <button :class="{ on: gType(si) === 'user' }" @click="grantType[si] = 'user'">ユーザー</button>
                        <button :class="{ on: gType(si) === 'position' }" @click="grantType[si] = 'position'">役職</button>
                        <button :class="{ on: gType(si) === 'everyone' }" @click="grantType[si] = 'everyone'">全員</button>
                        <button :class="{ on: gType(si) === 'field' }" @click="grantType[si] = 'field'">項目の担当</button>
                    </div>
                    <div class="flow-seg" v-if="def.project_record_id">
                        <button :class="{ on: gType(si) === 'project_member' }" @click="grantType[si] = 'project_member'">メンバー</button>
                        <button :class="{ on: gType(si) === 'project_manager' }" @click="grantType[si] = 'project_manager'">PM</button>
                        <button :class="{ on: gType(si) === 'project_director' }" @click="grantType[si] = 'project_director'">ディレクター</button>
                    </div>
                </div>

                <div v-if="gType(si) === 'user'" class="rec-sel mt-[8px]">
                    <MemberSelector :multiple="true" :options="(users as any)" v-model="grantUserPicks[si]" compact place-holder="ユーザーを検索（複数可）" />
                </div>
                <div v-else-if="gType(si) === 'position'" class="rec-sel mt-[8px]">
                    <ItemSelector :multiple="true" :options="positions" :reduce="(o: any) => o.id" label="name" v-model="grantPosPicks[si]" :clearable="true" :close-on-select="false" place-holder="役職を検索（複数可）" />
                </div>
                <div v-else-if="gType(si) === 'field'" class="mt-[8px]">
                    <select v-model="grantTarget[si]" class="custom-a-input !w-[200px] !box-border">
                        <option :value="null" disabled>ユーザー項目</option>
                        <option v-for="f in userFields" :key="f.id" :value="f.id">{{ f.label }}</option>
                    </select>
                </div>

                <div class="flags-row mt-[8px]">
                    <div class="flow-perm-flags">
                        <span v-for="p in PERMS" :key="p.k" class="flow-perm-flag" @click="(flagsFor(si) as any)[p.k] = !(flagsFor(si) as any)[p.k]">
                            <span class="flow-cbox" :class="{ on: (flagsFor(si) as any)[p.k] }">
                                <svg v-if="(flagsFor(si) as any)[p.k]" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,12 10,17 19,7"></polyline></svg>
                            </span>{{ p.l }}
                        </span>
                    </div>
                    <button class="flow-ghost-btn ml-auto" :disabled="grantAddDisabled(si)" @click="addGrant(si)">＋ 追加</button>
                </div>
            </div>
        </div>

        <button class="flow-ghost-btn w-fit" @click="addSet">＋ 権限セットを追加</button>
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { computed, reactive } from 'vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'
import ItemSelector from '@/components/Form/ItemSelector.vue'
import { subjectLabelFor } from '@/utils/flowSubject'
import type { BuilderDefinition, RecordPermConditionRow, RecordPermGrantRow, FlowOptionUser, FlowOptionPosition } from '@/types/flow'

const props = defineProps<{ def: BuilderDefinition; users: FlowOptionUser[]; positions: FlowOptionPosition[] }>()

const conditionFields = computed(() => props.def.fields.filter((f) => f.input_type !== 'heading' && f.input_type !== 'formula'))
const userFields = computed(() => props.def.fields.filter((f) => f.input_type === 'user' || f.input_type === 'member'))

const PERMS = [
    { k: 'can_view', l: '閲覧' },
    { k: 'can_edit', l: '編集' },
    { k: 'can_delete', l: '削除' },
] as const

const grantType = reactive<Record<number, 'user' | 'position' | 'everyone' | 'field' | 'project_member' | 'project_manager' | 'project_director'>>({})
const NO_TARGET = ['everyone', 'project_member', 'project_manager', 'project_director']
const grantTarget = reactive<Record<number, number | null>>({})
const gType = (si: number) => grantType[si] ?? 'user'

// Per-set multi-select picks + a shared view/edit/delete template (bulk-add, like app-level).
const grantUserPicks = reactive<Record<number, any[]>>({})
const grantPosPicks = reactive<Record<number, number[]>>({})
const grantFlags = reactive<Record<number, { can_view: boolean; can_edit: boolean; can_delete: boolean }>>({})
const flagsFor = (si: number) => {
    if (!grantFlags[si]) grantFlags[si] = { can_view: true, can_edit: false, can_delete: false }
    return grantFlags[si]
}
const grantAddDisabled = (si: number) => {
    const t = gType(si)
    if (t === 'user') return (grantUserPicks[si]?.length ?? 0) === 0
    if (t === 'position') return (grantPosPicks[si]?.length ?? 0) === 0
    if (t === 'field') return !grantTarget[si]
    return false
}

const fieldById = (id: number | null | undefined) => props.def.fields.find((f) => f.id === id)

const valueMode = (c: RecordPermConditionRow): 'user' | 'status' | 'option' | 'text' => {
    if (c.source === 'creator' || c.source === 'updater') return 'user'
    if (c.source === 'status') return 'status'
    const f = fieldById(c.field_id)
    if (f?.options?.length) return 'option'
    if (f?.input_type === 'user' || f?.input_type === 'member') return 'user'
    return 'text'
}
const candidates = (c: RecordPermConditionRow): { value: any; label: string }[] => {
    const mode = valueMode(c)
    if (mode === 'user') return props.users.map((u) => ({ value: u.id, label: u.name }))
    if (mode === 'status') return props.def.statuses.map((s) => ({ value: s.name, label: s.name }))
    if (mode === 'option') return (fieldById(c.field_id)?.options ?? []).map((o) => ({ value: o, label: o }))
    return []
}
const splitCsv = (s: string) => s.split(',').map((x) => x.trim()).filter(Boolean)

const grantLabel = (g: RecordPermGrantRow) =>
    subjectLabelFor(g.subject_type, g.subject_id, props.users, props.positions, fieldById(g.subject_id)?.label)

const addSet = () => props.def.recordPermissions.push({ match_mode: 'all', conditions: [], grants: [] })

const addGrant = (si: number) => {
    const type = gType(si)
    const set = props.def.recordPermissions[si]
    const flags = flagsFor(si)
    const mk = (id: number | null) => ({ subject_type: type, subject_id: id, can_view: flags.can_view, can_edit: flags.can_edit, can_delete: flags.can_delete })
    const exists = (id: number | null) => set.grants.some((g) => g.subject_type === type && g.subject_id === id)

    if (NO_TARGET.includes(type)) {
        if (set.grants.some((g) => g.subject_type === type)) return
        set.grants.push(mk(null))
    } else if (type === 'user') {
        ;(grantUserPicks[si] ?? []).forEach((u: any) => { if (u && !exists(u.id)) set.grants.push(mk(u.id)) })
        grantUserPicks[si] = []
    } else if (type === 'position') {
        ;(grantPosPicks[si] ?? []).forEach((id) => { if (!exists(id)) set.grants.push(mk(id)) })
        grantPosPicks[si] = []
    } else if (type === 'field') {
        const t = grantTarget[si]
        if (t && !exists(t)) set.grants.push(mk(t))
        grantTarget[si] = null
    }
}
</script>

<style scoped>
.set-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.sub-sec { font-size: 12px; color: gray; margin-bottom: 6px; }
.cond-row, .grant-row { display: flex; align-items: center; gap: 6px; padding: 5px 0; flex-wrap: wrap; }
.cond-src { width: 110px; }
.cond-op { width: 130px; }
.cond-val { min-width: 160px; min-height: 34px; flex: 1; }
.subj { font-size: 13px; flex: 1; min-width: 100px; }
.rm { border: none; background: none; color: gray; cursor: pointer; padding: 3px; }
.addbar { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
.ml-auto { margin-left: auto; }
.w-fit { width: fit-content; }

.add-panel { margin-top: 8px; background: var(--bg3); border-radius: 8px; padding: 10px; box-sizing: border-box !important; }
.flags-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.rec-sel, .cond-val { max-width: 420px; box-sizing: border-box !important; }
.rec-sel :deep(.item-selector-shell), .cond-val :deep(.item-selector-shell) { border: 1px solid var(--formBorder) !important; border-radius: 6px !important; box-sizing: border-box !important; }
.rec-sel :deep(.one-selector .v-field__input), .cond-val :deep(.one-selector .v-field__input) { min-height: 38px; padding-top: 2px; padding-bottom: 2px; }
</style>
