<template>
    <div>
        <div
            ref="bodyRef"
            class="leading-normal"
            :style="clampStyle"
            v-html="html"
        ></div>
        <p v-if="needsMore" class="jump-link mt-2" @click="expanded = !expanded">
            {{ expanded ? '閉じる' : '続きを表示する' }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch, type StyleValue } from 'vue'

const props = withDefaults(defineProps<{
    /** 表示するHTML。呼び出し側でサニタイズ済みのものを渡すこと。 */
    html?: string
    /** 折りたたみ時に見せる行数。 */
    lines?: number
}>(), {
    html: '',
    lines: 5,
})

const bodyRef = ref<HTMLElement | null>(null)
const expanded = ref(false)
const needsMore = ref(false)

/*
 * 行数の指定はTailwindの任意値クラス（line-clamp-[n]）ではなくインラインstyleで行う。
 * ビルド時にクラス名を走査する仕組みなので、propsの値から組み立てたクラスは生成されない。
 */
const clampStyle = computed<StyleValue | undefined>(() => expanded.value ? undefined : {
    display: '-webkit-box',
    WebkitBoxOrient: 'vertical',
    WebkitLineClamp: String(props.lines),
    overflow: 'hidden',
})

/** 折りたたんだ状態で中身がはみ出すか。はみ出すときだけボタンを出す。 */
const measure = async () => {
    await nextTick()
    const el = bodyRef.value
    if (!el) return

    // 展開中は必ず溢れないので、判定は折りたたみ時の実寸で行う。
    needsMore.value = expanded.value ? needsMore.value : el.scrollHeight > el.clientHeight + 1
}

// 別のプロジェクトへ切り替えたときに前の判定が残らないようにする。
watch(() => props.html, () => {
    expanded.value = false
    needsMore.value = false
    measure()
})

onMounted(measure)
</script>
