import axios, { AxiosRequestConfig, AxiosError } from "axios";
import { AskOptions } from "@/interface/globalInterface";
import { useDialog } from "./dialog";
import { Ref } from "vue";

interface ApiOptions {
    respondOptions?: AskOptions;
    ask?: string;
    silent?: boolean;
    toast?: string;
    loadingRef?: Ref<boolean>;
    rawResponse?: boolean;
    cancel?: boolean; // ← 追加: デフォルト true（後で適用）
}

// URLごとのAbortController
const pendingControllers = new Map<string, AbortController>();

const getRequestKey = (url: string) => {
    return url.split("?")[0];
};

const prepareAbortController = (url: string) => {
    const key = getRequestKey(url);

    const prev = pendingControllers.get(key);
    if (prev) {
        prev.abort();
    }

    const controller = new AbortController();
    pendingControllers.set(key, controller);

    return { key, controller };
};

const clearAbortController = (key: string, controller: AbortController) => {
    const current = pendingControllers.get(key);
    if (current === controller) {
        pendingControllers.delete(key);
    }
};

const dialog = useDialog();

export function useApi() {
    const handleAsk = async (options?: ApiOptions) => {
        if (!options?.ask) return true;

        const confirmed = await dialog.ask(options.ask, options.respondOptions);
        return !!confirmed.value;
    };

    const handleError = (error: any, options?: ApiOptions) => {
        // キャンセルは無視
        if (axios.isCancel?.(error) || error?.code === "ERR_CANCELED") {
            return;
        }

        console.error("API request failed:", error);
        if (!options?.silent) {
            const axiosErr = error as AxiosError<any>;
            const msg =
                axiosErr.response?.data?.message ||
                (error as any)?.message ||
                "エラーが発生しました。";
            dialog.ping(msg);
        }
    };

    const get = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;

        const pass = await handleAsk(options);
        if (!pass) {
            if (loadingRef) loadingRef.value = false;
            return null;
        }

        const useCancel = options?.cancel ?? false;
        let key: string | null = null;
        let controller: AbortController | null = null;

        if (useCancel) {
            const prepared = prepareAbortController(url);
            key = prepared.key;
            controller = prepared.controller;
        }

        try {
            const response = await axios.get(url, {
                params: data,
                ...axiosOptions,
                // cancel === false のとき signal は渡さない
                ...(controller ? { signal: controller.signal } : {}),
            });

            if (options?.toast) {
                setTimeout(() => dialog.toast(options.toast!), 400);
            }

            return options?.rawResponse ? response : response.data;
        } catch (error: any) {
            handleError(error, options);
            if (!axios.isCancel?.(error) && error?.code !== "ERR_CANCELED") {
                throw error;
            }
            return null;
        } finally {
            if (key && controller) {
                clearAbortController(key, controller);
            }
            if (loadingRef) loadingRef.value = false;
        }
    };

    const post = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;

        const pass = await handleAsk(options);
        if (!pass) {
            if (loadingRef) loadingRef.value = false;
            return null;
        }

        const useCancel = options?.cancel ?? true;
        let key: string | null = null;
        let controller: AbortController | null = null;

        if (useCancel) {
            const prepared = prepareAbortController(url);
            key = prepared.key;
            controller = prepared.controller;
        }

        try {
            const response = await axios.post(url, data, {
                ...axiosOptions,
                ...(controller ? { signal: controller.signal } : {}),
            });

            if (options?.toast) {
                setTimeout(() => dialog.toast(options.toast!), 400);
            }

            return options?.rawResponse ? response : response.data;
        } catch (error: any) {
            handleError(error, options);
            if (!axios.isCancel?.(error) && error?.code !== "ERR_CANCELED") {
                console.error("throw", error);
                throw error;
            }
            return null;
        } finally {
            if (key && controller) {
                clearAbortController(key, controller);
            }
            if (loadingRef) loadingRef.value = false;
        }
    };

    const put = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;

        const pass = await handleAsk(options);
        if (!pass) {
            if (loadingRef) loadingRef.value = false;
            return null;
        }

        const useCancel = options?.cancel ?? true;
        let key: string | null = null;
        let controller: AbortController | null = null;

        if (useCancel) {
            const prepared = prepareAbortController(url);
            key = prepared.key;
            controller = prepared.controller;
        }

        try {
            const response = await axios.put(url, data, {
                ...axiosOptions,
                ...(controller ? { signal: controller.signal } : {}),
            });

            if (options?.toast) {
                setTimeout(() => dialog.toast(options.toast!), 400);
            }

            return response.data;
        } catch (error: any) {
            handleError(error, options);
            if (!axios.isCancel?.(error) && error?.code !== "ERR_CANCELED") {
                throw error;
            }
            return null;
        } finally {
            if (key && controller) {
                clearAbortController(key, controller);
            }
            if (loadingRef) loadingRef.value = false;
        }
    };

    const patch = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;

        const pass = await handleAsk(options);
        if (!pass) {
            if (loadingRef) loadingRef.value = false;
            return null;
        }

        const useCancel = options?.cancel ?? true;
        let key: string | null = null;
        let controller: AbortController | null = null;

        if (useCancel) {
            const prepared = prepareAbortController(url);
            key = prepared.key;
            controller = prepared.controller;
        }

        try {
            const response = await axios.patch(url, data, {
                ...axiosOptions,
                ...(controller ? { signal: controller.signal } : {}),
            });

            if (options?.toast) {
                setTimeout(() => dialog.toast(options.toast!), 400);
            }

            return response.data;
        } catch (error: any) {
            handleError(error, options);
            if (!axios.isCancel?.(error) && error?.code !== "ERR_CANCELED") {
                throw error;
            }
            return null;
        } finally {
            if (key && controller) {
                clearAbortController(key, controller);
            }
            if (loadingRef) loadingRef.value = false;
        }
    };

    const del = async (
        url: string,
        data: any = null,
        options?: ApiOptions,
        axiosOptions?: AxiosRequestConfig
    ) => {
        const loadingRef = options?.loadingRef;
        if (loadingRef) loadingRef.value = true;

        const pass = await handleAsk(options);
        if (!pass) {
            if (loadingRef) loadingRef.value = false;
            return null;
        }

        const useCancel = options?.cancel ?? true;
        let key: string | null = null;
        let controller: AbortController | null = null;

        if (useCancel) {
            const prepared = prepareAbortController(url);
            key = prepared.key;
            controller = prepared.controller;
        }

        try {
            const response = await axios.delete(url, {
                data,
                ...axiosOptions,
                ...(controller ? { signal: controller.signal } : {}),
            });

            if (options?.toast) {
                setTimeout(() => dialog.toast(options.toast!), 400);
            }

            return response.data;
        } catch (error: any) {
            handleError(error, options);
            if (!axios.isCancel?.(error) && error?.code !== "ERR_CANCELED") {
                throw error;
            }
            return null;
        } finally {
            if (key && controller) {
                clearAbortController(key, controller);
            }
            if (loadingRef) loadingRef.value = false;
        }
    };

    return {
        get,
        post,
        put,
        del,
        patch,
    };
}
