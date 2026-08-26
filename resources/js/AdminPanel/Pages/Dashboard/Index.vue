<template>
    <div class="dashboard">
        <div class="page-header">
            <h1 class="page-title">Главная</h1>
            <p class="page-subtitle">Общая статистика платформы</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon tenants">🏢</div>
                <div class="stat-content">
                    <div class="stat-label">Тенанты</div>
                    <div class="stat-value">{{ stats.tenants?.total || 0 }}</div>
                    <div class="stat-change">
                        <span class="active">{{ stats.tenants?.active || 0 }} активных</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon users">👥</div>
                <div class="stat-content">
                    <div class="stat-label">Пользователи</div>
                    <div class="stat-value">{{ stats.users?.total || 0 }}</div>
                    <div class="stat-change">
                        <span class="active">{{ stats.users?.active || 0 }} активных</span>
                        <span class="separator">•</span>
                        <span class="vip">{{ stats.users?.vip || 0 }} VIP</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orders">🛒</div>
                <div class="stat-content">
                    <div class="stat-label">Заказы</div>
                    <div class="stat-value">{{ stats.orders?.total || 0 }}</div>
                    <div class="stat-change">
                        <span class="today">{{ stats.orders?.today || 0 }} сегодня</span>
                        <span class="separator">•</span>
                        <span class="paid">{{ stats.orders?.paid || 0 }} оплачено</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon revenue">💰</div>
                <div class="stat-content">
                    <div class="stat-label">Выручка</div>
                    <div class="stat-value">{{ formatCurrency(stats.revenue?.total || 0) }}</div>
                    <div class="stat-change">
                        <span class="today">{{ formatCurrency(stats.revenue?.today || 0) }} сегодня</span>
                        <span class="separator">•</span>
                        <span class="month">{{ formatCurrency(stats.revenue?.month || 0) }} за месяц</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Tenants -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Топ тенантов по выручке</h2>
                <select v-model="topTenantsPeriod" @change="loadTopTenants" class="period-select">
                    <option value="month">За месяц</option>
                    <option value="year">За год</option>
                    <option value="all">За все время</option>
                </select>
            </div>

            <div class="top-tenants-list">
                <div v-if="loadingTopTenants" class="loading-state">
                    <div class="spinner"></div>
                    <span>Загрузка...</span>
                </div>

                <div v-else-if="topTenants.length === 0" class="empty-state">
                    <span class="empty-icon">📊</span>
                    <p>Нет данных для отображения</p>
                </div>

                <div v-else class="tenants-grid">
                    <div
                        v-for="(tenant, index) in topTenants"
                        :key="tenant.tenant_id"
                        class="tenant-card"
                    >
                        <div class="tenant-rank">
                            <span class="rank-number">{{ index + 1 }}</span>
                        </div>
                        <div class="tenant-info">
                            <div class="tenant-name">{{ tenant.tenant_name }}</div>
                            <div class="tenant-revenue">{{ formatCurrency(tenant.total_revenue) }}</div>
                        </div>
                        <div class="tenant-bar">
                            <div
                                class="bar-fill"
                                :style="{ width: getBarWidth(tenant.total_revenue) + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Быстрые действия</h2>
            </div>

            <div class="quick-actions">
                <router-link
                    v-if="authStore.hasPermission('tenants.create')"
                    :to="{ name: 'admin.tenants.create' }"
                    class="action-card"
                >
                    <span class="action-icon">➕</span>
                    <span class="action-label">Создать тенанта</span>
                </router-link>

                <router-link
                    v-if="authStore.hasPermission('admin_users.create')"
                    :to="{ name: 'admin.admin-users.create' }"
                    class="action-card"
                >
                    <span class="action-icon">👤</span>
                    <span class="action-label">Добавить админа</span>
                </router-link>

                <router-link
                    v-if="authStore.hasPermission('reports.view')"
                    :to="{ name: 'admin.reports.dashboard' }"
                    class="action-card"
                >
                    <span class="action-icon">📈</span>
                    <span class="action-label">Посмотреть отчеты</span>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '../../composables/useApi'
import { useAuthStore } from '../../stores/auth'
import { useNotifications } from '../../composables/useNotifications'

const api = useApi()
const authStore = useAuthStore()
const notifications = useNotifications()

const stats = ref({})
const topTenants = ref([])
const topTenantsPeriod = ref('month')
const loadingStats = ref(false)
const loadingTopTenants = ref(false)

const loadStats = async () => {
    loadingStats.value = true
    try {
        const response = await api.get('/reports/dashboard')
        stats.value = response.data || response
    } catch (error) {
        console.error('Ошибка загрузки статистики:', error)
        notifications.error('Не удалось загрузить статистику')
    } finally {
        loadingStats.value = false
    }
}

const loadTopTenants = async () => {
    loadingTopTenants.value = true
    try {
        const response = await api.get('/reports/top-tenants', {
            period: topTenantsPeriod.value,
            limit: 10,
        })
        topTenants.value = response.data || response
    } catch (error) {
        console.error('Ошибка загрузки топ тенантов:', error)
        notifications.error('Не удалось загрузить топ тенантов')
    } finally {
        loadingTopTenants.value = false
    }
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value)
}

const getBarWidth = (value) => {
    if (topTenants.value.length === 0) return 0
    const maxValue = Math.max(...topTenants.value.map(t => t.total_revenue))
    return (value / maxValue) * 100
}

onMounted(() => {
    loadStats()
    loadTopTenants()
})
</script>

<style scoped>
.dashboard {
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    flex-shrink: 0;
}

.stat-icon.tenants {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.users {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.orders {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.revenue {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-content {
    flex: 1;
}

.stat-label {
    font-size: 14px;
    color: #718096;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 8px;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.stat-change .active {
    color: #48bb78;
}

.stat-change .vip {
    color: #ed8936;
}

.stat-change .today {
    color: #4299e1;
}

.stat-change .paid {
    color: #48bb78;
}

.stat-change .month {
    color: #9f7aea;
}

.separator {
    color: #cbd5e0;
}

/* Dashboard Section */
.dashboard-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
    margin: 0;
}

.period-select {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    cursor: pointer;
    transition: border-color 0.2s;
}

.period-select:focus {
    border-color: #667eea;
}

/* Top Tenants */
.top-tenants-list {
    min-height: 200px;
}

.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px;
    color: #718096;
}

.spinner {
    width: 24px;
    height: 24px;
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

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #a0aec0;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.tenants-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.tenant-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f7fafc;
    border-radius: 8px;
    transition: background 0.2s;
}

.tenant-card:hover {
    background: #edf2f7;
}

.tenant-rank {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
}

.tenant-info {
    flex: 1;
}

.tenant-name {
    font-size: 16px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 4px;
}

.tenant-revenue {
    font-size: 14px;
    color: #48bb78;
    font-weight: 500;
}

.tenant-bar {
    flex: 2;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
    transition: width 0.3s ease;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 24px;
    background: #f7fafc;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
    gap: 12px;
}

.action-card:hover {
    background: #edf2f7;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-icon {
    font-size: 36px;
}

.action-label {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
    text-align: center;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 20px;
    }

    .stat-value {
        font-size: 24px;
    }

    .tenant-card {
        flex-wrap: wrap;
    }

    .tenant-bar {
        flex: 1 1 100%;
        order: 3;
    }
}
</style>
