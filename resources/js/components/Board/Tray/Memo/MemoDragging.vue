<template>
    <div v-html="draggingItemsIconBoard" 
        id="sharingMemo" 
        class="shadow-me" 
        style="position:fixed;z-index:555;padding:10px;background:#fff;font-size:12px;display:flex;align-items:center;top:-100vh;max-width:200px;white-space:nowrap;line-height:1.7">
    </div>  
</template>

<script>
    export default {
        mounted() {
            document.body.style.userSelect = 'none'
            window.addEventListener('mousemove', this.onMove);
            window.addEventListener('mouseup', this.onReset);
        },
        unmounted(){
            
            window.removeEventListener('mousemove', this.onMove);
            window.removeEventListener('mouseup', this.onReset);
        },
        computed:{
           
            draggingItemsIconBoard(){
                if(this.$store.state.sharingMemo.active && this.$store.state.sharingMemo.memo){
                    const el = '<p style="text-overflow: ellipsis;overflow: hidden;">' + this.$store.state.sharingMemo.memo.content + '</p>'
                    return el
                }
                
            }
        },
        methods:{
            onReset(){
                document.body.style.userSelect = 'unset'
                const data = {
                    active: false,
                    memo: null,
                    drag: false,
                    window: false
                }
                setTimeout(()=> {
                    this.$store.commit('setSharingMemo', data)
                },0)

            },
            onMove(e){
                if(!this.$store.state.sharingMemo.active) return
                let el = document.getElementById('sharingMemo');                    
                el.style.top = e.clientY + 10 + 'px'
                el.style.left = e.clientX + 10 + 'px'
            },   
        }
    }
</script>
