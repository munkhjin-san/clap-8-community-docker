<template>
    <div id="workMemberSelector" class="workMemberSelector" :style="customStyle">
        <div id="checkUserSelecter" class="selector-scroll">
            <div class="selector-sticky">
                <div class="sub-tab-container">
                    <div @click="byWorkGroups = 0" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 0 }]">メンバー</div>
                    <div @click="byWorkGroups = 1" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 1 }]">プロジェクト</div>
                    <div
                        v-if="auth.activeUser.id === 610 || auth.activeUser.id === 608"
                        @click="byWorkGroups = 2"
                        :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 2 }]"
                    >
                        車両
                    </div>
                </div>
                <div class="searchBarInner" style="margin: 10px 15px 0; width: auto; min-width: 270px;">
                    <PostSearchBar
                        className="newChatMemberSearch"
                        @search-start="(word) => { keywords = word }"
                        :custom-place-holder="placeHolder"
                    />
                </div>
                <div class="selector-actions">
                    <button
                        v-if="byWorkGroups == 0"
                        type="button"
                        class="selector-action-button selector-action-button-primary"
                        @click="selectAllMembers"
                    >
                        全員選択
                    </button>
                    <button
                        v-if="byWorkGroups == 2"
                        type="button"
                        class="selector-action-button selector-action-button-primary"
                        @click="selectAllVehicles"
                    >
                        全選択
                    </button>
                    <button
                        v-if="hasAnySelection"
                        type="button"
                        class="selector-action-button"
                        @click.stop="resetSelection"
                    >
                        リセット
                    </button>
                </div>
            </div>
            <div v-if="hasVisibleResults">
                <div v-if="byWorkGroups !== 2">
                    <div
                        v-for="group in displayUsers"
                        :key="group.id"
                        class="selector-line"
                        :class="{ 'selector-line-selected': isSelectedLine(group) }"
                    >
                        <div v-if="(group.members && group.members.length) || (group.manager && group.manager.length)">
                            <label
                                class="work-member-check selector-row"
                                style="align-self: center; padding-left: 30px; padding-bottom: 0; margin-bottom: 0;"
                            >
                                <input
                                    :value="group.id"
                                    :checked="selectedGroups.includes(group.id)"
                                    @change="value = groupMemberIds(group); selectGroup(group.id)"
                                    name="memberCheckBox"
                                    type="checkbox"
                                >
                                <span class="work-check-mark" style="top: 13px;"></span>
                                <div class="left-panel-items selector-row-copy" style="width: auto; padding: 5px 0; margin: 0; user-select: none; cursor: pointer; background: inherit;">
                                    <p class="userName" style="line-height: 30px; margin-left: 0;">{{ group.name }}</p>
                                </div>
                            </label>
                            <template v-if="selectedGroups.includes(group.id)">
                                <div
                                    v-for="member in groupMembers(group)"
                                    :key="member.id"
                                    class="selector-line selector-line-nested"
                                    :class="{ 'selector-line-selected': isSelectedMember(member.id) }"
                                >
                                    <label
                                        class="work-member-check selector-row"
                                        style="align-self: center; padding-left: 30px; padding-bottom: 0; margin-bottom: 0;"
                                    >
                                        <input v-model="value" :value="member.id" name="memberCheckBox" type="checkbox">
                                        <span class="work-check-mark" style="top: 10px;"></span>
                                        <div class="left-panel-items selector-row-copy member-row-body" style="width: auto; padding: 5px 0; margin: 0; user-select: none; cursor: pointer; background: inherit;">
                                            <UserPanel :disable-instant="true" :with-name="true" size="30" :title="member.name" :user="member" imgClass="userNormalIcon" />
                                        </div>
                                    </label>
                                </div>
                            </template>
                        </div>
                        <div class="w-full" v-else>
                            <label
                                class="work-member-check selector-row"
                                style="align-self: center; padding-left: 30px; padding-bottom: 0; margin-bottom: 0;"
                            >
                                <input v-model="value" :value="group.id" name="memberCheckBox" type="checkbox">
                                <span class="work-check-mark" style="top: 10px;"></span>
                                <div class="left-panel-items selector-row-copy member-row-body" style="width: auto; padding: 5px 0; margin: 0; user-select: none; cursor: pointer; background: inherit;">
                                    <UserPanel :disable-instant="true" size="30" :with-name="true" :title="group.name" :user="group" imgClass="userNormalIcon" />
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div
                        v-for="vehicle in displayVehicles"
                        :key="vehicle.value"
                        class="selector-line"
                        :class="{ 'selector-line-selected': isSelectedVehicle(vehicle.value) }"
                    >
                        <label
                            class="work-member-check selector-row"
                            style="align-self: center; padding-left: 30px; padding-bottom: 0; margin-bottom: 0;"
                        >
                            <input :value="vehicle.value" v-model="vehicles" name="memberCheckBox" type="checkbox">
                            <span class="work-check-mark" style="top: 13px;"></span>
                            <div class="left-panel-items selector-row-copy" style="width: auto; padding: 5px 0; margin: 0; user-select: none; cursor: pointer; background: inherit;">
                                <p class="userName" style="line-height: 30px; margin-left: 0;">{{ vehicle.label }}</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            <div v-else-if="byUserOrGroup.length" style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center; white-space: nowrap; font-size: 13px; padding: 30px;">
                検索結果はありません。
            </div>
            <div v-else style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center; white-space: nowrap; font-size: 13px; padding: 30px;">
                現在予定申請中のメンバーはいません。
            </div>
        </div>
    </div>
</template>
<script setup>
import PostSearchBar from '../Post/PostSearchBar.vue';
import UserPanel from '../Global/UserPanel.vue';
import { computed, ref } from 'vue';
import { vehicleAsOptions } from '@/utils/workApi';
import { useAuthUserStore } from '@/store/auth';

const props = defineProps(['workUsers', 'workGroups', 'customStyle'])
const auth = useAuthUserStore()
const byWorkGroups = ref(0)
const keywords = ref('')
const selectedGroups = ref([])
const value = defineModel('users')
const vehicles = defineModel('vehicles')
const openedSelectedUserIds = ref(Array.isArray(value.value) ? [...value.value] : [])
const openedSelectedVehicleIds = ref(Array.isArray(vehicles.value) ? [...vehicles.value] : [])

const placeHolder = computed(() => {
    return byWorkGroups.value === 1 ? 'プロジェクト検索' : byWorkGroups.value === 2 ? '車両検索' : 'メンバー検索'
})
const normalizedUsersModel = computed(() => Array.isArray(value.value) ? value.value : [])
const normalizedVehiclesModel = computed(() => Array.isArray(vehicles.value) ? vehicles.value : [])
const selectedUserLookup = computed(() => new Set(normalizedUsersModel.value))
const selectedVehicleLookup = computed(() => new Set(normalizedVehiclesModel.value))
const openedSelectedUserLookup = computed(() => new Set(openedSelectedUserIds.value))
const openedSelectedVehicleLookup = computed(() => new Set(openedSelectedVehicleIds.value))
const byUserOrGroup = computed(() => {
    if (byWorkGroups.value == 0) {
        return props.workUsers
    }

    return props.workGroups
})
const searchUsers = computed(() => {
    if (keywords.value && Array.isArray(byUserOrGroup.value)) {
        let lowSearch = keywords.value.toLowerCase()
        return byUserOrGroup.value.filter(user =>
            Object.values(user).some(val =>
                String(val).toLowerCase().includes(lowSearch)
            )
        )
    }

    return byUserOrGroup.value
})
const searchVehicles = computed(() => {
    if (keywords.value) {
        let lowSearch = keywords.value.toLowerCase()
        return vehicleAsOptions.filter(vehicle =>
            vehicle.label.toLowerCase().includes(lowSearch)
        )
    }

    return vehicleAsOptions
})
const prioritizeOpenedSelection = (list, matcher) => {
    const selected = []
    const normal = []

    list.forEach(item => {
        if (matcher(item)) {
            selected.push(item)
            return
        }

        normal.push(item)
    })

    return [...selected, ...normal]
}
const displayUsers = computed(() => {
    if (byWorkGroups.value === 0) {
        return prioritizeOpenedSelection(searchUsers.value, user => openedSelectedUserLookup.value.has(user.id))
    }

    return searchUsers.value
})
const displayVehicles = computed(() => {
    return prioritizeOpenedSelection(searchVehicles.value, vehicle => openedSelectedVehicleLookup.value.has(vehicle.value))
})
const hasVisibleResults = computed(() => {
    return byWorkGroups.value === 2 ? displayVehicles.value.length : displayUsers.value.length
})
const hasAnySelection = computed(() => {
    return normalizedUsersModel.value.length || normalizedVehiclesModel.value.length || selectedGroups.value.length
})
const groupMembers = (group) => {
    return [...(group?.manager || []), ...(group?.members || [])]
}
const groupMemberIds = (group) => {
    return groupMembers(group).map(member => member.id)
}
const isSelectedMember = (memberId) => {
    return selectedUserLookup.value.has(memberId)
}
const isSelectedVehicle = (vehicleId) => {
    return selectedVehicleLookup.value.has(vehicleId)
}
const isSelectedLine = (group) => {
    if ((group.members && group.members.length) || (group.manager && group.manager.length)) {
        return selectedGroups.value.includes(group.id)
    }

    return isSelectedMember(group.id)
}
const selectAllMembers = () => {
    value.value = searchUsers.value.map(ob => ob.id)
    selectedGroups.value = []
}
const selectAllVehicles = () => {
    vehicles.value = displayVehicles.value.map(vehicle => vehicle.value)
}
const resetSelection = () => {
    value.value = []
    vehicles.value = []
    selectedGroups.value = []
}
const selectGroup = (groupId) => {
    const index = selectedGroups.value.indexOf(groupId)
    if (index !== -1) {
        selectedGroups.value.splice(index, 1)
        value.value = []
    } else {
        selectedGroups.value = [groupId]
    }
}
</script>
<style scoped lang="scss">
    .selector-scroll{
        max-height: 50vh;
        overflow: hidden auto;
        background: var(--background-color);
    }
    .selector-sticky{
        position: sticky;
        top: 0;
        background: var(--background-color);
        z-index: 2;
        padding-bottom: 10px;
        box-shadow: 0 8px 14px -16px rgba(0, 0, 0, 0.7);
    }
    .selector-actions{
        display: flex;
        gap: 8px;
        padding: 10px 15px 0;
    }
    .selector-action-button{
        height: 28px;
        padding: 0 10px;
        border: 1px solid var(--calendarBorder);
        background: transparent;
        color: var(--primary-color);
        font-size: 12px;
        line-height: 28px;
        white-space: nowrap;
        cursor: pointer;
    }
    .selector-action-button-primary{
        background: var(--bg3);
    }
    .sub-tab-item{
        padding: 10px 15px;
        font-size: 14px;
        border-bottom: solid thin transparent;
        box-sizing: border-box;
        cursor: pointer;
    }
    .selected-sub-tab{
        border-bottom: solid thin var(--primary-color);
    }
    .sub-tab-container{
        display: flex;
    }
    .selector-line{
        display: flex;
        padding: 0 15px;
        background: var(--background-color);
    }
    .selector-line-nested{
        padding-left: 30px;
    }
    .selector-line-selected{
        background: var(--bg3);
    }
    .selector-row{
        display: flex;
        align-items: center;
        width: 100%;
        min-width: 0;
        transition: background-color 0.2s ease;
        box-sizing: border-box;
        padding-right: 10px;
    }
    .selector-row-copy{
        min-width: 0;
        flex: 1;
        padding-right: 10px;
    }
    .member-row-body{
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>
