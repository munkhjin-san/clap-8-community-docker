<template>
    <div @mousedown="closeModal" class="overlay" v-if="user">
        <Form ref="form" v-slot="{ errors }" style="width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;">  
            <div id="editModal01" class="chatCreate" ref="editModal01" @mousedown.stop style="overflow: hidden auto;">
                <div v-if="step == 0" style="position:absolute;bottom: 10px;left: 10px;color:gray;font-size: 11px;">{{ $t('registeredDate') }} : {{ createdDate(user.created_at) }} (Japan Standart Time)</div>                   
                <div v-if="step == 0">
                    <div @click="$router.go(-1)" class="m-close-button" v-if="!$store.state.mobile" style="z-index:22">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                    <div @click="$router.go(-1)" class="m-close-button" style="z-index:22;right: auto;left: 10px;" v-else>
                        <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg>
                        
                    </div>
                    <h1 :style="{margin: $store.state.mobile ? '2px 0 0 40px' : '0'}">{{$t('personalEdit')}}</h1>
                </div>
                <div style="display:flex;align-items: center;">
                    <div v-if="step > 0" @click="step = 0" class="m-close-button" style="left:10px;right: auto;">
                        <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg> 
                    
                    </div>
                    <h1 class="subMenuTitle">{{ settingTitle }}</h1>
                </div>            
                <Transition name="page-transition" mode="out-in">
                <div v-if="step == 0">  
                    <!-- <div>
                        <div class="title" style="display: flex;align-items:center;position: relative;">
                            <p class="record-inner">{{ $t('color') }}:</p>
                            
                            <div class="h-chip-pb">?
                                <div class="qr-exp-pb">{{ $t('colorExplaination')}}</div>
                            </div>       
                            
                            
                        </div>   
                        <div class="c-picker" style="position: unset;width: fit-content;padding: 0;">                                                        
                            <div @click="setSelectedColor(color.id)" :id="color.name" :class="color.code" :key="'color_' + color.id" v-for="color in avialableColors" :style="{background: $store.state.dark ? color.dark : color.light}">
                                <div v-if="user.color == color.id">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" :fill="$store.state.dark ? '#8d8d8d' : '#000'">
                                        <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>                            
                    </div>   -->
                    <div style="margin-top:25px">
                        <div @click="step = 5" class="suggested-wrap p-setting-item" v-html="$t('userPrivacy')"></div>
                        <!-- <div @click="step = 2" class="suggested-wrap p-setting-item" v-html="$t('changeLogin')"></div> -->
                        <div @click="step = 1" class="suggested-wrap p-setting-item" v-html="$t('updatePassword')"></div>
                        <div @click="step = 4" class="suggested-wrap p-setting-item" v-html="$t('blockList')"></div>
                        <div @click="generateQr()" class="suggested-wrap p-setting-item" v-html="$t('regenerateQrCode')"></div>                       
                        <div @click="deleteAccount" class="suggested-wrap p-setting-item p-danger" v-html="$t('deleteAccount')"></div>
                    </div>
                    
                </div>
                <div v-else-if="step==1" style="margin-top:20px">
                    <div class="pw-reset" style="margin-top: 20px;">
                        
                        <div style="position:relative">    
                            <span :class="{smallPlc : $store.state.activeInput == 'currentPassword' || currentPassword.length}" class="form-plc">{{ $t('currentPassword') }}</span>                            
                            <Field rules="required" @focus="$store.commit('setActiveInput', 'currentPassword')" @blur="$store.commit('setActiveInput', '')" id="currentPassword" autocomplete="off" class="recordText slide-plc" v-model="currentPassword" type="password" name="currentPassword" />   
                            <span class="form-error">{{ errors.currentPassword }}</span>
                        </div>  
                    </div> 
                    <div class="pw-reset">
                        
                        <div style="position:relative">      
                            <span :class="{smallPlc : $store.state.activeInput == 'newPassword' || newPassword.length}" class="form-plc">{{ $t('newPassword') }}</span>                          
                            <Field id="newPassword" @focus="$store.commit('setActiveInput', 'newPassword')" @blur="$store.commit('setActiveInput', '')" rules="required|passwordCase|min:8" autocomplete="off" class="recordText slide-plc" v-model="newPassword" type="password" name="newPassword" />   
                            <span class="form-error">{{ errors.newPassword }}</span>
                        </div>  
                    </div> 
                    <div class="pw-reset">
                        
                        <div style="position:relative">       
                            <span :class="{smallPlc : $store.state.activeInput == 'newPasswordConfirm' || newPasswordConfirm.length}" class="form-plc">{{ $t('newPasswordConfirm') }}</span>                         
                            <Field id="newPasswordConfirm" @focus="$store.commit('setActiveInput', 'newPasswordConfirm')" @blur="$store.commit('setActiveInput', '')" rules="confirmed:@newPassword" autocomplete="off" class="recordText slide-plc" v-model="newPasswordConfirm" type="password" name="newPasswordConfirm" />   
                            <span class="form-error">{{ errors.newPasswordConfirm }}</span>
                        </div>  
                    </div> 
                    <div style="text-align: center;margin-top: auto;padding-top: 30px;">
                        <LoaderButton @triggered="reset" :loading="loader" :content="$t('save')"/>
                    </div>


                </div>
                <div v-else-if="step==2" class="login_step2">
                    <!-- <div class="pw-reset">
                        <div style="display:flex; align-items:center; margin: 30px 0px 15px;">
                            <p style="font-size: 16px;">{{$t('phoneNumber')}}</p>
                            <div v-if="user.phone_isVerified" style="display:flex; margin-left: 8px; align-items:center;padding:5px 10px;background: var(--bg2);border-radius: 20px;color: var(--primary-color);">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32" fill="var(--primary-color)"><path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                                <span style="margin-left:5px;" class="verified-text">{{ $t('verified')}}</span>
                            </div>
                            <div v-else style="margin-left: 8px;">
                                <span class="verified-text">({{ $t('notverified')}})</span>
                            </div>
                        </div>
                        
                        <div style="display:flex; align-items: center;position:relative">
                            <div class="select-wrapper" style="display: flex;">
                                <select class="recordText country_option" v-model="country_code" name="password_country">
                                    <option value="+81">JP +81</option>
                                    <option value="+976">MN +976</option>
                                </select>
                                <Field class="recordText phone-select" type="phone_login" v-model="phone_login" :rules="this.email_login ? '' : 'required'" name="phone_login" autocomplete="phone_login" autofocus />
                            </div>
                            
                            
                            <svg class="deleteMark" @click="phone_login && deleteLogin(phone_login)" :class="{'notAllowed' : !phone_login || !phone_login}" :disabled="!phone_login" data-v-fe7cdca0="" version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 27 32"><path data-v-fe7cdca0="" d="M18.68 10.952c-0.427-0.035-0.797 0.289-0.832 0.716-0.104 1.271-0.173 2.542-0.243 3.812l-0.104 1.906-0.081 1.906-0.069 1.906c-0.023 0.635-0.035 1.271-0.046 1.906-0.023 1.271-0.046 2.553-0.023 3.824 0.012 0.37 0.289 0.693 0.682 0.728 0.416 0.035 0.774-0.266 0.809-0.67 0.116-1.271 0.196-2.542 0.266-3.812 0.035-0.635 0.081-1.271 0.104-1.906l0.081-1.906 0.069-1.906 0.046-1.906c0.023-1.271 0.058-2.553 0.058-3.824-0.012-0.393-0.312-0.739-0.716-0.774zM9.473 21.21l-0.069-1.918-0.081-1.906c-0.023-0.635-0.069-1.271-0.104-1.906-0.069-1.271-0.15-2.542-0.266-3.812-0.035-0.37-0.347-0.67-0.728-0.67-0.416-0.012-0.751 0.323-0.751 0.739-0.023 1.271 0 2.553 0.023 3.824 0.012 0.635 0.023 1.271 0.046 1.906l0.069 1.906 0.081 1.906 0.104 1.906c0.069 1.271 0.15 2.542 0.243 3.812 0.035 0.393 0.37 0.716 0.774 0.716 0.427 0 0.774-0.347 0.774-0.774 0-1.271-0.023-2.553-0.058-3.824l-0.058-1.906zM14.279 15.515c-0.023-1.271-0.046-2.542-0.092-3.824-0.023-0.404-0.335-0.728-0.739-0.739-0.427-0.012-0.786 0.312-0.809 0.739-0.046 1.271-0.069 2.542-0.092 3.824l-0.023 1.906-0.012 1.906 0.012 1.906c0 0.635 0.023 1.271 0.023 1.906 0.023 1.271 0.058 2.542 0.116 3.824 0.023 0.37 0.323 0.682 0.705 0.705 0.416 0.023 0.762-0.289 0.786-0.705 0.069-1.271 0.104-2.542 0.116-3.824 0.012-0.635 0.023-1.271 0.023-1.906l0.012-1.906-0.023-3.812z"></path> <path data-v-fe7cdca0="" d="M26.64 7.601v-0.012c0-0.531-0.439-0.97-0.982-0.959-0.127 0-0.3 0.012-0.451 0.023-0.231 0.012-0.451-0.046-0.647-0.162-0.312-0.196-0.682-0.404-0.855-0.485l-0.693-0.323c-0.231-0.104-0.474-0.196-0.705-0.289-0.947-0.37-1.918-0.682-2.9-0.924-0.416-0.104-0.947-0.208-1.282-0.277-0.116-0.023-0.196-0.139-0.196-0.254 0.035-0.451 0.081-1.178 0.092-1.536 0.023-0.439 0.023-0.866 0.046-1.305 0.012-0.554-0.416-1.017-0.97-1.028h-0.058l-1.814-0.046c-0.601-0.023-1.213-0.023-1.814-0.023l-1.814 0.012c-0.601 0.012-1.213 0.023-1.814 0.046h-0.081c-0.543 0.023-0.97 0.485-0.947 1.028l0.023 0.647c0.012 0.22 0.012 0.439 0.023 0.647 0.023 0.358 0.058 1.028 0.081 1.479 0 0.139-0.092 0.254-0.231 0.277-0.335 0.058-0.832 0.162-1.259 0.266-0.994 0.231-1.964 0.531-2.911 0.901-0.751 0.289-1.49 0.612-2.207 1.005-0.196 0.116-0.416 0.162-0.635 0.162h-0.485c-0.624 0-1.132 0.497-1.132 1.121v0.012l-0.023 3.5c0 0.635 0.508 1.155 1.144 1.155h0.751l1.074 18.622v0.023c0.046 0.635 0.578 1.144 1.225 1.132l18.449-0.116c0.578 0 1.063-0.462 1.097-1.051l1.040-18.784h0.901c0.543 0 0.994-0.439 0.982-0.994l-0.023-3.489zM10.755 2.38c0-0.081 0.012-0.162 0.012-0.254 0.277 0.012 0.555 0.012 0.832 0.023l1.814 0.012c0.601 0 1.213 0 1.814-0.012l0.82-0.023c0 0.081 0 0.162 0.012 0.254 0.012 0.393 0.035 0.994 0.035 1.352 0 0.058-0.046 0.104-0.104 0.104-0.543-0.046-1.721-0.116-2.576-0.116-0.832 0-2.091 0.081-2.53 0.116-0.069 0.012-0.116-0.046-0.127-0.104-0.012-0.335-0.023-0.97 0-1.352zM22.816 11.033v0.012l-1.201 18.126c-0.023 0.3-0.266 0.52-0.555 0.52l-15.203 0.023c-0.266 0-0.474-0.208-0.497-0.462l-1.19-18.218v-0.012c-0.035-0.612-0.543-1.086-1.167-1.086h-0.866c-0.116 0-0.208-0.092-0.208-0.208v-0.797c0-0.116 0.081-0.208 0.196-0.208 0.254-0.023 0.612-0.069 0.751-0.15h0.012c0.751-0.474 1.571-0.89 2.414-1.248s1.721-0.647 2.622-0.878c1.791-0.474 3.651-0.705 5.51-0.716 1.86 0 3.72 0.22 5.522 0.67 0.901 0.231 1.791 0.508 2.634 0.855 0.22 0.081 0.427 0.173 0.635 0.266l0.312 0.139 0.312 0.15c0.208 0.092 0.404 0.208 0.601 0.312 0 0 0.254 0.15 0.393 0.22 0.104 0.046 0.22 0.116 0.312 0.162 0.058 0.035 0.127 0.046 0.196 0.046h0.312c0.104 0 0.173 0.081 0.173 0.185l-0.012 1.19c0 0.104-0.081 0.185-0.185 0.185h-0.797c-0.543-0.012-0.982 0.393-1.028 0.924z"></path></svg>
                            <small class="form-error" v-if="phoneMessage">{{ phoneMessage }}</small>
                            <span class="form-error">{{ errors.phone_login }}</span>
                        </div>
                        
                    </div> -->
                    <div class="pw-reset">
                        <div style="display:flex; align-items:center;margin: 15px 0px;">
                            <p style="font-size: 16px;">{{$t('emailAddress')}}</p>                            
                            <div v-if="user.email_verified_at" style="display:flex; margin-left: 8px; align-items:center;padding:5px 10px;background: var(--bg2);border-radius: 20px;color: var(--primary-color);">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32" fill="var(--primary-color)"><path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                                <span style="margin-left:5px;" class="verified-text">{{ $t('verified')}}</span>
                            </div>
                            <div v-else style="margin-left: 8px;">
                                <span class="verified-text">({{ $t('notverified')}})</span>
                            </div>
                        </div>
                        <div style="margin-top: 15px;position:relative;display:flex; align-items: center;">
                            <Field class="recordText" type="login" v-model="email_login" :rules="this.phone_login ? '' : 'required'" name="email_login" autocomplete="email_login" autofocus />
                            
                            <svg class="deleteMark" @click="email_login && deleteLogin(email_login)" :class="{'notAllowed' : !email_login || !email_login}" :disabled="!email_login" data-v-fe7cdca0="" version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 27 32"><path data-v-fe7cdca0="" d="M18.68 10.952c-0.427-0.035-0.797 0.289-0.832 0.716-0.104 1.271-0.173 2.542-0.243 3.812l-0.104 1.906-0.081 1.906-0.069 1.906c-0.023 0.635-0.035 1.271-0.046 1.906-0.023 1.271-0.046 2.553-0.023 3.824 0.012 0.37 0.289 0.693 0.682 0.728 0.416 0.035 0.774-0.266 0.809-0.67 0.116-1.271 0.196-2.542 0.266-3.812 0.035-0.635 0.081-1.271 0.104-1.906l0.081-1.906 0.069-1.906 0.046-1.906c0.023-1.271 0.058-2.553 0.058-3.824-0.012-0.393-0.312-0.739-0.716-0.774zM9.473 21.21l-0.069-1.918-0.081-1.906c-0.023-0.635-0.069-1.271-0.104-1.906-0.069-1.271-0.15-2.542-0.266-3.812-0.035-0.37-0.347-0.67-0.728-0.67-0.416-0.012-0.751 0.323-0.751 0.739-0.023 1.271 0 2.553 0.023 3.824 0.012 0.635 0.023 1.271 0.046 1.906l0.069 1.906 0.081 1.906 0.104 1.906c0.069 1.271 0.15 2.542 0.243 3.812 0.035 0.393 0.37 0.716 0.774 0.716 0.427 0 0.774-0.347 0.774-0.774 0-1.271-0.023-2.553-0.058-3.824l-0.058-1.906zM14.279 15.515c-0.023-1.271-0.046-2.542-0.092-3.824-0.023-0.404-0.335-0.728-0.739-0.739-0.427-0.012-0.786 0.312-0.809 0.739-0.046 1.271-0.069 2.542-0.092 3.824l-0.023 1.906-0.012 1.906 0.012 1.906c0 0.635 0.023 1.271 0.023 1.906 0.023 1.271 0.058 2.542 0.116 3.824 0.023 0.37 0.323 0.682 0.705 0.705 0.416 0.023 0.762-0.289 0.786-0.705 0.069-1.271 0.104-2.542 0.116-3.824 0.012-0.635 0.023-1.271 0.023-1.906l0.012-1.906-0.023-3.812z"></path> <path data-v-fe7cdca0="" d="M26.64 7.601v-0.012c0-0.531-0.439-0.97-0.982-0.959-0.127 0-0.3 0.012-0.451 0.023-0.231 0.012-0.451-0.046-0.647-0.162-0.312-0.196-0.682-0.404-0.855-0.485l-0.693-0.323c-0.231-0.104-0.474-0.196-0.705-0.289-0.947-0.37-1.918-0.682-2.9-0.924-0.416-0.104-0.947-0.208-1.282-0.277-0.116-0.023-0.196-0.139-0.196-0.254 0.035-0.451 0.081-1.178 0.092-1.536 0.023-0.439 0.023-0.866 0.046-1.305 0.012-0.554-0.416-1.017-0.97-1.028h-0.058l-1.814-0.046c-0.601-0.023-1.213-0.023-1.814-0.023l-1.814 0.012c-0.601 0.012-1.213 0.023-1.814 0.046h-0.081c-0.543 0.023-0.97 0.485-0.947 1.028l0.023 0.647c0.012 0.22 0.012 0.439 0.023 0.647 0.023 0.358 0.058 1.028 0.081 1.479 0 0.139-0.092 0.254-0.231 0.277-0.335 0.058-0.832 0.162-1.259 0.266-0.994 0.231-1.964 0.531-2.911 0.901-0.751 0.289-1.49 0.612-2.207 1.005-0.196 0.116-0.416 0.162-0.635 0.162h-0.485c-0.624 0-1.132 0.497-1.132 1.121v0.012l-0.023 3.5c0 0.635 0.508 1.155 1.144 1.155h0.751l1.074 18.622v0.023c0.046 0.635 0.578 1.144 1.225 1.132l18.449-0.116c0.578 0 1.063-0.462 1.097-1.051l1.040-18.784h0.901c0.543 0 0.994-0.439 0.982-0.994l-0.023-3.489zM10.755 2.38c0-0.081 0.012-0.162 0.012-0.254 0.277 0.012 0.555 0.012 0.832 0.023l1.814 0.012c0.601 0 1.213 0 1.814-0.012l0.82-0.023c0 0.081 0 0.162 0.012 0.254 0.012 0.393 0.035 0.994 0.035 1.352 0 0.058-0.046 0.104-0.104 0.104-0.543-0.046-1.721-0.116-2.576-0.116-0.832 0-2.091 0.081-2.53 0.116-0.069 0.012-0.116-0.046-0.127-0.104-0.012-0.335-0.023-0.97 0-1.352zM22.816 11.033v0.012l-1.201 18.126c-0.023 0.3-0.266 0.52-0.555 0.52l-15.203 0.023c-0.266 0-0.474-0.208-0.497-0.462l-1.19-18.218v-0.012c-0.035-0.612-0.543-1.086-1.167-1.086h-0.866c-0.116 0-0.208-0.092-0.208-0.208v-0.797c0-0.116 0.081-0.208 0.196-0.208 0.254-0.023 0.612-0.069 0.751-0.15h0.012c0.751-0.474 1.571-0.89 2.414-1.248s1.721-0.647 2.622-0.878c1.791-0.474 3.651-0.705 5.51-0.716 1.86 0 3.72 0.22 5.522 0.67 0.901 0.231 1.791 0.508 2.634 0.855 0.22 0.081 0.427 0.173 0.635 0.266l0.312 0.139 0.312 0.15c0.208 0.092 0.404 0.208 0.601 0.312 0 0 0.254 0.15 0.393 0.22 0.104 0.046 0.22 0.116 0.312 0.162 0.058 0.035 0.127 0.046 0.196 0.046h0.312c0.104 0 0.173 0.081 0.173 0.185l-0.012 1.19c0 0.104-0.081 0.185-0.185 0.185h-0.797c-0.543-0.012-0.982 0.393-1.028 0.924z"></path></svg>
                            <small class="form-error" v-if="emailMessage">{{ emailMessage }}</small>
                            <span class="form-error">{{ errors.email_login }}</span>                                
                        </div>
                    </div>
                    <div style="text-align: center;margin-top: auto">
                        <div class="col-md-6 offset-md-4">
                            <LoaderButton @triggered="changeLoginInfo" :loading="loader" :content="$t('toNext')"/>
                        </div>
                    </div>
                </div>
                <div v-else-if="step==3" style="margin-top:20px">
                    <div class="pw-reset">
                        <p style="font-size: 14px;margin: 10px 0px;">{{$t('otp')}} {{user_login}}</p>
                        <div class="col-md-6" style="margin-top:15px;position:relative;">
                            <Field class="recordText" type="tel" name="verification_code" v-model="verification_code" rules="required" />
                            <span class="form-error">{{ errors.verification_code }}</span>
                            <small class="form-error" v-if="verifyError">{{ verifyError }}</small>
                            <small class="form-error" v-if="newCode">{{ newCode }}</small>
                        </div>
                        
                    </div>
                    <div style="text-align: center;margin-top: auto;">
                        <div class="col-md-6 offset-md-4" >
                            <LoaderButton @triggered="verifyChangeLogin" :loading="loader" :content="$t('verify')"/>
                        </div>
                    </div>
                    <div style="width: 100%;text-align: center;margin-top:30px;">
                        <a @click="sendVerify()" class="sendAgain-link">
                            {{$t('resendCode')}}
                        </a>
                    </div>
                </div>
                <BlockList v-else-if="step == 4" :user="user" :errorToast="errorToast"/>
                <div v-else-if="step == 5" style="margin-top:20px">
                    <div style="margin-top:15px 0;">
                        <div class="title" style="margin: 15px 0;" v-for="privacy in privacySetting">
                            <div style="padding: 10px 0px 0px;display: flex;align-items:center">
                                <input @change="setPrivate(privacy.value)" v-model="privateSetting" type="radio" :id="privacy.id" name="answer" :value="privacy.value">
                                <label style="margin-left:10px;cursor:pointer" :for="privacy.id">{{privacy.label}}</label>  
                            </div>
                            <div style="margin-left:30px; line-height:1.8; color:gray;font-size: 13px;margin-top:10px" v-html="privacy.info"></div>
                            
                        </div> 
                    </div> 
                </div>
                </Transition>                    
                       
            </div>
        </Form>
    </div>
</template>

<script>
import PhoneVerify from '../../Auth/PhoneVerify.vue';
import colors from '../../../../assets/colors.json'
import ToolTip from '../../Global/ToolTip';
import LoaderButton from '../../Global/LoaderButton.vue'
import BlockList from './BlockList.vue';
import { Field, Form  } from 'vee-validate'
  
    export default {
        props: ['user', 'errorToast'],
        emits: ['close', 'reload'],
        data(){
            return{
                privacySetting:[
                    { label: this.$t('userPublic'), value: 1, id:"public", info: this.$t('userPublicInfo')},
                    { label: this.$t('userPrivate'), value: 0, id:"private", info: this.$t('userPrivateInfo')},
                ],
                toggleDisabled: false,                
                privateSetting: this.user && this.user.is_public ? 1 : 0,
                step: 0,
                currentPassword: '',
                newPassword: '',
                newPasswordConfirm: '',
                loader: false,
                avialableColors: colors,
                qrLock: false,
                phone_login: '',
                email_login: '',
                login_token: '',
                country_code: '+81',
                emailMessage: '',
                phoneMessage: '',
                verification_code: '',
                verifyError: '',
                newCode: '',
                mailChange: false,
            }
        },
        mounted() {
            if(this.user){
                this.phone_login = this.user.phone
                this.email_login = this.user.email
            }
        },
        watch:{
            user(){
                this.phone_login = this.user.phone
                this.email_login = this.user.email
            }
        },
        computed:{
            settingTitle(){
                switch (this.step) {
                case 5:
                    return this.$t('userPrivacy')
                case 4:
                    return this.$t('blockList');
                case 3:
                    return this.$t('loginVerification');
                case 2:
                    return this.$t('changeLogin');
                case 1:
                    return this.$t('updatePassword');
                
                default:
                    return ''
                }
            }
        },
        components:{
            ToolTip,
            LoaderButton,
            Field,
            Form,
            PhoneVerify,
            BlockList
        },
        methods:{
            deleteLogin(login){
                if(login == this.user.email && this.user.phone){
                    const uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('deleteField'),
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                        channel: uniqueChannell
                    })
                  
                    emitter.on(uniqueChannell, (data) => {                             
                        if(data.answer == this.$t('confirmToAction')){
                            const params = {
                                email_login : '',
                                phone_login : this.phone_login
                            }
                            axios.post('/profile_delete_api', params)
                            .then(response => {
                                if(response.data == "saved"){
                                    this.step = 0
                                    this.email_login = ''
                                    emitter.emit('setToast', { 
                                        active: true,
                                        content: this.$t('success'),
                                        closeButton: false,
                                    })
                                    this.$emit('reload')
                                }else{
                                    
                                    emitter.emit('setToast', { 
                                        active: true,
                                        content: this.$t('unknownError')
                                    })            
                                }
                            }).catch(error => {
                                if(error.response.status == 422){
                                    emitter.emit('setToast', { 
                                        active: true,
                                        content: this.$t(error.response.data.message)
                                    }) 
                                }
                            })
                        }
                    });
                }else if(login == this.user.phone && this.user.email){
                    const uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('deleteField'),
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                        channel: uniqueChannell
                    })
                  
                    emitter.on(uniqueChannell, (data) => {                             
                        if(data.answer == this.$t('confirmToAction')){
                            const params = {
                                email_login : this.email_login,
                                phone_login : ''
                            }
                            axios.post('/profile_delete_api', params)
                            .then(response => {
                                if(response.data == "saved"){
                                    this.step = 0
                                    emitter.emit('setToast', { 
                                        active: true,
                                        content: this.$t('success'),
                                        closeButton: false,
                                    })
                                    this.$emit('reload')
                                    this.phone_login = ''
                                }else{
                                    
                                    emitter.emit('setToast', { 
                                        active: true,
                                        content: this.$t('unknownError')
                                    })            
                                }
                            }).catch(error => {
                                if(error.response.status == 422){
                                    emitter.emit('setToast', { 
                                        active: true,
                                        content: this.$t(error.response.data.message)
                                    }) 
                                }
                            })
                        }
                    });
                }else{
                    emitter.emit('setToast', {
                        active: true,
                        type: 'info',
                        content: this.$t('cantChangeLogin'),
                        closeButton: false,
                        answers: [this.$t('confirmToAction')],
                    })
                }
            },
            sendVerify(){
                axios.post('/phone/send-code-again', {
                    editData: this.user_login,
                    phone_prefix: this.country_code,
                    login_token: this.login_token,
                    lang: this.$store.state.local 
                }).then(response => {
                    this.newCode = this.$t(response.data.message)
                    this.verifyError = ''
                }).catch(error=> {
                    if(error.response.status == 429){
                        this.verifyError = this.$t('tooManyAttemptError.otpSendAction')
                        this.newCode = ''
                    }else{
                        this.verifyError = this.$t('sendCodeError')
                        this.newCode = ''
                    }
                })
            },      
            async verifyChangeLogin(){
                const result = await this.$refs.form.validate();
                if(!result.valid) {
                    return
                }
                this.loader = true
                axios.post('/phone/verification', {
                    verification_code: this.verification_code,
                    editData: this.user_login,
                    phone_prefix: this.country_code,
                    login_token: this.login_token
                }).then(response => {
                    if(response.data == 'saved'){
                        this.step = 0
                        this.verification_code = ''
                        emitter.emit('setToast', { 
                            active: true,
                            content: this.$t('success'),
                            closeButton: false
                        })
                        this.$emit('reload')
                        this.loader = false
                    }
                }).catch(error => {
                    this.loader = false
                    if(error.response.status == 422){
                        this.verifyError = this.$t(error.response.data.message)
                        this.newCode = ''
                    }else if(error.response.status == 429){
                        this.verifyError = this.$t('tooManyAttemptError.otpCheckAction')
                        this.newCode = ''
                    }else{
                        this.verifyError = this.$t('verifyError')
                        this.newCode = ''
                    }
                })
            },
            async changeLoginInfo(){
                const result = await this.$refs.form.validate();
                if(!result.valid) {
                    return
                }

                let params = ''
                if(this.email_login != this.user.email){
                    this.mailChange = true
                    params = {
                        email_login : this.email_login,
                        country_code : this.country_code,
                        lang: this.$store.state.local
                    }
                }

                if(this.phone_login != this.user.phone){
                    params = {
                        phone_login : this.phone_login,
                        country_code : this.country_code
                    }
                }
                this.loader = true
                axios.post('/profile_login_edit_api', params)
                    .then(response => {
                        console.log(response)
                        if(response.data){
                            this.user_login = response.data.phoneOrMail
                            this.country_code = response.data.prefix
                            this.login_token = response.data.login
                            this.step = 3
                            this.mailChange = false
                        }
                        this.loader = false
                    })
                    .catch(error => {
                        this.loader = false
                        if(error.response.status == 422){
                            if(this.mailChange){
                                this.emailMessage = this.$t(error.response.data.message)
                                this.mailChange = false
                            }else{
                                this.phoneMessage = this.$t(error.response.data.message)
                            }
                        }else if(error.response.data.message.includes('Max send attempts')){
                            if(this.mailChange){
                                this.emailMessage = this.$t('tooManyAttemptError.mailOrPhoneRegisterCheck')
                                this.mailChange = false
                            }else{
                                this.phoneMessage = this.$t('tooManyAttemptError.mailOrPhoneRegisterCheck')
                            }
                        }else{
                            if(this.mailChange){
                                this.emailMessage = this.$t('sendCodeError')
                                this.mailChange = false
                            }else{
                                this.phoneMessage = this.$t('sendCodeError')
                            }
                        }
                    })
            },
            changeLogin(){
                this.emailOrPhone = !this.emailOrPhone
            },
            generateQr(){
                var uniqueChannell = Math.random().toString(36).substring(5);   
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: this.$t('confirmToGenerateQr') ,
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                    channel: uniqueChannell

                })            
                emitter.on(uniqueChannell, (data) => { data.answer === this.$t('confirmToAction') ? this.generateQrSend(): false});
            },
            generateQrSend(){
                if(this.qrLock) return
                this.qrLock = true
                axios.post('/profile_generate_new_code').then(response => {
                    this.$emit('reload')
                    this.errorToast(this.$t('qrGeneratedSuccessfully'))
                    setTimeout(() => {
                        this.qrLock = false   
                    }, 500); 
                                
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    this.qrLock = false   
                }.bind(this));
            },
            deleteAccount(){
                const uniqueChannell = Math.random().toString(36).substring(5);
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: this.$t('deleteAccountWarning'),
                    closeButton: true, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                    channel: uniqueChannell
                })
                emitter.on(uniqueChannell, (data) => {
                    if(data.answer == this.$t('confirmToAction')){
                        this.deleteAccountSend()
                    } 
                })    
            },
            deleteAccountSend(){
                axios.post('/user_delete_account').then(response => {
                    if(response.status == 200){
                        location.replace(window.location.origin)
                    }                  
                                
                }).catch(function (error) { 
                    console.log(error)               
                    if (error.response) this.errorToast(this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t(error.message))   
                }.bind(this));
            },
            async reset(){
                const result = await this.$refs.form.validate();
                if(!result.valid) {
                    return
                }
                const d = {
                    current: this.currentPassword,
                    password: this.newPassword,
                    password_confirmation: this.newPasswordConfirm
                }
                axios.post('/user_pass_change_api', d).then(response => {
                    this.errorToast(this.$t(response.data.message))   
                    if(response.status == 200){
                        this.step = 0
                        this.currentPassword = this.newPassword = this.newPasswordConfirm =  ''
                    }
                                
                }).catch(function (error) { 
                    console.log(error)               
                    if (error.response) this.errorToast(this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t(error.message))   
                }.bind(this));
            },
            closeModal(){
                if (!this.$refs.editModal01.contains(event.target)) {
                    this.$emit('close')
                }
            },
            setSelectedColor(code){
                if(this.toggleDisabled) return
                this.toggleDisabled = true
                axios.post('/profile_set_color', {value: code}).then(response => { 
                    this.toggleDisabled = false     
                    this.$emit('reload')    
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    this.toggleDisabled = false
                }.bind(this));
            },
            setPrivate(val){
                console.log(val)
                // if(this.toggleDisabled) return
                // this.toggleDisabled = true
                // const value = event.target.checked
                axios.post('/profile_set_privacy', {value: val}).then(response => { 
                    // this.toggleDisabled = false
                    this.step = 0     
                    this.$emit('reload')    
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    // this.toggleDisabled = false
                    this.privateSetting = this.data.is_public ? 1 : 0
                }.bind(this));

            },
            createdDate(timestamp){
                const date = new Date(timestamp);
                const options = { timeZone: 'Japan', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' };
                const formattedDate = date.toLocaleString('ja', options);
                return formattedDate
            }
        }
    }
</script> 
<style lang="scss" scoped>
    .subMenuTitle{
        position: absolute;
        margin-left: 25px;   

    }
    .step2__header{
        margin-top: -28px; 
        margin-left: 20px;
    }
    .select-wrapper{
        width: 100%;
    }
    .login_step2{
        margin-top: 20px;
    }
    .notAllowed{
        cursor: not-allowed !important;
    }
    .deleteMark {
        position: absolute;
        fill: var(--primary-color);
        right: 0;
        cursor: pointer;
        background: var(--formBorder);
        height: 100%;
        padding: 0 15px;
    }
    input:autofill {
        -webkit-text-fill-color: var(--primary-color);
    }
    .pw-reset{
        margin-bottom: 30px;
    }
    .verified-text{
        font-size: 12px;
        color: inherit;
    }
    .form-error{
        position: absolute; 
        bottom: -10px;
        font-size: 11px;
        color: inherit;
        left: 0;
        color:tomato;
    }
    .p-setting-item{        
        margin: 0 -10px !important;
        padding: 15px;
    }
    .p-danger:hover{
        color: #f53b3b !important;
    }
    .page-transition-enter {
        opacity: 0;
        transform: translateX(100%);
    }

    .page-transition-enter-active {
        opacity: 1;
        transform: translateX(0);
        transition: all 0.2s ease;
    }

    /* Leave transition */
    .page-transition-leave {
        opacity: 1;
        position: absolute;
        top: 0;
        width: 100%;
    }

    .page-transition-leave-active {
        opacity: 0;
        transform: translateX(-100%);
        transition: all 0.3s ease-in;
    }
    .c-picker{
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        grid-template-rows: repeat(3, 1fr);
        gap: 10px;
    }
    .c-picker > div {
        min-width: 25px;
        min-height: 25px;
        display: flex;
        align-items: center;
        place-content: center;
    }
    
    .separator {
        display: flex;
        align-items: center;
        padding-top: 10px;
        width: 120px;
        margin: 0 auto 10px;
    }

    .separator-line {
        flex: 1;
        height: 1px;
        margin: 0 auto;
        border-top: 1px solid #ccc;
    }
    .secondary-button{
        background-color: #8888 !important;
    }
    .separator-text {
        font-size: 12px;
    }
    .recordText option{
        background-color: var(--background-color);
    }
    .sendAgain-link{
        color: var(--primary-color);
        font-size: 12px;
        cursor: pointer;
    }
    .country_option{
        width:120px;
        border-right:none;
        padding: 9px;
    }
    input[type="radio"] {
        -webkit-appearance: none;
        appearance: none;
        background-color: #f1f1f1;
        border: 1px solid rgb(0, 0, 0);
        border-radius: 50%;
        min-height: 20px;
        min-width: 20px;
        width: 20px;
        height: 20px;
        outline: none;
        transition: all 0.3s;
        position: relative;
        cursor:pointer;
     }
     
     input[type="radio"]:checked::before {
        content: "";
        background-color: black;
        border-radius: 50%;
        height: 10px;
        position: absolute;
        width: 10px;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        margin: auto;
     }
    @media screen and (max-width: 959px) {
        .subMenuTitle{
            position: absolute;
            margin-left: 35px;
            margin-top: 22px;       

        }
        .login_step2{
            margin-top: 40px;
        }
        .step2__header{
            margin-top: -38px;
            margin-left: 25px;
            margin-right: 25px;
        }
        input[type="radio"]:checked::before{
            top: 0;
        }
    }
</style>
