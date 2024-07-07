<template>
    <div style="height: 100%; overflow: hidden;position: relative;">
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
        <div class="admin-sub-c-bar">
            <PostSearchBar className="newChatMemberSearch" style="width:auto;" :searching="false"  @searchStart="(val) => keywords = val"/>   
            <div class="admin-work-header">
            <div style="display: flex;align-items: center;">
                <YearPicker 
                    :selectedYear="year"
                    @setDate="setDate"
                />
            </div>
        </div>
        </div>  

        
        <div class="overlay" v-if="open">
            <div class="chatCreate scrollable">
                <div class="recordFormTitle" style="z-index: 26;">
                    <div @click="open = false" class="cursor-pointer" style="margin: auto 0 auto auto;">
                        <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div>
                        名前: {{ editUser.name }}
                    </div>
                    <div>
                        当年度有休付与日: {{ editUser.work_temps ? editUser.work_temps.date : null }}
                    </div>
                    <div>
                        計画消化日数: {{ editUser.work_temps ? editUser.work_temps.planned_days : null }}
                    </div>
                    <div>
                        消化日数合計: {{ editUser.shift_records ? editUser.shift_records.length : null }}
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <table>
                            <tr>
                                <th>計画付与日</th>
                                <th>変更前</th>
                            </tr>
                            <tr v-for="shift in editUser.shift_records" :key="shift.id">
                                <td><input class="taskDateTimePicker" :class="[{'date-color' : theme.dark }]"  :value="shift.shift_day" type="date" @input="getShift($event.target.value, shift.id)"></td>
                                <td>{{ shift?.old_shift?.shift_day }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="saveShift" :loading="processing" :content="'保存する'"/>
                </div>
            </div>
        </div>
        <div style="height: calc(100% - 70px);overflow: hidden auto">        
            <table>
                <thead style="position:sticky; top: -1px;z-index: 1;">
                    <tr>
                        <th>名前</th>
                        <th>当年度有休付与日</th>
                        <th>計画消化日数</th>
                        <th>消化日数合計</th>
                        <th>計画付与日 / 変更前</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in filteredData" :key="user.id">
                        <td>{{ user.name }}</td>
                        <td>{{ user.work_temps ? user.work_temps.date : null}}</td>
                        <td>{{ user.work_temps ? user.work_temps.planned_days : null }}</td>
                        <td>{{ user.shift_records ? user.shift_records.length : null }}</td>
                        <td>
                            <div v-for="shift in user.shift_records" :key="shift.id">
                                <td>{{ shift.shift_day }}</td>
                                <td v-if="shift.old_shift">{{ shift?.old_shift?.shift_day }}</td>
                            </div>
                        </td>
                        <td>
                            <CommandButton :buttons="[{ title: '変更', action:() => changePlannedShifts(user) }]"/>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
    import CommandButton from '../../Global/CommandButton.vue';
    import YearPicker from '../../Global/YearPicker.vue'
    import LoaderButton from '../../Global/LoaderButton.vue';
    import { computed, inject, onMounted, ref } from 'vue';
    import { useTheme } from '@/store/theme';
    import PostSearchBar from '../../Post/PostSearchBar.vue';
    const keywords = ref('')
    const plannedShifts = ref([])
    const year = ref(new Date().getFullYear())
    const open = ref(false)
    const editUser = ref([])
    const processing = ref(false)
    const changedShifts = ref([])
    const theme = useTheme()
    const fetch = ref(0)
    const { notify } = inject('dialog')
    onMounted(async () => {
        await getPlannedShifts()
        fetch.value++
    })
    const filteredData = computed(() => {
        let result = plannedShifts.value.filter(user1 => {
            return Object.values(user1).some(val => 
                String(val).toLowerCase().includes(keywords.value)
            )
        })
        return result
    })
    const getPlannedShifts = async() => {
        plannedShifts.value = await axios.post('/get_planned_shifts', {year: year.value}).then(res => res.data)
    }
    const setDate = (val) => {
        year.value = val.year
        getPlannedShifts()
    } 
    const changePlannedShifts = (val) =>{
        open.value = true
        editUser.value = val
    }
    const getShift = (val, id) => {
        const existingShiftIndex = changedShifts.value.findIndex(s => s.id === id);

        if (existingShiftIndex !== -1) {
            changedShifts.value[existingShiftIndex].shift_day = val;
        } else {
            changedShifts.value.push({ id: id, shift_day: val });
        }
    }
    const saveShift = async() => {
        processing.value = true
        try {
            await axios.post('/change_planned_shifts', {shifts: changedShifts.value, userId: editUser.value.id})
            getPlannedShifts()
            open.value = false
            changedShifts.value = []
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
        processing.value = false
        
    }
</script>
<style scoped>

    .workRecords-button{
        color: #fff;
        background-color: var(--primary-button);
        padding: 5px 10px 5px 10px;
        font-size: 12px;
        line-height: 1.5;
    }
    .admin-work-header{
        display: flex;
        gap:20px;
    }
    table {
  
        border-collapse: collapse;
        width: 100%;
        font-size: 14px;
    }

    table td, table th {
        border: 1px solid var(--formBorder);
        padding: 8px;
    }

    table tr:nth-child(even){background-color:var(--bg3)}

    table th {
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: left;
        background-color: #363636;
        color: white;
    }
</style>