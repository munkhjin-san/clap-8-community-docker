import { AskOptions, DecisionOption } from "@/interface/globalInterface";
import { ref, watch } from "vue";


const askData = ref<string|null>(null);
const pingData = ref<string|null>(null)
const decision = ref<DecisionOption>({value: false, label: ''});
const toastData = ref(null)
const respondOptions = ref<AskOptions>({answers: []})

export function useDialog() {


    const resetDialog = () => {
        askData.value = null
        pingData.value = null
        decision.value = {value: false, label: ''}
        toastData.value = null
        respondOptions.value = {answers: []}
    }
    const ask = async (question:string, options?:AskOptions): Promise<DecisionOption> => {
        resetDialog()
        if(options && options.answers && options.answers.length){
            respondOptions.value = options
        }else{
            respondOptions.value = {answers : [
                {value: true, label: 'OK'},
                {value: false, label: 'キャンセル'}
            ]}
        }

        decision.value = {value: false, label: ''}
        pingData.value = null
        askData.value = question;
        
        await new Promise((resolve) => {
            const unsubscribe = watch(() => decision.value, (value) => {
                if (value !== null) {
                    unsubscribe();
                    resolve(value);
                }
            });
        });    
        return decision.value;        
    };
    const ping = (message:string) => {
        resetDialog()
        respondOptions.value = {answers : [
            {value: true, label: 'OK'}
        ]}
        pingData.value = message
    }
    const toast = (message) => {
        resetDialog()
        toastData.value = null
        toastData.value = message
        setTimeout(() => {
            toastData.value = null
        }, 4000);
    }
    return {
        ask,
        ping,
        toast,
        resetDialog,
        askData,
        pingData,
        toastData,
        respondOptions,
        decision
    };
}