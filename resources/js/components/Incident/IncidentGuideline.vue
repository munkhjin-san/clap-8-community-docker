<script setup lang="ts">
import Modal from '../Global/Modal.vue';

withDefaults(defineProps<{
    inline?: boolean
}>(), {
    inline: false,
});

const emit = defineEmits<{
    close: [value: boolean]
}>();

type GuidelineSection = {
    title: string
    paragraphs?: string[]
    items?: string[]
    contactItems?: { label: string; value: string; href?: string }[]
}

const guidelineTitle = 'インシデント発生時の報告・対応ガイドライン';
const guidelineSections: GuidelineSection[] = [
    {
        title: '目的',
        paragraphs: [
            '事故、情報漏洩、金銭差異、顧客トラブル、システム障害など、会社運営に影響を及ぼす事象が発生した場合の報告ルートと対応フローを定めます。',
            '被害拡大の防止、迅速な初動対応、再発防止、内部統制の強化を目的とします。',
        ],
    },
    {
        title: '対象',
        paragraphs: [
            '本ガイドラインは、全従業員、契約社員、登録社員、役員、委託先など、会社業務に関与するすべての人を対象とします。',
        ],
    },
    {
        title: 'インシデントの定義',
        paragraphs: [
            '以下を含む、会社運営上の問題または異常をインシデントとして扱います。実害が発生していない未遂事象も対象です。',
        ],
        items: [
            '情報漏洩・個人情報漏洩',
            '金銭差異・不明金',
            '顧客クレーム・顧客トラブル',
            '取引先トラブル',
            'システム障害',
            '誤送信・誤登録',
            '備品紛失',
            '事故・破損',
            'セキュリティ事故',
            'コンプライアンス違反',
            'ハラスメント',
            '従業員の重大な体調悪化・労働災害・救急搬送など安全衛生上の重大事象',
            'その他、会社が報告必要と判断する事項',
        ],
    },
    {
        title: '一次報告について',
        paragraphs: [
            'インシデントまたはその可能性を確認した場合、速やかに配属部門のプロジェクトマネージャー（PM）へ報告してください。',
            'PMへ連絡がつかない場合は、以下いずれかの方法で経営管理本部へ一次報告を行ってください。',
        ],
        contactItems: [
            { label: '電話', value: '092-291-1355' },
            { label: 'メール', value: 'soumu@glowd.co.jp', href: 'mailto:soumu@glowd.co.jp' },
            { label: 'GLOWDアプリ内チャット', value: '経営管理本部へ連絡' },
            { label: 'サポートデスク', value: '緊急連絡またはインシデント報告を利用' },
        ],
    },
    {
        title: 'GLOWDアプリによる一次報告',
        paragraphs: [
            '勤務終了時その他適切なタイミングで、GLOWDアプリから一次報告およびインシデントレポートの作成ができます。',
            'この機能は初動共有と記録保存を目的とするものです。入力のみで報告義務が完了するものではありません。',
            'ガイドラインに従い、PMまたは経営管理本部への報告も必ず行ってください。',
        ],
    },
    {
        title: 'PMによる初動対応',
        paragraphs: [
            'PMは一次報告を受けた後、速やかに以下を確認してください。',
        ],
        items: [
            '発生日時',
            '発生場所',
            '対象顧客・取引先・社員',
            '被害状況',
            '現在の対応状況',
            '被害拡大リスク',
            '原因および経緯',
            '顧客・取引先対応の要否',
            '安全確保が必要な場合の医療機関・緊急連絡先への連絡',
        ],
    },
    {
        title: 'インシデントレポート提出',
        paragraphs: [
            'PMは原則として当日中に、GLOWDアプリ上でインシデントレポートを作成・提出してください。',
            '当日提出が難しい場合でも、一次報告は必ず当日中に実施してください。',
        ],
    },
    {
        title: '取締役会での対応判断',
        paragraphs: [
            '提出されたインシデントレポートをもとに、取締役会で以下を判断します。',
        ],
        items: [
            '事故対応方針',
            '顧客・取引先対応方針',
            '公表要否',
            '是正対応内容',
            '再発防止策',
            '懲戒処分要否',
            'その他必要対応',
        ],
    },
    {
        title: '再発防止策',
        paragraphs: [
            '再発防止策はPMが中心となって策定し、インシデントレポートへ記載したうえで取締役会へ提出します。',
            '実施した再発防止策は、実施日時・実施内容・対象者などのログを記録・保管してください。',
        ],
        items: [
            '規程改定',
            'マニュアル改定',
            '業務フロー見直し',
            '権限変更',
            '二者確認強化',
            'システム改修',
            '社員研修',
            '注意喚起',
        ],
    },
    {
        title: '懲戒・指導について',
        paragraphs: [
            'インシデント内容に応じて、当社規定のリスクレベル・損害レベル基準表に基づき、懲戒処分その他必要措置を検討します。',
            '懲戒処分の決定権限は取締役会とします。懲戒処分は、就業規則その他関連規程の定めによります。',
        ],
    },
    {
        title: '禁止事項',
        paragraphs: [
            'インシデント対応において、以下の行為は禁止します。',
        ],
        items: [
            '隠蔽',
            '虚偽報告',
            '報告遅延',
            '証憑改ざん',
            '無断対応',
            '独断での顧客回答',
            'その他、会社が不適切と判断する行為',
        ],
    },
    {
        title: 'その他',
        paragraphs: [
            '問題を自己判断で抱え込まず、速やかに報告・相談してください。',
            '本ガイドラインは、会社の組織体制、法令改正、内部統制強化その他必要に応じて改定される場合があります。',
        ],
    },
];
</script>

<template>
    <section v-if="inline" class="incident-guideline incident-guideline--inline">
        <header class="incident-guideline-header">
            <h3>{{ guidelineTitle }}</h3>
        </header>
        <div class="incident-guideline-body">
            <article
                v-for="(section, index) in guidelineSections"
                :key="section.title"
                class="incident-guideline-section"
            >
                <div class="incident-guideline-section-number">{{ index + 1 }}</div>
                <div class="incident-guideline-section-content">
                    <h4>{{ section.title }}</h4>
                    <p v-for="paragraph in section.paragraphs" :key="paragraph">{{ paragraph }}</p>
                    <ul v-if="section.items?.length">
                        <li v-for="item in section.items" :key="item">{{ item }}</li>
                    </ul>
                    <div v-if="section.contactItems?.length" class="incident-guideline-contact">
                        <div v-for="item in section.contactItems" :key="item.label">
                            <span>{{ item.label }}</span>
                            <a v-if="item.href" :href="item.href">{{ item.value }}</a>
                            <strong v-else>{{ item.value }}</strong>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
    <Modal v-else @close="emit('close', $event)">
        <template #title>{{ guidelineTitle }}</template>
        <template #content>
            <div class="incident-guideline">
                <div class="incident-guideline-body">
                    <article
                        v-for="(section, index) in guidelineSections"
                        :key="section.title"
                        class="incident-guideline-section"
                    >
                        <div class="incident-guideline-section-number">{{ index + 1 }}</div>
                        <div class="incident-guideline-section-content">
                            <h4>{{ section.title }}</h4>
                            <p v-for="paragraph in section.paragraphs" :key="paragraph">{{ paragraph }}</p>
                            <ul v-if="section.items?.length">
                                <li v-for="item in section.items" :key="item">{{ item }}</li>
                            </ul>
                            <div v-if="section.contactItems?.length" class="incident-guideline-contact">
                                <div v-for="item in section.contactItems" :key="item.label">
                                    <span>{{ item.label }}</span>
                                    <a v-if="item.href" :href="item.href">{{ item.value }}</a>
                                    <strong v-else>{{ item.value }}</strong>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </template>
    </Modal>
</template>

<style scoped>
.incident-guideline{
    color: var(--primary-color);
}

.incident-guideline--inline{
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    padding: 18px;
}

.incident-guideline-header{
    border-bottom: 1px solid var(--calendarBorder);
    margin-bottom: 18px;
    padding-bottom: 14px;
}

.incident-guideline-header span{
    display: block;
    margin-bottom: 5px;
    color: gray;
    font-size: 10px;
    letter-spacing: 0.12em;
}

.incident-guideline-header h3{
    margin: 0;
    font-size: 16px;
    font-weight: 700;
}

.incident-guideline--inline .incident-guideline-body{
    max-height: min(62vh, 720px);
    overflow: auto;
    padding-right: 8px;
}

.incident-guideline-body{
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.incident-guideline-section{
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 14px;
}

.incident-guideline-section-number{
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}

.incident-guideline-section-content{
    border-bottom: 1px solid var(--calendarBorder);
    padding-bottom: 18px;
}

.incident-guideline-section:last-child .incident-guideline-section-content{
    border-bottom: none;
    padding-bottom: 0;
}

.incident-guideline-section-content h4{
    margin: 0 0 10px;
    font-size: 15px;
    font-weight: 700;
}

.incident-guideline-section-content p{
    margin: 0 0 8px;
    color: var(--primary-color);
    font-size: 13px;
    line-height: 1.75;
}

.incident-guideline-section-content ul{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 14px;
    margin: 12px 0 0;
    padding: 0;
    list-style: none;
}

.incident-guideline-section-content li{
    position: relative;
    padding-left: 14px;
    color: var(--primary-color);
    font-size: 13px;
    line-height: 1.5;
}

.incident-guideline-section-content li::before{
    content: "";
    position: absolute;
    top: 0.7em;
    left: 0;
    width: 5px;
    height: 5px;
    background: var(--mainColor);
}

.incident-guideline-contact{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 14px;
}

.incident-guideline-contact div{
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    padding: 10px;
}

.incident-guideline-contact span{
    display: block;
    margin-bottom: 4px;
    color: gray;
    font-size: 11px;
}

.incident-guideline-contact strong,
.incident-guideline-contact a{
    color: var(--primary-color);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}

@media screen and (max-width: 959px) {
    .incident-guideline-section{
        grid-template-columns: 28px minmax(0, 1fr);
        gap: 10px;
    }

    .incident-guideline-section-number{
        width: 28px;
        height: 28px;
    }

    .incident-guideline-section-content ul,
    .incident-guideline-contact{
        grid-template-columns: 1fr;
    }
}
</style>
