<template>
    <div class="sample-d">
        <div class="sample-p" v-for="portfolio in portfolios" :key="portfolio.id">
            <UserPortfolio     
                style="border: none;background: var(--background-color);margin-bottom: 0"            
                :portfolio="portfolio"
                @reload="getPortfolios(portfolio.id ?? -1)"
            >
                <!-- @vue-ignore UserPortfolio exposes this runtime slot without typed slot metadata -->
                <template #user="slotProps">
                    <div style="display: flex;align-items: center;gap: 10px;margin: 20px 0;">
                        <!-- @vue-ignore slot type comes from UserPortfolio at runtime -->
                        <UserPanel :user="slotProps.user" size="30" imgClass="userNormalIcon"/>
                        <!-- @vue-ignore slot type comes from UserPortfolio at runtime -->
                        <p>{{ slotProps.user.name }}</p>
                    </div>
                </template>
            </UserPortfolio>
        </div>        
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import UserPortfolio from '../../Profile/UserPortfolio.vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import { useLearningApi } from '@/composables/learningApi';
import type { LearningPortfolio } from '@/types/learning';
    const portfolio_list = ref<LearningPortfolio[]>([])
    const route = useRoute()
    const learningApi = useLearningApi()
    onMounted(() => {
        getPortfolios(-1)
    })
    const getPortfolios = async(id: number) => {
        const themeId = Array.isArray(route.params.lessonThemeId) ? route.params.lessonThemeId[0] : route.params.lessonThemeId
        if (!themeId) return
        const data = await learningApi.getPortfolioView(themeId, id)
        if(id == -1){
            portfolio_list.value = data
        }else{
            const record = data[0]
            if (!record) return
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
    height: 100%;
    overflow: hidden auto;
    gap: 25px;
}
</style>
