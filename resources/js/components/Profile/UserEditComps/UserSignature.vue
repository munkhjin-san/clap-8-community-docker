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
<script>
import SignaturePad from 'signature_pad'
import LoaderButton from '../../Global/LoaderButton.vue'
    export default{
        props: ['user'],
        components: {
            LoaderButton
        },
        data(){
            return {
                signaturePad: null,
                imgData: null,
                loader: false,
                showOptions: false,
                selectedLineWidth: 1
            }
        },
        mounted(){
            this.showSettingMySign()
        },
        methods: {
            selectLineWidth(width) {
                this.signaturePad.maxWidth = width;
                this.showOptions = false; 
            },
            toggleOptions() {
                this.showOptions = !this.showOptions;
            },
            showSettingMySign(){
                console.log(this.user)
                if(this.user.sign_path){
                    this.imgData = this.$store.state.baseLocation + '/content/user_signatures/' + this.user.id + '_' + this.user.sign_path + '.png'
                    console.log(this.imgData)
                }else{
                    this.canvasCreate()
                }
            },
            canvasCreate(){
                setTimeout(() =>{
                    const canvas = this.$refs.signature;
                    const w = this.$store.state.mobile ? window.innerWidth - 35 :  window.innerWidth * 0.5 
                    this.signaturePad = new SignaturePad(canvas)
                    this.signaturePad.maxWidth = 2
                    // const penColor = this.$store.state.dark ? '#fff' : '#000'
                    // this.signaturePad.penColor = penColor
                    if(this.$store.state.mobile){
                        canvas.width = w
                        canvas.height = 300
                    }else{
                        canvas.width = w
                        canvas.height = 400
                        
                    }
                })
                
            },
            signChange(){
                this.imgData = null
                this.canvasCreate()
            },
            signSave(){
                if(!this.signaturePad.isEmpty()){
                    // this.signaturePad.penColor = '#000'
                    const signImage = this.signaturePad.toDataURL();
                    const uniqueChannell = Math.random().toString(36).substring(5); 
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: 'このサインをマイサインとして保存しますか? \n保存するとマイサインを何度でも使用することができます。' ,
                        closeButton: false, 
                        autoClose: false,
                        answers: ['はい', 'いいえ'],
                        channel: uniqueChannell

                    })            
                    emitter.on(uniqueChannell, (data) => { 
                        if(data.answer === 'はい'){
                            axios.post('/save_user_signature', {sign: signImage})
                                .then(response => {
                                    this.$emit('reload')
                                }).catch((error) => {   
                                    emitter.emit('setToast', {
                                        active: true,  
                                        type: 'info', 
                                        content: 'ファイルアップロード中にエラーが発生しました。' ,
                                        closeButton: false, 
                                        autoClose: false,
                                        answers: ['OK'],
                                    }) 
                                })
                        }
                    });

                }else{
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: 'サインされていません。',
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK'],
                    }) 
                }
            },
            reset(){
                this.signaturePad.clear()
            },
            clear(){
                var data = this.signaturePad.toData();
                if (data) {
                    data.pop(); // remove the last dot or line
                    this.signaturePad.fromData(data);
                }            
            },
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