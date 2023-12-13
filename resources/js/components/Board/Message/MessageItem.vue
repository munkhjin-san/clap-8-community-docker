<template>
    <div :id="'messageRoot_' + message.id" 
        class="messageBoxRoot" 
        :class="{infoMessage : message.info_flag == 1, selfMessage: message.user_id == $store.state.user.id}"
        :style="{marginBottom:lastMsgMargin}">
        <div class="infoMessageInner" v-if="message.info_flag == 1">   
            <p v-if="showDate">{{momentMessage}}</p>       
            <p style="cursor:pointer" @click="showDate = !showDate" v-html="infoMessage"></p>        
        </div>
        <div class="infoMessageInner" v-if="message.info_flag == 2">   
            <p v-if="showDate">{{momentMessage}}</p>       
            <p style="cursor:pointer" @click="showDate = !showDate" v-html="taskInfoMessage"></p>        
        </div>
        <div 
            v-else-if="message.info_flag == 0" 
            :style="{
                float: $store.state.user && $store.state.user.id == message.user.id ? 'right' : 'left',
                margin: '0 15px',
                maxWidth: message.message == null || !message.message || !message.message.length ? '50%' : '80%',
                width: 'fit-content'
            }" 
            :class="['mobileMessageBody', { 'reached' : $store.state.urlMessageId == message.id}, { emojiOnly: (message.emoji_flag == 1 || message.emoji_flag == 2) && !message.message_reply && !message.message_quot, editIsOn:commentEditToggle}]"
        >
            <div id="commentBody">
                <div :id="'reply_' + message.id" class="commentHeder" style="position:relative;">
                    <div v-if="message.user && message.user.deleted_at == null" class="column-01 cursor-pointer">                        
                        <UserIconPreLoad size="30" :user="message.user" imgClass="userNormalIcon"/>                       
                    </div>   
                    <div v-else class="column-01 cursor-pointer"> 
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30">
                            <circle cx="15" cy="15" r="15" fill="#ddd"/>
                        </svg>
                    </div>                 
                    <div @click.stop="pushInstantUser($event, message.user_id)" class="column-02 cursor-pointer" style="margin-top: 7px;line-height: unset;">                        
                        <p :id="'messageSender_' + message.id" class="userName" @dragstart.prevent style="margin:0 10px;">{{ messageUserName }}</p>                        
                    </div>                    
                    <div class="column-03" style="position: absolute;top: -40px;right: -13px;">                                                    
                        <p @dragstart.prevent class="dateText" style="font-size:12px;color:grey">{{momentMessage}}</p>
                    </div>
                    <div class="messageIconContainer">
                        <div class="bell-icon cursor-pointer" v-if="reminded" @click="remindRequestModal(message)">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" style="margin:auto;fill:var(--kebab-icon)" version="1.1" x="0px" y="0px" height="13" viewBox="0 0 26 29" enable-background="new 0 0 26 29" xml:space="preserve">
                                <defs>
                                </defs>
                                <path d="M25.469,20.171c-0.7-0.206-1.325-0.619-1.875-1.108c-0.156-0.436-0.258-1.137-0.337-1.714  c-0.223-1.772-0.337-3.599-0.568-5.4c-0.225-1.931-0.658-4.1-1.937-5.683c-1.059-1.357-2.512-2.479-4.189-2.918  c-0.066-0.017-0.112-0.075-0.112-0.143c0.001-0.889,0.002-1.944,0.002-1.944C16.452,0.563,15.887,0,15.19,0  c-0.003,0.001-3.967,0-3.97,0.001c-0.696,0.001-1.261,0.566-1.26,1.262l0.002,1.943c0,0.068-0.046,0.126-0.111,0.143  c-1.678,0.44-3.13,1.561-4.189,2.918c-1.867,2.38-1.902,5.581-2.224,8.422c-0.086,0.902-0.167,1.799-0.277,2.661  c-0.085,0.601-0.146,1.16-0.335,1.698c-0.004,0.01-0.008,0.021-0.012,0.029c-0.19,0.17-0.688,0.562-0.969,0.706  c-0.289,0.167-0.585,0.305-0.9,0.394c0.041-0.03-0.948,1.155-0.945,1.155c0.001,0.015,0.017,2.729,0.019,2.741  c0.004,0.636,0.522,1.147,1.159,1.143c2.012-0.012,5.394-0.045,8.306-0.076c-0.027,0.112-0.038,0.231-0.025,0.354  c0.007,0.051,0.015,0.156,0.024,0.206c0.131,0.869,0.464,1.659,1.089,2.321c1.045,1.095,2.678,1.354,4.108,0.914  c1.402-0.504,2.303-2.001,2.318-3.443c0.008-0.115-0.001-0.222-0.02-0.32c2.899,0.021,6.253,0.041,8.257,0.053  c0.642,0.004,1.165-0.513,1.168-1.154c0.003-0.012,0.012-2.726,0.016-2.737C26.423,21.332,25.428,20.14,25.469,20.171   M23.537,19.014c0,0,0.002,0.002,0.003,0.002c-0.006-0.005-0.012-0.01-0.012-0.01C23.52,18.998,23.533,19.01,23.537,19.014   M4.502,20.775c0.779-0.735,0.893-2.135,1.055-3.106c0.127-0.933,0.216-1.84,0.31-2.74c0.187-1.71,0.342-3.536,0.779-5.15  c0.507-1.773,1.895-3.339,3.644-3.939c0.332-0.112,0.729-0.203,1.012-0.277c0.796-0.209,1.008-0.459,1.009-1.151  c0,0,0.001-1.216,0.002-2.071c0-0.092,0.074-0.167,0.168-0.167h1.491c0.093,0,0.168,0.075,0.168,0.168  c0.001,0.854,0.002,2.07,0.002,2.07c0,0.693,0.302,1.014,1.031,1.188c2.149,0.252,4.041,2.189,4.595,4.18  c0.653,2.528,0.717,5.269,1.085,7.892c0.083,0.588,0.178,1.231,0.356,1.842c0.126,0.409,0.271,0.817,0.612,1.182  c0.651,0.607,1.407,1.135,2.236,1.486c0.002,0.307,0.002,0.365,0.004,0.714c-3.2,0.019-8.211,0.051-10.854,0.078  c-0.094,0.004-0.181,0.019-0.263,0.038c-2.706-0.032-7.499-0.083-10.598-0.105c0.002-0.355,0.003-0.416,0.005-0.728  C3.143,21.84,3.866,21.341,4.502,20.775 M14.984,25.282c-0.001-0.019-0.008,0.005-0.012,0.012l-0.017,0.036  c-0.16,0.356-0.385,0.793-0.687,1.014c-0.139,0.112-0.296,0.146-0.448,0.225c-0.31,0.149-0.857,0.176-1.188,0.07  c-0.591-0.15-0.941-0.739-1.098-1.311l-0.01-0.037c-0.003-0.007-0.007-0.03-0.006-0.011c-0.006-0.057-0.018-0.11-0.031-0.162  c0.529-0.006,1.019-0.012,1.455-0.017c0.082,0.021,0.169,0.034,0.263,0.038c0.521,0.005,1.132,0.012,1.802,0.017  C14.999,25.197,14.99,25.238,14.984,25.282"/>
                            </svg>
                        </div>
                        <div 
                            v-if="message.deleted_at == null"
                            @click.stop="messageMenu"
                            :style="{ visibility: commentEditToggle ? 'hidden' : 'visible'}"
                            id="boardMessageMenuButton"  
                            class="boardMenuContainer cursor-pointer">
                        
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32" style="margin:auto;min-width: 3px;">
                                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                            </svg>
                        </div>
                    </div>                    
                    
                 
                    
                    <Transition name="modalFade"> 
                    <div id="boardMessageMenu" class="boxMenuComment cursor-pointer" v-if="$store.state.menu.name == 'boardMessageMenu' && $store.state.menu.id == message.id" :style="topOrBottom,right" style="z-index:2;box-shadow:none;background-color: unset;">
                  
                        <ul v-if="commentMenuLayer == 0" class="messageMenuList">
                            <li @click="commentEdit(message.id), closeMenu()" v-if="message.user_id == $store.state.user.id && message.message !== null && message.emoji_flag == 0" class="boxMenuItems cursor-pointer">編集する</li>
                            <li @click="replyQuotStart(message, 'reply'), closeMenu()" v-if="message.user_id !== $store.state.user.id" class="boxMenuItems cursor-pointer">返信する</li>                          
                            <li @click="replyQuotStart(message, 'quot'), closeMenu()" class="boxMenuItems cursor-pointer">引用する</li>
                            
                            <li @click="copyTextStart(message.id), closeMenu()" v-if="message.message !== null && message.message !== ''" class="boxMenuItems cursor-pointer">コピー</li>
                            <li @click.stop="commentMenuLayer = 1" v-if="$store.state.user.partner_flag !== 1 && message.message !== null && message.message !== ''" class="boxMenuItems cursor-pointer" style="display: flex;">
                                <span style="margin-right: 10px;">シェア</span>
                                <svg style="transform:rotate(180deg);margin: auto 0 auto auto;" version="1.1" width="10" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                </svg>  
                            </li>
                            <li @click="remindRequestModal(message), closeMenu()" v-if="message.user_id" class="boxMenuItems cursor-pointer">リマインド</li>                                                                                               
                            <li @click="checkRequestModal(message), closeMenu()" v-if="message.user_id == $store.state.user.id && !message.check_flag && message.emoji_flag == 0 && openedBoard.private_flag !== 3" class="boxMenuItems cursor-pointer">確認依頼</li>                                                                                              
                            <li @click="sendMailConfirm(message, openedBoard.id, 2, message.id), closeMenu()" v-if="message.user_id == $store.state.user.id && message.check_flag == 1 && message.emoji_flag == 0" class="boxMenuItems cursor-pointer">再確認依頼</li>


                            <li @click="commentDeleteConfirm(message.id)" v-if="message.user_id == $store.state.user.id" class="boxMenuItems cursor-pointer">削除する</li>
                        </ul>
                        <ul v-if="commentMenuLayer == 1" class="messageMenuList">  
                            <li @click.stop="commentMenuLayer = 0" v-if="message.message !== null" class="boxMenuItems cursor-pointer">
                            <svg version="1.1" width="10" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                            </svg>  
                            <span style="margin-left: 10px;">戻る</span></li>      
                            <li @click="shareTo('board')" class="boxMenuItems cursor-pointer">ボード</li>    
                            <li @click="shareTo('knowledge')" class="boxMenuItems cursor-pointer">ナレッジ</li>                                           
                            <li @click="shareTo('nice')" class="boxMenuItems cursor-pointer">ナイス</li> 
                            <li @click="shareTo('challenge')" class="boxMenuItems cursor-pointer">チャレンジ</li> 
                            <li @click="shareTo('calendar')" class="boxMenuItems cursor-pointer">カレンダー</li> 
                            <li @click="shareTo('task')" class="boxMenuItems cursor-pointer">タスク</li>
                            <!-- <li @click="shareTo('memo')" class="boxMenuItems cursor-pointer">ノート</li> -->
                            
                        </ul>                                                    
                    </div>
                    </Transition>
                    <div class="clearBoth"></div>
                </div>                                            
            </div>    
            
            <div v-if="message.deleted_at" style="background: var(--bg2);">
                <p style="color: gray;padding: 10px;font-size: 13px;">{{ $t('messageDeleted') }}</p>
            </div>
            
            <div v-if="message.deleted_at == null" :class="message.user_id == this.$store.state.user.id ? 'commentTextBoxRight' : 'commentTextBoxLeft'" style="background:transparent">               
                
                <MessageQuoteReply 
                    v-if="message.message_reply"
                    :which="'reply'"
                    :message="message.message_reply"
                    :quotMessage="null"
                    :openedBoard="openedBoard"
                    :urlCheck="urlCheck"
                    @pushInstantUser="pushInstantUser"/>
                <MessageQuoteReply 
                    v-if="message.message_quot"
                    :which="'quot'"
                    :message="message.message_quot"
                    :quotMessage="message.quot_message"
                    :openedBoard="openedBoard"
                    :urlCheck="urlCheck"
                    @pushInstantUser="pushInstantUser"/>
                <MessageQuoteReply 
                    v-if="message.message_forward"
                    :which="'forward'"
                    :message="message.message_forward"
                    :quotMessage="null"
                    :openedBoard="openedBoard"
                    :urlCheck="urlCheck"
                    @pushInstantUser="pushInstantUser"/>
                



                <div class="normal-body">
                    <div
                        @mousedown="$store.commit('setMenu', {id: null, name: ''})"
                        @touchstart="$store.commit('setMenu', {id: null, name: ''})"
                        @mousedown.stop="menuClick"
                        @click.stop="mentionClick"
                        @dragstart.prevent
                        v-touch:hold="longTapAction" 
                        :contentEditable="commentEditToggle"
                        @blur.prevent
                        @paste="pasteListener($event)" 
                        :id="'editComment_' + message.id" 
                        :style="{display: messageBody || commentEditToggle ? 'inline-block' : 'none', marginBottom: message.message_files && message.message_files.length && commentEditToggle && !messageBody ? '10px' : '0'}" 
                        v-html="messageBody" 
                        class="messageInnerBody"
                        :class="{ emojiOnlyInner: (message.emoji_flag == 1 || message.emoji_flag == 2) && !message.message_reply && !message.message_quot}">
                    </div>   
                        <MessageFiles 
                            v-if="message.message_files && message.message_files.length"
                            :list="message.message_files"
                            :message="message"
                            :mIndex="mIndex"
                        /> 
                                
                    
                </div>
                           
            </div>
            <div v-if="message.deleted_at == null" :id="'messageCheckViewArea' + message.id" style="display: flex;width:100%;position:relative;">
                <div style="display:flex;width: fit-content;">
                    
                    <div 
                        v-if="reactButtonView(message)" 
                        class="reactButton" 
                        :class="{cursorBlock : message.user_id == $store.state.user.id, reactOn: reacting}"  
                        @click="checkSendAreaClick($event, message)">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" :fill="checkSendIconColor ? 'var(--primary-color)' : 'var(--check-inactive)'">
                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                        </svg>
                    </div>
                    <div v-if="message.reacted_users.length" @click.stop="viewReactedUsersList" style="display:flex;padding: 10px;margin: 5px 0 -15px -15px;height: 15px;">
                        <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in reactedUsersList">  
                            <UserIconPreLoad :title="user.name" size="30" :user="user" imgClass="userSmallIcon"/>                                         
                        </div>
                        <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="message.reacted_users.length > 3">...({{message.reacted_users.length}})</span>
                    </div>                                    
                </div>
                <div v-if="checkFunctionView" style="display: flex;font-size: 12px;margin-left:auto;height:30px">
                    <p @click.stop="viewCheckedUserList" class="cursor-pointer" :class="{activeCheck : $store.state.menu.name == 'checkedUsersList' && $store.state.menu.id == message.id && message.checked_users !== null}" style="margin-top: auto;padding: 8px 8px 9px 8px;margin-bottom:-8px;">{{$t('checked')}} ({{ message.checked_users.length}})</p>                                            
                    <Transition name="modalFade">
                    <div id="checkedUsersList" v-if="$store.state.menu.name == 'checkedUsersList' && $store.state.menu.id == message.id && message.checked_users !== null" class="checkUsersList" :class="{rightSide : message.checked_users && message.user_id == $store.state.user.id}">                               
                        <a v-for="(user, index) in message.checked_users" :key="index" class="boardUsersListInner" :href="'/app/public/user?id='+user.id">
                            <p class="cursor-pointer" style="font-size:small;">{{user.name}}</p>
                        </a>                                
                    </div>
                    </Transition>
                </div>
                <div v-if="checkFunctionView" style="display: flex;font-size: 12px;padding-left:10px;height:30px">
                    <p @click.stop="viewunCheckedUserList"  class="cursor-pointer" :class="{activeCheck : $store.state.menu.name == 'uncheckedUsersList' && $store.state.menu.id == message.id && message.unchecked_users !== null}" style="margin-top: auto;padding: 8px 8px 9px 8px;margin-bottom:-8px;">{{$t('unchecked')}} ({{ message.unchecked_users.length}})</p>                                               
                    <Transition name="modalFade">
                    <div id="uncheckedUsersList" style="top: 38px;"  v-if="$store.state.menu.name == 'uncheckedUsersList' && $store.state.menu.id == message.id && message.unchecked_users !== null" class="checkUsersList" :class="{rightSide : message.unchecked_users && message.user_id == $store.state.user.id}">                                
                        <a v-for="(user, index) in message.unchecked_users" :key="index" class="boardUsersListInner" :href="'/user/'+user.id">
                            <p class="cursor-pointer" style="font-size:small;">{{user.name}}</p>
                        </a>                                
                    </div>
                    </Transition>
                </div> 
            </div>
            <div id="reactedUserListAll" v-if="$store.state.menu.name == 'reactedUserListAll' && $store.state.menu.id == message.id" class="taskUsersList" style="left: 15px;right: auto;margin-top: 10px;">
                <div @click.stop="pushInstantUser($event, user.id)" :key="user.id" class="mentionBox-inner" v-for="user in reactedUsersListAll">                                                
                    <div class="column-01"> 
                        <UserIconPreLoad size="25" :user="user" imgClass="userMidIcon"/>   
                    </div> 
                    <p class="cursor-pointer" style="margin: auto auto auto 5px;font-size: 13px;">{{user.name}}</p>                                                                           
                </div>
            </div>            
            <Transition name="slidePop">                 
            <div v-if="commentEditToggle" style="display: flex;width: 100%;position: absolute;bottom: -40px;left: 0;">
                <ul style="display: inline;z-index:81;margin-top: 10px;width: 100%;">
                    <li @click="commentEditSend(message.id, message.message)" class="commentEditButton">保存</li>
                    <li @click="commentEditCancel(message.id, message.message)" class="commentEditButton">キャンセル</li>
                </ul>
            </div>
            </Transition>
        </div>
            <div class="clear-both"></div>
            
            <div v-bind="unreadLineVisible(unreadMessages)" v-if="unreadMessages.id == message.id" :id="'unread_line_' + message.id" style="user-select:none;width:100%;border-bottom:solid thin #a09f9f;position: absolute;bottom:10px;font-size:12px;">
                <p class="unread-inner" style="margin-bottom: -12px;">{{ $t('newMessages') }}</p>
            </div>
    </div>
</template>

<script>
import MessageQuoteReply from "./MessageQuoteReply.vue";
import MessageFiles from "./MessageFiles.vue";
import moment from 'moment';
import { filesize } from 'filesize';
import Autolinker from 'autolinker';
import UserIconPreLoad from '../Mixed/UserIcon.vue'
import { nextTick } from 'vue'
    export default {
        props: ['message', 'openedBoard', 'mIndex', 'mLength', 'lastReadMessage', 'searchTargetId', 'messageListType', 'unreadMessages'],
        data(){
            return{
                commentEditToggle: false,
                reactedUserListToggle: false,
                commentMenuLayer: 0,
                lastMenuPx: 0,
                unreadTrigger: false,
                reacting: false,
                topOrBottom: 'top:18px',
                right: 'right: 23px',
                showDate: false
                
            }
        },
        components:{
            MessageQuoteReply,
            MessageFiles,
            UserIconPreLoad
            
        },
        mounted() {
            if((this.message.id == this.searchTargetId && this.messageListType == 'search') || this.$store.state.urlMessageId == this.message.id){
                var elem = document.getElementById('messageRoot_' + this.message.id);   
                if(elem){
                    elem.scrollIntoView({block: 'center' }); 
                } 
                   
                this.$store.commit('setUrlMessageId', this.message.id)
                setTimeout(() => { this.$store.commit('setUrlMessageId', null)}, 5000);  
            }
            emitter.on('messagePreviewToShare',(to, message, flag, file) => {                                              
                if(message && message.id == this.message.id){
                    this.shareTo(to, flag, file)
                }                 
            });         
        },
        computed:{
            // unreadLineTrigger(){
            //     if(this.$store.state.boardBadge && this.$store.state.boardBadge[this.openedBoard.id]){
            //         const user = this.openedBoard.board_to_users.filter(ob => ob.user_id == this.$store.state.user.id)
                    
            //         if(user.length && user[0].last_message == this.message.id){
                        
            //             return true
            //         }
            //     }
            // },
            taskInfoMessage(){
                if(this.message.task){
                    const title = this.message.task.title ? this.message.task.title : ''
                    const body = this.message.task.remarks ? this.message.task.remarks : ''
                    const merge = title + body
                    return `<strong>新しいタスクが追加されました。</strong><br><p style=text-align:left;margin-top:10px>${merge.slice(0, 100)}${merge.length > 100 ? '...' : ''}</p>`
                }
            },
            memoInfoMessage(){
                if(this.message.memo && this.message.memo.content && this.message.memo.content.length){
                    return `<strong>新しいノートが追加されました。</strong><br><p style=text-align:left;margin-top:10px>${this.message.memo.content.slice(0, 100)}${this.message.memo.content.length > 100 ? '...' : ''}</p>`
                }
            },
            reminded(){
                const list = this.message.message_remind_users
                return list ? list.some(item => item.user_id === this.$store.state.user.id && item.reminded === 1) : false
            },
            checkSendIconColor(){                
                const check_list = this.message.reacted_users.filter(ob => ob.id == this.$store.state.user.id).length                
                return (this.message.user_id == this.$store.state.user.id || check_list) ? true : false              
                
            }, 
            infoMessage(){
                return this.message.message
            },
            messageUserName(){                
                return this.message.user && this.message.user.deleted_at == null
                ? this.message.user.name
                : this.$t('unAvailableUserName');
            },
            reactedUsersList(){
                
                const list = this.message.reacted_users
                if(list && list !== ''){
                    var res = [];
                    
                    list.forEach((user) => {
                        let checked = this.openedBoard.board_to_users.filter(obj => obj.user_id == user.id);
                        if(checked && checked.length){
                            res.push(checked[0].user);
                        }                
                    }); 
                    
                    return res.slice(0,3);   
                }
            },
            messageBody(){
                // if(this.message.id == this.searchTargetId && this.messageListType == 'search'){
                //     const br_remove = this.message.message.replace(/&lt;br&gt;/g," ");
                //     let text = this.urlCheck(br_remove)
                //     const anchorTagRegex = /<a.*?href="(.*?)".*?>(.*?)<\/a>/gi;
                //     const placeholders = [];
                //     const replaceAnchorTagsWithPlaceholders = (match, href, innerText) => {
                //         placeholders.push(match);
                //         return `{${placeholders.length - 1}}`;
                //     };
                //     const textWithoutAnchorTags = text.replace(anchorTagRegex, replaceAnchorTagsWithPlaceholders);
                //     const keywordRegex = new RegExp(`(${this.$store.state.keyword})(?![^<]*>|[^<>]*<\/)`, 'gi');
                //     const highlightKeyword = (match) => `<span style="background-color: yellow;">${match}</span>`;
                //     const highlightedText = textWithoutAnchorTags.replace(keywordRegex, highlightKeyword);
                //     const restoreAnchorTagsFromPlaceholders = (textWithPlaceholders, placeholder, index) =>
                //     textWithPlaceholders.replace(`{${index}}`, placeholder);
                //     const textWithHighlightedKeywords = placeholders.reduce(restoreAnchorTagsFromPlaceholders, highlightedText);
                //     return textWithHighlightedKeywords;
                // }
                if(this.message.info_flag == 0){
                    const text = this.message.message ? this.message.message : ''
                    const to_all = text.replace('<span class="toAll">@全員</span>', '<a class="toAll">@全員</a>');
                    const converterd = to_all.replace(/<((?!a )[^>]*)>/g, "&lt;$1&gt;").replace(/&lt;\/a&gt;/g, "</a>");
                    const br_remove = converterd.replace(/&lt;br&gt;/g," ");
                    return this.urlCheck(br_remove)
                }
                
                
            },
            lastMenu(){
                // return this.mIndex == 0 && this.$store.state.menu.name == 'boardMessageMenu' && this.$store.state.menu.id == this.message.id ? this.lastMenuPx : '0'
                return this.mIndex == 0 && this.$store.state.menu.id == this.message.id && this.lastMenuPx > 0 ? this.lastMenuPx + 'top:auto;bottom:0' : ''
            },
            lastMsgMargin(){
                return this.mIndex == 0 && this.$store.state.menu.id == this.message.id && (
                    this.$store.state.menu.name === 'uncheckedUsersList' || 
                    this.$store.state.menu.name === 'checkedUsersList' || 
                    this.$store.state.menu.name === 'reactedUserListAll'
                )
                ? 
                `${this.lastMenuPx}px` : '0'
            },
            reactedUsersListAll(){
                return this.message.reacted_users && this.message.reacted_users.length ? this.message.reacted_users : []                
            },
            checkFunctionView(){
                if(this.message.check_flag == 1){
                    let checked = this.message.checked_users.filter(ob => ob.id == this.$store.state.user.id).length
                    let unchecked = this.message.unchecked_users.filter(ob => ob.id == this.$store.state.user.id).length
                    return checked || unchecked || this.message.user_id == this.$store.state.user.id
                }
                return false
                
            },
            momentMessage () {
                moment.locale('ja')
                const date = this.message.created_at
                return moment(this.message.created_at).isSame(moment(), 'day') ? 
                moment(date).format('HH:mm') : 
                moment(date).isSame(moment(), 'year') ? 
                moment(date).format('M / D (ddd) HH:mm') : 
                moment(date).format('YYYY / M / D (ddd) HH:mm')                       
            }, 
        },
        methods:{
            remindRequestModal(item){
                this.$parent.$emit('remindRequest', item)
            },
            menuClick(){
                if(this.$store.state.menu.name == 'boardMessageMenu' && this.$store.state.menu.id == this.message.id){
                    const cont = document.getElementById('boardMessageMenu');   
                    if(cont && !cont.contains(event.target)){
                        const menu = {name: null, id: null}
                        this.$store.commit('setMenu', menu);
                    } 
                    return
                } 
            },
            mentionClick(){
                
                if(event.target.tagName == 'A' && event.target.className == 'mntuser'){
                   
                    const id = parseInt(event.target.id)
                    if(id){
                        console.log('mention')
                        this.pushInstantUser(event, id)
                    }
                }else{
                    // this.$store.commit('setMenu',{ name: '', id: null})
                }
                
            },
            pasteListener(e){                    
                e.preventDefault();    
                var text = e.clipboardData.getData("text/plain");            
                if(!text || text == ''){            
                    return  
                }else{
                    var text = e.clipboardData.getData("text/plain");
                    document.execCommand("insertText", false, text);
                }     
            },
            unreadLineVisible(data){
                
                setTimeout(() => {
                    const element = document.getElementById('messageRoot_' + this.message.id);
                    
                    const rect = element.getBoundingClientRect()
                    if(this.$store.state.boardBadge[this.openedBoard.id] && (rect.y + rect.height < 0)){
                        const data = {
                            status: true,
                            count: this.unreadMessages.count,
                            id: this.message.id
                        }
                        this.$emit('unreadJumperOn', data)
                        
                    
                    }
                    
                })
            },
            replyQuotStart(message, which){ 
                const widthS = document.getElementById('editComment_' + message.id).clientWidth + 20;
                const heightS = document.getElementById('editComment_' + message.id).clientHeight + 20;
                const file = message.message && message.message.length ? false : true
                const text = message.message && message.message.length ? document.getElementById('editComment_' + message.id).textContent : null
                const data = {
                    active: true,
                    which: which,
                    message: message,
                    height: heightS,
                    width: widthS,
                    text: text,
                    file: file
                }
                this.$store.commit('setQuoteReply', data);
                this.$emit('updateReplyKey')


            },
            closeMenu(){
                const menu = {name: null, id: null}
                this.$store.commit('setMenu', menu)
            },
            checkRequestModal(item){            
                const request = 'confirm'            
                this.$parent.$emit('checkRequest', item, request)
            },
            sendMailConfirm(message, record_id, pattern, msg_id){      

                const uniqueChannell = Math.random().toString(36).substring(5);
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: '未確認者へ確認依頼のメールを送りますか。' ,
                    closeButton: false, 
                    autoClose: false,
                    answers: ['はい', 'いいえ'],
                    channel: uniqueChannell
                })            
                emitter.on(uniqueChannell, (data) => { 
                    if(data.answer == this.$t('confirmToAction')){
                        this.sendMail(message, record_id, pattern, msg_id);
                    }
                })

            },
            sendMail(message, record_id, pattern, message_id){           
                var send_users = message.unchecked_users.map(ob => ob.id)     
                axios.post('/send_reconfirm_email',{send_list: send_users, board_id: record_id, send_condition: pattern, msg_id:message_id}).then(                
                    response => {                  
                        setTimeout(() => {
                            const m = pattern == 1 ? "確認依頼がオンになりました。": pattern == 2 ? "再確認依頼のメールを送信しました。" : ''
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: m,
                                closeButton: true, 
                                autoClose: true,
                                answers: ['OK'],
                            }) 
                        }, 300);                      
                    }).catch(function (error) {
                        if (error.response) this.errorToast(this.$t(error.response.data.message))
                        else if (error.request) this.errorToast(this.$t('commonError'))
                        else this.errorToast(this.$t('commonError') + error.message)              
                    }.bind(this));            
            },
            commentEdit(id) {
                this.commentEditToggle = true
                // document.getElementById('editComment_' + id).focus();  
                // var elem = document.getElementById('editComment_' + id).style;
                // elem.border = "none";
                // elem.position = "relative";
                // elem.background = "white";
                setTimeout(() => {
                    document.getElementById('editComment_' + id).focus();  
                    document.execCommand('selectAll', false, null);
                    document.getSelection().collapseToEnd();                    
                },0)
                
            },
            commentEditSend(id, message) {            
                var new_text = document.getElementById('editComment_' + id).textContent
                if (new_text == "") {
                    this.$toast.clear();
                    this.$toast("メッセージを空にすることはできません",{toastClassName: "toastConfirm",});
                }else {
                    axios.post('/chat_edit_api', {
                        id: id,
                        message: new_text
                    }).then(response => {
                        
                        this.$parent.$emit('reload')
                        this.commentEditToggle = false;
                        // document.getElementById('editComment_' + id).style.border = "none";
                        // document.getElementById('editComment_' + id).style.position = "unset";
                                          
                    });
                }
            },
            commentEditCancel(id, message) {
                var body = document.getElementById('editComment_' + id);
                // body.style.position = "unset";
                // body.style.border = "none";
                this.commentEditToggle = false;
                body.innerHTML = message;
            }, 
            longTapAction(event){
                event.stopPropagation()
                const xPos = event.type === 'touchstart' ? Math.ceil(event.touches[0].clientX) : Math.ceil(event.clientX);
                const yPos = event.type === 'touchstart' ? Math.ceil(event.touches[0].clientY) : Math.ceil(event.clientY);        
                this.$store.commit('setMenu', {name: 'boardMessageMenu', id: this.message.id})                
                nextTick(() => {
                    const a = document.getElementById('boardMessageMenu');                 
                    if(a){
                        const offset = this.$store.state.mobile ? 40 : 10
                        let l = xPos - 50 < 0 ? 10 : xPos - 50; 
                        let t = yPos - a.clientHeight - offset < 0 ? yPos + offset : yPos - a.clientHeight - offset;
                        a.style.position = 'fixed'
                        a.style.top = t + 'px';
                        a.style.left = l + 'px'; 
                    }
                })   
                    
            },            
            urlCheck: function (text) {
                if(text){                
                    var linkedText = Autolinker.link(text, {stripPrefix: false});       
                    const catch_tag = '<a href=/app/public/user?id=' 
                    const rep_tag = '<a class="mntuser" style="cursor:pointer" id=' 
                    linkedText = linkedText.replaceAll(catch_tag, rep_tag);
                    return linkedText;                
                }            
            },
            reactButtonView(msg){
                if(msg.user_id == this.$store.state.user.id && !msg.reacted_users.length){
                    return false;
                }else{
                    return true;
                }
            },
            
            
            

            copyTextStart(id){   
                const widthS = document.getElementById('editComment_' + id).clientWidth + 20;
                const heightS = document.getElementById('editComment_' + id).clientHeight + 20;
                const text = document.getElementById('editComment_' + id).textContent;
                const ob = {
                    height: heightS,
                    width: widthS,
                    text: text
                }
                this.$parent.$emit('copyText', ob)               
            }, 
            messageMenu(){
                if(this.commentEditToggle) return
                this.commentMenuLayer = 0
                this.topOrBottom = 'top:18px'
                this.right = 'right: 23px'
                this.$store.commit('setMenu', {name: 'boardMessageMenu', id: this.message.id}) 
                setTimeout(() =>{
                    const menu = document.getElementById('boardMessageMenu')
                    const rect = menu.getBoundingClientRect()
                    if(rect.left < 0){
                        this.right = 'right: auto'
                    }
                })               
                if(this.mIndex !==0) return
                const el = document.getElementById('messageRoot_' + this.message.id)
                
                if(el && el.clientHeight < 250 ){
                    this.topOrBottom = 'top:auto;bottom:30px'
                }
            },
            viewReactedUsersList(){
                const data = {
                    active: true,
                    userList: this.reactedUsersListAll,
                    title: this.$t('reacted')
                }
                this.$store.commit('setMessageUsers', data)
                
            },
            viewCheckedUserList(){
                const data = {
                    active: true,
                    userList: this.message.checked_users,
                    title: this.$t('checkedMembers')
                }
                this.$store.commit('setMessageUsers', data)
            },
            viewunCheckedUserList(){
                const data = {
                    active: true,
                    userList: this.message.unchecked_users,
                    title: this.$t('uncheckedMembers')
                }
                this.$store.commit('setMessageUsers', data)
            },
            setMargin(id, val){
                setTimeout(() => {
                    // const cont = document.getElementById('messageRoot_' + this.message.id)
                    
                    const el = document.getElementById(id)
                    
                    if(el){
                        this.lastMenuPx = el.clientHeight - val
                    }                                   
                },0)
            },
            commentDeleteConfirm: function(id){                     
                   
                const uniqueChannell = Math.random().toString(36).substring(5);
                const toastData = {                    
                    active: true,  
                    type: 'info', 
                    content: this.$t('confirmToDeleteMessage') ,
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                    channel: uniqueChannell
                };                       

                emitter.emit('setToast',toastData)            
                emitter.on(uniqueChannell, (data) => {                                       
                    if(data.answer === this.$t('confirmToAction')){
                        this.commentDelete(id);
                    }                 
                });
                
            }, 
            commentDelete: function (id) {
                axios.post('/chat_delete_api', {id: id}).then(response => {                    
                    this.$parent.$emit('reload')                                     
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)              
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
            pushInstantUser(event, id){
                if(id == this.$store.state.user.id) return
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
            checkSendAreaClick(event, msg){
                var checked_user = msg.reacted_users.filter(ob => ob.id == this.$store.state.user.id).length;   
                var elem = event.currentTarget.firstChild         
                if(msg.user_id == this.$store.state.user.id){
                    return
                }                
                this.reacting = !checked_user             
                axios.post('/send_reaction_api', {id: msg.id}).then(response => {     
                    this.$parent.$emit('reload')
                    if(msg.check_flag == 1){
                        this.checkSendConfirm(response.data);
                    }               
                });  
            }, 
            checkSendConfirm(msg){
                var checked = msg.checked_users.filter(ob => ob.id == this.$store.state.user.id).length
                var unchecked = msg.unchecked_users.filter(ob => ob.id == this.$store.state.user.id).length
                var reacted =   msg.reacted_users.filter(ob => ob.id == this.$store.state.user.id).length          
                if(unchecked && reacted){     
                    var uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('confirmToCheck') ,
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                        channel: uniqueChannell
                    })            
                    emitter.on(uniqueChannell, (data) => { 
                        if(data.answer == this.$t('confirmToAction')){
                            this.checkSend(msg.id, 'check')
                        }
                    });                     
                }
                if(checked && reacted){                  
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('alreadyConfirmed') ,
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('OK')],
                    })   
                }
            },
            checkSend (id, which) {            
                const params = {
                    message_id: id,
                    user_id: this.$store.state.user.id,
                    pattern: which
                };
                axios.post('/check_send_api', params).then(response => 
                {
                    this.$parent.$emit('reload')
                });
            },
            shareTo(to, flag, single_file){
                let files = []
                this.message.message_files.forEach(element => {
                    const file = {
                        path: `/shared_files/${this.message.record_id}/${element.id}_${element.user_id}_${element.message_id}.${element.extension}`,
                        record: element
                    }
                    files.push(file)
                });
                const shareData = {
                    message: this.message,
                    title: '',
                    text: this.message.message_text,
                    files: files,
                    from: 'message',
                    to: to,
                    drag: false,
                    instruction: to == 'board' ? '送る先のボードを選択してください' : ''
                }
                this.$store.commit('setSharingData', shareData)
                if(to == 'memo' || to == 'task'){
                    if(this.$store.state.mobile){
                        setTimeout(() =>{
                            this.$router.push({name: to})
                        },0) 
                    }else{           
                        const data = to == 'task' ? 1 : 2            
                        emitter.emit('messageShareTo',data)                            
                    }
                }else if(to !== 'board'){
                    this.$router.push({name: to})
                }
                else if(to == 'board' && this.$store.state.mobile){
                    this.$router.push({name: 'board'})
                }
                this.closeMenu()
            }
              
        },
    }
</script>
