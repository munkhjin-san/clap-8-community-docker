<template>
    <div class="g-news-wrap" :style="{ display : visibility}">
        <img class="gn-banner" v-lazy="{src:'/gn.png'}"/>
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
<script>
import Swiper from 'swiper';
import { Autoplay } from 'swiper';
import 'swiper/css'
import 'swiper/css/autoplay'
export default{
    props:['newsItems'],
    data(){
        return{
            visibility: 'none'
        }
    },
    mounted(){
        setTimeout(() => {
            this.visibility = 'block'
            new Swiper('.swiper-news', {
                modules: [Autoplay],
                centeredSlides: true,
                autoplay: {
                    delay: 10000,
                    disableOnInteraction: false
                },
                spaceBetween: 10
            })
        }, 300);  
    },
    
  
}
</script>