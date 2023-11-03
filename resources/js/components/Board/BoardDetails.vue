<template>
    <div class="overlay" @click="closeModal">            
        <div @click.stop ref="detailWindow" class="detailed-window" style="padding-top: 0;">
            <div class="recordFormTitle">
                <p>ボードの詳細情報</p>
                <div @click="$emit('close')" class="m-close-button" style="position: unset;margin-left: auto;">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>                
            </div>
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
                                        <router-link class="user-link" :to="`/user/${admin.id}`">{{admin.name}}</router-link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="divTableRow">
                            <div class="divTableCell">{{ $t('members') }} ({{members.length}})</div>
                            <div class="divTableCell">
                                <div class="member-out">
                                    <div class="member" v-for="member in members">
                                        <router-link class="user-link" :to="`/user/${member.id}`">{{member.name}}</router-link>
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
                                        <router-link class="user-link" :to="`/user/${board.user.id}`">{{board.user.name}}</router-link>
                                    </div>
                                    <div class="member" v-else>
                                        <p class="user-link">{{$t('unAvailableUserName')}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>                
                    </div>
                </div>        
                <!-- <div style="margin: 20px auto 0;" @click.stop="$emit('close')" class="commentEditButton">{{ $t('close') }}</div> -->
                
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
    white-space: nowrap;
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
        width: 85%;
        max-width: 85%;
        max-height: calc(85% - 40px);
    }
}
</style>
