<template>
    <div
        v-if="count"
        class="badge-circle"
        :class="{'custom-heartbeat' : pulse}"
        >
        <svg v-if="count > 99" fill="white" xmlns="http://www.w3.org/2000/svg" width="13px" height="13px" viewBox="0 0 71.01052 31.96521">
            <path d="M58.47978,31.53354c-4.12124.94909-8.16524.31293-11.78539-1.70922-4.33648-2.36561-7.63024-5.87788-11.19228-9.3815l-4.56919,4.42938c-1.97927,1.91872-4.12836,3.50977-6.55586,4.81707-3.59917,1.94769-7.63162,2.6911-11.67081,2.04968-5.0224-.79755-9.21878-3.9871-11.26074-8.63348C.34797,20.60808-.11163,17.94071.02273,15.19163.36671,8.15407,5.34102,2.25727,12.13388.52867,16.214-.50962,20.3833-.00437,24.09396,2.03135c4.4176,2.34632,7.86243,5.98664,11.40605,9.51273,3.53322-3.50353,6.80565-6.93886,11.19372-9.30842,3.84557-2.03997,8.17366-2.75162,12.43684-1.85355,4.61313.97179,8.43012,3.98207,10.36143,8.30122,1.45284,3.24911,1.89052,6.90402,1.19455,10.41204-1.22736,6.18652-6.03619,11.01712-12.20677,12.43816ZM31.17074,15.99976l-3.71428-3.62053c-1.46077-1.4239-2.95805-2.70203-4.66079-3.81423-4.16296-2.78239-9.49662-2.9385-13.11522.60467-2.18644,2.14087-3.21288,5.17361-2.78293,8.27253.30012,2.16314,1.18747,4.11802,2.76954,5.61604,3.19901,3.02905,7.84942,3.00215,11.64111,1.11169,3.8853-1.94715,6.78078-5.02431,9.86256-8.17017ZM47.79228,23.13431c4.22821,2.98265,9.63143,3.34503,13.41358-.22876,2.18963-2.06899,3.283-5.02505,2.93894-8.09438-.53143-4.74087-3.98154-8.18468-8.78159-8.31862-6.34313-.177-11.35817,5.21958-15.52736,9.47699l3.87466,3.77729c1.27911,1.24697,2.58729,2.34126,4.08177,3.38747Z"/>
        </svg>
        <span v-else>{{ count }}</span>
        
    </div>
</template>
<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    count: number
    color?: 'tomato' | 'blue' | 'green' | 'yellow' | 'orange'
    pulse?: boolean
}>(), {
    count: 0,
    color: 'tomato',
    pulse: false
})

const colorMap = {
    tomato: 'tomato',
    blue: 'dodgerblue',
    green: 'limegreen',
    yellow: 'gold',
    orange: '#F28C28'
}
const fillColor = computed(() => colorMap[props.color])
</script>
<style scoped>
.badge-circle {
    position: absolute;
    top: 8px;
    left: 33px;
    color: #ffffff;
    background: v-bind('fillColor');
    min-width: 15px;
    padding: 1px;
    height: 15px;
    text-align: center;
    font-size: 10px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 6;
    text-indent: 1px;
    line-height: 15px;
}
</style>
