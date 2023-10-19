<template>
    <div class="main-bar">
        <div class="overlay" v-show="iconEditModal" style="z-index:99;">   
            <div class="chatCreate">
                <div class="recordFormTitle" style="display:flex">
                    <h1 style="font-size: 17px;margin: -10px 0 15px;">{{$t('IconChangeTitle')}}</h1>
                    <div @click="closeIconEditModal" class="m-close-button">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>              
                </div>        
                <div id="cropperContainer" class="" style="display:flex;height: 100%;width: 100%;">                 
                        
                    <div class="filedrop-area" v-if="!cropperIs" style="width:100% !important;height:80% !important;display:flex;margin: auto;">
                        <label for="userIcon" class="file-label cursor-pointer">
                            {{$t('uploadIcon')}}
                        </label>
                        <input  type="file" name="userIcon" id="userIcon" v-on:change="preUpload" style="display: none;">
                    </div>
                    <div v-else style="height: auto;min-height:200px;background:var(--bg3);width: 100%;max-height: 80%;margin: auto;">
                        <img style="display:none;" id="hiddenImageWrap" :src="tempImage">
                    </div>                      
                    
                </div>
                <div style="width:100%; margin-top:auto;display:flex;text-align:center">        
                    <button v-on:click="cropComplete()" class="l-button cursor-pointer" style="position:relative;">
                        <span v-if="!sendLoader">{{$t('save')}}</span>
                        <div v-if="sendLoader" id="loaderMini" style="position: absolute;">
                            <div style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;" class="spinner-mini"></div>
                        </div>
                    </button>   
                </div>
            </div>
        </div>
        <div class="overlay" v-if="iconViewModal">            
            <div class="chatCreate" style="justify-content:center;align-items:center;">                    
                <img style="width:fit-content;height:-webkit-fill-available" v-if="UserAllData.icons.use_of == 'profile'" :src="$store.state.baseLocation + '/content/profile_icon/' + UserAllData.icon_id + '_' + UserAllData.icons.profile_id +  '_x.' + UserAllData.icons.extension">
                <img style="width:fit-content;" v-else :src="$store.state.baseLocation + '/content/profile_icon/' + UserAllData.icon_id + '_' + UserAllData.icons.profile_id +  '_200.' + UserAllData.icons.extension">
                <div @click="iconViewModal = false" class="m-close-button">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div> 
            </div>
            
        </div>
        <div>
            <!-- 20201207 -->
            <div class="profile-icon-content">
                <div id="imageWrap" style="position: relative;width: fit-content;margin: auto;min-height: 120px;">
                    <div @click.stop="iconClickMenu" class="cursor-pointer">
                        <UserIconPreLoad size="120" :title="UserAllData.name" :user="UserAllData" imgClass="profile-image"/>
                    </div>
                    <div id="iconMenuWrap" class="iconChange" v-if="$store.state.menu.name == 'iconMenuWrap' && $store.state.menu.id == 23">
                        <div @click="iconViewModal = true" class="cursor-pointer">フルサイズを表示</div>
                        <div @click="iconEditModal = true" class="cursor-pointer">{{$t('uploadIcon')}}</div>
                        <div v-if="auth_id == targetId" @click="iconDeleteConfirm()" class="cursor-pointer">{{$t('deleteIcon')}}</div>
                        
                    </div>
                </div>
            </div>

            <!-- 20201207 -->
            <div class="bar01">
                <div style="font-size: 20px;margin-bottom: 20px;display:flex;justify-content:center;gap:5px;" v-if="UserAllData.name !== null">
                    <p><span>{{UserAllData.name}}</span></p>
                    <img v-if="weathers" :src="'/images/icon_' + weathers.value_int + '.svg'" alt="Weather Icon" width="20" height="20" />
                </div>
                <div style="display:flex; font-size:12px;justify-content:center;" v-if="userDaysWeather.length">
                    <div v-for="(weather, index) in userDaysWeather" :key="index">
                        <div style="display:flex;align-items:center;margin-right:5px;">
                            <p>{{dateFormat(weather.date)}}</p>
                            <img :src="'/images/icon_' + weather.value_int + '.svg'" alt="Weather Icon" width="16" height="16" />
                        </div>
                    </div>
                </div>                
                <div v-if="UserAllData.name_kana" class="bar02">
                    <p>{{UserAllData.name_kana}}</p>
                </div>
                <div v-if="UserAllData.phone_number" class="bar03">                       
                    <p>{{UserAllData.phone_number}}</p>
                </div>
                <div v-if="UserAllData.work_email" class="bar04">                       
                    <p>{{UserAllData.work_email}}</p>
                </div>
                <div v-if="clapData !== null && clapData.sum !== null" class="bar05">
                    <svg xmlns="http://www.w3.org/2000/svg" width="35" height="27" viewBox="0 0 41 32" fill="var(--primary-color)">
                        <path d="M22.697 25.355c1.893-1.873 3.579-3.949 5.025-6.195l0.087-0.144 0.511-0.818c0.366-0.582 0.709-1.254 0.992-1.958l0.031-0.087 0.204-1.125v-0.818c0-0.204-0.102-0.511-0.307-0.716l-0.613-0.613-0.307-0.102 0.307-0.409 0.511-1.125c0.204-0.511 0.409-1.534 0.102-2.249-0.204-0.613-0.613-1.022-1.125-1.227 0.307-0.409 0.511-1.227 0.511-1.738 0.005-0.056 0.008-0.121 0.008-0.186 0-0.602-0.237-1.149-0.622-1.553l0.001 0.001-0.102-0.102-0.818-0.409h-0.204l-0.102-0.102h-0.102l-1.329 0.102-1.022 0.409-0.92 0.511-0.102 0.102-0.307-0.613v-0.102l-0.102-0.102h-0.102l-0.511-0.511-1.022-0.204-1.534 0.204-1.329 0.511c-0.923 0.449-1.693 0.891-2.431 1.378l0.080-0.049c-1.22 0.828-2.253 1.601-3.25 2.415l0.081-0.064-1.022 0.716-1.329 1.125v-0.307c0.075-0.4 0.118-0.859 0.118-1.329s-0.043-0.929-0.125-1.376l0.007 0.046v-0.102l-0.102-0.102-0.102-0.307c-0.102-0.409-0.409-0.92-0.818-1.227-0.316-0.222-0.7-0.369-1.115-0.408l-0.009-0.001h-0.409c-0.508 0.031-0.966 0.219-1.334 0.515l0.005-0.004c-0.424 0.292-0.768 0.669-1.014 1.108l-0.008 0.017-0.307 0.613c-1.076 1.862-2.508 4.61-3.864 7.402l-0.43 0.981c-0.397 1.084-0.719 2.36-0.908 3.679l-0.012 0.104v0.511l-0.102 0.204v1.738l0.102 0.511 0.102 1.022 0.511 1.84c0.102 0.511 0.613 0.716 1.022 0.613 0.409-0.204 0.716-0.613 0.613-1.125-0.409-1.125-0.613-2.249-0.613-3.374v-1.022l0.102-0.204v-0.511c0.303-1.838 0.874-3.488 1.678-4.994l-0.042 0.086c1.252-2.614 2.41-4.724 3.663-6.769l-0.187 0.328 0.409-0.818 0.409-0.409 0.307-0.102h0.102l0.409 0.409v0.307l0.102 0.307c0.016 0.192 0.025 0.416 0.025 0.642 0 0.941-0.159 1.845-0.452 2.687l0.017-0.057-0.307 1.227c-0.204 0.307-0.307 0.613-0.307 1.022-0.102 0.613-0.204 1.227 0.204 1.431 0.204 0.102 0.511 0 0.716-0.204l2.351-2.249 2.147-1.943 0.92-0.818c0.902-0.751 1.904-1.46 2.96-2.088l0.107-0.059c0.905-0.609 1.945-1.19 3.033-1.683l0.136-0.055 0.613-0.204c0.102 0 0.613 0 0.716 0.204 0.204 0.204 0.102 0.716-0.204 0.92-0.807 0.747-1.673 1.484-2.569 2.182l-0.089 0.067c-1.475 1.349-2.841 2.683-4.163 4.060l-0.028 0.030c-0.307 0.307-0.409 0.818-0.102 1.125s0.92 0.204 1.227-0.102l2.658-2.351c1.796-1.574 3.762-3.118 5.809-4.555l0.223-0.148c0.511-0.409 1.125-0.613 1.431-0.716s0.716-0.102 0.92 0.102c0.102 0.204 0 0.818-0.307 1.125l-1.431 1.534-7.361 7.361c-0.409 0.409-0.409 1.022-0.204 1.227 0.307 0.307 0.818 0.307 1.329-0.102l6.85-6.441 0.818-0.716c0.204-0.204 0.409-0.409 0.716-0.409 0.204 0 0.409 0.102 0.511 0.307l-0.204 0.92-0.409 0.818-0.92 1.227-1.636 1.943-1.636 1.738-2.76 2.965c-0.307 0.409-0.307 0.818-0.102 1.125s0.716 0.307 1.125 0c0.409-0.204 1.84-1.636 2.454-2.249l2.147-2.249 1.227-1.227c0.204-0.307 0.613-0.511 0.818-0.307 0.204 0.102 0.204 0.409 0.102 0.613l-0.204 0.613c-0.222 0.585-0.498 1.092-0.831 1.553l0.013-0.020c-0.948 1.514-1.891 2.815-2.912 4.049l0.050-0.062c-2.033 2.47-4.355 4.598-6.941 6.368l-0.113 0.073-0.409 0.204-0.409 0.307-0.409 0.204-0.409 0.102c-1.032 0.448-2.235 0.804-3.488 1.010l-0.090 0.012c-0.46 0.067-0.992 0.105-1.532 0.105-0.76 0-1.502-0.075-2.22-0.219l0.072 0.012c-0.409-0.102-0.818 0.102-0.92 0.511s0.102 0.92 0.511 1.022c0.951 0.267 2.042 0.42 3.169 0.42s2.219-0.153 3.255-0.44l-0.085 0.020 2.045-0.716 0.511-0.204 0.409-0.204 0.511-0.204 0.511-0.307 1.738-1.125c1.125-0.818 2.147-1.738 3.067-2.76z"></path>
                        <path d="M40.792 21.265l-0.204-0.613-0.409-0.409-0.818-0.511-1.534-0.613-0.204-0.102 0.102-0.409 0.102-0.204v-0.409l-0.204-0.511-0.511-0.716-0.511-0.511c-0.522-0.404-1.136-0.72-1.802-0.911l-0.038-0.009-0.613-0.204-2.147-0.409c-0.511-0.102-0.92 0.102-1.125 0.511-0.102 0.409 0.102 0.92 0.716 1.022 1.492 0.447 2.746 0.933 3.953 1.503l-0.17-0.072c0.307 0.204 0.409 0.613 0.307 0.716-0.396-0.008-0.862-0.013-1.329-0.013s-0.934 0.005-1.399 0.014l0.069-0.001-2.556-0.102c-0.409 0-0.818 0.307-0.818 0.716s0.102 0.818 0.818 0.92l2.76 0.204c1.717 0.11 3.319 0.439 4.832 0.959l-0.129-0.039 0.818 0.511c0.204 0.204 0.204 0.511 0 0.613l-0.818 0.204-1.636 0.102h-8.281c-0.409 0.102-0.716 0.307-0.818 0.716 0 0.511 0.307 0.818 0.716 0.818l4.703 0.204 2.863 0.102 0.818 0.102 0.511 0.204c0.102 0 0.204 0.307 0 0.511-0.102 0.204-0.409 0.307-0.818 0.511l-1.329 0.204c-1.879 0.267-4.049 0.42-6.255 0.42-0.353 0-0.705-0.004-1.056-0.012l0.052 0.001-2.045 0.102c-0.409 0-0.818 0.307-0.818 0.716s0.307 0.818 0.716 0.818l4.192 0.204h1.022l0.92-0.102h1.125l0.92-0.102c0.307 0 0.409 0.204 0.409 0.307 0.102 0.102 0 0.307-0.102 0.409l-0.716 0.307c-0.284 0.115-0.626 0.221-0.978 0.299l-0.044 0.008c-2.569 0.664-5.519 1.045-8.558 1.045-0.442 0-0.882-0.008-1.32-0.024l0.063 0.002h-2.863c-0.613 0-0.818 0.307-0.92 0.716 0 0.409 0.204 0.716 0.716 0.818l3.169 0.204h3.067c2.272-0.048 4.455-0.306 6.566-0.756l-0.228 0.041c0.92-0.204 1.84-0.613 2.454-0.92s1.227-0.818 1.431-1.329 0.102-1.125 0-1.329l1.329-0.409 1.125-0.716 0.511-0.613 0.204-0.92v-0.818h-0.102c0.515-0.144 0.96-0.391 1.333-0.719l-0.004 0.003c0.389-0.307 0.677-0.727 0.814-1.21l0.004-0.017 0.102-0.204v-0.511l-0.102-0.307zM32 4.805l0.409-0.409 0.307-0.511 0.716-0.818 0.613-0.92c0.102-0.307 0.409-0.613 0.409-1.022 0.102-0.204 0-0.511-0.204-0.716-0.307-0.409-0.92-0.511-1.431-0.204-0.307 0.204-0.409 0.613-0.613 0.92-0.224 0.475-0.5 1.151-0.752 1.838l-0.066 0.207-0.204 0.511-0.102 0.511 0.102 0.409c0.204 0.307 0.511 0.307 0.818 0.204zM34.658 6.85l-0.511 0.613v0.511c0.102 0.204 0.409 0.307 0.716 0.204l0.613-0.307 0.613-0.511 2.454-1.84c0.409-0.307 0.818-0.511 1.125-1.022v-0.818c-0.204-0.409-0.716-0.613-1.125-0.409-0.511 0.204-0.818 0.613-1.227 0.92-0.744 0.703-1.444 1.403-2.124 2.122l-0.023 0.025-0.511 0.511zM40.179 8.383l-0.92 0.204-1.022 0.409-0.92 0.307-0.409 0.204-0.511 0.204c-0.105 0.11-0.17 0.26-0.17 0.424 0 0.29 0.202 0.534 0.473 0.597l0.004 0.001h1.022l0.92-0.204 1.022-0.204c0.307 0 0.716 0 0.92-0.307l0.409-0.511c0-0.511-0.307-1.022-0.818-1.125z"></path>
                    </svg>
                    <p style="margin-left: 3px;">{{clapData.sum}}</p>                        
                </div>
            </div>
            <div class="albumBody" style="display: flex; padding: 15px 50px 0 50px; justify-content: center;">
                <div v-if="movExist">
                    <video controls="controls" style="width: 100%;max-height: 290px;background: #000;">
                        <source v-bind:src="$store.state.baseLocation + '/user_files/' + targetId + '/' + movExist.path">
                    </video>                                      
                    <div class="mov-del-button" @click="introMovDeleteConfirm" v-if="movExist && (auth_id == 1  || auth_id == 610 || auth_id == 604 || auth_id == 765)">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="17" viewBox="0 0 27 32" fill="var(--primary-color)">
                        <path d="M18.68 10.952c-0.427-0.035-0.797 0.289-0.832 0.716-0.104 1.271-0.173 2.542-0.243 3.812l-0.104 1.906-0.081 1.906-0.069 1.906c-0.023 0.635-0.035 1.271-0.046 1.906-0.023 1.271-0.046 2.553-0.023 3.824 0.012 0.37 0.289 0.693 0.682 0.728 0.416 0.035 0.774-0.266 0.809-0.67 0.116-1.271 0.196-2.542 0.266-3.812 0.035-0.635 0.081-1.271 0.104-1.906l0.081-1.906 0.069-1.906 0.046-1.906c0.023-1.271 0.058-2.553 0.058-3.824-0.012-0.393-0.312-0.739-0.716-0.774zM9.473 21.21l-0.069-1.918-0.081-1.906c-0.023-0.635-0.069-1.271-0.104-1.906-0.069-1.271-0.15-2.542-0.266-3.812-0.035-0.37-0.347-0.67-0.728-0.67-0.416-0.012-0.751 0.323-0.751 0.739-0.023 1.271 0 2.553 0.023 3.824 0.012 0.635 0.023 1.271 0.046 1.906l0.069 1.906 0.081 1.906 0.104 1.906c0.069 1.271 0.15 2.542 0.243 3.812 0.035 0.393 0.37 0.716 0.774 0.716 0.427 0 0.774-0.347 0.774-0.774 0-1.271-0.023-2.553-0.058-3.824l-0.058-1.906zM14.279 15.515c-0.023-1.271-0.046-2.542-0.092-3.824-0.023-0.404-0.335-0.728-0.739-0.739-0.427-0.012-0.786 0.312-0.809 0.739-0.046 1.271-0.069 2.542-0.092 3.824l-0.023 1.906-0.012 1.906 0.012 1.906c0 0.635 0.023 1.271 0.023 1.906 0.023 1.271 0.058 2.542 0.116 3.824 0.023 0.37 0.323 0.682 0.705 0.705 0.416 0.023 0.762-0.289 0.786-0.705 0.069-1.271 0.104-2.542 0.116-3.824 0.012-0.635 0.023-1.271 0.023-1.906l0.012-1.906-0.023-3.812z"></path>
                        <path d="M26.64 7.601v-0.012c0-0.531-0.439-0.97-0.982-0.959-0.127 0-0.3 0.012-0.451 0.023-0.231 0.012-0.451-0.046-0.647-0.162-0.312-0.196-0.682-0.404-0.855-0.485l-0.693-0.323c-0.231-0.104-0.474-0.196-0.705-0.289-0.947-0.37-1.918-0.682-2.9-0.924-0.416-0.104-0.947-0.208-1.282-0.277-0.116-0.023-0.196-0.139-0.196-0.254 0.035-0.451 0.081-1.178 0.092-1.536 0.023-0.439 0.023-0.866 0.046-1.305 0.012-0.554-0.416-1.017-0.97-1.028h-0.058l-1.814-0.046c-0.601-0.023-1.213-0.023-1.814-0.023l-1.814 0.012c-0.601 0.012-1.213 0.023-1.814 0.046h-0.081c-0.543 0.023-0.97 0.485-0.947 1.028l0.023 0.647c0.012 0.22 0.012 0.439 0.023 0.647 0.023 0.358 0.058 1.028 0.081 1.479 0 0.139-0.092 0.254-0.231 0.277-0.335 0.058-0.832 0.162-1.259 0.266-0.994 0.231-1.964 0.531-2.911 0.901-0.751 0.289-1.49 0.612-2.207 1.005-0.196 0.116-0.416 0.162-0.635 0.162h-0.485c-0.624 0-1.132 0.497-1.132 1.121v0.012l-0.023 3.5c0 0.635 0.508 1.155 1.144 1.155h0.751l1.074 18.622v0.023c0.046 0.635 0.578 1.144 1.225 1.132l18.449-0.116c0.578 0 1.063-0.462 1.097-1.051l1.040-18.784h0.901c0.543 0 0.994-0.439 0.982-0.994l-0.023-3.489zM10.755 2.38c0-0.081 0.012-0.162 0.012-0.254 0.277 0.012 0.555 0.012 0.832 0.023l1.814 0.012c0.601 0 1.213 0 1.814-0.012l0.82-0.023c0 0.081 0 0.162 0.012 0.254 0.012 0.393 0.035 0.994 0.035 1.352 0 0.058-0.046 0.104-0.104 0.104-0.543-0.046-1.721-0.116-2.576-0.116-0.832 0-2.091 0.081-2.53 0.116-0.069 0.012-0.116-0.046-0.127-0.104-0.012-0.335-0.023-0.97 0-1.352zM22.816 11.033v0.012l-1.201 18.126c-0.023 0.3-0.266 0.52-0.555 0.52l-15.203 0.023c-0.266 0-0.474-0.208-0.497-0.462l-1.19-18.218v-0.012c-0.035-0.612-0.543-1.086-1.167-1.086h-0.866c-0.116 0-0.208-0.092-0.208-0.208v-0.797c0-0.116 0.081-0.208 0.196-0.208 0.254-0.023 0.612-0.069 0.751-0.15h0.012c0.751-0.474 1.571-0.89 2.414-1.248s1.721-0.647 2.622-0.878c1.791-0.474 3.651-0.705 5.51-0.716 1.86 0 3.72 0.22 5.522 0.67 0.901 0.231 1.791 0.508 2.634 0.855 0.22 0.081 0.427 0.173 0.635 0.266l0.312 0.139 0.312 0.15c0.208 0.092 0.404 0.208 0.601 0.312 0 0 0.254 0.15 0.393 0.22 0.104 0.046 0.22 0.116 0.312 0.162 0.058 0.035 0.127 0.046 0.196 0.046h0.312c0.104 0 0.173 0.081 0.173 0.185l-0.012 1.19c0 0.104-0.081 0.185-0.185 0.185h-0.797c-0.543-0.012-0.982 0.393-1.028 0.924z"></path>
                    </svg>
                    <p style="margin: auto 0 auto 3px;">削除</p>
                    </div>
                </div>
                <div style="width:100%; height:100%; background: var(--kebab-bg1);color: grey;text-align:center;display:flex;height:inherit;position:relative;" v-else>
                    <div class="uploadMask" v-if="uploadingProgress"><div>アップロード中</div><div> {{uploadingProgress }}%</div></div>                   
                    <div style="margin:auto;" v-if="(auth_id == 1  || auth_id == 610 || auth_id == 604 || auth_id == 765)">
                        <label for="file" class="file-label" style="cursor:pointer">
                            自己紹介Movアップロード
                        </label>
                        <input type="file" name="file" id="file" v-on:change="introMovUpload" style="display: none;">
                    </div>                         
                    <div v-else style="margin:auto;">現在準備中</div>
                    
                </div>
                
            </div>
            
        </div>
    </div>
</template>
<script>
    import Cropper from 'cropperjs';
    import UserIconPreLoad from '../../Board/Mixed/UserIcon.vue'
    import 'cropperjs/dist/cropper.css';
    import moment from 'moment';

    export default{
        props: ['UserAllData', 'deviceWidth', 'isAccessible', 'clapData'],
        data(){
            return {
                iconViewModal: false,
                iconEditModal: false,
                cropperIs: false,
                isEnter: false,
                sendLoader: false,
                tempImage: null,
                uniqueImage: null,
                cropperInstance: null,
                tempLock: false,
                inviteLock: false,
                showIntroduction: false,
                auth_id: this.$store.state.user.id,
                targetId: this.UserAllData.id,
                uploadingProgress: 0,
                orgImage: null,
                weathers: this.UserAllData.weathers
            }
        },
        components: {
            UserIconPreLoad,
        },
        computed: {
            // deviceWidthStyle(){
            //     if(this.deviceWidth < 500){
            //         return { width: '50%'}
            //     }
            //     return null
            // },
            userDaysWeather(){
                const weathers = this.UserAllData.days_weathers
                return weathers.sort((a, b) => new Date(a.date) - new Date(b.date));
            },
            movExist(){
                if(this.UserAllData && this.UserAllData.user_album && this.UserAllData.user_album.length){
                    for(let mov of this.UserAllData.user_album){
                        if(mov.intro_flag == 1){
                            return mov
                        }
                    }
                }
            }
        },  
        methods: {
            dateFormat(day){
                const date = moment(day)
                return date.locale('ja').format('D ddd')
            },
            introMovDeleteConfirm(){
                var uniqueChannell = Math.random().toString(36).substring(5);  
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: '自己紹介Movを削除しますか。' ,
                    closeButton: false, 
                    autoClose: false,
                    answers: ['はい', 'いいえ'],
                    channel: uniqueChannell

                })            
                emitter.on(uniqueChannell, (data) => { 
                    if(data.answer === 'はい'){
                        this.introMovDelete()
                    }
                });
            },
            introMovDelete(){
                axios.post('/mov_delete', {delete_id: this.targetId}).then(response => {
                        if(response.data == 'saved'){
                            this.$emit('getUserInfo');
                        }               

                    })
            },
            introMovUpload(event){
                const fileInfo = event.target.files[0];    
                const formData = new FormData()
                formData.append('file', fileInfo)
                formData.append('user_id', this.targetId)            
                axios.post('/mov_up', formData, { onUploadProgress: (e) => this.uploadingProgress = Math.floor((e.loaded * 100) / e.total) }).then(response => {
                    this.uploadingProgress = 0;
                    if(response.data == "fileExist") {
                        emitter.emit('setToast', {
                            active: true,  
                            type: 'info', 
                            content: 'エラーが発生しました' ,
                            closeButton: true, 
                            autoClose: true,
                            answers: ['OK'],
                        }) 
                    }else if(response.data == "typeError") {
                        emitter.emit('setToast', {
                            active: true,  
                            type: 'info', 
                            content: 'ファイルタイプは許可されていません' ,
                            closeButton: true, 
                            autoClose: true,
                            answers: ['OK'],
                        }) 
                    }else{
                        this.$emit('getUserInfo');
                    }
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                }.bind(this)) 

            },
            iconClickMenu(){
                if(this.UserAllData.id == this.$store.state.user.id){
                    this.$store.commit('setMenu', {name: 'iconMenuWrap', id: 23})
                }
            },
            cropComplete(){
                if(!this.cropperInstance || this.sendLoader){
                    return;
                }
                let data = JSON.stringify(this.orgImage)
                this.sendLoader = true;
                this.cropperInstance.getCroppedCanvas({
                    }).toBlob((blob) => {
                    const formData = new FormData();
                    // Pass the image file name as the third parameter if necessary.
                    formData.append('croppedImage', blob/*, 'example.png' */);
                    formData.append('orgImage', data)            
                    // Use `jQuery.ajax` method for example
                    axios.post('/user_icon_cropped_up_api',formData)
                        .then(response => {
                            
                            this.$emit('getUserInfo');
                            this.iconEditModal = false;
                            this.cropCancel();
                            this.sendLoader = false;
                            this.$emit('updateUser')
                            
                        }).catch(function (error) {
                            if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                            else if (error.request) this.errorToast(this.$t('commonError'))
                            else this.errorToast(this.$t('commonError') + error.message)   
                            this.sendLoader = false;                       
                        }.bind(this)) 
                }/*, 'image/png' */);
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
            getFileExtension(fileName) {
                const lastDotIndex = fileName.lastIndexOf('.');
                if (lastDotIndex === -1) {
                    return '';
                }
                return fileName.substring(lastDotIndex + 1).toLowerCase();
            },
            preUpload() {
                this.cropperIs = true;
                this.tempImage = URL.createObjectURL(event.target.files[0]);
                const file = event.target.files[0]
                if(file){
                    const fileExtension = this.getFileExtension(file.name);
                    const reader = new FileReader();
                    
                    reader.onload = () => {
                        const image = {
                            name: file.name,
                            url: reader.result,
                            mime_type: file.type,
                            extension: fileExtension,
                            size: file.size
                        };
                        this.orgImage = image;
                        console.log(this.orgImage)
                    }
                    reader.readAsDataURL(file);
                    
                }
                

                setTimeout(() => {
                    var image = document.getElementById('hiddenImageWrap');
                    var width = 300;
                    var height = 300;
                    var container = document.getElementById('cropperContainer');            
                    if(container){
                        width = container.offsetWidth * 0.8;
                        height = container.offsetHeight * 0.8;
                    }            
                    if(this.cropperInstance){
                        this.cropperInstance.destroy();
                        this.cropperInstance = null;
                    }            
                    this.cropperInstance = new Cropper(image, {              
                        dragMode: 'move',
                        preview: '.preview',
                        aspectRatio: 1 / 1,
                        minContainerWidth: width,
                        maxContainerWidth: width,
                        minContainerHeight: height,
                        maxContainerHeight: height,
                        viewMode: 1,
                        responsive:true,
                        autoCrop: true,
                        background: false,
                        guides: false,
                        crop(event) { 
                        },            
                    });
                },0)
            },            
            closeIconEditModal(){
                this.iconEditModal = false;
                this.cropCancel();
            },
            dragEnter: function(){    
                this.isEnter = true;           
            },
            dragLeave: function(){            
                this.isEnter = false;
            }, 
            cropCancel(){
                this.cropperIs = false;
                if(this.cropperInstance){
                    this.cropperInstance.destroy();
                    this.cropperInstance = null;
                }
                            
            },
            cropImage(which){
                var image = document.getElementById(this.uniqueImage);
                var width = 300;
                var height = 300;
                var container = document.getElementById('cropperContainer');            
                if(container){
                    width = container.offsetWidth * 0.8;
                    height = container.offsetHeight * 0.8;
                }            
                if(this.cropperInstance){
                    this.cropperInstance.destroy();
                    this.cropperInstance = null;
                }            
                this.cropperInstance = new Cropper(image, {              
                    dragMode: 'move',
                    preview: '.preview',
                    aspectRatio: 1 / 1,
                    minContainerWidth: width,
                    maxContainerWidth: width,
                    minContainerHeight: height,
                    maxContainerHeight: height,
                    viewMode: 1,
                    responsive:true,
                    autoCrop: true,
                    background: false,
                    guides: false,
                    crop(event) { 
                    },            
                });
                this.tempLock = true;
                this.loaderOff();           
            },
            loaderOff(){
                var sp = document.getElementsByClassName('cropper-view-box');
                
                if(!sp.length){
                    setTimeout(function() {
                        this.loaderOff();                       
                    }.bind(this), 300);
                }else{
                    this.uploadSpinner = false;
                }
            },
            iconDeleteConfirm(id){
                emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('confirmToDeleteIcon') ,
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                        channel: 'userIconDelete'

                    })            
                    emitter.on('userIconDelete', (data) => { 
                        if(data.answer == this.$t('confirmToAction')){
                            this.defaultIconCreate(id)
                        }
                    });        
            }, 
            defaultIconCreate(callback){           
                axios.post('/user_icon_create_api',{create: 1})
                    .then(response => {                       
                        
                        this.$emit('updateUser');
                                     
                    }).catch(function (error) {
                        if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                        else if (error.request) this.errorToast('エラーが発生しました。')
                        else this.errorToast('エラーが発生しました。 ' + error.message)                                                 
                    }.bind(this));; 
            },
        },
        
        mounted(){
            
        }
    }
</script>