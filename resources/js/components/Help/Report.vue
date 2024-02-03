<template>
    <div class="help-content-container">
        <div class="help-content-inner">
            <div class="help-report">
                <div class="help-title-m">
                    <div class="mobile">
                        <div @click="$router.push({name: 'help'})" class="help-back-topic">    
                                                            
                            <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                            </svg>                                        
                            
                        </div>
                    </div>
                    <h1>{{ $t(`help.${$route.name}`) }}</h1>
                </div>
                
                <div style="padding: 0 15px;">
                    <Form ref="reportForm" v-slot="{ errors }">  
                    <div class="report-item-wrap">   
                        <span :class="{smallPlc : $store.state.activeInput == 'reportEmail'|| reportEmail.length}" class="form-plc">{{ $t('help.reportEmail') }}</span>
                        <Field
                            @focus="$store.commit('setActiveInput', 'reportEmail')"
                            class="recordText slide-plc"
                            name="reportEmail"
                            id="reportEmail"
                            rules="required|email"
                            v-model="reportEmail"
                            @blur="$store.commit('setActiveInput', '')"
                            style="color:var(--primary-color)"
                            autocomplete="email"
                        />
                        <input type="text" name="reportEmail" hidden autocomplete="email"/>
                        <span class="report-form-error">{{ errors.reportEmail }}</span>
                    </div>
                    <div class="report-item-wrap">   
                        <span :class="{smallPlc : $store.state.activeInput == 'reportTitle'|| reportTitle.length}" class="form-plc">{{ $t('help.reportTitle') }}</span>
                        <Field
                            @focus="$store.commit('setActiveInput', 'reportTitle')"
                            class="recordText slide-plc"
                            name="reportTitle"
                            id="reportTitle"
                            rules="required"
                            type="email"
                            v-model="reportTitle"
                            @blur="$store.commit('setActiveInput', '')"
                            autocomplete="off"
                        />
                        <span class="report-form-error">{{ errors.reportTitle }}</span>
                    </div>
                    <div class="report-item-wrap">   
                        <span :class="{smallPlc : $store.state.activeInput == 'reportDescription'|| reportDescription.length}" class="form-plc">{{ $t('help.reportDescription') }}</span>
                        <Field as="textarea"
                            rules="required"
                            name="reportDescription"
                            @focus="$store.commit('setActiveInput', 'reportDescription')"
                            class="recordTextArea slide-plc"
                            id="reportDescription"
                            style="padding-top: 20px !important;height: auto;"
                            v-model="reportDescription"
                            @blur="$store.commit('setActiveInput', '')"
                        ></Field>
                        <span class="report-form-error">{{ errors.reportDescription }}</span>
                    </div>
                    </Form>
                    <div class="report-item-wrap">
                        <div>
                            <input ref="reportFileCollector" multiple @change="tempUpload" type="file" style="display: none;" id="reportFile" name="reportFile"/>
                            <label for="reportFile" style="margin:0" class="l-button">{{ $t('help.reportFile') }}</label>

                        </div>
                    </div>
                    <div class="report-item-wrap" style="display: flex;gap: 20px;flex-direction: column;">
                        <div v-for="(file , index ) in attachedFiles">
                            <div style="max-width: 100px;max-width: 100px;font-size: 12px;word-wrap: break-word;line-height: 1.3;align-items: center;display: flex;flex-wrap: nowrap;max-width: 100%;gap: 10px;">
                                
                                <div style="position: relative;">
                                    <div @click="cancelUpload(index)" style="width: 15px;height: 15px;display: flex;align-items: center;justify-content: center;background: var(--primary-color);border-radius: 50px;position: absolute;right: -7px;top: -7px;cursor: pointer;">
                                        <svg @click.prevent version="1.1" xmlns="http://www.w3.org/2000/svg" width="7" height="7" viewBox="0 0 32 32" fill="var(--background-color)" style="pointer-events: none;">
                                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                        </svg>  
                                    </div>
                                    <div v-if="isImage(file)">
                                        <img style="max-width: 50px;max-height: 50px;" :src="file.src"/>                                        
                                    </div>                                
                                    <div v-else>
                                        <FileIcon :ext="file.ext"/>
                                    </div>
                                </div>
                                
                                <div>{{ file.name }}</div>
                            </div>
                            
                        </div>
                        
                    </div>
                    
                    
                </div>
                <!-- <div @click="submitReport" class="l-button" style="margin: auto auto 20px auto;">{{ $t('send') }}</div> -->
                <div style="margin: auto 0 20px 0;">
                    <LoaderButton @triggered="submitReport" :loading="loading" :content="$t('send')"/>
                </div>
                

            </div>
            
        </div>
       
    </div>
</template>

<script>
import { Field, Form  } from 'vee-validate'
import { defineAsyncComponent } from 'vue'
export default {
    data(){
        return{
            reportEmail: '',
            reportTitle: '',
            reportDescription: '',
            attachedFiles: [],
            loading: false
        }
    },
    components:{
        Field, 
        Form, 
        FileIcon: defineAsyncComponent(() => import('../Board/Mixed/FileIcon.vue')),
        LoaderButton: defineAsyncComponent(() => import('../Global/LoaderButton.vue')),
    },
    mounted() {
        
    },
    methods:{
        async submitReport(){
            const result = await this.$refs.reportForm.validate();
        
            if (!result.valid) return
            let params = {
                title: this.reportTitle,
                description: this.reportDescription,
                email: this.reportEmail,
                id: this.$store.state.user ? this.$store.state.user.id : null,
                language: this.$store.state.local 
            }
            params = JSON.stringify(params)
            const files = this.attachedFiles.map( item => item.file)

            const formData = new FormData();

            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }

            formData.append('params', params);
            this.loading = true
            axios.post('/report_send', formData)
            .then(response => {
                console.log(response.data);
                setTimeout(() => {
                    this.loading = false
                    this.errorToast(this.$t('help.reportSentSuccessfully'));
                    this.attachedFiles = []
                    this.$refs.reportForm.resetForm({values : {
                        reportEmail: '',
                        reportDescription: '',
                        reportTitle: ''
                    }})
                }, 200);
                
            })
            .catch(error => {
                console.error(error);
                setTimeout(() => {
                    this.loading = false
                    this.errorToast(this.$t('commonError'));
                }, 200);
            });
            

        },
        isImage(file){
            return file && file.file && file.file.type.includes('image');

        },
        cancelUpload(index){
            this.attachedFiles.splice(index, 1)
        },
        errorToast(message){
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: [this.$t('confirmToAction')]

            }) 
        },
        tempUpload(){   
            // for(var i in event.target.files) {  
            event.target.files.forEach(file => {
                
                
                if(file.size > 100000000){
                    this.errorToast(this.$t('help.maximumFileSizeIsHundred'))
                    return
                }    
                if(this.attachedFiles.length >= 3){
                    this.errorToast(this.$t('help.maximumFileIsThree'))
                    return
                }           
                if(file.type !== undefined){
                    var uniqueId = Math.random().toString(36).substring(5);
                    
                    var source = 'nonimagefile'
                    if(file.type.indexOf('image') > -1){
                        var source = URL.createObjectURL(file);
                    }               
                    const name = file.name;
                    const lastDot = name.lastIndexOf('.');
                    const fileName = name.substring(0, lastDot);
                    const extension = name.substring(lastDot + 1);
                    this.attachedFiles.push({
                        src: source,
                        name: file.name,
                        uId: uniqueId,
                        ext: extension,
                        file: file
                    });  
                    
                }       
            });      
            this.$refs.reportFileCollector.value = null
        }
    }
}
</script>
<style lang="scss">
    .report-item-wrap{
        position: relative;
        margin-top: 25px;
    }
    .report-form-error{
        font-size: 10px;
        margin-top: 5px;
        color: tomato;
    }
    input:autofill {
        -webkit-text-fill-color: var(--primary-color);
        background-color: var(--background-color) !important; 
    }
    input:-internal-autofill-selected {
        background-color: var(--background-color) !important; 
    }

</style>