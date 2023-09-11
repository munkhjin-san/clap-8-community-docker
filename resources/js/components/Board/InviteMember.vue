
<template>
    <div @mousedown="closeModal" class="overlay" style="z-index: 31;font-size:14px">
        <Transition name="slidePop">
            <div v-if="copied" class="copySuccess" style="position: fixed;">    
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 38 32" fill="#fff">
                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                </svg>
                <span>{{ $t('copied') }}</span>
            </div>
        </Transition>
        <div id="inviteModal" class="chatCreate" ref="inviteModal" @mousedown.stop>   
            <Transition name="modalFade">
                <div v-if="enlarge" class="largeQr">
                    <img :src="`${$store.state.baseLocation}/chat_qr/${item.q_token}_${item.id}.png`"/>
                    <button @click="enlarge = false" class="commentEditButton" style="height:32px;margin-top: 15px">{{$t('back')}}</button>
                </div>        
            </Transition>
            <div>
                <div style="display:flex;align-items: center;">
                    <div style="font-size: 14px;margin-right: 40px;line-height: 1.5;margin-bottom: 20px;" v-html="headTitle"></div>
                    <div @click="$emit('close')" class="m-close-button">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>                
            </div>
            <div v-if="item.able_join == 2" class="warn-window" v-html="$t('unableToinviteMembersByQr')"></div>
            <div v-else class="invite-by-public-method">
                <div v-if="item.q_token" style="display: flex;gap:10px;width: fit-content;">
                    
                    <img @click="enlarge = true" style="width:80px;height:80px;margin:auto" :src="`${$store.state.baseLocation}/chat_qr/${item.q_token}_${item.id}.png`"/>
                    <div style="margin-left: 10px;">
                        <p class="exp-text">{{$t('QrInviteExplaination')}}</p>
                        <button @click="enlarge = true" class="commentEditButton" style="height:32px;margin-top: 15px">{{$t('enlarge')}}</button>
                    </div>
                    
                    
                </div>
                <div style="width:1px;height:60%;margin:auto 20px;border-right:solid thin var(--formBorder)"></div>
                <div style="position:relative">
                    <p class="exp-text" style="margin-bottom: 15px;">{{$t('urlInviteExplaination')}}</p>
                    <div style="display:flex">
                        
                        <input style="height: 30px;padding: 0 10px;color:inherit" disabled class="recordText" type="text" :value="`${$store.state.baseLocation}/join?token=${item.q_token}&id=${item.id}`"/>
                        <button @click="copyUrl" class="commentEditButton" style="height:32px;">{{$t('copy')}}</button>
                    </div>
                </div>
                
            </div>
            <!-- <p v-if="item.able_join !== 2" style="line-height: 1.5;width: 80%;" class="exp-text">{{ $t('inviteDetailedExplaination') }}</p> -->
            <span v-if="item.able_join !== 2" style="margin-top: 15px ;">{{$t('or')}}</span>
            <div style="margin: 15px 0">
                <PostSearchBar className="newChatMemberSearch" :customPlaceHolder="$t('searchAcrossMyNembersAndPublicUsers')" :searching="searching" @searchStart="searchStart"/>
            </div>            
            <div style="height: -webkit-fill-available;user-select: none;overflow: hidden auto;">
                    <div v-if="friendList.length">
                        <div style="font-weight: 600;margin-bottom: 15px;">{{ $t('myMembers') }}</div>
                        <div class="suggested-list">
                            <div :key="friend.id" v-for="friend in friendList">
                                <div @click="selectToUser(friend)" class="suggested-wrap">
                                    <UserIcon :user="friend" imgClass="userNormalIcon" size="30"/>
                                    <div class="suggested-user-name">{{ friend.name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="suggestedList.length">
                        <div style="font-weight: 600;margin: 15px 0;">{{ $t('publicUsers') }}</div>
                        <div class="suggested-list">
                            <div :key="friend.id" v-for="friend in suggestedList">
                                <div @click="selectToUser(friend)" class="suggested-wrap">
                                    <UserIcon :user="friend" imgClass="userNormalIcon" size="30"/>
                                    <div class="suggested-user-name">{{ friend.name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</template>

<script>
import PostSearchBar from '../Post/PostSearchBar.vue'
import { nextTick } from 'vue'
    export default {
        props: ['item'],
        emits: ['close'],
        data(){
            return{
                possibleMemberList: [],
                keyword: '',
                searching: 0,
                enlarge: false,
                lock: false,
                copied: false
            }
        },
        components:{
            PostSearchBar
        },
        mounted() {
            this.getMembers();
        },
        computed:{
            headTitle(){
                return `<strong>"${this.item.title}"</strong> ${this.$t('addMembersTitle')}`
            },
            excludeList(){
                return this.item.board_to_users ? this.item.board_to_users.map( ob => ob.user_id) : []
            },
            friendList(){
                return this.possibleMemberList && this.possibleMemberList.friends && this.possibleMemberList.friends.length ? this.possibleMemberList.friends : []
            },
            suggestedList(){
                return this.possibleMemberList && this.possibleMemberList.suggested && this.possibleMemberList.suggested.length ? this.possibleMemberList.suggested : []
            },
        },
        methods:{
            selectToUser(user){
                const name = user.name
                const uniqueChannell = Math.random().toString(36).substring(5);
                const question = 'addMemberConfirm'
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: this.$t(question, {userName: name}) ,
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                    channel: uniqueChannell
                })            
                emitter.on(uniqueChannell, (data) => { data.answer === this.$t('confirmToAction') ? this.selectToUserSend(user): false});
            },
            selectToUserSend(user){
                if(this.lock) return
                this.lock = true
                const formData = {
                    record_id : this.item.id,
                    user_id: user.id
                }
                axios.post('/group_add_member', formData)
                .then(response => {
                    if(response.status == 200){
                        this.$emit('reload');
                    }else{
                        this.errorToast(this.$t('commonError'))
                    }
                    this.lock = false
                    
                }).catch(function (error) {    
                    if (error.response) this.errorToast(this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)                
                    this.lock = false                         
                }.bind(this)); 
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
            searchStart(key){
                this.keyword = key
                this.getMembers()
                this.searching = 2
            },
            getMembers(){
                const key = this.keyword ? this.keyword : ''
                axios.post('/members_get_possible_member_list', {key: key, exc: this.excludeList}).then(               
                    response => {
                        if(response.data){
                            this.possibleMemberList = response.data

                            nextTick(() => {                                
                                this.searching = 0
                            })

                        }                    

                    });
            },
            closeModal(){
                if (!this.$refs.inviteModal.contains(event.target)) {
                    this.$emit('close')
                }
            },
            copyUrl(){
                const url = `${this.$store.state.baseLocation}/join?token=${this.item.q_token}&id=${this.item.id}`
                console.log(url)
                navigator.clipboard.writeText(url)
                .then(() => {
                    this.copied = true
                    setTimeout(() => {
                        this.copied = false
                    }, 1500);
                })
                
                .catch((error) => {
                    console.error('Unable to copy text to clipboard:', error);
                    
                });


            }       
            
        }
    }
</script>
<style lang="scss">
    .exp-text{
        font-size:14px;
        color: var(--primary-color);

    }
    .largeQr{
        background: var(--background-color);
        position:absolute;
        width: 100%;
        top:50px;
        height: calc(100% - 50px);
        left: 0;
        z-index: 4;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .warn-window{
        background: var(--bg2);
        padding: 10px;
        line-height: 1.5;
    }
    .invite-by-public-method{
        display: flex;
        width: -webkit-fill-available;
    }

    @media screen and (max-width: 959px) {
        .invite-by-public-method{
            flex-direction: column;
            gap: 10px;
        }
    }
</style>