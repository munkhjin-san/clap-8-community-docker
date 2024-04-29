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
                    <div v-if="menu.id == 199 && menu.name == 'shiftApproveSelector'" id="shiftApproveSelector" class="workMemberSelector" style="width: min-content; left:0; top:40px;">
                        <div id="checkUserSelecter" style=" max-height: 50vh; overflow-y: auto;">                
                            <div v-if="work_users.length">
                                <div style="padding:0 15px;display:flex;">                                
                                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                        <input @change="selectAll" :checked="work_users.length && work_users.length == checkedUsers.length"  name="memberCheckBox" type="checkbox">
                                        <span class="cal-check-mark" style="top: 13px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                                    
                                            <p class="userName" style="line-height: 30px;margin-left: 0;">全員選択</p>                                    
                                        </div>
                                    </label>  
                                </div>    
                                <div :key="user.id" v-for="user in work_users" style="padding:0 15px;display:flex;">                                
                                    <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                        <input v-model="checkedUsers" :value="user.id" name="memberCheckBox" type="checkbox">
                                        <span class="work-check-mark" style="top: 10px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                            <UserIcon :disable-instant="true" size="30" :title="user.name" :user="user" imgClass="userNormalIcon"/>                      
                                            <p class="userName">{{user.name}}</p>                                    
                                        </div>
                                    </label>  
                                    
                                </div>
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
                                        <span :class="getShiftClass(shiftCheck(shift, user.id)?.shift_type)">{{ shiftCheck(shift, user.id)?.shift_type?.abbreviation }}</span><span>{{statuses[shiftCheck(shift, user.id)?.status_flag]}}</span>
                                    </div>
                                    <div v-if="authorityCheck(user, shiftCheck(shift, user.id)) && shiftCheck(shift, user.id)?.status_flag !== 1" class="authority-buttons">
                                        <CommandButton customClass="custom-padding" v-if="shiftCheck(shift, user.id)?.status_flag == 2" :buttons="[{name: '承認', value: 1}, {name: '差戻', value: 2}]" @select="(button) => button.value === 1 ? shiftApprove(shiftCheck(shift, user.id), 3) : shiftApprove(shiftCheck(shift, user.id))"/>
                                        <CommandButton customClass="custom-padding" v-else-if="shiftCheck(shift, user.id)?.status_flag == 3" :buttons="[{name: '取消'}]" @select="shiftApprove(shiftCheck(shift, user.id), 2)"/>
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
    import { inject, ref, computed, onMounted, watch } from 'vue';
    import moment from 'moment';
    import UserIcon from '../Board/Mixed/UserIcon.vue';
    import CommandButton from '../Global/CommandButton.vue';
    import MonthPicker from '../Global/MonthPicker.vue';
    import { useTheme } from '../../store/theme';
    import { useResponsive } from '../../store/responsive';
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
    const work_users = ref([])
    const loading = ref(0)
    const keywords = ref('')
    const checkedUsers = ref([])
    const statuses = ['', '', ' : 申請中', ' : 承認済']
    const { notify, confirm, info } = inject('dialog')
    onMounted(async() => {
        await getWorkGroups()
        const exist = work_users.value.filter(ob => props.usersCheckArray.includes(ob.id))
        checkedUsers.value = exist.map(ob => ob.id)
    })

    const filterGroups = computed(() => {
        return work_users.value.filter(user => checkedUsers.value.find(id => id == user.id))
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
    const shiftCheck = (shift, userId) => {
        return shift && shift.length && userId ? shift.find(val => val.user_id == userId) : null
    }
    const authorityCheck = (user, shift) => {
        return auth.activeUser.work_authority > user?.work_authority && shift
    }
    const getWorkGroups = async() => {
        let yearMonth = moment([approveYear.value, approveMonth.value]).format('YYYY-MM')
        try {
            const data = await axios.post('/get_shift_with_work_group', {year_month: yearMonth, user_ids: checkedUsers.value}).then(res => res.data)
            work_users.value = data.work_users
            shiftRecords.value = data.shift_records



        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            loading.value ++    
        }
    }
    const selectAll = (event) => {        
        checkedUsers.value = event.target.checked ? work_users.value.map(ob => ob.id) : []   
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
    .overstyle{
        width: fit-content;
        overflow: hidden;
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
            z-index: 1;
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