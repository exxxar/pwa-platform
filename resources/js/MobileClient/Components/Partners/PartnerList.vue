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
            <div v-if="isHeroEnabled" class="hero-section" :style="heroBackgroundStyle">
                <div class="hero-orb orb-1"></div>
                <div class="hero-orb orb-2"></div>

                <div class="hero-images">
                    <div class="food-image food-image-1"><img v-lazy="dynamicHeroImages.image1" alt="Food 1"></div>
                    <div class="food-image food-image-2"><img v-lazy="dynamicHeroImages.image2" alt="Food 2"></div>
                    <div class="food-image food-image-3"><img v-lazy="dynamicHeroImages.image3" alt="Food 3"></div>
                    <div class="food-image food-image-4"><img v-lazy="dynamicHeroImages.image4" alt="Food 4"></div>
                </div>

                <div class="hero-content">
                    <h2 class="hero-title">{{ dynamicHeroSettings.title }}</h2>
                    <p class="hero-subtitle">{{ dynamicHeroSettings.subtitle }}</p>
                </div>
            </div>



            <!-- ВОЗДУШНАЯ ПАНЕЛЬ ФИЛЬТРОВ + ВИД -->
            <div class="filter-bar-wrapper" v-if="activeMainTab === 'establishments'">
                <div class="filter-bar">
                    <button
                        class="view-orb"
                        :class="{ 'active': viewMode === 'list' }"
                        @click="viewMode = 'list'"
                        title="Список"
                    >
                        <i class="fa-solid fa-list"></i>
                    </button>

                    <div class="bar-line"></div>

                    <button
                        class="filter-orb"
                        :class="{ 'has-active': totalFiltersCount > 0 }"
                        @click="openFiltersModal"
                    >
                        <span class="orb-pulse" v-if="totalFiltersCount > 0"></span>
                        <span class="orb-pulse delayed" v-if="totalFiltersCount > 0"></span>

                        <div class="orb-icon">
                            <i class="fa-solid fa-sliders"></i>
                        </div>

                        <transition name="badge-pop">
                            <span v-if="totalFiltersCount > 0" class="orb-badge">
                                {{ totalFiltersCount }}
                            </span>
                        </transition>
                    </button>

                    <div class="bar-line"></div>

                    <button
                        class="view-orb"
                        :class="{ 'active': viewMode === 'grid' }"
                        @click="viewMode = 'grid'"
                        title="Сетка"
                    >
                        <i class="fa-solid fa-grip"></i>
                    </button>
                </div>
            </div>

            <!-- Сводка активных фильтров -->
            <transition name="summary-slide">
                <div class="filter-summary" v-if="totalFiltersCount > 0 && activeMainTab === 'establishments'">
                    <span class="summary-text">{{ filtersSummaryText }}</span>
                    <button class="summary-reset" @click="handleResetFilters" title="Сбросить">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </transition>

            <!-- ПЛАВАЮЩАЯ КНОПКА СБРОСА (при прокрутке) -->
            <transition name="reset-fab">
                <button
                    v-if="totalFiltersCount > 0 && activeMainTab === 'establishments' && scrollY > 300"
                    class="reset-filters-fab"
                    @click="handleResetFilters"
                    title="Сбросить все фильтры"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    <span v-if="totalFiltersCount > 0" class="fab-badge">{{ totalFiltersCount }}</span>
                </button>
            </transition>

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

                <div v-else-if="!filteredPartners || filteredPartners.length === 0" class="empty-state">
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
        <!-- ВКЛАДКА: ЗАКАЗЫ -->
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
                                <div class="group-count">{{ (group.orders || []).length }}
                                    {{ pluralize((group.orders || []).length, 'заказ', 'заказа', 'заказов') }}
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
        <!-- 🆕 BOTTOM SHEET MODAL: ВСЕ ФИЛЬТРЫ -->
        <!-- ========================================== -->
        <transition name="bottom-sheet">
            <div v-if="showFiltersModal" class="filters-modal-backdrop" @click="closeFiltersModal">
                <div class="filters-modal-sheet" @click.stop>
                    <div class="modal-handle"></div>

                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fa-solid fa-sliders"></i>
                            Фильтры
                            <span v-if="totalFiltersCount > 0" class="modal-badge">{{ totalFiltersCount }}</span>
                        </h3>
                        <button class="modal-close-btn" @click="closeFiltersModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

<!--
                    &lt;!&ndash; 🆕 ПОИСК ВНУТРИ МОДАЛКИ &ndash;&gt;
                    <div class="modal-search-wrapper">
                        <div class="modal-search-box">
                            <i class="fa-solid fa-magnifying-glass modal-search-icon"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="modal-search-input"
                                placeholder="Поиск заведений, блюд..."
                                ref="modalSearchInput"
                            >
                            <button
                                v-if="searchQuery"
                                class="modal-search-clear"
                                @click="searchQuery = ''"
                                type="button"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="modal-search-hint" v-if="searchQuery">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Найдено заведений: <strong>{{ filteredPartners.length }}</strong></span>
                        </div>
                    </div>
-->

                    <!-- 🆕 РЕЖИМ СОВПАДЕНИЯ ФИЛЬТРОВ -->
                    <div class="match-mode-wrapper">
                        <div class="match-mode-info">
                            <div class="match-mode-label">
                                <i class="fa-solid fa-compass-drafting"></i>
                                <span>Критерий совпадения</span>
                            </div>
                            <div class="match-mode-hint">
                                {{ filterMatchMode === 'any'
                                ? 'Покажем заведения с любым из выбранных'
                                : 'Только заведения со всеми выбранными' }}
                            </div>
                        </div>

                        <div class="match-mode-toggle">
                            <button
                                type="button"
                                class="match-mode-btn"
                                :class="{ 'active': filterMatchMode === 'any' }"
                                @click="filterMatchMode = 'any'"
                            >
                                <i class="fa-solid fa-circle-dot"></i>
                                <span>Хотя бы одна</span>
                            </button>
                            <button
                                type="button"
                                class="match-mode-btn"
                                :class="{ 'active': filterMatchMode === 'all' }"
                                @click="filterMatchMode = 'all'"
                            >
                                <i class="fa-solid fa-layer-group"></i>
                                <span>Все</span>
                            </button>
                        </div>
                    </div>


                    <!-- ГОРИЗОНТАЛЬНЫЙ СКРОЛЛ КУХОНЬ СВЕРХУ -->
                    <div class="cuisines-scroll-wrapper" v-if="dynamicCuisines.length > 0">
                        <div class="cuisines-scroll-label">
                            <i class="fa-solid fa-utensils"></i>
                            <span>Кухни мира</span>
                            <span v-if="selectedCuisines.length > 0" class="selection-badge">
            {{ selectedCuisines.length }}
        </span>
                        </div>
                        <div class="cuisines-scroll">
                            <button
                                v-for="cuisine in filteredModalCuisines"
                                :key="cuisine.id"
                                class="cuisine-chip"
                                :class="{ 'active': isCuisineSelected(cuisine.slug) }"
                                @click="selectCuisine(cuisine)"
                            >
                                <div class="cuisine-chip-image">
                                    <img v-lazy="cuisine.image || getDefaultImage('pizza')" :alt="cuisine.name">
                                </div>
                                <span class="cuisine-chip-name">{{ cuisine.name }}</span>
                                <i v-if="isCuisineSelected(cuisine.slug)" class="fa-solid fa-check cuisine-chip-check"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Вкладки -->
                    <div class="filters-tabs">
                        <button class="filter-tab-btn" :class="{ 'active': filtersTab === 'categories' }" @click="filtersTab = 'categories'">
                            <i class="fa-solid fa-tags"></i>
                            <span>Категории</span>
                            <span v-if="selectedCategories.length > 0" class="tab-indicator"></span>
                        </button>
                        <button
                            class="filter-tab-btn"
                            :class="{ 'active': filtersTab === 'filters' }"
                            @click="filtersTab = 'filters'"
                        >
                            <i class="fa-solid fa-filter"></i>
                            <span>Фильтры</span>
                            <span v-if="isCustomFilterActive" class="tab-indicator"></span>
                        </button>
                        <button
                            class="filter-tab-btn"
                            :class="{ 'active': filtersTab === 'view' }"
                            @click="filtersTab = 'view'"
                        >
                            <i class="fa-solid fa-grip"></i>
                            <span>Вид</span>
                        </button>
                    </div>

                    <div class="filters-modal-body">
                        <!-- ВКЛАДКА: КАТЕГОРИИ -->
                        <div v-if="filtersTab === 'categories'" class="filters-section fade-in">
                            <div class="filters-section-title">
                                Выберите категории
                                <span v-if="selectedCategories.length > 0" class="selection-badge-sm">
            {{ selectedCategories.length }} выбрано
        </span>
                            </div>
                            <div class="filters-grid" v-if="filteredModalCategories.length > 0">
                                <button
                                    v-for="category in filteredModalCategories"
                                    :key="category.id"
                                    class="filter-card"
                                    :class="{ 'active': isCategorySelected(category.id) }"
                                    @click="selectCategory(category.id)"
                                >
                                    <div class="filter-card-icon">{{ category.icon || '🍽️' }}</div>
                                    <div class="filter-card-name">{{ category.name }}</div>
                                    <i v-if="isCategorySelected(category.id)" class="fa-solid fa-check filter-card-check"></i>
                                </button>
                            </div>
                            <div v-else class="modal-empty-hint">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span>По запросу "{{ searchQuery }}" ничего не найдено</span>
                            </div>
                        </div>

                        <!-- ВКЛАДКА: ФИЛЬТРЫ -->
                        <div v-if="filtersTab === 'filters'" class="filters-section fade-in">
                            <div class="filters-section-title">Дополнительные фильтры</div>
                            <div class="filters-list">
                                <button
                                    v-for="filterItem in dynamicFilters"
                                    :key="filterItem.id"
                                    class="filter-option"
                                    :class="{ 'active': filter === filterItem.slug }"
                                    @click="selectFilter(filterItem.slug)"
                                >
                                    <div class="filter-option-content">
                                        <div class="filter-option-icon">
                                            <i :class="getFilterIcon(filterItem.slug)"></i>
                                        </div>
                                        <span class="filter-option-name">{{ filterItem.name }}</span>
                                    </div>
                                    <i v-if="filter === filterItem.slug" class="fa-solid fa-check filter-option-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ВКЛАДКА: ВИД -->
                        <div v-if="filtersTab === 'view'" class="filters-section fade-in">
                            <div class="filters-section-title">Отображение</div>
                            <div class="view-mode-grid">
                                <button
                                    class="view-mode-option"
                                    :class="{ 'active': viewMode === 'list' }"
                                    @click="viewMode = 'list'"
                                >
                                    <div class="view-mode-icon">
                                        <i class="fa-solid fa-list"></i>
                                    </div>
                                    <div class="view-mode-info">
                                        <div class="view-mode-title">Список</div>
                                        <div class="view-mode-desc">Удобный просмотр деталей</div>
                                    </div>
                                    <i v-if="viewMode === 'list'" class="fa-solid fa-circle-check view-mode-check"></i>
                                </button>

                                <button
                                    class="view-mode-option"
                                    :class="{ 'active': viewMode === 'grid' }"
                                    @click="viewMode = 'grid'"
                                >
                                    <div class="view-mode-icon">
                                        <i class="fa-solid fa-grip"></i>
                                    </div>
                                    <div class="view-mode-info">
                                        <div class="view-mode-title">Сетка</div>
                                        <div class="view-mode-desc">Больше заведений на экране</div>
                                    </div>
                                    <i v-if="viewMode === 'grid'" class="fa-solid fa-circle-check view-mode-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Кнопка сброса -->
                        <button
                            v-if="hasActiveFilters"
                            class="reset-filters-btn"
                            @click="resetFilters"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Сбросить все фильтры</span>
                        </button>
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
    components: {PartnerLocationModal, PartnerCard, ReviewCard},
    emits: ['select'],

    setup() {
        const partnerStore = usePartnersStore();
        const orderComposable = useOrders();
        return {partnerStore, ...orderComposable};
    },

    data() {
        return {
            showFiltersModal: false,
            filtersTab: 'categories',
            scrollY: 0,
            showLocationModal: false,
            selectedPartnerForMap: null,
            activeMainTab: 'establishments',
            searchQuery: '',

            // 🆕 МНОЖЕСТВЕННЫЙ ВЫБОР
            selectedCategories: [],
            selectedCuisines: [],

            filter: 'all',
            viewMode: 'list',
            partnerList: [],
            isPartnersLoading: false,
            ordersTab: 0,
            reviewsLoaded: false,

            filterMatchMode: 'any',
        };
    },

    computed: {
        totalFiltersCount() {
            let count = 0;
            if (this.searchQuery && this.searchQuery.trim()) count++;
            count += this.selectedCategories.length; // 🆕 каждый элемент = 1 фильтр
            count += this.selectedCuisines.length;   // 🆕

            const defaultFilter = this.dynamicFilters.length > 0 ? this.dynamicFilters[0].slug : 'all';
            if (this.filter !== defaultFilter) count++;
            return count;
        },

        isCustomFilterActive() {
            const defaultFilter = this.dynamicFilters.length > 0 ? this.dynamicFilters[0].slug : 'all';
            return this.filter !== defaultFilter;
        },

        // 🆕 СВОДКА АКТИВНЫХ ФИЛЬТРОВ (поддерживает множественный выбор)
        filtersSummaryText() {
            const parts = [];
            const joiner = this.filterMatchMode === 'all' ? ' + ' : ', ';

            if (this.selectedCategories.length > 0) {
                const catNames = this.selectedCategories
                    .map(id => {
                        const cat = this.dynamicCategories.find(c => c.id === id);
                        return cat ? `${cat.icon || ''} ${cat.name}`.trim() : null;
                    })
                    .filter(Boolean);

                parts.push(catNames.length <= 2
                    ? catNames.join(joiner)
                    : `${catNames.slice(0, 2).join(joiner)} +${catNames.length - 2}`);
            }

            if (this.selectedCuisines.length > 0) {
                const cuisineNames = this.selectedCuisines
                    .map(slug => {
                        const cu = this.dynamicCuisines.find(c => c.slug === slug);
                        return cu ? cu.name : null;
                    })
                    .filter(Boolean);

                parts.push(cuisineNames.length <= 2
                    ? cuisineNames.join(joiner)
                    : `${cuisineNames.slice(0, 2).join(joiner)} +${cuisineNames.length - 2}`);
            }

            if (this.isCustomFilterActive) {
                const f = this.dynamicFilters.find(f => f.slug === this.filter);
                if (f) parts.push(f.name);
            }

            if (this.searchQuery && this.searchQuery.trim()) {
                parts.push(`"${this.searchQuery.trim()}"`);
            }

            return parts.join(' · ') || 'Все заведения';
        },


        uiSettings() {
            const tenant = window.Tenant || {};
            const fromMeta = tenant?.meta?.partners?.ui;
            if (fromMeta && typeof fromMeta === 'object') return fromMeta;
            const fromSettings = tenant?.settings?.partners?.ui;
            if (fromSettings && typeof fromSettings === 'object') return fromSettings;
            return {};
        },

        isHeroEnabled() {
            return this.uiSettings?.hero?.enabled !== false;
        },

        hasActiveFilters() {
            return this.totalFiltersCount > 0;
        },

        // 🎯 КУХНИ — ТОЛЬКО из cuisines (отдельный массив в настройках)
        dynamicCuisines() {
            const cuisines = this.uiSettings.cuisines;
            if (Array.isArray(cuisines) && cuisines.length > 0) return cuisines;
            // Дефолтный набор кухонь (отличается от категорий!)
            return [
                {id: 1, name: 'Итальянская', slug: 'italian', image: this.getDefaultImage('pizza')},
                {id: 2, name: 'Японская', slug: 'japanese', image: this.getDefaultImage('sushi')},
                {id: 3, name: 'Американская', slug: 'american', image: this.getDefaultImage('burger')},
                {id: 4, name: 'Грузинская', slug: 'georgian', image: this.getDefaultImage('khachapuri')},
                {id: 5, name: 'Азиатская', slug: 'asian', image: this.getDefaultImage('sushi')},
                {id: 6, name: 'Европейская', slug: 'european', image: this.getDefaultImage('pasta')},
            ];
        },

        // 🎯 КАТЕГОРИИ — ТОЛЬКО из categories (отдельный массив в настройках)
        dynamicCategories() {
            const categories = this.uiSettings.categories;
            if (Array.isArray(categories) && categories.length > 0) return categories;
            // Дефолтный набор категорий блюд (отличается от кухонь!)
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
            ];
        },

        // 🆕 Фильтрация категорий по поиску (для модалки)
        filteredModalCategories() {
            if (!this.searchQuery || !this.searchQuery.trim()) {
                return this.dynamicCategories;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return this.dynamicCategories.filter(c =>
                (c.name || '').toLowerCase().includes(q) ||
                (c.slug || '').toLowerCase().includes(q)
            );
        },

        // 🆕 Фильтрация кухонь по поиску (для модалки)
        filteredModalCuisines() {
            if (!this.searchQuery || !this.searchQuery.trim()) {
                return this.dynamicCuisines;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return this.dynamicCuisines.filter(c =>
                (c.name || '').toLowerCase().includes(q) ||
                (c.slug || '').toLowerCase().includes(q)
            );
        },

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

        dynamicServices() {
            const services = this.uiSettings.services;
            if (Array.isArray(services) && services.length > 0) return services;
            return [
                {id: 1, label: 'Быстрая доставка', icon: 'fa-solid fa-bolt'},
                {id: 2, label: 'Большой выбор', icon: 'fa-solid fa-bag-shopping'},
                {id: 3, label: 'Работа 24/7', icon: 'fa-solid fa-clock'},
                {id: 4, label: 'Все виды оплат', icon: 'fa-solid fa-home'}
            ];
        },

        dynamicFilters() {
            const filters = this.uiSettings.filters;
            if (Array.isArray(filters) && filters.length > 0) return filters;
            return [
                {id: 1, name: 'Все', slug: 'all'},
                {id: 2, name: 'Избранные', slug: 'favorites'},
                {id: 3, name: 'С акциями', slug: 'promo'},
                {id: 4, name: 'Популярные', slug: 'popular'}
            ];
        },

        activePartners() {
            const list = Array.isArray(this.partnerList) ? this.partnerList : [];
            return list.filter(p => p && p.is_active);
        },

        filteredPartners() {
            let list = [...(this.activePartners || [])];

            // Фильтры favorites/promo/popular
            if (this.filter === 'favorites') {
                const favIds = Array.isArray(this.favoriteIds) ? this.favoriteIds.map(String) : [];
                list = list.filter(p => favIds.includes(String(p.id)));
            } else if (this.filter === 'promo') {
                list = list.filter(p => {
                    const tags = Array.isArray(p.tags) ? p.tags.map(String) : [];
                    const hasPromoTag = tags.includes('promo') || tags.includes('sale') || tags.includes('акция');
                    return hasPromoTag || p.has_promo === true || (p.discount || 0) > 0;
                });
            } else if (this.filter === 'popular') {
                list = list.filter(p => (p.rating || 0) >= 4.5 || (p.order_position || 0) > 0);
            }

            // Поиск по тексту
            if (this.searchQuery && this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase().trim();
                list = list.filter(p => {
                    const name = (p.name || '').toLowerCase();
                    const address = (p.address || '').toLowerCase();
                    const description = (p.description || '').toLowerCase();
                    return name.includes(query) || address.includes(query) || description.includes(query);
                });
            }

            // 🎯 МНОЖЕСТВЕННАЯ ФИЛЬТРАЦИЯ С УЧЁТОМ РЕЖИМА
            const hasCuisines = this.selectedCuisines.length > 0;
            const hasCategories = this.selectedCategories.length > 0;

            // Если ничего не выбрано — возвращаем отфильтрованный список как есть
            if (!hasCuisines && !hasCategories) {
                return list.sort((a, b) => {
                    const posA = a.order_position || 0;
                    const posB = b.order_position || 0;
                    if (posB !== posA) return posB - posA;
                    return (a.name || '').localeCompare(b.name || '');
                });
            }

            const selectedCuisineStrs = hasCuisines
                ? this.selectedCuisines.map(slug => String(slug))
                : [];

            const selectedCatStrs = hasCategories
                ? this.selectedCategories.map(id => String(id))
                : [];

            list = list.filter(p => {
                // Извлекаем теги (кухни)
                const tags = Array.isArray(p.tags) ? p.tags.map(String) : [];

                // Извлекаем категории
                const categories = Array.isArray(p.categories) ? p.categories : [];
                let partnerCatIds = [];

                if (categories.length > 0) {
                    partnerCatIds = categories.map(c =>
                        String((typeof c === 'object' && c !== null) ? c.id : c)
                    );
                } else {
                    const pCatId = p.category_id || p.category;
                    if (pCatId) partnerCatIds = [String(pCatId)];
                }

                if (this.filterMatchMode === 'all') {
                    // 🎯 РЕЖИМ "ВСЕ": каждый выбранный критерий должен присутствовать
                    const allCuisinesMatch = !hasCuisines || selectedCuisineStrs.every(slug => tags.includes(slug));
                    const allCategoriesMatch = !hasCategories || selectedCatStrs.every(catId => partnerCatIds.includes(catId));

                    return allCuisinesMatch && allCategoriesMatch;
                } else {
                    // 🎯 РЕЖИМ "ХОТЯ БЫ ОДНА": глобальный OR между всеми фильтрами
                    const matchesCuisine = hasCuisines && selectedCuisineStrs.some(slug => tags.includes(slug));
                    const matchesCategory = hasCategories && selectedCatStrs.some(catId => partnerCatIds.includes(catId));

                    return matchesCuisine || matchesCategory;
                }
            });

            return list.sort((a, b) => {
                const posA = a.order_position || 0;
                const posB = b.order_position || 0;
                if (posB !== posA) return posB - posA;
                return (a.name || '').localeCompare(b.name || '');
            });
        },

        favoriteIds() {
            const self = window.TenantUser || null;
            const favs = self?.settings?.fav_partners;
            return Array.isArray(favs) ? favs : [];
        },

        totalOrdersCount() {
            return this.orders_paginate_object?.total || (Array.isArray(this.orders) ? this.orders.length : 0);
        },

        groupedOrders() {
            const groups = {};
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            const ordersList = Array.isArray(this.orders) ? this.orders : [];

            ordersList.forEach(order => {
                if (!order || !order.created_at) return;
                const date = new Date(order.created_at);
                if (isNaN(date.getTime())) return;

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
                        day: 'numeric', month: 'long',
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

    watch: {
        // 🆕 При открытии модалки — фокус на поиск
        showFiltersModal(val) {
            if (val) {
                this.$nextTick(() => {
                    this.$refs.modalSearchInput?.focus();
                });
            }
        }
    },

    mounted() {
        this.filter = this.dynamicFilters.length > 0 ? this.dynamicFilters[0].slug : 'all';
        this.loadInitialData();
        if (this.loadOrders) this.loadOrders({page: 0, size: 20});

        this._uiUpdateHandler = (event) => {
            console.log('🔄 UI настройки обновлены:', event.detail);
            this.$forceUpdate();
        };
        window.addEventListener('partners-ui-updated', this._uiUpdateHandler);
        window.addEventListener('scroll', this.handleScroll, {passive: true});
    },

    beforeUnmount() {
        document.body.style.overflow = '';
        if (this._uiUpdateHandler) {
            window.removeEventListener('partners-ui-updated', this._uiUpdateHandler);
        }
        window.removeEventListener('scroll', this.handleScroll);
    },

    methods: {
        openFiltersModal() {
            this.showFiltersModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeFiltersModal() {
            this.showFiltersModal = false;
            document.body.style.overflow = '';
        },

        getFilterIcon(slug) {
            const icons = {
                'all': 'fa-solid fa-globe',
                'favorites': 'fa-solid fa-heart',
                'promo': 'fa-solid fa-percent',
                'popular': 'fa-solid fa-fire',
            };
            return icons[slug] || 'fa-solid fa-filter';
        },

        showPartnerLocation(partner) {
            this.selectedPartnerForMap = partner;
            this.showLocationModal = true;
        },

        getDefaultImage(type) {
            const images = {
                pizza: '/images/fastoran/pizza.png', salad: '/images/fastoran/salad.png',
                pasta: '/images/fastoran/pasta.png', dessert: '/images/fastoran/dessert.png',
                sushi: '/images/fastoran/suschi.png', burger: '/images/fastoran/burger.png',
                khachapuri: '/images/fastoran/khachapuri.png'
            };
            return images[type] || images.pizza;
        },

        async loadInitialData() {
            const tenant = window.Tenant || null;
            if (Array.isArray(tenant?.partners) && tenant.partners.length > 0) {
                this.partnerList = tenant.partners;
            } else {
                await this.loadPartners();
            }
        },

        async loadPartners(pageIndex = 0) {
            this.isPartnersLoading = true;
            try {
                await this.partnerStore.loadPartners({dataObject: {}, page: pageIndex});
                this.partnerList = Array.isArray(this.partnerStore.getPartners) ? this.partnerStore.getPartners : [];
            } catch (error) {
                console.error('Ошибка загрузки партнёров:', error);
            } finally {
                this.isPartnersLoading = false;
            }
        },

        // 🆕 Переключение категории (добавить/убрать)
        selectCategory(categoryId) {
            const index = this.selectedCategories.indexOf(categoryId);
            if (index === -1) {
                this.selectedCategories.push(categoryId);
            } else {
                this.selectedCategories.splice(index, 1);
            }
        },

        // 🆕 Переключение кухни (добавить/убрать)
        selectCuisine(cuisine) {
            const index = this.selectedCuisines.indexOf(cuisine.slug);
            if (index === -1) {
                this.selectedCuisines.push(cuisine.slug);
            } else {
                this.selectedCuisines.splice(index, 1);
            }
        },

        // 🆕 Хелперы для проверки активности
        isCategorySelected(categoryId) {
            return this.selectedCategories.includes(categoryId);
        },

        isCuisineSelected(cuisineSlug) {
            return this.selectedCuisines.includes(cuisineSlug);
        },

        resetFilters() {
            this.searchQuery = '';
            this.selectedCategories = []; // 🆕 массив вместо null
            this.selectedCuisines = [];   // 🆕 массив вместо null
            this.filter = this.dynamicFilters.length > 0 ? this.dynamicFilters[0].slug : 'all';
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
            const favs = Array.isArray(this.favoriteIds) ? this.favoriteIds : [];
            return favs.some(id => String(id) === String(partnerId));
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
                    const products = this.getOrderProducts(item);
                    const productTitles = products.map(p => p.name || p.title).filter(Boolean);
                    const resp = await this.repeatOrder({products: productTitles});
                    const currentProducts = resp?.data || resp || [];

                    if (!Array.isArray(currentProducts) || currentProducts.length === 0) {
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

        selectFilter(slug) {
            this.filter = slug;
        },

        handleScroll() {
            this.scrollY = window.scrollY;
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
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '';
            return date.toLocaleString('ru-RU', {hour: '2-digit', minute: '2-digit'});
        },

        formatPrice(price) {
            const num = parseFloat(price) || 0;
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency', currency: 'RUB', minimumFractionDigits: 0
            }).format(num);
        },

        getOrderProducts(order) {
            if (!order || !order.product_details) return [];
            const details = order.product_details;
            if (Array.isArray(details)) {
                for (const group of details) {
                    if (group && Array.isArray(group.products)) {
                        return group.products;
                    }
                }
            } else if (typeof details === 'object' && details !== null && Array.isArray(details.products)) {
                return details.products;
            }
            return [];
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



        handleResetFilters() {
            this.resetFilters();
            window.scrollTo({top: 0, behavior: 'smooth'});
        },
    },
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
// MODERN STICKY TABS
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
// HERO SECTION
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
// 🎯 ВОЗДУШНАЯ ПАНЕЛЬ ФИЛЬТРОВ + ВИД
// ==========================================
.filter-bar-wrapper {
    position: sticky;
    top: 65px;
    z-index: 90;
    padding: 20px 16px;
    background: transparent;
    display: flex;
    justify-content: center;

    @media (max-width: 768px) {
        top: 84px;
        padding: 16px 12px;
    }
}

.filter-bar {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 8px 20px;
    max-width: 380px;
    width: 100%;
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-radius: 50px;
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.08),
        0 2px 8px rgba(0, 0, 0, 0.04),
        inset 0 1px 0 rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.5);
    transition: all 0.3s ease;

    &:hover {
        box-shadow:
            0 12px 40px rgba(0, 0, 0, 0.1),
            0 4px 12px rgba(0, 0, 0, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    @media (max-width: 768px) {
        gap: 10px;
        padding: 6px 14px;
        max-width: 340px;
    }
}

.bar-line {
    flex: 0 0 1px;
    width: 1px;
    height: 24px;
    background: linear-gradient(
            to bottom,
            transparent 0%,
            rgba($primary, 0.25) 30%,
            rgba($primary, 0.4) 50%,
            rgba($primary, 0.25) 70%,
            transparent 100%
    );
    border-radius: 1px;
}

.view-orb {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.6);
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);

    @media (max-width: 768px) {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }

    &:hover {
        background: rgba(255, 255, 255, 0.95);
        color: $primary;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba($primary, 0.15);
    }

    &:active {
        transform: translateY(0) scale(0.95);
    }

    &.active {
        background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
        color: white;
        box-shadow: 0 4px 12px rgba($primary, 0.35);

        &:hover {
            color: white;
            box-shadow: 0 6px 16px rgba($primary, 0.45);
        }
    }
}

.filter-orb {
    position: relative;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 6px 20px rgba($primary, 0.4),
        0 2px 8px rgba($primary, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;

    @media (max-width: 768px) {
        width: 46px;
        height: 46px;
    }

    &:hover {
        transform: scale(1.08) translateY(-2px);
        box-shadow:
            0 10px 28px rgba($primary, 0.5),
            0 4px 12px rgba($primary, 0.3);

        .orb-icon {
            transform: rotate(15deg);
        }
    }

    &:active {
        transform: scale(0.95);
    }

    &.has-active {
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
        box-shadow:
            0 6px 20px rgba(236, 72, 153, 0.4),
            0 2px 8px rgba(139, 92, 246, 0.3);

        &:hover {
            box-shadow:
                0 10px 28px rgba(236, 72, 153, 0.5),
                0 4px 12px rgba(139, 92, 246, 0.4);
        }
    }
}

.orb-icon {
    color: white;
    font-size: 1.2rem;
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;

    @media (max-width: 768px) {
        font-size: 1.05rem;
    }
}

.orb-pulse {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
    animation: orbPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 0;

    &.delayed {
        animation-delay: 1s;
    }
}

@keyframes orbPulse {
    0% {
        transform: scale(1);
        opacity: 0.6;
    }
    100% {
        transform: scale(1.7);
        opacity: 0;
    }
}

.orb-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    background: #ffffff;
    color: $primary;
    border-radius: 11px;
    font-size: 0.7rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    z-index: 3;
    border: 2px solid $primary;
}

.filter-summary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 8px 16px;
    margin: -8px auto 16px;
    max-width: 90%;
    width: fit-content;
    background: rgba($primary, 0.08);
    border: 1px solid rgba($primary, 0.15);
    border-radius: 50px;

    .summary-text {
        font-size: 0.85rem;
        color: $text;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 300px;
    }

    .summary-reset {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba($danger, 0.1);
        color: $danger;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        transition: all 0.2s;
        flex-shrink: 0;

        &:hover {
            background: $danger;
            color: white;
            transform: rotate(90deg);
        }
    }
}

.summary-slide-enter-active,
.summary-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.summary-slide-enter-from,
.summary-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.badge-pop-enter-active {
    animation: badgePop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.badge-pop-leave-active {
    animation: badgePop 0.3s ease reverse;
}

@keyframes badgePop {
    0% {
        opacity: 0;
        transform: scale(0) rotate(-180deg);
    }
    100% {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

// ==========================================
// ORDERS TAB
// ==========================================
.orders-tab-content {
    animation: fadeIn 0.3s ease;
}

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
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

.hero-title-orders {
    font-size: 2rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;

    @media (max-width: 768px) {
        font-size: 1.5rem;
    }
}

.hero-subtitle-orders {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
    max-width: 400px;

    @media (max-width: 768px) {
        font-size: 0.9rem;
    }
}

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

    i {
        font-size: 1rem;
    }

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
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
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

    strong {
        color: $primary;
    }
}

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

    &:last-child {
        margin-bottom: 0;
    }

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

    i {
        color: $primary;
        font-size: 0.9rem;
    }
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

    i {
        font-size: 1rem;
    }
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

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

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

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
// PARTNERS GRID
// ==========================================
.partners-list {
    width: 100%;
}

.partners-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: 1fr;

    &.grid-view {
        grid-template-columns: repeat(2, 1fr);
    }

    @media (min-width: 768px) {
        grid-template-columns: repeat(2, 1fr);

        &.grid-view {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (min-width: 992px) {
        grid-template-columns: repeat(3, 1fr);

        &.grid-view {
            grid-template-columns: repeat(5, 1fr);
        }
    }

    @media (min-width: 1200px) {
        &.grid-view {
            grid-template-columns: repeat(5, 1fr);
        }
    }
}

.partners-grid > div {
    height: 100%;
    display: flex;
    flex-direction: column;
    margin-bottom: 0 !important;
}

// ==========================================
// 🆕 BOTTOM SHEET MODAL: ФИЛЬТРЫ
// ==========================================
.filters-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.filters-modal-sheet {
    background: #ffffff;
    width: 100%;
    max-width: 600px;
    max-height: 85vh;
    border-radius: 24px 24px 0 0;
    padding: 16px;
    overflow-y: auto;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
    position: relative;
    display: flex;
    flex-direction: column;
}

.modal-handle {
    width: 40px;
    height: 4px;
    background: #d1d5db;
    border-radius: 2px;
    margin: 0 auto 16px;
    flex-shrink: 0;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 0 4px;
    flex-shrink: 0;
}

.modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;

    i {
        color: $primary;
    }
}

.modal-close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.1rem;
    transition: all 0.2s;

    &:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
}

.modal-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
    color: white;
    border-radius: 11px;
    font-size: 0.75rem;
    font-weight: 800;
}

// ==========================================
// 🆕 ПОИСК ВНУТРИ МОДАЛКИ
// ==========================================
.modal-search-wrapper {
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;
}

.modal-search-box {
    position: relative;
    width: 100%;
}

.modal-search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.95rem;
    z-index: 1;
    pointer-events: none;
}

.modal-search-input {
    width: 100%;
    padding: 14px 44px 14px 44px;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    font-size: 0.95rem;
    background: $bg-secondary;
    color: $text;
    transition: all 0.25s ease;
    box-sizing: border-box;

    &::placeholder {
        color: #94a3b8;
    }

    &:focus {
        outline: none;
        background: #ffffff;
        border-color: $primary;
        box-shadow: 0 0 0 4px rgba($primary, 0.1);
    }
}

.modal-search-clear {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #e2e8f0;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    transition: all 0.2s;
    z-index: 1;

    &:hover {
        background: $danger;
        color: white;
    }
}

.modal-search-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 8px 12px;
    background: rgba($primary, 0.08);
    border-radius: 10px;
    font-size: 0.8rem;
    color: $text-muted;

    i {
        color: $primary;
        font-size: 0.85rem;
    }

    strong {
        color: $primary;
        font-weight: 700;
    }
}

// ==========================================
// ГОРИЗОНТАЛЬНЫЙ СКРОЛЛ КУХОНЬ СВЕРХУ
// ==========================================
.cuisines-scroll-wrapper {
    padding: 0 4px 16px;
    margin-bottom: 12px;
    border-bottom: 1px solid $border;
}

.cuisines-scroll-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    padding: 0 4px;
    font-size: 0.85rem;
    font-weight: 700;
    color: $text;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    i {
        color: #ec4899;
        font-size: 0.9rem;
    }
}

.cuisines-scroll {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 4px 4px 8px;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;

    &::-webkit-scrollbar {
        display: none;
    }

    scrollbar-width: none;
}

.cuisine-chip {
    position: relative;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 4px;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: transform 0.2s ease;
    scroll-snap-align: start;

    &:hover {
        transform: translateY(-2px);
    }

    &:active {
        transform: scale(0.95);
    }
}

.cuisine-chip-image {
    position: relative;
    width: 68px;
    height: 68px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid transparent;
    background: $bg-secondary;
    transition: all 0.25s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .cuisine-chip:hover & img {
        transform: scale(1.1);
    }

    .cuisine-chip.active & {
        border-color: $primary;
        box-shadow: 0 6px 16px rgba($primary, 0.35);
    }
}

.cuisine-chip-name {
    font-size: 0.7rem;
    font-weight: 600;
    color: $text-muted;
    max-width: 72px;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.2;
    transition: color 0.2s ease;

    .cuisine-chip.active & {
        color: $primary;
    }
}

.cuisine-chip-check {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: $primary;
    color: white;
    font-size: 0.65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    border: 2px solid white;
    z-index: 2;
    animation: checkPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes checkPop {
    0% {
        transform: scale(0);
    }
    100% {
        transform: scale(1);
    }
}

// ==========================================
// МОДАЛКА — ПУСТАЯ ПОДСКАЗКА
// ==========================================
.modal-empty-hint {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px 20px;
    text-align: center;
    color: $text-muted;

    i {
        font-size: 2rem;
        color: #cbd5e1;
    }

    span {
        font-size: 0.9rem;
        font-weight: 500;
        max-width: 240px;
        line-height: 1.4;
    }
}

// Вкладки
.filters-tabs {
    display: flex;
    gap: 4px;
    padding: 0 4px;
    border-bottom: 1px solid $border;
    background: $bg;
    overflow-x: auto;
    scrollbar-width: none;
    margin-bottom: 16px;

    &::-webkit-scrollbar {
        display: none;
    }
}

.filter-tab-btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 14px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    flex-shrink: 0;

    i {
        font-size: 0.95rem;
    }

    &:hover {
        color: $text;
    }

    &.active {
        color: $primary;
        border-bottom-color: $primary;
    }
}

.tab-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ec4899;
    animation: indicatorPulse 1.5s ease-in-out infinite;
}

@keyframes indicatorPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.2);
    }
}

.filters-modal-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.filters-section {
    flex: 1;
}

.filters-section-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    padding: 0 4px;
}

.fade-in {
    animation: fadeIn 0.3s ease;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;

    @media (max-width: 480px) {
        grid-template-columns: repeat(2, 1fr);
    }
}

.filter-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 8px;
    background: $bg-secondary;
    border: 2px solid transparent;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        background: rgba($primary, 0.05);
        border-color: rgba($primary, 0.2);
        transform: translateY(-2px);
    }

    &.active {
        background: rgba($primary, 0.1);
        border-color: $primary;
    }
}

.filter-card-icon {
    font-size: 1.8rem;
    line-height: 1;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.filter-card-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: $text;
    text-align: center;
    line-height: 1.2;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.filter-card-check {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: $primary;
    color: white;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: checkPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.filters-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: $bg-secondary;
    border: 2px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        background: rgba($primary, 0.05);
        border-color: rgba($primary, 0.2);
    }

    &.active {
        background: rgba($primary, 0.1);
        border-color: $primary;
    }
}

.filter-option-content {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.filter-option-icon {
    width: 36px;
    height: 36px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.filter-option.active .filter-option-icon {
    background: $primary;
    color: white;
}

.filter-option-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: $text;
}

.filter-option-check {
    font-size: 1.1rem;
    color: $primary;
    flex-shrink: 0;
}

.view-mode-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.view-mode-option {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: $bg-secondary;
    border: 2px solid transparent;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;

    &:hover {
        background: rgba($primary, 0.05);
        border-color: rgba($primary, 0.2);
    }

    &.active {
        background: rgba($primary, 0.1);
        border-color: $primary;
    }
}

.view-mode-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    transition: all 0.2s ease;

    .view-mode-option.active & {
        background: $primary;
        color: white;
    }
}

.view-mode-info {
    flex: 1;
    min-width: 0;
}

.view-mode-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: $text;
    margin-bottom: 2px;
}

.view-mode-desc {
    font-size: 0.8rem;
    color: $text-muted;
    line-height: 1.3;
}

.view-mode-check {
    font-size: 1.3rem;
    color: $primary;
    flex-shrink: 0;
    animation: checkPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.reset-filters-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: rgba($danger, 0.1);
    color: $danger;
    border: 2px solid rgba($danger, 0.2);
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 8px;

    &:hover {
        background: $danger;
        color: white;
        border-color: $danger;
    }
}

.bottom-sheet-enter-active,
.bottom-sheet-leave-active {
    transition: opacity 0.3s ease;

    .filters-modal-sheet {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
}

.bottom-sheet-enter-from,
.bottom-sheet-leave-to {
    opacity: 0;

    .filters-modal-sheet {
        transform: translateY(100%);
    }
}

// ==========================================
// ПЛАВАЮЩАЯ КНОПКА СБРОСА (FAB)
// ==========================================
.reset-filters-fab {
    position: fixed;
    left: 16px;
    bottom: 80px;
    z-index: 500;

    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;

    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    border: none;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4), 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;

    i {
        font-size: 1rem;
        transition: transform 0.4s ease;
    }

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(239, 68, 68, 0.5), 0 6px 12px rgba(0, 0, 0, 0.15);

        i {
            transform: rotate(-180deg);
        }
    }

    &:active {
        transform: translateY(0) scale(0.97);
    }

    .fab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        background: rgba(255, 255, 255, 0.3);
        color: #ffffff;
        border-radius: 11px;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1;
    }

    @media (max-width: 400px) {
        padding: 14px;
        border-radius: 50%;
        width: 52px;
        height: 52px;
        justify-content: center;
    }
}

.reset-fab-enter-active,
.reset-fab-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.reset-fab-enter-from,
.reset-fab-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.8);
}

// ==========================================
// ФУНКЦИОНАЛЬНАЯ ПАНЕЛЬ
// ==========================================
.functional-panel {
    padding: 20px;
    max-width: 700px;
    margin: 0 auto 16px;
    transition: all 0.3s ease;

    &.compact-mode {
        padding-top: 24px;
        padding-bottom: 16px;
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border-radius: 0 0 24px 24px;
        margin-bottom: 24px;

        .search-box .search-input {
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
    }

    .search-container {
        margin-bottom: 16px;
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

    .service-features {
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
}

@media (max-width: 768px) {
    .functional-panel {
        padding: 16px;

        &.compact-mode {
            padding: 16px;
            border-radius: 0 0 16px 16px;
        }

        .search-box .search-input {
            padding: 12px 40px 12px 40px;
            font-size: 0.9rem;
        }
    }
}

// ==========================================
// 🆕 БЕЙДЖИ МНОЖЕСТВЕННОГО ВЫБОРА
// ==========================================
.selection-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 800;
    margin-left: 8px;
    box-shadow: 0 2px 6px rgba(236, 72, 153, 0.3);
    animation: badgePop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.selection-badge-sm {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 8px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-left: 8px;
    text-transform: none;
    letter-spacing: 0;
}

// Улучшаем визуальный отклик для множественного выбора
.cuisine-chip.active .cuisine-chip-image {
    border-color: $primary;
    box-shadow: 0 6px 16px rgba($primary, 0.35);
    transform: scale(1.05);
}

.cuisine-chip.active .cuisine-chip-image::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba($primary, 0.1);
    border-radius: 50%;
}

.filter-card.active {
    background: rgba($primary, 0.1);
    border-color: $primary;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba($primary, 0.2);
}

// Анимация появления галочки
.cuisine-chip-check,
.filter-card-check {
    animation: checkPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes checkPop {
    0% {
        transform: scale(0) rotate(-180deg);
    }
    100% {
        transform: scale(1) rotate(0deg);
    }
}

// ==========================================
// 🆕 РЕЖИМ СОВПАДЕНИЯ (any / all)
// ==========================================
.match-mode-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    margin-bottom: 14px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 14px;
}

.match-mode-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.match-mode-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    color: $text;
    white-space: nowrap;

    i {
        color: $primary;
        font-size: 0.85rem;
    }
}

.match-mode-hint {
    font-size: 0.72rem;
    color: $text-muted;
    line-height: 1.3;
}

.match-mode-toggle {
    display: inline-flex;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
    padding: 3px;
    gap: 2px;
    flex-shrink: 0;
}

.match-mode-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $text-muted;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;

    i {
        font-size: 0.75rem;
    }

    &.active {
        background: #ffffff;
        color: $primary;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    &:not(.active):hover {
        color: $text;
    }
}

@media (max-width: 480px) {
    .match-mode-wrapper {
        flex-direction: column;
        align-items: stretch;
    }

    .match-mode-toggle {
        align-self: stretch;

        .match-mode-btn {
            flex: 1;
            justify-content: center;
        }
    }
}
</style>
