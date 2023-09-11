<template>
<div style="position: relative;background:inherit">
    <Form :ref="uId" v-slot="{ errors }" style="background:inherit">
        <span :class="{smallPlc : $store.state.activeInput == uId || (value.length)}" class="form-plc">{{placeHolder}}</span> 
        <Field 
            autocomplete="off" 
            :id="uId" 
            :class="['recordText', 'slide-plc']" 
            type="text" 
            :name="name" 
            :rules="rules" 
            v-model="value"
            @focus="$store.commit('setActiveInput', uId)"
            @blur="$store.commit('setActiveInput', '')"
            @input="$emit('setValue', $event.target.value)"
        />
        <span v-if="errors[uId]" class="form-error" style="font-size: 11px;color:tomato">{{ errors[uId] }}</span>   
    </Form>
    
</div>   
</template>
<script>
import { Field, Form , ErrorMessage } from 'vee-validate'
export default{
    props: ['placeHolder', 'name', 'rules', 'uId', 'validate'],
    emits: ['setValue'],
    data(){
        return{
            value: ''
        }
    },
    components: {
        Field, 
        Form,
        ErrorMessage
              
    },
    watch:{
        validate(after){
            const val = this.$refs[this.uId].validate()
            console.log(val)
            // console.log(this.$refs[this.uId])
        }
    },
    mounted(){
        
    }
}

</script>