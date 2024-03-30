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
                    <ShortInput
                        :initialValue="to"
                        ref="consultTo"
                        placeHolder="希望連絡先（必須）"
                        name="consultTo"
                        rules="required|max:48"
                        label="タイトル"
                        v-model="to"
                    />
                </div>
                <div :key="updateKey" class="si-box">
                    <LongInput
                        :initialValue="content"  
                        ref="consultContent"
                        :placeHolder="`相談内容（必須）`"
                        name="consultContent"
                        rules="required|max:2000"
                        label="メモ"
                        v-model="content"
                    />   
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="send" content="送信する" :loading="sending"/>
                </div>
            </div>
           
        </div>


</template>
<script setup>
import LongInput from '../Form/LongInput.vue';
import ShortInput from '../Form/ShortInput.vue';
import LoaderButton from '../Global/LoaderButton.vue'
import { inject, ref } from 'vue';
    const list = ref([
               { value: 0, content: ' 法務（コンプライアンス、情報管理、社内規定）'}, 
               { value: 1, content: ' 総務（社内施策、kintone）'},
               { value: 2, content: ' 会計（経費、生産関連、決算情報）'},
               { value: 3, content: ' 人事（人事制度、採用関連、人材紹介）'},
               { value: 4, content: ' 労務（給与関連、福利厚生、休職復職、安全衛生）'},
               { value: 5, content: ' 広報（ホームページ、社会活動、ブログ、SNS）'},
               { value: 6, content: ' 事業（企画開発、事業計画、事業実績）'},
               { value: 7, content: ' システム開発（CLAP）'},
               { value: 8, content: ' その他'}
            ])
    const selectedAnswer = ref(null)
    const { notify, info } = inject('dialog')
    const to = ref(null)
    const content = ref(null)
    const sending = ref(false)
    const updateKey = ref(0)
    const consultTo = ref(null)
    const consultContent = ref(null)
    const validate = async() => {
        try {                    
            let result = true
            let checkRef = [consultTo.value, consultContent.value]
            for(const check of checkRef){
                const exec = await check.validate()
                result = result * exec.valid
            }              
            
            return result
        } catch (error) {
            notify('Error fetching data:', error)
        }    
    }
    const send = async() => {
        const valid = await validate()
        if(selectedAnswer.value == null || !valid) {
            if(selectedAnswer.value == null){
                notify('相談種別を選択してください。')
            }
            return
        }
        sending.value = true
        const params = {
            kind_value: selectedAnswer.value,
            contact_address: to.value,
            consultation_content: content.value
        }
        try{
            await axios.post('/support_add_consult',params) 
            info('送信しました。')
            selectedAnswer.value = to.value = content.value = null
            updateKey.value++
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
        } finally {
            sending.value = false
        }     
    }
        

</script>
