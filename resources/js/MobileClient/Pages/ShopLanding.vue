<template>
    <div class="shop-landing" :style="themeStyles">

        <!-- Навигация -->
        <ShopNavbar
            :config="config"
            :cart-count="cartItems.length"
            @open-cart="showCart = true"
            @open-feedback="showFeedbackModal = true"
        />

        <!-- Hero -->
        <ShopHero
            :config="config.hero"
            @scroll-to-categories="scrollToCategories"
        />

        <ShopCategories />
        <ShopProducts
            :products="shopStore.filteredProducts"
            @open-modal="openProductModal" />

        <ShopPromotions />
        <ShopDelivery />
        <!-- НОВЫЙ БЛОК: PWA Приложение -->
        <ShopPwaBanner />

        <ShopLoyalty :user-points="2450" />
        <ShopWheel />
        <ShopReviews :reviews="config.reviews" :config="config.reviewsSection" />
        <ShopFaq @open-feedback="showFeedbackModal = true" />

        <!-- НОВЫЙ БЛОК: Бронирование -->
        <ShopReservation :address="config.footer.address" :phone="config.footer.phone" />

        <!-- CTA -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">{{ config.cta.title }}</h2>
                    <p class="cta-text">{{ config.cta.text }}</p>
                    <button class="btn-primary" @click="showFeedbackModal = true">
                        <i class="fa-solid fa-paper-plane"></i>
                        {{ config.cta.buttonText }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <ShopFooter :config="config.footer" @open-privacy="showPrivacyModal = true" />

        <!-- Обновленная корзина -->
        <ShopCart
            v-if="showCart"
            :items="cartStore.items"
            :config="config.cart"
            @close="showCart = false"
            @update-qty="cartStore.updateQuantity"
            @checkout="handleCheckout"
        />

        <!-- Модалка товара -->
        <ShopProductModal
            v-if="selectedProduct"
            :product="selectedProduct"
            :is-open="showProductModal"
            @close="showProductModal = false"
        />

        <!-- Модалка обратной связи -->
        <ShopFeedbackModal
            v-if="showFeedbackModal"
            :config="config.feedbackModal"
            @close="showFeedbackModal = false"
            @submit="submitFeedback"
        />

        <!-- Модалка конфиденциальности -->
        <ShopPrivacyModal
            v-if="showPrivacyModal"
            :config="config.privacyModal"
            @close="showPrivacyModal = false"
        />

    </div>
</template>

<script>
import ShopNavbar from '@/MobileClient/Components/ShopLanding/ShopNavbar.vue';
import ShopHero from '@/MobileClient/Components/ShopLanding/ShopHero.vue';
import ShopCategories from '@/MobileClient/Components/ShopLanding/ShopCategories.vue';
import ShopProducts from '@/MobileClient/Components/ShopLanding/ShopProducts.vue';
import ShopReviews from '@/MobileClient/Components/ShopLanding/ShopReviews.vue';
import ShopFooter from '@/MobileClient/Components/ShopLanding/ShopFooter.vue';
import ShopCart from '@/MobileClient/Components/ShopLanding/ShopCart.vue';
import ShopFeedbackModal from '@/MobileClient/Components/ShopLanding/ShopFeedbackModal.vue';
import ShopPrivacyModal from '@/MobileClient/Components/ShopLanding/ShopPrivacyModal.vue';
import ShopProductModal from '@/MobileClient/Components/ShopLanding/ShopProductModal.vue';

import ShopPwaBanner from '@/MobileClient/Components/ShopLanding/ShopPwaBanner.vue';       // <-- Добавлено
import ShopReservation from '@/MobileClient/Components/ShopLanding/ShopReservation.vue';   // <-- Добавлено

import ShopPromotions from '@/MobileClient/Components/ShopLanding/ShopPromotions.vue';
import ShopDelivery from '@/MobileClient/Components/ShopLanding/ShopDelivery.vue';
import ShopLoyalty from '@/MobileClient/Components/ShopLanding/ShopLoyalty.vue';
import ShopFaq from '@/MobileClient/Components/ShopLanding/ShopFaq.vue';
import ShopWheel from '@/MobileClient/Components/ShopLanding/ShopWheel.vue';

import {useLandingCartStore} from "@/MobileClient/stores/ShopLanding/cart";
import {useShopLandingStore} from "@/MobileClient/stores/ShopLanding/shop";

export default {
    name: "ShopLanding",

    components: {
        ShopNavbar,
        ShopHero,
        ShopCategories,
        ShopProducts,
        ShopReviews,
        ShopFooter,
        ShopCart,
        ShopPwaBanner,
        ShopReservation,
        ShopProductModal,
        ShopFeedbackModal,
        ShopPrivacyModal,
        ShopPromotions,
        ShopDelivery,
        ShopLoyalty,
        ShopFaq,
        ShopWheel,
    },

    data() {
        return {

            selectedProduct: null,
            activeCategory: 'all',
            showCart: false,
            showProductModal: false,
            showFeedbackModal: false,
            showPrivacyModal: false,
            cartItems: [],


            cartStore: useLandingCartStore(),
            shopStore: useShopLandingStore(),

            // Конфигурация магазина (загружается из API или props)
            config: {
                // Цветовая схема
                theme: {
                    primary: '#ff7a00',
                    primaryDark: '#e56f00',
                    primaryLight: '#ffb300',
                    accent: '#f4c542',
                    dark: '#0f0f14',
                    light: '#fffdf8',
                    gray: '#6c757d',
                },

                // Hero секция
                hero: {
                    badge: 'Мобильный магазин',
                    title: 'Свежие продукты с доставкой',
                    subtitle: 'Заказывайте любимые товары прямо со смартфона. Быстро, удобно, с бонусами.',
                    backgroundImage: '/images/hero-bg.jpg',
                    buttonText: 'Смотреть каталог',
                },

                // Категории
                categories: [
                    { id: 'all', name: 'Все товары', icon: 'fa-solid fa-grid' },
                    { id: 'food', name: 'Еда', icon: 'fa-solid fa-burger' },
                    { id: 'drinks', name: 'Напитки', icon: 'fa-solid fa-mug-hot' },
                    { id: 'desserts', name: 'Десерты', icon: 'fa-solid fa-cake-candles' },
                    { id: 'other', name: 'Другое', icon: 'fa-solid fa-box' },
                ],

                // Товары
                products: {
                    title: 'Популярные товары',
                    subtitle: 'Выберите то, что вам по вкусу',
                    addToCartText: 'В корзину',
                },

                items: [
                    { id: 1, name: 'Пицца Маргарита', price: 590, oldPrice: 690, category: 'food', image: '/images/pizza.jpg', badge: 'Хит' },
                    { id: 2, name: 'Капучино', price: 250, category: 'drinks', image: '/images/cappuccino.jpg' },
                    { id: 3, name: 'Чизкейк', price: 350, category: 'desserts', image: '/images/cheesecake.jpg', badge: 'Новинка' },
                    { id: 4, name: 'Бургер Классик', price: 450, category: 'food', image: '/images/burger.jpg' },
                    { id: 5, name: 'Латте', price: 280, category: 'drinks', image: '/images/latte.jpg' },
                    { id: 6, name: 'Тирамису', price: 380, oldPrice: 450, category: 'desserts', image: '/images/tiramisu.jpg' },
                ],

                // Отзывы
                reviewsSection: {
                    title: 'Что говорят клиенты',
                    subtitle: 'Реальные отзывы наших покупателей',
                },

                reviews: [
                    { id: 1, name: 'Анна К.', text: 'Отличный сервис! Заказываю каждую неделю, всё всегда свежее и вкусное.', rating: 5, avatar: '/images/avatar1.jpg' },
                    { id: 2, name: 'Дмитрий П.', text: 'Удобно заказывать через телефон. Доставка быстрая, курьеры вежливые.', rating: 5, avatar: '/images/avatar2.jpg' },
                    { id: 3, name: 'Мария С.', text: 'Бонусы копятся, уже несколько раз получала десерты в подарок. Рекомендую!', rating: 4, avatar: '/images/avatar3.jpg' },
                ],

                // CTA
                cta: {
                    title: 'Остались вопросы?',
                    text: 'Свяжитесь с нами — поможем с выбором и расскажем о актуальных акциях',
                    buttonText: 'Написать нам',
                },

                // Footer
                footer: {
                    companyName: 'Ваш Магазин',
                    description: 'Доставка свежих продуктов и готовой еды',
                    phone: '+7 (999) 123-45-67',
                    email: 'info@example.com',
                    address: 'г. Москва, ул. Примерная, 1',
                    socialLinks: [
                        { icon: 'fa-brands fa-telegram', url: '#' },
                        { icon: 'fa-brands fa-vk', url: '#' },
                        { icon: 'fa-brands fa-whatsapp', url: '#' },
                    ],
                },

                // Корзина
                cart: {
                    title: 'Ваш заказ',
                    emptyText: 'Корзина пуста',
                    checkoutText: 'Оформить заказ',
                    totalText: 'Итого:',
                },

                // Модалка обратной связи
                feedbackModal: {
                    title: 'Связаться с нами',
                    subtitle: 'Оставьте контакт — перезвоним в течение 15 минут',
                    nameLabel: 'Ваше имя',
                    phoneLabel: 'Телефон',
                    messageLabel: 'Сообщение',
                    submitText: 'Отправить',
                },

                // Модалка конфиденциальности
                privacyModal: {
                    title: 'Политика конфиденциальности',
                    content: 'Здесь будет текст политики конфиденциальности...',
                },
            },
        };
    },

    computed: {
        themeStyles() {
            const t = this.config.theme;
            return {
                '--primary': t.primary,
                '--primary-dark': t.primaryDark,
                '--primary-light': t.primaryLight,
                '--accent': t.accent,
                '--dark': t.dark,
                '--light': t.light,
                '--gray': t.gray,
            };
        },

        filteredProducts() {
            if (this.activeCategory === 'all') return this.config.items;
            return this.config.items.filter(item => item.category === this.activeCategory);
        },
    },

    methods: {
        scrollToCategories() {
            this.$refs.categoriesSection?.$el.scrollIntoView({ behavior: 'smooth' });
        },

        openProductModal(product) {
            this.selectedProduct = product;
            this.showProductModal = true;
        },

        selectCategory(categoryId) {
            this.activeCategory = categoryId;
        },

        addToCart(product) {
            const existing = this.cartItems.find(item => item.id === product.id);
            if (existing) {
                existing.quantity++;
            } else {
                this.cartItems.push({ ...product, quantity: 1 });
            }

            this.$notify?.({
                title: 'Добавлено',
                text: `${product.name} добавлен в корзину`,
                type: 'success',
            });
        },

        removeFromCart(productId) {
            this.cartItems = this.cartItems.filter(item => item.id !== productId);
        },

        checkout() {
            this.$notify?.({
                title: 'Заказ оформлен',
                text: 'Мы свяжемся с вами для подтверждения',
                type: 'success',
            });
            this.cartItems = [];
            this.showCart = false;
        },

        submitFeedback(data) {
            console.log('Feedback:', data);
            this.$notify?.({
                title: 'Отправлено',
                text: 'Мы свяжемся с вами в ближайшее время',
                type: 'success',
            });
            this.showFeedbackModal = false;
        },
        handleCheckout(orderData) {
            console.log('Отправка заказа на сервер:', orderData);

            // Здесь будет ваш API запрос:
            // await this.$axios.post('/api/orders', orderData);

            this.$notify?.({
                title: 'Заказ успешно оформлен!',
                text: `Сумма: ${orderData.total} ₽. Мы свяжемся с вами по номеру ${orderData.customer.phone}`,
                type: 'success',
            });

            this.cartStore.clearCart();
            this.showCart = false;
        },
    },
};
</script>

<style lang="scss">
// Глобальные переменные (будут переопределены через themeStyles)
.shop-landing {
    --primary: #ff7a00;
    --primary-dark: #e56f00;
    --primary-light: #ffb300;
    --accent: #f4c542;
    --dark: #0f0f14;
    --light: #fffdf8;
    --gray: #6c757d;

    font-family: 'Inter', sans-serif;
    color: var(--dark);
    background: var(--light);
    overflow-x: hidden;
}

// Общие стили
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    border: none;
    padding: 14px 32px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 10px 30px rgba(255, 122, 0, 0.25);

    &:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(255, 122, 0, 0.35);
    }
}

// CTA секция
.cta-section {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    padding: 80px 0;
    text-align: center;
    color: white;
}

.cta-title {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 1rem;
}

.cta-text {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.95;
}

.cta-section .btn-primary {
    background: white;
    color: var(--primary);

    &:hover {
        background: var(--light);
    }
}
</style>
