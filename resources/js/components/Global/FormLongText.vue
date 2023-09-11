<template>
    <div style="position: relative;background:inherit">
        <Form :ref="uId" v-slot="{ errors }" style="background:inherit">
            <span :class="['form-plc', {smallPlc : $store.state.activeInput == uId || (value.length)}] ">{{placeHolder}}</span> 
            <Field 
                as="textarea"
                autocomplete="off" 
                :id="uId" 
                :class="['recordTextArea', 'slide-plc']" 
                type="text" 
                :name="name" 
                :rules="rules" 
                v-model="value"
                @focus="$store.commit('setActiveInput', uId)"
                @blur="$store.commit('setActiveInput', '')"
                @input="$emit('setValue', $event.target.value)"
            />
            <span class="form-error" style="font-size: 11px;color:tomato">{{ errors[uId] }}</span>
        </Form>
        
    </div>   
    </template>
    <script>
   import { Field, Form , ErrorMessage } from 'vee-validate'
    export default{
        props: ['placeHolder', 'name', 'rules', 'uId',],
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
    }
    
    </script>
    <style>
    .errorBorder{
        border-color: tomato !important;
    }
    </style>