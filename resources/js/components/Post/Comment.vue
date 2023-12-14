<template>
    <div :key="comment.id" class="commentRoot" >
        <div :class="['commentInner']" :style="{position:'relative',padding:'15px',borderRadius: '0', border: isEditing, boxSizing: 'border-box', float: comment.user_id == $store.state.user.id ? 'right' : 'left'}">
            <div class="commentHeder" style="position:relative;">                                            
                <div class="column-01" style="cursor: pointer;">
                    <UserIcon v-if="comment.user" size="30" :user="comment.user" imgClass="userNormalIcon"/>    
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30">
                        <circle cx="15" cy="15" r="15" fill="#ddd"/>
                    </svg>
                </div>
                <div @click.stop="pushInstantUser($event, comment.user)" class="column-02" style="margin-right: 15px;line-height: 30px;height: 30px;cursor: pointer;">
                    <p class="userName" style="margin-left:10px;height:30px;line-height: 30px;">{{ comment.user ? comment.user.name : '非アクティブユーザー' }}</p>
                </div>
                <div class="column-03" style="position: absolute;top: -45px;right: -8px;">
                    <p style="font-size: 12px;color: grey;">{{ momentMessage }}</p>
                </div>
                <div v-if="comment.user_id == $store.state.user.id && comment.deleted_flag == 0 && editing !== comment.id" class="boardMenuContainer cursor-pointer" @click.stop="$store.commit('setMenu', {id: comment.id, name: 'commentBoxMenu'})">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="6" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin:auto;">
                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                    </svg>
                </div>                                            
                <div class="clearBoth"></div>                                       
                <Transition name="modalFade">
                    <div id="commentBoxMenu" class="boxMenu boardMenuIcon" v-if="$store.state.menu.name == 'commentBoxMenu' && $store.state.menu.id == comment.id" style="top: 25px;right: 25px;z-index:6;">
                        <ul>
                            <li class="boxMenuItems cursor-pointer" @click.stop="$emit('editComment', comment), closeMenu()">コメントを編集する</li>
                            <li class="boxMenuItems cursor-pointer" @click.stop="$emit('deleteComment', comment.id),closeMenu()">コメントを削除する</li>                          
                        </ul>                                            
                    </div>
                    </Transition>
            </div>
            <!--deleted message -->

            <div v-if="comment.deleted_flag == 1" style="background: #F5F5F5;">
                <p style="color: darkgray;padding: 10px;font-size: 15px;margin-bottom: 4px;">このコメントは削除されました</p>
            </div>

            <!--deleted message -->
            <div v-else class="commentBox">
                <p 
                    :class="{emojiOnlyInner : comment.emoji_flag == 1}" 
                    :id="'editComment_' + comment.id" 
                    style="font-size: 14px;line-height: 2;white-space: break-spaces;outline: none;word-break: break-word;display: inline-block;" 
                    v-html="urlCheck(comment.messages)"
                    :contentEditable="editing == comment.id">
                </p>
                <Transition name="slidePop">   
                <div v-if="editing == comment.id" style="position: absolute;bottom: -40px;left: 0;">
                    <ul style="white-space: nowrap;">
                        <li @click="$emit('editSend', comment.id, comment.messages)" class="commentEditButton">保存</li>
                        <li @click="$emit('editCancel',comment.id, comment.messages)" class="commentEditButton">キャンセル</li>
                    </ul>
                </div>
                </Transition>
            </div>                                           
        </div>                                            
        <div class="clearBoth"></div>
    </div> 
</template>
<script>
import UserIcon from '../Board/Mixed/UserIcon.vue';

import Autolinker from 'autolinker';
import moment from 'moment';
export default{
    props: ['comment', 'editing'],
    emits: ['deleteComment', 'editComment', 'editCancel', 'editSend'],
   
    components: {UserIcon},
    computed:{
        isEditing(){
            if(this.comment.emoji_flag == 1){
                return 'none'
            }else{
                return this.editing == this.comment.id ? 'solid 2px var(--hoverBorder)' : 'solid 2px transparent'
            }
            

        },
        classNameChat(){
                    
            if(this.comment.user_id == this.$store.state.user.id){
                if(this.comment.emoji_flag == 1){
                    return "chatRight emojiOnly";
                }
                    return "chatRight";
            }else{
                if(this.comment.emoji_flag == 1){
                    return "chatLeft emojiOnly";
                }
                return "chatLeft";
            }
            
        },
        messageBodyStyle() {
            const comment = this.comment
            const selfid = this.$store.state.user.id
            var width = '';
            var max_w = '';
            var min_w = '';
            var float = '';
            var margin = '';          
            if(comment.user_id == selfid){
                float = "float:right;";
                margin = "margin-right:15px;";
            }else{
                float = "float:left;";
                margin = "margin-left:15px;";
            }
            
            if(comment.message !== null){
                min_w = "min-width:30%;";
                max_w = "max-width:85%;";
            }else if(comment.message == null){
                width = "width:fit-content;";
                max_w = "max-width:50%;";
            }           
               
            if(comment.message_files && comment.message_files.length){
                min_w = "min-width:0 !important;";
            }  
            return float + margin + width + min_w + max_w;     
            var result;       
        },
        momentMessage () {
            moment.locale('ja')
            const date = this.comment.created_at
            return moment(this.comment.created_at).isSame(moment(), 'day') ? 
            moment(date).format('HH:mm') : 
            moment(date).isSame(moment(), 'year') ? 
            moment(date).format('M / D (ddd) HH:mm') : 
            moment(date).format('YYYY / M / D (ddd) HH:mm')                       
        },
    },
    methods:{
        pushInstantUser(event, id){
            if(this.$store.state.user && id == this.$store.state.user.id) return
            const cX = event.clientX;
            const cY = event.clientY;  
            const data = {
                id: id,
                cX: cX,
                cY: cY
            }
            this.$store.commit('setInstantUser', data)   
            this.$store.commit('setMenu', {name: 'instantProfileWindow', id: 5000})                 
        },
        urlCheck: function (text) {
            if(text){

            var linkedText = Autolinker.link(text, { 
                    stripPrefix: false
                } );
            
            return linkedText                             

            }
            
        },
        closeMenu(){
            this.$store.commit('setMenu', { name: '', id : null})
        },
    }
}
</script>