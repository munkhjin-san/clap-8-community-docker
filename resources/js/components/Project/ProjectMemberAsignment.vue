<template>
    <div class="goals-wrap bg-[var(--background-color)]">
        <div>{{ memberData?.name }}</div>


    </div>
</template>
<script setup lang="ts">
import { useProject } from '@/composables/project';
import { onMounted } from 'vue';

const { memberData, selectedProject } = useProject()

onMounted(() => {
    getMemberDetailedData()
})

const getMemberDetailedData = async () => {
    if(selectedProject.value && memberData.value?.id){
        
    }
}

</script>