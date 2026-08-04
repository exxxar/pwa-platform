import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/basket';

export const useBasketStore = defineStore('basket', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        basket_items: [],
        basket_items_paginate_object: null,
        itemsCount: 0,
        isLoading: false,
        isHydrated: false,
        isSending: false,
        productActions: {},
        lastError: null,
        errors: [],
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        getProductsInBasket: (state) => state.basket_items || [],
        getBasketPaginateObject: (state) => state.basket_items_paginate_object || null,

        /**
         * Количество конкретного варианта коллекции в корзине
         */
        inCollectionCart: (state) => (collectionId, variantId = null) => {
            return state.basket_items.find(bItem =>
                bItem.collection?.id === collectionId &&
                (variantId == null || bItem.params?.variant_id === variantId)
            )?.count || 0;
        },

        inCart: (state) => (id) => {
            return (state.basket_items.find(item => item.product?.id === id))?.count || 0;
        },

        cartProducts: (state) => state.basket_items || [],

        cartCollections: (state) => (state.basket_items || []).filter(item => item.collection),

        /**
         * 🆕 Все варианты конкретной коллекции в корзине
         */
        getCollectionVariants: (state) => (collectionId) => {
            return (state.basket_items || []).filter(
                item => item.collection?.id === collectionId
            );
        },

        /**
         * 🆕 Получить конкретный вариант корзины по variant_id
         */
        getBasketItemByVariantId: (state) => (variantId) => {
            return (state.basket_items || []).find(
                item => item.params?.variant_id === variantId
            ) || null;
        },

        cartTotalCount: (state) => {
            if (!state.basket_items?.length) return 0;
            return state.basket_items.reduce((sum, item) => {
                return sum + (item.product?.is_weight_product ? 1 : item.count);
            }, 0);
        },

        /**
         * Улучшенный расчёт общей суммы корзины.
         * Работает и с товарами, и с вариантами коллекций.
         */
        cartTotalPrice: (state) => {
            if (!state.basket_items?.length) return 0;

            return state.basket_items.reduce((sum, item) => {
                // ==========================================
                // ОБЫЧНЫЙ ТОВАР
                // ==========================================
                if (item.product) {
                    const currentPrice = item.params?.discount_price
                        ? item.params.discount_price
                        : (item.product.price || 0);

                    const count = item.product?.is_weight_product ? 1 : item.count;
                    const price = item.product?.is_weight_product
                        ? (currentPrice * item.count) / (item.product.weight_config?.step || 100)
                        : currentPrice;

                    return sum + price * count;
                }

                // ==========================================
                // ВАРИАНТ КОЛЛЕКЦИИ
                // ==========================================
                if (item.collection) {
                    const variantPrice = this._calculateVariantPrice(item);
                    return sum + variantPrice * (item.count || 1);
                }

                return sum;
            }, 0);
        },

        isEmpty: (state) => !state.basket_items?.length,

        isProductLoading: (state) => (productId) => {
            return !!state.productActions[String(productId)];
        },

        getItemById: (state) => (productId) => {
            return state.basket_items.find(item => item.product?.id === productId) || null;
        },

        getCollectionById: (state) => (collectionId) => {
            return state.basket_items.find(item => item.collection?.id === collectionId) || null;
        },

        /**
         * 🆕 Подсчёт цены одного варианта коллекции
         * Вынесен во внутренний метод, чтобы переиспользовать в разных местах
         */
        _calculateVariantPrice: () => (basketItem) => {
            if (!basketItem?.collection) return 0;

            const collection = basketItem.collection;
            const params = basketItem.params || {};

            // 1. Фиксированная цена коллекции
            if (collection.pricing_type === 'fixed' && collection.fixed_price) {
                const price = parseFloat(collection.fixed_price) || 0;
                const discount = collection.discount || 0;
                return discount > 0 ? price * (1 - discount / 100) : price;
            }

            // 2. Цена как сумма выбранных товаров
            const selectedIds = params.ids || [];
            let variantPrice = 0;

            // Пытаемся найти товары в collection_categories
            if (collection.collection_categories?.length) {
                collection.collection_categories.forEach(cat => {
                    (cat.products || []).forEach(p => {
                        if (selectedIds.includes(p.id)) {
                            variantPrice += parseFloat(p.price || 0);
                        }
                    });
                });
            }
            // Fallback: плоский массив products (старая структура)
            else if (collection.products?.length) {
                collection.products.forEach(p => {
                    if (selectedIds.includes(p.id)) {
                        variantPrice += parseFloat(p.price || 0);
                    }
                });
            }

            // Применяем скидку коллекции
            const discount = collection.discount || 0;
            if (discount > 0) {
                variantPrice = variantPrice * (1 - discount / 100);
            }

            return variantPrice;
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        async requestDeliveryPriceNew(payload) {
            try {
                const response = await axios.post('/basket/get-delivery-price-new', payload);
                return response.data;
            } catch (error) {
                console.error('[BasketStore] Ошибка расчета доставки:', error);
                throw error;
            }
        },

        setInitialData(data) {
            if (!data) return;
            this.items = data.items || [];
            this.itemsCount = data.items_count || 0;
            this.totalPrice = data.total_price || 0;
            this.isHydrated = true;
        },

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
        // ТОВАРЫ
        // ==========================================
        async addProductToCart(productId) {
            this.productActions[String(productId)] = 'inc';

            const cartItem = this.basket_items.find(item => item.product?.id === productId);
            if (cartItem) cartItem.count++;

            try {
                const response = await axios.post(`${BASE}/inc-product`, {
                    product_id: productId,
                });
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                if (cartItem) cartItem.count--;
                console.error('[Basket Store] Ошибка добавления товара:', err);
                throw err;
            } finally {
                delete this.productActions[String(productId)];
            }
        },

        async removeProductFromCart(productId) {
            this.productActions[String(productId)] = 'dec';

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

        async incQuantity(productId) {
            this.productActions[String(productId)] = 'inc';
            const cartItem = this.basket_items.find(item => item.product?.id === productId);
            if (cartItem) cartItem.count++;

            try {
                const response = await axios.post(`${BASE}/increment/${productId}`);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                if (cartItem) cartItem.count--;
                console.error('[Basket Store] Ошибка увеличения количества:', err);
                throw err;
            } finally {
                delete this.productActions[String(productId)];
            }
        },

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

        async removeProduct(productId) {
            this.productActions[String(productId)] = 'remove';
            const previousItems = [...this.basket_items];
            const removedIndex = this.basket_items.findIndex(item => item.product?.id === productId);
            const removedItem = removedIndex !== -1 ? this.basket_items[removedIndex] : null;

            if (removedIndex !== -1) this.basket_items.splice(removedIndex, 1);

            try {
                const response = await axios.delete(`${BASE}/remove/${productId}`);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
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
        // 🆕 КОЛЛЕКЦИИ: ВАРИАНТЫ
        // ==========================================

        /**
         * 🆕 Добавление НОВОГО варианта сборки коллекции в корзину.
         *
         * В отличие от addCollectionToCart, этот метод ВСЕГДА создаёт отдельную
         * строку в корзине с уникальным variant_id, даже если эта коллекция
         * уже есть в корзине в другом варианте.
         *
         * @param {Object} variant
         *   {
         *     collection: { id, name, ... },
         *     selected_products: [{ product, category_name, category_id }],
         *     total_price: 500,
         *   }
         */
        async addCollectionVariantToCart(variant) {
            if (!variant?.collection?.id) {
                throw new Error('variant.collection.id обязателен');
            }

            const collectionId = variant.collection.id;
            const actionKey = `coll-var-${collectionId}-${Date.now()}`;
            this.productActions[actionKey] = 'add-variant';

            // Генерируем уникальный ID варианта на клиенте
            const variantId = `cv_${collectionId}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;

            // Собираем IDs выбранных товаров
            const ids = (variant.selected_products || []).map(sp => sp.product?.id || sp.id).filter(Boolean);

            // Оптимистичное добавление
            const optimisticItem = {
                id: `temp_${variantId}`,
                collection: variant.collection,
                count: 1,
                params: {
                    variant_id: variantId,
                    ids: ids,
                    selected_products: variant.selected_products,
                    total_price: variant.total_price || 0,
                },
            };
            this.basket_items.push(optimisticItem);

            try {
                const response = await axios.post(`${BASE}/inc-collection-variant`, {
                    collection_id: collectionId,
                    variant_id: variantId,
                    product_collection: variant,
                });
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                // Откат: удаляем оптимистично добавленный элемент
                const tempIndex = this.basket_items.findIndex(i => i.id === optimisticItem.id);
                if (tempIndex !== -1) this.basket_items.splice(tempIndex, 1);
                console.error('[Basket Store] Ошибка добавления варианта коллекции:', err);
                throw err;
            } finally {
                delete this.productActions[actionKey];
            }
        },

        /**
         * 🆕 Увеличить количество конкретного варианта коллекции
         */
        async incCollectionVariantQuantity(variantId) {
            if (!variantId) throw new Error('variantId обязателен');

            this.productActions[String(variantId)] = 'inc-variant';

            const item = this.basket_items.find(i => i.params?.variant_id === variantId);
            if (item) item.count++;

            try {
                const response = await axios.post(`${BASE}/inc-collection-variant`, {
                    variant_id: variantId,
                });
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                if (item) item.count--;
                console.error('[Basket Store] Ошибка увеличения варианта:', err);
                throw err;
            } finally {
                delete this.productActions[String(variantId)];
            }
        },

        /**
         * 🆕 Уменьшить количество конкретного варианта коллекции
         */
        async decCollectionVariantQuantity(variantId) {
            if (!variantId) throw new Error('variantId обязателен');

            this.productActions[String(variantId)] = 'dec-variant';

            const item = this.basket_items.find(i => i.params?.variant_id === variantId);
            if (item && item.count > 1) {
                item.count--;
            } else if (item && item.count === 1) {
                const index = this.basket_items.indexOf(item);
                this.basket_items.splice(index, 1);
            }

            try {
                const response = await axios.post(`${BASE}/dec-collection-variant`, {
                    variant_id: variantId,
                });
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка уменьшения варианта:', err);
                throw err;
            } finally {
                delete this.productActions[String(variantId)];
            }
        },

        /**
         * 🆕 Полностью удалить конкретный вариант коллекции из корзины
         */
        async removeCollectionVariant(variantId) {
            if (!variantId) throw new Error('variantId обязателен');

            this.productActions[String(variantId)] = 'remove-variant';

            const previousItems = [...this.basket_items];
            const removedIndex = this.basket_items.findIndex(i => i.params?.variant_id === variantId);
            const removedItem = removedIndex !== -1 ? this.basket_items[removedIndex] : null;

            if (removedIndex !== -1) this.basket_items.splice(removedIndex, 1);

            try {
                const response = await axios.post(`${BASE}/remove-collection-variant`, {
                    variant_id: variantId,
                });
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                if (removedItem && removedIndex !== -1) {
                    this.basket_items.splice(removedIndex, 0, removedItem);
                }
                console.error('[Basket Store] Ошибка удаления варианта:', err);
                throw err;
            } finally {
                delete this.productActions[String(variantId)];
            }
        },

        /**
         * 🆕 Удалить ВСЕ варианты одной коллекции из корзины
         */
        async removeAllVariantsOfCollection(collectionId) {
            if (!collectionId) throw new Error('collectionId обязателен');

            const actionKey = `coll-all-${collectionId}`;
            this.productActions[actionKey] = 'remove-all-variants';

            const previousItems = [...this.basket_items];
            this.basket_items = this.basket_items.filter(
                item => item.collection?.id !== collectionId
            );

            try {
                const response = await axios.post(`${BASE}/remove-collection-variants`, {
                    collection_id: collectionId,
                });
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                this.basket_items = previousItems;
                console.error('[Basket Store] Ошибка удаления всех вариантов:', err);
                throw err;
            } finally {
                delete this.productActions[actionKey];
            }
        },

        /**
         * 🆕 Заменить сборку существующего варианта (пересобрать)
         */
        async replaceCollectionVariant(variantId, newVariant) {
            if (!variantId || !newVariant) throw new Error('variantId и newVariant обязательны');

            this.productActions[String(variantId)] = 'replace-variant';

            try {
                const response = await axios.post(`${BASE}/replace-collection-variant`, {
                    variant_id: variantId,
                    product_collection: newVariant,
                });
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка замены варианта:', err);
                throw err;
            } finally {
                delete this.productActions[String(variantId)];
            }
        },

        /**
         * 🆕 Рассчитать цену конкретного варианта коллекции
         * (удобно для использования в компонентах)
         */
        calculateVariantPrice(variantId) {
            const item = this.basket_items.find(i => i.params?.variant_id === variantId);
            if (!item) return 0;
            return this._calculateVariantPrice(item);
        },

        // ==========================================
        // СТАРЫЕ МЕТОДЫ КОЛЛЕКЦИЙ (для обратной совместимости)
        // ==========================================
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
                if (collectionId) delete this.productActions[String(collectionId)];
            }
        },

        async incCollectionQuantity(payload) {
            const collectionId = payload?.id || payload?.collection_id;
            if (collectionId) this.productActions[String(collectionId)] = 'inc-collection';

            try {
                const response = await axios.post(`${BASE}/inc-collection`, payload);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка увеличения подборки:', err);
                throw err;
            } finally {
                if (collectionId) delete this.productActions[String(collectionId)];
            }
        },

        async decCollectionQuantity(payload) {
            const collectionId = payload?.id || payload?.collection_id;
            if (collectionId) this.productActions[String(collectionId)] = 'dec-collection';

            try {
                const response = await axios.post(`${BASE}/dec-collection`, payload);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка уменьшения подборки:', err);
                throw err;
            } finally {
                if (collectionId) delete this.productActions[String(collectionId)];
            }
        },

        async removeCollectionFromCart(payload) {
            const collectionId = payload?.id || payload?.collection_id;
            if (collectionId) this.productActions[String(collectionId)] = 'remove-collection';

            try {
                const response = await axios.post(`${BASE}/dec-collection`, payload);
                this._updateBasketFromResponse(response.data);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка удаления подборки:', err);
                throw err;
            } finally {
                if (collectionId) delete this.productActions[String(collectionId)];
            }
        },

        // ==========================================
        // КОММЕНТАРИИ / ОФОРМЛЕНИЕ / ОЧИСТКА
        // ==========================================
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

        async createCheckoutLink(payload = { deliveryForm: null }) {
            try {
                const response = await axios.post(`${BASE}/checkout-link`, payload.deliveryForm);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка создания ссылки:', err);
                throw err;
            }
        },

        async startCheckout(payload = { deliveryForm: null }) {
            this.isSending = true;
            try {
                const response = await axios.post(`${BASE}/checkout`, payload.deliveryForm);
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

        async useWheelOfFortunePrize(payload = { form: null }) {
            try {
                const response = await axios.post(`${BASE}/use-wheel-of-fortune-prize`, payload.form);
                return response.data;
            } catch (err) {
                console.error('[Basket Store] Ошибка использования приза:', err);
                throw err;
            }
        },

        async clearCart() {
            const previousItems = [...this.basket_items];
            const previousPaginate = this.basket_items_paginate_object;

            this.basket_items = [];
            this.basket_items_paginate_object = null;

            try {
                await axios.delete(`${BASE}/clear`);
            } catch (err) {
                this.basket_items = previousItems;
                this.basket_items_paginate_object = previousPaginate;
                console.error('[Basket Store] Ошибка очистки корзины:', err);
                throw err;
            }
        },

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
