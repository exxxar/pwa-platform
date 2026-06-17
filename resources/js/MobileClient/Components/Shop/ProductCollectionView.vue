<template>
    <div v-if="product" class="collection-card">

        <!-- ========================================== -->
        <!-- ШАПКА ТОВАРА -->
        <!-- ========================================== -->
        <div class="collection-header">
            <div class="header-image">
                <img
                    :src="product.image ? `/images-by-company-id/${bot.company.id}/${product.image}` : '/images/default-product-image.jpg'"
                    :alt="product.title"
                >
                <div v-if="product.discount > 0" class="discount-badge">
                    -{{ product.discount }}%
                </div>
            </div>

            <div class="header-info">
                <h2 class="product-title">{{ product.title || 'Подборка товаров' }}</h2>
                <p class="product-description">{{ product.description || 'Составьте свою идеальную подборку' }}</p>

                <div class="price-block">
                    <div class="price-current">
                        {{ formatPrice(finalPrice) }}
                    </div>
                    <div v-if="product.discount > 0" class="price-old">
                        {{ formatPrice(summaryPrice) }}
                    </div>
                    <div v-if="product.products?.length > 0" class="price-hint">
                        За {{ product.products.length }} {{ pluralize(product.products.length, 'товар', 'товара', 'товаров') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КАТЕГОРИИ ТОВАРОВ -->
        <!-- ========================================== -->
        <div class="categories-section">

            <div
                v-for="cat in preparedCategories"
                :key="cat.id"
                class="category-block"
            >
                <!-- Заголовок категории -->
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="category-info">
                        <h3 class="category-title">{{ cat.title || 'Без категории' }}</h3>
                        <p v-if="cat.products.length > 1" class="category-hint">
                            Выберите {{ canChooseMultiple(cat) ? 'один или несколько' : 'один' }} вариант
                        </p>
                    </div>
                    <div v-if="cat.selectedCount > 0" class="category-counter">
                        {{ cat.selectedCount }} выбрано
                    </div>
                </div>

                <!-- Сетка товаров категории -->
                <div class="products-grid">
                    <button
                        v-for="subProduct in cat.products"
                        :key="subProduct.id"
                        type="button"
                        class="subproduct-card"
                        :class="{
                            'is-selected': subProduct.is_checked,
                            'is-disabled': !canSelectSubProduct(subProduct, cat)
                        }"
                        :disabled="!canSelectSubProduct(subProduct, cat)"
                        @click="toggleSubProduct(subProduct, cat)"
                    >
                        <div class="subproduct-image">
                            <img
                                :src="subProduct.image ? `/images-by-company-id/${bot.company.id}/${subProduct.image}` : '/images/default-product-image.jpg'"
                                :alt="subProduct.title"
                            >
                            <div v-if="subProduct.is_checked" class="check-mark">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </div>
                        <div class="subproduct-info">
                            <div class="subproduct-title">{{ subProduct.title }}</div>
                            <div class="subproduct-price">{{ formatPrice(subProduct.current_price || 0) }}</div>
                        </div>
                    </button>

                    <!-- Кнопка "Пропустить категорию" -->
                    <button
                        v-if="canSkipCategory"
                        type="button"
                        class="skip-category-btn"
                        @click="cancelCategory(cat)"
                    >
                        <div class="skip-icon">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <div class="skip-info">
                            <div class="skip-title">Пропустить</div>
                            <div class="skip-hint">Не интересует</div>
                        </div>
                    </button>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- НИЖНЯЯ ПАНЕЛЬ -->
        <!-- ========================================== -->
        <div class="bottom-panel">
            <div class="panel-summary">
                <div class="summary-label">Итого к оплате</div>
                <div class="summary-price">
                    <span class="price-final">{{ formatPrice(finalPrice) }}</span>
                    <span v-if="product.discount > 0" class="price-was">{{ formatPrice(summaryPrice) }}</span>
                </div>
                <div v-if="selectedProductsCount > 0" class="summary-hint">
                    Выбрано {{ selectedProductsCount }} {{ pluralize(selectedProductsCount, 'товар', 'товара', 'товаров') }}
                </div>
            </div>

            <button
                type="button"
                class="add-to-cart-btn"
                :class="{
                    'is-disabled': !canAddToCart,
                    'is-loading': sending
                }"
                :disabled="!canAddToCart || sending"
                @click="addCollectionToCart"
            >
                <span v-if="sending" class="btn-spinner"></span>
                <template v-else>
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>В корзину</span>
                    <span v-if="cartCollections.length > 0" class="cart-badge">{{ cartCollections.length }}</span>
                </template>
            </button>
        </div>

    </div>
</template>

<script>
import ProductCard from "@/MobileClient/Components/Shop/ProductCard.vue";

export default {
    name: "CollectionCard",

    components: {
        ProductCard,
    },

    props: {
        item: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            product: null,
            sending: false,
            isOnline: true,
            localChecked: {}, // { productId: true/false } - локальное состояние выбора
        };
    },

    computed: {

        tenant() {
            return window.Tenant || null;
        },

        self() {
            return window.TenantUser || null;
        },

        canProductAction() {
            return this.isOnline && !this.sending;
        },

        canAddToCart() {
            return (
                this.canProductAction &&
                this.summaryPrice > 0 &&
                !this.product?.in_stop_list_at &&
                this.selectedProductsCount > 0
            );
        },

        summaryPrice() {
            if (!this.product?.products) return 0;
            return this.product.products.reduce((sum, product) => {
                return sum + (this.isProductChecked(product) ? (product.current_price || 0) : 0);
            }, 0);
        },

        finalPrice() {
            const discount = this.product?.discount || 0;
            if (discount === 0) return this.summaryPrice;
            return Math.round(this.summaryPrice * (1 - discount / 100));
        },

        selectedProductsCount() {
            if (!this.product?.products) return 0;
            return this.product.products.filter(p => this.isProductChecked(p)).length;
        },

        canSkipCategory() {
            return this.product?.config?.can_skip_categories || false;
        },

        preparedCategories() {
            if (!this.product?.products) return [];

            const categoriesMap = new Map();
            let hasCategories = false;

            // Группируем товары по категориям
            this.product.products.forEach(product => {
                const category = product.categories?.[0];

                if (category) {
                    hasCategories = true;
                    if (!categoriesMap.has(category.id)) {
                        categoriesMap.set(category.id, {
                            id: category.id,
                            title: category.title,
                            products: [],
                        });
                    }
                    categoriesMap.get(category.id).products.push(product);
                }
            });

            // Если категорий нет — создаём одну общую
            if (!hasCategories) {
                categoriesMap.set('default', {
                    id: 'default',
                    title: 'Товары подборки',
                    products: [...this.product.products],
                });
            }

            // Добавляем счётчики выбранных
            const categories = Array.from(categoriesMap.values());
            return categories.map(cat => ({
                ...cat,
                selectedCount: cat.products.filter(p => this.isProductChecked(p)).length,
            }));
        },
    },

    watch: {
        item: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.initProduct(newVal);
                }
            },
        },
    },

    mounted() {
        window.addEventListener('online', this.handleOnline);
        window.addEventListener('offline', this.handleOffline);
    },

    beforeUnmount() {
        window.removeEventListener('online', this.handleOnline);
        window.removeEventListener('offline', this.handleOffline);
    },

    methods: {
        initProduct(item) {
            // Глубокое клонирование, чтобы не мутировать оригинал
            this.product = JSON.parse(JSON.stringify(item));

            // Инициализируем состояние выбора на основе конфига
            this.initializeSelection();
        },

        initializeSelection() {
            if (!this.product?.products) return;

            const config = this.product.config || {};
            const chooseAll = config.choose_all_in_category || false;

            // Группируем по категориям для инициализации
            const categoriesMap = new Map();
            this.product.products.forEach(product => {
                const categoryId = product.categories?.[0]?.id || 'default';
                if (!categoriesMap.has(categoryId)) {
                    categoriesMap.set(categoryId, []);
                }
                categoriesMap.get(categoryId).push(product);
            });

            // Для каждой категории выбираем первый товар (или все, если chooseAll)
            categoriesMap.forEach((products) => {
                products.forEach((product, index) => {
                    // Используем локальное состояние вместо мутации пропа
                    const productId = product.id;
                    if (chooseAll) {
                        this.$set ? this.$set(this.localChecked, productId, true) : (this.localChecked[productId] = true);
                    } else {
                        this.$set ? this.$set(this.localChecked, productId, index === 0) : (this.localChecked[productId] = index === 0);
                    }
                });
            });
        },

        isProductChecked(product) {
            return !!this.localChecked[product.id];
        },

        canChooseMultiple(category) {
            return this.product?.config?.choose_all_in_category || false;
        },

        canSelectSubProduct(subProduct, category) {
            // Если можно выбирать несколько — всегда можно
            if (this.canChooseMultiple(category)) return true;

            // Если уже выбран — можно снять
            if (this.isProductChecked(subProduct)) return true;

            // Если ничего не выбрано в категории — можно выбрать
            if (category.selectedCount === 0) return true;

            // Иначе нельзя (только один товар в категории)
            return false;
        },

        toggleSubProduct(subProduct, category) {
            const isChecked = this.isProductChecked(subProduct);
            const productId = subProduct.id;

            if (!this.canChooseMultiple(category)) {
                // Снимаем выбор со всех товаров в категории
                category.products.forEach(p => {
                    if (this.$set) {
                        this.$set(this.localChecked, p.id, false);
                    } else {
                        this.localChecked[p.id] = false;
                    }
                });
            }

            // Переключаем текущий
            if (this.$set) {
                this.$set(this.localChecked, productId, !isChecked);
            } else {
                this.localChecked[productId] = !isChecked;
            }
        },

        cancelCategory(category) {
            // Снимаем выбор со всех товаров в категории
            category.products.forEach(product => {
                if (this.$set) {
                    this.$set(this.localChecked, product.id, false);
                } else {
                    this.localChecked[product.id] = false;
                }
            });

            this.$notify?.({
                title: "Подборка товара",
                text: `Категория "${category.title}" исключена`,
                type: 'info',
            });
        },

        async addCollectionToCart() {
            if (!this.canAddToCart) return;

            this.sending = true;

            try {
                // Создаём копию продукта с актуальным состоянием выбора
                const productToCart = {
                    ...this.product,
                    products: this.product.products.map(p => ({
                        ...p,
                        is_checked: this.isProductChecked(p),
                    })),
                };

                await this.$store.dispatch("addCollectionToCart", productToCart);

                this.$notify?.({
                    title: "Корзина",
                    text: 'Подборка добавлена в корзину',
                    type: 'success',
                });
            } catch (err) {
                console.error('Ошибка добавления подборки:', err);
                this.$notify?.({
                    title: "Ошибка",
                    text: 'Не удалось добавить подборку',
                    type: 'error',
                });
            } finally {
                this.sending = false;
            }
        },

        handleOnline() {
            this.isOnline = true;
        },

        handleOffline() {
            this.isOnline = false;
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0);
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
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #6366f1;
$primary-light: #818cf8;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.collection-card {
    background: $bg;
    min-height: 100vh;
    padding-bottom: 160px; // Отступ под нижнюю панель
}

// ==========================================
// ШАПКА ТОВАРА
// ==========================================
.collection-header {
    background: $card-bg;
    padding: 20px;
    border-bottom: 1px solid $border;
    display: flex;
    gap: 20px;
}

.header-image {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 16px;
    overflow: hidden;
    flex-shrink: 0;
    background: $bg;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.discount-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 10px;
    background: $danger;
    color: white;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
}

.header-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.product-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: $text;
    margin: 0;
    line-height: 1.3;
}

.product-description {
    font-size: 0.9rem;
    color: $text-muted;
    margin: 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.price-block {
    margin-top: auto;
}

.price-current {
    font-size: 1.5rem;
    font-weight: 800;
    color: $primary;
    line-height: 1;
}

.price-old {
    font-size: 0.9rem;
    color: $text-muted;
    text-decoration: line-through;
    margin-top: 2px;
}

.price-hint {
    font-size: 0.8rem;
    color: $text-muted;
    margin-top: 4px;
}

// ==========================================
// КАТЕГОРИИ
// ==========================================
.categories-section {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.category-block {
    background: $card-bg;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;
}

.category-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.category-info {
    flex: 1;
    min-width: 0;
}

.category-title {
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 2px 0;
}

.category-hint {
    font-size: 0.8rem;
    color: $text-muted;
    margin: 0;
}

.category-counter {
    padding: 4px 10px;
    background: rgba($success, 0.1);
    color: $success;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

// ==========================================
// СЕТКА ТОВАРОВ
// ==========================================
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
}

.subproduct-card {
    position: relative;
    background: $card-bg;
    border: 2px solid $border;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    padding: 0;
    display: flex;
    flex-direction: column;

    &:hover:not(.is-disabled) {
        border-color: $primary-light;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba($primary, 0.15);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.04);
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }

    &.is-disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.subproduct-image {
    position: relative;
    aspect-ratio: 1;
    background: $bg;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.check-mark {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: $primary;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    box-shadow: 0 2px 8px rgba($primary, 0.4);
    animation: checkPop 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes checkPop {
    0% { transform: scale(0); }
    70% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.subproduct-info {
    padding: 10px;
}

.subproduct-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
    line-height: 1.3;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.2em;
}

.subproduct-price {
    font-size: 0.9rem;
    font-weight: 700;
    color: $primary;
}

// ==========================================
// КНОПКА "ПРОПУСТИТЬ КАТЕГОРИЮ"
// ==========================================
.skip-category-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    background: $bg;
    border: 2px dashed $border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;

    &:hover {
        border-color: $danger;
        background: rgba($danger, 0.04);
    }
}

.skip-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba($danger, 0.1);
    color: $danger;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.skip-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.skip-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
}

.skip-hint {
    font-size: 0.75rem;
    color: $text-muted;
}

// ==========================================
// НИЖНЯЯ ПАНЕЛЬ
// ==========================================
.bottom-panel {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: $card-bg;
    border-top: 1px solid $border;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    padding: 16px 20px;
    z-index: 100;
    display: flex;
    align-items: center;
    gap: 16px;
}

.panel-summary {
    flex: 1;
    min-width: 0;
}

.summary-label {
    font-size: 0.75rem;
    color: $text-muted;
    margin-bottom: 2px;
}

.summary-price {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.price-final {
    font-size: 1.5rem;
    font-weight: 800;
    color: $text;
    line-height: 1;
}

.price-was {
    font-size: 0.9rem;
    color: $text-muted;
    text-decoration: line-through;
}

.summary-hint {
    font-size: 0.75rem;
    color: $success;
    margin-top: 4px;
    font-weight: 600;
}

.add-to-cart-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba($primary, 0.3);
    white-space: nowrap;
    position: relative;

    &:hover:not(.is-disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($primary, 0.4);
    }

    &.is-disabled {
        background: #d1d5db;
        box-shadow: none;
        cursor: not-allowed;
    }

    &.is-loading {
        pointer-events: none;
    }
}

.btn-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.cart-badge {
    padding: 2px 8px;
    background: $success;
    color: white;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-left: 4px;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .collection-header {
        flex-direction: column;
        align-items: stretch;
    }

    .header-image {
        width: 100%;
        height: 200px;
    }

    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .bottom-panel {
        flex-direction: column;
        gap: 12px;
        padding: 12px 16px;
    }

    .panel-summary {
        width: 100%;
        text-align: center;
    }

    .add-to-cart-btn {
        width: 100%;
    }

    .collection-card {
        padding-bottom: 200px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .subproduct-info {
        padding: 8px;
    }

    .subproduct-title {
        font-size: 0.8rem;
    }

    .subproduct-price {
        font-size: 0.85rem;
    }

    .category-header {
        flex-wrap: wrap;
    }

    .category-counter {
        width: 100%;
        text-align: center;
        margin-top: 8px;
    }
}
</style>
