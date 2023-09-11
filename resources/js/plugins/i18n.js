import { createI18n } from 'vue-i18n';
import mn from './languages/mn'
import ja from './languages/ja'
import en from './languages/en'
const messages = {
    mn, ja, en    
};

// let defaultLocale = localStorage.getItem('lang')
// if(!defaultLocale){
   
//     const browserLang = navigator.language.substring(0, 2)
//     if (browserLang === 'ja' || browserLang === 'mn') {
//         defaultLocale = browserLang
//     } else {
//         defaultLocale = 'en'
//     }
// }

const i18n = createI18n({
    warnHtmlMessage: 'off',
    warnHtmlInMessage: 'off',
    locale: 'ja',
    messages
});

export default i18n;