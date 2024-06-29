<template>
    <div v-if="viewWeatherComponent" class="overlay">
        <div id="weatherID" class="weather-wrapper">
            <div class="list-wrapper">
                <p>おはようございます。今日のコンディションを選択してください。</p>
                <div class="f-icons">
                    <div @click="saveWeather(num)" class="f-wrap" v-for="num in [0, 1, 2, 3, 4, 5]">
                        <WeatherIcon :class="['f-inner',{'selected' : selected == num}]" :which="num" size="50" />
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

</template>
<script setup>

import moment from 'moment';
import { inject, onMounted, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import WeatherIcon from '@/components/Global/WeatherIcon.vue';
const auth = useAuthUserStore()
const { notify } = inject('dialog')
const viewWeatherComponent = ref(false)
const loading = ref(false)
const selected = ref(null)
onMounted(() => {
    getTodayWeather()
})
const getTodayWeather = async () => {
    let today = moment().local().format('YYYY-MM-DD')
    const user_id = auth.id
    const yesterday = localStorage.getItem('weather_' + user_id)
    if (today != yesterday) {
        const response = await axios.post('/today_weather', { today }).then(res => res.data)
        viewWeatherComponent.value = _.isEmpty(response.data) || response.data !== 'weekend' ? true : false
    }
}
const saveWeather = async (index) => {
    let today = moment().local().format('YYYY-MM-DD')
    if(loading.value) return
    try {
        selected.value = index
        loading.value = true
        await axios.post('/save_weather', { today, value: index })
        localStorage.setItem('weather_' + auth.id, today)
        viewWeatherComponent.value = false
        const user = await axios.post('/profile_get_update_user', {id: auth.id}).then(res => res.data)
        if(user && Object.hasOwn(user, 'id')){
            auth.setUser(user)           
        } 
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally{
        loading.value = false
    }

}

</script>
<style scoped lang="scss">
.weather-wrapper {
    position: fixed;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    background: rgba(0, 0, 0, 0.6);
    z-index: 44;
}

.list-wrapper button {
    margin-top: 20px;
    margin-bottom: 10px;
    color: white;
    background: black;
    padding: 5px 10px;
}

.list-wrapper p {
    font-size: 20px;
    word-break: keep-all;
    line-height: 1.5;
    user-select: none;
}

.list-wrapper {
    background: #fff;
    text-align: center;
    padding: 30px;
}




.f-icons {
    display: flex;
    place-content: space-around;
    flex-wrap: wrap;
    row-gap: 20px;
    margin-top: 20px

}

.f-wrap{
    height: 75px;
    min-height: 75px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1 1 calc(16.66%);
    background-color: #fff;
    transition: background-color 0.2s ease;
}
.f-wrap:hover{
    background-color: #f3f3f3;
}
.f-inner{
    transition: transform 0.2s ease;
}
.selected{
    transform: scale(1.3);
}
@media screen and (max-width: 959px) {
    .f-wrap{
        flex: 1 1 calc(50%);
    }

    .list-wrapper p {
        font-size: 16px;
        word-break: break-word;
    }

    .list-wrapper {

        max-width: 75%;
    }
}
</style>