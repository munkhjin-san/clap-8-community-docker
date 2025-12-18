import { EmoteUser } from "@/interface/globalInterface";
import { ref } from "vue";
const emoteUsers = ref<EmoteUser[]> ([])
export function useModal() {
    const setEmoteUsers = (users: EmoteUser[]) => {
        console.log('setEmoteUsers', users);
        emoteUsers.value = users;
    };
    return {
        emoteUsers,
        setEmoteUsers,
    }
}