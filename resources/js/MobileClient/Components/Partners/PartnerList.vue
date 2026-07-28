<template>
    <div class="partners-page-modern">

        <!-- ========================================== -->
        <!-- MODERN STICKY TABS -->
        <!-- ========================================== -->
        <div class="modern-tabs-wrapper">
            <div class="modern-tabs">
                <button
                    class="tab-pill"
                    :class="{ 'active': activeMainTab === 'establishments' }"
                    @click="activeMainTab = 'establishments'"
                >
                    <i class="fa-solid fa-store"></i>
                    <span>Заведения</span>
                </button>
                <button
                    class="tab-pill"
                    :class="{ 'active': activeMainTab === 'orders' }"
                    @click="activeMainTab = 'orders'"
                >
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Заказы</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ВКЛАДКА: ЗАВЕДЕНИЯ -->
        <!-- ========================================== -->
        <div v-if="activeMainTab === 'establishments'">
            <!-- HERO SECTION -->
            <div class="hero-section" :style="heroBackgroundStyle">
                <div class="hero-orb orb-1"></div>
                <div class="hero-orb orb-2"></div>

                <div class="hero-images">
                    <div class="food-image food-image-1"><img :src="dynamicHeroImages.image1" alt="Food 1"></div>
                    <div class="food-image food-image-2"><img :src="dynamicHeroImages.image2" alt="Food 2"></div>
                    <div class="food-image food-image-3"><img :src="dynamicHeroImages.image3" alt="Food 3"></div>
                    <div class="food-image food-image-4"><img :src="dynamicHeroImages.image4" alt="Food 4"></div>
                </div>

                <div class="hero-content">
                    <h2 class="hero-title">{{ dynamicHeroSettings.title }}</h2>
                    <p class="hero-subtitle">{{ dynamicHeroSettings.subtitle }}</p>

                    <div class="search-container">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input v-model="searchQuery" type="text" class="search-input"
                                   :placeholder="dynamicHeroSettings.searchPlaceholder">
                            <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''"><i
                                class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>

                    <!-- Динамические категории (бывшие кухни в шапке, если нужны) -->
                    <div class="categories-row" v-if="dynamicCategories.length > 0">
                        <button v-for="category in dynamicCategories" :key="category.id" class="category-pill"
                                :class="{ 'active': selectedCategory === category.id }"
                                @click="selectCategory(category.id)">
                            <span class="category-icon">{{ category.icon || '🍽️' }}</span>
                            <span class="category-name">{{ category.name }}</span>
                        </button>
                    </div>

                    <!-- Динамические сервисы -->
                    <div class="service-features-wrapper" v-if="dynamicServices.length > 0">
                        <div class="service-features">
                            <div v-for="feature in dynamicServices" :key="feature.id" class="feature-item">
                                <div class="feature-icon-wrapper"><i :class="feature.icon"></i></div>
                                <span class="feature-label">{{ feature.label }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="view-mode-toggle">
                        <button class="view-btn" :class="{ 'active': viewMode === 'list' }" @click="viewMode = 'list'"
                                title="Список"><i class="fa-solid fa-list"></i></button>
                        <button class="view-btn" :class="{ 'active': viewMode === 'grid' }" @click="viewMode = 'grid'"
                                title="Сетка"><i class="fa-solid fa-grip"></i></button>
                    </div>
                </div>
            </div>

            <!-- CUISINES SLIDER (Динамический заголовок и данные) -->
            <div class="cuisines-section">
                <div class="section-header">
                    <h3 class="section-title">{{ uiSettings.cuisines_title || 'Популярные кухни' }}</h3>
                    <button class="view-all-btn" @click="viewAllCuisines">
                        <span>Смотреть все</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
                <!-- В блоке CUISINES SLIDER -->
                <div class="cuisines-slider">
                    <div
                        v-for="cuisine in dynamicCuisines"
                        :key="cuisine.id"
                        class="cuisine-card"
                        :class="{ 'active': selectedCuisine === cuisine.slug }"
                        @click="selectCuisine(cuisine)"
                    >
                        <div class="cuisine-image-wrapper">
                            <img :src="cuisine.image || getDefaultImage('pizza')" :alt="cuisine.name"
                                 class="cuisine-image">
                            <div class="cuisine-overlay"></div>
                        </div>
                        <div class="cuisine-info"><span class="cuisine-name">{{ cuisine.name }}</span></div>
                    </div>
                </div>
            </div>

            <!-- ДИНАМИЧЕСКИЕ ФИЛЬТРЫ -->
            <div class="filter-tabs-container" v-if="dynamicFilters.length > 0">
                <div class="filter-tabs">
                    <button
                        v-for="filterItem in dynamicFilters"
                        :key="filterItem.id"
                        class="filter-tab"
                        :class="{ 'active': filter === filterItem.slug }"
                        @click="filter = filterItem.slug"
                    >
                        {{ filterItem.name }}
                    </button>
                </div>
            </div>

            <div class="container px-3">
                <div v-if="isPartnersLoading" class="loading-state">
                    <div v-for="i in 4" :key="i" class="skeleton-card">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-line w-60"></div>
                            <div class="skeleton-line w-40"></div>
                            <div class="skeleton-line w-80"></div>
                        </div>
                    </div>
                </div>

                <div v-else-if="filteredPartners.length === 0" class="empty-state">
                    <div class="empty-icon-wrapper">
                        <div class="empty-icon"><i class="fa-solid fa-handshake"></i></div>
                    </div>
                    <h5 class="empty-title">{{ searchQuery ? 'Ничего не найдено' : 'Нет партнёров' }}</h5>
                    <p class="empty-text">{{
                            searchQuery ? `По запросу "${searchQuery}" партнёров не найдено` : 'Партнёры скоро появятся'
                        }}</p>
                    <button v-if="searchQuery" class="empty-btn" @click="resetFilters"><i
                        class="fa-solid fa-rotate-left me-2"></i> Сбросить фильтры
                    </button>
                </div>

                <div v-else class="partners-list">
                    <div class="partners-grid" :class="{ 'grid-view': viewMode === 'grid' }">


                        <div  v-for="partner in filteredPartners">

                            <PartnerCard

                                :key="partner.id"
                                :partner="partner"
                                :is-favorite="isFavorite(partner.id)"
                                :view-mode="viewMode"
                                @select="selectPartner"
                                @toggle-favorite="toggleFavorite"
                                @show-location="showPartnerLocation"
                            />
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ВКЛАДКА: ЗАКАЗЫ (без изменений) -->
        <!-- ========================================== -->
        <div v-else-if="activeMainTab === 'orders'" class="orders-tab-content">
            <div class="orders-hero">
                <div class="hero-background"></div>
                <div class="hero-content-orders">
                    <div class="hero-icon-orders"><i class="fa-solid fa-bag-shopping"></i></div>
                    <h2 class="hero-title-orders">Мои заказы</h2>
                    <p class="hero-subtitle-orders">
                        {{ (orders || []).length > 0 ? `Всего заказов: ${totalOrdersCount}` : 'История ваших покупок' }}
                    </p>
                </div>
            </div>

            <div class="orders-tabs-wrapper">
                <div class="orders-tabs-container">
                    <button class="orders-tab-item" :class="{ active: ordersTab === 0 }" @click="switchOrdersTab(0)">
                        <i class="fa-solid fa-receipt"></i><span>Заказы</span>
                        <span v-if="orders && orders.length" class="tab-badge">{{ orders.length }}</span>
                    </button>
                    <button class="orders-tab-item" :class="{ active: ordersTab === 1 }" @click="switchOrdersTab(1)">
                        <i class="fa-solid fa-star"></i><span>Отзывы</span>
                        <span v-if="reviews && reviews.length" class="tab-badge">{{ reviews.length }}</span>
                    </button>
                </div>
            </div>

            <div class="container px-3">
                <div v-if="ordersTab === 0" class="info-banner">
                    <div class="info-icon"><i class="fa-solid fa-circle-info"></i></div>
                    <div class="info-text"><strong>Повторный заказ</strong> — товары из стоп-листа заведения будут
                        автоматически исключены
                    </div>
                </div>

                <div v-if="ordersTab === 0">
                    <div v-if="isLoadingOrders" class="loading-state">
                        <div v-for="i in 3" :key="i" class="skeleton-card">
                            <div class="skeleton-line w-60"></div>
                            <div class="skeleton-line w-40"></div>
                            <div class="skeleton-line w-80"></div>
                        </div>
                    </div>

                    <div v-else-if="!orders || orders.length === 0" class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                        <h5 class="empty-title">Заказов пока нет</h5>
                        <p class="empty-text">Оформите первый заказ, и он появится здесь</p>
                        <button class="empty-btn" @click="activeMainTab = 'establishments'">
                            <i class="fa-solid fa-store me-2"></i> Перейти в каталог
                        </button>
                    </div>

                    <div v-else class="orders-list">
                        <div v-for="(group, dateKey) in groupedOrders" :key="dateKey" class="orders-group">
                            <div class="group-header">
                                <div class="group-date">{{ group.label }}</div>
                                <div class="group-count">{{ group.orders.length }}
                                    {{ pluralize(group.orders.length, 'заказ', 'заказа', 'заказов') }}
                                </div>
                            </div>
                            <div v-for="order in group.orders" :key="order.id" class="order-card">
                                <div class="order-header">
                                    <div class="order-number">
                                        <span class="order-number-label">Заказ</span>
                                        <span class="order-number-value">#{{ order.id }}</span>
                                    </div>
                                    <div class="order-status" :class="getStatusClass(order)">{{
                                            getStatusText(order)
                                        }}
                                    </div>
                                </div>
                                <div class="order-meta">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>{{ formatDateTime(order.created_at) }}</span>
                                </div>
                                <div class="order-products" v-if="getOrderProducts(order).length > 0">
                                    <div v-for="(product, i) in getOrderProducts(order).slice(0, 3)" :key="i"
                                         class="product-item">
                                        <span class="product-qty">{{ getProductQty(product) }}×</span>
                                        <span class="product-name">{{ getProductName(product) }}</span>
                                    </div>
                                    <div v-if="getOrderProducts(order).length > 3" class="product-more">
                                        + ещё {{ getOrderProducts(order).length - 3 }} товаров
                                    </div>
                                </div>
                                <div v-if="getOrderTotal(order)" class="order-total">
                                    <span>Итого:</span>
                                    <span class="total-value">{{ formatPrice(getOrderTotal(order)) }}</span>
                                </div>
                                <div class="order-actions">
                                    <button class="repeat-btn" @click.stop="repeatOrderHandler(order)">
                                        <i class="fa-solid fa-arrow-rotate-right"></i>
                                        <span>Повторить заказ</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="ordersTab === 1">
                    <div v-if="isLoadingReviews" class="loading-state">
                        <div v-for="i in 3" :key="i" class="skeleton-card">
                            <div class="skeleton-line w-40"></div>
                            <div class="skeleton-line w-80"></div>
                        </div>
                    </div>
                    <div v-else-if="!reviews || reviews.length === 0" class="empty-state">
                        <div class="empty-icon reviews-icon"><i class="fa-solid fa-comment-dots"></i></div>
                        <h5 class="empty-title">Отзывов пока нет</h5>
                        <p class="empty-text">Поделитесь мнением о товарах — оставьте первый отзыв</p>
                    </div>
                    <div v-else class="reviews-list">
                        <ReviewCard v-for="(review, index) in reviews" :key="review.id || index"
                                    v-model="reviews[index]" :need-product="true"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- BOTTOM SHEET MODAL: ВСЕ КАТЕГОРИИ -->
        <!-- ========================================== -->
        <transition name="bottom-sheet">
            <div v-if="showCuisinesModal" class="cuisine-modal-backdrop" @click="closeCuisinesModal">
                <div class="cuisine-modal-sheet" @click.stop>
                    <div class="modal-handle"></div>

                    <div class="modal-header">
                        <!-- Динамический заголовок модалки -->
                        <h3 class="modal-title">Все
                            {{ uiSettings.cuisines_title ? uiSettings.cuisines_title.toLowerCase() : 'кухни' }}</h3>
                        <button class="modal-close-btn" @click="closeCuisinesModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- В блоке BOTTOM SHEET MODAL: ВСЕ КАТЕГОРИИ -->
                    <div class="cuisine-modal-grid">
                        <div
                            v-for="cuisine in dynamicCuisines"
                            :key="cuisine.id"
                            class="cuisine-modal-card"
                            :class="{ 'active': selectedCuisine === cuisine.slug }"
                            @click="selectCuisineAndClose(cuisine)"
                        >
                            <div class="cuisine-modal-image-wrapper">
                                <img :src="cuisine.image || getDefaultImage('pizza')" :alt="cuisine.name"
                                     class="cuisine-modal-image">
                                <div class="cuisine-modal-overlay"></div>
                            </div>
                            <div class="cuisine-modal-info">
                                <span class="cuisine-modal-name">{{ cuisine.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Модальное окно с картой партнера -->
        <PartnerLocationModal
            :is-visible="showLocationModal"
            :partner="selectedPartnerForMap"
            @close="showLocationModal = false"
        />
    </div>
</template>

<script>
import {usePartnersStore} from "@/MobileClient/stores/Shop/partners.js";
import {useOrders} from "@/MobileClient/composables/useOrders.js";
import PartnerCard from "@/MobileClient/Components/Partners/PartnerCard.vue";
import ReviewCard from "@/MobileClient/Components/Shop/Reviews/ReviewCard.vue";
import PartnerLocationModal from "@/MobileClient/Components/Partners/PartnerLocationModal.vue";

export default {
    name: "PartnerListModern",

    components: {
        PartnerLocationModal,
        PartnerCard,
        ReviewCard,
    },

    emits: ['select'],

    setup() {
        const partnerStore = usePartnersStore();
        const orderComposable = useOrders();
        return {partnerStore, ...orderComposable};
    },

    data() {
        return {

            showLocationModal: false,
            selectedPartnerForMap: null,

            activeMainTab: 'establishments',
            searchQuery: '',
            selectedCategory: null, // 🆕 ID выбранной категории
            selectedCuisine: null,  // 🆕 Slug выбранной кухни (тега)

            filter: 'nearby', // Будет перезаписано в mounted, если есть настройки
            viewMode: 'list',
            partnerList: [],
            isPartnersLoading: false,

            ordersTab: 0,
            reviewsLoaded: false,
            showCuisinesModal: false,
        };
    },

    computed: {
        // 🆕 Безопасное получение UI настроек
        uiSettings() {
            return window.Tenant?.settings?.partners?.ui || {};
        },
        dynamicCuisines() {
            if (this.uiSettings.cuisines && this.uiSettings.cuisines.length > 0) {
                return this.uiSettings.cuisines;
            }
            return [
                {id: 1, name: 'Итальянская', slug: 'italian', image: this.getDefaultImage('pizza')},
                {id: 2, name: 'Японская', slug: 'japanese', image: this.getDefaultImage('sushi')},
                {id: 3, name: 'Американская', slug: 'american', image: this.getDefaultImage('burger')}
            ];
        },

        // 🆕 Динамические категории (вместо кухонь)
        dynamicCategories() {
            const settings = this.uiSettings.categories || this.uiSettings.cuisines; // обратная совместимость
            if (settings && settings.length > 0) {
                return settings;
            }
            // Fallback список, если в настройках пусто
            return [
                { id: 1, name: 'Пицца', icon: '🍕', slug: 'pizza' },
                { id: 2, name: 'Бургеры', icon: '🍔', slug: 'burgers' },
                { id: 3, name: 'Шаурма', icon: '🌯', slug: 'shawarma' },
                { id: 4, name: 'Суши и роллы', icon: '🍣', slug: 'sushi' },
                { id: 5, name: 'Шашлык', icon: '🍖', slug: 'shashlik' },
                { id: 6, name: 'Хинкали', icon: '🥟', slug: 'khinkali' },
                { id: 7, name: 'Хачапури', icon: '🫓', slug: 'khachapuri' },
                { id: 8, name: 'Лапша / Wok', icon: '🍜', slug: 'wok' },
                { id: 9, name: 'Паста', icon: '🍝', slug: 'pasta' },
                { id: 10, name: 'Донер / Кебаб', icon: '🥙', slug: 'doner' },
                { id: 11, name: 'Тако и буррито', icon: '🌮', slug: 'taco' },
                { id: 12, name: 'Пельмени и вареники', icon: '🥟', slug: 'dumplings' },
                { id: 13, name: 'Обеды', icon: '🍱', slug: 'lunch' },
                { id: 14, name: 'Морепродукты', icon: '🦞', slug: 'seafood' },
                { id: 15, name: 'Закуски', icon: '🍟', slug: 'snacks' },
                { id: 16, name: 'Пивные закуски', icon: '🍺', slug: 'beer_snacks' },
                { id: 17, name: 'Салаты', icon: '🥗', slug: 'salads' },
                { id: 18, name: 'ПП', icon: '🥑', slug: 'pp' }
            ];
        },

        // 🆕 Динамические настройки Hero
        dynamicHeroSettings() {
            const hero = this.uiSettings.hero || {};
            return {
                title: hero.title || 'Вкусные блюда из любимых заведений',
                subtitle: hero.subtitle || 'Доставка из кафе и ресторанов быстро и удобно',
                searchPlaceholder: hero.search_placeholder || 'Поиск блюд и заведений',
                backgroundImage: hero.backgroundImage || null,
                backgroundColor: 'linear-gradient(165deg, rgba(255, 248, 240, 0.95) 0%, rgba(255, 179, 138, 0.85) 30%, rgba(255, 140, 97, 0.75) 60%, rgba(118, 75, 162, 0.65) 100%)'
            };
        },
        // 🆕 Динамические картинки Hero
        dynamicHeroImages() {
            const hero = this.uiSettings.hero || {};
            return {
                image1: hero.bg_image_1 || this.getDefaultImage('pizza'),
                image2: hero.bg_image_2 || this.getDefaultImage('salad'),
                image3: hero.bg_image_3 || this.getDefaultImage('pasta'),
                image4: hero.bg_image_4 || this.getDefaultImage('dessert'),
            };
        },

        heroBackgroundStyle() {
            if (this.dynamicHeroSettings.backgroundImage) {
                return {
                    backgroundImage: `url(${this.dynamicHeroSettings.backgroundImage})`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center'
                };
            }
            return {background: this.dynamicHeroSettings.backgroundColor};
        },




        // 🆕 Динамические сервисы
        dynamicServices() {
            if (this.uiSettings.services && this.uiSettings.services.length > 0) {
                return this.uiSettings.services;
            }
            return [
                {id: 1, label: 'Быстрая доставка', icon: 'fa-solid fa-bolt'},
                {id: 2, label: 'Большой выбор', icon: 'fa-solid fa-bag-shopping'},
                {id: 3, label: 'Работа 24/7', icon: 'fa-solid fa-clock'},
                {id: 4, label: 'Все виды оплат', icon: 'fa-solid fa-home'}
            ];
        },

        // 🆕 Динамические фильтры (уровень ПАРТНЕРА, а не товара)
        dynamicFilters() {
            if (this.uiSettings.filters && this.uiSettings.filters.length > 0) {
                return this.uiSettings.filters;
            }
            return [
                {id: 1, name: 'Все', slug: 'all'},
                {id: 2, name: 'Избранные', slug: 'favorites'},
                {id: 3, name: 'С акциями', slug: 'promo'},
                {id: 4, name: 'Популярные', slug: 'popular'}
            ];
        },

        activePartners() {
            return (this.partnerList || []).filter(p => p.is_active);
        },

        filteredPartners() {
            // Начинаем со всех активных партнеров
            let list = [...this.activePartners];

            // ==========================================
            // 1. ФИЛЬТР ПО ВКЛАДКЕ (Все / Избранные / Акции / Популярные)
            // ==========================================
            if (this.filter === 'favorites') {
                list = list.filter(p => this.favoriteIds.includes(p.id));
            } else if (this.filter === 'promo') {
                list = list.filter(p => {
                    // Проверяем наличие тега 'promo' или 'sale', либо флага has_promo
                    const hasPromoTag = Array.isArray(p.tags) && (p.tags.includes('promo') || p.tags.includes('sale') || p.tags.includes('акция'));
                    return hasPromoTag || p.has_promo === true || p.discount > 0;
                });
            } else if (this.filter === 'popular') {
                list = list.filter(p => {
                    // Популярные: высокий рейтинг (>= 4.5) или заданная позиция в выдаче (order_position > 0)
                    return (p.rating || 0) >= 4.5 || (p.order_position || 0) > 0;
                });
            }

            // ==========================================
            // 2. ФИЛЬТР ПО ПОИСКОВОМУ ЗАПРОСУ
            // ==========================================
            if (this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase();
                list = list.filter(p =>
                    p.name?.toLowerCase().includes(query) ||
                    p.address?.toLowerCase().includes(query) ||
                    p.description?.toLowerCase().includes(query)
                );
            }

            // ==========================================
            // 3. ФИЛЬТР ПО КАТЕГОРИИ (если выбрана)
            // ==========================================
            if (this.selectedCategory) {
                list = list.filter(p => {
                    if (Array.isArray(p.categories)) {
                        // Поддержка массива объектов {id: 1} или массива ID [1, 2]
                        return p.categories.some(c => c.id === this.selectedCategory || c === this.selectedCategory);
                    }
                    // Поддержка одиночного значения
                    return p.category_id === this.selectedCategory || p.category === this.selectedCategory;
                });
            }

            // ==========================================
            // 4. ФИЛЬТР ПО КУХНЕ/ТЕГУ (если выбран)
            // ==========================================
            if (this.selectedCuisine) {
                list = list.filter(p => {
                    return Array.isArray(p.tags) && p.tags.includes(this.selectedCuisine);
                });
            }

            // ==========================================
            // 5. ФИНАЛЬНАЯ СОРТИРОВКА
            // ==========================================
            return list.sort((a, b) => {
                // Сначала по order_position (чем выше, тем выше в списке), затем по имени
                if ((b.order_position || 0) !== (a.order_position || 0)) {
                    return (b.order_position || 0) - (a.order_position || 0);
                }
                return (a.name || '').localeCompare(b.name || '');
            });
        },
        favoriteIds() {
            const self = window.TenantUser || null;


            return self?.settings?.fav_partners || [];
        },

        totalOrdersCount() {
            return this.orders_paginate_object?.total || (this.orders || []).length;
        },

        groupedOrders() {
            const groups = {};
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            const ordersList = this.orders || [];

            ordersList.forEach(order => {
                const date = new Date(order.created_at);
                let key, label;

                if (date.toDateString() === today.toDateString()) {
                    key = 'today';
                    label = 'Сегодня';
                } else if (date.toDateString() === yesterday.toDateString()) {
                    key = 'yesterday';
                    label = 'Вчера';
                } else {
                    key = date.toISOString().split('T')[0];
                    label = date.toLocaleDateString('ru-RU', {
                        day: 'numeric',
                        month: 'long',
                        year: date.getFullYear() !== today.getFullYear() ? 'numeric' : undefined
                    });
                }

                if (!groups[key]) groups[key] = {key, label, orders: []};
                groups[key].orders.push(order);
            });

            return Object.fromEntries(Object.entries(groups).sort((a, b) => {
                if (a[0] === 'today') return -1;
                if (b[0] === 'today') return 1;
                if (a[0] === 'yesterday') return -1;
                if (b[0] === 'yesterday') return 1;
                return b[0].localeCompare(a[0]);
            }));
        }
    },

    mounted() {

        this.filter = this.dynamicFilters.length > 0 ? this.dynamicFilters[0].slug : 'all';

        // 🆕 Инициализируем фильтр первым элементом из динамических настроек
        if (this.dynamicFilters.length > 0) {
            this.filter = this.dynamicFilters[0].slug;
        }

        this.loadInitialData();
        if (this.loadOrders)
            this.loadOrders({page: 0, size: 20});
    },

    methods: {

        showPartnerLocation(partner) {
            this.selectedPartnerForMap = partner;
            this.showLocationModal = true;
        },

        getDefaultImage(type) {
            const images = {
                pizza: '/images/fastoran/pizza.png',
                salad: '/images/fastoran/salad.png',
                pasta: '/images/fastoran/pasta.png',
                dessert: '/images/fastoran/dessert.png',
                sushi: '/images/fastoran/suschi.png',
                burger: '/images/fastoran/burger.png',
                khachapuri: '/images/fastoran/khachapuri.png'
            };
            return images[type] || images.pizza;
        },

        async loadInitialData() {
            const tenant = window.Tenant || null;
            if (tenant?.partners?.length > 0) {
                this.partnerList = tenant.partners;
            } else {
                await this.loadPartners();
            }
        },

        async loadPartners(pageIndex = 0) {
            this.isPartnersLoading = true;
            try {
                await this.partnerStore.loadPartners({dataObject: {}, page: pageIndex});
                this.partnerList = this.partnerStore.getPartners || [];
            } catch (error) {
                console.error('Ошибка загрузки партнёров:', error);
            } finally {
                this.isPartnersLoading = false;
            }
        },

        // 🆕 Выбор категории (сбрасывает выбор кухни, чтобы фильтры не конфликтовали)
        selectCategory(categoryId) {
            if (this.selectedCategory === categoryId) {
                this.selectedCategory = null; // Повторный клик сбрасывает фильтр
            } else {
                this.selectedCategory = categoryId;
                this.selectedCuisine = null; // Сбрасываем другой фильтр
            }
        },

        // 🆕 Выбор кухни/тега (сбрасывает выбор категории)
        selectCuisine(cuisine) {
            if (this.selectedCuisine === cuisine.slug) {
                this.selectedCuisine = null; // Повторный клик сбрасывает фильтр
            } else {
                this.selectedCuisine = cuisine.slug;
                this.selectedCategory = null; // Сбрасываем другой фильтр
            }
        },

        viewAllCuisines() {
            this.showCuisinesModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeCuisinesModal() {
            this.showCuisinesModal = false;
            document.body.style.overflow = '';
        },

        selectCuisineAndClose(cuisine) {
            this.selectCuisine(cuisine);
            this.closeCuisinesModal();
        },

        selectPartner(partner) {
            this.$emit('select', partner);
        },

        async toggleFavorite(partnerId) {
            try {
                await this.partnerStore.togglePartnerInFavorites({id: partnerId});
                await this.loadPartners();
            } catch (error) {
                console.error('Ошибка изменения избранного:', error);
            }
        },

        isFavorite(partnerId) {
            return this.favoriteIds.some(id => Number(id) === partnerId);
        },

        // 🆕 Обновленный сброс всех фильтров
        resetFilters() {
            this.searchQuery = '';
            this.selectedCategory = null;
            this.selectedCuisine = null;
            this.filter = 'all'; // 🆕 Сбрасываем динамический фильтр на "Все"
        },

        async switchOrdersTab(index) {
            this.ordersTab = index;
            if (index === 1 && !this.reviewsLoaded && this.loadReviews) {
                await this.loadReviews({page: 0, size: 20});
                this.reviewsLoaded = true;
            }
        },

        async repeatOrderHandler(item) {
            if (this.repeatOrder && this.clearCart && this.addProductToCart) {
                try {
                    const productTitles = (item.product_details?.[0]?.products || []).map(p => p.name || p.title);
                    const resp = await this.repeatOrder({products: productTitles});
                    const currentProducts = resp?.data || resp || [];
                    if (currentProducts.length === 0) {
                        this.$notify?.({title: 'Корзина', text: 'Нет доступных к заказу товаров', type: 'warning'});
                        return;
                    }
                    await this.clearCart();
                    for (const product of currentProducts) await this.addProductToCart(product);
                    this.$notify?.({title: 'Корзина', text: 'Товары добавлены в корзину', type: 'success'});
                } catch (error) {
                    console.error('Ошибка повторного заказа:', error);
                }
            }
        },

        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },

        getStatusClass(order) {
            const status = String(order.status ?? '').toLowerCase();
            if (['0', 'new'].includes(status)) return 'status-new';
            if (['1', '4', '5', 'processing'].includes(status)) return 'status-processing';
            if (['2', 'completed', 'delivered', 'done'].includes(status)) return 'status-completed';
            if (['3', 'cancelled', 'отмен'].includes(status)) return 'status-cancelled';
            return 'status-new';
        },

        getStatusText(order) {
            const status = String(order.status ?? '').toLowerCase();
            if (['0', 'new'].includes(status)) return 'Новый';
            if (['1', 'processing'].includes(status)) return 'В обработке';
            if (['2', 'completed', 'delivered', 'done'].includes(status)) return 'Выполнен';
            if (['3', 'cancelled', 'отмен'].includes(status)) return 'Отменён';
            if (['4'].includes(status)) return 'Готов к доставке';
            if (['5'].includes(status)) return 'Передан на кухню';
            return 'Новый';
        },

        formatDateTime(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('ru-RU', {hour: '2-digit', minute: '2-digit'});
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(price || 0);
        },

        getOrderProducts(order) {
            return order.product_details?.[0]?.products || [];
        },
        getProductQty(product) {
            return product.count || product.quantity || 1;
        },
        getProductName(product) {
            return product.name || product.title || 'Товар';
        },
        getOrderTotal(order) {
            return order.summary_price || order.total || 0;
        },
    },

    beforeUnmount() {
        document.body.style.overflow = '';
    }
};
</script>

<style scoped>
/* Стили остаются точно такими же, как в предыдущей версии,
   так как структура HTML не изменилась, только стала динамической */
.partners-page-modern {
    padding-bottom: 100px;
    background: #FAFAFA;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.modern-tabs-wrapper {
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.modern-tabs {
    display: flex;
    gap: 8px;
    background: rgba(0, 0, 0, 0.04);
    padding: 4px;
    border-radius: 16px;
    max-width: 400px;
    margin: 0 auto;
}

.tab-pill {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    color: #666;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tab-pill i {
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.tab-pill:hover:not(.active) {
    color: #333;
    background: rgba(255, 255, 255, 0.5);
}

.tab-pill.active {
    background: #FFFFFF;
    color: #1A1A1A;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.tab-pill.active i {
    transform: scale(1.1);
    color: #FF6B35;
}

.hero-section {
    position: relative;
    padding: 32px 20px 48px;
    overflow: hidden;
    border-radius: 0 0 32px 32px;
    margin-bottom: 24px;
}

.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.4;
    z-index: 0;
    pointer-events: none;
}

.orb-1 {
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, #FF9A8B 0%, transparent 70%);
    top: -100px;
    right: -50px;
    animation: floatOrb 8s ease-in-out infinite;
}

.orb-2 {
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, #FFD194 0%, transparent 70%);
    bottom: -50px;
    left: -50px;
    animation: floatOrb 10s ease-in-out infinite reverse;
}

@keyframes floatOrb {
    0%, 100% {
        transform: translate(0, 0);
    }
    50% {
        transform: translate(20px, -20px);
    }
}

.hero-images {
    position: absolute;
    right: -30px;
    top: 40px;
    width: 220px;
    height: 320px;
    pointer-events: none;
    z-index: 1;
}

.food-image {
    position: absolute;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    border: 3px solid rgba(255, 255, 255, 0.8);
    animation: floatImage 6s ease-in-out infinite;
}

.food-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.food-image-1 {
    width: 130px;
    height: 130px;
    top: 0;
    right: 30px;
    transform: rotate(6deg);
    z-index: 4;
    animation-delay: 0s;
}

.food-image-2 {
    width: 110px;
    height: 110px;
    top: 90px;
    right: 0;
    transform: rotate(-4deg);
    z-index: 3;
    animation-delay: 1s;
}

.food-image-3 {
    width: 120px;
    height: 120px;
    top: 160px;
    right: 40px;
    transform: rotate(3deg);
    z-index: 2;
    animation-delay: 2s;
}

.food-image-4 {
    width: 100px;
    height: 100px;
    bottom: 10px;
    right: 10px;
    transform: rotate(-6deg);
    z-index: 3;
    animation-delay: 1.5s;
}

@keyframes floatImage {
    0%, 100% {
        transform: translateY(0) rotate(var(--r, 0deg));
    }
    50% {
        transform: translateY(-10px) rotate(var(--r, 0deg));
    }
}

.hero-content {
    position: relative;
    z-index: 10;
    max-width: 420px;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1A1A1A;
    margin: 0 0 8px;
    line-height: 1.15;
    letter-spacing: -0.02em;
}

.hero-subtitle {
    font-size: 0.95rem;
    color: #666;
    margin: 0 0 24px;
    line-height: 1.5;
}

.search-container {
    margin-bottom: 20px;
}

.search-box {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 16px;
    padding: 4px 4px 4px 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.search-box:focus-within {
    box-shadow: 0 8px 32px rgba(255, 107, 53, 0.15);
    border-color: rgba(255, 107, 53, 0.3);
    transform: translateY(-1px);
}

.search-icon {
    color: #999;
    font-size: 1.1rem;
    margin-right: 12px;
}

.search-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 14px 0;
    font-size: 1rem;
    color: #1A1A1A;
    outline: none;
    font-weight: 500;
}

.search-input::placeholder {
    color: #AAA;
    font-weight: 400;
}

.search-clear {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #F0F0F0;
    border: none;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-clear:hover {
    background: #E0E0E0;
    color: #333;
}

.categories-row {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 12px;
    margin-bottom: 24px;
    scrollbar-width: none;
    -ms-overflow-style: none;
    mask-image: linear-gradient(to right, black 95%, transparent 100%);
}

.categories-row::-webkit-scrollbar {
    display: none;
}

.category-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 99px;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.category-icon {
    font-size: 1.2rem;
}

.category-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #444;
}

.category-pill:hover {
    background: rgba(255, 255, 255, 0.95);
    transform: translateY(-1px);
}

.category-pill.active {
    background: #1A1A1A;
    border-color: #1A1A1A;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: scale(1.02);
}

.category-pill.active .category-name {
    color: #FFFFFF;
}

.service-features-wrapper {
    margin-bottom: 20px;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    padding: 16px;
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.service-features {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 4px;
}

.service-features::-webkit-scrollbar {
    display: none;
}

.feature-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    min-width: 85px;
}

.feature-icon-wrapper {
    width: 56px;
    height: 56px;
    background: #FFFFFF;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    transition: transform 0.3s ease;
}

.feature-item:hover .feature-icon-wrapper {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.feature-icon-wrapper i {
    font-size: 1.4rem;
    background: linear-gradient(135deg, #FF6B35 0%, #FF9A8B 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-label {
    font-size: 0.75rem;
    color: #555;
    text-align: center;
    font-weight: 600;
    line-height: 1.3;
}

.view-mode-toggle {
    display: inline-flex;
    gap: 6px;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(8px);
    padding: 6px;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.view-btn {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: transparent;
    border: none;
    color: #888;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.25s ease;
}

.view-btn:hover {
    color: #333;
    background: rgba(0, 0, 0, 0.04);
}

.view-btn.active {
    background: #1A1A1A;
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.cuisines-section {
    padding: 8px 0 24px;
    background: transparent;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px 16px;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 0;
}

.view-all-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    background: none;
    border: none;
    color: #FF6B35;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.view-all-btn:hover {
    opacity: 0.8;
}

.cuisines-slider {
    display: flex;
    gap: 16px;
    padding: 0 20px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.cuisines-slider::-webkit-scrollbar {
    display: none;
}

.cuisine-card {
    min-width: 130px;
    cursor: pointer;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.cuisine-card:active {
    transform: scale(0.95);
}

.cuisine-image-wrapper {
    position: relative;
    width: 130px;
    height: 130px;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 10px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.cuisine-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.cuisine-card:hover .cuisine-image {
    transform: scale(1.1);
}

.cuisine-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.4) 0%, transparent 60%);
}

.cuisine-info {
    text-align: center;
}

.cuisine-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
}

.filter-tabs-container {
    background: #FFFFFF;
    padding: 16px 20px;
    margin-bottom: 16px;
    border-top: 1px solid rgba(0, 0, 0, 0.04);
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.filter-tabs {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.filter-tabs::-webkit-scrollbar {
    display: none;
}

.filter-tab {
    padding: 10px 20px;
    background: #F5F5F7;
    border: 1px solid transparent;
    border-radius: 99px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #666;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.25s ease;
}

.filter-tab:hover {
    background: #EBEBEF;
}

.filter-tab.active {
    background: #1A1A1A;
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.partners-list {
    padding: 0;
}

.partners-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.partners-grid.grid-view {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.loading-state {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 20px;
}

.skeleton-card {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.skeleton-image {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    background: linear-gradient(90deg, #F0F0F0 0%, #E0E0E0 50%, #F0F0F0 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.skeleton-line {
    height: 14px;
    border-radius: 8px;
    background: linear-gradient(90deg, #F0F0F0 0%, #E0E0E0 50%, #F0F0F0 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-line.w-40 {
    width: 40%;
}

.skeleton-line.w-60 {
    width: 60%;
}

.skeleton-line.w-80 {
    width: 80%;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.empty-state {
    text-align: center;
    padding: 60px 24px;
    animation: fadeInUp 0.5s ease-out;
}

.empty-icon-wrapper {
    margin-bottom: 20px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(26, 26, 26, 0.05);
    color: #1A1A1A;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto;
}

.empty-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: #1A1A1A;
}

.empty-text {
    font-size: 0.95rem;
    color: #888;
    margin-bottom: 24px;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    background: #1A1A1A;
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.25s ease;
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.orders-tab-content {
    animation: fadeInUp 0.4s ease-out;
}

.orders-hero {
    position: relative;
    padding: 32px 24px;
    background: linear-gradient(135deg, var(--bs-primary, #667eea) 0%, var(--bs-primary-hover, #764ba2) 100%);
    color: white;
    text-align: center;
    overflow: hidden;
    border-radius: 0 0 32px 32px;
    margin-bottom: 24px;
}

.hero-background {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content-orders {
    position: relative;
    z-index: 1;
}

.hero-icon-orders {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 16px;
}

.hero-title-orders {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.hero-subtitle-orders {
    font-size: 0.95rem;
    opacity: 0.9;
    margin: 0;
}

.orders-tabs-wrapper {
    position: sticky;
    top: 60px;
    z-index: 90;
    background: rgba(250, 250, 250, 0.9);
    backdrop-filter: blur(12px);
    padding: 12px 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.orders-tabs-container {
    display: flex;
    gap: 8px;
    background: rgba(0, 0, 0, 0.04);
    padding: 4px;
    border-radius: 16px;
    max-width: 400px;
    margin: 0 auto;
}

.orders-tab-item {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    color: #666;
    cursor: pointer;
    transition: all 0.3s ease;
}

.orders-tab-item.active {
    background: #FFFFFF;
    color: #1A1A1A;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.orders-tab-item.active i {
    color: var(--bs-primary, #FF6B35);
}

.tab-badge {
    padding: 2px 8px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
}

.orders-tab-item.active .tab-badge {
    background: var(--bs-primary, #FF6B35);
    color: white;
}

.info-banner {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(var(--bs-primary-rgb, 255, 107, 53), 0.05);
    border: 1px solid rgba(var(--bs-primary-rgb, 255, 107, 53), 0.15);
    border-radius: 16px;
    margin-bottom: 20px;
}

.info-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bs-primary, #FF6B35);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.info-text {
    flex: 1;
    font-size: 0.85rem;
    color: #333;
    line-height: 1.4;
}

.info-text strong {
    color: var(--bs-primary, #FF6B35);
}

.orders-group {
    margin-bottom: 24px;
}

.group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 0 4px;
}

.group-date {
    font-weight: 700;
    font-size: 1rem;
    color: #1A1A1A;
}

.group-count {
    font-size: 0.8rem;
    color: #666;
}

.order-card {
    background: #FFFFFF;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 16px;
    cursor: pointer;
    transition: all 0.25s ease;
}

.order-card:hover {
    border-color: var(--bs-primary, #FF6B35);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.order-number {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.order-number-label {
    font-size: 0.75rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.order-number-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1A1A1A;
}

.order-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-new {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.status-processing {
    background: rgba(255, 193, 7, 0.15);
    color: #b8860b;
}

.status-completed {
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.order-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 16px;
}

.order-products {
    padding: 16px;
    background: #F8F9FA;
    border-radius: 16px;
    margin-bottom: 16px;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.product-item:last-child {
    border-bottom: none;
}

.product-qty {
    font-weight: 700;
    color: var(--bs-primary, #FF6B35);
    min-width: 24px;
}

.product-name {
    flex: 1;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-more {
    padding-top: 8px;
    font-size: 0.8rem;
    color: #666;
    font-style: italic;
}

.order-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-top: 1px dashed rgba(0, 0, 0, 0.1);
    margin-bottom: 16px;
    font-size: 0.95rem;
}

.total-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-primary, #FF6B35);
}

.order-actions {
    display: flex;
    gap: 8px;
}

.repeat-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: #1A1A1A;
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.25s ease;
}

.repeat-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.reviews-icon {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1a1a1a;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.cuisine-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.cuisine-modal-sheet {
    width: 100%;
    max-width: 600px;
    background: #FFFFFF;
    border-radius: 24px 24px 0 0;
    padding: 12px 24px 32px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
}

.modal-handle {
    width: 40px;
    height: 4px;
    background: #E0E0E0;
    border-radius: 2px;
    margin: 0 auto 20px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 0;
}

.modal-close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #F5F5F7;
    border: none;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-close-btn:hover {
    background: #E0E0E0;
    color: #1A1A1A;
}

.cuisine-modal-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.cuisine-modal-card {
    cursor: pointer;
    transition: transform 0.2s ease;
}

.cuisine-modal-card:active {
    transform: scale(0.96);
}

.cuisine-modal-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 10px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.cuisine-modal-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.cuisine-modal-card:hover .cuisine-modal-image {
    transform: scale(1.08);
}

.cuisine-modal-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.5) 0%, transparent 50%);
}

.cuisine-modal-info {
    text-align: center;
}

.cuisine-modal-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #333;
}

.bottom-sheet-enter-active, .bottom-sheet-leave-active {
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.bottom-sheet-enter-from, .bottom-sheet-leave-to {
    opacity: 0;
}

.bottom-sheet-enter-from .cuisine-modal-sheet, .bottom-sheet-leave-to .cuisine-modal-sheet {
    transform: translateY(100%);
}

.bottom-sheet-enter-to .cuisine-modal-sheet, .bottom-sheet-leave-from .cuisine-modal-sheet {
    transform: translateY(0);
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 1.6rem;
    }

    .hero-images {
        width: 160px;
        height: 260px;
        right: -20px;
        opacity: 0.3;
    }

    .food-image-1 {
        width: 124px;
        height: 124px;
        right: 62px;
        top: -26px;
    }

    .food-image-2 {
        width: 100px;
        height: 100px;
        top: 51px;
        right: 25px;
    }

    .food-image-3 {
        width: 90px;
        height: 90px;
        top: 202px;
    }

    .food-image-4 {
        width: 70px;
        height: 70px;
        top: 150px;
        left: -11px;
    }

    .feature-icon-wrapper {
        width: 50px;
        height: 50px;
    }

    .feature-label {
        font-size: 0.7rem;
    }

    /*.partners-grid.grid-view { grid-template-columns: 1fr; }*/
    .cuisine-modal-sheet {
        max-height: 90vh;
        padding: 12px 16px 24px;
    }

    .cuisine-modal-grid {
        gap: 12px;
    }
}

/* ========================================== */
/* Активные состояния фильтров */
/* ========================================== */

/* Подсветка выбранной категории в шапке (уже была, но убедимся, что она работает) */
.category-pill.active {
    background: #1A1A1A;
    border-color: #1A1A1A;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: scale(1.02);
}

.category-pill.active .category-name {
    color: #FFFFFF;
}

/* 🆕 Подсветка выбранной кухни в слайдере */
.cuisine-card.active .cuisine-image-wrapper {
    border: 3px solid #FF6B35;
    box-shadow: 0 8px 24px rgba(255, 107, 53, 0.25);
}

.cuisine-card.active .cuisine-name {
    color: #FF6B35;
    font-weight: 700;
}

/* 🆕 Подсветка выбранной кухни в модальном окне */
.cuisine-modal-card.active .cuisine-modal-image-wrapper {
    border: 3px solid #FF6B35;
    box-shadow: 0 8px 24px rgba(255, 107, 53, 0.25);
}

.cuisine-modal-card.active .cuisine-modal-name {
    color: #FF6B35;
    font-weight: 700;
}
</style>
