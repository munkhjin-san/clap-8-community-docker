<template>
    <div class="relative flex min-h-[30px] items-stretch">
        <button @click.stop="toggle" class="flex min-h-[30px] items-center px-2 cursor-pointer w-fit text-[var(--primary-color)]" :class="{'!cursor-not-allowed pointer-events-none': disabled}">        
            <div v-if="selectedUsers.length" class="flex gap-2 flex-wrap">
                <UserPanel size="20" v-for="user in selectedUsers" :key="user.id" :user="user" :with-name="!(selectedUsers.length > 5 || responsive.mobile)" disable-instant/>
                <span class="font-xs text-[gray]" v-if="selectedUsers.length > 5">+{{ selectedUsers.length - 5 }}</span>    
            </div>
            <div v-else class="text-[12px] text-[gray]">メンバー選択</div>
            <div>
                <Back class="rotate-[-90deg] ml-3" size="10"/>
            </div>
        </button>
        <Teleport defer :to="teleportTarget" :disabled="responsive.mobile ? false : true">
            <Transition name="slidePop">
                <div @click.stop @touchstart.stop :id="menuKey" v-if="menu.parent == menuKey" :class="[rightOrLeft === 'right' ? 'right-0' : 'left-0', { 'rounded-md': !square }]" class="max-w-[80vw] absolute top-full w-max max-h-[400px] bg-[var(--background-color)] border border-solid border-[var(--secondary-background)] shadow-lg overflow-auto z-[4]">
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
                                <div class="flex gap-3 items-center justify-around">
                                    <span class="text-[12px] text-[gray]">{{ selectedCountLabel }}</span>
                                    <CommandButton :buttons="commandButtons" />
                                </div>
                                
                                <label v-for="resultUser in searchResult" :key="resultUser.id" class="cursor-pointer hover:bg-[var(--secondary-background)] p-2 flex items-center gap-2" :class="{ 'rounded-md': !square }">
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
import CommandButton from './CommandButton.vue';


const user = defineModel<number[] | null>();

const props = withDefaults(defineProps<{
    disabled?: boolean
    users?: User[]
    teleportTarget?: string
    slice?: boolean
    selectAll?: boolean
    rightOrLeft?: 'right' | 'left'
    menuKey?: string
    square?: boolean
}>(), {
    users: undefined,
    teleportTarget: '#memberDropDown',
    slice: true,
    selectAll: true,
    rightOrLeft: 'right',
    menuKey: 'p-user-pick',
    square: false
})

const menu = useMenuStore()

const searchName = ref<string>('');

const pickerUsers = computed(() => props.users)

const searchResult = computed(() => {
    if (!pickerUsers.value) return [];
    if(!searchName.value.length) return pickerUsers.value;
    const totalList:User[] = pickerUsers.value;
    if(!searchName.value.length) return [];
    const lowerSearch = searchName.value.toLowerCase();
    return totalList.filter(user => {
        return (user.name && user.name.toLowerCase().includes(lowerSearch));
    }).filter((user, index, self) =>
        index === self.findIndex((u) => u.id === user.id)
    );
})
const allOptionIds = computed(() => pickerUsers.value?.map(option => option.id) ?? [])
const selectedCountLabel = computed(() => `${user.value?.length ?? 0}件選択中`)
const commandButtons = computed(() => {
    const buttons = [
        { title: 'リセット', action: () => { user.value = []; searchName.value = ''; menu.close() } }
    ]

    if (props.selectAll) {
        buttons.unshift({
            title: '全選択',
            action: () => {
                user.value = [...allOptionIds.value]
                searchName.value = ''
                menu.close()
            }
        })
    }

    return buttons
})
const selectedUsers = computed(() => {
    if (!pickerUsers.value || !user.value) return [];
    if(props.slice && user.value.length > 6){
        return pickerUsers.value.filter(u => user.value?.includes(u.id)).slice(0,6) ?? [];
    }
    return pickerUsers.value.filter(u => user.value?.includes(u.id)) ?? [];
})

const responsive = useResponsive()

const toggle = () => {
    if (props.disabled) return;
    if (menu.parent === props.menuKey) {
        menu.close();
    } else {
        menu.setMenu({ parent: props.menuKey });
    }
    
}

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
