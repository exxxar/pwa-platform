import { defineStore } from 'pinia'
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export const useAuthStore = defineStore('auth', () => {
    // usePage() дает доступ к глобальным данным, которые мы передали через Middleware
    const page = usePage()

    // Пользователь берется напрямую из props.auth.user
    const user = computed(() => page.props.auth?.user || null)

    // Флаг авторизации
    const isAuthenticated = computed(() => !!user.value)

    // Собираем все разрешения из всех ролей пользователя в один плоский массив
    const permissions = computed(() => {
        if (!user.value || !user.value.roles) return []

        return user.value.roles.flatMap(role => role.permissions || [])
    })

    // Удобный метод для проверки прав
    const hasPermission = (permissionName) => {
        return permissions.value.includes(permissionName)
    }

    // Метод выхода (Inertia сам обработает редирект, который вернет бэкенд)
    const logout = () => {
        router.post('/admin/logout')
    }

    return {
        user,
        isAuthenticated,
        permissions,
        hasPermission,
        logout,
    }
})
