<template>
    <div class="ffr">
    <template v-if="hasRules">
        <div class="divider"></div>
        <div class="sec">入力ルール</div>

        <template v-if="inputType === 'short' || inputType === 'long'">
            <div class="irow">
                <label>文字数</label>
                <div class="minmax">
                    <input type="number" min="0" v-model.number="v.min_length" placeholder="最小" class="ffr-input">
                    <span class="tilde">〜</span>
                    <input type="number" min="0" v-model.number="v.max_length" placeholder="最大" class="ffr-input">
                </div>
            </div>
            <div class="irow" v-if="inputType === 'short'">
                <label>形式</label>
                <select v-model="v.format" class="ffr-input">
                    <option value="none">指定なし</option>
                    <option value="email">メールアドレス</option>
                    <option value="tel">電話番号</option>
                    <option value="url">URL</option>
                </select>
            </div>
        </template>

        <template v-else-if="inputType === 'number'">
            <div class="irow">
                <label>値の範囲</label>
                <div class="minmax">
                    <input type="number" v-model.number="v.min" placeholder="最小" class="ffr-input">
                    <span class="tilde">〜</span>
                    <input type="number" v-model.number="v.max" placeholder="最大" class="ffr-input">
                </div>
            </div>
            <div class="irow">
                <label>整数のみ</label>
                <span class="flow-sw" :class="{ on: v.integer_only }" @click="v.integer_only = !v.integer_only"></span>
            </div>

            <!-- 表示のしかた。数値項目は「金額」にも「ID」にも使われるので、桁区切りは選べる必要がある
                 （IDに桁区切りが入ると量のように見えてしまう）。 -->
            <div class="irow">
                <label>桁区切り</label>
                <span class="flow-sw" :class="{ on: v.thousand_separator !== false }"
                    @click="v.thousand_separator = v.thousand_separator === false"></span>
            </div>
            <div class="irow">
                <label>小数点以下</label>
                <div class="flex items-center gap-[6px]">
                    <input type="number" min="0" max="10" v-model.number="v.decimals" placeholder="そのまま" class="ffr-input !w-[110px]">
                    <span class="text-[12px] text-gray-500">桁</span>
                </div>
            </div>
            <div class="irow">
                <label>単位</label>
                <div class="flex items-center gap-[6px] flex-1 min-w-0">
                    <input type="text" v-model="v.unit" placeholder="円・人・% など" maxlength="12" class="ffr-input flex-1 min-w-0">
                    <select :value="v.unit_position || 'after'" @change="v.unit_position = ($event.target as HTMLSelectElement).value as 'before' | 'after'" class="ffr-input !w-[76px]">
                        <option value="after">後ろ</option>
                        <option value="before">前</option>
                    </select>
                </div>
            </div>
            <p class="ffr-hint">表示例: {{ numberPreview }}</p>
        </template>

        <template v-else-if="inputType === 'checkbox'">
            <div class="irow">
                <label>選択数</label>
                <div class="minmax">
                    <input type="number" min="0" v-model.number="v.min_select" placeholder="最小" class="ffr-input">
                    <span class="tilde">〜</span>
                    <input type="number" min="0" v-model.number="v.max_select" placeholder="最大" class="ffr-input">
                </div>
            </div>
        </template>

        <template v-else-if="inputType === 'file'">
            <div class="vcol">
                <label class="vlabel">受付形式</label>
                <div class="chips">
                    <button v-for="a in fileAccepts" :key="a.value" class="achip" :class="{ on: (v.accept || []).includes(a.value) }" @click="toggleAccept(a.value)">{{ a.label }}</button>
                </div>
            </div>
            <div class="irow">
                <label>最大サイズ</label>
                <div class="flex items-center gap-[6px]">
                    <input type="number" min="0" v-model.number="v.max_size_mb" placeholder="制限なし" class="ffr-input ffr-narrow">
                    <span class="text-[12px] text-gray-500">MB</span>
                </div>
            </div>
            <div class="irow">
                <label>複数可</label>
                <span class="flow-sw" :class="{ on: v.allow_multiple }" @click="v.allow_multiple = !v.allow_multiple"></span>
            </div>
        </template>

        <template v-else-if="inputType === 'user' || inputType === 'member'">
            <div class="irow">
                <label>複数選択</label>
                <span class="flow-sw" :class="{ on: v.multiple !== false }" @click="v.multiple = v.multiple === false"></span>
            </div>
        </template>

        <template v-else-if="inputType === 'date'">
            <div class="irow">
                <label>日付の範囲</label>
                <div class="minmax">
                    <input type="date" v-model="v.min_date" class="ffr-input" :style="{ colorScheme: nativeScheme }">
                    <span class="tilde">〜</span>
                    <input type="date" v-model="v.max_date" class="ffr-input" :style="{ colorScheme: nativeScheme }">
                </div>
            </div>
        </template>

        <template v-else-if="inputType === 'datetime'">
            <div class="irow">
                <label>日時の範囲</label>
                <div class="minmax">
                    <input type="datetime-local" v-model="v.min_date" class="ffr-input" :style="{ colorScheme: nativeScheme }">
                    <span class="tilde">〜</span>
                    <input type="datetime-local" v-model="v.max_date" class="ffr-input" :style="{ colorScheme: nativeScheme }">
                </div>
            </div>
        </template>

        <template v-else-if="inputType === 'time'">
            <div class="irow">
                <label>時刻の範囲</label>
                <div class="minmax">
                    <input type="time" v-model="v.min_time" class="ffr-input" :style="{ colorScheme: nativeScheme }">
                    <span class="tilde">〜</span>
                    <input type="time" v-model="v.max_time" class="ffr-input" :style="{ colorScheme: nativeScheme }">
                </div>
            </div>
        </template>
    </template>

    <template v-if="hasDefault">
        <div class="divider"></div>
        <div class="sec">初期値（新規作成時）</div>

        <input v-if="inputType === 'short'" type="text" v-model="v.default" class="ffr-input" placeholder="初期テキスト">
        <textarea v-else-if="inputType === 'long'" v-model="v.default" rows="2" class="ffr-input" placeholder="初期テキスト"></textarea>
        <input v-else-if="inputType === 'number'" type="number" v-model.number="v.default" class="ffr-input" placeholder="初期値">

        <div v-else-if="inputType === 'toggle'" class="irow" style="margin: 0">
            <label>初期状態</label>
            <span class="flow-sw" :class="{ on: v.default }" @click="v.default = !v.default"></span>
        </div>

        <select v-else-if="inputType === 'select' || inputType === 'radio'" v-model="v.default" class="ffr-input">
            <option :value="null">なし</option>
            <option v-for="o in options || []" :key="o" :value="o">{{ o }}</option>
        </select>

        <div v-else-if="inputType === 'checkbox'" class="def-checks">
            <label v-for="o in options || []" :key="o" class="fi-opt">
                <input type="checkbox" :checked="defaultArray.includes(o)" @change="toggleDefault(o)"> {{ o }}
            </label>
            <span v-if="!(options || []).length" class="text-[12px] text-gray-400">選択肢を先に追加してください。</span>
        </div>

        <template v-else-if="inputType === 'date' || inputType === 'datetime' || inputType === 'time'">
            <div class="irow" style="margin: 0">
                <label>現在日時にする</label>
                <span class="flow-sw" :class="{ on: v.default_now }" @click="v.default_now = !v.default_now"></span>
            </div>
            <p class="def-hint">オンにすると作成時の日時が自動で入ります。</p>
        </template>

        <div v-else-if="inputType === 'user' || inputType === 'member'" class="irow" style="margin: 0">
            <label>作成者を初期値</label>
            <span class="flow-sw" :class="{ on: v.default_me }" @click="v.default_me = !v.default_me"></span>
        </div>
    </template>
    </div>
</template>

<script setup lang="ts">
/**
 * 入力ルール + 初期値 for one field OR one table column.
 *
 * A table column has always been a field as far as the rest of the system is concerned — the cell
 * renderer (FlowFieldInput.cellFields) and the validator (FlowService::validateOne's `table` branch)
 * both wrap a column into a synthetic FlowField and run the same code, and TableColumn already
 * declares the full FlowFieldValidation. What was missing was only the builder UI: the column editor
 * never wrote to col.validation, so the rules existed and were enforced but could not be set.
 *
 * Hence one component driven by (input_type + validation + options) rather than by a field object:
 * a rule added here shows up for columns automatically, so the two can't drift apart again.
 *
 * `validation` is the caller's own reactive object and is written through directly (same contract as
 * FlowRecordForm's values/errors).
 */
import { computed } from 'vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import { useTheme } from '@/store/theme'
import { FLOW_FILE_ACCEPT } from '@/types/flow'
import type { FlowFieldValidation, FlowInputType } from '@/types/flow'

import { formatFlowNumber } from '@/utils/flowNumber'

const props = defineProps<{
    inputType: FlowInputType
    validation: FlowFieldValidation
    options?: string[] | null
}>()

const theme = useTheme()
// native date/time pickers draw their icon per color-scheme; follow the app theme so it stays visible
const nativeScheme = computed(() => (theme.dark ? 'dark' : 'light'))

const v = computed<FlowFieldValidation>(() => props.validation)

/** 設定した見え方をその場で示す。桁区切りの有無は数字を見ないと伝わりにくい。 */
const numberPreview = computed(() => formatFlowNumber(1234567.891, props.validation))
const fileAccepts = FLOW_FILE_ACCEPT

const RULE_TYPES = ['short', 'long', 'number', 'date', 'datetime', 'time', 'checkbox', 'file', 'user', 'member']
const DEFAULT_TYPES = ['short', 'long', 'number', 'select', 'radio', 'checkbox', 'toggle', 'date', 'datetime', 'time', 'user', 'member']
const hasRules = computed(() => RULE_TYPES.includes(props.inputType))
const hasDefault = computed(() => DEFAULT_TYPES.includes(props.inputType))

const defaultArray = computed<any[]>(() => (Array.isArray(v.value.default) ? v.value.default : []))
const toggleDefault = (o: string) => {
    const next = defaultArray.value.slice()
    const i = next.indexOf(o)
    if (i >= 0) next.splice(i, 1)
    else next.push(o)
    v.value.default = next
}
const toggleAccept = (val: string) => {
    if (!v.value.accept) v.value.accept = []
    const i = v.value.accept.indexOf(val)
    if (i >= 0) v.value.accept.splice(i, 1)
    else v.value.accept.push(val)
}
</script>

<style scoped>
.irow { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.irow label { font-size: 12px; color: gray; width: 86px; flex-shrink: 0; }
.vcol { margin-bottom: 10px; }
.vlabel { font-size: 12px; color: gray; display: block; margin-bottom: 6px; }
.minmax { display: flex; align-items: center; gap: 6px; flex: 1; }
.minmax input { width: 100%; min-width: 0; }
.tilde { color: gray; font-size: 12px; }
.chips { display: flex; flex-wrap: wrap; gap: 6px; }
.achip { font-size: 12px; padding: 5px 11px; border: 1px solid var(--calendarBorder); border-radius: 14px; background: var(--background-color); color: gray; cursor: pointer; user-select: none; }
.achip.on { border-color: var(--primary-color); background: var(--bg3); color: var(--primary-color); }
.sec { font-size: 12px; color: gray; margin: 0 0 8px; }
.divider { height: 1px; background: var(--calendarBorder); margin: 14px 0; }
.sremove { border: none; background: none; color: gray; cursor: pointer; padding: 4px; display: flex; }
.def-checks { display: flex; flex-direction: column; gap: 7px; }
.def-checks .fi-opt { font-size: 13px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
.def-hint { font-size: 11.5px; color: gray; margin-top: 6px; line-height: 1.8; line-break: strict; }

/* 自前の入力スタイル。customForm.css の .custom-a-input は使わない：
   `input[type='text'].custom-a-input { width: 50% }` と `textarea… { min-height: 150px }` を
   持っていて、要素+属性+クラスで詳細度 0-2-1 のため、こちら側で付けていた w-full（0-1-0）では
   上書きできなかった。結果、初期値の入力欄がパネル幅の半分のまま小さく、長文の初期値だけ
   150px も縦に伸びる、という食い違いになっていた。 */
.ffr-input { box-sizing: border-box; width: 100%; min-width: 0; font-size: 13px; padding: 6px 10px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); color: var(--font-color); transition: border-color .15s; }
.ffr-input:focus { outline: none; border-color: var(--primary-color); }
.ffr-input::placeholder { color: var(--sub-color); }
/* rows=2 に見合う高さ。縦だけ伸ばせる */
textarea.ffr-input { min-height: 58px; resize: vertical; line-height: 1.7; }
/* 最小〜最大のように横に2つ並ぶものは幅を分け合う */
.ffr-narrow { width: auto; flex: 1; }
.ffr-hint { font-size: 11.5px; color: gray; line-height: 1.7; margin: 2px 0 0; }
</style>
