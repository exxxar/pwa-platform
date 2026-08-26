<template>
    <aside class="sidebar" :class="{ collapsed: uiStore.sidebarCollapsed }">
        <div class="sidebar-header">
            <div class="logo">
                <span v-if="!uiStore.sidebarCollapsed" class="logo-text">Админ-панель</span>
                <span v-else class="logo-icon">АП</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <div class="nav-section">
                <!-- 🆕 Используем Link вместо router-link, и проверяем page.url -->
                <Link
                    href="/admin"
                    class="nav-item"
                    :class="{ active: page.url === '/admin' }"
                >
                    <span class="nav-icon">📊</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Главная</span>
                </Link>
            </div>

            <!-- Global Management -->
            <div v-if="hasAnyGlobalPermission" class="nav-section">
                <div v-if="!uiStore.sidebarCollapsed" class="nav-section-title">Управление</div>

                <Link
                    v-if="authStore.hasPermission('tenants.view')"
                    href="/admin/tenants"
                    class="nav-item"
                    :class="{ active: isActive('/admin/tenants') }"
                >
                    <span class="nav-icon">🏢</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Тенанты</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('admin_users.view')"
                    href="/admin/admin-users"
                    class="nav-item"
                    :class="{ active: isActive('/admin/admin-users') }"
                >
                    <span class="nav-icon">👥</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Администраторы</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('roles.view')"
                    href="/admin/roles"
                    class="nav-item"
                    :class="{ active: isActive('/admin/roles') }"
                >
                    <span class="nav-icon">🛡️</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Роли</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('permissions.view')"
                    href="/admin/permissions"
                    class="nav-item"
                    :class="{ active: isActive('/admin/permissions') }"
                >
                    <span class="nav-icon">🔑</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Разрешения</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('settings.view')"
                    href="/admin/settings"
                    class="nav-item"
                    :class="{ active: isActive('/admin/settings') }"
                >
                    <span class="nav-icon">⚙️</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Настройки</span>
                </Link>
            </div>

            <!-- Tenant Data -->
            <div v-if="hasAnyTenantDataPermission" class="nav-section">
                <div v-if="!uiStore.sidebarCollapsed" class="nav-section-title">Данные тенантов</div>

                <Link
                    v-if="authStore.hasPermission('tenant_users.view')"
                    href="/admin/tenant-users"
                    class="nav-item"
                    :class="{ active: isActive('/admin/tenant-users') }"
                >
                    <span class="nav-icon">👤</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Пользователи</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('orders.view')"
                    href="/admin/orders"
                    class="nav-item"
                    :class="{ active: isActive('/admin/orders') }"
                >
                    <span class="nav-icon">🛒</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Заказы</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('products.view')"
                    href="/admin/products"
                    class="nav-item"
                    :class="{ active: isActive('/admin/products') }"
                >
                    <span class="nav-icon">📦</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Товары</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('transactions.view')"
                    href="/admin/transactions"
                    class="nav-item"
                    :class="{ active: isActive('/admin/transactions') }"
                >
                    <span class="nav-icon">💳</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Транзакции</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('dialogs.view')"
                    href="/admin/dialogs"
                    class="nav-item"
                    :class="{ active: isActive('/admin/dialogs') }"
                >
                    <span class="nav-icon">💬</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Диалоги</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('cashback.view')"
                    href="/admin/cashback"
                    class="nav-item"
                    :class="{ active: isActive('/admin/cashback') }"
                >
                    <span class="nav-icon">🎁</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Кэшбэк</span>
                </Link>

                <Link
                    v-if="authStore.hasPermission('referrals.view')"
                    href="/admin/referrals"
                    class="nav-item"
                    :class="{ active: isActive('/admin/referrals') }"
                >
                    <span class="nav-icon">🔗</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Рефералы</span>
                </Link>
            </div>

            <!-- Reports -->
            <div v-if="authStore.hasPermission('reports.view')" class="nav-section">
                <div v-if="!uiStore.sidebarCollapsed" class="nav-section-title">Аналитика</div>

                <Link
                    href="/admin/reports"
                    class="nav-item"
                    :class="{ active: isActive('/admin/reports') }"
                >
                    <span class="nav-icon">📈</span>
                    <span v-if="!uiStore.sidebarCollapsed" class="nav-label">Отчеты</span>
                </Link>
            </div>
        </nav>

        <div class="sidebar-footer">
            <button
                class="toggle-btn"
                @click="uiStore.toggleSidebar()"
                :title="uiStore.sidebarCollapsed ? 'Развернуть' : 'Свернуть'"
            >
                <span>{{ uiStore.sidebarCollapsed ? '→' : '←' }}</span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3' // 🆕 Импортируем Link и usePage
import { useUiStore } from '../../stores/ui'
import { useAuthStore } from '../../stores/auth'

const uiStore = useUiStore()
const authStore = useAuthStore()

// 🆕 Получаем текущую страницу из Inertia
const page = usePage()

// 🆕 Хелпер для проверки активного состояния (работает как startsWith для вложенных маршрутов)
const isActive = (path) => {
    return page.url === path || page.url.startsWith(path + '/') || page.url.startsWith(path + '?')
}

// Проверка наличия хотя бы одного разрешения из Global
const hasAnyGlobalPermission = computed(() => {
    return (
        authStore.hasPermission('tenants.view') ||
        authStore.hasPermission('admin_users.view') ||
        authStore.hasPermission('roles.view') ||
        authStore.hasPermission('permissions.view') ||
        authStore.hasPermission('settings.view')
    )
})

// Проверка наличия хотя бы одного разрешения из TenantData
const hasAnyTenantDataPermission = computed(() => {
    return (
        authStore.hasPermission('tenant_users.view') ||
        authStore.hasPermission('orders.view') ||
        authStore.hasPermission('products.view') ||
        authStore.hasPermission('transactions.view') ||
        authStore.hasPermission('dialogs.view') ||
        authStore.hasPermission('cashback.view') ||
        authStore.hasPermission('referrals.view')
    )
})
</script>

<style scoped>
/* Ваши стили остаются абсолютно без изменений */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 260px;
    background: #2d3748;
    color: white;
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
    z-index: 1000;
    overflow-x: hidden;
}

.sidebar.collapsed {
    width: 80px;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #4a5568;
}

.logo {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 40px;
}

.logo-text {
    font-size: 20px;
    font-weight: 700;
    white-space: nowrap;
}

.logo-icon {
    font-size: 24px;
    font-weight: 700;
}

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 16px 0;
}

.nav-section {
    margin-bottom: 24px;
}

.nav-section-title {
    padding: 8px 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: #a0aec0;
    letter-spacing: 0.5px;
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #e2e8f0;
    text-decoration: none;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.nav-item:hover {
    background: #4a5568;
    color: white;
}

.nav-item.active {
    background: #4a5568;
    border-left-color: #667eea;
    color: white;
}

.nav-icon {
    font-size: 20px;
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.nav-label {
    font-size: 14px;
    white-space: nowrap;
}

.sidebar-footer {
    padding: 16px;
    border-top: 1px solid #4a5568;
}

.toggle-btn {
    width: 100%;
    padding: 10px;
    background: #4a5568;
    border: none;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    font-size: 18px;
    transition: background 0.2s;
}

.toggle-btn:hover {
    background: #718096;
}

/* Scrollbar */
.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: #2d3748;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: #4a5568;
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: #718096;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.mobile-open {
        transform: translateX(0);
    }
}
</style>
