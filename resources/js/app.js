import './bootstrap';
import { createApp } from 'vue';
import { defineAsyncComponent } from 'vue'
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import MasonryWall from '@yeger/vue-masonry-wall'
import VueLazyload from 'vue3-lazyload';
const app = createApp({});
import '../sass/app.scss'

import { createPinia } from 'pinia'
const pinia = createPinia()
import 'moment/dist/locale/ja'
import router from './router'


let dark = true
import theme from '../assets/theme.json'
const customTheme = localStorage.getItem('dark')
if(customTheme == 0 || customTheme == '0' || !customTheme){
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        dark = true
    } else {
        dark = false
    }
}else if(parseInt(customTheme) == 1 ){
    dark = true
}else if(parseInt(customTheme) == 2 ){
    dark = false
}
if(theme){
    theme.forEach(pallete => {
        document.documentElement.style.setProperty(pallete.className, dark ? pallete.dark : pallete.light);
    });
} 

import Vue3TouchEvents from "vue3-touch-events";
// Vuetify
// import 'vuetify/styles'
import { createVuetify } from 'vuetify'
// import { VDataTableVirtual } from 'vuetify/components/VDataTableVirtual'

// const vuetify = createVuetify({
//   components : { VDataTableVirtual },
// })
const vuetify = createVuetify()
app
.component('Root', defineAsyncComponent(() => import('./components/Root.vue')))
.component('Login', defineAsyncComponent(() => import('./components/Auth/LoginComponent.vue')))

// third party plugin
.component("drop-selector", VueSelect)
.use(Vue3TouchEvents)
.use(MasonryWall)
.use(VueLazyload)
.use(pinia)
.use(router)
.use(vuetify)
app.mount('#app');

