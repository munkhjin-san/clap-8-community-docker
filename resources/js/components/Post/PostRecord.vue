<template>
    <div class="post-item-outer">
        <div class="post-item-header-wrap">
            <div v-html="title" class="post-title"></div>
            
            <div @click.stop="$store.commit('setMenu', {name: 'recordBoxMenu', id: record.id})" v-if="isOwner" class="boardMenuContainer cursor-pointer" style="align-self: normal;">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin: auto;">
                    <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path><path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path><path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                </svg>            
            </div>
            <Transition name="modalFade">
            <div id="recordBoxMenu" class="boxMenu boardMenuIcon" v-if="$store.state.menu.name == 'recordBoxMenu' && $store.state.menu.id == record.id" style="top: 25px;right: 25px;z-index:6;">
                <ul>
                    <li class="boxMenuItems cursor-pointer">{{`${appNameJp}を編集する`}}</li>
                    <li class="boxMenuItems cursor-pointer">{{`${appNameJp}を削除する`}}</li>                  
                    
                </ul>                                            
            </div>
            </Transition>
        </div>
        <div class="post-second-wrap">
            <div class="post-user-wrap">
                <div v-if="record.app_type == 2 || record.app_type == 3" style="display:flex;align-items: center;gap:10px">
                    <UserIcon :user="record.user" imgClass="userNormalIcon" size="30"/>
                    <div>{{ record.user ? record.user.name : '' }}</div>
                </div>
                
                <div v-if="record.app_type == 4 || record.app_type == 3" style="position: relative;">
                    <div style="display: flex;align-items: center;">
                        <svg v-if="record.app_type == 3" version="1.1" xmlns="http://www.w3.org/2000/svg" class="nice-arrow" viewBox="0 0 47 32" style="margin-right: 15px;">
                            <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
                        </svg>
                        <div :ref="`to_users_${record.id}`" :class="['toUserListContainer', {expandToUserListContainer : expand}]">
                            <div :key="user.id" v-for="user in record.to_users" style="display: flex;align-items: center;">                                                             
                                <UserIcon size="30" :user="user" imgClass="toUsersIcon"/> 
                                <p style="width: max-content;" class="userName">{{ user.name }}</p>                                       
                            </div>
                                
                            
                        </div>
                    </div>
                    
                    <div v-if="viewExpand" @click="expand = !expand" class="toUserExpandButton">
                                                                                  
                        <svg :class="['userListArrow', {reverse : expand}]" version="1.1" width="25" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg> 
                        <span style="margin:0 5px;">({{record.to_users.length}})</span>
                        
                    </div> 
                </div>
            </div>
            
            <PostDate :record="record" dateClass="dateText"/> 
        </div>
        <div>
            <div>
                <p style="white-space: break-spaces;" v-html="body"></p>
                <span @click="showAll = !showAll" class="jump-link" v-if="truncated">{{ showAll ? '閉じる' : '続きを表示する' }}</span>
            </div>

            <div v-if="goal" style="margin-top: 15px;">
                <div style="border-bottom: 1px dashed rgb(89, 86, 86); display: flex; width: 100%; margin-bottom: 30px;">
                    <p style="margin: 0px auto -13px;font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--background-color); height: fit-content; line-height: 1.2; border-radius: 5px;">達 成 条 件</p>
                </div>
                <div v-html="goal"></div>
            </div>
            <div>

            </div>
            <div class="post-url" v-if="record.referrer">
                参照元 : <a :href="record.referrer">{{ record.referrer }}</a>
            </div>
            <div v-if="tags.length" style="display: flex;gap: 5px 10px;flex-wrap: wrap;">
                <PostTag
                    v-for="tag in tags"
                    :tag="tag"
                    :key="tag.id"
                />

            </div>
            <div v-if="challengeButtonView">                                    
                <button @click="$emit('setChargeTarget', record.id)" v-if="challengeButtonSwitch" id="chargeAddButton" class="chargeFormeAddButton cursor-pointer">チャレンジにチャージする</button>
                <button v-else class="chargeFormeAddButton" disabled="">{{canNotCharge}}</button>
            </div>  
        </div>
        
    </div>
</template>
<script>
import UserIcon from '../Board/Mixed/UserIcon.vue'
import PostDate from './PostDate.vue'
import Autolinker from 'autolinker';
import PostTag from './PostTag.vue';
import moment from 'moment';
export default{
    props: ['record', 'appNameJp'],
    emits: ['setChargeTarget'],
    data(){
        return{
            maxLength: 200,
            truncated: false,
            showAll: false,
            expand: false,
            viewExpand: false
        }
    },
    mounted(){
        const to_user = this.$refs[`to_users_${this.record.id}`]
        if(to_user && to_user.scrollHeight > to_user.clientHeight){
            this.viewExpand = true
        }
    },
    components: { UserIcon, PostDate, PostTag },
    computed:{
        challengeButtonSwitch(){
            var todayDate = (moment().format("YYYY-MM-DD"));                
            var charged_user = this.record.challenge_awards.filter(obj => obj.user_id == this.$store.state.user.id);
            if(todayDate <= this.record.date_end && this.record.status_flag == 0 && charged_user.length == 0){
                return true
            }                
        },
        canNotCharge(){
            const todayDate = (moment().format("YYYY-MM-DD"));
            if(this.record.status_flag > 0){
                return 'チャレンジの結果が確定しました'
            }else{
                const charged_user = this.record.challenge_awards.filter(obj => obj.user_id == this.$store.state.user.id);
                if(charged_user.length){
                    return '既にチャージしています'
                }else if(todayDate > this.record.date_end){
                    return 'チャージ期間を終了しました'
                }
            }
        },
        challengeButtonView(){
            if(this.record.app_type == 4){
                let flag = this.record.to_users.filter(obj => obj.id == this.$store.state.user.id);  
                return !flag.length ? true : false   
            }
            return false
            
        },
        isOwner(){
            if(this.record.user_id == this.$store.state.user.id){
                return true
            }
            return false
        },
        tags(){
            return this.record.tags ? this.record.tags : []
        },
        title(){
            return this.record && this.record.title ? this.record.title : ''
        },
        body(){           
            
            const text = this.record.app_type == 4 ? this.record.content_rule : this.record.content
            const truncate = this.cutter(text, this.maxLength)
            const urlParse = Autolinker.link(truncate, {stripPrefix: false});   
            return urlParse          
            
        },
        goal(){
            const text = this.record.app_type == 4 ? this.record.content_goal : ''
            const truncate = this.cutter(text, this.maxLength)
            const urlParse = Autolinker.link(truncate, {stripPrefix: false});   
            return urlParse    
        }
    },
    methods:{
        cutter(string, len){
            if(this.showAll || string.length <= len || string.length <= len + 50){
                return string
            }
            const last = string.substring(len - 5, len + 5)
            const check_emoji = last.match(/[\p{Emoji}\u200d]+/gu)
            if(!check_emoji){
                this.truncated = true
                return string.substring(0, len) + '...'
            
            }else{
                return this.cutter(string, len + 5)
            }
            
        }
    }
}
</script>
<style>


</style>