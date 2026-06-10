<template>
    <div class="relative flex min-h-[35px] w-full items-stretch">
        <button @click.stop.prevent="toggle" @touchstart.stop.prevent="toggle" class="temp-reserve-user-button">        
            <div v-if="selectedUsers.length" class="flex flex-wrap">
                <UserPanel size="20" v-for="user in selectedUsers.slice(0, 3)" :key="user.id" :user="user" :with-name="!(selectedUsers.length > 1 || responsive.mobile)" disable-instant/>
                <div v-if="selectedUsers.length > 3" class=" text-[gray] ml-1">+{{ selectedUsers.length - 3 }}</div>
            </div>
            <div v-else class="text-[12px] text-[gray]">メンバー選択</div>
            <div>
                <Back class="rotate-[-90deg] ml-3" size="10"/>
            </div>
        </button>

            <Transition name="slidePop">
                <div
                    @click.stop
                    @touchstart.stop
                    id="p-user-pick"
                    v-if="menu.parent == 'p-user-pick'"
                    class="temp-reserve-user-picker-menu max-w-[80vw] left-0 absolute top-full w-max bg-[var(--background-color)] border border-solid border-[var(--bg3)] shadow-lg rounded-md overflow-auto z-[4]"
                >
                    <div class="sticky top-0 bg-[var(--background-color)] z-[2] p-3">                
                        <div class="flex w-full ">
                            <input 
                                name="asset-member-search-input" 
                                v-model="searchName" 
                                class="border border-solid border-[var(--formBorder)] px-3 py-2 w-full focus:border-[var(--primary-color)] text-[var(--primary-color)]" 
                                placeholder="メンバー検索" 
                                type="text"
                                @click.stop
                            />
                        </div>
                    </div>
                    <div class="px-3 pb-3">
                        <div>
                            <div v-if="!searchName.length && myGroups.length" class="temp-reserve-user-groups">
                                <div v-for="group in myGroups" :key="group.id" class="temp-reserve-user-group">
                                    <button class="temp-reserve-user-group-header" type="button" @click="toggleGroupOpen(group.id)">
                                        <Back size="10" :class="openedGroups.includes(group.id) ? '-rotate-90' : 'rotate-180'"/>
                                        <span class="temp-reserve-user-group-name">{{ group.name }}</span>
                                        <span class="temp-reserve-user-group-count">{{ groupSelectedCount(group) }}/{{ group.users.length }}</span>
                                    </button>
                                    <div v-if="openedGroups.includes(group.id)" class="temp-reserve-user-group-members">
                                        <label class="temp-reserve-user-check-row temp-reserve-user-check-row--all">
                                            <input
                                                type="checkbox"
                                                class="custom-f-checkbox"
                                                :checked="allGroupMembersSelected(group)"
                                                @change="toggleGroupMembers(group, $event)"
                                            />
                                            <span>全員選択</span>
                                        </label>
                                        <label v-for="groupUser in sortUsersWithSelf(group.users)" :key="groupUser.id" class="temp-reserve-user-check-row">
                                            <input
                                                type="checkbox"
                                                class="custom-f-checkbox"
                                                :checked="isUserSelected(groupUser)"
                                                @change="toggleUser(groupUser)"
                                            />
                                            <UserPanel size="25" disable-instant :user="groupUser" with-name/>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div v-if="searchResult.length">
                                <button class="text-[12px] px-2 py-1 bg-[var(--bg3)] mb-2" @click="selectedUsers = []">リセット</button>
                                <label v-for="resultUser in searchResult" :key="resultUser.id" class="temp-reserve-user-check-row" >
                                    <input
                                        type="checkbox"
                                        name="assetMemberSelect"
                                        class="custom-f-checkbox"
                                        :checked="isUserSelected(resultUser)"
                                        @change="toggleUser(resultUser)"
                                    />
                                    <UserPanel size="25" disable-instant :user="resultUser" with-name/>
                                </label>
                            </div>
                            <div v-else>
                                <div class="text-sm text-[gray] py-3 text-center">該当するメンバーが見つかりません</div>
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
import { useCalendar } from '@/composables/calendar';
import { CalendarGroup } from '@/interface/calendarInterface';
import { User } from '@/interface/globalInterface.js';
import { useAuthUserStore } from '@/store/auth';
import { useMenuStore } from '@/store/menu';
import { useResponsive } from '@/store/responsive';
import { ref, computed } from 'vue';
const selectedUsers = defineModel<User[]>({default: []})
const { myGroupData } = useCalendar()
const auth = useAuthUserStore()
const menu = useMenuStore()
const searchName = ref('')
const responsive = useResponsive()
const openedGroups = ref<number[]>([])
const selfId = computed(() => auth.activeUser?.id)
const myGroups = computed(() => myGroupData.value?.my_groups || [])
const sortUsersWithSelf = <T extends User>(users: T[]) => {
    if (!selfId.value) return users
    return [...users].sort((userA, userB) => {
        if (userA.id === selfId.value) return -1
        if (userB.id === selfId.value) return 1
        return 0
    })
}
const searchResult = computed(() => {
    if(!searchName.value.length) return sortUsersWithSelf(myGroupData.value?.all_members || []);
    const totalList:User[] = myGroupData.value?.all_members || [];
    if(!searchName.value.length) return [];
    const lowerSearch = searchName.value.toLowerCase();
    const matchedUsers = totalList.filter(user => {
        return (user.name && user.name.toLowerCase().includes(lowerSearch));
    }).filter((user, index, self) =>
        index === self.findIndex((u) => u.id === user.id)
    );
    return sortUsersWithSelf(matchedUsers)
})
const isUserSelected = (user: User) => selectedUsers.value.some(selectedUser => selectedUser.id === user.id)
const toggleUser = (user: User) => {
    if (isUserSelected(user)) {
        selectedUsers.value = selectedUsers.value.filter(selectedUser => selectedUser.id !== user.id)
        return
    }
    selectedUsers.value = [...selectedUsers.value, user]
}
const groupSelectedCount = (group: CalendarGroup) => {
    return group.users.filter(user => isUserSelected(user)).length
}
const allGroupMembersSelected = (group: CalendarGroup) => {
    return group.users.length > 0 && groupSelectedCount(group) === group.users.length
}
const toggleGroupOpen = (groupId: number) => {
    if (openedGroups.value.includes(groupId)) {
        openedGroups.value = openedGroups.value.filter(openedGroupId => openedGroupId !== groupId)
        return
    }
    openedGroups.value = [...openedGroups.value, groupId]
}
const toggleGroupMembers = (group: CalendarGroup, event: Event) => {
    const checked = (event.target as HTMLInputElement).checked
    const groupUserIds = new Set(group.users.map(user => user.id))
    if (!checked) {
        selectedUsers.value = selectedUsers.value.filter(user => !groupUserIds.has(user.id))
        return
    }
    selectedUsers.value = sortUsersWithSelf(group.users)
}
const toggle = () => {
    console.log('now',menu.parent)
    menu.toggle('p-user-pick')
    
}
</script>
<style lang="scss" scoped>
.temp-reserve-user-button {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    min-height: 32px;
    height: 32px;
    padding: 0 11px;
    border: solid 1px var(--calendarBorder);
    border-radius: 6px;
    background-color: var(--bg3);
    color: var(--primary-color);
    cursor: pointer;
    box-sizing: border-box;
    overflow: hidden;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;

    &:hover,
    &:focus {
        border-color: var(--primary-color);
        background-color: var(--background-color);
        /* box-shadow: inset 0 0 0 1px var(--primary-color); */
        outline: none !important;
    }
}

.temp-reserve-user-picker-menu {
    max-height: max(160px, min(400px, calc(100vh - 300px)));
    max-height: max(160px, min(400px, calc(100dvh - 300px)));
}

.temp-reserve-user-groups {
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: solid 1px var(--bg3);
}

.temp-reserve-user-group {
    border: solid 1px var(--bg3);
    border-radius: 6px;
    overflow: hidden;
    background-color: var(--bg3);

    & + & {
        margin-top: 6px;
    }
}

.temp-reserve-user-group-header {
    display: flex;
    align-items: center;
    gap: 8px;
    width: calc(100% - 18px);
    min-height: 32px;
    padding: 0 9px;
    color: var(--primary-color);
    background-color: var(--bg3);
    text-align: left;
    cursor: pointer;
}

.temp-reserve-user-group-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    font-size: 12px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.temp-reserve-user-group-count {
    color: gray;
    font-size: 11px;
}

.temp-reserve-user-group-members {
    padding: 4px;
}

.temp-reserve-user-check-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 8px;
    border-radius: 6px;
    cursor: pointer;

    &:hover {
        background-color: var(--bg3);
    }
}

.temp-reserve-user-check-row--all {
    min-height: 32px;
    color: gray;
    font-size: 12px;
}
</style>
