<template>
    <div style="height: calc(100% - 60px); overflow: hidden auto;">
        <div class="admin-work-header">
            <div style="display: flex;align-items: center;">
                <YearPicker 
                    :selectedYear="year"
                    @setDate="setDate"
                />
            </div>
        </div>
        
        <table>
            <thead style="position:sticky; top: -1px;">
                <tr>
                    <th>名前</th>
                    <th>当年度有休付与日</th>
                    <th>計画消化日数</th>
                    <th>消化日数合計</th>
                    <th>消費された日付</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in plannedShifts" :key="user.id">
                    <td>{{ user.name }}</td>
                    <td>{{ user.work_temps ? user.work_temps.date : null}}</td>
                    <td>{{ user.work_temps ? user.work_temps.planned_days : null }}</td>
                    <td>{{ user.shift_records ? user.shift_records.length : null }}</td>
                    <td>
                        <div v-for="shift in user.shift_records" :key="shift.id">
                            {{ shift.shift_day }}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
    import YearPicker from '../Global/YearPicker.vue'
    import { onMounted, ref } from 'vue';
    const plannedShifts = ref([])
    const year = ref(new Date().getFullYear())
    onMounted(() => {
        getPlannedShifts()
    })
    const getPlannedShifts = () => {
        axios.post('/get_planned_shifts', {year: year.value}).then(res => {
            plannedShifts.value = res.data
        })
    }
    const setDate = (val) => {
        year.value = val.year
        getPlannedShifts()
    } 
</script>
<style scoped>
    .admin-work-header{
        position: absolute;
        top: 0;
        right: 15px;
        display: flex;
        gap:20px;
        height: 60px;
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