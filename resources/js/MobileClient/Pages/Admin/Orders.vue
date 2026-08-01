<template>
    <div class="admin-orders-page">
        <!-- Шапка -->
        <div class="page-header">
            <div class="header-content">
                <h2 class="header-title"><i class="fa-solid fa-list-check me-2"></i> Управление заказами</h2>
                <span class="header-subtitle">Просмотр и фильтрация всех заказов в системе</span>
            </div>
        </div>

        <!-- Панель управления -->
        <div class="control-panel">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    v-model="search"
                    @input="debounceSearch"
                    placeholder="Поиск по № заказа, имени или телефону..."
                />
                <button v-if="search" class="clear-search" @click="clearSearch">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="filters">
                <select v-model="filterStatus" @change="reloadData" class="modern-select">
                    <option value="">Все статусы</option>
                    <option value="new">🆕 Новые</option>
                    <option value="processing">⏳ В обработке</option>
                    <option value="completed">✅ Завершенные</option>
                    <option value="cancelled">❌ Отмененные</option>
                </select>

                <!-- 🔥 КНОПКА ЭКСПОРТА -->
                <button class="btn-export" @click="exportToExcel" :disabled="isExporting">
                    <i class="fa-solid fa-file-excel" :class="{ 'fa-spin': isExporting }"></i>
                    <span>{{ isExporting ? 'Формирование...' : 'Выгрузить в Excel' }}</span>
                </button>
            </div>
        </div>

        <!-- Таблица заказов -->
        <div class="table-wrapper">
            <table class="admin-table" v-if="!isLoadingAdmin && adminOrders?.length > 0">
                <thead>
                <tr>
                    <th @click="toggleSort('id')" class="sortable">
                        № Заказа <i :class="sortIcon('id')"></i>
                    </th>
                    <th>Заведение</th> <!-- 🔥 НОВАЯ КОЛОНКА -->
                    <th class="text-end">Действия</th>
                    <th>Дата и время</th>
                    <th>Клиент</th>
                    <th @click="toggleSort('summary_price')" class="sortable">
                        Сумма <i :class="sortIcon('summary_price')"></i>
                    </th>
                    <th>Статус</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="order in adminOrders" :key="order.id" class="order-row">
                    <td class="font-monospace fw-bold text-primary">#{{ order.id }}</td>

                    <!-- 🔥 ДАННЫЕ ЗАВЕДЕНИЯ -->
                    <td>
                        <div class="tenant-info">
                            <i class="fa-solid fa-store text-muted me-1"></i>
                            {{ order.tenant?.name || '—' }}
                        </div>
                    </td>

                    <td class="text-end">
                        <router-link :to="{ name: 'AdminOrderDetails', params: { id: order.id } }" class="btn-action primary" title="Открыть детали">
                            <i class="fa-solid fa-eye"></i>
                        </router-link>
                    </td>
                    <td class="text-muted">{{ formatDate(order.created_at) }}</td>
                    <td>
                        <div class="client-info">
                            <span class="client-name">{{ order.tenant_user?.name || 'Гость' }}</span>
                            <span class="client-phone">{{ order.tenant_user?.phone || 'Нет телефона' }}</span>
                        </div>
                    </td>
                    <td class="fw-bold text-dark">{{ formatPrice(order.summary_price) }} ₽</td>
                    <td>
                        <span class="status-badge" :class="getStatusInfo(order.status).class">
                            {{ getStatusInfo(order.status).label }}
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- Пустое состояние и загрузка (без изменений) -->
            <div v-else-if="!isLoadingAdmin" class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
                <h3>Заказы не найдены</h3>
                <p>Попробуйте изменить параметры поиска или фильтры</p>
            </div>
            <div v-if="isLoadingAdmin" class="loading-state">
                <div class="spinner"></div>
                <p>Загрузка данных...</p>
            </div>
        </div>

        <!-- Пагинация (без изменений) -->
        <div v-if="adminOrdersPaginate && adminOrdersPaginate.last_page > 1" class="pagination-wrapper">
            <Pagination
                :simple="false"
                :pagination="adminOrdersPaginate"
                @pagination_page="handlePageChange"
            />
        </div>
    </div>
</template>

<script>
import { useOrders } from '@/MobileClient/composables/useOrders';
import Pagination from '@/MobileClient/Components/Shop/Helpers/Pagination.vue';
import axios from 'axios'; // 🔥 Добавили axios для экспорта

const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

export default {
    name: 'AdminOrdersList',
    components: { Pagination },

    setup() {
        const { loadAdminOrders, adminOrders, adminOrdersPaginate, isLoadingAdmin } = useOrders();
        return { loadAdminOrders, adminOrders, adminOrdersPaginate, isLoadingAdmin };
    },

    data() {
        return {
            search: '',
            filterStatus: '',
            sort: { param: 'id', direction: 'desc' },
            currentPage: 1,
            isExporting: false, // 🔥 Состояние для кнопки экспорта
        };
    },

    mounted() {
        this.loadData(1);
    },

    methods: {
        // 🔥 НОВЫЙ МЕТОД ЭКСПОРТА
        async exportToExcel() {
            this.isExporting = true;
            try {
                const response = await axios.get('/admin/orders/export', {
                    params: {
                        search: this.search || null,
                        status: this.filterStatus || null,
                        order_by: this.sort.param,
                        direction: this.sort.direction
                    },
                    responseType: 'blob' // 🔥 ВАЖНО: указываем, что ждем файл
                });

                // Создаем ссылку для скачивания
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;

                // Имя файла берем из заголовков ответа или генерируем
                const disposition = response.headers['content-disposition'];
                let filename = 'orders.xlsx';
                if (disposition && disposition.indexOf('filename=') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = decodeURIComponent(matches[1].replace(/['"]/g, ''));
                    }
                }

                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);

            } catch (error) {
                console.error('Ошибка экспорта:', error);
                alert('Не удалось выгрузить файл. Попробуйте позже.');
            } finally {
                this.isExporting = false;
            }
        },

        debounceSearch: debounce(function () {
            this.loadData(1);
        }, 400),

        clearSearch() {
            this.search = '';
            this.loadData(1);
        },

        async loadData(page = 1) {
            this.currentPage = page;
            if (typeof this.loadAdminOrders !== 'function') return;

            try {
                await this.loadAdminOrders({
                    page: page,
                    size: 20,
                    search: this.search || null,
                    status: this.filterStatus || null,
                    order_by: this.sort.param,
                    direction: this.sort.direction
                });
            } catch (err) {
                console.error('Ошибка загрузки:', err);
            }
        },

        handlePageChange(page) {
            this.loadData(page);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        reloadData() {
            this.loadData(1);
        },

        toggleSort(param) {
            if (this.sort.param === param) {
                this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.sort.param = param;
                this.sort.direction = 'desc';
            }
            this.reloadData();
        },

        sortIcon(param) {
            if (this.sort.param !== param) return 'fa-solid fa-sort text-muted opacity-50';
            return this.sort.direction === 'asc' ? 'fa-solid fa-sort-up text-primary' : 'fa-solid fa-sort-down text-primary';
        },

        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },

        formatPrice(price) {
            return Number(price || 0).toLocaleString('ru-RU');
        },

        getStatusInfo(status) {
            const map = {
                0: { label: 'Новый', class: 'new' },
                1: { label: 'В обработке', class: 'processing' },
                2: { label: 'Выполнен', class: 'completed' },
                3: { label: 'Отменен', class: 'cancelled' },
                4: { label: 'Готов к доставке', class: 'processing' },
                5: { label: 'Передан на кухню', class: 'processing' },
                'new': { label: 'Новый', class: 'new' },
                'processing': { label: 'В работе', class: 'processing' },
                'completed': { label: 'Выполнен', class: 'completed' },
                'cancelled': { label: 'Отменен', class: 'cancelled' },
            };
            return map[status] || { label: status || 'Неизвестно', class: 'new' };
        },
    }
};
</script>
<style lang="scss" scoped>
$admin-bg: #f8fafc;
$admin-card-bg: #ffffff;
$admin-text: #0f172a;
$admin-text-muted: #64748b;
$admin-border: #e2e8f0;
$admin-primary: #3b82f6;
$admin-primary-light: #eff6ff;
$admin-success: #10b981;
$admin-warning: #f59e0b;
$admin-danger: #ef4444;

.admin-orders-page {
    padding: 32px;
    background: $admin-bg;
    min-height: 100vh;
}

// --- Шапка ---
.page-header {
    margin-bottom: 24px;
}
.header-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0 0 4px 0;
}
.header-subtitle {
    font-size: 0.9rem;
    color: $admin-text-muted;
    margin: 0;
}

// --- Панель управления ---
.control-panel {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 280px;
    position: relative;

    input {
        width: 100%;
        padding: 12px 40px 12px 44px;
        border: 1px solid $admin-border;
        border-radius: 10px;
        font-size: 0.95rem;
        background: $admin-card-bg;
        transition: all 0.2s;

        &:focus {
            outline: none;
            border-color: $admin-primary;
            box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
        }
    }

    i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: $admin-text-muted;
    }

    .clear-search {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: transparent;
        border: none;
        color: $admin-text-muted;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;

        &:hover {
            background: $admin-bg;
            color: $admin-danger;
        }
    }
}

.modern-select {
    padding: 12px 16px;
    border: 1px solid $admin-border;
    border-radius: 10px;
    font-size: 0.95rem;
    background: $admin-card-bg;
    color: $admin-text;
    cursor: pointer;
    min-width: 200px;

    &:focus {
        outline: none;
        border-color: $admin-primary;
    }
}

// --- Таблица ---
.table-wrapper {
    background: $admin-card-bg;
    border-radius: 12px;
    border: 1px solid $admin-border;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;

    th {
        text-align: left;
        padding: 16px 20px;
        background: $admin-bg;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: $admin-text-muted;
        border-bottom: 1px solid $admin-border;

        &.sortable {
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;

            &:hover {
                color: $admin-primary;
            }
        }
    }

    td {
        padding: 16px 20px;
        border-bottom: 1px solid $admin-border;
        vertical-align: middle;
        font-size: 0.95rem;
    }

    tbody tr:last-child td {
        border-bottom: none;
    }
}

.order-row {
    transition: background 0.15s;

    &:hover {
        background: $admin-primary-light;
    }
}

.client-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.client-name {
    font-weight: 600;
    color: $admin-text;
}

.client-phone {
    font-size: 0.85rem;
    color: $admin-text-muted;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;

    &.new { background: rgba($admin-primary, 0.1); color: $admin-primary; }
    &.processing { background: rgba($admin-warning, 0.1); color: $admin-warning; }
    &.completed { background: rgba($admin-success, 0.1); color: $admin-success; }
    &.cancelled { background: rgba($admin-danger, 0.1); color: $admin-danger; }
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;

    &.primary {
        background: $admin-primary-light;
        color: $admin-primary;

        &:hover {
            background: $admin-primary;
            color: white;
            transform: translateY(-1px);
        }
    }
}

// --- Состояния ---
.empty-state, .loading-state {
    text-align: center;
    padding: 60px 20px;
    color: $admin-text-muted;
}

.empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: $admin-bg;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;
    color: $admin-text-muted;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid $admin-border;
    border-top-color: $admin-primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
}

@keyframes spin { to { transform: rotate(360deg); } }

.pagination-wrapper {
    margin-top: 24px;
    display: flex;
    justify-content: center;
}

// --- Адаптив ---
@media (max-width: 768px) {
    .admin-orders-page { padding: 16px; }

    .table-wrapper {
        overflow-x: auto;
        border-radius: 8px;
    }

    .admin-table {
        min-width: 800px; // Чтобы таблица скроллилась горизонтально на мобильных
    }
}

// 🔥 ДОБАВЬТЕ ЭТИ СТИЛИ ДЛЯ КНОПКИ ЭКСПОРТА И НОВОЙ КОЛОНКИ
.filters {
    display: flex;
    gap: 12px;
    align-items: center;
}

.btn-export {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #10b981; // Зеленый цвет Excel
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: #059669;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
}

.tenant-info {
    display: flex;
    align-items: center;
    font-weight: 500;
    color: #0f172a;
}
</style>
