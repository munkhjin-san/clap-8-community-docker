<template>
    <div class="admin-window">
        <div class="project-admin-nav">
            <div class="sub-tab-container project-admin-tabs">
                <div
                    v-for="item in visibleTabs"
                    :key="item.routeName"
                    :class="['sub-tab-item', { 'selected-sub-tab': route.name === item.routeName }]"
                    @click="router.push({ name: item.routeName })"
                >
                    {{ item.label }}
                </div>
                <div class="project-admin-more">
                    <div
                        class="sub-tab-item"
                        @click.stop="toggleMoreMenu"
                    >
                        設定
                    </div>
                    <Transition name="modalFade">
                        <div v-if="moreMenuOpen" class="project-admin-more__menu" @click.stop>
                            <button
                                v-for="item in secondaryTabs"
                                :key="item.routeName"
                                type="button"
                                :class="['project-admin-more__item', { 'project-admin-more__item--active': route.name === item.routeName }]"
                                @click="openSecondaryTab(item.routeName)"
                            >
                                {{ item.label }}
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
            <div v-if="showSearchBar" class="project-admin-nav__search">
                <PostSearchBar
                    className="newChatMemberSearch"
                    :customPlaceHolder="customPlaceHolder"
                    @search-start="(word) => {keywords = word}"
                />
            </div>
        </div>
        <router-view
            :keywords="keywords"
            :userList="userList"
            :mentorList="mentorList"
            v-model="selectedDate"
        >
        </router-view>
    </div>
</template>
<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import { useRoute, useRouter } from 'vue-router';
import { User } from '@/interface/globalInterface';
import { detailedDateOptions } from '@/utils/tools';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { useProject } from '@/composables/project';

const keywords = ref('')
const router = useRouter()
const route = useRoute()
const api = useApi()
const userList = ref<User[]>([])
const mentorList = ref([])
const selectedDate = ref({
    year: '',
    which_half: '',
    name: '',
    short_name: ''
})

type ProjectAdminRouteName =
  | 'projectlist'
  | 'mentorcontrol'
  | 'projecttypes'
  | 'checkitem-categories'
  | 'checkitems'
  | 'accountcontrol'

interface NavigationItem {
  routeName: ProjectAdminRouteName
  label: string
}

const primaryTabs: NavigationItem[] = [
  { routeName: 'projectlist', label: 'プロジェクト一覧' },
  { routeName: 'mentorcontrol', label: '人事考課管理' },
]
const secondaryTabs: NavigationItem[] = [
  { routeName: 'projecttypes', label: 'プロジェクト種別' },
  { routeName: 'checkitem-categories', label: 'チェックカテゴリ' },
  { routeName: 'checkitems', label: 'チェック項目' },
  { routeName: 'accountcontrol', label: '科目管理' },
]
const searchBarRoutes: Record<string, string> = {
  projectlist: 'プロジェクト検索',
  mentorcontrol: 'メンバーとメンター検索',
}

const moreMenuOpen = ref(false)
const activeSecondaryTab = computed(() => {
  const routeName = route.name as ProjectAdminRouteName | undefined
  return secondaryTabs.find(item => item.routeName === routeName) ?? null
})
const visibleTabs = computed(() => {
  return activeSecondaryTab.value ? [...primaryTabs, activeSecondaryTab.value] : primaryTabs
})
const showSearchBar = computed(() => {
  return Boolean(searchBarRoutes[route.name as string])
})
const customPlaceHolder = computed(() => {
  return searchBarRoutes[route.name as string] ?? ''
})

const toggleMoreMenu = () => {
  moreMenuOpen.value = !moreMenuOpen.value
}
const openSecondaryTab = (routeName: ProjectAdminRouteName) => {
  moreMenuOpen.value = false
  router.push({ name: routeName })
}
const closeMoreMenu = () => {
  moreMenuOpen.value = false
}

const { getProjects } = useProject()
onMounted(() => {
    const options = detailedDateOptions()
    const today = DateTime.now()
    const which_half = today.month >= 4 && today.month <= 10 ? 'first' : 'second'
    const fiscalYear = today.month >= 4 ? today.year : today.year - 1
    const year = fiscalYear.toString()
    const foundOption = options.find(option => option.year == year && option.which_half == which_half)
    if (foundOption) {
        selectedDate.value = foundOption
    }
    getProjects()
    document.addEventListener('click', closeMoreMenu)
})
onBeforeUnmount(() => {
    document.removeEventListener('click', closeMoreMenu)
})

const getSelectableUsers = async() => {
    const data = await api.post('/get_selectable_users', {params: selectedDate.value})
    userList.value = data.users
    mentorList.value = data.mentors
}

watch(() => selectedDate.value, () => {
    getSelectableUsers()
})
watch(() => route.name, () => {
    keywords.value = ''
    closeMoreMenu()
})

provide('refresh', getSelectableUsers)
</script>
<style scoped>
    .project-admin-nav{
        margin: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .project-admin-tabs{
        margin-bottom: 0;
        flex-wrap: wrap;
    }
    .project-admin-more{
        position: relative;
    }
    .project-admin-more__menu{
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        min-width: 180px;
        background: var(--background-color);
        border: 1px solid var(--calendarBorder);
        box-shadow: #3c40434d 0 1px 2px, #3c404326 0 2px 6px 2px;
        z-index: 21;
        display: flex;
        flex-direction: column;
        padding: 6px 0;
    }
    .project-admin-more__item{
        min-height: 38px;
        padding: 0 14px;
        background: transparent;
        border: 0;
        text-align: left;
        font-size: 13px;
        cursor: pointer;
        color: var(--primary-color);
    }
    .project-admin-more__item:hover,
    .project-admin-more__item--active{
        background: var(--bg3);
    }
    .project-admin-nav__search{
        display: flex;
        width: 30%;
    }
    @media (max-width: 959px) {
        .project-admin-nav{
            margin: 16px;
        }
        .project-admin-tabs{
            gap: 8px;
        }
        .project-admin-tabs :deep(.sub-tab-item){
            flex: 1 1 calc(50% - 8px);
            min-width: 0;
        }
        .project-admin-more{
            flex: 1 1 calc(50% - 8px);
        }
        .project-admin-more__menu{
            left: 0;
            right: 0;
            min-width: 0;
        }
    }
</style>
