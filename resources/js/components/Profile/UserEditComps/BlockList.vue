<template>
    <div style="margin-top:30px;height: calc(100% - 25px);overflow: auto;position: relative;">
        
         
            <div class="suggested-list" style="gap:0">
                <div class="suggested-wrap" :key="member.id" v-for="member in list" style="margin: 0;">
                    <UserIcon :user="member" imgClass="userNormalIcon" size="30"/>
                    <div class="suggested-user-name">{{ member.name }}</div>
                    <div>
                        <div @click="blockUser(member)" class="commentEditButton">{{ $t('unBlockUser') }}</div>
                    </div>   
                </div>
                <div v-if="fetched && !list.length" class="no-comment-text">
                    {{ $t('noBlockUserYet') }}
                </div>
            </div>
        
    </div>
</template>

<script>
import UserIcon from '../../Board/Mixed/UserIcon.vue';
    export default {
        props: ['user', 'errorToast'],        
        components: {
            UserIcon
        },
        data(){
            return{
                list: [],
                inviteLock: false,
                fetched: 0
            }
        },
        mounted() {
            this.getBlockedUsers()
        },
        methods: {
            blockUser(user){
                if(this.inviteLock) return
                this.inviteLock = true
                axios.post('/block_user', {id: user.id, block: false}).then(response => {  
                    this.inviteLock = false
                    
                    this.getBlockedUsers()
                    // this.errorToast(this.$t(response.data.message))
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.inviteLock = false                
                }.bind(this));
            },
            getBlockedUsers(){
                axios.post('/member_get_block_list').then(response => {  
                    this.list = response.data
                    this.fetched ++
                    console.log('gt', this.fetched)
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)      
                    this.inviteLock = false                
                }.bind(this));
            },
        }
    }
</script>
