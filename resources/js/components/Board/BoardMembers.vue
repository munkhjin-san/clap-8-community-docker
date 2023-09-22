
<template>
    <div @mousedown="closeModal" class="overlay" style="z-index: 31;font-size:14px">
        <div id="createModal" class="chatCreate" ref="createModal" @mousedown.stop>
            <div>
                <div style="display:flex;align-items: center;margin-bottom: 20px;">
                    <div style="font-size: 14px;margin-right: 40px;line-height: 1.5;" v-html="headTitle"></div>
                    <div @click="$emit('close')" class="m-close-button">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div>                   
                    <PostSearchBar className="newChatMemberSearch" :searching="searching" @searchStart="searchStart"/>
                </div>
            </div>
            <div style="margin-top: 15px;height: -webkit-fill-available;overflow:hidden auto;">                
                <div style="height: -webkit-fill-available;user-select: none;">                    
                    <div v-if="filteredAdmins.length">
                        <div style="font-weight: 600;margin-bottom: 15px;">{{ $t('admins') }} ({{ filteredAdmins.length }})</div>
                        <div class="suggested-list">
                            <div :key="admin.id" v-for="admin in filteredAdmins">
                                <div class="suggested-wrap">
                                    <UserIcon :user="admin" imgClass="userNormalIcon" size="30"/>
                                    <router-link :to="`/profile/${admin.id}`" class="suggested-user-name user-link">{{ admin.name }}</router-link>
                                    <div v-if="checkAdminAccess" @click.stop="$store.commit('setMenu', {id: admin.id, name: 'adminBoxMenu'})" style="height:100%;width:40px;display:flex;position: absolute;right: 0;top: 0;">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" width="3" viewBox="0 0 7 32" style="margin: auto;">
                                            <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path><path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path><path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                        </svg>
                                    </div>
                                    <Transition name="modalFade">
                                    <div id="adminBoxMenu" class="boxMenu boardMenuIcon" v-if="$store.state.menu.name == 'adminBoxMenu' && $store.state.menu.id == admin.id" style="top: 20px;right: 30px;z-index:6;">
                                        <ul v-if="checkAdminAccess">
                                            <li class="boxMenuItems cursor-pointer" @click.stop="setAdmin(admin, 0)">{{$t('removeAdmin')}}</li>
                                        </ul>                                            
                                    </div>
                                    </Transition>    
                                </div>
                            </div>
                        </div>
                    </div>                   
                    <div v-if="filteredMembers.length">
                        <div style="font-weight: 600;margin: 15px 0;">{{ $t('members') }} ({{ filteredMembers.length }})</div>
                        <div class="suggested-list">
                            <div :key="member.id" v-for="member in filteredMembers">
                                <div class="suggested-wrap">
                                    <UserIcon :user="member" imgClass="userNormalIcon" size="30"/>
                                    <router-link :to="`/profile/${member.id}`" class="suggested-user-name user-link">{{ member.name }}</router-link>
                                    <div v-if="checkAdminAccess" @click.stop="$store.commit('setMenu', {id: member.id, name: 'memberBoxMenu'})" style="height:100%;width:40px;display:flex;position: absolute;right: 0;top: 0;">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" width="3" viewBox="0 0 7 32" style="margin: auto;">
                                            <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path><path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path><path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                        </svg>
                                    </div>
                                    <Transition name="modalFade">
                                    <div id="memberBoxMenu" class="boxMenu boardMenuIcon" v-if="$store.state.menu.name == 'memberBoxMenu' && $store.state.menu.id == member.id" style="top: 20px;right: 30px;z-index:6;">
                                        <ul v-if="checkAdminAccess">
                                            <li class="boxMenuItems cursor-pointer" @click.stop="setAdmin(member, 1)">{{$t('addAdmin')}}</li>
                                            <li class="boxMenuItems cursor-pointer" @click.stop="removeMember(member)">{{$t('removeMember')}}</li>
                                        </ul>                                            
                                    </div>
                                    </Transition>                                    
                                </div>
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
import UserIcon from './Mixed/UserIcon.vue'
    export default {
        props:['board'],
        emits:['close', 'afterRequestHandled'],
        data(){
            return{
                searching: 0,
                keyword: '',
                lock: false
            }
        },
        mounted() {
            
        },
        watch: {
           
        },
        components:{
            PostSearchBar,
            UserIcon
        },
        computed:{
            headTitle(){
                return `<strong>"${this.board.title}"</strong> ${this.$t('groupMembers')}`
            },
            admins(){
                return this.board && this.board.board_to_users && this.board.board_to_users.length ? 
                this.board.board_to_users.filter(ob => ob.admin_flag == 1).map(ob => ob.user) : []
            },
            filteredAdmins(){
                const searchText = this.keyword.toLowerCase();
                return this.admins.filter(user => user.name.toLowerCase().includes(searchText));
            },
            members(){
                return this.board && this.board.board_to_users && this.board.board_to_users.length ? 
                this.board.board_to_users.filter(ob => ob.admin_flag == 0).map(ob => ob.user) : []
            },
            filteredMembers(){
                const searchText = this.keyword.toLowerCase();
                return this.members.filter(user => user.name.toLowerCase().includes(searchText));
            },
            checkAdminAccess () {
                return this.board && this.board.board_to_users && this.board.board_to_users.length ? 
                this.board.board_to_users.filter(ob => ob.admin_flag == 1 && ob.user_id == this.$store.state.user.id).length : false
            },
            
        },
        methods:{
            sendAdminResponse(user, val){
                const data = {
                    target_id: user.id,
                    response: val,
                    record_id: this.board.id
                }
                axios.post('/respond_join_request', data).then(response => {                              
                    this.$emit('afterRequestHandled', response.data, user.record_id)
            
                }).catch(function (error) {
                                      
                }.bind(this));

            },
            removeMember(user){
                const name = user.name
                const uniqueChannell = Math.random().toString(36).substring(5);
                const question = 'memberRemoveConfirm'
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: this.$t(question, {userName: name}) ,
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                    channel: uniqueChannell

                })            
                emitter.on(uniqueChannell, (data) => { data.answer === this.$t('confirmToAction') ? this.removeMemberSend(this.board, user): false});
            },
            removeMemberSend(board, user){
                if(this.lock) return
                this.lock = true
                
                const formData = {
                    record_id : board.id,
                    user_id: user.id,
                }
                axios.post('/remove_group_member', formData)
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
            setAdmin(user, flag){
                const name = user.name
                const uniqueChannell = Math.random().toString(36).substring(5);
                const question = flag == 1 ? 'adminMakeConfirm' : 'adminRemoveConfirm'
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: this.$t(question, {userName: name}) ,
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                    channel: uniqueChannell

                })            
                emitter.on(uniqueChannell, (data) => { data.answer === this.$t('confirmToAction') ? this.setAdminSend(this.board, user, flag): false});
            },
            setAdminSend(board, user, flag){
                if(this.lock) return
                this.lock = true
                
                const formData = {
                    record_id : board.id,
                    user_id: user.id,
                    flag: flag
                }
                axios.post('/set_admin_role', formData)
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
            closeModal(){
                if (!this.$refs.createModal.contains(event.target)) {
                    this.$emit('close')
                }
            },
            searchStart(key){
                this.keyword = key
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

        }
    }
</script>
<style lang="scss">
 
</style>
