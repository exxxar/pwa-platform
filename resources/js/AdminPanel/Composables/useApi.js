import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

export function useApi() {
    const router = useRouter()
    const authStore = useAuthStore()

    // Создаем экземпляр axios с базовой конфигурацией
    const apiClient = axios.create({
        baseURL: '/admin', // Базовый URL для всех запросов
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    })

    // Interceptor для добавления токена авторизации
    apiClient.interceptors.request.use(
        (config) => {
            const token = authStore.token
            if (token) {
                config.headers.Authorization = `Bearer ${token}`
            }
            return config
        },
        (error) => {
            return Promise.reject(error)
        }
    )

    // Interceptor для обработки ошибок
    apiClient.interceptors.response.use(
        (response) => {
            return response
        },
        (error) => {
            if (error.response) {
                const { status, data } = error.response

                // Обработка ошибки авторизации
                if (status === 401) {
                    authStore.logout()
                    router.push('/admin/login')
                }

                // Обработка ошибки доступа
                if (status === 403) {
                    console.error('Доступ запрещен:', data.message)
                }

                // Обработка ошибки валидации
                if (status === 422) {
                    console.error('Ошибка валидации:', data.errors)
                }
            }

            return Promise.reject(error)
        }
    )

    /**
     * GET запрос
     */
    const get = async (url, params = {}) => {
        try {
            const response = await apiClient.get(url, { params })
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * POST запрос
     */
    const post = async (url, data = {}) => {
        try {
            const response = await apiClient.post(url, data)
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * PUT запрос
     */
    const put = async (url, data = {}) => {
        try {
            const response = await apiClient.put(url, data)
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * PATCH запрос
     */
    const patch = async (url, data = {}) => {
        try {
            const response = await apiClient.patch(url, data)
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * DELETE запрос
     */
    const del = async (url) => {
        try {
            const response = await apiClient.delete(url)
            return response.data
        } catch (error) {
            throw error
        }
    }

    return {
        apiClient,
        get,
        post,
        put,
        patch,
        del,
    }
}
