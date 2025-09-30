import axios, { AxiosRequestConfig } from "axios";
import { AskOptions, DecisionOption } from "@/interface/globalInterface";
import { useDialog } from "./dialog";
import { Ref } from "vue";
interface ApiOptions {
    respondOptions?: AskOptions;
    ask?:string
    silent?: boolean;
    toast?: string
    loadingRef?: Ref<boolean>;
    rawResponse?: boolean;
}
const dialog = useDialog()
export function useApi() {

    const get = async (
        url: string, 
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;
        try {

            let pass = true;
            if (options && options.ask) {
                const confirmed = await dialog.ask(options.ask, options.respondOptions)
                if (!confirmed.value) {
                    pass = false; 
                }
                
            }
            if (!pass) {
                return null; // Exit if the user did not confirm
            }
            const response = await axios.get(url, {
                params: data,
                ...axiosOptions
            });
            if (options?.toast) {
                setTimeout(() => {
                    dialog.toast(options.toast)
                }, 400);
                
            }
            return options?.rawResponse ? response : response.data
        } catch (error) {
            console.error('API request failed:', error);
            if(!options?.silent) {
                dialog.ping(error.response?.data.message || error?.message || 'エラーが発生しました。')
                throw error;
            }
        }
        finally { 
            if (loadingRef) loadingRef.value = false;
        }
    }
    const post = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;
        try {
            let pass = true;
            if (options && options.ask) {
                const confirmed = await dialog.ask(options.ask, options.respondOptions) 
                if (!confirmed.value) {
                    pass = false; 
                }   
            }
            if (!pass) {
                return null; // Exit if the user did not confirm
            }
            const response = await axios.post(url, data, axiosOptions);
            if (options?.toast) {
                setTimeout(() => {
                    dialog.toast(options.toast)
                }, 400);
            }
            return options?.rawResponse ? response : response.data
        } catch (error) {
            console.error('API request failed:', error);
            if(!options?.silent) {
                console.error('throw', error);
                dialog.ping(error.response?.data.message || error?.message || 'エラーが発生しました。')
                throw error;
            }
        }

        finally { 
            if (loadingRef) loadingRef.value = false;
        }
    }
    const put = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;
        try {
            let pass = true;
            if (options && options.ask) {
                const confirmed = await dialog.ask(options.ask, options.respondOptions)
                if (!confirmed.value) {
                    pass = false; 
                }   
            }
            if (!pass) {
                return null; // Exit if the user did not confirm
            }
            const response = await axios.put(url, data, axiosOptions);
            if (options?.toast) {
                setTimeout(() => {
                    dialog.toast(options.toast)
                }, 400);
            }
            return response.data;
        } catch (error) {
            console.error('API request failed:', error);
            if(!options?.silent) {
                dialog.ping(error.response?.data.message || error?.message || 'エラーが発生しました。')
                throw error;
            }
        }
        finally { 
            if (loadingRef) loadingRef.value = false;
        }
    }
    const patch = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;
        try {
            let pass = true;
            if (options && options.ask) {
                const confirmed = await dialog.ask(options.ask, options.respondOptions)
                if (!confirmed.value) {
                    pass = false; 
                }   
            }
            if (!pass) {
                return null; // Exit if the user did not confirm
            }
            const response = await axios.patch(url, data, axiosOptions);
            if (options?.toast) {
                setTimeout(() => {
                    dialog.toast(options.toast)
                }, 400);
            }
            return response.data;
        } catch (error) {
            console.error('API request failed:', error);
            if(!options?.silent) {
                dialog.ping(error.response?.data.message || error?.message || 'エラーが発生しました。')
                throw error;
            }
        }
        finally { 
            if (loadingRef) loadingRef.value = false;
        }
    }
    const del = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;
        try {
            let pass = true;
            if (options && options.ask) {
                const confirmed = await dialog.ask(options.ask, options.respondOptions)
                if (!confirmed.value) {
                    pass = false; 
                }
            }
            if (!pass) {
                return null; // Exit if the user did not confirm
            }
            const response = await axios.delete(url, {
                data: data,
                ...axiosOptions
            });
            if (options?.toast) {
                setTimeout(() => {
                    dialog.toast(options.toast)
                }, 400);
            }
            return response.data;
        } catch (error) {
            console.error('API request failed:', error);
            if(!options?.silent) {
                dialog.ping(error.response?.data.message || error?.message || 'エラーが発生しました。')
                throw error;
            }
        }
        finally { 
            if (loadingRef) loadingRef.value = false;
        }
    }

    return {
        get,
        post,
        put,
        del,
        patch
    };
}