<template>
    <teleport to="body">
        <transition name="modal-fade">
            <div v-if="isOpen" class="modal-overlay" @click.self="close">
                <div class="product-modal">
                    <button class="modal-close" @click="close" aria-label="Закрыть">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <div class="modal-grid">
                        <!-- Изображение -->
                        <div class="modal-image">
                            <img
                                :src="product.image || (product.images && product.images[0]) || 'https://via.placeholder.com/400x400?text=Нет+фото'"
                                :alt="product.name"
                                loading="lazy"
                            >
                            <div v-if="product.badge" class="image-badge">{{ product.badge }}</div>
                        </div>

                        <!-- Информация -->
                        <div class="modal-info">
                            <h2 class="product-title">{{ product.name }}</h2>

                            <div class="product-price-block">
                                <span class="price-current">{{ formatPrice(product.price) }} ₽</span>
                                <span v-if="product.oldPrice" class="price-old">{{ formatPrice(product.oldPrice) }} ₽</span>
                            </div>

                            <!-- Табы -->
                            <div class="tabs">
                                <button
                                    class="tab-btn"
                                    :class="{ active: activeTab === 'desc' }"
                                    @click="activeTab = 'desc'"
                                >Описание</button>
                                <button
                                    class="tab-btn"
                                    :class="{ active: activeTab === 'reviews' }"
                                    @click="activeTab = 'reviews'"
                                >Отзывы ({{ realReviews.length }})</button>
                            </div>

                            <div class="tab-content">
                                <div v-if="activeTab === 'desc'" class="content-desc">
                                    <p>{{ product.description || 'Свежий товар высокого качества. Готовим из натуральных ингредиентов.' }}</p>
                                </div>

                                <div v-else class="content-reviews">
                                    <div v-if="realReviews.length === 0" class="no-reviews">
                                        <i class="fa-regular fa-comment-dots"></i>
                                        <p>Пока нет отзывов. Будьте первым!</p>
                                    </div>
                                    <div v-else class="review-list">
                                        <div v-for="rev in realReviews" :key="rev.id" class="modal-review">
                                            <div class="review-header">
                                                <div class="reviewer-info">
                                                    <strong class="reviewer-name">{{ rev.tenant_user?.name || 'Гость' }}</strong>
                                                    <span v-if="rev.title" class="review-title">{{ rev.title }}</span>
                                                </div>
                                                <div class="stars">
                                                    <i v-for="s in 5" :key="s" class="fa-solid fa-star" :class="{ filled: s <= rev.rating }"></i>
                                                </div>
                                            </div>

                                            <p class="review-text">{{ rev.text }}</p>

                                            <!-- Ответ администратора -->
                                            <div v-if="rev.admin_response" class="admin-response">
                                                <div class="response-header">
                                                    <i class="fa-solid fa-store"></i>
                                                    <span>Ответ заведения</span>
                                                </div>
                                                <p>{{ rev.admin_response }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Действия: Полностью реактивные через useBasket -->
                            <div class="modal-actions">
                                <template v-if="currentQty > 0">
                                    <div class="qty-selector">
                                        <button
                                            @click="decreaseQty"
                                            :disabled="basket.isProductLoading(productId)"
                                            aria-label="Уменьшить количество"
                                        >
                                            <i v-if="basket.isProductLoading(productId)" class="fa-solid fa-spinner fa-spin"></i>
                                            <span v-else>−</span>
                                        </button>
                                        <span>{{ currentQty }}</span>
                                        <button
                                            @click="increaseQty"
                                            :disabled="basket.isProductLoading(productId)"
                                            aria-label="Увеличить количество"
                                        >
                                            <i v-if="basket.isProductLoading(productId)" class="fa-solid fa-spinner fa-spin"></i>
                                            <span v-else>+</span>
                                        </button>
                                    </div>
                                </template>

                                <button
                                    v-else
                                    class="add-btn"
                                    @click="handleAdd"
                                    :disabled="basket.isProductLoading(productId)"
                                >
                                    <i v-if="basket.isProductLoading(productId)" class="fa-solid fa-spinner fa-spin me-2"></i>
                                    <i v-else class="fa-solid fa-cart-plus me-2"></i>
                                    В корзину
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script>
import { useBasket } from "@/MobileClient/composables/useBasket";

export default {
    name: "ShopProductModal",

    props: {
        product: { type: Object, required: true },
        isOpen: { type: Boolean, default: false }
    },

    emits: ['close'],

    setup() {
        return {
            basket: useBasket()
        };
    },

    data() {
        return {
            activeTab: 'desc',
        };
    },

    computed: {
        productId() {
            return this.product.id || this.product.product_id;
        },

        currentQty() {
            const item = this.basket.getItemById(this.productId);
            return item ? (item.count || item.quantity || 0) : 0;
        },

        // 🆕 Получаем реальные одобренные отзывы из модели Product
        realReviews() {
            // Поскольку вы добавили 'reviews' в $with, фильтруем только одобренные (status === 1)
            // и сортируем по дате создания (новые сверху)
            const reviews = this.product.reviews || [];
            return reviews
                .filter(rev => rev.status === 1) // 1 = STATUS_APPROVED из модели Review
                .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        },
    },

    watch: {
        isOpen(newVal) {
            if (newVal) {
                this.activeTab = 'desc';
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },
    },

    beforeUnmount() {
        document.body.style.overflow = '';
    },

    methods: {
        close() {
            this.$emit('close');
        },

        formatPrice(value) {
            if (!value && value !== 0) return '0';
            return Number(value).toLocaleString('ru-RU');
        },

        increaseQty() {
            this.basket.incrementQuantity(this.productId);
        },

        decreaseQty() {
            this.basket.decrementQuantity(this.productId);
        },

        handleAdd() {
            this.basket.addProduct(this.productId);
        },
    },
};
</script>

<style lang="scss" scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.product-modal {
    background: white;
    border-radius: 24px;
    width: 100%;
    max-width: 900px; /* Чуть шире для комфорта */
    /* 🆕 Фиксируем высоту: минимальная высота большая, максимальная не дает выйти за экран */
    max-height: 90vh;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    animation: scaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(0, 0, 0, 0.05);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 10;
    font-size: 1.2rem;
    color: var(--dark, #222);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
        background: #ef4444;
        color: white;
        transform: rotate(90deg);
    }
}

.modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    height: 100%;
    overflow: hidden;
}

@media (max-width: 768px) {
    .modal-grid {
        grid-template-columns: 1fr;
        overflow-y: auto;
    }

    .modal-overlay {
        padding: 0;
        align-items: flex-end;
    }

    .product-modal {
        min-height: 85vh; /* На мобильных занимаем почти весь экран */
        max-height: 95vh;
        border-radius: 24px 24px 0 0;
        animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUp {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
}

.modal-image {
    background: var(--light, #f8f9fa);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
}

.modal-image img {
    max-width: 100%;
    max-height: 400px; /* Чуть больше места для фото */
    object-fit: contain;
    border-radius: 16px;
    transition: transform 0.3s ease;

    &:hover {
        transform: scale(1.05);
    }
}

.image-badge {
    position: absolute;
    top: 24px;
    left: 24px;
    background: var(--primary, #ff7a00);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.modal-info {
    padding: 2rem;
    display: flex;
    flex-direction: column;
    height: 100%; /* Занимает всю высоту ячейки грида */
}

.product-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 1rem;
    color: var(--dark, #222);
    line-height: 1.2;
}

.product-price-block {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.price-current {
    font-size: 2rem;
    font-weight: 900;
    color: var(--primary, #ff7a00);
}

.price-old {
    font-size: 1.2rem;
    text-decoration: line-through;
    color: var(--gray, #888);
}

.tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 1rem;
    background: var(--light, #f8f9fa);
    padding: 4px;
    border-radius: 12px;
    flex-shrink: 0; /* Не сжимать табы */
}

.tab-btn {
    flex: 1;
    padding: 10px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--gray, #888);

    &:hover:not(.active) {
        color: var(--dark, #222);
    }

    &.active {
        background: white;
        color: var(--dark, #222);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
}

/* 🆕 Ключевое изменение для скролла отзывов */
/* 🆕 ИСПРАВЛЕНИЕ: Позволяем контенту растягиваться */
.tab-content {
    flex: 1; /* Заставляет этот блок занимать ВСЁ доступное свободное пространство */
    overflow-y: auto; /* Скролл появится только если текст/отзывы реально не влезут */
    /* УБРАЛИ max-height: 350px, чтобы блок мог расти вместе с модалкой */
    margin-bottom: 1.5rem;
    padding-right: 8px; /* Место для скроллбара */

    /* Кастомный скроллбар для красоты */
    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-track {
        background: var(--light, #f8f9fa);
        border-radius: 3px;
    }
    &::-webkit-scrollbar-thumb {
        background: var(--gray, #ccc);
        border-radius: 3px;
        &:hover { background: var(--primary, #ff7a00); }
    }
}

/* 🆕 ИСПРАВЛЕНИЕ: Кнопки теперь корректно прижимаются к низу,
   а блок выше заполняет всё пространство между ними */
.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: auto; /* Прижимает кнопки к самому низу .modal-info */
    padding-top: 1.5rem;
    border-top: 1px solid var(--light, #f8f9fa);
    flex-shrink: 0; /* Запрещаем кнопкам сжиматься */
}

.content-desc p {
    line-height: 1.6;
    color: var(--gray, #6c757d);
    font-size: 0.95rem;
}

.no-reviews {
    text-align: center;
    color: var(--gray, #888);
    padding: 3rem 0;

    i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        display: block;
        opacity: 0.5;
    }
}

.review-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.modal-review {
    background: var(--light, #f8f9fa);
    padding: 1rem;
    border-radius: 12px;
    border-left: 3px solid var(--primary, #ff7a00);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.6rem;
}

.reviewer-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.reviewer-name {
    font-size: 0.95rem;
    color: var(--dark, #222);
}

.review-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary, #ff7a00);
}

.stars i {
    color: #e5e7eb;
    margin-left: 2px;
    font-size: 0.85rem;

    &.filled {
        color: #fbbf24;
    }
}

.review-text {
    font-size: 0.9rem;
    color: var(--gray, #6c757d);
    margin: 0 0 0.8rem 0;
    line-height: 1.5;
}

/* 🆕 Стили для ответа администратора */
.admin-response {
    background: rgba(255, 255, 255, 0.6);
    border-radius: 8px;
    padding: 0.8rem;
    margin-top: 0.5rem;

    .response-header {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--primary, #ff7a00);
        margin-bottom: 4px;
    }

    p {
        font-size: 0.85rem;
        color: var(--dark, #222);
        margin: 0;
        line-height: 1.4;
    }
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: auto; /* Прижимает кнопки к низу */
    padding-top: 1rem;
    border-top: 1px solid var(--light, #f8f9fa);
    flex-shrink: 0; /* Не сжимать блок кнопок */
}

.qty-selector {
    display: flex;
    align-items: center;
    background: var(--light, #f8f9fa);
    border-radius: 12px;
    padding: 4px;
    flex: 1;
}

.qty-selector button {
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    font-size: 1.2rem;
    cursor: pointer;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--dark, #222);
    transition: background 0.2s;

    &:hover:not(:disabled) {
        background: white;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.qty-selector span {
    flex: 1;
    text-align: center;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--dark, #222);
}

.add-btn {
    flex: 1;
    background: var(--primary, #ff7a00);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb, 255, 122, 0), 0.3);

    &:hover:not(:disabled) {
        background: var(--primary-dark, #e56f00);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(var(--primary-rgb, 255, 122, 0), 0.4);
    }

    &:active:not(:disabled) {
        transform: translateY(0);
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }
}

.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}
</style>
