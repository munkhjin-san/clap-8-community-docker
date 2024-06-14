import axios from "axios";
import { useAuthUserStore } from "../store/auth";
import { useFilePreview } from "../store/filePreview";

export const getWorkGroup = async () => {
    try {
        const auth = useAuthUserStore()
        const response = await axios.post('/get_work_group', {id: auth.activeUser.id})
        return response.data
    } catch (e) {
        throw new Error(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}

export const getCustomFields = async () => {
    try {
        const params = {
            app_name : 'work'
        };
        const response = await axios.post('/custom_field_data', params)
        return response.data
    } catch (e) {
        throw new Error(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const fetchData = async(url: string, yearMonth: string, checkedUsers: number[]) => {
    try {
        const params = {
            current_date : yearMonth,
            work_group: checkedUsers
        }
        const response = await axios.get(url, {params: params})
        return response.data
    } catch (e) {
        throw new Error(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
export const getWorkData = async(yearMonth: string, checkedUsers: number[]) => {
    return fetchData('/get_work_data', yearMonth, checkedUsers)
}

export const getShiftDataTable = async(yearMonth: string, checkedUsers: number[]) => {
    return fetchData('/get_shift_data_table', yearMonth, checkedUsers)
}

export const getAttendanceData = async(yearMonth: string, checkedUsers: number[]) => {
    return fetchData('/get_attendance_data', yearMonth, checkedUsers)
}

export const getShiftData = async(yearMonth: string, checkedUsers: number[]) => {
    return fetchData('/get_shift_data', yearMonth, checkedUsers)
}

export const getShiftWithWorkGroup = async(yearMonth: string, checkedUsers: number[]) => {
    return fetchData('/get_shift_with_work_group', yearMonth, checkedUsers)
}

export const workFilePreview = (file: string) => {
    const filePreview = useFilePreview()
    const file_path = `/cdn/timecard_files/${file}`

    let target_data = {
        extension: 'webp',
        mime_type: 'image',
        file_path: file_path,
        name: file
    }
    const data = {
        active: true,
        files: [target_data],
        source: 'work',
        index: 0,
        message: null,
    }
    filePreview.setFilePreview(data)
}