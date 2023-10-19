<template>
    <div @mousedown="closeFeedBack" class="overlay">
        <div class="incompleteWindow" @mousedown.stop>
            <div style="display:flex">
                <p style="font-weight:600;margin-right:20px;">{{ $t('whyTaskExpired') }}</p>
                <div style="margin-left:auto;display: flex;">                                          
                    <div class="cursor-pointer" @click="closeFeedBack" style="position:unset;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
            </div>
            <div style="margin-top:20px;position:relative">
                <div v-for="answer in answers" style="padding: 10px 0px;display: flex;">
                    <input @change="selectedAnswer = answer.value" type="radio" :id="answer.id" name="answer" :value="answer.value">
                    <label style="margin-left:10px;cursor:pointer" :for="answer.id">{{answer.label}}</label>  
                </div> 
                <span v-if="this.validationFailed && !isValid.status" class="valid-error post-error" style="bottom:auto">{{$t('required')}}</span>
                <Transition name="feedbackAreaToggle">
                    <div v-if="selectedAnswer == 6" style="position:relative">
                        <textarea class="feedbackArea" :placeholder="$t('writeOtherReason')" v-model="lateAnswerCustom" name="" id=""></textarea>
                        <span v-if="validationFailed && isValid.inputRequired" class="valid-error post-error" style="bottom: -15px;">{{$t('required')}}</span>
                    </div>    
                </Transition>   
            </div>
            
            <div @click="completeTask" class="l-button cursor-pointer" style="margin:20px auto 0px auto">
                <span>{{$t('save')}}</span>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data(){
            return{
                answers:[
                    { label: this.$t('tookTimeToComplete'), value: 1, id:"incomplete_ans1"},
                    { label: this.$t('changeTaskPriority'), value: 2, id:"incomplete_ans2"},
                    { label: this.$t('forgotToClickComplete'), value: 3, id:"incomplete_ans3"},
                    { label: this.$t('dontKnowTheTask'), value: 4, id:"incomplete_ans4"},
                    { label: this.$t('notResponsibleForTask'), value: 5, id:"incomplete_ans5"},
                    { label: this.$t('otherReason'), value: 6, id:"incomplete_ans6"}
                ],
                selectedAnswer: 0,
                validationFailed: false,
                lateAnswerCustom: ''
            }
        },
        mounted() {
            
        },
        computed:{
            isValid(){
                return {
                    status: this.selectedAnswer > 0,
                    inputRequired : this.selectedAnswer == 6 && !this.lateAnswerCustom.length
                }
            }
        },  
        methods:{
            completeTask() {
                
                this.validationFailed = !this.isValid.status || this.isValid.inputRequired
                if(this.validationFailed) return
                // return
                // let button = document.getElementById("taskButton" + this.task.record.id);
                
                //     button.style.background = "#64bc44";
                //     button.style.color = "#fff";
                
                // setTimeout(() => {this.taskKey ++},500) 
                
                const data = {
                    task_id: this.$store.state.taskFeedBack.data.id, 
                    comp_flag: 1, 
                    late_answer:  this.selectedAnswer,
                    late_answer_custom: this.lateAnswerCustom
                }
                axios.post("/complete_task_api", data).then(response => {
                    this.closeFeedBack()
                });
            },
            closeFeedBack(){
                const data = {
                    active: false,
                    data: null
                }
                this.$store.commit('setTaskFeedback', data)
            }
        }
    }
</script>
<style scoped lang="scss">
    .feedbackArea{
        resize: none;
        border: solid thin #c5c5c5;
        width: -webkit-fill-available;
        color: var(--primary-color);
        height: 40px;
        padding: 10px;
        font-size: 14px;
        margin-top: 10px;

    }
    .feedbackAreaToggle-enter-active,
    .feedbackAreaToggle-leave-active {
        height: 77px;
        transition: height 0.2s;
        opacity: 1;
    }

    .feedbackAreaToggle-enter,
    .feedbackAreaToggle-leave-to {
        height: 0;
        opacity: 0;
        transition: height 0.2s;

    }
    .feedbackArea::-webkit-input-placeholder {
        font-size: 14px;
    }
    .feedbackArea::-moz-placeholder {
        font-size: 14px;
    }
    .feedbackArea:-ms-input-placeholder {
        font-size: 14px;
    }
    .feedbackArea::placeholder {
        font-size: 14px !important;
    }
    input[type="radio"] {
        -webkit-appearance: none;
        appearance: none;
        background-color: #f1f1f1;
        border: 1px solid rgb(0, 0, 0);
        border-radius: 50%;
        min-height: 20px;
        min-width: 20px;
        width: 20px;
        height: 20px;
        outline: none;
        transition: all 0.3s;
        position: relative;
        cursor:pointer;
     }
     
     input[type="radio"]:checked::before {
        content: "";
        background-color: black;
        border-radius: 50%;
        height: 10px;
        position: absolute;
        width: 10px;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        margin: auto;
     }
     .incompleteWindow{
        box-shadow: rgb(0 0 0 / 35%) 0px 5px 15px;
        padding: 20px;
        margin: auto;
        background: var(--background-color);
        color: var(--primary-color);
        max-width: 80%;
        font-size: 14px;
        line-height: 1.5;
        max-height: 90%;
        overflow: hidden auto;
     }
     @media screen and (max-width: 959px){
        input[type="radio"]:checked::before{
            bottom: 0;
        }
     }
</style>
