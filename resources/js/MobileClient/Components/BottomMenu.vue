<template>
    <nav class="bottom-nav" v-if="!$route.meta.hideBottomMenu">
        <div class="container-fluid h-100">
            <div class="row h-100 text-center g-0">

                <div
                    v-for="item in navItems"
                    :key="item.route"
                    class="col p-0"
                >
                    <button
                        type="button"
                        @click="goTo(item.route)"
                        :class="{
                            'active': $route.name === item.route,
                            'has-items': item.hasItems && item.hasItems()
                        }"
                        class="nav-link"
                    >
                        <div class="icon-wrapper">
                            <i :class="item.icon"></i>
                            <transition name="badge-pop">
                                <span
                                    v-if="item.badge && item.badge() > 0"
                                    class="counter-badge"
                                >
                                    {{ item.badge() > 99 ? '99+' : item.badge() }}
                                </span>
                            </transition>
                        </div>
                        <span class="nav-label">{{ item.label }}</span>
                    </button>
                </div>

            </div>
        </div>
    </nav>
</template>

<script>
import { useBasket } from '@/MobileClient/Composables/useBasket.js';
import { useChat } from '@/MobileClient/Composables/useChat.js';

export default {
    name: "BottomMenu",

    setup() {
        const basket = useBasket();
        const chat = useChat();

        return {
            totalUnread: chat.totalUnread,
            cartTotalCount: basket.cartTotalCount,
            isEmpty: basket.isEmpty,
        };
    },

    data() {
        return {
            navItems: [
                {
                    route: 'Menu',
                    label: 'Главная',
                    icon: 'fa-solid fa-house'
                },
                {
                    route: 'Catalog',
                    label: 'Товары',
                    icon: 'fa-solid fa-box'
                },
                {
                    route: 'Cart',
                    label: 'Корзина',
                    icon: 'fa-solid fa-cart-shopping',
                    badge: () => this.cartTotalCount,
                    hasItems: () => !this.isEmpty,
                },
                {
                    route: 'Chat',
                    label: 'Чат',
                    icon: 'fa-regular fa-comments',
                    badge: () => this.totalUnread,
                    hasItems: () => true,
                },
                {
                    route: 'Profile',
                    label: 'Профиль',
                    icon: 'fa-solid fa-user'
                },
            ],
        };
    },

    methods: {
        goTo(name) {
            if (!name || this.$route.name === name) return;
            this.$router.push({ name });
        },
    },
};
</script>

<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: var(--bs-primary, #3b82f6);
$danger: var(--bs-danger, #ef4444);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$bg: var(--bs-body-bg, #ffffff);
$border: var(--bs-border-color-translucent, rgba(0, 0, 0, 0.05));

// ==========================================
// КОНТЕЙНЕР МЕНЮ
// ==========================================
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 999;
    padding: 8px;
    background: transparent;
    pointer-events: none;
}

.bottom-nav .container-fluid {
    pointer-events: auto;
    background-color: $bg;
    color: $text;
    border-radius: 16px;
    box-shadow:
        0 -2px 20px rgba(0, 0, 0, 0.08),
        0 0 0 1px $border;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

// ==========================================
// ПУНКТЫ МЕНЮ
// ==========================================
.bottom-nav .nav-link {
    background: transparent;
    border: none;
    color: $text-muted;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 64px;
    width: 100%;
    font-size: 10px;
    font-weight: 500;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    cursor: pointer;
    padding: 0;
    -webkit-tap-highlight-color: transparent;

    &:hover {
        color: $primary;
    }

    &:focus {
        outline: none;
        box-shadow: none;
    }

    // Подсветка иконки корзины при наличии товаров
    &.has-items i {
        color: $primary;
    }
}

// Обёртка для иконки + бейджа
.icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
}

.bottom-nav .nav-link i {
    font-size: 20px;
    transition: all 0.25s ease;
}

.nav-label {
    letter-spacing: 0.2px;
}

// ==========================================
// АКТИВНЫЙ ПУНКТ
// ==========================================
.bottom-nav .nav-link.active {
    color: $primary;
}

.bottom-nav .nav-link.active i {
    transform: translateY(-3px) scale(1.05);
}

.bottom-nav .nav-link.active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 36px;
    height: 4px;
    background: $primary;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 2px 8px rgba($primary, 0.4);
}

// ==========================================
// БЕЙДЖ СЧЁТЧИКА
// ==========================================
.counter-badge {
    position: absolute;
    top: -6px;
    right: -12px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    background: $danger;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid $bg;
    line-height: 1;
    box-shadow: 0 2px 8px rgba($danger, 0.3);
}

// Анимация появления/исчезновения бейджа
.badge-pop-enter-active {
    animation: badgePop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.badge-pop-leave-active {
    animation: badgePop 0.3s ease reverse;
}

@keyframes badgePop {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    70% {
        transform: scale(1.3);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

// ==========================================
// ТЁМНАЯ ТЕМА
// ==========================================
:root[data-bs-theme="dark"] .bottom-nav .container-fluid {
    box-shadow:
        0 -2px 20px rgba(0, 0, 0, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.08);
}
</style>
