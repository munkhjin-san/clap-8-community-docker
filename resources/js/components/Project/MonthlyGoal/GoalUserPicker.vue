<template>
    <div class="relative">
        <button @click.stop="toggle" class="flex items-center border border-solid border-[var(--secondary-background)] text-[var(--primary-color)] px-2 py-1 cursor-pointer w-fit" :class="{'!cursor-not-allowed pointer-events-none': disabled}">        
            <div v-if="user">
                <UserPanel size="25" :user="user" with-name disable-instant/>
            </div>
            <div v-else class="text-sm text-[gray]">メンバーが選択されていません</div>
            <div>
                <Back class="rotate-[-90deg] ml-3" size="10"/>
            </div>
        </button>
        <Transition name="slidePop">
        <div id="p-user-pick" v-if="menu.parent == 'p-user-pick'" class="absolute top-full left-0 w-max max-h-[400px] bg-[var(--background-color)] border border-solid border-[var(--secondary-background)] shadow-lg rounded-md overflow-auto z-10">
            <div class="sticky top-0 bg-[var(--background-color)] z-[2] p-3">
                <div class="flex gap-3">
                    <label :key="tab.name" v-for="tab in tabs" class="py-2 text-[12px] cursor-pointer">
                        <input v-model="selectedTab" :value="tab.name" type="radio" class="hidden text-[12px]" />
                        <span>{{ tab.label }}</span>
                        <Transition name="shrink">
                            <div class="h-[1px] w-full bg-[var(--primary-color)] mt-2" v-if="selectedTab === tab.name"></div>
                        </Transition>
                    </label>
                </div>
                <div class="flex w-full ">
                    <input 
                        name="member-search-input" 
                        v-model="searchName" 
                        class="border border-solid border-[var(--formBorder)] px-3 py-2 w-full focus:border-[var(--primary-color)] text-[var(--primary-color)]" 
                        placeholder="メンバー検索" 
                        type="text"
                    />
                </div>
            </div>
            <div class="px-3 pb-3">
                <div v-if="searchName.length">
                    <div v-if="searchResult.length">
                        <label @click="menu.close()" v-for="resultUser in searchResult" :key="resultUser.id" class="cursor-pointer hover:bg-[var(--secondary-background)] p-2 flex items-center gap-2 rounded-md" >
                            <span v-if="isApprovalNeeded(resultUser)" class="rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5" title="承認対応が必要"></span>
                            <input type="radio" class="hidden" :value="resultUser" v-model="user" />
                            <UserPanel size="25" disable-instant :user="resultUser" with-name/>
                        </label>
                    </div>
                    <div v-else>
                        <div class="text-sm text-[gray] py-3 text-center">該当するメンバーが見つかりません</div>
                    </div>
                </div>
                <div v-if="!searchName.length">
                    <div v-if="baseUserList.length">
                        <label @click="menu.close()" v-for="selectableUser in baseUserList" :key="selectableUser.id" class="cursor-pointer hover:bg-[var(--secondary-background)] p-2 flex items-center gap-2 rounded-md" >
                            <span v-if="isApprovalNeeded(selectableUser)" class="rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5" title="承認対応が必要"></span>
                            <input type="radio" class="hidden" :value="selectableUser" v-model="user" />
                            <UserPanel size="25" disable-instant :user="selectableUser" with-name/>
                        </label>
                        <div v-if="menteeData.loading" class="flex justify-center items-center py-3">
                            <div class="spinner-mini"></div>
                        </div>
                        <div v-if="selectedTab === 'mentees' && menteeData.fetched && !menteeData.loading && !menteeData.users.length">
                            <div class="text-sm text-[gray] py-3 text-center">メンティーが見つかりません</div>
                        </div>
                    </div>
                    <div v-if="selectedTab === 'project_members'">
                        <div v-for="project in projectData.projects">
                            <label class="flex items-center px-3 my-3 py-1 cursor-pointer">
                                <Back class="rotate-[180deg] inline-block mr-2" :class="{'rotate-[270deg]' : openedProjects.includes(project.id)}" size="10"/>
                                <input v-model="openedProjects" type="checkbox" name="openedProjects" class="hidden" :value="project.id"/>
                                <div class="text-[14px]">{{ project.name }}</div>
                            </label>
                            <div v-if="openedProjects.includes(project.id)" class="pl-5">
                                <label @click="menu.close()" v-for="member in project.members" :key="member.id" class="cursor-pointer hover:bg-[var(--secondary-background)] p-2 flex items-center gap-2 rounded-md" >
                                    <span v-if="isApprovalNeeded(member)" class="rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5" title="承認対応が必要"></span>
                                    <input type="radio" class="hidden" :value="member" v-model="user" />
                                    <UserPanel size="25" disable-instant :user="member" with-name/>
                                </label>
                            </div>
                        </div>
                    </div>  
                </div>
            </div>
        </div>
        </Transition>
    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import Back from '@/components/Icons/Back.vue';
import { useApi } from '@/composables/api';
import { User } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';
import { useMenuStore } from '@/store/menu';
import { computed, onMounted, ref } from 'vue';


const user = defineModel<User | null>();

const props = withDefaults(defineProps<{
    disabled?: boolean
    year: number | string
    which_half: string
    approvalNeededIds?: (number | string)[]
}>(), {
    approvalNeededIds: () => [],
})

const auth = useAuthUserStore();
const tabs = ref<{ name: string; label: string }[]>([]);
const menu = useMenuStore()

const approvalNeededIdSet = computed(() => new Set((props.approvalNeededIds ?? []).map((id) => String(id))))
const isApprovalNeeded = (candidate: User) => approvalNeededIdSet.value.has(String(candidate.id))

onMounted(() => {
    tabs.value.push({ name: 'self', label: '自分' });
    if(auth.isBoss){
        tabs.value.push({ name: 'pms', label: 'PM' });
        tabs.value.push({ name: 'project_members', label: 'プロジェクトメンバー' });
        tabs.value.push({ name: 'mentees', label: 'メンティー' });
    }else if(auth.user.position_id == 6){
        tabs.value.push({ name: 'project_members', label: 'プロジェクトメンバー' });
    }
    if(auth.activeUser.general_position !== '一般職' && !auth.isBoss){
        tabs.value.push({ name: 'mentees', label: 'メンティー' });
    }
    if(auth.isBoss || auth.isAdmin){
        tabs.value.push({ name: 'all', label: '全員' });
    }
    if(tabs.value.length === 1){
        selectedTab.value = 'self';        
    }
    fetchInitialData();

})
const openedProjects = ref<number[]>([]);
const selectedTab = ref<string>('self');
const searchName = ref<string>('');
const projectData = ref({
    fetched: false,
    loading: false,
    projects: [] as Project[],
});

const menteeData = ref({
    fetched: false,
    loading: false,
    users: [] as User[],
});

const allUsersData = ref({
    fetched: false,
    loading: false,
    users: [] as User[],
});

const baseUserList = computed(() => {
    if(!selectedTab.value) return [];
    const collection: Record<string, User[]> = {
        'self': [auth.user],
        'mentees': menteeData.value.users,
        'pms': pmsData.value.users,
        'all': allUsersData.value.users,
    };
    return collection[selectedTab.value] || [];
})

const searchResult = computed(() => {
    const totalList:User[] = [... menteeData.value.users, ...pmsData.value.users, ...allUsersData.value.users, ...projectData.value.projects.flatMap(project => project.members)];
    if(!searchName.value.length) return [];
    const lowerSearch = searchName.value.toLowerCase();
    return totalList.filter(user => {
        return (user.name && user.name.toLowerCase().includes(lowerSearch));
    }).filter((user, index, self) =>
        index === self.findIndex((u) => u.id === user.id)
    );
})

const fetchInitialData = async () => {
    const names = tabs.value.map(tab => tab.name);
    if(names.length < 2) return;
    const data = await api.get('/user_related_goal_member_data', {
        year: props.year,
        which_half: props.which_half,
        by: names,
    });
    if(data){
        if(data.project_members){
            projectData.value.projects = data.project_members;    
            openedProjects.value = data.project_members.map((project: Project) => project.id);
            projectData.value.fetched = true;
        }
        if(data.mentees){
            menteeData.value.users = data.mentees;             
            menteeData.value.fetched = true;
        }
        if(data.pms){
            pmsData.value.users = data.pms;             
            pmsData.value.fetched = true;
        }
        if(data.all){
            allUsersData.value.users = data.all;             
            allUsersData.value.fetched = true;
        }
    }
}


const toggle = () => {
    if (props.disabled) return;
    if (menu.parent === 'p-user-pick') {
        menu.close();
    } else {
        menu.setMenu({ parent: 'p-user-pick' });
    }
    
}

const pmsData = ref({
    fetched: false,
    loading: false,
    users: [] as User[],
});
const api = useApi()

</script>
<style scoped>
.shrink-enter-active, .shrink-leave-active {
  transition: all 0.3s ease;
}
.shrink-enter-from, .shrink-leave-to {
  transform: scaleX(0);
  opacity: 0;
}

</style>
