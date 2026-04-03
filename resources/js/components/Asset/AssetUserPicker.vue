<template>
    <div class="relative h-full">
        <button @click.stop="toggle" class="flex items-center px-2 cursor-pointer w-fit h-full" :class="{'!cursor-not-allowed pointer-events-none': disabled}">        
            <div v-if="selectedUsers.length" class="flex gap-2 flex-wrap">
                <UserPanel size="20" v-for="user in selectedUsers" :key="user.id" :user="user" :with-name="!(selectedUsers.length > 5 || responsive.mobile)" disable-instant/>
            </div>
            <div v-else class="text-[12px] text-[gray]">メンバー選択</div>
            <div>
                <Back class="rotate-[-90deg] ml-3" size="10"/>
            </div>
        </button>
        <Teleport defer to="#assetSort" :disabled="responsive.mobile ? false : true">
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
                                <button class="text-[12px] px-2 py-1 bg-[var(--bg3)] mb-2" @click="user = []">リセット</button>
                                <label v-for="resultUser in searchResult" :key="resultUser.id" class="cursor-pointer hover:bg-[var(--secondary-background)] p-2 flex items-center gap-2 rounded-md" >
                                    <input type="checkbox" id="assetMemberSelect" name="assetMemberSelect" class="custom-f-checkbox" :value="resultUser.id" v-model="user" />
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
        </Teleport>
    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import Back from '@/components/Icons/Back.vue';
import { useApi } from '@/composables/api';
import { useAsset } from '@/composables/asset';
import { User } from '@/interface/globalInterface';
import { useMenuStore } from '@/store/menu';
import { useResponsive } from '@/store/responsive';
import { computed, ref } from 'vue';


const user = defineModel<number[] | null>();

const props = defineProps<{
    disabled?: boolean
}>()

const menu = useMenuStore()

const selectedTab = ref<string>('self');
const searchName = ref<string>('');

const { userList } = useAsset()

const searchResult = computed(() => {
    if(!searchName.value.length) return userList.value;
    const totalList:User[] = userList.value;
    if(!searchName.value.length) return [];
    const lowerSearch = searchName.value.toLowerCase();
    return totalList.filter(user => {
        return (user.name && user.name.toLowerCase().includes(lowerSearch));
    }).filter((user, index, self) =>
        index === self.findIndex((u) => u.id === user.id)
    );
})

const selectedUsers = computed(() => {
    return userList.value.filter(u => user.value?.includes(u.id)) ?? [];
})

const responsive = useResponsive()

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
