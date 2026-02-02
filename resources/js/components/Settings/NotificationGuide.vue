<template>
    <Modal @close="emit('close')" v-if="guideStep" size="large">
        <template #title>
            {{ guideTitle }}
        </template>
        <template #content>
            <div v-if="guideStep == 1">
                <ul>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            この画面は、ブラウザが「このサイトから通知を受け取ってよいか」を確認しています。<br/>
                            通知を受け取るには 「許可」 をクリックしてください。

                            許可：通知を受け取れます <br/>

                            ブロック：通知は送信されません <br/>

                            ※「ブロック」を選択すると、後から手動で設定を変更する必要があります。
                        </p>
                        <img src="/pc-notification/1.png" alt="PC通知設定案内1" loading="lazy" style="width: 100%;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            通知設定が 許可済み です。<br/>
                            ブラウザでの通知許可は正常に設定されています。<br/>

                            この状態で、アプリ内の通知設定をONにすると通知を受け取れます。
                        </p>
                        <img src="/pc-notification/2.png" alt="PC通知設定案内2" loading="lazy" style="width: 100%;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            このチャットの通知が OFF になっています。<br/>
                            ブラウザで通知が許可されていても、この設定がOFFの場合は通知されません。<br/>

                            通知を受け取りたい場合は、<br/>
                            メニューから 「通知設定」→ ON に変更してください。
                        </p>
                        <img src="/pc-notification/3.png" alt="PC通知設定案内3" loading="lazy" style="width: 100%;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            このチャットの通知が有効になりました。<br/>
                            新しいメッセージでメンションされたら、ブラウザ通知が届きます。
                        </p>
                        <img src="/pc-notification/4.png" alt="PC通知設定案内4" loading="lazy" style="width: 100%;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            現在、ブラウザで通知が 許可されていません。<br/>
                            この状態では、アプリから通知を送信できません。<br/>

                            通知を利用するには、ブラウザの設定から通知を許可してください。
                        </p>
                        <img src="/pc-notification/5.png" alt="PC通知設定案内5" loading="lazy" style="width: 100%;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            通知がブロックされている場合は、以下の手順で設定を変更してください。<br/>

                            アドレスバー左の 鍵アイコン をクリック<br/>

                            「通知」 の設定を ON（許可） に変更<br/>

                            必要に応じて 「権限をリセット」 をクリック<br/>

                            ページを再読み込みし、表示される通知許可画面で 「許可」 を選択<br/>

                            ※ ブラウザでブロックされた通知は、アプリ側から解除できません。  
                        </p>
                        <img src="/pc-notification/6.png" alt="PC通知設定案内6" loading="lazy" style="width: 100%;height: auto;"/>
                    </li>
                </ul>
            </div>
            <div v-else-if="guideStep == 2">
                <ul>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            この画面は、ブラウザが「このサイトから通知を受け取ってよいか」を確認しています。<br/>
                            通知を受け取るには 「許可」 をクリックしてください。

                            許可：通知を受け取れます <br/>

                            ブロック：通知は送信されません <br/>

                            ※「ブロック」を選択すると、後から手動で設定を変更する必要があります。
                        </p>
                        <img src="/android-notification/1.jpg" alt="android通知設定案内1" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            通知設定が 許可済み です。<br/>
                            ブラウザでの通知許可は正常に設定されています。<br/>

                            この状態で、アプリ内の通知設定をONにすると通知を受け取れます。
                        </p>
                        <img src="/android-notification/2.jpg" alt="android通知設定案内2" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            このチャットの通知が OFF になっています。<br/>
                            ブラウザで通知が許可されていても、この設定がOFFの場合は通知されません。<br/>

                            通知を受け取りたい場合は、<br/>
                            メニューから 「通知設定」→ ON に変更してください。
                        </p>
                        <img src="/android-notification/3.jpg" alt="android通知設定案内3" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            このチャットの通知が有効になりました。<br/>
                            新しいメッセージでメンションされたら、ブラウザ通知が届きます。
                        </p>
                        <img src="/android-notification/4.jpg" alt="android通知設定案内4" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            現在、ブラウザで通知が 許可されていません。<br/>
                            この状態では、アプリから通知を送信できません。<br/>

                            通知を利用するには、ブラウザの設定から通知を許可してください。
                        </p>
                        <img src="/android-notification/5.jpg" alt="android通知設定案内5" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            説明文：<br/>
                            通知がブロックされている場合は、以下の手順で設定を変更してください。<br/>

                            アドレスバー左の 鍵アイコン をクリック<br/>

                            「通知」 の設定を ON（許可） に変更<br/>

                            必要に応じて 「権限をリセット」 をクリック<br/>

                            ページを再読み込みし、表示される通知許可画面で 「許可」 を選択<br/>

                            ※ ブラウザでブロックされた通知は、アプリ側から解除できません。  
                        </p>
                        <img class="mb-5" src="/android-notification/6.jpg" alt="android通知設定案内6" loading="lazy" style="max-height: 400px;height: auto;"/>
                        <img src="/android-notification/7.jpg" alt="android通知設定案内7" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                </ul>
            </div>
            <div v-else-if="guideStep == 3">
                <p class="mb-5 leading-normal">
                    
                    GLOWDの通知機能は、
                    iOS 16.4 以降の端末でのみ利用可能です。<br/><br/>
                    はじめに（重要）<br/>
                    
                    すでに GLOWD をホーム画面に追加している場合 は、<br/>
                    一度削除してから、最初から設定をやり直してください。<br/>

                    これは「新しい更新通知を正しく受け取るため」に必要な手順です。
                </p>
                <ul>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            Safariで GLOWD のページを開きます。<br/>
                            画面下にある 「…（共有メニュー）」ボタン をタップします。
                        </p>
                        <img src="/ios-notification/1.png" alt="ios通知設定案内1" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            表示されたメニューの中から 「共有」 をタップします。
                        </p>
                        <img src="/ios-notification/2.png" alt="ios通知設定案内2" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            一覧の中から 「ホーム画面に追加」 をタップします。
                        </p>
                        <img src="/ios-notification/3.png" alt="ios通知設定案内3" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            アプリ名（例：GLOWD）を確認<br/>

                            「Webアプリとして開く」 が ON になっていることを確認<br/>

                            右上の 「追加」 をタップします
                        </p>
                        <img src="/ios-notification/4.png" alt="ios通知設定案内4" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            iPhoneのホーム画面に GLOWD アイコン が追加されます。<br/>

                            ⚠️ 以降は Safariではなく、このアイコンから起動してください
                        </p>
                        <img src="/ios-notification/5.png" alt="ios通知設定案内5" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                            GLOWDアプリを起動<br/>

                            左上の メニュー（三本線） をタップ<br/>

                            「設定」 を選択します
                        </p>
                        <img src="/ios-notification/6.png" alt="ios通知設定案内6" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                           「通知設定」をタップすると、通知許可の確認が表示されます。<br/>
                        </p>
                        <img src="/ios-notification/7.png" alt="ios通知設定案内7" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                           「表示されたダイアログで 「許可」 をタップします。<br/>
                           ここで重要<br/>

                            「許可」 を選択 → 次へ進めます<br/>

                            「許可しない」 を選択した場合<br/>
                            👉 通知は受け取れません<br/>

                            ⚠️「許可しない」を選んだ場合<br/>

                            一度でも 「許可しない」 を選択すると、<br/>
                            あとから設定を変更しても通知は有効になりません。<br/>

                            その場合は：<br/>

                            GLOWDを ホーム画面から削除<br/>

                            手順1から再インストールが必要になります。
                        </p>
                        <img src="/ios-notification/8.png" alt="ios通知設定案内8" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                           設定画面で
                            「通知設定：許可済み」 と表示されていれば完了です。
                        </p>
                        <img src="/ios-notification/9.png" alt="ios通知設定案内9" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                    <li class="mb-5">
                        <p class="mb-5 leading-normal">
                          補足：チャットごとの通知設定<br/>

                        全体通知が許可されていても、
                        チャット単位で通知がOFF になっていると通知は届きません。<br/>

                        チャット一覧で「︙」をタップ<br/>

                        「通知設定：OFF」 の場合は ON に変更してください<br/>
                        新しいメッセージでメンションされたら、GLOWD通知が届きます。

                        </p>
                        <img class="mb-5" src="/ios-notification/10.png" alt="ios通知設定案内10" loading="lazy" style="max-height: 400px;height: auto;"/>
                        <img src="/ios-notification/11.png" alt="ios通知設定案内11" loading="lazy" style="max-height: 400px;height: auto;"/>
                    </li>
                </ul>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import Modal from '../Global/Modal.vue';
const props = defineProps<{
    guideStep: number;
    guideTitle: string;
}>();
const emit = defineEmits<{
    (e: 'close'): void;
}>();
</script>