import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/shop/collections';
const STORAGE_KEY = 'collection_cart_variants';

export const useCollectionsStore = defineStore('collections', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        collections: [],
        collectionsPaginate: null,
        currentCollection: null,

        // Состояния загрузки
        isLoading: false,
        isHydrated: false,
        isSingleLoading: false,
        isLoadingMore: false,
        isListLoading: false,

        // Действия над коллекциями: { [id]: 'toggle' | 'delete' | ... }
        collectionActions: {},
        // Действия над категориями: { [id]: 'update' | 'delete' | ... }
        categoryActions: {},

        // Корзина вариантов коллекций
        cartVariants: {},

        // UI
        searchQuery: '',

        // Ошибки
        lastError: null,
        errors: [],

        // Синхронизация
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        getCollections: (state) => state.collections || [],
        getCurrentCollection: (state) => state.currentCollection,
        getCollectionsPaginate: (state) => state.collectionsPaginate,

        getCollectionById: (state) => (id) =>
            state.collections.find(c => String(c.id) === String(id)) || null,

        sortedCollections: (state) => {
            return [...(state.collections || [])].sort((a, b) => {
                if ((a.order_position || 0) !== (b.order_position || 0)) {
                    return (a.order_position || 0) - (b.order_position || 0);
                }
                const dateA = new Date(a.created_at || 0);
                const dateB = new Date(b.created_at || 0);
                return dateB - dateA;
            });
        },

        activeCollections: (state) =>
            (state.collections || []).filter(c => c.is_active && !c.in_stop_list),

        filteredCollections: (state) => {
            if (!state.searchQuery) return state.collections || [];
            const query = state.searchQuery.toLowerCase();
            return (state.collections || []).filter(c =>
                c.name?.toLowerCase().includes(query) ||
                c.description?.toLowerCase().includes(query) ||
                c.short_description?.toLowerCase().includes(query)
            );
        },

        collectionsCount: (state) => state.collections?.length || 0,

        isCollectionLoading: (state) => (id) => !!state.collectionActions[String(id)],
        isCategoryLoading: (state) => (id) => !!state.categoryActions[String(id)],

        getVariantsForCollection: (state) => (collectionId) => {
            return state.cartVariants[String(collectionId)] || [];
        },

        totalCartVariantsCount: (state) => {
            return Object.values(state.cartVariants || {}).reduce(
                (sum, variants) => sum + (variants?.length || 0), 0
            );
        },

        totalCartVariantsPrice: (state) => {
            return Object.values(state.cartVariants || {}).reduce((sum, variants) => {
                return sum + variants.reduce((s, v) => s + (v.totalPrice || 0), 0);
            }, 0);
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ==========================================
        // ВСПОМОГАТЕЛЬНЫЕ
        // ==========================================

        /**
         * Обновить или добавить коллекцию в общий список
         */
        _upsertCollection(collection) {
            if (!collection?.id) return;
            const index = this.collections.findIndex(
                c => String(c.id) === String(collection.id)
            );
            if (index !== -1) {
                this.collections[index] = { ...this.collections[index], ...collection };
            } else {
                this.collections.unshift(collection);
            }
        },

        /**
         * Удалить коллекцию из списка
         */
        _removeCollectionFromList(id) {
            const index = this.collections.findIndex(
                c => String(c.id) === String(id)
            );
            if (index !== -1) {
                this.collections.splice(index, 1);
            }
        },

        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка активных коллекций для фронта
         * GET /shop/collections
         */
        async loadCollections(payload = { page: 1, size: 20, partner_id: null }) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await axios.get(BASE, {
                    params: {
                        page: payload.page || 1,
                        size: payload.size || 20,
                        partner_id: payload.partner_id || null,
                    },
                });

                const dataObject = response.data;
                const items = dataObject.data || dataObject || [];
                this.collections = Array.isArray(items) ? items : [];

                if (dataObject.meta) {
                    this.collectionsPaginate = dataObject.meta;
                }

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                return this.collections;
            } catch (err) {
                console.error('[Collections Store] Ошибка загрузки коллекций:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить коллекции';
                this.errors = err.response?.data?.errors || [];
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка списка с пагинацией (для админки)
         * POST /shop/collections/list
         */
        async loadCollectionsList(payload = { page: 1, size: 20, search: null, filters: {} }) {
            this.isListLoading = true;
            this.lastError = null;

            try {
                const response = await axios.post(`${BASE}/list`, null, {
                    params: {
                        search: payload.search || null,
                        size: payload.size || 20,
                        page: payload.page || 1,
                        ...payload.filters,
                    },
                });

                const dataObject = response.data;
                const items = dataObject.data || [];
                this.collections = Array.isArray(items) ? items : [];

                if (dataObject.meta) {
                    this.collectionsPaginate = dataObject.meta;
                }

                return dataObject;
            } catch (err) {
                console.error('[Collections Store] Ошибка загрузки списка:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить список';
                throw err;
            } finally {
                this.isListLoading = false;
            }
        },

        /**
         * Загрузка одной коллекции с деталями
         * GET /shop/collections/{id}
         */
        async loadCollection(id) {
            if (!id) throw new Error('Не указан ID коллекции');

            this.isSingleLoading = true;
            this.collectionActions[String(id)] = 'load';

            try {
                const response = await axios.get(`${BASE}/${id}`);
                const collection = response.data?.data || response.data;

                if (collection?.id) {
                    this._upsertCollection(collection);
                    this.currentCollection = collection;
                }

                return collection;
            } catch (err) {
                console.error('[Collections Store] Ошибка загрузки коллекции:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить коллекцию';
                throw err;
            } finally {
                this.isSingleLoading = false;
                delete this.collectionActions[String(id)];
            }
        },

        /**
         * Подгрузить ещё (пагинация через list)
         * POST /shop/collections/list
         */
        async loadMoreCollections(payload = { page: 2, search: null, filters: {} }) {
            this.isLoadingMore = true;

            try {
                const response = await axios.post(`${BASE}/list`, null, {
                    params: {
                        page: payload.page || 2,
                        size: payload.size || 20,
                        search: payload.search || null,
                        ...payload.filters,
                    },
                });

                const newItems = response.data?.data || [];
                if (newItems.length > 0) {
                    this.collections.push(...newItems);
                }

                if (response.data?.meta) {
                    this.collectionsPaginate = response.data.meta;
                }

                return newItems;
            } catch (err) {
                console.error('[Collections Store] Ошибка дозагрузки:', err);
                throw err;
            } finally {
                this.isLoadingMore = false;
            }
        },

        // ==========================================
        // CRUD: КОЛЛЕКЦИИ
        // ==========================================

        /**
         * Создание коллекции
         * POST /shop/collections
         */
        async createCollection(formData) {
            this.lastError = null;

            try {
                const response = await axios.post(BASE, formData, {
                    headers: formData instanceof FormData
                        ? { 'Content-Type': 'multipart/form-data' }
                        : {},
                });

                const newCollection = response.data?.data || response.data;
                if (newCollection?.id) {
                    this._upsertCollection(newCollection);
                }

                return newCollection;
            } catch (err) {
                console.error('[Collections Store] Ошибка создания коллекции:', err);
                this.lastError = err.response?.data?.message || 'Не удалось создать коллекцию';
                this.errors = err.response?.data?.errors || [];
                throw err;
            }
        },

        /**
         * Обновление коллекции
         * POST /shop/collections
         */
        async updateCollection(formData) {
            this.lastError = null;

            try {
                const response = await axios.post(BASE, formData, {
                    headers: formData instanceof FormData
                        ? { 'Content-Type': 'multipart/form-data' }
                        : {},
                });

                const updated = response.data?.data || response.data;
                if (updated?.id) {
                    this._upsertCollection(updated);
                    if (this.currentCollection?.id === updated.id) {
                        this.currentCollection = updated;
                    }
                }

                return updated;
            } catch (err) {
                console.error('[Collections Store] Ошибка обновления коллекции:', err);
                this.lastError = err.response?.data?.message || 'Не удалось обновить коллекцию';
                this.errors = err.response?.data?.errors || [];
                throw err;
            }
        },

        /**
         * Удаление коллекции (оптимистично)
         * DELETE /shop/collections/{id}
         */
        async destroyCollection(id) {
            if (!id) throw new Error('Не указан ID коллекции');

            this.collectionActions[String(id)] = 'delete';

            const previousCollections = [...this.collections];
            const removedIndex = this.collections.findIndex(
                c => String(c.id) === String(id)
            );
            const removed = removedIndex !== -1 ? this.collections[removedIndex] : null;

            if (removedIndex !== -1) {
                this.collections.splice(removedIndex, 1);
            }

            try {
                const response = await axios.delete(`${BASE}/${id}`);
                return response.data;
            } catch (err) {
                // Откат
                if (removed && removedIndex !== -1) {
                    this.collections.splice(removedIndex, 0, removed);
                }
                console.error('[Collections Store] Ошибка удаления коллекции:', err);
                throw err;
            } finally {
                delete this.collectionActions[String(id)];
            }
        },

        /**
         * Переключение активности (оптимистично)
         * POST /shop/collections/{id}/toggle-active
         */
        async toggleActive(id) {
            if (!id) throw new Error('Не указан ID коллекции');

            this.collectionActions[String(id)] = 'toggle-active';

            const collection = this.getCollectionById(id);
            const previousState = collection?.is_active;

            // Оптимистичное переключение
            if (collection) {
                collection.is_active = !collection.is_active;
            }

            try {
                const response = await axios.post(`${BASE}/${id}/toggle-active`);
                const updated = response.data?.data || response.data;
                if (updated?.id) {
                    this._upsertCollection(updated);
                }
                return updated;
            } catch (err) {
                // Откат
                if (collection) {
                    collection.is_active = previousState;
                }
                console.error('[Collections Store] Ошибка переключения активности:', err);
                throw err;
            } finally {
                delete this.collectionActions[String(id)];
            }
        },

        /**
         * Переключение стоп-листа (оптимистично)
         * POST /shop/collections/{id}/toggle-stop-list
         */
        async toggleStopList(id) {
            if (!id) throw new Error('Не указан ID коллекции');

            this.collectionActions[String(id)] = 'toggle-stop-list';

            const collection = this.getCollectionById(id);
            const previousState = collection?.in_stop_list;

            if (collection) {
                collection.in_stop_list = !collection.in_stop_list;
            }

            try {
                const response = await axios.post(`${BASE}/${id}/toggle-stop-list`);
                const updated = response.data?.data || response.data;
                if (updated?.id) {
                    this._upsertCollection(updated);
                }
                return updated;
            } catch (err) {
                if (collection) {
                    collection.in_stop_list = previousState;
                }
                console.error('[Collections Store] Ошибка переключения стоп-листа:', err);
                throw err;
            } finally {
                delete this.collectionActions[String(id)];
            }
        },

        /**
         * Дублировать коллекцию
         * POST /shop/collections/{id}/duplicate
         */
        async duplicateCollection(id) {
            if (!id) throw new Error('Не указан ID коллекции');

            this.collectionActions[String(id)] = 'duplicate';

            try {
                const response = await axios.post(`${BASE}/${id}/duplicate`);
                const duplicate = response.data?.data || response.data;

                if (duplicate?.id) {
                    this._upsertCollection(duplicate);
                }

                return duplicate;
            } catch (err) {
                console.error('[Collections Store] Ошибка дублирования:', err);
                throw err;
            } finally {
                delete this.collectionActions[String(id)];
            }
        },

        /**
         * Удалить все коллекции
         * POST /shop/collections/remove-all
         */
        async removeAllCollections() {
            try {
                const response = await axios.post(`${BASE}/remove-all`);
                this.collections = [];
                this.currentCollection = null;
                this.collectionsPaginate = null;
                return response.data;
            } catch (err) {
                console.error('[Collections Store] Ошибка массового удаления:', err);
                throw err;
            }
        },

        // ==========================================
        // КАТЕГОРИИ ВНУТРИ КОЛЛЕКЦИИ
        // ==========================================

        /**
         * Добавить категорию в коллекцию
         * POST /shop/collections/{collectionId}/categories
         */
        async addCategory(collectionId, data) {
            if (!collectionId) throw new Error('Не указан ID коллекции');

            this.collectionActions[String(collectionId)] = 'add-category';

            try {
                const response = await axios.post(
                    `${BASE}/${collectionId}/categories`,
                    data
                );

                const updatedCollection = response.data?.data || response.data;
                if (updatedCollection?.id) {
                    this._upsertCollection(updatedCollection);
                    if (this.currentCollection?.id === updatedCollection.id) {
                        this.currentCollection = updatedCollection;
                    }
                }

                return updatedCollection;
            } catch (err) {
                console.error('[Collections Store] Ошибка добавления категории:', err);
                throw err;
            } finally {
                delete this.collectionActions[String(collectionId)];
            }
        },

        /**
         * Обновить категорию
         * POST /shop/collections/categories/{categoryId}
         */
        async updateCategory(categoryId, data) {
            if (!categoryId) throw new Error('Не указан ID категории');

            this.categoryActions[String(categoryId)] = 'update';

            try {
                const response = await axios.post(
                    `${BASE}/categories/${categoryId}`,
                    data
                );

                const updatedCollection = response.data?.data || response.data;
                if (updatedCollection?.id) {
                    this._upsertCollection(updatedCollection);
                    if (this.currentCollection?.id === updatedCollection.id) {
                        this.currentCollection = updatedCollection;
                    }
                }

                return updatedCollection;
            } catch (err) {
                console.error('[Collections Store] Ошибка обновления категории:', err);
                throw err;
            } finally {
                delete this.categoryActions[String(categoryId)];
            }
        },

        /**
         * Удалить категорию из коллекции
         * DELETE /shop/collections/categories/{categoryId}
         */
        async removeCategory(categoryId) {
            if (!categoryId) throw new Error('Не указан ID категории');

            this.categoryActions[String(categoryId)] = 'delete';

            try {
                const response = await axios.delete(
                    `${BASE}/categories/${categoryId}`
                );

                const updatedCollection = response.data?.data || response.data;
                if (updatedCollection?.id) {
                    this._upsertCollection(updatedCollection);
                    if (this.currentCollection?.id === updatedCollection.id) {
                        this.currentCollection = updatedCollection;
                    }
                }

                return updatedCollection;
            } catch (err) {
                console.error('[Collections Store] Ошибка удаления категории:', err);
                throw err;
            } finally {
                delete this.categoryActions[String(categoryId)];
            }
        },

        // ==========================================
        // ТОВАРЫ В КАТЕГОРИЯХ
        // ==========================================

        /**
         * Добавить товары в категорию
         * POST /shop/collections/categories/{categoryId}/products
         */
        async addProductsToCategory(categoryId, productIds) {
            if (!categoryId) throw new Error('Не указан ID категории');

            this.categoryActions[String(categoryId)] = 'add-products';

            try {
                const response = await axios.post(
                    `${BASE}/categories/${categoryId}/products`,
                    { product_ids: productIds }
                );

                const updatedCollection = response.data?.data || response.data;
                if (updatedCollection?.id) {
                    this._upsertCollection(updatedCollection);
                    if (this.currentCollection?.id === updatedCollection.id) {
                        this.currentCollection = updatedCollection;
                    }
                }

                return updatedCollection;
            } catch (err) {
                console.error('[Collections Store] Ошибка добавления товаров:', err);
                throw err;
            } finally {
                delete this.categoryActions[String(categoryId)];
            }
        },

        /**
         * Удалить товар из категории
         * DELETE /shop/collections/categories/{categoryId}/products/{productId}
         */
        async removeProductFromCategory(categoryId, productId) {
            if (!categoryId || !productId) {
                throw new Error('categoryId и productId обязательны');
            }

            this.categoryActions[String(categoryId)] = 'remove-product';

            try {
                const response = await axios.delete(
                    `${BASE}/categories/${categoryId}/products/${productId}`
                );

                const updatedCollection = response.data?.data || response.data;
                if (updatedCollection?.id) {
                    this._upsertCollection(updatedCollection);
                    if (this.currentCollection?.id === updatedCollection.id) {
                        this.currentCollection = updatedCollection;
                    }
                }

                return updatedCollection;
            } catch (err) {
                console.error('[Collections Store] Ошибка удаления товара:', err);
                throw err;
            } finally {
                delete this.categoryActions[String(categoryId)];
            }
        },

        /**
         * Изменить порядок товаров в категории
         * POST /shop/collections/categories/{categoryId}/products/reorder
         */
        async reorderProducts(categoryId, order) {
            if (!categoryId) throw new Error('Не указан ID категории');

            this.categoryActions[String(categoryId)] = 'reorder';

            try {
                const response = await axios.post(
                    `${BASE}/categories/${categoryId}/products/reorder`,
                    { order }
                );

                const updatedCollection = response.data?.data || response.data;
                if (updatedCollection?.id) {
                    this._upsertCollection(updatedCollection);
                    if (this.currentCollection?.id === updatedCollection.id) {
                        this.currentCollection = updatedCollection;
                    }
                }

                return updatedCollection;
            } catch (err) {
                console.error('[Collections Store] Ошибка сортировки товаров:', err);
                throw err;
            } finally {
                delete this.categoryActions[String(categoryId)];
            }
        },

        // ==========================================
        // КОРЗИНА ВАРИАНТОВ
        // ==========================================

        initCartFromStorage() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                if (raw) {
                    this.cartVariants = JSON.parse(raw);
                }
            } catch (e) {
                console.warn('[Collections Store] Не удалось загрузить корзину:', e);
                this.cartVariants = {};
            }
        },

        _saveCartToStorage() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.cartVariants));
            } catch (e) {
                console.warn('[Collections Store] Не удалось сохранить корзину:', e);
            }
        },

        addVariantToCart(variant) {
            if (!variant?.collection_id) {
                throw new Error('collection_id обязателен');
            }

            const key = String(variant.collection_id);

            if (!this.cartVariants[key]) {
                this.cartVariants[key] = [];
            }

            const newVariant = {
                ...variant,
                variantId: `${key}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
                totalPrice: variant.total_price || 0,
                addedAt: new Date().toISOString(),
            };

            this.cartVariants[key].push(newVariant);
            this._saveCartToStorage();

            return newVariant;
        },

        removeVariantFromCart(collectionId, variantId) {
            const key = String(collectionId);
            if (!this.cartVariants[key]) return;

            this.cartVariants[key] = this.cartVariants[key].filter(
                v => v.variantId !== variantId
            );

            if (this.cartVariants[key].length === 0) {
                delete this.cartVariants[key];
            }

            this._saveCartToStorage();
        },

        removeAllVariantsForCollection(collectionId) {
            delete this.cartVariants[String(collectionId)];
            this._saveCartToStorage();
        },

        clearAllVariants() {
            this.cartVariants = {};
            this._saveCartToStorage();
        },

        // ==========================================
        // РАСЧЁТ ЦЕН
        // ==========================================

        calculateCollectionPrice(collection, selections = {}) {
            if (!collection) return 0;

            if (collection.pricing_type === 'fixed' && collection.fixed_price) {
                return parseFloat(collection.fixed_price) || 0;
            }

            const categories = collection.collection_categories || [];
            let total = 0;

            categories.forEach(cat => {
                const selectedIds = selections[String(cat.id)] || [];
                const products = cat.products || [];

                products.forEach(p => {
                    if (selectedIds.includes(p.id)) {
                        total += parseFloat(p.price || 0);
                    }
                });
            });

            if (collection.discount && collection.discount > 0) {
                total = total * (1 - collection.discount / 100);
            }

            return Math.round(total * 100) / 100;
        },

        validateCollectionSelection(collection, selections = {}) {
            if (!collection?.collection_categories) return false;

            return collection.collection_categories.every(cat => {
                const selected = selections[String(cat.id)] || [];
                const rule = cat.selection_rule || 'one';
                const products = cat.products || [];

                if (products.length === 0) return true;

                switch (rule) {
                    case 'one':
                        return selected.length === 1;
                    case 'all':
                        return selected.length === products.length;
                    case 'several':
                        return selected.length > 0;
                    default:
                        return selected.length > 0;
                }
            });
        },

        // ==========================================
        // UI
        // ==========================================

        setSearch(query) {
            this.searchQuery = query || '';
        },

        clearCollections() {
            this.collections = [];
            this.currentCollection = null;
            this.collectionsPaginate = null;
            this.searchQuery = '';
        },

        // ==========================================
        // СБРОС
        // ==========================================
        $reset() {
            this.collections = [];
            this.collectionsPaginate = null;
            this.currentCollection = null;

            this.isLoading = false;
            this.isHydrated = false;
            this.isSingleLoading = false;
            this.isLoadingMore = false;
            this.isListLoading = false;

            this.collectionActions = {};
            this.categoryActions = {};
            this.cartVariants = {};
            this.searchQuery = '';

            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;
        },
    },
});
