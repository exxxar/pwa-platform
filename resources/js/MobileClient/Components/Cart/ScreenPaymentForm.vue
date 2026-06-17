<template>
    <form class="payment-form" @submit.prevent="startCheckout">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="payment-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h2 class="hero-title">Оплата переводом</h2>
                <p class="hero-subtitle">Загрузите чек для подтверждения оплаты</p>
            </div>
        </div>

        <div class="payment-content">

            <!-- ========================================== -->
            <!-- ИНФОРМАЦИЯ ОБ ОПЛАТЕ -->
            <!-- ========================================== -->
            <div v-if="settings?.payment_info" class="info-banner">
                <div class="info-icon">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div class="info-text" v-text="settings.payment_info"></div>
            </div>

            <!-- ========================================== -->
            <!-- СВОДКА ЗАКАЗА -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <h6 class="section-title">Сводка заказа</h6>
                </div>

                <div class="summary-card">
                    <div class="summary-row">
                        <div class="row-label">
                            <i class="fa-solid fa-cubes"></i>
                            <span>Товаров</span>
                        </div>
                        <div class="row-value">{{ cartTotalCount }} шт.</div>
                    </div>

                    <div class="summary-row">
                        <div class="row-label">
                            <i class="fa-solid fa-tag"></i>
                            <span>Стоимость</span>
                        </div>
                        <div class="row-value">{{ formatPrice(cartTotalPrice) }}</div>
                    </div>

                    <div v-if="form.use_cashback" class="summary-row discount-row">
                        <div class="row-label">
                            <i class="fa-solid fa-coins"></i>
                            <span>Оплата бонусами</span>
                        </div>
                        <div class="row-value discount-value">−{{ formatPrice(cashbackLimit) }}</div>
                    </div>

                    <div v-if="form.discount > 0" class="summary-row discount-row">
                        <div class="row-label">
                            <i class="fa-solid fa-ticket"></i>
                            <span>Промокод</span>
                        </div>
                        <div class="row-value discount-value">−{{ formatPrice(form.discount) }}</div>
                    </div>

                    <div v-if="!form.need_pickup" class="summary-row">
                        <div class="row-label">
                            <i class="fa-solid fa-truck"></i>
                            <span>Доставка</span>
                        </div>
                        <div class="row-value">
                            {{ form.delivery_price > 0
                            ? formatPrice(form.delivery_price)
                            : 'рассчитает курьер' }}
                        </div>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row total-row">
                        <div class="row-label total-label">
                            <span>К оплате</span>
                        </div>
                        <div class="row-value total-value">{{ formatPrice(finallyPrice) }}</div>
                    </div>
                </div>

                <!-- Предупреждение о промокоде -->
                <div v-if="form.discount > 0" class="promo-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Скидка по промокоду не распространяется на доставку</span>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- СДАЧА (для наличных) -->
            <!-- ========================================== -->
            <div v-if="form.payment_type === 3 && settings?.can_use_cash" class="form-section">
                <div class="section-header">
                    <div class="section-icon change-icon">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <h6 class="section-title">Подготовить сдачу</h6>
                </div>

                <div class="change-variants">
                    <button
                        v-for="money in moneyVariants"
                        :key="money"
                        type="button"
                        class="variant-btn"
                        :class="{ 'active': form.money === money }"
                        @click="updateField('money', money)"
                    >
                        {{ money }}₽
                    </button>
                </div>

                <div class="custom-change">
                    <div class="input-wrapper">
                        <input
                            type="number"
                            min="0"
                            :value="form.money"
                            @input="updateField('money', $event.target.value ? Number($event.target.value) : null)"
                            placeholder="Или введите свою сумму..."
                            class="change-input"
                        >
                        <span class="input-suffix">₽</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ЗАГРУЗКА ФОТО ЧЕКА -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon photo-icon">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Фотография чека</h6>
                        <p class="section-subtitle">JPG, PNG или BMP, до 10 МБ</p>
                    </div>
                </div>

                <!-- Загрузка -->
                <label
                    v-if="!form.image"
                    class="photo-upload"
                    :class="{ 'has-error': photoError }"
                >
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/bmp"
                        @change="onChangePhotos"
                        class="photo-input"
                    >
                    <div class="upload-content">
                        <div class="upload-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="upload-text">
                            <strong>Нажмите для загрузки</strong>
                            <span>или перетащите файл сюда</span>
                        </div>
                        <div class="upload-hint">
                            Формат: JPG, PNG, BMP · Максимум 10 МБ
                        </div>
                    </div>
                </label>

                <!-- Превью -->
                <div v-else class="photo-preview">
                    <div class="preview-image-wrapper">
                        <img :src="photoPreviewUrl" alt="Чек">
                        <button
                            type="button"
                            class="remove-photo-btn"
                            @click="removePhoto"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                    <div class="preview-info">
                        <div class="preview-name">
                            <i class="fa-solid fa-image"></i>
                            <span>{{ form.image.name }}</span>
                        </div>
                        <div class="preview-size">
                            {{ formatFileSize(form.image.size) }}
                        </div>
                    </div>
                </div>

                <!-- Ошибка фото -->
                <div v-if="photoError" class="error-banner">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ photoError }}</span>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КОММЕНТАРИЙ К ОПЛАТЕ -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon comment-icon">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <h6 class="section-title">Комментарий к оплате</h6>
                </div>

                <div class="comment-field">
                    <textarea
                        :value="form.image_info"
                        @input="updateField('image_info', $event.target.value)"
                        placeholder="Например: номер заказа, имя получателя..."
                        class="comment-input"
                        rows="3"
                        maxlength="500"
                    ></textarea>
                    <div class="comment-counter">
                        {{ (form.image_info || '').length }} / 500
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ОШИБКА ФОРМЫ -->
            <!-- ========================================== -->
            <div v-if="formError" class="error-banner form-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ formError }}</span>
            </div>

            <!-- ========================================== -->
            <!-- КНОПКА ОФОРМЛЕНИЯ -->
            <!-- ========================================== -->
            <div class="form-actions">
                <button
                    type="button"
                    class="back-btn"
                    @click="$emit('change-tab', 0)"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Назад</span>
                </button>

                <button
                    type="submit"
                    class="submit-btn"
                    :class="{ 'waiting': spentTime > 0 }"
                    :disabled="!canSubmitForm"
                >
                    <template v-if="spentTime > 0">
                        <div class="waiting-spinner"></div>
                        <span>Осталось {{ spentTime }} сек.</span>
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Оформить заказ</span>
                    </template>
                </button>
            </div>

        </div>
    </form>
</template>

<script>
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

export default {
    name: "ScreenPaymentForm",

    props: {
        modelValue: {
            type: Object,
            required: true,
        },
    },

    emits: ['update:modelValue', 'start-checkout', 'change-tab'],

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            spentTime: 0,
            timerId: null,
            photoPreviewUrl: null,
            photoError: '',
            formError: '',
            moneyVariants: [500, 1000, 2000, 5000],
        };
    },

    computed: {
        form() {
            return this.modelValue || {};
        },

        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        self() {
            return window.TenantUser || null;
        },

        cartTotalCount() {
            return this.basketStore.cartTotalCount || 0;
        },

        cartTotalPrice() {
            return this.basketStore.cartTotalPrice || 0;
        },

        cashbackLimit() {
            if (!this.form?.use_cashback) return 0;

            const maxUserCashback = this.self?.cashBack?.amount || 0;
            const botCashbackPercent = this.settings?.max_cashback_use_percent || 0;
            const cashBackAmount = this.cartTotalPrice * (botCashbackPercent / 100);

            return Math.min(cashBackAmount, maxUserCashback);
        },

        finallyPrice() {
            let price = this.cartTotalPrice;

            // Вычитаем кэшбэк
            if (this.form?.use_cashback) {
                price -= this.cashbackLimit;
            }

            // Вычитаем промокод
            const activatePrice = this.form?.promo?.activate_price || 0;
            const discount = this.form?.promo?.discount || 0;
            if (price >= activatePrice) {
                price -= discount;
            }

            // Прибавляем доставку
            price += this.form?.delivery_price || 0;

            return Math.max(1, price);
        },

        canSubmitForm() {
            return (
                this.form?.image != null &&
                this.spentTime <= 0 &&
                !this.photoError
            );
        },
    },

    mounted() {
        // Восстанавливаем таймер из localStorage
        const saved = localStorage.getItem('cashman_payment_counter');
        if (saved && parseInt(saved) > 0) {
            this.startTimer(parseInt(saved));
        }
    },

    beforeUnmount() {
        // Очищаем Object URL
        if (this.photoPreviewUrl) {
            URL.revokeObjectURL(this.photoPreviewUrl);
        }
        // Очищаем таймер
        if (this.timerId) {
            clearInterval(this.timerId);
        }
    },

    methods: {
        // Универсальное обновление поля формы
        updateField(field, value) {
            this.$emit('update:modelValue', { ...this.form, [field]: value });
        },

        // Загрузка фото
        onChangePhotos(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.photoError = '';

            // Проверка типа
            const validTypes = ['image/jpeg', 'image/png', 'image/bmp'];
            if (!validTypes.includes(file.type)) {
                this.photoError = 'Недопустимый формат. Используйте JPG, PNG или BMP';
                return;
            }

            // Проверка размера (10 МБ)
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                this.photoError = 'Файл слишком большой. Максимум 10 МБ';
                return;
            }

            // Освобождаем предыдущий URL
            if (this.photoPreviewUrl) {
                URL.revokeObjectURL(this.photoPreviewUrl);
            }

            // Создаём превью
            this.photoPreviewUrl = URL.createObjectURL(file);
            this.updateField('image', file);
        },

        // Удаление фото
        removePhoto() {
            if (this.photoPreviewUrl) {
                URL.revokeObjectURL(this.photoPreviewUrl);
                this.photoPreviewUrl = null;
            }
            this.updateField('image', null);
            this.photoError = '';
        },

        // Форматирование размера файла
        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' Б';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
            return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
        },

        // Запуск таймера
        startTimer(seconds) {
            if (this.timerId) clearInterval(this.timerId);

            this.spentTime = Math.min(seconds, 10);

            this.timerId = setInterval(() => {
                if (this.spentTime > 0) {
                    this.spentTime--;
                    localStorage.setItem('cashman_payment_counter', this.spentTime.toString());
                } else {
                    clearInterval(this.timerId);
                    this.timerId = null;
                    localStorage.removeItem('cashman_payment_counter');
                }
            }, 1000);
        },

        // Оформление заказа
        startCheckout() {
            this.formError = '';

            if (this.spentTime > 0) return;

            if (!this.form?.image) {
                this.formError = 'Загрузите фотографию чека';
                return;
            }

            this.startTimer(10);
            this.$emit('start-checkout');
        },

        // Форматирование цены
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0);
        },
    },
};
</script>

<style scoped>
.payment-form {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO
   ========================================== */
.payment-hero {
    position: relative;
    padding: 32px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.hero-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0;
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.payment-content {
    padding: 20px 16px;
}

/* ==========================================
   ИНФОРМАЦИОННЫЙ БАННЕР
   ========================================== */
.info-banner {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.15);
    border-radius: 14px;
    margin-bottom: 20px;
}

.info-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.info-text {
    flex: 1;
    font-size: 0.85rem;
    color: var(--bs-body-color);
    line-height: 1.5;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.form-section {
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.section-icon.change-icon {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
}

.section-icon.photo-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
}

.section-icon.comment-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   СВОДКА
   ========================================== */
.summary-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
}

.summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

.summary-row:last-child {
    border-bottom: none;
}

.row-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
}

.row-label i {
    color: var(--bs-primary);
    width: 16px;
    text-align: center;
}

.row-value {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.discount-row .row-value {
    color: #198754;
}

.summary-divider {
    height: 1px;
    background: var(--bs-border-color);
    margin: 0 16px;
}

.total-row {
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.total-label {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.total-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--bs-primary);
}

/* Предупреждение промокода */
.promo-warning {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: rgba(255, 193, 7, 0.08);
    border: 1px solid rgba(255, 193, 7, 0.2);
    border-radius: 10px;
    margin-top: 10px;
    font-size: 0.8rem;
    color: #856404;
}

.promo-warning i {
    color: #ffc107;
    flex-shrink: 0;
}

/* ==========================================
   СДАЧА
   ========================================== */
.change-variants {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.variant-btn {
    padding: 12px 8px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.variant-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.variant-btn.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.custom-change {
    margin-top: 8px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.change-input {
    width: 100%;
    padding: 14px 40px 14px 16px;
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-size: 0.95rem;
    font-weight: 500;
    outline: none;
    transition: all 0.2s ease;
}

.change-input:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.input-suffix {
    position: absolute;
    right: 16px;
    color: var(--bs-secondary-color);
    font-weight: 700;
}

/* ==========================================
   ЗАГРУЗКА ФОТО
   ========================================== */
.photo-upload {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 20px;
    background: var(--bs-body-bg);
    border: 2px dashed var(--bs-border-color);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.photo-upload:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.photo-upload.has-error {
    border-color: #dc3545;
    background: rgba(220, 53, 69, 0.03);
}

.photo-input {
    display: none;
}

.upload-content {
    text-align: center;
}

.upload-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;
}

.upload-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 8px;
}

.upload-text strong {
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.upload-text span {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.upload-hint {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    opacity: 0.7;
}

/* Превью фото */
.photo-preview {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
}

.preview-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 16/10;
    background: var(--bs-secondary-bg);
    overflow: hidden;
}

.preview-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-photo-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    backdrop-filter: blur(10px);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.remove-photo-btn:hover {
    background: #dc3545;
    transform: scale(1.1);
}

.preview-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
}

.preview-name {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.preview-name i {
    color: var(--bs-primary);
}

.preview-size {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   КОММЕНТАРИЙ
   ========================================== */
.comment-field {
    position: relative;
}

.comment-input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-size: 0.95rem;
    font-family: inherit;
    outline: none;
    resize: none;
    transition: all 0.2s ease;
}

.comment-input:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.comment-input::placeholder {
    color: var(--bs-secondary-color);
}

.comment-counter {
    position: absolute;
    bottom: 10px;
    right: 14px;
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ОШИБКИ
   ========================================== */
.error-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: rgba(220, 53, 69, 0.08);
    border: 1px solid rgba(220, 53, 69, 0.2);
    border-radius: 12px;
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 12px;
}

.error-banner i {
    font-size: 1rem;
    flex-shrink: 0;
}

.form-error {
    margin-bottom: 16px;
}

/* ==========================================
   КНОПКИ ДЕЙСТВИЙ
   ========================================== */
.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 24px;
}

.back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    background: transparent;
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.back-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.submit-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.submit-btn.waiting {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3);
}

.waiting-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.4rem;
    }

    .change-variants {
        grid-template-columns: repeat(2, 1fr);
    }

    .form-actions {
        flex-direction: column;
    }

    .back-btn,
    .submit-btn {
        width: 100%;
    }

    .total-value {
        font-size: 1.1rem;
    }
}
</style>
