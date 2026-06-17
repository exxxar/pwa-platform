<template>
    <form class="review-form" @submit.prevent="submitReview">

        <!-- Слот для заголовка (если передан извне) -->
        <slot name="title"></slot>

        <!-- ========================================== -->
        <!-- ВЫБОР РЕЙТИНГА -->
        <!-- ========================================== -->
        <div class="form-section">
            <div class="section-label">
                <i class="fa-solid fa-star"></i>
                <span>Как вы оцениваете?</span>
            </div>

            <div class="rating-grid">
                <button
                    v-for="option in ratingOptions"
                    :key="option.value"
                    type="button"
                    class="rating-card"
                    :class="{
                        'active': form.rating == option.value,
                        ['rating-' + option.value]: true
                    }"
                    @click="form.rating = option.value"
                >
                    <div class="rating-emoji">{{ option.emoji }}</div>
                    <div class="rating-label">{{ option.label }}</div>
                    <div class="rating-check">
                        <i class="fa-solid fa-check"></i>
                    </div>
                </button>
            </div>

            <!-- Визуальный индикатор выбранного рейтинга -->
            <div class="rating-indicator" :class="'level-' + form.rating">
                <div class="indicator-stars">
                    <i
                        v-for="star in 5"
                        :key="star"
                        class="fa-solid fa-star"
                        :class="{ 'filled': star <= form.rating }"
                    ></i>
                </div>
                <span class="indicator-text">{{ currentRatingText }}</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ТЕКСТ ОТЗЫВА -->
        <!-- ========================================== -->
        <div class="form-section">
            <div class="section-label">
                <i class="fa-solid fa-pen"></i>
                <span>Ваш отзыв</span>
                <span class="required">*</span>
            </div>

            <div class="textarea-wrapper">
                <textarea
                    ref="reviewTextarea"
                    v-model="form.text"
                    class="review-textarea"
                    :class="{ 'has-error': textError }"
                    placeholder="Расскажите о вашем опыте... Что понравилось? Что можно улучшить?"
                    rows="5"
                    maxlength="1000"
                    required
                    @input="autoResize"
                ></textarea>
                <div class="textarea-footer">
                    <div v-if="textError" class="textarea-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ textError }}</span>
                    </div>
                    <div class="textarea-counter" :class="{ 'warning': form.text.length > 900 }">
                        {{ form.text.length }} / 1000
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ОШИБКА ОТПРАВКИ -->
        <!-- ========================================== -->
        <div v-if="submitError" class="error-banner">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ submitError }}</span>
        </div>

        <!-- ========================================== -->
        <!-- КНОПКИ ДЕЙСТВИЙ -->
        <!-- ========================================== -->
        <div class="form-actions">
            <button
                type="submit"
                class="submit-btn"
                :disabled="!canSubmit || isSubmitting"
            >
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fa-solid fa-paper-plane me-2"></i>
                {{ isSubmitting ? 'Отправка...' : 'Оставить отзыв' }}
            </button>

            <button
                type="button"
                class="cancel-btn"
                @click="$emit('close')"
                :disabled="isSubmitting"
            >
                Отмена
            </button>
        </div>

    </form>
</template>

<script>
export default {
    name: "ReviewForm",

    props: {
        review: {
            type: Object,
            default: () => ({}),
        },
    },

    emits: ['callback', 'close'],

    data() {
        return {
            isSubmitting: false,
            textError: '',
            submitError: '',
            form: {
                id: null,
                text: '',
                rating: 5,
                image: null,
                order_id: null,
                product_id: null,
            },
            ratingOptions: [
                { value: 5, emoji: '😍', label: 'Отлично' },
                { value: 4, emoji: '😊', label: 'Хорошо' },
                { value: 3, emoji: '😐', label: 'Нормально' },
                { value: 2, emoji: '😕', label: 'Плохо' },
                { value: 1, emoji: '😞', label: 'Ужасно' },
            ],
        };
    },

    computed: {
        currentRatingText() {
            const option = this.ratingOptions.find(o => o.value == this.form.rating);
            return option ? option.label : '';
        },

        canSubmit() {
            return (
                this.form.rating > 0 &&
                this.form.text.trim().length >= 10 &&
                !this.textError
            );
        },
    },

    mounted() {
        this.form.id = this.review?.id || null;
        this.form.order_id = this.review?.order_id || null;
        this.form.product_id = this.review?.product_id || null;

        // Автофокус на textarea
        this.$nextTick(() => {
            if (this.$refs.reviewTextarea) {
                this.$refs.reviewTextarea.focus();
            }
        });
    },

    methods: {
        // Автоматическое изменение высоты textarea
        autoResize() {
            const textarea = this.$refs.reviewTextarea;
            if (!textarea) return;

            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 300) + 'px';

            // Валидация в реальном времени
            if (this.form.text.trim().length > 0 && this.form.text.trim().length < 10) {
                this.textError = 'Минимум 10 символов';
            } else {
                this.textError = '';
            }
        },

        async submitReview() {
            // Валидация
            if (!this.canSubmit) {
                if (this.form.text.trim().length < 10) {
                    this.textError = 'Минимум 10 символов';
                }
                return;
            }

            this.isSubmitting = true;
            this.submitError = '';

            const data = new FormData();

            Object.keys(this.form).forEach(key => {
                const item = this.form[key];
                if (item === null || item === undefined || item === '') return;

                if (typeof item === 'object' && !(item instanceof File)) {
                    data.append(key, JSON.stringify(item));
                } else {
                    data.append(key, item);
                }
            });

            // Фото (если есть)
            if (this.form.image instanceof File) {
                data.append('photo', this.form.image);
            }

            try {
                // TODO: Замени на Pinia action
                // const response = await this.reviewStore.storeReview(data);

                // Имитация запроса (удали после подключения API)
                await new Promise(resolve => setTimeout(resolve, 1200));

                this.$notify?.({
                    title: 'Отзыв',
                    text: 'Спасибо за ваш отзыв!',
                    type: 'success',
                });

                this.$emit('callback', {
                    rating: this.form.rating,
                    text: this.form.text
                });
                this.$emit('close');

            } catch (error) {
                console.error('Ошибка отправки отзыва:', error);
                this.submitError = error.response?.data?.message || 'Не удалось отправить отзыв';

                this.$notify?.({
                    title: 'Ошибка',
                    text: this.submitError,
                    type: 'error',
                });
            } finally {
                this.isSubmitting = false;
            }
        },
    },
};
</script>

<style scoped>
.review-form {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.form-section {
    margin-bottom: 24px;
}

.section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
}

.section-label i {
    color: var(--bs-primary);
}

.required {
    color: #dc3545;
}

/* ==========================================
   ВЫБОР РЕЙТИНГА
   ========================================== */
.rating-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
    margin-bottom: 14px;
}

.rating-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 8px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.rating-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.rating-card.active {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

/* Цвета для каждого рейтинга */
.rating-card.rating-5.active {
    border-color: #198754;
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.08) 0%, rgba(32, 201, 151, 0.03) 100%);
}

.rating-card.rating-4.active {
    border-color: #20c997;
    background: linear-gradient(135deg, rgba(32, 201, 151, 0.08) 0%, rgba(32, 201, 151, 0.03) 100%);
}

.rating-card.rating-3.active {
    border-color: #ffc107;
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.08) 0%, rgba(255, 193, 7, 0.03) 100%);
}

.rating-card.rating-2.active {
    border-color: #fd7e14;
    background: linear-gradient(135deg, rgba(253, 126, 20, 0.08) 0%, rgba(253, 126, 20, 0.03) 100%);
}

.rating-card.rating-1.active {
    border-color: #dc3545;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.08) 0%, rgba(220, 53, 69, 0.03) 100%);
}

.rating-emoji {
    font-size: 2rem;
    line-height: 1;
    transition: transform 0.3s ease;
}

.rating-card:hover .rating-emoji {
    transform: scale(1.15);
}

.rating-card.active .rating-emoji {
    animation: emojiPop 0.4s ease;
}

@keyframes emojiPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

.rating-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-align: center;
    transition: color 0.3s ease;
}

.rating-card.active .rating-label {
    color: var(--bs-body-color);
    font-weight: 700;
}

.rating-check {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(var(--bs-primary-rgb), 0.4);
}

.rating-card.active .rating-check {
    opacity: 1;
    transform: scale(1);
}

/* Индикатор рейтинга */
.rating-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 10px 16px;
    background: var(--bs-secondary-bg, #f8f9fa);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.indicator-stars {
    display: flex;
    gap: 4px;
}

.indicator-stars i {
    font-size: 1rem;
    color: var(--bs-border-color);
    transition: all 0.3s ease;
}

.indicator-stars i.filled {
    color: #ffc107;
    transform: scale(1.1);
    text-shadow: 0 2px 4px rgba(255, 193, 7, 0.4);
}

.indicator-text {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

/* Цвета индикатора по уровню */
.rating-indicator.level-5 {
    background: rgba(25, 135, 84, 0.08);
}
.rating-indicator.level-5 .indicator-text {
    color: #198754;
}

.rating-indicator.level-4 {
    background: rgba(32, 201, 151, 0.08);
}
.rating-indicator.level-4 .indicator-text {
    color: #20c997;
}

.rating-indicator.level-3 {
    background: rgba(255, 193, 7, 0.08);
}
.rating-indicator.level-3 .indicator-text {
    color: #b8860b;
}

.rating-indicator.level-2 {
    background: rgba(253, 126, 20, 0.08);
}
.rating-indicator.level-2 .indicator-text {
    color: #fd7e14;
}

.rating-indicator.level-1 {
    background: rgba(220, 53, 69, 0.08);
}
.rating-indicator.level-1 .indicator-text {
    color: #dc3545;
}

/* ==========================================
   TEXTAREA
   ========================================== */
.textarea-wrapper {
    position: relative;
}

.review-textarea {
    width: 100%;
    min-height: 120px;
    max-height: 300px;
    padding: 14px 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-body-color);
    font-size: 0.95rem;
    font-family: inherit;
    line-height: 1.5;
    outline: none;
    resize: none;
    transition: all 0.2s ease;
}

.review-textarea:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.review-textarea::placeholder {
    color: var(--bs-secondary-color);
}

.review-textarea.has-error {
    border-color: #dc3545;
}

.review-textarea.has-error:focus {
    box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
}

.textarea-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 8px;
    min-height: 20px;
}

.textarea-error {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #dc3545;
}

.textarea-counter {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin-left: auto;
}

.textarea-counter.warning {
    color: #fd7e14;
    font-weight: 600;
}

/* ==========================================
   ОШИБКА ОТПРАВКИ
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
    margin-bottom: 16px;
}

.error-banner i {
    font-size: 1rem;
    flex-shrink: 0;
}

/* ==========================================
   КНОПКИ ДЕЙСТВИЙ
   ========================================== */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 8px;
}

.submit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
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

.submit-btn:active:not(:disabled) {
    transform: translateY(0);
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.cancel-btn {
    padding: 14px 20px;
    background: transparent;
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cancel-btn:hover:not(:disabled) {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.cancel-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .rating-grid {
        gap: 6px;
    }

    .rating-card {
        padding: 10px 4px;
    }

    .rating-emoji {
        font-size: 1.6rem;
    }

    .rating-label {
        font-size: 0.65rem;
    }

    .review-textarea {
        font-size: 0.9rem;
        padding: 12px 14px;
    }

    .submit-btn {
        font-size: 0.95rem;
        padding: 14px 20px;
    }
}

@media (max-width: 380px) {
    .rating-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>
