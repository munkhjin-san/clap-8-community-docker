<template>

    <div class="post-root">
        <div class="post-header">
            <HamBurger v-if="$store.state.mobile"/>
            <div class="post-search-wrap">
                <PostSearchBar className="newChatMemberSearch" :customPlaceHolder="`${appNameJp}検索`" @focus="searchWindow = true"/>
            </div>
            
        </div>
        <Transition name="modalFade">
            <PostSearchWindow 
                v-if="searchWindow"
                :appName="appName"
                :appTitle="appNameJp"
                @closePostSearch="searchWindow = false"
            />
        </Transition> 
        <Transition name="modalFade">                              
            <PostCreate 
                v-if="create"
                :key="componentKey" 
                :formIs="formIs" 
                :currentStatus="null" 
                :editTarget="editTarget"
                :sharedFrom="sharedFrom"
                @postFinish="postFinish"
                :filesToShare="filesToShare"  
                :appName="appName"
                :appNameJp="appNameJp"                
            />
            
        </Transition> 
        
       
            <transition-group name="slidePop" tag="div" class="post-container scrollable" @scroll="scrollListen">
                <PostRecord 
                    v-for="record in records"
                    :key="record.id"
                    :record="record"
                    :appName="appName"
                    :appNameJp="appNameJp"  
                    @setChargeTarget=" val => chargeTarget = val"
                    @setCommentCount="setCommentCount"
                    @setClap="setClap"
                    @editRecord="editRecord"
                    @updateStatus="val => updateTarget = val"
                    @deleteRecord="deleteRecordConfirm"
                />
                    
            </transition-group>
      
        <div :title="$t('createBoard')" id="boardCreate" class="createBoardButton fileNewButton" @click="newRecord">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>

        <router-link v-if="defaultListShow" :to="`/${appName}`" class="post-list-reset">一覧表示に戻す</router-link>
        <Transition name="modalFade">
            <Charge 
                v-if="chargeTarget" 
                @close="closeCharge" 
                :chargeTarget="chargeTarget"
            />
        </Transition>
        <Transition name="modalFade">
            <Status 
                v-if="updateTarget" 
                :record="updateTarget"
                @close="closeStatus" 
            />
        </Transition>

    </div>
</template>
<script>
import HamBurger from '../Global/HamBurger.vue';
import PostRecord from './PostRecord.vue';
import PostCreate from './PostCreate.vue';
import PostSearchBar from './PostSearchBar.vue'
import Charge from './Charge.vue';
import Status from './Status.vue';
import PostSearchWindow from './PostSearchWindow.vue'

export default{
    data(){
        return{
            postList: [],
            create: false,
            componentKey: 0,
            formIs: '',
            sharedFrom: null,
            filesToShare: null,
            postKey: 0,
            defaultListShow: false,
            chargeTarget: null,
            editTarget: null,
            updateTarget: null,
            searchWindow: false

        }
    },
    computed:{
        records(){
            if(this.postList && this.postList.length){ 
                return this.postList    
            } 
            else{
                return []
            } 
        },
        appName(){
            return this.$route.name
        },
        appNameJp(){
            return this.appName == 'challenge' ? 'チャレンジ' : this.appName == 'knowledge' ? 'ナレッジ' : this.appName == 'nice' ? 'ナイス' : ''
        },
    },
    created(){
        if(this.$route.meta.data && this.$route.meta.data.length){
            this.postList = this.$route.meta.data;
        }else{
                
            const query = this.getQuery()
            this.fetchPosts(query, null)
        }
        
        this.defaultListShow = Object.getOwnPropertyNames(this.$route.query).length ? true : false
        
    },
    mounted(){
        setTimeout(() => {
            if(this.$route.name.includes('challenge') || this.$route.name.includes('knowledge') || this.$route.name.includes('nice')){
                this.updatePostBadge()
            }
            
        }, 2000);
        emitter.on('pusher-event', (e) => {
            // if(e.message.new_post_from && e.message.new_post_from !== this.$store.state.user && e.){
                
            // }
            const data = e && e.message && e.message.new_post_from ? e.message : null
            if(data && data.new_post_from !== this.$store.state.user.id && data.app_name == this.appName && data.record_id && !this.defaultListShow){
                const query = {
                    id: data.record_id,
                    search_tags: null
                }
                this.fetchPosts(query, data.record_id)
            }
        });
        if(this.$store.state.sharingData){
            this.newRecord()
        }
    },
    components: {
        HamBurger, 
        PostRecord,
        PostCreate,
        PostSearchBar,
        Charge,
        Status,
        PostSearchWindow
    },
    methods:{
        deleteRecordConfirm(record){
            var uniqueChannell = Math.random().toString(36).substring(5);   
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: `${this.appNameJp}を削除しますか。`,
                closeButton: false, 
                autoClose: false,
                answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                channel: uniqueChannell

            })            
            emitter.on(uniqueChannell, (data) => { data.answer === this.$t('confirmToAction') ? this.postDelete(record): false});
        },
        postDelete(record){
            axios.post('/delete_post', {
                path: this.appName,
                id: record.id
            })
            .then(response => {
                this.postList = this.postList.filter(ob => ob.id !== response.data)
                
                const data = {
                    text: '削除しました。',
                    channel: Math.random().toString(36).substring(5),
                    icon: 0,
                    view: true
                }
                emitter.emit('setInfo', data)

            })
            .catch(error => {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError') + error.message)      
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
        updatePostBadge(){
            axios.patch('/update_post_badge', {which: this.appName}).then( response => { this.$store.commit('setPostBadge', response.data) });
        },
        scrollListen(){
            var percent = 100 * event.currentTarget.scrollTop / (event.currentTarget.scrollHeight - event.currentTarget.clientHeight);  
            if(percent > 99){          
                if (this.infiniteLoader){
                    return;
                }                       
                this.infiniteLoader = true;
                let query = this.getQuery()
                this.fetchPosts(query)                                   
            }
        },
        closeStatus(id){
            this.updateTarget = false
            if(id){
                let query = this.getQuery()
                if(!query.hasOwnProperty('id') || !query.id){
                    query['id'] = id
                }
                this.fetchPosts(query, id)
            }
        },
        editRecord(record){
            this.editTarget = record
            this.create = true
        },
        closeCharge(id){
            this.chargeTarget = null
            let query = this.getQuery()
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id
            }
            this.fetchPosts(query, id)
        },
        getQuery(){
            const id = this.$route.query.hasOwnProperty('id') && this.$route.query.id ? this.$route.query.id : null
            const search_tags = this.$route.query.hasOwnProperty('search_tags') && this.$route.query.search_tags ? this.$route.query.search_tags : null
            const query = {
                id: id,
                search_tags: search_tags
            }
            return query
        },
        postFinish(flag, id){
            this.create = false
            this.editTarget = null
            console.log('flag',flag)
            if(flag && id){
                const query = {
                    id: id,
                    search_tags: null
                }
                this.fetchPosts(query, id)
            }
        },
        newRecord(){
            this.formIs = 1
            this.create = true
        },
        fetchPosts(query, replace){
            axios.post('/get_posts', {
                path: this.appName,
                query: query,
                skip: this.postList.length
                
            })
            .then(response => {
                if(replace ){
                    const index = this.postList.findIndex(ob => ob.id == replace)
                    if(index > -1){
                        this.postList[index] = response.data[0]
                    }else{
                        this.postList.unshift(response.data[0])
                    }
                }else{
                    this.postList.push(...response.data);
                }
                setTimeout(() => {
                    this.infiniteLoader = false
                }, 300);
            })
            .catch(error => {
                
            });
        },
        setCommentCount(num, id){
            const index = this.postList.findIndex(item => item.id === id);
            if(index > -1){
                this.postList[index].comments_count = num
            }
        },
        setClap(val, id){
            if(id){
                let query = this.getQuery()
                if(!query.hasOwnProperty('id') || !query.id){
                    query['id'] = id
                }
                this.fetchPosts(query, id)
            }
        },
    }
}
</script>
<style>

</style>