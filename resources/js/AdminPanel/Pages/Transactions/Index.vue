<!-- resources/js/AdminPanel/Pages/Transactions/Index.vue -->
<template>
    <div class="transactions-page">
        <div class="page-header">
            <h1>💳 Транзакции</h1>
            <p class="subtitle">История всех финансовых операций</p>
        </div>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <span class="stat-label">Всего поступлений</span>
                    <span class="stat-value">{{ formatCurrency(stats.totalIncome) }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📤</div>
                <div class="stat-info">
                    <span class="stat-label">Всего выводов</span>
                    <span class="stat-value">{{ formatCurrency(stats.totalOutcome) }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <span class="stat-label">За сегодня</span>
                    <span class="stat-value">{{ formatCurrency(stats.today) }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <span class="stat-label">Ожидают обработки</span>
                    <span class="stat-value">{{ stats.pending }}</span>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="filters-bar">
            <div class="filter-group">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="🔍 Поиск по ID, тенанту..."
                    class="filter-input"
                >
            </div>
            <div class="filter-group">
                <select v-model="filters.type" class="filter-select">
                    <option value="">Все типы</option>
                    <option value="payment">Платежи</option>
                    <option value="withdrawal">Выводы</option>
                    <option value="subscription">Подписки</option>
                    <option value="tax">Налоги</option>
                </select>
            </div>
            <div class="filter-group">
                <select v-model="filters.status" class="filter-select">
                    <option value="">Все статусы</option>
                    <option value="completed">Завершённые</option>
                    <option value="pending">В обработке</option>
                    <option value="failed">Отклонённые</option>
                </select>
            </div>
            <div class="filter-group">
                <input
                    v-model="filters.dateFrom"
                    type="date"
                    class="filter-input"
                >
            </div>
            <button class="btn-reset" @click="resetFilters">Сбросить</button>
        </div>

        <!-- Таблица транзакций -->
        <div class="table-container">
            <table class="transactions-table">
                <thead>
                <tr>
                    <th @click="sortBy('id')" class="sortable">
                        ID {{ sortIcon('id') }}
                    </th>
                    <th>Тенант</th>
                    <th>Тип</th>
                    <th @click="sortBy('amount')" class="sortable">
                        Сумма {{ sortIcon('amount') }}
                    </th>
                    <th>Статус</th>
                    <th>Метод</th>
                    <th @click="sortBy('created_at')" class="sortable">
                        Дата {{ sortIcon('created_at') }}
                    </th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="tx in paginatedTransactions" :key="tx.id">
                    <td class="mono">#{{ tx.id }}</td>
                    <td>
                        <div class="tenant-cell">
                            <span class="tenant-dot" :style="{ background: tx.tenantColor }"></span>
                            {{ tx.tenantName }}
                        </div>
                    </td>
                    <td>
                            <span class="type-badge" :class="tx.type">
                                {{ typeLabel(tx.type) }}
                            </span>
                    </td>
                    <td class="amount" :class="tx.amount > 0 ? 'positive' : 'negative'">
                        {{ tx.amount > 0 ? '+' : '' }}{{ formatCurrency(tx.amount) }}
                    </td>
                    <td>
                            <span class="status-badge" :class="tx.status">
                                {{ statusLabel(tx.status) }}
                            </span>
                    </td>
                    <td>{{ tx.method }}</td>
                    <td class="date-cell">{{ formatDate(tx.created_at) }}</td>
                    <td>
                        <router-link
                            :to="{ name: 'admin.transactions.show', params: { id: tx.id } }"
                            class="btn-view"
                        >
                            👁️
                        </router-link>
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="filteredTransactions.length === 0" class="empty-state">
                <span class="empty-icon">📭</span>
                <p>Транзакций не найдено</p>
            </div>
        </div>

        <!-- Пагинация -->
        <div class="pagination" v-if="totalPages > 1">
            <button
                :disabled="currentPage === 1"
                @click="currentPage--"
                class="page-btn"
            >
                ← Назад
            </button>
            <span class="page-info">
                Страница {{ currentPage }} из {{ totalPages }}
            </span>
            <button
                :disabled="currentPage === totalPages"
                @click="currentPage++"
                class="page-btn"
            >
                Вперёд →
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Мок-данные для демонстрации
const transactions = ref([
    { id: 1001, tenantName: 'Fastoran', tenantColor: '#ff7912', type: 'payment', amount: 15000, status: 'completed', method: 'СБП Тинькофф', created_at: '2026-09-08 14:23:00' },
    { id: 1002, tenantName: 'ДонМак Южный', tenantColor: '#3490dc', type: 'subscription', amount: -5000, status: 'completed', method: 'Карта', created_at: '2026-09-08 12:10:00' },
    { id: 1003, tenantName: 'Чебурек ми', tenantColor: '#3490dc', type: 'payment', amount: 8500, status: 'pending', method: 'СБП Сбер', created_at: '2026-09-08 11:45:00' },
    { id: 1004, tenantName: 'Чилл суши', tenantColor: '#3490dc', type: 'withdrawal', amount: -25000, status: 'completed', method: 'Банковский перевод', created_at: '2026-09-07 18:30:00' },
    { id: 1005, tenantName: 'Большой шашлык', tenantColor: '#3490dc', type: 'tax', amount: -257.74, status: 'completed', method: 'Автоматически', created_at: '2026-09-07 00:00:00' },
    { id: 1006, tenantName: 'Полная чаша', tenantColor: '#3490dc', type: 'payment', amount: 4200, status: 'failed', method: 'Карта', created_at: '2026-09-06 16:20:00' },
    { id: 1007, tenantName: 'Вупер бургерс', tenantColor: '#3490dc', type: 'subscription', amount: -5000, status: 'completed', method: 'Карта', created_at: '2026-09-06 12:00:00' },
    { id: 1008, tenantName: 'Лабиринт', tenantColor: '#3490dc', type: 'payment', amount: 12800, status: 'completed', method: 'СБП Тинькофф', created_at: '2026-09-05 14:15:00' },
])

const stats = computed(() => ({
    totalIncome: transactions.value.filter(t => t.amount > 0).reduce((s, t) => s + t.amount, 0),
    totalOutcome: Math.abs(transactions.value.filter(t => t.amount < 0).reduce((s, t) => s + t.amount, 0)),
    today: transactions.value.filter(t => t.created_at.startsWith('2026-09-08')).reduce((s, t) => s + t.amount, 0),
    pending: transactions.value.filter(t => t.status === 'pending').length,
}))

const filters = ref({ search: '', type: '', status: '', dateFrom: '' })
const currentPage = ref(1)
const perPage = 10
const sortField = ref('created_at')
const sortDirection = ref('desc')

const filteredTransactions = computed(() => {
    let result = [...transactions.value]
    if (filters.value.search) {
        const q = filters.value.search.toLowerCase()
        result = result.filter(t => t.tenantName.toLowerCase().includes(q) || t.id.toString().includes(q))
    }
    if (filters.value.type) result = result.filter(t => t.type === filters.value.type)
    if (filters.value.status) result = result.filter(t => t.status === filters.value.status)
    if (filters.value.dateFrom) result = result.filter(t => t.created_at >= filters.value.dateFrom)

    result.sort((a, b) => {
        const dir = sortDirection.value === 'asc' ? 1 : -1
        return (a[sortField.value] > b[sortField.value] ? 1 : -1) * dir
    })
    return result
})

const totalPages = computed(() => Math.ceil(filteredTransactions.value.length / perPage))
const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filteredTransactions.value.slice(start, start + perPage)
})

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortField.value = field
        sortDirection.value = 'asc'
    }
}

const sortIcon = (field) => sortField.value === field ? (sortDirection.value === 'asc' ? '↑' : '↓') : ''

const resetFilters = () => {
    filters.value = { search: '', type: '', status: '', dateFrom: '' }
    currentPage.value = 1
}

const formatCurrency = (n) => new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(n)
const formatDate = (d) => new Date(d).toLocaleString('ru-RU')
const typeLabel = (t) => ({ payment: 'Платёж', withdrawal: 'Вывод', subscription: 'Подписка', tax: 'Налог' }[t] || t)
const statusLabel = (s) => ({ completed: 'Завершён', pending: 'В обработке', failed: 'Отклонён' }[s] || s)
</script>

<style scoped>
.transactions-page { padding: 24px; }
.page-header { margin-bottom: 24px; }
.page-header h1 { margin: 0 0 4px; font-size: 24px; }
.subtitle { color: #666; margin: 0; }

.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.stat-icon { font-size: 32px; }
.stat-info { display: flex; flex-direction: column; }
.stat-label { font-size: 12px; color: #888; text-transform: uppercase; }
.stat-value { font-size: 22px; font-weight: 600; margin-top: 4px; }

.filters-bar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; background: #fff; padding: 16px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.filter-group { flex: 1; min-width: 160px; }
.filter-input, .filter-select { width: 100%; padding: 10px 14px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; background: #fafafa; }
.filter-input:focus, .filter-select:focus { outline: none; border-color: #667eea; background: #fff; }
.btn-reset { padding: 10px 16px; background: #f0f0f0; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; }
.btn-reset:hover { background: #e0e0e0; }

.table-container { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.transactions-table { width: 100%; border-collapse: collapse; }
.transactions-table th { background: #f8f9fa; padding: 14px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: #666; border-bottom: 2px solid #e9ecef; }
.transactions-table th.sortable { cursor: pointer; user-select: none; }
.transactions-table th.sortable:hover { background: #e9ecef; }
.transactions-table td { padding: 14px 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
.transactions-table tbody tr:hover { background: #f8f9fa; }

.tenant-cell { display: flex; align-items: center; gap: 8px; }
.tenant-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.mono { font-family: 'SF Mono', Monaco, monospace; color: #666; }

.type-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.type-badge.payment { background: #d1ecf1; color: #0c5460; }
.type-badge.withdrawal { background: #f8d7da; color: #721c24; }
.type-badge.subscription { background: #d4edda; color: #155724; }
.type-badge.tax { background: #fff3cd; color: #856404; }

.amount { font-weight: 600; font-family: 'SF Mono', Monaco, monospace; }
.amount.positive { color: #28a745; }
.amount.negative { color: #dc3545; }

.status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; display: inline-block; }
.status-badge.completed { background: #d4edda; color: #155724; }
.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.failed { background: #f8d7da; color: #721c24; }

.date-cell { color: #666; font-size: 13px; white-space: nowrap; }

.btn-view { padding: 6px 12px; background: #f0f0f0; border-radius: 6px; text-decoration: none; font-size: 13px; transition: background 0.2s; }
.btn-view:hover { background: #e0e0e0; }

.empty-state { text-align: center; padding: 60px 20px; color: #999; }
.empty-icon { font-size: 48px; display: block; margin-bottom: 12px; }

.pagination { display: flex; justify-content: center; align-items: center; gap: 16px; margin-top: 24px; }
.page-btn { padding: 10px 20px; border: 1px solid #e0e0e0; background: #fff; border-radius: 8px; cursor: pointer; font-size: 14px; }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.page-btn:not(:disabled):hover { background: #f0f0f0; }
.page-info { font-size: 14px; color: #666; }
</style>
