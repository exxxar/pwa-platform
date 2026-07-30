<template>
    <div class="order-details-page" v-if="order">
        <!-- Шапка с навигацией и статусом -->
        <div class="details-header">
            <router-link to="/admin/orders" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Назад к списку
            </router-link>
            <div class="header-title-block">
                <h2>Заказ #{{ order.id }}</h2>
                <span class="order-status" :class="getStatusClass(order)">
                    {{ getStatusText(order) }}
                </span>
            </div>
        </div>

        <div class="details-grid">
            <!-- ЛЕВАЯ КОЛОНКА: Информация о заказе -->
            <div class="info-panel">
                <!-- Карточка клиента -->
                <div class="panel-card">
                    <h3><i class="fa-solid fa-user me-2"></i>Клиент</h3>
                    <div class="client-block">
                        <div class="client-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="client-data">
                            <div class="name">{{ order.receiver_name || 'Гость' }}</div>
                            <div class="base">{{ order.tenant_user?.name || 'Не указано' }}</div>
                            <a :href="'tel:' + (order.receiver_phone || order.tenant_user?.phone)" class="phone">
                                <i class="fa-solid fa-phone me-1"></i>{{ order.receiver_phone || order.tenant_user?.phone || 'Не указан' }}
                            </a>
                        </div>

                    </div>

                    <router-link
                        v-if="order.dialog_id"
                        :to="{ name: 'ChatRoom', params: { id: order.dialog_id } }"
                        target="_blank"
                        class="btn-chat"
                    >
                        <i class="fa-solid fa-comments me-1"></i> В чат
                    </router-link>
                </div>

                <!-- 🆕 Карточка деталей доставки (на основе данных из BasketHelper) -->
                <div class="panel-card">
                    <h3><i class="fa-solid fa-truck-fast me-2"></i>Детали доставки</h3>
                    <div class="delivery-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Тип получения</span>
                            <span class="detail-value">
                                <i :class="isDelivery ? 'fa-solid fa-motorcycle text-primary' : 'fa-solid fa-store text-success'"></i>
                                {{ isDelivery ? 'Доставка' : 'Самовывоз' }}
                            </span>
                        </div>

                        <template v-if="isDelivery">
                            <div class="detail-item full-width">
                                <span class="detail-label">Адрес</span>
                                <span class="detail-value">{{ order.address || 'Не указан' }}</span>
                            </div>
                            <div class="detail-item" v-if="order.flat_number">
                                <span class="detail-label">Кв. / Офис</span>
                                <span class="detail-value">{{ order.flat_number }}</span>
                            </div>
                            <div class="detail-item" v-if="order.entrance_number">
                                <span class="detail-label">Подъезд</span>
                                <span class="detail-value">{{ order.entrance_number }}</span>
                            </div>
                            <div class="detail-item" v-if="order.floor_number">
                                <span class="detail-label">Этаж</span>
                                <span class="detail-value">{{ order.floor_number }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Стоимость доставки</span>
                                <span class="detail-value highlight">{{ formatPrice(order.delivery_price) }} ₽</span>
                            </div>
                            <div class="detail-item" v-if="order.delivery_range">
                                <span class="detail-label">Расстояние</span>
                                <span class="detail-value">{{ order.delivery_range }} км</span>
                            </div>
                        </template>

                        <div class="detail-item full-width">
                            <span class="detail-label">Комментарий / Детали</span>
                            <span class="detail-value note">{{ order.delivery_note || 'Нет дополнительного комментария' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Карточка состава заказа -->
                <div class="panel-card">
                    <h3><i class="fa-solid fa-receipt me-2"></i>Состав заказа</h3>

                    <div class="order-products">
                        <!-- 🆕 Используем вычисляемое свойство orderProducts для безопасного доступа -->
                        <div
                            v-for="(item, idx) in orderProducts"
                            :key="idx"
                            class="product-item"
                        >
                            <span class="product-qty">{{ item.count }}×</span>
                            <span class="product-name">{{ item.name }}</span>
                            <span class="product-price">{{ formatPrice(item.price) }} ₽</span>
                        </div>
                    </div>

                    <div class="order-total">
                        <span>Итого:</span>
                        <span class="total-value">{{ formatPrice(order.summary_price) }} ₽</span>
                    </div>
                </div>

                <!-- Карточка управления статусом -->
                <div class="panel-card">
                    <h3><i class="fa-solid fa-sliders me-2"></i>Управление статусом</h3>
                    <div class="status-control">
                        <select v-model="newStatus" class="modern-select">
                            <option value="new">Новый</option>
                            <option value="processing">В обработке</option>
                            <option value="completed">Выполнен</option>
                            <option value="cancelled">Отменен</option>
                        </select>
                        <button @click="changeStatus" :disabled="isSaving" class="btn-save">
                            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                            <span v-else>Сохранить</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ КОЛОНКА: Быстрое сообщение -->
            <div class="chat-panel" v-if="order.dialog_id">
                <div class="panel-card chat-card">
                    <h3><i class="fa-solid fa-paper-plane me-2"></i>Сообщение клиенту</h3>
                    <p class="hint">Сообщение будет отправлено в чат, привязанный к этому заказу.</p>

                    <textarea
                        v-model="quickMessage"
                        rows="4"
                        placeholder="Например: Ваш заказ уже передан курьеру..."
                        class="message-input"
                    ></textarea>

                    <div class="quick-templates">
                        <button @click="setTemplate('Ваш заказ принят и передан на кухню! 👨‍🍳')" class="template-btn">
                            На кухне
                        </button>
                        <button @click="setTemplate('Курьер уже выехал к вам! 🚗')" class="template-btn">
                            Курьер едет
                        </button>
                        <button @click="setTemplate('Заказ успешно доставлен. Приятного аппетита! ✅')" class="template-btn">
                            Доставлен
                        </button>
                    </div>

                    <button @click="sendQuickMessage" :disabled="isSending || !quickMessage.trim()" class="btn-send">
                        <i v-if="isSending" class="fa-solid fa-spinner fa-spin me-2"></i>
                        <span v-else><i class="fa-solid fa-paper-plane me-2"></i>Отправить сообщение</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useOrders } from '@/MobileClient/composables/useOrders';

export default {
    name: 'AdminOrderDetails',
    setup() {
        const { loadAdminOrderDetails, updateAdminOrderStatus, sendAdminOrderMessage } = useOrders();
        return { loadAdminOrderDetails, updateAdminOrderStatus, sendAdminOrderMessage };
    },
    data() {
        return {
            order: null,
            newStatus: '',
            quickMessage: '',
            isSaving: false,
            isSending: false,
        };
    },
    computed: {
        // 🆕 Безопасно извлекаем массив товаров, так как product_details приходит как [{ from: "...", products: [...] }]
        orderProducts() {
            if (!this.order?.product_details) return [];
            const details = Array.isArray(this.order.product_details)
                ? this.order.product_details[0]
                : this.order.product_details;
            return details?.products || [];
        },
        // 🆕 Определяем тип заказа на основе наличия стоимости доставки или дистанции
        isDelivery() {
            return (this.order?.delivery_price > 0) || (this.order?.delivery_range > 0);
        }
    },
    async mounted() {
        try {
            this.order = await this.loadAdminOrderDetails(this.$route.params.id);
            this.newStatus = this.order.status;
        } catch (e) {
            this.$notify?.({ title: 'Ошибка', text: 'Заказ не найден', type: 'error' });
            this.$router.push('/admin/orders');
        }
    },
    methods: {
        async changeStatus() {
            this.isSaving = true;
            try {
                await this.updateAdminOrderStatus(this.order.id, this.newStatus);
                this.order.status = this.newStatus;
                this.$notify?.({ title: 'Успех', text: 'Статус обновлен', type: 'success' });
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось изменить статус', type: 'error' });
            } finally {
                this.isSaving = false;
            }
        },
        setTemplate(text) {
            this.quickMessage = text;
        },
        async sendQuickMessage() {
            if (!this.order.dialog_id) {
                return this.$notify?.({ title: 'Ошибка', text: 'У заказа нет привязанного чата', type: 'warning' });
            }
            this.isSending = true;
            try {
                await this.sendAdminOrderMessage(this.order.id, this.quickMessage);
                this.$notify?.({ title: 'Отправлено', text: 'Сообщение доставлено клиенту', type: 'success' });
                this.quickMessage = '';
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось отправить', type: 'error' });
            } finally {
                this.isSending = false;
            }
        },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0).replace('RUB', '₽').trim();
        },
        getStatusClass(order) {
            const status = String(order.status ?? '').toLowerCase();
            const statusMap = {
                '0': 'status-new', '1': 'status-processing', '2': 'status-completed',
                '3': 'status-cancelled', '4': 'status-processing', '5': 'status-processing',
            };
            if (statusMap[status]) return statusMap[status];
            if (status.includes('cancel') || status.includes('отмен')) return 'status-cancelled';
            if (status.includes('complet') || status.includes('выполн') || status.includes('доставлен')) return 'status-completed';
            if (status.includes('process') || status.includes('готов') || status.includes('в пути')) return 'status-processing';
            return 'status-new';
        },
        getStatusText(order) {
            const status = String(order.status ?? '').toLowerCase();
            const statusTextMap = {
                '0': 'Новый', '1': 'В обработке', '2': 'Выполнен',
                '3': 'Отменён', '4': 'Готов к доставке', '5': 'Передан на кухню',
            };
            if (statusTextMap[status]) return statusTextMap[status];
            if (status.includes('cancel') || status.includes('отмен')) return 'Отменён';
            if (status.includes('complet') || status.includes('выполн') || status.includes('доставлен')) return 'Выполнен';
            if (status.includes('process') || status.includes('готов') || status.includes('в пути')) return 'В обработке';
            return 'Новый';
        }
    }
};
</script>

<style scoped>
.order-details-page {
    padding: 24px;
    max-width: 1200px;
    margin: 0 auto;
}

/* ===== ШАПКА ===== */
.details-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}

.btn-back {
    text-decoration: none;
    color: var(--bs-secondary-color);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.2s;
}

.btn-back:hover {
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.header-title-block {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-title-block h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--bs-body-color);
}

/* ===== СЕТКА ===== */
.details-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 24px;
}

@media (max-width: 900px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
}

/* ===== КАРТОЧКИ ===== */
.panel-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.panel-card h3 {
    margin: 0 0 16px 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--bs-body-color);
    display: flex;
    align-items: center;
}

.panel-card h3 i {
    color: var(--bs-primary);
}

/* ===== КЛИЕНТ ===== */
.client-block {
    display: flex;
    align-items: center;
    gap: 16px;
}

.client-avatar {
    width: 52px;
    height: 52px;
    background: var(--bs-secondary-bg);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.2rem;
    flex-shrink: 0;
}

.client-data {
    flex: 1;
}

.client-data .base {
    font-weight: 400;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    line-height: 100%;
}

.client-data .name {
    font-weight: 600;
    font-size: 1.05rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.client-data .phone {
    color: var(--bs-primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: opacity 0.2s;
}

.client-data .phone:hover {
    opacity: 0.8;
}

.btn-chat {
    padding: 8px 16px;
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s;
    white-space: nowrap;
    margin-top: 20px;
    display: inline-block;
}

.btn-chat:hover {
    background: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

/* 🆕 ===== ДЕТАЛИ ДОСТАВКИ ===== */
.delivery-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-label {
    display: block;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin-bottom: 4px;
    font-weight: 500;
}

.detail-value {
    font-size: 0.95rem;
    color: var(--bs-body-color);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-value.note {
    font-weight: 400;
    font-size: 0.9rem;
    line-height: 1.5;
    color: var(--bs-body-color);
    background: var(--bs-secondary-bg);
    padding: 12px;
    border-radius: 8px;
    white-space: pre-wrap; /* Сохраняет переносы строк из delivery_note */
}

.detail-value.highlight {
    color: var(--bs-primary);
}

@media (max-width: 768px) {
    .delivery-details-grid {
        grid-template-columns: 1fr;
    }
}

/* ===== ТОВАРЫ ===== */
.order-products {
    padding: 12px;
    background: var(--bs-secondary-bg);
    border-radius: 12px;
    margin-bottom: 16px;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    font-size: 0.95rem;
    border-bottom: 1px solid var(--bs-border-color);
}

.product-item:last-child {
    border-bottom: none;
}

.product-qty {
    font-weight: 700;
    color: var(--bs-primary);
    min-width: 28px;
}

.product-name {
    flex: 1;
    color: var(--bs-body-color);
    line-height: 1.4;
}

.product-price {
    font-weight: 600;
    color: var(--bs-body-color);
    white-space: nowrap;
}

.order-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0 4px;
    border-top: 1px dashed var(--bs-border-color);
    font-size: 1.1rem;
}

.order-total span:first-child {
    font-weight: 600;
    color: var(--bs-body-color);
}

.total-value {
    font-weight: 800;
    font-size: 1.25rem;
    color: var(--bs-primary);
}

/* ===== СТАТУСЫ ===== */
.status-control {
    display: flex;
    gap: 12px;
}

.modern-select {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid var(--bs-border-color);
    border-radius: 10px;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-size: 0.95rem;
    transition: border-color 0.2s;
}

.modern-select:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
}

.btn-save {
    padding: 10px 24px;
    background: var(--bs-success);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-save:hover:not(:disabled) {
    background: #0b9c6c;
    transform: translateY(-1px);
}

.btn-save:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.order-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-new { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
.status-processing { background: rgba(255, 193, 7, 0.15); color: #b8860b; }
.status-completed { background: rgba(25, 135, 84, 0.1); color: #198754; }
.status-cancelled { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

/* ===== ЧАТ ПАНЕЛЬ ===== */
.chat-card .hint {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    margin-top: -8px;
    margin-bottom: 16px;
}

.message-input {
    width: 100%;
    padding: 14px;
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    resize: none;
    font-family: inherit;
    font-size: 0.95rem;
    margin-bottom: 16px;
    transition: all 0.2s;
    background: var(--bs-body-bg);
}

.message-input:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
}

.quick-templates {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.template-btn {
    padding: 8px 14px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--bs-body-color);
    cursor: pointer;
    transition: all 0.2s;
}

.template-btn:hover {
    background: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
    transform: translateY(-1px);
}

.btn-send {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.25);
}

.btn-send:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(var(--bs-primary-rgb), 0.35);
}

.btn-send:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
</style>
