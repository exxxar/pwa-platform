<template>
    <div class="review-card">
        <!-- Шапка отзыва -->
        <div class="review-header">
            <div class="review-author">
                <div class="author-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="author-info">
                    <div class="author-name">{{ review.tenant_user?.name || 'Пользователь' }}</div>
                    <div class="review-date">{{ formatDate(review.created_at) }}</div>
                </div>
            </div>

            <!-- Эмодзи рейтинг -->
            <div class="review-rating-emoji" :class="`rating-${review.rating}`">
                {{ getEmojiByRating(review.rating) }}
            </div>
        </div>

        <!-- Заголовок -->
        <div v-if="review.title" class="review-title">
            {{ review.title }}
        </div>

        <!-- Текст отзыва -->
        <div class="review-text">
            {{ review.text }}
        </div>

        <!-- Товар (если есть) -->
        <div v-if="needProduct && review.product" class="review-product">
            <i class="fa-solid fa-box"></i>
            <span>{{ review.product.name }}</span>
        </div>

        <!-- Ответ администратора -->
        <div v-if="review.admin_response" class="review-response">
            <div class="response-header">
                <i class="fa-solid fa-reply"></i>
                <span>Ответ заведения</span>
            </div>
            <div class="response-text">
                {{ review.admin_response }}
            </div>
        </div>

        <!-- Действия -->
        <div class="review-actions">
            <button
                v-if="canEdit"
                class="action-btn"
                @click="$emit('edit', review)"
            >
                <i class="fa-solid fa-pen"></i>
                <span>Редактировать</span>
            </button>
            <button
                v-if="canDelete"
                class="action-btn delete"
                @click="$emit('delete', review)"
            >
                <i class="fa-solid fa-trash"></i>
                <span>Удалить</span>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ReviewCard',

    props: {
        modelValue: { type: Object, required: true },
        needProduct: { type: Boolean, default: false },
        canEdit: { type: Boolean, default: false },
        canDelete: { type: Boolean, default: false },
    },

    emits: ['update:modelValue', 'edit', 'delete'],

    computed: {
        review() {
            return this.modelValue;
        },
    },

    methods: {
        getEmojiByRating(rating) {
            const emojis = {
                1: '😡',
                2: '😞',
                3: '😐',
                4: '😊',
                5: '🤩',
            };
            return emojis[rating] || '😐';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        },
    },
};
</script>

<style scoped>
.review-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 20px;
    transition: all 0.2s ease;
}

.review-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

/* Шапка */
.review-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.review-author {
    display: flex;
    align-items: center;
    gap: 12px;
}

.author-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.author-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.author-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.review-date {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

/* Эмодзи рейтинг */
.review-rating-emoji {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    transition: all 0.3s ease;
}

.review-rating-emoji.rating-1 {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
}

.review-rating-emoji.rating-2 {
    background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
}

.review-rating-emoji.rating-3 {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.review-rating-emoji.rating-4 {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}

.review-rating-emoji.rating-5 {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

/* Заголовок */
.review-title {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
    margin-bottom: 8px;
}

/* Текст */
.review-text {
    font-size: 0.95rem;
    color: var(--bs-body-color);
    line-height: 1.6;
    margin-bottom: 16px;
}

/* Товар */
.review-product {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    margin-bottom: 16px;
}

/* Ответ администратора */
.review-response {
    padding: 14px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.15);
    border-radius: 12px;
    margin-bottom: 16px;
}

.response-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--bs-primary);
    margin-bottom: 8px;
}

.response-text {
    font-size: 0.9rem;
    color: var(--bs-body-color);
    line-height: 1.5;
}

/* Действия */
.review-actions {
    display: flex;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid var(--bs-border-color);
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--bs-secondary-bg);
    border: none;
    border-radius: 8px;
    color: var(--bs-body-color);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: var(--bs-primary);
    color: white;
}

.action-btn.delete:hover {
    background: var(--bs-danger);
}
</style>
