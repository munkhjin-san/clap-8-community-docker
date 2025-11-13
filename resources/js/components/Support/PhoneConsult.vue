<template>
    <div class="support-content">
        <div class="support-title">電話相談</div>
        <div class="support-content-inner">
            <div class="mobile-tel-title">【社内・経営管理本部へのご相談】</div>
                <div style="line-height:1.3;margin-top: 10px;">
                    <a href="tel:092-291-1355">092-291-1355</a>
                    <span>（本社代表番号）</span>
                </div>

                <div class="mobile-tel-title" style="margin-top:20px;margin-bottom: 10px;">【グラウドが加入している業務災害総合保険の付帯サービス】</div>
                <a style="margin-top: 20px;" href="tel:0120-437-213">0120-437-213</a>
                <div class="tell-content" style="line-height:2; margin: 20px">

                    <p>※詳細は添付ファイルをご確認ください。<br>
①24時間電話健康相談　介護相談ホットライン<br>
②女性のための24時間電話健康相談<br>
③セカンドオピニオンアレンジサービス<br>
④メンタルケアカウンセリングサービス<br>
⑤生活習慣病サポートサービス<br>
⑥がん治療と仕事の両立支援サービス<br>
⑦二次検針機関の手配サービス</p>

                </div>
                <p>【完全匿名のご相談】</p>
                <div class="mobile-tel-title" style="line-height:2.8;margin-top:20px;">全国労働基準監督署</div>

                <div style="line-height:2">
                    <a href="https://www.mhlw.go.jp/stf/seisakunitsuite/bunya/koyou_roudou/roudoukijun/location.html" target="_blank">https://www.mhlw.go.jp/stf/seisakunitsuite/bunya/koyou_roudou/roudoukijun/location.html</a>
                </div>
                <div class="mt-[30px]">
                    <SupportFiles :list="files"/>
                </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref } from 'vue';
import SupportFiles from './SupportFiles.vue';

const files = ref([
    {id: 1, name: '従業員様向けカード.pdf', extension: 'pdf', mime_type: 'application', size: 772668, user_id: 610, record_id: 456, removed_at: null},
])
</script>