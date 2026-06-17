<template>
    <div class="cashback-shop">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ С БАЛАНСОМ -->
        <!-- ========================================== -->
        <div class="shop-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">✨</div>
                    <div class="hero-sparkle sparkle-2">🎁</div>
                </div>
                <h1 class="hero-title">Магазин бонусов</h1>
                <p class="hero-subtitle">Обменяйте бонусы на мерч или помогите нуждающимся</p>

                <!-- Баланс бонусов -->
                <div class="balance-card">
                    <div class="balance-icon">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="balance-info">
                        <div class="balance-value">{{ formatNumber(userBalance) }}</div>
                        <div class="balance-label">Ваших бонусов</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="shop-content">

            <!-- ========================================== -->
            <!-- ТАБЫ -->
            <!-- ========================================== -->
            <div class="tabs-wrapper">
                <div class="tabs">
                    <button
                        class="tab"
                        :class="{ 'is-active': activeTab === 'merch' }"
                        @click="activeTab = 'merch'"
                    >
                        <i class="fa-solid fa-shirt"></i>
                        <span>Мерч</span>
                        <span v-if="merchCart.length > 0" class="tab-badge">{{ merchCart.length }}</span>
                    </button>
                    <button
                        class="tab charity"
                        :class="{ 'is-active': activeTab === 'charity' }"
                        @click="activeTab = 'charity'"
                    >
                        <i class="fa-solid fa-hand-holding-heart"></i>
                        <span>Благотворительность</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ВКЛАДКА: МЕРЧ -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'merch'" class="merch-section">

                <!-- Пустое состояние -->
                <div v-if="merchProducts.length === 0" class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h3>Товары скоро появятся</h3>
                    <p>Следите за обновлениями — мы добавляем новый мерч регулярно</p>
                </div>

                <!-- Сетка товаров -->
                <div v-else class="products-grid">
                    <div
                        v-for="product in merchProducts"
                        :key="product.id"
                        class="product-card"
                        :class="{
                            'is-unavailable': product.stock === 0,
                            'is-in-cart': isInCart(product.id)
                        }"
                        @click="openProductModal(product)"
                    >
                        <!-- Изображение -->
                        <div class="card-image">
                            <img
                                v-lazy="product.image || '/images/default-product-image.jpg'"
                                :alt="product.title"
                            >
                            <div v-if="product.stock === 0" class="out-of-stock-badge">
                                <i class="fa-solid fa-ban"></i>
                                <span>Нет в наличии</span>
                            </div>
                            <div v-if="product.is_new" class="new-badge">NEW</div>
                            <div v-if="product.is_hit" class="hit-badge">ХИТ</div>
                        </div>

                        <!-- Информация -->
                        <div class="card-info">
                            <h3 class="card-title">{{ product.title }}</h3>
                            <p class="card-description">{{ product.description }}</p>

                            <div class="card-footer">
                                <div class="card-price">
                                    <i class="fa-solid fa-coins"></i>
                                    <span>{{ formatNumber(product.price) }}</span>
                                    <span class="price-unit">бонусов</span>
                                </div>
                                <div v-if="product.stock > 0" class="card-stock">
                                    Осталось: {{ product.stock }}
                                </div>
                            </div>
                        </div>

                        <!-- Кнопка добавления -->
                        <div class="card-action">
                            <button
                                v-if="isInCart(product.id)"
                                class="btn-added"
                                @click.stop="removeFromCart(product.id)"
                            >
                                <i class="fa-solid fa-check"></i>
                                <span>В корзине</span>
                            </button>
                            <button
                                v-else
                                class="btn-add"
                                :disabled="product.stock === 0"
                                @click.stop="addToCart(product)"
                            >
                                <i class="fa-solid fa-cart-plus"></i>
                                <span>В корзину</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- ВКЛАДКА: БЛАГОТВОРИТЕЛЬНОСТЬ -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'charity'" class="charity-section">

                <div class="charity-intro">
                    <div class="intro-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <div class="intro-info">
                        <h3>Помогите тем, кто нуждается</h3>
                        <p>Ваши бонусы превратятся в реальную помощь. Выберите акцию и сумму пожертвования.</p>
                    </div>
                </div>

                <!-- Список акций -->
                <div class="charity-list">
                    <div
                        v-for="campaign in charityCampaigns"
                        :key="campaign.id"
                        class="charity-card"
                    >
                        <div class="charity-image">
                            <img v-lazy="campaign.image || '/images/default-charity.jpg'" :alt="campaign.title">
                            <div class="charity-progress">
                                <div class="progress-bar">
                                    <div
                                        class="progress-fill"
                                        :style="{ width: getProgressPercent(campaign) + '%' }"
                                    ></div>
                                </div>
                                <div class="progress-info">
                                    <span>Собрано: {{ formatNumber(campaign.collected) }}</span>
                                    <span>Цель: {{ formatNumber(campaign.goal) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="charity-info">
                            <h3 class="charity-title">{{ campaign.title }}</h3>
                            <p class="charity-description">{{ campaign.description }}</p>

                            <div class="charity-meta">
                                <div class="meta-item">
                                    <i class="fa-solid fa-users"></i>
                                    <span>{{ campaign.donors_count || 0 }} помогли</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>до {{ campaign.end_date }}</span>
                                </div>
                            </div>

                            <!-- Выбор суммы пожертвования -->
                            <div class="donation-amounts">
                                <label class="donation-label">Выберите сумму пожертвования:</label>
                                <div class="amount-buttons">
                                    <button
                                        v-for="amount in campaign.amounts"
                                        :key="amount"
                                        type="button"
                                        class="amount-btn"
                                        :class="{ 'is-selected': selectedDonation[campaign.id] === amount }"
                                        :disabled="amount > userBalance"
                                        @click="selectDonation(campaign.id, amount)"
                                    >
                                        {{ formatNumber(amount) }}
                                    </button>
                                    <button
                                        type="button"
                                        class="amount-btn custom"
                                        :class="{ 'is-selected': selectedDonation[campaign.id] === 'custom' }"
                                        @click="selectCustomDonation(campaign.id)"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </div>

                                <!-- Поле для своей суммы -->
                                <transition name="collapse">
                                    <div v-if="selectedDonation[campaign.id] === 'custom'" class="custom-amount">
                                        <div class="input-with-suffix">
                                            <input
                                                type="number"
                                                min="1"
                                                :max="userBalance"
                                                v-model.number="customAmounts[campaign.id]"
                                                class="amount-input"
                                                placeholder="Введите сумму"
                                            >
                                            <span class="input-suffix">бонусов</span>
                                        </div>
                                        <span v-if="customAmounts[campaign.id] > userBalance" class="amount-error">
                                            Недостаточно бонусов
                                        </span>
                                    </div>
                                </transition>
                            </div>

                            <!-- Кнопка пожертвования -->
                            <button
                                class="donate-btn"
                                :class="{ 'is-disabled': !canDonate(campaign) }"
                                :disabled="!canDonate(campaign) || isDonating === campaign.id"
                                @click="donate(campaign)"
                            >
                                <span v-if="isDonating === campaign.id" class="btn-spinner"></span>
                                <template v-else>
                                    <i class="fa-solid fa-hand-holding-heart"></i>
                                    <span>Пожертвовать {{ getDonationAmount(campaign) ? formatNumber(getDonationAmount(campaign)) + ' бонусов' : '' }}</span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================== -->
        <!-- НИЖНЯЯ ПАНЕЛЬ (для мерча) -->
        <!-- ========================================== -->
        <transition name="slide-up">
            <div v-if="activeTab === 'merch' && merchCart.length > 0" class="bottom-panel">
                <div class="panel-info">
                    <div class="panel-label">Итого к обмену</div>
                    <div class="panel-price">
                        <i class="fa-solid fa-coins"></i>
                        <span>{{ formatNumber(cartTotal) }}</span>
                        <span class="price-unit">бонусов</span>
                    </div>
                    <div class="panel-hint" :class="{ 'is-warning': cartTotal > userBalance }">
                        <template v-if="cartTotal > userBalance">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Не хватает {{ formatNumber(cartTotal - userBalance) }} бонусов
                        </template>
                        <template v-else>
                            {{ merchCart.length }} {{ pluralize(merchCart.length, 'товар', 'товара', 'товаров') }} в корзине
                        </template>
                    </div>
                </div>

                <button
                    type="button"
                    class="checkout-btn"
                    :class="{ 'is-disabled': cartTotal > userBalance }"
                    :disabled="cartTotal > userBalance || isCheckingOut"
                    @click="checkout"
                >
                    <span v-if="isCheckingOut" class="btn-spinner"></span>
                    <template v-else>
                        <i class="fa-solid fa-check"></i>
                        <span>Обменять бонусы</span>
                    </template>
                </button>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ТОВАР -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showProductModal" class="modal-overlay" @click.self="closeProductModal">
                <div class="modal-container product-modal">

                    <button class="modal-close" @click="closeProductModal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <div v-if="selectedProduct" class="modal-body">
                        <div class="product-gallery">
                            <img
                                v-lazy="selectedProduct.image || '/images/default-product-image.jpg'"
                                :alt="selectedProduct.title"
                            >
                            <div v-if="selectedProduct.is_new" class="new-badge large">NEW</div>
                        </div>

                        <div class="product-details">
                            <h2 class="product-title">{{ selectedProduct.title }}</h2>

                            <div class="product-price-large">
                                <i class="fa-solid fa-coins"></i>
                                <span>{{ formatNumber(selectedProduct.price) }}</span>
                                <span class="price-unit">бонусов</span>
                            </div>

                            <p class="product-description-full">{{ selectedProduct.description }}</p>

                            <div v-if="selectedProduct.details" class="product-specs">
                                <h4>Характеристики</h4>
                                <ul>
                                    <li v-for="(value, key) in selectedProduct.details" :key="key">
                                        <strong>{{ key }}:</strong> {{ value }}
                                    </li>
                                </ul>
                            </div>

                            <div v-if="selectedProduct.stock > 0" class="product-stock-info">
                                <i class="fa-solid fa-box"></i>
                                <span>В наличии: {{ selectedProduct.stock }} шт.</span>
                            </div>

                            <button
                                class="modal-add-btn"
                                :class="{ 'is-in-cart': isInCart(selectedProduct.id) }"
                                :disabled="selectedProduct.stock === 0"
                                @click="toggleCartFromModal(selectedProduct)"
                            >
                                <template v-if="isInCart(selectedProduct.id)">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Убрать из корзины</span>
                                </template>
                                <template v-else>
                                    <i class="fa-solid fa-cart-plus"></i>
                                    <span>Добавить в корзину</span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОДТВЕРЖДЕНИЕ ЗАКАЗА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showCheckoutModal" class="modal-overlay" @click.self="showCheckoutModal = false">
                <div class="modal-container checkout-modal">

                    <div class="checkout-header">
                        <div class="checkout-icon">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <h3>Подтверждение обмена</h3>
                    </div>

                    <div class="checkout-body">
                        <div class="checkout-items">
                            <div
                                v-for="item in merchCart"
                                :key="item.id"
                                class="checkout-item"
                            >
                                <img v-lazy="item.image || '/images/default-product-image.jpg'" :alt="item.title">
                                <div class="checkout-item-info">
                                    <div class="checkout-item-title">{{ item.title }}</div>
                                    <div class="checkout-item-price">{{ formatNumber(item.price) }} бонусов</div>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-summary">
                            <div class="summary-row">
                                <span>Товаров:</span>
                                <span>{{ merchCart.length }} {{ pluralize(merchCart.length, 'шт.', 'шт.', 'шт.') }}</span>
                            </div>
                            <div class="summary-row total">
                                <span>К списанию:</span>
                                <span class="total-value">{{ formatNumber(cartTotal) }} бонусов</span>
                            </div>
                            <div class="summary-row balance">
                                <span>Остаток после обмена:</span>
                                <span>{{ formatNumber(userBalance - cartTotal) }} бонусов</span>
                            </div>
                        </div>

                        <div class="checkout-warning">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>После подтверждения бонусы будут списаны, а товары отправлены на указанный адрес.</span>
                        </div>

                        <div class="checkout-actions">
                            <button class="btn-secondary-modern" @click="showCheckoutModal = false">
                                Отмена
                            </button>
                            <button class="btn-primary-modern" @click="confirmCheckout" :disabled="isCheckingOut">
                                <span v-if="isCheckingOut" class="btn-spinner"></span>
                                <template v-else>
                                    <i class="fa-solid fa-check"></i>
                                    Подтвердить
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>
export default {
    name: "CashbackShop",

    data() {
        return {
            activeTab: 'merch',
            userBalance: 0,
            merchProducts: [],
            charityCampaigns: [],
            merchCart: [], // [{id, title, price, image, quantity}]
            selectedDonation: {}, // { campaignId: amount | 'custom' }
            customAmounts: {}, // { campaignId: number }
            showProductModal: false,
            showCheckoutModal: false,
            selectedProduct: null,
            isCheckingOut: false,
            isDonating: null, // id кампании, которая сейчас отправляется
        };
    },

    computed: {
        cartTotal() {
            return this.merchCart.reduce((sum, item) => sum + item.price, 0);
        },

        tenant() {
            return window.Tenant || null;
        },

        self() {
            return window.TenantUser || null;
        },
    },

    async mounted() {
        await this.loadData();
    },

    methods: {
        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        async loadData() {
            try {
                // TODO: Замените на реальные API-запросы
                // const [balanceRes, merchRes, charityRes] = await Promise.all([
                //     this.$store.dispatch('loadCashbackBalance'),
                //     this.$store.dispatch('loadMerchProducts'),
                //     this.$store.dispatch('loadCharityCampaigns'),
                // ]);

                // Имитация загрузки данных
                await new Promise(resolve => setTimeout(resolve, 300));

                this.userBalance = window.TenantUser?.cashBack?.amount || 0;

                this.merchProducts = [
                    {
                        id: 1,
                        title: 'Фирменная футболка',
                        description: 'Хлопок 100%, принт логотипа компании',
                        image: '/images/merch/tshirt.jpg',
                        price: 1500,
                        stock: 25,
                        is_new: true,
                        is_hit: false,
                        details: { 'Материал': 'Хлопок 100%', 'Размеры': 'S, M, L, XL', 'Цвет': 'Чёрный' },
                    },
                    {
                        id: 2,
                        title: 'Термокружка 350мл',
                        description: 'Нержавеющая сталь, сохраняет тепло до 6 часов',
                        image: '/images/merch/mug.jpg',
                        price: 800,
                        stock: 40,
                        is_new: false,
                        is_hit: true,
                        details: { 'Объём': '350 мл', 'Материал': 'Нерж. сталь', 'Цвет': 'Серебро' },
                    },
                    {
                        id: 3,
                        title: 'Худи с логотипом',
                        description: 'Тёплое худи с вышитым логотипом',
                        image: '/images/merch/hoodie.jpg',
                        price: 2500,
                        stock: 15,
                        is_new: true,
                        is_hit: true,
                        details: { 'Материал': 'Футер с начёсом', 'Размеры': 'M, L, XL', 'Цвет': 'Тёмно-синий' },
                    },
                    {
                        id: 4,
                        title: 'Рюкзак городской',
                        description: 'Вместительный рюкзак с отделением для ноутбука',
                        image: '/images/merch/backpack.jpg',
                        price: 3000,
                        stock: 10,
                        is_new: false,
                        is_hit: false,
                        details: { 'Объём': '20 л', 'Ноутбук': 'до 15.6"', 'Цвет': 'Чёрный' },
                    },
                    {
                        id: 5,
                        title: 'Стикерпак',
                        description: 'Набор из 20 виниловых стикеров',
                        image: '/images/merch/stickers.jpg',
                        price: 300,
                        stock: 100,
                        is_new: false,
                        is_hit: false,
                        details: { 'Количество': '20 шт.', 'Материал': 'Винил', 'Размер': '5-10 см' },
                    },
                    {
                        id: 6,
                        title: 'Кепка с вышивкой',
                        description: 'Бейсболка с вышитым логотипом',
                        image: '/images/merch/cap.jpg',
                        price: 0,
                        stock: 0,
                        is_new: false,
                        is_hit: false,
                        details: {},
                    },
                ];

                this.charityCampaigns = [
                    {
                        id: 101,
                        title: 'Помощь приюту для животных',
                        description: 'Сбор на корм и лечение бездомных животных. Каждый бонус — это миска корма для хвостатого друга.',
                        image: '/images/charity/animals.jpg',
                        goal: 50000,
                        collected: 32500,
                        donors_count: 127,
                        end_date: '31.12.2026',
                        amounts: [100, 250, 500, 1000],
                    },
                    {
                        id: 102,
                        title: 'Поддержка детского дома',
                        description: 'Покупка книг и канцелярии для детей из детских домов.',
                        image: '/images/charity/children.jpg',
                        goal: 100000,
                        collected: 45000,
                        donors_count: 89,
                        end_date: '15.01.2027',
                        amounts: [200, 500, 1000, 2000],
                    },
                    {
                        id: 103,
                        title: 'Посадка деревьев',
                        description: 'Каждые 100 бонусов = 1 посаженное дерево в вашем городе.',
                        image: '/images/charity/trees.jpg',
                        goal: 30000,
                        collected: 18700,
                        donors_count: 203,
                        end_date: '01.05.2027',
                        amounts: [100, 300, 500, 1000],
                    },
                ];
            } catch (error) {
                console.error('Ошибка загрузки данных магазина:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить данные магазина',
                    type: 'error',
                });
            }
        },

        // ==========================================
        // КОРЗИНА МЕРЧА
        // ==========================================
        isInCart(productId) {
            return this.merchCart.some(item => item.id === productId);
        },

        addToCart(product) {
            if (product.stock === 0) return;
            if (this.isInCart(product.id)) return;

            this.merchCart.push({
                id: product.id,
                title: product.title,
                price: product.price,
                image: product.image,
                stock: product.stock,
            });

            this.$notify?.({
                title: 'Корзина',
                text: `"${product.title}" добавлен в корзину`,
                type: 'success',
            });
        },

        removeFromCart(productId) {
            const index = this.merchCart.findIndex(item => item.id === productId);
            if (index !== -1) {
                const product = this.merchCart[index];
                this.merchCart.splice(index, 1);

                this.$notify?.({
                    title: 'Корзина',
                    text: `"${product.title}" убран из корзины`,
                    type: 'info',
                });
            }
        },

        toggleCartFromModal(product) {
            if (this.isInCart(product.id)) {
                this.removeFromCart(product.id);
            } else {
                this.addToCart(product);
            }
        },

        openProductModal(product) {
            this.selectedProduct = product;
            this.showProductModal = true;
        },

        closeProductModal() {
            this.showProductModal = false;
            this.selectedProduct = null;
        },

        // ==========================================
        // ОФОРМЛЕНИЕ ЗАКАЗА
        // ==========================================
        checkout() {
            if (this.cartTotal > this.userBalance) return;
            this.showCheckoutModal = true;
        },

        async confirmCheckout() {
            this.isCheckingOut = true;

            try {
                // TODO: Замените на реальный API-запрос
                // await this.$store.dispatch('checkoutMerchOrder', {
                //     items: this.merchCart,
                //     total: this.cartTotal,
                // });

                await new Promise(resolve => setTimeout(resolve, 1500));

                // Уменьшаем баланс
                this.userBalance -= this.cartTotal;

                // Уменьшаем остатки товаров
                this.merchCart.forEach(cartItem => {
                    const product = this.merchProducts.find(p => p.id === cartItem.id);
                    if (product) {
                        product.stock -= 1;
                    }
                });

                // Очищаем корзину
                this.merchCart = [];
                this.showCheckoutModal = false;

                this.$notify?.({
                    title: 'Заказ оформлен!',
                    text: 'Бонусы списаны, товары скоро будут отправлены',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка оформления заказа:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось оформить заказ',
                    type: 'error',
                });
            } finally {
                this.isCheckingOut = false;
            }
        },

        // ==========================================
        // БЛАГОТВОРИТЕЛЬНОСТЬ
        // ==========================================
        getProgressPercent(campaign) {
            return Math.min(100, Math.round((campaign.collected / campaign.goal) * 100));
        },

        selectDonation(campaignId, amount) {
            this.selectedDonation = {
                ...this.selectedDonation,
                [campaignId]: amount,
            };
        },

        selectCustomDonation(campaignId) {
            this.selectedDonation = {
                ...this.selectedDonation,
                [campaignId]: 'custom',
            };
            if (!this.customAmounts[campaignId]) {
                this.customAmounts = {
                    ...this.customAmounts,
                    [campaignId]: null,
                };
            }
        },

        getDonationAmount(campaign) {
            const selected = this.selectedDonation[campaign.id];
            if (!selected) return 0;
            if (selected === 'custom') {
                return this.customAmounts[campaign.id] || 0;
            }
            return selected;
        },

        canDonate(campaign) {
            const amount = this.getDonationAmount(campaign);
            return amount > 0 && amount <= this.userBalance;
        },

        async donate(campaign) {
            const amount = this.getDonationAmount(campaign);
            if (!this.canDonate(campaign)) return;

            this.isDonating = campaign.id;

            try {
                // TODO: Замените на реальный API-запрос
                // await this.$store.dispatch('makeDonation', {
                //     campaign_id: campaign.id,
                //     amount: amount,
                // });

                await new Promise(resolve => setTimeout(resolve, 1200));

                // Обновляем данные
                this.userBalance -= amount;
                campaign.collected += amount;
                campaign.donors_count = (campaign.donors_count || 0) + 1;

                // Сбрасываем выбор
                this.selectedDonation = { ...this.selectedDonation, [campaign.id]: null };
                this.customAmounts = { ...this.customAmounts, [campaign.id]: null };

                this.$notify?.({
                    title: 'Спасибо за помощь!',
                    text: `Вы пожертвовали ${this.formatNumber(amount)} бонусов`,
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка пожертвования:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось выполнить пожертвование',
                    type: 'error',
                });
            } finally {
                this.isDonating = null;
            }
        },

        // ==========================================
        // УТИЛИТЫ
        // ==========================================
        formatNumber(number) {
            return new Intl.NumberFormat('ru-RU').format(number || 0);
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

<style lang="scss" scoped>
@use 'sass:color';
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #8b5cf6;
$primary-light: #a78bfa;
$primary-dark: #7c3aed;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$charity: #ec4899;
$charity-light: #f472b6;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.cashback-shop {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 120px;
}

// ==========================================
// HERO
// ==========================================
.shop-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 215, 0, 0.1) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 16px;
}

.hero-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.hero-sparkle {
    position: absolute;
    font-size: 1.3rem;
    animation: sparkle 2s ease-in-out infinite;
}

.sparkle-1 { top: -10px; right: -10px; animation-delay: 0s; }
.sparkle-2 { bottom: -10px; left: -10px; animation-delay: 0.7s; }

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0.5); }
    50% { opacity: 1; transform: scale(1.2); }
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 6px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0 0 24px 0;
}

.balance-card {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    padding: 16px 24px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
}

.balance-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

.balance-info {
    text-align: left;
}

.balance-value {
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1;
}

.balance-label {
    font-size: 0.75rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

// ==========================================
// КОНТЕНТ
// ==========================================
.shop-content {
    padding: 0 16px;
}

// ==========================================
// ТАБЫ
// ==========================================
.tabs-wrapper {
    position: sticky;
    top: 0;
    z-index: 10;
    background: $bg;
    padding: 16px 0;
    margin: 0 -16px;
    padding-left: 16px;
    padding-right: 16px;
}

.tabs {
    display: flex;
    gap: 8px;
    background: $card-bg;
    padding: 6px;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;

    &:hover {
        background: rgba($primary, 0.05);
        color: $primary;
    }

    &.is-active {
        background: $primary;
        color: white;
        box-shadow: 0 4px 12px rgba($primary, 0.3);
    }

    &.charity.is-active {
        background: $charity;
        box-shadow: 0 4px 12px rgba($charity, 0.3);
    }
}

.tab-badge {
    padding: 2px 8px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

// ==========================================
// МЕРЧ
// ==========================================
.merch-section {
    padding: 8px 0 24px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $text-muted;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: $card-bg;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 16px;
        color: $primary;
    }

    h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        margin: 0;
    }
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}

.product-card {
    background: $card-bg;
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    display: flex;
    flex-direction: column;

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border-color: rgba($primary, 0.2);
    }

    &.is-in-cart {
        border-color: $success;
        box-shadow: 0 0 0 3px rgba($success, 0.1);
    }

    &.is-unavailable {
        opacity: 0.6;
        cursor: not-allowed;

        &:hover {
            transform: none;
            box-shadow: none;
        }
    }
}

.card-image {
    position: relative;
    aspect-ratio: 1;
    background: $bg;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover & img {
        transform: scale(1.05);
    }
}

.out-of-stock-badge {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(2px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: white;
    font-size: 0.8rem;
    font-weight: 600;

    i {
        font-size: 1.5rem;
    }
}

.new-badge, .hit-badge {
    position: absolute;
    top: 8px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.5px;

    &.large {
        top: 12px;
        left: 12px;
        padding: 6px 14px;
        font-size: 0.75rem;
    }
}

.new-badge {
    left: 8px;
    background: $primary;
    color: white;
}

.hit-badge {
    right: 8px;
    background: $warning;
    color: white;
}

.card-info {
    padding: 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 4px 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.3em;
}

.card-description {
    font-size: 0.75rem;
    color: $text-muted;
    margin: 0 0 10px 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}

.card-footer {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 10px;
}

.card-price {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 1.05rem;
    font-weight: 800;
    color: $primary;

    i {
        color: #fbbf24;
    }

    .price-unit {
        font-size: 0.7rem;
        font-weight: 600;
        color: $text-muted;
        margin-left: auto;
    }
}

.card-stock {
    font-size: 0.7rem;
    color: $text-muted;
}

.card-action {
    padding: 0 12px 12px;
}

.btn-add, .btn-added {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    border-radius: 10px;
    border: none;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-add {
    background: $primary;
    color: white;

    &:hover:not(:disabled) {
        background: $primary-dark;
    }

    &:disabled {
        background: #d1d5db;
        cursor: not-allowed;
    }
}

.btn-added {
    background: rgba($success, 0.1);
    color: $success;
    border: 1px solid rgba($success, 0.3);

    &:hover {
        background: rgba($success, 0.15);
    }
}

// ==========================================
// БЛАГОТВОРИТЕЛЬНОСТЬ
// ==========================================
.charity-section {
    padding: 8px 0 24px;
}

.charity-intro {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, rgba($charity, 0.08) 0%, rgba($charity-light, 0.08) 100%);
    border: 1px solid rgba($charity, 0.2);
    border-radius: 16px;
    margin-bottom: 20px;
}

.intro-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, $charity 0%, $charity-light 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.intro-info {
    flex: 1;

    h3 {
        font-size: 1rem;
        font-weight: 700;
        color: $text;
        margin: 0 0 4px 0;
    }

    p {
        font-size: 0.85rem;
        color: $text-muted;
        margin: 0;
        line-height: 1.4;
    }
}

.charity-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.charity-card {
    background: $card-bg;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.charity-image {
    position: relative;
    aspect-ratio: 16/9;
    background: $bg;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.charity-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 6px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, $charity 0%, $charity-light 100%);
    border-radius: 3px;
    transition: width 0.5s ease;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: white;
    font-weight: 600;
}

.charity-info {
    padding: 16px;
}

.charity-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 8px 0;
}

.charity-description {
    font-size: 0.85rem;
    color: $text-muted;
    line-height: 1.5;
    margin: 0 0 12px 0;
}

.charity-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $text-muted;

    i {
        color: $charity;
    }
}

.donation-amounts {
    margin-bottom: 16px;
}

.donation-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: $text-muted;
    margin-bottom: 10px;
}

.amount-buttons {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.amount-btn {
    padding: 12px 8px;
    background: $bg;
    border: 2px solid $border;
    border-radius: 10px;
    color: $text;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        border-color: $charity;
        color: $charity;
    }

    &.is-selected {
        background: $charity;
        border-color: $charity;
        color: white;
        box-shadow: 0 4px 12px rgba($charity, 0.3);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    &.custom {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

.custom-amount {
    margin-top: 10px;
}

.input-with-suffix {
    position: relative;
}

.amount-input {
    width: 100%;
    padding: 12px 80px 12px 14px;
    border: 2px solid $border;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    color: $text;
    transition: all 0.2s ease;

    &:focus {
        outline: none;
        border-color: $charity;
        box-shadow: 0 0 0 3px rgba($charity, 0.1);
    }
}

.input-suffix {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    pointer-events: none;
}

.amount-error {
    display: block;
    margin-top: 6px;
    font-size: 0.8rem;
    color: $danger;
    font-weight: 600;
}

.donate-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: linear-gradient(135deg, $charity 0%, $charity-light 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba($charity, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($charity, 0.4);
    }

    &.is-disabled {
        background: #d1d5db;
        box-shadow: none;
        cursor: not-allowed;
    }
}

// ==========================================
// НИЖНЯЯ ПАНЕЛЬ
// ==========================================
.bottom-panel {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: $card-bg;
    border-top: 1px solid $border;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    padding: 16px 20px;
    z-index: 100;
    display: flex;
    align-items: center;
    gap: 16px;
}

.panel-info {
    flex: 1;
    min-width: 0;
}

.panel-label {
    font-size: 0.75rem;
    color: $text-muted;
    margin-bottom: 2px;
}

.panel-price {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.4rem;
    font-weight: 800;
    color: $primary;

    i {
        color: #fbbf24;
    }

    .price-unit {
        font-size: 0.8rem;
        font-weight: 600;
        color: $text-muted;
    }
}

.panel-hint {
    font-size: 0.75rem;
    color: $success;
    margin-top: 2px;
    font-weight: 600;

    &.is-warning {
        color: $danger;
    }
}

.checkout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba($primary, 0.3);
    white-space: nowrap;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($primary, 0.4);
    }

    &.is-disabled {
        background: #d1d5db;
        box-shadow: none;
        cursor: not-allowed;
    }
}

.btn-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// МОДАЛКИ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: $card-bg;
    border-radius: 20px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-modal {
    max-width: 500px;
    position: relative;
}

.modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(8px);
    border: none;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    z-index: 10;
    transition: all 0.2s ease;

    &:hover {
        background: $danger;
        color: white;
    }
}

.modal-body {
    overflow-y: auto;
}

.product-gallery {
    position: relative;
    aspect-ratio: 1;
    background: $bg;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.product-details {
    padding: 20px;
}

.product-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 12px 0;
}

.product-price-large {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.8rem;
    font-weight: 800;
    color: $primary;
    margin-bottom: 16px;

    i {
        color: #fbbf24;
    }

    .price-unit {
        font-size: 0.9rem;
        font-weight: 600;
        color: $text-muted;
    }
}

.product-description-full {
    font-size: 0.95rem;
    color: $text-muted;
    line-height: 1.5;
    margin: 0 0 20px 0;
}

.product-specs {
    margin-bottom: 20px;

    h4 {
        font-size: 0.9rem;
        font-weight: 700;
        color: $text;
        margin: 0 0 10px 0;
    }

    ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    li {
        font-size: 0.85rem;
        color: $text-muted;
        padding: 8px 12px;
        background: $bg;
        border-radius: 8px;

        strong {
            color: $text;
            margin-right: 6px;
        }
    }
}

.product-stock-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: rgba($success, 0.08);
    border-radius: 10px;
    color: $success;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 16px;
}

.modal-add-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: $primary;
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        background: $primary-dark;
    }

    &:disabled {
        background: #d1d5db;
        cursor: not-allowed;
    }

    &.is-in-cart {
        background: rgba($success, 0.1);
        color: $success;
        border: 2px solid rgba($success, 0.3);
    }
}

// Модалка подтверждения заказа
.checkout-modal {
    max-width: 450px;
}

.checkout-header {
    text-align: center;
    padding: 24px 24px 16px;
    border-bottom: 1px solid $border;
}

.checkout-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 12px;
}

.checkout-header h3 {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0;
}

.checkout-body {
    padding: 20px 24px 24px;
    overflow-y: auto;
}

.checkout-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.checkout-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: $bg;
    border-radius: 10px;

    img {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        object-fit: cover;
    }
}

.checkout-item-info {
    flex: 1;
    min-width: 0;
}

.checkout-item-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.checkout-item-price {
    font-size: 0.85rem;
    color: $primary;
    font-weight: 700;
    margin-top: 2px;
}

.checkout-summary {
    padding: 16px;
    background: $bg;
    border-radius: 12px;
    margin-bottom: 16px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 0.9rem;
    color: $text-muted;

    &.total {
        padding-top: 12px;
        margin-top: 6px;
        border-top: 1px solid $border;
        font-size: 1rem;
        font-weight: 700;
        color: $text;
    }

    &.balance {
        font-size: 0.85rem;
        color: $success;
        font-weight: 600;
    }
}

.total-value {
    color: $primary;
    font-size: 1.1rem;
}

.checkout-warning {
    display: flex;
    gap: 10px;
    padding: 12px;
    background: rgba($primary, 0.08);
    border-radius: 10px;
    font-size: 0.8rem;
    color: $text-muted;
    margin-bottom: 20px;
    line-height: 1.4;

    i {
        color: $primary;
        flex-shrink: 0;
        margin-top: 2px;
    }
}

.checkout-actions {
    display: flex;
    gap: 10px;
}

.btn-secondary-modern, .btn-primary-modern {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary-modern {
    background: $bg;
    color: $text;
    border: 1px solid $border;

    &:hover {
        background: color.adjust($bg, $lightness: -3%);
    }
}

.btn-primary-modern {
    background: $primary;
    color: white;

    &:hover:not(:disabled) {
        background: $primary-dark;
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from, .slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}

.collapse-enter-active, .collapse-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.collapse-enter-from, .collapse-leave-to {
    opacity: 0;
    max-height: 0;
}

.collapse-enter-to, .collapse-leave-from {
    opacity: 1;
    max-height: 100px;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .shop-hero {
        padding: 32px 16px 24px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .hero-icon {
        width: 64px;
        height: 64px;
        font-size: 1.8rem;
    }

    .balance-card {
        padding: 12px 20px;
    }

    .balance-value {
        font-size: 1.5rem;
    }

    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .amount-buttons {
        grid-template-columns: repeat(3, 1fr);
    }

    .bottom-panel {
        flex-direction: column;
        gap: 12px;
        padding: 12px 16px;
    }

    .panel-info {
        width: 100%;
        text-align: center;
    }

    .checkout-btn {
        width: 100%;
    }

    .cashback-shop {
        padding-bottom: 180px;
    }

    .checkout-actions {
        flex-direction: column-reverse;
    }
}

@media (max-width: 480px) {
    .products-grid {
        gap: 8px;
    }

    .card-info {
        padding: 10px;
    }

    .card-title {
        font-size: 0.85rem;
    }

    .card-price {
        font-size: 0.95rem;
    }

    .charity-meta {
        flex-direction: column;
        gap: 8px;
    }
}
</style>
