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
        }

        return Promise.reject(error)
    },
)