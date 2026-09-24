import axios from 'axios'

export function useMobileApi() {
    const apiClient = axios.create({
        baseURL: '/', // Без префикса /admin
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })

    // Добавляем CSRF токен
    apiClient.interceptors.request.use((config) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token
        }
        return config
    })

    const get = async (url, params = {}) => {
        const response = await apiClient.get(url, { params })
        return response.data
    }

    return { get }
}
