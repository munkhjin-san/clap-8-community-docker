<template>
    <div class="c-bar-wrap">
        <div @click.stop="menu.setMenu( { id: 6, name: 'calendarMemberSelector', parent: 'calendarMemberSelector'})" class="c-bar-button" style="margin-left: 15px;">メンバー</div>
        <div @click.stop="menu.setMenu( { id: 7, name: 'calendarFacilitySelector', parent: 'calendarFacilitySelector'})" class="c-bar-button">施設</div>
        <div @click.stop="menu.setMenu( { id: 8, name: 'departmentSelector', parent: 'departmentSelector'})" class="c-bar-button">部門</div>
        <div @click="emit('jumpToday')" class="c-bar-button">本日</div>
        <MemberPanel
            @setActiveMembers="val => emit('setActiveMembers', val)"
            @updated="emit('updated')"
        />
        <Transition name="modalFade">
            <div
                v-if="menu.parent == 'calendarFacilitySelector'"
                id="calendarFacilitySelector"
                class="calendarMemberSelector facility-filter-popover"
            >
                <div class="facility-filter-popover__body">
                    <section
                        v-for="(facilities, index) in facilitiesList"
                        :key="index"
                        class="facility-filter-group"
                    >
                        <div class="facility-filter-group__heading">
                            <span class="facility-filter-group__icon" aria-hidden="true">
                                <svg
                                    v-if="index === 'qualified_institution'"
                                    class="facility-filter-group__line-icon"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <path d="M5 21V3H19V21M3 21H21" />
                                    <rect x="8" y="6" width="3" height="3" />
                                    <rect x="13" y="6" width="3" height="3" />
                                    <rect x="8" y="12" width="3" height="3" />
                                    <rect x="13" y="12" width="3" height="3" />
                                </svg>
                                <svg
                                    v-else-if="index === 'zoom_value'"
                                    class="facility-filter-group__line-icon"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <rect x="3" y="6" width="12" height="12" rx="2" />
                                    <path d="M15 10L21 7.5V16.5L15 14" />
                                </svg>
                                <MyCarIcon v-else class="facility-filter-group__car-icon" :size="12" />
                            </span>
                            <p>{{ facilityTitle(index) }}</p>
                        </div>

                        <div
                            class="facility-filter-group__options"
                            :class="{ 'facility-filter-group__options--cars': index === 'qualified_car' }"
                        >
                            <label
                                v-for="(facility, sub_index) in facilities"
                                :key="facility.value"
                                class="facility-filter-option"
                                :class="{
                                    'facility-filter-option--selected': facility.selected,
                                    'facility-filter-option--disabled': !facility.selectable,
                                }"
                            >
                                <input
                                    :checked="facility.selected"
                                    :disabled="!facility.selectable"
                                    :value="facility.value"
                                    name="facilityCheckBox"
                                    type="checkbox"
                                    @change="updateFacility($event, index, sub_index)"
                                >
                                <span class="facility-filter-option__check" aria-hidden="true">
                                    <svg viewBox="0 0 12 12" fill="none">
                                        <path d="M2.2 6.2L4.8 8.6L9.8 3.4" />
                                    </svg>
                                </span>
                                <span class="facility-filter-option__label">{{ facility.label }}</span>
                                <span v-if="!facility.selectable" class="facility-filter-option__status">利用停止</span>
                            </label>
                        </div>
                    </section>
                </div>
            </div>
        </Transition>
        <Transition>
            <div v-if="menu.parent == 'departmentSelector'" id="departmentSelector" class="calendarMemberSelector">
                <div id="departmentSelector" style=" max-height: 50vh; overflow-y: auto;"> 
                    <div style="position: sticky; padding: 10px 15px 5px; top: 0; background: var(--bg3);z-index: 2;">
                        <div class="searchBarInner" style="margin: auto;width: auto;min-width: 270px"> 
                            <PostSearchBar  
                                className="newChatMemberSearch" 
                                :customPlaceHolder="'部門検索'"
                                @search-start="(word) => {keywords = word}"
                            />
                        </div> 
                    </div>               
                    <div>
                            
                        <div style="padding: 0 15px;">                                                
                            <label v-for="department in searchDepartment" class="cal-member-check" style="align-self: center;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
                            <input :value="department.id" :checked="selectedDepartment.map(d => d.id).includes(department.id)" @input="updateDepartment(department.id)" name="memberCheckBox" type="checkbox">
                            <span class="cal-check-mark" style="top: 5px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">                    
                                    <p class="userName">{{department.name}}</p>                                    
                                </div>
                            </label>  
                        </div>     
                    </div>                             
                </div>
            </div>
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { computed, ref } from 'vue'
import { useMenuStore } from "@/store/menu";
import PostSearchBar from '../Post/PostSearchBar.vue'
import MemberPanel from './MemberPanel.vue'
import { FacilityData } from '@/interface/calendarInterface'
import { useCalendar } from '@/composables/calendar'
import MyCarIcon from '../Icons/MyCarIcon.vue'
    const menu = useMenuStore()
    const props = defineProps(['selectedYear', 'selectedMonth'])
    const emit = defineEmits(['jumpToday', 'updated', 'setActiveMembers', 'refresh'])
    const keywords = ref('')
    const { facilitiesList, setFacility, departmentsList, setSelectedDepartment, selectedDepartment } = useCalendar()

    const searchDepartment = computed(() => {
        const keyword = keywords.value.toLowerCase();

        if (keyword && Array.isArray(departmentsList.value)) {
            return departmentsList.value.filter(department => {
                return department.name.toLowerCase().includes(keyword);
            });
        }
        return departmentsList.value
    })
    const facilityTitle = (index: string) => {
        if(index == 'qualified_institution'){
            return '会議室'
        }else 
        if(index == 'zoom_value'){
            return 'Web会議'
        }else if(index == 'qualified_car'){
            return '車両'
        }
        return ''
    }
    const updateFacility = (event: Event, index:keyof FacilityData, sub_index:number) => {
        const target = event.target as HTMLInputElement
        const checked = target.checked
        setFacility(index, sub_index, checked)
        emit('refresh')
    }
    const updateDepartment = (id: number) => {
        setSelectedDepartment(id)
        emit('refresh')
    }
</script>
<style lang="scss">
.fac-select-pop{
    max-height: 50vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    padding: 15px;
    gap: 15px;
}
.facility-filter-popover {
    width: min(420px, calc(100vw - 30px));
    overflow: hidden;
    color: var(--primary-color);
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
}

.facility-filter-popover__body {
    display: flex;
    max-height: min(64vh, 560px);
    overflow-y: auto;
    flex-direction: column;
    padding: 8px 12px;
}

.facility-filter-group {
    padding: 10px 2px 12px;
    border-bottom: 1px solid var(--calendarBorder);
}

.facility-filter-group:last-child {
    border-bottom: 0;
}

.facility-filter-group__heading {
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 8px;
    margin-bottom: 5px;
    font-size: 12px;
    font-weight: 600;
}

.facility-filter-group__icon {
    display: grid;
    place-items: center;
    width: 18px;
    height: 18px;
    flex: 0 0 18px;
    color: var(--primary-color);
}

.facility-filter-group__line-icon {
    width: 17px;
    height: 17px;
    stroke: currentColor;
    stroke-width: 1.6;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.facility-filter-group__car-icon {
    display: block;
    width: 17px;
    max-width: 100%;
}

.facility-filter-group__options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 2px 8px;
}

.facility-filter-group__options--cars {
    grid-template-columns: 1fr;
}

.facility-filter-option {
    display: flex;
    align-items: center;
    min-width: 0;
    min-height: 32px;
    gap: 7px;
    padding: 5px 7px;
    box-sizing: border-box !important;
    color: var(--primary-color);
    font-size: 11px;
    border: 0;
    background: transparent;
    cursor: pointer;
    transition: border-color 120ms ease, background-color 120ms ease;
}

.facility-filter-option:hover:not(.facility-filter-option--disabled) {
    background: var(--bg3);
}

.facility-filter-option--selected {
    background: var(--bg3);
}

.facility-filter-option--disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.facility-filter-option input {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
}

.facility-filter-option__check {
    display: grid;
    place-items: center;
    width: 15px;
    height: 15px;
    flex: 0 0 15px;
    box-sizing: border-box !important;
    border: 1px solid var(--primary-color);
}

.facility-filter-option__check svg {
    width: 11px;
    height: 11px;
    stroke: var(--background-color);
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
    opacity: 0;
}

.facility-filter-option--selected .facility-filter-option__check {
    background: var(--primary-color);
}

.facility-filter-option--selected .facility-filter-option__check svg {
    opacity: 1;
}

.facility-filter-option:focus-within {
    outline: 1px solid var(--primary-color);
    outline-offset: 1px;
}

.facility-filter-option__label {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.facility-filter-option__status {
    flex: 0 0 auto;
    margin-left: auto;
    color: tomato;
    font-size: 8px;
    white-space: nowrap;
}

@media (max-width: 460px) {
    .facility-filter-group__options {
        grid-template-columns: 1fr;
    }
}
</style>
