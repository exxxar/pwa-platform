<template>
    <Teleport to="body">
        <transition name="modal-fade">
            <div
                v-if="modelValue"
                class="favorites-modal-overlay"
                @click.self="close"
            >
                <transition name="modal-slide" appear>
                    <div class="favorites-modal-container">

                        <!-- Шапка -->
                        <div class="modal-header">
                            <div class="header-left">
                                <div class="modal-icon">
                                    <i class="fa-solid fa-heart"></i>
                                </div>
                                <div class="header-info">
                                    <h3 class="modal-title">Избранное</h3>
                                    <p class="modal-subtitle">
                                        {{ products.length > 0
                                        ? `${products.length} ${pluralize(products.length, 'товар', 'товара', 'товаров')}`
                                        : 'Пока пусто' }}
                                    </p>
                                </div>
                            </div>
                            <button class="close-btn" @click="close" title="Закрыть">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <!-- Тело -->
                        <div class="modal-body">

                            <!-- Загрузка -->
                            <div v-if="isLoading" class="loading-state">
                                <div class="loading-spinner"></div>
                                <p>Загружаем избранное...</p>
                            </div>

                            <!-- Пустое состояние -->
                            <div v-else-if="products.length === 0" class="empty-state">
                                <div class="empty-icon">
                                    <i class="fa-regular fa-heart"></i>
                                </div>
                                <h4 class="empty-title">Избранное пусто</h4>
                                <p class="empty-text">
                                    Нажимайте на сердечко у товаров, чтобы добавить их в избранное
                                </p>
                                <button class="go-catalog-btn" @click="close">
                                    <i class="fa-solid fa-shop"></i>
                                    <span>Перейти в каталог</span>
                                </button>
                            </div>

                            <!-- Список товаров -->
                            <div v-else class="favorites-grid">
                                <div
                                    v-for="product in products"
                                    :key="product.id"
                                    class="favorite-item"
                                >
                                    <ProductCard :item="product" />
                                </div>
                            </div>

                        </div>

                    </div>
                </transition>
            </div>
        </transition>
    </Teleport>
</template>

<script>
import ProductCard from "@/MobileClient/Components/Shop/ProductCard.vue";
import { useFavoritesStore } from '@/MobileClient/stores/Shop/favorites.js';

export default {
    name: 'FavoritesModal',

    components: {
        ProductCard,
    },

    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            favoritesStore: useFavoritesStore(),
            isLoading: false,
        };
    },

    computed: {
        // 🆕 Берём данные напрямую из store (без ref-обёрток)
        products() {
            return this.favoritesStore.favoriteProducts || [];
        },

        hasFavoriteIds() {
            return (this.favoritesStore.favoriteIds?.length || 0) > 0;
        },
    },

    watch: {
        modelValue(isOpen) {
            if (isOpen) {
                this.loadData();
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },
    },

    beforeUnmount() {
        document.body.style.overflow = '';
    },

    methods: {
        close() {
            this.$emit('update:modelValue', false);
        },

        async loadData() {
            this.isLoading = true;

            try {
                // 1. Загружаем ID
                await this.favoritesStore.loadFavorites();

                // 2. Если есть ID — загружаем товары
                if (this.hasFavoriteIds) {
                    await this.favoritesStore.loadFavoriteProducts();
                }

            } catch (error) {
                console.error('[FavoritesModal] Ошибка:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить избранное',
                    type: 'error',
                });
            } finally {
                this.isLoading = false;
            }
        },

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
$primary: #667eea;
$primary-dark: #5a67d8;
$danger: #ef4444;
$bg: #ffffff;
$bg-secondary: #f9fafb;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;

.favorites-modal-overlay {
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

.favorites-modal-container {
    background: $bg;
    width: 100%;
    max-width: 720px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px;
    background: rgba($primary, 0.04);
    border-bottom: 1px solid $border;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(238, 9, 121, 0.3);
}

.header-info {
    flex: 1;
    min-width: 0;
}

.modal-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0 0 2px;
    color: $text;
}

.modal-subtitle {
    font-size: 0.8rem;
    color: $text-muted;
    margin: 0;
}

.close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: transparent;
    border: 1px solid $border;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover {
        background: $danger;
        border-color: $danger;
        color: white;
        transform: scale(1.1);
    }
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    -webkit-overflow-scrolling: touch;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: $text-muted;

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 14px;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 50px 20px;

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 16px;
        background: rgba($primary, 0.1);
        color: $primary;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .empty-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: $text;
        margin: 0 0 8px;
    }

    .empty-text {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0 0 20px;
        line-height: 1.5;
    }
}

.go-catalog-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, $primary, $primary-dark);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($primary, 0.3);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba($primary, 0.4);
    }
}

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}

.favorite-item {
    animation: fadeInUp 0.3s ease-out backwards;

    @for $i from 1 through 12 {
        &:nth-child(#{$i}) {
            animation-delay: #{$i * 0.05}s;
        }
    }
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

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-slide-enter-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-slide-leave-active {
    transition: all 0.2s ease-in;
}

.modal-slide-enter-from {
    opacity: 0;
    transform: scale(0.9) translateY(20px);
}

.modal-slide-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

@media (max-width: 640px) {
    .favorites-modal-overlay {
        padding: 0;
        align-items: flex-end;
    }

    .favorites-modal-container {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 20px 20px 0 0;
    }

    .modal-header {
        padding: 16px;
    }

    .modal-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .modal-title {
        font-size: 1.05rem;
    }

    .modal-body {
        padding: 16px;
    }

    .favorites-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
}
</style>
