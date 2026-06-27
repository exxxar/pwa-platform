import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/basket';

export const useBasketStore = defineStore('basket', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        basket_items: [],
        basket_items_paginate_object: null,

        // Состояние загрузки
        isLoading: false,
        isHydrated: false,
        isSending: false,

        // Действия над товарами: { [productId]: 'inc' | 'dec' | 'remove' }
        productActions: {},

        // Ошибки
        lastError: null,
        errors: [],

        // Время последней синхронизации
        lastSyncAt: null,
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
         * Пагинация
         */
        getBasketPaginateObject: (state) => state.basket_items_paginate_object || null,

        /**
         * Количество товара в подборке
         */
        inCollectionCart: (state) => (id, variantId) => {
            return state.basket_items.find(bItem =>
                bItem.collection?.id === id &&
                (bItem.params?.variant_id === variantId || variantId == null)
            )?.count || 0;
        },

        /**
         * Количество товара в корзине
         */
        inCart: (state) => (id) => {
            return (state.basket_items.find(item => item.product?.id === id))?.count || 0;
        },

        /**
         * Все товары (алиас)
         */
        cartProducts: (state) => state.basket_items || [],

        /**
         * Только подборки
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
         * Общая сумма корзины (с учётом весовых товаров и подборок)
         */
        cartTotalPrice: (state) => {
            if (!state.basket_items?.length) return 0;

            return state.basket_items.reduce((sum, item) => {
                // Обычный товар
                if (item.product) {
                    const currentPrice = item.params?.discount_price
                        ? item.params.discount_price
                        : (item.product.current_price || 0);

                    const count = item.product?.is_weight_product ? 1 : item.count;
                    const price = item.product?.is_weight_product
                        ? (currentPrice * item.count) / (item.product.weight_config?.step || 100)
                        : currentPrice;

                    return sum + price * count;
                }

                // Подборка (коллекция)
                if (item.collection) {
                    const selected = item.params?.ids || [];
                    const collectionPrice = item.collection.products.reduce((acc, sub) => {
                        return acc + (selected.includes(sub.id) ? (sub.current_price || 0) : 0);
                    }, 0);

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
         * Проверка, загружается ли товар
         */
        isProductLoading: (state) => (productId) => {
            return !!state.productActions[String(productId)];
        },

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
        // ==========================================
        // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
        // ==========================================

        /**
         * Обновление корзины из ответа сервера
         */
        _updateBasketFromResponse(dataObject) {
            if (!dataObject) return;

            this.basket_items = dataObject.data || [];
            const { data, ...pagination } = dataObject;
            this.basket_items_paginate_object = pagination;
            this.lastSyncAt = new Date();
        },

        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка товаров корзины
         */
        async loadProductsInBasket(payload = { dataObject: {}, page: 0, size: 20 }) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const page = payload.page || 0;
                const size = payload.size || 20;
                const link = `${BASE}?page=${page}&size=${size}`;

                const response = await axios.post(link);
                this._updateBasketFromResponse(response.data);
                this.isHydrated = true;

                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка загрузки корзины:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить корзину';
                this.errors = err.response?.data?.errors || [];
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        // ==========================================
        // ТОВАРЫ: ДОБАВЛЕНИЕ/УДАЛЕНИЕ
        // ==========================================

        /**
         * Добавление товара в корзину (через /inc-product)
         */
        async addProductToCart(productId) {
            this.productActions[String(productId)] = 'inc';

            // Оптимистичное обновление
            const cartItem = this.basket_items.find(item => item.product?.id === productId);
            if (cartItem) {
                cartItem.count++;
            }

            try {
                const response = await axios.post(`${BASE}/inc-product`, {
                    product_id: productId,
                });

                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                // Откат
                if (cartItem) {
                    cartItem.count--;
                }
                console.error('[Basket Store] Ошибка добавления товара:', err);
                throw err;
            } finally {
                delete this.productActions[String(productId)];
            }
        },

        /**
         * Удаление товара из корзины (через /dec-product)
         */
        async removeProductFromCart(productId) {
            this.productActions[String(productId)] = 'dec';

            // Оптимистичное обновление
            const cartItem = this.basket_items.find(item => item.product?.id === productId);
            if (cartItem && cartItem.count > 1) {
                cartItem.count--;
            } else if (cartItem && cartItem.count === 1) {
                const index = this.basket_items.indexOf(cartItem);
                this.basket_items.splice(index, 1);
            }

            try {
                const response = await axios.post(`${BASE}/dec-product`, {
                    product_id: productId,
                });

                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка удаления товара:', err);
                throw err;
            } finally {
                delete this.productActions[String(productId)];
            }
        },

        /**
         * Увеличение количества товара (через /increment/{id})
         */
        async incQuantity(productId) {
            this.productActions[String(productId)] = 'inc';

            const cartItem = this.basket_items.find(item => item.product?.id === productId);
            if (cartItem) {
                cartItem.count++;
            }

            try {
                const response = await axios.post(`${BASE}/increment/${productId}`);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                if (cartItem) {
                    cartItem.count--;
                }
                console.error('[Basket Store] Ошибка увеличения количества:', err);
                throw err;
            } finally {
                delete this.productActions[String(productId)];
            }
        },

        /**
         * Уменьшение количества товара (через /decrement/{id})
         */
        async decQuantity(productId) {
            this.productActions[String(productId)] = 'dec';

            const cartItem = this.basket_items.find(item => item.product?.id === productId);
            if (cartItem && cartItem.count > 1) {
                cartItem.count--;
            } else if (cartItem && cartItem.count === 1) {
                const index = this.basket_items.indexOf(cartItem);
                this.basket_items.splice(index, 1);
            }

            try {
                const response = await axios.post(`${BASE}/decrement/${productId}`);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка уменьшения количества:', err);
                throw err;
            } finally {
                delete this.productActions[String(productId)];
            }
        },

        /**
         * Полное удаление товара из корзины (через /remove/{id})
         */
        async removeProduct(productId) {
            this.productActions[String(productId)] = 'remove';

            // Сохраняем для отката
            const previousItems = [...this.basket_items];
            const removedIndex = this.basket_items.findIndex(item => item.product?.id === productId);
            const removedItem = removedIndex !== -1 ? this.basket_items[removedIndex] : null;

            if (removedIndex !== -1) {
                this.basket_items.splice(removedIndex, 1);
            }

            try {
                const response = await axios.delete(`${BASE}/remove/${productId}`);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                // Откат
                if (removedItem && removedIndex !== -1) {
                    this.basket_items.splice(removedIndex, 0, removedItem);
                }
                console.error('[Basket Store] Ошибка удаления товара:', err);
                throw err;
            } finally {
                delete this.productActions[String(productId)];
            }
        },

        // ==========================================
        // ПОДБОРКИ (КОЛЛЕКЦИИ)
        // ==========================================

        /**
         * Добавление подборки в корзину
         */
        async addCollectionToCart(collection) {
            const collectionId = collection?.id;
            if (collectionId) {
                this.productActions[String(collectionId)] = 'inc-collection';
            }

            try {
                const response = await axios.post(`${BASE}/inc-collection`, {
                    product_collection: collection,
                });

                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка добавления подборки:', err);
                throw err;
            } finally {
                if (collectionId) {
                    delete this.productActions[String(collectionId)];
                }
            }
        },

        /**
         * Увеличение количества подборки
         */
        async incCollectionQuantity(payload) {
            const collectionId = payload?.id || payload?.collection_id;
            if (collectionId) {
                this.productActions[String(collectionId)] = 'inc-collection';
            }

            try {
                const response = await axios.post(`${BASE}/inc-collection`, payload);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка увеличения подборки:', err);
                throw err;
            } finally {
                if (collectionId) {
                    delete this.productActions[String(collectionId)];
                }
            }
        },

        /**
         * Уменьшение количества подборки
         */
        async decCollectionQuantity(payload) {
            const collectionId = payload?.id || payload?.collection_id;
            if (collectionId) {
                this.productActions[String(collectionId)] = 'dec-collection';
            }

            try {
                const response = await axios.post(`${BASE}/dec-collection`, payload);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка уменьшения подборки:', err);
                throw err;
            } finally {
                if (collectionId) {
                    delete this.productActions[String(collectionId)];
                }
            }
        },

        /**
         * Удаление подборки из корзины
         */
        async removeCollectionFromCart(payload) {
            const collectionId = payload?.id || payload?.collection_id;
            if (collectionId) {
                this.productActions[String(collectionId)] = 'remove-collection';
            }

            try {
                const response = await axios.post(`${BASE}/dec-collection`, payload);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка удаления подборки:', err);
                throw err;
            } finally {
                if (collectionId) {
                    delete this.productActions[String(collectionId)];
                }
            }
        },

        // ==========================================
        // КОММЕНТАРИИ
        // ==========================================

        /**
         * Добавление комментария к товару
         */
        async addCommentToProduct(payload) {
            try {
                const response = await axios.post(`${BASE}/comment-product`, {
                    product_id: payload.id,
                    comment: payload.comment || null,
                });

                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка добавления комментария:', err);
                throw err;
            }
        },

        // ==========================================
        // ОФОРМЛЕНИЕ ЗАКАЗА
        // ==========================================

        /**
         * Создание ссылки на оплату
         */
        async createCheckoutLink(payload = { deliveryForm: null }) {
            try {
                const response = await axios.post(`${BASE}/checkout-link`, payload.deliveryForm);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка создания ссылки:', err);
                throw err;
            }
        },

        /**
         * Оформление заказа
         */
        async startCheckout(payload = { deliveryForm: null }) {
            this.isSending = true;

            try {
                const response = await axios.post(`${BASE}/checkout`, payload.deliveryForm);

                // Очищаем корзину после успешного оформления
                this.basket_items = [];
                this.basket_items_paginate_object = null;

                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка оформления заказа:', err);
                throw err;
            } finally {
                this.isSending = false;
            }
        },

        /**
         * Использование приза из колеса фортуны
         */
        async useWheelOfFortunePrize(payload = { form: null }) {
            try {
                const response = await axios.post(`${BASE}/use-wheel-of-fortune-prize`, payload.form);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка использования приза:', err);
                throw err;
            }
        },

        // ==========================================
        // ОЧИСТКА
        // ==========================================

        /**
         * Полная очистка корзины
         */
        async clearCart() {
            // Оптимистичная очистка
            const previousItems = [...this.basket_items];
            const previousPaginate = this.basket_items_paginate_object;

            this.basket_items = [];
            this.basket_items_paginate_object = null;

            try {
                await axios.delete(`${BASE}/clear`);
            } catch (err) {
                // Откат
                this.basket_items = previousItems;
                this.basket_items_paginate_object = previousPaginate;
                console.error('[Basket Store] Ошибка очистки корзины:', err);
                throw err;
            }
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.basket_items = [];
            this.basket_items_paginate_object = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isSending = false;
            this.productActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;
        },
    },
});
