<template>
    <div class="h-full">
        <div class="sub-tab-container p-5 bg-[var(--background-color)]" v-if="['project-overview-detail', 'project-overview-checkitems', 'project-overview-apply'].includes(route.name as string)">
            <router-link :to="{name: 'project-overview-detail'}" :class="{'selected-sub-tab': route.name === 'project-overview-detail'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">
                詳細
                <span v-if="checkItemConfirmBadge"
                    class="side-notification"
                    style="position: unset;"
                >
                    {{ checkItemConfirmBadge }}
                </span>
            </router-link>
            <!-- <router-link v-if="hasPrivilage" :to="{name: 'project-overview-apply'}" :class="{'selected-sub-tab': route.name === 'project-overview-apply'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">確認・申請</router-link> -->
            <router-link v-if="isManager || auth.isBoss || auth.isAdmin" :to="{name: 'project-overview-checkitems'}" :class="{'selected-sub-tab': route.name === 'project-overview-checkitems'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">
                確認事項
                <div class="flex relative items-center justify-center font-normal" title="コメントバッジ" v-if="projectReportBadge">
                    <svg fill="#F28C28" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 30.88051 24.9735">
                        <path d="M30.72814,8.8769c-.14532-.82959-.40253-1.64972-.77496-2.4184-.37347-.76801-.86078-1.48114-1.43018-2.11041-.56958-.63019-1.21985-1.17505-1.91077-1.64008-.69165-.46552-1.42749-.84625-2.17938-1.16577-1.5072-.63647-3.08105-1.02167-4.65607-1.25201C18.1997.06067,16.61914-.02142,15.04528.00464c-1.57648.02826-3.16119.16687-4.73059.47339-1.56677.30853-3.12598.77979-4.58923,1.52222-.73016.37158-1.43451.81073-2.08917,1.32697-.65393.51624-1.25677,1.11188-1.7735,1.78302-.51813.66943-.9433,1.41797-1.25366,2.21051-.31232.7923-.4989,1.63013-.57269,2.46863-.03809.41821-.04175.84344-.03156,1.24939.01123.41052.04254.82294.0976,1.23492.11224.82324.32281,1.6463.65656,2.427.33209.7807.78845,1.51337,1.34021,2.15607.55261.64252,1.19427,1.19592,1.88171,1.6568,1.37878.92578,2.68457,1.41705,4.21594,1.83752,1.40436.38562,3.01337.61237,4.42383.68085.11499.00562.22223.05609.29999.14099.35828.39093.73218.8374,1.12903,1.18121.52246.45294,1.09735.87909,1.70001,1.23297.59595.34991,1.21814.62427,1.8606.87347.67725.2442,1.7251.4682,2.2804.51007.54651.0412.61255-.37128.435-.73407s-.21918-.43036-.29242-.58905c-.07404-.16064-.14563-.32257-.21429-.48541-.13745-.3255-.26355-.65436-.37738-.98267-.09088-.26556-.22833-.73004-.30035-1.09607-.02545-.12921.06171-.25269.19214-.27081,1.26611-.17621,2.52991-.42755,3.77478-.80463.76044-.23096,1.51337-.50958,2.24554-.85553.73206-.34485,1.44232-.76208,2.10303-1.26599.65881-.50543,1.26453-1.10352,1.7677-1.78918.25061-.34308.4754-.70667.67157-1.0849.19421-.37921.35907-.77295.49432-1.17499.26868-.80518.41492-1.64044.46771-2.46826.05145-.82404.01685-1.66162-.12994-2.49219Z" />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-white text-[10px]">{{ projectReportBadge }}</span>
                </div>
                <!-- <span v-if="projectReportBadge"
                    class="side-notification side-notification--comment-only"
                    style="position: unset;"
                >
                    {{ projectReportBadge }}
                </span> -->
            </router-link>
        </div>
        <router-view v-slot="{ Component }">
            <keep-alive>
                <component 
                    :is="Component" 
                    :hasPrivilage="hasPrivilage"
                />
            </keep-alive>
        </router-view>
    </div>    
</template>
<script setup lang="ts">
import { useProject } from '@/composables/project';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
const route = useRoute()
const props = defineProps<{
    hasPrivilage: boolean
}>()
const { isManager, projectReportBadge, checkItemConfirmBadge } = useProject()
const auth = useAuthUserStore()
const badge = useBadgeStore()

</script>