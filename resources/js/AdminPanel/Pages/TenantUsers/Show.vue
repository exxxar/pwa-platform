<template>
    <div class="tenant-user-show-page">
        <div class="page-header">
            <div class="user-header">
                <div class="user-avatar-large">
                    <img v-if="user?.avatar" :src="user.avatar" :alt="user.name" />
                    <span v-else>{{ user?.name?.charAt(0).toUpperCase() }}</span>
                </div>
                <div>
                    <h1 class="page-title">{{ user?.name || 'Загрузка...' }}</h1>
                    <p class="page-subtitle">{{ user?.phone || user?.email || 'Контакты не указаны' }}</p>
                </div>
            </div>
            <div class="header-actions">
                <Button
                    v-if="authStore.hasPermission('tenant_users.update')"
                    variant="outline"
                    @click="$router.push({ name: 'admin.tenant-users.edit', params: { id: userId } })"
                >
                    ✏️ Редактировать
                </Button>
                <Button
                    v-if="authStore.hasPermission('tenant_users.block')"
                    :variant="user?.is_blocked ? 'success' : 'danger'"
                    @click="toggleBlock"
                >
                    {{ user?.is_blocked ? '🔓 Разблокировать' : '🔒 Заблокировать' }}
                </Button>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <span>Загрузка...</span>
        </div>

        <div v-else-if="user" class="user-content">
            <!-- Status Badges -->
            <div class="status-badges">
                <StatusBadge :variant="user.is_active && !user.is_blocked ? 'success' : 'danger'" size="lg">
                    {{ user.is_blocked ? 'Заблокирован' : (user.is_active ? 'Активен' : 'Неактивен') }}
                </StatusBadge>
                <StatusBadge v-if="user.has_active_vip" variant="warning" size="lg">
                    👑 VIP {{ user.vip_days_left !== null ? `(${user.vip_days_left} дн.)` : '(бессрочно)' }}
                </StatusBadge>
            </div>

            <!-- Main Info -->
            <div class="info-section">
                <h2 class="section-title">Основная информация</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Имя</div>
                        <div class="info-value">{{ user.name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <div class="info-value">{{ user.phone || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ user.email || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Пол</div>
                        <div class="info-value">{{ getSexLabel(user.sex) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Дата рождения</div>
                        <div class="info-value">{{ formatDate(user.birthday) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Город</div>
                        <div class="info-value">{{ user.city || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Страна</div>
                        <div class="info-value">{{ user.country || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Адрес</div>
                        <div class="info-value">{{ user.address || '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- VIP Info -->
            <div v-if="user.is_vip" class="info-section">
                <h2 class="section-title">VIP статус</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Статус</div>
                        <div class="info-value">
                            <StatusBadge :variant="user.has_active_vip ? 'success' : 'danger'">
                                {{ user.has_active_vip ? 'Активен' : 'Истек' }}
                            </StatusBadge>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Активирован</div>
                        <div class="info-value">{{ formatDateTime(user.vip_activated_at) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Истекает</div>
                        <div class="info-value">
                            {{ user.vip_expires_at ? formatDateTime(user.vip_expires_at) : 'Бессрочно' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Осталось дней</div>
                        <div class="info-value">
                            {{ user.vip_days_left !== null ? user.vip_days_left : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Block Info -->
            <div v-if="user.is_blocked" class="info-section">
                <h2 class="section-title">Информация о блокировке</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Заблокирован</div>
                        <div class="info-value">{{ formatDateTime(user.blocked_at) }}</div>
                    </div>
                    <div class="info-item full-width">
                        <div class="info-label">Причина</div>
                        <div class="info-value">{{ user.blocked_message || 'Не указана' }}</div>
                    </div>
                </div>
            </div>

            <!-- Financial Info -->
            <div class="info-section">
                <h2 class="section-title">Финансы</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <div class="stat-value">{{ formatCurrency(user.cashback_balance) }}</div>
                            <div class="stat-label">Кэшбэк</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-content">
                            <div class="stat-value">{{ user.orders_count || 0 }}</div>
                            <div class="stat-label">Заказов</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💵</div>
                        <div class="stat-content">
                            <div class="stat-value">{{ formatCurrency(user.total_referral_earnings) }}</div>
                            <div class="stat-label">Реферальный доход</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referral Info -->
            <div class="info-section">
                <h2 class="section-title">Реферальная система</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Реферальный код</div>
                        <div class="info-value mono">{{ user.referral_code || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Реферальная ссылка</div>
                        <div class="info-value">
                            <a v-if="user.referral_link" :href="user.referral_link" target="_blank" class="link">
                                {{ user.referral_link }}
                            </a>
                            <span v-else>-</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Пригласил</div>
                        <div class="info-value">
                            <router-link
                                v-if="user.referrer"
                                :to="{ name: 'admin.tenant-users.show', params: { id: user.referrer.id } }"
                                class="link"
                            >
                                {{ user.referrer.name }}
                            </router-link>
                            <span v-else>-</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Приглашено</div>
                        <div class="info-value">{{ user.referrals_count || 0 }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Друзей</div>
                        <div class="info-value">{{ user.friends_count || 0 }}</div>
                    </div>
                </div>
            </div>

            <!-- Roles -->
            <div v-if="user.roles && user.roles.length > 0" class="info-section">
                <h2 class="section-title">Роли</h2>
                <div class="roles-list">
                    <StatusBadge
                        v-for="role in user.roles"
                        :key="role.id"
                        variant="primary"
                        size="lg"
                    >
                        {{ role.label || role.name }}
                    </StatusBadge>
                </div>
            </div>

            <!-- Addresses -->
            <div v-if="user.addresses && user.addresses.length > 0" class="info-section">
                <h2 class="section-title">Адреса</h2>
                <div class="addresses-list">
                    <div
                        v-for="address in user.addresses"
                        :key="address.id"
                        class="address-card"
                        :class="{ 'is-default': address.is_default }"
                    >
                        <div class="address-header">
                            <div class="address-title">{{ address.title || 'Адрес' }}</div>
                            <StatusBadge v-if="address.is_default" variant="success" size="sm">
                                По умолчанию
                            </StatusBadge>
                        </div>
                        <div class="address-text">{{ address.address }}</div>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="info-section">
                <h2 class="section-title">Системная информация</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ID</div>
                        <div class="info-value mono">{{ user.id }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">UUID</div>
                        <div class="info-value mono">{{ user.uuid }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Зарегистрирован</div>
                        <div class="info-value">{{ formatDateTime(user.created_at) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Обновлен</div>
                        <div class="info-value">{{ formatDateTime(user.updated_at) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '../../composables/useApi'
import { useAuthStore } from '../../stores/auth'
import { useNotifications } from '../../composables/useNotifications'
import Button from '../../components/UI/Button.vue'
import StatusBadge from '../../components/UI/StatusBadge.vue'

const route = useRoute()
const api = useApi()
const authStore = useAuthStore()
const notifications = useNotifications()

const userId = route.params.id
const user = ref(null)
const loading = ref(true)

const loadUser = async () => {
    loading.value = true
    try {
        const response = await api.get(`/tenant-users/${userId}`)
        user.value = response.data || response
    } catch (error) {
        notifications.error('Ошибка при загрузке пользователя')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const toggleBlock = async () => {
    try {
        await api.patch(`/tenant-users/${userId}/toggle-block`)
        notifications.success(
            user.value.is_blocked ? 'Пользователь разблокирован' : 'Пользователь заблокирован'
        )
        loadUser()
    } catch (error) {
        notifications.error('Ошибка при изменении статуса')
    }
}

const getSexLabel = (sex) => {
    const labels = {
        male: 'Мужской',
        female: 'Женский',
        other: 'Другой',
    }
    return labels[sex] || '-'
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

onMounted(() => {
    loadUser()
})
</script>

<style scoped>
.tenant-user-show-page {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
}

.user-header {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 32px;
    flex-shrink: 0;
    overflow: hidden;
}

.user-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 8px 0;
}

.page-subtitle {
    font-size: 16px;
    color: #718096;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 60px;
    color: #718096;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e2e8f0;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.status-badges {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.user-content {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.info-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-size: 13px;
    font-weight: 500;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 15px;
    color: #2d3748;
    font-weight: 500;
}

.info-value.mono {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: #4a5568;
}

.link {
    color: #667eea;
    text-decoration: none;
    transition: color 0.2s;
}

.link:hover {
    color: #764ba2;
    text-decoration: underline;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #f7fafc;
    border-radius: 8px;
}

.stat-icon {
    font-size: 32px;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: #718096;
}

.roles-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.addresses-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
}

.address-card {
    padding: 16px;
    background: #f7fafc;
    border-radius: 8px;
    border: 2px solid transparent;
}

.address-card.is-default {
    border-color: #48bb78;
}

.address-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.address-title {
    font-weight: 600;
    color: #1a202c;
}

.address-text {
    font-size: 14px;
    color: #4a5568;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .header-actions {
        width: 100%;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
