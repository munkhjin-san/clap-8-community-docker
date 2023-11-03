<template>
    <div class="support-content">
        <div class="support-title">メール相談（受信BOX）</div>
        <div class="support-content-inner">
            <table class="supportMailFormList-table">
                <thead>

                    <tr>
                        <th>氏名</th>
                        <th>問合せ日時</th>
                        <th>相談種別</th>
                        <th>希望連絡先</th>
                        <th>相談内容</th>
                        <th>ステータス</th>
                    </tr>

                </thead>

                <tbody>
                    <tr v-for="item in list" @click="select(item)">
                        <td>{{ item.user ? item.user.name : '' }}</td>
                        <td>{{ createdDate(item.created_at) }}</td>
                        <td>{{  type(item.kind_value) }}</td>
                        <td>{{  item.contact_address }}</td>
                        <td>                            
                            <p>{{  item.consultation_content && item.consultation_content.length > 20 ? `${item.consultation_content.slice(0, 20)}...` : item.consultation_content }}</p>
                            <div style="display: flex;align-items: center;gap: 3px;margin-top: 10px;" v-if="item.support_mail_responding_logs && item.support_mail_responding_logs.length">
                                <svg fill="var(--primary-color)" width="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 32">
                                    <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                </svg>
                                <div>{{ item.support_mail_responding_logs.length }}</div>
                            </div>
                        </td>
                        <td>{{  status(item.status_flag) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Transition name="modalFade">
        <div class="overlay" v-if="selectedItem" @mousedown="reset">
            <div class="chatCreate scrollable" @mousedown.stop>
                <div class="recordFormTitle" style="display:flex">                        
                    <div class="cursor-pointer" @click="reset" style="position:unset; margin:auto 0 auto auto">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
                <div style="font-size: 14px;line-height: 1.7;">
                    <div class="si-box">
                        <p>氏名</p>                    
                        <div>{{ selectedItem.user ? selectedItem.user.name : '' }}</div>
                    </div>
                    <div class="si-box">
                        <p>問合せ日時</p>
                        <div>{{ createdDate(selectedItem.created_at) }}</div>
                    </div>
                    <div class="si-box">
                        <p>相談種別</p>
                        <div>{{ type(selectedItem.kind_value) }}</div>
                    </div>
                    <div class="si-box">
                        <p>希望連絡先</p>
                        <div>{{selectedItem.contact_address }}</div>
                    </div>
                    <div class="si-box">
                        <p>相談内容</p>
                        <div>{{selectedItem.consultation_content}}</div>
                        <div style="background: var(--bg3);padding: 10px;margin-top: 10px;">
                            <div style="display: flex;align-items: center;justify-content: space-between;">
                                <p>メモ</p>
                                <div @click="viewNewMemo" class="commentEditButton">メモ追加</div>                                
                            </div>
                            <div v-if="addMemoWindow" style="background: inherit;margin-top: 10px;padding: 10px;">
                                <FormLongText
                                    :initialValue="newMemo"  
                                    ref="consultMemo"
                                    placeHolder="新しいメモ"
                                    uId="consultMemo"
                                    name="consultMemo"
                                    rules="max:2000"
                                    label="タイトル"
                                    @setValue="val => newMemo = val"
                                /> 
                                <div style="margin-top: 15px;">
                                    <LoaderButton content="保存する" :loading="sending" @triggered="sendMemo"/>
                                </div>
                            </div>
                            <div v-for="memo in selectedItem.support_mail_responding_logs">
                                <p>【{{ createdDate(memo.created_at) }} {{ memo.user ? memo.user.name : ''}}】 :  {{ memo.text }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="si-box">
                        <p>ステータス</p>
                        <div class="locale-selector" style="width: fit-content;margin-top: 15px;">
                            <select @change="setStatus" name="locales" v-model="newStatus" class="dropDownSelector cursor-pointer">
                                <option :value="0">{{ status(0) }}</option>
                                <option :value="1">{{ status(1)}}</option>
                                <option :value="2">{{ status(2) }}</option>
                            </select>
                        </div>  
                    </div>
                </div>

            </div>
        </div>
        </Transition>
    </div>

</template>
<script>
import moment from 'moment'
import FormLongText from '../Global/FormLongText.vue'
import LoaderButton from '../Global/LoaderButton.vue'
export default{
    data(){
        return{
            list: [],
            selectedItem:null,
            newStatus: 0,
            addMemoWindow: false,
            newMemo: '',
            sending: false
        }
        
    },
    mounted(){
        this.getRecievedConsults()
    },
    components:{
        FormLongText,
        LoaderButton
    },
    methods:{
        viewNewMemo(){
            this.addMemoWindow = !this.addMemoWindow
            if(this.addMemoWindow){
                setTimeout(() => {
                    const el = document.getElementById('consultMemo')
                    if(el){
                        el.scrollIntoView({behavior: 'smooth', block: 'center'})
                        el.focus()
                    }
                }, 0);
                
            }
        },
        setStatus(){
            const value = event.target.value

            axios.post('/update_consult_status', {
                record_id: this.selectedItem.id,
                value: value,                
            })
            .then(response => {                   
                
                const data = {
                    text: '更新しました。',
                    channel: Math.random().toString(36).substring(5),
                    icon: 0,
                    view: true
                }
                emitter.emit('setInfo', data)
                this.sending = false
                this.newMemo = '';
                this.addMemoWindow = false
                this.getRecievedConsults(this.selectedItem.id)

            })
            .catch(error => {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError') + error.message)   
                this.sending = false   
            });
        },
        sendMemo(){
            this.sending = true
            axios.post('/add_memo_to_consult', {
                record_id: this.selectedItem.id,
                text: this.newMemo,                
            })
            .then(response => {                   
                
                const data = {
                    text: '送信しました。',
                    channel: Math.random().toString(36).substring(5),
                    icon: 0,
                    view: true
                }
                emitter.emit('setInfo', data)
                this.sending = false
                this.newMemo = '';
                this.addMemoWindow = false
                this.getRecievedConsults(this.selectedItem.id)

            })
            .catch(error => {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError') + error.message)   
                this.sending = false   
            });
        },
        errorToast(message){
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: true, 
                autoClose: true,
                answers: [this.$t('confirmToAction')]

            }) 
        },
        select(item){
            this.selectedItem = item
            this.newStatus = item.status_flag
        },
        reset(){
            this.selectedItem = null
        },
        getRecievedConsults(id){
            axios.get('/get_recieved_consults' ).then(response => {
                this.list = response.data
                if(id){
                    const replaceData = this.list.find(ob => ob.id == id)
                    if(replaceData){
                        this.selectedItem = replaceData
                    }
                }
            })
        },
        createdDate(date){
            return moment(date).format('YYYY/M/D HH:mm')
        },
        type(value){
            const items = [ '法務','総務','会計','人事','労務','広報','事業','システム開発','その他','よくある質問未解決' ]
            const index = value == 99 ? 9 : value
            return items[index]
                    
        },
        status(val){
            const list = ['未対応','対応中','対応済']
            return list[val]
        }
    }
}
</script>
<style>
table.supportMailFormList-table{
    padding: 10px;
    width: 100%;
}   


table.supportMailFormList-table > thead > tr > th{
    vertical-align: middle;
    border: 1px solid #ccc;
    font-weight: 400;
    font-size: 14px;
    padding: 10px;

}
table.supportMailFormList-table > tbody > tr > td{
    font-size: 12px !important;
    border: 1px solid #ccc;
    font-weight: 400;
    font-size: 14px;
    padding: 10px;
    max-width: 150px; /* Adjust the value to your desired maximum width */
    overflow: hidden; /* This prevents content from overflowing */
    line-height: 1.5;
}
table.supportMailFormList-table > tbody tr:hover{
    background-color: var(--bg3) !important;
    cursor: pointer;
}
</style>