<template>


        <div class="support-content">
            <div class="support-title">メール相談</div>
            <div class="support-content-inner">
                <div class="mail-content" style="margin-bottom:25px;line-height:2;">
                    <p>相談内容は経営管理本部担当者へ送信されます。</p>
                    <p>秘匿性の高い内容は、別途個別連絡時にご相談ください。</p>
                    <p>なお、メール相談窓口への回答は最大一週間程度お時間をいただく場合あります。</p>
                    <p>お急ぎの場合は、電話相談窓口までご連絡ください。</p>
                </div>
                <div>
                    <p style="margin-bottom: 10px;">相談種別</p>
                    <div v-for="answer in list" style="padding: 10px 0px;display: flex;">
                        <input class="fish-eye" v-model="selectedAnswer" type="radio" :id="answer.value" name="answer" :value="answer.value">
                        <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>  
                    </div> 
                </div>
                <div :key="updateKey" class="si-box">
                    <FormShortText
                        :initialValue="to"
                        ref="consultTo"
                        placeHolder="希望連絡先（必須）"
                        uId="consultTo"
                        name="consultTo"
                        rules="required|max:48"
                        label="タイトル"
                        @setValue="val => to = val"
                    />
                </div>
                <div :key="updateKey" class="si-box">
                    <FormLongText
                        :initialValue="content"  
                        ref="consultContent"
                        :placeHolder="`相談内容（必須）`"
                        uId="consultContent"
                        name="consultContent"
                        rules="required|max:2000"
                        label="メモ"
                        @setValue="val => content = val"
                    />   
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="send" content="送信する" :loading="sending"/>
                </div>
            </div>
           
        </div>


</template>
<script>
import FormShortText from '../Global/FormShortText.vue'
import FormLongText from '../Global/FormLongText.vue'
import LoaderButton from '../Global/LoaderButton.vue'
export default{
    data(){
        return{
            list: [
               { value: 0, content: ' 法務（コンプライアンス、情報管理、社内規定）'}, 
               { value: 1, content: ' 総務（社内施策、kintone）'},
               { value: 2, content: ' 会計（経費、生産関連、決算情報）'},
               { value: 3, content: ' 人事（人事制度、採用関連、人材紹介）'},
               { value: 4, content: ' 労務（給与関連、福利厚生、休職復職、安全衛生）'},
               { value: 5, content: ' 広報（ホームページ、社会活動、ブログ、SNS）'},
               { value: 6, content: ' 事業（企画開発、事業計画、事業実績）'},
               { value: 7, content: ' システム開発（CLAP）'},
               { value: 8, content: ' その他'}
                
            ],
            selectedAnswer: null,
            to: null,
            content: null,
            sending: false,
            updateKey: 0
        }
    },
    components:{
        FormLongText,
        FormShortText,
        LoaderButton
    },
    methods:{
        async validate(){
            try {                    
                let result = true
                let checkRef = ['consultTo', 'consultContent']
                for(const check of checkRef){
                    const exec = await this.$refs[check].$refs[check].validate()
                    result = result * exec.valid
                }              
                
                return result
            } catch (error) {
                console.error('Error fetching data:', error);
                throw error; // Re-throw the error to handle it further if needed
            }    
        },
        async send(){
            const valid = await this.validate()
            if(this.selectedAnswer == null || !valid) {
                if(this.selectedAnswer == null){
                    this.errorToast('相談種別を選択してください。')
                }
                return
            }
            this.sending = true
            const params = {
                kind_value: this.selectedAnswer,
                contact_address: this.to,
                consultation_content: this.content
            }
            axios.post('/support_add_consult',params)
            .then(response =>  {
                const data = {
                    text: '送信しました。',
                    channel: Math.random().toString(36).substring(5),
                    icon: 0,
                    view: true
                }   
                emitter.emit('setInfo', data)
                this.sending = false
                this.selectedAnswer = this.to = this.content = null
                this.updateKey++
            })
            .catch(function (error) {
                if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) this.errorToast('エラーが発生しました。')
                else this.errorToast('エラーが発生しました。 ' + error.message)      
                this.sending = false     
                          
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
            this.processing = false
            
        }, 
    }
}
</script>
