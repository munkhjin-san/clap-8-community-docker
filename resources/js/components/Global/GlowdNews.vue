<template>
    <div class="g-news-wrap" :style="{ display : visibility}">
        <img class="gn-banner" v-if="newsItems && newsItems.length" v-lazy="{src:'/gn.png'}"/>
        <div class="swiper-news">
            <div class="swiper-wrapper">
                <div v-for="item in newsItems" class="swiper-slide news-slider">
                    <a class="g-news-link" :href="item.url" target="_blank">
                        <div class="gn-img-container">                            
                            <img class="gn-image" v-lazy="{src: item.src }" />                            
                        </div>
                        <p class="gn-title">{{ item.title }}</p>
                    </a>                    
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import Swiper from 'swiper';
import { Autoplay } from 'swiper/modules';
import 'swiper/css'
import 'swiper/css/autoplay'
import { onMounted, ref } from 'vue';
    const props = defineProps(['newsItems'])
    const visibility = ref('none')
    onMounted(() => {
        setTimeout(() => {
            visibility.value = 'block'
            new Swiper('.swiper-news', {
                modules: [Autoplay],
                centeredSlides: true,
                autoplay: {
                    delay: 10000,
                    disableOnInteraction: false
                },
                spaceBetween: 10,
                slidesPerView: 1,
            })
        }, 300);  
    })
</script>