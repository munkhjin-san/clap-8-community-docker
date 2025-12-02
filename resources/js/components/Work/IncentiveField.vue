<template>
    <div class="report-field" style="background:inherit;">
        <p class="report-header" style="margin-bottom: 20px;">インセンティブ</p>
        <div v-for="(incentive, index) in model" :key="index" style="display:flex;gap:20px;background:inherit;margin-top:20px;align-items: center;">
            <input 
                name="incentive_count" 
                placeholder="件"
                type="number"
                style="width: 40px; padding:0 10px; height:38px;border:1px solid var(--primary-color);color:var(--primary-color);"
                v-model="incentive.count"
            />
            <!-- <ShortInput 
                name="expenses" 
                placeHolder="件" 
                type="number"
                v-model="incentive.count"
                customStyle="padding:0 10px; height:38px;width: 40px;"
            /> -->
        </div>
    </div>
</template>
<script setup>
    import ShortInput from '../Form/ShortInput.vue'
    const model = defineModel()
</script>