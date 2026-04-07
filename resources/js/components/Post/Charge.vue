<template>
<div class="overlay" @mousedown="closeChargeModal()">          
    <div class="chatCreate" style="height:auto;max-width: 70%;" @mousedown.stop>            
        <div class="recordFormTitle" style="display:flex">
            <p>チャージする</p>
                <div class="cursor-pointer" @click="closeChargeModal()" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div class="sm" style="display:inline-flex;margin:0 auto;margin: 30px 0px auto auto;line-height: 30px;font-size: 14px;margin-bottom: 10px;">
                <span>チャージ可能金額:</span>
                
                <span style="display: flex;align-items: center;margin-left: 5px;" v-if="!fetched && possibleAmount == null">
                    <div id="loaderMicro" style="margin:0 5px;width: fit-content;">
                        <div class="spinner-micro" style="border: 3px var(--primary-color) solid;border-top: 3px transparent solid;width:15px;height:15px;"></div>
                    </div>  
                </span>
                <span v-else>{{`${possibleAmount}円`}}</span>
            </div>
            
            <div :class="['form-wrapper', {focused: value.length || charge_bet || focus}]">

            
                <span style="z-index: 5;" class="form-plc">チャージ金額を選択</span> 
                <drop-selector
                    @input="value = $event.target.value"
                    :class="['taskUserSelecArea']"   
                    style="background-image: unset; margin:0px;width: 100%;border: 1px solid var(--primary-color);" 
                    v-model="charge_bet" 
                    name="charge" 
                    :options="chargeOptions"
                    inputId="chargeSelector"
                    @search:focus="focus = true"
                    @search:blur="focus = false"
                > 
                    <template v-slot:no-options="{ search, searching }">                    
                        <div style="font-size: 13px;opacity: 0.5;padding: 10px 0">お探しのチャージ額は見つかりません。</div>
                    </template>
                </drop-selector> 
            </div>
            <div style="margin-top:20px">
                <LoaderButton 
                    :loading="chargeLock"
                    content="チャージする"
                    @triggered="challengeChargeBet"
                />
            </div>
            
        </div>
    </div>  
</template>

<script setup lang="ts">
import { ref } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue'
import { onMounted } from 'vue';
import { useApi } from '@/composables/api';
    const props = defineProps<{
        chargeTarget: number
    }>()
    const emit = defineEmits<{
        'close': [number | undefined]
    }>()
    const possibleAmount = ref<number>(0)
    const charge_bet = ref<{value: number, label: string} | null>(null)
    const chargeOptions = ref<{value: number, label: string}[]>([])
    const chargeLock = ref(false)
    const value = ref('')
    const fetched = ref(false)
    const focus = ref(false)
    const api = useApi()
    onMounted(() => {
        getMyCharge()
    })

    const closeChargeModal = (id?: number) => {
        emit('close', id)
    }
    const getMyCharge = async () => {
        const data = await api.get('/post_get_possible_charge')  
    
        possibleAmount.value = data  
        pushChargeSelect(possibleAmount.value)   
        fetched.value = true                          
       
    }
    const pushChargeSelect = (my_charge: number) => {
        var award_bit = my_charge/100;
        var charges: {label: string, value: number}[]= [];
        for (let step = 1; step < award_bit + 1; step++) {
            charges.push({ label : step * 100 + '円' , value : step * 100 });
        }
        chargeOptions.value = charges;

    }
    const challengeChargeBet = async() => {

        if(chargeLock.value|| !props.chargeTarget || !charge_bet.value || charge_bet.value.value == 0) return

        await api.post('/challenge_charge_to',{ charge_bet: charge_bet.value.value, record_id: props.chargeTarget }, {
            toast: 'チャージしました。',
            loadingRef: chargeLock,
        })

        closeChargeModal(props.chargeTarget)                 
    }
        
    
</script>
