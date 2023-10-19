<template>
    <!-- <div id="leftPanel" class="left-panel" :style="{top: scrollableTop, transform: scrollableTransform, height: '-webkit-fill-available'}">    -->
    <div id="leftPanel" class="left-panel" :style="{height: 'calc(100% - 60px)'}">  
                            
        <div id="leftModal" style="height: 100%;display: flex;flex-direction: column;position:relative">                            
            

            
            <div id="searchContainer" class="left-panel-outer" @scroll="scrollListen">       
                           
                <div 
                    :key="item.id" 
                    @mouseenter="boardListDropEnterFromFile(item)" 
                    @mouseleave="boardListDropLeaveFromFile(item)" 
                    class="left-panel-inner cursor-pointer" 
                    v-for="(item) in pinnedBoards"
                >                  
                    <BoardItem 
                        :item="item"                        
                        :isOpened="isOpened(item.id)"
                        :openedBoard="openedBoard"
                        :active="true"
                        :hasFailedMessage="failedMessageLen(item.id)"
                        @openBoard="openBoard"
                        @pinBoard="pinBoard"
                    />
                </div>
                <div 
                    :key="item.id" 
                    @mouseenter="boardListDropEnterFromFile(item)" 
                    @mouseleave="boardListDropLeaveFromFile(item)" 
                    class="left-panel-inner cursor-pointer" 
                    v-for="(item) in unPinnedBoards"
                >
                    <BoardItem 
                        :item="item"                                               
                        :isOpened="isOpened(item.id)"
                        :openedBoard="openedBoard"
                        :active="true"
                        :hasFailedMessage="failedMessageLen(item.id)"
                        @openBoard="openBoard"
                        @pinBoard="pinBoard"
                        @setDetailedBoard="item => $emit('setDetailedBoard', item)"
                    />
                </div>
                
                <BoardCreateButton v-if="$store.state.user && $store.state.user.partner_flag !== 1" :createHidden="createHidden"/>
            </div>                           
            <SkeletonBoard v-if="skeletonBoard == 0 && ($route.params && !$route.params.chatId)"/>
                        
        </div>                    
    </div> 
</template>

<script>
import BoardItem from './BoardItem.vue'
import SkeletonBoard from './SkeletonBoard.vue'
import BoardCreateButton from './BoardCreateButton.vue'
    export default {
        props: ['list', 'openedBoard', 'skeletonBoard', 'failedMessagesList'],
        emits: ['reload', 'openBoard', 'setSearchView', 'setDetailedBoard', 'boardCreate', 'viewMembers', 'boardEdit', 'leaveBoard', 'delete'],
        data(){
            return{
                createSelector: false,
                scrollPosition: 0,
                createHidden: false,
                bounceId: null,
                respondLock: false
            }
        },
        components:{
            BoardItem,
            SkeletonBoard,
            BoardCreateButton
        },
        mounted() {
            
            setTimeout(() => {
                const el = document.getElementById('searchContainer')
                if(el){
                    el.scrollTo({top: this.$store.state.scrollRemember, left: 0})
                }
            },0)
                
            
        },
        unmounted(){
            
        },
        computed:{
            scrollableTransform(){
                const mobile = this.$store.state.mobile
                if(mobile){
                    return !this.createHidden ? 'translateY(0)' : 'translateY(-40px)'
                }else{
                    return 'translateY(0)'
                }
            },
            scrollableTop(){
                const mobile = this.$store.state.mobile
                if(mobile){
                    return !this.createHidden ? '90px' : '90px'
                }else{
                    return '90px'
                }
                // return '90px'
                
            },
            scollableHeight(){
                const mobile = this.$store.state.mobile
                if(mobile){
                    return !this.createHidden ? 'calc(100% - 90px)' : 'calc(100% - 50px)'
                }else{
                    return 'calc(100% - 90px)'
                }
            },
            pinnedBoards(){
                let res = []
                this.list.forEach((board, index) => {
                    
                    let users = board.board_to_users  
                    let pinned = users.filter( obj => obj.user_id == this.$store.state.user?.id)
                    if(pinned.length && pinned[0].pin_flag){
                        res.push(board)
                    }
                }); 
                return res
            },
            unPinnedBoards(){
                let res = []
                this.list.forEach((board, index) => {
                    let users = board.board_to_users      
                    let pinned = users.filter( obj => obj.user_id == this.$store.state.user?.id)
                    if(pinned.length && !pinned[0].pin_flag){                    
                        res.push(board)
                    }
                }); 
                return res
            },
        },
        methods:{
            boardListDropEnterFromFile(board){
                if(this.$store.state.mobile) return 
                this.bounceId = board.id
                if(this.$store.state.sharingData && this.$store.state.sharingData.drag){
                    if(!this.openedBoard || this.openedBoard.id !== board.id){                        
                        setTimeout(() => {
                            if(this.bounceId == board.id){
                                this.openBoard(board)
                            }
                        }, 400)
                        
                    }
                }
            },
            boardListDropLeaveFromFile(board){
                if(this.$store.state.mobile) return 
                if(this.$store.state.sharingData && this.$store.state.sharingData.drag){
                    this.bounceId = null
                }
            },
            scrollListen(){
                var percent = 100 * event.target.scrollTop / (event.target.scrollHeight - event.target.clientHeight);       
                if(event.target.scrollTop < 0){
                    this.createHidden = false
                    
                }else if(percent > 98){
                    
                    this.createHidden = true
                }else{                 
                    this.createHidden = event.target.scrollTop > this.scrollPosition
                    this.scrollPosition = event.target.scrollTop;
                }
                // const mobile = this.$store.state.mobile
                // if(mobile) this.$emit('setSearchView', !this.createHidden)
                // this.createHidden = event.target.scrollTop > this.scrollPosition                   
                // this.scrollPosition = event.target.scrollTop;
            },
            failedMessageLen(id){
                if(!this.failedMessagesList.length) return 0
                const list = this.failedMessagesList.filter(ob => ob.record_id == id)
                return list.length 
            },
            pinBoard(id){           
                axios.post('/pin_board_api', {group_id: id}).then(response => {
                    this.$emit('reload')
                    this.activeMenu = false; 
                });
            },
            openBoard(item){
                this.$emit('openBoard', item)               
            },
            isOpened(id){
                return this.openedBoard && this.openedBoard.id == id ? true : false
            },

        }
    }
</script>
