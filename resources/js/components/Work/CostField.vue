<template>
    <div class="report-field" style="background:inherit;">
        <p class="report-header" style="margin-bottom: 20px;">経費</p>
        <div v-for="(cost, index) in model" :key="index" style="display:flex;gap:20px;background:inherit;margin-top:20px;align-items: center;flex-wrap:wrap;">
            
            <select class="dropDownSelector taskDateTimePicker" v-model="cost.type" name="costType">
                <option :key="index" v-for="(item , index) in costOptions" :value="item.value">{{ item.label }}</option>
            </select>
            <ShortInput 
                name="content" 
                placeHolder="内容" 
                type="text"
                v-model="cost.content"
                customStyle="padding:0 10px; height:38px;"
            />
            <div style="background:inherit; position:relative;">
                <ShortInput 
                    name="expenses" 
                    placeHolder="経費" 
                    type="number"
                    v-model="cost.expenses"
                    customStyle="padding: 0px 25px 0 10px; height:38px; width: 50px;"
                />
                <span style="position:absolute; height:100%; top:0; right: 5px; line-height: 40px;">円</span>
            </div>
            
            
            <div v-if="!cost.file" style="display:inline-block">
                <label for="sharedfile" class="cursor-pointer" @click="currentIndex = index">                                       
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="27" viewBox="0 0 27 32" style="fill: var(--third-color);padding-right: 5px;">
                        <path d="M25.954 7.013c-0.479-0.575-4.378-4.56-5.978-5.816-0.623-0.489-1.284-0.853-2.127-0.949-1.178-0.125-2.97-0.182-4.091-0.22-1.36-0.029-2.472-0.029-3.832-0.029-1.36 0.010-3.008 0.077-4.474 0.172-1.36 0.077-2.328 0.134-2.845 0.22-0.69 0.105-1.188 0.489-1.265 1.303-0.077 0.805-0.172 4.905-0.172 7.454 0.010 2.558 0.115 5.835 0.201 6.822 0.096 0.987 0.556 1.447 1.083 1.504 0.527 0.067 0.843-0.537 0.853-1.159 0.019-0.623 0.019-1.226 0.019-1.734s-0.048-2.913-0.019-5.432c0.029-2.098 0.086-4.206 0.192-6.304 0.010-0.134 0.115-0.24 0.249-0.24 0.92-0.029 1.849-0.048 2.778-0.058 1.341-0.019 2.683-0.019 4.024-0.010s2.683 0.029 4.024 0.058c0.987 0.019 1.983 0.048 2.96 0.086 0.153 0.010 0.268 0.134 0.268 0.287-0.010 0.901-0.019 3.612-0.019 3.612 0 0.546 0.010 1.083 0.019 1.629v0.010c0.010 0.546 0.45 0.987 0.996 0.987l1.705 0.019h1.705c0.441 0 1.428-0.019 1.926-0.029 0.153 0 0.287 0.125 0.297 0.278 0.048 1.399 0.067 2.807 0.077 4.216 0.010 1.878 0 3.756-0.029 5.634s-0.077 3.756-0.153 5.624c-0.067 1.514-0.144 3.037-0.268 4.532-0.019 0.201-0.182 0.355-0.383 0.364-1.418 0.038-2.778 0.067-4.302 0.077-1.648 0.010-6.266 0.010-8.163 0-1.964-0.010-5.365-0.029-7.042-0.086-0.125 0-0.24-0.153-0.259-0.278-0.058-0.374-0.105-0.834-0.163-1.389-0.067-0.623-0.469-1.092-1.035-1.025-0.45 0.048-0.824 0.45-0.891 1.198-0.067 0.738-0.019 1.619 0.067 2.213s0.441 1.016 1.198 1.14c1.006 0.163 5.72 0.249 8.057 0.268 2.347 0.019 6.275-0.019 8.259-0.019 1.974-0.010 3.286-0.019 4.857-0.182 1.121-0.115 1.408-0.747 1.552-1.715 0.24-1.667 0.345-3.325 0.469-4.982 0.134-1.887 0.24-3.775 0.326-5.672 0.086-1.887 0.144-3.784 0.192-5.672 0.029-1.428 0.038-3.21 0.019-4.235-0.010-0.948-0.287-1.782-0.862-2.472zM19.832 7.023c-0.019-0.537-0.077-2.060-0.096-2.692 0-0.096 0.105-0.144 0.182-0.086 0.537 0.489 2.491 2.271 3.152 2.874 0.077 0.067 0.029 0.192-0.077 0.182-0.719-0.029-2.434-0.086-2.98-0.105-0.096 0.010-0.182-0.077-0.182-0.172z"></path>
                        <path d="M18.405 25.61l2.050-6.189c0.029-0.086 0.048-0.182 0.048-0.268 0-0.45-0.383-0.843-0.881-0.843h-18.74c-0.24 0-0.46 0.096-0.623 0.249s-0.259 0.364-0.259 0.604v6.189c0 0.23 0.096 0.441 0.259 0.594s0.383 0.249 0.623 0.249h16.69c0.374-0.010 0.709-0.24 0.834-0.584zM22.41 11.89c0.019-0.383-0.278-0.719-0.671-0.738-1.284-0.067-2.568-0.096-3.842-0.115-0.642-0.010-1.284-0.029-1.926-0.029l-1.926-0.010-1.926 0.010-1.926 0.029c-1.284 0.019-2.568 0.038-3.842 0.086-0.374 0.019-0.69 0.316-0.699 0.699-0.010 0.402 0.297 0.738 0.699 0.757 1.284 0.048 2.568 0.067 3.842 0.086l1.926 0.019 1.926 0.010 1.926-0.010c0.642 0 1.284-0.019 1.926-0.029 1.284-0.019 2.568-0.048 3.842-0.115 0.364-0.010 0.651-0.287 0.671-0.652zM15.875 14.63c-0.527-0.010-1.054-0.029-1.581-0.029l-1.59-0.010-1.581 0.010-1.59 0.029c-1.054 0.019-2.117 0.038-3.171 0.086-0.374 0.019-0.68 0.316-0.69 0.699-0.019 0.402 0.297 0.738 0.69 0.757 1.054 0.048 2.117 0.067 3.171 0.086l1.59 0.029 1.581 0.010 1.59-0.010c0.527 0 1.054-0.019 1.581-0.029 1.054-0.019 2.117-0.048 3.171-0.115 0.345-0.019 0.632-0.297 0.661-0.661 0.019-0.383-0.268-0.719-0.661-0.738-1.054-0.057-2.117-0.086-3.171-0.115z"></path>
                    </svg>  
                </label>
                <input  accept="image/*" type="file" name="sharedfile" id="sharedfile" @change="addAttachment($event, currentIndex)" style="display: none;">
            </div>
            <div class="file-area-content" v-else style="margin:0">
                <div class="file-wrap" @click.stop style="min-width: auto;height:40px;min-height: auto;">   
                    <img
                        style="max-width:100%;margin:auto;max-height:100%;cursor: pointer;" 
                        class="list-image-mobile" 
                        :src=fileSrc(cost.file)
                        @click="previewImage(cost.file)"
                    />
                    <div @click.stop="fileUpCancel(cost.file, index)" class="cancelButton">
                        <svg @click.prevent version="1.1" xmlns="http://www.w3.org/2000/svg" width="7" height="7" viewBox="0 0 32 32" fill="var(--background-color)">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>  
                    </div>  
                </div>
            </div>
            <div style="position: static;width: 30px;height: 30px; display:flex; cursor: pointer;" @click="addMovingAllowance">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 32 32" style="fill: var(--primary-color); margin: auto;">
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </div> 
            <div v-if="model.length > 1 || cost.id" style="position: static;width: 30px;height: 30px;display:flex; cursor: pointer;" @click="removeMovingAllowance(index, cost)">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 32 32" style="fill: var(--primary-color); margin: auto;transform: rotate(45deg);">
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </div>
        </div>
    </div>
</template>
<script setup>
    import { useFilePreview } from '../../store/filePreview';
    import ShortInput from '../Form/ShortInput.vue'
    import { inject, ref } from 'vue';
    const model = defineModel()
    const filePreview = useFilePreview()
    const { confirm, notify } = inject('dialog')
    const costOptions = inject('costOptions')
    const currentIndex = ref(null)
    const addMovingAllowance = () => {
        const newElement = {
            content: '',
            type: 1,
            expenses: null,
            file: null,
        };
        model.value.push(newElement);
    }
    const removeMovingAllowance = async(index, cost) => {
        if(cost?.id){
            const answer = await confirm('作業費を削除してもよろしいですか')
            if(!answer) return
            try {
                await axios.delete(`/work_cost_delete?id=${cost?.id}`)
            } catch (e) {
                notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            } finally {
                model.value.splice(index, 1)
                if(model.value.length === 0){
                    addMovingAllowance()
                }
            }
        } else {
            model.value.splice(index, 1)
        }
    }
    const fileUpCancel = async(file, index) => {
        let fileId;
        let path;
        if (typeof file === 'string') {
            path = file;
        } else {
            fileId = file.id;
            path = '/cdn/timecard_files';
        }
        try {
            await axios.post('/work_file_delete', { file_id: fileId, path });
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            model.value[index].file = null
        }
    }
    const fileSrc = (file) => {
        return file.path ? `/cdn/timecard_files/${file.id}_${file.user_id}_${file.path}.${file.extension}` : `/cdn/${file}`
    }
    const addAttachment = (event, index) => {
        if(event.target.files && event.target.files.length && index !== null){
            uploadStart(event.target.files[0], index)
        }
    }
    const uploadStart = async(file, index) => {
        if(file){
            const formData = new FormData()                    
                                    
            formData.append('file', file)

            try{
                const response = await axios.post('/work_file_upload', formData)
                const file = response.data
                if(file == 'notimage'){
                    notify('画像をアップロードしてください。')
                }else{
                    model.value[index].file = file
                }
            }catch (e){
                notify(e.response?.data.message || e?.message || 'エラーが発生しました。')    
            }
        }
    }
    const previewImage = (file) => {
        if(file?.id){
            let target_data = file
            const file_path = fileSrc(file)
            target_data['file_path'] = file_path
            const data = {
                active: true,
                files: [target_data],
                source: 'work',
                index: 0,
                message: null,
            }
            filePreview.setFilePreview(data)
        }
    }
</script>