<template>
<div class="taskOuterContainerBg">

    <!--<div class="pc" style="width:100%;display:flex;align-items:center;background:#000;color:#fff;height: 40px;font-size: 13px;min-height: 40px;">
        <p style="margin-left:15px;">ノート</p>
    </div>-->
    <div        
        :title="$t('createNew')" 
        :style="{bottom: '15px'}" 
        class="createBoardButton fileNewButton" 
        @click="memoCreateWindow = !memoCreateWindow"
        :class="{hiddenButton : createHidden}">
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
            <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
        </svg>        
    </div>
    <!--<MemoRecycleButton :createHidden="createHidden"/>-->
    <!--<MemoSortButton
        :fullScreen="fullScreen"
        :createHidden="createHidden"
        :footerMenuView="false"
        :sortIs="sortIs"
        @sortBy="sortBy"
    />-->
    
    <!--<Transition name="modalFade">
    <MemoCreate v-if="memoCreateWindow" @closeMe="memoCreateWindow = false"/>
    </Transition>-->
     <div style="height:100%;overflow: hidden scroll;position: relative;" @scroll.passive="flettyScroll">
        <Transition name="slidePop">
            <div v-if="copied" class="copySuccess">    
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 38 32" fill="#fff">
                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                </svg>
                <span>{{ $t('copied') }}</span>
            </div>
        </Transition>
        
        <div class="" style="position:relative;width:100%">
            <div class="no-comment-text" v-if="!memoList.length && !memoCreateWindow" style="font-size:14px;">
                <p>{{$t('noItemsCurrentlyAvailable')}}</p>
            </div>
            <div class="task-list-wrap">  
                <!--<Transition name="newMemoWindow">-->
                <div v-if="memoCreateWindow" class="newMemo memoBox" :style="{
                    border: isFocus ? 'solid 2px var(--primary-color)' : 'solid 2px #bfbfbf',
                }">
                    <!--<div class="task-box-header">
                        <div style="display:flex;width: fit-content;">
                            <div style="position:relative;">
                                <div class="column-01">
                                    <UserIcon size="15" :user="$store.state.user" imgClass="userSmallIcon"/>                               
                                </div>                                                         
                            </div>                                                                                      
                        </div> 
                    </div>  -->                  
                    <div 
                        @paste="pasteListener($event)"
                        @focus="focusOn" 
                        @blur="blurOn" 
                        @keyup.enter="addMemoShort"
                        id="newMemoType" 
                        contentEditable="true" 
                        style="white-space: break-spaces;line-height: 1.4;outline:none;display:inline-block;width: 100%;overflow-wrap: break-word;"></div>
                    <div style="display:flex;margin-top:10px">
                        <div @click="addMemo" class="commentEditButton">{{$t('save')}}</div>
                        <!--<div @click="createCancel" style="margin-left:10px;" class="commentEditButton">取消</div>   -->                     
                    </div>
                </div>  
                <!--</Transition>-->
                  
                <div >
                    <MemoBox 
                        @memoDrag="memoDrag"
                        v-for="item in memoData"
                        :key="masterKey + '_' + item.id"                        
                        :item="item" 
                        :editAble="editAble"
                        :myColor="myColor"
                        @setEditAble="setEditAble"
                        @editSend="editSend"
                        @deleteSend="deleteConfirm"
                        @shareTo="shareTo"
                        @copyText="copyText"/>
                </div>            
            </div> 
        </div>
    </div>
</div>
</template>
<style>


</style>
<script>
// import NotifyComponent from "../../NotifyComponent.vue";
import colors from '../../../../../assets/colors.json'
import MemoSortButton from "./MemoSortButton.vue";
import MemoRecycleButton from "./MemoRecycleButton.vue";
import MemoBox from './MemoBox'
export default {
    props: ["fullScreen", "ftSelector"],
    data() {
        return {
            memoList: [],
            plView: true,
            leftMenuActiveItem: 0,
            memoCreateWindow: false,
            isFocus: false,
            editAble: null,
            loading: false,
            masterKey: 950,
            createHidden: false,
            scrollPosition: 0,
            sortIs: {
                by: 'date',
                order: 'desc'
            },
            avialableColors: colors,
            copied: false
        };
    },
    mounted() {
        this.getMemo();
        if (this.$store.state.messageShareToMemo) {
            this.memoCreateWindow = true;
        }
        emitter.on("messageShareToMemo", (data) => {
            if (this.$store.state.messageShareToMemo) {
                this.memoCreateWindow = false;
                setTimeout(() => {
                    this.memoCreateWindow = true;
                }, 0);
            }
        });
    },
    created() {
        this.$store.commit("setFileInstance", null);
    },
    watch: {
        memoCreateWindow(after, before) {
            if (after) {
                setTimeout(() => {
                    const el = document.getElementById("newMemoType");
                    if (el) {
                        el.innerText = "";
                        el.focus();
                        if (this.$store.state.messageShareToMemo) {
                            document.execCommand("insertHTML", false, this.$store.state.messageShareToMemo.message_text);
                            this.$store.commit("setMessageShareToMemo", null);
                        }
                    }
                }, 0);
            }
            else {
                const el = document.getElementById("newMemoType");
                if (el) {
                    el.innerText = "";
                }
            }
        },
        ftSelector(after, before) {
            this.getMemo();
        }
    },
    computed: {
        myColor(){
            if(this.$store.state.user && this.avialableColors){
                const color = this.avialableColors.filter(ob => ob.id == this.$store.state.user.color)
                if(color.length){
                    return this.$store.state.dark ? color[0].dark : color[0].light
                }
                return ''
            }
            return ''
        },
        memoData(){
            if(this.sortIs.by == 'date'){
                return this.memoList
            }else if(this.sortIs.by == 'myMemo'){
                let my = [];
                let other = [];
                this.memoList.map(ob => {
                    if(ob.user_id == this.$store.state.user.id){
                        my.push(ob)
                    }else{
                        other.push(ob)
                    }
                })
                my.sort((a,b) => (a.created_at > b.created_at))
                other.sort((a,b) => (a.created_at > b.created_at))
                const res = my.concat(other)
                return res
            }
            
        },
        memoPathSwitcher() {
            return this.ftSelector == 0 ? this.$store.state.activeBoard : this.ftSelector == 1 ? this.$store.state.myBoard : null;
        },
        createButtonStyle() {
            var width = window.innerWidth
                || document.documentElement.clientWidth
                || document.body.clientWidth;
            return this.fullScreen || width > 959;
        }, 
    },  
    methods: {
        copyText(text){
            navigator.clipboard.writeText(text)
            .then(() => {
                this.copied = true
                setTimeout(() => {
                    this.copied = false
                }, 1500);
            })
            .catch((error) => {
                console.error('Unable to copy text to clipboard:', error);
                
            });
        },
        shareTo(to, item){
            if(to == 'current'){
                if(this.$store.state.mobile){
                    const data = {
                        active: true,
                        window: false,
                        memo: item,
                        drag: false
                    }
                    this.$store.commit('setSharingMemo', data)
                    this.$router.go(-1)
                }else{
                    emitter.emit('dropSharingMemo',item.content);
                }
            }else if(to == 'another'){
                const data = {
                    active: true,
                    memo: item,
                    window: true,
                    drag: false
                }
                this.$store.commit('setSharingMemo', data)
                if(this.$store.state.mobile){
                    
                    this.$router.push({name: 'board'})
                }else{
                    
                }
            }
        },
        flettyScroll(){
            this.createHidden = event.target.scrollTop > this.scrollPosition     
            this.scrollPosition = event.target.scrollTop; 
        },
        memoDrag(memo){
            if(memo.id !== this.editAble){
                const data = {
                    active: true,
                    memo: memo,
                    drag: true,
                    window:false
                }
                this.$store.commit('setSharingMemo', data)
            }
            
        },
        pasteListener(e) {
            e.preventDefault();
            var text = e.clipboardData.getData("text/plain");
            if (!text || text == "") {
                return;
            }
            else {
                var text = e.clipboardData.getData("text/plain");
                document.execCommand("insertHTML", false, text);
            }
        },
        editSend(data) {
            this.loading = false;
            axios.post("/edit_memo", data).then(response => {
                this.getMemo();
                this.loading = false;
                this.editAble = null;
            }).catch(function (error) {
                this.loading = false;
                this.editAble = null;
                if (error.response)
                    this.errorToast(this.$t('unknownError') + error.response.data.message);
                else if (error.request)
                    this.errorToast(this.$t('unknownError'));
                else
                    this.errorToast(this.$t('unknownError') + error.message);
            }.bind(this));
        },
        errorToast(message) {
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: true, 
                autoClose: true,

            }) 
        },
        deleteConfirm(id) {
            
            this.deleteSend(id)
        },
        deleteSend(id) {
            axios.post("/delete_memo", { id: id }).then(response => {
                this.getMemo();
                this.editAble = null;
            }).catch(function (error) {
                this.loading = false;
                this.editAble = false;
                if (error.response)
                    this.errorToast(this.$t('unknownError') + error.response.data.message);
                else if (error.request)
                    this.errorToast(this.$t('unknownError'));
                else
                    this.errorToast(this.$t('unknownError') + error.message);
            }.bind(this));
        },
        addMemoShort(){
            if(event.altKey){
                this.addMemo()
            }
        },
        addMemo() {
            if (this.loading)
                return;
            var textCheck = document.getElementById("newMemoType").textContent;
            var nospace = textCheck.replace(/\s/g, "");
            if (!nospace) {
                return;
            }
            this.loading = true;
            const data = {
                text: textCheck,
                board_id: this.memoPathSwitcher.id,
                message_id: null
            };
            axios.post("/add_memo", data).then(response => {
                this.getMemo();
                this.createCancel();
                this.loading = false;
            }).catch(function (error) {
                this.loading = false;
            }.bind(this));
        },
        createCancel() {
            this.memoCreateWindow = false;
        },
        setEditAble(id) {
            this.editAble = id;
        },
        focusOn() {
            this.isFocus = true;
        },
        blurOn() {
            this.isFocus = false;
            const textCheck = event.currentTarget.innerText
            if(!textCheck || textCheck == ''){
                this.createCancel();
                return;
            }
            var nospace = textCheck.replace(/\s/g, "");
            if (!nospace) {
                return;
            }
            this.addMemo();
        },
        getMemo() {
            axios.post("/get_memo_api", { record_id: this.memoPathSwitcher.id }).then(response => {
                this.memoList = response.data;
            });
        },
        sortBy(by){
            this.sortIs.by = by
        }
    },
    components: { 
        MemoSortButton, 
        MemoRecycleButton,
        MemoBox
    }
}
</script>
