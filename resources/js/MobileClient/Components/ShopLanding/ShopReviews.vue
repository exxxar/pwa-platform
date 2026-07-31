<template>
    <section class="shop-reviews">
        <div class="container">
            <!-- Заголовок с общим рейтингом -->
            <div class="section-header">
                <div class="rating-summary">
                    <div class="big-rating">{{ averageRating }}</div>
                    <div class="rating-stars">
                        <i v-for="star in 5" :key="star" class="fa-solid fa-star" :class="{ 'filled': star <= Math.round(averageRating) }"></i>
                    </div>
                    <div class="rating-count">На основе {{ reviews.length }} отзывов</div>
                </div>
                <h2 class="section-title">{{ config.title || 'Отзывы наших клиентов' }}</h2>
                <p class="section-subtitle">{{ config.subtitle || 'Узнайте, что говорят о нас настоящие гости' }}</p>
            </div>

            <div class="reviews-grid">
                <div v-for="review in reviews" :key="review.id" class="review-card">

                    <!-- Шапка отзыва -->
                    <div class="review-header">
                        <div class="reviewer-info">
                            <img v-if="review.avatar" :src="review.avatar" :alt="review.name" class="review-avatar">
                            <div v-else class="review-avatar placeholder">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="reviewer-details">
                                <div class="review-name">
                                    {{ review.name }}
                                    <span v-if="review.isVerified" class="verified-badge" title="Проверенный покупатель">
                                        <i class="fa-solid fa-circle-check"></i> Проверен
                                    </span>
                                </div>
                                <div class="review-date">{{ review.date || 'Недавно' }}</div>
                            </div>
                        </div>
                        <div class="review-rating-mini">
                            <span class="rating-number">{{ review.rating }}</span>
                            <div class="stars">
                                <i v-for="star in 5" :key="star" class="fa-solid fa-star" :class="{ 'filled': star <= review.rating }"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Текст отзыва -->
                    <p class="review-text">{{ review.text }}</p>

                    <!-- 🆕 Галерея фотографий -->
                    <div v-if="review.photos && review.photos.length > 0" class="review-photos">
                        <div
                            v-for="(photo, idx) in review.photos"
                            :key="idx"
                            class="photo-item"
                            @click="openLightbox(photo)"
                        >
                            <img :src="photo" alt="Фото из отзыва" loading="lazy">
                            <div class="photo-overlay">
                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Футер отзыва (Полезно / Ответ) -->
                    <div class="review-footer">
                        <div class="helpful-block">
                            <span class="helpful-text">Полезен ли отзыв?</span>
                            <button class="helpful-btn" @click="toggleLike(review)">
                                <i class="fa-regular fa-thumbs-up" :class="{ 'fa-solid text-primary': review.userLiked }"></i>
                                <span>{{ review.likes || 0 }}</span>
                            </button>
                        </div>

                        <!-- Ответ заведения (если есть) -->
                        <div v-if="review.sellerResponse" class="seller-response">
                            <div class="response-header">
                                <i class="fa-solid fa-store"></i>
                                <span>Ответ заведения</span>
                            </div>
                            <p>{{ review.sellerResponse }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🆕 Простой Lightbox для просмотра фото -->
        <transition name="fade">
            <div v-if="lightboxImage" class="lightbox-overlay" @click.self="lightboxImage = null">
                <button class="lightbox-close" @click="lightboxImage = null">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <img :src="lightboxImage" class="lightbox-image" alt="Увеличенное фото">
            </div>
        </transition>
    </section>
</template>

<script>
export default {
    name: "ShopReviews",
    props: {
        reviews: {
            type: Array,
            default: () => []
        },
        config: {
            type: Object,
            default: () => ({})
        },
    },
    data() {
        return {
            lightboxImage: null
        };
    },
    computed: {
        averageRating() {
            if (!this.reviews.length) return 0;
            const sum = this.reviews.reduce((acc, rev) => acc + (rev.rating || 0), 0);
            return (sum / this.reviews.length).toFixed(1);
        }
    },
    methods: {
        openLightbox(imageUrl) {
            this.lightboxImage = imageUrl;
            document.body.style.overflow = 'hidden'; // Блокируем скролл фона
        },
        toggleLike(review) {
            // Имитация лайка (в реальности здесь должен быть API-запрос)
            if (!review.userLiked) {
                review.likes = (review.likes || 0) + 1;
                review.userLiked = true;
            } else {
                review.likes = (review.likes || 0) - 1;
                review.userLiked = false;
            }
        }
    },
    watch: {
        lightboxImage(newVal) {
            if (!newVal) {
                document.body.style.overflow = ''; // Возвращаем скролл
            }
        }
    }
};
</script>

<style lang="scss" scoped>
// Переменные (с фоллбэками)
$primary: var(--primary, #ff8a00);
$primary-light: var(--primary-light, #ffb347);
$dark: var(--dark, #1f2937);
$gray: var(--gray, #6b7280);
$light: var(--light, #f9fafb);
$border: var(--border, #e5e7eb);
$success: #10b981;

.shop-reviews {
    padding: 80px 0;
    background: $light;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

// ==========================================
// ЗАГОЛОВОК И ОБЩИЙ РЕЙТИНГ
// ==========================================
.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.rating-summary {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 20px 40px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    border: 1px solid $border;
}

.big-rating {
    font-size: 3rem;
    font-weight: 900;
    color: $dark;
    line-height: 1;
}

.rating-stars {
    margin: 8px 0;
    i {
        color: #e5e7eb;
        font-size: 1.2rem;
        margin: 0 2px;
        &.filled {
            color: #fbbf24; // Желтый для звезд
            text-shadow: 0 2px 4px rgba(251, 191, 36, 0.3);
        }
    }
}

.rating-count {
    font-size: 0.9rem;
    color: $gray;
}

.section-title {
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    color: $dark;
}

.section-subtitle {
    font-size: 1.1rem;
    color: $gray;
    max-width: 600px;
    margin: 0 auto;
}

// ==========================================
// СЕТКА И КАРТОЧКИ
// ==========================================
.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
}

.review-card {
    background: white;
    border: 1px solid $border;
    border-radius: 20px;
    padding: 24px;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 16px;

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.06);
        border-color: rgba($primary, 0.3);
    }
}

// ==========================================
// ШАПКА ОТЗЫВА
// ==========================================
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.review-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);

    &.placeholder {
        background: $light;
        display: flex;
        align-items: center;
        justify-content: center;
        color: $gray;
        font-size: 1.2rem;
    }
}

.reviewer-details {
    display: flex;
    flex-direction: column;
}

.review-name {
    font-weight: 700;
    font-size: 1rem;
    color: $dark;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    color: $success;
    background: rgba($success, 0.1);
    padding: 2px 8px;
    border-radius: 20px;
}

.review-date {
    font-size: 0.8rem;
    color: $gray;
    margin-top: 2px;
}

.review-rating-mini {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.rating-number {
    font-weight: 800;
    font-size: 1.1rem;
    color: $dark;
}

.review-rating-mini .stars {
    i {
        color: #e5e7eb;
        font-size: 0.9rem;
        &.filled { color: #fbbf24; }
    }
}

// ==========================================
// КОНТЕНТ И ФОТО
// ==========================================
.review-text {
    font-size: 0.95rem;
    line-height: 1.6;
    color: #374151;
    margin: 0;
}

.review-photos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 10px;
    margin-top: 8px;
}

.photo-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid $border;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .photo-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;

        i {
            color: white;
            font-size: 1.5rem;
        }
    }

    &:hover {
        img {
            transform: scale(1.05);
        }
        .photo-overlay {
            opacity: 1;
        }
    }
}

// ==========================================
// ФУТЕР И ОТВЕТ
// ==========================================
.review-footer {
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid $border;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.helpful-block {
    display: flex;
    align-items: center;
    gap: 12px;
}

.helpful-text {
    font-size: 0.85rem;
    color: $gray;
}

.helpful-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: $light;
    border: 1px solid $border;
    border-radius: 20px;
    font-size: 0.85rem;
    color: $gray;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: rgba($primary, 0.05);
        border-color: rgba($primary, 0.3);
        color: $primary;
    }
}

.seller-response {
    background: rgba($primary, 0.04);
    border-left: 3px solid $primary;
    border-radius: 0 12px 12px 0;
    padding: 16px;

    .response-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        color: $primary;
        margin-bottom: 8px;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
        color: #4b5563;
        line-height: 1.5;
    }
}

// ==========================================
// LIGHTBOX (ПРОСМОТР ФОТО)
// ==========================================
.lightbox-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.lightbox-image {
    max-width: 90%;
    max-height: 90vh;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    animation: zoomIn 0.3s ease;
}

@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.lightbox-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
    }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .reviews-grid {
        grid-template-columns: 1fr;
    }

    .review-header {
        flex-direction: column;
        gap: 12px;
    }

    .review-rating-mini {
        flex-direction: row;
        align-items: center;
        gap: 8px;
    }

    .rating-summary {
        padding: 16px 24px;
    }

    .big-rating {
        font-size: 2.5rem;
    }
}
</style>
