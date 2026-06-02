<template>
    <div id="footArea" 
        style="padding:0;position:relative"
        @dragenter="footerDropEnter" 
        @dragover.prevent 
        @drop.prevent 
        @mouseenter="footerDropEnterFromFile"
        class="footAreaContainer" 
        v-show="openedBoard"
    >
        <div v-if="charLength >= 5000" style="position: absolute;right: 10px;top: -20px;font-size: 12px;color: tomato;">メッセージは5000文字以内で入力してください</div>
        <div @click="emit('unreadJumped')" v-if="unread.status" class="unread" style="position:absolute;top: -40px;bottom:auto;user-select:none;">
            <p class="unread-inner cursor-pointer">{{ `${unread.count} 件の新しいメッセージ` }}</p>
        </div>
        <Transition name="replyQuotBox">
            <ReplyQuotWindow 
                v-if="quoteReply.active"
                :editing="replyLoader"
                :key="replyKey"/>
        </Transition>
        <Transition name="replyQuotBox">
            <ForwardWindowMessage 
                v-if="forwardItem"
                :item="forwardItem"
                @close="forwardItem = null"
            />
        </Transition>
        <span 
            @dragleave="footerDropLeave" 
            @mouseleave="footerDropLeave" 
            @mouseup.stop="dropSharingItems" 
            @dragover.prevent 
            @drop.prevent="footerDropDropped"
            :style="{color: '#fff', height: dropActive ? '100%' : '0'}" 
            id="footerDropArea" 
            class="download-area">ファイルドロップ
        </span>

        <Transition name="modalFade">
            <MentionBox 
                :forced="true"
                v-if="mentionBoxForced && !mentionBoxToggle" 
                :mentionAbleList="mentionAbleList"
                @close="mentionBoxForced = false"
                @mentionUser="mentionUser"
                ref="mentionBoxForce"
            />
        </Transition>
        <Transition name="modalFade">
            <MentionBox 
                :forced="false"
                v-if="keyCharacters.length || mentionBoxToggle" 
                :mentionAbleList="mentionAbleList"
                @mentionUser="mentionUser"
                @close="mentionBoxToggle = false;keyCharacters = ''"
                ref="mentionBox"
            />
        </Transition>
        <Transition name="downShiftPop">
            <AiCorrection 
                v-if="aiEditing"
                :target="messageInputArea"
                ref="aiCorrectionRef"
                @close="aiEditing = false"
            />
        </Transition>
        <div class="typeContainer">                 
            <div v-show="successUploadedFiles.length">
                <div class="preUploadImage">    
                    <div :key="image.id" class="cursor-pointer" v-for="image in successUploadedFiles" style="margin: auto 10px 10px 0;min-height: 40px;user-select:none">
                        <div class="preImgWrapper" @click="previewFile(image)">
                            <img draggable="false" v-if="image.mime_type == 'image'" :src="'/cdn/temp_upload/' + image.id + '.' + image.extension">                                
                            <FileIcon v-if="image.mime_type !== 'image'" :ext="image.extension"/>
                            <p class="shared-file-name">{{image.name}}</p>
                        </div>
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
                    <div :key="image.record.id" class="cursor-pointer" v-for="image in sharingFiles" style="margin: auto 10px 10px 0;user-select:none">
                        <div class="preImgWrapper" @click="previewSharingFile(image)">
                            <img draggable="false" v-if="image.record.mime_type == 'image'" :src="image.path" >
                            <FileIcon v-if="image.record.mime_type !== 'image'" :ext="image.record.extension"/>
                            <p class="shared-file-name">{{image.record.name}}</p>
                        </div>
                        <button @click="removeSharingFile(image)">
                            <svg @click.prevent version="1.1" xmlns="http://www.w3.org/2000/svg" width="7" height="7" viewBox="0 0 32 32" fill="var(--primary-color)" style="pointer-events: none;">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>  
                        </button>                                                    
                    </div>                                           
                </div>
            </div> 
            
            <div class="typeAreaOuter">
                <div class="pBarC" v-if="progressPercentage > 0 && progressPercentage < 100">
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
                    @input="setInput"
                    @compositionupdate="composeUpdate"
                    id="typeArea" 
                    contenteditable="plaintext-only" 
                    :class="['typeBoxArea',  'boardTypeArea', {maxLengthAlert: charLength >= 5000}, {hasText: true}]"
                    >
                </div>
                <Transition name="modalFade">
                    <div id="EmojiPicker" class="absolute bottom-20" v-if="menu.name == 'EmojiPicker' && menu.id == 1002">
                        <div class="sub-tab-container bg-[var(--background-color)]">
                            <div @click="tab = 'oikawa'" :class="{'selected-sub-tab': tab === 'oikawa'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">おぃかわ</div>
                            <div @click="tab = 'emoji'" :class="{'selected-sub-tab': tab === 'emoji'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">絵文字</div>
                        </div>
                        <EmojiPicker  
                            v-show="tab === 'emoji'"                                   
                            :native="true" 
                            @select="selectEmoji" 
                            :hide-search="true" 
                            :hide-group-names="true" 
                            theme="dark" 
                            :disable-sticky-group-names="true" 
                            :disable-skin-tones="true"
                            :display-recent="true"
                            style="left: 0;right:auto;position: unset !important;"
                        />
                        <div v-show="tab === 'oikawa'" class="w-max p-4 bg-[var(--background-color)] shadow-xl">
                            <div class="grid grid-cols-5 gap-2">                        
                                <div class="flex items-end justify-center transition-transform duration-200 ease-out hover:scale-105" v-for="oikawa in oikawaMap" @click="selectOikawa(oikawa.name)">
                                    <Character :size="40" :emote-name="oikawa.name"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </Transition>
                <div class="typeCommandBar">
                    <div class="message-icon-outer">
                        <div class="message-icon-wrapper" title="絵文字" @click.stop="menu.setMenu( {name: 'EmojiPicker', id: 1002})">                                          
                            <svg style="fill: var(--third-color);" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 30 30">
                                <path d="M14.977,0C6.735-0.056-0.127,6.93,0.002,15.153c-0.028,8.165,6.816,14.938,14.975,14.811v-0.04c0.967,0.013,1.936-0.067,2.889-0.242c4.817-0.863,9.055-4.275,10.937-8.8C32.985,11.039,25.688-0.021,14.977,0 M14.977,27.902C6.08,27.658-0.075,18.755,3.433,10.373C7.814,0.291,22.13,0.293,26.49,10.386C30.002,18.61,23.886,27.788,14.977,27.902"/>
                                <path d="M22.441,18.263c-0.623-0.436-1.479-0.284-1.917,0.338c0.007-0.011,0.002-0.006-0.001-0.004c-0.002,0.002-0.006,0.005-0.011,0.01l-0.027,0.025c-0.734,0.658-1.568,1.264-2.479,1.639c-0.291,0.123-0.596,0.222-0.9,0.292c-0.67,0.185-1.332,0.349-2.043,0.376c-2.039,0.059-4.107-0.841-5.435-2.355c-1.226-1.563-3.443,0.199-2.196,1.769c0.199,0.27,0.418,0.529,0.646,0.772c1.784,1.911,4.359,3.094,6.986,3.106c1.119,0.021,2.305-0.08,3.354-0.525c1.753-0.72,3.36-1.896,4.362-3.526C23.214,19.556,23.063,18.698,22.441,18.263"/>
                                <path d="M18.513,14.558c0.905,0.201,1.834-0.509,2.073-1.585c0.239-1.076-0.302-2.111-1.208-2.313c-0.904-0.201-1.833,0.509-2.072,1.585C17.065,13.322,17.606,14.357,18.513,14.558"/>
                                <path d="M11.44,14.558c0.906-0.201,1.446-1.236,1.208-2.313c-0.239-1.076-1.167-1.786-2.074-1.585c-0.906,0.203-1.446,1.238-1.208,2.313C9.605,14.049,10.534,14.759,11.44,14.558"/>
                            </svg>
                        </div>
                        <div title="メンション" class="message-icon-wrapper" style="position: relative;">
                            <svg @click.stop="mentionBoxForceOpen"  height="19" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 32" style="fill: var(--third-color);">
                                <path d="M7.073 10.146c0.051 0.267 0.127 0.533 0.19 0.787 0.076 0.254 0.178 0.521 0.279 0.775 0.406 1.003 1.029 1.93 1.816 2.692 0.775 0.762 1.714 1.359 2.717 1.74 1.003 0.394 2.298 0.559 3.149 0.559 1.143 0 2.133-0.19 3.149-0.584 1.003-0.381 1.93-0.978 2.717-1.727 0.775-0.749 1.41-1.664 1.841-2.667s0.648-2.095 0.66-3.187c0.013-1.092-0.19-2.197-0.597-3.213-0.406-1.029-1.016-1.968-1.778-2.768-0.775-0.787-1.702-1.435-2.73-1.879s-2.146-0.673-3.264-0.673-2.222 0.229-3.251 0.673c-1.016 0.432-1.943 1.092-2.717 1.879-1.524 1.587-2.387 3.797-2.349 5.968l0.051 0.813c0.025 0.267 0.076 0.533 0.114 0.813zM10.197 6.438c0.292-0.648 0.711-1.232 1.232-1.727 0.508-0.483 1.117-0.864 1.765-1.117s1.333-0.381 2.032-0.381 1.384 0.14 2.032 0.394 1.244 0.635 1.752 1.117c0.508 0.483 0.927 1.067 1.219 1.714s0.444 1.359 0.457 2.070c0.025 1.435-0.559 2.857-1.549 3.924-0.495 0.533-1.105 0.952-1.765 1.27-0.673 0.305-1.537 0.47-2.146 0.47-0.432 0-1.473-0.203-2.133-0.495-0.66-0.305-1.27-0.724-1.765-1.244-0.99-1.041-1.625-2.451-1.6-3.898 0.025-0.737 0.178-1.448 0.47-2.095zM15.264 19.048c4.064 0 6.54 1.168 8.444 2.387 2.171 1.384 3.708 3.073 4.686 4.457s1.702 2.984 2.019 4.127c0.292 1.054 0 1.829-0.673 1.981-0.622 0.127-1.232-0.33-1.524-0.927-0.419-0.851-1.168-2.337-1.93-3.352-0.838-1.13-1.981-2.235-3.124-3.060-0.978-0.711-2.489-1.384-3.822-1.765-1.054-0.305-2.565-0.483-4.089-0.483-1.537 0-3.187 0.229-4.14 0.508-1.333 0.381-2.806 1.041-3.771 1.74-1.13 0.825-2.273 1.917-3.124 3.060-0.749 1.016-1.498 2.502-1.93 3.352-0.292 0.597-0.902 1.054-1.524 0.927-0.673-0.14-0.952-0.927-0.673-1.981 0.317-1.143 1.041-2.743 2.019-4.127s2.514-3.086 4.686-4.457c1.93-1.219 4.406-2.387 8.47-2.387z"></path>
                            </svg>
                        </div>
                        <div id="attachArea" title="添付ファイル" class="message-icon-wrapper mt-[2px]">
                            <div style="display:inline-block">
                                <label for="sharedfile" class="cursor-pointer">                                       
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="17" viewBox="0 0 27 32" style="fill: var(--third-color);">
                                    <path d="M25.954 7.013c-0.479-0.575-4.378-4.56-5.978-5.816-0.623-0.489-1.284-0.853-2.127-0.949-1.178-0.125-2.97-0.182-4.091-0.22-1.36-0.029-2.472-0.029-3.832-0.029-1.36 0.010-3.008 0.077-4.474 0.172-1.36 0.077-2.328 0.134-2.845 0.22-0.69 0.105-1.188 0.489-1.265 1.303-0.077 0.805-0.172 4.905-0.172 7.454 0.010 2.558 0.115 5.835 0.201 6.822 0.096 0.987 0.556 1.447 1.083 1.504 0.527 0.067 0.843-0.537 0.853-1.159 0.019-0.623 0.019-1.226 0.019-1.734s-0.048-2.913-0.019-5.432c0.029-2.098 0.086-4.206 0.192-6.304 0.010-0.134 0.115-0.24 0.249-0.24 0.92-0.029 1.849-0.048 2.778-0.058 1.341-0.019 2.683-0.019 4.024-0.010s2.683 0.029 4.024 0.058c0.987 0.019 1.983 0.048 2.96 0.086 0.153 0.010 0.268 0.134 0.268 0.287-0.010 0.901-0.019 3.612-0.019 3.612 0 0.546 0.010 1.083 0.019 1.629v0.010c0.010 0.546 0.45 0.987 0.996 0.987l1.705 0.019h1.705c0.441 0 1.428-0.019 1.926-0.029 0.153 0 0.287 0.125 0.297 0.278 0.048 1.399 0.067 2.807 0.077 4.216 0.010 1.878 0 3.756-0.029 5.634s-0.077 3.756-0.153 5.624c-0.067 1.514-0.144 3.037-0.268 4.532-0.019 0.201-0.182 0.355-0.383 0.364-1.418 0.038-2.778 0.067-4.302 0.077-1.648 0.010-6.266 0.010-8.163 0-1.964-0.010-5.365-0.029-7.042-0.086-0.125 0-0.24-0.153-0.259-0.278-0.058-0.374-0.105-0.834-0.163-1.389-0.067-0.623-0.469-1.092-1.035-1.025-0.45 0.048-0.824 0.45-0.891 1.198-0.067 0.738-0.019 1.619 0.067 2.213s0.441 1.016 1.198 1.14c1.006 0.163 5.72 0.249 8.057 0.268 2.347 0.019 6.275-0.019 8.259-0.019 1.974-0.010 3.286-0.019 4.857-0.182 1.121-0.115 1.408-0.747 1.552-1.715 0.24-1.667 0.345-3.325 0.469-4.982 0.134-1.887 0.24-3.775 0.326-5.672 0.086-1.887 0.144-3.784 0.192-5.672 0.029-1.428 0.038-3.21 0.019-4.235-0.010-0.948-0.287-1.782-0.862-2.472zM19.832 7.023c-0.019-0.537-0.077-2.060-0.096-2.692 0-0.096 0.105-0.144 0.182-0.086 0.537 0.489 2.491 2.271 3.152 2.874 0.077 0.067 0.029 0.192-0.077 0.182-0.719-0.029-2.434-0.086-2.98-0.105-0.096 0.010-0.182-0.077-0.182-0.172z"></path>
                                    <path d="M18.405 25.61l2.050-6.189c0.029-0.086 0.048-0.182 0.048-0.268 0-0.45-0.383-0.843-0.881-0.843h-18.74c-0.24 0-0.46 0.096-0.623 0.249s-0.259 0.364-0.259 0.604v6.189c0 0.23 0.096 0.441 0.259 0.594s0.383 0.249 0.623 0.249h16.69c0.374-0.010 0.709-0.24 0.834-0.584zM22.41 11.89c0.019-0.383-0.278-0.719-0.671-0.738-1.284-0.067-2.568-0.096-3.842-0.115-0.642-0.010-1.284-0.029-1.926-0.029l-1.926-0.010-1.926 0.010-1.926 0.029c-1.284 0.019-2.568 0.038-3.842 0.086-0.374 0.019-0.69 0.316-0.699 0.699-0.010 0.402 0.297 0.738 0.699 0.757 1.284 0.048 2.568 0.067 3.842 0.086l1.926 0.019 1.926 0.010 1.926-0.010c0.642 0 1.284-0.019 1.926-0.029 1.284-0.019 2.568-0.048 3.842-0.115 0.364-0.010 0.651-0.287 0.671-0.652zM15.875 14.63c-0.527-0.010-1.054-0.029-1.581-0.029l-1.59-0.010-1.581 0.010-1.59 0.029c-1.054 0.019-2.117 0.038-3.171 0.086-0.374 0.019-0.68 0.316-0.69 0.699-0.019 0.402 0.297 0.738 0.69 0.757 1.054 0.048 2.117 0.067 3.171 0.086l1.59 0.029 1.581 0.010 1.59-0.010c0.527 0 1.054-0.019 1.581-0.029 1.054-0.019 2.117-0.048 3.171-0.115 0.345-0.019 0.632-0.297 0.661-0.661 0.019-0.383-0.268-0.719-0.661-0.738-1.054-0.057-2.117-0.086-3.171-0.115z"></path>
                                </svg>  
                                </label>
                                <input multiple type="file" name="sharedfile" id="sharedfile" v-on:change="addAttachment" style="display: none;">
                            </div>
                        </div>
                        <div @click="editWithAi" title="AI添削" class="message-icon-wrapper" style="width:28px">     
                            <AiIcon size="20" fill="var(--third-color)" :class="{'animate-pulse': aiCorrectionRef?.working}"/>
                        </div>       

                        <div @click="commentSendConfirm(1)" title="下書き保存" class="message-icon-wrapper">
                            <svg width="20" height="20" style="fill: var(--third-color);" viewBox="0 0 21.60806 22.36145">
                                <path d="M21.41274,7.98387c-.16877-.77089-.79066-1.82398-1.27363-2.40522-.40918-.49237-2.83109-3.49307-4.1876-4.55837-.53076-.41681-1.09003-.7251-1.802-.80176-.99628-.10736-2.51617-.15704-3.46716-.18555C9.52322-.00175,8.57937-.00242,7.42251.00179c-1.15717.00421-2.55054.06677-3.79272.14044-1.15851.06866-1.97693.11963-2.41547.18915C.63199.42366.20682.75142.14242,1.4363.07797,2.12111-.00473,5.5931.00021,7.76119c.00537,2.16809.00366,2.89462-.00021,3.84656,0,0,.10542,3.96337.17523,5.37293.13512,2.72811.35339,3.54022.42908,4.04816s.54745.9967,1.18747,1.10174c.85425.1402,4.84924.20996,6.83667.22595,1.98737.01593,5.32568-.01129,7.00098-.01801,1.67535-.00671,2.78833-.01416,4.12445-.15656.94928-.1012,1.39606-.40491,1.51514-1.23169l.1972-4.75057c.06982-1.59167.09403-3.42481.13139-5.01898.02716-1.21082.01306-2.29277-.18486-3.19685ZM7.96115,2.15926c1.13818.00592,2.27661.02161,3.41211.04486.14404.00342.30237.00342.4646.00256-.01288.40375-.03351.8075-.04315,1.2113l-.026,1.34534s.03961.39691-.08453.5188c-.24243.23792-1.27368.15051-1.27368.15051l-1.34534-.00751-1.34534.00903c-.44843.00323-.84918.07355-1.2923.00403-.11401-.01788-.19861.00317-.34991-.2049-.1441-.19818-.12286-.50073-.12286-.50073l-.02594-1.3454c-.00977-.40753-.03052-.81512-.04352-1.22266.69196-.0033,1.38342-.01025,2.07587-.00525ZM8.6813,20.6249c-.61932-.00195-1.40601-.00537-2.2226-.01154.04303-1.09473.07928-2.18982.10242-3.28278l.03613-2.05176c-.01953-.22852-.04004-.79004.2002-1.06055.14258-.15918.44434-.37109,1.41797-.37109.31445,0,.56934.02246.57129.02441l1.82031.01172,1.82812-.01367c.19238-.00195.37891-.01465.56152-.02734.20801-.01367.41211-.02734.61816-.02734.20898,0,.4209.01367.68652.05957.24902.0332.43555.11914.65527.46387.24707.38867.22266.91992.21973.95312l.03516,2.08594c.02209,1.08203.0589,2.16376.10126,3.24536-1.59058.0072-5.12183.0069-6.63147.00208ZM19.80866,9.87143c.00958,1.59045-.00248,4.92571-.02567,6.51732-.00067.04358-.00287.08704-.00354.13062l-.19708,3.72797c-.01465.17145-.15717.30432-.3291.30896-.81342.02197-1.61987.0379-2.44714.04993.04175-1.07635.078-2.15314.10071-3.22778l.03516-2.08301.00586-.9043c0-.00293-.01465-.52637-.04492-.7627-.02539-.1875-.15332-.56152-.30664-.74609-.44824-.54004-.92871-.64844-1.77441-.67578,0,0-.46289-.02637-.55957-.0293l-.66699-.01758c-.38574-.01172-.77051-.02344-1.15625-.02637l-1.82324-.01367-1.82227.01074-1.8457.04199c-.81152.02148-1.19043.05371-1.65234.59961-.16602.19531-.36914.56738-.40918.69531-.05273.17188-.06738.66895-.06055.97363l.00391.80273.03613,2.08594c.02222,1.0899.05902,2.17957.10199,3.26904-.87262-.0108-1.55521.02814-2.12809.00757-.10614-.00385-.49034-.25744-.60027-.50834-.13048-.29782-.08341-.60297-.13688-1.0744,0,0-.21046-5.10071-.24681-6.79597-.03324-1.55031-.06197-2.31752-.03743-4.45808.02081-1.78308.07654-3.56598.16241-5.34711.00543-.11237.09637-.20209.2088-.20557.67474-.021,1.35443-.03192,2.03406-.04211-.01288.40417-.03351.80835-.04315,1.21252l-.026,1.3454-.0033.59058h-.00061s.01105.35376.03528.5141c.02155.1427.12756.43036.2807.59229.41376.43738.88824.49786,1.4892.5152.13855.00397.27716.01538.41571.0188.44843.00891.89691.02496,1.34534.0282l1.34534.00903,1.34534-.00751,1.34534-.026c.0047-.00012.00946-.00037.01416-.00049.6015-.01434.98212-.03198,1.40057-.4646.138-.14264.3067-.4057.34906-.52557.06866-.19446.05542-.69287.05542-.69287l-.00293-.52039-.026-1.34534c-.00952-.39954-.02997-.79901-.04266-1.19849.15967.01227.29688.03027.38812.06,1.17566.38257,4.86643,4.34381,5.1237,5.09961.24066.70728.78821,1.29891.79645,2.49233Z"/>
                            </svg>
                        </div>
                    </div>
                    
                        
                </div>
                                        
            </div>
            <div @mousedown.prevent.stop @click="commentSendConfirm(0)" id="sendArea" class="sendAreaBox" style="display:flex;bottom:6px;"> 
                <div style="display: flex;position: relative;">
                    <div style="position: absolute;bottom: 8px;right: 0;" v-if="auth.linked.length">
                        <UserPanel :user="(auth.activeUser)" :disableInstant="true" size="15" imgClass="userSmallIcon"/>
                    </div>
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="33" viewBox="0 0 43 32" style="margin:auto;fill: var(--third-color);">
                        <path d="M40.638 0.087c-1.842 0.361-6.097 1.292-9.435 2.047l-30.046 6.891c-0.419 0.096-0.793 0.374-1.003 0.793-0.364 0.728-0.058 1.585 0.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56 0.287 0.157 0.487 0.439 0.542 0.762 0 0 0.711 4.473 0.921 5.891 0.21 1.417 0.714 4.465 1.184 6.482 0.168 0.726 0.631 1.335 1.215 1.512 0.495 0.152 1.030 0.037 1.43-0.285 1.394-1.128 5.787-5.445 7.388-7.272 0.133-0.152 0.355-0.19 0.531-0.085l6.184 3.646c0 0 0.439 0.294 0.919 0.519 1.283 0.601 2.479 0.625 3.062-0.829 0.325-0.813 4.316-12.627 4.316-12.627l4.466-13.209c0.053-0.152 0.082-0.321 0.082-0.492 0-0.844-0.654-1.675-2.496-1.312zM20.045 24.741c-0.475 0.477-1.473 1.473-2.284 2.197-0.155 0.137-0.385-0.002-0.313-0.195l1.796-4.842c0.051-0.157 0.236-0.226 0.378-0.142l1.796 1.054c0.157 0.091 0.161 0.294 0.041 0.432-0.401 0.458-0.975 1.058-1.413 1.495zM32.151 25.117c-0.106 0.325-0.482 0.47-0.777 0.301l-1.447-0.824-3.554-2.014-7.121-4.024c-0.067-0.037-0.138-0.068-0.214-0.094-0.677-0.232-1.411 0.13-1.64 0.808l-1.944 7.086c-0.053 0.166-0.229 0.143-0.251-0.046-0.13-1.23-0.328-3.178-0.467-4.759-0.13-1.459-0.366-3.357-0.494-4.434-0.111-0.931-0.427-1.423-1.131-1.837-0.704-0.415-6.489-3.354-7.668-4.049-0.241-0.142-0.166-0.415 0.065-0.463 0 0 13.334-2.689 16.022-3.304 2.689-0.617 10.513-2.447 10.513-2.447 0.103-0.025 0.152 0.118 0.056 0.161l-5.127 2.281-2.961 1.459c-0.987 0.487-7.32 3.516-9.259 4.562-0.477 0.258-0.665 0.871-0.373 1.36 0.255 0.429 0.808 0.574 1.265 0.374 2.004-0.882 16.208-7.766 17.651-8.441 0.345-0.162 0.376-0.012 0.287 0.049-0.89 0.615-9.43 6.896-10.25 7.528l-2.448 1.905c-0.432 0.342-0.519 0.976-0.173 1.42 0.335 0.432 0.965 0.497 1.413 0.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66c0 0 5.775-4.365 6.187-4.682 0.166-0.128 0.397 0.033 0.331 0.234l-2.517 7.675-3.585 10.965z"></path>
                    </svg> 
                </div>                                   
            </div>                                    
        </div>                
    </div>  
</template>

<script setup lang="ts">
import ReplyQuotWindow from './ReplyQuotWindow.vue'
import ForwardWindowMessage from './ForwardWindowMessage.vue'
import EmojiPicker from 'vue3-emoji-picker'
import FileIcon from '../Mixed/FileIcon.vue'
import MentionBox from './MentionBox.vue'
import { computed, inject, onMounted, onUnmounted, ref, useTemplateRef, nextTick } from 'vue'
import { useFilePreview } from '@/store/filePreview'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useQuoteReply } from '@/store/quoteReply'
import { useSharingDataStore } from '@/store/sharingData'
import UserPanel from '@/components/Global/UserPanel.vue'
import { DateTime } from 'luxon'
import AiIcon from '@/components/Icons/AiIcon.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useBoardList } from '@/composables/board';
import { Message, MessageFile, SharingFile, UploadingFile, User } from '@/interface/globalInterface';
import { MessageMethodsKey, MessageMethods } from '@/interface/keys';
import AiCorrection from '@/components/Global/AiCorrection.vue';
import Character from '@/components/Global/Character.vue'
import { oikawaMap } from '@/utils/tools'
import type { AxiosProgressEvent } from 'axios'
    const sharingData = useSharingDataStore()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const quoteReply = useQuoteReply()
    const props = defineProps([ 'replyKey', 'unread', 'messageListType'])
    const emit = defineEmits(['unreadJumped'])
    const caretPosition = ref(0)
    const mentionBoxToggle = ref(false)
    const highlighted = ref(0)
    const attachedFiles = ref<UploadingFile[]>([])
    const draggingFiles = ref([])
    const successUploadedFiles = ref<MessageFile[]>([])
    const progressPercentage = ref(0)
    const keyCharacters = ref('')
    const startPosition = ref(0)
    const forwardItem = ref<Message | null>(null)
    const charLength = ref(0)
    const editing = ref(false)
    const dropActive = ref(false)
    const sharingFiles = ref<SharingFile[]>([])
    const messageInputArea = useTemplateRef('messageInputArea')
    const mentionBox = useTemplateRef('mentionBox')
    const { openedBoard } = useBoardList()
    const { addQueue } = inject(MessageMethodsKey) as MessageMethods;
    const filePreview = useFilePreview()
    const mentionBoxForced = ref(false)
    const replyLoader = ref(false)
    const aiEditing = ref(false)
    const api = useApi()
    const { toast } = useDialog()
    const tab = ref('oikawa')
    const aiCorrectionRef = useTemplateRef('aiCorrectionRef')
    
    onUnmounted(() => {
        messageInputArea.value?.removeEventListener('keyup', inputKeyEventfirst);  
        messageInputArea.value?.removeEventListener('keyup',inputKeyEventSecond);
    })
    onMounted(() => {
        if(openedBoard.value && messageInputArea.value){
            const temp = localStorage.getItem('temp_message_' + openedBoard.value.id); 
            if(temp && messageInputArea.value){                
                messageInputArea.value.textContent = temp;
            }
            if(sharingData.active && sharingData.drag == false){
                if(sharingData.message && sharingData.from == 'message'){
                    forwardItem.value = sharingData.message
                } else if (sharingData.from == 'schedule') {
                    messageInputArea.value.textContent = sharingData.text    
                }else if(sharingData.files){
                    sharingFiles.value = sharingData.files
                }
                

                resetSharingData()
                footerDropLeave();
                
            }
        
            
            if(messageInputArea.value){
                messageInputArea.value.scrollTo( 0, messageInputArea.value.scrollHeight)
            }

            messageInputArea.value.addEventListener('keyup', inputKeyEventfirst);              
            messageInputArea.value.addEventListener('keyup',inputKeyEventSecond)
        }
    })
    
    const resetSharingData = () => {
        const shareData = {
            active: false,
            title: '',
            text: '',
            files: [],
            from: '',
            to: '',
            drag: false 
        }
        sharingData.setSharingData(shareData)
    }
    const filteredUsers = computed(() => {
        if(!openedBoard.value) return []
        const list:User[] = openedBoard.value.board_to_users.map(ob => ob.user)
        
        if(openedBoard.value.private_flag == 0){
            list.unshift({name: '全員', id: -1, icon_path: null} as User)
        }
        return list       
    })
    const mentionAbleList = computed(() => {
        if (keyCharacters.value) {
            const keyCharactersLowerCase = keyCharacters.value.replace(/[@＠]/g, '').toLowerCase()
            return filteredUsers.value.filter(member => {
                const memberNameLowerCase = member.name?.toLowerCase() || '';
                return memberNameLowerCase.includes(keyCharactersLowerCase) && member.id !== auth.activeUser.id
            })
        } else {
            return filteredUsers.value.filter(ob => ob.id !== auth.activeUser.id)
        }
    })          

    const previewFile = (file:MessageFile) => {
        let target_data: Record<string, any> = file
        target_data['file_path'] = `/cdn/temp_upload/${file.id}.${file.extension}`
        target_data['doc_path'] = `/temp_upload/${file.id}.${file.extension}`
        const data = {
            active: true,
            files: [target_data],
            source: 'message',
            index: 0,
            message: null,
        }
        filePreview.setFilePreview(data)
    }
    const previewSharingFile = (file: SharingFile) => {
        let target_data: Record<string, any> = file.record
        target_data['file_path'] = file.path
        target_data['doc_path'] = file.path
        const data = {
            active: true,
            files: [target_data],
            source: 'message',
            index: 0,
            message: null,
        }
        filePreview.setFilePreview(data)
    }
    
    const editWithAi = async() => {
        if(!messageInputArea.value?.innerText) return
        aiEditing.value = true
    }
    const setInput = (event: Event) => {
        charLength.value = (event.target as HTMLElement).innerText.length
    }            
    const inputKeyEventSecond = () => {
        if(openedBoard.value){
            getCharacterPrecedingCaret();
                
        }  
    }
    const inputKeyEventfirst = (event: KeyboardEvent) => {
        startPosition.value = getCaretPosition();
        if(openedBoard.value && mentionBoxToggle.value && messageInputArea.value && messageInputArea.value.textContent){      
            if (event.key === 'Backspace' || event.key === 'Delete') {
                const textBeforeCursor = messageInputArea.value.textContent.substring(0, getCaretPosition());
                const lastAtSign = textBeforeCursor.lastIndexOf('@');
                if (lastAtSign === -1 || lastAtSign < startPosition.value - 1) {
                    resetMention();
                }  
            } 
        } 
    }
    const getCaretPosition = () => {
        const selection = window.getSelection();
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            return range.endOffset;
        }
        return 0;
    }
    const resetMention = () => {
        mentionBoxToggle.value = false
        highlighted.value = 0
    }
    const selectEmoji = (emoji: { i: string }) => {   
        if (!messageInputArea.value) return  
        var a = messageInputArea.value?.textContent;    
        var b = emoji.i;
        var position = caretPosition.value;
        var output = [a?.slice(0, position), b, a?.slice(position)].join('');        
        messageInputArea.value.textContent = output
        caretPosition.value = caretPosition.value + 2;
        msgSave();
    }
    const commentSendConfirm = async(draftFlag: number) => {
        if (!messageInputArea.value || !openedBoard.value) return  
        let textCheck = messageInputArea.value.textContent || '';     
        const nospace = textCheck.replace(/\s/g, "")       
        charLength.value = textCheck.length
        if(draftFlag == 1 && !nospace){
            toast('下書き保存する内容がありません。')
            return
        }
        if((!nospace && (!attachedFiles.value || !attachedFiles.value.length) && !sharingFiles.value.length && !forwardItem.value) || charLength.value >= 5000) return;       
   
        const replyFlag = quoteReply.active && quoteReply.which == 'reply'
        const replyId = replyFlag ? quoteReply.message.id : null
        const quotFlag = quoteReply.active && quoteReply.which == 'quot'
        const quotId = quotFlag ? quoteReply.message.id : null 
        const message_quot = quotFlag ? quoteReply.message : null
        const message_reply = replyFlag ? quoteReply.message : null
        const forward_message_id = forwardItem.value ? forwardItem.value.id : null
        const message_forward = forwardItem.value ? forwardItem.value : null
        const queueMessage:Message = {
            deleted_at: null,
            message: textCheck,
            user: auth.activeUser as User,
            reply_flag: replyFlag,
            reply_id: replyId,
            quot_flag: quotFlag,
            quot_id: quotId,
            quot_message: quotFlag && quotId ? quoteReply.text : null,
            forward_message_id: forward_message_id,
            record_id: openedBoard.value.id,
            user_id: Number(auth.activeUser.id),
            id: Math.random().toString(36).substring(5),
            attached_temp_files: successUploadedFiles.value,
            message_quot: message_quot,
            message_reply: message_reply,
            message_forward: message_forward,
            message_attachments: attachedFiles.value && attachedFiles.value.length ? attachedFiles.value : [],
            created_at: DateTime.now().toFormat('yyyy-MM-dd HH:mm:ss'),
            error: false,
            u_id: `${Date.now().toString()}_${Math.random().toString(36).substring(5)}`,
            sharing_files: sharingFiles.value,
            draft_flag: draftFlag,
            message_files: [],
            actual_sender: null,
            reserved_at: null,
            emoted_users: []
        }
        addQueue(queueMessage)        
        messageInputArea.value.textContent = ''
        localStorage.setItem(openedBoard.value.id.toString(), '');
        mentionBoxToggle.value = false;
        successUploadedFiles.value = [];
        msgSave();   
        attachedFiles.value = []; 
                    
        resetSharingData();
        forwardItem.value = null;
        sharingFiles.value = []             
        
    }       
    const selectOikawa = (label: string) => {
        const el = messageInputArea.value
        if (!el) return

        const oikawaSyntax = `{#${label}}`
        const text = el.textContent || ''

        const start = caretPosition.value ?? text.length // fallback to end if undefined

        const output = text.slice(0, start) + oikawaSyntax + text.slice(start)
        el.textContent = output

        const newPosition = start + oikawaSyntax.length

        // IMPORTANT: keep your state in sync
        caretPosition.value = newPosition

        setEndOfContenteditable(newPosition)
        msgSave()
        menu.close()
    }
    const mentionUser = (user: User, index: number) => {             
        if(user && messageInputArea.value){
            const mentionSyntax = `[To:${user.name}:]`
            const text = messageInputArea.value.textContent || ''
            if(mentionBoxToggle.value && text){            
                const textBeforeCursor = text.slice(0, caretPosition.value)
                const match = textBeforeCursor.match(/[＠@]([^＠@^\s]*)$/);
                if (match) {
                    const mentionTarget = match[1]
                    const position = caretPosition.value - mentionTarget.length - 1
                    const outputBeforeMention = text.slice(0, position)
                    const outputAfterMention = text.slice(caretPosition.value)
                    const output = outputBeforeMention + mentionSyntax + outputAfterMention
                    messageInputArea.value.textContent = output
                    const newPosition = position + mentionSyntax.length
                    setEndOfContenteditable(newPosition)
                }
                mentionBoxToggle.value = false;
            }
            else if(mentionBoxForced.value){
                const position = caretPosition.value
                const outputBeforeMention = text.slice(0, position)
                const outputAfterMention = text.slice(caretPosition.value)
                const output = outputBeforeMention + mentionSyntax + outputAfterMention
                messageInputArea.value.textContent = output
                const newPosition = position + mentionSyntax.length
                setEndOfContenteditable(newPosition)
                mentionBoxForced.value = false
            }
            else if(keyCharacters.value.length && text){             
                let searchText = keyCharacters.value
                let replacement = mentionSyntax
                const lastIndex = text.lastIndexOf(searchText);
                const hasAt = text[lastIndex - 1] && (text[lastIndex - 1] == '@' || text[lastIndex - 1] == '＠') ? 1 : 0
                if (lastIndex === -1) {
                    return text;
                }
                const beforeLastIndex = text.slice(0, (lastIndex - hasAt));
                const afterLastIndex = text.slice(lastIndex + searchText.length);
                const result = beforeLastIndex + replacement.replace(/[@＠]/g, '') + afterLastIndex;
                messageInputArea.value.textContent = result
                setEndOfContenteditable(beforeLastIndex.length + mentionSyntax.length)
                keyCharacters.value = '';
            }
            msgSave()
        }    
    }, 
    composeUpdate = (event: CompositionEvent) => {
        if(event.data == '@' || event.data == '＠'){ 
            keyCharacters.value = ''
            highlighted.value = 0
            mentionBoxToggle.value = true;
            mentionBoxForced.value = false
        }else{
            keyCharacters.value = event.data as string
            if(!keyCharacters.value.length){
                resetMention()
            }
        }                
    }
    const setEndOfContenteditable = (pos: number) => { 
        if( !messageInputArea.value ) return  
        var node = messageInputArea.value
        node.focus();
        const textNode = node.firstChild as Text;
        if( !textNode ) return 
        var range = document.createRange();
        const nPos = pos <= textNode.length ? pos : textNode.length
        range.setStart(textNode, nPos);
        range.setEnd(textNode, nPos);
        var sel = window.getSelection();
        sel?.removeAllRanges();
        sel?.addRange(range);
    }
    const getCharacterPrecedingCaret = () => {
        var precedingChar = "";
        const sel = window.getSelection();
        if (sel && messageInputArea.value && sel.rangeCount > 0) {
            const range = sel.getRangeAt(0).cloneRange();
            range.collapse(true);
            range.setStart(messageInputArea.value, 0);
            precedingChar = range.toString().slice(-1);
        }
        if((precedingChar === '＠' || precedingChar === '@')){
            keyCharacters.value = ''
            highlighted.value = 0
            mentionBoxToggle.value = true;
        }else{
            mentionBoxToggle.value = false;                   
            
        }
    }
    const pasteListener = async (e: ClipboardEvent) => {                    
        if(!messageInputArea.value || !e.clipboardData) return
        var text = e.clipboardData.getData("text/plain");            
        if(!text || text == ''){           
            if(!e.clipboardData.files.length) return
            e.preventDefault();    
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
                    attachedFiles.value.push({
                        src: source,
                        name: e.clipboardData.files[i].name,
                        uId: uniqueId,
                        ext: extension,
                        file: e.clipboardData.files[i]
                    });  
                    formData.append(i, e.clipboardData.files[i])
                }               
            } 
            uploadStart(formData)
            await nextTick()
            msgSave();
            return
        }    
        setTimeout(() => {
            msgSave();
        }, 0);
    }
    const caretPos = (event: Event) => {
        const target = event.target as HTMLElement;
        var caretOffset:number | undefined = 0;
        if (window.getSelection) {
            var range = window.getSelection()?.getRangeAt(0);
            var preCaretRange = range?.cloneRange();
            preCaretRange?.selectNodeContents(target);
            if(range){
                preCaretRange?.setEnd(range.endContainer, range.endOffset);
                caretOffset = preCaretRange?.toString().length;
                caretPosition.value = caretOffset ? caretOffset : 0
            }
        }   
        msgSave()         
    }
    const msgSave = () => {                        
        setTimeout(() => { 
            if(!openedBoard.value || !messageInputArea.value) return
            var text = messageInputArea.value?.textContent    
            if(text){
                localStorage.setItem('temp_message_' + openedBoard.value.id, text);
            } 
            else{
                localStorage.removeItem('temp_message_' + openedBoard.value.id);     
            }                         
        }, 100);
    }
    const addAttachment = (event: Event) => {
        const target = event.target as HTMLInputElement;
        if(target.files && target.files.length){
            const formData = new FormData()                  
            for(var i in target.files) {                
                if(target.files[i].type !== undefined){
                    var uniqueId = Math.random().toString(36).substring(5);
                    
                    var source = 'nonimagefile'
                    if(target.files[i].type.indexOf('image') > -1){
                        var source = URL.createObjectURL(target.files[i]);
                    }               
                    const name = target.files[i].name;
                    const lastDot = name.lastIndexOf('.');
                    const fileName = name.substring(0, lastDot);
                    const extension = name.substring(lastDot + 1);
                    attachedFiles.value.push({
                        src: source,
                        name: target.files[i].name,
                        uId: uniqueId,
                        ext: extension,
                        file: target.files[i]
                    });  
                    formData.append(i, target.files[i])
                }               
            } 
            uploadStart(formData)
            target.value = '';
            msgSave();
        }
        
    }   
    const progressload = (e: AxiosProgressEvent) => {              
        if (e.total) {
            progressPercentage.value = Math.floor((e.loaded * 100) / e.total);
        }                          
    }
    const uploadStart = async(formData: FormData) => {
        const files = await api.post('/attach_upload_api', formData, {}, {
            onUploadProgress: progressload
        })
  
        if(files && files.length){
            for( let i in files){
                successUploadedFiles.value.push(files[i])
            }
        }            
        progressPercentage.value = 0

    }      
    const footerDropEnter = (event: DragEvent) => {
        const data = event.dataTransfer
        if(!data) return
        if (data.types) {
            for (var i = 0; i < data.types.length; i++) {
                if (data.types[i] == "Files") {
                    dropActive.value = true
                }
            }
        }                
        return false;          
    }
    const footerDropLeave = () => {
        setTimeout(() => {
            dropActive.value = false
        }, 0);            
    }
    const footerDropEnterFromFile = () => {
        if(sharingData.active){
            dropActive.value = true
        }
    }
    const dropSharingItems = () => {
        if(sharingData.active && sharingData.drag && sharingData.files && messageInputArea.value){
            sharingFiles.value = sharingData.files
            if(sharingData.text){
                var el = messageInputArea.value
                const nl = el.textContent && el.textContent.length ? '\n' : '';
                el.textContent = el.textContent + nl + sharingData.text      
                msgSave()
            }
            resetSharingData()
            footerDropLeave();
        }
    }
    const footerDropDropped = (event: DragEvent) => {  
        footerDropLeave();      
        const files = event.dataTransfer?.files
        if(!files || !messageInputArea.value) return         
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
                    attachedFiles.value.push({
                        src: source,
                        name: event.dataTransfer.files[i].name,
                        uId: uniqueId,
                        ext: extension,
                        file: event.dataTransfer.files[i]
                    });  
                    formData.append(i, event.dataTransfer.files[i])
                }               
            } 
            uploadStart(formData)        
        }   
        msgSave();       
    }
    const removeAttachment = async (image: MessageFile) => {  
        successUploadedFiles.value = successUploadedFiles.value.filter( ob => ob !== image )
        await api.post('/remove_temp_file', {id: image.id})
        msgSave();          
    }
    const removeSharingFile =  (image:SharingFile) => {
        sharingFiles.value = sharingFiles.value.filter( obj => obj !== image)
        msgSave();   
    }
    const enterSend = (event: KeyboardEvent) => {
        if(mentionBox.value && mentionBox.value.highlighted > -1){
            const user = mentionAbleList.value[mentionBox.value.highlighted]
            mentionBox.value.mentionUser(user, mentionBox.value.highlighted)
            event.preventDefault()
        }
        if(event.altKey){
            commentSendConfirm(0)
        }
    }
    const focused = () => {
        if ("virtualKeyboard" in navigator) {
            (navigator as any).virtualKeyboard.overlaysContent = true;                   
        }
    }
    const mentionBoxForceOpen = () => {
        mentionBoxForced.value = !mentionBoxForced.value
        if(mentionBoxForced.value && !mentionAbleList.value.length){
            toast('メンションできるユーザーがいません。')
        }            
    }

</script>
