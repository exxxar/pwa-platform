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
                        <div class="action-buttons">
                            <router-link :to="{ name: 'AdminOrderDetails', params: { id: order.id } }" class="btn-action primary" title="Открыть детали">
                                <i class="fa-solid fa-eye"></i>
                            </router-link>
                            <button @click="openProductsModal(order)" class="btn-action info" title="Состав заказа">
                                <i class="fa-solid fa-receipt"></i>
                            </button>
                            <button @click="openQuickActionModal(order)" class="btn-action warning" title="Быстрые действия">
                                <i class="fa-solid fa-bolt"></i>
                            </button>
                        </div>
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

        <!-- 🔥 МОДАЛЬНОЕ ОКНО: СОСТАВ ЗАКАЗА -->
        <div v-if="showProductsModal && currentOrderForProducts" class="modal-overlay" @click.self="closeProductsModal">
            <div class="modal-content modal-content-sm">
                <div class="modal-header">
                    <h3>🛒 Состав заказа #{{ currentOrderForProducts.id }}</h3>
                    <button @click="closeProductsModal" class="btn-close-modal"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div v-if="modalOrderProducts.length > 0" class="products-list-modal">
                        <div v-for="(item, idx) in modalOrderProducts" :key="idx" class="product-row-modal">
                            <span class="prod-qty">{{ item.count }}×</span>
                            <span class="prod-name">{{ item.name || 'Товар' }}</span>
                            <span class="prod-price">{{ formatPrice(item.price) }} ₽</span>
                        </div>
                    </div>

                    <div v-else class="text-muted text-center py-4">
                        <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 12px; display: block; opacity: 0.5;"></i>
                        <p>Состав заказа не указан или пуст</p>
                    </div>

                    <div class="modal-total">
                        <span>Итого к оплате:</span>
                        <span class="total-value">{{ formatPrice(currentOrderForProducts.summary_price) }} ₽</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- 🔥 МОДАЛЬНОЕ ОКНО БЫСТРЫХ ДЕЙСТВИЙ -->
        <div v-if="showQuickModal && currentOrder" class="modal-overlay" @click.self="closeQuickActionModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>⚡ Быстрые действия: Заказ #{{ currentOrder.id }}</h3>
                    <button @click="closeQuickActionModal" class="btn-close-modal"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <!-- Мини-инфо о заказе -->
                    <div class="order-mini-info">
                        <span><i class="fa-solid fa-user me-1"></i> {{ currentOrder.tenant_user?.name || 'Гость' }}</span>
                        <span class="divider">|</span>
                        <span><i class="fa-solid fa-coins me-1"></i> {{ formatPrice(currentOrder.summary_price) }} ₽</span>
                    </div>

                    <!-- Смена статуса -->
                    <div class="modal-section">
                        <label class="section-label">Изменить статус</label>
                        <div class="status-control-row">
                            <select v-model="quickStatus" class="modern-select">
                                <option :value="0">🆕 Новый</option>
                                <option :value="1">⏳ В обработке</option>
                                <option :value="4">🍳 Передан на кухню</option>
                                <option :value="5">🚗 Готов к доставке</option>
                                <option :value="2">✅ Выполнен</option>
                                <option :value="3">❌ Отменен</option>
                            </select>
                            <button @click="saveQuickStatus" :disabled="isSavingStatus || !isQuickStatusChanged" class="btn-save-sm">
                                <i v-if="isSavingStatus" class="fa-solid fa-spinner fa-spin"></i>
                                <span v-else>Применить</span>
                            </button>
                        </div>
                    </div>

                    <!-- Сообщение (только если есть dialog_id) -->
                    <div class="modal-section" v-if="currentOrder.dialog_id">
                        <label class="section-label">Сообщение клиенту</label>
                        <textarea v-model="quickMessage" rows="3" placeholder="Например: Курьер уже выехал..." class="message-input-sm" :disabled="isSendingMessage"></textarea>

                        <div class="quick-templates-sm">
                            <button @click="quickMessage = 'Ваш заказ принят и передан на кухню! 👨‍🍳'" :disabled="isSendingMessage" class="template-chip">На кухне</button>
                            <button @click="quickMessage = 'Курьер уже выехал к вам! 🚗'" :disabled="isSendingMessage" class="template-chip">Курьер едет</button>
                            <button @click="quickMessage = 'Заказ успешно доставлен. Приятного аппетита! ✅'" :disabled="isSendingMessage" class="template-chip">Доставлен</button>
                        </div>

                        <button @click="sendQuickMessageFromModal" :disabled="isSendingMessage || !quickMessage.trim()" class="btn-send-sm">
                            <i v-if="isSendingMessage" class="fa-solid fa-spinner fa-spin"></i>
                            <span v-else><i class="fa-solid fa-paper-plane me-1"></i> Отправить сообщение</span>
                        </button>
                    </div>

                    <div v-else class="modal-section text-muted text-center py-3">
                        <i class="fa-solid fa-comment-slash me-1"></i> У этого заказа нет привязанного чата
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useOrders } from '@/MobileClient/composables/useOrders';
import Pagination from '@/MobileClient/Components/Shop/Helpers/Pagination.vue';
import axios from 'axios';

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
        // 🔥 ДОБАВИЛИ функции обновления статуса и отправки сообщений
        const {
            loadAdminOrders,
            adminOrders,
            adminOrdersPaginate,
            isLoadingAdmin,
            updateAdminOrderStatus,
            sendAdminOrderMessage
        } = useOrders();

        return {
            loadAdminOrders,
            adminOrders,
            adminOrdersPaginate,
            isLoadingAdmin,
            updateAdminOrderStatus,
            sendAdminOrderMessage
        };
    },

    data() {
        return {
            search: '',
            filterStatus: '',
            sort: { param: 'id', direction: 'desc' },
            currentPage: 1,
            isExporting: false,

            // Быстрые действия
            showQuickModal: false,
            currentOrder: null,
            quickStatus: null,
            quickMessage: '',
            isSavingStatus: false,
            isSendingMessage: false,

            // 🔥 НОВЫЕ ПЕРЕМЕННЫЕ ДЛЯ МОДАЛКИ ТОВАРОВ
            showProductsModal: false,
            currentOrderForProducts: null,
        };
    },

    computed: {
        isQuickStatusChanged() {
            return this.currentOrder && String(this.currentOrder.status) !== String(this.quickStatus);
        },

        // 🔥 Безопасное извлечение товаров для модалки (та же логика, что и в деталях заказа)
        modalOrderProducts() {
            if (!this.currentOrderForProducts?.product_details) return [];
            const details = Array.isArray(this.currentOrderForProducts.product_details)
                ? this.currentOrderForProducts.product_details[0]
                : this.currentOrderForProducts.product_details;
            return Array.isArray(details?.products) ? details.products : [];
        }
    },

    mounted() {
        this.loadData(1);
    },

    methods: {
        openProductsModal(order) {
            this.currentOrderForProducts = order;
            this.showProductsModal = true;
        },

        closeProductsModal() {
            this.showProductsModal = false;
            this.currentOrderForProducts = null;
        },

        // ==========================================
        // 🔥 МЕТОДЫ МОДАЛЬНОГО ОКНА
        // ==========================================
        openQuickActionModal(order) {
            this.currentOrder = order;
            this.quickStatus = Number(order.status);
            this.quickMessage = '';
            this.showQuickModal = true;
        },

        closeQuickActionModal() {
            this.showQuickModal = false;
            this.currentOrder = null;
            this.quickMessage = '';
        },

        async saveQuickStatus() {
            if (!this.isQuickStatusChanged) return;

            this.isSavingStatus = true;
            try {
                await this.updateAdminOrderStatus(this.currentOrder.id, Number(this.quickStatus));

                // Обновляем статус локально в текущем заказе
                this.currentOrder.status = Number(this.quickStatus);

                // 🔥 Обновляем статус в общей таблице, чтобы изменения были видны сразу
                const index = this.adminOrders.findIndex(o => o.id === this.currentOrder.id);
                if (index !== -1) {
                    this.adminOrders[index].status = Number(this.quickStatus);
                }

                this.$notify?.({ title: 'Успех', text: 'Статус заказа обновлен', type: 'success' });
            } catch (e) {
                console.error(e);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось изменить статус', type: 'error' });
                // Откат при ошибке
                this.quickStatus = Number(this.currentOrder.status);
            } finally {
                this.isSavingStatus = false;
            }
        },

        async sendQuickMessageFromModal() {
            if (!this.currentOrder?.dialog_id || !this.quickMessage.trim()) return;

            this.isSendingMessage = true;
            try {
                await this.sendAdminOrderMessage(this.currentOrder.id, this.quickMessage);
                this.$notify?.({ title: 'Отправлено', text: 'Сообщение доставлено клиенту', type: 'success' });
                this.quickMessage = ''; // Очищаем поле после успешной отправки
            } catch (e) {
                console.error(e);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось отправить сообщение', type: 'error' });
            } finally {
                this.isSendingMessage = false;
            }
        },

        // ==========================================
        // СУЩЕСТВУЮЩИЕ МЕТОДЫ (без изменений)
        // ==========================================
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
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;

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

// --- Кнопки действий в таблице ---
.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: none;
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

    &.warning {
        background: rgba($admin-warning, 0.1);
        color: $admin-warning;

        &:hover {
            background: $admin-warning;
            color: white;
            transform: translateY(-1px);
        }
    }
}

// --- Стили модального окна ---
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
    animation: fadeIn 0.2s ease-out;
}

.modal-content {
    background: $admin-card-bg;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid $admin-border;

    h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: $admin-text;
    }
}

.btn-close-modal {
    background: transparent;
    border: none;
    color: $admin-text-muted;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    transition: all 0.2s;

    &:hover {
        background: $admin-bg;
        color: $admin-danger;
    }
}

.modal-body {
    padding: 24px;
}

.order-mini-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: $admin-bg;
    border-radius: 10px;
    margin-bottom: 24px;
    font-size: 0.95rem;
    color: $admin-text;

    .divider {
        color: $admin-border;
    }
}

.modal-section {
    margin-bottom: 24px;

    &:last-child {
        margin-bottom: 0;
    }
}

.section-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text-muted;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-control-row {
    display: flex;
    gap: 12px;
}

.btn-save-sm {
    padding: 10px 20px;
    background: $admin-success;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
    white-space: nowrap;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        background: #0b9c6c;
        transform: translateY(-1px);
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
}

.message-input-sm {
    width: 100%;
    padding: 12px;
    border: 1px solid $admin-border;
    border-radius: 10px;
    resize: none;
    font-family: inherit;
    font-size: 0.95rem;
    margin-bottom: 12px;
    transition: all 0.2s;
    background: $admin-card-bg;
    box-sizing: border-box;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }
}

.quick-templates-sm {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.template-chip {
    padding: 6px 12px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    color: $admin-text;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $admin-primary-light;
        border-color: $admin-primary;
        color: $admin-primary;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-send-sm {
    width: 100%;
    padding: 12px;
    background: $admin-primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: #2563eb;
        transform: translateY(-1px);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

// --- Новая кнопка в таблице ---
.btn-action {
    // ... ваши существующие стили .primary и .warning ...

    &.info {
        background: rgba($admin-primary, 0.1);
        color: $admin-primary;

        &:hover {
            background: $admin-primary;
            color: white;
            transform: translateY(-1px);
        }
    }
}

// --- Стили модалки состава заказа ---
.modal-content-sm {
    max-width: 450px; // Чуть уже, так как тут только список
}

.products-list-modal {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 4px; // Место для скроллбара

    // Кастомный скроллбар для красоты
    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-track {
        background: $admin-bg;
        border-radius: 3px;
    }
    &::-webkit-scrollbar-thumb {
        background: $admin-border;
        border-radius: 3px;
        &:hover {
            background: $admin-text-muted;
        }
    }
}

.product-row-modal {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: $admin-bg;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 0.95rem;

    &:last-child {
        margin-bottom: 0;
    }
}

.prod-qty {
    font-weight: 700;
    color: $admin-primary;
    min-width: 32px;
    text-align: center;
    background: white;
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid $admin-border;
}

.prod-name {
    flex: 1;
    color: $admin-text;
    line-height: 1.4;
    font-weight: 500;
}

.prod-price {
    font-weight: 700;
    color: $admin-text;
    white-space: nowrap;
}

.modal-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0 4px;
    margin-top: 16px;
    border-top: 1px dashed $admin-border;
    font-size: 1.1rem;

    span:first-child {
        font-weight: 600;
        color: $admin-text-muted;
    }

    .total-value {
        font-weight: 800;
        font-size: 1.25rem;
        color: $admin-primary;
    }
}
</style>
