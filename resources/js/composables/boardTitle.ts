import { Board } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
import { DateTime } from 'luxon';
import { computed } from 'vue';

const auth = useAuthUserStore()

export function useBoardTitle(board?:Board) {
    if(!board){
        return '';
    }    
    const title = computed(():string => {     
        if(board.project ){
            return board.project.name
        }       
        if(board.private_flag == 1 && board.board_to_users.length == 2){
            const coresspondUser = board.board_to_users.find(obj => obj.user_id !== auth.id);
            return coresspondUser && coresspondUser.user && coresspondUser.user.name ? coresspondUser.user.name: '非アクティブユーザー';           
        }else if(board.private_flag == 3){
            return 'マイチャット'
        }
        
        else{
            return board.title ? board.title : '非アクティブユーザー';
        }           
    })
    return title.value && title.value !== null ? title.value : '非アクティブユーザー';
}