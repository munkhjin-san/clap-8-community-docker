<template>
    <div class="tt-container">
        <div class="tt-inner">
            <div class="tt-icon" @mouseenter="active = true" @mouseleave="active = false">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="18px" height="18px" viewBox="0 0 18 18" enable-background="new 0 0 18 18" xml:space="preserve">
                    <path id="Oval_318_2_" fill="#B2B2B2" d="M7.74,12.441v2.184h2.645v-2.184H7.74z M9,18c-4.971,0-9-4.029-9-9s4.029-9,9-9
                        s9,4.029,9,9S13.971,18,9,18z M6.267,3.948C5.16,4.549,4.571,5.565,4.5,7.002h2.566c0-0.419,0.143-0.821,0.43-1.21
                        c0.287-0.387,0.773-0.581,1.46-0.581c0.698,0,1.179,0.157,1.442,0.473c0.264,0.314,0.395,0.663,0.395,1.047
                        c0,0.332-0.118,0.637-0.354,0.914L9.924,8.09L9.275,8.521C8.637,8.943,8.24,9.317,8.086,9.642c-0.153,0.325-0.248,0.914-0.283,1.765
                        h2.395c0.008-0.402,0.046-0.7,0.117-0.892c0.111-0.302,0.34-0.566,0.684-0.793l0.631-0.416c0.638-0.422,1.07-0.771,1.295-1.042
                        c0.385-0.448,0.576-1,0.576-1.654c0-1.068-0.441-1.874-1.326-2.419c-0.885-0.543-1.996-0.815-3.333-0.815
                        C7.822,3.375,6.965,3.566,6.267,3.948z"
                    />
                </svg>
            </div>
            <Transition name="modalFade">
                <div class="tt-chip" v-if="active">
                    <p v-html="content"></p>
                </div>
            </Transition>
        </div>
    </div>
</template>
<script>    
    export default {
        props: ['content'],
        data(){
            return{
                active: false
            }
        }, 
    }
</script>
<style lang="scss">
    $primary: #B1B0B1;
    $secondary: white;
    .tt-container{
        position: absolute;
        right: 5px;
        top: 5px;
        cursor: pointer;
        width: 18px;
        height: 18px;
        z-index: 1;
    }
    .tt-inner{
        position: relative;
    }
    .tt-chip{
        position: absolute;
        color: $secondary;
        white-space: break-spaces;
        right: 18px;
        bottom: 15px;
        border-radius: 10px 10px 0 10px;
        font-size: 14px;
        width: max-content;
        background: $primary;
        font-size: 14px;
        font-weight: 200;
        line-height: 1.8;
        padding: 16px 20px 16px 20px;
    }
</style>
