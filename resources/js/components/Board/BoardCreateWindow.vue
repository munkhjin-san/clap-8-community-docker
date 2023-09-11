<template>
    <div @click="closeModal" class="overlay" style="z-index: 24;font-size:14px">
        <div id="createModal" class="chatCreate" ref="createModal" @click.stop>
            <div class="chatCreateHeader">
                <div v-if="step > 0" @click="step--" class="m-close-button" style="position: unset;margin-right: -10px;">
                    <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
                <h1>{{ step == 0 ? $t('createNewChat') : $t('back') }}</h1>
                <div @click="$emit('close')" class="m-close-button" style="position: unset;margin-left: auto;">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
                
            </div>
            <div>
                <!-- <div style="display:flex;align-items: center;">
                    <h1 :style="{fontSize: '17px', margin: step == 0 ? '-8px 0 15px' : '-8px 0 15px 15px'}">{{ step == 0 ? $t('createNewChat') : $t('back') }}</h1>
                    <div @click="$emit('close')" class="m-close-button">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                    <div v-if="step > 0" @click="step--" class="m-close-button" style="left:10px;right:auto">
                        <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg>
                    </div>
                </div> -->
                <div v-if="step == 0" class="selector-search">
                    <div style="display:flex;margin-bottom:15px">
                        <div :class="['ch-selector', {chSelected : chatType == 0}]" @click="switchType(0)">{{ $t('groupChat') }}</div>
                        <div :class="['ch-selector', {chSelected : chatType == 1}]" @click="switchType(1)">{{ $t('privateChat') }}</div>                
                    </div>

                    <PostSearchBar className="newChatMemberSearch" :searching="searching" @searchStart="searchStart"/>
                </div>
                <div v-if="step == 1" class="selector-search"></div>

            </div>

            <div style="height: -webkit-fill-available;overflow:hidden auto;">
                <div ref="selectedArea" class="selectedArea" v-if="selectedList.length" style="margin-bottom: 15px">
                    <div>
                        <div style="font-weight: 600;margin-bottom: 15px;">{{ $t('selectedMember') }} ({{ selectedList.length }})</div>
                        <div class="selected-list">
                            <div @click="releaseUser(selectedUser)" v-for="selectedUser in selectedList">
                                <div class="suggested-wrap">
                                    <UserIcon :user="selectedUser" imgClass="userNormalIcon" size="30"/>                                
                                    <div>{{ selectedUser.name }}</div>                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div  v-if="viewSortableUsers" style="height: -webkit-fill-available;user-select: none;">
                    <div v-if="friendList.length">
                        <div style="font-weight: 600;margin-bottom: 15px;">{{ $t('myMembers') }}</div>
                        <div class="suggested-list">
                            <div :key="friend.id" v-for="friend in friendList">
                                <div @click="selectToUser(friend)" class="suggested-wrap">
                                    <!-- <div v-if="chatType == 0">
                                        <label class="check-container" style="align-self: center;">
                                            <input @click.prevent disabled id="allMemberSelector" :checked="selectedList.map(ob => ob.id).includes(friend.id)" name="" type="checkbox">
                                            <span class="checkmark-mini"></span>
                                        </label>  
                                    </div> -->
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
                                    <!-- <div v-if="chatType == 0">
                                        <label class="check-container" style="align-self: center;">
                                            <input @click.prevent disabled id="allMemberSelector" :checked="selectedList.map(ob => ob.id).includes(friend.id)" name="" type="checkbox">
                                            <span class="checkmark-mini"></span>
                                        </label>  
                                    </div> -->
                                    <UserIcon :user="friend" imgClass="userNormalIcon" size="30"/>
                                    <div class="suggested-user-name">{{ friend.name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="step == 1">
                    <Form ref="boardCreate" v-slot="{ errors }">
                        <!-- <p style="font-size: 14px;margin: 15px 0px;">{{ $t('groupChatTitle') }}</p> -->
                        <!-- <div class="" style="position:relative">                                
                            <input autocomplete="off" class="recordText" @input="iconPreviewText($event.target.value)" v-model="title" type="text" name="title">   
                        </div>   -->
                        <div class="" style="position:relative">           
                            <span :class="{smallPlc : $store.state.activeInput == 'chatTitle' || title.length}" class="form-plc">{{ $t('groupChatTitle') }}</span>                     
                            <Field 
                                @input="validateTitle" 
                                @focus="$store.commit('setActiveInput', 'chatTitle')" 
                                @blur="$store.commit('setActiveInput', '')"  
                                rules="required|max:100" 
                                id="chatTitle" 
                                autocomplete="off" 
                                class="recordText slide-plc" 
                                v-model="title" 
                                type="text" 
                                name="title" 
                            />   
                            <span class="form-error" style="font-size: 11px;color:tomato">{{ errors.title }}</span>                        
                        </div> 
                    </Form> 
                    <div>
                        
                        <div class="form-border" style="margin-top: 15px;padding: 10px;position:relative">   
                            <p style="font-size: 14px;color: gray;">{{ $t('Icon') }}</p>                     
                            <div v-if="tempImage" style="height: auto;max-height:calc(80vh / 2);background:#efefef;width: 100%;margin: auto;position:relative">
                                <div @click="cancelCrop" style="position:absolute;right:10px;top:10px;background:#fff;border-radius:50px;min-width:30px;width:30px;height:30px;cursor:pointer;z-index: 5;display: flex;box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;"> 
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width:10px;height:10px;margin:auto;" fill="#000" viewBox="0 0 32 32">
                                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                    </svg>
                                </div>
                                <img id="cropImage" :src="tempImage"> 
                                
                            </div>
                            <div v-else>
                                <!-- <p style="margin:auto 10px auto 0;">{{ $t('iconPreview') }}</p> -->
                                <div style="width: fit-content;padding: 15px;margin: auto;">
                                    <div id="boardIconPreview" class="iconPreview">
                                        <div v-show="!upFileArray.length" id="iconPreviewInnerText" :style="previewFontSize" class="iconPreviewInner" v-html="titleText"></div>
                                        <div v-if="upFileArray.length" style="position:absolute;">
                                            <img style="height:45px;width:45px;border-radius:50%" :src="$store.state.baseLocation + '/content/board_icon/' + upFileArray[0]"/>
                                            <div @click.stop="iconDelete()" style="position:absolute; top:-5px;right:-5px;border-radius:50%;background:rgb(181 181 181);">
                                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="padding:4px;fill:#fff;" width="10" height="10" viewBox="0 0 32 32">
                                                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                                </svg>
                                            </div>                                                
                                        </div>
                                    </div>
                                </div>                                    
                                
                                <div style="display: flex;width: fit-content;margin: auto;">
                                    <button class="commentEditButton" style="margin:0">
                                        <label for="boardIcon" class="cursor-pointer" :class="{uploadPassive: upFileArray.length}">
                                            {{ $t('uploadIcon') }}
                                        </label>
                                        <input type="file" name="boardIcon" id="boardIcon" v-on:change="iconSelected" style="display: none;" accept="image/*">
                                    </button>                                       
                                </div>
                            </div>
                        </div> 
                                                    
                    </div>
                    <div style="margin-top: 15px;">
                        
                        <div class="form-border" style="padding:10px">
                            <div style="display: flex;margin-bottom: 15px;color: gray;">
                                <p style="font-size: 14px;">{{ $t('privacySettings') }}</p>
                                <span style="opacity: 0.7;margin-left: 10px;">{{ $t('YouCanChangeItLater') }}</span>
                            </div>
                            
                            <p style="font-size: 14px;margin-bottom: 15px;">{{ $t('newMemberRequest') }}</p>
                            <div style="display: flex;gap: 15px;flex-wrap: wrap;">
                                <div v-for="num in [0, 1, 2]">
                                    <label :for="`chat_pr_st_${num}`" class="check-container privacy-check" style="align-self: center;">
                                        <input v-model="ableJoin" :value="num" :id="`chat_pr_st_${num}`" name="privacy" type="radio">
                                        <span class="checkmark-mini"></span>
                                        {{ $t(privacyTitle(num)) }}
                                    </label>  
                                </div>   
                            </div>   
                            
                            <p style="font-size: 14px;margin: 15px 0px;">{{ $t('messagePermission') }}</p>
                            <div style="display: flex;gap: 15px;flex-wrap: wrap;">
                                <div v-for="num in [0, 1]">
                                    <label :for="`msg_pr_st_${num}`" class="check-container privacy-check" style="align-self: center;">
                                        <input v-model="messageFrom" :value="num" :id="`msg_pr_st_${num}`" name="messagePermission" type="radio">
                                        <span class="checkmark-mini"></span>
                                        {{ $t(permissionTitle(num)) }}
                                    </label>  
                                </div>  
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>

            <div style="text-align: center;margin-top: auto;padding-top: 20px;">
                <LoaderButton @triggered="toContinue" :loading="loader" :content="createValid.button"/>
            </div>

        </div>

        
    </div>
</template>

<script>
import PostSearchBar from '../Post/PostSearchBar.vue'
import { nextTick } from 'vue'
import UserIcon from './Mixed/UserIcon.vue'
import Cropper from 'cropperjs';
import LoaderButton from '../Global/LoaderButton.vue'
import 'cropperjs/dist/cropper.css';

    import { Field, Form } from 'vee-validate'

    export default {
        emits:['close', 'openPrivateBoard'],
        data(){
            return{
                chatType: 0,
                searching: 0,
                keyword: '',
                possibleMemberList: [],
                excludeList: [],
                lock: false,
                selectedList: [],
                step: 0,
                tempImage: null,
                cropperInstance: null,
                loader: false,
                upFileArray: [],
                iconFileInfo: [],
                previewFontSize: null,
                title: '',
                iconId: null,
                titleText: '',
                messageFrom: 0,
                ableJoin: 0,
            }
        },        
        mounted() {
            this.getMembers();
        },
        components:{
            PostSearchBar,
            LoaderButton,
            UserIcon,
            Field,
            Form,
        },
        computed:{
            friendList(){
                return this.possibleMemberList && this.possibleMemberList.friends && this.possibleMemberList.friends.length ? this.possibleMemberList.friends : []
            },
            suggestedList(){
                return this.possibleMemberList && this.possibleMemberList.suggested && this.possibleMemberList.suggested.length ? this.possibleMemberList.suggested : []
            },
            viewSortableUsers(){
                if(this.chatType == 0){
                    return this.step == 0
                }else{
                    if(this.selectedList.length) return false
                    return true
                }
            },
            createValid(){
                if(this.chatType == 0){
                    
                    return this.selectedList.length ? { state: true, button: this.step == 0 ? this.$t('toNext') : this.$t('createChat')} : {state: false, button: this.$t('toNext')}


                }else{
                    return this.selectedList.length == 1 ? { state: true, button: this.$t('createChat')} : {state: false, button: this.$t('createChat')}
                }
            }
        },
        methods:{
            async validateTitle(){
                const result = await this.$refs.boardCreate.validate();
                this.iconPreviewText(event.target.value)
            },
            cancelCrop(){
                if(this.cropperInstance){
                    this.cropperInstance.destroy();
                    this.cropperInstance = null;
                }
                this.tempImage = null 
            },
            iconDelete(){
                this.iconDeleteFlag = 1;
                this.upFileArray = [];
                this.iconId = null;
                this.iconPreviewText(this.title);            
            },
            iconSelected() {          
                this.tempImage = URL.createObjectURL(event.target.files[0]);


                setTimeout(() => {
                    var image = document.getElementById('cropImage');
                    var width = 300;
                    var height = 300;
                    var container = document.getElementById('uploadContainer');          
                    if(container){
                        // width = container.offsetWidth * 0.8;
                        // height = container.offsetHeight * 0.8;
                        
                    }       

                    if(this.cropperInstance){
                        this.cropperInstance.destroy();
                        this.cropperInstance = null;
                    }            
                    this.cropperInstance = new Cropper(image, {              
                        dragMode: 'move',
                        preview: '.preview',
                        aspectRatio: 1 / 1,
                     
                        viewMode: 1,
                        responsive:true,
                        autoCrop: true,
                        background: false,
                        guides: false,
                        zoomable:false,
                        crop(event) {
                            
                            
                        },            
                    });
                },0)
                
               
                
            },
            iconPreviewText(text){
                const no_space = text ? text.replace(/\s/g, '', '　') : ''  

                switch(true) {
                case (no_space.length == 0):
                    this.titleText = ''
                    break;
                case (no_space.length == 1):
                    this.previewFontSize = 'font-size:22px;margin-bottom:13px;';
                    this.titleText = no_space;
                    break;
                case (no_space.length == 2):
                    this.previewFontSize = 'font-size:17px;';
                    this.titleText = no_space;
                    break;
                case (no_space.length == 3):
                    this.previewFontSize = 'font-size:13px;';
                    this.titleText = no_space;
                    break;
                case (no_space.length == 4):
                    var upper = no_space.slice(0, 2);
                    var lower = no_space.slice(2, 4);
                    this.previewFontSize = 'font-size:13px;';
                    this.titleText = `${upper}<br>${lower}`;
                    break;
                case (no_space.length == 5):
                    var upper = no_space.slice(0, 3);
                    var lower = no_space.slice(3, 5);
                    this.previewFontSize = 'font-size:12px;';
                    this.titleText = `${upper}<br>${lower}`;
                    break;
                case (no_space.length >= 6):
                    var upper = no_space.slice(0, 3);
                    var lower = no_space.slice(3, 6);
                    this.previewFontSize = 'font-size:12px;';
                    this.titleText = `${upper}<br>${lower}`;
                    break;
                default:                   
                }                
            },
            toContinue(){
                if(this.chatType == 0){
                    if(this.createValid.state){
                        if(this.step == 0){
                            this.step = 1
                            return
                        }else if(this.step == 1){
                            this.cropComplete()
                        }
                        
                    }
                    else {
                        if(this.step == 0){
                            if(!this.selectedList.length){
                                this.errorToast(this.$t('toContinueSelectMembers'))
                            }
                            
                        }else if(this.step == 1){
                            
                        }
                        
                    }
                }else{
                    this.boardAdd()
                }
            },
            releaseUser(user){
                if(this.step > 0){
                    return
                }
                const index = this.selectedList.indexOf(user);
                if(index > -1){
                    this.selectedList.splice(index, 1)                        
                }
                this.excludeList = this.selectedList.map(ob => ob.id)
                this.getMembers()
            },
            selectToUser(user){
                if(this.chatType == 0){
                    const index = this.selectedList.indexOf(user);
                    if(index > -1){
                        this.selectedList.splice(index, 1)                        
                    } else {
                        this.selectedList.push(user);
                        setTimeout(() => {
                            const myDiv = this.$refs.selectedArea;
                            myDiv.scrollTop = myDiv.scrollHeight;
                        }, 0);
                    } 
                }else if(this.chatType == 1){
                    this.selectedList = []
                    this.selectedList.push(user)                    
                }
                this.excludeList = this.selectedList.map(ob => ob.id)
                this.getMembers()

            },
            closeModal(){
                if (!this.$refs.createModal.contains(event.target)) {
                    this.$emit('close')
                }
            },
            searchStart(key){

                this.keyword = key
                this.getMembers()
                this.searching = 2


            },
            getMembers(){
                this.lock = true
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
            switchType(flag){
                this.chatType = flag
                this.excludeList = this.selectedList = []
                this.getMembers()
            },
            cropComplete(flag){
                if(this.loader) return
                if(this.cropperInstance){
                    this.loader = true                    
                    this.cropperInstance.getCroppedCanvas({

                    }).toBlob((blob) => {                        
                        const formData = new FormData()
                        formData.append('file', blob)            
                        axios.post('/icon_up_api', formData)
                        .then(response => {
                            if(response.data.icon_id && response.data.set_path){
                                this.upFileArray = []
                                this.upFileArray[0] = response.data.set_path;                        
                                this.iconId = response.data.icon_id;                            
                                this.boardAdd()
                            }else{                                
                                this.errorToast(this.$t('commonError'))
                            }
                           
                        }).catch(function (error) { 
                            if(error.response.status == 413){
                                this.errorToast(this.$t('imageIsTooBig'))
                                this.cancelCrop()
                            }else{
                                this.errorToast(this.$t('commonError'))  
                            }
                            this.loader = false                         
                        }.bind(this));                      
                    });
                }else{
                    this.boardAdd() 
                }
            },
            async boardAdd(){
                if(this.chatType == 0 && this.$refs.boardCreate){
                    const result = await this.$refs.boardCreate.validate();
                    if(!result.valid) {
                        this.loader = false;
                        return
                    }
                }
                
                
                const selectedUsers = this.selectedList.map(ob => ob.id);
                if(!selectedUsers.length){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('enterChatTitleOrSelectUser'),
                        closeButton: true, 
                        autoClose: true,
                        answers: [this.$t('confirmToAction')]
                    }) 
                    return                
                }         
                
                
                const noSpace = this.title.replace(/\s/g, '', '　')  
                const UpFileArray = this.upFileArray;               
                
                this.loader = true
                const titleLen = this.title == null ? 4 : this.title.length              
                axios.post('/chat_create', {
                    private_flag: this.chatType,
                    str_len : titleLen,
                    icon_id: this.iconId,
                    to_users: selectedUsers,
                    title_no_space: noSpace,
                    able_join: this.ableJoin,
                    message_from: this.messageFrom,
                    title: this.title,
                    file: UpFileArray,
                }).then(response => {    
                    setTimeout(() => {
                        if(response.data.message == 'success'){
                            this.$emit('reload', response.data.data.id) 
                            this.loader = false   
                            this.$emit('close')   
                        }else if(response.data.message == 'empty' || response.data.message == 'titleNeeded'){
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: this.$t('enterChatTitleOrSelectUser'),
                                closeButton: true, 
                                autoClose: true,
                                answers: [this.$t('confirmToAction')]

                            }) 
                            this.loader = false   
                            this.$emit('close')   
                        }else if(response.data.message == 'existAndAccessable'){                            
                            if(response.data.restored){
                                this.$emit('reload', response.data.data.id) 
                            }else{
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
                                        this.$emit('openPrivateBoard', response.data.data.id)
                                        
                                    }     
                                    this.loader = false   
                                    this.$emit('close')      
                                       
                                });                               
                            }                        
                        }  
                        
                    }, 500)                
                                                      
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    this.loader = false                         
                }.bind(this));
            },
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction')]

                }) 
            },
            privacyTitle(num){
                return num == 0 ? 
                'approvalNeeded' : num == 1 ? 
                'openPrivacy' : 
                'closedPrivacy'
            },
            permissionTitle(num){
                return num == 0 ? 
                'byAddedTime' : 
                'byAll' 
            }
        }
    }
</script>
<style lang="scss">
    .selected-list{
        display: flex;
        gap:10px;
        white-space: nowrap;
        flex-wrap: wrap;
    }
    .suggested-list{
        display: flex;
        flex-direction: column;
        gap: 15px;
        
    }
    .suggested-wrap{
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        padding: 10px;
        margin: -10px 0;
        white-space: nowrap;    
        position: relative;
    }
    .suggested-user-name{
        margin-right: 30px;
        
        overflow: hidden;
        text-overflow: ellipsis;
        margin-left: 5px;
    }
    .suggested-wrap:hover{
        background: var(--bg2);
        color: var(--primary-color);
    }
    .chatCreate{
        width: 50%;
        height: 70%;
        background: var(--background-color);
        padding: 30px;
        color: var(--primary-color);
        position: relative;
        display: flex;
        flex-direction: column;
    }
    .ch-selector{
        padding: 10px;
        box-sizing: border-box;
        border: solid thin transparent;
        color: var(--primary-color);
        cursor: pointer;
    }
    .chSelected {
        background-color: var(--bg2);
        border: solid thin gray;
    }
    .invalidCreate{
        cursor: not-allowed;
        opacity: 0.6;
    }
    .selectedArea{
        background: var(--bg2);
        padding: 15px;
        min-height: 60px;
    }
    .m-close-button{
        position: absolute;
        right: 10px;
        top: 10px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--primary-color);
        fill: var(--primary-color);
    }
    .privacy-check{
        font-size: inherit !important;
        color: var(--primary-color) !important;
        padding-left: 25px !important;
        margin-bottom: 0 !important;
        line-height: 1.3 !important;
    }
    .chatCreateHeader{
        display:flex;
        align-items: center;
        position: absolute;
        width:-webkit-fill-available;
        width:--moz-available;
        left:0;
        top:0;
        height: 40px;
        padding: 10px 20px 10px 20px;
        width: calc(100% - 40px);
    }
    .chatCreateHeader > h1 {
            margin-left: 10px;
        }
    .selector-search{
        margin: 30px 0 20px 0;
    }
    @media screen and (max-width: 959px) {
        .memberHeader{
            overflow-x: auto;
            width: calc(100% - 40px) !important;
        }
        .selector-search{
            margin: 20px 0;
        }
        .chatCreateHeader{
            padding: 0;
            position: unset;
            margin: -15px -15px -15px 0;
            width: unset;
        }
        .chatCreateHeader > h1 {
            margin-left: 0;
        }
        .chatCreate{
            padding: 20px;
            width: calc(100% - 40px);
            height: calc(100% - 40px);
        }

    }
</style>
