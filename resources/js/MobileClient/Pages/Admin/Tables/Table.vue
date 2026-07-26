<template>
    <div class="table-details-page pb-5" v-if="!isCurrentTableLoading">
        <div class="container py-4">
            <!-- Шапка с визуализацией столика -->
            <div class="modern-header mb-4">
                <button class="btn-back" @click="$router.back()">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>

                <!-- Визуализация столика -->
                <div class="table-preview-mini floor-plan-bg">
                    <div class="css-table css-table-md" :class="`shape-${getTableShape(table)}`">
                        <div v-for="(seat, index) in getTableSeats(table)" :key="index" class="seat" :class="[`type-${seat.type}`, `pos-${seat.pos}`]"></div>
                        <div class="table-top">
                            <span class="table-number">{{ table?.number }}</span>
                        </div>
                    </div>
                </div>

                <div class="header-info">
                    <h2 class="page-title">
                        Столик №{{ table?.number }}
                    </h2>
                    <div class="status-badge-modern" :class="statusClass">
                        <span class="status-dot"></span>
                        <span class="status-text">{{ statusLabel }}</span>
                    </div>
                </div>
            </div>

            <!-- Предупреждение о закрытом столике -->
            <div v-if="table?.closed_at" class="alert-modern alert-danger mb-4">
                <div class="alert-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="alert-content">
                    <h6>Внимание!</h6>
                    <p>Данный столик <strong>закрыт</strong>. Операции с ним недоступны.</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- ЛЕВАЯ КОЛОНКА: Инфо и Персонал -->
                <div class="col-lg-4">
                    <!-- Информация -->
                    <div class="info-card mb-4">
                        <h5 class="card-title">
                            <i class="fa-solid fa-circle-info"></i>
                            Информация
                        </h5>
                        <div class="info-row">
                            <span class="label">
                                <i class="fa-solid fa-users"></i> Гостей
                            </span>
                            <span class="value">{{ clients.length }} чел.</span>
                        </div>
                        <div class="info-row">
                            <span class="label">
                                <i class="fa-solid fa-list"></i> Позиций в заказе
                            </span>
                            <span class="value">{{ summaryCount }} ед.</span>
                        </div>
                        <div class="info-row total">
                            <span class="label">
                                <i class="fa-solid fa-ruble-sign"></i> Итого
                            </span>
                            <span class="value">{{ summaryPrice }} ₽</span>
                        </div>
                    </div>

                    <!-- Персонал -->
                    <div class="info-card mb-4">
                        <h5 class="card-title">
                            <i class="fa-solid fa-user-tie"></i>
                            Персонал
                        </h5>
                        <div class="info-row">
                            <span class="label">Официант</span>
                            <span class="value">
                                {{ table?.officiant?.name || table?.officiant?.fio_from_telegram || 'Не назначен' }}
                            </span>
                        </div>
                        <button
                            class="btn-modern btn-outline w-100 mt-3"
                            :disabled="table?.closed_at"
                            @click="handleWaiterChange"
                        >
                            <i class="fa-solid" :class="table?.officiant ? 'fa-user-slash' : 'fa-user-plus'"></i>
                            {{ table?.officiant ? 'Снять с обслуживания' : 'Взять в работу' }}
                        </button>
                    </div>

                    <!-- Клиент -->
                    <div class="info-card">
                        <h5 class="card-title">
                            <i class="fa-solid fa-user"></i>
                            Клиент
                        </h5>
                        <div class="info-row" v-if="table?.creator">
                            <span class="value">
                                {{ table.creator.fio_from_telegram || table.creator.name || 'Аноним' }}
                            </span>
                        </div>
                        <div class="info-row" v-else>
                            <span class="value text-muted">Гость без авторизации</span>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА: Заказ и Действия -->
                <div class="col-lg-8">
                    <!-- Блок заказа -->
                    <div class="info-card mb-4">
                        <div class="card-header-modern">
                            <h5 class="card-title mb-0">
                                <i class="fa-solid fa-receipt"></i>
                                Текущий заказ
                            </h5>
                            <div class="btn-group-modern" v-if="!table?.closed_at && table?.officiant">
                                <button class="btn-action btn-success" @click="changeOrderStatus(0)">
                                    <i class="fa-solid fa-check"></i> Подтвердить
                                </button>
                                <button class="btn-action btn-danger" @click="changeOrderStatus(1)">
                                    <i class="fa-solid fa-ban"></i> Отклонить
                                </button>
                            </div>
                        </div>

                        <div v-if="basket.length === 0" class="empty-state-modern">
                            <i class="fa-solid fa-basket-shopping fa-2x text-muted mb-3"></i>
                            <p class="mb-0">Заказ пока пуст</p>
                        </div>

                        <div v-else class="basket-list">
                            <div v-for="(clientOrder, cIndex) in basket" :key="cIndex" class="client-order-card">
                                <div class="client-header">
                                    <div class="client-avatar">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <div class="client-name">
                                        {{ clientOrder.name || clientOrder.fio_from_telegram || 'Гость' }}
                                    </div>
                                </div>

                                <div class="basket-items">
                                    <div v-for="item in clientOrder.basket" :key="item.id" class="basket-item">
                                        <div class="item-info">
                                            <span class="item-status" :class="item.table_approved_at ? 'approved' : 'pending'">
                                                <i class="fa-solid" :class="item.table_approved_at ? 'fa-check-double' : 'fa-clock'"></i>
                                            </span>
                                            <span class="item-title">{{ item.product?.title || 'Товар удален' }}</span>
                                        </div>
                                        <div class="item-price">
                                            {{ item.count }} × {{ item.product?.current_price }} =
                                            <strong>{{ item.count * item.product?.current_price }} ₽</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="client-total">
                                    <span>По клиенту:</span>
                                    <strong>{{ clientOrder.summary_price || 0 }} ₽</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Доп. услуги -->
                    <div class="info-card mb-4">
                        <h5 class="card-title">
                            <i class="fa-solid fa-plus-circle"></i>
                            Дополнительные услуги
                        </h5>
                        <div class="service-input-row mb-3">
                            <input v-model="newService.title" type="text" class="form-input-modern" placeholder="Название услуги">
                            <input v-model.number="newService.price" type="number" class="form-input-modern price-input" placeholder="Цена">
                            <button class="btn-modern btn-primary" @click="addService" :disabled="!newService.title || table?.closed_at">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div v-if="localServices.length > 0" class="services-list">
                            <div v-for="(service, index) in localServices" :key="index" class="service-item">
                                <div class="service-info">
                                    <span class="service-title">{{ service.title }}</span>
                                    <span class="service-price">{{ service.price }} ₽</span>
                                </div>
                                <button class="btn-icon btn-delete" @click="removeService(index)" :disabled="table?.closed_at">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <button class="btn-modern btn-success w-100 mt-3" @click="saveServices" :disabled="!hasNewServices || table?.closed_at">
                                <i class="fa-solid fa-save"></i> Сохранить услуги в чек
                            </button>
                        </div>
                    </div>

                    <!-- Закрытие столика -->
                    <div class="info-card card-danger">
                        <h5 class="card-title">
                            <i class="fa-solid fa-lock"></i>
                            Закрытие столика
                        </h5>
                        <p class="warning-text">
                            Закрывая столик, вы подтверждаете, что клиент завершил посещение и оплатил заказ.
                        </p>

                        <label class="modern-switch mb-2">
                            <input type="checkbox" v-model="needCashback">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Начислить баллы кэшбэка автоматически</span>
                        </label>

                        <label class="modern-switch mb-3">
                            <input type="checkbox" v-model="confirmClose">
                            <span class="switch-slider"></span>
                            <span class="switch-label fw-bold">Я проверил(а), можно закрывать!</span>
                        </label>

                        <button
                            class="btn-modern btn-danger w-100 p-3"
                            :disabled="!confirmClose || table?.closed_at"
                            @click="handleCloseTable"
                        >
                            <i class="fa-solid fa-lock"></i>
                            Закрыть столик и завершить обслуживание
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="loading-fullscreen">
        <div class="loader-spinner"></div>
        <p>Загрузка данных столика...</p>
    </div>
</template>

<script>
import { useTables } from '@/MobileClient/composables/useTables.js';

export default {
    name: 'TableDetails',

    data() {
        return {
            tablesApi: useTables(),
            tableData: null,
            needCashback: true,
            confirmClose: false,
            newService: { title: '', price: 0 },
            localServices: []
        };
    },

    computed: {
        table() {
            return this.tableData?.table || null;
        },
        clients() {
            return this.tableData?.clients || [];
        },
        basket() {
            return this.tableData?.basket || [];
        },
        summaryPrice() {
            return this.tableData?.summary_price || 0;
        },
        summaryCount() {
            return this.tableData?.summary_count || 0;
        },
        hasNewServices() {
            const originalCount = (this.table?.additional_services || []).length;
            return this.localServices.length > originalCount;
        },
        statusClass() {
            if (this.table?.closed_at) return 'status-closed';
            if (this.table?.officiant_id || this.table?.is_occupied) return 'status-occupied';
            return 'status-free';
        },
        statusLabel() {
            if (this.table?.closed_at) return 'Закрыт';
            if (this.table?.officiant_id || this.table?.is_occupied) return 'В работе';
            return 'Ожидает официанта';
        },
        isCurrentTableLoading() {
            return this.tablesApi.isCurrentTableLoading;
        }
    },

    methods: {
        // 🆕 Методы для визуализации столика
        getTableShape(table) {
            if (!table) return 'round';
            if (table.description?.toLowerCase().includes('диван')) return 'rect';
            return table.seats <= 2 ? 'round' : (table.seats === 4 ? 'square' : 'round');
        },

        getTableSeats(table) {
            if (!table) return [];
            const seats = table.seats || 2;
            const hasSofa = table.description?.toLowerCase().includes('диван') || table.type === 'sofa';
            let seatsConfig = [];

            if (seats <= 2) {
                seatsConfig = [{ type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }];
            } else if (seats === 4) {
                seatsConfig = hasSofa
                    ? [{ type: 'sofa', pos: 'top' }, { type: 'chair', pos: 'bottom' }, { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }]
                    : [{ type: 'chair', pos: 'top' }, { type: 'chair', pos: 'bottom' }, { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }];
            } else {
                seatsConfig = hasSofa
                    ? [{ type: 'sofa', pos: 'top' }, { type: 'chair', pos: 'bottom-left' }, { type: 'chair', pos: 'bottom-right' }, { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }]
                    : [{ type: 'chair', pos: 'top' }, { type: 'chair', pos: 'bottom' }, { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }, { type: 'chair', pos: 'tl' }, { type: 'chair', pos: 'br' }].slice(0, seats);
            }
            return seatsConfig;
        },

        async loadData() {
            try {
                const response = await this.tablesApi.loadTableData({ table_id: this.$route.params.tableId });
                this.tableData = response;
                this.localServices = [...(response.table?.additional_services || [])];
            } catch (error) {
                console.error('Ошибка загрузки данных стола:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить данные стола', type: 'error' });
            }
        },

        async handleWaiterChange() {
            try {
                await this.tablesApi.changeWaiter({ table_id: this.$route.params.tableId });
                await this.loadData();
                this.$notify?.({ title: 'Успех', text: 'Официант успешно изменен', type: 'success' });
            } catch (error) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сменить официанта', type: 'error' });
            }
        },

        async changeOrderStatus(type) {
            try {
                await this.tablesApi.acceptTableOrder({ table_id: this.$route.params.tableId, type });
                await this.loadData();
                this.$notify?.({
                    title: 'Заказ',
                    text: type === 0 ? 'Заказ подтвержден' : 'Заказ отклонен',
                    type: type === 0 ? 'success' : 'warning'
                });
            } catch (error) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось изменить статус', type: 'error' });
            }
        },

        addService() {
            if (!this.newService.title || !this.newService.price) return;
            this.localServices.push({
                title: this.newService.title,
                price: this.newService.price,
                temp_id: Date.now()
            });
            this.newService = { title: '', price: 0 };
        },

        removeService(index) {
            this.localServices.splice(index, 1);
        },

        async saveServices() {
            try {
                await this.tablesApi.storeTableAdditionalServices({
                    table_id: this.$route.params.tableId,
                    services: this.localServices
                });
                await this.loadData();
                this.$notify?.({ title: 'Успех', text: 'Услуги сохранены в чеке', type: 'success' });
            } catch (error) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить услуги', type: 'error' });
            }
        },

        async handleCloseTable() {
            if (!confirm('Вы уверены? Это действие необратимо.')) return;
            try {
                await this.tablesApi.closeTable({
                    table_id: this.$route.params.tableId,
                    need_automatic_cashback: this.needCashback
                });
                this.$notify?.({ title: 'Успех', text: 'Столик успешно закрыт', type: 'success' });
                this.$router.push({ name: 'AdminTablesManager' });
            } catch (error) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось закрыть столик', type: 'error' });
            }
        }
    },

    mounted() {
        this.loadData();
    }
};
</script>

<style scoped>
/* ==========================================
   🎨 MODERN ADMIN VARIABLES & BASE
   ========================================== */
.table-details-page {
    background-color: #F8FAFC;
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #334155;
}

/* ==========================================
   HEADER WITH TABLE VISUALIZATION
   ========================================== */
.modern-header {
    display: flex;
    align-items: center;
    gap: 20px;
}

.btn-back {
    width: 48px; height: 48px;
    border-radius: 12px;
    background: white;
    border: 1px solid #E2E8F0;
    display: flex; align-items: center; justify-content: center;
    color: #64748B;
    transition: all 0.2s;
    cursor: pointer;
    flex-shrink: 0;
}
.btn-back:hover { background: #F1F5F9; color: #0F172A; transform: translateX(-2px); }

.table-preview-mini {
    width: 100px; height: 100px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    position: relative;
    flex-shrink: 0;
}

.floor-plan-bg {
    background-color: #F1F5F9;
    background-image: radial-gradient(#CBD5E1 1px, transparent 1px);
    background-size: 12px 12px;
}

.header-info {
    flex: 1;
}
.page-title { font-size: 1.75rem; font-weight: 700; color: #0F172A; margin: 0 0 8px 0; }

.status-badge-modern {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: white;
    border-radius: 99px;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.status-dot {
    width: 10px; height: 10px; border-radius: 50%;
}
.status-free .status-dot { background: #10B981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); }
.status-free .status-text { color: #059669; }
.status-occupied .status-dot { background: #3B82F6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
.status-occupied .status-text { color: #2563EB; }
.status-closed .status-dot { background: #64748B; box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.2); }
.status-closed .status-text { color: #475569; }

/* ==========================================
   ALERTS
   ========================================== */
.alert-modern {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border-radius: 12px;
    border: 1px solid;
}
.alert-danger {
    background: #FEF2F2;
    border-color: #FECACA;
}
.alert-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    background: #EF4444;
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.alert-content h6 { margin: 0 0 4px 0; color: #991B1B; font-weight: 600; }
.alert-content p { margin: 0; color: #7F1D1D; font-size: 0.9rem; }

/* ==========================================
   INFO CARDS
   ========================================== */
.info-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
}
.card-danger {
    border-color: #FECACA;
    background: #FEF2F2;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #0F172A;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-title i { color: #3B82F6; }

.card-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #F1F5F9;
}
.info-row:last-child { border-bottom: none; }
.info-row .label {
    color: #64748B;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.info-row .label i { color: #94A3B8; font-size: 0.85rem; }
.info-row .value {
    font-weight: 600;
    font-size: 0.95rem;
    color: #0F172A;
}
.info-row.total {
    padding-top: 16px;
    margin-top: 4px;
    border-top: 2px solid #E2E8F0;
    border-bottom: none;
}
.info-row.total .value {
    font-size: 1.25rem;
    color: #3B82F6;
}

/* ==========================================
   BUTTONS
   ========================================== */
.btn-modern {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border: 1px solid transparent;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-modern.btn-primary {
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(37, 99, 235, 0.3); }
.btn-modern.btn-success {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.2);
}
.btn-modern.btn-success:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(5, 150, 105, 0.3); }
.btn-modern.btn-danger {
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);
}
.btn-modern.btn-danger:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(220, 38, 38, 0.3); }
.btn-modern.btn-outline {
    background: white;
    color: #475569;
    border-color: #E2E8F0;
}
.btn-modern.btn-outline:hover:not(:disabled) { background: #F1F5F9; color: #0F172A; border-color: #CBD5E1; }
.btn-modern:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-group-modern {
    display: flex;
    gap: 8px;
}
.btn-action {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-action.btn-success { background: #10B981; color: white; }
.btn-action.btn-success:hover { background: #059669; }
.btn-action.btn-danger { background: #EF4444; color: white; }
.btn-action.btn-danger:hover { background: #DC2626; }

.btn-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: white;
    color: #64748B;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-icon.btn-delete:hover { background: #FEF2F2; color: #EF4444; border-color: #FECACA; }

/* ==========================================
   SWITCHES
   ========================================== */
.modern-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    user-select: none;
}
.modern-switch input { display: none; }
.switch-slider {
    width: 44px; height: 24px;
    background: #CBD5E1;
    border-radius: 99px;
    position: relative;
    transition: background 0.3s ease;
    flex-shrink: 0;
}
.switch-slider::after {
    content: '';
    position: absolute;
    top: 2px; left: 2px;
    width: 20px; height: 20px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #3B82F6; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(20px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.9rem; }

/* ==========================================
   BASKET / ORDER
   ========================================== */
.basket-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.client-order-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px;
}

.client-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #E2E8F0;
}
.client-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}
.client-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: #0F172A;
}

.basket-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.basket-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px dashed #E2E8F0;
}
.basket-item:last-child { border-bottom: none; }

.item-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}
.item-status {
    font-size: 0.9rem;
}
.item-status.pending { color: #F59E0B; }
.item-status.approved { color: #10B981; }
.item-title {
    font-size: 0.9rem;
    color: #334155;
}

.item-price {
    font-size: 0.85rem;
    color: #64748B;
    white-space: nowrap;
}
.item-price strong { color: #0F172A; }

.client-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 2px solid #E2E8F0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #3B82F6;
}

/* ==========================================
   SERVICES
   ========================================== */
.service-input-row {
    display: flex;
    gap: 8px;
}
.form-input-modern {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.2s;
    background: white;
}
.form-input-modern:focus { outline: none; border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.price-input { max-width: 120px; }

.services-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.service-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
}
.service-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.service-title { font-weight: 600; font-size: 0.9rem; color: #0F172A; }
.service-price { font-size: 0.85rem; color: #64748B; }

/* ==========================================
   EMPTY & LOADING STATES
   ========================================== */
.empty-state-modern {
    text-align: center;
    padding: 40px 20px;
    color: #94A3B8;
}

.loading-fullscreen {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    color: #94A3B8;
}
.loader-spinner {
    width: 48px; height: 48px;
    border: 3px solid #E2E8F0;
    border-top-color: #3B82F6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 20px;
}
@keyframes spin { to { transform: rotate(360deg); } }

.warning-text {
    color: #7F1D1D;
    font-size: 0.9rem;
    margin-bottom: 16px;
}

/* ==========================================
   🪑 CSS TABLES (Из конструктора)
   ========================================== */
.css-table { position: relative; width: 90px; height: 90px; display: flex; align-items: center; justify-content: center; z-index: 1; }
.css-table-md { width: 80px; height: 80px; }

.table-top {
    background: linear-gradient(145deg, #3B82F6 0%, #2563EB 100%);
    box-shadow: inset 0 2px 4px rgba(255,255,255,0.3), 0 8px 16px -4px rgba(37, 99, 235, 0.4);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; border: 2px solid rgba(255,255,255,0.2);
    z-index: 2;
}
.css-table-md .table-top { width: 45px !important; height: 45px !important; font-size: 0.9rem !important; }
.shape-round .table-top { border-radius: 50%; width: 50px; height: 50px; }
.shape-square .table-top { border-radius: 10px; width: 55px; height: 55px; }
.shape-rect .table-top { border-radius: 12px; width: 70px; height: 45px; }

.seat { position: absolute; z-index: 1; }
.type-chair {
    width: 18px; height: 18px;
    background: linear-gradient(145deg, #94A3B8 0%, #64748B 100%);
    border-radius: 5px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2), inset 0 1px 1px rgba(255,255,255,0.3);
}
.type-sofa {
    background: linear-gradient(145deg, #8B5CF6 0%, #7C3AED 100%);
    border-radius: 7px;
    box-shadow: 0 6px 10px -2px rgba(124, 58, 237, 0.3);
}

.pos-top { top: -3px; left: 50%; transform: translateX(-50%); width: 35px; height: 12px; }
.pos-bottom { bottom: -3px; left: 50%; transform: translateX(-50%); width: 18px; height: 18px; }
.pos-bottom-left { bottom: 2px; left: 15%; transform: translateX(-50%); width: 18px; height: 18px; }
.pos-bottom-right { bottom: 2px; right: 15%; transform: translateX(50%); width: 18px; height: 18px; }
.pos-left { left: -3px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; }
.pos-right { right: -3px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; }
.pos-tl { top: 5px; left: 5px; width: 14px; height: 14px; }
.pos-br { bottom: 5px; right: 5px; width: 14px; height: 14px; }

/* ==========================================
   RESPONSIVE
   ========================================== */
@media (max-width: 768px) {
    .modern-header { flex-wrap: wrap; }
    .service-input-row { flex-wrap: wrap; }
    .price-input { max-width: 100%; }
    .btn-group-modern { width: 100%; }
    .btn-action { flex: 1; justify-content: center; }
}
</style>
