<template>
    <div class="help-container">
        <div style="height: 60px;display: flex;align-items: center;padding-right: 20px;">
            <HamBurger v-if="$store.state.user"/>
            <div v-else @click="$router.go(-1)" class="help-back-topic" style="margin-right: -15px;">   
                                                            
                <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg>                                        
                
            </div>
            <div :class="['searchBarInner', 'topicSearchBar']" :style="{marginLeft: $store.state.user ? '0': '20px'}">   
                <input @input="setKeyWord" v-model="keyword" class="searchBarArea searchInputArea memberSearch" :placeholder="$t('help.searchPlaceHolder')" type="text" style="margin: 0;width:100%;"/>
                <div style="position: absolute;left: 9px;top:0;display: flex;height: 30px;">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="margin: 7px auto auto auto;fill:#767676">
                        <path d="M31.875 28.185c-0.034-0.444-0.159-0.888-0.376-1.275-0.102-0.194-0.239-0.387-0.387-0.547-0.171-0.194-0.239-0.251-0.342-0.353-0.752-0.752-1.526-1.492-2.278-2.232-0.387-0.376-0.763-0.74-1.15-1.116l-0.865-0.831-0.091-0.091c-0.034-0.034-0.080-0.068-0.125-0.102-0.080-0.068-0.171-0.137-0.262-0.194-0.729-0.49-1.651-0.626-2.471-0.376-0.148 0.046-0.285 0.091-0.421 0.159-0.068 0.034-0.148 0.023-0.205-0.034-0.251-0.262-0.854-0.9-1.139-1.207-0.057-0.068-0.068-0.159-0.011-0.228 0.717-0.911 1.275-1.902 1.697-2.938 0.592-1.469 0.888-3.029 0.888-4.589s-0.296-3.12-0.888-4.601c-0.592-1.469-1.492-2.847-2.676-4.043-1.173-1.196-2.54-2.095-4.009-2.688-1.469-0.604-3.029-0.9-4.589-0.9-1.549 0-3.109 0.296-4.578 0.9-1.469 0.592-2.847 1.492-4.031 2.688-1.184 1.184-2.084 2.562-2.676 4.031s-0.888 3.041-0.888 4.601 0.296 3.12 0.888 4.589c0.592 1.469 1.492 2.847 2.676 4.043s2.562 2.084 4.031 2.688c1.469 0.604 3.029 0.9 4.589 0.9s3.12-0.296 4.578-0.9c1.036-0.421 2.038-1.002 2.949-1.72 0.046-0.034 0.114-0.034 0.159 0.011 0.273 0.273 1.002 0.957 1.253 1.196 0.034 0.034 0.046 0.091 0.023 0.137-0.205 0.444-0.307 0.945-0.285 1.446 0.023 0.421 0.137 0.854 0.342 1.23 0.102 0.194 0.228 0.376 0.364 0.535 0.171 0.194 0.228 0.251 0.33 0.353 0.74 0.774 1.469 1.549 2.209 2.3l1.116 1.15 0.558 0.569 0.376 0.376c0.034 0.034 0.080 0.080 0.125 0.114 0.080 0.068 0.171 0.137 0.262 0.205 0.74 0.512 1.708 0.683 2.574 0.444 0.433-0.114 0.843-0.319 1.196-0.615 0.046-0.034 0.091-0.068 0.125-0.114l0.114-0.102 0.421-0.421c0.319-0.319 0.558-0.706 0.717-1.127s0.216-0.877 0.182-1.321zM15.795 21.159c-1.15 0.467-2.391 0.706-3.621 0.706s-2.46-0.239-3.621-0.706c-1.15-0.467-2.243-1.173-3.177-2.118-0.945-0.945-1.64-2.027-2.118-3.189-0.467-1.162-0.706-2.403-0.706-3.633 0-1.241 0.239-2.471 0.706-3.633s1.173-2.243 2.118-3.189c0.945-0.957 2.027-1.651 3.189-2.13 1.15-0.467 2.38-0.706 3.621-0.706 1.23 0 2.46 0.239 3.621 0.706 1.15 0.467 2.232 1.173 3.177 2.118v0c0.945 0.945 1.64 2.027 2.118 3.189 0.467 1.162 0.706 2.403 0.706 3.633 0 1.241-0.239 2.471-0.706 3.633s-1.173 2.243-2.118 3.189c-0.957 0.957-2.038 1.663-3.189 2.13zM29.153 28.823l-0.478 0.478c-0.057 0.057-0.137 0.091-0.216 0.114-0.159 0.046-0.342 0.011-0.478-0.080-0.011-0.011-0.034-0.023-0.046-0.034l-0.068-0.068-0.285-0.273-1.708-1.674c-0.763-0.752-1.526-1.48-2.3-2.221-0.239-0.239-0.251-0.239-0.319-0.342-0.057-0.080-0.091-0.182-0.102-0.285-0.034-0.205 0.046-0.433 0.182-0.592 0.125-0.159 0.364-0.399 0.558-0.535 0.273-0.194 0.604-0.125 0.797 0.068s1.697 1.754 2.061 2.141c0.74 0.763 1.48 1.537 2.232 2.289 0.239 0.239 0.239 0.239 0.285 0.33 0.034 0.068 0.057 0.159 0.068 0.239 0.011 0.159-0.057 0.319-0.182 0.444z"></path>
                    </svg>
                </div>
                <div @click="cancelSearch" v-if="keyword.length && !searching" style="min-width:30px;min-height:28px;display:flex;position: absolute;right: 1px;cursor:pointer;background: var(--background-color);z-index: 3;">
                    <svg class="smallCancelButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 32 32" style="margin: auto;">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
                <div v-if="searching" style="min-width:30px;min-height:30px;display:flex;position: absolute;right: 0;cursor:pointer;z-index: 1;">
                    <div style="margin:auto;" class="spinner-nano"></div>
                </div>                
            </div>
        </div>
        <div class="helpSearchResultModal" v-if="keyword.length || searchResults.length">
            <div>
                <div v-if="searchResults.length">
                    <div v-for="section in searchResults">
                        <h1 style="font-size: 18px;" v-html="`${section.title}`"></h1>
                        <div style="padding-left: 15px;">
                            <p v-if="section.description" v-html="section.description"></p>
                            <ul>
                                <li style="padding: 5px 0;" v-for="(step, index) in section.steps" :key="step" v-html="`${index + 1}. ${$rt(step)}`"></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div v-else class="no-comment-text" v-html="$t('noResultsFound')"></div>
            </div>
        </div>
        <div style="height: 40px;line-height: 40px;padding: 0 20px;">
            <h1>{{ $t('help.helpTitle') }}</h1>
        </div>
        
        <div class="help-topic-container">            
            <div>
                <router-link :to="`/help/${topic.path}`" :class="['help-topic', {selectedTopic : selectedTopic(topic)}]" v-for="topic in helpList">
                    {{ $t(`help.${topic.name}`) }}
                </router-link>
            </div>
            <router-view v-slot="{ Component }">
                <transition name="slideFromRight">
                    <component :is="Component" />
                </transition>
            </router-view> 

        </div>
    </div>
</template>

<script>
import HamBurger from '../Global/HamBurger.vue'
    export default {
        data(){
            return{
                keyword: '',
                searching: false,
                timeout: 0,
                searchResults: []
            }
        },
        components:{
            HamBurger
        },
        created(){
            document.body.style.position = 'fixed'
            // const customLocale = localStorage.getItem('lang')
            // if(customLocale){
            //     this.$store.commit('setLocale', customLocale)
            //     this.$i18n.locale = customLocale
            // }else {
            //     const browserLang = navigator.language.substring(0, 2)
            //     if (browserLang === 'ja' || browserLang === 'mn') {
            //         this.$store.commit('setLocale', browserLang)
            //         this.$i18n.locale = browserLang
            //     } else {
            //         this.$store.commit('setLocale', 'en')
            //         this.$i18n.locale = 'en'
            //     }
            // }
            window.document.title = `サポート - ${this.$t('help.titleHelp')}`; 
        },
        
        computed:{
            helpList(){
                return this.$route && this.$route.matched && this.$route.matched[0].children ? this.$route.matched[0].children : []
            }
        },
        methods:{
            cancelSearch(){
                this.keyword = '';
                this.searching = false
                this.searchResults = []
            },
            selectedTopic(topic){
                return window.location.pathname.includes(topic.path)
            },
            setKeyWord(){
                
                
                this.keyword = event.currentTarget.value
                this.autoFillDebounce()
                
                
            },
            autoFillDebounce(val) {
                if (this.timeout) clearTimeout(this.timeout)
                this.timeout = setTimeout(() => {
                    this.searchStart(this.keyword)
                }, 300)
            },
            searchStart(keyword){
                if(!keyword || !keyword.length){
                    this.searchResults = []
                    return
                }
                const data = this.$tm('help')
                const arrays = Object.values(data).filter(value => Array.isArray(value));
                let preLoad = []
                arrays.map(topics => {
                    topics.map(topic => {
                        const found = this.containsKeyword(topic, keyword)
                        if(found){
                            preLoad.push(topic)
                        }
                        
                    })
                })
                
                this.searchResults = preLoad
                console.log(this.searchResults)
            },
            
            containsKeyword(item, keyword) {

                const title = item.title ? item.title : ''
                const desc = item.description ? this.clearHTMLFromString(item.description) : ''
                let steps = ''
                if(item.steps){
                    let cleared = item.steps.map(this.clearHTMLFromString)
                    steps = cleared.join(" ")
                }
                let joined = `${title} ${desc} ${steps}` 
                return joined.toLocaleLowerCase().includes(keyword.toLocaleLowerCase())
                
            },
            clearHTMLFromString(htmlString) {
                return htmlString.replace(/<[^>]*>/g, '');
            }
        }
    }
</script>
<style lang="scss">
    .selectedTopic{
        background: var(--background-color);
    }
    .help-report{
        padding: 15px;
        height: calc(100% - 15px);
        display: flex;
        flex-direction: column;
        overflow: hidden auto;
    }
    .topicSearchBar{
        width: auto !important;
    }
    .help-container{
        height: 100%;
        width: 100%;
        display: flex;
        flex-direction: column;
        color: var(--primary-color);
        position: relative;
    }
    .help-topic-container{
        width: 100%;
        height: calc(100% - 100px);
        display: flex;
        flex-grow: 1;
    }
    .help-topic{
        margin: 5px 10px !important;
        color: inherit !important;
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        padding: 10px;
        margin: -10px 0;
        white-space: nowrap;    
        position: relative;
    }
    .help-content-container{
        flex-grow: 1;
        
        height: 100%;
    }
    .help-content-inner{
        width: calc(100% - 20px);
        height: calc(100% - 20px);
        background: var(--background-color);    
        overflow: hidden auto;
        font-size: 15px;
    }
    .help-title-m{
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .help-back-topic{
        width: 40px;
        height: 40px;
        min-width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius:50px;
    }
    .helpSearchResultModal{
        position: absolute;
        left: 0;
        top: 60px;
        width: 100%;
        height: calc(100% - 60px);
        background: var(--bg2);
        z-index: 5;
        padding: 0 20px;
        line-height: 1.5;
    }
    @media screen and (max-width: 959px) {
        .help-content-container{
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .help-content-inner{
            width: 100%;
            height: 100%;  
        }
        .topicSearchBar{
            width: -webkit-fill-available !important;
            width: -moz-available !important;
        }

    }
</style>