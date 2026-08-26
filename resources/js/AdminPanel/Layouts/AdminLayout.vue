<template>
    <div class="admin-layout" :class="{ 'sidebar-collapsed': uiStore.sidebarCollapsed }">
        <Sidebar />
        <div class="admin-main">
            <Header />
            <main class="admin-content">
                <slot></slot>
            </main>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useUiStore } from '../stores/ui'
import { useAuthStore } from '../stores/auth'
import { useApi } from '../composables/useApi'
import Sidebar from '../components/Layout/Sidebar.vue'
import Header from '../components/Layout/Header.vue'

const uiStore = useUiStore()
const authStore = useAuthStore()
const api = useApi()

onMounted(async () => {
    // Загружаем данные пользователя, если они еще не загружены
    if (authStore.isAuthenticated && !authStore.user) {
        try {
            const response = await api.get('/user')
            authStore.setUser(response)
        } catch (error) {
            console.error('Ошибка загрузки пользователя:', error)
        }
    }
})
</script>

<style scoped>
.admin-layout {
    display: flex;
    min-height: 100vh;
    background: #f7fafc;
}

.admin-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    margin-left: 260px;
    transition: margin-left 0.3s ease;
}

.sidebar-collapsed .admin-main {
    margin-left: 80px;
}

.admin-content {
    flex: 1;
    padding: 24px;
    overflow-x: auto;
}

@media (max-width: 768px) {
    .admin-main {
        margin-left: 0;
    }
}
</style>
