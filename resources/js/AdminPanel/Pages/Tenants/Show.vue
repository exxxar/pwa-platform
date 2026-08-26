<template>
    <div class="tenant-show-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ tenant?.name || 'Загрузка...' }}</h1>
                <p class="page-subtitle">/{{ tenant?.slug }}</p>
            </div>
            <div class="header-actions">
                <Button
                    v-if="authStore.hasPermission('tenants.update')"
                    variant="outline"
                    @click="$router.push({ name: 'admin.tenants.edit', params: { id: tenantId } })"
                >
                    ✏️ Редактировать
                </Button>
                <Button
                    v-if="authStore.hasPermission('reports.view')"
                    variant="info"
                    @click="$router.push({ name: 'admin.reports.tenant-stats', params: { tenantId: tenantId } })"
                >
                    📈 Статистика
                </Button>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <span>Загрузка...</span>
        </div>

        <div v-else-if="tenant" class="tenant-content">
            <!-- Main Info -->
            <div class="info-section">
                <h2 class="section-title">Основная информация</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Название</div>
                        <div class="info-value">{{ tenant.name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Slug</div>
                        <div class="info-value">/{{ tenant.slug }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Краткое название</div>
                        <div class="info-value">{{ tenant.short_name || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Статус</div>
                        <div class="info-value">
                            <StatusBadge :variant="tenant.is_active ? 'success' : 'danger'">
                                {{ tenant.is_active ? 'Активен' : 'Неактивен' }}
                            </StatusBadge>
                        </div>
                    </div>
                    <div class="info-item full-width">
                        <div class="info-label">Описание</div>
                        <div class="info-value">{{ tenant.description || '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Financial Info -->
            <div class="info-section">
                <h2 class="section-title">Финансы</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Баланс</div>
                        <div class="info-value balance">{{ formatCurrency(tenant.balance) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Налог в день</div>
                        <div class="info-value">{{ formatCurrency(tenant.tax_per_day) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Тарифный план</div>
                        <div class="info-value">
                            <StatusBadge variant="info">
                                {{ tenant.plan_slug || 'Базовый' }}
                            </StatusBadge>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance -->
            <div class="info-section">
                <h2 class="section-title">Внешний вид</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Цвет темы</div>
                        <div class="info-value">
                            <div class="color-preview" :style="{ background: tenant.theme_color || '#667eea' }"></div>
                            {{ tenant.theme_color || '#667eea' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Цвет фона</div>
                        <div class="info-value">
                            <div class="color-preview" :style="{ background: tenant.background_color || '#ffffff' }"></div>
                            {{ tenant.background_color || '#ffffff' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Тип приложения</div>
                        <div class="info-value">{{ tenant.app_type || '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div v-if="stats" class="info-section">
                <h2 class="section-title">Статистика</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <div class="stat-value">{{ stats.users_count }}</div>
                            <div class="stat-label">Пользователей</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <div class="stat-value">{{ stats.active_users_count }}</div>
                            <div class="stat-label">Активных</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-content">
                            <div class="stat-value">{{ stats.orders_count }}</div>
                            <div class="stat-label">Заказов</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            <div class="info-section">
                <h2 class="section-title">Системная информация</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">UUID</div>
                        <div class="info-value mono">{{ tenant.uuid }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Создан</div>
                        <div class="info-value">{{ formatDate(tenant.created_at) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Обновлен</div>
                        <div class="info-value">{{ formatDate(tenant.updated_at) }}</div>
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

const tenantId = route.params.id
const tenant = ref(null)
const stats = ref(null)
const loading = ref(true)

const loadTenant = async () => {
    loading.value = true
    try {
        const response = await api.get(`/tenants/${tenantId}`, { with_stats: true })
        tenant.value = response.data || response

        // Извлекаем статистику, если она есть
        if (tenant.value.stats) {
            stats.value = tenant.value.stats
            delete tenant.value.stats
        }
    } catch (error) {
        notifications.error('Ошибка при загрузке тенанта')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 2,
    }).format(value || 0)
}

const formatDate = (date) => {
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
    loadTenant()
})
</script>

<style scoped>
.tenant-show-page {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
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

.tenant-content {
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

.info-value.balance {
    font-size: 24px;
    color: #48bb78;
    font-weight: 700;
}

.info-value.mono {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: #4a5568;
}

.color-preview {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 2px solid #e2e8f0;
    margin-right: 8px;
    vertical-align: middle;
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
    font-size: 28px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: #718096;
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
