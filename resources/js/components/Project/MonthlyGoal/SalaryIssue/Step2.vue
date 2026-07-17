<template>
    <div style="margin-top: 20px;">
        <div>
            <div @click="emit('next', 1)" class="undo-kadai">
                <svg fill="var(--primary-color)" version="1.1" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg>
                <div>戻る</div>
            </div>
            <div class="selected-theme">
                
                <div style="margin-top: 15px;">
                    <div><strong>成果目標</strong></div>
                    <div style="font-size: 12px;margin-top: 10px;white-space: break-spaces;">{{ chosenGoal.title || chosenGoal.outcome_goal }}</div>
                </div>
            </div>
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">等級</p>
                <p>{{ evaluationData?.after_salary_rank?.slice(0, 3) ?? '' }}</p>
            </div>
            <div v-if="eligibility" class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">今期の昇給課題設定枠</p>
                <p v-if="eligibility.allowance > 0" :class="{'!text-[tomato]': eligibility.remaining <= 0}">
                    残り {{ eligibility.remaining }} 件 ／ 上限 {{ eligibility.allowance }} 件（設定済み {{ eligibility.used }} 件）
                </p>
                <p v-else class="!text-[tomato]">
                    前期の成果目標評価（{{ eligibility.previous_total }}点）が基準（360点）に達していないため、今期は昇給課題を設定できません。
                </p>
            </div>
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">等級別昇給課題設定上限数</p>
                <div class="gc-wrap">
                    <table class="gc">
                        <thead>
                            <tr>
                                <th class="gc__gh">等級</th>
                                <th>自己</th>
                                <th>組織</th>
                                <th>社会</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="grade in grades" :key="grade.level" :class="{'gc--current': isCurrentGrade(grade.level)}">
                                <td class="gc__grade">
                                    {{ grade.level }}
                                    <span v-if="isCurrentGrade(grade.level)" class="gc__now">現在</span>
                                </td>
                                <td>{{ grade.self }}</td>
                                <td>{{ grade.organization }}</td>
                                <td>{{ grade.society }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">テーマを選択</p>
                <div v-if="suggesting" class="mb-3 text-[12px] text-[var(--third-color)]">
                    AIがあなたにおすすめのテーマを分析しています…
                </div>
                <div v-else-if="suggestion && suggestion.title_full" class="si-recommend mb-3">
                    <div class="si-recommend__head">
                        <span class="si-chip si-chip--rec">★ Recommended</span>
                        <span class="si-recommend__title">{{ suggestion.title_full }}</span>
                    </div>
                    <p v-if="suggestion.rationale" class="si-recommend__reason">{{ suggestion.rationale }}</p>
                    <p class="si-recommend__note">最終的な選択はご自身で行ってください。</p>
                </div>
                <div class="tm-wrap">
                    <table class="tm">
                        <thead>
                            <tr>
                                <th class="tm__corner"></th>
                                <th class="tm__axis" v-for="level in levels" :key="level">{{ level }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in matrix" :key="row.theme">
                                <th class="tm__theme" scope="row"><span>{{ row.theme }}</span></th>
                                <td
                                    v-for="cell in row.cells"
                                    :key="cell.level"
                                    class="tm-cell"
                                    :class="{
                                        'tm-cell--selectable': !cell.disabled,
                                        'tm-cell--disabled': cell.disabled,
                                        'tm-cell--rec': cell.recommended,
                                    }"
                                    @click="setTheme(cell.level, cell.theme)"
                                >
                                    <div class="tm-cell__chips">
                                        <span v-if="cell.recommended" class="si-chip si-chip--rec">★ Recommended</span>
                                        <span v-if="cell.completed" class="si-chip si-chip--done">✓ 受講済み</span>
                                        <span v-if="cell.disabled && cell.reason" class="si-chip si-chip--blocked">{{ cell.reason }}</span>
                                    </div>
                                    <div class="tm-cell__title">{{ cell.issue?.title }}</div>
                                    <p class="tm-cell__desc">{{ cell.issue?.content }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useDashboardGoalsStore, issueThemes } from '@/store/dashboardGoals';
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';
import { useDialog } from '@/composables/dialog'
import { useApi } from '@/composables/api';
import { ProjectGoal, SalaryIssueEligibility } from '@/interface/projectInterface';

const props = defineProps<{
    getIssues: Function,
    chosenGoal: ProjectGoal,
    possibleThemes: string[],
    eligibility: SalaryIssueEligibility | null,
}>()
const emit = defineEmits(['next', 'selectThemeConfirm'])
const levels = ['自己', '組織', '社会']
const themes = ['意義', '調和', '創造']
const { ping } = useDialog()
const { evaluationData } = storeToRefs(useDashboardGoalsStore())
const grades = [
  { level: '1等級', self: 2, organization: 2, society: 2 },
  { level: '2等級', self: 1, organization: 2, society: 2 },
  { level: '3等級', self: 0, organization: 2, society: 2 },
  { level: '4等級', self: 0, organization: 1, society: 2 },
  { level: '5等級', self: 0, organization: 0, society: 2 },
  { level: '6等級', self: 0, organization: 0, society: 2 }
]
// Null eligibility (still loading / unavailable) is treated as unconstrained here;
// the server re-checks on submit, so the UI only guides — it never gates alone.
const quotaExhausted = computed(() => !!props.eligibility && props.eligibility.remaining <= 0)
const axisAllowed = (level: string) => {
    const axes = props.eligibility?.allowed_axes
    return !axes || axes.includes(level)
}
const isCurrentGrade = (gradeLabel: string) => {
    return props.eligibility?.grade != null && parseInt(gradeLabel, 10) === props.eligibility.grade
}
const cellDisabled = (level: string, theme: string) => {
    if (quotaExhausted.value) return true
    if (!axisAllowed(level)) return true
    return !props.possibleThemes.includes(filteredIssues(level, theme)[0].title)
}
const filteredIssues = (level: string, theme: string) => {
    return props.getIssues(level, theme);
};

const api = useApi()
const suggesting = ref(false)
const suggestion = ref<{ title_full: string | null; rationale: string | null } | null>(null)

// The eligible (selectable) cells offered to the AI as candidates.
const candidates = computed(() => {
    const list: { title_full: string; title: string; level: string; theme: string; content: string }[] = []
    for (const theme of themes) {
        for (const level of levels) {
            if (cellDisabled(level, theme)) continue
            const issue = filteredIssues(level, theme)[0]
            if (issue) {
                list.push({ title_full: issue.title_full, title: issue.title, level, theme, content: issue.content })
            }
        }
    }
    return list
})

const requestSuggestion = async (silent = false) => {
    if (!candidates.value.length) {
        if (!silent) ping('提案できるテーマがありません。')
        return
    }
    suggesting.value = true
    try {
        suggestion.value = await api.post('/suggest_salary_issue_theme', {
            goal_id: props.chosenGoal.id,
            candidates: candidates.value,
        })
        if (!suggestion.value?.title_full && !silent) {
            ping('おすすめテーマを提案できませんでした。')
        }
    } finally {
        suggesting.value = false
    }
}

// Once eligibility + cleared themes have loaded (candidates become available),
// automatically request the AI recommendation exactly once.
let autoRequested = false
watch(candidates, (list) => {
    if (!autoRequested && !quotaExhausted.value && list.length) {
        autoRequested = true
        requestSuggestion(true)
    }
}, { immediate: true })

// Per-cell state for the theme matrix (state channels are independent, so a cell
// can be recommended + completed at once; recommended visually dominates).
const cellInfo = (level: string, theme: string) => {
    const issue = filteredIssues(level, theme)[0]
    const completed = props.possibleThemes.includes(issue?.title)
    const axisOk = axisAllowed(level)
    const quotaOk = !quotaExhausted.value
    const disabled = !quotaOk || !axisOk || !completed
    let reason = ''
    if (!quotaOk) reason = '上限到達'
    else if (!axisOk) reason = '等級制限'
    else if (!completed) reason = '未受講'
    const recommended = !!suggestion.value?.title_full && issue?.title_full === suggestion.value.title_full
    return { level, theme, issue, completed, disabled, recommended, reason }
}
const matrix = computed(() =>
    themes.map(theme => ({ theme, cells: levels.map(level => cellInfo(level, theme)) }))
)

const setTheme = (level: string, theme: string) => {
    if (quotaExhausted.value) {
        ping(props.eligibility && props.eligibility.allowance <= 0
            ? '前期の成果目標評価が基準に達していないため、今期は昇給課題を設定できません。'
            : '今期に設定できる昇給課題の上限に達しています。')
        return;
    }
    if (!axisAllowed(level)) {
        ping('現在の等級では、この軸のテーマを選択できません。')
        return;
    }
    if (!props.possibleThemes.includes(filteredIssues(level, theme)[0].title)) {
        ping('このテーマの受講が完了していません。')
        return;
    }

    emit('selectThemeConfirm', level, theme)
}
</script>

<style scoped>
/* ============================================================
   昇給課題 modal tables — clean collapsed grids (tokens only ->
   auto light/dark). No border-radius, no box-shadow. State shown
   via chips + tint + inset primary outline (same 1px width).
   ============================================================ */

/* ---------- AI recommendation summary panel ---------- */
.si-recommend {
    border: solid thin var(--primary-color);
    background: var(--selected-background);
    padding: 10px 12px;
}
.si-recommend__head {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.si-recommend__title {
    font-size: 13px;
    font-weight: 700;
    color: var(--primary-color);
}
.si-recommend__reason {
    margin-top: 8px;
    font-size: 12px;
    line-height: 1.7;
    color: var(--primary-color);
    white-space: pre-wrap;
    word-break: break-word;
}
.si-recommend__note {
    margin-top: 8px;
    font-size: 11px;
    color: var(--third-color);
}

/* shared status chips */
.si-chip {
    display: inline-block;
    font-size: 11px;
    line-height: 1.6;
    padding: 0 6px;
    border: solid thin var(--formBorder);
    background: var(--bg2);
    color: var(--primary-color);
    white-space: nowrap;
}
.si-chip--done {
    border-color: #64bc44;
    background: #64bc44;
    color: #fff;
}
.si-chip--rec {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: var(--background-color);
    font-weight: 700;
}
.si-chip--blocked {
    color: var(--third-color);
    background: var(--bg3);
}

/* ---------- TABLE A : 等級別昇給課題設定上限数 ---------- */
.gc-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.gc {
    border-collapse: collapse;
    width: 100%;
    min-width: 280px;
    font-size: 12px;
    color: var(--primary-color);
}
.gc th,
.gc td {
    border: solid thin var(--formBorder);
    padding: 6px 10px;
    text-align: center;
    white-space: nowrap;
}
.gc thead th { background: var(--bg2); font-weight: 700; }
.gc__gh,
.gc__grade { text-align: left; }
.gc__grade { background: var(--bg3); font-weight: 700; }
.gc--current td { background: var(--selected-background); border-color: var(--primary-color); font-weight: 700; }
.gc--current .gc__grade { background: var(--selected-background); }
.gc__now {
    display: inline-block;
    margin-left: 6px;
    padding: 0 5px;
    font-size: 9px;
    font-weight: 700;
    line-height: 15px;
    background: var(--primary-color);
    color: var(--background-color);
}

/* ---------- TABLE B : テーマを選択 (3x3 collapsed grid) ---------- */
.tm-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.tm {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    min-width: 500px;
    color: var(--primary-color);
}
.tm th,
.tm td { border: solid thin var(--formBorder); }
.tm__corner { width: 30px; border: none; background: transparent; }
.tm__axis {
    background: var(--bg2);
    padding: 6px;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
}
.tm__theme {
    width: 30px;
    background: var(--bg2);
    font-size: 12px;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
}
.tm__theme span {
    writing-mode: vertical-rl;
    text-orientation: upright;
}

.tm-cell {
    vertical-align: top;
    padding: 9px 10px;
    background: var(--background-color);
}
.tm-cell--selectable { cursor: pointer; }
.tm-cell--selectable:hover { background: var(--bg3); }
.tm-cell--disabled { cursor: not-allowed; background: var(--bg3); }
.tm-cell--disabled .tm-cell__title { color: var(--third-color); }
/* recommended: inset primary outline (keeps the 1px grid width) + tint */
.tm-cell--rec { background: var(--selected-background); outline: solid thin var(--primary-color); outline-offset: -1px; }
.tm-cell--rec.tm-cell--selectable:hover { background: var(--bg2); }

.tm-cell__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.tm-cell__chips:not(:empty) { margin-bottom: 6px; }
.tm-cell__title {
    font-size: 13px;
    font-weight: 700;
    line-height: 1.35;
}
.tm-cell__desc {
    margin-top: 4px;
    font-size: 11px;
    line-height: 1.55;
    color: var(--third-color);
    word-break: break-word;
}
</style>