<template>
    <div class="post-root" @click="keyListView = false">
        <div class="post-header">
            <HamBurger v-if="$store.state.mobile"/>
            <div class="post-search-wrap" style="position: relative;" @click.stop>
                <!-- <PostSearchBar className="newChatMemberSearch" customPlaceHolder="キーワードを入力" @searchStart="setKeyWord" @focus="keyListView = true" :initialValue="searchWord"/> -->
                
                <div style="width:100%;display:flex;">
                    <div class="searchBarInner" style="margin: 0;width: 100%;">   
                        <input v-model="searchWord" @focus="keyListView = true" class="searchBarArea searchInputArea memberSearch" placeholder="キーワードを入力" type="search" style="margin: 0;width:100%;"/>
                        <div style="position: absolute;left: 10px;display: flex;height: 30px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="margin: 7px auto auto auto;fill:#767676">
                                <path d="M31.875 28.185c-0.034-0.444-0.159-0.888-0.376-1.275-0.102-0.194-0.239-0.387-0.387-0.547-0.171-0.194-0.239-0.251-0.342-0.353-0.752-0.752-1.526-1.492-2.278-2.232-0.387-0.376-0.763-0.74-1.15-1.116l-0.865-0.831-0.091-0.091c-0.034-0.034-0.080-0.068-0.125-0.102-0.080-0.068-0.171-0.137-0.262-0.194-0.729-0.49-1.651-0.626-2.471-0.376-0.148 0.046-0.285 0.091-0.421 0.159-0.068 0.034-0.148 0.023-0.205-0.034-0.251-0.262-0.854-0.9-1.139-1.207-0.057-0.068-0.068-0.159-0.011-0.228 0.717-0.911 1.275-1.902 1.697-2.938 0.592-1.469 0.888-3.029 0.888-4.589s-0.296-3.12-0.888-4.601c-0.592-1.469-1.492-2.847-2.676-4.043-1.173-1.196-2.54-2.095-4.009-2.688-1.469-0.604-3.029-0.9-4.589-0.9-1.549 0-3.109 0.296-4.578 0.9-1.469 0.592-2.847 1.492-4.031 2.688-1.184 1.184-2.084 2.562-2.676 4.031s-0.888 3.041-0.888 4.601 0.296 3.12 0.888 4.589c0.592 1.469 1.492 2.847 2.676 4.043s2.562 2.084 4.031 2.688c1.469 0.604 3.029 0.9 4.589 0.9s3.12-0.296 4.578-0.9c1.036-0.421 2.038-1.002 2.949-1.72 0.046-0.034 0.114-0.034 0.159 0.011 0.273 0.273 1.002 0.957 1.253 1.196 0.034 0.034 0.046 0.091 0.023 0.137-0.205 0.444-0.307 0.945-0.285 1.446 0.023 0.421 0.137 0.854 0.342 1.23 0.102 0.194 0.228 0.376 0.364 0.535 0.171 0.194 0.228 0.251 0.33 0.353 0.74 0.774 1.469 1.549 2.209 2.3l1.116 1.15 0.558 0.569 0.376 0.376c0.034 0.034 0.080 0.080 0.125 0.114 0.080 0.068 0.171 0.137 0.262 0.205 0.74 0.512 1.708 0.683 2.574 0.444 0.433-0.114 0.843-0.319 1.196-0.615 0.046-0.034 0.091-0.068 0.125-0.114l0.114-0.102 0.421-0.421c0.319-0.319 0.558-0.706 0.717-1.127s0.216-0.877 0.182-1.321zM15.795 21.159c-1.15 0.467-2.391 0.706-3.621 0.706s-2.46-0.239-3.621-0.706c-1.15-0.467-2.243-1.173-3.177-2.118-0.945-0.945-1.64-2.027-2.118-3.189-0.467-1.162-0.706-2.403-0.706-3.633 0-1.241 0.239-2.471 0.706-3.633s1.173-2.243 2.118-3.189c0.945-0.957 2.027-1.651 3.189-2.13 1.15-0.467 2.38-0.706 3.621-0.706 1.23 0 2.46 0.239 3.621 0.706 1.15 0.467 2.232 1.173 3.177 2.118v0c0.945 0.945 1.64 2.027 2.118 3.189 0.467 1.162 0.706 2.403 0.706 3.633 0 1.241-0.239 2.471-0.706 3.633s-1.173 2.243-2.118 3.189c-0.957 0.957-2.038 1.663-3.189 2.13zM29.153 28.823l-0.478 0.478c-0.057 0.057-0.137 0.091-0.216 0.114-0.159 0.046-0.342 0.011-0.478-0.080-0.011-0.011-0.034-0.023-0.046-0.034l-0.068-0.068-0.285-0.273-1.708-1.674c-0.763-0.752-1.526-1.48-2.3-2.221-0.239-0.239-0.251-0.239-0.319-0.342-0.057-0.080-0.091-0.182-0.102-0.285-0.034-0.205 0.046-0.433 0.182-0.592 0.125-0.159 0.364-0.399 0.558-0.535 0.273-0.194 0.604-0.125 0.797 0.068s1.697 1.754 2.061 2.141c0.74 0.763 1.48 1.537 2.232 2.289 0.239 0.239 0.239 0.239 0.285 0.33 0.034 0.068 0.057 0.159 0.068 0.239 0.011 0.159-0.057 0.319-0.182 0.444z"></path>
                            </svg>
                        </div>
                        <div @click="searchWord = ''" v-if="searchWord.length" class="searchBarCancelButton" style="right: 0;">
                            <svg class="smallCancelButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 32 32" style="margin: auto;">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <Transition name="modalFade">
                    <div class="key-container" v-if="keyListView">
                        <div @click.prevent.stop="setKeyWord(key.text)" class="keyword-result" v-for="key in key_word_list">{{ key.text }}</div>
                    </div>
                </Transition>
            </div>
            
        </div>
     
        <div class="support-container">
            <div class="support-category">
                <router-link :to="{name: 'faq'}" :class="['t-selector', { tSelected: selectedRoute == 'faq'}]">よくある質問</router-link>
                <router-link :to="{name: 'email_consult'}" :class="['t-selector', { tSelected: selectedRoute == 'email_consult'}]">メール相談</router-link>
                <router-link v-if="viewTrayUsers.includes($store.state.user.id)" :to="{name: 'email_inbox'}" :class="['t-selector', { tSelected: selectedRoute == 'email_inbox'}]">メール相談（受信BOX）</router-link>
                <router-link :to="{name: 'phone_consult'}" :class="['t-selector', { tSelected: selectedRoute == 'phone_consult'}]">電話相談</router-link>

                <!-- <div @click="selectedCategory = 1" :class="['t-selector', { 'tSelected' : selectedCategory == 1}]">メール相談</div>
                <div @click="selectedCategory = 2" :class="['t-selector', { 'tSelected' : selectedCategory == 2}]">電話相談</div> -->
            </div>

                <!-- <Faq v-if="selectedCategory == 0" :qaList="qaList" :tagList="tagList" @setKeyWord="setKeyWord"/>

                <Consult v-if="selectedCategory > 0"/> -->
                    <div class="post-container scrollable" style="height: 100%;">
                        <router-view v-slot="{ Component }">
                            <transition name="supportShift" mode="out-in">
                                <component :is="Component" :qaList="qaList" :tagList="tagList" @setKeyWord="setKeyWord"/>
                            </transition>
                        </router-view> 
                    </div>

            
        </div>
     
    </div>
    
</template>
<script>
import HamBurger from '../Global/HamBurger.vue';
import PostSearchBar from '../Post/PostSearchBar.vue';
import { defineAsyncComponent } from 'vue';
export default{
    data(){
        return{
            qanda_info: [],
            tag_list: [],
            key_word_list: [],
            selectedCategory: 0,
            keyListView: false,
            searchWord: '',
            viewTrayUsers: [610, 516, 517, 519, 518, 526, 494, 604]
        }
    },
    components:{
        HamBurger,
        PostSearchBar,
        // Faq: defineAsyncComponent(() => import('./Faq.vue')),
        // Consult: defineAsyncComponent(() => import('./Consult.vue'))
    },
    mounted(){
        this.getSupportData()
    },
    computed:{
        selectedRoute(){
            return this.$route.name
        },
        tagList(){
            if(this.tag_list && this.tag_list.length){
                const all = {id: 0, text: '全て'}
                let pre = this.tag_list
                pre.unshift(all)
                return pre
            }
            
        },
        qaList(){
            if(!this.searchWord){
                return this.qanda_info
            }else{
                const list = this.qanda_info.filter( ob => {
                    const area = ob.question + ob.answer + ob.content + ob.tag_text
                    return area.includes(this.searchWord)
                })
                return list
            }
            
        }
    },
    methods: {
        setKeyWord(text){
            console.log(text)
            this.searchWord = text
            this.keyListView = false
        },
        getSupportData(){
            axios.post('/support_record_list' ).then(response => {
                this.qanda_info = response.data.record_list
                this.tag_list = response.data.tag_list
                this.key_word_list =  response.data.key_word_list
            })
        }
    }
}
</script>
<style lang="scss">
.supportShift-enter-active,
.supportShift-leave-active {
    transition: transform 0.2s ease;

}

.supportShift-enter-from,
.supportShift-leave-to {
    display: none;
    transform: translateX(100vw);
}
.answerBox{
    white-space: break-spaces;
    line-height: 1.7;
}
.keyword-result{
    padding: 15px;
    font-size: 14px;
    background-color: var(--background-color);
    color: var(--primary-color);
    cursor: pointer;
}
.keyword-result:hover{
    background-color: var(--bg3);
}
.key-container{
    position: absolute;
    top: 31px;
    background: var(--background-color);
    width: 100%;
    box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
    z-index: 6;
}
.support-title{
    font-size: 20px;
    margin-bottom: 20px;
    margin-top: 10px;
}
.qandaContent{
    padding: 15px 0;
    line-height: 1.7;
    white-space: break-spaces;
    padding: 20px;
}
.qandaContent:hover{
    cursor: pointer;
    background-color: var(--bg3);
}
.support-tag-selector{
    display: flex;
    flex-wrap: wrap;
}
.support-tag{
    padding: 15px;
    cursor: pointer;
    transition: background-color 0.2s;
    background-color: var(--background-color);
}
.tag-selected{
    background-color: var(--bg3);
}
.support-content-inner{
    padding: 20px;
    background: var(--background-color);
    margin-right: 20px;
}
.support-content{
    width: -webkit-fill-available;
    height: 100%;
    overflow: auto;
}
.support-container{
    width: 100%;
    display: flex;
    color: var(--primary-color);
    height: calc(100% - 60px);
}
.support-category{
    width: 20%;
    margin-right: 20px;
    height: 100%;
    padding: 0 15px;
    display: flex;
    flex-direction: column;
    min-width: 20%;
}
.t-selector{
    padding: 15px;
    box-sizing: border-box;
    border: solid thin transparent;
    color: var(--primary-color);
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    text-decoration: none;
    font-weight: 400;
}
.t-selector:hover{
    text-decoration: none;
    font-weight: 400;
    color: var(--primary-color);
}
.tSelected{
    background: var(--background-color);

}
@media screen and (max-width: 959px) {
    .t-selector{
        padding: 10px;
        font-size: 13px;
    }
    .support-title{
        padding: 0 20px;
    }
    .support-content{
        margin-top: 20px;
    }
    .support-content-inner{
        margin-left: 20px;
    }
    .support-container{
        flex-direction: column;
        font-size: 14px;

    }
    .support-category{
        flex-direction: row;
        height: auto;
        width: auto;
        flex-wrap: wrap;
    }

}
</style>