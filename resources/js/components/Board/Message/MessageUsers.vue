<template>
    <div @mousedown="closeFeedBack" class="overlay" style="z-index: 24">
        <div class="incompleteWindow" @mousedown.stop>
            <div style="display:flex">
                <p style="font-weight:600;margin-right:20px;">{{ title }}</p>
                <div style="margin-left:auto;display: flex;align-items: center;">                                          
                    <div class="cursor-pointer" @click="closeFeedBack" style="position:unset;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
            </div>
            <div style="position:relative; margin-top:20px;">
                    <div v-if="userList.length" class="suggested-list">
                        <div :key="user.id" v-for="user in userList">
                            <div class="suggested-wrap">
                                <UserIcon :user="user" imgClass="userNormalIcon" size="30"/>
                                <router-link :to="`/user/${user.id}`" class="suggested-user-name user-link" style="margin:0">{{ user.name }}</router-link>
                                <svg v-if="user.pivot.cancel_flag == 1" style="margin-left:5px;" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="13px" height="13px" viewBox="0 0 55.704 55.703" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M27.852,0C19.905,0,12.743,3.363,7.664,8.72C7.628,8.751,7.583,8.762,7.549,8.796C7.495,8.85,7.476,8.922,7.426,8.98 C2.833,13.949,0,20.568,0,27.852c0,15.357,12.493,27.851,27.851,27.851c15.356,0,27.851-12.494,27.851-27.851 C55.703,12.494,43.208,0,27.852,0z M4.489,27.851c0-5.315,1.805-10.207,4.806-14.138l32.691,32.694 c-3.93,3.001-8.819,4.806-14.135,4.806C14.969,51.213,4.489,40.732,4.489,27.851z M45.282,43.352l-32.933-32.93 c4.13-3.678,9.551-5.934,15.503-5.934c12.881,0,23.362,10.48,23.362,23.363C51.213,33.803,48.958,39.225,45.282,43.352z"></path> </g> </g></svg>
                            </div>
                        </div>
                    </div>
                    <span v-else>{{$t('currentlyNoUser')}}</span>                 
            </div>
        </div>
    </div>
</template>

<script>
    import UserIconPreLoad from '../Mixed/UserIcon.vue'
    export default {
        components: {
            UserIconPreLoad
        },
        computed: {
            userList(){
                return this.$store.state.messageUsers.userList
            },
            title(){
                return this.$store.state.messageUsers.title
            }
        },  
        methods:{
            
            closeFeedBack(){
                const data = {
                    active: false,
                    userList: [],
                    title: ''
                }
                this.$store.commit('setMessageUsers', data)
            }
        }
    }
</script>
<style scoped lang="scss">
 
    

     .incompleteWindow{
        box-shadow: rgb(0 0 0 / 35%) 0px 5px 15px;
        padding: 20px;
        margin: auto;
        background: var(--background-color);
        color: var(--primary-color);
        max-width: 80%;
        font-size: 14px;
        line-height: 1.5;
        max-height: 90%;
        overflow: hidden auto;
        max-height: 60vh;
     }
    
</style>
