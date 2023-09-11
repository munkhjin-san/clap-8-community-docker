<template>
    <div>
        <div v-if="type == 'button'">
            <div style="display:flex;">
                <div v-if="canAddPartner" @click.stop="togglePartnerMenu" class="secondaryButton" style="position: relative;">
                    <span>{{ user.is_friend ?  $t('removeFromMyMembers') : user.is_waiting ? $t('cancelPartnerRequest') : $t('addToMyMembers') }}</span>        
                    <span v-if="inviting" class="spinner-container-interaction" @click.stop>
                        <div class="spinner-nano" style="border: 3px #ffffff solid;border-top: 3px transparent solid;"></div>
                    </span>                                            
                </div>
                <div v-if="user.is_recieved_request" @click="respondPartnerRequest( user.id, 1)" class="secondaryButton">
                    <span>{{ $t('acceptPartnerRequest') }}</span>
                    <span v-if="responding" class="spinner-container-interaction" @click.stop>
                        <div class="spinner-nano" style="border: 3px #ffffff solid;border-top: 3px transparent solid;"></div>
                    </span>  
                </div>
                <div v-if="user.is_recieved_request" @click="respondPartnerRequest( user.id, 0)" class="secondaryButton">
                    <span>{{ $t('denyDenyPartnerRequest') }}</span>
                    <span v-if="responding" class="spinner-container-interaction" @click.stop>
                        <div class="spinner-nano" style="border: 3px #ffffff solid;border-top: 3px transparent solid;"></div>
                    </span>  
                </div>
                <div v-if="canChat" class="secondaryButton" style="position: relative;" @click="openPrivateChat">{{ $t('chat') }}</div>
                <div v-if="!disableBlock || this.user.is_blocked" @click="blockUser" class="secondaryButton" style="position: relative;">
                    <span>{{ this.user.is_blocked ? $t('unBlockUser') : $t('blockUser') }}</span>
                    <span v-if="blocking" class="spinner-container-interaction" @click.stop>
                        <div class="spinner-nano" style="border: 3px #ffffff solid;border-top: 3px transparent solid;"></div>
                    </span>   
                </div>                            
            </div>
        </div>
        <div v-if="type == 'list'">
            <ul>                        
                <li v-if="canAddPartner" @click.stop="togglePartnerMenu" class="boxMenuItems cursor-pointer">{{ user.is_friend ?  $t('removeFromMyMembers') : user.is_waiting ? $t('cancelPartnerRequest') : $t('addToMyMembers') }}</li>
                <li @click="openPrivateChat" v-if="canChat" class="boxMenuItems cursor-pointer">{{ $t('chat') }}</li>
                <li class="boxMenuItems cursor-pointer" v-if="!disableBlock" @click="blockUser">{{ this.user.is_blocked ? $t('unBlockUser') : $t('blockUser') }}    </li>
            </ul>  

        </div>
    </div>
</template>

<script>
    export default {
        props: ['user', 'type', 'hasAlert', 'disableBlock', 'privateOverride'],
        emits: ['reload', 'closeWindow'],
        data(){
            return{
                inviteLock: false,
                blocking: false,
                chatting: false,
                inviting: false,
                responding: false
            }
        },
        mounted() {
            
        },
        computed:{
            canChat(){
                return !this.user.is_blocked && !this.user.is_blocked_by && (this.user.is_friend || this.user.is_waiting)
            },
            canAddPartner(){

                return (this.user.is_friend || this.user.is_waiting) ? true : !this.user.is_blocked_by && !this.user.is_blocked && !this.user.is_recieved_request && (this.user.is_public || this.user.has_mutual_chat || this.privateOverride)
                
            }
        },
        methods:{
            respondPartnerRequest(id, decision){
                if(this.responding) return
                this.responding = true
                axios.post('/respond_partner_request', {id: id, decision: decision}).then(response => {  
                    
                    if(this.hasAlert){
                        this.errorToast(this.$t(response.data.message))
                    }
                    setTimeout(() => {
                        this.inviting = false
                    }, 300);
                    this.responding = false
                    
                    this.$emit('reload')
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.responding = false                
                }.bind(this));
            },
            openPrivateChat(){
                const link = document.createElement('a');
                link.href = `${window.location.origin}/request_private_chat/${this.user.id}`;               
                document.body.appendChild(link);            
                link.click();   
                link.remove();
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
            togglePartnerMenu(){
                if(this.is_waiting){
                    return
                }
                else{
                    this.setMyMembers(this.user)
                }
                
            },
            setMyMembers(user){
                if(this.inviteLock) return
                this.inviteLock = true
                this.inviting = true
                axios.post('/set_member_link', {id: user.id, token: user.q_token}).then(response => {  
                    if(this.hasAlert){
                        this.errorToast(this.$t(response.data.message))
                    }
                    setTimeout(() => {
                        this.inviting = false
                    }, 300);
                    // this.errorToast(this.$t(response.data.message))
                    this.inviteLock = false
                    
                    this.$emit('reload')
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)      
                    this.inviteLock = false       
                    setTimeout(() => {
                        this.inviting = false
                    }, 300);         
                }.bind(this));
            },            
            blockUser(){
                if(this.inviteLock) return
                this.inviteLock = true
                this.blocking = true
                axios.post('/block_user', {id: this.user.id, block: !this.user.is_blocked}).then(response => {  
                    this.inviteLock = false
                    this.$emit('reload')
                    if(this.hasAlert){
                        this.errorToast(this.$t(response.data.message))
                    }
                    setTimeout(() => {
                        this.blocking = false
                    }, 300);   
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.inviteLock = false     
                    setTimeout(() => {
                        this.blocking = false
                    }, 300);            
                }.bind(this));
            },
        }
    }
</script>
<style>
    .spinner-container-interaction{
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left:0;
        background: rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        place-content: center;
    }
</style>
