<template>
    <transition name="modal-fade">
        <div v-if="isVisible" class="modal-overlay" @click.self="close">
            <div class="modal-container">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-money-bill-transfer text-primary me-2"></i> Оплата доставки</h3>
                    <button class="modal-close" @click="close"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <!-- Инфо о заказе -->
                    <div class="order-info-badge">
                        <span>Заказ #{{ order.id }}</span>
                        <span class="divider">|</span>
                        <span>{{ order.receiver_name || 'Клиент' }}</span>
                    </div>

                    <!-- 🆕 ЭКРАН 1: Форма ввода -->
                    <form v-if="!paymentUrl" @submit.prevent="submit">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-ruble-sign"></i> Сумма к оплате <span class="required">*</span>
                            </label>
                            <input
                                type="number"
                                v-model.number="form.amount"
                                class="form-input"
                                placeholder="0"
                                min="1"
                                step="1"
                                required
                                :disabled="sending"
                            >
                            <small class="form-hint">Базовая стоимость: {{ formatPrice(order.delivery_price) }} ₽</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-file-lines"></i> Описание
                            </label>
                            <textarea
                                v-model="form.description"
                                class="form-textarea"
                                placeholder="Например: Оплата доставки заказа #{{ order.id }}"
                                maxlength="100"
                                rows="2"
                                :disabled="sending"
                            ></textarea>
                        </div>

                        <button type="submit" class="btn-primary-modern" :disabled="sending || !isValid">
                            <span v-if="sending" class="spinner-small"></span>
                            <template v-else>
                                <i class="fa-solid fa-link"></i>
                                Сгенерировать ссылку
                            </template>
                        </button>
                    </form>

                    <!-- 🆕 ЭКРАН 2: Успех, QR-код и копирование -->
                    <div v-else class="payment-success">
                        <div class="success-header">
                            <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
                            <h4>Ссылка готова!</h4>
                            <p>Покажите QR-код клиенту или скопируйте ссылку</p>
                        </div>

                        <div class="qr-code-wrapper">
                            <img :src="qrCodeUrl" alt="QR Code для оплаты" class="qr-image">
                        </div>

                        <div class="link-wrapper">
                            <input type="text" :value="paymentUrl" readonly class="link-input" title="Ссылка на оплату">
                            <button class="copy-btn" @click="copyLink" :class="{ 'copied': isCopied }">
                                <i :class="isCopied ? 'fa-solid fa-check' : 'fa-regular fa-copy'"></i>
                            </button>
                        </div>

                        <button class="btn-secondary-modern w-100 mt-3" @click="resetModal">
                            <i class="fa-solid fa-xmark me-1"></i> Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import axios from 'axios';

export default {
    name: 'DeliveryPaymentModal',

    props: {
        isVisible: { type: Boolean, default: false },
        order: { type: Object, required: true }
    },

    emits: ['close', 'success'],

    data() {
        return {
            sending: false,
            paymentUrl: null,
            isCopied: false,
            form: {
                amount: 0,
                description: '',
            }
        }
    },

    watch: {
        isVisible(newVal) {
            if (newVal && this.order) {
                this.form.amount = this.order.delivery_price || 0;
                this.form.description = `Оплата доставки заказа #${this.order.id}`;
            }
        }
    },

    computed: {
        isValid() {
            return this.form.amount > 0 && this.form.description.trim().length > 0;
        },
        // 🆕 Генерируем URL для QR-кода через бесплатный API
        qrCodeUrl() {
            if (!this.paymentUrl) return '';
            return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(this.paymentUrl)}`;
        }
    },

    methods: {
        close() {
            this.$emit('close');
        },

        resetModal() {
            this.paymentUrl = null;
            this.isCopied = false;
            this.form.amount = this.order.delivery_price || 0;
            this.form.description = `Оплата доставки заказа #${this.order.id}`;
            this.$emit('close');
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price || 0);
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.paymentUrl);
                this.isCopied = true;
                setTimeout(() => { this.isCopied = false; }, 2000);
            } catch (err) {
                console.error('Не удалось скопировать ссылку', err);
            }
        },

        async submit() {
            if (!this.isValid) return;
            this.sending = true;

            try {
                const payload = {
                    amount: this.form.amount,
                    description: this.form.description,
                };

                const response = await axios.post(`/deliveryman/orders/${this.order.id}/request-payment`, payload);

                if (response.data.success) {
                    this.paymentUrl = response.data.data.url;
                    this.$notify?.({
                        title: 'Успех',
                        text: 'Ссылка на оплату сформирована',
                        type: 'success',
                    });
                }
            } catch (err) {
                console.error('Ошибка отправки ссылки:', err);
                this.$notify?.({
                    title: 'Ошибка',
                    text: err.response?.data?.error || err.response?.data?.message || 'Не удалось сформировать ссылку',
                    type: 'error',
                });
            } finally {
                this.sending = false;
            }
        }
    }
}
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;
$danger: #ef4444;
$success: #10b981;

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(31, 41, 55, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: $card-bg;
    border-radius: 20px;
    width: 100%;
    max-width: 450px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid $border;
    h3 { font-size: 1.1rem; font-weight: 700; margin: 0; color: $text; display: flex; align-items: center; }
}

.modal-close {
    width: 36px; height: 36px; border-radius: 50%; background: $bg; border: none;
    color: $text-muted; cursor: pointer; display: flex; align-items: center; justify-content: center;
    &:hover { background: $danger; color: white; transform: rotate(90deg); }
}

.modal-body { padding: 24px; }

.order-info-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: rgba($primary, 0.08);
    color: $primary;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 20px;
    .divider { color: $text-muted; font-weight: 400; }
}

.form-group { margin-bottom: 16px; }
.form-label {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.85rem; font-weight: 600; color: $text; margin-bottom: 8px;
    i { color: $primary; }
    .required { color: $danger; }
}

.form-input, .form-textarea {
    width: 100%; padding: 12px 16px; border: 1px solid $border; border-radius: 10px;
    font-size: 0.95rem; color: $text; background: $card-bg; transition: all 0.2s;
    &:focus { outline: none; border-color: $primary; box-shadow: 0 0 0 3px rgba($primary, 0.1); }
    &:disabled { background: $bg; cursor: not-allowed; opacity: 0.6; }
}
.form-textarea { resize: vertical; min-height: 80px; font-family: inherit; }
.form-hint { display: block; margin-top: 6px; font-size: 0.75rem; color: $text-muted; }

.btn-primary-modern, .btn-secondary-modern {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 14px 20px; border-radius: 10px; font-size: 0.95rem; font-weight: 600;
    border: none; cursor: pointer; transition: all 0.2s; min-height: 48px;
    &:active:not(:disabled) { transform: scale(0.98); filter: brightness(0.9); }
    &:disabled { opacity: 0.5; cursor: not-allowed; }
}
.btn-primary-modern { background: $primary; color: white; &:hover:not(:disabled) { background: #2563eb; } }
.btn-secondary-modern { background: $bg; color: $text; border: 1px solid $border; &:hover:not(:disabled) { background: $border; } }

.spinner-small {
    width: 16px; height: 16px; border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

// ==========================================
// 🆕 СТИЛИ ДЛЯ ЭКРАНА УСПЕХА
// ==========================================
.payment-success {
    text-align: center;
    animation: fadeIn 0.3s ease;
}

.success-header {
    margin-bottom: 20px;
    .success-icon {
        font-size: 3rem;
        color: $success;
        margin-bottom: 12px;
        animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    h4 { margin: 0 0 4px; font-size: 1.1rem; color: $text; }
    p { margin: 0; font-size: 0.85rem; color: $text-muted; }
}

@keyframes popIn {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.qr-code-wrapper {
    background: white;
    padding: 16px;
    border-radius: 16px;
    border: 1px solid $border;
    display: inline-block;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);

    .qr-image {
        width: 200px;
        height: 200px;
        display: block;
    }
}

.link-wrapper {
    display: flex;
    gap: 8px;
    background: $bg;
    padding: 8px;
    border-radius: 10px;
    border: 1px solid $border;

    .link-input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.8rem;
        color: $text-muted;
        outline: none;
        min-width: 0; // Позволяет тексту сокращаться с многоточием
    }

    .copy-btn {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: none;
        background: $primary;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;

        &:hover { background: #2563eb; }
        &.copied { background: $success; }
    }
}
</style>
