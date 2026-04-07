import axios from "axios";
import { useAuthUserStore } from "../store/auth";
import { useFilePreview } from "../store/filePreview";
import { DateTime } from "luxon";
export const getWorkGroup = async () => {
    try {
        const auth = useAuthUserStore()
        const response = await axios.post('/get_work_group', {id: auth.activeUser.id})
        return response.data
    } catch (e: any) {
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
    } catch (e: any) {
        throw new Error(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const fetchData = async(url: string, yearMonth: string, checkedUsers: number[], selectedVehicles?: number[], shift_type?: number) => {
    try {
        const params = {
            current_date : yearMonth,
            work_group: checkedUsers,
            vehicles: selectedVehicles,
            shift_type: shift_type
        }
        const response = await axios.get(url, {params: params})
        return response.data
    } catch (e: any) {
        throw new Error(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
export const getWorkData = async(yearMonth: string, checkedUsers: number[]) => {
    return fetchData('/get_work_data', yearMonth, checkedUsers)
}

export const getShiftDataTable = async(yearMonth: string, checkedUsers: number[], selectedVehicles: number[]) => {
    return fetchData('/get_shift_data_table', yearMonth, checkedUsers, selectedVehicles)
}

export const getAttendanceData = async(yearMonth: string, checkedUsers: number[]) => {
    return fetchData('/get_attendance_data', yearMonth, checkedUsers)
}

export const getShiftData = async(yearMonth: string, checkedUsers: number[], shift_type?: number) => {
    return fetchData('/get_shift_data', yearMonth, checkedUsers, [], shift_type)
}

export const getShiftWithWorkGroup = async(yearMonth: string, checkedUsers: number[]) => {
    return fetchData('/get_shift_with_work_group', yearMonth, checkedUsers)
}

export const workFilePreview = (file: string, type: string, base: string) => {
    const filePreview = useFilePreview()
    const file_path = `${base}/${file}`
    let target_data: any
    if(type == 'image'){
        target_data = {
            extension: 'webp',
            mime_type: 'image',
            file_path: file_path,
            name: file
        }
    } else {
        target_data = {
            extension: 'pdf',
            mime_type: 'application',
            file_path: file_path,
            name: file
        }
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

export const dateDetail = (value: string | Date) => {
    const now = DateTime.local();
    const taskDate = DateTime.fromISO(typeof value === 'string' ? value : value.toISOString());

    const thisYear = now.year;
    const taskYear = taskDate.year;

    return (thisYear === taskYear)
        ? taskDate.setLocale('ja').toFormat('M/d (ccc)')
        : taskDate.setLocale('ja').toFormat('yyyy/M/d (ccc)');
};

export const vehicleAsOptions = ([
    { label: '福岡582く5617 ホンダライフ', value: 0},
    { label: '福岡582え8686 ダイハツミラ', value: 1},
    { label: '福岡580と5654 オッティ', value: 2},
    { label: '福岡480わ3206 クリッパー', value: 3},
    { label: '福岡480ね5019 バン', value: 4},
    { label: '福岡480ね5020 バン', value: 5},
    { label: '鹿児島582そ6650 ミライース', value: 6},
    { label: '福岡582ち7350', value: 7},
    { label: 'なにわ502の1116', value: 8},
    { label: '大阪581わ707（ﾚﾝﾀｶｰ）', value: 9},
    { label: '福岡582て7672', value: 10},
    { label: '長崎581つ9501', value: 11},
    { label: '福岡582た8963', value: 12},
    { label: '大分581な4912', value: 13},
    { label: '鹿児島582そ8143', value: 14},
    { label: 'レンタカー', value: 15},
    { label: 'マイカー', value: 16},
    { label: '弘前580い7009', value: 17},
    { label: '弘前580い7008', value: 18},
    { label: '仙台580よ8134', value: 19},
    { label: '郡山580け8503', value: 20},
    { label: '愛媛581な1880', value: 22},
    { label: '盛岡580さ6353', value: 23},
    { label: '福岡582そ1234', value: 24},
    { label: '仙台580ひ6191', value: 25},
    { label: 'なにわ581き9917', value: 27},
    { label: '久留米581と3345', value: 28},
])