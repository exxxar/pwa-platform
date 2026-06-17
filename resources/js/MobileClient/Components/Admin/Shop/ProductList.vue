<template>
    <div class="product-list-page">

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
        <!-- ========================================== -->
        <div class="control-panel">


            <button class="btn-icon" @click="showFilters = !showFilters">
                <i class="fa-solid fa-sliders"></i>
            </button>

            <button class="btn-primary-modern" @click="openAddProductModal">
                <i class="fa-solid fa-plus"></i> Новый товар
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ ФИЛЬТРОВ (СКРЫВАЕМАЯ) -->
        <!-- ========================================== -->
        <div v-if="showFilters" class="filters-bar mobile-optimized">
            <div class="filter-section">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        type="text"
                        v-model="search"
                        class="search-input"
                        placeholder="Поиск..."
                    >
                    <button
                        v-if="search"
                        type="button"
                        class="search-clear"
                        @click="search = ''"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <label class="filter-label">Сортировка</label>
                <div class="sort-controls">
                    <select v-model="sort.param" @change="reloadProducts" class="select-modern">
                        <option value="id">По ID</option>
                        <option value="title">По названию</option>
                        <option value="current_price">По цене</option>
                        <option value="old_price">По скидке</option>
                        <option value="rating">По рейтингу</option>
                        <option value="updated_at">По дате</option>
                    </select>
                    <button class="sort-direction-btn" @click="toggleSortDirection">
                        <i class="fa-solid" :class="sort.direction === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                    </button>
                </div>
            </div>

            <div class="filter-section">
                <label class="filter-label">Статус</label>
                <div class="filter-toggles">
                    <button
                        class="toggle-btn"
                        :class="{ 'is-active': statusFilter === 'all' }"
                        @click="statusFilter = 'all'"
                    >
                        <i class="fa-solid fa-box-open"></i>
                        <span>Все</span>
                    </button>
                    <button
                        class="toggle-btn warning"
                        :class="{ 'is-active': statusFilter === 'stop' }"
                        @click="statusFilter = 'stop'"
                    >
                        <i class="fa-solid fa-ban"></i>
                        <span>Стоп</span>
                    </button>
                    <button
                        class="toggle-btn danger"
                        :class="{ 'is-active': statusFilter === 'removed' }"
                        @click="statusFilter = 'removed'"
                    >
                        <i class="fa-solid fa-trash"></i>
                        <span>Удалённые</span>
                    </button>
                </div>
            </div>

            <div class="filter-section">
                <label class="filter-label">Вид</label>
                <div class="view-toggle">
                    <button
                        class="toggle-btn"
                        :class="{ 'is-active': !need_table }"
                        @click="need_table = false"
                    >
                        <i class="fa-solid fa-grip"></i>
                        <span>Сетка</span>
                    </button>
                    <button
                        class="toggle-btn"
                        :class="{ 'is-active': need_table }"
                        @click="need_table = true"
                    >
                        <i class="fa-solid fa-list"></i>
                        <span>Список</span>
                    </button>
                </div>
            </div>

            <div class="filter-stats">
                <span class="stats-badge">
                    <i class="fa-solid fa-cube"></i>
                    <span>{{ totalProducts }}</span>
                </span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- РЕЖИМ РЕКОМЕНДАЦИЙ -->
        <!-- ========================================== -->
        <div v-if="need_recommendation_config" class="recommendation-bar">
            <div class="reco-icon">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
            <div class="reco-info">
                <strong>Режим рекомендаций</strong>
                <span>Выберите статус</span>
            </div>
            <button class="btn-text" @click="need_recommendation_config = false">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Загрузка...</p>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ТОВАРОВ -->
        <!-- ========================================== -->
        <div v-if="!isLoading" class="products-container">

            <div v-if="products.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3>Товары не найдены</h3>
                <p>{{ search ? 'Измените запрос' : 'Добавьте первый товар' }}</p>
                <button v-if="!search" class="btn-primary-modern" @click="openAddProductModal">
                    <i class="fa-solid fa-plus"></i> Добавить
                </button>
            </div>

            <!-- Режим сетки -->
            <div v-else-if="!need_table && !isSimple" class="products-grid">
                <div
                    v-for="product in products"
                    :key="'product-' + product.id"
                    class="product-card"
                    :class="{
                        'is-stopped': product.in_stop_list_at,
                        'is-deleted': product.deleted_at,
                        'is-selected': isSelected(product.id)
                    }"
                    @click="selectProduct(product)"
                >
                    <div class="card-image">
                        <img v-lazy="product.images?.[0] || '/no-image.png'" :alt="product.title">

                        <div class="status-badges">
                            <span v-if="product.in_stop_list_at" class="status-badge stopped">
                                <i class="fa-solid fa-ban"></i>
                            </span>
                            <span v-if="product.deleted_at" class="status-badge deleted">
                                <i class="fa-solid fa-trash"></i>
                            </span>
                        </div>

                        <div class="integration-badges">
                            <span v-if="product.vk_product_id" class="integration-badge vk">VK</span>
                            <span v-if="product.iiko_article" class="integration-badge iiko">IIKO</span>
                            <span v-if="product.frontpad_article" class="integration-badge fp">FP</span>
                        </div>
                    </div>

                    <div class="card-info">
                        <h4 class="card-title">{{ product.title }}</h4>

                        <div class="card-price">
                            <span class="price-current">{{ formatPrice(product.current_price) }}</span>
                            <span v-if="product.old_price" class="price-old">{{ formatPrice(product.old_price) }}</span>
                        </div>

                        <div class="card-meta">
                            <div class="meta-rating" v-if="product.rating">
                                <i class="fa-solid fa-star"></i>
                                <span>{{ product.rating.toFixed(1) }}</span>
                            </div>
                            <div class="meta-id">ID: {{ product.id }}</div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button
                            v-if="!product.deleted_at"
                            class="action-btn danger"
                            @click.stop="openRemoveModal(product)"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <button
                            v-else
                            class="action-btn success"
                            @click.stop="openRestoreModal(product)"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                        <button
                            class="action-btn"
                            :class="product.in_stop_list_at ? 'success' : 'warning'"
                            @click.stop="openStopListModal(product)"
                        >
                            <i class="fa-solid" :class="product.in_stop_list_at ? 'fa-check' : 'fa-ban'"></i>
                        </button>
                    </div>

                    <div v-if="need_recommendation_config" class="card-recommendation" @click.stop>
                        <div class="reco-buttons">
                            <button
                                class="reco-btn"
                                :class="{ 'active': getRecommendationStatus(product.id) === 0 }"
                                @click="changeStatus(product.id, 0)"
                            >
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <button
                                class="reco-btn recommend"
                                :class="{ 'active': getRecommendationStatus(product.id) === 1 }"
                                @click="changeStatus(product.id, 1)"
                            >
                                <i class="fa-solid fa-thumbs-up"></i>
                            </button>
                            <button
                                class="reco-btn exclude"
                                :class="{ 'active': getRecommendationStatus(product.id) === 2 }"
                                @click="changeStatus(product.id, 2)"
                            >
                                <i class="fa-solid fa-thumbs-down"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Режим списка (мобильный) -->
            <div v-else class="products-list">
                <div
                    v-for="product in products"
                    :key="'list-' + product.id"
                    class="product-list-item"
                    :class="{
                        'is-stopped': product.in_stop_list_at,
                        'is-deleted': product.deleted_at,
                        'is-selected': isSelected(product.id)
                    }"
                    @click="selectProduct(product)"
                >
                    <div class="list-item-image">
                        <img v-lazy="product.images?.[0] || '/no-image.png'" :alt="product.title">
                        <span v-if="product.in_stop_list_at" class="list-status stopped">
                            <i class="fa-solid fa-ban"></i>
                        </span>
                        <span v-else-if="product.deleted_at" class="list-status deleted">
                            <i class="fa-solid fa-trash"></i>
                        </span>
                    </div>

                    <div class="list-item-info">
                        <h4 class="list-item-title">{{ product.title }}</h4>
                        <div class="list-item-price">
                            <span class="price-current">{{ formatPrice(product.current_price) }}</span>
                            <span v-if="product.old_price" class="price-old">{{ formatPrice(product.old_price) }}</span>
                        </div>
                        <div class="list-item-meta">
                            <span v-if="product.rating" class="meta-rating">
                                <i class="fa-solid fa-star"></i>
                                {{ product.rating.toFixed(1) }}
                            </span>
                            <span class="meta-id">ID: {{ product.id }}</span>
                        </div>
                    </div>

                    <div class="list-item-actions">
                        <button
                            v-if="!product.deleted_at"
                            class="action-btn-small danger"
                            @click.stop="openRemoveModal(product)"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <button
                            v-else
                            class="action-btn-small success"
                            @click.stop="openRestoreModal(product)"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                        <button
                            class="action-btn-small"
                            :class="product.in_stop_list_at ? 'success' : 'warning'"
                            @click.stop="openStopListModal(product)"
                        >
                            <i class="fa-solid" :class="product.in_stop_list_at ? 'fa-check' : 'fa-ban'"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- ПАГИНАЦИЯ -->
        <!-- ========================================== -->
        <div v-if="paginate && paginate.last_page > 1" class="pagination-wrapper">
            <Pagination
                :simple="true"
                @pagination_page="nextProducts"
                :pagination="paginate"
            />
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПРОСМОТР ТОВАРА (FULLSCREEN НА МОБИЛЬНЫХ) -->
        <!-- ========================================== -->
        <div v-if="showProductModal" class="modal-overlay mobile-fullscreen" @click.self="hideProductModal">
            <div class="modal-container product-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="hideProductModal">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>{{ selected_product?.title || 'Товар' }}</h3>
                </div>

                <div class="modal-body" v-if="selected_product">
                    <div class="modal-tabs">
                        <button class="modal-tab" :class="{ 'active': modalTab === 0 }" @click="modalTab = 0">
                            <i class="fa-solid fa-info-circle"></i>
                            <span>Описание</span>
                        </button>
                        <button class="modal-tab" :class="{ 'active': modalTab === 1 }" @click="modalTab = 1">
                            <i class="fa-solid fa-star"></i>
                            <span>Отзывы</span>
                        </button>
                        <button class="modal-tab" :class="{ 'active': modalTab === 2 }" @click="modalTab = 2">
                            <i class="fa-solid fa-pen"></i>
                            <span>Редактор</span>
                        </button>
                    </div>

                    <!-- Описание -->
                    <div v-show="modalTab === 0" class="modal-tab-content">
                        <div class="product-preview mobile-stack">
                            <div class="preview-gallery">
                                <div class="preview-main">
                                    <img v-lazy="selected_image || selected_product.images?.[0] || '/no-image.png'" :alt="selected_product.title">
                                </div>
                                <div v-if="selected_product.images?.length > 1" class="preview-thumbs">
                                    <button
                                        v-for="(img, idx) in selected_product.images"
                                        :key="idx"
                                        class="thumb-btn"
                                        :class="{ 'active': selected_image === img }"
                                        @click="selected_image = img"
                                    >
                                        <img v-lazy="img" :alt="'Фото ' + (idx + 1)">
                                    </button>
                                </div>
                            </div>

                            <div class="preview-info">
                                <div class="preview-price">
                                    <span class="price-current">{{ formatPrice(selected_product.current_price) }}</span>
                                    <span v-if="selected_product.old_price" class="price-old">{{ formatPrice(selected_product.old_price) }}</span>
                                </div>

                                <div class="preview-rating" v-if="selected_product.rating">
                                    <div class="rating-stars">
                                        <i
                                            v-for="star in 5"
                                            :key="star"
                                            class="fa-solid fa-star"
                                            :class="{ 'filled': star <= Math.round(selected_product.rating) }"
                                        ></i>
                                    </div>
                                    <span class="rating-value">{{ selected_product.rating.toFixed(1) }} из 5</span>
                                </div>

                                <div class="preview-description">
                                    <h4>Описание</h4>
                                    <p>{{ selected_product.description || 'Описание не указано' }}</p>
                                </div>

                                <div v-if="selected_product.delivery_terms" class="preview-delivery">
                                    <h4><i class="fa-solid fa-truck-fast"></i> Доставка</h4>
                                    <p>{{ selected_product.delivery_terms }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Отзывы -->
                    <div v-show="modalTab === 1" class="modal-tab-content">
                        <div v-if="loading_reviews" class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Загрузка...</p>
                        </div>
                        <div v-else-if="reviews.length === 0" class="empty-state small">
                            <i class="fa-solid fa-comment-slash"></i>
                            <p>Отзывов нет</p>
                        </div>
                        <div v-else class="reviews-list">
                            <div v-for="(review, idx) in reviews" :key="idx" class="review-item">
                                <ReviewCard :need-product="false" v-model="reviews[idx]" />
                            </div>
                            <Pagination
                                v-if="review_paginate"
                                :simple="true"
                                @pagination_page="nextReviews"
                                :pagination="review_paginate"
                            />
                        </div>
                    </div>

                    <!-- Редактор -->
                    <div v-show="modalTab === 2" class="modal-tab-content">
                        <ProductForm
                            @remove-product="openRemoveModal"
                            @callback="onProductSaved"
                            @cancel="modalTab = 0"
                            v-model="selected_product"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ДОБАВИТЬ ТОВАР -->
        <!-- ========================================== -->
        <div v-if="showAddModal" class="modal-overlay mobile-fullscreen" @click.self="showAddModal = false">
            <div class="modal-container add-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="showAddModal = false">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>Новый товар</h3>
                </div>
                <div class="modal-body">
                    <ProductForm
                        @callback="onProductAdded"
                        @cancel="showAddModal = false"
                    />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОДТВЕРЖДЕНИЕ ДЕЙСТВИЯ (BOTTOM SHEET) -->
        <!-- ========================================== -->
        <div v-if="showConfirmModal" class="modal-overlay bottom-sheet" @click.self="hideConfirmModal">
            <div class="modal-container confirm-modal">
                <div class="confirm-icon" :class="confirmType">
                    <i :class="confirmIcon"></i>
                </div>
                <h4>{{ confirmTitle }}</h4>
                <p>{{ confirmText }}</p>
                <div class="confirm-actions">
                    <button class="btn-secondary-modern" @click="hideConfirmModal">Отмена</button>
                    <button class="btn-primary-modern" :class="confirmType" @click="executeConfirm">
                        {{ confirmButtonText }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useProductsStore } from '@/MobileClient/stores/Shop/products'
import ProductForm from '@/MobileClient/Components/Admin/Shop/ProductForm.vue'
import Pagination from '@/MobileClient/Components/Pagination.vue'
import ReviewCard from '@/MobileClient/Components/Shop/ReviewCard.vue'

export default {
    name: 'ProductList',

    components: {
        ProductForm,
        Pagination,
        ReviewCard,
    },

    props: {
        selected: { type: Array, default: null },
        isSimple: { type: Boolean, default: false },
    },

    data() {
        return {
            isLoading: false,
            search: '',
            searchDebounce: null,
            reviews: [],
            selected_product: null,
            selected_image: null,
            paginate: null,
            review_paginate: null,
            loading_reviews: false,

            showFilters: false,
            statusFilter: 'all',
            need_table: false, // По умолчанию сетка на мобильных
            need_recommendation_config: false,
            recommendations: [],
            excludes: [],

            sort: {
                param: 'id',
                direction: 'asc',
            },
            modalTab: 0,

            showProductModal: false,
            showAddModal: false,
            showConfirmModal: false,
            confirmAction: null,
            confirmType: 'danger',
            confirmIcon: 'fa-solid fa-question',
            confirmTitle: '',
            confirmText: '',
            confirmButtonText: 'Подтвердить',
        }
    },

    computed: {
        ...mapState(useProductsStore, [
            'products',
            'products_paginate_object',
        ]),

        tenant() {
            return window.Tenant || null
        },

        totalProducts() {
            return this.products_paginate_object?.total || this.products.length || 0
        },

        isAllSelected() {
            if (!this.selected || !this.products.length) return false
            return this.products.every(p => this.selected.includes(p.id))
        },
    },

    watch: {
        search() {
            if (this.searchDebounce) clearTimeout(this.searchDebounce)
            this.searchDebounce = setTimeout(() => this.reloadProducts(), 300)
        },

        statusFilter() {
            this.reloadProducts()
        },

        modalTab(newTab) {
            if (newTab === 1 && this.selected_product) {
                this.loadReviews(0)
            }
        },
    },

    mounted() {
        this.recommendations = this.tenant?.settings?.recommendation?.products || []
        this.excludes = this.tenant?.settings?.recommendation?.excludes || []

        const savedPage = this.getSavedPage()
        this.loadProductsPage(savedPage)
    },

    beforeUnmount() {
        if (this.searchDebounce) clearTimeout(this.searchDebounce)
    },

    methods: {
        ...mapActions(useProductsStore, [
            'loadProducts',
            'loadReviewsByProductId',
            'changeProductRecommendationStatus',
            'removeShopProduct',
            'restoreProduct',
            'addToStopListProduct',
        ]),

        getSavedPage() {
            try {
                const page = localStorage.getItem('cashman_admin_product_list_page_index')
                return page !== null ? parseInt(page) : 0
            } catch {
                return 0
            }
        },

        savePage(page) {
            try {
                localStorage.setItem('cashman_admin_product_list_page_index', page)
            } catch (err) {
                console.error('Ошибка сохранения страницы:', err)
            }
        },

        reloadProducts() {
            this.loadProductsPage(0)
        },

        async loadProductsPage(page = 0) {
            this.isLoading = true
            try {
                const payload = {
                    dataObject: {
                        search: this.search,
                        direction: this.sort.direction,
                        order_by: this.sort.param,
                        need_removed: this.statusFilter === 'removed',
                        need_all: this.statusFilter !== 'stop',
                    },
                    page,
                }

                await this.loadProducts(payload)
                this.paginate = this.products_paginate_object
            } catch (err) {
                console.error('Ошибка загрузки товаров:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить товары',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        async loadReviews(page = 0) {
            if (!this.selected_product) return
            this.loading_reviews = true
            try {
                const resp = await this.loadReviewsByProductId({
                    dataObject: { product_id: this.selected_product.id },
                    page,
                    size: 30,
                })
                this.reviews = resp?.data || []
                this.review_paginate = resp?.paginate || null
            } catch (err) {
                console.error('Ошибка загрузки отзывов:', err)
                this.reviews = []
            } finally {
                this.loading_reviews = false
            }
        },

        nextProducts(index) {
            this.savePage(index)
            this.loadProductsPage(index)
        },

        nextReviews(index) {
            this.loadReviews(index)
        },

        toggleSortDirection() {
            this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc'
            this.reloadProducts()
        },

        isSelected(id) {
            return Array.isArray(this.selected) && this.selected.includes(id)
        },

        toggleSelect(id) {
            if (!Array.isArray(this.selected)) return
            const idx = this.selected.indexOf(id)
            if (idx >= 0) this.selected.splice(idx, 1)
            else this.selected.push(id)
            this.$emit('update:selected', [...this.selected])
        },

        toggleSelectAll() {
            if (!Array.isArray(this.selected)) return
            if (this.isAllSelected) {
                this.selected.splice(0, this.selected.length)
            } else {
                const ids = this.products.map(p => p.id)
                this.selected.splice(0, this.selected.length, ...ids)
            }
            this.$emit('update:selected', [...this.selected])
        },

        getRecommendationStatus(productId) {
            if (this.excludes.includes(productId)) return 2
            if (this.recommendations.includes(productId)) return 1
            return 0
        },

        async changeStatus(productId, status) {
            try {
                const resp = await this.changeProductRecommendationStatus({
                    product_id: productId,
                    status,
                })
                if (resp?.products) this.recommendations = resp.products
                if (resp?.excludes) this.excludes = resp.excludes
            } catch (err) {
                console.error('Ошибка изменения статуса рекомендации:', err)
            }
        },

        selectProduct(product) {
            this.selected_product = product
            this.selected_image = product.images?.[0] || null
            this.modalTab = 0
            this.showProductModal = true
        },

        openEditProduct(product) {
            this.selected_product = { ...product }
            this.selected_image = product.images?.[0] || null
            this.modalTab = 2
            this.showProductModal = true
        },

        hideProductModal() {
            this.showProductModal = false
            this.selected_product = null
            this.selected_image = null
            this.modalTab = 0
        },

        openRemoveModal(product) {
            this.selected_product = product
            this.confirmType = 'danger'
            this.confirmIcon = 'fa-solid fa-trash'
            this.confirmTitle = 'Удалить товар?'
            this.confirmText = `Товар «${product.title}» будет перемещён в корзину.`
            this.confirmButtonText = 'Удалить'
            this.confirmAction = 'remove'
            this.showConfirmModal = true
        },

        openRestoreModal(product) {
            this.selected_product = product
            this.confirmType = 'success'
            this.confirmIcon = 'fa-solid fa-rotate-left'
            this.confirmTitle = 'Восстановить?'
            this.confirmText = `Товар «${product.title}» будет восстановлен.`
            this.confirmButtonText = 'Восстановить'
            this.confirmAction = 'restore'
            this.showConfirmModal = true
        },

        openStopListModal(product) {
            this.selected_product = product
            const isAdding = !product.in_stop_list_at
            this.confirmType = isAdding ? 'warning' : 'success'
            this.confirmIcon = isAdding ? 'fa-solid fa-ban' : 'fa-solid fa-check'
            this.confirmTitle = isAdding ? 'В стоп-лист?' : 'Вернуть?'
            this.confirmText = isAdding
                ? `Товар «${product.title}» будет недоступен.`
                : `Товар «${product.title}» снова доступен.`
            this.confirmButtonText = isAdding ? 'В стоп-лист' : 'Вернуть'
            this.confirmAction = 'stoplist'
            this.showConfirmModal = true
        },

        hideConfirmModal() {
            this.showConfirmModal = false
            this.selected_product = null
            this.confirmAction = null
        },

        async executeConfirm() {
            if (!this.selected_product || !this.confirmAction) return
            const productId = this.selected_product.id

            try {
                switch (this.confirmAction) {
                    case 'remove':
                        await this.removeShopProduct(productId)
                        this.$notify?.({ title: 'Успех', text: 'Удалён', type: 'success' })
                        break
                    case 'restore':
                        await this.restoreProduct(productId)
                        this.$notify?.({ title: 'Успех', text: 'Восстановлен', type: 'success' })
                        break
                    case 'stoplist':
                        await this.addToStopListProduct(productId)
                        this.$notify?.({ title: 'Успех', text: 'Статус изменён', type: 'success' })
                        break
                }

                this.hideConfirmModal()
                this.loadProductsPage(this.paginate?.current_page || 0)

                if (this.showProductModal) this.hideProductModal()
            } catch (err) {
                console.error('Ошибка выполнения действия:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось выполнить',
                    type: 'error',
                })
            }
        },

        openAddProductModal() {
            this.showAddModal = true
        },

        onProductAdded() {
            this.showAddModal = false
            this.loadProductsPage(0)
        },

        onProductSaved() {
            this.loadProductsPage(this.paginate?.current_page || 0)
            this.hideProductModal()
            this.$notify?.({ title: 'Успех', text: 'Сохранён', type: 'success' })
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0)
        },
    },
}
</script>

<style lang="scss" scoped>
@use "sass:color";
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-warning: #f59e0b;
$admin-success: #10b981;
$admin-danger: #ef4444;

.product-list-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ПАНЕЛЬ УПРАВЛЕНИЯ (КОМПАКТНАЯ)
// ==========================================
.control-panel {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    z-index: 100;
}

.search-box {
    flex: 1;
    position: relative;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: $admin-text-muted;
    font-size: 0.9rem;
}

.search-input {
    width: 100%;
    padding: 10px 36px 10px 36px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }
}

.search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: $admin-border;
    border: none;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    transition: all 0.2s;

    &:hover {
        background: $admin-danger;
        color: white;
    }
}

.btn-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;

    &:hover, &.is-active {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;
    }
}

.btn-primary-modern {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: $admin-primary;
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;

    &:hover {
        background:  color.adjust($admin-primary, $lightness: -5%);
    }
}

// ==========================================
// ПАНЕЛЬ ФИЛЬТРОВ (СКРЫВАЕМАЯ)
// ==========================================
.filters-bar {
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    padding: 16px;
    animation: slideDown 0.3s ease;

    &.mobile-optimized {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filter-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text-muted;
}

.sort-controls {
    display: flex;
    gap: 8px;
}

.select-modern {
    flex: 1;
    padding: 10px 32px 10px 12px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.9rem;
    background: $admin-card-bg;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;

    &:focus {
        outline: none;
        border-color: $admin-primary;
    }
}

.sort-direction-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $admin-primary;
        color: white;
        border-color: $admin-primary;
    }
}

.filter-toggles, .view-toggle {
    display: flex;
    gap: 8px;
}

.toggle-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:hover {
        border-color: $admin-primary;
        color: $admin-primary;
    }

    &.is-active {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;
    }

    &.warning.is-active {
        background: $admin-warning;
        border-color: $admin-warning;
    }

    &.danger.is-active {
        background: $admin-danger;
        border-color: $admin-danger;
    }
}

.filter-stats {
    text-align: center;
}

.stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: rgba($admin-primary, 0.08);
    color: $admin-primary;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
}

// ==========================================
// РЕЖИМ РЕКОМЕНДАЦИЙ
// ==========================================
.recommendation-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: linear-gradient(135deg, rgba(#8b5cf6, 0.08) 0%, rgba(#7c3aed, 0.08) 100%);
    border-bottom: 1px solid rgba(#8b5cf6, 0.2);
}

.reco-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.reco-info {
    flex: 1;
    display: flex;
    flex-direction: column;

    strong {
        font-size: 0.85rem;
        color: $admin-text;
    }

    span {
        font-size: 0.75rem;
        color: $admin-text-muted;
    }
}

// ==========================================
// ИНДИКАТОР ЗАГРУЗКИ
// ==========================================
.loading-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: $admin-text-muted;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid $admin-border;
    border-top-color: $admin-primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// КОНТЕЙНЕР ТОВАРОВ
// ==========================================
.products-container {
    padding: 5px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $admin-text-muted;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: $admin-bg;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 16px;
    }

    h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: $admin-text;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        margin-bottom: 20px;
    }

    &.small {
        padding: 30px 20px;

        i {
            font-size: 2rem;
            margin-bottom: 8px;
        }
    }
}

// ==========================================
// СЕТКА ТОВАРОВ
// ==========================================
.products-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.product-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;

    &:active {
        transform: scale(0.98);
    }

    &.is-stopped {
        opacity: 0.7;
        border-color: $admin-warning;
    }

    &.is-deleted {
        opacity: 0.5;
        border-color: $admin-danger;
    }

    &.is-selected {
        border-color: $admin-success;
        box-shadow: 0 0 0 2px rgba($admin-success, 0.2);
    }
}

.card-image {
    position: relative;
    aspect-ratio: 1;
    background: $admin-bg;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.status-badges {
    position: absolute;
    top: 8px;
    left: 8px;
    display: flex;
    gap: 4px;
}

.status-badge {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: white;

    &.stopped {
        background: $admin-warning;
    }

    &.deleted {
        background: $admin-danger;
    }
}

.integration-badges {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.integration-badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    color: white;
    backdrop-filter: blur(8px);

    &.vk {
        background: rgba(0, 119, 255, 0.9);
    }

    &.iiko {
        background: rgba(239, 68, 68, 0.9);
    }

    &.fp {
        background: rgba(245, 158, 11, 0.9);
    }
}

.card-info {
    padding: 12px;
}

.card-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text;
    margin-bottom: 8px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.3em;
}

.card-price {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-bottom: 8px;
}

.price-current {
    font-size: 1rem;
    font-weight: 700;
    color: $admin-text;
}

.price-old {
    font-size: 0.8rem;
    color: $admin-text-muted;
    text-decoration: line-through;
}

.card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.75rem;
    color: $admin-text-muted;
}

.meta-rating {
    display: flex;
    align-items: center;
    gap: 3px;

    i {
        color: #fbbf24;
    }
}

.card-actions {
    display: flex;
    gap: 6px;
    padding: 0 12px 12px;
}

.action-btn {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    min-height: 40px;

    &:active {
        transform: scale(0.95);
    }

    &.danger:active {
        background: $admin-danger;
        border-color: $admin-danger;
        color: white;
    }

    &.warning:active {
        background: $admin-warning;
        border-color: $admin-warning;
        color: white;
    }

    &.success:active {
        background: $admin-success;
        border-color: $admin-success;
        color: white;
    }
}

.card-recommendation {
    padding: 12px;
    background: $admin-bg;
    border-top: 1px solid $admin-border;
}

.reco-buttons {
    display: flex;
    gap: 6px;
}

.reco-btn {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    min-height: 40px;

    &.active {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;
    }

    &.recommend.active {
        background: $admin-success;
        border-color: $admin-success;
    }

    &.exclude.active {
        background: $admin-danger;
        border-color: $admin-danger;
    }
}

// ==========================================
// СПИСОК ТОВАРОВ (МОБИЛЬНЫЙ)
// ==========================================
.products-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.product-list-item {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 12px;
    display: flex;
    gap: 12px;
    cursor: pointer;
    transition: all 0.2s;

    &:active {
        transform: scale(0.98);
    }

    &.is-stopped {
        opacity: 0.7;
        border-color: $admin-warning;
    }

    &.is-deleted {
        opacity: 0.5;
        border-color: $admin-danger;
    }

    &.is-selected {
        border-color: $admin-success;
        box-shadow: 0 0 0 2px rgba($admin-success, 0.2);
    }
}

.list-item-image {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    background: $admin-bg;
    flex-shrink: 0;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.list-status {
    position: absolute;
    top: 4px;
    left: 4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    color: white;

    &.stopped {
        background: $admin-warning;
    }

    &.deleted {
        background: $admin-danger;
    }
}

.list-item-info {
    flex: 1;
    min-width: 0;
}

.list-item-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text;
    margin-bottom: 6px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.list-item-price {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-bottom: 6px;
}

.list-item-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.75rem;
    color: $admin-text-muted;
}

.list-item-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.action-btn-small {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;

    &:active {
        transform: scale(0.9);
    }

    &.danger:active {
        background: $admin-danger;
        border-color: $admin-danger;
        color: white;
    }

    &.warning:active {
        background: $admin-warning;
        border-color: $admin-warning;
        color: white;
    }

    &.success:active {
        background: $admin-success;
        border-color: $admin-success;
        color: white;
    }
}

// ==========================================
// ПАГИНАЦИЯ
// ==========================================
.pagination-wrapper {
    padding: 20px 16px;
    display: flex;
    justify-content: center;
}

// ==========================================
// МОДАЛКИ (MOBILE FULLSCREEN)
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    animation: fadeIn 0.2s ease;

    &.mobile-fullscreen {
        align-items: stretch;
    }

    &.bottom-sheet {
        align-items: flex-end;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;

    .mobile-fullscreen & {
        border-radius: 0;
        max-height: 100vh;
    }

    .bottom-sheet & {
        border-radius: 16px 16px 0 0;
        max-height: 80vh;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(100%);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-modal, .add-modal {
    max-width: 100%;
}

.confirm-modal {
    padding: 24px 20px;
    text-align: center;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    background: $admin-card-bg;
    z-index: 10;

    h3 {
        flex: 1;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

.modal-back {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
    border: none;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:active {
        background: $admin-primary;
        color: white;
    }
}

.modal-body {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

.modal-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    background: $admin-bg;
    padding: 4px;
    border-radius: 10px;
}

.modal-tab {
    flex: 1;
    padding: 10px 12px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 44px;

    span {
        display: none;
    }

    &.active {
        background: $admin-card-bg;
        color: $admin-primary;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
}

.modal-tab-content {
    animation: fadeIn 0.3s ease;
}

.product-preview {
    display: flex;
    flex-direction: column;
    gap: 16px;

    &.mobile-stack {
        flex-direction: column;
    }
}

.preview-gallery {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.preview-main {
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    background: $admin-bg;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.preview-thumbs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    -webkit-overflow-scrolling: touch;
}

.thumb-btn {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
    padding: 0;
    background: $admin-bg;

    &.active {
        border-color: $admin-primary;
    }

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.preview-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.preview-price {
    display: flex;
    align-items: baseline;
    gap: 12px;

    .price-current {
        font-size: 1.5rem;
    }
}

.preview-rating {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rating-stars {
    display: flex;
    gap: 2px;

    i {
        color: $admin-border;
        font-size: 1rem;

        &.filled {
            color: #fbbf24;
        }
    }
}

.rating-value {
    font-weight: 600;
    color: $admin-text;
}

.preview-description,
.preview-delivery {
    h4 {
        font-size: 0.9rem;
        font-weight: 600;
        color: $admin-text;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    p {
        font-size: 0.9rem;
        color: $admin-text-muted;
        line-height: 1.5;
        margin: 0;
    }
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.loading-state {
    text-align: center;
    padding: 40px 20px;
    color: $admin-text-muted;
}

.confirm-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;

    &.danger {
        background: rgba($admin-danger, 0.1);
        color: $admin-danger;
    }

    &.warning {
        background: rgba($admin-warning, 0.1);
        color: $admin-warning;
    }

    &.success {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }
}

.confirm-modal {
    h4 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        color: $admin-text-muted;
        margin-bottom: 24px;
        line-height: 1.4;
    }
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

.btn-primary-modern, .btn-secondary-modern {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:active {
        transform: scale(0.98);
    }
}

.btn-primary-modern {
    background: $admin-primary;
    color: white;

    &.danger {
        background: $admin-danger;
    }

    &.warning {
        background: $admin-warning;
    }

    &.success {
        background: $admin-success;
    }
}

.btn-secondary-modern {
    background: $admin-bg;
    color: $admin-text;
    border: 1px solid $admin-border;
}

.btn-text {
    background: none;
    border: none;
    color: $admin-text-muted;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 6px;

    &:active {
        background: rgba($admin-danger, 0.1);
        color: $admin-danger;
    }
}

// ==========================================
// ДЕСКТОПНЫЕ СТИЛИ (ОПЦИОНАЛЬНО)
// ==========================================
@media (min-width: 768px) {
    .control-panel {
        padding: 16px 24px;
    }

    .search-box {
        max-width: 400px;
    }

    .btn-primary-modern {
        width: auto;
        padding: 10px 18px;
        gap: 8px;

        span {
            display: inline;
        }
    }

    .filters-bar {
        padding: 16px 24px;
    }

    .products-container {
        padding: 24px;
    }

    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }

    .modal-overlay {
        padding: 20px;

        &.mobile-fullscreen {
            align-items: center;
        }

        &.bottom-sheet {
            align-items: center;
        }
    }

    .modal-container {
        max-width: 900px;
        border-radius: 16px;
        max-height: 90vh;

        .product-modal &, .add-modal & {
            max-width: 900px;
        }

        .confirm-modal & {
            max-width: 400px;
        }
    }

    .modal-tab {
        span {
            display: inline;
        }
    }

    .product-preview {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .products-list {
        display: none; // На десктопе используем только сетку
    }
}
</style>
