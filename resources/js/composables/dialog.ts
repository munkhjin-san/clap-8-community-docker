import { AskOptions, DecisionOption } from "@/interface/globalInterface";
import { ref, watch } from "vue";

type InputOptions = {
  value?: string
  placeholder?: string
  label?: string
  submitText?: string
  required?: boolean
  selectBaseName?: boolean
  multiline?: boolean
  maxLength?: number
  validate?: (v: string) => string | null
};
const askData = ref<string|null>(null);
const pingData = ref<string|null>(null)
const decision = ref<DecisionOption>({value: false, label: ''});
const toastData = ref<string|null>(null)
const respondOptions = ref<AskOptions>({answers: []})
const inputOptions = ref<InputOptions | null>(null);
const inputResult = ref<string | null>(null);
export function useDialog() {


    const resetDialog = () => {
        askData.value = null
        pingData.value = null
        decision.value = {value: false, label: ''}
        toastData.value = null
        respondOptions.value = {answers: []}
        inputOptions.value = null;             // NEW
        inputResult.value = null;  
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
    const askInput = async (
        question: string,
        iopts: InputOptions,
        options?: AskOptions
    ): Promise<{ input: string | null; decision: DecisionOption }> => {
        resetDialog();
        respondOptions.value = options?.answers?.length
        ? options
        : { answers: [{ value: true, label: iopts.submitText || 'OK' }, { value: false, label: 'キャンセル' }] };

        inputOptions.value = iopts;
        inputResult.value = null;
        askData.value = question;

        await new Promise(resolve => {
        const stop = watch(() => decision.value, v => {
            if (v !== null) { stop(); resolve(v); }
        });
        });

        return { input: inputResult.value, decision: decision.value };
    };
    const ping = (message:string) => {
        resetDialog()
        respondOptions.value = {answers : [
            {value: true, label: 'OK'}
        ]}
        pingData.value = message
    }
    const toast = (message:string) => {
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
        askInput,
        toast,
        resetDialog,
        askData,
        pingData,
        toastData,
        respondOptions,
        decision,
        inputOptions,
        inputResult
    };
}