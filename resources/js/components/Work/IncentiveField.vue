<template>
    <div class="report-field" style="background:inherit;">
        <p class="report-header" style="margin-bottom: 20px;">インセンティブ</p>
        <div v-for="(incentive, index) in model" :key="index" style="display:flex;gap:20px;background:inherit;margin-top:20px;align-items: center;">
            
            <ShortInput 
                name="expenses" 
                placeHolder="件" 
                type="number"
                v-model="incentive.count"
                customStyle="padding:0 10px; height:38px;width: 40px;"
            />
        </div>
    </div>
</template>
<script setup>
    import ShortInput from '../Form/ShortInput.vue'
    const model = defineModel()
</script>