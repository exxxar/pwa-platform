<template>
    <div class="shop-landing" :style="themeStyles">
        <ShopNavbar :config="config" :cart-count="basket.cartTotalCount" @open-cart="showCart = true" @open-feedback="showFeedbackModal = true" />

        <!-- Hero -->
        <ShopHero v-if="config.sectionsVisibility?.hero !== false" :config="config.hero" @scroll-to-categories="scrollToPartners">
            <template #stories>
                <StoryListLanding :is-admin="isAdmin" @create-story="openStoryCreator" />
            </template>
        </ShopHero>

        <!-- Партнеры -->
        <template v-if="config.sectionsVisibility?.partners !== false">
            <ShopPartners @select-partner="handlePartnerSelect"/>
            <div class="container" style="margin-top: 2rem;">
                <ShopSelectedPartnerBanner v-if="selectedPartner" :partner="selectedPartner" @reset="resetPartnerSelection" />
            </div>
        </template>

        <!-- Товары (обычно всегда нужны, но можно скрыть) -->
        <div id="shop-products-section">
            <ShopProductsSkeleton v-if="shopStore.isLoading"/>
            <template v-else>
                <ShopCategories/>
                <ShopProducts
                    @open-modal="openProductModal"
                    @go-to-partners="scrollToPartners"
                />
            </template>
        </div>

        <!-- Остальные секции с проверкой -->
        <ShopPromotions v-if="config.sectionsVisibility?.promotions !== false" />
        <ShopDelivery v-if="config.sectionsVisibility?.delivery !== false" />
        <ShopPwaBanner v-if="config.sectionsVisibility?.pwaBanner !== false" />
        <ShopLoyalty v-if="config.sectionsVisibility?.loyalty !== false" :user-points="2450"/>
        <ShopWheel
            @open-privacy="showPrivacyModal = true"
            v-if="config.sectionsVisibility?.wheel !== false" />

        <ShopReviews
            v-if="config.sectionsVisibility?.reviews !== false"
            :reviews="config.reviews"
            :config="config.reviewsSection"
        />

        <ShopFaq
            v-if="config.sectionsVisibility?.faq !== false"
            @open-feedback="showFeedbackModal = true"
        />

        <ShopReservation
            v-if="config.sectionsVisibility?.reservation !== false"
            :address="config.footer.address"
            :phone="config.footer.phone"
        />

        <!-- CTA -->
        <section class="cta-section" v-if="config.sectionsVisibility?.cta !== false">
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
        <ShopFooter v-if="config.sectionsVisibility?.footer !== false" :config="config.footer" @open-privacy="showPrivacyModal = true"/>

        <!-- 🆕 Обновленная корзина (исправлены пропсы) -->
        <ShopCart
            v-if="showCart"
            :is-open="showCart"
            :config="config.cart"
            @close="showCart = false"
            @checkout="openCheckoutForm"
        />

        <!-- 🆕 НОВАЯ МОДАЛКА ОФОРМЛЕНИЯ ЗАКАЗА -->
        <ShopCheckoutForm
            v-if="showCheckoutForm"
            :is-open="showCheckoutForm"
            @close="showCheckoutForm = false"
            @success="handleOrderSuccess"
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
import ShopPwaBanner from '@/MobileClient/Components/ShopLanding/ShopPwaBanner.vue';
import ShopReservation from '@/MobileClient/Components/ShopLanding/ShopReservation.vue';
import ShopPromotions from '@/MobileClient/Components/ShopLanding/ShopPromotions.vue';
import ShopDelivery from '@/MobileClient/Components/ShopLanding/ShopDelivery.vue';
import ShopLoyalty from '@/MobileClient/Components/ShopLanding/ShopLoyalty.vue';
import ShopFaq from '@/MobileClient/Components/ShopLanding/ShopFaq.vue';
import ShopWheel from '@/MobileClient/Components/ShopLanding/ShopWheel.vue';
import ShopPartners from '@/MobileClient/Components/ShopLanding/ShopPartners.vue';
import ShopProductsSkeleton from '@/MobileClient/Components/ShopLanding/ShopProductsSkeleton.vue';
import ShopCheckoutForm from '@/MobileClient/Components/ShopLanding/ShopCheckoutForm.vue';
import ShopSelectedPartnerBanner from '@/MobileClient/Components/ShopLanding/ShopSelectedPartnerBanner.vue';
import StoryListLanding from '@/MobileClient/Components/ShopLanding/StoryListLanding.vue';

import { useShopLandingStore } from "@/MobileClient/stores/ShopLanding/shop";
import { useBasket } from '@/MobileClient/composables/useBasket';

export default {
    name: "ShopLanding",

    components: {
        ShopSelectedPartnerBanner,
        StoryListLanding,
        ShopNavbar,
        ShopHero,
        ShopCheckoutForm,
        ShopPartners,
        ShopProductsSkeleton,
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

    props: {
        tenant: {
            type: Object,
            required: true
        },
        tenant_user: {
            type: Object,
            default: null
        },
        initial_data: {
            type: Object,
            default: () => ({}),
        },
    },

    setup() {
        return {
            basket: useBasket(),
            shopStore: useShopLandingStore()
        };
    },

    data() {
        return {
            selectedProduct: null,
            showCart: false,
            showProductModal: false,
            showFeedbackModal: false,
            showPrivacyModal: false,
            showCheckoutForm: false,
            selectedPartner: null,

            // Инициализируем пустым, заполним в created() после слияния
            config: {}
        };
    },

    created() {
        // 1. Устанавливаем глобальные переменные
        window.Tenant = this.tenant;
        window.TenantUser = this.tenant_user;

        // 2. Получаем настройки лендинга из бэкенда (или пустой объект, если их нет)
        const backendLandingConfig = this.tenant?.settings?.landing || {};

        // 3. Безопасно сливаем дефолтные настройки с настройками из бэкенда
        this.config = this.deepMerge(this.getDefaultConfig(), backendLandingConfig);
    },

    async mounted() {
        // 1. Загружаем данные магазина
        const initialPartnerId = typeof window !== 'undefined' ? window.Tenant?.id : null;
        await this.shopStore.fetchShopData(initialPartnerId);

        // 2. Загружаем корзину с бэкенда
        await this.basket.loadProductsInBasket();
    },

    computed: {
        isAdmin() {
            const user = window.TenantUser;
            return user?.is_admin === true || user?.role === 'admin';
        },
        themeStyles() {
            const t = this.config.theme || {};
            return {
                '--primary': t.primary || '#ff7a00',
                '--primary-dark': t.primaryDark || '#e56f00',
                '--primary-light': t.primaryLight || '#ffb300',
                '--accent': t.accent || '#f4c542',
                '--dark': t.dark || '#0f0f14',
                '--light': t.light || '#fffdf8',
                '--gray': t.gray || '#6c757d',
            };
        }
    },

    methods: {
        // 🆕 Метод для получения дефолтных настроек (фоллбэк)
        getDefaultConfig() {
            return {
                theme: {
                    primary: '#ff7a00',
                    primaryDark: '#e56f00',
                    primaryLight: '#ffb300',
                    accent: '#f4c542',
                    dark: '#0f0f14',
                    light: '#fffdf8',
                    gray: '#6c757d',
                },
                hero: {
                    badge: 'Мобильный магазин',
                    title: 'Свежие продукты с доставкой',
                    subtitle: 'Выберите заведение и закажите любимые товары',
                    backgroundImage: '/images/hero-bg.jpg',
                    buttonText: 'Выбрать заведение',
                },
                sectionsVisibility: {
                    hero: true, partners: true, promotions: true, delivery: true,
                    pwaBanner: true, loyalty: true, wheel: true, reviews: true,
                    faq: true, reservation: true, cta: true, footer: true
                },
                reviewsSection: {
                    title: 'Что говорят клиенты',
                    subtitle: 'Реальные отзывы наших покупателей',
                },
                reviews: [
                    { id: 1, name: 'Анна К.', text: 'Отличный сервис! Заказываю каждую неделю.', rating: 5, avatar: '/images/avatar1.jpg' },
                    { id: 2, name: 'Дмитрий П.', text: 'Удобно заказывать через телефон.', rating: 5, avatar: '/images/avatar2.jpg' },
                ],
                cta: {
                    title: 'Остались вопросы?',
                    text: 'Свяжитесь с нами — поможем с выбором и расскажем о актуальных акциях',
                    buttonText: 'Написать нам',
                },
                footer: {
                    companyName: 'Ваш Магазин',
                    description: 'Доставка свежих продуктов и готовой еды',
                    phone: '+7 (999) 123-45-67',
                    email: 'info@example.com',
                    address: 'г. Москва, ул. Примерная, 1',
                    socialLinks: [
                        { icon: 'fa-brands fa-telegram', url: '#' },
                        { icon: 'fa-brands fa-vk', url: '#' },
                    ],
                },
                cart: {
                    title: 'Ваш заказ',
                    emptyText: 'Корзина пуста',
                    checkoutText: 'Оформить заказ',
                    totalText: 'Итого:',
                },
                feedbackModal: {
                    title: 'Связаться с нами',
                    subtitle: 'Оставьте контакт — перезвоним в течение 15 минут',
                    nameLabel: 'Ваше имя',
                    phoneLabel: 'Телефон',
                    messageLabel: 'Сообщение',
                    submitText: 'Отправить',
                },
                privacyModal: {
                    title: 'Политика конфиденциальности',
                    content: 'Здесь будет текст политики конфиденциальности...',
                },
            };
        },

        // 🆕 Функция глубокого слияния объектов
        // Она гарантирует, что если бэкенд вернет только { theme: { primary: '#000' } },
        // остальные цвета из дефолтов не потеряются.
        deepMerge(target, source) {
            const output = Object.assign({}, target);
            if (this.isObject(target) && this.isObject(source)) {
                Object.keys(source).forEach(key => {
                    if (this.isObject(source[key])) {
                        if (!(key in target)) {
                            Object.assign(output, { [key]: source[key] });
                        } else {
                            output[key] = this.deepMerge(target[key], source[key]);
                        }
                    } else {
                        Object.assign(output, { [key]: source[key] });
                    }
                });
            }
            return output;
        },

        isObject(item) {
            return (item && typeof item === 'object' && !Array.isArray(item));
        },

        openStoryCreator() {
            this.$notify?.({
                title: 'Создание истории',
                text: 'Здесь откроется форма добавления новой истории',
                type: 'info'
            });
        },

        resetPartnerSelection() {
            this.selectedPartner = null;
            this.shopStore.partnerId = null;
            this.$notify?.({
                title: 'Выбор сброшен',
                text: 'Показаны все доступные заведения',
                type: 'info',
            });
            this.scrollToPartners();
        },

        openCheckoutForm() {
            this.showCart = false;
            this.showCheckoutForm = true;
        },

        async handleCheckout(orderData) {
            try {
                await this.basket.startCheckout(orderData);
                this.$notify?.({
                    title: 'Заказ успешно оформлен!',
                    text: `Сумма: ${orderData.total} ₽. Мы свяжемся с вами.`,
                    type: 'success',
                });
                this.showCart = false;
            } catch (error) {
                console.error('Ошибка при оформлении:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось оформить заказ', type: 'error' });
            }
        },

        handlePartnerSelect(partner) {
            this.selectedPartner = partner;
            this.shopStore.partnerId = partner.id;
            this.shopStore.fetchShopData(partner.id);

            this.$nextTick(() => {
                const productsSection = document.getElementById('shop-products-section');
                if (productsSection) {
                    productsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });

            this.$notify?.({
                title: 'Заведение выбрано',
                text: `Меню обновлено для: ${partner.title || partner.name}`,
                type: 'success',
            });
        },

        scrollToPartners() {
            const partnersSection = document.getElementById('partners-section');
            if (partnersSection) {
                partnersSection.scrollIntoView({ behavior: 'smooth' });
            }
        },

        openProductModal(product) {
            this.selectedProduct = product;
            this.showProductModal = true;
        },

        submitFeedback(data) {
            console.log('Feedback:', data);
            this.$notify?.({ title: 'Отправлено', text: 'Мы свяжемся с вами в ближайшее время', type: 'success' });
            this.showFeedbackModal = false;
        }
    }
};
</script>

<style lang="scss">
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

    /* Фикс для горизонтального скролла категорий */
    overflow-x: clip;
    position: relative;
    isolation: isolate;
}

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

// ==========================================
// 🆕 СЕКЦИЯ ИСТОРИЙ (Премиальный вид)
// ==========================================
// 🆕 Минимальные стили для секции-обертки (фон и отступы)
.stories-section {
    padding: 1rem 0 2rem;
    background: var(--light, #fffdf8);
}

.stories-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 1.5rem 1rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden; // Чтобы скролл не вылезал за скругления

    // Декоративный градиент сверху (опционально, для красоты)
    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
        opacity: 0.8;
    }

    // Скрываем стандартный скроллбар для чистоты дизайна
    .stories-scroll {
        scrollbar-width: none; // Firefox
        -ms-overflow-style: none; // IE/Edge

        &::-webkit-scrollbar {
            display: none; // Chrome, Safari, Opera
        }
    }
}

// Адаптив для мобильных: делаем отступы по бокам, чтобы первая и последняя история не прилипали к краю
@media (max-width: 576px) {
    .stories-section {
        padding: 1rem 0 1.5rem;
    }

    .stories-card {
        border-radius: 20px;
        padding: 1.25rem 0.5rem; // Меньше боковые отступы на мобильном
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    }
}
</style>
