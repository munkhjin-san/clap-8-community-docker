<template>
<div id="mRw1" class="md-window" style="z-index:60;-webkit-transform: translate3d(0,0,0);transform: translate3d(0, 0, 0);">
    <div class="searchMessageArea" style="padding: 10px;">
        <div style="display:flex;height: 40px;min-height: 40px;line-height: 40px;">
            <p style="margin:0 20px;" class="copyareaTitle">{{appTitle}}検索</p>
            <div @click="closeMessageSearch" style="margin:0 0 0 auto;cursor:pointer;width:40px;height:40px;display:flex">
                <svg style="margin:auto" class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>
            </div>
        </div> 
        <div style="margin: 15px 0  ;display:flex;padding: 0 20px;position:relative" class="advancedSearchWindowContainer">
            <input 
                ref="postAdvancedSearch"
                @focus="searchFocus" 
                @blur="searchBlur" 
                @keyup.enter="triggerSearch" 
                type="search"
                spellcheck="false" 
                autocomplete="off" 
                autocorrect="off" 
                autocapitalize="off" 
                placeholder="キーワードを入力" 
                id="advancedSearchInputPost" 
                class="searchInputArea"
                @keyup="setKeyWord"
                @keydown="setSelected"
                style="border: solid thin var(--formBorder);padding:3px 10px;width:100%;color: var(--primary-color);"
            />
            <!-- <button @click="getPostSearch(1)" style="background:#000;color:#fff;padding:5px 20px;font-size:13px;position:relative;width:100px;min-width:100px;height:33px;white-space: nowrap;">
                <span v-if="!searchMiniLoader">検索</span>
                <div v-if="searchMiniLoader" id="loaderMicro" style="margin-top:0;">
                    <div class="spinner-micro" style="width: 15px;height: 15px;border: 4px #ffffff solid;border-top: 4px #000 solid;"></div>
                </div>
            </button> -->
            <LoaderButton @triggered="getPostSearch(1)" :loading="searchMiniLoader" content="検索"/>
            <Transition name="searchWindowToggle">
            <div id="historyWrapWindow" v-if="isFocusing" style="position: absolute;top: 32px;width: calc(100% - 170px);z-index: 5;">
                <SearchHistory 
                    @setKeyWordFromHistory="setKeyWordFromHistory"
                    v-if="searchHistory.length" 
                    :allHistoryData="searchHistory"
                    :selected="selectedHistory"
                />
            </div>
            </Transition>
        </div>
        <div>
            <div @scroll="tagInfinite" style="display:flex;font-size:12px;flex-wrap:wrap;margin:0 20px;max-height: 135px;overflow:hidden auto;transition: max-height 0.5s;height: fit-content;">
                <div class="tag-list-container">
                    <div @click="selectTag(tag)" :key="tag.id" v-for="tag in usedTags" class="tagListItem cell-tag" :class="{selectedTag : selectedTags.includes(tag.id)}">
                        <div>#{{sanitized(tag.text)}} ({{tag.occurrence}})</div>
                    </div>                
                </div>
            </div>
        </div>
        <div style="display:flex;margin: 10px 0;font-size:14px;padding: 0 20px;"> 
            <div v-if="fetchCount > 0">
                <p>検索結果：{{resultCount}}件</p>
            </div>          
            <div v-if="result.length" @click="orderChange" style="margin: auto 15px 0 auto;display:flex;cursor:pointer">
                <span style="margin-right: 5px;">{{resultSortDateReverse ? '古い順' : '新しい順'}}</span>
                <svg style="margin-top:3px;width: 6px;" class="userListArrow" version="1.1" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg> 
            </div>
        </div>
        
        <div @scroll="getAppend" id="searchScrollOn" class="post-search-result-window" v-if="result.length && !searchMiniLoader">
         
            <div @click="jumpToRecord(item)" :key="item.id" v-for="item in result" class="" style="padding: 15px;background: var(--background-color);">
                <div class="recordBox-inner">        
                    <div class="post-second-wrap" style="gap: 10px">
                        <div :class="['post-user-wrap', {'post-users-wrap' : isMultipleUsers(item)}]">
                            <div v-if="item.app_type == 2 || item.app_type == 3" style="display:flex;align-items: center;">
                                <UserIcon :disableInstant="true" :user="item.user" imgClass="userNormalIcon" size="30"/>
                                <p class="userName">{{ item.user ? item.user.name : '' }}</p>
                            </div>                
                            <div v-if="item.app_type == 4 || item.app_type == 3" style="position: relative;">
                                <div style="display: flex;align-items: center;">
                                    <svg v-if="item.app_type == 3" version="1.1" xmlns="http://www.w3.org/2000/svg" class="nice-arrow" viewBox="0 0 47 32" style="margin-right: 15px;">
                                        <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
                                    </svg>
                                    <div :ref="`to_users_${item.id}`" :class="['toUserListContainer']">
                                        <div :key="user.id" v-for="user in item.to_users" style="display: flex;align-items: center;">                                                             
                                            <UserIcon :disableInstant="true" size="30" :user="user" :imgClass="isMultipleUsers(item) ? 'toUsersIconSmall' : 'toUsersIcon'"/> 
                                            <p style="width: max-content;" class="userName">{{ user.name }}</p>                                       
                                        </div>                               
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <PostDate :record="item" dateClass="dateText"/> 
                        <div @click="updateStatus" v-if="appName == 'challenge'" style="font-size: 14px;margin-left: 10px;cursor:pointer">{{ status(item) }}</div>
                    </div> 

                    

                    <div v-if="appName == 'knowledge' || appName == 'nice'" class="recordContents" style="line-heigth:1.5;margin-top:0;font-size:14px;line-height: 1.8;margin-top:10px;">
                        <div class="recordContents-inner">        
                            <!--<read-more more-str="続きを表示する" :text="urlCheck(item.content)" link="#" less-str="閉じる" :max-chars="200"></read-more>-->
                            <p v-html="searchMessageBody(item.content)"></p>

                        </div>                                           
                    </div>
                    <div v-if="appName == 'challenge'">
                        <div class="recordContents" style="line-heigth:1.5;margin-top:0;font-size:14px;line-height: 1.8;margin-top:10px;">
                            <div class="recordContents-inner">        
                                <!--<read-more more-str="続きを表示する" :text="urlCheck(item.content_rule)" link="#" less-str="閉じる" :max-chars="200"></read-more>-->
                                <p v-html="searchMessageBody(item.content_rule)"></p>
                            </div>                                           
                        </div>
                        <div style="border-bottom: 1px dashed rgb(89, 86, 86); display: flex; width: 100%;">
                            <p style="margin: 0px auto -13px; font-size: 13px; padding: 5px 10px; background: rgb(255, 255, 255); height: fit-content; line-height: 1.2; border-radius: 5px;">達 成 条 件</p>
                        </div>
                        <div class="recordContents" style="line-heigth:1.5;margin-top:0;font-size:14px;line-height: 1.8;margin-top:10px;">
                            <div class="recordContents-inner">        
                                <!--<read-more more-str="続きを表示する" :text="urlCheck(item.content_goal)" link="#" less-str="閉じる" :max-chars="200"></read-more>-->
                                <p v-html="searchMessageBody(item.content_goal)"></p>
                            </div>                                           
                        </div>
                    </div>


                    <!-- <PostFiles v-if="item['files'].length" :items="item['files']"/>                        -->

                    <div class="recordReferrer" v-if="item.referrer">
                        <div class="recordReferrer-inner">            
                            参照元 : <a target="_blank" :href="item.referrer">{{ referrerFilter(item.referrer) }}</a>            
                        </div>
                    </div>

                    <div class="recordTag" v-if="item['tags'].length">
                        <div class="recordTag-inner">            
                            <span class="tagTextSpan" :key="tag.id" v-for="tag in item['tags']">  
                                <a :style="{cursor:'pointer', background: selectedTags.includes(tag.id) ? 'yellow' : 'inherit', color:selectedTags.includes(tag.id) ? 'black' : 'auto'}">#{{ sanitized(tag.text) }}</a>
                            </span>
                        </div>
                    </div>
                </div>    
            </div>    
           
            
        </div>  
        <div v-if="fetchCount > 0 && !result.length" class="no-comment-text" style="font-size: 14px;top:40vh;padding: 0 50px;line-height:2"><p>検索結果はありません</p></div> 
        <PostSearchPager 
            v-if="fetchCount > 0 && result.length" 
            :key="pagerKey"
            :possiblePage="possiblePage" 
            :activePath="searchPageIndex" 
            @setActivePage="setActivePage"
            @setNavi="setNavi"
        />
    </div>
</div>
</template>

<script>
import moment from 'moment'
import Autolinker from 'autolinker';
import UserIcon from '../Board/Mixed/UserIcon.vue'
import PostSearchPager from './PostSearchPager.vue'
import SearchHistory from './SearchHistory.vue'
import PostDate from './PostDate.vue';
import LoaderButton from '../Global/LoaderButton.vue'
    export default {
        props: [
            'appName',
            'appTitle'
        ],
        data(){
            return{
                keyword: '',
                resultSortDateReverse: false,
                searchResult: [],
                searchMiniLoader: false,
                searchAppendLoader: false,
                fetchCount: 0,
                usedTags: [],
                selectedTags: [],
                searchPageIndex: 1,
                activePage: 1,
                possiblePage: 1,
                resultCount: 0,
                pagerKey: 200,
                searchHistory: [],
                isFocusing: false,
                timeout: 0,
                selectedHistory: -1,
                tagOffset: 1,
                infineLock: false,
                searchState: 'first'
            }
        },
        components:{
            UserIcon,
            PostSearchPager,
            SearchHistory,
            PostDate,
            LoaderButton
        },
        computed: {
            result(){
                return this.searchResult
                // if(this.searchResult && this.searchResult.data){
                //     return this.searchResult.data
                //     // if(this.resultSortDateReverse){
                //     //     return this.searchResult.data.sort((a,b) => (a.created_at > b.created_at) ? 1 : ((b.created_at > a.created_at) ? -1 : 0))                    
                //     // }else{
                //     //     return  this.searchResult.sort((a,b) => (a.created_at < b.created_at) ? 1 : ((b.created_at < a.created_at) ? -1 : 0))                    
                //     // }
                // }else {
                //     return [];
                // } 
                
            },
            suggestion(){
                if(this.fetchCount == 0){
                    return 'キーワードを入力して検索ボタンを押してください'
                }else if(!this.result.length){
                    return '検索結果はありません'
                }
            }

        },
        watch: {
            
        },
        unmounted(){
            window.removeEventListener('click', this.onClickSearch);
            window.removeEventListener('touchstart', this.onClickSearch);
            // window.removeEventListener('keydown', this.onSelectHistory);
        },
        mounted() {
            window.addEventListener('click', this.onClickSearch);
            window.addEventListener('touchstart', this.onClickSearch);
            // window.addEventListener('keydown', this.onSelectHistory);
            setTimeout(() =>{
                const el = document.getElementById('searchScrollOn')
                // disableBodyScroll(el)
                // document.body.style.height = '100%';
                // document.body.style.width = '100%';
                // document.body.style.position = 'fixed';
                // document.body.style.overflow = 'hidden';
            },0)
            this.getFeaturedTags([], 'first')
            // this.getSearchHistory()
            this.$refs['postAdvancedSearch'].focus()
        },
        watch:{
            keyword(after, before){
                if(this.selectedTags.length == 0 && !after){
                    this.resetSearch()
                };            
            },
            // selectedTags(after, before){
            //     if(!after.length && !this.keyword){
            //         this.resetSearch()
            //     }
            // }
        },
        methods:{ 
            sanitized(text){
                const sanitizedString = text ? text.replace(/#|♯|＃/g, '') : '';
                return sanitizedString;
            },
            isMultipleUsers(item){
                return this.$store.state.mobile && item && item.to_users && item.to_users.length > 1
            },
            status(item){
                if(item.app_type == 4){
                    var todayDate = (moment().format("YYYY-MM-DD"));
                                        
                    if(todayDate <= item.date_end && item.status_flag == 0){
                        var statusText = '実施中';
                        return statusText;
                    }                
                    else if(item.status_flag == 1)
                    {
                        var statusText = '達成';
                        return statusText;
                    }
                    else if(item.status_flag == 2)
                    {
                        var statusText = '未達成';
                        return statusText;
                    } else if(item.status_flag == 3)
                    {
                        var statusText = '中止';
                        return statusText;
                    }
                    else if(todayDate > item.date_end){
                        var statusText = '結果待ち';
                        return statusText;
                    }
                }
            },
            tagInfinite(){
                var percent = 100 * event.currentTarget.scrollTop / (event.currentTarget.scrollHeight - event.currentTarget.clientHeight);   
                if(percent > 99 && !this.infineLock){
                    this.tagOffset ++;
                    this.getFeaturedTags(this.selectedTags, this.searchState)
                }
            },
            resetSearch(){
                
                this.selectedTags = [];
                this.searchResult = [];
                this.getFeaturedTags([], 'reset');
                this.fetchCount = 0;
            },
            onSelectHistory(){
                // if(this.isFocusing){    
                //     $('li').attr('tabindex', 0); 
                //     if(event.which === 38 || event.which === 40){
                //         event.preventDefault()
                //     }
                //     if(event.which === 38){
                //         this.selectedHistory = this.selectedHistory == 0 ? this.searchHistory.length - 1 : this.selectedHistory - 1
                //         setTimeout(() => { 
                //             $('li.selectedHistoryItem').focus() 
                //             const input = document.getElementById('advancedSearchInputPost')
                //             input.value = $('li.selectedHistoryItem').text()
                //             // this.keyword = $('li.selectedHistoryItem').text()
                //         },0)                         
                //     }
                //     if(event.which === 40){//dooshoo                        
                //         this.selectedHistory = this.selectedHistory == this.searchHistory.length - 1 ? 0 : this.selectedHistory + 1
                //         setTimeout(() => { 
                //             $('li.selectedHistoryItem').focus() 
                //             const input = document.getElementById('advancedSearchInputPost')
                //             input.value = $('li.selectedHistoryItem').text()
                //             // this.keyword = $('li.selectedHistoryItem').text()
                //         },0)                          
                //     } 
                // }
            },
            triggerSearch(){
                event.preventDefault()
                if(this.isFocusing && this.selectedHistory !== -1){
                    let input = document.getElementById('advancedSearchInputPost')
                    input.value = this.searchHistory[this.selectedHistory].content
                    this.getPostSearch(1)
                    this.isFocusing = false
                }else{
                    this.getPostSearch(1)
                }
                this.isFocusing = false
            },
            onClickSearch(){
                const el = document.getElementById('historyWrapWindow')
                const input = document.getElementById('advancedSearchInputPost')
                if(el && input && !el.contains(event.target) && !input.contains(event.target) && this.isFocusing){
                    this.isFocusing = false
                }

            }, 
            autoFillDebounce(val) {
                if (this.timeout) clearTimeout(this.timeout)
                this.timeout = setTimeout(() => {
                    this.getSearchHistory()
                }, 300)
            },
            setSelected(){
                if(event.which === 27){
                    this.isFocusing = false;
                    this.selectedHistory = -1;
                    document.getElementById('advancedSearchInputPost').value = '';
                    document.getElementById('advancedSearchInputPost').blur();
                    this.keyword = '',
                    this.searchHistory = []
                    return
                } 
                if(event.which === 38 || event.which === 40){
                    event.preventDefault()
                    
                    if(this.isFocusing){
                        if(event.which === 38){
                            this.selectedHistory = this.selectedHistory == 0 ? this.searchHistory.length - 1 : this.selectedHistory - 1
                            // setTimeout(() => { 
                            //     // $('li.selectedHistoryItem').focus() 
                            //     // const input = document.getElementById('advancedSearchInputPost')
                            //     // input.value = $('li.selectedHistoryItem').text()
                            //     // this.keyword = $('li.selectedHistoryItem').text()
                            // },0)                         
                        }
                        if(event.which === 40){//dooshoo                        
                            this.selectedHistory = this.selectedHistory == this.searchHistory.length - 1 ? 0 : this.selectedHistory + 1
                            // setTimeout(() => { 
                            //     $('li.selectedHistoryItem').focus() 
                            //     const input = document.getElementById('advancedSearchInputPost')
                            //     input.value = $('li.selectedHistoryItem').text()
                            //     // this.keyword = $('li.selectedHistoryItem').text()
                            // },0)                          
                        } 
                    }
                    
                }
            },
            setKeyWord(){
                
                if(event.which === 38 || event.which === 40 || event.which === 13){
                    event.preventDefault()
                    
                    return
                    
                }
                else{
                    this.keyword = event.currentTarget.value
                    this.autoFillDebounce()
                }
                
            },
            setKeyWordFromHistory(val){
                const input = document.getElementById('advancedSearchInputPost')
                input.value = val
                this.keyword = val
                this.isFocusing = false
                this.getPostSearch(1)
            },
            searchFocus(){
                this.isFocusing = true
                this.getSearchHistory()
            },
            searchBlur(){
                // setTimeout(() => {
                //     this.isFocusing = false
                // },100)
                
            },
            getSearchHistory(){
                const inputSearch = document.getElementById('advancedSearchInputPost')
                const text = inputSearch.value
                axios.post('/get_history', {key: text}).then(response => {       
                    this.searchHistory = response.data
                }).catch(function (error) {

                }.bind(this)).then(() => {
                    this.selectedHistory = -1
                });
            },
            setActivePage(page){
                this.searchPageIndex = page
                this.getPostSearch(this.searchPageIndex)
            }, 
            setNavi(val){
                this.searchPageIndex = this.searchPageIndex + val
                this.getPostSearch(this.searchPageIndex)
            },
            getAppend(){
                
            },
            orderChange(){
                this.resultSortDateReverse = !this.resultSortDateReverse
                this.getPostSearch(1)
            },
            selectTag(tag){
                if(this.selectedTags.includes(tag.id)){
                    // this.selectedTags.splice(this.selectedTags.findIndex(item => item == tag.id), 1)
                    const current = this.selectedTags
                    // this.selectedTags = [];
                    this.usedTags = []
                    this.selectedTags = current;
                    const newList = current.filter(ob => ob !== tag.id)
                    this.selectedTags = newList
                }else{
                    this.usedTags = []
                    this.selectedTags.push(tag.id)
                } 
                
                this.getPostSearch(1)
                if(!this.selectedTags.length){
                    this.getFeaturedTags([], 'first')
                }
            }, 
            getFeaturedTags(tags, sub_param){
                // const tagIds = tags.map(ob => ob.id)
                this.infineLock = true
                const inputSearch = document.getElementById('advancedSearchInputPost')
                const text = inputSearch.value
                var keyList = text.split(/[\u{20}\u{3000}]/u); 
                axios.post('/get_featured_tags',{
                    app_name: this.appName, 
                    tags: tags, 
                    offset: this.tagOffset,
                    key_list: keyList,
                    pattern: sub_param
                }).then(response => {  
                    this.usedTags = response.data      
                    if(!this.usedTags.length){
                        this.selectedTags = [];
                    } 
            
                }).catch(function (error) {

                }.bind(this)).then(() => {
                    this.infineLock = false
                    this.searchState = sub_param
                });
            },
            referrerFilter(link){
                var str_lenght = link.length;
                if (str_lenght > 45) {
                    var sliced = link.slice(0, 45) + " ...";
                    return sliced;
                }
                return link;
            },  
            momentMessage (date) {
                moment.locale('ja');  
                
                return moment(date).isSame(moment(), 'day') ? 
                moment(date).format('HH:mm') : 
                moment(date).isSame(moment(), 'year') ? 
                moment(date).format('M / D (dd) HH:mm') : 
                moment(date).format('YYYY / M / D (dd) HH:mm')                       
            },
            closeMessageSearch(){
                window.removeEventListener('touchstart', this.onClickSearch);
                window.removeEventListener('click', this.onClickSearch);
                this.$emit('closePostSearch')
            },
            searchMessageBody(text){                
                const a = text.replace(this.keyword, "<span style='background: yellow;color:var(--primary-button)'>" + this.keyword + "</span>");           
                let r = this.urlCheck(a);                
                return r
            },
            urlCheck: function (text) {
                if(text){                
                    var linkedText = Autolinker.link(text, {stripPrefix: false});              
                    return linkedText;                
                }            
            },
            getPostSearch(index){
                this.searchPageIndex = index
                const inputSearch = document.getElementById('advancedSearchInputPost')
                if(inputSearch){
                    inputSearch.blur()
                }
                const text = inputSearch.value
                if(this.searchMiniLoader || (!this.selectedTags.length && !text) || this.searchAppendLoader) return
                
                
                this.searchMiniLoader = true
                this.searchAppendLoader = true
                var keyList = text.split(/[\u{20}\u{3000}]/u);   
                const params = {
                    app_name: this.appName,
                    key_list: keyList,
                    tags: this.selectedTags,
                    order: this.resultSortDateReverse ? 'asc' : 'desc',
                    key_word : text
                }
                axios.post('/post_advanced_search?page=' + index, params).then(response => {  
                    this.searchResult = response.data.data
                    this.possiblePage = response.data.last_page
                    this.resultCount = response.data.total
                    // if(this.selectedTags.length){
                        this.getFeaturedTags(this.selectedTags, 'afterSearch')
                    // }
            
                }).catch(function (error) {

                }.bind(this)).then(() => {
                    this.searchMiniLoader = false
                    this.searchAppendLoader = false 
                    this.fetchCount ++
                });
            },
            jumpToRecord(item){
                let appName;
                switch (true) {
                    case (item.app_type == 0):
                        appName = "home";
                        break;
                    case (item.app_type == 1):
                        appName = "board";
                        break;
                    case (item.app_type == 2):
                        appName = "knowledge";
                        break;
                    case (item.app_type == 3):
                        appName = "nice";
                        break;
                    case (item.app_type == 4):
                        appName = "challenge";
                        break;
                    default:
                        appName = null;
                }
                const url = '/app/public/' + appName + '?id=' + item.id;
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank'                    
                document.body.appendChild(link);            
                link.click();   
                link.remove();
            }
        }
    }
</script>
<style scoped lang="scss">
    .tag-list-container {
        display: flex;
        flex-wrap: wrap;
        place-content: flex-start;
        gap: 10px;
        width: 100%;
        //height: 135px;
    }

    .cell-tag-move {
        transition: transform 0.5s;
    }
    .post-search-result-window{
        height: -webkit-fill-available;
        overflow: hidden auto;
        font-size:13px;
        padding:15px;
        background:var(--bg2);
        margin:0px 20px 15px 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .tagListItem{
        padding: 6px 10px;
        border-radius: 5px;
        background: var(--kebab-bg1);
        color: var(--primary-color);
        cursor: pointer;
        user-select:none;
        height: fit-content;
    }
    .selectedTag{
        background: var(--primary-color);
        color: var(--background-color);
    }
    .tagTextSpan {
        display: inline-block;
        height: 30px;
        line-height: 30px;
        margin-right: 20px;
    }
    .tagTextSpan>a{
        text-decoration: none;
    }
    .recordContents-inner{
        white-space: break-spaces;
    }

</style>
