import { defineStore } from 'pinia';
import { apiRequest } from '../utils/api.js';

const BASE_BASKET_LINK = '/basket';

/**
 * Состояние загрузки для действий с товарами
 * @typedef {Object} LoadingState
 * @property {boolean} isLoading - Общая загрузка корзины
 * @property {boolean} isHydrated - Загружена ли корзина с сервера
 * @property {Object} productActions - Загрузка по конкретным товарам { [productId]: 'inc' | 'dec' | 'remove' }
 * @property {number} pendingCount - Количество товаров в процессе изменения
 * @property {Date|null} lastSyncAt - Время последней синхронизации
 * @property {string|null} lastError - Последняя ошибка
 */

export const useBasketStore = defineStore('basket', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные корзины
        basket_items: [],
        basket_items_paginate_object: null,

        // Отслеживание загрузки
        isLoading: false,
        isHydrated: false,
        lastSyncAt: null,
        lastError: null,

        // Загрузка по конкретным товарам: { [productId]: 'inc' | 'dec' | 'remove' }
        productActions: {},

        // Debounce-таймеры для предотвращения спама запросов
        _debounceTimers: {},
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Все товары в корзине
         */
        getProductsInBasket: (state) => state.basket_items || [],

        /**
         * Объект пагинации
         */
        getBasketPaginateObject: (state) => state.basket_items_paginate_object || null,

        /**
         * Количество конкретного товара в корзине (для подборок)
         */
        inCollectionCart: (state) => (id, variantId) => {
            return state.basket_items.find(bItem =>
                bItem.collection?.id === id &&
                (bItem.params?.variant_id === variantId || variantId == null)
            )?.count || 0;
        },

        /**
         * Количество конкретного товара в корзине
         */
        inCart: (state) => (id) => {
            return (state.basket_items.find(item => item.product?.id === id))?.count || 0;
        },

        /**
         * Все товары (алиас)
         */
        cartProducts: (state) => state.basket_items || [],

        /**
         * Только подборки (коллекции)
         */
        cartCollections: (state) => (state.basket_items || []).filter(item => item.collection),

        /**
         * Общее количество товаров
         */
        cartTotalCount: (state) => {
            if (!state.basket_items?.length) return 0;
            return state.basket_items.reduce((sum, item) => {
                return sum + (item.product?.is_weight_product ? 1 : item.count);
            }, 0);
        },

        /**
         * Общая сумма корзины
         */
        cartTotalPrice: (state) => {
            if (!state.basket_items?.length) return 0;

            return state.basket_items.reduce((sum, item) => {
                // Обычный товар
                if (item.product) {
                    const currentPrice = item.params?.discount_price || item.product.price || 0;
                    const count = item.product?.is_weight_product ? 1 : item.count;
                    const price = item.product?.is_weight_product
                        ? (currentPrice * item.count) / (item.product.weight_config?.step || 100)
                        : currentPrice;
                    return sum + price * count;
                }

                // Подборка (коллекция)
                if (item.collection) {
                    const selected = item.params?.ids || [];
                    const collectionPrice = item.collection.products
                        .filter(sub => selected.includes(sub.id))
                        .reduce((acc, sub) => acc + (sub.price || 0), 0);
                    const discountedPrice = collectionPrice * (1 - (item.collection.discount || 0) / 100);
                    return sum + discountedPrice * item.count;
                }

                return sum;
            }, 0);
        },

        /**
         * Пуста ли корзина
         */
        isEmpty: (state) => !state.basket_items?.length,

        /**
         * Проверка, идёт ли действие над конкретным товаром
         */
        isProductLoading: (state) => (productId) => !!state.productActions[productId],

        /**
         * Получить товар из корзины по ID
         */
        getItemById: (state) => (productId) => {
            return state.basket_items.find(item => item.product?.id === productId) || null;
        },

        /**
         * Получить подборку из корзины по ID
         */
        getCollectionById: (state) => (collectionId) => {
            return state.basket_items.find(item => item.collection?.id === collectionId) || null;
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ------------------------------------------
        // Вспомогательные методы
        // ------------------------------------------

        /**
         * Универсальный обработчик API-запросов корзины
         * Автоматически обновляет состояние корзины после успешного запроса
         */
        async _basketApiRequest(link, method = 'POST', payload = null) {
            try {
                const response = await apiRequest(link, method, payload);
                const dataObject = response.data;

                // Обновляем корзину из ответа сервера
                if (dataObject?.data) {
                    this.basket_items = dataObject.data;
                    const { data, ...pagination } = dataObject;
                    this.basket_items_paginate_object = pagination;
                }

                this.lastSyncAt = new Date();
                this.lastError = null;
                return response.data;
            } catch (err) {
                const errorMessage = err.response?.data?.message || 'Ошибка запроса';
                this.lastError = errorMessage;
                console.error('[Basket Store] Ошибка:', errorMessage, err.response?.data?.errors || []);
                throw err;
            }
        },

        /**
         * Debounce для предотвращения спама запросов при быстром клике
         */
        _debounce(key, fn, delay = 300) {
            if (this._debounceTimers[key]) {
                clearTimeout(this._debounceTimers[key]);
            }
            this._debounceTimers[key] = setTimeout(fn, delay);
        },

        // ------------------------------------------
        // Сеттеры
        // ------------------------------------------

        setBasket(payload) {
            this.basket_items = payload || [];
        },

        setBasketPaginateObject(payload) {
            this.basket_items_paginate_object = payload || null;
        },

        // ------------------------------------------
        // Оптимистичные обновления (с откатом)
        // ------------------------------------------

        /**
         * Мгновенное изменение количества товара в UI (оптимистично)
         * Сохраняет предыдущее значение для возможного отката
         */
        _optimisticUpdate(productId, delta) {
            const cartItem = this.basket_items.find(
                item => item.product?.id === productId || item.collection?.id === productId
            );

            if (!cartItem) return null;

            // Сохраняем предыдущее значение для отката
            const previousCount = cartItem.count;

            // Применяем изменение
            cartItem.count = Math.max(0, cartItem.count + delta);

            // Если количество стало 0 — удаляем из корзины визуально
            if (cartItem.count === 0) {
                const index = this.basket_items.indexOf(cartItem);
                if (index !== -1) {
                    this.basket_items.splice(index, 1);
                }
            }

            return previousCount;
        },

        /**
         * Откат изменений при ошибке API
         */
        _rollbackUpdate(productId, previousCount) {
            if (previousCount === null) return;

            const cartItem = this.basket_items.find(
                item => item.product?.id === productId || item.collection?.id === productId
            );

            if (cartItem) {
                cartItem.count = previousCount;
            } else if (previousCount > 0) {
                // Товар был удалён — нужно вернуть (упрощённо)
                console.warn('[Basket Store] Откат: товар не найден для восстановления');
            }
        },

        // ------------------------------------------
        // Загрузка корзины
        // ------------------------------------------

        /**
         * Загрузка товаров корзины с сервера
         */
        async loadProductsInBasket(payload = { dataObject: { search: null, categories: null }, page: 0, size: 20 }) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const page = payload.page || 0;
                const size = payload.size || 20;
                const link = `${BASE_BASKET_LINK}?page=${page}&size=${size}`;

                await this._basketApiRequest(link, 'POST');
                this.isHydrated = true;
            } catch (err) {
                // Ошибка уже залогирована в _basketApiRequest
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        // ------------------------------------------
        // Добавление / удаление товаров
        // ------------------------------------------

        /**
         * Увеличение количества товара
         * С оптимистичным обновлением и debouncing
         */
        async addProductToCart(productId) {
            // Помечаем товар как "в процессе изменения"
            this.productActions[productId] = 'inc';

            // Оптимистично обновляем UI
            const previousCount = this._optimisticUpdate(productId, 1);

            try {
                await this._basketApiRequest(
                    `${BASE_BASKET_LINK}/inc-product`,
                    'POST',
                    { product_id: productId }
                );
            } catch (err) {
                // Откатываем изменения
                this._rollbackUpdate(productId, previousCount);
                throw err;
            } finally {
                delete this.productActions[productId];
            }
        },

        /**
         * Уменьшение количества товара
         * С оптимистичным обновлением и debouncing
         */
        async removeProductFromCart(productId) {
            this.productActions[productId] = 'dec';

            const previousCount = this._optimisticUpdate(productId, -1);

            try {
                await this._basketApiRequest(
                    `${BASE_BASKET_LINK}/dec-product`,
                    'POST',
                    { product_id: productId }
                );
            } catch (err) {
                this._rollbackUpdate(productId, previousCount);
                throw err;
            } finally {
                delete this.productActions[productId];
            }
        },

        /**
         * Увеличение количества (legacy, для совместимости)
         */
        incrementItemQuantity(id) {
            const cartItem = this.basket_items.find(
                item => item.product?.id === id || item.collection?.id === id
            );
            if (cartItem) {
                cartItem.count++;
            }
        },

        /**
         * Уменьшение количества (legacy, для совместимости)
         */
        decrementItemQuantity(id) {
            const cartItem = this.basket_items.find(
                item => item.product?.id === id || item.collection?.id === id
            );
            if (cartItem && cartItem.count > 1) {
                cartItem.count--;
            }
        },

        // ------------------------------------------
        // Очистка корзины
        // ------------------------------------------

        /**
         * Полная очистка корзины
         */
        async clearCart() {
            // Сохраняем предыдущее состояние для отката
            const previousItems = [...this.basket_items];
            const previousPaginate = this.basket_items_paginate_object;

            // Оптимистично очищаем
            this.basket_items = [];
            this.basket_items_paginate_object = null;

            try {
                await apiRequest(`${BASE_BASKET_LINK}/clear`, 'DELETE');
                this.lastSyncAt = new Date();
            } catch (err) {
                // Откатываем
                this.basket_items = previousItems;
                this.basket_items_paginate_object = previousPaginate;
                throw err;
            }
        },

        // ------------------------------------------
        // Checkout
        // ------------------------------------------

        /**
         * Начало оформления заказа
         */
        async startCheckout(payload = { deliveryForm: null }) {
            this.isLoading = true;

            try {
                const result = await this._basketApiRequest(
                    `${BASE_BASKET_LINK}/checkout`,
                    'POST',
                    payload.deliveryForm
                );

                // Очищаем корзину после успешного оформления
                this.basket_items = [];
                this.basket_items_paginate_object = null;

                return result;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Создание ссылки на оплату
         */
        async createCheckoutLink(payload = { deliveryForm: null }) {
            try {
                const response = await apiRequest(
                    `${BASE_BASKET_LINK}/checkout-link`,
                    'POST',
                    payload.deliveryForm
                );
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка создания ссылки:', err);
                throw err;
            }
        },

        // ------------------------------------------
        // Дополнительные действия
        // ------------------------------------------

        /**
         * Расчёт стоимости доставки
         */
        async requestDeliveryPriceNew(payload) {
            try {
                const response = await apiRequest(
                    `${BASE_BASKET_LINK}/get-delivery-price-new`,
                    'POST',
                    payload
                );
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка расчёта доставки:', err);
                throw err;
            }
        },

        /**
         * Добавление комментария к товару
         */
        async addCommentToProduct(payload = { form: null }) {
            try {
                const response = await apiRequest(
                    `${BASE_BASKET_LINK}/comment-product`,
                    'POST',
                    payload.form
                );
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка добавления комментария:', err);
                throw err;
            }
        },

        /**
         * Использование приза из колеса фортуны
         */
        async useWheelOfFortunePrize(payload = { form: null }) {
            try {
                const response = await apiRequest(
                    `${BASE_BASKET_LINK}/use-wheel-of-fortune-prize`,
                    'POST',
                    payload.form
                );
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка использования приза:', err);
                throw err;
            }
        },

        // ------------------------------------------
        // Сброс состояния
        // ------------------------------------------

        /**
         * Полный сброс состояния стора (например, при выходе)
         */
        $reset() {
            this.basket_items = [];
            this.basket_items_paginate_object = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.lastSyncAt = null;
            this.lastError = null;
            this.productActions = {};

            // Очищаем все debounce-таймеры
            Object.values(this._debounceTimers).forEach(clearTimeout);
            this._debounceTimers = {};
        },
    },
});
