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
                        :class="{ 'active': $route.name === item.route }"
                        class="nav-link"
                    >
                        <div class="icon-wrapper">
                            <i :class="item.icon"></i>
                            <span
                                v-if="item.badge && item.badge() > 0"
                                class="counter-badge"
                            >
                                {{ item.badge() > 99 ? '99+' : item.badge() }}
                            </span>
                        </div>
                        <span class="nav-label">{{ item.label }}</span>
                    </button>
                </div>

            </div>
        </div>
    </nav>
</template>

<script>
// import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

export default {
    name: "BottomMenu",

    setup() {


        // const basketStore = useBasketStore();
        // return { basketStore };
        return {};
    },
    mounted() {
       // console.log(this.$route.meta.hideBottomMenu)
    },
    data() {
        return {
            // Пункты меню в одном месте — легко расширять
            navItems: [
                { route: 'Menu', label: 'Главная', icon: 'fa-solid fa-house' },
                { route: 'Catalog', label: 'Товары', icon: 'fa-solid fa-box' },
                { route: 'Cart', label: 'Корзина', icon: 'fa-solid fa-cart-shopping', badge: () => this.cartTotalCount },
                { route: 'Profile', label: 'Профиль', icon: 'fa-solid fa-user' },
            ],
        };
    },

    computed: {
        // TODO: Подключи свой basket store
        cartTotalCount() {
            // return this.basketStore?.cartTotalCount || 0;
            return 0;
        },
    },

    methods: {
        goTo(name) {
            if (!name || this.$route.name === name) return;
            this.$router.push({ name });
        },
    },
};
</script>

<style scoped>
/* ============================================
   КОНТЕЙНЕР МЕНЮ
   Используем CSS-переменные Bootstrap 5.3,
   которые автоматически меняются при data-bs-theme
   ============================================ */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    padding: 8px;
    background: transparent;
    pointer-events: none; /* Прозрачный для кликов вне меню */
}

.bottom-nav .container-fluid {
    pointer-events: auto; /* Возвращаем клики на сам контейнер */
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-radius: 16px;
    box-shadow:
        0 -2px 20px rgba(0, 0, 0, 0.08),
        0 0 0 1px var(--bs-border-color-translucent, rgba(0, 0, 0, 0.05));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

/* ============================================
   ПУНКТЫ МЕНЮ
   ============================================ */
.bottom-nav .nav-link {
    background: transparent;
    border: none;
    color: var(--bs-secondary-color);
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
}

.bottom-nav .nav-link:hover {
    color: var(--bs-primary);
}

.bottom-nav .nav-link:focus {
    outline: none;
    box-shadow: none;
}

/* Обёртка для иконки + бейджа */
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

/* ============================================
   АКТИВНЫЙ ПУНКТ
   ============================================ */
.bottom-nav .nav-link.active {
    color: var(--bs-primary);
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
    background: var(--bs-primary);
    border-radius: 0 0 10px 10px;
    box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.4);
}

/* ============================================
   БЕЙДЖ СЧЁТЧИКА (корзина)
   ============================================ */
.counter-badge {
    position: absolute;
    top: -6px;
    right: -12px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    background: var(--bs-danger);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--bs-body-bg);
    line-height: 1;
    animation: badgePop 0.3s ease;
}

@keyframes badgePop {
    0% { transform: scale(0); }
    70% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* ============================================
   АДАПТИВ ДЛЯ ТЁМНОЙ ТЕМЫ
   (Bootstrap 5.3+ автоматически применяет
   эти переменные при data-bs-theme="dark")
   ============================================ */
:root[data-bs-theme="dark"] .bottom-nav .container-fluid {
    box-shadow:
        0 -2px 20px rgba(0, 0, 0, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.08);
}
</style>
