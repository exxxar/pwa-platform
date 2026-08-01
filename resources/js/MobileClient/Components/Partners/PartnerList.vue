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


                    <!-- 🆕 ПЕРЕМЕЩЕНО СЮДА: Переключатель вида (внизу слева) -->
                    <div class="view-mode-toggle-wrapper">
                        <div class="view-mode-toggle">
                            <button class="view-btn" :class="{ 'active': viewMode === 'list' }"
                                    @click="viewMode = 'list'" title="Список">
                                <i class="fa-solid fa-list"></i>
                            </button>
                            <button class="view-btn" :class="{ 'active': viewMode === 'grid' }"
                                    @click="viewMode = 'grid'" title="Сетка">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>
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
                    <div class="partners-grid mb-3" :class="{ 'grid-view': viewMode === 'grid' }">

                        <div v-for="partner in filteredPartners" :key="partner.id">
                            <PartnerCard
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
                {id: 1, name: 'Пицца', icon: '🍕', slug: 'pizza'},
                {id: 2, name: 'Бургеры', icon: '🍔', slug: 'burgers'},
                {id: 3, name: 'Шаурма', icon: '🌯', slug: 'shawarma'},
                {id: 4, name: 'Суши и роллы', icon: '🍣', slug: 'sushi'},
                {id: 5, name: 'Шашлык', icon: '🍖', slug: 'shashlik'},
                {id: 6, name: 'Хинкали', icon: '🥟', slug: 'khinkali'},
                {id: 7, name: 'Хачапури', icon: '🫓', slug: 'khachapuri'},
                {id: 8, name: 'Лапша / Wok', icon: '🍜', slug: 'wok'},
                {id: 9, name: 'Паста', icon: '🍝', slug: 'pasta'},
                {id: 10, name: 'Донер / Кебаб', icon: '🥙', slug: 'doner'},
                {id: 11, name: 'Тако и буррито', icon: '🌮', slug: 'taco'},
                {id: 12, name: 'Пельмени и вареники', icon: '🥟', slug: 'dumplings'},
                {id: 13, name: 'Обеды', icon: '🍱', slug: 'lunch'},
                {id: 14, name: 'Морепродукты', icon: '🦞', slug: 'seafood'},
                {id: 15, name: 'Закуски', icon: '🍟', slug: 'snacks'},
                {id: 16, name: 'Пивные закуски', icon: '🍺', slug: 'beer_snacks'},
                {id: 17, name: 'Салаты', icon: '🥗', slug: 'salads'},
                {id: 18, name: 'ПП', icon: '🥑', slug: 'pp'}
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
                // 🆕 Преобразуем p.id в строку для корректного сравнения
                list = list.filter(p => this.favoriteIds.includes(String(p.id)));
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

<style lang="scss" scoped>
// ==========================================
// 🎨 ПЕРЕМЕННЫЕ (SASS)
// ==========================================
$primary: #667eea;
$primary-dark: #5a67d8;
$primary-light: #818cf8;
$bg: #ffffff;
$bg-secondary: #f8f9fa;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;
$text-muted-light: #9ca3af;
$success: #22c55e;
$danger: #ef4444;
$warning: #f59e0b;
$info: #3b82f6;

// ==========================================
// MODERN STICKY TABS (Адаптивные табы)
// ==========================================
.modern-tabs-wrapper {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 12px 16px;
    display: flex;
    justify-content: center;

    @media (max-width: 768px) {
        justify-content: center;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        &::-webkit-scrollbar {
            display: none;
        }
    }
}

.modern-tabs {
    display: inline-flex;
    gap: 8px;

    background: rgba(0, 0, 0, 0.04);
    padding: 4px;
    border-radius: 16px;
}

.tab-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    background: transparent;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;

    &.active {
        background: #ffffff;
        color: #2563eb;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    @media (max-width: 480px) {
        padding: 8px 14px;
        font-size: 0.9rem;
    }
}

// ==========================================
// HERO SECTION (Адаптивный Hero)
// ==========================================
.hero-section {
    position: relative;
    padding: 40px 20px 60px;
    background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
    border-radius: 0 0 32px 32px;
    overflow: hidden;
    text-align: center;

    @media (max-width: 768px) {
        padding: 24px 16px 40px;
        border-radius: 0 0 24px 24px;
    }
}

.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
    z-index: 0;

    &.orb-1 {
        width: 300px;
        height: 300px;
        background: #3b82f6;
        top: -100px;
        left: -100px;
        @media (max-width: 768px) {
            width: 200px;
            height: 200px;
        }
    }

    &.orb-2 {
        width: 250px;
        height: 250px;
        background: #8b5cf6;
        bottom: -50px;
        right: -50px;
        @media (max-width: 768px) {
            width: 150px;
            height: 150px;
        }
    }
}

.hero-images {
    position: relative;
    width: 100%;
    max-width: 800px;
    height: 200px;
    margin: 0 auto 32px;
    z-index: 1;

    @media (max-width: 768px) {
        height: 140px;
        margin-bottom: 24px;
    }
}

.food-image {
    position: absolute;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border: 4px solid #ffffff;
    transition: transform 0.3s ease;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    &.food-image-1 {
        width: 160px;
        height: 160px;
        top: 0;
        left: 10%;
        transform: rotate(-6deg);
        @media (max-width: 768px) {
            width: 100px;
            height: 100px;
            left: 5%;
        }
    }

    &.food-image-2 {
        width: 180px;
        height: 180px;
        top: 20px;
        left: 35%;
        z-index: 2;
        @media (max-width: 768px) {
            width: 120px;
            height: 120px;
            left: 30%;
        }
    }

    &.food-image-3 {
        width: 160px;
        height: 160px;
        top: 0;
        right: 10%;
        transform: rotate(6deg);
        @media (max-width: 768px) {
            width: 100px;
            height: 100px;
            right: 5%;
        }
    }

    &.food-image-4 {
        width: 140px;
        height: 140px;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) rotate(3deg);
        z-index: 3;
        @media (max-width: 768px) {
            width: 90px;
            height: 90px;
            bottom: -26px;
            left: 67%;
        }
    }
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 700px;
    margin: 0 auto;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 12px;
    line-height: 1.2;

    @media (max-width: 768px) {
        font-size: 1.8rem;
    }
}

.hero-subtitle {
    font-size: 1.1rem;
    color: #64748b;
    margin-bottom: 24px;
    line-height: 1.5;

    @media (max-width: 768px) {
        font-size: 1rem;
        margin-bottom: 20px;
    }
}

.search-container {
    margin-bottom: 24px;
}

.search-box {
    position: relative;
    max-width: 500px;
    margin: 0 auto;
    width: 100%;

    .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .search-input {
        width: 100%;
        padding: 16px 48px 16px 48px;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        font-size: 1rem;
        background: #ffffff;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);

        &:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
        }
    }

    .search-clear {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: #f1f5f9;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #64748b;
        transition: all 0.2s;

        &:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
    }
}

// Горизонтальный скролл для мобильных (категории и фичи)
.categories-row, .service-features {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 4px 4px 12px 4px;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;

    &::-webkit-scrollbar {
        display: none;
    }
}

.category-pill {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    scroll-snap-align: start;

    &.active {
        background: #3b82f6;
        color: #ffffff;
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
}

.service-features-wrapper {
    margin: 24px auto 0;
    max-width: 600px;
}

.feature-item {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 16px;
    min-width: 100px;
    scroll-snap-align: start;
}

.feature-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #eff6ff;
    color: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.feature-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    text-align: center;
}

// ==========================================
// 🆕 ПЕРЕКЛЮЧАТЕЛЬ ВИДА (Внизу слева)
// ==========================================
.view-mode-toggle-wrapper {
    margin-top: 32px;
    display: flex;
    justify-content: flex-start; // Выравнивание по левому краю

    @media (max-width: 768px) {
        margin-top: 24px;
        // На мобильных можно оставить слева или сделать по центру,
        // но раз вам понравилось слева, оставляем flex-start
    }
}

.view-mode-toggle {
    display: inline-flex;
    background: #ffffff;
    border-radius: 12px;
    padding: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.view-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    border-radius: 8px;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;

    &.active {
        background: #3b82f6;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    &:hover:not(.active) {
        background: #f1f5f9;
        color: #475569;
    }
}

.view-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    border-radius: 8px;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;

    &.active {
        background: #3b82f6;
        color: #ffffff;
    }
}

// ==========================================
// CUISINES SLIDER (Адаптивный слайдер)
// ==========================================
.cuisines-section {
    padding: 32px 0 16px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    margin-bottom: 16px;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.view-all-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: transparent;
    border: none;
    color: #3b82f6;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;

    &:hover {
        opacity: 0.8;
    }
}

.cuisines-slider {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 8px 16px 24px;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;

    &::-webkit-scrollbar {
        display: none;
    }
}

.cuisine-card {
    flex-shrink: 0;
    width: 140px;
    cursor: pointer;
    scroll-snap-align: start;
    transition: transform 0.2s;

    &.active {
        transform: scale(1.05);
    }

    @media (max-width: 768px) {
        width: 120px;
    }
}

.cuisine-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 8px;
}

.cuisine-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
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
    color: #334155;
}

// ==========================================
// FILTER TABS (Липкие фильтры)
// ==========================================
.filter-tabs-container {
    position: sticky;
    top: 65px; /* Подстраивается под высоту modern-tabs-wrapper */
    z-index: 90;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    padding: 12px 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    margin-bottom: 16px;

    @media (max-width: 768px) {
        top: 84px;
    }
}

.filter-tabs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 4px;

    &::-webkit-scrollbar {
        display: none;
    }
}

.filter-tab {
    flex-shrink: 0;
    padding: 8px 16px;
    background: #f1f5f9;
    border: 1px solid transparent;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    &.active {
        background: #1e293b;
        color: #ffffff;
    }
}

// ==========================================
// 🆕 ВКЛАДКА ЗАКАЗОВ (Orders Tab)
// ==========================================
.orders-tab-content {
    animation: fadeIn 0.3s ease;
}

// Hero секция заказов
.orders-hero {
    position: relative;
    padding: 48px 32px;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.25);

    @media (max-width: 768px) {
        padding: 32px 20px;
        border-radius: 16px;
        margin-bottom: 16px;
    }
}

.hero-background {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

.hero-content-orders {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.hero-icon-orders {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    animation: floatIcon 3s ease-in-out infinite;
}

@keyframes floatIcon {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

.hero-title-orders {
    font-size: 2rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;

    @media (max-width: 768px) { font-size: 1.5rem; }
}

.hero-subtitle-orders {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
    max-width: 400px;

    @media (max-width: 768px) { font-size: 0.9rem; }
}

// ==========================================
// ТАБЫ ВНУТРИ ЗАКАЗОВ
// ==========================================
.orders-tabs-wrapper {
    margin-bottom: 20px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 6px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.orders-tabs-container {
    display: flex;
    gap: 4px;
}

.orders-tab-item {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    background: transparent;
    border: none;
    border-radius: 12px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;

    i { font-size: 1rem; }

    &:hover {
        background: $bg-secondary;
        color: $text;
    }

    &.active {
        background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
        color: white;
        box-shadow: 0 4px 12px rgba($primary, 0.25);
    }
}

.tab-badge {
    padding: 2px 8px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    min-width: 24px;
    text-align: center;

    .orders-tab-item:not(.active) & {
        background: rgba($primary, 0.1);
        color: $primary;
    }
}

// ==========================================
// ИНФОРМАЦИОННЫЙ БАННЕР
// ==========================================
.info-banner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    background: rgba($primary, 0.05);
    border-left: 4px solid $primary;
    border-radius: 12px;
    margin-bottom: 20px;
    animation: slideInLeft 0.4s ease;

    @media (max-width: 768px) {
        padding: 12px 16px;
        font-size: 0.85rem;
    }
}

@keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(0); }
}

.info-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba($primary, 0.15);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.info-text {
    flex: 1;
    font-size: 0.9rem;
    color: $text;
    line-height: 1.5;

    strong { color: $primary; }
}

// ==========================================
// ГРУППЫ ЗАКАЗОВ (по датам)
// ==========================================
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.orders-group {
    animation: fadeIn 0.4s ease;
}

.group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 4px 12px;
    margin-bottom: 12px;
    border-bottom: 2px solid $border;
}

.group-date {
    font-size: 1.1rem;
    font-weight: 700;
    color: $text;
    display: flex;
    align-items: center;
    gap: 8px;

    &::before {
        content: '';
        width: 4px;
        height: 20px;
        background: linear-gradient(180deg, $primary 0%, $primary-dark 100%);
        border-radius: 2px;
    }
}

.group-count {
    font-size: 0.85rem;
    color: $text-muted;
    background: $bg-secondary;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
}

// ==========================================
// КАРТОЧКА ЗАКАЗА
// ==========================================
.order-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        border-color: rgba($primary, 0.3);
    }

    &:last-child { margin-bottom: 0; }

    @media (max-width: 768px) {
        padding: 16px;
        border-radius: 12px;
    }
}

.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
}

.order-number {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.order-number-label {
    font-size: 0.85rem;
    color: $text-muted;
    font-weight: 500;
}

.order-number-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: $text;
}

.order-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;

    // Статусы (подставьте свои классы)
    &.new, &.pending {
        background: rgba($warning, 0.1);
        color: darken($warning, 15%);
    }
    &.processing, &.cooking {
        background: rgba($primary, 0.1);
        color: $primary;
    }
    &.ready, &.delivered, &.completed {
        background: rgba($success, 0.1);
        color: $success;
    }
    &.cancelled {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.order-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: $text-muted;
    margin-bottom: 12px;

    i { color: $primary; font-size: 0.9rem; }
}

.order-products {
    padding: 12px 0;
    border-top: 1px dashed $border;
    border-bottom: 1px dashed $border;
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: $text;
}

.product-qty {
    font-weight: 700;
    color: $primary;
    min-width: 28px;
}

.product-name {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-more {
    font-size: 0.8rem;
    color: $text-muted;
    font-style: italic;
    padding-top: 4px;
}

.order-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 0.95rem;
    color: $text;

    .total-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: $primary;
    }
}

.order-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid $border;
}

.repeat-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($primary, 0.2);

    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba($primary, 0.3);
    }

    i { font-size: 1rem; }
}

// ==========================================
// СПИСОК ОТЗЫВОВ
// ==========================================
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    background: $bg;
    border: 2px dashed $border;
    border-radius: 16px;
    animation: fadeIn 0.4s ease;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 16px;

    &.reviews-icon {
        background: rgba($warning, 0.1);
        color: $warning;
    }
}

.empty-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 8px;
}

.empty-text {
    font-size: 0.9rem;
    color: $text-muted;
    margin: 0 0 20px;
    max-width: 300px;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($primary, 0.25);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba($primary, 0.35);
    }
}

// ==========================================
// СОСТОЯНИЕ ЗАГРУЗКИ (Skeleton)
// ==========================================
.loading-state {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.skeleton-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-image {
    width: 100%;
    height: 120px;
    background: linear-gradient(90deg, $bg-secondary 25%, #e5e7eb 50%, $bg-secondary 75%);
    background-size: 200% 100%;
    border-radius: 8px;
    animation: shimmer 1.5s infinite;
}

.skeleton-line {
    height: 14px;
    background: linear-gradient(90deg, $bg-secondary 25%, #e5e7eb 50%, $bg-secondary 75%);
    background-size: 200% 100%;
    border-radius: 6px;
    animation: shimmer 1.5s infinite;

    &.w-40 { width: 40%; }
    &.w-60 { width: 60%; }
    &.w-80 { width: 80%; }
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// АДАПТИВ ДЛЯ ВКЛАДКИ ЗАКАЗОВ
// ==========================================
@media (max-width: 768px) {
    .orders-tabs-container {
        flex-direction: row;
    }

    .orders-tab-item {
        padding: 10px 12px;
        font-size: 0.85rem;
        gap: 6px;

        span:not(.tab-badge) {
            display: inline;
        }
    }

    .group-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .order-actions {
        flex-direction: column;
    }

    .repeat-btn {
        width: 100%;
    }
}

// ==========================================
// 🆕 АДАПТИВНАЯ СЕТКА ПАРТНЕРОВ (List / Grid)
// ==========================================
.partners-list {
    width: 100%;
}

.partners-grid {
    display: grid;
    gap: 16px; // Отступы между карточками (заменяет необходимость в mb-2, но можно оставить для совместимости)

    // 📱 МОБИЛЬНЫЕ УСТРОЙСТВА (по умолчанию, < 768px)
    // Список: 1 карточка в ряд
    // Сетка: 2 карточки в ряд (чтобы на телефоне это все еще выглядело как сетка)
    grid-template-columns: 1fr;

    &.grid-view {
        grid-template-columns: repeat(2, 1fr);
    }

    // 💻 ПЛАНШЕТЫ (md >= 768px)
    @media (min-width: 768px) {
        // Список: 2 карточки в ряд
        grid-template-columns: repeat(2, 1fr);

        &.grid-view {
            // Сетка: 3 карточки в ряд
            grid-template-columns: repeat(3, 1fr);
        }
    }

    // 🖥️ ДЕСКТОП (lg >= 992px)
    @media (min-width: 992px) {
        // Список: 3 карточки в ряд
        grid-template-columns: repeat(3, 1fr);

        &.grid-view {
            // Сетка: 5 карточек в ряд
            grid-template-columns: repeat(5, 1fr);
        }
    }

    // 🖥️ БОЛЬШИЕ ДЕСКТОПЫ (xl >= 1200px) - опционально, чтобы не растягивались слишком сильно
    @media (min-width: 1200px) {
        &.grid-view {
            grid-template-columns: repeat(5, 1fr); // Можно оставить 5 или увеличить до 6
        }
    }
}

// Небольшая оптимизация для карточки внутри грида, чтобы она занимала всю высоту ячейки
.partners-grid > div {
    height: 100%;
    display: flex;
    flex-direction: column;

    // Убираем лишний нижний отступ, так как gap в grid уже дает пространство
    margin-bottom: 0 !important;
}
</style>
