<template>
    <section v-if="isVisible" class="recommendations-section">

        <!-- ========================================== -->
        <!-- ЗАГОЛОВОК СЕКЦИИ -->
        <!-- ========================================== -->
        <div class="section-header">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fa-solid fa-medal"></i>
                </div>
                <div class="header-info">
                    <h3 class="header-title">{{ title }}</h3>
                    <p v-if="products.length > 0" class="header-subtitle">
                        {{ products.length }}
                        {{ pluralize(products.length, 'товар', 'товара', 'товаров') }}
                    </p>
                </div>
            </div>

            <!-- Навигация карусели -->
            <div v-if="products.length > itemsToShow" class="carousel-nav">
                <button
                    class="nav-btn prev"
                    :disabled="isBeginning"
                    @click="slidePrev"
                    aria-label="Предыдущий"
                >
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button
                    class="nav-btn next"
                    :disabled="isEnd"
                    @click="slideNext"
                    aria-label="Следующий"
                >
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СОСТОЯНИЕ ЗАГРУЗКИ (SKELETON) -->
        <!-- ========================================== -->
        <div v-if="isLoading" class="skeleton-container">
            <div v-for="i in skeletonCount" :key="i" class="skeleton-card">
                <div class="skeleton-image shimmer"></div>
                <div class="skeleton-content">
                    <div class="skeleton-line w-80 shimmer"></div>
                    <div class="skeleton-line w-50 shimmer"></div>
                    <div class="skeleton-line w-30 shimmer"></div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ОШИБКА -->
        <!-- ========================================== -->
        <div v-else-if="error" class="error-state">
            <div class="error-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <p class="error-text">Не удалось загрузить рекомендации</p>
            <button class="retry-btn" @click="loadProducts">
                <i class="fa-solid fa-rotate-right"></i>
                Попробовать снова
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ПУСТОЕ СОСТОЯНИЕ -->
        <!-- ========================================== -->
        <div v-else-if="products.length === 0" class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <p class="empty-text">Рекомендации скоро появятся</p>
        </div>

        <!-- ========================================== -->
        <!-- КАРУСЕЛЬ ТОВАРОВ -->
        <!-- ========================================== -->
        <div v-else class="carousel-wrapper">
            <Carousel
                ref="carousel"
                v-bind="carouselConfig"
                @slide-start="handleSlideStart"
            >
                <Slide
                    v-for="product in products"
                    :key="product.id"
                    class="carousel-slide"
                >
                    <div class="carousel-item">
                        <ProductCard
                            :item="product"
                            @click="handleProductClick(product)"
                        />
                    </div>
                </Slide>

                <template #addons>
                    <Pagination />
                </template>
            </Carousel>
        </div>

    </section>
</template>

<script>
import { useProductsStore } from '@/MobileClient/stores/Shop/products';
import ProductCard from '@/MobileClient/Components/Shop/ProductCard.vue';
import 'vue3-carousel/dist/carousel.css';
import { Carousel, Slide, Pagination } from 'vue3-carousel';

export default {
    name: 'ProductRecommendationList',

    components: {
        Carousel,
        Slide,
        Pagination,
        ProductCard,
    },

    props: {
        /**
         * Заголовок секции
         */
        title: {
            type: String,
            default: 'Также рекомендуем',
        },
        /**
         * Максимальное количество товаров
         */
        limit: {
            type: Number,
            default: 20,
        },
        /**
         * ID товара для исключения (чтобы не рекомендовать текущий)
         */
        excludeProductId: {
            type: [Number, String],
            default: null,
        },
        /**
         * ID категории для фильтрации
         */
        categoryId: {
            type: [Number, String],
            default: null,
        },
        /**
         * Автоматически загружать при монтировании
         */
        autoLoad: {
            type: Boolean,
            default: true,
        },
        /**
         * Количество элементов в skeleton
         */
        skeletonCount: {
            type: Number,
            default: 4,
        },
        /**
         * Количество видимых элементов на разных экранах
         */
        itemsToShow: {
            type: Number,
            default: 2.2,
        },
    },

    emits: ['select', 'loaded', 'error'],

    data() {
        return {
            products: [],
            isLoading: false,
            error: null,
            isBeginning: true,
            isEnd: false,
            productStore: useProductsStore(),
        };
    },

    computed: {
        /**
         * Конфигурация карусели с breakpoints
         */
        carouselConfig() {
            return {
                itemsToShow: this.itemsToShow,
                wrapAround: true,
                snapAlign: 'start',
                transition: 500,
                mouseDrag: true,
                touchDrag: true,
                breakpoints: {
                    // Мобильные (< 576px)
                    0: {
                        itemsToShow: 1.2,
                        snapAlign: 'start',
                    },
                    // Планшеты (576-768px)
                    576: {
                        itemsToShow: 2.2,
                    },
                    // Десктоп (768-1024px)
                    768: {
                        itemsToShow: 3.2,
                    },
                    // Большие экраны (> 1024px)
                    1024: {
                        itemsToShow: 4.2,
                    },
                },
            };
        },

        /**
         * Видимость секции
         */
        isVisible() {
            return this.autoLoad || this.products.length > 0;
        },
    },

    watch: {
        /**
         * Перезагрузка при изменении фильтров
         */
        excludeProductId() {
            if (this.autoLoad) {
                this.loadProducts();
            }
        },
        categoryId() {
            if (this.autoLoad) {
                this.loadProducts();
            }
        },
    },

    mounted() {
        if (this.autoLoad) {
            this.loadProducts();
        }
    },

    methods: {
        /**
         * Загрузка товаров
         */
        async loadProducts() {
            this.isLoading = true;
            this.error = null;

            try {
                const response = await this.productStore.loadRecommendedProducts({
                    limit: this.limit,
                    exclude_id: this.excludeProductId,
                    category_id: this.categoryId,
                });

                this.products = response.data || [];
                this.$emit('loaded', this.products);

                // Сбрасываем состояние карусели
                this.$nextTick(() => {
                    this.isBeginning = true;
                    this.isEnd = false;
                });

            } catch (err) {
                console.error('[Recommendations] Ошибка загрузки:', err);
                this.error = err.message || 'Не удалось загрузить';
                this.$emit('error', err);
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Перезагрузка (публичный метод для родителя)
         */
        reload() {
            return this.loadProducts();
        },

        /**
         * Обработка клика по товару
         */
        handleProductClick(product) {
            this.$emit('select', product);
        },

        /**
         * Навигация карусели: предыдущий
         */
        slidePrev() {
            this.$refs.carousel?.prev();
        },

        /**
         * Навигация карусели: следующий
         */
        slideNext() {
            this.$refs.carousel?.next();
        },

        /**
         * Обработка начала слайда
         */
        handleSlideStart({ currentSlide }) {
            this.isBeginning = currentSlide === 0;
            this.isEnd = currentSlide === this.products.length - 1;
        },

        /**
         * Склонение слов
         */
        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
    },
};
</script>

<style lang="scss" scoped>
@use "sass:color";
$primary: #667eea;
$primary-dark: #5a67d8;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: #f8f9fa;
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$danger: #ef4444;

.recommendations-section {
    padding: 20px 0;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

// ==========================================
// ЗАГОЛОВОК СЕКЦИИ
// ==========================================
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding: 0 4px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba($primary, 0.3);
}

.header-info {
    flex: 1;
    min-width: 0;
}

.header-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 2px;
    line-height: 1.2;
}

.header-subtitle {
    font-size: 0.8rem;
    color: $text-muted;
    margin: 0;
}

// ==========================================
// НАВИГАЦИЯ КАРУСЕЛИ
// ==========================================
.carousel-nav {
    display: flex;
    gap: 8px;
}

.nav-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg;
    border: 1px solid $border;
    color: $text;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

    &:hover:not(:disabled) {
        background: $primary;
        color: white;
        border-color: $primary;
        transform: scale(1.1);
    }

    &:active:not(:disabled) {
        transform: scale(0.95);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

// ==========================================
// SKELETON
// ==========================================
.skeleton-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}

.skeleton-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    overflow: hidden;
}

.skeleton-image {
    width: 100%;
    aspect-ratio: 1;
    background: $bg-secondary;
}

.skeleton-content {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-line {
    height: 12px;
    border-radius: 6px;
    background: $bg-secondary;

    &.w-80 { width: 80%; }
    &.w-50 { width: 50%; }
    &.w-30 { width: 30%; }
}

.shimmer {
    background: linear-gradient(
            90deg,
            $bg-secondary 0%,
            color.adjust($bg-secondary, $lightness: -3%) 50%,
            $bg-secondary 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// ОШИБКА
// ==========================================
.error-state {
    text-align: center;
    padding: 40px 20px;
    background: rgba($danger, 0.05);
    border: 1px solid rgba($danger, 0.2);
    border-radius: 14px;
}

.error-icon {
    font-size: 2rem;
    color: $danger;
    margin-bottom: 12px;
}

.error-text {
    color: $text;
    margin-bottom: 16px;
    font-size: 0.9rem;
}

.retry-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        background: $primary-dark;
        transform: translateY(-2px);
    }
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: $text-muted;
}

.empty-icon {
    font-size: 2.5rem;
    opacity: 0.3;
    margin-bottom: 12px;
}

.empty-text {
    font-size: 0.9rem;
    margin: 0;
}

// ==========================================
// КАРУСЕЛЬ
// ==========================================
.carousel-wrapper {
    :deep(.carousel) {
        padding: 4px 0;
    }

    :deep(.carousel__slide) {
        padding: 4px;
    }

    :deep(.carousel__pagination) {
        margin-top: 16px;

        .carousel__pagination-button {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: $border;
            margin: 0 4px;
            transition: all 0.2s ease;

            &::after {
                display: none;
            }

            &:hover {
                background: $primary;
                transform: scale(1.3);
            }
        }

        .carousel__pagination-button--active {
            background: $primary;
            width: 24px;
            border-radius: 4px;
        }
    }
}

.carousel-item {
    height: 100%;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 576px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .carousel-nav {
        align-self: flex-end;
    }

    .header-title {
        font-size: 1rem;
    }

    .skeleton-container {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
