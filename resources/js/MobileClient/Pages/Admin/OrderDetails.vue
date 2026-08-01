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

            <!-- 🔥 НОВАЯ КНОПКА ЭКСПОРТА -->
            <button @click="exportToExcel" :disabled="isExporting" class="btn-export-header"
                    title="Скачать детали заказа в Excel">
                <i :class="isExporting ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-file-excel'"></i>
                <span>{{ isExporting ? 'Скачивание...' : 'Скачать чек (Excel)' }}</span>
            </button>
        </div>

        <div class="details-grid">
            <!-- ЛЕВАЯ КОЛОНКА: Информация о заказе -->
            <div class="info-panel">

                <!-- Карточка клиента и получателя -->
                <div class="panel-card">
                    <h3><i class="fa-solid fa-users me-2"></i>Информация о клиенте</h3>

                    <div class="customer-sections">
                        <!-- 🔥 БЛОК 1: ПОЛУЧАТЕЛЬ ЗАКАЗА (Самое важное для доставки) -->
                        <div class="customer-section receiver-section">
                            <div class="section-header">
                                <i class="fa-solid fa-location-dot"></i>
                                <span class="section-title">Получатель заказа</span>
                                <span class="section-hint">Кому доставить</span>
                            </div>

                            <div class="customer-data">
                                <div class="customer-avatar primary">
                                    <i class="fa-solid fa-user-tag"></i>
                                </div>
                                <div class="customer-info">
                                    <div class="customer-name">
                                        {{ order.receiver_name || 'Имя не указано' }}
                                    </div>
                                    <a
                                        v-if="order.receiver_phone"
                                        :href="'tel:' + order.receiver_phone"
                                        class="customer-phone"
                                    >
                                        <i class="fa-solid fa-phone"></i>
                                        {{ order.receiver_phone }}
                                    </a>
                                    <div v-else class="customer-phone empty">
                                        <i class="fa-solid fa-phone-slash"></i>
                                        Телефон не указан
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 🔥 БЛОК 2: ДАННЫЕ АККАУНТА (Зарегистрированный пользователь) -->
                        <div class="customer-section account-section" v-if="order.tenant_user">
                            <div class="section-header">
                                <i class="fa-solid fa-user-circle"></i>
                                <span class="section-title">Данные аккаунта</span>
                                <span class="section-hint">Кто оформил заказ</span>
                            </div>

                            <div class="customer-data">
                                <div class="customer-avatar secondary">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div class="customer-info">
                                    <div class="customer-name">
                                        {{ order.tenant_user.name || 'Гость' }}
                                    </div>
                                    <a
                                        v-if="order.tenant_user.phone"
                                        :href="'tel:' + order.tenant_user.phone"
                                        class="customer-phone"
                                    >
                                        <i class="fa-solid fa-phone"></i>
                                        {{ order.tenant_user.phone }}
                                    </a>
                                    <div v-else class="customer-phone empty">
                                        <i class="fa-solid fa-phone-slash"></i>
                                        Телефон не указан
                                    </div>

                                    <!-- Дополнительно: ссылка на профиль пользователя -->
                                    <router-link
                                        v-if="order.tenant_user.id"
                                        :to="{ name: 'AdminUserDetails', params: { id: order.tenant_user.id } }"
                                        class="profile-link"
                                        target="_blank"
                                    >
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        Открыть профиль
                                    </router-link>
                                </div>
                            </div>
                        </div>

                        <!-- Заглушка, если заказ сделал неавторизованный гость -->
                        <div class="customer-section account-section" v-else>
                            <div class="section-header">
                                <i class="fa-solid fa-user-secret"></i>
                                <span class="section-title">Гость</span>
                            </div>
                            <div class="customer-data">
                                <div class="customer-avatar muted">
                                    <i class="fa-solid fa-user-slash"></i>
                                </div>
                                <div class="customer-info">
                                    <div class="customer-name text-muted">Заказ оформлен без регистрации</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка открытия чата (без изменений) -->
                    <router-link
                        v-if="order.dialog_id"
                        :to="{ name: 'ChatRoom', params: { id: order.dialog_id } }"
                        target="_blank"
                        class="btn-chat"
                    >
                        <i class="fa-solid fa-comments me-1"></i> Открыть чат с клиентом
                    </router-link>
                </div>

                <!-- Карточка деталей доставки -->
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
                            <!-- 🔥 ИСПРАВЛЕНО: Безопасное получение адреса (проверяем и корень, и delivery_service_info) -->
                            <div class="detail-item full-width">
                                <span class="detail-label">Адрес</span>
                                <span class="detail-value">{{
                                        order.address || order.delivery_service_info?.address || 'Не указан'
                                    }}</span>
                            </div>
                            <div class="detail-item"
                                 v-if="order.flat_number || order.delivery_service_info?.flat_number">
                                <span class="detail-label">Кв. / Офис</span>
                                <span class="detail-value">{{
                                        order.flat_number || order.delivery_service_info?.flat_number
                                    }}</span>
                            </div>
                            <div class="detail-item"
                                 v-if="order.entrance_number || order.delivery_service_info?.entrance_number">
                                <span class="detail-label">Подъезд</span>
                                <span class="detail-value">{{
                                        order.entrance_number || order.delivery_service_info?.entrance_number
                                    }}</span>
                            </div>
                            <div class="detail-item"
                                 v-if="order.floor_number || order.delivery_service_info?.floor_number">
                                <span class="detail-label">Этаж</span>
                                <span class="detail-value">{{
                                        order.floor_number || order.delivery_service_info?.floor_number
                                    }}</span>
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
                            <span class="detail-value note">{{
                                    order.delivery_note || 'Нет дополнительного комментария'
                                }}</span>
                        </div>
                    </div>
                </div>

                <!-- Карточка состава заказа -->
                <div class="panel-card">
                    <h3><i class="fa-solid fa-receipt me-2"></i>Состав заказа</h3>

                    <div class="order-products" v-if="orderProducts.length > 0">
                        <div v-for="(item, idx) in orderProducts" :key="idx" class="product-item">
                            <span class="product-qty">{{ item.count }}×</span>
                            <span class="product-name">{{ item.name || 'Товар' }}</span>
                            <span class="product-price">{{ formatPrice(item.price) }} ₽</span>
                        </div>
                    </div>
                    <div v-else class="text-muted text-center py-3">Состав заказа не указан</div>

                    <div class="order-total">
                        <span>Итого:</span>
                        <span class="total-value">{{ formatPrice(order.summary_price) }} ₽</span>
                    </div>
                </div>

                <!-- Карточка управления статусом -->
                <div class="panel-card">
                    <h3><i class="fa-solid fa-sliders me-2"></i>Управление статусом</h3>
                    <div class="status-control">
                        <!-- 🔥 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Используем числовые значения, как в БД -->
                        <select v-model="newStatus" class="modern-select" :disabled="isSaving">
                            <option :value="0">🆕 Новый</option>
                            <option :value="1">⏳ В обработке</option>
                            <option :value="4">🍳 Передан на кухню</option>
                            <option :value="5">🚗 Готов к доставке</option>
                            <option :value="2">✅ Выполнен</option>
                            <option :value="3">❌ Отменен</option>
                        </select>
                        <button @click="changeStatus" :disabled="isSaving || !isStatusChanged" class="btn-save">
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
                        :disabled="isSending"
                    ></textarea>

                    <div class="quick-templates">
                        <button @click="setTemplate('Ваш заказ принят и передан на кухню! 👨‍🍳')" class="template-btn"
                                :disabled="isSending">
                            На кухне
                        </button>
                        <button @click="setTemplate('Курьер уже выехал к вам! 🚗')" class="template-btn"
                                :disabled="isSending">
                            Курьер едет
                        </button>
                        <button @click="setTemplate('Заказ успешно доставлен. Приятного аппетита! ✅')"
                                class="template-btn" :disabled="isSending">
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

    <!-- Заглушка, если заказ не найден -->
    <div v-else-if="!isLoadingDetails" class="empty-state">
        <h3>Заказ не найден или удален</h3>
        <router-link to="/admin/orders" class="btn-back">Вернуться к списку</router-link>
    </div>
</template>

<script>
import { useOrders } from '@/MobileClient/composables/useOrders';
import axios from 'axios'; // На всякий случай, если используется в экспорте

export default {
    name: 'AdminOrderDetails',
    setup() {
        const ordersData = useOrders();

        // 🔥 ЛОГ 1: Проверяем, что приходит из composable на странице деталей
        console.log('[AdminOrderDetails] setup - useOrders вернул:', {
            loadAdminOrderDetailsType: typeof ordersData.loadAdminOrderDetails,
            loadAdminOrderDetailsValue: ordersData.loadAdminOrderDetails,
            updateAdminOrderStatusType: typeof ordersData.updateAdminOrderStatus,
            sendAdminOrderMessageType: typeof ordersData.sendAdminOrderMessage,
        });

        const { loadAdminOrderDetails, updateAdminOrderStatus, sendAdminOrderMessage } = ordersData;
        return { loadAdminOrderDetails, updateAdminOrderStatus, sendAdminOrderMessage };
    },
    data() {
        return {
            order: null,
            newStatus: null,
            quickMessage: '',
            isSaving: false,
            isSending: false,
            isLoadingDetails: true,
            isExporting: false,
        };
    },
    computed: {
        orderProducts() {
            if (!this.order?.product_details) return [];
            const details = Array.isArray(this.order.product_details)
                ? this.order.product_details[0]
                : this.order.product_details;
            return Array.isArray(details?.products) ? details.products : [];
        },
        isDelivery() {
            return (Number(this.order?.delivery_price) > 0) || (Number(this.order?.delivery_range) > 0);
        },
        isStatusChanged() {
            return String(this.order?.status) !== String(this.newStatus);
        }
    },
    async mounted() {
        console.log('[AdminOrderDetails] mounted - начало загрузки');
        this.isLoadingDetails = true;

        // 🔥 ЛОГ 2: Проверяем тип функции ПЕРЕД вызовом
        console.log('[AdminOrderDetails] mounted - typeof this.loadAdminOrderDetails:', typeof this.loadAdminOrderDetails);
        console.log('[AdminOrderDetails] mounted - значение this.loadAdminOrderDetails:', this.loadAdminOrderDetails);

        try {
            const orderId = this.$route.params.id;
            console.log(`[AdminOrderDetails] mounted - запрашиваем заказ с ID: ${orderId}`);

            if (typeof this.loadAdminOrderDetails !== 'function') {
                throw new Error('loadAdminOrderDetails не является функцией! Проверьте useOrders.js');
            }

            // 🔥 ЛОГ 3: Ловим момент вызова
            console.log('[AdminOrderDetails] mounted - вызываем await this.loadAdminOrderDetails...');
            this.order = await this.loadAdminOrderDetails(orderId);
            console.log('[AdminOrderDetails] mounted - заказ успешно загружен:', this.order);

            this.newStatus = Number(this.order.status);
        } catch (e) {
            console.error('🚨 [AdminOrderDetails] mounted - КРИТИЧЕСКАЯ ОШИБКА:', e);
            this.$notify?.({ title: 'Ошибка', text: 'Заказ не найден или произошла ошибка загрузки', type: 'error' });
            this.$router.push('/admin/orders');
        } finally {
            this.isLoadingDetails = false;
            console.log('[AdminOrderDetails] mounted - загрузка завершена, isLoadingDetails = false');
        }
    },
    methods: {
        async exportToExcel() {
            if (!this.order?.id) return;
            this.isExporting = true;
            try {
                const response = await axios.get(`/admin/orders/${this.order.id}/export`, {
                    responseType: 'blob'
                });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                const disposition = response.headers['content-disposition'];
                let filename = `order_${this.order.id}.xlsx`;
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
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось скачать файл', type: 'error' });
            } finally {
                this.isExporting = false;
            }
        },
        async changeStatus() {
            if (!this.isStatusChanged) return;
            this.isSaving = true;
            try {
                await this.updateAdminOrderStatus(this.order.id, Number(this.newStatus));
                this.order.status = Number(this.newStatus);
                this.$notify?.({ title: 'Успех', text: 'Статус заказа обновлен', type: 'success' });
            } catch (e) {
                console.error(e);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось изменить статус', type: 'error' });
                this.newStatus = Number(this.order.status);
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
                console.error(e);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось отправить сообщение', type: 'error' });
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
            if (status.includes('process') || status.includes('готов') || status.includes('в пути') || status.includes('кухн')) return 'status-processing';
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
            if (status.includes('process') || status.includes('готов') || status.includes('в пути') || status.includes('кухн')) return 'В обработке';
            return 'Новый';
        }
    }
};
</script>

<!-- Ваши стили <style lang="scss" scoped> остаются без изменений -->

<style scoped lang="scss">
.order-details-page {
    padding: 24px;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    box-sizing: border-box; // Гарантирует, что padding не увеличивает ширину
    overflow-x: hidden;
}

/* ===== ШАПКА ===== */
.details-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
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
    flex-shrink: 0;
}

.btn-back:hover {
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.header-title-block {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1 1 auto;
    min-width: 0; // 🔥 КРИТИЧЕСКИ ВАЖНО: позволяет сжиматься

    h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--bs-body-color);
        word-break: break-word;
        line-height: 1.2;
    }
}

.order-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    flex-shrink: 0;
}

.status-new { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
.status-processing { background: rgba(255, 193, 7, 0.15); color: #b8860b; }
.status-completed { background: rgba(25, 135, 84, 0.1); color: #198754; }
.status-cancelled { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

/* ===== КНОПКА ЭКСПОРТА ===== */
.btn-export-header {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--bs-body-bg);
    color: #10b981;
    border: 1px solid #10b981;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        background: #10b981;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        border-color: var(--bs-border-color);
        color: var(--bs-secondary-color);
    }
}

/* ===== СЕТКА ===== */
.details-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 24px;
}

/* ===== КАРТОЧКИ ===== */
.panel-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    overflow-wrap: break-word;
    word-wrap: break-word;
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
    align-items: flex-start;
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
    min-width: 0; // 🔥 Позволяет сжиматься
}

.client-data .base {
    font-weight: 400;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    line-height: 1.2;
    word-break: break-word;
}

.client-data .name {
    font-weight: 600;
    font-size: 1.05rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    word-break: break-word;
}

.client-data .phone {
    color: var(--bs-primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: opacity 0.2s;
    word-break: break-all;
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

/* ===== ДЕТАЛИ ДОСТАВКИ ===== */
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
    word-break: break-word;
}

.detail-value.note {
    font-weight: 400;
    font-size: 0.9rem;
    line-height: 1.5;
    color: var(--bs-body-color);
    background: var(--bs-secondary-bg);
    padding: 12px;
    border-radius: 8px;
    white-space: pre-wrap;
    word-break: break-word;
}

.detail-value.highlight {
    color: var(--bs-primary);
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
    min-width: 0;
    flex-wrap: wrap; // 🔥 ДОБАВЛЕНО: позволяет переносить строку, если название очень длинное
}

.product-item:last-child {
    border-bottom: none;
}

.product-qty {
    font-weight: 700;
    color: var(--bs-primary);
    min-width: 28px;
    flex-shrink: 0;
}

.product-name {
    flex: 1;
    color: var(--bs-body-color);
    line-height: 1.4;
    min-width: 0;
    word-break: break-word;
}

.product-price {
    font-weight: 600;
    color: var(--bs-body-color);
    white-space: nowrap;
    flex-shrink: 0;
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
    white-space: nowrap;
}

/* ===== СТАТУСЫ И УПРАВЛЕНИЕ ===== */
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
    min-width: 0;
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
    flex-shrink: 0;
}

.btn-save:hover:not(:disabled) {
    background: #0b9c6c;
    transform: translateY(-1px);
}

.btn-save:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

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
    box-sizing: border-box;
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
    flex: 1 1 auto; // 🔥 Равномерно распределяет кнопки на мобильных
    text-align: center;
    min-width: 120px;
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
    box-sizing: border-box;
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

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--bs-secondary-color);
}

/* =========================================================
   🔥 МОБИЛЬНЫЕ АДАПТИВЫ (ИСПРАВЛЕНИЕ ВЫЛЕЗАНИЯ)
   ========================================================= */

@media (max-width: 900px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .order-details-page {
        padding: 16px; // Уменьшаем отступы на мобильных
    }

    .details-header {
        flex-direction: column;
        align-items: stretch; // Растягиваем элементы на всю ширину
    }

    .header-title-block {
        width: 100%;
        justify-content: space-between;
    }

    .header-title-block h2 {
        font-size: 1.25rem; // Чуть меньше шрифт заголовка
    }

    .btn-export-header {
        width: 100%;
        margin-top: 8px;
    }

    .delivery-details-grid {
        grid-template-columns: 1fr; // Одна колонка для деталей доставки
    }

    // 🔥 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Ставим select и кнопку друг под другом
    .status-control {
        flex-direction: column;
    }

    .btn-save {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .client-block {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .client-data {
        width: 100%;
    }

    .btn-chat {
        width: 100%;
        text-align: center;
        margin-top: 16px;
    }

    .template-btn {
        width: 100%; // Кнопки шаблонов на всю ширину на очень маленьких экранах
    }
}

// --- Разделенная карточка клиента ---
.customer-sections {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.customer-section {
    background: var(--bs-secondary-bg);
    border-radius: 12px;
    padding: 16px;
    border: 1px solid transparent;
    transition: all 0.2s;

    &:hover {
        border-color: var(--bs-border-color);
    }
}

.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;

    i {
        color: var(--bs-primary);
        font-size: 0.9rem;
    }

    .section-title {
        color: var(--bs-body-color);
    }

    .section-hint {
        margin-left: auto;
        color: var(--bs-secondary-color);
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: none;
        letter-spacing: normal;
    }
}

// Выделяем блок получателя (он важнее для доставки)
.receiver-section {
    background: rgba(var(--bs-primary-rgb), 0.04);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.1);
}

.customer-data {
    display: flex;
    align-items: center;
    gap: 12px;
}

.customer-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;

    &.primary {
        background: var(--bs-primary);
        color: white;
    }

    &.secondary {
        background: var(--bs-body-bg);
        color: var(--bs-secondary-color);
        border: 1px solid var(--bs-border-color);
    }

    &.muted {
        background: var(--bs-border-color);
        color: var(--bs-secondary-color);
    }
}

.customer-info {
    flex: 1;
    min-width: 0;
}

.customer-name {
    font-weight: 600;
    font-size: 1rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    word-break: break-word;
    line-height: 1.3;
}

.customer-phone {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--bs-primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 6px;
    transition: opacity 0.2s;

    &:hover {
        opacity: 0.8;
    }

    &.empty {
        color: var(--bs-secondary-color);
        cursor: default;
        font-weight: 400;
    }
}

.profile-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--bs-secondary-color);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    margin-top: 4px;
    transition: color 0.2s;

    &:hover {
        color: var(--bs-primary);
    }
}
</style>
