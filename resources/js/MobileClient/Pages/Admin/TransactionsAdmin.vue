<template>
    <div class="transactions-admin-page">

        <!-- ========================================== -->
        <!-- HERO & СТАТИСТИКА -->
        <!-- ========================================== -->
        <div class="page-hero">
            <div class="hero-bg">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h1 class="hero-title">Финансовые транзакции</h1>
                <p class="hero-subtitle">История платежей, статусы и детализация</p>
            </div>
        </div>

        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.total_count }}</div>
                        <div class="stat-label">Всего операций</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.success_count }}</div>
                        <div class="stat-label">Успешных</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.pending_count }}</div>
                        <div class="stat-label">В ожидании</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fa-solid fa-ruble-sign"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ formatCurrency(stats.total_amount) }}</div>
                        <div class="stat-label">Общая выручка</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ ФИЛЬТРОВ -->
        <!-- ========================================== -->
        <div class="filters-section">
            <div class="filters-grid">
                <div class="filter-field">
                    <label>Поиск</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" v-model="filters.search" placeholder="ID, заказ, телефон..." @input="debounceSearch">
                    </div>
                </div>

                <div class="filter-field">
                    <label>Статус</label>
                    <select v-model="filters.status" @change="applyFilters">
                        <option value="">Все статусы</option>
                        <option value="success">Успешно</option>
                        <option value="pending">В ожидании</option>
                        <option value="failed">Ошибка</option>
                        <option value="refunded">Возврат</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label>Платежная система</label>
                    <select v-model="filters.provider" @change="applyFilters">
                        <option value="">Все системы</option>
                        <option value="tinkoff">Т-Банк</option>
                        <option value="sber">Сбербанк</option>
                        <option value="psb">Промсвязьбанк</option>
                        <option value="vtb">ВТБ</option>
                        <option value="yandex">ЮKassa</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label>С даты</label>
                    <input type="date" v-model="filters.date_from" @change="applyFilters">
                </div>

                <div class="filter-field">
                    <label>По дату</label>
                    <input type="date" v-model="filters.date_to" @change="applyFilters">
                </div>

                <div class="filter-field filter-actions">
                    <button class="btn-reset" @click="resetFilters">
                        <i class="fa-solid fa-rotate-left"></i> Сбросить
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ТАБЛИЦА ДАННЫХ -->
        <!-- ========================================== -->
        <div class="table-section">
            <div v-if="isLoading" class="loading-state">
                <div class="loader-spinner"></div>
                <p>Загрузка транзакций...</p>
            </div>

            <div v-else-if="transactions.data.length === 0" class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <h3>Транзакции не найдены</h3>
                <p>Попробуйте изменить параметры фильтров</p>
            </div>

            <div v-else class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th @click="toggleSort('id')">ID <i class="fa-solid fa-sort"></i></th>
                        <th>Дата и время</th>
                        <th>Клиент / Заказ</th>
                        <th>Провайдер</th>
                        <th>Внешний ID</th>
                        <th @click="toggleSort('amount')">Сумма <i class="fa-solid fa-sort"></i></th>
                        <th>Статус</th>
                        <th class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="tx in transactions.data" :key="tx.id">
                        <td class="font-mono">#{{ tx.id }}</td>
                        <td>
                            <div>{{ formatDate(tx.created_at) }}</div>
                            <div v-if="tx.paid_at" class="text-muted text-xs">Оплачено: {{ formatDate(tx.paid_at) }}</div>
                        </td>
                        <td>
                            <div class="font-semibold">{{ tx.user?.name || 'Гость' }}</div>
                            <div class="text-muted text-xs" v-if="tx.order">
                                Заказ #{{ tx.order_id }}
                            </div>
                        </td>
                        <td>
                                <span class="provider-badge" :class="`provider-${tx.provider}`">
                                    <i :class="getProviderIcon(tx.provider)"></i>
                                    {{ formatProviderName(tx.provider) }}
                                </span>
                        </td>
                        <td class="font-mono text-xs truncate-max" :title="tx.external_payment_id">
                            {{ tx.external_payment_id || '—' }}
                        </td>
                        <td class="font-bold">{{ formatCurrency(tx.amount) }}</td>
                        <td>
                                <span class="status-badge" :class="`status-${tx.status}`">
                                    {{ formatStatus(tx.status) }}
                                </span>
                        </td>
                        <td class="text-center">
                            <button class="icon-btn" @click="viewMeta(tx)" title="Просмотреть данные">
                                <i class="fa-solid fa-code"></i>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div v-if="transactions.last_page > 1" class="pagination-wrapper">
                <button
                    class="page-btn"
                    :disabled="transactions.current_page === 1"
                    @click="changePage(transactions.current_page - 1)"
                >
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <span class="page-info">
                    Страница {{ transactions.current_page }} из {{ transactions.last_page }}
                </span>

                <button
                    class="page-btn"
                    :disabled="transactions.current_page === transactions.last_page"
                    @click="changePage(transactions.current_page + 1)"
                >
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПРОСМОТР META (JSON) -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showMetaModal" class="modal-overlay" @click.self="closeMetaModal">
                <div class="modal-container modal-lg">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fa-solid fa-code"></i> Детали транзакции #{{ selectedTx?.id }}
                        </h3>
                        <button class="modal-close" @click="closeMetaModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="meta-grid">
                            <div class="meta-item">
                                <span class="label">Пользователь:</span>
                                <span class="value">{{ selectedTx?.user?.name }} ({{ selectedTx?.user?.phone }})</span>
                            </div>
                            <div class="meta-item">
                                <span class="label">Заказ:</span>
                                <span class="value">#{{ selectedTx?.order_id }}</span>
                            </div>
                            <div class="meta-item full-width">
                                <span class="label">Сырые данные (Meta):</span>
                                <pre class="json-viewer">{{ JSON.stringify(selectedTx?.meta, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>
export default {
    name: 'TransactionsAdmin',

    data() {
        return {
            isLoading: false,
            transactions: { data: [], current_page: 1, last_page: 1 },
            stats: { total_count: 0, success_count: 0, pending_count: 0, total_amount: 0 },

            filters: {
                search: '',
                status: '',
                provider: '',
                date_from: '',
                date_to: ''
            },

            searchTimeout: null,
            showMetaModal: false,
            selectedTx: null,
        };
    },

    async mounted() {
        await this.fetchTransactions();
    },

    methods: {
        async fetchTransactions(page = 1) {
            this.isLoading = true;
            try {
                const params = { ...this.filters, page };
                const response = await axios.get('/admin/transactions', { params });

                this.transactions = response.data.transactions;
                this.stats = response.data.stats;
            } catch (error) {
                console.error('Ошибка загрузки транзакций:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить данные', type: 'error' });
            } finally {
                this.isLoading = false;
            }
        },

        applyFilters() {
            this.fetchTransactions(1);
        },

        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.applyFilters();
            }, 500); // Задержка 500мс для оптимизации запросов
        },

        resetFilters() {
            this.filters = { search: '', status: '', provider: '', date_from: '', date_to: '' };
            this.applyFilters();
        },

        changePage(page) {
            this.fetchTransactions(page);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        toggleSort(field) {
            // Простая реализация сортировки (можно расширить)
            this.fetchTransactions(1);
        },

        viewMeta(tx) {
            this.selectedTx = tx;
            this.showMetaModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeMetaModal() {
            this.showMetaModal = false;
            this.selectedTx = null;
            document.body.style.overflow = '';
        },

        // 🆕 Форматирование
        formatCurrency(value) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(value || 0);
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            return new Date(dateString).toLocaleString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },

        formatStatus(status) {
            const map = {
                'success': 'Успешно',
                'pending': 'В ожидании',
                'failed': 'Ошибка',
                'refunded': 'Возврат'
            };
            return map[status] || status;
        },

        formatProviderName(provider) {
            const map = {
                'tinkoff': 'Т-Банк',
                'sber': 'Сбербанк',
                'psb': 'ПСБ',
                'vtb': 'ВТБ',
                'yandex': 'ЮKassa'
            };
            return map[provider] || provider;
        },

        getProviderIcon(provider) {
            const map = {
                'tinkoff': 'fa-solid fa-building-columns',
                'sber': 'fa-solid fa-s',
                'psb': 'fa-solid fa-shield-halved',
                'vtb': 'fa-solid fa-b',
                'yandex': 'fa-brands fa-yandex'
            };
            return map[provider] || 'fa-solid fa-credit-card';
        }
    }
};
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-warning: #f59e0b;
$admin-danger: #ef4444;

.transactions-admin-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: 40px;
}

// ==========================================
// HERO & STATS (как в AchievementsAdmin)
// ==========================================
.page-hero {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px 50px;
    color: white;
    overflow: hidden;
}
.hero-bg { position: absolute; inset: 0; }
.blob {
    position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.3;
    &.blob-1 { width: 300px; height: 300px; background: rgba(255, 255, 255, 0.3); top: -100px; right: -50px; animation: float 20s ease-in-out infinite; }
    &.blob-2 { width: 250px; height: 250px; background: rgba(255, 255, 255, 0.2); bottom: -80px; left: -30px; animation: float 25s ease-in-out infinite reverse; }
}
@keyframes float { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(20px, -20px) scale(1.1); } }

.hero-content { position: relative; z-index: 1; max-width: 900px; margin: 0 auto; text-align: center; }
.hero-icon { width: 72px; height: 72px; margin: 0 auto 16px; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
.hero-title { font-size: 2rem; font-weight: 800; margin: 0 0 8px; }
.hero-subtitle { font-size: 1rem; opacity: 0.9; margin: 0; }

.stats-section { padding: 10px; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 6px; }
.stat-card { display: flex; align-items: center; gap: 12px; padding: 16px; background: #ffffff; border: 1px solid #e9ecef; border-radius: 14px; transition: all 0.2s; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.3rem; flex-shrink: 0; }
.stat-info { flex: 1; min-width: 0; }
.stat-value { font-size: 1.4rem; font-weight: 800; color: #2c3e50; line-height: 1.2; }
.stat-label { font-size: 0.75rem; color: #6c757d; margin-top: 2px; line-height: 100%; }

// ==========================================
// ФИЛЬТРЫ
// ==========================================
.filters-section {
    max-width: 1200px; margin: 20px auto 0; padding: 0 16px;
}
.filters-grid {
    display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end;
}
.filter-field { display: flex; flex-direction: column; gap: 6px; }
.filter-field label { font-size: 0.8rem; font-weight: 600; color: $admin-text; }
.filter-field input, .filter-field select {
    padding: 10px 12px; border: 1px solid $admin-border; border-radius: 8px; font-size: 0.9rem; background: white;
    &:focus { outline: none; border-color: $admin-primary; box-shadow: 0 0 0 3px rgba($admin-primary, 0.1); }
}
.input-with-icon { position: relative; }
.input-with-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: $admin-text-muted; }
.input-with-icon input { padding-left: 36px; width: 100%; box-sizing: border-box; }

.btn-reset {
    padding: 10px 16px; background: $admin-bg; border: 1px solid $admin-border; border-radius: 8px;
    color: $admin-text-muted; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;
    &:hover { background: $admin-danger; color: white; border-color: $admin-danger; }
}

// ==========================================
// ТАБЛИЦА
// ==========================================
.table-section { max-width: 1200px; margin: 20px auto; padding: 0 16px; }
.table-responsive { overflow-x: auto; background: white; border-radius: 14px; border: 1px solid $admin-border; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
.data-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.data-table th {
    padding: 14px 16px; text-align: left; font-size: 0.8rem; font-weight: 700; color: $admin-text-muted;
    text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid $admin-border; cursor: pointer;
    &:hover { color: $admin-primary; }
}
.data-table td {
    padding: 14px 16px; border-bottom: 1px solid $admin-border; font-size: 0.9rem; color: $admin-text; vertical-align: middle;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover { background: rgba($admin-primary, 0.02); }

.font-mono { font-family: 'Courier New', monospace; font-size: 0.85rem; }
.text-muted { color: $admin-text-muted; }
.text-xs { font-size: 0.75rem; }
.truncate-max { max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

// Бейджи
.status-badge {
    padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    &.status-success { background: rgba($admin-success, 0.1); color: $admin-success; }
    &.status-pending { background: rgba($admin-warning, 0.1); color: $admin-warning; }
    &.status-failed, &.status-refunded { background: rgba($admin-danger, 0.1); color: $admin-danger; }
}

.provider-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 8px;
    background: $admin-bg; font-size: 0.8rem; font-weight: 600; color: $admin-text;
    i { font-size: 0.9rem; }
}

.icon-btn {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid $admin-border; background: white;
    color: $admin-text-muted; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    &:hover { background: $admin-primary; color: white; border-color: $admin-primary; }
}

// Пагинация
.pagination-wrapper {
    display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 20px;
}
.page-btn {
    width: 36px; height: 36px; border-radius: 8px; border: 1px solid $admin-border; background: white;
    color: $admin-text; cursor: pointer; display: flex; align-items: center; justify-content: center;
    &:hover:not(:disabled) { background: $admin-primary; color: white; border-color: $admin-primary; }
    &:disabled { opacity: 0.4; cursor: not-allowed; }
}
.page-info { font-size: 0.9rem; color: $admin-text-muted; font-weight: 600; }

// Пустое состояние и загрузка
.loading-state, .empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px;
    background: white; border: 1px solid $admin-border; border-radius: 14px; color: $admin-text-muted; text-align: center;
    .loader-spinner { width: 40px; height: 40px; border: 3px solid $admin-border; border-top-color: $admin-primary; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 16px; }
    i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
}
@keyframes spin { to { transform: rotate(360deg); } }

// ==========================================
// МОДАЛКА
// ==========================================
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 9999;
    display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-container {
    background: white; width: 100%; max-width: 600px; max-height: 90vh; border-radius: 20px;
    overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    &.modal-lg { max-width: 800px; }
}
.modal-header {
    padding: 20px; border-bottom: 1px solid $admin-border; display: flex; align-items: center; justify-content: space-between;
}
.modal-title { font-size: 1.1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
.modal-close {
    width: 36px; height: 36px; border-radius: 50%; background: $admin-bg; border: none; color: $admin-text;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    &:hover { background: $admin-danger; color: white; }
}
.modal-body { padding: 20px; overflow-y: auto; flex: 1; }

.meta-grid { display: grid; gap: 16px; }
.meta-item { display: flex; flex-direction: column; gap: 4px; }
.meta-item.full-width { grid-column: 1 / -1; }
.meta-item .label { font-size: 0.8rem; font-weight: 600; color: $admin-text-muted; }
.meta-item .value { font-size: 0.95rem; font-weight: 600; color: $admin-text; }
.json-viewer {
    background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 10px; font-family: 'Courier New', monospace;
    font-size: 0.85rem; overflow-x: auto; margin: 0; max-height: 400px;
}

// Адаптив
@media (max-width: 1024px) {
    .filters-grid { grid-template-columns: 1fr 1fr; }
    .filter-actions { grid-column: 1 / -1; }
}
@media (max-width: 640px) {
    .filters-grid { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .modal-container { max-width: 100%; max-height: 100vh; border-radius: 0; }
}
</style>
