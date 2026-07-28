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
        <ShopMenu v-if="currentMode === 'shop'" />

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
        <!-- 🆕 МОДАЛКА: ИЗБРАННОЕ (вынесена в компонент) -->
        <!-- ========================================== -->
        <FavoritesModal v-model="showFavoritesModal" />

    </div>
</template>

<script>
import ShopMenu from "@/MobileClient/Components/Shop/Menu/ShopMenu.vue";
import TableBookingPlanner from "@/MobileClient/Components/Shop/Booking/TableBookingPlanner.vue";
import CoffeeProgress from "@/MobileClient/Components/Shop/CoffeeProgress.vue";
import { useBasketStore } from "@/MobileClient/stores/Shop/basket.js";
import { useFavorites } from '@/MobileClient/Composables/useFavorites.js';
import FloatingMenu from '@/MobileClient/Components/Shop/FloatingMenu.vue';
import FavoritesModal from '@/MobileClient/Components/Shop/Favorites/FavoritesModal.vue';

export default {
    name: "ShopContainer",

    components: {
        ShopMenu,
        TableBookingPlanner,
        CoffeeProgress,
        FloatingMenu,
        FavoritesModal,
    },

    setup() {
        const basketStore = useBasketStore();
        const favoritesStore = useFavorites();

        return {
            basketStore,
            favoritesStore,
        };
    },

    data() {
        return {
            currentMode: 'shop',
            coffeeModal: null,
            showFavoritesModal: false, // 🆕 Управление модалкой избранного
        };
    },

    computed: {
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

        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
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
    },

    mounted() {
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
        initCoffeeModal() {
            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    this.coffeeModal = new bootstrap.Modal(document.getElementById('coffeeModal'));
                }
            });
        },

        handleMenuClick(item) {
            // Действия уже выполняются через item.action
        },

        async loadBasketData() {
            try {
                await this.basketStore.loadProductsInBasket();
            } catch (error) {
                console.error('Ошибка загрузки корзины:', error);
            }
        },

        showCoffee() {
            if (this.coffeeModal) {
                this.coffeeModal.show();
            }
        },

        // 🆕 Открытие модалки избранного
        openFavorites() {
            this.showFavoritesModal = true;
        },
    },
};
</script>

<style scoped>
.shop-container {
    min-height: 100vh;
    background: var(--bs-body-bg);
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
   МОДАЛКА КОФЕ (оставляем Bootstrap)
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
