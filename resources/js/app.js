import './bootstrap';
import { createApp } from 'vue';
import { defineAsyncComponent } from 'vue'
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import i18n from './plugins/i18n';
import MasonryWall from '@yeger/vue-masonry-wall'
import VueLazyload from 'vue3-lazyload';
const app = createApp({});



import store from './store'
import router from './router'
app.use(store)
app.use(router)
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
import mitt from 'mitt'
window.emitter = mitt()
import { required, max, min, confirmed } from '@vee-validate/rules';
import { configure, defineRule  } from 'vee-validate'

defineRule('required', required);
defineRule('passwordCase', (value) => {
    const regex = /^(?=.*[a-zA-Z])(?=.*\d).{8,}$/
    return regex.test(value);
});
defineRule('confirmed', confirmed);
defineRule('nameCase', (value) => {
    const regex = /^[\p{L}\p{N}\p{Pd}\s]+$/u;
    return regex.test(value);
});
defineRule('email', (value) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(value);
});
defineRule('max', max);
defineRule('min', min);
configure({
    generateMessage: ({ rule }) => {
        const messages = {
            required: () => i18n.global.t('required'),
            max: () => i18n.global.tc('max', {max: rule.params[0]}),
            min: () => i18n.global.tc('min',{min: rule.params[0]}),
            confirmed: () => i18n.global.t('confirmed'),
            passwordCase: () => i18n.global.t('passwordCase'),
            nameCase: () => i18n.global.t('validText'),
            email: () => i18n.global.t('validEmail')
        }
        return messages[rule.name]()
    }
})
app
.component('AdminControlList', defineAsyncComponent(() => import('./components/AccountControl/AdminControlList.vue')))
.component('OverRide', defineAsyncComponent(() => import('./components/Header/OverRide.vue')))
.component('NotifyComponent', defineAsyncComponent(() => import('./components/Global/NotifyComponent.vue')))
// Board //
.component('Root', defineAsyncComponent(() => import('./components/Root.vue')))
.component('BoardIcon', defineAsyncComponent(() => import('./components/Board/Mixed/BoardIcon.vue')))
.component('BoardTitle', defineAsyncComponent(() => import('./components/Board/Mixed/BoardTitle.vue')))
.component('UserIcon', defineAsyncComponent(() => import('./components/Board/Mixed/UserIcon.vue')))
.component('UserComponent', defineAsyncComponent(() => import('./components/Profile/UserComponent.vue')))
.component('LoginComponent', defineAsyncComponent(() => import('./components/Auth/LoginComponent.vue')))
.component('InstantProfile', defineAsyncComponent(() => import('./components/Board/InstantProfile.vue')))
// Board //

.component('Help', defineAsyncComponent(() => import('./components/Help/Help.vue')))
// third party plugin
.component("v-select", VueSelect)

.use(Vue3TouchEvents)
.use(i18n)
.use(MasonryWall)
.use(VueLazyload);

app.mount('#app');

