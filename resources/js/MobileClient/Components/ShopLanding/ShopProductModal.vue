<template>
    <transition name="modal-fade">
        <div v-if="isOpen" class="modal-overlay" @click.self="close">
            <div class="product-modal">
                <button class="modal-close" @click="close">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="modal-grid">
                    <!-- Изображение -->
                    <div class="modal-image">
                        <!-- 🆕 Надежное получение изображения: строка или первый элемент массива -->
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
                            >Отзывы ({{ mockReviews.length }})</button>
                        </div>

                        <div class="tab-content">
                            <div v-if="activeTab === 'desc'" class="content-desc">
                                <p>{{ product.description || 'Свежий товар высокого качества. Готовим из натуральных ингредиентов.' }}</p>
                            </div>

                            <div v-else class="content-reviews">
                                <div v-if="mockReviews.length === 0" class="no-reviews">Пока нет отзывов</div>
                                <div v-else class="review-list">
                                    <div v-for="rev in mockReviews" :key="rev.id" class="modal-review">
                                        <div class="review-header">
                                            <strong>{{ rev.name }}</strong>
                                            <div class="stars">
                                                <i v-for="s in 5" :key="s" class="fa-solid fa-star" :class="{ filled: s <= rev.rating }"></i>
                                            </div>
                                        </div>
                                        <p class="review-text">{{ rev.text }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 🆕 Действия: Полностью реактивные через useBasket -->
                        <div class="modal-actions">
                            <!-- Если товар уже в корзине, показываем селектор количества -->
                            <template v-if="currentQty > 0">
                                <div class="qty-selector">
                                    <button
                                        @click="decreaseQty"
                                        :disabled="basket.isProductLoading(productId)"
                                    >
                                        <i v-if="basket.isProductLoading(productId)" class="fa-solid fa-spinner fa-spin"></i>
                                        <span v-else>−</span>
                                    </button>

                                    <span>{{ currentQty }}</span>

                                    <button
                                        @click="increaseQty"
                                        :disabled="basket.isProductLoading(productId)"
                                    >
                                        <i v-if="basket.isProductLoading(productId)" class="fa-solid fa-spinner fa-spin"></i>
                                        <span v-else>+</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Если товара нет в корзине, показываем кнопку добавления -->
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
        // 🆕 Надежное определение ID товара
        productId() {
            return this.product.id || this.product.product_id;
        },

        // 🆕 Реактивное получение количества из корзины (учитываем поле count или quantity)
        currentQty() {
            const item = this.basket.getItemById(this.productId);
            return item ? (item.count || item.quantity || 0) : 0;
        },

        mockReviews() {
            return [
                { id: 1, name: 'Анна К.', text: 'Очень вкусно! Буду заказывать ещё.', rating: 5 },
                { id: 2, name: 'Дмитрий П.', text: 'Доставка быстрая, всё горячее.', rating: 4 },
                { id: 3, name: 'Елена С.', text: 'Отличное качество, рекомендую!', rating: 5 },
            ];
        },
    },

    watch: {
        // Сбрасываем таб на описание при каждом открытии
        isOpen(newVal) {
            if (newVal) {
                this.activeTab = 'desc';
            }
        },
    },

    methods: {
        close() {
            this.$emit('close');
        },

        formatPrice(value) {
            if (!value && value !== 0) return '0';
            return Number(value).toLocaleString('ru-RU');
        },

        // 🆕 Прямой вызов методов useBasket
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
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
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
    max-width: 850px;
    max-height: 90vh;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(0,0,0,0.05);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 10;
    font-size: 1.2rem;
    transition: 0.2s;
}

.modal-close:hover {
    background: #ef4444;
    color: white;
}

.modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    height: 100%;
    overflow: auto;
}

@media (max-width: 768px) {
    .modal-grid { grid-template-columns: 1fr; }
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
    max-height: 350px;
    object-fit: contain;
    border-radius: 16px;
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
}

.modal-info {
    padding: 2rem;
    display: flex;
    flex-direction: column;
}

.product-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 1rem;
    color: var(--dark, #222);
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
}

.tab-btn {
    flex: 1;
    padding: 10px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    color: var(--gray, #888);
}

.tab-btn.active {
    background: white;
    color: var(--dark, #222);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.tab-content {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 1.5rem;
}

.content-desc p {
    line-height: 1.6;
    color: var(--gray, #6c757d);
}

.modal-review {
    background: var(--light, #f8f9fa);
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 0.8rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.4rem;
}

.stars i {
    color: #ddd;
    margin-left: 2px;
}

.stars i.filled {
    color: #fbbf24;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: auto;
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
}

.qty-selector button:hover:not(:disabled) {
    background: white;
}

.qty-selector button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.qty-selector span {
    flex: 1;
    text-align: center;
    font-weight: 700;
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
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: 0.3s;
}

.add-btn:hover:not(:disabled) {
    background: var(--primary-dark, #e56f00);
    transform: translateY(-2px);
}

.add-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}
</style>
