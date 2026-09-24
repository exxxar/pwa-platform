import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

export function useApi() {
    const router = useRouter()
    const authStore = useAuthStore()

    // Создаем экземпляр axios БЕЗ жёсткого Content-Type
    const apiClient = axios.create({
        baseURL: '/admin',
        headers: {
            'Accept': 'application/json',
            // Убираем Content-Type отсюда — axios сам определит нужный
        },
    })

    // Interceptor для добавления токена и обработки FormData
    apiClient.interceptors.request.use(
        (config) => {
            const token = authStore.token
            if (token) {
                config.headers.Authorization = `Bearer ${token}`
            }

            // 🎯 ВАЖНО: если это FormData, удаляем Content-Type
            // чтобы axios сам добавил multipart/form-data с правильным boundary
            if (config.data instanceof FormData) {
                delete config.headers['Content-Type']
            } else {
                // Для обычных JSON-запросов ставим application/json
                config.headers['Content-Type'] = 'application/json'
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

                if (status === 401) {
                    authStore.logout()
                    router.push('/admin/login')
                }

                if (status === 403) {
                    console.error('Доступ запрещен:', data.message)
                }

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
    const get = async (url, params = {}, config = {}) => {
        try {
            const response = await apiClient.get(url, { params, ...config })
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * POST запрос
     */
    const post = async (url, data = {}, config = {}) => {
        try {
            const response = await apiClient.post(url, data, config)
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * PUT запрос
     */
    const put = async (url, data = {}, config = {}) => {
        try {
            const response = await apiClient.put(url, data, config)
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * PATCH запрос
     */
    const patch = async (url, data = {}, config = {}) => {
        try {
            const response = await apiClient.patch(url, data, config)
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * DELETE запрос
     */
    const del = async (url, config = {}) => {
        try {
            const response = await apiClient.delete(url, config)
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
