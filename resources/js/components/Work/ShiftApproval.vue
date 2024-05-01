<template>
    <div class="work-modal" @mousedown="emit('closeModal')">
        <div class="work-modal-inner overstyle" @mousedown.stop>
            <Transition name="modalFade">
                <div class="work-loader" v-if="loading == 0">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div> 
            </Transition>
            <div class="recordFormTitle" style="z-index: 26; gap:30px;">
                <p style="font-size: 18px;">{{ approveYear }}年{{ approveMonth+1 }}月の勤怠予定承認</p>
                <div @click="emit('closeModal')" class="cursor-pointer" style="margin: auto 0 auto auto;">
                    <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
            <div style="margin: 10px 0 30px; display: flex; gap: 30px; position: relative;">
                <button style="margin: unset;" class="work-button" @click.stop="menu.setMenu( { id: 199, name: 'shiftApproveSelector'})">メンバー</button>
                <button style="margin: unset;" class="work-button" @click="approveAll">一括承認</button>
                <MonthPicker
                    :selectedMonth="approveMonth"
                    :selectedYear="approveYear"
                    :right="windowWidth < 425 ? 'auto' : '0'" 
                    @setDate="setDate"
                />
                <Transition name="modalFade">
                    <div v-if="menu.id == 199 && menu.name == 'shiftApproveSelector'" id="shiftApproveSelector" class="workMemberSelector" style="width: fit-content; left:0; top:40px;">
                        <div id="checkUserSelecter" style=" max-height: 50vh; overflow-y: auto;"> 
                            <div class="sub-tab-container">
                                <div @click="byWorkGroups = 0" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 0}]">メンバー</div>
                                <div @click="byWorkGroups = 1, checkedUsers = []" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 1}]">ワークグループ</div>
                            </div>
                            <div class="searchBarInner" style="height: 40px;margin: 10px 15px 0;width: auto;min-width: 270px"> 
                                <input @input="setKeyord" v-model="keywords" class="searchBarArea searchInputArea memberSearch" id="workMemberSearch" :placeholder="byWorkGroups == 0 ? 'ユーザー検索' : 'ワークグループ検索'" type="search" style="margin: auto 0 auto auto;width:100%;background:#fff"/>
                                <div style="position: absolute;left: 10px;display: flex;height: 30px;">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="margin: 7px auto auto auto;fill:#767676">
                                        <path d="M31.875 28.185c-0.034-0.444-0.159-0.888-0.376-1.275-0.102-0.194-0.239-0.387-0.387-0.547-0.171-0.194-0.239-0.251-0.342-0.353-0.752-0.752-1.526-1.492-2.278-2.232-0.387-0.376-0.763-0.74-1.15-1.116l-0.865-0.831-0.091-0.091c-0.034-0.034-0.080-0.068-0.125-0.102-0.080-0.068-0.171-0.137-0.262-0.194-0.729-0.49-1.651-0.626-2.471-0.376-0.148 0.046-0.285 0.091-0.421 0.159-0.068 0.034-0.148 0.023-0.205-0.034-0.251-0.262-0.854-0.9-1.139-1.207-0.057-0.068-0.068-0.159-0.011-0.228 0.717-0.911 1.275-1.902 1.697-2.938 0.592-1.469 0.888-3.029 0.888-4.589s-0.296-3.12-0.888-4.601c-0.592-1.469-1.492-2.847-2.676-4.043-1.173-1.196-2.54-2.095-4.009-2.688-1.469-0.604-3.029-0.9-4.589-0.9-1.549 0-3.109 0.296-4.578 0.9-1.469 0.592-2.847 1.492-4.031 2.688-1.184 1.184-2.084 2.562-2.676 4.031s-0.888 3.041-0.888 4.601 0.296 3.12 0.888 4.589c0.592 1.469 1.492 2.847 2.676 4.043s2.562 2.084 4.031 2.688c1.469 0.604 3.029 0.9 4.589 0.9s3.12-0.296 4.578-0.9c1.036-0.421 2.038-1.002 2.949-1.72 0.046-0.034 0.114-0.034 0.159 0.011 0.273 0.273 1.002 0.957 1.253 1.196 0.034 0.034 0.046 0.091 0.023 0.137-0.205 0.444-0.307 0.945-0.285 1.446 0.023 0.421 0.137 0.854 0.342 1.23 0.102 0.194 0.228 0.376 0.364 0.535 0.171 0.194 0.228 0.251 0.33 0.353 0.74 0.774 1.469 1.549 2.209 2.3l1.116 1.15 0.558 0.569 0.376 0.376c0.034 0.034 0.080 0.080 0.125 0.114 0.080 0.068 0.171 0.137 0.262 0.205 0.74 0.512 1.708 0.683 2.574 0.444 0.433-0.114 0.843-0.319 1.196-0.615 0.046-0.034 0.091-0.068 0.125-0.114l0.114-0.102 0.421-0.421c0.319-0.319 0.558-0.706 0.717-1.127s0.216-0.877 0.182-1.321zM15.795 21.159c-1.15 0.467-2.391 0.706-3.621 0.706s-2.46-0.239-3.621-0.706c-1.15-0.467-2.243-1.173-3.177-2.118-0.945-0.945-1.64-2.027-2.118-3.189-0.467-1.162-0.706-2.403-0.706-3.633 0-1.241 0.239-2.471 0.706-3.633s1.173-2.243 2.118-3.189c0.945-0.957 2.027-1.651 3.189-2.13 1.15-0.467 2.38-0.706 3.621-0.706 1.23 0 2.46 0.239 3.621 0.706 1.15 0.467 2.232 1.173 3.177 2.118v0c0.945 0.945 1.64 2.027 2.118 3.189 0.467 1.162 0.706 2.403 0.706 3.633 0 1.241-0.239 2.471-0.706 3.633s-1.173 2.243-2.118 3.189c-0.957 0.957-2.038 1.663-3.189 2.13zM29.153 28.823l-0.478 0.478c-0.057 0.057-0.137 0.091-0.216 0.114-0.159 0.046-0.342 0.011-0.478-0.080-0.011-0.011-0.034-0.023-0.046-0.034l-0.068-0.068-0.285-0.273-1.708-1.674c-0.763-0.752-1.526-1.48-2.3-2.221-0.239-0.239-0.251-0.239-0.319-0.342-0.057-0.080-0.091-0.182-0.102-0.285-0.034-0.205 0.046-0.433 0.182-0.592 0.125-0.159 0.364-0.399 0.558-0.535 0.273-0.194 0.604-0.125 0.797 0.068s1.697 1.754 2.061 2.141c0.74 0.763 1.48 1.537 2.232 2.289 0.239 0.239 0.239 0.239 0.285 0.33 0.034 0.068 0.057 0.159 0.068 0.239 0.011 0.159-0.057 0.319-0.182 0.444z"></path>
                                    </svg>
                                </div>
                            </div>               
                            <div v-if="searchUsers.length">
                                <div style="padding:0 15px;display:flex;" v-if="byWorkGroups == 0">                                
                                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                        <input @change="selectAll" :checked="searchUsers.length && searchUsers.length == checkedUsers.length"  name="memberCheckBox" type="checkbox">
                                        <span class="cal-check-mark" style="top: 13px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                                    
                                            <p class="userName" style="line-height: 30px;margin-left: 0;">全員選択</p>                                    
                                        </div>
                                    </label>  
                                </div>    
                                <div :key="group.id" v-for="group in searchUsers" style="padding:0 15px;display:flex;">
                                    <div v-if="group.members && group.members.length">
                                        <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                            <input :value="group.id" :checked="selectedGroups.includes(group.id)" @change="checkedUsers = group.members.map(ob => ob.id), selectGroup(group.id)" name="memberCheckBox" type="checkbox">
                                            <span class="work-check-mark" style="top: 13px;"></span>
                                            <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                                <p class="userName" style="line-height: 30px; margin-left: 0;">{{group.name}}</p>                                    
                                            </div>
                                        </label>
                                        <div v-if="selectedGroups.includes(group.id)" v-for="member in group.members" style="padding:0 15px 0 30px;display:flex;">
                                            <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                                <input v-model="checkedUsers" :value="member.id" name="memberCheckBox" type="checkbox">
                                                <span class="work-check-mark" style="top: 10px;"></span>
                                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                                    <UserIcon :disable-instant="true" size="30" :title="member.name" :user="member" imgClass="userNormalIcon"/>                      
                                                    <p class="userName">{{member.name}}</p>                                    
                                                </div>
                                            </label>
                                        </div>
                                    </div>                                
                                    <div v-else>
                                        <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                            <input v-model="checkedUsers" :value="group.id" name="memberCheckBox" type="checkbox">
                                            <span class="work-check-mark" style="top: 10px;"></span>
                                            <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                                <UserIcon :disable-instant="true" size="30" :title="group.name" :user="group" imgClass="userNormalIcon"/>                      
                                                <p class="userName">{{group.name}}</p>                                    
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div> 
                            <div v-else-if="loading != 0 && byUserOrGroup.length" style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center;white-space: nowrap;font-size: 13px;padding: 30px;">
                                検索結果はありません。
                            </div>
                            <div v-else-if="loading != 0" style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center;white-space: nowrap;font-size: 13px;padding: 30px;">
                                現在予定申請中のメンバーはいません。
                            </div>                          
                        </div>
                    </div>
                </Transition>
            </div>
            <div v-if="loading !== 0 && filterGroups.length" style="height: calc(100% - 128px); overflow: auto;">
                <table style="width: 100%;">
                    <thead>
                        <th>
                            日付
                        </th>
                        <th v-for="user in filterGroups">
                            <div style="display: flex; align-items: center; gap:10px; padding: 10px;">
                                <div>{{ user.name }}</div>
                            </div>
                        </th>
                    </thead>
                    <tbody>
                        <tr v-for="shift, index in shiftRecords">
                            <td :class="[getDayClass(index)]">
                                {{ dayFormatter(index) }}
                            </td>
                            <td v-for="user in filterGroups">
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <div>
                                        <span 
                                            :class="getShiftClass(shift[user.id]?.old_shift?.shift_type)"
                                            v-if="shift[user.id]?.old_shift"
                                        >
                                            {{ shift[user.id]?.old_shift?.shift_type.abbreviation }} ➞
                                        </span>
                                        <span :class="getShiftClass(shift[user.id]?.shift_type)">
                                            {{ shift[user.id]?.shift_type?.abbreviation }}
                                        </span>
                                        <span>{{statuses[shift[user.id]?.status_flag]}}</span>
                                    </div>
                                    <div v-if="authorityCheck(user, shift[user.id]) && shift[user.id]?.status_flag !== 1" class="authority-buttons">
                                        <CommandButton customClass="custom-padding" v-if="shift[user.id]?.status_flag == 2" :buttons="[{name: '承認', value: 1}, {name: '差戻', value: 2}]" @select="(button) => button.value === 1 ? shiftApprove(shift[user.id], 3) : shiftApprove(shift[user.id])"/>
                                        <CommandButton customClass="custom-padding" v-else-if="shift[user.id]?.status_flag == 3" :buttons="[{name: '取消'}]" @select="shiftApprove(shift[user.id], 2)"/>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else-if="loading != 0" style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center;">
                メンバーを選択してください。
            </div>
        </div>
    </div>
</template>
<script setup>
    import { inject, ref, computed, onMounted } from 'vue';
    import moment from 'moment';
    import UserIcon from '../Board/Mixed/UserIcon.vue';
    import CommandButton from '../Global/CommandButton.vue';
    import MonthPicker from '../Global/MonthPicker.vue';
    import { useAuthUserStore } from '../../store/auth';
    import holiday_jp from '@holiday-jp/holiday_jp'
    import { useMenuStore } from '../../store/menu';
    const props = defineProps([
        'selectedYear',
        'selectedMonth',
        'workGroups',
        'usersCheckArray'
    ])
    const emit = defineEmits([
        'closeModal'
    ])
    const nextMonthOrCurrent = computed(() => {
        const now = moment()
        if(now.date() >= 25){
            return props.selectedMonth + 1
        } 
        return props.selectedMonth
    })
    const menu = useMenuStore()
    const approveYear = ref(props.selectedYear)
    const approveMonth = ref(nextMonthOrCurrent.value)
    const shiftRecords = ref([])
    const auth = useAuthUserStore()
    const workUsers = ref([])
    const workGroups = ref([])
    const loading = ref(0)
    const keywords = ref('')
    const checkedUsers = ref([])
    const byWorkGroups = ref(0)
    const selectedGroups = ref([])
    const statuses = ['', '', ' : 申請中', ' : 承認済']
    const { notify, confirm, info } = inject('dialog')
    onMounted(async() => {
        await getWorkGroups()
        const exist = workUsers.value.filter(ob => props.usersCheckArray.includes(ob.id))
        checkedUsers.value = exist.map(ob => ob.id)
    })

    const filterGroups = computed(() => {
        return workUsers.value.filter(user => checkedUsers.value.find(id => id == user.id))
    })
    const setKeyord = (event) => {
        keywords.value = event.target.value
    }
    const searchUsers = computed(() => {
        if(keywords.value && Array.isArray(byUserOrGroup.value)){
            let lowSearch = keywords.value.toLowerCase()
            return byUserOrGroup.value.filter(user => 
                Object.values(user).some(val => 
                    String(val).toLowerCase().includes(lowSearch)
                )
            )
        }else{         
            return byUserOrGroup.value
        }
    })
    const byUserOrGroup = computed(() => {
        if(byWorkGroups.value == 0){
            return workUsers.value
        } else {
            return workGroups.value
        }
    })
    const getDayClass = (date) => {
        const day = moment(date).day()
        return {
            'shift-saturday': day === 6,
            'shift-sunday': day === 0,
            'shift-everyholiday' : holiday(date),
            'today' : date === props.currentDay
        }
    }
    const getShiftClass = (shift) => {
        return shift && [0,5,14,15,16,3].includes(shift?.id) ? 'shift-sunday' : ''
    }
    const holiday = (day) => {
        const holidays = holiday_jp.between(new Date(props.selectedYear + '-01-01'), new Date(props.selectedYear + '-12-31'));
        return holidays.find(h => moment(h.date).isSame(day, 'day'));
    }
    const dayFormatter = (value) => {
        if(value){
            const date =  moment(value).format('M / D (dd)')
            return date
        }
    }
    const authorityCheck = (user, shift) => {
        return auth.activeUser.work_authority > user?.work_authority && shift
    }
    const getWorkGroups = async() => {
        let yearMonth = moment([approveYear.value, approveMonth.value]).format('YYYY-MM')
        try {
            const data = await axios.post('/get_shift_with_work_group', {year_month: yearMonth, user_ids: checkedUsers.value}).then(res => res.data)
            workUsers.value = data.work_users
            shiftRecords.value = data.shift_records
            workGroups.value = data.work_groups
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            loading.value ++    
        }
    }
    const selectAll = (event) => {        
        checkedUsers.value = event.target.checked ? searchUsers.value.map(ob => ob.id) : []   
    }
    const selectGroup = (groupId) => {
        const index = selectedGroups.value.indexOf(groupId);
        if (index !== -1) {
            selectedGroups.value.splice(index, 1);
            checkedUsers.value = []
        } else {
            selectedGroups.value = [groupId];
        }
    }
    const approveAll = async() => {
        if(!checkedUsers.value || !checkedUsers.value.length){
            notify('メンバーを選択してください。')
            return
        }
        const answer = await confirm('選択中メンバー全員の勤怠予定を纏めて承認します。<br>よろしいですか。')
        if(!answer) return
        const userIds = checkedUsers.value
        
        let yearMonth = moment([approveYear.value, approveMonth.value]).format('YYYY-MM')
        try {
            await axios.patch('/shift_approve_all', {user_ids: userIds, year_month: yearMonth}).then(res => res.data)
            info('承認しました。')
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            getWorkGroups()
        }
    }
    const shiftApprove = async(shift, status) => {
        if(!status){
            const answer = await confirm(`${shift?.shift_day}の勤怠予定を差戻します。よろしいでか。`)
            if(!answer) return
        }
        const shiftId = shift?.id
        try {
            await axios.patch('/shift_approve', {shift_id: shiftId, status: status}).then(res => res.data)
            info(status == 3 ? '承認しました。' : status == 2 ? '承認取消しました。' : '差戻しました。')
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            getWorkGroups()
        }
    }
    const setDate = (date) => {
        approveMonth.value = date.month - 1
        approveYear.value = date.year
        getWorkGroups()
    }
</script>
<style scoped lang="scss">
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
    .overstyle{
        width: fit-content;
        overflow: hidden;
        max-width: 90%;
    }
    table{
        font-size: 12px;
        background: var(--background-color);
        border-collapse: separate;
        border-spacing: 0;
        color: var(--primary-color);
        thead{
            text-align: center;
            width: 100px;
            background-color: #606060;
            font-size: 12px;
            color: #fff;
            z-index: 2;
            white-space: nowrap;
            height: 40px;
            position: sticky;
            top: 0;
            th{
                border-right: 1px solid var(--calendarBorder);
                border-left: none;
                border-top: none;
                text-align: center;
                font-weight: 400;
                vertical-align: middle;
            }
            th:first-child{
                border-left:1px solid var(--calendarBorder);
                position: sticky;
                left: 0;
                background-color: #606060;
            }
            
        }
        tbody{
            tr{
                td{
                    border-bottom: 1px solid var(--calendarBorder);
                    border-right: 1px solid var(--calendarBorder);
                    vertical-align: middle;
                    text-align: center;
                    box-sizing: border-box;
                    padding: 10px;
                    white-space: nowrap;
                }
                td:first-child{
                    border-left:1px solid var(--calendarBorder);
                    position: sticky;
                    left: 0;
                    background-color: var(--background-color);
                    z-index: 1;
                }
            }
        }
    }
    .user-wrapper{
        display: flex;
        margin: 10px 10px 10px 0;
        font-size: 13px;
        height: fit-content;
        align-items: center;
        border: solid thin var(--calendarBorder);
        padding: 5px;
        background-color: var(--background-color);
        color: var(--primary-color);
        cursor: pointer;
    }
    .user-wrapper input[type="radio"] {
        display: none;
    }

    .user-wrapper input[type="radio"] + label {  
        padding: 5px;
        cursor: pointer;
    }

    .selected {
        background-color: var(--bg2);
    }
    .authority-buttons {
        display: flex; 
        justify-content: center; 
        gap: 10px; 
        align-items: center;
        font-size: 12px;
    }
    ::-webkit-scrollbar {
        height: 4px;
    }
    @media (max-width: 959px) {
        .overstyle{
            width: 100%;
        }
    }
</style>