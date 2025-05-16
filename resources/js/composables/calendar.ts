import { CalendarRecord, FacilityData } from "@/interface/calendarInterface";
import { Project } from "@/interface/projectInterface";
import axios from "axios";
import { computed, ref } from "vue";
const list = ref<FacilityData>({
    qualified_car: [],
    qualified_institution: [],
    zoom_value: []
})



const selectedDepartmentIds = ref<number[]>([])

const dragItem = ref<CalendarRecord | null>(null)

const departments = ref<Project[]>([])
export function useCalendar() {

    const setFacility = (index: keyof FacilityData, sub_index: number, value: boolean) => {
        list.value[index][sub_index].selected = value
    }

    const getFacilities = async () => {
        try {
            axios.post('/get_all_facilities').then(response => list.value = response.data)
        } catch (e) {}
    };

    const facilitiesList = computed(() => list.value)

    const getDepartments = async() => {
        try {
            await axios.get('/get_departments_calendar').then(res => departments.value = res.data)
        } catch (e) {}
    }

    const departmentsList = computed(() => departments.value)

    const selectedDepartment = computed(() => {
        return departments.value.filter((department) => {
            return selectedDepartmentIds.value.includes(department.id)
        })
    })

    const setSelectedDepartment = (id: number) => {
        if (selectedDepartmentIds.value.includes(id)) {
            selectedDepartmentIds.value = selectedDepartmentIds.value.filter((departmentId) => departmentId !== id)
        } else {
            selectedDepartmentIds.value.push(id)
        }
    }

    const draggingCalendar = computed(() => {
        return dragItem.value
    })

    const setDraggingCalendar = (item: CalendarRecord | null) => {
        dragItem.value = item
    }

    return {
        facilitiesList,
        setFacility,
        getFacilities,
        getDepartments,
        departmentsList,
        selectedDepartment,
        setSelectedDepartment,
        draggingCalendar,
        setDraggingCalendar
    }

}