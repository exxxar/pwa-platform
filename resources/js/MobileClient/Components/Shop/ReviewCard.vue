<template>
    <div v-if="review" class="review-card-wrapper">

        <!-- ========================================== -->
        <!-- КАРТОЧКА ТОВАРА (если нужна) -->
        <!-- ========================================== -->
        <div v-if="needProduct && review.product" class="product-preview">
            <ProductCardSimple :item="review.product" />
        </div>

        <!-- ========================================== -->
        <!-- СОСТОЯНИЕ 1: ОТЗЫВ УЖЕ ОСТАВЛЕН -->
        <!-- ========================================== -->
        <div v-if="review.send_review_at" class="review-card filled">

            <!-- Заголовок с рейтингом -->
            <div class="review-header">
                <div class="rating-display">
                    <div class="stars">
                        <i
                            v-for="star in 5"
                            :key="star"
                            class="fa-solid fa-star"
                            :class="{ 'filled': star <= review.rating }"
                        ></i>
                    </div>
                    <span class="rating-value">{{ review.rating }}.0</span>
                </div>
                <div class="review-date">
                    <i class="fa-regular fa-calendar"></i>
                    <span>{{ formatDate(review.send_review_at) }}</span>
                </div>
            </div>

            <!-- Текст отзыва -->
            <div class="review-body">
                <div class="review-text">
                    <i class="fa-solid fa-quote-left quote-icon"></i>
                    <p>{{ review.text || 'Без текста' }}</p>
                </div>
            </div>

            <!-- Действия администратора -->
            <div v-if="isAdmin" class="review-admin-actions">
                <!-- Уведомление пользователя -->
                <label class="notify-toggle">
                    <input
                        type="checkbox"
                        v-model="need_user_notify"
                        class="notify-checkbox"
                    >
                    <div class="notify-track" :class="{ 'active': need_user_notify }">
                        <div class="notify-thumb"></div>
                    </div>
                    <span class="notify-label">
                        {{ need_user_notify ? 'Уведомить пользователя' : 'Не уведомлять' }}
                    </span>
                </label>

                <!-- Кнопка удаления -->
                <button
                    type="button"
                    class="remove-btn"
                    @click="removeReview"
                    :disabled="isRemoving"
                >
                    <span v-if="isRemoving" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fa-solid fa-trash-can me-2"></i>
                    {{ isRemoving ? 'Удаление...' : 'Сбросить отзыв' }}
                </button>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- СОСТОЯНИЕ 2: ОТЗЫВ ЕЩЁ НЕ ОСТАВЛЕН -->
        <!-- ========================================== -->
        <div v-else class="review-card empty">

            <!-- Для пользователя: приглашение оставить отзыв -->
            <template v-if="!isAdmin">
                <div class="empty-content">
                    <div class="empty-icon">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <div class="empty-info">
                        <h6 class="empty-title">Поделитесь впечатлениями</h6>
                        <p class="empty-text">
                            Ваш отзыв поможет нам стать лучше и поможет другим пользователям сделать выбор
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="leave-review-btn"
                    @click="showReviewsForm"
                >
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Оставить отзыв</span>
                </button>
            </template>

            <!-- Для админа: управление -->
            <template v-else>
                <div class="admin-empty-content">
                    <div class="admin-icon">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <div class="admin-info">
                        <h6 class="admin-title">Ожидание отзыва</h6>
                        <p class="admin-text">Пользователь ещё не оставил отзыв</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="remind-btn"
                    :class="{ 'waiting': spent_time_counter > 0 }"
                    :disabled="spent_time_counter > 0"
                    @click="notifyUser"
                >
                    <template v-if="spent_time_counter > 0">
                        <div class="waiting-spinner"></div>
                        <span>Подождите {{ spent_time_counter }} сек.</span>
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-bell"></i>
                        <span>Напомнить пользователю</span>
                    </template>
                </button>
            </template>

        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА ФОРМЫ ОТЗЫВА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div
                v-if="isModalOpen"
                class="modal-overlay"
                @click.self="closeModal"
            >
                <div class="modal-container">

                    <!-- Шапка модалки -->
                    <div class="modal-header">
                        <div class="modal-icon">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <div class="modal-title-wrapper">
                            <h5 class="modal-title">
                                {{ review.product_id ? 'Отзыв о товаре' : 'Отзыв о заказе' }}
                            </h5>
                            <span class="modal-subtitle">
                                Заказ #{{ review.order_id || '—' }}
                            </span>
                        </div>
                        <button class="modal-close" @click="closeModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Тело модалки -->
                    <div class="modal-body">
                        <ReviewForm
                            :review="review"
                            @callback="callback"
                            @close="closeModal"
                        />
                    </div>

                </div>
            </div>
        </transition>

    </div>
</template>

<script>
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';
import ReviewForm from '@/MobileClient/Components/Shop/ReviewForm.vue';
import ProductCardSimple from '@/MobileClient/Components/Shop/ProductCardSimple.vue';

export default {
    name: "ReviewCard",

    components: {
        ReviewForm,
        ProductCardSimple
    },

    props: {
        modelValue: {
            type: Object,
            required: true
        },
        needProduct: {
            type: Boolean,
            default: false
        },
        isAdmin: {
            type: Boolean,
            default: false
        },
    },

    emits: ['update:modelValue'],

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            need_user_notify: false,
            spent_time_counter: 0,
            timerId: null,
            isRemoving: false,
            isModalOpen: false,
        };
    },

    computed: {
        review: {
            get() { return this.modelValue; },
            set(value) { this.$emit('update:modelValue', value); },
        },
    },

    mounted() {
        // Восстанавливаем таймер из localStorage
        const savedAt = localStorage.getItem('mypwa_review_notify_saved_at');
        if (savedAt) {
            const elapsed = Math.floor((Date.now() - Number(savedAt)) / 1000);
            const remaining = 10 - elapsed;
            if (remaining > 0) {
                this.startTimer(remaining);
            } else {
                localStorage.removeItem('mypwa_review_notify_saved_at');
            }
        }
    },

    beforeUnmount() {
        if (this.timerId) clearInterval(this.timerId);
        document.body.style.overflow = '';
    },

    methods: {
        // Форматирование даты
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        // Открытие модалки
        showReviewsForm() {
            this.isModalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        // Закрытие модалки
        closeModal() {
            this.isModalOpen = false;
            document.body.style.overflow = '';
        },

        // Удаление отзыва (админ)
        async removeReview() {
            this.isRemoving = true;

            const updated = {
                ...this.modelValue,
                send_review_at: null,
                text: null,
                rating: 5,
                need_user_notify: this.need_user_notify || false,
            };

            try {
                await this.basketStore.storeReview({ reviewForm: updated });
                this.review = updated;

                this.$notify?.({
                    title: 'Отзывы',
                    text: 'Отзыв успешно удалён',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка удаления отзыва:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить отзыв',
                    type: 'error',
                });
            } finally {
                this.isRemoving = false;
            }
        },

        // Callback после отправки отзыва
        callback(item) {
            this.review = item;
            this.closeModal();
        },

        // Напоминание пользователю (админ)
        async notifyUser() {
            try {
                await this.basketStore.notifyUserForReview({
                    reviewForm: this.modelValue
                });

                this.$notify?.({
                    title: 'Отзывы',
                    text: 'Напоминание отправлено',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка напоминания:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить напоминание',
                    type: 'error',
                });
            }

            this.startTimer(10);
        },

        // Запуск таймера
        startTimer(time = 10) {
            if (this.timerId) clearInterval(this.timerId);

            this.spent_time_counter = Math.min(time, 10);
            localStorage.setItem('mypwa_review_notify_saved_at', Date.now());

            this.timerId = setInterval(() => {
                if (this.spent_time_counter > 0) {
                    this.spent_time_counter--;
                } else {
                    clearInterval(this.timerId);
                    this.timerId = null;
                    this.spent_time_counter = 0;
                    localStorage.removeItem('mypwa_review_notify_saved_at');
                }
            }, 1000);
        },
    },
};
</script>

<style scoped>
.review-card-wrapper {
    width: 100%;
    margin-bottom: 12px;
}

/* Карточка товара */
.product-preview {
    margin-bottom: 12px;
}

/* ==========================================
   КАРТОЧКА ОТЗЫВА (ОБЩАЯ)
   ========================================== */
.review-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.review-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

/* ==========================================
   СОСТОЯНИЕ: ОТЗЫВ ЕСТЬ
   ========================================== */
.review-card.filled {
    border-left: 4px solid var(--bs-primary);
}

/* Заголовок с рейтингом */
.review-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    border-bottom: 1px solid var(--bs-border-color);
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stars {
    display: flex;
    gap: 3px;
}

.stars i {
    font-size: 1rem;
    color: var(--bs-border-color);
    transition: all 0.3s ease;
}

.stars i.filled {
    color: #ffc107;
    text-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
}

.rating-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--bs-primary);
}

.review-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.review-date i {
    color: var(--bs-primary);
}

/* Тело отзыва */
.review-body {
    padding: 20px;
}

.review-text {
    position: relative;
    padding-left: 24px;
}

.quote-icon {
    position: absolute;
    left: 0;
    top: 0;
    color: var(--bs-primary);
    opacity: 0.3;
    font-size: 1.2rem;
}

.review-text p {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--bs-body-color);
    word-break: break-word;
}

/* Действия администратора */
.review-admin-actions {
    padding: 16px 20px;
    background: var(--bs-secondary-bg, #f8f9fa);
    border-top: 1px solid var(--bs-border-color);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Toggle уведомления */
.notify-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.notify-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.notify-track {
    width: 44px;
    height: 24px;
    border-radius: 12px;
    background: var(--bs-border-color);
    position: relative;
    transition: background 0.3s ease;
    flex-shrink: 0;
}

.notify-track.active {
    background: var(--bs-primary);
}

.notify-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.notify-track.active .notify-thumb {
    transform: translateX(20px);
}

.notify-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

/* Кнопка удаления */
.remove-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 20px;
    background: transparent;
    border: 2px solid #dc3545;
    border-radius: 12px;
    color: #dc3545;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.remove-btn:hover:not(:disabled) {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.remove-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ==========================================
   СОСТОЯНИЕ: ОТЗЫВА НЕТ
   ========================================== */
.review-card.empty {
    border: 2px dashed var(--bs-border-color);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.02) 0%, transparent 100%);
}

.review-card.empty:hover {
    border-color: var(--bs-primary);
}

/* Для пользователя */
.empty-content {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px 20px;
}

.empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.empty-info {
    flex: 1;
}

.empty-title {
    margin: 0 0 4px 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.empty-text {
    margin: 0;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    line-height: 1.4;
}

.leave-review-btn {
    width: calc(100% - 40px);
    margin: 0 20px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 20px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.leave-review-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

/* Для админа */
.admin-empty-content {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px 20px;
}

.admin-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(255, 193, 7, 0.15);
    color: #b8860b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.admin-info {
    flex: 1;
}

.admin-title {
    margin: 0 0 4px 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.admin-text {
    margin: 0;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

.remind-btn {
    width: calc(100% - 40px);
    margin: 0 20px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 20px;
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    border: none;
    border-radius: 12px;
    color: #1a1a1a;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3);
}

.remind-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255, 193, 7, 0.4);
}

.remind-btn:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.remind-btn.waiting {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
    box-shadow: none;
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
   МОДАЛКА ФОРМЫ ОТЗЫВА
   ========================================== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: var(--bs-body-bg);
    border-radius: 20px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.modal-title-wrapper {
    flex: 1;
}

.modal-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

.modal-subtitle {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.modal-close {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #dc3545;
    color: white;
    transform: rotate(90deg);
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .review-header {
        padding: 14px 16px;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .review-body {
        padding: 16px;
    }

    .empty-content,
    .admin-empty-content {
        padding: 20px 16px;
        flex-direction: column;
        text-align: center;
    }

    .leave-review-btn,
    .remind-btn {
        width: calc(100% - 32px);
        margin: 0 16px 16px;
    }

    .modal-container {
        max-height: 95vh;
        border-radius: 16px 16px 0 0;
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        animation: modalSlideUpMobile 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes modalSlideUpMobile {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
}
</style>
