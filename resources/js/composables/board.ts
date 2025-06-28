import { Board } from "@/interface/globalInterface";
import { useAuthUserStore } from "@/store/auth";
import { computed, ref } from "vue";
import { useRoute } from "vue-router";

const list = ref<Board[]>([]);


export function useBoardList() {
    
    const setList = (data: Board[]) => {
        list.value = data;
    }
    const boardList = computed(() =>  list.value)
    const route = useRoute();
    const openedBoard = computed(() => {
        
        const chatId = route.params.chatId;
        if (chatId) {
            const board = list.value.find((board) => board.id === Number(chatId));
            return board ? board : null;
        }
        return null;
    })
    const auth = useAuthUserStore()

    return { setList, boardList, openedBoard };
}