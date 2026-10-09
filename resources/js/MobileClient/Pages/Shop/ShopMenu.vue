<template>
    <div class="shop-container">

        <!-- ========================================== -->
        <!-- ПЕРЕКЛЮЧАТЕЛЬ РЕЖИМОВ (Магазин / Бронь) -->
        <!-- ========================================== -->
        <div v-if="hasBooking" class="mode-switcher">
            <div class="switcher-container">
                <button
                    class="switcher-btn"
                    :class="{ active: currentMode === 'shop' }"
                    @click="currentMode = 'shop'"
                >
                    <i class="fa-solid fa-shop"></i>
                    <span>Магазин</span>
                </button>
                <button
                    class="switcher-btn"
                    :class="{ active: currentMode === 'booking' }"
                    @click="currentMode = 'booking'"
                >
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Бронирование</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- РЕЖИМ: МАГАЗИН -->
        <!-- ========================================== -->
        <div v-if="currentMode === 'shop'" class="menu-container">
            <!-- Индикатор загрузки -->
            <div v-if="isLoading" class="loading-container d-flex flex-column justify-content-center align-items-center">
                <div class="loading-content text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <h5 class="loading-title">Загружаем товары...</h5>
                    <p class="loading-subtitle text-muted">Пожалуйста, подождите</p>
                </div>
            </div>

            <!-- Основной контент -->
            <template v-else>
                <MenuHeader
                    :settings="settings"
                    :categories="filteredProducts"
                    :collections="collections"
                    :show-back-button="hasPartners"
                    @select-category="onCategorySelect"
                    @search="onSearch"
                    @back-to-partners="backToPartners"
                />

                <div class="menu-content d-flex flex-column">
                    <!-- Предупреждение о блокировке -->
                    <div v-if="settings?.is_disabled" class="p-2 mt-2">
                        <div class="alert alert-danger mb-0">
                            <p class="mb-0">{{ settings.disabled_text }}</p>
                        </div>
                    </div>

                    <!-- Бронирование -->
                    <BookingDropdown />

                    <!-- 🆕 СЕКЦИЯ КОЛЛЕКЦИЙ -->
                    <div v-if="activeCollections.length > 0" class="collections-section">
                        <div class="container g-2">
                            <AppDivider text="Коллекции" icon="fa-layer-group" />
                            <div class="row row-cols-2 row-cols-sm-2 row-cols-lg-6 row-cols-md-4 g-2 mb-3">
                                <div class="col" v-for="collection in activeCollections" :key="collection.id">
                                    <CollectionCard
                                        :partner-id="selectedPartner?.tenant_partner_id"
                                        :item="collection"
                                        @open-collection="onCollectionOpen"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🎁 НОВЫЙ БЛОК: АНОНИМНЫЙ БОКС (только если включен) -->
                    <div
                        v-if="isMysteryBoxEnabled"
                        class="mystery-box-banner-wrapper mt-3 mb-3"
                    >
                        <div class="container g-2">
                            <div
                                class="mystery-box-banner"
                                @click="openMysteryBoxModal"
                                :style="mysteryBoxGradientStyle"
                            >
                                <div class="mystery-box-glow"></div>
                                <div class="mystery-box-content">
                                    <div class="mystery-box-icon-wrapper">
                                        <div class="mystery-box-icon" :style="mysteryBoxGradientStyle">
                                            <span class="gift-emoji">{{ mysteryBoxConfig.emoji || '🎁' }}</span>
                                        </div>
                                        <div class="sparkles">
                                            <span class="sparkle s1">✨</span>
                                            <span class="sparkle s2">⭐</span>
                                            <span class="sparkle s3">✨</span>
                                        </div>
                                    </div>
                                    <div class="mystery-box-text">
                                        <div class="mystery-box-label">
                                            {{ mysteryBoxConfig.banner_subtitle || 'Эксклюзив' }}
                                        </div>
                                        <h3 class="mystery-box-title">
                                            {{ mysteryBoxConfig.banner_title || 'Анонимный бокс' }}
                                        </h3>
                                        <p class="mystery-box-description">
                                            Соберем пакет случайных товаров на выбранную сумму.
                                            Состав — сюрприз до момента получения!
                                        </p>
                                    </div>
                                    <div class="mystery-box-cta">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Товары по категориям -->
                    <ProductGrid
                        :categories="filteredProducts"
                        :stories="storiesStore.stories"
                        :is-product-list="settings?.is_product_list"
                        @load-more="onLoadMore"
                        @swipe-left="onSwipeLeft"
                        @swipe-right="onSwipeRight"
                    />
                </div>

                <slot name="navigation"></slot>
            </template>

            <!-- Модалка коллекции -->
            <CollectionModal ref="collectionModal" />
        </div>

        <!-- ========================================== -->
        <!-- РЕЖИМ: БРОНИРОВАНИЕ -->
        <!-- ========================================== -->
        <div v-else-if="currentMode === 'booking'" class="booking-section p-0">
            <TableBookingPlanner />
        </div>

        <!-- ========================================== -->
        <!-- 🎁 МОДАЛКА: АНОНИМНЫЙ БОКС -->
        <!-- ========================================== -->
        <MysteryBoxModal
            :is-visible="showMysteryBoxModal"
            :config="mysteryBoxConfig"
            @close="showMysteryBoxModal = false"
            @add-to-cart="handleAddMysteryBoxToCart"
        />

        <!-- ========================================== -->
        <!-- МОДАЛКА: КОФЕ -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            id="coffeeModal"
            tabindex="-1"
            aria-labelledby="coffeeModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content coffee-modal">
                    <div class="modal-header">
                        <div class="modal-icon coffee-icon">
                            <i class="fa-solid fa-mug-hot"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title" id="coffeeModalLabel">Кофейная карта</h5>
                            <small class="text-muted">Ваш прогресс и бонусы</small>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Закрыть"
                        ></button>
                    </div>
                    <div class="modal-body p-0">
                        <CoffeeProgress />
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПЛАВАЮЩЕЕ МЕНЮ -->
        <!-- ========================================== -->
        <FloatingMenu
            :items="menuItems"
            :has-unread="favoritesCount > 0"
            @item-click="handleMenuClick"
        />

        <!-- ========================================== -->
        <!-- МОДАЛКА: ИЗБРАННОЕ -->
        <!-- ========================================== -->
        <FavoritesModal v-model="showFavoritesModal" />

    </div>
</template>

<script>
import { useProducts } from '@/MobileClient/composables/useProducts.js';
import { useCollections } from '@/MobileClient/composables/useCollections.js';
import { useStoriesStore } from '@/MobileClient/stores/Shop/stories.js';
import { useBasketStore } from "@/MobileClient/stores/Shop/basket.js";
import { useFavorites } from '@/MobileClient/Composables/useFavorites.js';

import MenuHeader from '@/MobileClient/Components/Shop/Menu/MenuHeader.vue';
import ProductGrid from '@/MobileClient/Components/Shop/Menu/ProductGrid.vue';
import BookingDropdown from '@/MobileClient/Components/Shop/Booking/BookingDropdown.vue';
import TableBookingPlanner from '@/MobileClient/Components/Shop/Booking/TableBookingPlanner.vue';
import CollectionCard from '@/MobileClient/Components/Shop/Collections/CollectionCard.vue';
import CollectionModal from '@/MobileClient/Components/Shop/Collections/CollectionModal.vue';
import AppDivider from '@/MobileClient/Components/AppDivider.vue';
import CoffeeProgress from '@/MobileClient/Components/Shop/CoffeeProgress.vue';
import FloatingMenu from '@/MobileClient/Components/Shop/FloatingMenu.vue';
import FavoritesModal from '@/MobileClient/Components/Shop/Favorites/FavoritesModal.vue';
import MysteryBoxModal from '@/MobileClient/Components/Shop/MysteryBoxModal.vue';

export default {
    name: 'ShopMenu',

    components: {
        AppDivider,
        ProductGrid,
        BookingDropdown,
        TableBookingPlanner,
        MenuHeader,
        CollectionCard,
        CollectionModal,
        CoffeeProgress,
        FloatingMenu,
        FavoritesModal,
        MysteryBoxModal,
    },

    setup() {
        const {
            filteredProducts,
            selectedPartner,
            loadProductsByCategory,
            setPartner,
            clearMenuData,
            setSearch,
            loadMoreProducts
        } = useProducts();

        const {
            collections,
            activeCollections,
            loadCollections,
            loadCollection,
        } = useCollections();

        const storiesStore = useStoriesStore();
        const basketStore = useBasketStore();
        const favoritesStore = useFavorites();

        return {
            filteredProducts,
            selectedPartner,
            storiesStore,
            collections,
            activeCollections,
            loadCollections,
            loadCollection,
            loadProductsByCategory,
            setPartner,
            clearMenuData,
            setSearch,
            loadMoreProducts,
            basketStore,
            favoritesStore,
        };
    },

    data() {
        return {
            isLoading: false,
            currentMode: 'shop',
            coffeeModal: null,
            showFavoritesModal: false,
            showMysteryBoxModal: false,
            isAddingBoxToCart: false,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        hasPartners() {
            return this.settings?.partners?.is_active || false;
        },

        hasBooking() {
            return this.settings?.has_booking || false;
        },

        favoritesCount() {
            return this.favoritesStore.count || 0;
        },

        canBuy() {
            if (typeof window.isCorrectSchedule !== 'function') return true;
            if (!window.isCorrectSchedule(this.settings?.schedule)) return true;
            return this.settings?.is_work || this.settings?.can_buy_after_closing;
        },
        mysteryBoxConfig() {
            const config = this.settings?.anonymous_box || {};
            return {
                enabled: config.enabled ?? false,
                amounts: (config.amounts || []).map(a => ({
                    value: typeof a === 'object' ? a.value : a,
                    label: typeof a === 'object' ? a.label : '',
                })),
                description: config.description || 'Хочешь сюрприз? Мы соберем для тебя случайный пакет из вкусных товаров ровно на выбранную сумму. Состав бокса ты узнаешь только в момент получения!',
                banner_title: config.banner_title || 'Анонимный бокс',
                banner_subtitle: config.banner_subtitle || 'Эксклюзив',
                gradient_from: config.gradient_from || '#667eea',
                gradient_to: config.gradient_to || '#f093fb',
                emoji: config.emoji || '🎁',
            };
        },

        isMysteryBoxEnabled() {
            if (!this.mysteryBoxConfig.enabled) return false;
            if (this.mysteryBoxConfig.amounts.length === 0) return false;
            if (!this.canBuy) return false;
            return true;
        },

        mysteryBoxGradientStyle() {
            const from = this.mysteryBoxConfig.gradient_from;
            const to = this.mysteryBoxConfig.gradient_to;
            return {
                background: `linear-gradient(135deg, ${from} 0%, ${to} 100%)`,
            };
        },
        menuItems() {
            return [
                {
                    key: 'favorites',
                    label: 'Избранное',
                    icon: 'fa-solid fa-heart',
                    color: '#ef4444',
                    badge: this.favoritesCount > 0 ? this.favoritesCount : null,
                    action: () => this.openFavorites(),
                },
                {
                    key: 'coffee',
                    label: 'Кофе в подарок',
                    icon: 'fa-solid fa-mug-hot',
                    color: '#8b5cf6',
                    badge: null,
                    action: () => this.showCoffee(),
                },
            ];
        },
    },

    mounted() {
        if (this.hasPartners && !this.selectedPartner) {
            this.$router.replace({ name: 'Partners' });
            return;
        }

        this.loadInitialData();
        this.loadBasketData();
        this.initCoffeeModal();

        if (!this.canBuy) {
            this.$nextTick(() => {
                const modalEl = document.querySelector('#schedule-list-display');
                if (modalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        }
    },

    beforeUnmount() {
        if (this.coffeeModal) this.coffeeModal.dispose();
    },

    methods: {
        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        async loadInitialData() {
            this.isLoading = true;
            try {
                const partnerId = this.selectedPartner?.tenant_partner_id || null;

                await Promise.all([
                    this.loadProductsByCategory(partnerId),
                    this.loadCollections({ partner_id: partnerId }),
                    this.storiesStore.loadPartnersStories(partnerId),
                ]);
            } catch (error) {
                console.error('❌ Ошибка загрузки данных:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async loadBasketData() {
            try {
                await this.basketStore.loadProductsInBasket();
            } catch (error) {
                console.error('Ошибка загрузки корзины:', error);
            }
        },

        // ==========================================
        // 🎁 АНОНИМНЫЙ БОКС
        // ==========================================
        openMysteryBoxModal() {
            if (!this.canBuy) {
                this.$notify?.({
                    title: 'Магазин закрыт',
                    text: 'Сейчас нельзя оформить заказ',
                    type: 'warning',
                });
                return;
            }
            this.showMysteryBoxModal = true;
        },

// В methods компонента ShopMenu.vue

        async handleAddMysteryBoxToCart(amount) {
            if (this.isAddingBoxToCart) return;
            this.isAddingBoxToCart = true;

            try {
                const result = await this.basketStore.addAnonymousBox({
                    amount: amount,
                    partner_id: this.selectedPartner?.tenant_partner_id,
                });

                if (result.success) {
                    this.showMysteryBoxModal = false;

                    this.$notify?.({
                        title: '🎁 Бокс добавлен!',
                        text: `Анонимный бокс на ${amount} ₽ в корзине. Состав — сюрприз!`,
                        type: 'success',
                    });

                    // Небольшая анимация иконки корзины (если у вас есть floating cart)
                    this.$emit('basket-updated');
                } else {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: result.message || 'Не удалось добавить бокс',
                        type: 'error',
                    });
                }
            } catch (error) {
                console.error('Ошибка добавления анонимного бокса:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Произошла непредвиденная ошибка',
                    type: 'error',
                });
            } finally {
                this.isAddingBoxToCart = false;
            }
        },

        // ==========================================
        // МОДАЛКИ
        // ==========================================
        initCoffeeModal() {
            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    this.coffeeModal = new bootstrap.Modal(document.getElementById('coffeeModal'));
                }
            });
        },

        showCoffee() {
            if (this.coffeeModal) {
                this.coffeeModal.show();
            }
        },

        openFavorites() {
            this.showFavoritesModal = true;
        },

        handleMenuClick(item) {},

        // ==========================================
        // НАВИГАЦИЯ И ВЗАИМОДЕЙСТВИЕ
        // ==========================================
        backToPartners() {
            this.clearMenuData();
            this.$router.push({ name: 'Partners' });
        },

        onCategorySelect(category) {
            this.$nextTick(() => {
                if (category) {
                    this.scrollToCategory(category.id);
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        },

        async onCollectionOpen(collection) {
            try {
                const partnerId = this.selectedPartner?.tenant_partner_id || null;
                const fullCollection = await this.loadCollection({
                    id: collection.id,
                    partner_id: partnerId
                });
                this.$refs.collectionModal?.open(fullCollection || collection);
            } catch (error) {
                console.error('Ошибка открытия коллекции:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить коллекцию',
                    type: 'error',
                });
            }
        },

        onSearch(query) {
            this.setSearch(query);
        },

        async onLoadMore(categoryId) {
            try {
                const partnerId = this.selectedPartner?.tenant_partner_id || null;
                await this.loadMoreProducts(categoryId, partnerId);
            } catch (error) {
                console.error('Ошибка загрузки:', error);
            }
        },

        onSwipeLeft() {
            this.$router.push({ name: 'Menu' });
        },
        onSwipeRight() {
            this.$router.push({ name: 'ShopCart' });
        },

        scrollToCategory(categoryId) {
            const element = document.getElementById(`cat-${categoryId}`);
            if (element) {
                const headerOffset = 80;
                const elementPosition = element.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
            }
        },
    },
};
</script>

<style scoped>
/* ==========================================
   ОБЩИЙ КОНТЕЙНЕР
   ========================================== */
.shop-container {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

.menu-container {
    min-height: 100vh;
}

/* ==========================================
   ПЕРЕКЛЮЧАТЕЛЬ РЕЖИМОВ
   ========================================== */
.mode-switcher {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--bs-body-bg);
    padding: 12px 16px;
    border-bottom: 1px solid var(--bs-border-color);
    backdrop-filter: blur(10px);
}

.switcher-container {
    display: flex;
    gap: 8px;
    background: var(--bs-secondary-bg, #f5f5f5);
    padding: 4px;
    border-radius: 14px;
    max-width: 400px;
    margin: 0 auto;
}

.switcher-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.switcher-btn:hover:not(.active) {
    color: var(--bs-body-color);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.switcher-btn.active {
    background: var(--bs-body-bg);
    color: var(--bs-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.switcher-btn i {
    font-size: 1rem;
}

/* ==========================================
   СЕКЦИЯ БРОНИРОВАНИЯ
   ========================================== */
.booking-section {
    padding: 16px;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* ==========================================
   ЗАГРУЗКА
   ========================================== */
.loading-container {
    min-height: 75vh;
    background: transparent;
}

.loading-content {
    max-width: 400px;
    padding: 2rem;
}

.loading-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.loading-subtitle {
    font-size: 0.9rem;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* ==========================================
   КОЛЛЕКЦИИ
   ========================================== */
.collections-section {
    margin-bottom: 24px;
}

/* ==========================================
   🎁 БАННЕР АНОНИМНОГО БОКСА
   ========================================== */
.mystery-box-banner-wrapper {
    margin-bottom: 24px;
    position: relative;
}

.mystery-box-banner {
    position: relative;
    border-radius: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    background-size: 200% 200%;
    animation: gradientShift 8s ease infinite;
    color: white;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 8px 24px rgba(118, 75, 162, 0.35);
    isolation: isolate;
}

.mystery-box-banner:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(118, 75, 162, 0.45);
}

.mystery-box-banner:active {
    transform: translateY(0);
}

.mystery-box-glow {
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
    border-radius: 50%;
    animation: floatGlow 6s ease-in-out infinite;
    z-index: -1;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes floatGlow {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.6; }
    50% { transform: translate(-20px, 20px) scale(1.15); opacity: 0.9; }
}

.mystery-box-content {
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    z-index: 2;
}

.mystery-box-icon-wrapper {
    position: relative;
    flex-shrink: 0;
}

.mystery-box-icon {
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.4);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    animation: pulseGift 2.5s ease-in-out infinite;
}

@keyframes pulseGift {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.08) rotate(-5deg); }
}

.gift-emoji {
    font-size: 32px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.sparkles {
    position: absolute;
    top: -6px;
    left: -6px;
    right: -6px;
    bottom: -6px;
    pointer-events: none;
}

.sparkle {
    position: absolute;
    font-size: 14px;
    animation: twinkle 2s ease-in-out infinite;
}

.sparkle.s1 {
    top: -8px;
    right: -8px;
    animation-delay: 0s;
}

.sparkle.s2 {
    top: 50%;
    left: -10px;
    animation-delay: 0.7s;
}

.sparkle.s3 {
    bottom: -8px;
    right: 50%;
    animation-delay: 1.4s;
}

@keyframes twinkle {
    0%, 100% { opacity: 0.3; transform: scale(0.8); }
    50% { opacity: 1; transform: scale(1.2); }
}

.mystery-box-text {
    flex: 1;
    min-width: 0;
}

.mystery-box-label {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.25);
    padding: 3px 10px;
    border-radius: 20px;
    margin-bottom: 6px;
    backdrop-filter: blur(5px);
}

.mystery-box-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.mystery-box-description {
    font-size: 0.82rem;
    margin: 0;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.92);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mystery-box-cta {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    background: white;
    border-radius: 50%;
    color: #764ba2;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s ease;
}

.mystery-box-banner:hover .mystery-box-cta {
    transform: translateX(3px);
}

.cta-text {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1;
    margin-bottom: 2px;
}

.mystery-box-cta i {
    font-size: 0.8rem;
}

/* ==========================================
   МОДАЛКА КОФЕ
   ========================================== */
.coffee-modal {
    border-radius: 20px;
    border: none;
    overflow: hidden;
}

.coffee-modal .modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.coffee-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    box-shadow: 0 4px 12px rgba(111, 78, 55, 0.3);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .switcher-btn {
        font-size: 0.85rem;
        padding: 8px 12px;
    }

    .switcher-btn span {
        display: none;
    }

    .switcher-btn i {
        font-size: 1.2rem;
    }

    .mystery-box-banner {
        padding: 16px;
    }

    .mystery-box-icon {
        width: 56px;
        height: 56px;
    }

    .gift-emoji {
        font-size: 28px;
    }

    .mystery-box-title {
        font-size: 1.1rem;
    }

    .mystery-box-description {
        font-size: 0.78rem;
    }

    .mystery-box-cta {
        width: 46px;
        height: 46px;
    }
}

@media (max-width: 380px) {
    .mystery-box-content {
        gap: 12px;
    }
}
</style>
