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
            // Товары
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

            // Корзина и избранное
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
        // 🆕 Guard: если партнёры включены, но не выбраны — редирект
        if (this.hasPartners && !this.selectedPartner) {
            this.$router.replace({ name: 'Partners' });
            return;
        }

        this.loadInitialData();
        this.loadBasketData();
        this.initCoffeeModal();

        // Проверка расписания работы
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

        handleMenuClick(item) {
            // Действия уже выполняются через item.action
        },

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
}
</style>
