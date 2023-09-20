<template>
    <div class="locale-selector" style="width: fit-content;position:relative;height:40px">
        <Form :ref="uId" v-slot="{ errors }">
            <Field 
                as="select"
                class="dropDownSelector cursor-pointer"
                :class="[{'date-color' : $store.state.dark }]" 
                :name="name" 
                :rules="rules" 
                v-model="value"
                @input="$emit('setValue', $event.target.value)"
                style="height: 40px; font-size: 14px; border: solid 1px var(--formBorder);"
            >
            <option :value="option" v-for="option in options" v-html="`${option}${unit}`"></option>
            </Field>
            <span class="form-error" style="font-size: 11px;color:tomato;position: absolute;left: 0;bottom: -15px;">{{ errors[uId] }}</span>
        </Form>
        
    </div>   
    </template>
    <script>
    import { Field, Form , ErrorMessage } from 'vee-validate'
    export default{
        props: ['name', 'rules', 'uId', 'initialValue', 'options', 'unit'],
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
        methods:{}
    }
    
    </script>