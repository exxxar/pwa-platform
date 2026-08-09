<template>
    <div
        v-if="item"
        class="collection-card"
        :class="{
            'is-loading': isLoading,
            'is-offline': !isOnline
        }"
    >
        <!-- Декоративная рамка-градиент -->
        <div class="collection-frame">
            <!-- ========================================== -->
            <!-- ИЗОБРАЖЕНИЕ -->
            <!-- ========================================== -->
            <div class="collection-image-wrapper" @click="openCollection">
                <img
                    v-lazy="item?.image || '/no-image.png'"
                    :alt="item?.name"
                    class="collection-image"
                    loading="lazy"
                >

                <!-- Overlay -->
                <div class="collection-overlay">
                    <!-- Верхний ряд: бейдж "Коллекция" -->
                    <div class="overlay-top">
                        <div class="collection-badge">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Коллекция</span>
                        </div>

                        <div v-if="item?.discount > 0" class="discount-badge">
                            -{{ item.discount }}%
                        </div>
                    </div>

                    <!-- Нижний ряд -->
                    <div class="overlay-bottom">
                        <!-- Количество товаров -->
                        <div v-if="productsCount > 0" class="items-badge">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span>{{ productsCount }} {{ pluralize(productsCount, ['товар', 'товара', 'товаров']) }}</span>
                        </div>

                        <div class="spacer"></div>

                        <!-- Категории -->
                        <div v-if="categoriesCount > 0" class="categories-badge">
                            <i class="fa-solid fa-tags"></i>
                            <span>{{ categoriesCount }}</span>
                        </div>
                    </div>
                </div>

                <!-- Индикатор загрузки -->
                <transition name="fade">
                    <div v-if="isLoading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- ИНФОРМАЦИЯ -->
            <!-- ========================================== -->
            <div class="collection-info" @click="openCollection">
                <h6 class="collection-name">{{ item.name }}</h6>
                <p v-if="item?.short_description" class="collection-description">
                    {{ item.short_description }}
                </p>

                <!-- Мини-превью категорий -->
                <div v-if="previewCategories.length > 0" class="categories-preview">
                    <span
                        v-for="cat in previewCategories"
                        :key="cat.id"
                        class="category-chip"
                    >
                        {{ cat.category_name }}
                    </span>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ДЕЙСТВИЕ -->
            <!-- ========================================== -->
            <div class="collection-action">
                <!-- Если уже есть варианты в корзине -->
                <template v-if="cartVariants.length > 0">
                    <div class="variants-info">
                        <div class="variants-count">
                            <i class="fa-solid fa-check-circle"></i>
                            <span>{{ cartVariants.length }} {{ pluralize(cartVariants.length, ['вариант', 'варианта', 'вариантов']) }} в корзине</span>
                        </div>
                        <button
                            class="add-another-btn"
                            @click.stop="openCollection"
                        >
                            <i class="fa-solid fa-plus"></i>
                            <span>Собрать ещё</span>
                        </button>
                    </div>
                </template>

                <!-- Основная кнопка -->
                <button
                    v-else
                    class="build-btn"
                    :disabled="isLoading || !isOnline"
                    @click.stop="openCollection"
                >
                    <div class="build-btn-content">
                        <div class="build-icon">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </div>
                        <div class="build-btn-info">
                            <span class="build-btn-label">Собрать и добавить</span>
                            <span class="build-btn-price">
                                <template v-if="item?.pricing_type === 'fixed' && item.fixed_price">
                                    {{ formatPrice(item.fixed_price) }}
                                </template>
                                <template v-else>
                                    от {{ formatPrice(minPrice) }}
                                </template>
                            </span>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { useBasket } from '@/MobileClient/Composables/useBasket.js';

export default {
    name: "CollectionCard",

    props: {
        item: {
            type: Object,
            required: true,
        },
        partnerId:{
            type: Number
        }
    },

    emits: ['open-collection'],

    setup() {
        const basket = useBasket();
        return {
            getCollectionVariants: basket.getCollectionVariants || (() => []),
        };
    },

    data() {
        return {
            isLoading: false,
            isOnline: navigator.onLine,
            _onlineHandler: null,
            _offlineHandler: null,
        };
    },

    computed: {
        productsCount() {
            return this.item?.collection_categories?.reduce((sum, cat) => {
                return sum + (cat.products?.length || 0);
            }, 0) || 0;
        },

        categoriesCount() {
            return this.item?.collection_categories?.length || 0;
        },

        previewCategories() {
            return (this.item?.collection_categories || []).slice(0, 3);
        },

        cartVariants() {
            return this.getCollectionVariants(this.item.id) || [];
        },

        /**
         * Минимальная цена коллекции (сумма минимальных товаров в каждой категории)
         */
        minPrice() {
            if (!this.item?.collection_categories) return 0;

            const allPrices = [];
            this.item.collection_categories.forEach(cat => {
                const products = cat.products || [];
                products.forEach(p => {
                    if (p.price) allPrices.push(Number(p.price));
                });
            });

            return allPrices.length > 0 ? Math.min(...allPrices) : 0;
        },
    },

    mounted() {
        this._onlineHandler = () => { this.isOnline = true; };
        this._offlineHandler = () => { this.isOnline = false; };

        window.addEventListener('online', this._onlineHandler);
        window.addEventListener('offline', this._offlineHandler);
    },

    beforeUnmount() {
        if (this._onlineHandler) window.removeEventListener('online', this._onlineHandler);
        if (this._offlineHandler) window.removeEventListener('offline', this._offlineHandler);
    },

    methods: {
        openCollection() {
            this.$emit('open-collection', this.item);
        },

        formatPrice(price) {
            if (!price && price !== 0) return '';
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        },

        pluralize(n, forms) {
            const abs = Math.abs(n) % 100;
            const n1 = abs % 10;
            if (abs > 10 && abs < 20) return forms[2];
            if (n1 > 1 && n1 < 5) return forms[1];
            if (n1 === 1) return forms[0];
            return forms[2];
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

$primary: #3b82f6;
$secondary: #8b5cf6;
$accent: #ec4899;
$success: #10b981;
$danger: #dc3545;
$warning: #ffc107;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #ffffff;
$bg-secondary: #f8f9fa;

.collection-card {
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    &.is-loading {
        pointer-events: none;
        .collection-image { filter: brightness(0.85); }
    }

    &.is-offline {
        opacity: 0.7;
        .build-btn, .add-another-btn { cursor: not-allowed; }
    }
}

// Декоративная градиентная рамка
.collection-frame {
    background: var(--bs-body-bg, #{$bg});
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    padding: 2px; // Для градиентной рамки
    background-image: linear-gradient(
            135deg,
            #{$primary} 0%,
            #{$secondary} 50%,
            #{$accent} 100%
    );

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 20px;
        padding: 2px;
        background: linear-gradient(
                135deg,
                #{$primary} 0%,
                #{$secondary} 50%,
                #{$accent} 100%
        );
        -webkit-mask:
            linear-gradient(#fff 0 0) content-box,
            linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
    }

    // Внутренняя часть (белая)
    > * {
        background: var(--bs-body-bg, #{$bg});
    }

    > *:first-child { border-radius: 18px 18px 0 0; }
    > *:last-child { border-radius: 0 0 18px 18px; }
}

// ==========================================
// ИЗОБРАЖЕНИЕ
// ==========================================
.collection-image-wrapper {
    position: relative;
    width: 100%;
    height:150px;
    aspect-ratio: 1;
    overflow: hidden;
    background: var(--bs-secondary-bg, #{$bg-secondary});
    cursor: pointer;
}

.collection-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;

    .collection-card:hover & {
        transform: scale(1.08);
    }
}

.collection-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 10px;
    pointer-events: none;
}

.overlay-top,
.overlay-bottom {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    pointer-events: auto;
}

.overlay-bottom { align-items: flex-end; }
.spacer { flex: 1; }

.collection-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #{$primary} 0%, #{$secondary} 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba($primary, 0.4);
    letter-spacing: 0.5px;
    text-transform: uppercase;

    i { font-size: 0.7rem; }
}

.discount-badge {
    padding: 4px 10px;
    background: linear-gradient(135deg, $danger 0%, color.adjust($danger, $lightness: -10%) 100%);
    color: white;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba($danger, 0.4);
}

.items-badge,
.categories-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(10px);
    color: white;
    border-radius: 16px;
    font-size: 0.7rem;
    font-weight: 600;

    i { font-size: 0.65rem; }
}

.loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
    z-index: 5;
}

.loading-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

// ==========================================
// ИНФОРМАЦИЯ
// ==========================================
.collection-info {
    padding: 14px 14px 10px;
    cursor: pointer;
    flex: 1;
}

.collection-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--bs-body-color, #{$text});
    margin: 0 0 6px 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.collection-description {
    font-size: 0.75rem;
    color: var(--bs-secondary-color, #{$text-muted});
    margin: 0 0 10px 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.categories-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.category-chip {
    padding: 3px 8px;
    background: rgba($primary, 0.08);
    color: $primary;
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 600;
    border: 1px solid rgba($primary, 0.15);
}

// ==========================================
// ДЕЙСТВИЕ
// ==========================================
.collection-action {
    padding: 0 8px 8px;
}

.build-btn {
    width: 100%;
    padding: 10px 12px;
    background: linear-gradient(135deg, #{$primary} 0%, #{$secondary} 50%, #{$accent} 100%);
    border: none;
    border-radius: 12px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba($secondary, 0.3);
    overflow: hidden;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba($secondary, 0.45);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.build-btn-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.build-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.build-btn-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
}

.build-btn-label {
    font-size: 0.7rem;
    opacity: 0.95;
    line-height: 1;
    margin-bottom: 3px;
    font-weight: 500;
}

.build-btn-price {
    font-size: 0.9rem;
    font-weight: 800;
    line-height: 1;
}

.variants-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 8px;
    background: rgba($success, 0.06);
    border: 1px solid rgba($success, 0.2);
    border-radius: 12px;
}

.variants-count {
    display: flex;
    align-items: center;
    gap: 6px;
    color: $success;
    font-size: 0.75rem;
    font-weight: 600;

    i { font-size: 0.85rem; }
}

.add-another-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px;
    background: linear-gradient(135deg, #{$primary} 0%, #{$secondary} 100%);
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba($primary, 0.3);
    }
}

.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

@media (max-width: 576px) {
    .collection-name { font-size: 0.88rem; }
    .build-btn-price { font-size: 0.8rem; }
    .collection-badge { font-size: 0.65rem; padding: 5px 10px; }
}
</style>
