import axios from 'axios'
import { defineStore } from 'pinia'
import { ref } from 'vue'

interface PublicHolidayResponse {
    id: number
    date: string
    holiday_name: string
}

export interface PublicHolidayItem {
    date: Date
    name: string
}

export const usePublicHolidayStore = defineStore('publicHolidayStore', () => {
    const holidays = ref<PublicHolidayResponse[]>([])
    const fetched = ref(false)
    const fetching = ref(false)

    const ensureLoaded = async () => {
        if (fetched.value || fetching.value) {
            return
        }

        fetching.value = true

        try {
            const response = await axios.get('/public_holidays')
            holidays.value = Array.isArray(response.data) ? response.data : []
            fetched.value = true
        } catch (error) {
            console.error('Error fetching public holidays:', error)
        } finally {
            fetching.value = false
        }
    }

    const between = (start: Date, end: Date): PublicHolidayItem[] => {
        const startAt = start.getTime()
        const endAt = end.getTime()

        return holidays.value
            .filter((holiday) => {
                const holidayAt = new Date(`${holiday.date}T00:00:00`).getTime()

                return holidayAt >= startAt && holidayAt <= endAt
            })
            .map((holiday) => ({
                date: new Date(`${holiday.date}T00:00:00`),
                name: holiday.holiday_name,
            }))
    }

    return {
        holidays,
        fetched,
        fetching,
        ensureLoaded,
        between,
    }
})