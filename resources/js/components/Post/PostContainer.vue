<template>
    <div class="post-root" v-if="auth.id == auth.activeUser.id">
        <div class="post-header">
            <HamBurger v-if="responsive.mobile"/>
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
        <div class="post-container scrollable" @scroll="scrollListen">
            <div v-if="hasQuery" style="height: auto;margin: 0 20px;display: flex;gap: 20px;">
                <div v-if="getQuery()?.member" class="active-query">
                    <div>{{ getQuery()?.member }}</div>
                    <div @click="router.push({name: appName})" style="cursor:pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width: 10px;height:10px" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div v-if="getQuery()?.search_tags" class="active-query"> 
                    <div>#{{ sanitized(getQuery()?.search_tags) }}</div>
                    <div @click="router.push({name: appName})" style="cursor:pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width: 10px;height:10px" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div v-else class="p-tag-container">
                <div v-if="tagLoading == 0" :class="['p-tag-wrap']">
                    <div class="tag-skeleton" :style="{width: randomWidth()}" :index="num" v-for="num in 30"></div>                    
                </div> 
                <div v-else :class="['p-tag-wrap', {'p-tag-expand' : topTags.expanded}]">
                    <router-link :to="`/${appName}?search_tags=${tag.text}`" class="jump-link" v-for="tag in topTags.tags">#{{ sanitized(tag.text) }} ({{ tag[`${appName}_occurence_count`] }})</router-link>
                </div>  
                  
                <div style="padding: 0px 20px 10px 20px;display: flex;justify-content: center;gap: 10px;align-items: center;" @click="topTags.setExpanded()">                                      
                    <div title="すべて表示する" class="selector-accordion-el">
                        <svg v-show="tagLoading > 0" class="dot-menu" version="1.1" width="11" height="11" :class="['selector-accordion-inactive' , {'selector-accordion-active' : topTags.expanded}]" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg>
                    </div>
                </div>          
            </div>
            <transition-group name="slidePop" tag="div" style="display: flex;flex-direction: column;gap: 20px;">
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
        </div>  
            

        
      
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
 
        <router-link v-if="hasQuery" :to="`/${appName}`" class="post-list-reset">一覧表示に戻す</router-link>
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
    <div v-else style="height: 100%;width: 100%;">
        <div v-if="responsive.mobile" style="min-height: 60px;display: flex;align-items: center">
            <HamBurger/>
        </div>        
        <div style="color:var(--primary-color);height: 100%;width: 100%;text-align: center;justify-content: center;display: flex;align-items: center;flex-direction: column;">
            <p>アクセス権限ありません。</p>
            <router-link class="l-button" style="margin: 30px 0 70px 0;" to="/board">ボードへ戻る</router-link>
        </div>        
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
import { computed, inject, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router'
import { provide } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import { useSharingDataStore } from '@/store/sharingData'
import { useBadgeStore } from '@/store/badge'
import { useTopTags } from '@/store/topTags'
    const badge = useBadgeStore()
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const { confirm, notify, info } = inject('dialog')
    const postList = ref([])
    const create = ref(false)
    const componentKey = ref(0)
    const formIs = ref('')
    const sharedFrom = ref(null)
    const filesToShare = ref(null)
    const hasQuery = ref(false)
    const chargeTarget =  ref(null)
    const editTarget = ref(null)
    const updateTarget = ref(null)
    const searchWindow = ref(false)
    const route = useRoute()    
    const router = useRouter()
    const infiniteLoader = ref(false)
    const tagsList = ref([])
    const tagLoading = ref(0)
    const topTags = useTopTags()
    const records = computed(() =>{
        return postList.value && postList.value.length ? postList.value : []
    })
    const appName = computed(() => {
        return route.name
    })
    const appNameJp = computed(() => {
        return appName.value == 'challenge' ? 'チャレンジ' : appName.value == 'knowledge' ? 'ナレッジ' : appName.value == 'nice' ? 'ナイス' : ''
    })
    const viewAccordian = ref(false)
    onMounted(() => {
        if(route.meta.data && route.meta.data.length){
            postList.value = route.meta.data;
        }else{
                
            const query = getQuery()
            fetchPosts(query, null)
        }
        
        hasQuery.value = Object.getOwnPropertyNames(route.query).length ? true : false
        
    

        setTimeout(() => {
            if(route.name.includes('challenge') || route.name.includes('knowledge') || route.name.includes('nice') && !auth.isPartner){
                badge.updatePostBadge(appName.value)
            }            
        }, 2000);
        if(sharingData.active){
            newRecord()
        }
        getTopTags()
    })
    const onPusher = (e) =>{
        console.log('yeee')
        const data = e && e.message && e.message.new_post_from ? e.message : null
        if(data && data.new_post_from !== auth.id && data.app_name == appName.value && data.record_id && !hasQuery.value){
            const query = {
                id: data.record_id,
                search_tags: null
            }
            fetchPosts(query, data.record_id)
        }
    }
    const getTopTags = async() => {
        // axios.get(`/get_top_tags?app_name=${appName.value}`).then( response => {
        //     tagsList.value = response.data
            
            
        // })
        if(topTags.appName == appName.value) {
            tagLoading.value ++
            return
        }
        await topTags.getTags({appName: appName.value, reset: true})
        setTimeout(() => {
            tagLoading.value ++
        }, 300);
    }
    const deleteRecordConfirm = async(record) => {
        const answer = await confirm(`${appNameJp.value}を削除しますか。`)
        if(!answer) return
        postDelete(record)
    }
    const postDelete = (record) => {
        axios.post('/delete_post', {
            path: appName.value,
            id: record.id
        })
        .then(response => {
            postList.value = postList.value.filter(ob => ob.id !== response.data)
            info('削除しました。')
        })
        .catch(error => {
            if (error.response) notify(error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。' + error.message)      
        });
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
        const search_member = route.query.hasOwnProperty('member') && route.query.member ? route.query.member : null
        const query = {
            id: id,
            search_tags: search_tags,
            member: search_member
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
            topTags.getTags({appName: appName.value, reset: false})
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
    const sanitized = (text) => {
        return text ? text.replace(/#|♯|＃/g, '') : '';
    }
    const randomWidth = () => {        
        const range = (3 - 1) / 0.2;
        const index = (Math.floor(Math.random() * range) * 0.2) + 1;
        return `${(Math.floor(Math.random() * (90 - 70 + 1)) + 70) * index}px`;

    }
    provide('postComment', {
        commentCount: (num, id) => setCommentCount(num, id)
    })

    defineExpose({onPusher})
</script>
<style scoped lang="scss">
.active-query{
    font-size: 14px;
    background: var(--background-color);
    color: var(--primary-color);
    padding: 10px 10px;
    width: fit-content;
    display: flex;
    gap: 15px;
    align-items: center;
}
.selector-accordion-inactive{
    transform: rotate(270deg);
    transition: transform 0.2s;
}
.selector-accordion-active{
    transform: rotate(90deg);
}
.selector-accordion-el{
    min-width: 35px;
    min-height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50px;    
    cursor: pointer;
}
.selector-accordion-el:hover{
    background: var(--bg3); 
}
.tag-skeleton{
    overflow: hidden;
    height: 18px;
    animation: pulse-bg 2s infinite;
    border-radius: 3px;
    
}
@keyframes pulse-bg {
    0% {
        background-color: var(--skItem1);
    }
    50% {
        background-color: var(--skItem2);
    }
    100% {
        background-color: var(--skItem1);
    }
}
</style>