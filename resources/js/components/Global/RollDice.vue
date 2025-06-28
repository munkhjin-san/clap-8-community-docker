<template>
    <div class="overlay">
        <div class="chatCreate" style="width: unset; height: unset;">
            <div class="recordFormTitle" style="display:flex;">
                <p>グラウドナインに挑戦！</p>
                <div style="margin-left:auto;">
                    <div @click="emit('close', true)" class="cursor-pointer" style="position:unset;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                            viewBox="0 0 32 32">
                            <path
                                d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="dice-wrapper">
                <div class="dice-container">
                    <div v-for="(dice, index) in dices" :key="index" class="dice">
                        {{ dice }}
                    </div>
                </div>
                <div style="margin-top: 20px;" v-if="!missed && hit !== undefined && hit >= 0 && hit < 800">
                    <CommandButton customStyle="height: 30px;"
                        :buttons="[{ title: hit && hit > 0 ? 'ダブルアップ' : 'サイコロを振る', action: () => hit && hit > 0 ? doubleUp() : startRolling() }]" />
                </div>
                <!-- <button @click="startRolling" :disabled="rolling">{{ hit && hit > 0 ? 'ダブルアップ' : 'サイコロを振る'}}</button> -->
                <div v-if="result !== null" style="font-size: 16px;" class="result">
                    結果: {{ result }}
                </div>
                <div class="result" v-if="hit">
                    アタリ{{ hit }}円<br>
                    賞金は翌月のリフレッシュ<br>補助金に増額されます。
                </div>
                <div v-if="hit === 800"
                    style="display: flex; margin-top: 20px; flex-direction: column; gap: 20px; align-items: center; font-size: 13px;">
                    <div>{{ `最高額おめでとう！<br> 賞金は翌月のリフレッシュ<br>補助金に増額されます。` }}</div>
                    <div @click="emit('close', false)" class="nine-answer-button">OK</div>
                </div>
                <div v-if="missed"
                    style="display: flex; margin-top: 20px; flex-direction: column; gap: 20px; align-items: center; font-size: 13px;">
                    <p>ハズレ　次回もお楽しみに！</p>
                    <div @click="emit('close', false)" class="nine-answer-button">OK</div>
                </div>
            </div>

        </div>

    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import CommandButton from './CommandButton.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
const dices = ref([1, 1, 1]);
const rolling = ref(false);
const result = ref<number | null>(null);
const intervals = ref<number[]>([]);
const prizes = [
    { try: 1, hit: 100 },
    { try: 2, hit: 200 },
    { try: 3, hit: 400 },
    { try: 4, hit: 800 }
]
const rollDice = () => Math.floor(Math.random() * 6) + 1;
const count = ref(0)
const emit = defineEmits(['close'])
const hit = ref<number | undefined>(0)
const greater = ref<any>(null)
const props = defineProps(['taskId'])
const missed = ref(false)
const api = useApi()
const { ask } = useDialog()
const updateTryFlag = async () => {
    await api.put('/task_update_flag', { task_id: props.taskId })
}
const startRolling = async () => {
    if (rolling.value) return
    if (hit.value && hit.value > 0) {
        await promptGreaterOrLesser();
    }
    // updateTryFlag()
    beginDiceRolling();
};
const promptGreaterOrLesser = async () => {
    const options = {
        answers: [
            { label: '大(十以上)', value: true },
            { label: '小(十以下)', value: false }
        ]
    };
    const answer = await ask('大か小', options);
    greater.value = answer.value ?? null;
};
const beginDiceRolling = () => {
    rolling.value = true;
    result.value = null;
    count.value++;

    stopAllIntervals();
    startDiceIntervals();

    // Stop rolling at different intervals
    [2000, 3000, 4000].forEach((delay, index) => {
        setTimeout(() => stopRolling(index), delay);
    });
};
const stopAllIntervals = () => {
    intervals.value.forEach(interval => clearInterval(interval));
    intervals.value = [];
};
const startDiceIntervals = () => {
    for (let i = 0; i < dices.value.length; i++) {
        intervals.value[i] = setInterval(() => {
            dices.value[i] = rollDice();
        }, 0);
    }
};
const handleResultChange = (newVal: number | null) => {
    if (!newVal) return;

    if (greater.value === null) {
        checkExactHit(newVal);
    } else {
        checkGreaterOrLesserHit(newVal);
    }
};
const checkExactHit = (value: number) => {
    if (value === 9) {
        awardPrize();
    } else {
        missed.value = true
        savePrize()
    }
};

const checkGreaterOrLesserHit = (value: number) => {
    const condition = greater.value ? value > 10 : value < 10;
    if (condition) {
        awardPrize();
    } else {
        missed.value = true
        hit.value = 0
        savePrize()
    }
};

const stopRolling = (diceIndex) => {
    clearInterval(intervals.value[diceIndex]);
    if (diceIndex === dices.value.length - 1) {
        result.value = dices.value.reduce((sum, val) => sum + val, 0);
        rolling.value = false;
    }
};
const awardPrize = () => {
    const prize = prizes.find(ob => ob.try === count.value);
    hit.value = prize?.hit;
    savePrize();

};
const savePrize = async () => {
    const params = {
        task_id: props.taskId,
        params: {
            prize: hit.value,
            try_flag: 1,
        }
    }
    await api.put('/task_update_prize', params)
    // closeWithUpdate(false)

}
const gotChange = async () => {
    const options = {
        answers: [{ label: 'OK', value: true }]
    };
    const answer = await ask(`アタリ${hit.value}円`, options);
    if (answer.value) {
        setTimeout(doubleUp, 1000);
    }
};

const doubleUp = async () => {
    const options = {
        answers: [
            { label: '挑戦する', value: true },
            { label: '挑戦しない', value: false }
        ]
    };
    const answer = await ask('ダブルアップに挑戦しますか？　（アタリ×２倍　ハズレ×０倍）', options);
    if (answer.value) {
        setTimeout(startRolling, 500);
    } else {
        savePrize()
        emit('close', false)
    }
};

const closeWithUpdate = (flag: boolean) => {
    emit('close', flag)
    // updateTryFlag()  
}
watch(() => result.value, (newVal) => {
    handleResultChange(newVal);
});


</script>

<style>
.nine-answer-button {
    background-color: var(--primary-button);
    color: #ffffff;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 13px;
    transition: transform 0.1s;
}

.dice-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    width: fit-content;
    height: fit-content;
    padding: 10px 20px 0;
    flex-direction: column;
}

.dice-container {
    display: flex;
    justify-content: space-between;
    width: fit-content;
    gap: 30px;
}

.dice {
    width: 45px;
    height: 45px;
    background-color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 20px;
    border-radius: 5px;
    border: 2px solid #000;
    color: #000;
}

.result {
    margin-top: 20px;
    line-height: normal;
    text-align: center;
    font-size: 13px;
}
</style>