import './bootstrap'
import '../sass/main.css'
import 'vue3-emoji-picker/css'
import '@cyhnkckali/vue3-color-picker/dist/style.css'

import theme from '../assets/theme.json'
import { Settings } from 'luxon'
import { createApp, defineAsyncComponent, type Plugin } from 'vue'

import { createPinia } from 'pinia'

import VueLazyload from 'vue3-lazyload'
import { createVuetify } from 'vuetify'

import router from './router'
import { useTheme } from './store/theme'

type ThemePalette = {
    className: string
    dark: string
    light: string
}

const app = createApp({})
const pinia = createPinia()
const vuetify = createVuetify()

Settings.defaultLocale = 'ja'

const screenOrientation = screen.orientation 
if (screenOrientation?.lock) {
    screenOrientation.lock('portrait').then(() => {
        console.log('Portrait mode locked')
    })
}

let dark = true
const customTheme = localStorage.getItem('dark')

if (customTheme === '0' || !customTheme) {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        dark = true
    } else {
        dark = false
    }
} else if (Number.parseInt(customTheme, 10) === 1) {
    dark = true
} else if (Number.parseInt(customTheme, 10) === 2) {
    dark = false
}

if (Array.isArray(theme)) {
    theme.forEach((palette) => {
        const currentPalette = palette as ThemePalette
        document.documentElement.style.setProperty(
            currentPalette.className,
            dark ? currentPalette.dark : currentPalette.light,
        )
    })
}

console.log(`Theme: ${dark ? 'dark' : 'light'}`)

app
    .component('Root', defineAsyncComponent(() => import('./components/Root.vue')))
    .component('Login', defineAsyncComponent(() => import('./components/Auth/LoginComponent.vue')))
    .component('PublicSurveyRoot', defineAsyncComponent(() => import('./components/PublicSurvey/PublicSurveyRoot.vue')))
    .use(VueLazyload as Plugin, {})
    .use(pinia)
    .use(router)
    .use(vuetify)

app.mount('#app')

const themeStore = useTheme()
themeStore.setDark(dark)