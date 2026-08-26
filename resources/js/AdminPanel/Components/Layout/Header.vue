<template>
    <header class="header">
        <div class="header-left">
            <button class="mobile-menu-btn" @click="toggleMobileMenu" title="Меню">☰</button>

            <nav class="breadcrumbs" v-if="breadcrumbs.length > 0">
                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                    <Link v-if="crumb.href" :href="crumb.href" class="breadcrumb-item">
                        {{ crumb.label }}
                    </Link>
                    <span v-else class="breadcrumb-item current">{{ crumb.label }}</span>
                    <span v-if="index < breadcrumbs.length - 1" class="breadcrumb-separator">/</span>
                </template>
            </nav>
        </div>

        <div class="header-right">
            <div class="user-menu" ref="userMenuRef">
                <button class="user-btn" @click="toggleUserMenu" title="Меню пользователя">
                    <div class="user-avatar">{{ userInitials }}</div>
                    <div class="user-info" v-if="!isMobile">
                        <div class="user-name">{{ authStore.user?.name || 'Администратор' }}</div>
                        <div class="user-email">{{ authStore.user?.email || '' }}</div>
                    </div>
                    <span class="dropdown-arrow">▼</span>
                </button>

                <!-- 🆕 ИСПРАВЛЕНО: переименовали классы, чтобы Bootstrap их не скрывал -->
                <div v-if="showUserMenu" class="user-dropdown-menu">
                    <div class="user-dropdown-header">
                        <div class="user-dropdown-name">{{ authStore.user?.name || 'Администратор' }}</div>
                        <div class="user-dropdown-email">{{ authStore.user?.email || '' }}</div>
                    </div>
                    <div class="user-dropdown-divider"></div>

                    <Link href="/admin/profile" class="user-dropdown-item">
                        <span class="dropdown-icon">👤</span>
                        Мой профиль
                    </Link>

                    <Link href="/admin/settings" class="user-dropdown-item">
                        <span class="dropdown-icon">⚙️</span>
                        Настройки
                    </Link>

                    <div class="user-dropdown-divider"></div>

                    <button class="user-dropdown-item user-dropdown-logout" @click="handleLogout">
                        <span class="dropdown-icon">🚪</span>
                        Выйти из системы
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
const page = usePage()

const showUserMenu = ref(false)
const userMenuRef = ref(null)
const isMobile = ref(typeof window !== 'undefined' ? window.innerWidth <= 768 : false)

// 🛡️ Безопасное вычисление инициалов
const userInitials = computed(() => {
    const name = String(authStore.user?.name || 'Администратор')
    const parts = name.trim().split(' ')
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
})

// 🛡️ Безопасное вычисление breadcrumbs с защитой от undefined
const breadcrumbs = computed(() => {
    // Если page или url еще не готовы, возвращаем пустой массив
    if (!page || !page.url) {
        return []
    }

    try {
        const path = page.url.split('?')[0]

        if (path === '/admin' || path === '/admin/') {
            return [{ label: 'Главная', href: null }]
        }

        const parts = path.split('/').filter(Boolean)
        const crumbs = [{ label: 'Главная', href: '/admin' }]

        const labelsMap = {
            'tenants': 'Тенанты',
            'admin-users': 'Администраторы',
            'roles': 'Роли',
            'permissions': 'Разрешения',
            'settings': 'Настройки',
            'profile': 'Профиль',
            'tenant-users': 'Пользователи',
            'orders': 'Заказы',
            'products': 'Товары',
            'transactions': 'Транзакции',
            'dialogs': 'Диалоги',
            'cashback': 'Кэшбэк',
            'referrals': 'Рефералы',
            'reports': 'Отчеты',
            'dashboard': 'Главная'
        }

        let currentPath = ''
        parts.forEach((part, index) => {
            if (part === 'admin') return

            currentPath += '/' + part

            if (!isNaN(part)) {
                crumbs.push({ label: 'Просмотр', href: null })
            } else {
                const isLast = index === parts.length - 1
                crumbs.push({
                    label: labelsMap[part] || part.charAt(0).toUpperCase() + part.slice(1),
                    href: isLast ? null : currentPath
                })
            }
        })

        return crumbs
    } catch (error) {
        console.warn('Ошибка при построении breadcrumbs:', error)
        return [] // Возвращаем пустой массив в случае любой ошибки
    }
})

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value
}

const handleClickOutside = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        showUserMenu.value = false
    }
}

const handleLogout = () => {
    showUserMenu.value = false
    router.post('/admin/logout')
}

const toggleMobileMenu = () => {
    console.log('Toggle mobile menu')
}

const handleResize = () => {
    isMobile.value = window.innerWidth <= 768
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.header {
    height: 64px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    position: relative;
    z-index: 50;
}

/* ... (ваши стили для header-left, breadcrumbs и user-btn остаются без изменений) ... */

.user-menu {
    position: relative;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: background 0.2s;
}

.user-btn:hover {
    background: #f7fafc;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    flex-shrink: 0;
}

.user-info {
    text-align: left;
}

.user-name {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
}

.user-email {
    font-size: 12px;
    color: #718096;
}

.dropdown-arrow {
    font-size: 10px;
    color: #718096;
}

/* 🆕 ИСПРАВЛЕНО: Уникальные классы + явный display: block */
.user-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    min-width: 240px;
    z-index: 9999;
    overflow: hidden;
    display: block; /* 🆕 Принудительно показываем, перебивая Bootstrap */
    animation: fadeIn 0.15s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

.user-dropdown-header {
    padding: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.user-dropdown-name {
    font-size: 14px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 4px;
}

.user-dropdown-email {
    font-size: 12px;
    color: #718096;
    word-break: break-all;
}

.user-dropdown-divider {
    height: 1px;
    background: #e2e8f0;
}

.user-dropdown-item {
    width: 100%;
    padding: 12px 16px;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #2d3748;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
}

.user-dropdown-item:hover {
    background: #f7fafc;
    color: #667eea;
}

.user-dropdown-logout:hover {
    background: #fee2e2;
    color: #dc2626;
}

.dropdown-icon {
    font-size: 18px;
    width: 20px;
    text-align: center;
}

@media (max-width: 768px) {
    .header { padding: 0 16px; }
    .user-info { display: none; }
}
</style>
