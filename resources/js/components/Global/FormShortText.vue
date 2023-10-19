<template>
<div style="position: relative;background:inherit">
    <Form :ref="uId" v-slot="{ errors }" style="background:inherit" @submit.prevent>
        <span class="form-plc smallPlc">{{placeHolder}}</span> 
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
    props: ['placeHolder', 'name', 'rules', 'uId', 'validate', 'initialValue'],
    emits: ['setValue'],
    data(){
        return{
            value: this.initialValue ? this.initialValue : ''
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