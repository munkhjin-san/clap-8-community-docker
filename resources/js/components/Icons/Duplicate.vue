<template>
    <!-- Same glyph the form builder uses for its 複製 button, so both duplicate actions read alike.
         The viewBox is taller than it is wide — set width only and let height follow, or it skews. -->
    <svg xmlns="http://www.w3.org/2000/svg" :width="`${size}px`" :fill="color" viewBox="0 0 17.85612 23.5403">
        <path d="M6.60832.8297c-.5011-.05275-.52747-.73846,0-.79121,6.14506-.60659,12.81758,6.06593,10.91868,12.29011-1.5033,4.82637-6.72528,6.40879-11.39341,5.67033,1.55604,1.0022,3.05934,2.05714,4.37802,3.34945,1.18681,1.16044-.63297,2.98022-1.81978,1.81978-2.50549-2.47912-5.3011-4.48352-8.22857-6.40879-.71209-.44835-.58022-1.5033.23736-1.76703,3.34945-1.05495,5.98681-2.9011,8.94066-4.74725.73846-.44835,1.3978.55385.8967,1.16044-1.3978,1.63517-3.24396,2.87473-5.22198,3.85055,3.34945.84396,7.85934.5011,9.6-2.61099C17.73799,7.50223,11.56656,1.40992,6.60832.8297Z" />
    </svg>
</template>
<script setup lang="ts">
interface Props {
    size?: string | number
    color?: string
}
withDefaults(defineProps<Props>(), {
    size: 8,
    color: 'currentColor',
})
</script>
