<template>
    <div class="main-bar">
        <div class="overlay" v-show="iconEditModal" style="z-index:99;">   
            <div class="chatCreate">
                <div class="recordFormTitle" style="display:flex">
                    <p>{{$t('IconChangeTitle')}}</p>
                    <div @click="closeIconEditModal" class="cursor-pointer" style="position:unset; margin-left:auto;">
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
            <div class="chatCreate" style="justify-content:center;align-items:center;padding-top: 30px;">                    
                <img style="width:fit-content;height:-webkit-fill-available" v-if="UserAllData.icons.use_of == 'profile'" :src="$store.state.baseLocation + '/content/profile_icon/' + UserAllData.icon_id + '_' + UserAllData.icons.profile_id +  '_x.' + UserAllData.icons.extension">
                <img style="width:fit-content;" v-else :src="$store.state.baseLocation + '/content/profile_icon/' + UserAllData.icon_id + '_' + UserAllData.icons.profile_id +  '_200.' + UserAllData.icons.extension">
                <div @click="iconViewModal = false" class="m-close-button">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div> 
            </div>
            
        </div>
        <div style="overflow: hidden;">
            <div class="profile-icon-content">
                <div id="imageWrap" style="position: relative;width: fit-content;margin: auto;min-height: 120px;">
                    <div @click.stop="iconClickMenu" class="cursor-pointer">
                        <UserIconPreLoad size="120" :title="UserAllData.name" :user="UserAllData" imgClass="profile-image"/>
                    </div>
                    <div id="iconMenuWrap" class="iconChange" v-if="$store.state.menu.name == 'iconMenuWrap' && $store.state.menu.id == 23">
                        <div @click="previewProfile(icon, 0)" class="cursor-pointer">フルサイズを表示</div>
                        <div @click="iconEditModal = true" class="cursor-pointer">{{$t('uploadIcon')}}</div>
                        <div v-if="auth_id == targetId" @click="iconDeleteConfirm()" class="cursor-pointer">{{$t('deleteIcon')}}</div>
                        
                    </div>
                </div>
            </div>

            <div class="bar01">
                <div style="font-size: 20px;margin-bottom: 20px;display:flex;justify-content:center;gap:5px;position:relative" v-if="UserAllData.name !== null">
                    <p><span>{{UserAllData.name}}</span></p>
                    <img @click.stop="updateWeather" style="cursor: pointer;" v-if="weathers" :src="'/images/icon_' + weathers.value_int + '.svg'" alt="Weather Icon" width="20" height="20" />
                    <Transition name="downShiftPop">
                        <WeatherUpdater 
                            v-if="$store.state.menu.name == 'weatherUpdater' && $store.state.menu.id == $store.state.user.id" 
                            @reload="$emit('updateUser')"/>
                    </Transition>
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
            <div class="albumBody">
                <div v-if="movExist && movExist.length">
                    <div class="swiper-icon" style="display:flex;align-items:center;justify-content:center;border:none;overflow: hidden;flex-direction: column;">
                        <div style="max-width: 280px;overflow: hidden auto; padding: 0 10px;">
                            <div class="swiper-wrapper vertical-wrapper">
                                <div class="swiper-slide" style="background: none;flex-direction: column;margin-bottom: 30px;" v-for="(mov, index) in movExist" :key="index">
                                    <div style="width: 100%;">
                                        <div class="gn-img-container cursor-pointer" style="background-color: var(--bg3); max-height: 160px;" @click="previewImage(mov, index)">
                                            <img class="gn-image" v-if="mov.mime_type == 'image'" :src="$store.state.baseLocation + '/user_album/' + targetId + '/' + mov.id + '_' + targetId + '_' + mov.path + '.' + mov.extension"/>
                                            <video class="gn-image" v-else-if="isMov(mov.mime_type)" controls="controls" style="pointer-events: none;max-height: 290px;">
                                                <source v-bind:src="movSrc(mov)">
                                            </video>
                                        </div>
                                        <p class="gn-title">{{ mov.title }}</p>
                                        <div v-if="mov.tags.length" style="display: flex;gap: 5px 10px;flex-wrap: wrap;margin-top:10px;">
                                            <p @click="viewalbumByTag(tag)" class="jump-link" v-for="tag in mov.tags" :tag="tag" style="font-size: 14px;" :key="tag.id">#{{ sanitized(tag) }}</p>
                                        </div>
                                    </div>
                                    <div v-if="targetId == auth_id" @click.stop="$store.commit('setMenu', { id: mov.id, name: 'userAlbumMenu'}), this.fileMenuLayer = 0" id="userMenuButton" style="position: absolute;right: 2px;top: 6px;background-color: var(--bg3);" class="boardMenuContainer cursor-pointer">                
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32" style="margin:auto;min-width: 3px;">
                                            <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                            <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                            <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                        </svg>
                                    </div>
                                    <div @click.stop id="userAlbumMenu" class="boxMenuComment cursor-pointer" v-if="$store.state.menu.name == 'userAlbumMenu' && $store.state.menu.id == mov.id" :style="{lineHeight:'normal',top:'20px',bottom:'auto', right: '25px', zIndex: 4}">
                                        <div style="position: relative;">
                                            <div style="right:0;overflow:hidden;box-shadow: rgb(60 64 67 / 30%) 0px 1px 2px 0px, rgb(60 64 67 / 15%) 0px 2px">
                                                <ul> 
                                                    <li @click="editAlbum(mov)" class="boxMenuItems cursor-pointer">編集する</li>
                                                    <li @click="introMovDeleteConfirm(mov.id)" class="boxMenuItems cursor-pointer">削除する</li> 
                                                </ul>
                                            </div>
                                        </div>                                                    
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        <div style="max-width: 280px;width: 100%;">
                            <div v-if="targetId == auth_id" title="作成" class="mov-del-button" style="margin-left: auto;margin-top:10px" @click="$emit('addIntroFile')">
                                <!-- <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="17" viewBox="0 0 32 32" style="fill:var(--primary-color);margin:auto;">
                                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                                </svg> -->
                                追加
                            </div>
                        </div>
                        
                        
                    </div>
                </div>
                
                <div style="width:100%; height:100%; background: var(--bg3);color: grey;text-align:center;display:flex;height:inherit;position:relative;min-height: 220px;max-width: 350px;margin:auto" v-else-if="targetId == auth_id">
                    <div @click="$emit('addIntroFile')" style="margin:auto;">
                        <p class="file-label" style="cursor:pointer">
                            自己紹介ファイルアップロード
                        </p>
                    </div>                         
                </div>
                
            </div>
            <Transition name="modalFade">
                <UserIntroFile 
                    v-if="introUpload"
                    :UserAllData="UserAllData"
                    :editData="editData"
                    @closeModal="closeModal()"
                    @updateUser="$emit('updateUser')"
                />
            </Transition>
            <Transition name="modalFade">
                <UserAlbumByTags 
                    v-if="viewAlbum"
                    :tagText="tagText"
                    :tagAlbums="tagAlbums"
                    :targetId=targetId
                    @closeModal="viewAlbum = false"
                />
            </Transition>   
        </div>
    </div>
</template>
<script>
    import Cropper from 'cropperjs';
    import UserIconPreLoad from '../../Board/Mixed/UserIcon.vue'
    import 'cropperjs/dist/cropper.css';
    import moment from 'moment';
    import UserIntroFile from './UserIntroFile.vue';
    import Swiper from 'swiper'
    import PostTag from '../../Post/PostTag.vue';
    import 'swiper/css'
    import { Navigation } from 'swiper';
    import 'swiper/css/navigation'
    import UserAlbumByTags from '../UserAlbumByTags.vue';
    import WeatherUpdater from '../../Global/WeatherUpdater.vue';
    export default{
        props: ['UserAllData', 'deviceWidth', 'isAccessible', 'clapData', 'movExist', 'introUpload'],
        emits: ['updateUser', 'closeModal', 'addIntroFile', 'editIntro'],
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
                iconSwiper: null,
                viewAlbum: false,
                tagText: '',
                tagAlbums: '',
                editData: null,
            }
        },
        components: {
            UserIconPreLoad,
            UserIntroFile,
            PostTag,
            UserAlbumByTags,
            WeatherUpdater
        },
        computed: {
            icon(){
                return this.UserAllData.icons
            },
            weathers(){
                return this.UserAllData.weathers
            },
            userDaysWeather(){
                const weathers = this.UserAllData.days_weathers
                return weathers.sort((a, b) => new Date(a.date) - new Date(b.date));
            },
            
        },  
        methods: {
            updateWeather(){
                if(this.UserAllData.id == this.$store.state.user.id){
                    this.$store.commit('setMenu', { name: 'weatherUpdater', id: this.$store.state.user.id})
                }
            },
            closeModal(){
                this.$emit('closeModal')
                this.editData = null
            },
            viewalbumByTag(tag){
                this.tagText = tag.text
                axios.post('/get_albums', { tag_id: tag.id }).then(response => {
                    this.tagAlbums = response.data
                    this.viewAlbum = true
                })
            },  
            sanitized(tag){
                const sanitizedString = tag.text ? tag.text.replace(/#|♯|＃/g, '') : '';
                return sanitizedString;
            },
            movSrc(mov){
                return mov.path.includes('intro') ? this.$store.state.baseLocation + '/user_album/' + this.targetId + '/' + mov.path : this.$store.state.baseLocation + '/user_album/' + this.targetId + '/' + mov.id + '_' + this.targetId + '_' + mov.path + '.' + mov.extension
            },
            previewProfile(file, index){
                let target_data = file
                if(target_data.use_of == 'profile'){
                    target_data['file_path'] = this.$store.state.baseLocation + '/content/profile_icon/' + target_data.id + '_' + target_data.profile_id +  '_x.' + target_data.extension
                }else{
                    target_data['file_path'] = this.$store.state.baseLocation + '/content/profile_icon/' + target_data.id + '_' + target_data.profile_id +  '_200.' + target_data.extension
                }
                
                const data = {
                    active: true,
                    files: [target_data],
                    target: target_data,
                    source: 'message',
                    index: 0,
                    message: null,
                    reminder: 'board',
                }
                this.$store.commit('setFilePreview', data)
            },
            previewImage(file, index){
                const files = this.movExist.map(fileData => ({
                    ...fileData,
                    file_path: fileData.path.includes('intro') ? `${this.$store.state.baseLocation}/user_album/${this.targetId}/${fileData.path}` : `${this.$store.state.baseLocation}/user_album/${fileData.user_id}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
                }));   
                const data = {
                    active: true,
                    files,
                    target: file,
                    source: 'user',
                    source_board_id: null,
                    index: index,
                    message: null,
                }
                this.$store.commit('setFilePreview', data)
            },
            editAlbum(data){
                this.editData = data
                this.$emit('editIntro')
            },
            // swiperCreate(){
            //     new Swiper('.swiper-icon', {
            //         direction: 'vertical',
            //         slidesPerView: 3,
            //     })
            // },
            isMov(type){
                return type.includes('video') 
            },
            
            dateFormat(day){
                const date = moment(day)
                return date.locale('ja').format('D ddd')
            },
            introMovDeleteConfirm(id){
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
                        this.introMovDelete(id)
                    }
                });
            },
            introMovDelete(id){
                axios.post('/mov_delete', {delete_id: this.targetId, mov_id: id}).then(response => {
                        if(response.data == 'saved'){
                            this.$emit('updateUser');
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
                        this.$emit('updateUser');
                    }
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                }.bind(this)) 

            },
            iconClickMenu(){
                this.$store.commit('setMenu', {name: 'iconMenuWrap', id: 23})
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
            // this.swiperCreate()
        }
    }
</script>