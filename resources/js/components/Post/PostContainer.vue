<template>

    <div :key="postKey" class="post-root">
        <div class="post-header">
            <HamBurger/>
            <div class="post-search-wrap">
                <PostSearchBar className="newChatMemberSearch" :customPlaceHolder="`${appNameJp}検索`"/>
            </div>
            
        </div>
        <Transition name="modalFade">
                              
                <PostCreate 
                    v-if="create"
                    :key="componentKey" 
                    :formIs="formIs" 
                    :currentStatus="null" 
                    :editRecord="editRecord"
                    :sharedFrom="sharedFrom"
                    @postFinish="postFinish"
                    :filesToShare="filesToShare"  
                    :appName="appName"
                    :appNameJp="appNameJp"                  
                />
            
        </Transition> 
        
        <div class="post-container scrollable">
            <PostRecord 
                v-for="record in records"
                :record="record"
                :appName="appName"
                :appNameJp="appNameJp"  
                @setChargeTarget=" val => chargeTarget = val"
            />
                
            
        </div>
        <div :title="$t('createBoard')" id="boardCreate" class="createBoardButton fileNewButton" @click="newRecord">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>

        <router-link v-if="defaultListShow" :to="`/${appName}`" class="post-list-reset">一覧表示に戻す</router-link>
        <Transition name="modalFade">
            <Charge v-if="chargeTarget" @close="chargeTarget = null"/>
        </Transition>

    </div>
</template>
<script>
import HamBurger from '../Global/HamBurger.vue';
import PostRecord from './PostRecord.vue';
import PostCreate from './PostCreate.vue';
import PostSearchBar from './PostSearchBar.vue'
import Charge from './Charge.vue';

export default{
    data(){
        return{
            postList: [],
            create: false,
            componentKey: 0,
            formIs: '',
            editRecord: null,
            sharedFrom: null,
            filesToShare: null,
            postKey: 0,
            defaultListShow: false,
            chargeTarget: null

        }
    },
    watch:{
        '$route.query'(after){
            console.log('changed', after)
        }
    },
    computed:{
        records(){
            return this.postList && this.postList.length ? this.postList : []
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
                const id = this.$route.query.hasOwnProperty('id') && this.$route.query.id ? this.$route.query.id : null
                const search_tags = this.$route.query.hasOwnProperty('search_tags') && this.$route.query.search_tags ? this.$route.query.search_tags : null
                const query = {
                    id: id,
                    search_tags: search_tags
                }
                
                this.fetchPosts(query)
        }
        
        this.defaultListShow = Object.getOwnPropertyNames(this.$route.query).length ? true : false
        
    },
    mounted(){

    },
    components: {
        HamBurger, 
        PostRecord,
        PostCreate,
        PostSearchBar,
        Charge
    },
    methods:{
        postFinish(){
            this.create = false
        },
        newRecord(){
            this.formIs = 1
            this.create = true
        },
        fetchPosts(query){
            axios.post('/get_posts', {
                path: this.appName,
                query: query
                
            })
            .then(response => {
                this.postList = response.data
                this.postKey ++
            })
            .catch(error => {
                
            });
        }
    }
}
</script>
<style>
.post-root{
    width: 100%;
    background: var(--bg2);
    height: 100%;
    overflow: hidden;
    position: relative;
}
.post-container{
    width: 100%;
    height: calc(100% - 60px);
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.post-header{
    width: 100%;
    height: 60px;
    display: flex;
    align-items: center;
}
.post-search-wrap{
    width: 30%;
}
.post-list-reset{
    background: var(--primary-color);
    color: var(--background-color);
    padding: 5px 15px;
    text-align: center;
    margin: auto;
    border-radius: 13px;
    font-size: 12px;
    line-height: 2;
    position: absolute;
    bottom: 30px;
    left: 0;
    right: 0;
    width: fit-content;
    cursor: pointer;
}
.post-list-reset:hover{
    text-decoration: none !important;
    color: var(--background-color) !important;
    font-weight: unset !important;

}
</style>