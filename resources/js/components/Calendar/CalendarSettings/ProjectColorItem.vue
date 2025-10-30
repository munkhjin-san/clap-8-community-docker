<template>
    <tr :key="project.id">
        <td>{{ project.name }}</td>
        <td>
            <div class="relative">
                <div @click.stop="menu.setMenu({parent: `picker-${project.id}`})" class="color-div cursor-pointer" :style="{backgroundColor: item || 'var(--task-background)'}"></div>
                <Transition name="slidePop">
                    <div :id="`picker-${project.id}`" v-if="menu.parent == `picker-${project.id}`" class="flex flex-col w-fit absolute top-0 right-0 bg-[var(--background-color)] p-[10px] shadow-lg z-10">             
                        <ColorPicker v-model="item"/>
                        <div class="flex items-center gap-[10px] ">
                            <div :style="{background: item}" class="color-div"></div>
                            <div>
                                <CommandButton :buttons="[{title: '選択', action: () => change()}]"/>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>                                            
        </td>
    </tr>
</template>
<script lang="ts" setup>
import ColorPicker from '@/components/Global/ColorPicker.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { ProjectSetting } from '@/interface/calendarInterface';
import { useMenuStore } from '@/store/menu';
const props = defineProps<{
    project: ProjectSetting
}>()

const item = defineModel<string>()
const menu = useMenuStore()
const change = () => {
    setTimeout(() => {
        menu.close()
    }, 0);
}
</script>
<style lang="scss" scoped>
.color-item-parent{
    padding: 5px;
    border: solid 1px transparent;
}
.selected-color{
    border: solid 1px var(--primary-color);
}
label{
    color: var(--primary-color);
}   

.color-div{
    width: 25px;
    height: 25px;
    min-width: 25px;
}
tr{
    transition: background 0.3s ease;
    background: var(--bg3);
}
tr:hover{
    background: var(--background-color);
}
td{
    padding:5px;
    font-size: 13px;
    font-weight: normal;
}
</style>