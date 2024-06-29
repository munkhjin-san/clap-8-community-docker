<template>
    <div 
        id="taskSortMenu" 
        class="boxMenuComment cursor-pointer"
        :style="{top: '25px', right: '15px'}">
        <div class="messageMenuList" style="width:auto;text-align:left">
            <div 
                @click.stop="emit('sortBy', 'mytask')" 
                class="boxMenuItems cursor-pointer sortItemBar">
                <span>マイタスク</span>
                <span style="margin-left:5px" v-if="sortIs.myRecord">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                        <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                    </svg>
                </span>
            </div>
        </div>
    
    </div>
    </template>
    
    <script setup>
        const props = defineProps(['sortIs'])
        const emit = defineEmits(['sortBy'])       
    </script>
    