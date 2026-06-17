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
                        <img v-lazy="product.image" :alt="product.name">
                        <div v-if="product.badge" class="image-badge">{{ product.badge }}</div>
                    </div>

                    <!-- Информация -->
                    <div class="modal-info">
                        <h2 class="product-title">{{ product.name }}</h2>
                        <div class="product-price-block">
                            <span class="price-current">{{ product.price }} ₽</span>
                            <span v-if="product.oldPrice" class="price-old">{{ product.oldPrice }} ₽</span>
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
                                <p>{{ product.description || 'Свежий товар высокого качества. Готовим из натуральных ингредиентов. Идеально подходит для быстрого перекуса или ужина.' }}</p>
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

                        <!-- Действия -->
                        <div class="modal-actions">
                            <div class="qty-selector">
                                <button @click="decreaseQty" :disabled="modalQty <= 1">−</button>
                                <span>{{ modalQty }}</span>
                                <button @click="increaseQty">+</button>
                            </div>
                            <button class="add-btn" @click="handleAdd">
                                <i class="fa-solid fa-cart-plus"></i>
                                {{ modalQty > 1 ? `Добавить ещё ${modalQty} шт.` : 'В корзину' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>

import {useShopLandingStore} from "@/MobileClient/stores/ShopLanding/shop";
import {useLandingCartStore} from "@/MobileClient/stores/ShopLanding/cart";

export default {
    name: "ShopProductModal",

    props: {
        product: { type: Object, required: true },
        isOpen: { type: Boolean, default: false }
    },

    emits: ['close'],

    data() {
        return {
            activeTab: 'desc',
            modalQty: 1,
            cartStore: useLandingCartStore(),
        };
    },

    computed: {
        mockReviews() {
            return [
                { id: 1, name: 'Анна К.', text: 'Очень вкусно! Буду заказывать ещё.', rating: 5 },
                { id: 2, name: 'Дмитрий П.', text: 'Доставка быстрая, всё горячее.', rating: 4 },
                { id: 3, name: 'Елена С.', text: 'Отличное качество, рекомендую!', rating: 5 },
            ];
        },
    },

    watch: {
        isOpen(newVal) {
            if (newVal) {
                this.activeTab = 'desc';
                this.modalQty = this.cartStore?.getItemQuantity(this.product.id) || 1;
            }
        },
    },

    methods: {
        close() {
            this.$emit('close');
        },
        decreaseQty() {
            if (this.modalQty > 1) this.modalQty--;
        },
        increaseQty() {
            this.modalQty++;
        },
        handleAdd() {
            this.cartStore?.addItem(this.product, this.modalQty);
            this.close();
        },
    },
};
</script>

<style lang="scss" scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.product-modal { background: white; border-radius: 24px; width: 100%; max-width: 850px; max-height: 90vh; overflow: hidden; position: relative; display: flex; flex-direction: column; animation: scaleIn 0.3s ease; }
@keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.modal-close { position: absolute; top: 16px; right: 16px; background: rgba(0,0,0,0.05); border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10; font-size: 1.2rem; transition: 0.2s; }
.modal-close:hover { background: #ef4444; color: white; }

.modal-grid { display: grid; grid-template-columns: 1fr 1fr; height: 100%; overflow: auto; }
@media (max-width: 768px) { .modal-grid { grid-template-columns: 1fr; } }

.modal-image { background: var(--light); display: flex; align-items: center; justify-content: center; padding: 2rem; position: relative; }
.modal-image img { max-width: 100%; max-height: 350px; object-fit: contain; border-radius: 16px; }
.image-badge { position: absolute; top: 24px; left: 24px; background: var(--primary); color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }

.modal-info { padding: 2rem; display: flex; flex-direction: column; }
.product-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 1rem; }
.product-price-block { display: flex; align-items: baseline; gap: 1rem; margin-bottom: 1.5rem; }
.price-current { font-size: 2rem; font-weight: 900; color: var(--primary); }
.price-old { font-size: 1.2rem; text-decoration: line-through; color: var(--gray); }

.tabs { display: flex; gap: 4px; margin-bottom: 1rem; background: var(--light); padding: 4px; border-radius: 12px; }
.tab-btn { flex: 1; padding: 10px; border: none; background: transparent; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.tab-btn.active { background: white; color: var(--dark); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }

.tab-content { flex: 1; overflow-y: auto; margin-bottom: 1.5rem; }
.content-desc p { line-height: 1.6; color: var(--gray); }
.modal-review { background: var(--light); padding: 1rem; border-radius: 12px; margin-bottom: 0.8rem; }
.review-header { display: flex; justify-content: space-between; margin-bottom: 0.4rem; }
.stars i { color: #ddd; margin-left: 2px; }
.stars i.filled { color: #fbbf24; }
.modal-actions { display: flex; gap: 12px; margin-top: auto; }
.qty-selector { display: flex; align-items: center; background: var(--light); border-radius: 12px; padding: 4px; }
.qty-selector button { width: 40px; height: 40px; border: none; background: transparent; font-size: 1.2rem; cursor: pointer; border-radius: 8px; }
.qty-selector button:hover:not(:disabled) { background: white; }
.qty-selector button:disabled { opacity: 0.3; cursor: not-allowed; }
.qty-selector span { width: 30px; text-align: center; font-weight: 700; }
.add-btn { flex: 1; background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s; }
.add-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>
