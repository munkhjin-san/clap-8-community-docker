<template>
    <div class="g-news-wrap" :style="{ visibility : visibility}">
        <img class="gn-banner" v-lazy="{src:'/gn.png'}"/>
        <swiper
            :modules="modules"
            :centeredSlides="true"
            :scrollbar="{ draggable: true }"
            :autoplay="{
                delay: 10000,
                disableOnInteraction: false,
            }"            
        >
            <swiper-slide v-for="item in newsItems" class="news-slider">
                <a class="g-news-link" :href="item.url" target="_blank">
                    <div class="gn-img-container">
                        
                        <img class="gn-image" v-lazy="{src: item.src }" />
                        
                    </div>
                    <p class="gn-title">{{ item.title }}</p>
                </a>
                
            </swiper-slide>
        </swiper>
    </div>
</template>
<script>
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay} from 'swiper';
import 'swiper/css'
import 'swiper/css/autoplay'
export default{
    props:['newsItems'],
    data(){
        return{
            visibility: 'hidden'
        }
    },
    mounted(){
        setTimeout(() => {
            this.visibility = 'visible'
        }, 300);  
    },
    setup() {
        return {
            modules: [Autoplay],
        };
    },
    components: {
            Swiper,
            SwiperSlide,
        },
}
</script>