<template>
<div v-if="message" class="quotBox">
    <div class="quotInner">
        <div class="quotTitle">
            <div v-if="which == 'reply'" style="font-size: 31px;line-height: 0.7; margin-right: 7px;">
                <svg class="dot-menu" version="1.1" viewBox="0 0 91 91" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24">
                    <g><path class="st0" d="M53.3,3.9c1.9-0.2,2-2.8,0-3C30-1.4,4.7,23.9,11.9,47.5C17.6,65.8,37.4,71.8,55.1,69   c-5.9,3.8-11.6,7.8-16.6,12.7c-4.5,4.4,2.4,11.3,6.9,6.9c9.5-9.4,20.1-17,31.2-24.3c2.7-1.7,2.2-5.7-0.9-6.7   C63,53.6,53,46.6,41.8,39.6c-2.8-1.7-5.3,2.1-3.4,4.4c5.3,6.2,12.3,10.9,19.8,14.6c-12.7,3.2-29.8,1.9-36.4-9.9   C11.1,29.2,34.5,6.1,53.3,3.9z"/></g>
                </svg>
            </div>
            <div v-if="which == 'quot'" style="font-size: 31px;line-height: 0.7; margin-right: 5px;">
                <svg class="dot-menu" version="1.1" width="24px" height="24px" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <g fill-rule="evenodd" id="Page-1" stroke="none" stroke-width="1"><g id="Artboard-Copy" transform="translate(-407.000000, -9.000000)"><path d="M448.6211,37.1646 C448.3691,36.3886 447.9321,35.6776 447.3241,35.0346 C446.6871,34.3626 445.8501,33.8426 444.8121,33.4666 C443.7731,33.0936 442.4391,32.9046 440.7951,32.9046 L438.5641,32.9046 C438.8301,30.1586 439.8081,27.8216 441.5001,25.8896 C443.1891,23.9636 445.5311,22.2686 448.5251,20.8146 L446.7091,18.0006 C442.7671,19.6796 439.3941,22.1356 436.5921,25.3796 C433.7911,28.6266 432.3961,31.8436 432.3961,35.0346 C432.3961,38.4626 433.2051,41.1516 434.8361,43.0926 C436.4671,45.0336 438.8031,46.0006 441.8401,46.0006 C443.7671,46.0006 445.4411,45.3576 446.8651,44.0726 C448.2871,42.7896 449.0001,41.2866 449.0001,39.5726 C449.0001,38.7456 448.8751,37.9406 448.6211,37.1646 M428.2251,37.1646 C427.9711,36.3886 427.5351,35.6776 426.9261,35.0346 C426.2911,34.3626 425.4531,33.8426 424.4141,33.4666 C423.3771,33.0936 422.0431,32.9046 420.3981,32.9046 L418.1661,32.9046 C418.4331,30.1586 419.4101,27.8216 421.1011,25.8896 C422.7911,23.9636 425.1351,22.2686 428.1271,20.8146 L426.3121,18.0006 C422.3691,19.6796 418.9981,22.1356 416.1951,25.3796 C413.3941,28.6266 412.0001,31.8436 412.0001,35.0346 C412.0001,38.4626 412.8071,41.1516 414.4391,43.0926 C416.0681,45.0336 418.4041,46.0006 421.4431,46.0006 C423.3691,46.0006 425.0451,45.3576 426.4691,44.0726 C427.8911,42.7896 428.6031,41.2866 428.6031,39.5726 C428.6031,38.7456 428.4761,37.9406 428.2251,37.1646"/><g id="slices" transform="translate(47.000000, 9.000000)"/></g></g>
                </svg>
            </div>
            <div v-if="which == 'forward'" style="margin: auto 5px auto 0;font-size:14px;font-weight:600">
                <svg class="dot-menu" version="1.1" style="transform: scaley(-1);" viewBox="0 0 91 91" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24">
                    <g><path class="st0" d="M53.3,3.9c1.9-0.2,2-2.8,0-3C30-1.4,4.7,23.9,11.9,47.5C17.6,65.8,37.4,71.8,55.1,69   c-5.9,3.8-11.6,7.8-16.6,12.7c-4.5,4.4,2.4,11.3,6.9,6.9c9.5-9.4,20.1-17,31.2-24.3c2.7-1.7,2.2-5.7-0.9-6.7   C63,53.6,53,46.6,41.8,39.6c-2.8-1.7-5.3,2.1-3.4,4.4c5.3,6.2,12.3,10.9,19.8,14.6c-12.7,3.2-29.8,1.9-36.4-9.9   C11.1,29.2,34.5,6.1,53.3,3.9z"/></g>
                </svg>                
            </div>
            <div v-if="message.user" @click.stop="pushInstantUser($event, message.user_id)" class="column-01 cursor-pointer">                        
                <UserIcon imgStyle="width:25px;height:25px;border-radius:50%" size="25" :user="message.user"/>                       
            </div> 
            <div v-else class="column-01 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30">
                    <circle cx="15" cy="15" r="15" fill="#ddd"/>
                </svg>
            </div>
            <div @click.stop="pushInstantUser( $event, message.user_id)" class="quotUserName cursor-pointer">
                <p class="userName" style="margin-left:5px;font-size:13px">{{ messageUserName }}</p>               
            </div>
        </div>
        <div class="clearBoth"></div>                                                    
        <div v-if="message.deleted_at" class="quotBody">
            <p style="color: darkgray;font-size: 15px;">このメッセージは削除されました</p>
        </div>      
        <div v-else class="quotBody">
            <div v-if="which == 'reply'" :style="{height: `${dynamicHeight}`, overflow: 'hidden', transition: 'height 0.1s ease'}">
                <p ref="replyBody" style="line-height: 1.5;white-space: pre-line;" v-html="messageBody"></p>
            </div>            
            <div @click="toggleFull" class="jump-link" style="margin-top:10px" v-if="dynamicHeight !== 'auto' && which == 'reply'">{{ dynamicHeight == '42px' ? '続きを表示する' : '閉じる' }}</div>
            <p v-if="which == 'forward'" style="line-height:1.5;white-space:pre-line;" v-html="messageBody"></p>
            <p v-if="which == 'quot' && quotMessage" style="line-height:1.5;white-space:pre-line;" v-html="messageBody"></p>
            <MessageFiles 
                v-if="message.message_files && message.message_files.length"
                :list="message.message_files"
                :message="message"
            />        
        </div>
        <div style="width:100%;height:0.5px;background:gray;margin: 10px 0 5px 0;"></div>
    </div>
</div>
</template>
<script setup>
import { computed, inject, onMounted, ref } from "vue";
import MessageFiles from "./MessageFiles.vue";
import UserIcon from "../Mixed/UserIcon.vue";
    const props = defineProps(['which', 'message', 'urlCheck', 'quotMessage'])
    const pushInstantUser = inject('pushInstantUser')
    const replyBody = ref(null)
    const dynamicHeight = ref('auto')

    onMounted(() => {
        if(props.which == 'reply' && replyBody.value){
            if(replyBody.value?.clientHeight > 42){
                dynamicHeight.value = '42px'
            }
        }        
    })
    const messageBody = computed(() => {
        const t_text = props.which == 'quot' ? props.quotMessage : props.message.message
        const text = t_text ? t_text : ''
        const to_all = text.replace('<span class="toAll">@全員</span>', '<a class="toAll">@全員</a>'); 
        const converterd = to_all.replace(/<((?!a )[^>]*)>/g, "&lt;$1&gt;").replace(/&lt;\/a&gt;/g, "</a>");
        const br_remove = converterd.replace(/&lt;br&gt;/g," ");
        return props.urlCheck(br_remove)
    })
    const messageUserName = computed(() => {                
        return props.message.user && props.message.user.deleted_at == null
        ? props.message.user.name
        : '非アクティブユーザー';
    })
    const toggleFull = () => {
        dynamicHeight.value = dynamicHeight.value == '42px' ? `${replyBody.value?.clientHeight}px` : '42px'
    }
</script>