<template>
    <section class="shop-products">
        <div class="container">
            <!-- ПОИСК -->
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Поиск товаров..."
                >
            </div>

            <div class="products-grid">
                <div v-for="product in filteredProducts" :key="product.id" class="product-card">
                    <div class="product-image" @click="openModal(product)">
                        <img v-lazy="product.image" :alt="product.name" loading="lazy">
                        <div v-if="product.badge" class="product-badge">{{ product.badge }}</div>
                    </div>

                    <div class="product-info">
                        <h3 class="product-name" @click="openModal(product)">{{ product.name }}</h3>
                        <div class="product-price">
                            <span class="current-price">{{ product.price }} ₽</span>
                            <span v-if="product.oldPrice" class="old-price">{{ product.oldPrice }} ₽</span>
                        </div>

                        <!-- УПРАВЛЕНИЕ КОРЗИНОЙ -->
                        <div class="cart-controls">
                            <template v-if="cartStore.getItemQuantity(product.id) > 0">
                                <div class="qty-selector-inline">
                                    <button class="qty-btn" @click="cartStore.updateQuantity(product.id, -1)">−</button>
                                    <span class="qty-value">{{ cartStore.getItemQuantity(product.id) }}</span>
                                    <button class="qty-btn" @click="cartStore.updateQuantity(product.id, 1)">+</button>
                                </div>
                            </template>
                            <button v-else class="add-btn-card" @click="cartStore.addItem(product)">
                                <i class="fa-solid fa-cart-plus"></i> В корзину
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="filteredProducts.length === 0" class="empty-products">
                <i class="fa-solid fa-basket-shopping"></i>
                <p>{{ searchQuery ? 'Ничего не найдено' : 'В этой категории пока нет товаров' }}</p>
            </div>
        </div>
    </section>
</template>

<script>

import {useShopLandingStore} from '@/MobileClient/stores/ShopLanding/shop';
import {useLandingCartStore} from "@/MobileClient/stores/ShopLanding/cart"; // Укажите правильный путь

export default {
    name: "ShopProducts",
    props: {
        products: Array,
        config: Object,
    },
    emits: ['add-to-cart', 'open-modal'],
    data() {
        return {
            searchQuery: '',
            cartStore: useLandingCartStore(),
            shopStore: useShopLandingStore(),
        }
    },
    computed:{
        filteredProducts() {
            if (!this.searchQuery.trim()) return this.products;
            const q = this.searchQuery.toLowerCase();
            return this.products.filter(p => p.name.toLowerCase().includes(q));
        },
    },
    methods: {
        openModal(product) {
            this.$emit('open-modal', product);
        },
        addToCart(product) {
            // Здесь можно либо эмитить событие наверх, либо сразу добавлять в store корзины
            // Например: cartStore.addItem(product)
            console.log('Добавлено в корзину:', product.name);
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

<style lang="scss" scoped>

.search-bar {
    position: relative;
    max-width: 600px;
    margin: 0 auto 40px;

    i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--gray); }

    input {
        width: 100%;
        padding: 14px 16px 14px 44px;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 14px;
        font-size: 1rem;
        background: white;

        &:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,122,0,0.15);
        }
    }
}

.shop-products {
    padding: 80px 0;
    background: var(--light);
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 1rem;
    color: var(--dark);
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--gray);
}

.products-grid {
    display: grid;
    gap: 1.5rem; /* Отступ между карточками */

    /* РОВНО 5 КОЛОНОК для больших экранов */
    grid-template-columns: repeat(4, 1fr);

    /* АДАПТИВНОСТЬ: чтобы на маленьких экранах не сплющивалось */
    @media (max-width: 1400px) {
        grid-template-columns: repeat(4, 1fr); /* 4 в ряд на ноутбуках */
    }

    @media (max-width: 1024px) {
        grid-template-columns: repeat(3, 1fr); /* 3 в ряд на планшетах */
    }

    @media (max-width: 768px) {
        grid-template-columns: repeat(2, 1fr); /* 2 в ряд на телефонах */
    }

    @media (max-width: 480px) {
        grid-template-columns: 1fr; /* 1 в ряд на маленьких телефонах */
    }
}

.product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
    transition: all 0.4s ease;

    &:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
}

.product-image {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;

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
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

.product-info {
    padding: 1.5rem;
}

.product-name {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.8rem;
    color: var(--dark);
}

.product-price {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1.2rem;
}

.current-price {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--primary);
}

.old-price {
    font-size: 1rem;
    color: var(--gray);
    text-decoration: line-through;
}

.add-btn-card {
    width: 100%;
    padding: 10px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: 0.2s;

    &:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }
}

.qty-selector-inline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--light);
    border-radius: 12px;
    padding: 4px;
}

.qty-btn {
    flex: 1;
    height: 38px;
    border: none;
    background: transparent;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    border-radius: 8px;

    &:hover { background: white; }
}

.qty-value {
    font-weight: 800;
    font-size: 1rem;
    min-width: 30px;
    text-align: center;
}

.empty-products {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);

    i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; display: block; }
}
</style>
