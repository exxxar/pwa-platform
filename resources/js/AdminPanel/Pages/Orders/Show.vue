<template>
    <div class="order-show-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Заказ #{{ order?.id || '...' }}</h1>
                <p class="page-subtitle">{{ formatDate(order?.created_at) }}</p>
            </div>
            <div class="header-actions">
                <Button
                    v-if="authStore.hasPermission('orders.update_status')"
                    variant="outline"
                    @click="openChangeStatusModal"
                >
                    🔄 Изменить статус
                </Button>
                <Button
                    v-if="order?.dialog && authStore.hasPermission('dialogs.view')"
                    variant="info"
                    @click="$router.push({ name: 'admin.dialogs.show', params: { id: order.dialog.id } })"
                >
                    💬 Открыть диалог
                </Button>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <span>Загрузка...</span>
        </div>

        <div v-else-if="order" class="order-content">
            <!-- Status -->
            <div class="status-section">
                <StatusBadge :variant="getStatusVariant(order.status)" size="lg">
                    {{ getStatusLabel(order.status) }}
                </StatusBadge>
                <StatusBadge :variant="order.is_paid ? 'success' : 'warning'" size="lg">
                    {{ order.is_paid ? 'Оплачен' : 'Не оплачен' }}
                </StatusBadge>
            </div>

            <!-- User & Tenant -->
            <div class="info-section">
                <h2 class="section-title">Информация о клиенте</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Пользователь</div>
                        <div class="info-value">
                            <router-link
                                v-if="order.user"
                                :to="{ name: 'admin.tenant-users.show', params: { id: order.user.id } }"
                                class="link"
                            >
                                {{ order.user.name }}
                            </router-link>
                            <span v-else>Неизвестный</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <div class="info-value">{{ order.user?.phone || order.receiver_phone || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Получатель</div>
                        <div class="info-value">{{ order.receiver_name || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Тенант</div>
                        <div class="info-value">{{ order.tenant?.name || '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="info-section">
                <h2 class="section-title">Товары ({{ order.product_count }} шт.)</h2>
                <div class="products-list">
                    <div
                        v-for="(product, index) in order.product_details"
                        :key="index"
                        class="product-card"
                    >
                        <div class="product-info">
                            <div class="product-name">{{ product.name || 'Товар' }}</div>
                            <div class="product-details">
                                <span class="product-count">{{ product.count || 1 }} шт.</span>
                                <span v-if="product.price" class="product-price">
                                    × {{ formatCurrency(product.price) }}
                                </span>
                            </div>
                            <div v-if="product.comment" class="product-comment">
                                💬 {{ product.comment }}
                            </div>
                        </div>
                        <div class="product-total">
                            {{ formatCurrency((product.count || 1) * (product.price || 0)) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="info-section">
                <h2 class="section-title">Финансовая информация</h2>
                <div class="financial-summary">
                    <div class="summary-row">
                        <span class="summary-label">Стоимость товаров</span>
                        <span class="summary-value">{{ formatCurrency(order.summary_price) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Стоимость доставки</span>
                        <span class="summary-value">{{ formatCurrency(order.delivery_price) }}</span>
                    </div>
                    <div class="summary-row total">
                        <span class="summary-label">Итого</span>
                        <span class="summary-value">{{ formatCurrency(order.summary_price + order.delivery_price) }}</span>
                    </div>
                    <div v-if="order.payed_at" class="summary-row">
                        <span class="summary-label">Оплачен</span>
                        <span class="summary-value">{{ formatDateTime(order.payed_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Delivery Info -->
            <div class="info-section">
                <h2 class="section-title">Информация о доставке</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Дальность доставки</div>
                        <div class="info-value">{{ order.delivery_range ? `${order.delivery_range} км` : '-' }}</div>
                    </div>
                    <div class="info-item full-width">
                        <div class="info-label">Примечание к доставке</div>
                        <div class="info-value">{{ order.delivery_note || '-' }}</div>
                    </div>
                    <div v-if="order.delivery_service_info" class="info-item full-width">
                        <div class="info-label">Служба доставки</div>
                        <div class="info-value">{{ JSON.stringify(order.delivery_service_info) }}</div>
                    </div>
                    <div v-if="order.deliveryman_info" class="info-item full-width">
                        <div class="info-label">Информация о курьере</div>
                        <div class="info-value">{{ JSON.stringify(order.deliveryman_info) }}</div>
                    </div>
                    <div v-if="order.deliveryman_latitude && order.deliveryman_longitude" class="info-item">
                        <div class="info-label">Координаты курьера</div>
                        <div class="info-value mono">
                            {{ order.deliveryman_latitude }}, {{ order.deliveryman_longitude }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dialog -->
            <div v-if="order.dialog" class="info-section">
                <h2 class="section-title">Диалог</h2>
                <div class="dialog-info">
                    <div class="info-item">
                        <div class="info-label">ID диалога</div>
                        <div class="info-value">#{{ order.dialog.id }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Статус</div>
                        <div class="info-value">
                            <StatusBadge :variant="order.dialog.is_closed ? 'danger' : 'success'">
                                {{ order.dialog.is_closed ? 'Закрыт' : 'Открыт' }}
                            </StatusBadge>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Заголовок</div>
                        <div class="info-value">{{ order.dialog.title || '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Review -->
            <div v-if="order.review" class="info-section">
                <h2 class="section-title">Отзыв</h2>
                <div class="review-info">
                    <div class="review-rating">
                        <span v-for="i in 5" :key="i" class="star" :class="{ filled: i <= order.review.rating }">
                            ★
                        </span>
                        <span class="rating-value">{{ order.review.rating }}/5</span>
                    </div>
                    <div class="review-comment">
                        {{ order.review.comment || 'Без комментария' }}
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="info-section">
                <h2 class="section-title">Системная информация</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ID заказа</div>
                        <div class="info-value mono">{{ order.id }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Тип заказа</div>
                        <div class="info-value">{{ order.order_type || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Создан</div>
                        <div class="info-value">{{ formatDateTime(order.created_at) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Обновлен</div>
                        <div class="info-value">{{ formatDateTime(order.updated_at) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Status Modal -->
        <Modal
            v-model="showChangeStatusModal"
            title="Изменить статус заказа"
            size="sm"
        >
            <div class="modal-content">
                <div class="form-group">
                    <label class="form-label">Новый статус</label>
                    <Select
                        v-model="newStatus"
                        :options="statusOptions"
                        placeholder="Выберите статус"
                    />
                </div>
            </div>
            <template #footer>
                <Button variant="outline" @click="showChangeStatusModal = false">
                    Отмена
                </Button>
                <Button
                    variant="primary"
                    :loading="changingStatus"
                    @click="changeStatus"
                >
                    Изменить
                </Button>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '../../composables/useApi'
import { useAuthStore } from '../../stores/auth'
import { useNotifications } from '../../composables/useNotifications'
import Button from '../../components/UI/Button.vue'
import Select from '../../components/UI/Select.vue'
import StatusBadge from '../../components/UI/StatusBadge.vue'
import Modal from '../../components/UI/Modal.vue'

const route = useRoute()
const api = useApi()
const authStore = useAuthStore()
const notifications = useNotifications()

const orderId = route.params.id
const order = ref(null)
const loading = ref(true)

const statusOptions = [
    { value: '0', label: 'Новый' },
    { value: '1', label: 'В обработке' },
    { value: '2', label: 'Готовится' },
    { value: '3', label: 'В доставке' },
    { value: '4', label: 'Выполнен' },
    { value: '5', label: 'Отменен' },
]

const loadOrder = async () => {
    loading.value = true
    try {
        const response = await api.get(`/orders/${orderId}`)
        order.value = response.data || response
    } catch (error) {
        notifications.error('Ошибка при загрузке заказа')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const getStatusVariant = (status) => {
    const variants = {
        0: 'info',
        1: 'warning',
        2: 'warning',
        3: 'primary',
        4: 'success',
        5: 'danger',
    }
    return variants[status] || 'default'
}

const getStatusLabel = (status) => {
    const labels = {
        0: 'Новый',
        1: 'В обработке',
        2: 'Готовится',
        3: 'В доставке',
        4: 'Выполнен',
        5: 'Отменен',
    }
    return labels[status] || 'Неизвестный'
}

// Change status
const showChangeStatusModal = ref(false)
const changingStatus = ref(false)
const newStatus = ref('')

const openChangeStatusModal = () => {
    newStatus.value = order.value.status
    showChangeStatusModal.value = true
}

const changeStatus = async () => {
    changingStatus.value = true
    try {
        await api.patch(`/orders/${orderId}/update-status`, {
            status: newStatus.value,
        })
        notifications.success('Статус заказа изменен')
        showChangeStatusModal.value = false
        loadOrder()
    } catch (error) {
        notifications.error('Ошибка при изменении статуса')
    } finally {
        changingStatus.value = false
    }
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
    loadOrder()
})
</script>

<style scoped>
.order-show-page {
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

.status-section {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.order-content {
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

/* Products */
.products-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.product-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: #f7fafc;
    border-radius: 8px;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 4px;
}

.product-details {
    display: flex;
    gap: 12px;
    font-size: 13px;
    color: #718096;
    margin-bottom: 4px;
}

.product-count {
    font-weight: 500;
}

.product-price {
    color: #48bb78;
}

.product-comment {
    font-size: 12px;
    color: #718096;
    font-style: italic;
}

.product-total {
    font-weight: 700;
    color: #48bb78;
    font-size: 16px;
}

/* Financial Summary */
.financial-summary {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.total {
    border-top: 2px solid #e2e8f0;
    border-bottom: none;
    padding-top: 16px;
    margin-top: 8px;
}

.summary-label {
    font-size: 14px;
    color: #718096;
}

.summary-value {
    font-size: 15px;
    font-weight: 600;
    color: #2d3748;
}

.summary-row.total .summary-label,
.summary-row.total .summary-value {
    font-size: 18px;
    color: #48bb78;
}

/* Dialog */
.dialog-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

/* Review */
.review-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.review-rating {
    display: flex;
    align-items: center;
    gap: 8px;
}

.star {
    font-size: 24px;
    color: #e2e8f0;
}

.star.filled {
    color: #fbbf24;
}

.rating-value {
    font-size: 18px;
    font-weight: 700;
    color: #1a202c;
}

.review-comment {
    font-size: 15px;
    color: #4a5568;
    line-height: 1.6;
}

/* Modal */
.modal-content {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
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

    .product-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>
