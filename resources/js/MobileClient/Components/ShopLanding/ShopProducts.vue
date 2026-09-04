<template>
    <!-- Убрали v-if="hasCategories", чтобы секция всегда рендерилась и показывала красивый empty-state -->
    <section class="shop-products" id="shop-products-section">
        <div class="container">

            <!-- СОСТОЯНИЕ ЗАГРУЗКИ -->
            <div v-if="shopStore.isLoading" class="loading-state">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-3 text-muted">Шеф-повар готовит меню...</p>
            </div>

            <!-- ОСНОВНОЙ КОНТЕНТ -->
            <template v-else>

                <!-- 🆕 ПОЗИТИВНОЕ ПУСТОЕ СОСТОЯНИЕ: Нет категорий/товаров вообще -->
                <div v-if="!hasCategories" class="empty-state-positive">
                    <div class="empty-illustration">
                        <i class="fa-solid fa-hat-chef"></i>
                        <div class="floating-steam steam-1"></div>
                        <div class="floating-steam steam-2"></div>
                        <div class="floating-steam steam-3"></div>
                    </div>
                    <h3>Шеф-повар уже готовит новое меню! 👨‍🍳</h3>
                    <p>Мы обновляем наши блюда, чтобы порадовать вас чем-то особенным. <br>Загляните к нам чуть позже
                        или выберите другое заведение.</p>
                    <button class="btn-positive-action" @click="$emit('go-to-partners')">
                        <i class="fa-solid fa-store"></i> Посмотреть другие заведения
                    </button>
                </div>

                <!-- 🆕 ПОЗИТИВНОЕ ПУСТОЕ СОСТОЯНИЕ: Ничего не найдено по поиску -->
                <div v-else-if="visibleCategories.length === 0 && searchQueryLocal" class="empty-state-positive">
                    <div class="empty-illustration">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <h3>По вашему запросу ничего не найдено 🔍</h3>
                    <p>Возможно, опечатка? Попробуйте изменить запрос или посмотрите наше полное меню.</p>
                    <button class="btn-positive-action" @click="searchQueryLocal = ''">
                        <i class="fa-solid fa-rotate-left"></i> Сбросить поиск
                    </button>
                </div>

                <!-- ОБЫЧНЫЙ КОНТЕНТ (есть категории и товары) -->
                <template v-else>
                    <!-- ПОИСК -->
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="text"
                            v-model="searchQueryLocal"
                            placeholder="Поиск блюд, напитков..."
                            @input="onSearchInput"
                        >
                    </div>

                    <!-- ГРУППИРОВКА ПО КАТЕГОРИЯМ -->
                    <template v-for="cat in visibleCategories" :key="cat.id">
                        <div :id="`category-block-${cat.id}`" class="category-section">
                            <h2 class="category-title">
                                <i :class="cat.icon || 'fa-solid fa-utensils'"></i>
                                {{ cat.name }}
                            </h2>

                            <div class="products-grid">
                                <div
                                    v-for="product in getFilteredProducts(cat)"
                                    :key="product.id"
                                    class="product-card"
                                >
                                    <div class="product-image" @click="openModal(product)">
                                        <img v-lazy="preparedImageLink(product.images[0].url)" :alt="product.name" loading="lazy"
                                             @error="handleImageError">
                                        <div v-if="product.badge" class="product-badge">{{ product.badge }}</div>
                                    </div>

                                    <div class="product-info">
                                        <h3 class="product-name" @click="openModal(product)">{{ product.name }}</h3>

                                        <div class="product-price">
                                            <span class="current-price">{{ formatPrice(product.price) }} ₽</span>
                                            <span v-if="product.oldPrice"
                                                  class="old-price">{{ formatPrice(product.oldPrice) }} ₽</span>
                                        </div>

                                        <!-- УПРАВЛЕНИЕ КОРЗИНОЙ -->
                                        <div class="cart-controls">
                                            <template v-if="getProductQuantity(product.id) > 0">
                                                <div class="qty-selector-inline">
                                                    <button
                                                        class="qty-btn"
                                                        :disabled="basket.isProductLoading(product.id)"
                                                        @click.stop="handleDecrement(product.id)"
                                                    >
                                                        <span v-if="basket.isProductLoading(product.id)"
                                                              class="spinner-border spinner-border-sm"></span>
                                                        <span v-else>−</span>
                                                    </button>

                                                    <span class="qty-value">{{ getProductQuantity(product.id) }}</span>

                                                    <button
                                                        class="qty-btn"
                                                        :disabled="basket.isProductLoading(product.id)"
                                                        @click.stop="handleIncrement(product.id)"
                                                    >
                                                        <span v-if="basket.isProductLoading(product.id)"
                                                              class="spinner-border spinner-border-sm"></span>
                                                        <span v-else>+</span>
                                                    </button>
                                                </div>
                                            </template>

                                            <button
                                                v-else
                                                class="add-btn-card"
                                                :disabled="basket.isProductLoading(product.id)"
                                                @click.stop="handleAdd(product.id)"
                                            >
                                                <span v-if="basket.isProductLoading(product.id)"
                                                      class="spinner-border spinner-border-sm me-2"></span>
                                                <i v-else class="fa-solid fa-cart-plus"></i>
                                                {{ basket.isProductLoading(product.id) ? 'Добавляем...' : 'В корзину' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </template>
        </div>
    </section>
</template>
<script>
import {useShopLandingStore} from '@/MobileClient/stores/ShopLanding/shop';
import {useBasket} from '@/MobileClient/composables/useBasket';

export default {
    name: "ShopProducts",
    // 🆕 Добавляем событие для возврата к списку партнеров
    emits: ['open-modal', 'go-to-partners'],

    setup() {
        const basket = useBasket();
        return {
            shopStore: useShopLandingStore(),
            basket,
            basketItemsRef: basket.basket_items,
            productActionsRef: basket.productActions
        };
    },

    data() {
        return {
            searchQueryLocal: '',
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null
        },
        visibleCategories() {
            return this.shopStore.categories.filter(cat => this.getFilteredProducts(cat).length > 0);
        },
        hasCategories() {
            return this.shopStore.categories.some(cat => cat.products && cat.products.length > 0);
        }
    },

    methods: {
        preparedImageLink(img) {
            const base = "https://" + this.tenant.slug + ".mypwa.ru"
            return base + (img || '/logo.png');
        },
        getProductQuantity(productId) {
            const items = Array.isArray(this.basketItemsRef)
                ? this.basketItemsRef
                : (this.basketItemsRef?.value || []);
            const found = items.find(i =>
                i.product_id === productId ||
                (i.product && i.product.id === productId) ||
                i.id === productId
            );
            return found ? (found.count || found.quantity || 0) : 0;
        },

        isProductLoading(productId) {
            const actions = Array.isArray(this.productActionsRef)
                ? this.productActionsRef
                : (this.productActionsRef?.value || {});
            return !!actions[productId];
        },

        async handleAdd(productId) {
            try {
                await this.basket.addProduct(productId);
            } catch (error) {
                console.error('Не удалось добавить товар:', error);
            }
        },

        async handleIncrement(productId) {
            try {
                await this.basket.incrementQuantity(productId);
            } catch (error) {
                console.error('Не удалось увеличить количество:', error);
            }
        },

        async handleDecrement(productId) {
            try {
                await this.basket.decrementQuantity(productId);
            } catch (error) {
                console.error('Не удалось уменьшить количество:', error);
            }
        },

        getFilteredProducts(category) {
            if (!this.searchQueryLocal.trim()) return category.products || [];
            const q = this.searchQueryLocal.toLowerCase().trim();
            return (category.products || []).filter(p =>
                p.name.toLowerCase().includes(q) ||
                (p.description && p.description.toLowerCase().includes(q))
            );
        },

        openModal(product) {
            this.$emit('open-modal', product);
        },

        formatPrice(value) {
            return Number(value).toLocaleString('ru-RU');
        },

        handleImageError(e) {
            e.target.src = 'https://via.placeholder.com/500x500?text=Нет+фото';
        },

        onSearchInput() {
            // Debounce можно добавить здесь при необходимости
        }
    }
};
</script>

<style lang="scss" scoped>
/* Твои существующие стили остаются без изменений */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 0;
}

.search-bar {
    position: relative;
    max-width: 600px;
    margin: 0 auto 40px;

    i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray, #999);
    }

    input {
        width: 100%;
        padding: 14px 16px 14px 44px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        font-size: 1rem;
        background: white;
        transition: all 0.3s ease;

        &:focus {
            outline: none;
            border-color: var(--primary, #ff7a00);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.15);
        }
    }
}

.shop-products {
    padding: 40px 0 80px;
    background: var(--light, #f8f9fa);
}

.category-section {
    margin-bottom: 60px;
    scroll-margin-top: 130px;
}

.category-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--dark, #222);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;

    i {
        color: var(--primary, #ff7a00);
        font-size: 1.5rem;
    }
}

.load-more-wrapper {
    text-align: center;
    margin-top: 30px;
}

.load-more-btn {
    background: transparent;
    border: 2px solid var(--primary, #ff7a00);
    color: var(--primary, #ff7a00);
    padding: 12px 32px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover:not(:disabled) {
        background: var(--primary, #ff7a00);
        color: white;
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.products-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(4, 1fr);

    @media (max-width: 1400px) {
        grid-template-columns: repeat(4, 1fr);
    }
    @media (max-width: 1024px) {
        grid-template-columns: repeat(3, 1fr);
    }
    @media (max-width: 768px) {
        grid-template-columns: repeat(2, 1fr);
    }
    @media (max-width: 480px) {
        grid-template-columns: 1fr;
    }
}

.product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
    transition: all 0.4s ease;
    display: flex;
    flex-direction: column;

    &:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
}

.product-image {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    cursor: pointer;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    &:hover img {
        transform: scale(1.1);
    }
}

.product-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, var(--primary, #ff7a00) 0%, var(--primary-light, #ff9e42) 100%);
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    z-index: 2;
}

.product-info {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-name {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 0.8rem;
    color: var(--dark, #222);
    cursor: pointer;
    line-height: 1.3;

    &:hover {
        color: var(--primary, #ff7a00);
    }
}

.product-price {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1.2rem;
    margin-top: auto;
}

.current-price {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--primary, #ff7a00);
}

.old-price {
    font-size: 1rem;
    color: var(--gray, #999);
    text-decoration: line-through;
}

.add-btn-card {
    width: 100%;
    padding: 12px;
    background: var(--primary, #ff7a00);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;

    &:hover {
        background: var(--primary-dark, #e66e00);
        transform: translateY(-2px);
    }

    &:active {
        transform: translateY(0);
    }
}

.qty-selector-inline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--light, #f0f2f5);
    border-radius: 12px;
    padding: 4px;
    width: 100%;
}

.qty-btn {
    flex: 1;
    height: 38px;
    border: none;
    background: transparent;
    font-size: 1.2rem;
    font-weight: 700;
    cursor: pointer;
    border-radius: 8px;
    color: var(--dark, #222);
    transition: background 0.2s;

    &:hover {
        background: white;
    }
}

.qty-value {
    font-weight: 800;
    font-size: 1rem;
    min-width: 30px;
    text-align: center;
    color: var(--dark, #222);
}



.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 0;
}

.search-bar {
    position: relative;
    max-width: 600px;
    margin: 0 auto 40px;
}

.search-bar i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray, #999);
}

.search-bar input {
    width: 100%;
    padding: 14px 16px 14px 44px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    font-size: 1rem;
    background: white;
}

.shop-products {
    padding: 40px 0 80px;
    background: var(--light, #f8f9fa);
}

.category-section {
    margin-bottom: 60px;
    scroll-margin-top: 130px;
}

.category-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--dark, #222);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-title i {
    color: var(--primary, #ff7a00);
    font-size: 1.5rem;
}

.products-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(4, 1fr);
}

@media (max-width: 1400px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 1024px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
}

.product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
    transition: all 0.4s ease;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.product-image {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    cursor: pointer;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.product-image:hover img {
    transform: scale(1.1);
}

.product-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, var(--primary, #ff7a00) 0%, var(--primary-light, #ff9e42) 100%);
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    z-index: 2;
}

.product-info {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-name {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 0.8rem;
    color: var(--dark, #222);
    cursor: pointer;
    line-height: 1.3;
}

.product-name:hover {
    color: var(--primary, #ff7a00);
}

.product-price {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1.2rem;
    margin-top: auto;
}

.current-price {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--primary, #ff7a00);
}

.old-price {
    font-size: 1rem;
    color: var(--gray, #999);
    text-decoration: line-through;
}

.add-btn-card {
    width: 100%;
    padding: 12px;
    background: var(--primary, #ff7a00);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.add-btn-card:hover:not(:disabled) {
    background: var(--primary-dark, #e66e00);
    transform: translateY(-2px);
}

.add-btn-card:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.qty-selector-inline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--light, #f0f2f5);
    border-radius: 12px;
    padding: 4px;
    width: 100%;
}

.qty-btn {
    flex: 1;
    height: 38px;
    border: none;
    background: transparent;
    font-size: 1.2rem;
    font-weight: 700;
    cursor: pointer;
    border-radius: 8px;
    color: var(--dark, #222);
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover:not(:disabled) {
    background: white;
}

.qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.qty-value {
    font-weight: 800;
    font-size: 1rem;
    min-width: 30px;
    text-align: center;
    color: var(--dark, #222);
}



.qty-btn {
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    font-weight: 700;
    cursor: pointer;
    border-radius: 6px;
}

.qty-btn:hover {
    background: white;
}

/* ... ваши существующие стили ... */

/* 🆕 ПОЗИТИВНОЕ ПУСТОЕ СОСТОЯНИЕ */
.empty-state-positive {
    text-align: center;
    padding: 80px 20px;
    max-width: 500px;
    margin: 0 auto;
    animation: fadeInUp 0.6s ease-out;

    .empty-illustration {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 24px;
        background: rgba(var(--primary-rgb, 255, 122, 0), 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;

        i {
            font-size: 3.5rem;
            color: var(--primary, #ff7a00);
            animation: bounce 2s infinite ease-in-out;
        }

        /* Анимация пара для иконки шеф-повара */
        .floating-steam {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--primary, #ff7a00);
            border-radius: 50%;
            opacity: 0;
        }

        .steam-1 {
            top: 20%;
            left: 40%;
            animation: steam 2s infinite 0.2s;
        }

        .steam-2 {
            top: 15%;
            left: 50%;
            animation: steam 2s infinite 0.6s;
        }

        .steam-3 {
            top: 20%;
            left: 60%;
            animation: steam 2s infinite 1.0s;
        }
    }

    h3 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dark, #222);
        margin-bottom: 12px;
    }

    p {
        font-size: 1rem;
        color: var(--gray, #666);
        line-height: 1.6;
        margin-bottom: 32px;
    }

    .btn-positive-action {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 28px;
        background: var(--primary, #ff7a00);
        color: white;
        border: none;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);

        &:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 122, 0, 0.4);
            background: var(--primary-dark, #e66e00);
        }

        &:active {
            transform: translateY(-1px);
        }
    }
}

/* Анимации для пустого состояния */
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

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes steam {
    0% {
        transform: translateY(0) scale(1);
        opacity: 0.6;
    }
    100% {
        transform: translateY(-20px) scale(1.5);
        opacity: 0;
    }
}


</style>
