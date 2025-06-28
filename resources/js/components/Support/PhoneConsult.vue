<template>
    <div class="support-content">
        <div class="support-title">電話相談</div>
        <div class="support-content-inner">
            <div class="mobile-tel-title">【経営管理本部へのご相談】</div>
                <div style="line-height:1.3;margin-top: 10px;">
                    <a href="tel:092-291-1355">092-291-1355</a>
                </div>

                <div class="mt-[20px] leading-[2]">

                    <p>【業務災害総合保険の付帯サービス】グラウドの従業員のみ利用可能です。</p>

                    <p><span><a href="tel:0120-437-213">0120-437-213</a></span></p>

                    <p class="whitespace-break-spaces">※詳細は添付ファイルをご確認ください。<br>
                        会員番号をヒアリングされた場合は「24Q2E8A」とお伝えください。<br><br>

                        ①24時間電話健康相談　介護相談ホットライン<br>
                        ②女性のための24時間電話健康相談<br>
                        ③セカンドオピニオンアレンジサービス<br>
                        ④メンタルケアカウンセリングサービス<br>
                        ⑤生活習慣病サポートサービス<br>
                        ⑥がん治療と仕事の両立支援サービス<br>
                        ⑦二次検針機関の手配サービス<br>
                    </p>

                </div>
                
                <div class="mobile-tel-title" style="line-height:2.8;margin-top:20px;">【完全匿名のご相談】</div>

                <div style="line-height:2">
                    <a href="https://www.mhlw.go.jp/stf/seisakunitsuite/bunya/koyou_roudou/roudoukijun/location.html" target="_blank">全国労働基準監督署</a>
                </div>
                <div class="mt-[20px]">
                    <Files :items="files" path="support_files" />
                </div>

        </div>
    </div>
</template>
<script setup lang="ts">
import Files from '../Global/Files.vue';
const files = [
    {
        id: 1,
        name: '付帯サービスご案内チラシ.pdf',
        path: 'service_guide',
        extension: 'pdf',
        size: 1561361,
        mime_type: 'application',
        user_id: 608,
    },
    {
        id: 2,
        name: '従業員様向けカード.pdf',
        path: 'employee_card',
        extension: 'pdf',
        size: 772668,
        mime_type: 'application',
        user_id: 608,
    }

]
</script>