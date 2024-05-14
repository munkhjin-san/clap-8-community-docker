<template>
    <div class="sample-d">
        <div class="sample-p" v-for="portfolio in portfolios">
            <UserPortfolio     
                style="border: none;background: var(--background-color);margin-bottom: 0"            
                :portfolio="portfolio"
                @reload="getPortfolios(portfolio.id)"
            >
                <template #user="{user}">
                    <div style="display: flex;align-items: center;gap: 10px;margin: 20px 0;">
                        <UserIcon :user="user" size="30" imgClass="userNormalIcon"/>
                        <p>{{ user.name }}</p>
                    </div>
                </template>
            </UserPortfolio>
        </div>        
    </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import UserPortfolio from '../../Profile/UserPortfolio.vue';
import UserIcon from '../../Board/Mixed/UserIcon.vue'
    const portfolio_list = ref([])
    const route = useRoute()
    onMounted(() => {
        getPortfolios(-1)
    })
    const getPortfolios = async(id) => {
        const data = await axios.get(`/get_portfolio_view?lesson_theme_id=${route.params?.lessonThemeId}&id=${id}`).then(res => res.data)
        if(id == -1){
            portfolio_list.value = data
        }else{
            const record = data[0]
            const index = portfolio_list.value.findIndex(ob => ob.id == record.id)
            if(index > -1){
                portfolio_list.value[index] = record
            }
        }
    }
    const portfolios = computed(() => {
        return portfolio_list.value.filter(ob => ob.public_content)
    })
</script>
<style lang="scss">
.sample-p{    
    color: var(--primary-color);
    padding: 10px;
    background: var(--background-color);
    margin: 0 20px;
}
.sample-d{
    
    display: flex;
    flex-direction: column;       
    height: calc(100% - 50px);
    overflow: hidden auto;
    gap: 25px;
}
</style>