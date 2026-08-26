import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
    // Состояние
    const sidebarCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')
    const theme = ref(localStorage.getItem('admin_theme') || 'light')
    const loading = ref(false)

    // Действия
    const toggleSidebar = () => {
        sidebarCollapsed.value = !sidebarCollapsed.value
        localStorage.setItem('sidebar_collapsed', sidebarCollapsed.value)
    }

    const setSidebarCollapsed = (collapsed) => {
        sidebarCollapsed.value = collapsed
        localStorage.setItem('sidebar_collapsed', collapsed)
    }

    const setTheme = (newTheme) => {
        theme.value = newTheme
        localStorage.setItem('admin_theme', newTheme)
        document.documentElement.setAttribute('data-theme', newTheme)
    }

    const setLoading = (isLoading) => {
        loading.value = isLoading
    }

    return {
        sidebarCollapsed,
        theme,
        loading,
        toggleSidebar,
        setSidebarCollapsed,
        setTheme,
        setLoading,
    }
})
