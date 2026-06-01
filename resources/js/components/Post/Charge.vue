<template>
<Modal :loader="fetching" @close="closeChargeModal()" :custom-class="'!h-auto !w-[70%] max-w-[70%]'">
    <template #title>
        <p>チャージする</p>
    </template>
    <template #content>
        <div class="text-center my-8 text-[14px]">
            <span>チャージ可能金額</span>
            <span v-if="!fetched && possibleAmount == null" class="ml-1 inline-flex items-center">
                <div id="loaderMicro" class="mx-[5px] w-fit">
                    <div class="spinner-micro h-[15px] w-[15px] border-[3px] border-solid border-[var(--primary-color)] border-t-transparent"></div>
                </div>  
            </span>
            <span class="py-1 px-4 bg-[var(--bg3)] ml-1" v-else>{{amountOfMoneyParser(possibleAmount)}}円</span>
        </div>
        <p class="text-center text-[12px] text-[gray] my-3" v-if="isMini">ミニチャレンジのため、最大のチャージ額は500円までです</p>
        <div v-if="chargeQuickOptions.length" class="flex flex-wrap justify-center gap-3 mt-2" >
            <button
                v-for="option in chargeQuickOptions"
                :key="option.value"
                type="button"
                @click="selectQuickCharge(option)"
                :class="[
                    'border border-solid border-[var(--formBorder)] px-[15px] py-[5px] text-[13px] font-semibold',
                    charge_bet?.value === option.value
                        ? 'bg-[var(--primary-color)] !text-[var(--background-color)]'
                        : 'bg-transparent text-[var(--primary-color)]'
                ]"
            >
                {{ option.label }}
            </button>
        </div>
        <div v-if="maxChargeAmount >= minChargeAmount" class="mt-8">
            <div class="flex items-center justify-center gap-3">
                <button
                    type="button"
                    @click="stepCharge(-chargeStep)"
                    :disabled="chargeAmount <= minChargeAmount"
                    class="w-11 h-11 rounded-full border border-[var(--primary-color)] text-[24px] leading-none text-[var(--primary-color)] disabled:opacity-40"
                >
                    -
                </button>
                <div class="flex items-center justify-center bg-[var(--bg3)] px-5 py-3 min-w-[100px]">
                    <span class="mr-1 text-[18px] font-semibold">¥</span>
                    <input
                        name="charge-pick"
                        :value="chargeInput"
                        type="number"
                        :min="minChargeAmount"
                        :max="maxChargeAmount"
                        :step="chargeStep"
                        inputmode="numeric"
                        class="w-full bg-transparent text-center text-[18px] outline-none text-[var(--primary-color)]"
                        @input="handleChargeAmountInput"
                        @blur="syncChargeAmount"
                    >
                </div>
                <button
                    type="button"
                    @click="stepCharge(chargeStep)"
                    :disabled="chargeAmount >= maxChargeAmount"
                    class="w-11 h-11 rounded-full border border-[var(--primary-color)] text-[24px] leading-none text-[var(--primary-color)] disabled:opacity-40"
                >
                    +
                </button>
            </div>
            <div class="mt-3 text-center text-[13px] opacity-70">
                Min ¥{{ amountOfMoneyParser(minChargeAmount) }} / Max ¥{{ amountOfMoneyParser(maxChargeAmount) }}
            </div>
            <div v-if="chargeInputError" class="mt-2 text-center text-[11px] text-[tomato] absolute left-1/2 -translate-x-1/2">
                {{ chargeInputError }}
            </div>
        </div>
        <div class="si-box">
            <LoaderButton 
                :loading="chargeLock"
                content="チャージする"
                @triggered="challengeChargeBet"
            />
        </div>
    </template>
</Modal>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import Modal from '../Global/Modal.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import { onMounted } from 'vue';
import { useApi } from '@/composables/api';
import { amountOfMoneyParser } from '@/utils/tools';
    type ChargeOption = { value: number, label: string }
    const minChargeAmount = 100
    const chargeStep = 100
    const props = defineProps<{
        chargeTarget: number,
        isMini: boolean
    }>()
    const emit = defineEmits<{
        'close': [number | undefined]
    }>()
    const possibleAmount = ref<number>(0)
    const charge_bet = ref<ChargeOption | null>(null)
    const chargeLock = ref(false)
    const chargeInput = ref('')
    const inputTouched = ref(false)
    const fetched = ref(false)
    const fetching = ref(false)
    const api = useApi()
    onMounted(() => {
        getMyCharge()
    })

    const maxChargeAmount = computed(() => Math.min(props.isMini ? 500 : possibleAmount.value, 15000))
    const chargeAmount = computed(() => charge_bet.value?.value ?? 0)
    const numericChargeInput = computed(() => Number.parseInt(chargeInput.value, 10))

    const closeChargeModal = (id?: number) => {
        emit('close', id)
    }
    const getMyCharge = async () => {
        if (fetching.value) return
        fetching.value = true
        const data = await api.get('/post_get_possible_charge')  
    
        possibleAmount.value = data  
        const defaultChargeAmount = getDefaultChargeAmount(maxChargeAmount.value)
        if (defaultChargeAmount >= minChargeAmount) {
            setChargeAmount(defaultChargeAmount)
        }
        fetched.value = true        
        fetching.value = false                  
       
    }
    const challengeChargeBet = async() => {

        inputTouched.value = true

        if(chargeLock.value|| !props.chargeTarget || !charge_bet.value || charge_bet.value.value == 0) return

        await api.post('/challenge_charge_to',{ charge_bet: charge_bet.value.value, record_id: props.chargeTarget }, {
            toast: 'チャージしました。',
            loadingRef: chargeLock,
        })

        closeChargeModal(props.chargeTarget)                 
    }
    const buildQuickChargeOptions = (maximumOption: number): ChargeOption[] => {
        if (maximumOption < minChargeAmount) {
            return []
        }

        const preferredOptions = [100, 300, 500, 1000, 3000, 5000, 10000, 15000]
        const valuesBelowMax = preferredOptions.filter((amount) => amount < maximumOption)
        const quickValues = [...valuesBelowMax.slice(-4), maximumOption]

        const normalizedQuickValues = Array.from(new Set(quickValues))
            .filter((amount) => amount >= minChargeAmount && amount <= maximumOption)
            .sort((left, right) => left - right)

        const trimmedQuickValues = maximumOption >= 1000
            ? normalizedQuickValues.filter((amount) => amount !== 100)
            : normalizedQuickValues

        return trimmedQuickValues.map((amount) => ({
            label: `¥${amountOfMoneyParser(amount)}`,
            value: amount,
        }))
    }

    const getDefaultChargeAmount = (maximumOption: number) => {
        if (maximumOption < minChargeAmount) {
            return 0
        }

        return Math.min(3000, maximumOption)
    }

    const normalizeChargeAmount = (amount: number) => {
        if (maxChargeAmount.value < minChargeAmount) {
            return 0
        }

        const boundedAmount = Math.min(Math.max(amount, minChargeAmount), maxChargeAmount.value)
        const normalizedAmount = Math.round(boundedAmount / chargeStep) * chargeStep

        return Math.min(maxChargeAmount.value, Math.max(minChargeAmount, normalizedAmount))
    }

    const setChargeAmount = (amount: number) => {
        const normalizedAmount = normalizeChargeAmount(amount)

        if (!normalizedAmount) {
            charge_bet.value = null
            return
        }

        chargeInput.value = String(normalizedAmount)
        charge_bet.value = {
            label: `¥${amountOfMoneyParser(normalizedAmount)}`,
            value: normalizedAmount,
        }
    }

    const getChargeInputError = (rawAmount: string) => {
        if (!inputTouched.value) {
            return ''
        }

        if (!rawAmount.trim()) {
            return '金額を入力してください。'
        }

        if (!/^\d+$/.test(rawAmount.trim())) {
            return '有効な金額を入力してください。'
        }

        const amount = Number.parseInt(rawAmount, 10)

        if (amount < minChargeAmount || amount > maxChargeAmount.value) {
            return `¥${amountOfMoneyParser(minChargeAmount)}〜¥${amountOfMoneyParser(maxChargeAmount.value)}で入力してください。`
        }

        if (amount % chargeStep !== 0) {
            return `${chargeStep}円単位で入力してください。`
        }

        return ''
    }

    const chargeInputError = computed(() => getChargeInputError(chargeInput.value))

    const syncChargeSelectionFromInput = () => {
        if (chargeInputError.value) {
            charge_bet.value = null
            return
        }

        const amount = Number.parseInt(chargeInput.value, 10)

        if (Number.isNaN(amount)) {
            charge_bet.value = null
            return
        }

        charge_bet.value = {
            label: `¥${amountOfMoneyParser(amount)}`,
            value: amount,
        }
    }

    const chargeQuickOptions = computed(() => {
        const maximumOption = maxChargeAmount.value

        return buildQuickChargeOptions(maximumOption)
    })

    const selectQuickCharge = (option: ChargeOption) => {
        inputTouched.value = false
        setChargeAmount(option.value)
    }

    const stepCharge = (step: number) => {
        const baseAmount = Number.isNaN(numericChargeInput.value)
            ? (charge_bet.value?.value || getDefaultChargeAmount(maxChargeAmount.value))
            : numericChargeInput.value

        inputTouched.value = false
        setChargeAmount(baseAmount + step)
    }

    const handleChargeAmountInput = (event: Event) => {
        const target = event.target as HTMLInputElement
        inputTouched.value = true
        chargeInput.value = target.value
        syncChargeSelectionFromInput()
    }

    const syncChargeAmount = (event: Event) => {
        const target = event.target as HTMLInputElement
        inputTouched.value = true
        chargeInput.value = target.value

        if (!chargeInputError.value) {
            setChargeAmount(Number.parseInt(chargeInput.value, 10))
            inputTouched.value = false
            return
        }

        syncChargeSelectionFromInput()
    }
    
</script>
