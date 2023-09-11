<template>
    <div style="position:relative;color: rgb(255, 255, 255);background: rgb(0, 0, 0);height: 40px;width:15%;">
        <div @click.stop="$store.commit('setMenu', {name: 'cMonthPicker', id:87})" style="width: 100%;height: 100%;flex;align-items: center;cursor:pointer;place-content: center;display:flex">
            {{ year }}
        </div>
        <Transition name="slidePop">
            <div v-if="$store.state.menu.name=='cMonthPicker' && $store.state.menu.id==87" class="monthPicker" style="left:0;">
                <div style="width: 100%;color: #000;font-size: 14px;text-align: center;padding: 10px 0;">有効期間</div>
                <div style="display:flex;">
                    <div v-if="pickerIs == 'year'" id="cMonthPicker" class="grid-container year-picker" style="grid-template-columns: repeat(1, 1fr);">
                        <div @click.stop="setYear(y)" :key="`y_start_${y}`" :id="`y_start_${y}`" :class="{thisYear : y == year}" v-for="y in yearList" class="grid-item">{{ y }}</div>
                    </div>
                    <div v-if="pickerIs == 'year'" id="cMonthPicker" class="grid-container year-picker" style="grid-template-columns: repeat(1, 1fr);">
                        <div @click.stop="setYear(y)" :key="`y_end_${y}`" :id="`y_end_${y}`" :class="{thisYear : y == year}" v-for="y in yearList" class="grid-item">{{ y }}</div>
                    </div>
                </div>

            </div>
        </Transition>
    </div>

</template>

<script>
import moment from 'moment'
import { nextTick } from 'vue'
    export default {        
        props: [],
        emits: ['setDate'],
        data(){
            return{
                pickerIs: 'year',
                year: moment().year(),
                open: false    
            }
        },  
        mounted() {
            
        },
        watch:{
            '$store.state.menu.name'(after){
                nextTick(() =>{
                    const el = document.getElementById(`y_${this.year}`)
                    document.getElementById('cMonthPicker').scrollTo(0, el.offsetTop)
                })
            }
        },
        computed:{
            yearList(){
                return Array.from({ length: (this.year + 100) - 1970 + 1 }, (_, i) => 1970 + i)
            }
        },
        methods:{
            increase(){
                if(this.year >= (this.year + 100)) return
                this.year ++;
            },
            decrease(){
                if(this.year <= 1970) return
                this.year --;
            },
            setYear(y){
                // this.year = y
                // this.$emit('setDate', {year: this.year})
                // this.$store.commit('setMenu',{ name: '', id: null})
            },            
        }
    }
</script>

