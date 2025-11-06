<!-- ContractFindings.vue -->
<template>
  <div v-if="contract && contract.json" class="space-y-3 mt-5">
    <div class="text-sm text-gray-600">
      全体的なリスク:
      <span :class="badgeClass(contract.json.overall_risk)">
        {{ severityLabel(contract.json.overall_risk) }}
      </span>
    </div>

    <!-- カウント + フィルタ -->
    <div class="flex flex-wrap gap-2 items-center">
      <button
        class="px-2 py-1 rounded border text-[tomato] border-[tomato]/40 hover:bg-[tomato]/10"
        @click="setFilter('high')"
      >
        高: {{ counts.high }}
      </button>
      <button
        class="px-2 py-1 rounded border text-[orange] border-[orange]/40 hover:bg-[orange]/10"
        @click="setFilter('medium')"
      >
        中: {{ counts.medium }}
      </button>
      <button
        class="px-2 py-1 rounded border text-gray-500 border-gray-300 hover:bg-gray-100"
        @click="setFilter('low')"
      >
        低: {{ counts.low }}
      </button>

      <button
        v-if="activeFilter"
        class="ml-2 px-2 py-1 text-xs rounded bg-gray-200 hover:bg-gray-300"
        @click="clearFilter"
        title="フィルタ解除"
      >
        すべて表示（{{ filteredFindings.length }}件）
      </button>
    </div>

    <!-- 一覧（クリックで詳細展開） -->
    <div v-if="filteredFindings.length" class="divide-y rounded border">
      <div
        v-for="(f, idx) in filteredFindings"
        :key="idx"
        class="p-3 hover:bg-gray-50 cursor-pointer"
        @click="toggle(idx)"
      >
        <!-- 概要行 -->
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="text-xs text-gray-500">{{ f.section || '（見出し未取得）' }}</div>
            <div class="font-medium truncate">{{ f.issue }}</div>
          </div>
          <span :class="badgeClass(f.severity)">{{ severityLabel(f.severity) }}</span>
        </div>

        <!-- 詳細行 -->
        <transition name="fade">
          <div v-if="isOpen(idx)" class="mt-2 space-y-1 text-sm text-gray-700">
            <div><span class="font-semibold">理由:</span> {{ f.rationale }}</div>
            <div>
              <span class="font-semibold">提案（修正案）:</span>
              <div class="mt-1">
                • {{ f.suggestion }}
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <div v-else class="text-sm text-gray-500">該当する指摘はありません。</div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

type Finding = {
  section?: string
  issue: string
  severity: 'high' | 'medium' | 'low'
  rationale: string
  suggestion: string
}

const props = defineProps<{
  contract: {
    json: {
      overall_risk: 'high' | 'medium' | 'low' | 'unknown'
      findings: Finding[]
    }
  } | null
}>()

const openSet = ref<Set<number>>(new Set())
const activeFilter = ref<null | 'high' | 'medium' | 'low'>(null)

const severityLabel = (s: string) => ({
  high: '高',
  medium: '中',
  low: '低',
  unknown: '不明'
}[s as keyof any] || '不明')

const badgeClass = (s: string) => {
  switch (s) {
    case 'high': return 'px-2 py-0.5 rounded text-[tomato] border border-[tomato]/40'
    case 'medium': return 'px-2 py-0.5 rounded text-[orange] border border-[orange]/40'
    case 'low': return 'px-2 py-0.5 rounded text-gray-600 border border-gray-300'
    default: return 'px-2 py-0.5 rounded text-gray-500 border border-gray-300'
  }
}

const counts = computed(() => {
  const f = props.contract?.json.findings ?? []
  return {
    high: f.filter(x => x.severity === 'high').length,
    medium: f.filter(x => x.severity === 'medium').length,
    low: f.filter(x => x.severity === 'low').length
  }
})

const filteredFindings = computed<Finding[]>(() => {
  const all = props.contract?.json.findings ?? []
  return activeFilter.value ? all.filter(f => f.severity === activeFilter.value) : all
})

function toggle(i: number) {
  if (openSet.value.has(i)) openSet.value.delete(i)
  else openSet.value.add(i)
}

function isOpen(i: number) {
  return openSet.value.has(i)
}

function setFilter(s: 'high' | 'medium' | 'low') {
  activeFilter.value = s
  openSet.value.clear()
}

function clearFilter() {
  activeFilter.value = null
  openSet.value.clear()
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
