import { Board } from "@/interface/globalInterface";
import { useAuthUserStore } from "@/store/auth";
import { computed, ref } from "vue";
import { useRoute } from "vue-router";

const list = ref<Board[]>([]);

const tempBoard = ref<Board | null>(null);

const cursor = ref<string | null>(null)
const end = ref(false)
const skeleton = ref(0)

export function useBoardList() {
    const setTempBoard = (data: Board | null) => {
        tempBoard.value = data;
    }
    
    const setList = (data: Board[]) => {
        list.value = data;
    }
    const setSkeleton = (data: number) => {
        skeleton.value = data;
    }
    const setNextCursor = (data: string | null) => {
        cursor.value = data
    }
    const setReachEnd = (data: boolean) => {
        end.value = data
    }
    const nextCursor = computed(() => cursor.value)
    const reachEnd = computed(() => end.value)
    const boardList = computed(() =>  list.value)
    const route = useRoute();
    const skeletonLoader = computed(() => skeleton.value);
    const openedBoard = computed(() => {
        if (tempBoard.value) {
            return tempBoard.value;
        }
        
        const chatId = route.params.chatId;
        if (chatId) {
            const board = list.value.find((board) => board.id === Number(chatId));
            return board ? board : null;
        }
        return null;
    })
    const auth = useAuthUserStore()

    return { setList, boardList, openedBoard, setTempBoard, setNextCursor, setReachEnd, nextCursor, reachEnd , skeletonLoader, setSkeleton };
}