import { User } from "@/interface/globalInterface";
import { computed, ref } from "vue";
import { useApi } from "./api";

const assetUsers = ref<User[]>([])
const api = useApi()
export function useAsset() {
    const setAssetUsers = (data: User[]) => {
        assetUsers.value = data;
    }

    const fetchAssetUsers = async(exclude: number[]) => {
        const response = await api.get('/get_asset_users', {     
            exclude     
        }) 
        assetUsers.value = response 
    }
    const userList = computed(() => assetUsers.value)

    return { setAssetUsers, userList, fetchAssetUsers };
}