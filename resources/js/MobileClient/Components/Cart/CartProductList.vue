<template>
    <div class="cart-page pb-5">

        <!-- ========================================== -->
        <!-- ПУСТАЯ КОРЗИНА -->
        <!-- ========================================== -->
        <div v-if="cartProducts.length === 0" class="empty-cart">
            <div class="empty-cart-content">
                <div class="empty-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <h4 class="empty-title">Корзина пуста</h4>
                <p class="empty-text">
                    Добавьте товары из каталога, чтобы оформить заказ
                </p>
                <button class="empty-btn" @click="goToCatalog">
                    <i class="fa-solid fa-store me-2"></i>
                    Перейти в каталог
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОРЗИНА С ТОВАРАМИ -->
        <!-- ========================================== -->
        <template v-else>

            <!-- HERO: Сводка корзины -->
            <div class="cart-hero">
                <div class="hero-background"></div>
                <div class="hero-content">
                    <div class="hero-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <h2 class="hero-title">Ваша корзина</h2>

                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="stat-value">{{ cartTotalCount }}</div>
                            <div class="stat-label">
                                {{ pluralize(cartTotalCount, 'товар', 'товара', 'товаров') }}
                            </div>
                        </div>
                        <div class="hero-divider"></div>
                        <div class="hero-stat">
                            <div class="stat-value">{{ formatPrice(cartTotalPrice) }}</div>
                            <div class="stat-label">Итого</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Верхний текст (слот) -->
            <div v-if="$slots['upper-text']" class="upper-text-wrapper">
                <slot name="upper-text"></slot>
            </div>

            <!-- ========================================== -->
            <!-- СПИСОК ТОВАРОВ -->
            <!-- ========================================== -->
            <div class="products-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-box"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Ваши товары</h6>
                        <p class="section-subtitle">
                            {{ cartTotalCount }} {{ pluralize(cartTotalCount, 'позиция', 'позиции', 'позиций') }} в корзине
                        </p>
                    </div>
                </div>

                <div class="products-list">
                    <div
                        v-for="item in cartProducts"
                        :key="item.product ? 'product-' + item.product.id : 'collection-' + item.collection?.id"
                    >
                        <ProductCardSimple
                            v-if="item.product"
                            :item="item.product"
                            :comment="item.comment"
                            :config="item.params"
                        >
                            <template #partner>
                                <div class="partner-info">
                                    <i class="fa-solid fa-store"></i>
                                    <span>{{ item.partner?.name || tenant?.name || 'Магазин' }}</span>
                                </div>
                            </template>
                        </ProductCardSimple>

                        <CollectionCardSimple
                            v-else-if="item.collection"
                            :item="item.collection"
                            :comment="item.comment"
                            :params="item.params"
                        >
                            <template #partner>
                                <div class="partner-info" v-if="item.partner">
                                    <i class="fa-solid fa-store"></i>
                                    <span>{{ item.partner.name }}</span>
                                </div>
                            </template>
                        </CollectionCardSimple>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ВЫБОР ПРИЗА КОЛЕСА ФОРТУНЫ -->
            <!-- ========================================== -->
            <div
                v-if="settings.need_prizes_from_wheel_of_fortune && filteredActionData.length > 0"
                class="prizes-section"
            >
                <div class="section-header">
                    <div class="section-icon prizes-icon">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Приз Колеса фортуны</h6>
                        <p class="section-subtitle">Выберите бонус к заказу</p>
                    </div>
                </div>

                <Carousel v-bind="carouselConfig" class="prizes-carousel">

                    <!-- "Приз не выбран" -->
                    <Slide :key="'empty-prize'">
                        <div
                            class="prize-card"
                            :class="{ 'is-selected': formData.action_prize == null }"
                            @click="openPrizeModal(null)"
                        >
                            <div class="prize-image-wrapper">
                                <img src="/images/chest.png" alt="Приз не выбран" class="prize-image">
                                <div v-if="formData.action_prize == null" class="selected-badge">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            </div>
                            <div class="prize-info">
                                <div class="prize-title">Без приза</div>
                                <div class="prize-description">Пропустить выбор</div>
                            </div>
                        </div>
                    </Slide>

                    <!-- Доступные призы -->
                    <Slide v-for="slide in filteredActionData" :key="slide.id">
                        <div
                            class="prize-card"
                            :class="{ 'is-selected': formData.action_prize?.prize?.description === slide.description }"
                            @click="openPrizeModal(slide)"
                        >
                            <div class="prize-image-wrapper">
                                <img src="/images/wheel.png" alt="Приз" class="prize-image">
                                <div
                                    v-if="formData.action_prize?.prize?.description === slide.description"
                                    class="selected-badge"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            </div>
                            <div class="prize-info">
                                <div class="prize-title">{{ slide.description || 'Приз' }}</div>
                                <div class="prize-description">
                                    <i class="fa-solid fa-gift me-1"></i>
                                    Бонус к заказу
                                </div>
                            </div>
                        </div>
                    </Slide>

                    <template #addons>
                        <Navigation />
                    </template>
                </Carousel>
            </div>

            <!-- ========================================== -->
            <!-- ИТОГ -->
            <!-- ========================================== -->
            <div v-if="!simpleMode" class="summary-section">
                <div class="summary-card">
                    <div class="summary-row">
                        <span class="summary-label">
                            <i class="fa-solid fa-box me-2"></i>
                            Товаров в корзине
                        </span>
                        <span class="summary-value">{{ cartTotalCount }} шт.</span>
                    </div>

                    <div class="summary-row">
                        <span class="summary-label">
                            <i class="fa-solid fa-tag me-2"></i>
                            Стоимость товаров
                        </span>
                        <span class="summary-value">{{ formatPrice(cartTotalPrice) }}</span>
                    </div>

                    <div v-if="formData.discount > 0" class="summary-row discount-row">
                        <span class="summary-label">
                            <i class="fa-solid fa-percent me-2"></i>
                            Скидка
                        </span>
                        <span class="summary-value discount-value">
                            −{{ formatPrice(formData.discount) }}
                        </span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row total-row">
                        <span class="summary-label total-label">К оплате</span>
                        <span class="summary-value total-value">
                            {{ formatPrice(cartTotalPrice - (formData.discount || 0)) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Слот рекомендаций -->
            <div v-if="$slots['recommendation-list'] && !simpleMode" class="recommendations-section">
                <slot name="recommendation-list"></slot>
            </div>

            <!-- ========================================== -->
            <!-- КНОПКИ ДЕЙСТВИЙ -->
            <!-- ========================================== -->
            <div v-if="!simpleMode" class="actions-section">
                <button class="clear-btn" @click="clearCart">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Очистить корзину</span>
                </button>

                <button class="checkout-btn" @click="$emit('change-tab', 1)">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Перейти к оформлению</span>
                    <i class="fa-solid fa-arrow-right arrow"></i>
                </button>
            </div>

        </template>

        <!-- ========================================== -->
        <!-- МОДАЛКА ВЫБОРА ПРИЗА -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            id="takePrizeModal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content prize-modal">
                    <div class="modal-header">
                        <div class="modal-icon prize-modal-icon">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title">Использование приза</h5>
                            <small class="text-muted">Применить к текущему заказу?</small>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">

                        <!-- Информация о призе -->
                        <div v-if="selectedPrize" class="prize-preview">
                            <div class="preview-image">
                                <img src="/images/wheel.png" alt="Приз">
                            </div>
                            <div class="preview-info">
                                <div class="preview-title">{{ selectedPrize.description }}</div>
                                <div class="preview-subtitle">Приз Колеса фортуны</div>
                            </div>
                        </div>

                        <!-- Информационный блок -->
                        <div class="info-banner">
                            <i class="fa-solid fa-circle-info"></i>
                            <div class="info-text">
                                <strong>Внимание!</strong> Призы со скидкой будут применены к текущей корзине.
                                Если товара по скидке нет, он будет добавлен в корзину и к нему применена скидка.
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn-cancel"
                            data-bs-dismiss="modal"
                        >
                            Отмена
                        </button>
                        <button
                            type="button"
                            class="btn-confirm"
                            @click="selectPrize"
                            :disabled="isSelectingPrize"
                        >
                            <span v-if="isSelectingPrize" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="fa-solid fa-check me-2"></i>
                            {{ isSelectingPrize ? 'Применяем...' : 'Использовать' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import 'vue3-carousel/dist/carousel.css';
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel';
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';
import ProductCardSimple from '@/MobileClient/Components/Shop/ProductCardSimple.vue';
import CollectionCardSimple from '@/MobileClient/Components/Shop/CollectionCardSimple.vue';

export default {
    name: "CartProductList",

    components: {
        Carousel,
        Slide,
        Pagination,
        Navigation,
        ProductCardSimple,
        CollectionCardSimple,
    },

    props: {
        formData: {
            type: Object,
            required: true,
        },
        simpleMode: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['change-tab', 'select-prize'],

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            selectedPrize: null,
            isSelectingPrize: false,
            prizeModal: null,
            action: null,
            carouselConfig: {
                itemsToShow: 1.2,
                wrapAround: false,
                snapAlign: 'start',
            },
        };
    },

    computed: {
        cartProducts() {
            return this.basketStore.cartProducts || [];
        },
        cartTotalCount() {
            return this.basketStore.cartTotalCount || 0;
        },
        cartTotalPrice() {
            return this.basketStore.cartTotalPrice || 0;
        },
        tenant() {
            return window.Tenant || null;
        },
        settings() {
            return this.tenant?.settings || {};
        },
        filteredActionData() {
            if (!this.action?.data) return [];
            return this.action.data.filter(item => !item.taked_at);
        },
    },

    mounted() {
        this.$nextTick(() => {
            if (typeof bootstrap !== 'undefined') {
                const modalEl = document.getElementById('takePrizeModal');
                if (modalEl) {
                    this.prizeModal = new bootstrap.Modal(modalEl);
                }
            }
        });
        this.loadClientNotUsedPrizes();
    },

    beforeUnmount() {
        if (this.prizeModal) {
            this.prizeModal.dispose();
        }
    },

    methods: {
        goToCatalog() {
            this.$router.push({ name: 'Catalog' });
        },

        openPrizeModal(item) {
            this.selectedPrize = item;
            if (this.selectedPrize != null) {
                this.prizeModal?.show();
            }
        },

        async selectPrize() {
            const item = this.selectedPrize;
            if (item == null) {
                this.prizeModal?.hide();
                return;
            }

            this.isSelectingPrize = true;

            try {
                const resp = await this.basketStore.useWheelOfFortunePrize({
                    form: {
                        action_prize: item,
                        action_id: this.action.id,
                    },
                });

                this.action = resp.action;

                this.$notify?.({
                    title: 'Выбор приза',
                    text: 'Приз успешно применён!',
                    type: 'success',
                });

                this.$emit('select-prize', {
                    prize: item,
                    action_id: this.action.id,
                });

                this.selectedPrize = null;
                this.prizeModal?.hide();

                await this.loadClientNotUsedPrizes();

            } catch (error) {
                console.error('Ошибка выбора приза:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось применить приз',
                    type: 'error',
                });
            } finally {
                this.isSelectingPrize = false;
            }
        },

        async loadClientNotUsedPrizes() {
            if (!this.settings.selected_script_id) return;

            try {
                const resp = await this.basketStore.getClientNotUsedPrizes({
                    slug_id: this.settings.selected_script_id,
                });
                this.action = resp.action;
            } catch (error) {
                console.error('Ошибка загрузки призов:', error);
            }
        },

        async clearCart() {
            if (!confirm('Очистить корзину? Все товары будут удалены.')) return;

            try {
                await this.basketStore.clearCart();
                this.$notify?.({
                    title: 'Корзина',
                    text: 'Корзина успешно очищена',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка очистки:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось очистить корзину',
                    type: 'error',
                });
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
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
.cart-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
.empty-cart {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.empty-cart-content {
    text-align: center;
    max-width: 320px;
    animation: fadeInUp 0.5s ease-out;
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

.empty-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 24px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.empty-title {
    font-weight: 700;
    font-size: 1.4rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.95rem;
    color: var(--bs-secondary-color);
    margin-bottom: 28px;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 32px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

/* ==========================================
   HERO: СВОДКА КОРЗИНЫ
   ========================================== */
.cart-hero {
    position: relative;
    padding: 32px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.hero-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.hero-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    max-width: 320px;
    margin: 0 auto;
}

.hero-stat {
    flex: 1;
    text-align: center;
}

.stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.75rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.hero-divider {
    width: 1px;
    height: 36px;
    background: rgba(255, 255, 255, 0.3);
}

/* ==========================================
   ВЕРХНИЙ ТЕКСТ (СЛОТ)
   ========================================== */
.upper-text-wrapper {
    padding: 16px;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.products-section,
.prizes-section,
.summary-section,
.actions-section,
.recommendations-section {
    padding: 20px 16px;
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.section-icon.prizes-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   СПИСОК ТОВАРОВ
   ========================================== */
.products-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.partner-info {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.partner-info i {
    font-size: 0.7rem;
}

/* ==========================================
   КАРУСЕЛЬ ПРИЗОВ
   ========================================== */
.prizes-carousel {
    margin: 0 -4px;
}

.prize-card {
    margin: 4px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    height: 200px;
    display: flex;
    flex-direction: column;
}

.prize-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.15);
}

.prize-card.is-selected {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, transparent 100%);
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.2);
}

.prize-image-wrapper {
    position: relative;
    width: 100%;
    height: 120px;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.1) 0%, rgba(var(--bs-primary-rgb), 0.05) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.prize-image {
    width: 80px;
    height: 80px;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.prize-card:hover .prize-image {
    transform: scale(1.1) rotate(5deg);
}

.selected-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.4);
    animation: badgePop 0.3s ease;
}

@keyframes badgePop {
    0% { transform: scale(0); }
    70% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.prize-info {
    padding: 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.prize-title {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.prize-description {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ИТОГ
   ========================================== */
.summary-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
}

.summary-label {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
}

.summary-label i {
    color: var(--bs-primary);
    width: 16px;
}

.summary-value {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.discount-row .summary-value {
    color: #198754;
}

.summary-divider {
    height: 1px;
    background: var(--bs-border-color);
    margin: 8px 0;
}

.total-row {
    padding-top: 14px;
}

.total-label {
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-body-color);
}

.total-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--bs-primary);
}

/* ==========================================
   КНОПКИ ДЕЙСТВИЙ
   ========================================== */
.actions-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.clear-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: transparent;
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.clear-btn:hover {
    border-color: #dc3545;
    color: #dc3545;
    background: rgba(220, 53, 69, 0.05);
}

.checkout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.checkout-btn .arrow {
    transition: transform 0.2s ease;
}

.checkout-btn:hover .arrow {
    transform: translateX(4px);
}

/* ==========================================
   МОДАЛКА ПРИЗА
   ========================================== */
.prize-modal {
    border-radius: 20px;
    border: none;
    overflow: hidden;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
    display: flex;
    align-items: center;
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

.prize-modal-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
}

.modal-title {
    font-weight: 700;
    margin-bottom: 2px;
    color: var(--bs-body-color);
}

.modal-body {
    padding: 20px;
}

.prize-preview {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-radius: 12px;
    margin-bottom: 16px;
}

.preview-image {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.1) 0%, rgba(var(--bs-primary-rgb), 0.05) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.preview-image img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.preview-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.preview-subtitle {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.info-banner {
    display: flex;
    gap: 12px;
    padding: 14px;
    background: rgba(13, 110, 253, 0.05);
    border: 1px solid rgba(13, 110, 253, 0.15);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-size: 0.85rem;
    line-height: 1.5;
}

.info-banner i {
    color: #0d6efd;
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.info-text strong {
    color: var(--bs-primary);
}

.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--bs-border-color);
    display: flex;
    gap: 10px;
}

.btn-cancel,
.btn-confirm {
    flex: 1;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-cancel {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
}

.btn-cancel:hover {
    background: var(--bs-border-color);
}

.btn-confirm {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.btn-confirm:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(var(--bs-primary-rgb), 0.4);
}

.btn-confirm:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.3rem;
    }

    .stat-value {
        font-size: 1.2rem;
    }

    .prize-card {
        height: 180px;
    }

    .prize-image-wrapper {
        height: 100px;
    }

    .checkout-btn {
        font-size: 0.95rem;
        padding: 14px 20px;
    }
}
</style>
