<template>
    <div class="signature-wrapper" style="align-items: center;">
        <div style="display:flex; justify-content:center;background: #efefef;" v-if="imgData">
            <img :src="imgData" style="border: 1px solid var(--formBorder);box-sizing: border-box;">
        </div>
        <div v-else id="canvasWrapper">
            <div style="position: relative;">
                <div v-if="showOptions" class="lineOptions">
                    <div class="lineOption" @click="selectLineWidth(1)" :class="{ selected: selectedLineWidth === 1 }">
                        <div class="line" style="border-bottom: 1px solid black;"></div>
                    </div>
                    <div class="lineOption" @click="selectLineWidth(2)" :class="{ selected: selectedLineWidth === 2 }">
                        <div class="line" style="border-bottom: 2px solid black;"></div>
                    </div>
                    <div class="lineOption" @click="selectLineWidth(3)" :class="{ selected: selectedLineWidth === 3 }">
                        <div class="line" style="border-bottom: 3px solid black;"></div>
                    </div>
                </div>
                <div style="font-size:16px;z-index:1;line-height:30px;">ここにサインしてください</div>
                <canvas ref="signature" class="canvasClass" style="background: #efefef; z-index:1;border:1px dotted var(--formBorder);">
                </canvas>
                <div style="margin-left: auto; margin-top:10px;position: absolute;top: 30px;right: 10px;" v-if="!imgData">
                    <button class="commentEditButton cursor-pointer" style="margin-right:5px;" type="button" @click="toggleOptions">
                        ペンの幅                 
                    </button>
                    
                    <button class="commentEditButton cursor-pointer" style="margin-right:5px;" type="button" @click="clear">元に戻す</button>
                    <button class="commentEditButton cursor-pointer" style="margin-right:5px;" type="button" @click="reset">リセット</button>
                </div>
            </div>
        </div>
        
        <LoaderButton style="margin-top:30px; margin-bottom:30px;" @triggered="imgData ? signChange() : signSave()" :loading="loader" :content="imgData ? '変更する' : 'サイン保存する'"/>
    </div>
</template>
<script setup>
import SignaturePad from 'signature_pad'
import LoaderButton from '../../Global/LoaderButton.vue'
import { inject, onMounted, ref } from 'vue';
import { useResponsive } from '@/store/responsive';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
    const responsive = useResponsive()
    const props = defineProps(['user'])
    const emit = defineEmits(['reload'])
    const signature = ref(null)
    const signaturePad = ref(null)
    const imgData = ref(null)
    const loader = ref(false)
    const showOptions = ref(false)
    const selectedLineWidth = ref(1)
    const api = useApi()
    const { ping } = useDialog()
    onMounted(() => {
        showSettingMySign()
    })
    
    const selectLineWidth = (width) => {
        signaturePad.value.maxWidth = width;
        showOptions.value = false; 
    }
    const toggleOptions = () => {
        showOptions.value = !showOptions.value;
    }
    const showSettingMySign = () => {
        if(props.user.sign_path){
            imgData.value = '/cdn/user_signatures/' + props.user.id + '_' + props.user.sign_path + '.png'
        }else{
            canvasCreate()
        }
    }
    const canvasCreate = () => {
        setTimeout(() =>{
            const canvas = signature.value;
            const w = responsive.mobile ? window.innerWidth - 35 :  window.innerWidth * 0.5 
            signaturePad.value = new SignaturePad(canvas)
            signaturePad.value.maxWidth = 2
            if(responsive.mobile){
                canvas.width = w
                canvas.height = 300
            }else{
                canvas.width = w
                canvas.height = 400
            }
        })
    }
    const signChange = () => {
        imgData.value = null
        canvasCreate()
    }
    const signSave = async() => {
        if(!signaturePad.value.isEmpty()){
            const signImage = signaturePad.value.toDataURL();      
            await api.post('/save_user_signature', {sign: signImage}, {
                ask: 'このサインをマイサインとして保存しますか? \n保存するとマイサインを何度でも使用することができます。'
            })
            emit('reload')

        }else{
            ping('サインされていません。')
        }
    }
    const reset = () => {
        signaturePad.value.clear()
    }
    const clear = () => {
        var data = signaturePad.value.toData();
        if (data) {
            data.pop(); // remove the last dot or line
            signaturePad.value.fromData(data);
        }            
    }
</script>
<style>
.darkIcon{
    filter: invert(0.8);
}
.lineOptions {
    position: absolute;
    top: 0;
    margin-bottom: 10px; 
    display: flex;
    flex-direction: column;
    background-color: white;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 1;
    height: fit-content;
    top: 75px;
    right: 130px;
    left: auto;
  }
  
  .lineOption {
    display: flex;
    align-items: center; /* Center the line vertically */
    padding: 10px;
    cursor: pointer;
    width: 100px;
  }
  
  .lineOption:hover {
    background-color: #f0f0f0;
  }
  
  .lineOption .line {
    flex-grow: 1;
  }
  
  .lineOption.selected .line {
    background-color: black; /* Highlight the selected line */
  }
  .signature-wrapper{
    position:relative;
    height: 100%;
    display: flex;
    flex-direction: column;
  }
</style>