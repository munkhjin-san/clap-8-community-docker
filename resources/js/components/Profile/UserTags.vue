<template>
    <div>
        <div :class="user ? 'justify-center' : ''" style="display: flex;
            font-size: 13px;
            flex-wrap: wrap;
            gap: 10px;
            "> 
            <div @click="searchByTag(tag)" class="tagItem" v-for="tag in tags">
                <p>#{{tag.name}}</p>
            </div>
           
            
        </div>            
    </div>
</template>

<script>
    export default {
        props: ['tags', 'user'],
        emits: ['searchByTag'],
        data(){
            return{
                editingTag: false
            }
        },
        mounted() {
            
        },
        methods:{
            searchByTag(tag){
                this.$emit('searchByTag', tag)
            }
        }
    }
</script>
<style lang="scss">
.justify-center{
    justify-content: center;
}
.tagItem{
    cursor:pointer;
    color: var(--link-color);

}
.tagItem:hover{
    
    color: var(--link-color);
    text-decoration: underline;
    font-weight: 600;

}
</style>
