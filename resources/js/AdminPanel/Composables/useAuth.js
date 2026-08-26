import { computed } from 'vue'
import { useAuthStore } from '../Stores/auth'
import { useApi } from './useApi'

export function useAuth() {
    const authStore = useAuthStore()
    const api = useApi()

    const user = computed(() => authStore.user)
    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const permissions = computed(() => authStore.permissions)

    /**
     * Вход в систему
     */
    const login = async (email, password, remember = false) => {
        try {
            const response = await api.post('/login', {
                email,
                password,
                remember,
            })

            authStore.setToken(response.token)
            authStore.setUser(response.user)

            return { success: true }
        } catch (error) {
            const message = error.response?.data?.message || 'Ошибка входа'
            return { success: false, message }
        }
    }

    /**
     * Выход из системы
     */
    const logout = async () => {
        try {
            await api.post('/logout')
        } catch (error) {
            console.error('Ошибка при выходе:', error)
        } finally {
            authStore.logout()
        }
    }

    /**
     * Проверка наличия разрешения
     */
    const hasPermission = (permission) => {
        return permissions.value.includes(permission)
    }

    /**
     * Проверка наличия роли
     */
    const hasRole = (role) => {
        return user.value?.roles?.some(r => r.name === role) || false
    }

    /**
     * Получить текущего пользователя
     */
    const getUser = async () => {
        try {
            const response = await api.get('/user')
            authStore.setUser(response)
            return response
        } catch (error) {
            throw error
        }
    }

    return {
        user,
        isAuthenticated,
        permissions,
        login,
        logout,
        hasPermission,
        hasRole,
        getUser,
    }
}
