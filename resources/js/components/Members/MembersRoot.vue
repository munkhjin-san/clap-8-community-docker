<template>
    <div id="memberRootContainer" ref="memberParent" class="memberRoot" @scroll="scrollListen">
        <div class="mem-header-section" ref="memberHeader">
            
            <div>
                <!-- <PostSearchBar :searching="searching" @searchStart="searchStart"/> -->
                <div class="mem-search-area">
                    <HamBurger/>
                    <div class="searchBarInner memberSearchBar">   
                        <input @input="setKeyWord" v-model="keyword" class="searchBarArea searchInputArea memberSearch" :placeholder="$t('searchInMembers')" type="text" style="margin: 0;width:100%;"/>
                        <div style="position: absolute;left: 9px;top:0;display: flex;height: 30px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="margin: 7px auto auto auto;fill:#767676">
                                <path d="M31.875 28.185c-0.034-0.444-0.159-0.888-0.376-1.275-0.102-0.194-0.239-0.387-0.387-0.547-0.171-0.194-0.239-0.251-0.342-0.353-0.752-0.752-1.526-1.492-2.278-2.232-0.387-0.376-0.763-0.74-1.15-1.116l-0.865-0.831-0.091-0.091c-0.034-0.034-0.080-0.068-0.125-0.102-0.080-0.068-0.171-0.137-0.262-0.194-0.729-0.49-1.651-0.626-2.471-0.376-0.148 0.046-0.285 0.091-0.421 0.159-0.068 0.034-0.148 0.023-0.205-0.034-0.251-0.262-0.854-0.9-1.139-1.207-0.057-0.068-0.068-0.159-0.011-0.228 0.717-0.911 1.275-1.902 1.697-2.938 0.592-1.469 0.888-3.029 0.888-4.589s-0.296-3.12-0.888-4.601c-0.592-1.469-1.492-2.847-2.676-4.043-1.173-1.196-2.54-2.095-4.009-2.688-1.469-0.604-3.029-0.9-4.589-0.9-1.549 0-3.109 0.296-4.578 0.9-1.469 0.592-2.847 1.492-4.031 2.688-1.184 1.184-2.084 2.562-2.676 4.031s-0.888 3.041-0.888 4.601 0.296 3.12 0.888 4.589c0.592 1.469 1.492 2.847 2.676 4.043s2.562 2.084 4.031 2.688c1.469 0.604 3.029 0.9 4.589 0.9s3.12-0.296 4.578-0.9c1.036-0.421 2.038-1.002 2.949-1.72 0.046-0.034 0.114-0.034 0.159 0.011 0.273 0.273 1.002 0.957 1.253 1.196 0.034 0.034 0.046 0.091 0.023 0.137-0.205 0.444-0.307 0.945-0.285 1.446 0.023 0.421 0.137 0.854 0.342 1.23 0.102 0.194 0.228 0.376 0.364 0.535 0.171 0.194 0.228 0.251 0.33 0.353 0.74 0.774 1.469 1.549 2.209 2.3l1.116 1.15 0.558 0.569 0.376 0.376c0.034 0.034 0.080 0.080 0.125 0.114 0.080 0.068 0.171 0.137 0.262 0.205 0.74 0.512 1.708 0.683 2.574 0.444 0.433-0.114 0.843-0.319 1.196-0.615 0.046-0.034 0.091-0.068 0.125-0.114l0.114-0.102 0.421-0.421c0.319-0.319 0.558-0.706 0.717-1.127s0.216-0.877 0.182-1.321zM15.795 21.159c-1.15 0.467-2.391 0.706-3.621 0.706s-2.46-0.239-3.621-0.706c-1.15-0.467-2.243-1.173-3.177-2.118-0.945-0.945-1.64-2.027-2.118-3.189-0.467-1.162-0.706-2.403-0.706-3.633 0-1.241 0.239-2.471 0.706-3.633s1.173-2.243 2.118-3.189c0.945-0.957 2.027-1.651 3.189-2.13 1.15-0.467 2.38-0.706 3.621-0.706 1.23 0 2.46 0.239 3.621 0.706 1.15 0.467 2.232 1.173 3.177 2.118v0c0.945 0.945 1.64 2.027 2.118 3.189 0.467 1.162 0.706 2.403 0.706 3.633 0 1.241-0.239 2.471-0.706 3.633s-1.173 2.243-2.118 3.189c-0.957 0.957-2.038 1.663-3.189 2.13zM29.153 28.823l-0.478 0.478c-0.057 0.057-0.137 0.091-0.216 0.114-0.159 0.046-0.342 0.011-0.478-0.080-0.011-0.011-0.034-0.023-0.046-0.034l-0.068-0.068-0.285-0.273-1.708-1.674c-0.763-0.752-1.526-1.48-2.3-2.221-0.239-0.239-0.251-0.239-0.319-0.342-0.057-0.080-0.091-0.182-0.102-0.285-0.034-0.205 0.046-0.433 0.182-0.592 0.125-0.159 0.364-0.399 0.558-0.535 0.273-0.194 0.604-0.125 0.797 0.068s1.697 1.754 2.061 2.141c0.74 0.763 1.48 1.537 2.232 2.289 0.239 0.239 0.239 0.239 0.285 0.33 0.034 0.068 0.057 0.159 0.068 0.239 0.011 0.159-0.057 0.319-0.182 0.444z"></path>
                            </svg>
                        </div>
                        <div @click="cancelSearch" v-if="keyword.length && searching == 0" style="min-width:30px;min-height:28px;display:flex;position: absolute;right: 1px;cursor:pointer;background: var(--background-color);z-index: 3;">
                            <svg class="smallCancelButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 32 32" style="margin: auto;">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>
                        </div>
                        <div v-if="searching > 0" style="min-width:30px;min-height:30px;display:flex;position: absolute;right: 0;cursor:pointer;z-index: 1;">
                            <div style="margin:auto;" class="spinner-nano"></div>
                        </div>
                        <div v-if="!keyword" class="searchBarCancelButton" @click="startQrReader" style="right: 0;">
                            <svg style="margin: auto;fill:#767676" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30">
                                <path d="M9.827.09C8.792.088 6.701.095 5.667.097L1.506.136C.769.154.155.756.136 1.506L.013 5.663.118 9.827c.124 1.432 2.149 1.5 2.363.071l.353-4.15.134-2.845 4.855-.271 2.076-.125C11.45 2.356 11.385.146 9.827.09m.071 27.429l-4.151-.354-2.845-.133-.271-4.855-.123-2.077c-.151-1.552-2.363-1.485-2.418.073l.009 4.161.037 4.16c.02.737.621 1.349 1.371 1.37l4.157.122 4.164-.104c1.43-.124 1.497-2.151.07-2.363m20.088-3.183l-.103-4.163c-.124-1.432-2.151-1.5-2.363-.072l-.354 4.15-.133 2.845-4.855.27-2.077.125c-1.553.15-1.487 2.361.072 2.417l4.161-.009 4.161-.036a1.42 1.42 0 0 0 1.37-1.371l.121-4.156"/>
                                <path d="M20.102 2.48l4.15.354 2.844.133.271 4.855.124 2.075c.15 1.553 2.362 1.487 2.418-.071l-.007-4.161-.038-4.16c-.019-.737-.62-1.349-1.37-1.37L24.337.013l-4.165.104c-1.431.124-1.499 2.15-.07 2.363m8.181 11.368l-9.933-.23c-5.438-.036-11.126.013-16.552.185-2.174.133-2.174 2.266 0 2.392 4.34.14 8.896.194 13.243.199 4.362-.015 8.898-.048 13.242-.243.817-.038 1.489-.49 1.546-1.073.063-.637-.629-1.186-1.546-1.23"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="memberHeader" style="display: flex;padding: 0 20px;">  
                
                
                <div @click="setSelector(2)" v-if="requestList.length" :class="{selectedTitle: selectedTitle == 2}" class="memberTitle">{{$t('friendRequests') }} ({{ requestList.length }})</div>        
                <div @click="setSelector(0)" :class="{selectedTitle: selectedTitle == 0}" class="memberTitle">{{$t('myMembers')}}</div>                      
                <div @click="setSelector(1)" :class="{selectedTitle: selectedTitle == 1}" class="memberTitle">{{$t('publicUsers')}}</div>                
            </div>
            
            
        </div>
        
        
        
        <div>

            <Transition name="slidePop">        
            <div v-if="targetTags && targetTags.length" style="margin: 0 20px;">
                <p>{{$t('searchResultOfTag')}}</p>
                <div class="searchTargetTags">
                    <div v-for="tag in targetTags">
                        <p>{{tag.name}}</p>
                        <button @click="releaseTag(tag)" style="width: 20px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32" class="smallCancelButton">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            </Transition>
            <div v-if="selectedTitle == 2 && requestList.length" style="margin: 0 20px 20px;">            
                <div>
                    <masonry-wall :items="requestList" :column-width="300" :gap="20" :scroll-container="$refs.memberParent">
                        <template #default="{ item, index }">
                            <MemberItem 
                                :key="`req_${item.id}`"
                                v-if="item && item.id"
                                :member="item.user"
                                :isFriend="false"
                                status="recieved_request"
                                @searhByTagTo="searhByTagTo"
                                @sendChatRequest="sendRequestConfirm"
                                @setMyMembers="setMyMembers"
                                @respondPartnerRequest="respondPartnerRequest"
                                @blockUser="blockUser"
                            />
                        </template>
                    </masonry-wall>
                </div>
            </div>
            <div v-if="selectedTitle == 0" style="margin: 0 20px;">            
                <div v-if="friendsList.length">
                    <masonry-wall :items="friendsList" :column-width="300" :gap="20" :scroll-container="$refs.memberParent">
                        <template #default="{ item, index }">
                            <MemberItem 
                                :key="item.id"
                                v-if="item && item.id"
                                :member="item"
                                :isFriend="true"
                                status="friends"
                                @searhByTagTo="searhByTagTo"
                                @sendChatRequest="sendRequestConfirm"
                                @setMyMembers="setMyMembers"
                                @blockUser="blockUser"
                            />
                        </template>
                    </masonry-wall>
                </div>
                <div class="no-comment-text" v-if="!friendsList.length && isSearchedWithKeyword">{{$t('noResultsFound')}}</div>
                <div class="no-comment-text" v-if="!friendsList.length && !isSearchedWithKeyword">{{$t('noMembersYet')}}</div>
            </div>



            <div  v-if="selectedTitle == 1" style="margin: 0 20px;">           
                <div v-if="publicUsersList.length">
                    <masonry-wall :items="publicUsersList" :column-width="300" :gap="20" :scroll-container="$refs.memberParent">
                        <template #default="{ item, index }">
                            <MemberItem 
                                :key="item.id"
                                v-if="item && item.id"
                                :member="item"
                                :isFriend="false"
                                status="public"
                                @searhByTagTo="searhByTagTo"
                                @sendChatRequest="sendRequestConfirm"
                                @setMyMembers="setMyMembers"
                                @blockUser="blockUser"
                            />
                        </template>
                    </masonry-wall>
                </div>
                <div class="no-comment-text" v-if="!publicUsersList.length">{{$t('noResultsFound')}}</div>
            </div>
           
        </div>
       
    </div>
</template>

<script>
import MemberItem from './MemberItem'
import MemberItemSkeleton from './MemberItemSkeleton'
import PostSearchBar from '../Post/PostSearchBar'
import HamBurger from '../Global/HamBurger.vue'
import { nextTick } from 'vue'
    export default {
        props: ['inviteUser'],
        data(){
            return{
                publicUsersList: [],
                currentData: [],
                listBefore: [],
                currentPage: 1,
                allPage: 1,
                index: 1,
                lock: false,
                targetTags: [],
                scrollPos: 0,
                inviteLock: false,
                membersList: [],
                searching: 0,
                keyword: '',
                scrollPosition: 0,
                searchHidden: false,
                selectedTitle: 0,
                timeout: 0,
                prevScrollPosition: 0,
                headerHidden: false,
                headerHeight: null,
                headerTop: '50px',
                scrollDelta: 0,
                scrollDirection: null,
                animationInProgress: false,
                isSearchedWithKeyword: false
            }
        },
        components:{
            MemberItem,
            PostSearchBar,
            MemberItemSkeleton,
            HamBurger
        },
        created(){
            const hd = document.getElementById('hd-bar')
            if(hd){
                hd.style.position = 'fixed'
                hd.style.zIndex = '12'
                hd.style.left = '0'
                hd.style.top = '0'
            }
            // const customLocale = localStorage.getItem('lang')
            // if(customLocale){
            //     this.$store.commit('setLocale', customLocale)
            //     this.$i18n.locale = customLocale
            // }else {
            //     const browserLang = navigator.language.substring(0, 2)
            //     if (browserLang === 'ja' || browserLang === 'mn') {
            //         this.$store.commit('setLocale', browserLang)
            //         this.$i18n.locale = browserLang
            //     } else {
            //         this.$store.commit('setLocale', 'en')
            //         this.$i18n.locale = 'en'
            //     }
            // }
            window.document.title = `GLOWD - ${this.$t('titleMembers')}`; 
        },
        mounted() {
            
            emitter.on('getFriends', () => this.getFriends());
            this.selectedTitle == 1 ? this.getMembers(1) : this.getFriends('mounted')           
            
            document.body.style.overflow = 'hidden';
            this.headerHeight = this.$refs.memberHeader.offsetHeight
        },
        computed:{
            friendsList(){
                return this.membersList && this.membersList.friends ? this.membersList.friends : []
            },
            requestList(){
                return this.membersList && this.membersList.requests ? this.membersList.requests : []
            },
            tagBoxMargin(){
                if(this.headerHidden){
                    return `50px 20px 0 20px`
                }else{
                    return this.$store.state.mobile ? '110px 20px 0 20px' : '60px 20px 0 20px'
                }
            },
            contentMargin() {    
                return this.headerHidden ? `${this.headerHeight}px` : this.$store.state.mobile ? '110px' : '60px'
            }
        },
        methods:{
            respondPartnerRequest(id, decision){
                if(this.inviteLock) return
                this.inviteLock = true
                axios.post('/respond_partner_request', {id: id, decision: decision}).then(response => {  
                    
                    this.inviteLock = false
                    
                    this.getFriends()
                    emitter.emit('getPartnerRequests', true)
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.inviteLock = false                
                }.bind(this));
            },
            blockUser(user){
                if(this.inviteLock) return
                this.inviteLock = true
                axios.post('/block_user', {id: user.id, block: true}).then(response => {  
                    this.inviteLock = false
                    
                    this.selectedTitle == 0 || this.selectedTitle == 2 ? this.getFriends() : this.getMembers(this.currentPage)
                    this.errorToast(this.$t(response.data.message))
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.inviteLock = false                
                }.bind(this));
            },
            startQrReader(){
                emitter.emit('scannerOn', true)
            },
            cancelSearch(){
                this.keyword = ''
                this.searchStart(this.keyword)
            },
            setKeyord(){
                this.keyword = event.target.value
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
            autoFillDebounce(val) {
                if (this.timeout) clearTimeout(this.timeout)
                this.timeout = setTimeout(() => {
                    this.searchStart(this.keyword)
                }, 300)
            },
            setSelector(val){
                this.selectedTitle = val
                val == 0 ? this.getFriends() : this.getMembers(1)
            },
            setMyMembers(user){
                if(this.inviteLock) return
                this.inviteLock = true
                axios.post('/set_member_link', {id: user.id, token: user.q_token}).then(response => {  
                    this.errorToast(this.$t(response.data.message))
                    this.inviteLock = false
                    
                    response.data.message === 'memberDetachSuccess' ? this.getFriends() : this.getMembers(this.currentPage)
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.loading = false  
                    this.inviteLock = false                
                }.bind(this));
            },
            sendRequestConfirm(user, message){
                if(this.inviteLock) return
                const params = {
                    id: user.id,
                    message:message
                }
                this.inviteLock = true
                axios.post('/members_chat_request', params).then(response => { 
                    // this.errorToast(this.$t('requestSent'))
                    if(response.data.message == 'restored' || response.data.message == 'created'){
                        this.errorToast(this.$t('requestSent'))  
                    }else if(response.data.message == 'exists'){
                        const uniqueChannell = Math.random().toString(36).substring(5);
                        emitter.emit('setToast', {
                            active: true,  
                            type: 'info', 
                            content: this.$t('chatIsExistsOpenIt'),
                            closeButton: false, 
                            autoClose: false,
                            answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                            channel: uniqueChannell

                        })
                        emitter.on(uniqueChannell, (data) => {                                        
                            if(data.answer == this.$t('confirmToAction')){    
                                                          
                                const url = `/chat/${response.data.id}`;
                                const link = document.createElement('a');
                                link.href = url;                   
                                document.body.appendChild(link);            
                                link.click();   
                                link.remove();
                            }                 
                        });
                    }
                    this.inviteLock = false
                    
                    
            
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    this.inviteLock = false
            
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
            scrollListen(){

                const container = event.target             
                const percent = 100 * container.scrollTop / (container.scrollHeight - container.clientHeight);    
                this.scrollPos = container.scrollTop
                if(percent > 90 && this.currentPage !== this.allPage && !this.lock){
                    this.getMembers(this.currentPage + 1)
                }
                
                // const currentScrollPosition = this.$refs.memberParent.scrollTop
                // if(currentScrollPosition == 0 || this.keyword.length){
                //     this.animationInProgress = true
                //     this.headerHidden = false
                //     this.animateHeader('down')
                    
                // }else{

                //     if(this.keyword.length){
                //         return
                //     }
                //     if (currentScrollPosition > this.prevScrollPosition) {
                //         this.scrollDirection = 'down'
                //         this.scrollDelta = currentScrollPosition - this.prevScrollPosition
                //     } else {
                //         this.scrollDirection = 'up'
                //         this.scrollDelta = this.prevScrollPosition - currentScrollPosition
                //     }
                //     if (this.scrollDirection === 'down' && !this.headerHidden && !this.animationInProgress) {
                //         if (this.scrollDelta > 15) {
                //             this.animationInProgress = true
                //             this.headerHidden = true
                //             this.animateHeader('up')
                //         }
                //     } else if (this.scrollDirection === 'up' && this.headerHidden && !this.animationInProgress) {
                //         if (this.scrollDelta > 30) {
                //             this.animationInProgress = true
                //             this.headerHidden = false
                //             this.animateHeader('down')
                //         }
                //     }
                // }
                // this.prevScrollPosition = currentScrollPosition   

            },
            animateHeader(direction) {
                const header = this.$refs.memberHeader
                const animationDuration = 0.3 // seconds
                const easing = 'cubic-bezier(0.4, 0.0, 0.2, 1)' // easing function

                // Set initial and target transform values based on scroll direction
                let initialTransform, targetTransform
                if (direction === 'up') {
                    initialTransform = 'translateY(0)'
                    targetTransform = `translateY(-${this.headerHeight + 50}px)`
                } else {
                    initialTransform = `translateY(-${this.headerHeight + 50}px)`
                    targetTransform = 'translateY(0)'
                }

                // Set initial transform and start animation
                header.style.transform = initialTransform
                header.style.transition = `transform ${animationDuration}s ${easing}`
                requestAnimationFrame(() => {
                    // Set target transform after a small delay to allow for initial transform to be applied
                    setTimeout(() => {
                    header.style.transform = targetTransform
                    }, 10)
                })

                // Clear animation styles and state after animation finishes
                setTimeout(() => {
                    header.style.transition = ''
                    header.style.transform = ''
                    this.animationInProgress = false
                }, animationDuration * 1000)
            },
            searchStart(key){

                this.currentData = []
                this.keyword = key
                this.getMembers(1)
                this.getFriends()
                this.searching = 2
                

            },
            getFriends(atr){
                const key = this.keyword ? this.keyword : ''
                const ids = this.targetTags && this.targetTags.length ? this.targetTags.map(ob => ob.id) : [];
                axios.post('/members_get_friends', {tag: ids, key: key}).then(response => {  
                    
                    this.membersList = response.data
                    nextTick(() => {                                
                        this.searching--                        
                    })
                    if(atr == 'mounted' && response.data && response.data.requests.length){
                        this.selectedTitle = 2
                    }
                    if(response.data && this.selectedTitle == 2 && !response.data.requests.length){
                        this.selectedTitle = 0
                    }
                    this.isSearchedWithKeyword = key ? true : false

            
                }).catch(function (error) {
                    this.searching--   
                }.bind(this));
            },
            getMembers(index){
                const ids = this.targetTags && this.targetTags.length ? this.targetTags.map(ob => ob.id) : [];
                this.lock = true
                const key = this.keyword ? this.keyword : ''
                axios.post(`/members_get_list?page=${index}`, {tag: ids, key: key}).then(               
                    response => {
                        if(response.data && response.data.data){
                            if(index == 1){
                                this.allPage = 1;
                                this.publicUsersList = [];
                                this.currentData = [];
                            }
                            this.allPage = response.data.last_page
                            this.currentPage = response.data.current_page
                            let list = [...this.currentData, ... response.data.data] 
                            if(this.allPage !== this.currentPage){
                                const columns = document.getElementsByClassName('masonry-column')
                            }                            
                            this.publicUsersList = list
                            this.currentData = this.publicUsersList
                            this.lock = false

                            nextTick(() => {                                
                                this.searching--
                            })

                        }                    

                    });
            },
            searhByTagTo(tag){
                const exist = this.targetTags.filter(ob => ob.id == tag.id).length
                if(!exist){
                    this.targetTags.push(tag)
                    this.currentData = []
                    this.getMembers(1)
                    this.getFriends()
                }
                
            },
            releaseTag(tag){
                this.targetTags.splice(this.targetTags.indexOf(tag), 1) 
                this.currentData = []
                this.getMembers(1);
                this.getFriends()
            }
        }
    }
</script>
<style lang="scss">

    .memberHeader{
        display: flex;
        height: 60px;
        align-items: center;
        position: sticky;
        top: -1px;
        background: var(--bg2);
        z-index: 5;
        place-content: flex-end;
    }
    .memberTitle{
        color: var(--primary-color);
        white-space: nowrap;
        height: 30px;
        border: solid thin transparent;
        padding: 0 15px;
        line-height: 30px;
        cursor: pointer;
        font-size: 12px;
        user-select: none;
    }
    .selectedTitle{
        background: var(--background-color);
        border: solid thin var(--normalBorder);
    }
    .memberListContainer{
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Set minimum width of 300px and auto-fit the columns */
        grid-template-rows: repeat(3, 1fr); /* Set 3 rows with equal height */
        grid-gap: 20px; /* Add a gap between each grid item */
        justify-items: center; /* Center items horizontally within each grid cell */
        align-items: center; /* Center items vertically within each grid cell */
    }
    .memberRoot{
        width:100%;
        background: var(--bg2);
        overflow:hidden auto;
        height: 100%;
        color:var(--primary-color);
        position: relative;
    }
    .searchTargetTags{
        display: flex;
        font-size: 13px;
        gap: 10px;
        flex-wrap: wrap;
        margin: 20px 0;
    }
    .searchTargetTags > div{
        padding: 0 0 0 8px;
        background-color: var(--background-color);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        height: 27px;

    }
    .mem-header-section{
        transition: transform 0.2s;
        display: flex;
        position: sticky;
        top: 0;
        width: 100%;
        background: var(--bg2);
        z-index: 3;
    }
    .mem-header-section > div {
        width: 50%;
    }
    .hiddenSearch {
        transform: translateY(calc(-100% - 50px));
    }
    .mem-search-area {
        width: 50%;
        height: 100%;
        display: flex;       
        align-items: center;
    }
    .memberSearchBar{
        width: 100%;
        width: 100%;
        height: fit-content;
        margin: auto;
    }
    @media screen and (max-width: 959px) {
        .mem-header-section{
            flex-direction: column;
        }
        .mem-header-section > div {
            width: 100%;
        }
        .mem-search-area{
            padding-top: 20px;
        }
        .mem-search-area{
            width: calc(100% - 20px);          
            margin: 0;       
        }
        .memberHeader{
            place-content: flex-start;
        }
    }
    </style>
