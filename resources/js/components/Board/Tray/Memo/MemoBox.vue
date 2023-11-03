<template>

        <div 
            @dragover.prevent 
            draggable="true" 
            @dragstart.prevent.stop="memoDrag"
            @click.stop="editStart" 
                :style="{
                    border: error ? 'solid 1px red' : editAble == item.id ? 'solid 1px var(--hoverBorder)' : memoBorder,
                    position: 'relative',
                }" 
                class="memoBox"
        >
            <div style="font-size: 12px;background: var(--bg3);padding: 5px;" v-if="item.editing && item.editing !== $store.state.user.name" v-html="`<strong>${item.editing}</strong>さんが編集中...`"></div>
        
            
            <div 
                @paste="pasteListener($event)"                 
                @keyup="stateChange"
                @blur="focusOut"
                @focus="setEdit(true)"
                @keyup.enter="editShort"
                :id="'editableMemo_' + item.id"
                :contentEditable="editAble == item.id" 
                class="memoBoxInner"
                :style="{ userSelect: 'text'}"
                v-html="urlCheck"></div>
            <Transition name="slidePop">
            <div class="memoCommandBar" v-if="editAble == item.id">
                <div @click.stop class="commentEditButton" style="margin:0">{{$t('save')}}</div>
                <div @click.stop="deleteSend()" class="commentEditButton" style="margin:0">{{ $t('delete') }}</div>
                <div @click.stop="copyText()" class="commentEditButton" style="margin:0">{{ $t('copy') }}</div>
            </div>
            </Transition>
            


        </div>

</template>

<script>
    import Autolinker from 'autolinker';
    export default {
        props: ['item', 'editAble', 'myColor'],
        emits: ['copyText', 'setEditAble', 'editSend', 'setEditing'],
        data(){
            return{
                content: '',
                error: 0,
                tempData: null,
                fileMenuLayer: 0,
            }
        },
        computed:{
            urlCheck() {            
                return Autolinker.link(this.item.content, {stripPrefix: false});             
            },
            memoBorder(){
                // var width = window.innerWidth
                // || document.documentElement.clientWidth
                // || document.body.clientWidth;
                // return width > 959 ? 'solid 1px #bfbfbf' : 'solid 1px #fff'
                return 'solid 1px var(--normalBorder)'

                // return this.item.user_id == this.$store.state.user.id ? `solid 1px ${this.myColor}` : 'solid 1px var(--normalBorder)'
            },
            menuBoxStyle(){
                return this.$store.state.sharingMemo.drag ? 'none' : 'text'
            }
        },
        methods: {
            editShort(){
                if(event.altKey){
                    this.editSend()
                }
            },
            copyText(){
                if(this.item.content.length){

                    this.$emit('copyText', this.item.content); 
        
                    
                }                
            },
            stateChange(){
                const inpt = document.getElementById('editableMemo_' + this.item.id)
                if(inpt){
                    const text = inpt.innerHTML
                    const nospace = text.replace(/\s/g, "")
                    this.error = !nospace.length                    
                }

            },
            editStart(index){
                if(event.target.nodeName === 'A') return
                if(this.item.editing && this.item.editing !== this.$store.state.user.name) return
                
                this.$emit('setEditAble', this.item.id)
                const el = document.getElementById('editableMemo_' + this.item.id)    
                this.tempData = el.innerHTML            
                setTimeout(() =>{
                    el.tabIndex = 1
                    el.focus();
                    if(index == -1){ 
                        document.execCommand('selectAll', false, null);
                        document.getSelection().collapseToEnd();
                    }
                },0)
                
                       
            },
            focusOut(){
                if(this.editAble == this.item.id){
                    this.editSend();
                }
                
            },
            setEdit(val){
                this.$emit('setEditing', this.item.id, val)
            },
            editCancel(){
                this.$emit('setEditAble', null); 
                document.getElementById('editableMemo_' + this.item.id).innerHTML = this.tempData
                this.tempData = null     
                this.error = 0
                
            },
            pasteListener(e){                    
                e.preventDefault();    
                var text = e.clipboardData.getData("text/plain");            
                if(!text || text == ''){            
                    return   
                }else{
                    var text = e.clipboardData.getData("text/plain");
                    document.execCommand("insertHTML", false, text);
                    this.stateChange()
                }     
            },
            editSend(){
                const inpt = document.getElementById('editableMemo_' + this.item.id)
                if(inpt){
                    const text = inpt.innerHTML
                    const nospace = text.replace(/\s/g, "")
                    this.error = !nospace.length
                    if(nospace.length){
                        const data = {
                            id: this.item.id,
                            text: text
                        }
                        this.$emit('editSend', data)
                    }
                    
                }
            },
            deleteSend(){
                this.$emit('deleteSend', this.item.id)
            },
            memoDrag(){
                const shareData = {
                    title: '',
                    text: this.item.content,
                    files: [],
                    from: 'memo',
                    to: '',
                    drag: true, 
                }
                this.$store.commit('setSharingData', shareData)
            }
            
        }
    }
</script>
