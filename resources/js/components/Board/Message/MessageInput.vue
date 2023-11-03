<template>
    <div id="footArea" 
        style="padding:0;position:relative"
        @dragenter="footerDropEnter" 
        @dragover.prevent 
        @drop.prevent 
        @mouseenter="footerDropEnterFromFile"
        class="footAreaContainer" 
        v-show="openedBoard">
                <div v-if="charLength >= 5000" style="position: absolute;right: 10px;top: -20px;font-size: 12px;color: tomato;">{{ $t('messageLimitAlert') }}</div>
                <div @click="$emit('unreadJumped')" v-if="unread.status" class="unread" style="position:absolute;top: -40px;bottom:auto;user-select:none;">
                    <p class="unread-inner cursor-pointer">{{ $tc('newMessagesWithCount', { num: unread.count}) }}</p>
                </div> 
                <Transition name="replyQuotBox">
                    <ReplyQuotWindow 
                        v-if="$store.state.quot_reply.active"
                        :key="replyKey"/>
                </Transition>
                <Transition name="replyQuotBox">
                    <ForwardWindowMessage 
                        v-if="forwardItem"
                        :item="forwardItem"
                        @cancel="forwardItem = null"
                    />
                </Transition>

                <span 
                    @dragleave="footerDropLeave" 
                    @mouseleave="footerDropLeaveFromFile" 
                    @mouseup.stop="dropSharingItems" 
                    @dragover.prevent 
                    @drop.prevent="footerDropDropped"
                    :style="{color: '#fff', height: dropActive ? '100%' : '0'}" 
                    id="footerDropArea" 
                    class="download-area">{{$t('fileDrop')}}
                </span>

               
                <!-- <Transition name="modalFade">
                <ul id="mentionedPc" ref="mentionBox" v-if="keyCharacters.length || mentionBoxToggle" class="mentionBox" :style="mentionBoxPosition()">
                    <li :id="'mentionAble_' + index" :key="index" @keyup.enter="mentionUser(user, index)" @click.stop.prevent="mentionUser(user, index)" v-for="(user, index) in mentionAbleList" class="mentionBox-inner" :class="{mUsrSlctdPc: highlighted == index}">                                    
                        <div class="column-01">  
                            <BoardIcon v-if="user.id == -1" imgClass="userMidIcon" :item="openedBoard"/> 
                            <UserIcon v-else size="30" :user="user" imgClass="userMidIcon"/>  
                        </div>
                        <p  class="cursor-pointer" style="padding:5px;font-size:13px;">{{user.name}}</p>                                   
                    </li>                    
                </ul>
                </Transition> -->
                <Transition name="modalFade">
                    <MentionBox 
                        v-if="keyCharacters.length || mentionBoxToggle" 
                        :mentionAbleList="mentionAbleList"
                        :openedBoard="openedBoard"
                        @mentionUser="mentionUser"
                        ref="mentionBox"
                    />
                </Transition>
                <Transition name="downShiftPop">
                    <div v-if="aiResponse" class="ai-prompt-root" style="color: var(--primary-color);" >
                        <span class="form-plc smallPlc" style="font-weight: 600;top: -13px;">ChatGPT修正案</span> 
                        <div v-html="aiResponse" ref="aiResponseText" class="typeBoxArea" style="width: calc(100% - 20px);outline: none;border: none;" :contenteditable="aiResponseCustomize"></div>
                        <div style="width:100%;display: flex;align-items: end;">                            
                            <div @click="replaceText" v-if="aiResponseCustomize" style="margin: 0 10px 10px auto;" class="commentEditButton">適用</div>
                            <div @click="resetAi" v-if="aiResponseCustomize" style="margin: 0 10px 10px 0;" class="commentEditButton">閉じる</div>
                        </div>                        
                    </div>
                </Transition>
               
                <div style="display:inline-block;z-index:5;width:100%;box-sizing: border-box;padding: 10px;position:relative">
                    <div id="attachArea" style="display:inline-block;vertical-align: super;position: absolute;bottom: 13px;">
                        <div style="display:inline-block">
                            <label for="sharedfile" class="cursor-pointer">                                       
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="27" viewBox="0 0 27 32" style="fill: var(--third-color);padding-right: 5px;">
                                <path d="M25.954 7.013c-0.479-0.575-4.378-4.56-5.978-5.816-0.623-0.489-1.284-0.853-2.127-0.949-1.178-0.125-2.97-0.182-4.091-0.22-1.36-0.029-2.472-0.029-3.832-0.029-1.36 0.010-3.008 0.077-4.474 0.172-1.36 0.077-2.328 0.134-2.845 0.22-0.69 0.105-1.188 0.489-1.265 1.303-0.077 0.805-0.172 4.905-0.172 7.454 0.010 2.558 0.115 5.835 0.201 6.822 0.096 0.987 0.556 1.447 1.083 1.504 0.527 0.067 0.843-0.537 0.853-1.159 0.019-0.623 0.019-1.226 0.019-1.734s-0.048-2.913-0.019-5.432c0.029-2.098 0.086-4.206 0.192-6.304 0.010-0.134 0.115-0.24 0.249-0.24 0.92-0.029 1.849-0.048 2.778-0.058 1.341-0.019 2.683-0.019 4.024-0.010s2.683 0.029 4.024 0.058c0.987 0.019 1.983 0.048 2.96 0.086 0.153 0.010 0.268 0.134 0.268 0.287-0.010 0.901-0.019 3.612-0.019 3.612 0 0.546 0.010 1.083 0.019 1.629v0.010c0.010 0.546 0.45 0.987 0.996 0.987l1.705 0.019h1.705c0.441 0 1.428-0.019 1.926-0.029 0.153 0 0.287 0.125 0.297 0.278 0.048 1.399 0.067 2.807 0.077 4.216 0.010 1.878 0 3.756-0.029 5.634s-0.077 3.756-0.153 5.624c-0.067 1.514-0.144 3.037-0.268 4.532-0.019 0.201-0.182 0.355-0.383 0.364-1.418 0.038-2.778 0.067-4.302 0.077-1.648 0.010-6.266 0.010-8.163 0-1.964-0.010-5.365-0.029-7.042-0.086-0.125 0-0.24-0.153-0.259-0.278-0.058-0.374-0.105-0.834-0.163-1.389-0.067-0.623-0.469-1.092-1.035-1.025-0.45 0.048-0.824 0.45-0.891 1.198-0.067 0.738-0.019 1.619 0.067 2.213s0.441 1.016 1.198 1.14c1.006 0.163 5.72 0.249 8.057 0.268 2.347 0.019 6.275-0.019 8.259-0.019 1.974-0.010 3.286-0.019 4.857-0.182 1.121-0.115 1.408-0.747 1.552-1.715 0.24-1.667 0.345-3.325 0.469-4.982 0.134-1.887 0.24-3.775 0.326-5.672 0.086-1.887 0.144-3.784 0.192-5.672 0.029-1.428 0.038-3.21 0.019-4.235-0.010-0.948-0.287-1.782-0.862-2.472zM19.832 7.023c-0.019-0.537-0.077-2.060-0.096-2.692 0-0.096 0.105-0.144 0.182-0.086 0.537 0.489 2.491 2.271 3.152 2.874 0.077 0.067 0.029 0.192-0.077 0.182-0.719-0.029-2.434-0.086-2.98-0.105-0.096 0.010-0.182-0.077-0.182-0.172z"></path>
                                <path d="M18.405 25.61l2.050-6.189c0.029-0.086 0.048-0.182 0.048-0.268 0-0.45-0.383-0.843-0.881-0.843h-18.74c-0.24 0-0.46 0.096-0.623 0.249s-0.259 0.364-0.259 0.604v6.189c0 0.23 0.096 0.441 0.259 0.594s0.383 0.249 0.623 0.249h16.69c0.374-0.010 0.709-0.24 0.834-0.584zM22.41 11.89c0.019-0.383-0.278-0.719-0.671-0.738-1.284-0.067-2.568-0.096-3.842-0.115-0.642-0.010-1.284-0.029-1.926-0.029l-1.926-0.010-1.926 0.010-1.926 0.029c-1.284 0.019-2.568 0.038-3.842 0.086-0.374 0.019-0.69 0.316-0.699 0.699-0.010 0.402 0.297 0.738 0.699 0.757 1.284 0.048 2.568 0.067 3.842 0.086l1.926 0.019 1.926 0.010 1.926-0.010c0.642 0 1.284-0.019 1.926-0.029 1.284-0.019 2.568-0.048 3.842-0.115 0.364-0.010 0.651-0.287 0.671-0.652zM15.875 14.63c-0.527-0.010-1.054-0.029-1.581-0.029l-1.59-0.010-1.581 0.010-1.59 0.029c-1.054 0.019-2.117 0.038-3.171 0.086-0.374 0.019-0.68 0.316-0.69 0.699-0.019 0.402 0.297 0.738 0.69 0.757 1.054 0.048 2.117 0.067 3.171 0.086l1.59 0.029 1.581 0.010 1.59-0.010c0.527 0 1.054-0.019 1.581-0.029 1.054-0.019 2.117-0.048 3.171-0.115 0.345-0.019 0.632-0.297 0.661-0.661 0.019-0.383-0.268-0.719-0.661-0.738-1.054-0.057-2.117-0.086-3.171-0.115z"></path>
                            </svg>  
                            </label>
                            <input multiple type="file" name="sharedfile" id="sharedfile" v-on:change="addAttachment" style="display: none;">
                        </div>
                    </div>
                    
                
                    <div v-show="successUploadedFiles.length">
                        <div class="preUploadImage">    
                            <div :key="image.id" v-for="image in successUploadedFiles" style="margin: auto 10px 10px 0;min-height: 40px;user-select:none">
                                <img draggable="false" v-if="image.mime_type == 'image'" :src="$store.state.baseLocation + '/temp_upload/' + image.id + '.' + image.extension">                                
                                <FileIcon v-if="image.mime_type !== 'image'" :ext="image.extension"/>
                                <p class="shared-file-name">{{image.name}}</p>
                                <button @click="removeAttachment(image)">
                                    <svg @click.prevent version="1.1" xmlns="http://www.w3.org/2000/svg" width="7" height="7" viewBox="0 0 32 32" fill="var(--primary-color)" style="pointer-events: none;">
                                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                    </svg>  
                                </button>                                                   
                            </div>                                          
                        </div>
                    </div>

                    <div v-show="sharingFiles.length">
                        <div class="preUploadImage">  
                            <div :key="image.record.id" v-for="image in sharingFiles" style="margin: auto 10px 10px 0;user-select:none">
                                <img draggable="false" v-if="image.record.mime_type == 'image'" :src="image.path" >
                                <FileIcon v-if="image.record.mime_type !== 'image'" :ext="image.record.extension"/>
                                <p class="shared-file-name">{{image.record.name}}</p>
                                <button @click="removeSharingFile($event,image)">
                                    <svg @click.prevent version="1.1" xmlns="http://www.w3.org/2000/svg" width="7" height="7" viewBox="0 0 32 32" fill="var(--primary-color)" style="pointer-events: none;">
                                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                    </svg>  
                                </button>                                                    
                            </div>                                           
                        </div>
                    </div> 


                    <div class="pBarC" v-show="progressPercentage">
                        <div :style="{width: progressPercentage + '%'}"></div>
                    </div>
                    <div
                        ref="messageInputArea"
                        @keydown.enter="enterSend"
                        @keydown="msgSave()" 
                        @keyup="caretPos" 
                        @click="caretPos"
                        @paste="pasteListener($event)" 
                        @focus="focused"
                        @blur="blured"
                        @input="setInput"
                        @compositionupdate="composeUpdate"
                        id="typeArea" 
                        contenteditable="true" 
                        :class="['typeBoxArea',  'boardTypeArea', {maxLengthAlert: charLength >= 5000}]">
                    </div>
                    
                    <div class="pc" style="position:absolute;right: 60px;bottom: 18px;cursor: pointer;" @click.stop="$store.commit('setMenu', {name: 'EmojiPicker', id: 1002})">                                            
                       
                        <svg style="fill: var(--third-color);" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 30 30">
                            <path d="M14.977,0C6.735-0.056-0.127,6.93,0.002,15.153c-0.028,8.165,6.816,14.938,14.975,14.811v-0.04c0.967,0.013,1.936-0.067,2.889-0.242c4.817-0.863,9.055-4.275,10.937-8.8C32.985,11.039,25.688-0.021,14.977,0 M14.977,27.902C6.08,27.658-0.075,18.755,3.433,10.373C7.814,0.291,22.13,0.293,26.49,10.386C30.002,18.61,23.886,27.788,14.977,27.902"/>
                            <path d="M22.441,18.263c-0.623-0.436-1.479-0.284-1.917,0.338c0.007-0.011,0.002-0.006-0.001-0.004c-0.002,0.002-0.006,0.005-0.011,0.01l-0.027,0.025c-0.734,0.658-1.568,1.264-2.479,1.639c-0.291,0.123-0.596,0.222-0.9,0.292c-0.67,0.185-1.332,0.349-2.043,0.376c-2.039,0.059-4.107-0.841-5.435-2.355c-1.226-1.563-3.443,0.199-2.196,1.769c0.199,0.27,0.418,0.529,0.646,0.772c1.784,1.911,4.359,3.094,6.986,3.106c1.119,0.021,2.305-0.08,3.354-0.525c1.753-0.72,3.36-1.896,4.362-3.526C23.214,19.556,23.063,18.698,22.441,18.263"/>
                            <path d="M18.513,14.558c0.905,0.201,1.834-0.509,2.073-1.585c0.239-1.076-0.302-2.111-1.208-2.313c-0.904-0.201-1.833,0.509-2.072,1.585C17.065,13.322,17.606,14.357,18.513,14.558"/>
                            <path d="M11.44,14.558c0.906-0.201,1.446-1.236,1.208-2.313c-0.239-1.076-1.167-1.786-2.074-1.585c-0.906,0.203-1.446,1.238-1.208,2.313C9.605,14.049,10.534,14.759,11.44,14.558"/>
                        </svg>

                    </div>   
                    <div @click="editWithAi" class="ai-loader-button">                                           
                        <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 30 30" width="23" height="23" style="fill: var(--third-color);" :class="[{'rotate-gtp' : editing}]">
                            <path d="M 14.070312 2 C 11.330615 2 8.9844456 3.7162572 8.0390625 6.1269531 C 6.061324 6.3911222 4.2941948 7.5446684 3.2773438 9.3066406 C 1.9078196 11.678948 2.2198602 14.567816 3.8339844 16.591797 C 3.0745422 18.436097 3.1891418 20.543674 4.2050781 22.304688 C 5.5751778 24.677992 8.2359331 25.852135 10.796875 25.464844 C 12.014412 27.045167 13.895916 28 15.929688 28 C 18.669385 28 21.015554 26.283743 21.960938 23.873047 C 23.938676 23.608878 25.705805 22.455332 26.722656 20.693359 C 28.09218 18.321052 27.78014 15.432184 26.166016 13.408203 C 26.925458 11.563903 26.810858 9.4563257 25.794922 7.6953125 C 24.424822 5.3220082 21.764067 4.1478652 19.203125 4.5351562 C 17.985588 2.9548328 16.104084 2 14.070312 2 z M 14.070312 4 C 15.226446 4 16.310639 4.4546405 17.130859 5.2265625 C 17.068225 5.2600447 17.003357 5.2865019 16.941406 5.3222656 L 12.501953 7.8867188 C 12.039953 8.1527187 11.753953 8.6456875 11.751953 9.1796875 L 11.724609 15.146484 L 9.5898438 13.900391 L 9.5898438 8.4804688 C 9.5898438 6.0104687 11.600312 4 14.070312 4 z M 20.492188 6.4667969 C 21.927441 6.5689063 23.290625 7.3584375 24.0625 8.6953125 C 24.640485 9.696213 24.789458 10.862812 24.53125 11.958984 C 24.470201 11.920997 24.414287 11.878008 24.351562 11.841797 L 19.910156 9.2773438 C 19.448156 9.0113437 18.879016 9.0103906 18.416016 9.2753906 L 13.236328 12.236328 L 13.248047 9.765625 L 17.941406 7.0546875 C 18.743531 6.5915625 19.631035 6.4055313 20.492188 6.4667969 z M 7.5996094 8.2675781 C 7.5972783 8.3387539 7.5898438 8.4087418 7.5898438 8.4804688 L 7.5898438 13.607422 C 7.5898438 14.141422 7.8729844 14.635297 8.3339844 14.904297 L 13.488281 17.910156 L 11.34375 19.134766 L 6.6484375 16.425781 C 4.5094375 15.190781 3.7747656 12.443687 5.0097656 10.304688 C 5.5874162 9.3043657 6.522013 8.5923015 7.5996094 8.2675781 z M 18.65625 10.865234 L 23.351562 13.574219 C 25.490562 14.809219 26.225234 17.556313 24.990234 19.695312 C 24.412584 20.695634 23.477987 21.407698 22.400391 21.732422 C 22.402722 21.661246 22.410156 21.591258 22.410156 21.519531 L 22.410156 16.392578 C 22.410156 15.858578 22.127016 15.364703 21.666016 15.095703 L 16.511719 12.089844 L 18.65625 10.865234 z M 15.009766 12.947266 L 16.78125 13.980469 L 16.771484 16.035156 L 14.990234 17.052734 L 13.21875 16.017578 L 13.228516 13.964844 L 15.009766 12.947266 z M 18.275391 14.853516 L 20.410156 16.099609 L 20.410156 21.519531 C 20.410156 23.989531 18.399687 26 15.929688 26 C 14.773554 26 13.689361 25.54536 12.869141 24.773438 C 12.931775 24.739955 12.996643 24.713498 13.058594 24.677734 L 17.498047 22.113281 C 17.960047 21.847281 18.246047 21.354312 18.248047 20.820312 L 18.275391 14.853516 z M 16.763672 17.763672 L 16.751953 20.234375 L 12.058594 22.945312 C 9.9195938 24.180312 7.1725 23.443687 5.9375 21.304688 C 5.3595152 20.303787 5.2105423 19.137188 5.46875 18.041016 C 5.5297994 18.079003 5.5857129 18.121992 5.6484375 18.158203 L 10.089844 20.722656 C 10.551844 20.988656 11.120984 20.989609 11.583984 20.724609 L 16.763672 17.763672 z"/>
                        </svg>
                   </div>  

                        <Transition name="modalFade">
                            <div id="EmojiPicker" v-if="$store.state.menu.name == 'EmojiPicker' && $store.state.menu.id == 1002">
                                <EmojiPicker                                     
                                    :native="true" 
                                    @select="selectEmoji" 
                                    :hide-search="true" 
                                    :hide-group-names="true" 
                                    theme="dark" 
                                    :disable-sticky-group-names="true" 
                                    :disable-skin-tones="true"
                                    :display-recent="true"
                                />
                            </div>
                           
                        </Transition>
                    <div @mousedown.prevent.stop @click="commentSendConfirm(openedBoard.id)" id="sendArea" class="sendAreaBox" style="display:flex;bottom:6px;">                                    
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="33" viewBox="0 0 43 32" style="margin:auto;fill: var(--third-color);">
                            <path d="M40.638 0.087c-1.842 0.361-6.097 1.292-9.435 2.047l-30.046 6.891c-0.419 0.096-0.793 0.374-1.003 0.793-0.364 0.728-0.058 1.585 0.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56 0.287 0.157 0.487 0.439 0.542 0.762 0 0 0.711 4.473 0.921 5.891 0.21 1.417 0.714 4.465 1.184 6.482 0.168 0.726 0.631 1.335 1.215 1.512 0.495 0.152 1.030 0.037 1.43-0.285 1.394-1.128 5.787-5.445 7.388-7.272 0.133-0.152 0.355-0.19 0.531-0.085l6.184 3.646c0 0 0.439 0.294 0.919 0.519 1.283 0.601 2.479 0.625 3.062-0.829 0.325-0.813 4.316-12.627 4.316-12.627l4.466-13.209c0.053-0.152 0.082-0.321 0.082-0.492 0-0.844-0.654-1.675-2.496-1.312zM20.045 24.741c-0.475 0.477-1.473 1.473-2.284 2.197-0.155 0.137-0.385-0.002-0.313-0.195l1.796-4.842c0.051-0.157 0.236-0.226 0.378-0.142l1.796 1.054c0.157 0.091 0.161 0.294 0.041 0.432-0.401 0.458-0.975 1.058-1.413 1.495zM32.151 25.117c-0.106 0.325-0.482 0.47-0.777 0.301l-1.447-0.824-3.554-2.014-7.121-4.024c-0.067-0.037-0.138-0.068-0.214-0.094-0.677-0.232-1.411 0.13-1.64 0.808l-1.944 7.086c-0.053 0.166-0.229 0.143-0.251-0.046-0.13-1.23-0.328-3.178-0.467-4.759-0.13-1.459-0.366-3.357-0.494-4.434-0.111-0.931-0.427-1.423-1.131-1.837-0.704-0.415-6.489-3.354-7.668-4.049-0.241-0.142-0.166-0.415 0.065-0.463 0 0 13.334-2.689 16.022-3.304 2.689-0.617 10.513-2.447 10.513-2.447 0.103-0.025 0.152 0.118 0.056 0.161l-5.127 2.281-2.961 1.459c-0.987 0.487-7.32 3.516-9.259 4.562-0.477 0.258-0.665 0.871-0.373 1.36 0.255 0.429 0.808 0.574 1.265 0.374 2.004-0.882 16.208-7.766 17.651-8.441 0.345-0.162 0.376-0.012 0.287 0.049-0.89 0.615-9.43 6.896-10.25 7.528l-2.448 1.905c-0.432 0.342-0.519 0.976-0.173 1.42 0.335 0.432 0.965 0.497 1.413 0.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66c0 0 5.775-4.365 6.187-4.682 0.166-0.128 0.397 0.033 0.331 0.234l-2.517 7.675-3.585 10.965z"></path>
                        </svg>                                          
                    </div>                                     
                </div>
                
    </div>  
</template>

<script>
import ReplyQuotWindow from './ReplyQuotWindow.vue'
import ForwardWindowMessage from './ForwardWindowMessage.vue'
import moment from 'moment'
import EmojiPicker from 'vue3-emoji-picker'
import FileIcon from '../Mixed/FileIcon.vue'
import OpenAI from "openai";
import MentionBox from './MentionBox.vue'
import 'vue3-emoji-picker/css'
    export default {
        props: ['openedBoard', 'replyKey', 'unread', 'messageListType'],
        data(){
            return{
                
                caretPosition: 0,
                mentionBoxToggle: false,
                emojiBox: false,
                sendLoader: false,
                highlighted: 0,
                tempChar: '',
                attachedFiles: [],
                importedFiles: [],
                attachForwardFiles: [],
                messageReady: '',
                emojiFlag: 0,
                mentionRecursive: false,
                mentionCounter: 0,
                draggingFiles: [],
                exportingFiles: [],
                mentionedUsers: [],
                successUploadedFiles: [],
                progressPercentage: 0,
                keyCharacters: '',
                startPosition: 0,
                forwardItem: null,
                charLength: 0,
                editing: false,
                aiResponse: '',
                aiResponseCustomize: false,
                dropActive: false,
                sharingFiles: []
                
                
            }   
        },
        components: {
            // VEmojiPicker,
            ReplyQuotWindow,
            ForwardWindowMessage,
            EmojiPicker,
            FileIcon,
            MentionBox
        },
        watch:{
            keyCharacters(after, before){
                if(!after){
                    this.resetMention()
                }
            }
        },
        unmounted() {
            this.$refs.messageInputArea?.removeEventListener('keyup', this.inputKeyEventfirst);  
            this.$refs.messageInputArea?.removeEventListener('keyup',this.inputKeyEventSecond);
        },
        mounted() {
            emitter.on('aiEditFinish', (data) => {  
                this.$refs.messageInputArea.textContent = data.text                           
            })
            
            if(this.$store.state.sharingData && this.$store.state.sharingData.drag == false){
                if(this.$store.state.sharingData.message){
                    this.forwardItem = this.$store.state.sharingData.message
                }else{
                    this.$store.state.sharingData.files.forEach((file) => {
                        const isFolder = file.record.hasOwnProperty('folder') && file.record.folder == 1
                        if(!isFolder && !this.sharingFiles.includes(item => item.path == file.path)){                            
                            this.sharingFiles.push(file)
                        }
                    });
                }
                

                this.$store.commit('setSharingData', null)
                this.footerDropLeave();
                
            }
            if(this.openedBoard){
                const temp = localStorage.getItem('temp_message_' + this.openedBoard.id); 
                if(temp){
                    var el = document.getElementById("typeArea");
                    el.textContent = temp;
                }
            }

            this.$refs.messageInputArea.addEventListener('keyup', this.inputKeyEventfirst);              
            this.$refs.messageInputArea.addEventListener('keyup',this.inputKeyEventSecond)
            
        },
        computed:{
            filteredUsers(){
                const list = this.$store.state.mentionAbleUsers.map(ob => ob.user)
                if(this.openedBoard.private_flag == 0){
                    list.unshift({name: '全員', id: -1})
                }
                return list
            },  
            mentionAbleList(){
                    if (this.keyCharacters) {
                    const keyCharactersLowerCase = this.keyCharacters.replace(/[@＠]/g, '').toLowerCase()
                    return this.filteredUsers.filter(member => {
                        const memberNameLowerCase = member.name.toLowerCase()
                        return memberNameLowerCase.includes(keyCharactersLowerCase)
                    })
                    } else {
                    return this.filteredUsers
                    }
            }          
        },
        methods:{
            resetAi(){
                this.aiResponse = ''
                this.aiResponseCustomize = false
            },
            replaceText(){
                try{
                    this.$refs.messageInputArea.textContent = this.$refs.aiResponseText.textContent
                    this.resetAi()
                }
                catch{
                    this.$toast('適用するに失敗しました。',{
                        toastClassName: "toastConfirm",
                        timeout: 3000, 
                        draggable: false,
                        closeButton: false,
                    });
                }
                
            },
            async editWithAi(){
                if(this.editing) return
                const text = this.$refs.messageInputArea.textContent
                if(text && text.length){
                    this.aiResponseCustomize = false
                    
                    const full = '文章を修正してください。' + text
                    const openai = new OpenAI({
                        apiKey: process.env.MIX_OPENAI_API_KEY,
                        dangerouslyAllowBrowser: true 
                    });
                    this.editing = true
                    this.aiResponse = ''
                    const stream = await openai.chat.completions.create({
                        // model: 'gpt-4',
                        model: 'gpt-3.5-turbo-16k',
                        messages: [{ role: 'assistant', content: full }],
                        stream: true,
                        temperature: 0.8
                    })
                    .catch((err) => {
                        if (err instanceof OpenAI.APIError) {
                            console.log(err.status); 
                            console.log(err); 
                            if(err.status == 500){
                                this.errorToast('ChatGPT修正に失敗しました。<br>ChatGPTサーバーから反応がありませんでした。しばらく立ってから再度お試しください。')
                            }else{
                                this.errorToast('ChatGPT修正に失敗しました。<br>' + err.message)
                            }
                            
                        } else {
                            this.errorToast('ChatGPT修正に失敗しました。<br>' + err)
                        }
                        this.editing = false
                        this.aiResponseCustomize = true
                    });
                    // try {
                        for await (const part of stream) {
                            const content = part.choices[0]?.delta?.content || ''
                            this.aiResponse = this.aiResponse + content
                        }
                    // } catch (error) {
                    //     this.errorToast('ChatGPT修正に失敗しました。<br>' + error)
                    // } finally {
                        this.editing = false
                        this.aiResponseCustomize = true
                    // }
                    
                    
                }


                // return
                
                // if(text && text.length){
                //     this.editing = true
                //     axios.post('/get_review_text', {text : text})
                //     .then(response => {    
                //         this.editing = false
                //         const data = {
                //             user_text: text,
                //             edited_text: response.data,
                //             view: true
                //         }
                //         this.$store.commit('setAiData', data)
                //     }).catch((error) => {
                //         this.$toast('Open AIレビューに失敗しました。',{
                //             toastClassName: "toastConfirm",
                //             timeout: 3000, 
                //             draggable: false,
                //             closeButton: false,
                //         });
                //         this.editing = false
                //     })
                // }
            },
            setInput(){
                this.charLength = event.target.innerText.length
            },            
            inputKeyEventSecond(){
                if(this.openedBoard){
                    var editableEl = document.getElementById("typeArea");
                        if(editableEl){
                            this.getCharacterPrecedingCaret(editableEl);
                        }
                }  
            },
            inputKeyEventfirst(){
                this.startPosition = this.getCaretPosition();
                if(this.openedBoard && this.mentionBoxToggle){            
                    var editableEl = document.getElementById("typeArea");
                    var singleChar = this.getCharacterPrecedingCaret(editableEl, 3);
                    if(editableEl.textContent.lastIndexOf('@') > -1){
                        this.charSet = editableEl.textContent.split('@').pop();
                    }
                    if(editableEl.textContent.lastIndexOf('＠') > -1){
                        this.charSet = editableEl.textContent.split('＠').pop();   
                    }
                    if (event.key === 'Backspace' || event.key === 'Delete') {
                        // Reset mention when @ is deleted
                        const textBeforeCursor = this.$refs.messageInputArea.textContent.substring(0, this.getCaretPosition());
                        console.log(textBeforeCursor)
                        const lastAtSign = textBeforeCursor.lastIndexOf('@');
                        if (lastAtSign === -1 || lastAtSign < this.startPosition - 1) {
                            this.resetMention();
                        }  
                    } 
                } 
            },
            getCaretPosition() {
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                return range.endOffset;
                }
                return 0;
            },
            resetMention(){
                this.mentionBoxToggle = false
                this.highlighted = 0
            },
            selectEmoji(emoji) {     
                var a = document.getElementById('typeArea').textContent;    
                var b = emoji.i;
                var position = this.caretPosition;
                var output = [a.slice(0, position), b, a.slice(position)].join('');
                document.getElementById('typeArea').textContent = output
                this.caretPosition = this.caretPosition + 2;
                this.msgSave();
            },
            createMention(){            
                
                if(this.messageReady.indexOf('[To:') > -1){
                    this.mentionRecursive = true;
                    var replace = this.messageReady.substring(
                        this.messageReady.indexOf(":") + 1, 
                        this.messageReady.indexOf("]")
                    );  

                    if(replace === '全員'){
                        this.messageReady = this.messageReady.replace('[To:全員]', ' <span class="toAll">@全員</span>');
                        var list = this.$store.state.mentionAbleUsers.map(obj => obj.user_id);
                        
                        this.mentionedUsers = list;
                        this.createMention()   
                        this.mentionCounter ++
                        
                    }else{
                        
                        var filtered = this.filteredUsers.filter(obj=>obj.name === replace)
                        if(filtered.length){

                            var user = filtered[0]  
                            let members = this.$store.state.mentionAbleUsers;
                            let check_member = members.filter( obj => obj.user.id == user.id)       
                            
                            var check = this.mentionedUsers.indexOf(user.id);
                            if(check == -1 && check_member.length){
                                this.mentionedUsers.push(user.id);  
                                this.messageReady = this.messageReady.replace('[To:'+user.name+']', ' <a href=/app/public/user?id='+user.id+'>@'+user.name+'</a>');    
                                this.createMention()   
                                this.mentionCounter ++                   
                            }else{
                                this.mentionRecursive = false;
                                this.mentionCounter = 0;
                            } 
                        
                        }else{
                            this.mentionRecursive = false;
                            this.mentionCounter = 0;
                        }
                    }             
                    
                }else{
                    this.mentionRecursive = false;
                    this.mentionCounter = 0;
                }
            }, 
            commentSendConfirm(recordId){
                var textCheck = document.getElementById('typeArea').textContent;     
                var nospace = textCheck.replace(/\s/g, "")       
                this.charLength = textCheck.length
                if(!nospace && (!this.attachedFiles || !this.attachedFiles.length) && !this.sharingFiles.length){            
                    return;
                }
                if(this.charLength >= 5000){
                    return;
                }
                this.messageReady = textCheck 
                var nospace = textCheck.replace(/\s/g, "")            
                if(nospace.length && nospace.length == 2){      
                    var ranges = [
                        '(?:[\u2700-\u27bf]|(?:\ud83c[\udde6-\uddff]){2}|[\ud800-\udbff][\udc00-\udfff]|[\u0023-\u0039]\ufe0f?\u20e3|\u3299|\u3297|\u303d|\u3030|\u24c2|\ud83c[\udd70-\udd71]|\ud83c[\udd7e-\udd7f]|\ud83c\udd8e|\ud83c[\udd91-\udd9a]|\ud83c[\udde6-\uddff]|[\ud83c[\ude01-\ude02]|\ud83c\ude1a|\ud83c\ude2f|[\ud83c[\ude32-\ude3a]|[\ud83c[\ude50-\ude51]|\u203c|\u2049|[\u25aa-\u25ab]|\u25b6|\u25c0|[\u25fb-\u25fe]|\u00a9|\u00ae|\u2122|\u2139|\ud83c\udc04|[\u2600-\u26FF]|\u2b05|\u2b06|\u2b07|\u2b1b|\u2b1c|\u2b50|\u2b55|\u231a|\u231b|\u2328|\u23cf|[\u23e9-\u23f3]|[\u23f8-\u23fa]|\ud83c\udccf|\u2934|\u2935|[\u2190-\u21ff])' // U+1F680 to U+1F6FF
                    ];
                    if (textCheck.match(ranges.join('|'))) {
                        this.emojiFlag = 1; 
                    } 
                }         
                this.createMention()
                if(this.mentionRecursive){
                    this.commentSendConfirm(recordId)
                }else{                                    
                    // this.sendDisable = true;
                    
                    //     this.sendLoader = true;
                    const a = Date.now().toString();
                    const b = Math.random().toString(36).substring(5);
                    const m_uid = a + '_' + b
                    // return 
                    const replyFlag = this.$store.state.quot_reply.active && this.$store.state.quot_reply.which == 'reply'
                    const replyId = replyFlag ? this.$store.state.quot_reply.message.id : null
                    const quotFlag = this.$store.state.quot_reply.active && this.$store.state.quot_reply.which == 'quot'
                    const quotId = quotFlag ? this.$store.state.quot_reply.message.id : null 
                    const selected_quot_text = quotFlag && quotId ? this.$store.state.quot_reply.text : null
                    const files = this.attachedFiles && this.attachedFiles.length ? this.attachedFiles : []
                    const message_quot = quotFlag ? this.$store.state.quot_reply.message : null
                    const message_reply = replyFlag ? this.$store.state.quot_reply.message : null
                    const forward_message_id = this.forwardItem ? this.forwardItem.id : null
                    const message_forward = this.forwardItem ? this.forwardItem : null
                    const queueMessage = {
                        deleted_at: null,
                        emoji_flag: this.emojiFlag,
                        message: this.messageReady,
                        user: this.$store.state.user,
                        reply_flag: replyFlag,
                        reply_id: replyId,
                        quot_flag: quotFlag,
                        quot_id: quotId,
                        quot_message: selected_quot_text,
                        forward_message_id: forward_message_id,
                        record_id: this.openedBoard.id,
                        mentioned_users: this.mentionedUsers,
                        user_id: this.$store.state.user.id,
                        id: Math.random().toString(36).substring(5),
                        attached_temp_files: this.successUploadedFiles,
                        message_quot: message_quot,
                        message_reply: message_reply,
                        message_forward: message_forward,
                        message_attachments: files,
                        created_at: moment().format(),
                        error: false,
                        u_id: m_uid,
                        sharing_files: this.sharingFiles 
                    }
                    this.$refs.messageInputArea.textContent = ''
                    if(this.messageListType == 'search'){
                        
                    }
                    this.$parent.$parent.$emit('addQueue',queueMessage )

                    localStorage.setItem(this.openedBoard.id, '');
                    this.mentionedUsers = [];
                    this.mentionBoxToggle = false;
                    this.successUploadedFiles = [];
                    this.messageReady = null;
                    this.emojiFlag = 0;
                    this.msgSave();   
                    this.attachedFiles = []; 
                              
                    this.$store.commit('setSharingData', null);
                    this.forwardItem = null;
                    this.sharingFiles = []             
                }
            },
             
            allMemberMentionMobile(){
                var all = {
                    name: '全員'
                    
                }
                    
                this.mentionUser(all)
                
            },
            mentionUser(user, index){             
                if(user){
                    const mentionSyntax = `[To:${user.name}]`
                    console.log(mentionSyntax)
                    if(this.mentionBoxToggle){
                    
                        const inputEl = this.$refs.messageInputArea
                        const textBeforeCursor = inputEl.textContent.slice(0, this.caretPosition)
                        const match = textBeforeCursor.match(/[＠@]([^＠@^\s]*)$/);
                        if (match) {
                            const mentionTarget = match[1]
                            const position = this.caretPosition - mentionTarget.length - 1
                            const outputBeforeMention = inputEl.textContent.slice(0, position)
                            const outputAfterMention = inputEl.textContent.slice(this.caretPosition)
                            const output = outputBeforeMention + mentionSyntax + outputAfterMention
                            inputEl.textContent = output
                            const newPosition = position + mentionSyntax.length
                            this.setEndOfContenteditable(newPosition)
                        }
                        this.mentionBoxToggle = false;
                    }
                    else if(this.keyCharacters.length){                       
                        let inputString = this.$refs.messageInputArea.textContent
                        let searchText = this.keyCharacters
                        let replacement = mentionSyntax
                        const lastIndex = inputString.lastIndexOf(searchText);
                        const hasAt = inputString[lastIndex - 1] && (inputString[lastIndex - 1] == '@' || inputString[lastIndex - 1] == '＠') ? 1 : 0
                        console.log(hasAt)
                        if (lastIndex === -1) {
                            return inputString;
                        }
                        const beforeLastIndex = inputString.slice(0, (lastIndex - hasAt));
                        const position = this.caretPosition
                        const afterLastIndex = inputString.slice(lastIndex + searchText.length);
                        const result = beforeLastIndex + replacement.replace(/[@＠]/g, '') + afterLastIndex;
                        this.$refs.messageInputArea.textContent = result
                        this.setEndOfContenteditable(beforeLastIndex.length + mentionSyntax.length)
                        this.keyCharacters = '';

                    }
                    this.msgSave()
                }    
            }, 
            composeUpdate(){
                this.keyCharacters = event.data
                if(!this.keyCharacters.length){
                    this.resetMention()
                }
            },
            setEndOfContenteditable(pos){    
                var node = document.querySelector("#typeArea");
                node.focus();
                var textNode = node.firstChild;
                var range = document.createRange();
                const nPos = pos <= textNode.length ? pos : textNode.length
                range.setStart(textNode, nPos);
                range.setEnd(textNode, nPos);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            },
            getCharacterPrecedingCaret(containerEl, pattern) {
                var precedingChar = "", sel, range, precedingRange;
                if (window.getSelection) {
                    sel = window.getSelection();
                    if (sel.rangeCount > 0) {
                        range = sel.getRangeAt(0).cloneRange();
                        range.collapse(true);
                        range.setStart(containerEl, 0);
                        precedingChar = range.toString().slice(-1);
                    }
                } else if ( (sel = document.selection) && sel.type != "Control") {
                    range = sel.createRange();
                    precedingRange = range.duplicate();
                    precedingRange.moveToElementText(containerEl);
                    precedingRange.setEndPoint("EndToStart", range);
                    precedingChar = precedingRange.text.slice(-1);
                }
                if(!this.jpInput && (precedingChar === '＠' || precedingChar === '@')){
                    this.keyCharacters = ''
                    this.highlighted = 0
                    this.mentionBoxToggle = true;
                }else{
                    this.mentionBoxToggle = false;                   
                    
                }
                if(pattern == 3){
                    return precedingChar;
                }
            },
            pasteListener(e){                    
                e.preventDefault();    
                var text = e.clipboardData.getData("text/plain");            
                if(!text || text == ''){            
                    // var uniqueId = Math.random().toString(36).substring(5);
                    
                    // var source = 'nonimagefile'
                    // // if(e.clipboardData.files[0].type.indexOf('image') > -1){
                    //     var source = URL.createObjectURL(e.clipboardData.files[0]);
                    // // }               
                    // const name = e.clipboardData.files[0].name;
                    // const lastDot = name.lastIndexOf('.');
                    // const fileName = name.substring(0, lastDot);
                    // const extension = name.substring(lastDot + 1);
                    // this.attachedFiles.push({
                    //     src: source,
                    //     name: e.clipboardData.files[0].name,
                    //     uId: uniqueId,
                    //     ext: extension,
                    //     file: e.clipboardData.files[0]
                    // });    
                    if(!e.clipboardData.files.length) return
                    const formData = new FormData()                    
                    for(var i in e.clipboardData.files) {                
                        if(e.clipboardData.files[i].type !== undefined){
                            var uniqueId = Math.random().toString(36).substring(5);
                            
                            var source = 'nonimagefile'
                            if(e.clipboardData.files[i].type.indexOf('image') > -1){
                                var source = URL.createObjectURL(e.clipboardData.files[i]);
                            }               
                            const name = e.clipboardData.files[i].name;
                            const lastDot = name.lastIndexOf('.');
                            const fileName = name.substring(0, lastDot);
                            const extension = name.substring(lastDot + 1);
                            this.attachedFiles.push({
                                src: source,
                                name: e.clipboardData.files[i].name,
                                uId: uniqueId,
                                ext: extension,
                                file: e.clipboardData.files[i]
                            });  
                            formData.append(i, e.clipboardData.files[i])
                        }               
                    } 
                    this.uploadStart(formData)
                    this.msgSave();
                }else{
                    var text = e.clipboardData.getData("text/plain");
                    document.execCommand("insertText", false, text);
                }     
                this.msgSave()
            },
            caretPos(){

                const cursorIndex = window.getSelection().getRangeAt(0).startOffset
                const textBeforeCursor = event.target.innerText.slice(0, cursorIndex)
                const match = textBeforeCursor.match(/@(\p{L}+)$/u)

                // if (match) {
                //     // this.showMembers = true
                //     this.keyCharacters = match[1]
                // } else {
                //     // this.showMembers = false
                //     this.keyCharacters = ''
                // }
                // console.log(this.keyCharacters)



                var element = event.target;
                var caretOffset = 0;
                if (window.getSelection) {
                    var range = window.getSelection().getRangeAt(0);
                    var preCaretRange = range.cloneRange();
                    preCaretRange.selectNodeContents(element);
                    preCaretRange.setEnd(range.endContainer, range.endOffset);
                    caretOffset = preCaretRange.toString().length;
                } 
                else if (document.selection && document.selection.type != "Control") {
                    var textRange = document.selection.createRange();
                    var preCaretTextRange = document.body.createTextRange();
                    preCaretTextRange.moveToElementText(element);
                    preCaretTextRange.setEndPoint("EndToEnd", textRange);
                    caretOffset = preCaretTextRange.text.length;
                }            
                this.caretPosition = caretOffset
            },
            msgSave(){                        
                setTimeout(() => { 
                    var text = document.getElementById('typeArea').textContent     
                    localStorage.setItem('temp_message_' + this.openedBoard.id, text); 
                     
                     
                }, 100);
            },
            addAttachment(){
                if(event.target.files && event.target.files.length){
                    const formData = new FormData()                    
                    // formData.append('board_id', this.openedBoard.id)
                    for(var i in event.target.files) {                
                        if(event.target.files[i].type !== undefined){
                            var uniqueId = Math.random().toString(36).substring(5);
                            
                            var source = 'nonimagefile'
                            if(event.target.files[i].type.indexOf('image') > -1){
                                var source = URL.createObjectURL(event.target.files[i]);
                            }               
                            const name = event.target.files[i].name;
                            const lastDot = name.lastIndexOf('.');
                            const fileName = name.substring(0, lastDot);
                            const extension = name.substring(lastDot + 1);
                            this.attachedFiles.push({
                                src: source,
                                name: event.target.files[i].name,
                                uId: uniqueId,
                                ext: extension,
                                file: event.target.files[i]
                            });  
                            formData.append(i, event.target.files[i])
                        }               
                    } 
                    this.uploadStart(formData)
                    event.target.value = '';
                    this.msgSave();
                }
                
            },   
            progressload: function(e){              
                this.progressPercentage = Math.floor((e.loaded * 100) / e.total);                          
            },
            uploadStart(formData){
                axios.post('/attach_upload_api', formData, {
                    onUploadProgress: this.progressload
                })
                .then(response => {    
                    if(response.data && response.data.length){
                        for( let i in response.data){
                            this.successUploadedFiles.push(response.data[i])
                        }
                    }            
                    this.progressPercentage = 0
                }).catch((error) => {
                    this.$toast('ファイルアップロードに失敗しました。',{
                            toastClassName: "toastConfirm",
                            timeout: 3000, 
                            draggable: false,
                            closeButton: false,
                        });
                    this.progressPercentage = 0
                })
                .then(() => {});
            },        
            footerDropEnter(event){
                if (event.dataTransfer.types) {
                    for (var i = 0; i < event.dataTransfer.types.length; i++) {
                        if (event.dataTransfer.types[i] == "Files") {
                            this.dropActive = true
                        }
                    }
                }                
                return false;          
            },
            footerDropLeave(){
                setTimeout(() => {
                    this.dropActive = false
                }, 0);            
            }, 
            footerDropEnterFromFile(){
                if(this.$store.state.sharingData && this.$store.state.sharingData){
                    this.dropActive = true
                }
            },
            footerDropLeaveFromFile(){
                    this.footerDropLeave();
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
            dropSharingItems(){
                if(this.$store.state.sharingData && this.$store.state.sharingData.drag){
                    
                    this.$store.state.sharingData.files.forEach((file) => {
                        const isFolder = file.record.hasOwnProperty('folder') && file.record.folder == 1
                        if(!isFolder && !this.sharingFiles.includes(item => item.path == file.path)){                            
                            this.sharingFiles.push(file)
                        }
                    });
                    let folders = this.draggingFiles.filter( obj => obj.record.folder == 1);
                    if(folders.length){
                         
                        this.errorToast('フォルダを送ることができません。')
                    }
                    if(this.$store.state.sharingData.text){
                        var el = this.$refs.messageInputArea
                        const nl = el.textContent && el.textContent.length ? '\n' : '';
                        el.textContent = el.textContent + nl + this.$store.state.sharingData.text      
                        this.msgSave()
                    }
                    this.$store.commit('setSharingData', null)
                    this.footerDropLeave();
                }
            },
            footerDropDropped(){  
                this.footerDropLeave();
                
                if(event.dataTransfer.files){
                    const formData = new FormData()  
                    for(var i in event.dataTransfer.files) {                
                        if(event.dataTransfer.files[i].type !== undefined){
                            var uniqueId = Math.random().toString(36).substring(5);
                            
                            var source = 'nonimagefile'
                            if(event.dataTransfer.files[i].type.indexOf('image') > -1){
                                var source = URL.createObjectURL(event.dataTransfer.files[i]);
                            }               
                            const name = event.dataTransfer.files[i].name;
                            const lastDot = name.lastIndexOf('.');
                            const fileName = name.substring(0, lastDot);
                            const extension = name.substring(lastDot + 1);
                            this.attachedFiles.push({
                                src: source,
                                name: event.dataTransfer.files[i].name,
                                uId: uniqueId,
                                ext: extension,
                                file: event.dataTransfer.files[i]
                            });  
                            formData.append(i, event.dataTransfer.files[i])
                        }               
                    } 
                    this.uploadStart(formData)
                    // event.target.value = '';
                    this.msgSave();           
                     
                }   
                this.msgSave();        
                           
            },
            iconColorFilter: function (ext) {
                var extensions = ["xlsx", "xlsm", "xlsb", "xltx", "xls", "xml", "xlam", "xlr", "xlw", "xla",
                    "doc", "docm", "docx", "dot", "dotx",
                    "potm", "potx", "ppam", "pps", "ppsm", "ppsx", "ppt", "pptm", "pptx",
                    "pdf",
                ]
                const format = extensions.indexOf(ext);                
                let w = 'min-width:35px;'
                let f = ''
                switch (true) {
                    case (format >= 0 && format <= 9):
                        f = "fill: #1D6F42";
                        break;
                    case (format >= 10 && format <= 14):
                        f = "fill: #0078d7";
                        break;
                    case (format >= 15 && format <= 23):
                        f = "fill: #d04423";
                        break;
                    case (format == 24):
                        f = "fill: #ff0000";
                        break;
                    default:
                        f = '';
                }
                return w + f;
            },
            removeAttachment(image){  
                // this.attachedFiles = $.grep(this.attachedFiles, function(e){ 
                //     return e.uId != image.uId; 
                // });
                this.successUploadedFiles = this.successUploadedFiles.filter( ob => ob !== image )
                axios.post('/remove_temp_file', {id: image.id})

                this.msgSave();          
            },
            removeSharingFile(event, image){
                this.sharingFiles = this.sharingFiles.filter( obj => obj !== image)
                this.msgSave();   
            },
            enterSend(){
                if(this.$refs.mentionBox && this.$refs.mentionBox.highlighted > -1){
                    console.log(this.$refs.mentionBox)
                    const user = this.mentionAbleList[this.$refs.mentionBox.highlighted]
                    this.$refs.mentionBox.mentionUser(user, this.$refs.mentionBox.highlighted)
                    event.preventDefault()
                }
                if(event.altKey){
                    this.commentSendConfirm(this.openedBoard.id)
                }
            },
            focused(){
                // const el = document.getElementById('boardListInner')
                // disableBodyScroll(el)
            },
            blured(){
                // const el = document.getElementById('boardListInner')
                // enableBodyScroll(el)
            }
            
        }
    }
</script>
