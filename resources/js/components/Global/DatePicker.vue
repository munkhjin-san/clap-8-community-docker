<template>
    <div style="position: relative;">
        <Form :ref="uId" v-slot="{ errors }">
            <Field 
                class="taskDateTimePicker" 
                :class="[{'date-color' : $store.state.dark }]" 
                :name="name" type="date" 
                rules="required" 
                v-model="value"
                @input="$emit('setValue', $event.target.value)"
            />
            <span class="form-error" style="font-size: 11px;color:tomato;position: absolute;left: 0;bottom: -15px;">{{ errors[uId] }}</span>
        </Form>
        
    </div>   
    </template>
    <script>
    import { Field, Form , ErrorMessage } from 'vee-validate'
    export default{
        props: ['placeHolder', 'name', 'rules', 'uId'],
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
        methods:{}
    }
    
    </script>