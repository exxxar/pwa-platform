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

                    <!-- Вкладки -->
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

                            <!-- Сводка выбранного -->
                            <div v-if="currentSelectionSummary.length > 0" class="selection-summary">
                                <div class="summary-header">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                    <span>Выбрано позиций: {{ currentSelectionSummary.length }}</span>
                                </div>
                                <div class="summary-list">
                                    <div
                                        v-for="(item, index) in currentSelectionSummary"
                                        :key="`${item.categoryId}-${item.product.id}`"
                                        class="summary-item"
                                    >
                                        <div class="summary-item-info">
                                            <span class="summary-item-name">{{ item.product.name }}</span>
                                            <span class="summary-item-category">{{ item.categoryName }}</span>
                                        </div>
                                        <div class="summary-item-actions">
                                            <span class="summary-item-price">{{ formatPrice(item.product.price) }}</span>
                                            <button
                                                class="remove-item-btn"
                                                @click="removeFromSelection(item.product, item.categoryId)"
                                                title="Убрать из сборки"
                                            >
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Вкладка "Мои варианты" -->
                        <div v-else class="variants-content">
                            <div
                                v-for="(variant, index) in cartVariants"
                                :key="variant.params?.variant_id || index"
                                class="variant-card"
                            >
                                <!-- 🆕 Кликабельная шапка для раскрытия деталей -->
                                <div class="variant-header" @click="toggleVariantDetails(variant)">
                                    <div class="variant-number">
                                        <i class="fa-solid fa-hashtag"></i>
                                        <span>Вариант {{ index + 1 }}</span>
                                        <span class="variant-count-badge">x{{ variant.count || 1 }}</span>
                                    </div>
                                    <div class="variant-header-right" @click.stop>
                                        <div class="variant-controls">
                                            <button class="ctrl-btn" :disabled="isProcessing(variant, 'dec')" @click="decrementVariant(index)">
                                                <i v-if="isProcessing(variant, 'dec')" class="fa-solid fa-spinner fa-spin"></i>
                                                <i v-else class="fa-solid fa-minus"></i>
                                            </button>
                                            <button class="ctrl-btn" :disabled="isProcessing(variant, 'inc')" @click="incrementVariant(index)">
                                                <i v-if="isProcessing(variant, 'inc')" class="fa-solid fa-spinner fa-spin"></i>
                                                <i v-else class="fa-solid fa-plus"></i>
                                            </button>
                                            <button class="ctrl-btn remove" :disabled="isProcessing(variant, 'remove')" @click="removeVariant(index)">
                                                <i v-if="isProcessing(variant, 'remove')" class="fa-solid fa-spinner fa-spin"></i>
                                                <i v-else class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                        <i class="fa-solid fa-chevron-down expand-icon" :class="{ 'rotated': isVariantExpanded(variant) }"></i>
                                    </div>
                                </div>

                                <!-- 🆕 Раскрывающийся блок с деталями -->
                                <div v-if="isVariantExpanded(variant)" class="variant-details">
                                    <div v-if="!getVariantProducts(variant).length" class="variant-loading">
                                        <i class="fa-solid fa-spinner fa-spin"></i>
                                        <span>Загрузка состава...</span>
                                    </div>
                                    <div v-else class="variant-items">
                                        <div
                                            v-for="item in getVariantProducts(variant)"
                                            :key="item.product?.id"
                                            class="variant-item"
                                        >
                                            <img v-lazy="item.product?.images?.[0]" class="variant-item-img">
                                            <div class="variant-item-info">
                                                <span class="variant-item-name">{{ item.product?.name || 'Неизвестный товар' }}</span>
                                                <span class="variant-item-category">{{ item.category_name }}</span>
                                            </div>
                                            <span class="variant-item-price">{{ formatPrice(item.product?.price) }}</span>
                                        </div>
                                    </div>

                                    <div class="variant-footer-expanded">
                                        <span class="variant-total">Итого за вариант:</span>
                                        <span class="variant-total-price">{{ formatPrice(variant.total_price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Футер с итогом и кнопкой -->
                    <div v-if="activeTab === 'build'" class="modal-footer">
                        <div class="total-info">
                            <span class="total-label">Итого за новый вариант:</span>
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
import axios from 'axios';
import CollectionCategory from './CollectionCategory.vue';
import { useBasket } from '@/MobileClient/Composables/useBasket.js';
import { useProducts } from "@/MobileClient/composables/useProducts";

export default {
    name: 'CollectionModal',
    components: { CollectionCategory },

    setup() {
        const basket = useBasket();
        const { selectedPartner } = useProducts();

        return {
            selectedPartner,
            addCollectionToCart: basket.addCollection || (() => {}),
            removeCollectionVariant: basket.removeCollectionVariant || (() => {}),
            getCollectionVariants: basket.getCollectionVariants || (() => []),
            incrementCollectionVariant: basket.incrementCollectionVariant || (() => {}),
            decrementCollectionVariant: basket.decrementCollectionVariant || (() => {}),
        };
    },

    data() {
        return {
            visible: false,
            collection: null,
            activeTab: 'build',
            selections: {},
            isLoading: false,

            // 🆕 Состояния для ленивой загрузки и управления действиями
            expandedVariants: new Set(), // Хранит variant_id раскрытых карточек
            resolvedProductsMap: {},     // { variant_id: [ {product, category_name, price} ] }
            processingVariants: {},      // { variant_id: 'inc' | 'dec' | 'remove' }
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
                const categorySum = selected.reduce((s, p) => s + (Number(p.price) || 0), 0);
                return sum + categorySum;
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

        currentSelectionSummary() {
            const summary = [];
            if (!this.collection?.collection_categories) return summary;

            this.collection.collection_categories.forEach(cat => {
                const selected = this.selections[cat.id] || [];
                selected.forEach(product => {
                    summary.push({
                        product,
                        categoryName: cat.category_name,
                        categoryId: cat.id
                    });
                });
            });
            return summary;
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
            // Очистка локального состояния при закрытии
            this.expandedVariants.clear();
            this.resolvedProductsMap = {};
            this.processingVariants = {};
        },

        initSelections() {
            this.selections = {};
            (this.collection?.collection_categories || []).forEach(cat => {
                if (cat.selection_rule === 'all') {
                    this.selections[cat.id] = [...(cat.products || [])];
                } else {
                    this.selections[cat.id] = [];
                }
            });
        },

        getSelectedForCategory(categoryId) {
            return this.selections[categoryId] || [];
        },

        setSelectedForCategory(categoryId, products) {
            this.selections[categoryId] = products;
        },

        removeFromSelection(product, categoryId) {
            const currentSelections = this.selections[categoryId] || [];
            const updatedSelections = currentSelections.filter(p => p.id !== product.id);
            this.setSelectedForCategory(categoryId, updatedSelections);
        },

        // 🆕 Методы для раскрытия и подгрузки деталей
        isVariantExpanded(variant) {
            const vId = variant.params?.variant_id || variant.id;
            return this.expandedVariants.has(vId);
        },

        async toggleVariantDetails(variant) {
            const vId = variant.params?.variant_id || variant.id;

            if (this.expandedVariants.has(vId)) {
                this.expandedVariants.delete(vId);
            } else {
                this.expandedVariants.add(vId);
                // Если детали еще не загружены, но есть ID товаров
                if (!this.resolvedProductsMap[vId] && variant.params?.ids) {
                    await this.loadProductDetails(variant);
                }
            }
        },

        async loadProductDetails(variant) {
            const vId = variant.params?.variant_id || variant.id;
            try {
                const response = await axios.post('/shop/products/by-ids', {
                    ids: variant.params.ids
                });

                // Маппим ответ в формат, который ожидает шаблон
                // Поддержка как response.data.data, так и response.data (в зависимости от структуры вашего API)
                const productsArray = response.data.data || response.data || [];

                this.resolvedProductsMap[vId] = variant.params.ids.map(id => {
                    const prodData = productsArray.find(p => p.id === id);
                    return {
                        product: prodData || { id, name: 'Неизвестный товар' },
                        category_name: variant.collection_name || 'Товар',
                        price: prodData?.price || 0
                    };
                });
            } catch (error) {
                console.error('Ошибка загрузки деталей сборки:', error);
                this.resolvedProductsMap[vId] = []; // Предотвращаем бесконечные повторные запросы при ошибке
            }
        },

        getVariantProducts(variant) {
            const vId = variant.params?.variant_id || variant.id;

            // 1. Приоритет: локально загруженные детали
            if (this.resolvedProductsMap[vId]) {
                return this.resolvedProductsMap[vId];
            }
            // 2. Фоллбэк: если товары пришли сразу с бэкенда
            if (variant.selected_products && Array.isArray(variant.selected_products)) {
                return variant.selected_products;
            }
            if (variant.params?.selected_products && Array.isArray(variant.params.selected_products)) {
                return variant.params.selected_products;
            }

            return [];
        },

        isProcessing(variant, actionType) {
            const vId = variant.params?.variant_id || variant.id;
            return this.processingVariants[vId] === actionType;
        },

        // 🆕 Методы для управления количеством в корзине (с состояниями загрузки)
        async incrementVariant(index) {
            const variant = this.cartVariants[index];
            const vId = variant.params?.variant_id;
            this.processingVariants[vId] = 'inc';

            try {
                await this.incrementCollectionVariant({
                    collection_id: this.collection.id,
                    variant_id: vId
                });
            } catch (error) {
                console.error('Ошибка инкремента:', error);
            } finally {
                delete this.processingVariants[vId];
            }
        },

        async decrementVariant(index) {
            const variant = this.cartVariants[index];
            const vId = variant.params?.variant_id;
            if (variant.count <= 1) return;

            this.processingVariants[vId] = 'dec';
            try {
                await this.decrementCollectionVariant({
                    collection_id: this.collection.id,
                    variant_id: vId
                });
            } catch (error) {
                console.error('Ошибка декремента:', error);
            } finally {
                delete this.processingVariants[vId];
            }
        },

        async removeVariant(index) {
            const variant = this.cartVariants[index];
            const vId = variant.params?.variant_id;

            this.processingVariants[vId] = 'remove';
            try {
                await this.removeCollectionVariant({
                    collection_id: this.collection.id,
                    variant_id: vId
                });
                this.$notify?.({
                    title: 'Удалено',
                    text: 'Вариант удален из корзины',
                    type: 'info',
                });
                if (this.cartVariants.length === 0) {
                    this.activeTab = 'build';
                }
            } catch (error) {
                console.error('Ошибка удаления:', error);
            } finally {
                delete this.processingVariants[vId];
            }
        },

        async addToCart() {
            if (!this.isValid || this.isLoading) return;
            this.isLoading = true;

            try {
                const selectedProducts = [];
                (this.collection.collection_categories || []).forEach(cat => {
                    const selected = this.selections[cat.id] || [];
                    selected.forEach(product => {
                        selectedProducts.push({
                            product_id: product.id,
                            partner_id: product.partner_id || product.tenant_partner_id || null,
                            category_name: cat.category_name,
                            category_id: cat.id,
                            price: Number(product.price) || 0,
                            name: product.name
                        });
                    });
                });

                const partnerId = this.selectedPartner?.tenant_partner_id || null;

                const variant = {
                    partner_id: partnerId,
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

                this.initSelections();

                const modalBody = document.querySelector('.collection-modal .modal-body');
                if (modalBody) {
                    modalBody.scrollTop = 0;
                }

            } catch (error) {
                console.error('Ошибка добавления коллекции:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось добавить коллекцию',
                    type: 'error',
                });
            } finally {
                this.isLoading = false;
            }
        },

        formatPrice(price) {
            if (!price && price !== 0) return '0 ₽';
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

    i { color: $secondary; }
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

/* ==========================================
   СТИЛИ ДЛЯ УЖЕ ДОБАВЛЕННЫХ ВАРИАНТОВ
   ========================================== */
.cart-variants-summary {
    padding: 16px;
    background: rgba(16, 185, 129, 0.05);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 12px;

    .summary-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        color: $success;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px dashed rgba(16, 185, 129, 0.3);
    }

    .cart-variants-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .cart-variant-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: white;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid $border;

        .variant-left {
            display: flex;
            flex-direction: column;
            flex: 1;

            .variant-name {
                font-size: 0.85rem;
                font-weight: 600;
                color: $text;
            }
            .variant-price {
                font-size: 0.8rem;
                color: $text-muted;
                margin-top: 2px;
            }
        }

        .variant-controls {
            display: flex;
            align-items: center;
            gap: 6px;

            .ctrl-btn {
                width: 28px;
                height: 28px;
                border-radius: 6px;
                border: 1px solid $border;
                background: white;
                color: $text;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 0.75rem;

                &:hover:not(:disabled) {
                    background: $bg-secondary;
                    border-color: $text-muted;
                }
                &:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                &.remove {
                    border-color: rgba($danger, 0.3);
                    color: $danger;
                    &:hover:not(:disabled) {
                        background: $danger;
                        color: white;
                        border-color: $danger;
                    }
                }
            }

            .ctrl-value {
                min-width: 24px;
                text-align: center;
                font-size: 0.9rem;
                font-weight: 700;
                color: $text;
            }
        }
    }
}

/* ==========================================
   СТИЛИ ДЛЯ ВКЛАДКИ "МОИ ВАРИАНТЫ"
   ========================================== */
.variants-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.variant-card {
    background: $bg;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid $border;
    transition: box-shadow 0.2s;

    &:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
}

.variant-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: $bg-secondary;
    border-bottom: 1px solid transparent;
    cursor: pointer;
    transition: background 0.2s;

    &:hover {
        background: #f1f5f9;
    }
}

.variant-number {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: $text;
    font-size: 0.9rem;

    .variant-count-badge {
        background: $primary;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: 700;
    }
}

.variant-header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.variant-controls {
    display: flex;
    align-items: center;
    gap: 4px;

    .ctrl-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid $border;
        background: white;
        color: $text;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.8rem;

        &:hover:not(:disabled) {
            background: $bg-secondary;
            border-color: $text-muted;
        }
        &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        &.remove {
            border-color: rgba($danger, 0.3);
            color: $danger;
            &:hover:not(:disabled) {
                background: $danger;
                color: white;
                border-color: $danger;
            }
        }
    }
}

.expand-icon {
    color: $text-muted;
    transition: transform 0.3s ease;
    font-size: 0.9rem;

    &.rotated {
        transform: rotate(180deg);
        color: $primary;
    }
}

/* 🆕 Раскрывающиеся детали варианта */
.variant-details {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.variant-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px;
    color: $text-muted;
    font-size: 0.9rem;
}

.variant-items {
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.variant-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    background: $bg-secondary;
    border-radius: 8px;
}

.variant-item-img {
    width: 40px;
    height: 40px;
    border-radius: 6px;
    object-fit: cover;
    flex-shrink: 0;
    background: #e2e8f0;
}

.variant-item-info {
    flex: 1;
    min-width: 0;
}

.variant-item-name {
    display: block;
    font-size: 0.85rem;
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
    font-size: 0.85rem;
    font-weight: 700;
    color: $primary;
    flex-shrink: 0;
}

.variant-footer-expanded {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: rgba(59, 130, 246, 0.05);
    border-top: 1px solid $border;
}

.variant-total {
    font-size: 0.85rem;
    color: $text-muted;
    font-weight: 600;
}

.variant-total-price {
    font-size: 1.1rem;
    font-weight: 800;
    color: $primary;
}

/* ==========================================
   СТИЛИ ДЛЯ СВОДКИ ВЫБРАННОГО (НИЖНЯЯ)
   ========================================== */
.selection-summary {
    margin-top: 8px;
    padding: 16px;
    background: rgba(59, 130, 246, 0.05);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;

    .summary-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        color: $primary;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px dashed rgba(59, 130, 246, 0.3);
    }

    .summary-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 200px;
        overflow-y: auto;
        padding-right: 4px;

        &::-webkit-scrollbar { width: 4px; }
        &::-webkit-scrollbar-track { background: transparent; }
        &::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.3); border-radius: 2px; }
    }

    .summary-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: white;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid $border;
        transition: all 0.2s;

        &:hover {
            border-color: $primary;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .summary-item-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;

            .summary-item-name {
                font-size: 0.85rem;
                font-weight: 600;
                color: $text;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .summary-item-category {
                font-size: 0.75rem;
                color: $text-muted;
                margin-top: 2px;
            }
        }

        .summary-item-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            margin-left: 12px;

            .summary-item-price {
                font-size: 0.85rem;
                font-weight: 700;
                color: $primary;
            }

            .remove-item-btn {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                border: none;
                background: rgba($danger, 0.1);
                color: $danger;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 0.8rem;

                &:hover {
                    background: $danger;
                    color: white;
                    transform: scale(1.1);
                }
            }
        }
    }
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
