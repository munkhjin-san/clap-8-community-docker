<template>
    <div class="overlay" @click="closeModal">            
        <div @click.stop ref="detailWindow" class="detailed-window">
            <div style="display:flex;flex-direction:column;overflow: hidden;">   
                <div class="divTable blueTable">
                    <div class="divTableBody">
                        <div class="divTableRow">
                            <div class="divTableCell">ID</div>
                            <div class="divTableCell">{{board.id}}</div>
                        </div>
                        <div class="divTableRow">
                            <div class="divTableCell">{{$t('chatTitle')}}</div>
                            <div class="divTableCell">
                                <BoardTitle :item="board" titleStyle="word-break: break-all;white-space: break-spaces;"/>
                            </div>
                        </div>
                        <div class="divTableRow">
                            <div class="divTableCell">{{$t('type')}}</div>
                            <div class="divTableCell">{{ boardType }}</div>
                        </div>
                        <div v-if="board.private_flag == 0 " class="divTableRow">
                            <div class="divTableCell">{{ $t('admin') }}</div>
                            <div class="divTableCell">
                                <div class="member-out">
                                    
                                    <div class="member" v-for="admin in admins">
                                        <router-link class="user-link" :to="`/profile/${admin.id}`">{{admin.name}}</router-link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="divTableRow">
                            <div class="divTableCell">{{ $t('members') }} ({{members.length}})</div>
                            <div class="divTableCell">
                                <div class="member-out">
                                    <div class="member" v-for="member in members">
                                        <router-link class="user-link" :to="`/profile/${member.id}`">{{member.name}}</router-link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="divTableRow">
                            <div class="divTableCell">{{ $t('createdDate') }}</div>
                            <div class="divTableCell">{{momentMessage}}</div>
                        </div>   
                        <div class="divTableRow" v-if="board.user"> 
                            <div class="divTableCell">{{ $t('createdBy') }}</div>
                            <div class="divTableCell">
                                <div class="member-out">
                                    <div class="member" v-if="board.user">
                                        <router-link class="user-link" :to="`/profile/${board.user.id}`">{{board.user.name}}</router-link>
                                    </div>
                                    <div class="member" v-if="else">
                                        <p class="user-link">{{$t('unAvailableUserName')}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>                
                    </div>
                </div>        
                <div style="margin: 20px auto 0;" @click.stop="$emit('close')" class="commentEditButton">{{ $t('close') }}</div>
                
            </div>
            
        </div>                                        
    </div> 
</template>

<script>
import moment from 'moment'
    export default {
        props:['board'],
        mounted() {
            
        },
        methods:{
            closeModal(){
                if (!this.$refs.detailWindow.contains(event.target)) {
                    this.$emit('close')
                }
                
                
            },
        },
        computed:{
            admins(){
                return this.board.board_to_users.filter( ob => ob.admin_flag).map( ob => ob.user)
            },
            members(){
                return this.board.board_to_users.map( ob => ob.user)
            },
            momentMessage () {
                moment.locale(this.$store.state.local);
                const date = this.board.created_at
                return moment(date).format('LLL')                    
            }, 
            boardType(){
                return this.board.private_flag == 0 ? this.$t('groupBoard') : this.board.private_flag == 1 ? this.$t('privateBoard') : this.board.private_flag == 3 ? this.$t('myChat') : ''
            }
        }
    }
</script>
<style lang="scss">

.member-out{
    display: flex;
    white-space: nowrap;
    flex-wrap: wrap;
    gap: 10px;

}
.member{
    padding: 0 10px;
    background: var(--bg2);
    border-radius: 50px;
    font-size: 13px;
    line-height: 25px;
}
div.blueTable {
    border: 1px solid var(--normalBorder);
    width: 100%;
    text-align: left;
    border-collapse: collapse;
    box-sizing: border-box;
}
.divTable.blueTable .divTableCell, .divTable.blueTable .divTableHead {
    border: 1px solid var(--normalBorder);
    padding: 10px 10px;
}
.divTable.blueTable .divTableBody .divTableCell {
    font-size: 13px;
}
.divTable.blueTable .divTableCell:nth-child(even) {
    background: var(--background-color);
}
.blueTable .tableFootStyle {
    font-size: 14px;
}
.blueTable .tableFootStyle .links {
	text-align: right;
}
.blueTable .tableFootStyle .links a{
    display: inline-block;
    padding: 2px 8px;
    border-radius: 5px;
}
.blueTable.outerTableFooter {
    border-top: none;
}
.blueTable.outerTableFooter .tableFootStyle {
    padding: 3px 5px; 
}
/* DivTable.com */
.divTable{ display: table; }
.divTableRow { display: table-row;line-height: 25px; }
.divTableHeading { display: table-header-group;}
.divTableCell, .divTableHead { display: table-cell;height: 100%;}
.divTableHeading { display: table-header-group;}
.divTableFoot { display: table-footer-group;}
.divTableBody { display: table-row-group;}
@media screen and (max-width: 959px) {
    .detailed-window{
        width: 100%;
        max-width: 100%;
        max-height: calc(100% - 40px);
    }
}
</style>
