<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>チャットの詳細情報</p>
        </template>
        <template #content>
            <div class="flex flex-col">   
                <div class="divTable blueTable">
                    <div class="divTableBody">
                        <div class="divTableRow wrap-mb">
                            <div class="divTableCell mb-title">ID</div>
                            <span class="mobile">:</span>
                            <div class="divTableCell">{{board.id}}</div>
                        </div>
                        <div class="divTableRow wrap-mb">
                            <div class="divTableCell mb-title">タイトル</div>
                            <span class="mobile">:</span>
                            <div class="divTableCell">
                                <BoardTitle :item="board" titleStyle="word-break: break-all;white-space: break-spaces;"/>
                            </div>
                        </div>
                        <div class="divTableRow wrap-mb">
                            <div class="divTableCell mb-title">タイプ</div>
                            <span class="mobile">:</span>
                            <div class="divTableCell">{{ boardType }}</div>
                        </div>
                        <div v-if="board.private_flag == 0 " class="divTableRow">
                            <div class="divTableCell mb-title">管理者</div>
                            <div class="divTableCell">
                                <div class="member-out">                                    
                                    <div class="member" v-for="admin in admins">
                                        <router-link class="user-link" :to="`/user/${admin.id}`">{{admin.name}}</router-link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="divTableRow">
                            <div class="divTableCell mb-title">メンバー ({{members.length}})</div>
                            <div class="divTableCell">
                                <div class="member-out">
                                    <div class="member" v-for="member in members">
                                        <router-link class="user-link" :to="`/user/${member.id}`">{{member.name}}</router-link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="divTableRow  wrap-mb">
                            <div class="divTableCell mb-title">作成日</div>
                            <span class="mobile">:</span>
                            <div class="divTableCell">{{DateTime.fromISO(board.created_at).toLocaleString(DateTime.DATETIME_MED)}}</div>
                        </div>   
                        <div class="divTableRow  wrap-mb" v-if="board.user"> 
                            <div class="divTableCell mb-title">作成者</div>
                            <span class="mobile">:</span>
                            <div class="divTableCell">
                                <div class="member-out">
                                    <div class="member" v-if="board.user">
                                        <router-link class="user-link" :to="`/user/${board.user.id}`">{{board.user.name}}</router-link>
                                    </div>
                                    <div class="member" v-else>
                                        <p class="user-link">非アクティブユーザー</p>
                                    </div>
                                </div>
                            </div>
                        </div>                
                    </div>
                </div>    
            </div>   
        </template> 
    </Modal>     
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import BoardTitle from './Mixed/BoardTitle.vue';
import { DateTime } from 'luxon';
import Modal from '../Global/Modal.vue';

    const props = defineProps(['board'])
    const emit = defineEmits(['close'])
    const admins = computed(() => {
        return props.board.board_to_users.filter( ob => ob.admin_flag).map( ob => ob.user)
    })
    const members = computed(() => {
        return props.board.board_to_users.map( ob => ob.user)
    })
    const boardType = computed(() => {
        return props.board.private_flag == 0 ? 'グループチャット' : props.board.private_flag == 1 ? '個別チャット' : props.board.private_flag == 3 ? 'マイチャット' : ''
    })

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
    div.blueTable {
        border: none;
    }
    .detailed-window{
        width: 85%;
        max-width: 85%;
        max-height: calc(85% - 40px);
    }
    .divTableRow { 
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    .divTable.blueTable .divTableCell, .divTable.blueTable .divTableHead {
        border: none;
        padding: 5px;
        line-height: 1.8;
    }
    .divTableBody { 
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    .wrap-mb{
        flex-direction: row;
        align-items: center;
    }
    .mb-title{
        font-weight: 600;
    }
}
</style>
