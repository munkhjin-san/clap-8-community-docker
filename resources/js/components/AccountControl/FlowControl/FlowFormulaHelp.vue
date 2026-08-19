<template>
    <Modal size="large" @close="emit('close')">
        <template #title>
            <h2 class="ffh-title">計算式の書き方</h2>
        </template>
        <template #content>
            <div class="ffh">
                <p class="ffh-lead">
                    計算式は、他の項目の値をもとに数値や文字を自動で計算する機能です。
                    項目・数値・関数・演算子を組み合わせて記述します。
                </p>

                <section>
                    <h3>1. 基本の要素</h3>
                    <table class="ffh-tbl">
                        <thead><tr><th>要素</th><th>書き方</th><th>例</th></tr></thead>
                        <tbody>
                            <tr><td>項目の値</td><td>「<code>[</code>」を入力すると項目の候補が表示されます</td><td><code>[単価]</code></td></tr>
                            <tr>
                                <td>テーブルの列</td>
                                <td><code>[テーブル名.列名]</code>。候補では「テーブル › 列名」と表示されます</td>
                                <td><code>SUM([明細.金額])</code></td>
                            </tr>
                            <tr>
                                <td>テーブル全体</td>
                                <td>列を付けないと<b>すべての数値列</b>が対象になります</td>
                                <td><code>SUM([明細])</code></td>
                            </tr>
                            <tr><td>数値</td><td>そのまま入力</td><td><code>100</code> / <code>3.14</code></td></tr>
                            <tr><td>文字</td><td>クォートで囲む</td><td><code>"要確認"</code></td></tr>
                            <tr><td>真偽</td><td>キーワード</td><td><code>TRUE</code> / <code>FALSE</code></td></tr>
                            <tr><td>かっこ</td><td>計算の優先順位を指定</td><td><code>([単価] + 10) * 2</code></td></tr>
                        </tbody>
                    </table>
                </section>

                <section>
                    <h3>2. 演算子</h3>
                    <table class="ffh-tbl">
                        <thead><tr><th>演算子</th><th>意味</th><th>例</th></tr></thead>
                        <tbody>
                            <tr><td><code>+ - * /</code></td><td>四則演算（0で割ると0を返します）</td><td><code>[単価] * [数量]</code></td></tr>
                            <tr><td><code>%</code></td><td>末尾に付けると「÷100」</td><td><code>8%</code> → <code>0.08</code></td></tr>
                            <tr><td><code>= !=</code></td><td>等しい / 等しくない（TRUE / FALSE を返す）</td><td><code>[状態] = "完了"</code></td></tr>
                            <tr><td><code>&gt; &lt; &gt;= &lt;=</code></td><td>大小の比較</td><td><code>[数量] &gt;= 10</code></td></tr>
                        </tbody>
                    </table>
                </section>

                <section>
                    <h3>3. 関数一覧</h3>
                    <table class="ffh-tbl">
                        <thead><tr><th>関数</th><th>書式</th><th>説明</th></tr></thead>
                        <tbody>
                            <tr v-for="fn in FLOW_FORMULA_FUNCTIONS" :key="fn.name">
                                <td><code>{{ fn.name }}</code></td>
                                <td><code>{{ fn.signature }}</code></td>
                                <td>{{ fn.description }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section>
                    <h3>4. 記述例</h3>
                    <div class="ffh-ex">
                        <div v-for="ex in EXAMPLES" :key="ex.code" class="ffh-ex-row">
                            <code class="ffh-code">{{ ex.code }}</code>
                            <span class="ffh-ex-desc">{{ ex.desc }}</span>
                        </div>
                    </div>
                </section>

                <section>
                    <h3>5. 結果の種類</h3>
                    <p class="ffh-note">
                        計算結果に合わせて <b>結果の種類</b>（数値／文字／オン・オフ）を選びます。
                        <br>
                        たとえば <code>IF(...)</code> が文字を返すのに「数値」を選ぶと、結果は <code>0</code> になります。
                        文字を返す式には「文字」を選んでください。エディター下の警告でも通知されます。
                    </p>
                </section>

                <section>
                    <h3>ヒント</h3>
                    <ul class="ffh-tips">
                        <li>ツールバーのボタンで関数や演算子をカーソル位置に挿入できます。</li>
                        <li>「<code>[</code>」を入力すると項目の候補が表示され、選ぶと挿入されます。</li>
                        <li>
                            1列だけ合計したいときは <code>SUM([明細.金額])</code> のように<b>列まで指定</b>します。
                            <code>SUM([明細])</code> は表の数値列をすべて合計するので、金額と数量が混ざって意図と違う結果になります。
                        </li>
                        <li>入力中はエディターの下に計算結果（またはエラー）がリアルタイムで表示されます。</li>
                    </ul>
                </section>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue'
import { FLOW_FORMULA_FUNCTIONS } from '@/utils/flowFormulaFunctions'

const emit = defineEmits<{ close: [] }>()

const EXAMPLES = [
    { code: '[単価] * [数量]', desc: '小計を計算' },
    { code: 'SUM([明細.金額])', desc: 'テーブルの「金額」列だけを合計' },
    { code: 'COUNT([明細.金額])', desc: 'テーブルの行数（値が入っている行）' },
    { code: 'ROUNDDOWN([単価] * [数量] * 1.1, 0)', desc: '税込金額（端数切り捨て）' },
    { code: 'IF([数量] >= 10, [単価] * 0.9, [単価])', desc: '10個以上で1割引' },
    { code: 'IF(AND([数量] > 0, [単価] > 0), [単価] * [数量], 0)', desc: '両方入力されている時だけ計算' },
    { code: 'IF([在庫] < 5, "要発注", "OK")', desc: '在庫が少なければ「要発注」（結果の種類は「文字」）' },
]
</script>

<style scoped>
.ffh { color: var(--primary-color); font-size: 13px; line-height: 1.6; display: flex; flex-direction: column; gap: 22px; }
.ffh-title { font-size: 16px; font-weight: 600; color: var(--primary-color); }
.ffh-lead { color: gray; }
.ffh section h3 { font-size: 13.5px; font-weight: 600; margin-bottom: 9px; color: var(--primary-color); }
.ffh-tbl { width: 100%; border-collapse: collapse; }
.ffh-tbl th { text-align: left; font-size: 11.5px; color: gray; font-weight: 600; padding: 7px 10px; background: var(--bg3); border: 1px solid var(--calendarBorder); white-space: nowrap; }
.ffh-tbl td { padding: 7px 10px; border: 1px solid var(--calendarBorder); vertical-align: top; }
.ffh-tbl td:nth-child(2) { white-space: nowrap; }
code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; background: var(--bg3); padding: 1px 6px; border-radius: 4px; color: var(--primary-color); }
.ffh-ex { display: flex; flex-direction: column; gap: 8px; }
.ffh-ex-row { display: flex; flex-wrap: wrap; align-items: baseline; gap: 10px; }
.ffh-code { display: inline-block; }
.ffh-ex-desc { color: gray; font-size: 12.5px; }
.ffh-note { color: gray; }
.ffh-note b { color: var(--primary-color); }
.ffh-tips { display: flex; flex-direction: column; gap: 6px; padding-left: 18px; list-style: disc; color: gray; }
</style>
