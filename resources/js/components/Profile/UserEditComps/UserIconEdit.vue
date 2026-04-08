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
                <div v-if="userDaysWeather.length" class="flex text-[12px] justify-center items-center gap-[8px]">
                    <div v-for="(weather, index) in userDaysWeather" :key="index" class="flex items-center">       
                        <p>{{DateTime.fromISO(weather.date).toFormat("d(EEE)")}}</p>
                        <WeatherIcon :key="`weather_${weather.value_int}`" :which="weather.value_int" size="16"/>  
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
                <div class="bar05 gap-2" v-if="UserAllData?.refresh_current_balance !== null && canViewRefreshHistory">
                    <div @click="emit('openHistory')" title="リフレッシュ" class="flex gap-1 items-center cursor-pointer">
                        <svg data-v-1e240681="" xmlns="http://www.w3.org/2000/svg" width="27" height="27" class="side-app-icon" viewBox="0 0 152 152"><path d="M35.35664,122.00491c-2.92196,4.71027-6.09263,9.26686-9.03117,13.97086-1.53951,2.46444-3.33378,7.18873-6.0073,8.3482-3.69011,1.60036-7.54913-.52241-7.82955-4.58333-.20116-2.91308,2.19207-6.1748,3.69795-8.63033,3.5865-5.84821,7.75654-11.39238,11.81684-16.91359-3.52069-5.04324-6.16224-10.79308-7.39876-16.85097-5.15406-25.25064,8.69527-47.64356,29.41981-60.71786,25.29294-15.95632,54.14093-17.83465,81.49292-27.78443,3.39769-1.23597,7.61434-4.24429,11.06763-1.54447,1.41795,1.10857,1.8595,2.68369,2.03845,4.40686.14072,1.355.25042,3.28182.31016,4.67031,1.65578,38.48073-9.9261,85.51135-46.39714,105.09187-15.61261,8.38208-37.00117,11.79489-53.88275,5.44193-3.39181-1.27642-6.21515-3.0805-9.29709-4.90505ZM64.46498,88.42486c-8.13962,7.74857-15.57825,16.25774-22.55533,25.06733-.1484.56878,2.62196,2.09445,3.20674,2.40824,13.86161,7.43824,34.81025,3.94291,48.24892-3.11919,29.48572-15.49492,39.75076-53.33804,40.72847-84.36919.03954-1.25506.16262-8.09017-.15083-8.66259-.26877-.49082-.40163-.11804-.63624-.04258-3.74828,1.20558-7.35931,2.70103-11.17357,3.77578-14.06932,3.96434-28.46257,6.06422-42.40211,10.91821-26.54063,9.2419-53.72366,26.99623-49.0277,59.29154.4657,3.20276,2.07105,8.46276,3.70516,11.23819.14153.24037-.08594.48899.49917.37106,16.13675-19.90509,35.39727-37.40145,57.30399-50.80299,2.40554-1.4716,10.43623-6.57973,12.71964-6.63968,2.89089-.0759,5.13694,2.83283,4.40191,5.59945-.2283.85932-1.1071,1.75234-1.77223,2.32705-3.69668,3.19412-10.53862,6.70129-14.88047,9.72891-9.90494,6.90682-19.47192,14.58696-28.21551,22.91048Z"></path></svg>
                        <p>{{ amountOfMoneyParser(UserAllData?.refresh_current_balance) }}円</p> 
                    </div>
                                          
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
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { amountOfMoneyParser } from '@/utils/tools';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const props = defineProps(['UserAllData', 'clapData', 'movExist', 'refreshSummary'])
    const emit = defineEmits(['updateUser', 'openHistory'])
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
    const api = useApi()
    const canViewRefreshHistory = computed(() => {
        return !!(props.UserAllData && auth.id && ((props.UserAllData.id === auth.id && auth.isEmployee) || auth.isAdmin))
    })
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
        const response = await api.post('/get_albums', { tag_id: tag.id })
        tagAlbums.value = response
        viewAlbum.value = true

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
    const introMovDeleteConfirm = async(id) => {
        const response = await api.post('/mov_delete', {delete_id: targetId, mov_id: id}, {
            ask: '自己紹介Movを削除しますか。',
            toast: '削除しました。'
        })
        if(response == 'saved'){
            emit('updateUser');
        } 
    }
    const iconClickMenu = () => {
        menu.setMenu( {name: 'iconMenuWrap', id: 23})
    }
    const sendIcon = async() => {
        if(iconType.value == 0) {
            iconDeleteConfirm()
        }else if(iconType.value == 1 && cropperInstance.value){
            customIconCreate()
        }

    }
    const customIconCreate = async() => {

        sendLoader.value = true;
        const { blob, source } = await cropperInstance.value.complete();
        if(blob && source){
            const formData = new FormData();
            formData.append('croppedImage', blob/*, 'example.png' */);
            formData.append('orgImage', source)    
            await api.post('/user_icon_cropped_up_api', formData)
            iconEditModal.value = false;
            emit('updateUser')   
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
    const iconDeleteConfirm = async() => {     
        sendLoader.value = true;
        await api.post('/user_icon_create_api', {icon_type: iconType.value, icon_bg: iconBg.value}, {
            ask: 'アイコンを変更しますか？',
            toast: 'アイコンを変更しました。'
        })
        emit('updateUser');
        iconEditModal.value = false;
        sendLoader.value = false;       
    }

</script>