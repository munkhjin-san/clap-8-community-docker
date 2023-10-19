<template>
    <div class="chatCreate scrollable" style="height: auto;">
        <div id="postCreateWindow">
            <div>
                <div class="officeFormHeader" style="display:flex;">
                    <p v-if="editFlag == false">新しい事務所を作成する</p>
                    <p v-if="editFlag == true">事務所を編集する</p>
                    <div class="cursor-pointer" @click="$emit('closeModal')" style="position:unset; margin:auto 0 auto auto;display:flex;">
                        <button v-if="editFlag" class="work-delete-button" @click.stop="deleteOffice">削除する</button>
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
                <div class="input-wrapper">
                    <span style="z-index: 1;background-color:var(--background-color);" class="form-plc smallPlc">事務所名</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                                <input class="recordText-office" v-model="office_name" type="text" name="office_name">
                                <span class="valid-error post-error"></span>
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span style="z-index: 1;background-color:var(--background-color);left:44px;" class="form-plc smallPlc">郵便番号</span> 

                    <div class="w-100">
                        <div class="input-inner-wrapper post-code">
                            <span class="post">〒</span>
                            <input class="recordText-address-post per20" v-model="code1" type="number" name="code1">
                            <span class="dash">-</span>
                            <input class="recordText-address-post per20" v-model="code2" type="number" name="code2">
                            <span class="valid-error post-error"></span>
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span style="z-index: 1;background-color:var(--background-color);" class="form-plc smallPlc">住所</span> 

                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-office" v-model="address" type="text" name="address">
                        </div>                    
                    </div>
                </div>                    
                <div class="input-wrapper mt-20">
                    <span style="z-index: 1;background-color:var(--background-color);" class="form-plc smallPlc">電話番号</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                                <input class="recordText-office" v-model="office_tel" type="text" name="office_tel">
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span style="z-index: 1;background-color:var(--background-color);" class="form-plc smallPlc">ファクス番号</span> 

                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-office" v-model="office_fax" type="text" name="office_fax">
                        </div>                    
                    </div>
                </div>
                <div v-if="editFlag" style="margin-top:30px;" class="l-button cursor-pointer" @click="editOfficeSend()" :disabled="processing">
                    <span v-if="!processing">保存する</span>
                    <div v-if="processing" id="loaderMini">
                        <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                    </div>
                </div>        
                <div v-else style="margin-top:30px;" class="l-button cursor-pointer" @click="officeAdd()" :disabled="processing">
                    <span v-if="!processing">保存する</span>
                    <div v-if="processing" id="loaderMini">
                        <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default{
        props: ['editFlag', 'editOfficeData'],
        data(){
            return {
                office_name: '',
                office_tel: '',
                office_fax: '',
                office_address: '',
                processing : false,
                code1: '',
                code2: '',
                address: '',
            }
        },
        methods: {
            officeAdd(){
                if (this.processing) return;
                this.processing = true;

                const params = {
                    office_name : this.office_name,
                    office_tel : this.office_tel,
                    office_fax : this.office_fax,
                    office_address : this.address,
                    office_code1 : this.code1,
                    office_code2 : this.code2
                }

                axios.post('/office_add', params).then(response => {
                    this.$emit('postFinish')
                    this.processing = false
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                    this.processing = false                      
                }.bind(this));

            },
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: false, 
                    autoClose: false,
                    answers: ['OK']
                })   
            },
            editOffice(){
                this.office_name = this.editOfficeData.name;
                this.office_tel = this.editOfficeData.tel;
                this.office_fax = this.editOfficeData.fax
                this.address = this.editOfficeData.address
                this.code1 = this.editOfficeData.post_code_1
                this.code2 = this.editOfficeData.post_code_2
            },
            editOfficeSend() {
                if (this.processing) return;
                this.processing = true;

                const params = {
                    office_id : this.editOfficeData.id,
                    office_name : this.office_name,
                    office_tel : this.office_tel,
                    office_fax : this.office_fax,
                    office_address : this.address,
                    office_code1 : this.code1,
                    office_code2 : this.code2
                }
                axios.post('/office_edit', params).then(response => {
                    this.$emit('postFinish')
                    this.processing = false
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                    this.processing = false                      
                }.bind(this));

            },
            deleteOffice(){
                const params = {
                    office_id : this.editOfficeData.id
                }
                const uniqueChannell = Math.random().toString(36).substring(5);
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: 'ワークグループを削除しますか?',
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                    channel: uniqueChannell

                })            
                emitter.on(uniqueChannell, (data) => { 
                    if(data.answer === this.$t('confirmToAction')){
                        axios.post('office_delete', params).then(
                            response => {
                                this.processing = false
                                this.$emit('postFinish')
                            }
                        ).catch(function (error) {
                            if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                            else if (error.request) this.errorToast('エラーが発生しました。')
                            else this.errorToast('エラーが発生しました。 ' + error.message)     
                        }.bind(this))
                    }
                })
            }
        },
        mounted(){
            if(this.editOfficeData){
                this.editOffice()
            }
        }
    }
</script>
<style scoped lang="scss">
.work-delete-button{
    width: auto; 
    height: 30px; 
    line-height: 30px; 
    background: var(--primary-button); 
    color: rgb(255, 255, 255); 
    cursor: pointer; font-size: 12px; 
    padding: 0px 8px;
    margin-right: 20px;
}
    .post-error{
        bottom: -12px !important;
    }
    #officeContent {
        z-index: 3;
        width: 80%;
        height: 80%;
        padding: 0px;
        background: #fff;
        -webkit-overflow-scrolling: touch;
        overflow:auto;
    }
    .officeFormHeader {
        width: 100%;
        height: auto;
        margin: 0 auto;
        margin-bottom: 30px;
        font-size: 17px;
    }
    .input-inner-wrapper{
        position: relative;
        width: 100%;
    }
    input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
    .input-wrapper{
        display: flex;
        flex-direction: row;
        align-items: center;
        position: relative;
      }
      .office.form-label{
        font-size:16px;
        width: 10%;
      }
      .post-code{
        display: flex;
        align-items: center;
      }
    
      .w-100{
        width:100%;
      }
      
      .post{
        font-size: 25px;
        margin-right: 10px;
      }
      .dash{
        font-size: 25px;
        margin: 0 10px;
      }
      .recordText-office {
        width: 100%;
        height: 40px;
        margin: 0 auto;
        padding: 0px;
        line-height: 48px;
        font-size: 16px;
        text-indent: 16px;
        border: 1px solid #ccc;
        box-sizing: border-box;
      }
      .recordText-office::placeholder, .recordText-address-post::placeholder{
        font-size:14px !important;
     } 
      .mt-20{
        margin-top: 20px;
      }
      .recordText-address-post.per20{
        width: 20%;
        padding: 0px;
        line-height: 48px;
        height: 40px;
        font-size: 16px;
        text-indent: 16px;
        border: 1px solid #ccc;
        box-sizing: border-box;
      }
      @media screen and (max-width: 959px){
        #officeContent{
            width: 90%;
        }
        .input-wrapper{
            display: block;
        }
        .officeFormHeader > p{
            font-size: 18px;
        }
        .input-inner-wrapper{
            margin-top: 10px;
        }
        .recordText-address-post.per20{
            width: 50%;
        }
        .post{
            font-size: 20px;
            margin-right: 0;
        }
        .dash{
            margin: 0;
        }
      }
</style>