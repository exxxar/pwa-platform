<template>
    <div class="collection-page">
        <!-- ШАПКА -->
        <div class="page-header">
            <button class="back-btn" @click="goBack" aria-label="Назад">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="header-info" v-if="collection">
                <h2 class="header-title">Сборка коллекции</h2>
                <p class="header-subtitle">{{ collection.name }}</p>
            </div>
            <button
                class="cart-btn"
                @click="activeTab = 'variants'"
                v-if="cartVariants.length > 0"
                aria-label="Перейти в корзину"
            >
                <i class="fa-solid fa-box-open"></i>
                <span class="cart-badge">{{ cartVariants.length }}</span>
            </button>
        </div>

        <!-- ДЕТАЛИ КОЛЛЕКЦИИ -->
        <div class="collection-hero" v-if="collection">
            <div class="hero-image-wrapper">
                <img
                    v-if="collection.image"
                    :src="collection.image"
                    :alt="collection.name"
                    class="hero-image"
                >
                <div v-else class="hero-image placeholder">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>
            <div class="hero-content">
                <h3>{{ collection.name }}</h3>
                <p v-if="collection.description">{{ collection.description }}</p>
            </div>
        </div>

        <!-- ВКЛАДКИ И УПРАВЛЕНИЕ -->
        <div class="page-tabs-wrapper" v-if="cartVariants.length > 0">
            <div class="page-tabs">
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'build' }"
                    @click="randomizeAndSwitch"
                    title="Случайно выбрать по 1 товару из каждой категории"
                >
                    <i class="fa-solid fa-folder-tree"></i>
                    <span>Коллекции</span>
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

            <!-- 🆕 Переключатель вида (только на вкладке сборки) -->
            <div v-if="activeTab === 'build'" class="view-controls">
                <button
                    class="view-btn"
                    :class="{ active: viewMode === 'grid' }"
                    @click="viewMode = 'grid'"
                >
                    <i class="fa-solid fa-table-cells"></i> Сетка
                </button>
                <button
                    class="view-btn"
                    :class="{ active: viewMode === 'list' }"
                    @click="viewMode = 'list'"
                >
                    <i class="fa-solid fa-list"></i> Список
                </button>


            </div>
        </div>

        <!-- КОНТЕНТ -->
        <div class="page-content">
            <!-- ВКЛАДКА СБОРКИ -->
            <div v-if="activeTab === 'build'" class="build-tab">
                <button
                    class="random-btn active w-100 "
                    @click="randomizeSelection"
                    title="Случайно выбрать по 1 товару из каждой категории"
                >
                    <i class="fa-solid fa-shuffle"></i>
                    <span>Собрать случайно</span>
                </button>


                <div
                    v-for="cat in collection?.collection_categories || []"
                    :key="cat.id"
                    class="category-section"
                >
                    <div class="category-header">
                        <h4>{{ cat.category_name }}</h4>
                        <span class="category-rule-badge">
                            <i class="fa-solid fa-circle-info"></i>
                            {{ getRuleText(cat.selection_rule) }}
                        </span>
                    </div>

                    <!-- 🆕 Динамический класс для сетки или списка -->
                    <div :class="viewMode === 'list' ? 'products-list' : 'products-grid'">

                        <!-- 🆕 Компактный вид для режима "Список" -->
                        <template v-if="viewMode === 'list'">
                            <CollectionProductItem
                                v-for="product in cat.products || []"
                                :key="product.id"
                                :product="product"
                                :category-name="cat.category_name"
                                :is-selected="isProductSelected(product.id, cat.id)"
                                @select="handleProductSelect($event, cat)"
                            />
                        </template>

                        <!-- Стандартный вид для режима "Сетка" -->
                        <template v-else>
                            <ProductCard
                                v-for="product in cat.products || []"
                                :key="product.id"
                                :item="product"
                                :collection-mode="true"
                                :is-selected="isProductSelected(product.id, cat.id)"
                                @select-in-collection="handleProductSelect($event, cat)"
                            />
                        </template>

                    </div>
                </div>

                <div v-if="!collection?.collection_categories?.length" class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <p>В этой коллекции пока нет товаров</p>
                </div>

                <!-- БЛОК СВОДКИ И ВЫБРАННЫХ ТОВАРОВ -->
                <div v-if="currentSelectionSummary.length > 0" class="checkout-section">
                    <h4 class="checkout-title">Ваша сборка:</h4>

                    <div class="selected-items-list">
                        <div v-for="(item, idx) in currentSelectionSummary" :key="idx" class="selected-item-row">
                            <div class="item-info">
                                <span class="item-category">{{ item.categoryName }}</span>
                                <span class="item-name">{{ item.product.name }}</span>
                            </div>
                            <div class="item-actions">
                                <span class="item-price">{{ formatPrice(item.product.price) }}</span>
                                <button class="remove-item-btn" @click="removeFromSelection(item.product, item.categoryId)" aria-label="Удалить товар">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="footer-summary">
                        <div class="summary-row">
                            <span class="summary-label">Всего позиций:</span>
                            <span class="summary-value">{{ currentSelectionSummary.length }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Общая сумма:</span>
                            <span class="summary-total">{{ formatPrice(currentTotalPrice) }}</span>
                        </div>
                    </div>

                    <div class="checkout-actions">
                        <!-- 🆕 Кнопка очистки -->
                        <button class="clear-btn" @click="clearSelection" :disabled="currentSelectionSummary.length === 0">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Очистить</span>
                        </button>

                        <!-- Кнопка добавления в корзину -->
                        <button
                            class="add-to-cart-btn"
                            :class="{ 'is-valid': isValid }"
                            :disabled="!isValid || isLoading"
                            @click="addToCart"
                        >
                            <i v-if="isLoading" class="fa-solid fa-spinner fa-spin"></i>
                            <template v-else>
                                <i class="fa-solid fa-cart-plus"></i>
                                <span>Добавить в корзину</span>
                            </template>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ВКЛАДКА ВАРИАНТОВ В КОРЗИНЕ -->
            <div v-else class="variants-tab">
                <div v-if="cartVariants.length === 0" class="empty-state">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <p>Корзина пуста. Соберите коллекцию на вкладке "Собрать"</p>
                    <button class="go-build-btn" @click="activeTab = 'build'">Перейти к сборке</button>
                </div>

                <div v-else class="variants-list">
                    <div v-for="(variant, index) in cartVariants" :key="variant.id" class="variant-card">
                        <div class="variant-header">
                            <span class="variant-number">Вариант #{{ index + 1 }} {{variant.name}}</span>
                            <button class="remove-btn" @click="removeVariant(index)" aria-label="Удалить вариант">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                        <div class="variant-products">
                            <div v-for="(item, idx) in variant.selected_products_names" :key="idx" class="variant-product-item">
                                <span class="vp-name fst-italic">{{ item }}</span>
<!--                                <span class="vp-price">{{ formatPrice(item.price) }}</span>-->
                            </div>
                        </div>
                        <div class="variant-total">
                            <span>Итого:</span>
                            <span class="variant-total-price">{{ formatPrice(variant.total_price) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import ProductCard from '@/MobileClient/Components/Shop/ProductCard.vue';
import CollectionProductItem from '@/MobileClient/Components/Shop/Collections/CollectionProductItem.vue'; // 🆕 Путь может отличаться

import { useBasket } from '@/MobileClient/Composables/useBasket.js';
import { useProducts } from '@/MobileClient/composables/useProducts';

export default {
    name: 'CollectionPage',
    components: { ProductCard,CollectionProductItem },

    setup() {
        const route = useRoute();
        const router = useRouter();
        const basket = useBasket();
        const { selectedPartner } = useProducts();
        const partnerId = route.params.partnerId || route.query.partnerId;

        return {
            route,
            router,
            selectedPartner,
            addCollectionToCart: basket.addCollection || (() => {}),
            getCollectionVariants: basket.getCollectionVariants || (() => []),
            removeCollectionVariant: basket.removeCollectionVariant || (() => {}),
            partnerId
        };
    },

    data() {
        return {
            collection: null,
            activeTab: 'build',
            viewMode: 'list', // 🆕 'grid' или 'list'
            selections: {},
            isLoading: false,
        };
    },

    computed: {
        collectionId() {
            return this.route.params.id;
        },

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

        isValid() {
            if (!this.collection?.collection_categories) return false;

            return this.collection.collection_categories.every(cat => {
                const selected = this.selections[cat.id] || [];
                const rule = cat.selection_rule;
                const products = cat.products || [];

                if (products.length === 0) return true;
                if (rule === 'one') return selected.length === 1;
                if (rule === 'all') return selected.length === products.length;
                if (rule === 'several' || rule === 'at_leastone' || rule === 'at_least_one') return selected.length >= 1;

                return selected.length >= 1;
            });
        },
    },

    async mounted() {
        await this.loadCollection();
    },

    methods: {
        async loadCollection() {
            this.isLoading = true;
            try {
                const response = await axios.get(`/shop/collections/${this.collectionId}?partner_id=${this.partnerId}`);
                this.collection = response.data.data || response.data;

                if (!this.collection.collection_categories) {
                    this.collection.collection_categories = [];
                }
                this.initSelections();
            } catch (error) {
                console.error('Ошибка загрузки коллекции:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить коллекцию', type: 'error' });
                this.goBack();
            } finally {
                this.isLoading = false;
            }
        },

        initSelections() {
            this.selections = {};
            (this.collection?.collection_categories || []).forEach(cat => {
                const products = cat.products || [];
                this.selections[cat.id] = (cat.selection_rule === 'all') ? [...products] : [];
            });
        },

        // 🆕 Метод для случайного выбора по 1 товару из каждой категории
        randomizeSelection() {
            if (!this.collection?.collection_categories) return;

            const newSelections = {};
            let hasChanged = false;

            this.collection.collection_categories.forEach(cat => {
                const products = cat.products || [];
                if (products.length > 0) {
                    const randomIndex = Math.floor(Math.random() * products.length);
                    newSelections[cat.id] = [products[randomIndex]];
                    hasChanged = true;
                } else {
                    newSelections[cat.id] = [];
                }
            });

            if (hasChanged) {
                this.selections = newSelections;
                this.$notify?.({ title: 'Успешно', text: 'Применен случайный выбор', type: 'success' });
            }
        },

        // 🆕 Обработчик кнопки "Собрать случайно"
        randomizeAndSwitch() {
            this.activeTab = 'build';

        },

        // 🆕 Метод для полной очистки выбора
        clearSelection() {
            this.collection?.collection_categories?.forEach(cat => {
                this.selections[cat.id] = [];
            });
            this.$notify?.({ title: 'Очищено', text: 'Выбор товаров сброшен', type: 'info' });
        },

        handleProductSelect(product, category) {
            const currentSelections = this.selections[category.id] || [];
            const isSelected = currentSelections.some(p => p.id === product.id);

            if (isSelected) {
                this.selections[category.id] = currentSelections.filter(p => p.id !== product.id);
            } else {
                const rule = category.selection_rule;
                if (rule === 'one') {
                    this.selections[category.id] = [product];
                } else {
                    this.selections[category.id] = [...currentSelections, product];
                }
            }
        },

        removeFromSelection(product, categoryId) {
            if (this.selections[categoryId]) {
                this.selections[categoryId] = this.selections[categoryId].filter(p => p.id !== product.id);
            }
        },

        isProductSelected(productId, categoryId) {
            return (this.selections[categoryId] || []).some(p => p.id === productId);
        },

        getRuleText(rule) {
            const texts = {
                'one': 'Выберите 1 товар',
                'all': 'Выберите все товары',
                'several': 'Выберите несколько',
                'at_least_one': 'Минимум 1 товар',
                'at_leastone': 'Минимум 1 товар'
            };
            return texts[rule] || 'Минимум 1 товар';
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
                            partner_id: this.partnerId,
                            category_name: cat.category_name,
                            category_id: cat.id,
                            price: Number(product.price) || 0,
                            name: product.name
                        });
                    });
                });

                const variant = {
                    partner_id:this.partnerId,
                    collection_id: this.collection.id,
                    collection_name: this.collection.name,
                    selected_products: selectedProducts,
                    total_price: this.currentTotalPrice,
                };

                await this.addCollectionToCart(variant);
                this.$notify?.({ title: 'Успешно', text: `Коллекция "${this.collection.name}" добавлена в корзину`, type: 'success' });

                this.initSelections();
                this.activeTab = 'variants';

            } catch (error) {
                console.error('Ошибка добавления:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось добавить коллекцию', type: 'error' });
            } finally {
                this.isLoading = false;
            }
        },

        removeVariant(index) {
            const variant = this.cartVariants[index];
            if (!variant || !variant.id) {
                console.error('Не удалось найти вариант для удаления');
                return;
            }

            this.removeCollectionVariant({
                collection_id: this.collection.id,
                variantId: variant.id
            })
                .then(() => {
                    this.$notify?.({ title: 'Удалено', text: 'Вариант удален из корзины', type: 'info' });
                })
                .catch((error) => {
                    console.error('Ошибка удаления варианта:', error);
                    this.$notify?.({ title: 'Ошибка', text: 'Не удалось удалить вариант', type: 'error' });
                });
        },

        goBack() {
            this.router.back();
        },

        formatPrice(price) {
            if (!price && price !== 0) return '0 ₽';
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: var(--bs-primary, #3b82f6);
$primary-light: rgba(59, 130, 246, 0.1);
$danger: #ef4444;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8fafc);
$border: var(--bs-border-color, #e2e8f0);
$text: var(--bs-body-color, #0f172a);
$text-muted: var(--bs-secondary-color, #64748b);

.collection-page {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 40px;
}

// --- ШАПКА ---
.page-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: rgba($bg, 0.85);
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.back-btn, .cart-btn {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: $bg-secondary;
    border: 1px solid $border;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &:hover {
        background: $primary;
        color: white;
        border-color: $primary;
    }
}

.cart-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: $danger;
    color: white;
    font-size: 0.65rem;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    border-radius: 9px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid $bg;
}

.header-info { flex: 1; }
.header-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: $text; }
.header-subtitle { margin: 0; font-size: 0.8rem; color: $text-muted; }

// --- HERO ---
.collection-hero {
    display: flex;
    gap: 16px;
    padding: 20px 16px;
    background: linear-gradient(to bottom, $bg-secondary, $bg);
    border-bottom: 1px solid $border;

    .hero-image-wrapper {
        flex-shrink: 0;
        width: 90px;
        height: 90px;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border: 1px solid $border;
    }

    .hero-image {
        width: 100%;
        height: 100%;
        object-fit: cover;

        &.placeholder {
            background: $primary-light;
            color: $primary;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
    }

    .hero-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;

        h3 { margin: 0 0 6px; font-size: 1.15rem; font-weight: 700; color: $text; }
        p { margin: 0; color: $text-muted; font-size: 0.85rem; line-height: 1.4; }
    }
}

// --- ВКЛАДКИ И УПРАВЛЕНИЕ ---
.page-tabs-wrapper {
    padding: 16px 16px 0;
    background: $bg;
}

.random-btn {
    flex: 1;
    border: 1px solid var(--bs-border-color, #e2e8f0);
    padding: 10px 12px;
    background: transparent;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    margin-bottom: 12px;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

    &:hover { color: $text; }

    &.active {
        background: $bg;
        color: $primary;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
}

.page-tabs {
    display: flex;
    gap: 8px;
    background: $bg-secondary;
    padding: 4px;
    border-radius: 14px;
    border: 1px solid $border;

    .tab-btn {
        flex: 1;
        padding: 10px 12px;
        border: none;
        background: transparent;
        border-radius: 10px;
        color: $text-muted;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

        &:hover { color: $text; }

        &.active {
            background: $bg;
            color: $primary;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
    }
}

// 🆕 Переключатель вида
.view-controls {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding: 4px;
    background: $bg-secondary;
    border-radius: 10px;
    border: 1px solid $border;
    width: fit-content;

    .view-btn {
        flex: 1;
        padding: 8px 16px;
        border: none;
        background: transparent;
        border-radius: 8px;
        color: $text-muted;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;

        &.active {
            background: $bg;
            color: $primary;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
    }
}

// --- КОНТЕНТ ---
.page-content { padding: 16px; }
.category-section { margin-bottom: 32px; }

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    h4 { margin: 0; font-size: 1.1rem; font-weight: 700; color: $text; }

    .category-rule-badge {
        font-size: 0.75rem;
        color: $primary;
        background: $primary-light;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }
}

// 🆕 Сетка товаров
.products-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;

    @media (min-width: 768px) {
        grid-template-columns: repeat(3, 1fr);
    }
}

// 🆕 Список товаров
.products-list {
    display: flex;
    flex-direction: column;
    gap: 12px;

    // Принудительно растягиваем карточку на всю ширину в режиме списка
    // (предполагается, что у ProductCard есть корневой div, который займет 100%)
    > * {
        width: 100%;
    }
}

// --- ВАРИАНТЫ В КОРЗИНЕ ---
.variants-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.variant-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);

    .variant-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px dashed $border;

        .variant-number { font-weight: 700; color: $primary; font-size: 0.9rem; }

        .remove-btn {
            background: rgba($danger, 0.1);
            color: $danger;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            &:hover { background: $danger; color: white; }
        }
    }

    .variant-products {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 12px;
    }

    .variant-product-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;

        .vp-name { color: $text; flex: 1; padding-right: 8px; }
        .vp-price { color: $text-muted; font-weight: 600; white-space: nowrap; }
    }

    .variant-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 12px;
        border-top: 1px solid $border;
        font-weight: 700;
        font-size: 1rem;
        color: $text;

        .variant-total-price { color: $primary; font-size: 1.1rem; }
    }
}

// --- БЛОК СВОДКИ ---
.checkout-section {
    margin-top: 32px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
}

.checkout-title {
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 16px 0;
}

.selected-items-list {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px dashed $border;

    .selected-item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid $border;

        &:last-child { border-bottom: none; }

        .item-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding-right: 12px;

            .item-category {
                font-size: 0.75rem;
                color: $primary;
                font-weight: 600;
                margin-bottom: 2px;
                text-transform: uppercase;
            }
            .item-name {
                font-size: 0.9rem;
                color: $text;
                font-weight: 500;
            }
        }

        .item-actions {
            display: flex;
            align-items: center;
            gap: 12px;

            .item-price {
                font-weight: 700;
                color: $text;
                font-size: 0.95rem;
            }

            .remove-item-btn {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                border: none;
                background: rgba($danger, 0.1);
                color: $danger;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
                flex-shrink: 0;

                &:hover { background: $danger; color: white; }
            }
        }
    }
}

.footer-summary {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;

    .summary-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.95rem;

        .summary-label { color: $text-muted; }
        .summary-value { font-weight: 600; color: $text; }
        .summary-total { font-weight: 800; color: $primary; font-size: 1.2rem; }
    }
}

// 🆕 Контейнер для кнопок действий
.checkout-actions {
    display: flex;
    gap: 12px;
    flex-direction: column;
}

.clear-btn {
    padding: 16px 20px;
    background: transparent;
    border: 1px solid $border;
    border-radius: 14px;
    color: $text-muted;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        border-color: $danger;
        color: $danger;
        background: rgba($danger, 0.05);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.add-to-cart-btn {
    flex: 1;
    padding: 16px;
    background: $text-muted;
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: not-allowed;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;

    &.is-valid {
        background: linear-gradient(135deg, $primary, #8b5cf6);
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
        }
        &:active { transform: translateY(0); }
    }

    &:disabled:not(.is-valid) { opacity: 0.6; }
}

// --- EMPTY STATE ---
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $text-muted;

    i { font-size: 3.5rem; opacity: 0.2; margin-bottom: 16px; display: block; }
    p { font-size: 1rem; margin-bottom: 24px; }

    .go-build-btn {
        background: $primary;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        &:hover { opacity: 0.9; }
    }
}

// 🆕 Список товаров (компактный вид)
.products-list {
    display: flex;
    flex-direction: column;
    gap: 10px; // Небольшой отступ между строками
}
</style>
