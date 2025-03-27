<template>
    <div class="main-bar">
        <Modal v-if="iconEditModal" @close="closeIconEditModal">
            <template #title>
                <p>アイコンを編集する</p>
            </template>
            <template #content>                         
                <div>
                    <div style="display:flex;gap:15px;font-size: 14px;">
                        <div :class="['ch-selector', {chSelected : iconType == 0}]" @click="iconType = 0" style="font-size: 14px;">デフォルトアイコン</div>
                        <div :class="['ch-selector', {chSelected : iconType == 1}]" @click="iconType = 1" style="font-size: 14px;">画像アイコン</div>                
                    </div>               
                </div>
                <div  class="si-box" style="padding: 10px;position:relative;border: solid thin var(--primary-color);">
                    <div v-if="iconType == 0">
                        <span class="form-plc smallPlc">アイコンカラー</span>                  
                        <div class="flex justify-center">
                            <div class="si-box">
                                <ColorPicker v-model="iconBg"/>
                            </div>
                                                            
                        </div>
                    </div>
                    <div v-else>
                        <Cropper ref="cropperInstance"/>                       
                    </div>
                </div>
                <div class="si-box">
                    <div v-if="iconType == 0" style="width: fit-content;padding: 15px;margin: auto;">
                        <div id="boardIconPreview" class="iconPreview">
                            <img draggable="false" loading="lazy" class="iconPreviewInner" :src="defaultIcon">
                        </div>
                    </div>
                </div>    
                <div class="si-box">
                    <LoaderButton @triggered="sendIcon" :loading="sendLoader" content="保存する"/>
                </div>                    
            </template> 
        </Modal>   
        <div style="overflow: hidden;">
            <div class="profile-icon-content">
                <div id="imageWrap" style="position: relative;width: fit-content;margin: auto;min-height: 120px;">
                    <div @click.stop="iconClickMenu" class="cursor-pointer">
                        <UserPanel disable-instant :user="UserAllData" imgClass="profile-image" size="120"/>
                    </div>
                    <div id="iconMenuWrap" class="iconChange" v-if="menu.name == 'iconMenuWrap' && menu.id == 23">
                        <div @click="previewProfile(icon, 0)" class="cursor-pointer">フルサイズを表示</div>
                        <div v-if="auth_id == targetId" @click="iconEditModal = true" class="cursor-pointer">アイコン変更</div>
                        <div v-if="auth_id == targetId" @click="iconDeleteConfirm" class="cursor-pointer">削除</div>
                        
                    </div>
                </div>
            </div>

            <div class="bar01">
                <div style="font-size: 20px;margin-bottom: 20px;display:flex;justify-content:center;gap:5px;position:relative" v-if="UserAllData.name !== null">
                    <p><span>{{UserAllData.name}}</span></p>
                    <WeatherIcon v-if="weathers" :key="`weather_${weathers.value_int}`" @click.stop="updateWeather" :which="weathers.value_int" size="20"/>
                    <Transition name="downShiftPop">
                        <WeatherUpdater 
                            v-if="menu.name == 'weatherUpdater' && menu.id == auth.id" 
                            @reload="emit('updateUser')"/>
                    </Transition>
                </div>
                <div style="display:flex; font-size:12px;justify-content:center;align-items: center;" v-if="userDaysWeather.length">
                    <div v-for="(weather, index) in userDaysWeather" :key="index">
                        <div style="display:flex;align-items:center;margin-right:5px;">
                            <p>{{dateFormat(weather.date)}}</p>
                            <!-- <img :src="'/images/icon_' + weather.value_int + '.svg'" alt="Weather Icon" width="16" height="16" />                             -->
                            <WeatherIcon :key="`weather_${weather.value_int}`" :which="weather.value_int" size="16"/>
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
                                        <div class="gn-img-container cursor-pointer" style="background-color: var(--bg3); max-height: 160px; min-width: 200px;" @click="previewImage(mov, index)">
                                            <img ref="imageRef" @error="handleImgError(index)" class="gn-image" v-if="mov.mime_type == 'image' && !imageError.includes(index)" :src="`/cdn/user_album/${targetId}/${mov.id}_${targetId}_${mov.path}.${mov.extension}`"/>
                                            <video class="gn-image" preload="metadata" v-else-if="isMov(mov.mime_type)" controls="controls" style="pointer-events: none;max-height: 290px;">
                                                <source v-bind:src="movSrc(mov)">
                                            </video>
                                            <p class="i-error" ref="errorRef" v-if="imageError.includes(index)" style="top: 50%">ファイル読み込みに失敗しました。</p>
                                        </div>
                                        <p class="gn-title">{{ mov.title }}</p>
                                        <div v-if="mov.tags.length" style="display: flex;gap: 5px 10px;flex-wrap: wrap;margin-top:10px;">
                                            <p @click="viewalbumByTag(tag)" class="jump-link" v-for="tag in mov.tags" :tag="tag" style="font-size: 14px;" :key="tag.id">#{{ sanitized(tag) }}</p>
                                        </div>
                                    </div>
                                    <div v-if="targetId == auth_id" style="position: absolute;right: 2px;top: 6px;">                
                                        <ItemMenu :items="[
                                            {title: '編集する', action:() => editAlbum(mov)},
                                            {title: '削除する', action:() => introMovDeleteConfirm(mov.id)}
                                        ]"/>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        <div style="max-width: 280px;width: 100%;">
                            <div v-if="targetId == auth_id" title="作成" class="mov-del-button" style="margin-left: auto;margin-top:10px" @click="introUpload = true">
                                追加
                            </div>
                        </div>                       
                        
                    </div>
                </div>
                
                <div style="width:100%; height:100%; background: var(--bg3);color: grey;text-align:center;display:flex;height:inherit;position:relative;min-height: 220px;max-width: 350px;margin:auto" v-else-if="targetId == auth_id">
                    <div @click="introUpload = true" style="margin:auto;">
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
                        @updateUser="emit('updateUser')"
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
<script setup>
import moment from 'moment';
import UserIntroFile from './UserIntroFile.vue';
import UserAlbumByTags from '../UserAlbumByTags.vue';
import WeatherUpdater from '../../Global/WeatherUpdater.vue';
import WeatherIcon from '@/components/Global/WeatherIcon.vue';
import { computed, inject, onMounted, ref, useTemplateRef } from 'vue';
import { useFilePreview } from '@/store/filePreview';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import ItemMenu from '@/components/Global/ItemMenu.vue'
import ColorPicker from '@/components/Global/ColorPicker.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import Modal from '@/components/Global/Modal.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Cropper from '@/components/Global/Cropper.vue';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const { confirm, notify } = inject('dialog')
    const props = defineProps(['UserAllData', 'clapData', 'movExist'])
    const emit = defineEmits(['updateUser'])
    const iconEditModal = ref(false)
    const cropperIs = ref(false)
    const sendLoader = ref(false)
    const cropperInstance = useTemplateRef('cropperInstance')
    const auth_id = auth.id
    const targetId = props.UserAllData.id
    const viewAlbum = ref(false)
    const tagText = ref('')
    const tagAlbums = ref('')
    const editData = ref(null)
    const filePreview = useFilePreview()
    const introUpload = ref(false)
    const imageError = ref([])
    const iconType = ref(props.UserAllData?.icon_type ?? 0)
    const iconBg = ref(props.UserAllData?.icon_bg ?? '#000')
    const handleImgError = (index) => {
        imageError.value.push(index)
    }
    const defaultIcon = computed(() => {
        const color = encodeURIComponent(iconBg.value);
        const noSpace = props.UserAllData.name?.charAt(0).toUpperCase();   
        const basePath = '/user_default_thumbnail'
        return `${basePath}/${noSpace}/45/${color}`; 
    })
    const icon = computed(() => {
        return props.UserAllData.icons
    })
    const weathers = computed(() => {
        return props.UserAllData.weathers
    })
    const userDaysWeather = computed(() => {
        const weathers = props.UserAllData.days_weathers
        return weathers.sort((a, b) => new Date(a.date) - new Date(b.date));
    })
    const updateWeather = () => {
        if(props.UserAllData.id == auth.id){
            menu.setMenu( { name: 'weatherUpdater', id: auth.id})
        }
    }
    const closeModal = () => {
        introUpload.value = false
        editData.value = null
    }
    const viewalbumByTag = async(tag) => {
        tagText.value = tag.text
        try{
            const response = await axios.post('/get_albums', { tag_id: tag.id })
            tagAlbums.value = response.data
            viewAlbum.value = true
        } catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }  
    const sanitized = (tag) => {
        const sanitizedString = tag.text ? tag.text.replace(/#|♯|＃/g, '') : '';
        return sanitizedString;
    }
    const movSrc = (mov) => {
        return mov.path.includes('intro') ? '/cdn/user_album/' + targetId + '/' + mov.path : '/cdn/user_album/' + targetId + '/' + mov.id + '_' + targetId + '_' + mov.path + '.' + mov.extension
    }
    const previewProfile = () => {
        let target_data = {
            mime_type: 'image',
            extenstion: 'webp',
            id: props.UserAllData.icon_path
        }
        const color = props.UserAllData.icon_bg || '000000'
        const path = props.UserAllData.icon_path ? `/user_icon_thumbnail/${props.UserAllData.icon_path}/original`  : `/user_default_thumbnail/${props.UserAllData.name?.charAt(0)}/200/${color}`
        target_data['file_path'] = path       
        
        const data = {
            active: true,
            files: [target_data],
            source: 'user',
            index: 0,
            message: null,
        }
        filePreview.setFilePreview(data)
    }
    const previewImage = (file, index) => {
        const files = props.movExist.map(fileData => ({
            ...fileData,
            file_path: fileData.path.includes('intro') ? `/cdn/user_album/${targetId}/${fileData.path}` : `/cdn/user_album/${fileData.user_id}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
            thumbnail_path: `/cdn/user_album/${fileData.user_id}/${fileData.id}_${fileData.user_id}_${fileData.path}_thumbnail.webp`
        }));   
        const data = {
            active: true,
            files,
            source: 'user',
            source_board_id: null,
            index: index,
            message: null,
        }
        filePreview.setFilePreview(data)
    }
    const editAlbum = (data) => {
        editData.value = data
        introUpload.value = true
    }
    const isMov = (type) => {
        return type.includes('video') 
    }
    const dateFormat = (day) => {
        const date = moment(day)
        return date.locale('ja').format('D ddd')
    }
    const introMovDeleteConfirm = async(id) => {
        const answer = await confirm('自己紹介Movを削除しますか。')
        if(!answer.value) return 
        introMovDelete(id)
    }
    const introMovDelete = async(id) => {
        try{
            const response = await axios.post('/mov_delete', {delete_id: targetId, mov_id: id})
            if(response.data == 'saved'){
                emit('updateUser');
            } 
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    const iconClickMenu = () => {
        menu.setMenu( {name: 'iconMenuWrap', id: 23})
    }
    const sendIcon = async() => {
        if(iconType.value == 0) {
            defaultIconCreate()
        }else if(iconType.value == 1 && cropperInstance.value){
            customIconCreate()
        }else{
            notify('エラーが発生しました。')
        }

    }
    const customIconCreate = async() => {

        sendLoader.value = true;
        const { blob, source } = await cropperInstance.value.complete();
        if(blob && source){
            const formData = new FormData();
            formData.append('croppedImage', blob/*, 'example.png' */);
            formData.append('orgImage', source)  
            try{
                await axios.post('/user_icon_cropped_up_api',formData)
                iconEditModal.value = false;
                emit('updateUser')
            } catch (e) {
                notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            } finally {
                sendLoader.value = false;
            } 
        }
    }     
    const closeIconEditModal = () => {
        iconEditModal.value = false
        cropCancel()
    }
    const cropCancel = () => {
        cropperIs.value = false;
        if(cropperInstance.value){
            cropperInstance.value.destroy();
            cropperInstance.value = null;
        }
                    
    }
    const iconDeleteConfirm = async(id) => {
        const answer = await confirm('アイコンを削除してもよろしいですか？')
                   
        if(!answer.value) return
        defaultIconCreate(id)
                  
    }
    const defaultIconCreate = async() => {     
        try{
            sendLoader.value = true;
            await axios.post('/user_icon_create_api',{icon_type: iconType.value, icon_bg: iconBg.value})
            emit('updateUser');

            iconEditModal.value = false;
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            sendLoader.value = false;
        } 
    }

</script>