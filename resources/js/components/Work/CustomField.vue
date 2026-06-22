<template>
    <div :class="{ 'custom-field-compact': compact }">
        <div class="relative" v-if="data.form_type == 'textarea'" style="background: var(--background-color);margin-bottom: 20px;">
            <LongInput
                ref="commentRef"
                :key="textAreaKey"
                :placeHolder="data.title"
                name="commentRef"
                v-model="value"
                :disabled="props.locked"
                customClass="!pb-10"
            />      
            <div class="absolute left-[3px] bottom-[3px]">
                <div class="relative">
                    <div title="下書き保存" @click.stop="menu.setMenu({parent: 'temp_comment'})" class="h-[30px] w-[30px] min-w-[30px] flex items-center justify-center rounded-full bg-[var(--background-color)] hover:bg-[var(--bg3)] cursor-pointer">
                        <svg width="15" height="15" style="fill: var(--third-color);" viewBox="0 0 21.60806 22.36145">
                            <path d="M21.41274,7.98387c-.16877-.77089-.79066-1.82398-1.27363-2.40522-.40918-.49237-2.83109-3.49307-4.1876-4.55837-.53076-.41681-1.09003-.7251-1.802-.80176-.99628-.10736-2.51617-.15704-3.46716-.18555C9.52322-.00175,8.57937-.00242,7.42251.00179c-1.15717.00421-2.55054.06677-3.79272.14044-1.15851.06866-1.97693.11963-2.41547.18915C.63199.42366.20682.75142.14242,1.4363.07797,2.12111-.00473,5.5931.00021,7.76119c.00537,2.16809.00366,2.89462-.00021,3.84656,0,0,.10542,3.96337.17523,5.37293.13512,2.72811.35339,3.54022.42908,4.04816s.54745.9967,1.18747,1.10174c.85425.1402,4.84924.20996,6.83667.22595,1.98737.01593,5.32568-.01129,7.00098-.01801,1.67535-.00671,2.78833-.01416,4.12445-.15656.94928-.1012,1.39606-.40491,1.51514-1.23169l.1972-4.75057c.06982-1.59167.09403-3.42481.13139-5.01898.02716-1.21082.01306-2.29277-.18486-3.19685ZM7.96115,2.15926c1.13818.00592,2.27661.02161,3.41211.04486.14404.00342.30237.00342.4646.00256-.01288.40375-.03351.8075-.04315,1.2113l-.026,1.34534s.03961.39691-.08453.5188c-.24243.23792-1.27368.15051-1.27368.15051l-1.34534-.00751-1.34534.00903c-.44843.00323-.84918.07355-1.2923.00403-.11401-.01788-.19861.00317-.34991-.2049-.1441-.19818-.12286-.50073-.12286-.50073l-.02594-1.3454c-.00977-.40753-.03052-.81512-.04352-1.22266.69196-.0033,1.38342-.01025,2.07587-.00525ZM8.6813,20.6249c-.61932-.00195-1.40601-.00537-2.2226-.01154.04303-1.09473.07928-2.18982.10242-3.28278l.03613-2.05176c-.01953-.22852-.04004-.79004.2002-1.06055.14258-.15918.44434-.37109,1.41797-.37109.31445,0,.56934.02246.57129.02441l1.82031.01172,1.82812-.01367c.19238-.00195.37891-.01465.56152-.02734.20801-.01367.41211-.02734.61816-.02734.20898,0,.4209.01367.68652.05957.24902.0332.43555.11914.65527.46387.24707.38867.22266.91992.21973.95312l.03516,2.08594c.02209,1.08203.0589,2.16376.10126,3.24536-1.59058.0072-5.12183.0069-6.63147.00208ZM19.80866,9.87143c.00958,1.59045-.00248,4.92571-.02567,6.51732-.00067.04358-.00287.08704-.00354.13062l-.19708,3.72797c-.01465.17145-.15717.30432-.3291.30896-.81342.02197-1.61987.0379-2.44714.04993.04175-1.07635.078-2.15314.10071-3.22778l.03516-2.08301.00586-.9043c0-.00293-.01465-.52637-.04492-.7627-.02539-.1875-.15332-.56152-.30664-.74609-.44824-.54004-.92871-.64844-1.77441-.67578,0,0-.46289-.02637-.55957-.0293l-.66699-.01758c-.38574-.01172-.77051-.02344-1.15625-.02637l-1.82324-.01367-1.82227.01074-1.8457.04199c-.81152.02148-1.19043.05371-1.65234.59961-.16602.19531-.36914.56738-.40918.69531-.05273.17188-.06738.66895-.06055.97363l.00391.80273.03613,2.08594c.02222,1.0899.05902,2.17957.10199,3.26904-.87262-.0108-1.55521.02814-2.12809.00757-.10614-.00385-.49034-.25744-.60027-.50834-.13048-.29782-.08341-.60297-.13688-1.0744,0,0-.21046-5.10071-.24681-6.79597-.03324-1.55031-.06197-2.31752-.03743-4.45808.02081-1.78308.07654-3.56598.16241-5.34711.00543-.11237.09637-.20209.2088-.20557.67474-.021,1.35443-.03192,2.03406-.04211-.01288.40417-.03351.80835-.04315,1.21252l-.026,1.3454-.0033.59058h-.00061s.01105.35376.03528.5141c.02155.1427.12756.43036.2807.59229.41376.43738.88824.49786,1.4892.5152.13855.00397.27716.01538.41571.0188.44843.00891.89691.02496,1.34534.0282l1.34534.00903,1.34534-.00751,1.34534-.026c.0047-.00012.00946-.00037.01416-.00049.6015-.01434.98212-.03198,1.40057-.4646.138-.14264.3067-.4057.34906-.52557.06866-.19446.05542-.69287.05542-.69287l-.00293-.52039-.026-1.34534c-.00952-.39954-.02997-.79901-.04266-1.19849.15967.01227.29688.03027.38812.06,1.17566.38257,4.86643,4.34381,5.1237,5.09961.24066.70728.78821,1.29891.79645,2.49233Z"/>
                        </svg>
                    </div>
                    <Transition nam="modalFade">
                        <div id="temp_comment" v-if="menu.parent == 'temp_comment'" class="absolute top-[25px] left-[0] z-[7] shadow-me whitespace-nowrap bg-[var(--background-color)]">
                           <div @click="saveComment" class="flex items-center gap-[5px] cursor-pointer p-[10px]">
                                <AddIcon size="12" />
                                <p class="text-[var(--primary-color)] text-[12px] ml-[5px]">下書保存</p>
                           </div>
                           <div v-if="tempComments.length > 0" class="flex flex-col gap-[5px] mt-[5px]">
                                <div v-for="(comment, index) in tempComments" :key="index" class="flex items-center gap-[5px] cursor-pointer justify-between leading-normal hover:bg-[var(--bg3)]  p-[10px]">                                    
                                    <p @click="setText(comment)" class="text-[var(--primary-color)] text-[12px] max-w-[100px] overflow-hidden text-ellipsis">{{ comment }}</p>
                                    <Trash size="12" @click.stop="deleteComment(index)" />
                                </div>

                           </div>
                        </div>
                    </Transition>
                </div>
            </div>         
        </div>
        <div v-if="data.form_type == 'radio'">
            <div :class="compact ? 'custom-field-compact-section' : 'report-field'">
                <p class="report-header">{{ data.title }}</p>
                <div class="report-input">
                    <div class="report-input-wrapper" v-for="(customPart , index) in data.custom_field_parts_records">
                        <input :id="fieldInputId('workRadio', customPart.id)" type="radio" :name="fieldInputId('workRadioName', data.id)" v-model="value" :value="customPart.parts_value" :disabled="props.locked">
                        <label :for="fieldInputId('workRadio', customPart.id)">{{ customPart.parts_lavel }}</label>
                    </div>
                </div>
            </div>
        </div>
        <VehicleField 
            v-if="data.id === 44 && value == 1"
            v-model:vehicle="vehicle"
            :compact="compact"
        />
        <div v-if="data.form_type == 'checkbox'">
            <div :class="compact ? 'custom-field-compact-section' : 'report-field'">
                <!-- <p class="report-header">{{ data.title }}</p> -->
                <div class="report-input flex-wrap md:flex-nowrap">
                    <div class="report-input-wrapper" v-for="(customPart , index) in data.custom_field_parts_records.filter(part => part.parts_value != 2 || shift_type?.id == 0)" :key="index">
                        <div>
                            <input :id="fieldInputId('workAllowance', index)" type="checkbox" :name="fieldInputId('allowance', data.id)" v-model="value" :value="customPart.parts_value" :disabled="props.locked" @change="handleCheckboxChange(customPart, $event)">
                            <label class="allowance-label" :for="fieldInputId('workAllowance', index)">{{ customPart.parts_lavel }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <CustomFieldSub 
            :sub-parts="subParts"
            :id-prefix="fieldInputId('workSub', data.id)"
            :locked="props.locked"
            v-model="value"
            v-if="subParts && subParts.length"
        />       
    </div>

</template>
<script setup>
import { useMenuStore } from '@/store/menu';
import LongInput from '../Form/LongInput.vue';    
import Trash from '../Icons/Trash.vue';
import VehicleField from './VehicleField.vue';
import AddIcon from '../Form/AddIcon.vue';
import { onMounted, ref, computed, useTemplateRef } from 'vue';
import { useDialog } from '@/composables/dialog';
import CustomFieldSub from './CustomFieldSub.vue';
    const props = defineProps(['data', 'shift_type', 'compact', 'locked', 'idPrefix'])
    const value = defineModel('fieldValue') 
    const vehicle = defineModel('vehicle')
    const menu = useMenuStore()
    const tempComments = ref([])
    const textAreaKey = ref(0)
    const subPartsRef = useTemplateRef('subPartsRef')
    const { ping } = useDialog()
    const fieldInputId = (base, value) => `${base}-${props.idPrefix ?? props.data?.id ?? 'field'}-${value}`
    onMounted(() => {
        getTempComments()
    })
    const getTempComments = async () => {
        const data = localStorage.getItem('temp_comments')
        if(data){
            tempComments.value = JSON.parse(data)
        }
    }
    const deleteComment = (index) => {
        tempComments.value.splice(index, 1)
        localStorage.setItem('temp_comments', JSON.stringify(tempComments.value))
        getTempComments()
    }
    const saveComment = () => {
        if(value.value){
            tempComments.value.push(value.value)
            localStorage.setItem('temp_comments', JSON.stringify(tempComments.value))
            getTempComments()
        }else{
            ping('下書き保存する内容がありません')
        }
    }
    const setText = (text) => {
        value.value = text
        menu.close()
        textAreaKey.value += 1
    }
    const partValue = (part) => Number(part?.parts_value)
    const removeSubPartValues = (customPart) => {
        if (!Array.isArray(value.value)) return

        const subPartValues = (customPart?.sub_parts ?? [])
            .map(partValue)
            .filter(Number.isFinite)

        if (!subPartValues.length) return

        value.value = value.value.filter(selectedValue => !subPartValues.includes(Number(selectedValue)))
    }
    const handleCheckboxChange = (customPart, event) => {
        if (event.target?.checked) return

        removeSubPartValues(customPart)
    }
    const subParts = computed(() => {
        const type = props.data.form_type;
        const parts = props.data.custom_field_parts_records;
        if(type === 'radio'){
            const selectedPart = parts.find(part => part.parts_value === value.value);
            return selectedPart ? selectedPart.sub_parts : [];
        }else if(type === 'checkbox'){
            const sub_parts = parts.filter(part => Array.isArray(value.value) && value.value.map(Number).includes(Number(part.parts_value)));
            return sub_parts.map(part => part.sub_parts).flat();
        }
    })
    const setSubPart = (event) => {
        const targetValue = event.target.value;
        if(event.target.checked){
            const otherParts = subParts.value.filter(part => part.parts_value !== targetValue);
            otherParts.forEach(part => {
                value.value = value.value.filter(v => v !== part.parts_value);
            })
        }
    }
    const subPartsChecked = computed(() => {
        const parts = props.data.custom_field_parts_records;
        if(!parts || !Array.isArray(value.value)) return true;
        let result = true;
        const selectedValues = value.value.map(Number)
        parts.forEach(part => {
            const subParts = part.sub_parts || [];
            if(!subParts.length || !selectedValues.includes(Number(part.parts_value))) {
                result = result && true;
            }else{
                const valuesList = subParts.map(subPart => Number(subPart.parts_value));
                const includes = value.value.filter(v => valuesList.includes(Number(v)));
                const valid = includes.length ? true : false;
                result = result && valid;
            }         
        });
        return result;        
    })

    defineExpose({
        subPartsChecked
    })
</script>
<style scoped>
.custom-field-compact :deep(.report-field),
.custom-field-compact-section {
    margin: 0 0 12px;
    padding: 0;
}

.custom-field-compact :deep(.report-header),
.custom-field-compact-section .report-header {
    margin-bottom: 8px;
    font-size: 13px;
}

.custom-field-compact :deep(.report-input),
.custom-field-compact-section .report-input {
    gap: 8px;
}
</style>
