<template>
    <div style="display:flex;margin: 5px 0;place-content: center;margin-top: auto;">  
                    
        <button class="search-navi-button" style="display:flex;" @click="setNavi(-1)">
            <svg version="1.1" height="8" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="margin:auto;fill:var(--primary-color)">
                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
            </svg>
        </button>           
        <div class="pagerWrap" style="display:flex;max-width: 180px;overflow: auto;">
            <div :id="'pagerButton_' + number" @click="emit('setActivePage', number)" :class="{selectedPageButton: number == activePath }" style="display:flex" class="search-navi-button cursor-pointer" :key="number" v-for="number in page">
                <span style="margin:auto;">{{number}}</span>
            </div>                   
        </div>
        <div id="pageNavigator01" v-if="possiblePage > 6" class="search-navi-button cursor-pointer" style="position:relative;padding:0;">
            <p @click.stop="menu.setMenu( {name: 'pageJumperList01', id: 367})" style="padding:10px;">...</p>
            <Transition name="modalFade">
                <div v-if="menu.name == 'pageJumperList01' && menu.id == 367" id="pageJumperList01" class="boxMenu" style="top: auto;bottom:30px;left: 50%; transform: translate(-50%, 0); margin-left: auto; margin-right: auto;max-height: 145px;max-width:145px;overflow: hidden auto;">
                    <p @click="emit('setActivePage', number), menu.setMenu( {name: '', id: null})" class="boxMenuItems" :key="number" v-for="number in possiblePage">{{number}}</p>
                </div>
            </Transition>                    
        </div>
        <button class="search-navi-button" style="display:flex;" @click="setNavi(1)">
            <svg version="1.1" height="8" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="margin:auto;fill:var(--primary-color)">
                <path d="M18.699 14.17c-1.305-1.166-2.612-2.332-3.927-3.486-1.311-1.161-2.634-2.308-3.953-3.46-1.316-1.156-2.646-2.296-3.973-3.439-1.33-1.139-2.667-2.273-4.014-3.394-0.662-0.551-1.647-0.52-2.272 0.107-0.65 0.654-0.619 1.725 0.019 2.393 1.198 1.254 2.407 2.495 3.621 3.729 1.232 1.245 2.462 2.492 3.704 3.725 0.902 0.9 1.803 1.802 2.707 2.699 0.033 0.032 0.055 0.069 0.072 0.106 0.045 0.036 0.082 0.080 0.111 0.129 0.069 0.046 0.129 0.117 0.176 0.216 0.021 0.047 0.044 0.092 0.066 0.136 0.12 0.062 0.214 0.168 0.246 0.325 0.001 0.005 0.002 0.009 0.003 0.014 0.104 0.157 0.187 0.327 0.254 0.505 0.109 0.185 0.182 0.388 0.226 0.601 0.002 0.012 0.005 0.024 0.007 0.036 0.016 0.085 0.028 0.172 0.036 0.26 0.195 0.593 0.26 1.183-0.030 1.652-0.006 0.157-0.067 0.277-0.157 0.361-0.019 0.050-0.039 0.099-0.063 0.149-0.040 0.084-0.1 0.145-0.17 0.188-0.008 0.015-0.019 0.028-0.028 0.042-0.032 0.13-0.106 0.228-0.202 0.293-0.072 0.145-0.157 0.287-0.26 0.43-0.046 0.063-0.101 0.113-0.163 0.151-0.018 0.020-0.037 0.038-0.059 0.054-0.014 0.059-0.044 0.116-0.094 0.165-0.9 0.888-1.797 1.782-2.699 2.672-1.244 1.231-2.476 2.475-3.714 3.717l-1.843 1.871-1.832 1.885c-0.655 0.681-0.669 1.793 0.044 2.48 0.652 0.631 1.693 0.624 2.385 0.038l1.964-1.66 1.995-1.71c1.32-1.149 2.648-2.293 3.962-3.45s2.636-2.308 3.943-3.474c1.311-1.159 3.284-2.806 4.106-3.689s0.792-2.492-0.192-3.369z"></path>
            </svg>
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import { useMenuStore } from "@/store/menu";
    const menu = useMenuStore()
    const props = defineProps(['possiblePage', 'activePath'])
    const emit = defineEmits(['setNavi', 'setActivePage'])
    const page = computed(() => {
        return props.possiblePage
    })
    watch(() => props.activePath, (after) => {
        const el = document.getElementById('pagerButton_' + after)?.scrollIntoView({behavior: "smooth", block: "center"})
    })
    const setNavi = (index: number) => {
        if((props.possiblePage == props.activePath && index == 1) || (props.activePath == 1 && index == -1))return
        emit('setNavi', index)
    }
   
</script>
<style scoped lang="scss">
    .search-navi-button{
        background-color: var(--background-color);
        color: var(--primary-color);
        font-size: 12px;
        width: 30px;
        height: 30px;
        min-width: 30px;
        user-select: none;
    }
    .pagerWrap {
        -ms-overflow-style: none;  /* Internet Explorer 10+ */
        scrollbar-width: none;  /* Firefox */
    }
    .pagerWrap { 
        display: none;  /* Safari and Chrome */
    }
    .selectedPageButton{
        background-color: var(--primary-color) !important;
        color:var(--background-color) !important;
    }
</style>
