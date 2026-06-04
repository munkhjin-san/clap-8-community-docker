<template>
    <div class="warning">
        <div class="flex justify-end cursor-pointer" @click="emit('close')">
            <CloseIcon size="12"/>
        </div>
        
        <div class="flex md:flex-row flex-col items-center gap-5">
            <svg xmlns="http://www.w3.org/2000/svg" id="a" data-name="Layer 1" width="30" height="30" viewBox="0 0 533.3862 470.03714">
                <path d="M30.61171,460.03683c-7.43506,0-14.09326-3.84375-17.81104-10.2832-3.71729-6.43848-3.71729-14.12695,0-20.56543L248.88221,20.28293c3.71777-6.43896,10.37598-10.2832,17.81055-10.2832,7.43555,0,14.09375,3.84424,17.81152,10.2832l236.08105,408.90527c3.71777,6.43848,3.7168,14.12695,0,20.56543-3.71777,6.43945-10.37598,10.2832-17.81055,10.2832H30.61171Z" style="fill: #ffce31;"/>
                <path d="M266.69319,20c1.83423,0,6.39752.51483,9.15057,5.28305l236.08118,408.90497c2.75305,4.76819.91718,8.9776,0,10.5661-.91705,1.58844-3.64459,5.28302-9.15045,5.28302H30.61165c-5.50586,0-8.2334-3.69458-9.15045-5.28302-.91708-1.5885-2.75296-5.79791,0-10.5661L257.54261,25.28305c2.75305-4.76822,7.31635-5.28305,9.15057-5.28305M266.69319,0c-10.29431,0-20.58862,5.09436-26.47107,15.28305L4.1407,424.18802c-11.76492,20.37726,2.94119,45.84912,26.47095,45.84912h472.16284c23.52979,0,38.2359-25.47174,26.47107-45.84912L293.16426,15.28305c-5.88251-10.18869-16.17676-15.28305-26.47107-15.28305h0Z"/>
                <path d="M289.14658,292.86605c3.96201-28.71871,2.50679-57.67466,2.38567-86.55373-.25368-9.62187-.7296-19.24579-1.83851-28.87555-3.58116-26.0806-40.50627-26.66922-44.68898-.41252-2.52609,19.17458-2.78175,38.41208-3.12161,57.68835-.51553,19.2803-.91856,38.51483,1.53835,57.73137,3.97847,26.27393,41.13143,26.88967,45.72508.42208Z"/>
                <path d="M292.9613,350.4207c-1.41042-3.83048-3.4294-7.15409-5.76379-10.25544-5.90764-4.73799-13.30961-8.11694-21.15578-8.09574-15.71595-.41786-29.55051,13.46041-28.98163,29.10032.12588,8.08967,3.7425,15.36736,9.00317,21.25188,23.82764,19.85124,57.14376-2.47241,46.89803-32.00102Z"/>
            </svg>
            
            <div>
                <p>{{ warningMessage }}</p>
                <a :href="warningHref" >{{ props.linkText }}</a>
            </div>
        </div>
        
        
    </div>
</template>
<script lang="ts" setup>
import { computed } from 'vue';
import CloseIcon from '../Form/CloseIcon.vue';
const emit = defineEmits<{
    (e: 'close'): void;
}>();
const props = withDefaults(defineProps<{
    pending?: boolean;
    message?: string;
    href?: string;
    linkText?: string;
}>(), {
    pending: false,
    linkText: 'ダッシュボードへ',
});
const warningMessage = computed(() => props.message ?? '未承認日報があります。\nご確認・ご対応をお願いいたします。');
const warningHref = computed(() => props.href ?? `/dashboard`);
</script>
<style scoped>
.warning {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--background-color);
    color: var(--primary-color);
    padding: 20px;
    border-radius: 5px;
    z-index: 1000;
    font-size: 14px;
    line-height: 2;
    
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
.warning p {
    white-space: pre-line;
}
@media screen and (max-width: 959px) {
    .warning {
        width: 80%;
    }
}
</style>
