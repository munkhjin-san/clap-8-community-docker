<template>
    <div class="overlay" style="z-index:90">            
        <div id="quotAreaModal" style="padding:20px;max-width:90%;max-height:85%;width:fit-content;background:var(--background-color);overflow: auto;color:var(--primary-color)">  
            <div style="width:100%;display:flex;">
                <p style="padding-bottom: 20px;line-height: 0.7;margin-right: 30px;" class="copyareaTitle">{{ $t('selectTextToQuot') }}</p>
                <svg style="margin:0 0 0 auto;cursor:pointer;fill:var(--primary-color)" v-on:click="$emit('closeMe')" version="1.1" xmlns="http://www.w3.org/2000/svg" width="11" height="14" viewBox="0 0 32 32">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>
            </div>           
            <textarea @focus.once.prevent v-model="advancedQuotMessage" readonly id="quotAreaEditor" style="overflow: hidden;letter-spacing:normal !important;min-width:100%" class="copyMessageArea"></textarea>
            <div @click="advancedQuotFinish" style="margin: 20px auto auto auto;width:auto;user-select:none;" class="groupEditButton cursor-pointer">{{ $t('quot') }}</div>                        
        </div>                                        
    </div>
</template>
<script>
    export default {
        data(){
            return{
                advancedQuotMessage: ''
            }
        },
        mounted(){
            this.$store.commit('setQoutWindowActive', true)

            this.advancedQuotMessage = this.$store.state.quot_reply.text
            var copyArea = document.getElementById('quotAreaEditor');
            copyArea.style.width = this.$store.state.quot_reply.width +'px'
            copyArea.style.height = this.$store.state.quot_reply.height + 'px'
            setTimeout(() => {
                copyArea.select()
                const scrollingElement = (document.getElementById('quotAreaEditor'));
                scrollingElement.scrollTop = scrollingElement.scrollHeight;
                
            }, 0);  
            
        },        
        methods:{
            advancedQuotFinish(){
                var textComponent = document.getElementById('quotAreaEditor');
                var selectedText;

                if (textComponent.selectionStart !== undefined)
                {// Standards Compliant Version
                    var startPos = textComponent.selectionStart;
                    var endPos = textComponent.selectionEnd;
                    selectedText = textComponent.value.substring(startPos, endPos);
                }
                else if (document.selection !== undefined)
                {// IE Version
                    textComponent.focus();
                    var sel = document.selection.createRange();
                    selectedText = sel.text;
                }
                if(!selectedText.length){
                    this.$toast.clear();
                    this.$toast('選択されたテキストはありません。',{
                        toastClassName: "toastConfirm",
                        timeout: 3000, 
                        draggable: false,                                           
                    });
                    return
                }else{
                    const quot_reply = {
                        active: this.$store.state.quot_reply.active,
                        message: this.$store.state.quot_reply.message,
                        which: this.$store.state.quot_reply.which,
                        text: selectedText,
                        file: this.$store.state.quot_reply.file,
                        height: this.$store.state.quot_reply.height,
                        width: this.$store.state.quot_reply.width
                    }
                    this.$store.commit('setQuoteReply', quot_reply);          
                    this.$emit('completed')      
                }  

                
            },            
        }
    }
</script>