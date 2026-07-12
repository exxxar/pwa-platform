<template>
    <div class="review-form-modal" v-if="isVisible" @click.self="close">
        <div class="review-form-container">
            <div class="review-form-header">
                <h3>{{ isEditing ? 'Редактировать отзыв' : 'Оставить отзыв' }}</h3>
                <button class="close-btn" @click="close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="review-form">
                <!-- Рейтинг через эмодзи -->
                <div class="form-group">
                    <label class="form-label">Как вам заказ?</label>
                    <div class="emoji-rating">
                        <div
                            v-for="emoji in emojiOptions"
                            :key="emoji.value"
                            class="emoji-option"
                            :class="{
                                selected: form.rating === emoji.value,
                                [emoji.class]: true,
                            }"
                            @click="form.rating = emoji.value"
                        >
                            <div class="emoji-icon">{{ emoji.icon }}</div>
                            <div class="emoji-label">{{ emoji.label }}</div>
                        </div>
                    </div>
                    <div v-if="form.rating" class="rating-feedback">
                        {{ getRatingFeedback(form.rating) }}
                    </div>
                </div>

                <!-- Заголовок -->
                <div class="form-group">
                    <label class="form-label">Заголовок (необязательно)</label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="form-input"
                        placeholder="Кратко опишите впечатление"
                        maxlength="255"
                    />
                </div>

                <!-- Текст отзыва -->
                <div class="form-group">
                    <label class="form-label">Ваш отзыв</label>
                    <textarea
                        v-model="form.text"
                        class="form-textarea"
                        placeholder="Расскажите о вашем опыте..."
                        rows="5"
                        maxlength="1000"
                        required
                    ></textarea>
                    <div class="char-count">{{ form.text.length }}/1000</div>
                </div>

                <!-- Ошибки -->
                <div v-if="error" class="form-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ error }}
                </div>

                <!-- Кнопки -->
                <div class="form-actions">
                    <button type="button" class="btn-cancel" @click="close">
                        Отмена
                    </button>
                    <button
                        type="submit"
                        class="btn-submit"
                        :disabled="isSubmitting"
                    >
                        <i v-if="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                        {{ isEditing ? 'Сохранить' : 'Отправить' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { useOrders } from '@/MobileClient/composables/useOrders.js';

export default {
    name: 'ReviewForm',

    props: {
        isVisible: { type: Boolean, default: false },
        orderId: { type: Number, default: null },
        review: { type: Object, default: null },
    },

    emits: ['close', 'success'],

    setup() {
        const { storeReview, updateReview } = useOrders();
        return { storeReview, updateReview };
    },

    data() {
        return {
            form: {
                rating: null,
                title: '',
                text: '',
            },
            isSubmitting: false,
            error: null,

            // Эмодзи рейтинг
            emojiOptions: [
                { value: 1, icon: '😡', label: 'Ужасно', class: 'rating-1' },
                { value: 2, icon: '😞', label: 'Плохо', class: 'rating-2' },
                { value: 3, icon: '😐', label: 'Нормально', class: 'rating-3' },
                { value: 4, icon: '😊', label: 'Хорошо', class: 'rating-4' },
                { value: 5, icon: '🤩', label: 'Отлично', class: 'rating-5' },
            ],
        };
    },

    computed: {
        isEditing() {
            return !!this.review;
        },
    },

    watch: {
        review: {
            immediate: true,
            handler(newReview) {
                if (newReview) {
                    this.form.rating = newReview.rating || null;
                    this.form.title = newReview.title || '';
                    this.form.text = newReview.text || '';
                } else {
                    this.resetForm();
                }
            },
        },
    },

    methods: {
        resetForm() {
            this.form = {
                rating: null,
                title: '',
                text: '',
            };
            this.error = null;
        },

        close() {
            this.resetForm();
            this.$emit('close');
        },

        getRatingFeedback(rating) {
            const feedbacks = {
                1: 'Нам очень жаль, что заказ вас расстроил 😔',
                2: 'Понимаем, что могло быть лучше 🙁',
                3: 'Спасибо за честную оценку 👍',
                4: 'Рады, что вам понравилось! 😊',
                5: 'Замечательно! Спасибо за отличный отзыв! 🎉',
            };
            return feedbacks[rating] || '';
        },

        async submitForm() {
            if (!this.form.rating) {
                this.error = 'Пожалуйста, выберите оценку';
                return;
            }

            if (!this.form.text.trim()) {
                this.error = 'Пожалуйста, напишите отзыв';
                return;
            }

            this.isSubmitting = true;
            this.error = null;

            try {
                const payload = {
                    ...this.form,
                    order_id: this.orderId,
                };

                let result;
                if (this.isEditing) {
                    result = await this.updateReview(this.review.id, payload);
                } else {
                    result = await this.storeReview(payload);
                }

                this.$notify?.({
                    title: 'Отзыв',
                    text: this.isEditing ? 'Отзыв обновлен' : 'Отзыв отправлен',
                    type: 'success',
                });

                this.$emit('success', result);
                this.close();

            } catch (err) {
                console.error('Ошибка сохранения отзыва:', err);
                this.error = err.response?.data?.message || 'Ошибка сохранения отзыва';
            } finally {
                this.isSubmitting = false;
            }
        },
    },
};
</script>

<style scoped>
.review-form-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 16px;
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.review-form-container {
    background: var(--bs-body-bg);
    border-radius: 20px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease-out;
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

.review-form-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--bs-border-color);
}

.review-form-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--bs-body-color);
}

.close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: var(--bs-danger);
    color: white;
}

.review-form {
    padding: 24px;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 12px;
}

/* ==========================================
   ЭМОДЗИ РЕЙТИНГ
   ========================================== */
.emoji-rating {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 16px;
}

.emoji-option {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 8px;
    background: var(--bs-secondary-bg);
    border: 2px solid transparent;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.emoji-option:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.emoji-option.selected {
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
}

/* Цвета для разных рейтингов */
.emoji-option.rating-1.selected {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-color: #ef4444;
}

.emoji-option.rating-2.selected {
    background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
    border-color: #f97316;
}

.emoji-option.rating-3.selected {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-color: #eab308;
}

.emoji-option.rating-4.selected {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-color: #10b981;
}

.emoji-option.rating-5.selected {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-color: #3b82f6;
}

.emoji-icon {
    font-size: 2.5rem;
    line-height: 1;
    transition: transform 0.3s ease;
}

.emoji-option:hover .emoji-icon {
    transform: scale(1.2);
}

.emoji-option.selected .emoji-icon {
    transform: scale(1.3);
    animation: bounce 0.5s ease;
}

@keyframes bounce {
    0%, 100% { transform: scale(1.3); }
    50% { transform: scale(1.5); }
}

.emoji-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-align: center;
    transition: color 0.3s ease;
}

.emoji-option.selected .emoji-label {
    color: var(--bs-body-color);
}

.rating-feedback {
    padding: 12px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-size: 0.9rem;
    text-align: center;
    animation: fadeIn 0.3s ease;
}

/* ==========================================
   ФОРМЫ
   ========================================== */
.form-input,
.form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-size: 0.95rem;
    transition: all 0.2s ease;
    font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 120px;
}

.char-count {
    text-align: right;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    margin-top: 4px;
}

.form-error {
    padding: 12px 16px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 12px;
    color: #dc3545;
    font-size: 0.9rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-actions {
    display: flex;
    gap: 12px;
}

.btn-cancel,
.btn-submit {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-cancel {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
}

.btn-cancel:hover {
    background: var(--bs-border-color);
}

.btn-submit {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.25);
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(var(--bs-primary-rgb), 0.35);
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .emoji-option {
        padding: 12px 4px;
    }

    .emoji-icon {
        font-size: 2rem;
    }

    .emoji-label {
        font-size: 0.7rem;
    }
}
</style>
