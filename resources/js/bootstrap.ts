import axios from 'axios'

declare global {
    interface Window {
        axios: typeof axios
    }
}

window.axios = axios

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.interceptors.response.use(
    (response) => {
        return response
    },
    (error) => {
        if (error.response && error.response.status === 401) {
            window.sessionStorage.setItem('loginError', 'セキュリティ保護のためもう一度ログインしてください。')
            window.location.href = '/login'
        } else if (!error.response) {
            // No response = network failure / timeout / CORS. Replace axios's raw
            // English "Network Error" with a localized message so any call site that
            // surfaces error.message shows Japanese. Backend messages
            // (error.response.data.message) are untouched.
            error.message = 'ネットワークエラーが発生しました。通信環境をご確認のうえ、もう一度お試しください。'
        }

        return Promise.reject(error)
    },
)