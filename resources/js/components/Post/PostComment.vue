<template>
 
    <div class="commentAreaBox">
        <div class="commentAreaBoxInner" :ref="`commentAreaBoxInner_${record.id}`">
            <div v-if="comments.length == 0" style="height:100%;width:100%;position:relative;">
                <!-- #20201221_023 -->
                <div class="post-no-comment-text">現在コメントはありません</div>
            </div>
            
            <Comment 
                v-for="(comment, index) in comments"
                :key="index"
                :comment="comment"
                :editing="editing"
                @editComment="editComment"
                @deleteComment="commentDeleteConfirm"
                @editCancel="commentEditCancel"
                @editSend="commentEditSend"
            />
            <div v-if="fetch == 0" id="loaderMini" style="position: absolute;background: var(--bg3);z-index: 5;">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
          
                               
        </div>                   

        <div id="commentSendArea" style="position:relative;width:100%;box-sizing: border-box;display:inline-block;transform: translate(0, 15px);">                                
            <div 
                @keyup="caretPos" 
                @click="caretPos" 
                @paste="pasteListener($event)" 
                :ref="`commentTypeArea_${record.id}`"
                style="visibility:visible;font-size:16px;max-height:185px;width:calc(100% - 70px);display:inline-block;padding: 5px 10px 5px 10px !important;" 
                contenteditable="true" 
                class="typeBoxArea">
            </div>
            <div class="pc" style="position:absolute;right:60px;bottom:9px" @click.stop="$store.commit('setMenu', {id: 1003, name: 'EmojiPicker'})">                               
                <svg style="fill:#878787;" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 30 30">
                    <path d="M14.977,0C6.735-0.056-0.127,6.93,0.002,15.153c-0.028,8.165,6.816,14.938,14.975,14.811v-0.04c0.967,0.013,1.936-0.067,2.889-0.242c4.817-0.863,9.055-4.275,10.937-8.8C32.985,11.039,25.688-0.021,14.977,0 M14.977,27.902C6.08,27.658-0.075,18.755,3.433,10.373C7.814,0.291,22.13,0.293,26.49,10.386C30.002,18.61,23.886,27.788,14.977,27.902"/>
                    <path d="M22.441,18.263c-0.623-0.436-1.479-0.284-1.917,0.338c0.007-0.011,0.002-0.006-0.001-0.004c-0.002,0.002-0.006,0.005-0.011,0.01l-0.027,0.025c-0.734,0.658-1.568,1.264-2.479,1.639c-0.291,0.123-0.596,0.222-0.9,0.292c-0.67,0.185-1.332,0.349-2.043,0.376c-2.039,0.059-4.107-0.841-5.435-2.355c-1.226-1.563-3.443,0.199-2.196,1.769c0.199,0.27,0.418,0.529,0.646,0.772c1.784,1.911,4.359,3.094,6.986,3.106c1.119,0.021,2.305-0.08,3.354-0.525c1.753-0.72,3.36-1.896,4.362-3.526C23.214,19.556,23.063,18.698,22.441,18.263"/>
                    <path d="M18.513,14.558c0.905,0.201,1.834-0.509,2.073-1.585c0.239-1.076-0.302-2.111-1.208-2.313c-0.904-0.201-1.833,0.509-2.072,1.585C17.065,13.322,17.606,14.357,18.513,14.558"/>
                    <path d="M11.44,14.558c0.906-0.201,1.446-1.236,1.208-2.313c-0.239-1.076-1.167-1.786-2.074-1.585c-0.906,0.203-1.446,1.238-1.208,2.313C9.605,14.049,10.534,14.759,11.44,14.558"/>
                </svg>
            </div> 
            <!-- <VEmojiPicker style="position:absolute;top: -250px;right: 0px;height: 250px;" v-if="emojiBox && activeEmojiBox == record.id" @select="selectEmoji" /> -->
            <Transition name="modalFade">
                <div id="EmojiPicker" v-if="$store.state.menu.name == 'EmojiPicker' && $store.state.menu.id == 1003">
                    <EmojiPicker                                     
                        :native="true" 
                        @select="selectEmoji" 
                        :hide-search="true" 
                        :hide-group-names="true" 
                        theme="dark" 
                        :disable-sticky-group-names="true" 
                        :disable-skin-tones="true"
                        :display-recent="true"
                        class="commentEmojiPicker"
                    />
                </div>
                
            </Transition>
            <div class="sendAreaBox" style="height: 35px;right:0">                                       
                <svg v-on:click="commentSend(record.id)" v-if="!sendLoader" version="1.1" xmlns="http://www.w3.org/2000/svg" width="35" viewBox="0 0 43 32" style="margin:auto;fill:#878787;">
                    <path d="M40.638 0.087c-1.842 0.361-6.097 1.292-9.435 2.047l-30.046 6.891c-0.419 0.096-0.793 0.374-1.003 0.793-0.364 0.728-0.058 1.585 0.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56 0.287 0.157 0.487 0.439 0.542 0.762 0 0 0.711 4.473 0.921 5.891 0.21 1.417 0.714 4.465 1.184 6.482 0.168 0.726 0.631 1.335 1.215 1.512 0.495 0.152 1.030 0.037 1.43-0.285 1.394-1.128 5.787-5.445 7.388-7.272 0.133-0.152 0.355-0.19 0.531-0.085l6.184 3.646c0 0 0.439 0.294 0.919 0.519 1.283 0.601 2.479 0.625 3.062-0.829 0.325-0.813 4.316-12.627 4.316-12.627l4.466-13.209c0.053-0.152 0.082-0.321 0.082-0.492 0-0.844-0.654-1.675-2.496-1.312zM20.045 24.741c-0.475 0.477-1.473 1.473-2.284 2.197-0.155 0.137-0.385-0.002-0.313-0.195l1.796-4.842c0.051-0.157 0.236-0.226 0.378-0.142l1.796 1.054c0.157 0.091 0.161 0.294 0.041 0.432-0.401 0.458-0.975 1.058-1.413 1.495zM32.151 25.117c-0.106 0.325-0.482 0.47-0.777 0.301l-1.447-0.824-3.554-2.014-7.121-4.024c-0.067-0.037-0.138-0.068-0.214-0.094-0.677-0.232-1.411 0.13-1.64 0.808l-1.944 7.086c-0.053 0.166-0.229 0.143-0.251-0.046-0.13-1.23-0.328-3.178-0.467-4.759-0.13-1.459-0.366-3.357-0.494-4.434-0.111-0.931-0.427-1.423-1.131-1.837-0.704-0.415-6.489-3.354-7.668-4.049-0.241-0.142-0.166-0.415 0.065-0.463 0 0 13.334-2.689 16.022-3.304 2.689-0.617 10.513-2.447 10.513-2.447 0.103-0.025 0.152 0.118 0.056 0.161l-5.127 2.281-2.961 1.459c-0.987 0.487-7.32 3.516-9.259 4.562-0.477 0.258-0.665 0.871-0.373 1.36 0.255 0.429 0.808 0.574 1.265 0.374 2.004-0.882 16.208-7.766 17.651-8.441 0.345-0.162 0.376-0.012 0.287 0.049-0.89 0.615-9.43 6.896-10.25 7.528l-2.448 1.905c-0.432 0.342-0.519 0.976-0.173 1.42 0.335 0.432 0.965 0.497 1.413 0.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66c0 0 5.775-4.365 6.187-4.682 0.166-0.128 0.397 0.033 0.331 0.234l-2.517 7.675-3.585 10.965z"></path>
                </svg>
                <div v-if="sendLoader" id="loaderMini">
                    <div class="spinner-mini"></div>
                </div> 
            </div>                        
        </div>                   
    </div>

    </template>
    
<script>
    import Comment from './Comment.vue';
    import EmojiPicker from 'vue3-emoji-picker'
    import { nextTick } from 'vue'
    import 'vue3-emoji-picker/css'
    export default {
        props: ['record', 'app_name'],
        emits: ['updateCommentCount'],
        data (){
            return {
                
                commentMenuToggle: false,
                activeId: '',
                commentEditToggle: false,
                menuCounter: 0,
                alertCounter: 0,
                sendLoader: false,     
                emojiBox:false,
                activeEmojiBox: null, 
                caretPosition: 0,  
                emojiFlag: false,     
                comments: [],
                fetch: 0,
                editing: null     


            }
        },
        components: {          
            Comment,
            EmojiPicker        
        },
        computed:{
            
        },
        mounted() {
            
        },
        created(){
            this.getCommentList('mounted')
        },
        methods: {
            selectEmoji(emoji) {       
                var a = this.$refs[`commentTypeArea_${this.record.id}`].textContent
                var b = emoji.i;
                var position = this.caretPosition;
                var output = [a.slice(0, position), b, a.slice(position)].join('');
                this.$refs[`commentTypeArea_${this.record.id}`].textContent = output
                this.caretPosition = this.caretPosition + 2;
            },
            getCommentList(from){
                axios.post('get_post_comments',{ 
                    record_id: this.record.id, 
                    app_name: this.app_name
                }).then(response => {   
                    if(from == 'mounted'){
                        setTimeout(() => {
                            this.comments = response.data   
                            nextTick(() => {                   
                                var cont = this.$refs[`commentAreaBoxInner_${this.record.id}`]
                                cont.scrollTop = cont.scrollHeight               
                            });  
                        }, 300);
                    }else{
                        this.comments = response.data   
                    }
                    
                    if(from == 'self'){
                        nextTick(() => {                   
                            var cont = this.$refs[`commentAreaBoxInner_${this.record.id}`]
                            cont.scrollTop = cont.scrollHeight               
                        });   
                    }  
                    setTimeout(() => {
                        this.fetch ++
                    }, 400);
                    if(this.record.comments_count !== response.data.length){
                        this.$emit('updateCommentCount', response.data .length, this.record.id)
                    }

                    
                            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError'))                          
                }.bind(this));
            },
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: false, 
                    autoClose: false,
                    answers: ['OK']

                })   
            },
            
            
            stopScroll () {
                this.scrollFlag = 1;                              

            },
            rootPosition(id){
                if(id == this.$store.state.user.id){
                    return null;
                }else{
                    return "display:flex;"
                }
            },
            momentMessage (date) {

                // return moment(date).format('YYYY年MM月DD日 HH:mm');
                var yearStr = moment(date).format('YYYY');
                var month_str = moment(date).format('MM');
                var monthStr = Number(month_str);
                var day_str = moment(date).format('DD');
                var dayStr = Number(day_str);
                var jsMonth = monthStr - 1;
                var dayOfWeekStrJP = ["日", "月", "火", "水", "木", "金", "土"];
                var dateStr = new Date(yearStr, jsMonth, dayStr);

                var itemDate = moment(date).format('YYYYMMDDHH:mm:ss');
                var todayDate = moment().format('YYYYMMDDHH:mm:ss');
                var itemDate01 = moment(date).format('YYYY-MM-DD');
                var todayDate02 = moment().format('YYYY-MM-DD');
                var now = moment(new Date());
                var then = new Date(date);
                var dayWrapper = moment(then);
        
                var diff = moment.duration(now.diff(dayWrapper)).asDays();
                var diffYear = moment.duration(now.diff(dayWrapper)).asYears();

                var return_date = monthStr + ' / ' + dayStr + ' ( ' + dayOfWeekStrJP[dateStr.getDay()] + ' )' + ' ' + moment(date).format('HH:mm');
                var today01 = moment().startOf('day');
                
                var diff2 = moment.duration(today01.diff(dayWrapper)).asHours();

                var thisYear = moment().startOf('year');
                var diffYear2 = moment.duration(thisYear.diff(dayWrapper)).asDays();           

                if(diff2 <= 0){
                    return moment(date).format('HH:mm');
                }
                if(diffYear2 > 0){
                    return yearStr + ' / ' + monthStr + ' / ' + dayStr + ' ( ' + dayOfWeekStrJP[dateStr.getDay()] + ' )' + ' ' + moment(date).format('HH:mm');
                }
                return return_date;
            }, 
            commentMenu (id) {

                this.menuCounter = 0; 
                // this.stopScroll();

                if (this.activeId == '' && this.commentMenuToggle == false) {

                    this.activeId = id;
                    this.commentMenuToggle = true;
                } else if (this.activeId == id && this.commentMenuToggle == true) {

                    this.commentMenuToggle = false;
                } else if (this.activeId == id && this.commentMenuToggle == false) {

                    this.commentMenuToggle = true;
                } else if (this.activeId !== id && this.commentMenuToggle == true) {

                    this.activeId = id;
                    this.commentMenuToggle = true;
                } else if (this.activeId !== id && this.commentMenuToggle == false) {

                    this.activeId = id;
                    this.commentMenuToggle = true;
                }

            },  
            
            editComment (comment) {
                this.editing = comment.id;
                setTimeout(() => {
                    const target = document.getElementById('editComment_' + comment.id)
                    if(target){                        
                        target.focus()
                        const range = document.createRange();
                        range.selectNodeContents(target);
                        range.collapse(false);

                        // Set the selection range to the end of the element
                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);
                        
                    }
                }, 0);

            },
            commentEditSend (id, message) {

                var new_text = document.getElementById('editComment_' + id).textContent;

                if (new_text == "") {
                    this.errorToast('コメントを空にすることはできません。')
                } else {                  
                    axios.post('post_comment_edit', {
                        id: id,
                        message: new_text
                    }).then(response => {
                        this.editing = null
                        this.getCommentList()
                    });
                }

            },
            commentEditCancel (id, message) {
                this.editing = null
                let body = document.getElementById('editComment_' + id)
                body.innerHTML = message;
            },
            commentDeleteConfirm(id){ 
                

                var uniqueChannell = Math.random().toString(36).substring(5);
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: 'コメントを削除しますか。',
                    closeButton: false, 
                    autoClose: false,
                    answers: ['はい', 'いいえ'],
                    channel: uniqueChannell
                })                      
                emitter.on(uniqueChannell, (data) => { data.answer == 'はい' ? this.commentDelete(id) : false });

            }, 
            commentDelete (id) {               

                axios.post('post_comment_delete', {
                    id: id
                }).then(response => {
                    this.getCommentList()
                });
                
            },            
            pasteListener(e){                    
                e.preventDefault();                    
                var text = e.clipboardData.getData("text/plain");
                document.execCommand("insertHTML", false, text);
            },               
            
            caretPos(){
                var element = event.target;
                var caretOffset = 0;

                if (window.getSelection) {
                    var range = window.getSelection().getRangeAt(0);
                    var preCaretRange = range.cloneRange();
                    preCaretRange.selectNodeContents(element);
                    preCaretRange.setEnd(range.endContainer, range.endOffset);
                    caretOffset = preCaretRange.toString().length;
                } 

                else if (document.selection && document.selection.type != "Control") {
                    var textRange = document.selection.createRange();
                    var preCaretTextRange = document.body.createTextRange();
                    preCaretTextRange.moveToElementText(element);
                    preCaretTextRange.setEndPoint("EndToEnd", textRange);
                    caretOffset = preCaretTextRange.text.length;
                }            
                this.caretPosition = caretOffset
                
            },
            commentSend (recordId){                
                var textCheck = this.$refs[`commentTypeArea_${this.record.id}`].textContent;
                            
                if(!textCheck){
                    return;
                }
                if(textCheck.length && textCheck.length == 2){
                    var ranges = [
                        '(?:[\u2700-\u27bf]|(?:\ud83c[\udde6-\uddff]){2}|[\ud800-\udbff][\udc00-\udfff]|[\u0023-\u0039]\ufe0f?\u20e3|\u3299|\u3297|\u303d|\u3030|\u24c2|\ud83c[\udd70-\udd71]|\ud83c[\udd7e-\udd7f]|\ud83c\udd8e|\ud83c[\udd91-\udd9a]|\ud83c[\udde6-\uddff]|[\ud83c[\ude01-\ude02]|\ud83c\ude1a|\ud83c\ude2f|[\ud83c[\ude32-\ude3a]|[\ud83c[\ude50-\ude51]|\u203c|\u2049|[\u25aa-\u25ab]|\u25b6|\u25c0|[\u25fb-\u25fe]|\u00a9|\u00ae|\u2122|\u2139|\ud83c\udc04|[\u2600-\u26FF]|\u2b05|\u2b06|\u2b07|\u2b1b|\u2b1c|\u2b50|\u2b55|\u231a|\u231b|\u2328|\u23cf|[\u23e9-\u23f3]|[\u23f8-\u23fa]|\ud83c\udccf|\u2934|\u2935|[\u2190-\u21ff])' // U+1F680 to U+1F6FF
                    ];
                    if (textCheck.match(ranges.join('|'))) {
                        this.emojiFlag = true;
                    } 
                
                }  
                
                this.sendLoader = true;               

                const params = { message : textCheck , app_name: this.app_name, record_id: recordId, emoji_flag: this.emojiFlag};
                
                // alert(JSON.stringify());
                axios.post('post_comment_add', params ).then(                    
                    // #20201202_010
                    response => {
                        this.$refs[`commentTypeArea_${this.record.id}`].innerHTML  = ''   
                        this.commentMessages = null;          
                        this.sendLoader = false;
                        this.emojiFlag = false;      
                        this.getCommentList('self')                          
                        
                    }
                )
            },
            sessionAlert(){
                var uniqueChannell = Math.random().toString(36).substring(5);  
                EventBus.$emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: 'セッションがタイムアウトしました。ログアウトされます。\nよろしいですか？ログアウトすると入力中のテキストは破棄されます。\n必要な場合は、「いいえ」を押して入力中のテキストをコピーすることをおすすめします。' ,
                    closeButton: false, 
                    autoClose: false,
                    answers: ['はい', 'いいえ'],
                    channel: uniqueChannell

                })            
                EventBus.$on(uniqueChannell, (data) => { 
                    if(data.answer === 'はい'){
                        window.location.reload();
                    }
                });
            },
        }
    }
</script>
<style lang="scss">
.queueComment-enter-active, .queueComment-leave-active {
    transition: transform 0.3s;
}
.queueComment-enter-from, .queueComment-leave-to {
    transform: translateY(50px);
    -ms-transform: translateY(50px);
    -webkit-transform: translateY(50px);
    -moz-transform: translateY(50px);
    -o-transform: translateY(50px);
    opacity: 0;
}
.post-no-comment-text{
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    top: 0;
    position: absolute;
    left: 0;
    width: 100%;
    color: gray;
}

</style>
    