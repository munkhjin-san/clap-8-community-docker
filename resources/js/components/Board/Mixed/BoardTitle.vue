<template>
    <div >
        <p :class="titleClass" :style="titleStyle">{{boardTitle}}</p>
    </div>
</template>

<script>
    export default {
        props: ['item', 'titleClass', 'titleStyle'],
        
        computed:{
            boardTitle(){            
                if(this.item.private_flag == 1 && this.item.board_to_users.length == 2){
                    var coresspondId = this.item.board_to_users.filter(obj => obj.user_id !== this.$store.state.user.id);
                    if(coresspondId && coresspondId.length && coresspondId[0].user){
                        return coresspondId[0].user.name;
                    }else{
                        return this.$t('unAvailableUserName')
                    }
                    
                }else if(this.item.private_flag == 3){
                    return this.$t('myChat')
                }else{
                    return this.item.title;
                }           
            }, 
        }
    }
</script>
