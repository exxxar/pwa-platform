<template>
    <transition name="modal-fade">
        <div
            v-if="isOpen"
            class="product-modal-overlay"
            @click.self="closeModal"
        >
            <div class="product-modal-sheet">

                <!-- ========================================== -->
                <!-- ШАПКА С ЗАКРЫТИЕМ -->
                <!-- ========================================== -->
                <div class="sheet-header">
                    <button class="close-btn" @click="closeModal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <button class="share-btn" @click="toggleShareMenu">
                        <i class="fa-solid fa-share-nodes"></i>
                    </button>

                    <!-- Меню "Поделиться" -->
                    <transition name="fade">
                        <div v-if="showShareMenu" class="share-menu">
                            <button class="share-option" @click="shareVia('copy')">
                                <i class="fa-solid fa-link"></i>
                                <span>Скопировать</span>
                            </button>
                            <button class="share-option" @click="shareVia('telegram')">
                                <i class="fa-brands fa-telegram"></i>
                                <span>Telegram</span>
                            </button>
                            <button class="share-option" @click="shareVia('whatsapp')">
                                <i class="fa-brands fa-whatsapp"></i>
                                <span>WhatsApp</span>
                            </button>
                        </div>
                    </transition>
                </div>

                <!-- ========================================== -->
                <!-- КОНТЕНТ (СКРОЛЛИТСЯ) -->
                <!-- ========================================== -->
                <div class="sheet-body" v-if="item">

                    <!-- ГАЛЕРЕЯ -->
                    <div class="gallery-section">
                        <div class="main-image-wrapper">
                            <img
                                v-lazy="selectedImage"
                                :alt="item.title"
                                class="main-image"
                            >

                            <!-- Бейдж скидки -->
                            <div v-if="discount > 0" class="discount-badge">
                                -{{ discount }}%
                            </div>

                            <!-- Индикатор "Нет в наличии" -->
                            <div v-if="item.in_stop_list" class="out-of-stock-badge">
                                <i class="fa-solid fa-lock"></i>
                                <span>Нет в наличии</span>
                            </div>
                        </div>

                        <!-- Превью -->
                        <div v-if="(item.images || []).length > 1" class="thumbnails-scroll">
                            <button
                                v-for="(img, index) in item.images"
                                :key="index"
                                class="thumbnail-btn"
                                :class="{ 'active': selectedImage === img }"
                                @click="selectedImage = img"
                            >
                                <img :src="img" :alt="'Фото ' + (index + 1)">
                            </button>
                        </div>
                    </div>

                    <!-- ОСНОВНАЯ ИНФОРМАЦИЯ -->
                    <div class="product-info-section">
                        <h2 class="product-title">{{ item.title || 'Товар' }}</h2>

                        <!-- Цена -->
                        <div class="price-block">
                            <div class="price-current">
                                {{ formatPrice(item.price) }}
                            </div>
                            <div v-if="item.old_price > 0" class="price-old">
                                {{ formatPrice(item.old_price) }}
                            </div>
                            <div v-if="discount > 0" class="price-save">
                                Вы экономите {{ formatPrice(item.old_price - item.price) }}
                            </div>
                        </div>

                        <!-- Рейтинг -->
                        <div v-if="item.rating" class="rating-block">
                            <div class="rating-stars">
                                <i
                                    v-for="star in 5"
                                    :key="star"
                                    class="fa-solid fa-star"
                                    :class="{ 'filled': star <= Math.round(item.rating) }"
                                ></i>
                            </div>
                            <span class="rating-value">{{ item.rating.toFixed(1) }}</span>
                            <span class="rating-count">
                                ({{ item.reviews_count || 0 }}
                                {{ pluralize(item.reviews_count || 0, 'отзыв', 'отзыва', 'отзывов') }})
                            </span>
                        </div>

                        <!-- Быстрые теги -->
                        <div class="quick-tags">
                            <div v-if="item.delivery_terms" class="quick-tag delivery-tag">
                                <i class="fa-solid fa-truck"></i>
                                <span>{{ item.delivery_terms }}</span>
                            </div>
                            <div v-if="item.dimension?.weight > 0" class="quick-tag">
                                <i class="fa-solid fa-weight-hanging"></i>
                                <span>{{ item.dimension.weight }} кг</span>
                            </div>
                        </div>
                    </div>

                    <!-- ПАРАМЕТРЫ ТОВАРА -->
                    <div v-if="hasDimensions" class="dimensions-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fa-solid fa-ruler-combined"></i>
                            </div>
                            <h6 class="section-title">Параметры</h6>
                        </div>
                        <div class="dimensions-grid">
                            <div v-if="item.dimension?.width > 0" class="dimension-card">
                                <div class="dimension-icon">
                                    <i class="fa-solid fa-arrows-left-right"></i>
                                </div>
                                <div class="dimension-info">
                                    <div class="dimension-label">Ширина</div>
                                    <div class="dimension-value">{{ item.dimension.width }} см</div>
                                </div>
                            </div>
                            <div v-if="item.dimension?.height > 0" class="dimension-card">
                                <div class="dimension-icon">
                                    <i class="fa-solid fa-arrows-up-down"></i>
                                </div>
                                <div class="dimension-info">
                                    <div class="dimension-label">Высота</div>
                                    <div class="dimension-value">{{ item.dimension.height }} см</div>
                                </div>
                            </div>
                            <div v-if="item.dimension?.length > 0" class="dimension-card">
                                <div class="dimension-icon">
                                    <i class="fa-solid fa-ruler"></i>
                                </div>
                                <div class="dimension-info">
                                    <div class="dimension-label">Длина</div>
                                    <div class="dimension-value">{{ item.dimension.length }} см</div>
                                </div>
                            </div>
                            <div v-if="item.dimension?.weight > 0" class="dimension-card">
                                <div class="dimension-icon">
                                    <i class="fa-solid fa-weight-hanging"></i>
                                </div>
                                <div class="dimension-info">
                                    <div class="dimension-label">Вес</div>
                                    <div class="dimension-value">{{ item.dimension.weight }} кг</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TABS -->
                    <div class="tabs-section">
                        <div class="pill-tabs">
                            <button
                                class="pill-tab"
                                :class="{ 'active': activeTab === 'description' }"
                                @click="activeTab = 'description'"
                            >
                                <i class="fa-solid fa-align-left"></i>
                                <span>Описание</span>
                            </button>
                            <button
                                class="pill-tab"
                                :class="{ 'active': activeTab === 'reviews' }"
                                @click="switchToReviews"
                            >
                                <i class="fa-solid fa-comments"></i>
                                <span>Отзывы</span>
                                <span v-if="item.reviews_count > 0" class="tab-badge">
                                    {{ item.reviews_count }}
                                </span>
                            </button>
                        </div>

                        <!-- Описание -->
                        <div v-if="activeTab === 'description'" class="tab-content">
                            <div v-if="item.description" class="description-text" v-text="item.description"></div>
                            <div v-else class="empty-description">
                                <i class="fa-solid fa-file-lines"></i>
                                <p>Описание пока не добавлено</p>
                            </div>
                        </div>

                        <!-- Отзывы -->
                        <div v-if="activeTab === 'reviews'" class="tab-content">

                            <!-- Загрузка -->
                            <div v-if="loadingReviews" class="loading-state">
                                <div class="loading-spinner"></div>
                                <p>Загружаем отзывы...</p>
                            </div>

                            <!-- Список отзывов -->
                            <div v-else-if="reviews.length > 0" class="reviews-list">
                                <div
                                    v-for="(review, index) in reviews"
                                    :key="review.id || index"
                                    class="review-card"
                                >
                                    <ReviewCard :need-product="false" v-model="reviews[index]" />
                                </div>

                                <!-- Пагинация -->
                                <button
                                    v-if="paginate?.current_page < paginate?.last_page"
                                    class="load-more-btn"
                                    @click="loadReviews(paginate.current_page)"
                                >
                                    <span>Показать ещё</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>

                            <!-- Пустое состояние -->
                            <div v-else class="empty-reviews">
                                <div class="empty-icon">
                                    <i class="fa-solid fa-comment-dots"></i>
                                </div>
                                <h6>Отзывов пока нет</h6>
                                <p>
                                    Чтобы оставить отзыв, необходимо заказать этот товар.
                                    После покупки вы сможете поделиться впечатлениями!
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ========================================== -->
                <!-- FOOTER С КНОПКАМИ ДЕЙСТВИЙ -->
                <!-- ========================================== -->
                <div class="sheet-footer" v-if="item">

                    <!-- Товар доступен -->
                    <template v-if="!item.in_stop_list">

                        <!-- Не в корзине — кнопка "В корзину" -->
                        <button
                            v-if="checkInCart === 0"
                            class="add-to-cart-btn"
                            :class="{ 'pulse': justAdded }"
                            :disabled="!canProductAction"
                            @click="incProductCart"
                        >
                            <div class="btn-content">
                                <i class="fa-solid fa-cart-plus"></i>
                                <div class="btn-info">
                                    <span class="btn-label">Добавить в корзину</span>
                                    <span class="btn-price">{{ formatPrice(item.price) }}</span>
                                </div>
                            </div>
                        </button>

                        <!-- В корзине — счётчик -->
                        <div v-else class="quantity-stepper">
                            <button
                                class="stepper-btn minus"
                                @click="decProductCart"
                                :disabled="!canProductAction"
                            >
                                <i class="fa-solid fa-minus"></i>
                            </button>

                            <div class="stepper-value">
                                <span class="value-number">{{ checkInCart }}</span>
                                <span class="value-label">в корзине</span>
                            </div>

                            <button
                                class="stepper-btn plus"
                                @click="incProductCart"
                                :disabled="!canProductAction"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                    </template>

                    <!-- Нет в наличии -->
                    <div v-else class="out-of-stock-footer">
                        <i class="fa-solid fa-lock"></i>
                        <span>Товар недоступен для заказа</span>
                    </div>

                </div>

            </div>
        </div>
    </transition>
</template>

<script>
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';
import ReviewCard from "@/MobileClient/Components/Shop/Reviews/ReviewCard.vue";

export default {
    name: "ProductInfo",

    components: {
        ReviewCard,
    },

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            isOpen: false,
            item: null,
            selectedImage: null,
            activeTab: 'description',
            loadingReviews: false,
            reviews: [],
            paginate: null,
            sending: false,
            isOnline: navigator.onLine,
            justAdded: false,
            showShareMenu: false,
        };
    },

    computed: {
        checkInCart() {
            return this.item ? this.basketStore.inCart(this.item.id) : 0;
        },

        canProductAction() {
            return this.isOnline && !this.sending;
        },

        discount() {
            const old = this.item?.old_price || 0;
            const cur = this.item?.price || 0;
            return old > 0 ? Math.round((1 - cur / old) * 100) : 0;
        },

        hasDimensions() {
            const d = this.item?.dimension;
            if (!d) return false;
            return d.width > 0 || d.height > 0 || d.length > 0 || d.weight > 0;
        },

        productLink() {
            if (!this.item) return '';
            const tenant = window.Tenant;
            return `${window.location.origin}/product/${this.item.id}?tenant=${tenant?.slug || ''}`;
        },
    },

    mounted() {
        window.addEventListener('online', () => { this.isOnline = true; });
        window.addEventListener('offline', () => { this.isOnline = false; });
        window.addEventListener('product-info-event', this.onProductInfo);
    },

    beforeUnmount() {
        window.removeEventListener('product-info-event', this.onProductInfo);
    },

    methods: {
        onProductInfo(event) {
            this.item = event.detail.product;
            this.selectedImage = this.item.images?.[0].url || null;
            this.activeTab = 'description';
            this.reviews = [];
            this.paginate = null;
            this.isOpen = true;

            // Блокируем скролл body
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.isOpen = false;
            this.showShareMenu = false;
            document.body.style.overflow = '';
        },

        toggleShareMenu() {
            this.showShareMenu = !this.showShareMenu;
        },

        async shareVia(type) {
            this.showShareMenu = false;

            try {
                if (type === 'copy') {
                    await navigator.clipboard.writeText(this.productLink);
                    this.$notify?.({
                        title: 'Ссылка',
                        text: 'Ссылка скопирована в буфер обмена',
                        type: 'success',
                    });
                } else if (type === 'telegram') {
                    window.open(
                        `https://t.me/share/url?url=${encodeURIComponent(this.productLink)}&text=${encodeURIComponent(this.item.title)}`,
                        '_blank'
                    );
                } else if (type === 'whatsapp') {
                    window.open(
                        `https://wa.me/?text=${encodeURIComponent(this.item.title + ' ' + this.productLink)}`,
                        '_blank'
                    );
                }
            } catch (error) {
                console.error('Ошибка шаринга:', error);
            }
        },

        switchToReviews() {
            this.activeTab = 'reviews';
            if (this.reviews.length === 0) {
                this.loadReviews(0);
            }
        },

        async loadReviews(page = 0) {
            this.loadingReviews = true;
            try {
                const resp = await this.basketStore.loadReviewsByProductId({
                    dataObject: { product_id: this.item.id },
                    page,
                    size: 30,
                });

                if (page === 0) {
                    this.reviews = resp.data || [];
                } else {
                    this.reviews = [...this.reviews, ...(resp.data || [])];
                }
                this.paginate = resp.paginate || null;
            } catch (error) {
                console.error('Ошибка загрузки отзывов:', error);
                this.$notify?.({
                    title: 'Отзывы',
                    text: 'Ошибка загрузки отзывов',
                    type: 'error',
                });
            } finally {
                this.loadingReviews = false;
            }
        },

        async incProductCart() {
            if (this.sending) return;
            this.sending = true;

            try {
                await this.basketStore.addProductToCart(this.item);

                this.justAdded = true;
                setTimeout(() => { this.justAdded = false; }, 600);

                this.$notify?.({
                    title: 'Корзина',
                    text: `«${this.item.title}» добавлен в корзину`,
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка добавления:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось добавить товар',
                    type: 'error',
                });
            } finally {
                this.sending = false;
            }
        },

        async decProductCart() {
            if (this.sending) return;
            this.sending = true;

            try {
                await this.basketStore.removeProductFromCart(this.item.id);
                this.$notify?.({
                    title: 'Корзина',
                    text: `«${this.item.title}» убран из корзины`,
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка удаления:', error);
            } finally {
                this.sending = false;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0);
        },

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
/* ==========================================
   OVERLAY
   ========================================== */
.product-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

/* ==========================================
   SHEET (Bottom Sheet)
   ========================================== */
.product-modal-sheet {
    width: 100%;
    max-width: 600px;
    max-height: 92vh;
    background: var(--bs-body-bg);
    border-radius: 24px 24px 0 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

@media (min-width: 768px) {
    .product-modal-overlay {
        align-items: center;
        padding: 20px;
    }

    .product-modal-sheet {
        max-height: 85vh;
        border-radius: 24px;
    }
}

/* ==========================================
   ШАПКА
   ========================================== */
.sheet-header {
    position: relative;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    z-index: 10;
}

.close-btn,
.share-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-body-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: #dc3545;
    color: white;
    transform: rotate(90deg);
}

.share-btn:hover {
    background: var(--bs-primary);
    color: white;
}

/* Меню "Поделиться" */
.share-menu {
    position: absolute;
    top: 60px;
    right: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 20;
    min-width: 160px;
}

.share-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: var(--bs-body-color);
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.share-option:hover {
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
}

.share-option i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

/* ==========================================
   ТЕЛО (СКРОЛЛ)
   ========================================== */
.sheet-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.sheet-body::-webkit-scrollbar {
    width: 4px;
}

.sheet-body::-webkit-scrollbar-thumb {
    background: var(--bs-border-color);
    border-radius: 2px;
}

/* ==========================================
   ГАЛЕРЕЯ
   ========================================== */
.gallery-section {
    position: relative;
}

.main-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    background: var(--bs-secondary-bg);
    overflow: hidden;
}

.main-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: opacity 0.3s ease;
}

.discount-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 14px;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

.out-of-stock-badge {
    position: absolute;
    bottom: 16px;
    left: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
    color: white;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Превью */
.thumbnails-scroll {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    overflow-x: auto;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
}

.thumbnails-scroll::-webkit-scrollbar {
    height: 3px;
}

.thumbnails-scroll::-webkit-scrollbar-thumb {
    background: var(--bs-border-color);
    border-radius: 2px;
}

.thumbnail-btn {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    padding: 0;
    background: var(--bs-secondary-bg);
}

.thumbnail-btn:hover {
    border-color: var(--bs-primary);
    transform: scale(1.05);
}

.thumbnail-btn.active {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.thumbnail-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ==========================================
   ОСНОВНАЯ ИНФОРМАЦИЯ
   ========================================== */
.product-info-section {
    padding: 20px 16px;
}

.product-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--bs-body-color);
    margin: 0 0 12px 0;
    line-height: 1.3;
}

/* Цена */
.price-block {
    display: flex;
    align-items: baseline;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.price-current {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--bs-primary);
    line-height: 1;
}

.price-old {
    font-size: 1.1rem;
    color: var(--bs-secondary-color);
    text-decoration: line-through;
}

.price-save {
    font-size: 0.85rem;
    color: #198754;
    font-weight: 600;
    padding: 4px 10px;
    background: rgba(25, 135, 84, 0.1);
    border-radius: 8px;
}

/* Рейтинг */
.rating-block {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
}

.rating-stars {
    display: flex;
    gap: 2px;
}

.rating-stars i {
    font-size: 0.9rem;
    color: var(--bs-border-color);
    transition: color 0.2s ease;
}

.rating-stars i.filled {
    color: #ffc107;
}

.rating-value {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.rating-count {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

/* Быстрые теги */
.quick-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.quick-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.quick-tag i {
    font-size: 0.75rem;
}

.delivery-tag {
    background: rgba(255, 193, 7, 0.12);
    color: #b8860b;
}

.delivery-tag i {
    color: #ffc107;
}

/* ==========================================
   ПАРАМЕТРЫ
   ========================================== */
.dimensions-section {
    padding: 0 16px 20px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.section-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.dimensions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.dimension-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
}

.dimension-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.dimension-info {
    flex: 1;
    min-width: 0;
}

.dimension-label {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 2px;
}

.dimension-value {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

/* ==========================================
   TABS
   ========================================== */
.tabs-section {
    padding: 0 16px 20px;
}

.pill-tabs {
    display: flex;
    gap: 8px;
    background: var(--bs-secondary-bg);
    padding: 4px;
    border-radius: 14px;
    margin-bottom: 16px;
}

.pill-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.pill-tab:hover:not(.active) {
    color: var(--bs-body-color);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.pill-tab.active {
    background: var(--bs-body-bg);
    color: var(--bs-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.tab-badge {
    padding: 2px 8px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.pill-tab.active .tab-badge {
    background: var(--bs-primary);
    color: white;
}

.tab-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Описание */
.description-text {
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--bs-body-color);
    white-space: pre-wrap;
}

.empty-description {
    text-align: center;
    padding: 40px 20px;
    color: var(--bs-secondary-color);
}

.empty-description i {
    font-size: 2rem;
    opacity: 0.3;
    margin-bottom: 12px;
}

.empty-description p {
    margin: 0;
    font-size: 0.9rem;
}

/* Загрузка отзывов */
.loading-state {
    text-align: center;
    padding: 40px 20px;
}

.loading-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid var(--bs-border-color);
    border-top-color: var(--bs-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-state p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
}

/* Отзывы */
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.review-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    overflow: hidden;
}

.load-more-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: transparent;
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-primary);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 8px;
}

.load-more-btn:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.load-more-btn i {
    transition: transform 0.2s ease;
}

.load-more-btn:hover i {
    transform: translateY(2px);
}

/* Пустые отзывы */
.empty-reviews {
    text-align: center;
    padding: 40px 20px;
}

.empty-reviews .empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;
}

.empty-reviews h6 {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-reviews p {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    margin: 0;
    line-height: 1.5;
}

/* ==========================================
   FOOTER С КНОПКАМИ
   ========================================== */
.sheet-footer {
    padding: 12px 16px;
    background: var(--bs-body-bg);
    border-top: 1px solid var(--bs-border-color);
    box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.05);
}

/* Кнопка "В корзину" */
.add-to-cart-btn {
    width: 100%;
    padding: 14px 20px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.add-to-cart-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.add-to-cart-btn:active:not(:disabled) {
    transform: translateY(0);
}

.add-to-cart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.add-to-cart-btn.pulse {
    animation: addPulse 0.6s ease;
}

@keyframes addPulse {
    0% { transform: scale(1); }
    30% { transform: scale(1.03); }
    60% { transform: scale(0.98); }
    100% { transform: scale(1); }
}

.btn-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.btn-content i {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.btn-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
}

.btn-label {
    font-size: 0.75rem;
    opacity: 0.9;
    line-height: 1;
    margin-bottom: 4px;
}

.btn-price {
    font-size: 1.2rem;
    font-weight: 700;
    line-height: 1;
}

/* Счётчик количества */
.quantity-stepper {
    display: flex;
    align-items: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-primary);
    border-radius: 14px;
    overflow: hidden;
}

.stepper-btn {
    width: 56px;
    height: 56px;
    border: none;
    background: transparent;
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.stepper-btn:hover:not(:disabled) {
    background: var(--bs-primary);
    color: white;
}

.stepper-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.stepper-btn.minus {
    border-right: 1px solid var(--bs-border-color);
}

.stepper-btn.plus {
    border-left: 1px solid var(--bs-border-color);
}

.stepper-value {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0 16px;
}

.value-number {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--bs-primary);
    line-height: 1;
}

.value-label {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-top: 4px;
}

/* Нет в наличии */
.out-of-stock-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: 0.95rem;
}

.out-of-stock-footer i {
    font-size: 1rem;
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .product-title {
        font-size: 1.2rem;
    }

    .price-current {
        font-size: 1.5rem;
    }

    .dimensions-grid {
        grid-template-columns: 1fr;
    }

    .thumbnail-btn {
        width: 56px;
        height: 56px;
    }

    .btn-price {
        font-size: 1.1rem;
    }
}
</style>
