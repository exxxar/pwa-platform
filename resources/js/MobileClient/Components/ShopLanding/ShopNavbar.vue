<template>
    <nav class="shop-navbar" :class="{ 'scrolled': isScrolled }">
        <div class="container">
            <div class="navbar-content">
                <div class="navbar-brand">
                    <i class="fa-solid fa-store"></i>
                    <span>{{ config.footer?.companyName || 'Магазин' }}</span>
                </div>

                <div class="navbar-actions">
                    <button class="nav-btn" @click="$emit('open-feedback')">
                        <i class="fa-solid fa-phone"></i>
                        <span class="btn-text">Связаться</span>
                    </button>
                    <button class="nav-btn cart-btn" @click="$emit('open-cart')">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="btn-text">Корзина</span>
                        <span v-if="cartTotalCount > 0" class="cart-badge">
                            {{ cartTotalCount }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
import {useBasket} from '@/MobileClient/composables/useBasket';

export default {
    name: "ShopNavbar",

    props: {
        config: { type: Object, default: () => ({}) },
    },

    emits: ['open-cart', 'open-feedback'],
    setup() {
        return {
            basket: useBasket()
        };
    },
    data() {
        return {
            isScrolled: false,

        };
    },
    computed:{
        cartTotalCount() {
            return this.basket.cartTotalCount.value || 0
        },
        cartTotalPrice() {
            return this.basket.cartTotalPrice.value  || 0
        },
    },
    mounted() {
        window.addEventListener('scroll', this.handleScroll);
    },

    beforeUnmount() {
        window.removeEventListener('scroll', this.handleScroll);
    },

    methods: {
        handleScroll() {
            this.isScrolled = window.scrollY > 50;
        },
    },
};
</script>

<style lang="scss" scoped>
.shop-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    padding: 1rem 0;
    transition: all 0.3s ease;
    z-index: 1000;

    &.scrolled {
        padding: 0.5rem 0;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
    }
}

.navbar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.navbar-brand {
    font-weight: 900;
    font-size: 1.5rem;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 0.5rem;

    i { color: var(--primary); }
}

.navbar-actions {
    display: flex;
    gap: 0.8rem;
}

.nav-btn {
    background: transparent;
    border: 1px solid rgba(0, 0, 0, 0.1);
    padding: 0.6rem 1.2rem;
    border-radius: 50px;
    color: var(--dark);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;

    &:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
}

.cart-btn {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    border: none;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 122, 0, 0.25);
        color: white;
    }
}

.cart-badge {
    background: white;
    color: var(--primary);
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    animation: popIn 0.3s ease;
}

@keyframes popIn {
    0% { transform: scale(0); }
    70% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

@media (max-width: 640px) {
    .btn-text { display: none; }
    .nav-btn { padding: 0.6rem; }
}
</style>
