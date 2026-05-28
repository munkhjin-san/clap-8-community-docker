<template>
    <div class="relative flex min-h-[35px] w-full items-stretch">
        <button @click.stop="toggle" class="temp-reserve-user-button">        
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
                <div @click.stop @touchstart.stop id="p-user-pick" v-if="menu.parent == 'p-user-pick'" class="max-w-[80vw] left-0 absolute top-full w-max max-h-[400px] bg-[var(--background-color)] border border-solid border-[var(--secondary-background)] shadow-lg rounded-md overflow-auto z-[4]">
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
                            <div v-if="searchResult.length">
                                <button class="text-[12px] px-2 py-1 bg-[var(--bg3)] mb-2" @click="selectedUsers = []">リセット</button>
                                <label v-for="resultUser in searchResult" :key="resultUser.id" class="cursor-pointer hover:bg-[var(--secondary-background)] p-2 flex items-center gap-2 rounded-md" >
                                    <input type="checkbox" id="assetMemberSelect" name="assetMemberSelect" class="custom-f-checkbox" :value="resultUser" v-model="selectedUsers" />
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
import { User } from '@/interface/globalInterface.js';
import { useMenuStore } from '@/store/menu';
import { useResponsive } from '@/store/responsive';
import { ref, computed } from 'vue';
const selectedUsers = defineModel<User[]>({default: []})
const { myGroupData } = useCalendar()
const menu = useMenuStore()
const searchName = ref('')
const responsive = useResponsive()
const searchResult = computed(() => {
    if(!searchName.value.length) return myGroupData.value?.all_members || [];
    const totalList:User[] = myGroupData.value?.all_members || [];
    if(!searchName.value.length) return [];
    const lowerSearch = searchName.value.toLowerCase();
    return totalList.filter(user => {
        return (user.name && user.name.toLowerCase().includes(lowerSearch));
    }).filter((user, index, self) =>
        index === self.findIndex((u) => u.id === user.id)
    );
})
const toggle = () => {
    if (menu.parent === 'p-user-pick') {
        menu.close();
    } else {
        menu.setMenu({ parent: 'p-user-pick' });
    }
    
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
    background-color: var(--secondary-background);
    color: var(--primary-color);
    cursor: pointer;
    box-sizing: border-box;
    overflow: hidden;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;

    &:hover,
    &:focus {
        border-color: var(--primary-color);
        background-color: var(--background-color);
        box-shadow: inset 0 0 0 1px var(--primary-color);
        outline: none !important;
    }
}
</style>
