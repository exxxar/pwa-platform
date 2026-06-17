<template>
    <div class="grocery-order-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="grocery-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">🛒</div>
                    <div class="hero-sparkle sparkle-1">🍎</div>
                    <div class="hero-sparkle sparkle-2">🥦</div>
                </div>
                <h1 class="hero-title">Заказ продуктов</h1>
                <p class="hero-subtitle">Соберите корзину, а мы бережно доставим всё до двери</p>
            </div>
        </div>

        <div class="order-content">

            <!-- ========================================== -->
            <!-- ПОИСК И КАТЕГОРИИ -->
            <!-- ========================================== -->
            <div class="sticky-controls">
                <!-- Поиск -->
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="search-input"
                        placeholder="Найти продукт (например, молоко)..."
                    >
                    <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Категории -->
                <div class="categories-scroll">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        class="category-tab"
                        :class="{ 'active': activeCategory === cat.id }"
                        @click="activeCategory = cat.id"
                    >
                        <span class="tab-emoji">{{ cat.emoji }}</span>
                        <span class="tab-name">{{ cat.name }}</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- СПИСОК ПРОДУКТОВ -->
            <!-- ========================================== -->
            <div class="section">
                <div v-if="filteredProducts.length === 0" class="empty-search">
                    <div class="empty-icon">🔍</div>
                    <p>Ничего не найдено. Попробуйте изменить запрос.</p>
                </div>

                <div v-else class="products-list">
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="product-card"
                        :class="{ 'is-selected': getProductQty(product.id) > 0 }"
                    >
                        <div class="product-emoji">{{ product.emoji }}</div>

                        <div class="product-info">
                            <div class="product-name">{{ product.name }}</div>
                            <div class="product-price-row">
                                <span class="product-price">
                                    <span v-if="product.priceType === 'open'" class="open-price-badge" title="Цена по весу">≈</span>
                                    {{ formatPrice(product.price) }}
                                    <span class="product-unit">/ {{ product.unit }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="product-actions">
                            <div v-if="getProductQty(product.id) > 0" class="qty-stepper">
                                <button type="button" class="stepper-btn" @click="updateQty(product.id, -1)">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                <span class="qty-value">{{ getProductQty(product.id) }}</span>
                                <button type="button" class="stepper-btn" @click="updateQty(product.id, 1)">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                            <button v-else type="button" class="add-btn" @click="updateQty(product.id, 1)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Кнопка добавления своего товара -->
                <button class="add-custom-btn" @click="showCustomModal = true">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Добавить товар, которого нет в списке</span>
                </button>

            </div>

            <!-- ========================================== -->
            <!-- БЮДЖЕТ ЗАКАЗА -->
            <!-- ========================================== -->
            <div class="section budget-section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Лимит бюджета</h6>
                        <p class="section-subtitle">Максимальная сумма, которую мы можем списать</p>
                    </div>
                </div>

                <div class="budget-control">
                    <label class="budget-toggle">
                        <input type="checkbox" v-model="useBudgetLimit">
                        <span class="toggle-slider"></span>
                        <span class="toggle-text">Ограничить бюджет</span>
                    </label>

                    <div v-if="useBudgetLimit" class="budget-inputs">
                        <div class="budget-display" :class="{ 'over-budget': isOverBudget }">
                            <span class="budget-label">Текущая сумма:</span>
                            <span class="budget-value">{{ formatPrice(totalEstimatedPrice) }}</span>
                        </div>

                        <div class="budget-slider-wrapper">
                            <input
                                type="range"
                                v-model.number="maxBudget"
                                min="500"
                                max="10000"
                                step="100"
                                class="budget-range"
                            >
                            <div class="budget-range-labels">
                                <span>500 ₽</span>
                                <span>10 000 ₽</span>
                            </div>
                        </div>

                        <div class="budget-manual-input">
                            <span class="currency-symbol">₽</span>
                            <input
                                type="number"
                                v-model.number="maxBudget"
                                class="manual-budget-input"
                                placeholder="Своя сумма"
                            >
                        </div>

                        <div v-if="isOverBudget" class="budget-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>Сумма корзины превышает установленный лимит!</span>
                        </div>
                    </div>
                    <div v-else class="budget-unlimited">
                        <i class="fa-solid fa-infinity"></i>
                        <span>Бюджет не ограничен. Списание по фактическому весу/цене.</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КОММЕНТАРИЙ ДОСТАВЩИКУ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <i class="fa-solid fa-motorcycle"></i>
                    </div>
                    <div>
                        <h6 class="section-title">
                            Комментарий для курьера
                            <span class="required-mark">*</span>
                        </h6>
                        <p class="section-subtitle">Код домофона, этаж, оставить у двери или позвонить</p>
                    </div>
                </div>

                <div class="comment-wrapper" :class="{ 'has-error': commentError }">
                    <textarea
                        v-model="deliveryComment"
                        class="comment-input"
                        placeholder="Например: 3-й подъезд, 5 этаж, кв. 42. Если не отвечаю, позвоните в домофон..."
                        rows="3"
                        @input="commentError = false"
                    ></textarea>
                    <div v-if="commentError" class="error-message">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>Пожалуйста, укажите комментарий для курьера (мин. 5 символов)</span>
                    </div>
                </div>
            </div>

        </div>


        <!-- ========================================== -->
        <!-- STICKY ПАНЕЛЬ ИТОГА -->
        <!-- ========================================== -->
        <div class="summary-panel" :class="{ 'has-open-prices': hasOpenPricesInCart, 'is-over-budget': isOverBudget }">
            <div class="summary-content">
                <div class="summary-info">
                    <div class="summary-label">
                        {{ hasOpenPricesInCart ? 'Примерная стоимость' : 'Итого к оплате' }}
                    </div>
                    <div class="summary-price">{{ formatPrice(totalEstimatedPrice) }}</div>
                    <div class="summary-details">
                        {{ totalItemsCount }} {{ pluralize(totalItemsCount, 'позиция', 'позиции', 'позиций') }}
                        <span v-if="hasOpenPricesInCart" class="open-warning">
                            <i class="fa-solid fa-scale-balanced"></i> Точный вес при доставке
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    class="checkout-btn"
                    :disabled="!canCheckout"
                    @click="submitOrder"
                >
                    <span v-if="isOverBudget">Превышен бюджет</span>
                    <span v-else>Оформить заказ</span>
                    <i class="fa-solid" :class="isOverBudget ? 'fa-lock' : 'fa-arrow-right'"></i>
                </button>
            </div>
        </div>

        <CustomProductModal
            v-model="showCustomModal"
            @add-custom-product="handleCustomProduct"
        />
    </div>
</template>

<script>

import CustomProductModal from '@/MobileClient/Components/Food/CustomProductModal.vue';
export default {
    name: "GroceryOrder",
    components:{
        CustomProductModal
    },
    data() {
        return {
            showCustomModal: false, // <-- новое состояние
            customProducts: [], // <-- массив для хранения добавленных товаров

            searchQuery: '',
            activeCategory: 'all',
            useBudgetLimit: true,
            maxBudget: 3000,
            deliveryComment: '',
            commentError: false,
            cart: {}, // { productId: quantity }

            categories: [
                { id: 'all', name: 'Все', emoji: '🛒' },
                { id: 'fruits', name: 'Фрукты и овощи', emoji: '🍎' },
                { id: 'dairy', name: 'Молочное', emoji: '🥛' },
                { id: 'meat', name: 'Мясо и птица', emoji: '🍗' },
                { id: 'bakery', name: 'Выпечка', emoji: '🍞' },
                { id: 'drinks', name: 'Напитки', emoji: '🧃' },
            ],

            products: [
                // Молочное
                { id: 1, category: 'dairy', name: 'Молоко 3.2%', price: 89, unit: 'шт', priceType: 'fixed', emoji: '🥛' },
                { id: 2, category: 'dairy', name: 'Сыр Российский', price: 850, unit: 'кг', priceType: 'open', emoji: '🧀' },
                { id: 3, category: 'dairy', name: 'Яйца С0 (10 шт)', price: 120, unit: 'уп', priceType: 'fixed', emoji: '🥚' },

                // Выпечка
                { id: 4, category: 'bakery', name: 'Хлеб Бородинский', price: 65, unit: 'шт', priceType: 'fixed', emoji: '🍞' },
                { id: 5, category: 'bakery', name: 'Булочка с маком', price: 45, unit: 'шт', priceType: 'fixed', emoji: '🥐' },

                // Фрукты и овощи (открытая цена)
                { id: 6, category: 'fruits', name: 'Бананы', price: 130, unit: 'кг', priceType: 'open', emoji: '🍌' },
                { id: 7, category: 'fruits', name: 'Помидоры черри', price: 290, unit: 'кг', priceType: 'open', emoji: '🍅' },
                { id: 8, category: 'fruits', name: 'Яблоки Гренни', price: 160, unit: 'кг', priceType: 'open', emoji: '🍏' },

                // Мясо (открытая цена)
                { id: 9, category: 'meat', name: 'Куриное филе', price: 420, unit: 'кг', priceType: 'open', emoji: '🍗' },
                { id: 10, category: 'meat', name: 'Фарш домашний', price: 550, unit: 'кг', priceType: 'open', emoji: '🥩' },

                // Напитки
                { id: 11, category: 'drinks', name: 'Сок апельсиновый 1л', price: 150, unit: 'шт', priceType: 'fixed', emoji: '🧃' },
                { id: 12, category: 'drinks', name: 'Вода минеральная 0.5', price: 60, unit: 'шт', priceType: 'fixed', emoji: '💧' },
            ]
        };
    },

    computed: {
        allProducts() {
            return [...this.products, ...this.customProducts];
        },
        filteredProducts() {
            let result = this.allProducts;
            if (this.activeCategory !== 'all') {
                // Кастомные товары всегда показываем в категории 'all' или создаем для них виртуальную 'custom'
                if (this.activeCategory !== 'custom') {
                    result = result.filter(p => p.category === this.activeCategory);
                }
            }
            if (this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase();
                result = result.filter(p => p.name.toLowerCase().includes(query));
            }
            return result;
        },

        totalItemsCount() {
            return Object.values(this.cart).reduce((sum, qty) => sum + qty, 0);
        },

        totalEstimatedPrice() {
            return Object.entries(this.cart).reduce((sum, [productId, qty]) => {
                const product = this.products.find(p => p.id === Number(productId));
                return sum + (product ? product.price * qty : 0);
            }, 0);
        },

        hasOpenPricesInCart() {
            return Object.keys(this.cart).some(productId => {
                const product = this.products.find(p => p.id === Number(productId));
                return product && product.priceType === 'open';
            });
        },

        isOverBudget() {
            if (!this.useBudgetLimit) return false;
            return this.totalEstimatedPrice > this.maxBudget;
        },

        canCheckout() {
            return (
                this.totalItemsCount > 0 &&
                this.deliveryComment.trim().length >= 5 &&
                !this.isOverBudget
            );
        }
    },

    methods: {
        // Метод, который принимает данные из модалки
        handleCustomProduct(product) {
            // 1. Добавляем в список доступных товаров (чтобы он отобразился в списке)
            this.customProducts.push(product);

            // 2. Сразу добавляем его в корзину с количеством, которое указал пользователь
            this.cart[product.id] = product.qty;

            // 3. Показываем уведомление
            this.$notify?.({
                title: 'Товар добавлен',
                text: `"${product.name}" добавлен в ваш список`,
                type: 'success'
            });
        },

        getProductQty(productId) {
            return this.cart[productId] || 0;
        },

        updateQty(productId, delta) {
            const currentQty = this.getProductQty(productId);
            const newQty = currentQty + delta;

            if (newQty <= 0) {
                delete this.cart[productId];
            } else {
                this.cart[productId] = newQty;
            }
        },

        submitOrder() {
            if (!this.canCheckout) {
                if (this.deliveryComment.trim().length < 5) {
                    this.commentError = true;
                    document.querySelector('.comment-wrapper')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            const orderData = {
                items: Object.entries(this.cart).map(([id, qty]) => ({
                    productId: Number(id),
                    quantity: qty,
                    product: this.products.find(p => p.id === Number(id))
                })),
                comment: this.deliveryComment,
                estimatedTotal: this.totalEstimatedPrice,
                maxBudget: this.useBudgetLimit ? this.maxBudget : null,
                hasVariableWeight: this.hasOpenPricesInCart
            };

            console.log('GROCERY ORDER SUBMITTED:', orderData);

            this.$notify?.({
                title: 'Заказ оформлен!',
                text: 'Мы бережно соберём продукты в рамках вашего бюджета.',
                type: 'success',
            });

            // Сброс
            this.cart = {};
            this.deliveryComment = '';
            this.searchQuery = '';
            this.activeCategory = 'all';
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price);
        },

        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        }
    }
};
</script>

<style scoped>
.add-custom-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    margin-top: 16px;
    background: rgba(67, 233, 123, 0.08);
    border: 2px dashed #43e97b;
    border-radius: 16px;
    color: #2cb55e;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.add-custom-btn:hover {
    background: rgba(67, 233, 123, 0.15);
    transform: translateY(-2px);
}

/* Стилизуем кастомные товары в списке иначе, чтобы пользователь их видел */
.product-card.is-custom {
    border-color: #4facfe;
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.05) 0%, transparent 100%);
}
.product-card.is-custom .product-emoji {
    /* Можно добавить небольшой бейдж "Ваш товар" */
}

.grocery-order-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 140px;
}

/* HERO (как в предыдущей версии) */
.grocery-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}
.hero-background { position: absolute; inset: 0; background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.15) 0%, transparent 50%); }
.hero-content { position: relative; z-index: 1; }
.hero-icon-wrapper { position: relative; display: inline-block; margin-bottom: 16px; }
.hero-icon { font-size: 4rem; animation: bounce 3s ease-in-out infinite; }
.hero-sparkle { position: absolute; font-size: 1.5rem; animation: float 3s ease-in-out infinite; }
.sparkle-1 { top: -10px; right: -15px; animation-delay: 0s; }
.sparkle-2 { bottom: -10px; left: -15px; animation-delay: 1s; }
@keyframes bounce { 0%, 100% { transform: translateY(0) rotate(-5deg); } 50% { transform: translateY(-10px) rotate(5deg); } }
@keyframes float { 0%, 100% { transform: translateY(0) scale(1); opacity: 0.8; } 50% { transform: translateY(-8px) scale(1.1); opacity: 1; } }
.hero-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; text-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.hero-subtitle { font-size: 0.95rem; opacity: 0.95; margin: 0; }

.order-content { padding: 20px 16px; }
.section { margin-bottom: 28px; }
.section-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
.section-icon { width: 44px; height: 44px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.section-title { margin: 0; font-weight: 700; font-size: 1.05rem; color: var(--bs-body-color); }
.section-subtitle { margin: 4px 0 0; font-size: 0.75rem; color: var(--bs-secondary-color); line-height: 1.4; }
.open-price-badge { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; background: #ff9800; color: white; font-size: 0.7rem; font-weight: 700; margin-right: 4px; vertical-align: middle; }
.required-mark { color: #dc3545; }

/* ==========================================
   ПОИСК И КАТЕГОРИИ (Sticky)
   ========================================== */
.sticky-controls {
    position: sticky;
    top: 0;
    z-index: 50;
    background: var(--bs-body-bg);
    padding: 12px 0 16px;
    margin: -20px -16px 20px;
    border-bottom: 1px solid var(--bs-border-color);
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.search-wrapper {
    position: relative;
    margin: 0 16px 12px;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--bs-secondary-color);
}

.search-input {
    width: 100%;
    padding: 12px 40px 12px 42px;
    background: var(--bs-secondary-bg);
    border: 2px solid transparent;
    border-radius: 12px;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    outline: none;
    transition: all 0.2s ease;
}

.search-input:focus {
    background: var(--bs-body-bg);
    border-color: #43e97b;
    box-shadow: 0 0 0 4px rgba(67, 233, 123, 0.1);
}

.search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bs-border-color);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.7rem;
}

.categories-scroll {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 0 16px;
    scrollbar-width: none;
}
.categories-scroll::-webkit-scrollbar { display: none; }

.category-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: var(--bs-secondary-bg);
    border: 2px solid transparent;
    border-radius: 20px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.85rem;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
}

.category-tab.active {
    background: rgba(67, 233, 123, 0.1);
    border-color: #43e97b;
    color: #2cb55e;
}

.tab-emoji { font-size: 1.1rem; }

/* ==========================================
   ПРОДУКТЫ
   ========================================== */
.empty-search {
    text-align: center;
    padding: 40px 20px;
    color: var(--bs-secondary-color);
}
.empty-icon { font-size: 3rem; margin-bottom: 12px; opacity: 0.5; }

.products-list { display: flex; flex-direction: column; gap: 10px; }
.product-card {
    display: flex; align-items: center; gap: 14px;
    padding: 14px; background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color); border-radius: 16px;
    transition: all 0.2s ease;
}
.product-card.is-selected {
    border-color: #43e97b;
    background: linear-gradient(135deg, rgba(67, 233, 123, 0.05) 0%, transparent 100%);
}
.product-emoji { font-size: 2rem; line-height: 1; }
.product-info { flex: 1; min-width: 0; }
.product-name { font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); margin-bottom: 4px; }
.product-price { font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); display: flex; align-items: baseline; gap: 4px; }
.product-unit { font-size: 0.75rem; color: var(--bs-secondary-color); font-weight: 400; }

.product-actions { flex-shrink: 0; }
.add-btn {
    width: 40px; height: 40px; border-radius: 50%;
    background: #43e97b; border: none; color: white;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(67, 233, 123, 0.3);
}
.add-btn:hover { transform: scale(1.1); }

.qty-stepper {
    display: flex; align-items: center; gap: 8px;
    background: var(--bs-body-bg); border: 2px solid #43e97b;
    border-radius: 20px; padding: 4px;
}
.stepper-btn {
    width: 32px; height: 32px; border-radius: 50%;
    background: #43e97b; border: none; color: white;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.2s ease;
}
.stepper-btn:hover { background: #38d96f; }
.qty-value { font-weight: 800; font-size: 1rem; color: var(--bs-body-color); min-width: 24px; text-align: center; }

/* ==========================================
   БЮДЖЕТ
   ========================================== */
.budget-control {
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
}

.budget-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    margin-bottom: 16px;
}

.budget-toggle input { display: none; }

.toggle-slider {
    width: 44px;
    height: 24px;
    background: var(--bs-border-color);
    border-radius: 12px;
    position: relative;
    transition: background 0.3s ease;
}

.toggle-slider::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.budget-toggle input:checked + .toggle-slider {
    background: #4facfe;
}

.budget-toggle input:checked + .toggle-slider::after {
    transform: translateX(20px);
}

.toggle-text {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.budget-inputs {
    display: flex;
    flex-direction: column;
    gap: 16px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.budget-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
}

.budget-display.over-budget {
    background: rgba(220, 53, 69, 0.1);
}

.budget-label { font-size: 0.85rem; color: var(--bs-secondary-color); }
.budget-value { font-size: 1.2rem; font-weight: 800; color: #43e97b; }
.budget-display.over-budget .budget-value { color: #dc3545; }

.budget-slider-wrapper { padding: 0 4px; }

.budget-range {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: var(--bs-border-color);
    outline: none;
    -webkit-appearance: none;
}

.budget-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #4facfe;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(79, 172, 254, 0.4);
}

.budget-range-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-top: 6px;
}

.budget-manual-input {
    position: relative;
    display: flex;
    align-items: center;
}

.currency-symbol {
    position: absolute;
    left: 14px;
    color: var(--bs-secondary-color);
    font-weight: 600;
}

.manual-budget-input {
    width: 100%;
    padding: 12px 14px 12px 32px;
    background: var(--bs-secondary-bg);
    border: 2px solid transparent;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-body-color);
    outline: none;
}

.manual-budget-input:focus {
    border-color: #4facfe;
    background: var(--bs-body-bg);
}

.budget-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.2);
    border-radius: 10px;
    color: #dc3545;
    font-size: 0.8rem;
    font-weight: 600;
}

.budget-unlimited {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: rgba(67, 233, 123, 0.1);
    border-radius: 10px;
    color: #2cb55e;
    font-size: 0.9rem;
}

/* ==========================================
   КОММЕНТАРИЙ (как в предыдущей версии)
   ========================================== */
.comment-wrapper { position: relative; }
.comment-wrapper.has-error .comment-input { border-color: #dc3545; box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1); }
.comment-input {
    width: 100%; padding: 14px 16px;
    background: var(--bs-body-bg); border: 2px solid var(--bs-border-color);
    border-radius: 14px; color: var(--bs-body-color);
    font-size: 0.95rem; font-family: inherit; line-height: 1.5;
    outline: none; resize: none; transition: all 0.2s ease;
}
.comment-input:focus { border-color: #fa709a; box-shadow: 0 0 0 4px rgba(250, 112, 154, 0.1); }
.comment-input::placeholder { color: var(--bs-secondary-color); }
.error-message { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 0.8rem; color: #dc3545; animation: shake 0.4s ease; }
@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

/* ==========================================
   STICKY ПАНЕЛЬ
   ========================================== */
.summary-panel {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: var(--bs-body-bg);
    border-top: 1px solid var(--bs-border-color);
    box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
    z-index: 100; padding: 16px;
    transition: all 0.3s ease;
}
.summary-panel.has-open-prices { border-top-color: #ff9800; }
.summary-panel.is-over-budget {
    border-top-color: #dc3545;
    background: linear-gradient(to top, rgba(220, 53, 69, 0.05) 0%, var(--bs-body-bg) 100%);
}

.summary-content { display: flex; align-items: center; justify-content: space-between; gap: 16px; max-width: 600px; margin: 0 auto; }
.summary-info { flex: 1; }
.summary-label { font-size: 0.75rem; color: var(--bs-secondary-color); margin-bottom: 2px; }
.summary-price { font-size: 1.6rem; font-weight: 800; color: #43e97b; line-height: 1; }
.summary-panel.is-over-budget .summary-price { color: #dc3545; }

.summary-details { font-size: 0.75rem; color: var(--bs-secondary-color); margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.open-warning { display: inline-flex; align-items: center; gap: 4px; color: #ff9800; font-weight: 600; background: rgba(255, 152, 0, 0.1); padding: 2px 8px; border-radius: 6px; }

.checkout-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 16px 24px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border: none; border-radius: 14px; color: white;
    font-weight: 700; font-size: 1rem; cursor: pointer;
    transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(67, 233, 123, 0.3);
}
.summary-panel.is-over-budget .checkout-btn {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    box-shadow: 0 4px 16px rgba(220, 53, 69, 0.3);
}
.checkout-btn:hover:not(:disabled) { transform: translateY(-2px); }
.checkout-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Адаптив */
@media (max-width: 576px) {
    .hero-title { font-size: 1.5rem; }
    .summary-price { font-size: 1.4rem; }
    .checkout-btn span { display: none; }
    .checkout-btn { padding: 16px; }
}
</style>
