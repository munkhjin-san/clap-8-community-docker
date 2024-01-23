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
                v-for="(record, index) in records"
                :key="`${record?.id}_${index}`"
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
      
        <div title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="newRecord">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>
        <Transition name="modalFade">
            <div v-if="infiniteLoader" style="position: absolute;left: 0;right: 0;bottom: 25px;margin: auto;width: fit-content;">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>
 
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
<script setup>
import HamBurger from '../Global/HamBurger.vue';
import PostRecord from './PostRecord.vue';
import PostCreate from './PostCreate.vue';
import PostSearchBar from './PostSearchBar.vue'
import Charge from './Charge.vue';
import Status from './Status.vue';
import PostSearchWindow from './PostSearchWindow.vue'
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router'
import { useStore } from 'vuex'
import { provide } from 'vue';

    const postList = ref([])
    const create = ref(false)
    const componentKey = ref(0)
    const formIs = ref('')
    const sharedFrom = ref(null)
    const filesToShare = ref(null)
    const defaultListShow = ref(false)
    const chargeTarget =  ref(null)
    const editTarget = ref(null)
    const updateTarget = ref(null)
    const searchWindow = ref(false)
    const route = useRoute()    
    const store = useStore()
    const infiniteLoader = ref(false)

    const records = computed(() =>{
        return postList.value && postList.value.length ? postList.value : []
    })
    const appName = computed(() => {
        return route.name
    })
    const appNameJp = computed(() => {
        return appName.value == 'challenge' ? 'チャレンジ' : appName.value == 'knowledge' ? 'ナレッジ' : appName.value == 'nice' ? 'ナイス' : ''
    })

    onMounted(() => {
        if(route.meta.data && route.meta.data.length){
            postList.value = route.meta.data;
        }else{
                
            const query = getQuery()
            fetchPosts(query, null)
        }
        
        defaultListShow.value = Object.getOwnPropertyNames(route.query).length ? true : false
        
    

        setTimeout(() => {
            if(route.name.includes('challenge') || route.name.includes('knowledge') || route.name.includes('nice')){
                updatePostBadge()
            }            
        }, 2000);
        emitter.on('pusher-event', (e) => {
            const data = e && e.message && e.message.new_post_from ? e.message : null
            if(data && data.new_post_from !== store.state.user.id && data.app_name == appName.value && data.record_id && !defaultListShow.value){
                const query = {
                    id: data.record_id,
                    search_tags: null
                }
                fetchPosts(query, data.record_id)
            }
        });
        if(store.state.sharingData){
            newRecord()
        }
    })

    const deleteRecordConfirm = (record) => {
        var uniqueChannell = Math.random().toString(36).substring(5);   
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: `${appName.valueJp}を削除しますか。`,
            closeButton: false, 
            autoClose: false,
            answers: ['はい','いいえ'],
            channel: uniqueChannell

        })            
        emitter.on(uniqueChannell, (data) => { data.answer === 'はい' ? postDelete(record): false});
    }
    const postDelete = (record) => {
        axios.post('/delete_post', {
            path: appName.value,
            id: record.id
        })
        .then(response => {
            postList.value = postList.value.filter(ob => ob.id !== response.data)
            
            const data = {
                text: '削除しました。',
                channel: Math.random().toString(36).substring(5),
                icon: 0,
                view: true
            }
            emitter.emit('setInfo', data)

        })
        .catch(error => {
            if (error.response) errorToast(error.response.data.message)
            else if (error.request) errorToast('エラーが発生しました。')
            else errorToast('エラーが発生しました。' + error.message)      
        });
    }
    const errorToast = (message) => {
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: message,
            closeButton: true, 
            autoClose: true,
            answers: ['はい']

        }) 
    }
    provide('errorToast', errorToast)
    const updatePostBadge = () => {
        axios.patch('/update_post_badge', {which: appName.value}).then( response => { store.commit('setPostBadge', response.data) });
    }
    const scrollListen = () => {
        var percent = 100 * event.currentTarget.scrollTop / (event.currentTarget.scrollHeight - event.currentTarget.clientHeight);  
        if(percent > 99){          
            if (infiniteLoader.value){
                return;
            }                       
            infiniteLoader.value = true;
            let query = getQuery()
            fetchPosts(query)                                   
        }
    }
    const closeStatus = (id) => {
        updateTarget.value = false
        if(id){
            let query = getQuery()
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id
            }
            fetchPosts(query, id)
        }
    }
    const editRecord = (record) => {
        editTarget.value = record
        create.value = true
    }
    const closeCharge = (id) => {
        chargeTarget.value = null
        if(id){                
            let query = getQuery()
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id
            }
            fetchPosts(query, id)
        }
        
    }
    const getQuery = () => {
        const id = route.query.hasOwnProperty('id') && route.query.id ? route.query.id : null
        const search_tags = route.query.hasOwnProperty('search_tags') && route.query.search_tags ? route.query.search_tags : null
        const query = {
            id: id,
            search_tags: search_tags
        }
        return query
    }
    const postFinish = (flag, id) => {
        create.value = false
        editTarget.value = null
        if(flag && id){
            const query = {
                id: id,
                search_tags: null
            }
            fetchPosts(query, id)
        }
    }
    const newRecord = () => {
        formIs.value = 1
        create.value = true
    }
    const fetchPosts = (query, replace) => {
        axios.post('/get_posts', {
            path: appName.value,
            query: query,
            skip: postList.value.length
            
        })
        .then(response => {
            if(replace ){
                const index = postList.value.findIndex(ob => ob.id == replace)
                if(index > -1){
                    postList.value[index] = response.data[0]
                }else{
                    postList.value.unshift(response.data[0])
                }
            }else{
                response.data.forEach((responseItem) => {
                    const existingPost = postList.value.find((post) => post.id === responseItem.id);
                    if (existingPost) {
                        Object.assign(existingPost, responseItem);
                    } else {
                        postList.value.push(responseItem);
                    }
                });
            }
            setTimeout(() => {
                infiniteLoader.value = false
            }, 500);
        })
        .catch(error => {
            
        });
    }
    const setCommentCount = (num, id) => {
        const index = postList.value.findIndex(item => item.id === id);
        if(index > -1){
            postList.value[index].comments_count = num
        }
    }
    const setClap = (val, id) => {
        if(id){
            let query = getQuery()
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id
            }
            fetchPosts(query, id)
        }
    }    

</script>