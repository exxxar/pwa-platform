<template>
    <teleport to="body">
        <transition name="modal">
            <div v-if="visible" class="collection-modal-backdrop" @click.self="close">
                <div class="collection-modal">
                    <!-- Шапка -->
                    <div class="modal-header">
                        <div class="modal-title">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Сборка коллекции</span>
                        </div>
                        <button class="close-btn" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Детали коллекции -->
                    <div class="collection-details">
                        <img
                            v-if="collection?.image"
                            v-lazy="collection.image"
                            :alt="collection.name"
                            class="details-image"
                        >
                        <div class="details-info">
                            <h3 class="details-name">{{ collection?.name }}</h3>
                            <p v-if="collection?.description" class="details-description">
                                {{ collection.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Вкладки: "Сборка" и "Мои варианты" -->
                    <div class="modal-tabs">
                        <button
                            class="tab-btn"
                            :class="{ active: activeTab === 'build' }"
                            @click="activeTab = 'build'"
                        >
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>Собрать новую</span>
                        </button>
                        <button
                            v-if="cartVariants.length > 0"
                            class="tab-btn"
                            :class="{ active: activeTab === 'variants' }"
                            @click="activeTab = 'variants'"
                        >
                            <i class="fa-solid fa-box-open"></i>
                            <span>В корзине ({{ cartVariants.length }})</span>
                        </button>
                    </div>

                    <!-- Контент вкладок -->
                    <div class="modal-body">
                        <!-- Вкладка "Сборка" -->
                        <div v-if="activeTab === 'build'" class="build-content">
                            <CollectionCategory
                                v-for="cat in collection?.collection_categories || []"
                                :key="cat.id"
                                :category="cat"
                                :selected-products="getSelectedForCategory(cat.id)"
                                @update:selected="setSelectedForCategory(cat.id, $event)"
                            />

                            <div v-if="!collection?.collection_categories?.length" class="empty-state">
                                <i class="fa-solid fa-box-open"></i>
                                <p>В этой коллекции пока нет товаров</p>
                            </div>
                        </div>

                        <!-- Вкладка "Мои варианты" -->
                        <div v-else class="variants-content">
                            <div
                                v-for="(variant, index) in cartVariants"
                                :key="index"
                                class="variant-card"
                            >
                                <div class="variant-header">
                                    <div class="variant-number">
                                        <i class="fa-solid fa-hashtag"></i>
                                        <span>Вариант {{ index + 1 }}</span>
                                    </div>
                                    <button
                                        class="remove-variant-btn"
                                        @click="removeVariant(index)"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                <div class="variant-items">
                                    <div
                                        v-for="item in variant.selected_products"
                                        :key="item.product.id"
                                        class="variant-item"
                                    >
                                        <img v-lazy="item.product.images[0]" class="variant-item-img">
                                        <div class="variant-item-info">
                                            <span class="variant-item-name">{{ item.product.name }}</span>
                                            <span class="variant-item-category">{{ item.category_name }}</span>
                                        </div>
                                        <span class="variant-item-price">{{ formatPrice(item.product.price) }}</span>
                                    </div>
                                </div>
                                <div class="variant-footer">
                                    <span class="variant-total">Итого:</span>
                                    <span class="variant-total-price">{{ formatPrice(variant.total_price) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Футер с итогом и кнопкой -->
                    <div v-if="activeTab === 'build'" class="modal-footer">
                        <div class="total-info">
                            <span class="total-label">Итого:</span>
                            <span class="total-price">{{ formatPrice(currentTotalPrice) }}</span>
                        </div>
                        <button
                            class="add-to-cart-btn"
                            :disabled="!isValid || isLoading"
                            @click="addToCart"
                        >
                            <i v-if="isLoading" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-cart-plus"></i>
                            <span>{{ isLoading ? 'Добавляем...' : 'Добавить в корзину' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script>
import CollectionCategory from './CollectionCategory.vue';
import { useBasket } from '@/MobileClient/Composables/useBasket.js';

export default {
    name: 'CollectionModal',
    components: { CollectionCategory },

    setup() {
        const basket = useBasket();
        return {
            addCollectionToCart: basket.addCollection || (() => {}),
            removeCollectionVariant: basket.removeCollectionVariant || (() => {}),
            getCollectionVariants: basket.getCollectionVariants || (() => []),
        };
    },

    data() {
        return {
            visible: false,
            collection: null,
            activeTab: 'build',
            selections: {}, // { categoryId: [productId, productId, ...] }
            isLoading: false,
        };
    },

    computed: {
        cartVariants() {
            return this.collection ? this.getCollectionVariants(this.collection.id) : [];
        },

        currentTotalPrice() {
            if (!this.collection?.collection_categories) return 0;

            return this.collection.collection_categories.reduce((sum, cat) => {
                const selected = this.selections[cat.id] || [];
                const selectedProducts = (cat.products || []).filter(p => {
                    // Приводим оба ID к строке для корректного сравнения
                    return selected.includes(String(p.id));
                });

                return sum + selectedProducts.reduce((s, p) => {
                    // Явно преобразуем price в число
                    const price = Number(p.price) || 0;
                    return s + price;
                }, 0);
            }, 0);
        },

        isValid() {
            if (!this.collection?.collection_categories) return false;

            return this.collection.collection_categories.every(cat => {
                const selected = this.selections[cat.id] || [];
                const rule = cat.selection_rule;
                const products = cat.products || [];

                if (products.length === 0) return true;

                if (rule === 'one') return selected.length === 1;
                if (rule === 'all') return selected.length === products.length;
                if (rule === 'several') return selected.length > 0;

                return selected.length > 0;
            });
        },
    },

    methods: {
        open(collection) {
            this.collection = collection;
            this.visible = true;
            this.activeTab = 'build';
            this.initSelections();
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.visible = false;
            document.body.style.overflow = '';
        },

        initSelections() {
            this.selections = {};
            (this.collection?.collection_categories || []).forEach(cat => {
                if (cat.selection_rule === 'all') {
                    // Приводим все ID к строке для единообразия
                    this.selections[cat.id] = (cat.products || []).map(p => String(p.id));
                } else {
                    this.selections[cat.id] = [];
                }
            });
        },

        getSelectedForCategory(categoryId) {
            return this.selections[categoryId] || [];
        },

        setSelectedForCategory(categoryId, products) {
            // Убеждаемся, что все ID - строки
            this.selections[categoryId] = products.map(id => String(id));
        },

        async addToCart() {
            if (!this.isValid || this.isLoading) return;

            this.isLoading = true;
            try {
                const selectedProducts = [];
                (this.collection.collection_categories || []).forEach(cat => {
                    const selected = this.selections[cat.id] || [];
                    (cat.products || []).forEach(p => {
                        if (selected.includes(p.id)) {
                            selectedProducts.push({
                                product: p,
                                category_name: cat.category_name,
                                category_id: cat.id,
                            });
                        }
                    });
                });

                const variant = {
                    collection_id: this.collection.id,
                    collection_name: this.collection.name,
                    selected_products: selectedProducts,
                    total_price: this.currentTotalPrice,
                };

                await this.addCollectionToCart(variant);

                this.$notify?.({
                    title: 'Успешно',
                    text: `Коллекция "${this.collection.name}" добавлена в корзину`,
                    type: 'success',
                });

                // Переключаемся на вкладку "Мои варианты"
                this.activeTab = 'variants';
                this.initSelections();
            } catch (error) {
                console.error(error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось добавить коллекцию',
                    type: 'error',
                });
            } finally {
                this.isLoading = false;
            }
        },

        async removeVariant(index) {
            try {
                await this.removeCollectionVariant(this.collection.id, index);
                this.$notify?.({
                    title: 'Удалено',
                    text: 'Вариант удален из корзины',
                    type: 'info',
                });
                if (this.cartVariants.length === 0) {
                    this.activeTab = 'build';
                }
            } catch (error) {
                console.error(error);
            }
        },

        formatPrice(price) {
            if (!price && price !== 0) return '';
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

$primary: #3b82f6;
$secondary: #8b5cf6;
$accent: #ec4899;
$danger: #dc3545;
$success: #10b981;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #ffffff;
$bg-secondary: #f8f9fa;

.collection-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    z-index: 1022;

    @media (min-width: 768px) {
        align-items: center;
    }
}

.collection-modal {
    background: var(--bs-body-bg, #{$bg});
    width: 100%;
    max-width: 560px;
    max-height: 92vh;
    border-radius: 24px 24px 0 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);

    @media (min-width: 768px) {
        border-radius: 24px;
        max-height: 85vh;
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid $border;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.05rem;
    font-weight: 700;
    color: $text;

    i {
        color: $secondary;
    }
}

.close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: $bg-secondary;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &:hover {
        background: $danger;
        color: white;
    }
}

.collection-details {
    display: flex;
    gap: 14px;
    padding: 16px 20px;
    border-bottom: 1px solid $border;
}

.details-image {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
    flex-shrink: 0;
}

.details-info { flex: 1; min-width: 0; }

.details-name {
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 6px 0;
}

.details-description {
    font-size: 0.8rem;
    color: $text-muted;
    margin: 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.modal-tabs {
    display: flex;
    padding: 0 20px;
    gap: 8px;
    border-bottom: 1px solid $border;
}

.tab-btn {
    flex: 1;
    padding: 12px;
    border: none;
    background: transparent;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;

    &.active {
        color: $primary;
        border-bottom-color: $primary;
    }

    &:hover:not(.active) {
        color: $text;
    }
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
}

.build-content {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: $text-muted;

    i { font-size: 2.5rem; opacity: 0.3; margin-bottom: 12px; }
    p { margin: 0; }
}

// Варианты в корзине
.variants-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.variant-card {
    background: $bg-secondary;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid $border;
}

.variant-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: white;
    border-bottom: 1px solid $border;
}

.variant-number {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: $primary;
    font-size: 0.85rem;
}

.remove-variant-btn {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    transition: all 0.2s ease;

    &:hover {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.variant-items { padding: 10px 14px; }

.variant-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;

    &:not(:last-child) {
        border-bottom: 1px solid $border;
    }
}

.variant-item-img {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.variant-item-info {
    flex: 1;
    min-width: 0;
}

.variant-item-name {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: $text;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.variant-item-category {
    display: block;
    font-size: 0.7rem;
    color: $text-muted;
}

.variant-item-price {
    font-size: 0.8rem;
    font-weight: 700;
    color: $primary;
    flex-shrink: 0;
}

.variant-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: white;
    border-top: 1px solid $border;
}

.variant-total {
    font-size: 0.8rem;
    color: $text-muted;
}

.variant-total-price {
    font-size: 1rem;
    font-weight: 800;
    color: $text;
}

.modal-footer {
    padding: 14px 20px;
    border-top: 1px solid $border;
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
}

.total-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.total-label {
    font-size: 0.7rem;
    color: $text-muted;
    margin-bottom: 2px;
}

.total-price {
    font-size: 1.3rem;
    font-weight: 800;
    color: $text;
}

.add-to-cart-btn {
    padding: 14px 20px;
    background: linear-gradient(135deg, #{$primary} 0%, #{$secondary} 50%, #{$accent} 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba($secondary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba($secondary, 0.45);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.modal-enter-active,
.modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
</style>
