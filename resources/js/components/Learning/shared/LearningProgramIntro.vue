<template>
    <section class="learning-program-intro">
        <div v-if="showEvaluationLink" class="learning-program-intro__eval">
            <router-link :to="{name: 'evaluate'}">職能研修機関確認用</router-link>
        </div>

        <p class="learning-program-intro__title">研修プログラムについて<br></p>
        <p class="learning-program-intro__body">この研修プログラムは、社会活動で求められる9つの職能を学ぶためのものです。<br>まず、GLOWDのラーニングアプリを使用して基礎学習を行い、内容を理解します。<br>理解が困難な場合は、補足資料の確認やフォローアップ面談を通じてサポートを受けることができます。<br>次に、基礎学習を理解した参加者は、グループディスカッションのためのポートフォリオを作成します。<br>このディスカッションでは、ポートフォリオを発表し、ポジティブフィードバックとネガティブフィードバックを受けて、ポートフォリオを完成させます。<br>完成したポートフォリオは、各参加者のマイページのプロフィール欄に自動的に掲載されます。<br>基礎学習を完了し、ポートフォリオが完成した参加者は、1つのテーマの履修が完了したとみなされます。</p>

        <p class="learning-program-intro__title">ポートフォリオとは<br></p>
        <p class="learning-program-intro__body">ポートフォリオは、自分の学んだことや経験をまとめた記録です。<br>これには、研修で学んだ内容、過去に取り組んだプロジェクトやその成果、自分の強みや特性、自分の意見や考え方などを含めます。<br>ポートフォリオを作ることで、自分がどう成長したか、どのように考えているかを他の人に示すことができます。<br>また、フィードバックを受け入れて改善することで、さらに自分自身を深く理解し、発展させることができます。</p>

        <div class="learning-program-intro__videos">
            <div v-for="video in videos" :key="video.src" class="learning-program-intro__video">
                <p><strong>{{ video.title }}</strong></p>
                <video controls>
                    <source :src="video.src">
                </video>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
defineProps<{
    showEvaluationLink?: boolean
}>()

const videos = [
    {
        title: '研修プログラムの説明',
        src: '/cdn/lesson_files/program-explaination.mp4',
    },
    {
        title: 'グループディスカッションの説明',
        src: '/cdn/lesson_files/discussion-explaination.mp4',
    },
    {
        title: 'ポートフォリオの説明',
        src: '/cdn/lesson_files/portfolio-explaination.mp4',
    },
] as const
</script>

<style scoped>
.learning-program-intro {
    padding: 20px;
    white-space: break-spaces;
    font-size: 14px;
    line-height: 1.5;
    color: var(--primary-color);
    background: var(--background-color);
}

.learning-program-intro__eval {
    margin-bottom: 20px;
}

.learning-program-intro__title {
    font-size: 18px;
    margin: 15px 0;
}

.learning-program-intro__title:first-of-type {
    margin-top: 0;
    margin-bottom: 15px;
}

.learning-program-intro__body {
    line-height: 2.5;
    margin-bottom: 25px;
}

.learning-program-intro__videos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 20px;
}

.learning-program-intro__video video {
    max-width: 100%;
    margin-top: 15px;
}

@media screen and (max-width: 959px) {
    .learning-program-intro__videos {
        grid-template-columns: 1fr;
    }
}
</style>
