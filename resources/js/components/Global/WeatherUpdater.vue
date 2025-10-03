<template>
    <div id="weatherUpdater" class="weather-updater"
        style="box-shadow: 0 1px 2px 0 rgba(60,64,67,.3), 0 2px 6px 2px rgba(60,64,67,.15);top: -50px;left: auto;">
        <div class="list-wrapper">
            <div class="list-item" @click="saveWeather(num)" v-for="num in [0,1,2,3,4,5]">
                <WeatherIcon :which="num" size="20"/>
            </div>
        </div>
    </div>
</template>
<script setup>

import { useMenuStore } from '@/store/menu'
import WeatherIcon from './WeatherIcon.vue'
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import { inject } from 'vue';
const emit = defineEmits(['reload'])
const menu = useMenuStore()
const api = useApi()
const { setUser } = useAuthUserStore()
const UserAllData = inject('UserAllData')
const saveWeather = async (value) => {
    const user = await api.post('/save_weather', { value: value })
    setUser(user)
    UserAllData.value = user
    menu.close()
}
</script>
<style scoped lang="scss">
.weather-updater {
    position: absolute;
    width: fit-content;
    height: fit-content;
    display: flex;
    justify-content: center;
    align-items: center;
    background: rgba(0, 0, 0, 0.6);
    z-index: 44;
    left: 0;
    top: 0;
}


.list-wrapper {
    background: var(--bg3);
    text-align: center;
    display: flex;
    flex-wrap: nowrap;
}
.list-item{
    height: 50px;
    width: 50px;
    min-width: 50px;
    min-height: 50px;
    align-items: center;
    justify-content: center;
    display: flex;
    cursor: pointer;
}
.list-item:hover{
    background: var(--bg2);
}


</style>