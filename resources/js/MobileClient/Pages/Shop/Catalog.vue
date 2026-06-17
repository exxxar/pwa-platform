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
        <div v-else-if="currentMode === 'booking'" class="booking-section">
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
                    <div class="modal-body">
                        <CoffeeProgress />
                    </div>
                </div>
            </div>
        </div>

        <FavoritesButton
            :count="favoritesCount"
            @click="openFavorites"
        />

        <!-- ========================================== -->
        <!-- МОДАЛКА: ИЗБРАННОЕ -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            id="favoritesModal"
            tabindex="-1"
            aria-labelledby="favoritesModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content favorites-modal">
                    <div class="modal-header">
                        <div class="modal-icon favorites-icon">
                            <i class="fa-solid fa-heart"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title" id="favoritesModalLabel">Избранное</h5>
                            <small class="text-muted">
                                {{ favoritesCount > 0
                                ? `${favoritesCount} ${pluralize(favoritesCount, 'товар', 'товара', 'товаров')}`
                                : 'Пока пусто' }}
                            </small>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Закрыть"
                        ></button>
                    </div>
                    <div class="modal-body">

                        <!-- Загрузка -->
                        <div v-if="isLoadingFavorites" class="loading-state">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                            <p class="mt-2 text-muted">Загружаем избранное...</p>
                        </div>

                        <!-- Пустое состояние -->
                        <div v-else-if="favoritesProducts.length === 0" class="empty-state">
                            <div class="empty-icon">
                                <i class="fa-solid fa-heart"></i>
                            </div>
                            <h6 class="empty-title">Избранное пусто</h6>
                            <p class="empty-text">
                                Добавляйте товары в избранное, чтобы быстро находить их
                            </p>
                        </div>

                        <!-- Список товаров -->
                        <div v-else class="favorites-grid">
                            <div
                                v-for="product in favoritesProducts"
                                :key="product.id"
                                class="favorite-item"
                            >
                                <ProductCard :item="product" />
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import ShopMenu from "@/MobileClient/Components/Shop/Menu/ShopMenu.vue";
import TableBookingPlanner from "@/MobileClient/Components/Shop/Booking/TableBookingPlanner.vue";
import CoffeeProgress from "@/MobileClient/Components/Shop/CoffeeProgress.vue";
import ProductCard from "@/MobileClient/Components/Shop/ProductCard.vue";
import { useBasketStore } from "@/MobileClient/stores/Shop/basket.js";
import { useFavoritesStore } from "@/MobileClient/stores/Shop/favorites.js";
import FavoritesButton from '@/MobileClient/Components/Shop/FavoritesButton.vue';

export default {
    name: "ShopContainer",

    components: {
        ShopMenu,
        TableBookingPlanner,
        CoffeeProgress,
        ProductCard,
        FavoritesButton
    },

    setup() {
        // ✅ Правильная инициализация Pinia stores
        const basketStore = useBasketStore();
        const favoritesStore = useFavoritesStore();

        return {
            basketStore,
            favoritesStore,
        };
    },

    data() {
        return {
            currentMode: 'shop',
            isLoadingFavorites: false,
            favoritesProducts: [],
            coffeeModal: null,
            favoritesModal: null,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        // Есть ли функция бронирования
        hasBooking() {
            return (this.settings?.tables_variants || []).length > 0;
        },

        // Кофе включен
        isCoffeeEnabled() {
            return this.settings?.coffee?.enabled === true;
        },

        // Количество товаров в корзине
        cartTotalCount() {
            return this.basketStore.cartTotalCount || 0;
        },

        // Сумма корзины
        cartTotalPrice() {
            return this.basketStore.cartTotalPrice || 0;
        },

        // Количество избранных товаров
        favoritesCount() {
            return this.favoritesStore.getFavorites?.length || 0;
        },

        // Можно ли покупать (проверка графика работы)
        canBuy() {
            if (typeof window.isCorrectSchedule !== 'function') return true;
            if (!window.isCorrectSchedule(this.settings?.schedule)) return true;
            return this.settings?.is_work || this.settings?.can_buy_after_closing;
        },
    },

    mounted() {
        this.loadBasketData();
        this.initModals();

        // Если нельзя покупать — открываем модалку с графиком
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
        // Очищаем экземпляры модалок
        if (this.coffeeModal) this.coffeeModal.dispose();
        if (this.favoritesModal) this.favoritesModal.dispose();
    },

    methods: {
        // Инициализация Bootstrap модалок
        initModals() {
            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    this.coffeeModal = new bootstrap.Modal(document.getElementById('coffeeModal'));
                    this.favoritesModal = new bootstrap.Modal(document.getElementById('favoritesModal'));
                }
            });
        },

        // Загрузка данных корзины
        async loadBasketData() {
            try {
                await this.basketStore.loadProductsInBasket();
            } catch (error) {
                console.error('Ошибка загрузки корзины:', error);
            }
        },

        // Открытие модалки кофе
        showCoffee() {
            if (this.coffeeModal) {
                this.coffeeModal.show();
            }
        },

        // Переход в корзину
        goToCart() {
            this.$router.push({ name: 'Cart' });
        },

        // Переход к бронированию
        goToBooking() {
            this.$router.push({ name: 'TableBooking' });
        },

        // Открытие модалки избранного
        async openFavorites() {
            if (this.favoritesModal) {
                this.favoritesModal.show();
            }

            // Загружаем избранное
            await this.loadFavoritesProducts();
        },

        // Загрузка списка избранных товаров
        async loadFavoritesProducts() {
            this.isLoadingFavorites = true;

            try {
                // TODO: Замени на Pinia action
                // const response = await this.favoritesStore.loadFavorites();
                // this.favoritesProducts = response.data || [];

                // Имитация запроса (удали после подключения API)
                await new Promise(resolve => setTimeout(resolve, 800));
                this.favoritesProducts = this.favoritesStore.getFavorites || [];

            } catch (error) {
                console.error('Ошибка загрузки избранного:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить избранное',
                    type: 'error',
                });
            } finally {
                this.isLoadingFavorites = false;
            }
        },

        // Склонение слов
        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
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
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* ==========================================
   МОДАЛКИ
   ========================================== */
.coffee-modal,
.favorites-modal {
    border-radius: 20px;
    border: none;
    overflow: hidden;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.coffee-icon {
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    box-shadow: 0 4px 12px rgba(111, 78, 55, 0.3);
}

.favorites-icon {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
    box-shadow: 0 4px 12px rgba(238, 9, 121, 0.3);
}

.modal-title {
    font-weight: 700;
    margin-bottom: 2px;
    color: var(--bs-body-color);
}

.modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

/* Состояния загрузки и пустоты */
.loading-state,
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
}

.empty-title {
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin: 0;
    line-height: 1.5;
}

/* Сетка избранного */
.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}

.favorite-item {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

    .favorites-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
}
</style>
