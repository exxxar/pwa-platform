import {defineStore} from 'pinia';
import axios from 'axios';

const BASE = '/shop/products';

export const useProductsStore = defineStore('products', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        products: [],
        categories: [],
        products_paginate_object: null,
        categories_paginate_object: null,

        // 🆕 Данные меню (из старого стора)
        collections: [],

        collectionsPaginate: null,

        // Состояния загрузки по секциям
        isLoading: false,
        isHydrated: false,
        isCategoriesLoading: false,
        isCategoriesHydrated: false,
        isRandomLoading: false,
        isRecommendedLoading: false,
        isByCategoryLoading: false,
        isInCategoryLoading: false,
        isModuleDataLoading: false,
        isLoadingMore: false, // 🆕 Добавлено для пагинации внутри категории

        // Действия над конкретными товарами/категориями
        productActions: {},
        categoryActions: {},

        // Ошибки
        lastError: null,
        errors: [],

        // Время последней синхронизации
        lastSyncAt: null,

        // 🆕 UI состояние меню
        searchQuery: '',
        selectedCategory: null,
        selectedPartner: null,
        extraCharge: 0,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        getSelectedPartner: (state) => state.selectedPartner || null,
        getProducts: (state) => state.products || [],
        getCategories: (state) => state.categories || [],
        getProductsPaginateObject: (state) => state.products_paginate_object || null,
        getCategoriesPaginateObject: (state) => state.categories_paginate_object || null,

        getProductById: (state) => (id) => state.products.find(item => String(item.id) === String(id)) || null,
        getCategoryById: (state) => (id) => state.categories.find(item => String(item.id) === String(id)) || null,

        sortedProducts: (state) => {
            return [...(state.products || [])].sort((a, b) => {
                if (a.is_recommended && !b.is_recommended) return -1;
                if (!a.is_recommended && b.is_recommended) return 1;
                const dateA = new Date(a.created_at || 0);
                const dateB = new Date(b.created_at || 0);
                return dateB - dateA;
            });
        },

        activeProducts: (state) => (state.products || []).filter(p => !p.in_stop_list_at),
        stopListProducts: (state) => (state.products || []).filter(p => !!p.in_stop_list_at),
        recommendedProducts: (state) => (state.products || []).filter(p => p.is_recommended),
        activeCategories: (state) => (state.categories || []).filter(c => c.is_active !== false),
        recommendedCategories: (state) => (state.categories || []).filter(c => c.is_recommended),

        isProductLoading: (state) => (id) => !!state.productActions[String(id)],
        isCategoryLoading: (state) => (id) => !!state.categoryActions[String(id)],

        productsCount: (state) => state.products?.length || 0,
        categoriesCount: (state) => state.categories?.length || 0,

        // 🆕 ГЕТТЕРЫ ИЗ СТАРОГО СТОРА
        /**
         * Фильтрация категорий и товаров внутри них по поисковому запросу
         */
        filteredProducts: (state) => {
            if (!state.searchQuery) return state.categories || [];

            const query = state.searchQuery.toLowerCase();
            return (state.categories || [])
                .map(cat => {
                    const filteredProducts = (cat.products || []).filter(product =>
                        product.name?.toLowerCase().includes(query)
                    );
                    return {
                        ...cat,
                        products: filteredProducts,
                        products_count: filteredProducts.length
                    };
                })
                .filter(cat => cat.products.length > 0);
        },
        filteredCategories: (state) => {
            if (!state.searchQuery) return state.categories;

            const query = state.searchQuery.toLowerCase();
            return state.categories
                .map(cat => {
                    const filteredProducts = (cat.products || []).filter(product =>
                        product.name?.toLowerCase().includes(query)
                    );
                    return {
                        ...cat,
                        products: filteredProducts,
                        products_count: filteredProducts.length
                    };
                })
                .filter(cat => cat.products.length > 0);
        },

        /**
         * Общее количество товаров во всех категориях
         */
        totalProductsCount: (state) => {
            return state.categories.reduce((sum, cat) => sum + (cat.products_count || 0), 0);
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // 🆕 Новый метод для загрузки товаров по массиву ID
        async fetchProductsByIds(ids) {
            if (!ids || ids.length === 0) return;

            // Убираем дубликаты и оставляем только те ID, которых еще нет в кэше
            const uniqueIds = [...new Set(ids)];
            const missingIds = uniqueIds.filter(id => !this.productsById[id]);

            if (missingIds.length === 0) {
                return; // Все нужные товары уже загружены
            }

            this.isLoadingByIds = true;
            try {
                const response = await axios.post('/shop/products/by-ids', {
                    ids: missingIds
                });

                // Сохраняем в кэш по ID для мгновенного доступа
                response.data.forEach(product => {
                    this.productsById[product.id] = product;
                });
            } catch (error) {
                console.error('Ошибка загрузки товаров по ID:', error);
            } finally {
                this.isLoadingByIds = false;
            }
        },
        /**
         * 🆕 Установить рекомендации
         */
        setRecommendations(products) {
            this.recommendations = products || [];
        },

        /**
         * 🆕 Установить общее количество
         */
        setTotalCount(count) {
            this.totalCount = count || 0;
        },
        // ==========================================
        // ТОВАРЫ: ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка списка товаров
         */
        async loadProducts(payload = {dataObject: {}, page: 0, size: 20}) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const page = payload.page || 0;
                const size = payload.size || 20;
                const link = `${BASE}?page=${page}&size=${size}`;

                const response = await axios.post(link, payload.dataObject || {});
                const dataObject = response.data;

                this.products = dataObject.data || [];
                const {data, ...paginate} = dataObject;
                this.products_paginate_object = paginate;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                return paginate;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки товаров:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить товары';
                this.errors = err.response?.data?.errors || [];
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка одного товара
         */
        async loadProduct(payload) {
            try {
                const link = `${BASE}/${payload.dataObject.productId}`;
                const response = await axios.post(link);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки товара:', err);
                throw err;
            }
        },

        /**
         * Загрузка случайных товаров
         */
        async loadRandomProducts() {
            this.isRandomLoading = true;

            try {
                const response = await axios.post(`${BASE}/random`, {});
                const dataObject = response.data;

                this.products = dataObject.data || [];
                const {data, ...paginate} = dataObject;
                this.products_paginate_object = paginate;

                return paginate;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки случайных товаров:', err);
                throw err;
            } finally {
                this.isRandomLoading = false;
            }
        },

        /**
         * Загрузка рекомендуемых товаров
         */
        async loadRecommendedProducts() {
            this.isRecommendedLoading = true;

            try {
                const response = await axios.post(`${BASE}/load-recommended-products`);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки рекомендуемых:', err);
                throw err;
            } finally {
                this.isRecommendedLoading = false;
            }
        },

        /**
         * Загрузка товаров по категориям (для меню)
         */
        /**
         * Загрузка товаров по категориям (для меню)
         */
        /**
         * Загрузка товаров по категориям (для меню)
         */
        async loadProductsByCategory(partnerId = null) {
            this.isLoading = true;
            try {
                const response = await axios.post('/shop/products/by-category', {
                    partner_id: partnerId
                });

                // 🔥 ЖЕСТКИЙ ДЕБАГ: смотрим, что вернул сервер
                console.log('🔥 RAW RESPONSE:', response);
                console.log('🔥 response.data:', response.data);

                // Пытаемся извлечь массив, проверяя оба возможных варианта структуры
                const payload = response.data?.data || response.data || [];

                console.log('🔥 ИТОГОВЫЙ МАССИВ ДЛЯ CATEGORIES:', payload);

                // Гарантируем, что это именно массив
                this.categories = Array.isArray(payload) ? payload : [];

            } catch (error) {
                console.error('[Products Store] Ошибка загрузки товаров по категориям:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка дополнительных товаров (пагинация внутри категории)
         */
        /**
         * Загрузка дополнительных товаров (пагинация внутри категории)
         */
        async loadMoreProducts(categoryId, offset, partnerId = null) {
            this.isLoadingMore = true;
            try {
                const response = await axios.post('/shop/products/more-by-category', {
                    category_id: categoryId,
                    offset: offset,
                    partner_id: partnerId
                });

                const responseData = response.data?.data || response.data || [];
                const newProducts = Array.isArray(responseData) ? responseData : [];

                console.log('🔄 [Store] Загружено новых товаров:', newProducts.length);

                const category = this.categories.find(c => String(c.id) === String(categoryId));

                if (category) {
                    console.log('✅ [Store] Категория найдена:', category.name);
                    console.log('📊 [Store] Было товаров:', category.products.length);

                    // 🎯 ЗАЩИТА ОТ ДУБЛЕЙ: собираем Set уже существующих ID
                    const existingIds = new Set(category.products.map(p => p.id));

                    // Фильтруем: оставляем только те, которых ещё нет
                    const uniqueNewProducts = newProducts.filter(p => {
                        if (!p || !p.id) return false;
                        return !existingIds.has(p.id);
                    });

                    console.log('🔒 [Store] Уникальных новых:', uniqueNewProducts.length,
                        '(отфильтровано дублей:', newProducts.length - uniqueNewProducts.length, ')');

                    // Пушим только уникальные
                    if (uniqueNewProducts.length > 0) {
                        category.products.push(...uniqueNewProducts);
                    }

                    // 🎯 Если пришло меньше, чем просили (или все были дублями) —
                    // значит больше грузить нечего. Обновляем products_count до текущего length
                    if (newProducts.length < 12 || uniqueNewProducts.length === 0) {
                        // Корректируем products_count, чтобы кнопка "Загрузить ещё" исчезла
                        category.products_count = category.products.length;
                        console.log('🏁 [Store] Все товары загружены. products_count обновлён до:', category.products_count);
                    }

                    console.log('🎉 [Store] Стало товаров:', category.products.length);
                } else {
                    console.error('❌ [Store] Категория с ID', categoryId, 'не найдена!');
                }

                return newProducts.length;
            } catch (error) {
                console.error('[Products Store] Ошибка загрузки:', error);
                throw error;
            } finally {
                this.isLoadingMore = false;
            }
        },


        // ==========================================
        // ТОВАРЫ: CRUD
        // ==========================================

        /**
         * Создание товара
         */
        async saveProduct(payload = {productForm: null}) {
            this.lastError = null;

            try {
                const response = await axios.post(`${BASE}/add-product`, payload.productForm);
                const newProduct = response.data?.data || response.data;

                // Добавляем в начало списка
                if (newProduct?.id) {
                    this.products.unshift(newProduct);
                }

                return newProduct;
            } catch (err) {
                console.error('[Products Store] Ошибка создания товара:', err);
                this.lastError = err.response?.data?.message || 'Не удалось создать товар';
                throw err;
            }
        },

        /**
         * Удаление товара (оптимистично)
         */
        async removeShopProduct(id) {
            if (!id) throw new Error('Не указан ID товара');

            this.productActions[String(id)] = 'delete';

            // Сохраняем для отката
            const previousProducts = [...this.products];
            const removedIndex = this.products.findIndex(p => String(p.id) === String(id));
            const removedProduct = removedIndex !== -1 ? this.products[removedIndex] : null;

            // Оптимистично удаляем
            if (removedIndex !== -1) {
                this.products.splice(removedIndex, 1);
            }

            try {
                const response = await axios.delete(`${BASE}/${id}`);
                return response.data;
            } catch (err) {
                // Откат
                if (removedProduct && removedIndex !== -1) {
                    this.products.splice(removedIndex, 0, removedProduct);
                }
                console.error('[Products Store] Ошибка удаления товара:', err);
                this.lastError = err.response?.data?.message || 'Не удалось удалить товар';
                throw err;
            } finally {
                delete this.productActions[String(id)];
            }
        },

        /**
         * Восстановление товара
         */
        async restoreProduct(id) {
            if (!id) throw new Error('Не указан ID товара');

            this.productActions[String(id)] = 'restore';

            try {
                const response = await axios.post(`${BASE}/restore-product/${id}`);
                const restored = response.data?.data || response.data;

                // Обновляем в списке
                if (restored) {
                    const index = this.products.findIndex(p => String(p.id) === String(id));
                    if (index !== -1) {
                        this.products[index] = {...this.products[index], ...restored};
                    } else {
                        this.products.unshift(restored);
                    }
                }

                return restored;
            } catch (err) {
                console.error('[Products Store] Ошибка восстановления товара:', err);
                throw err;
            } finally {
                delete this.productActions[String(id)];
            }
        },

        /**
         * Добавление/удаление из стоп-листа (оптимистично)
         */
        async addToStopListProduct(id) {
            if (!id) throw new Error('Не указан ID товара');

            this.productActions[String(id)] = 'stop-list';

            // Сохраняем предыдущее состояние
            const product = this.getProductById(id);
            const previousState = product?.in_stop_list_at;

            // Оптимистично переключаем
            if (product) {
                product.in_stop_list_at = product.in_stop_list_at ? null : new Date().toISOString();
            }

            try {
                const response = await axios.post(`${BASE}/stop-list-product/${id}`);
                const updated = response.data?.data || response.data;

                // Синхронизируем с сервером
                if (product && updated) {
                    Object.assign(product, updated);
                }

                return updated;
            } catch (err) {
                // Откат
                if (product) {
                    product.in_stop_list_at = previousState;
                }
                console.error('[Products Store] Ошибка стоп-листа:', err);
                throw err;
            } finally {
                delete this.productActions[String(id)];
            }
        },

        /**
         * Изменение статуса рекомендации (оптимистично)
         */
        async changeProductRecommendationStatus(payload) {
            const productId = payload?.product_id || payload?.id;
            if (!productId) throw new Error('Не указан ID товара');

            this.productActions[String(productId)] = 'recommend';

            // Сохраняем предыдущее состояние
            const product = this.getProductById(productId);
            const previousState = product?.is_recommended;

            // Оптимистично переключаем
            if (product) {
                product.is_recommended = !product.is_recommended;
            }

            try {
                const response = await axios.post(`${BASE}/change-recommendation-status`, payload);
                const updated = response.data?.data || response.data;

                if (product && updated) {
                    Object.assign(product, updated);
                }

                return updated;
            } catch (err) {
                // Откат
                if (product) {
                    product.is_recommended = previousState;
                }
                console.error('[Products Store] Ошибка рекомендации:', err);
                throw err;
            } finally {
                delete this.productActions[String(productId)];
            }
        },

        /**
         * Удалить все товары
         */
        async removeAllProducts() {
            try {
                const response = await axios.post(`${BASE}/remove-all-products`);
                this.products = [];
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка массового удаления:', err);
                throw err;
            }
        },

        // ==========================================
        // ЭКСПОРТ
        // ==========================================

        async exportAllProducts() {
            try {
                const response = await axios.post(`${BASE}/export-all-products`);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка экспорта товаров:', err);
                throw err;
            }
        },

        async exportAllOrders() {
            try {
                const response = await axios.post(`${BASE}/export-all-orders`);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка экспорта заказов:', err);
                throw err;
            }
        },

        // ==========================================
        // СИНХРОНИЗАЦИЯ С ВНЕШНИМИ СИСТЕМАМИ
        // ==========================================

        /**
         * Синхронизация с ВК
         */
        async updateProductsFromVk() {
            this.isLoading = true;

            try {
                const response = await axios.post(`${BASE}/update-from-vk`);
                this.lastSyncAt = new Date();
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка синхронизации с ВК:', err);
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        async updateShopLink(payload = {botForm: null}) {
            try {
                const response = await axios.post(`${BASE}/update-shop-link`, payload.botForm);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка обновления ссылки:', err);
                throw err;
            }
        },

        /**
         * Синхронизация с FrontPad
         */
        async updateProductsFromFrontPad() {
            this.isLoading = true;

            try {
                const response = await axios.post(`${BASE}/update-from-frontpad`);
                this.lastSyncAt = new Date();
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка синхронизации с FrontPad:', err);
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Синхронизация с FrontPad через Excel
         */
        async updateProductsFromFrontPadExcel(payload = {form: null}) {
            this.isLoading = true;

            try {
                const response = await axios.post(
                    `${BASE}/update-from-frontpad-excel`,
                    payload.form,
                    {headers: {'Content-Type': 'multipart/form-data'}}
                );
                this.lastSyncAt = new Date();
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки Excel:', err);
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        // ==========================================
        // ОТЗЫВЫ
        // ==========================================

        async loadReviewsByProductId(payload = {dataObject: {}, page: 0, size: 30}) {
            try {
                const page = payload.page || 0;
                const size = payload.size || 30;
                const link = `${BASE}/${payload.dataObject.product_id}/reviews?page=${page}&size=${size}`;
                const response = await axios.get(link);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки отзывов:', err);
                throw err;
            }
        },

        // ==========================================
        // ИЗБРАННОЕ (дублируется в favorites store)
        // ==========================================

        async getFavList() {
            try {
                const response = await axios.post(`${BASE}/fav-list`);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки избранного:', err);
                throw err;
            }
        },

        async toggleProductInFavorites(payload = {form: null}) {
            try {
                const response = await axios.post(`${BASE}/toggle-favorite`, payload.form);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка переключения избранного:', err);
                throw err;
            }
        },

        // ==========================================
        // МОДУЛЬ
        // ==========================================

        async loadShopModuleData() {
            this.isModuleDataLoading = true;

            try {
                const response = await axios.post(`${BASE}/load-data`);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки данных модуля:', err);
                throw err;
            } finally {
                this.isModuleDataLoading = false;
            }
        },

        // ==========================================
        // КАТЕГОРИИ
        // ==========================================

        /**
         * Загрузка категорий
         */
        async loadCategories(payload = {dataObject: {}, page: 0, size: 5}) {
            this.isCategoriesLoading = true;

            try {
                const page = payload.page || 0;
                const size = payload.size || 5;
                const link = `${BASE}/categories?page=${page}&size=${size}`;

                const response = await axios.post(link, payload.dataObject || {});
                const dataObject = response.data;

                this.categories = dataObject.data || [];
                const {data, ...paginate} = dataObject;
                this.categories_paginate_object = paginate;

                this.isCategoriesHydrated = true;

                return paginate;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки категорий:', err);
                throw err;
            } finally {
                this.isCategoriesLoading = false;
            }
        },

        /**
         * Загрузка одной категории
         */
        async loadCategory(payload) {
            try {
                const link = `${BASE}/category/${payload.dataObject.categoryId}`;
                const response = await axios.post(link);
                return response.data;
            } catch (err) {
                console.error('[Products Store] Ошибка загрузки категории:', err);
                throw err;
            }
        },

        /**
         * Создание категории
         */
        async storeProductCategory(payload) {
            try {
                const response = await axios.post(`${BASE}/store-category`, payload);
                const newCategory = response.data?.data || response.data;

                if (newCategory?.id) {
                    this.categories.unshift(newCategory);
                }

                return newCategory;
            } catch (err) {
                console.error('[Products Store] Ошибка создания категории:', err);
                throw err;
            }
        },

        /**
         * Добавление категории (алиас)
         */
        async addProductCategory(payload) {
            return this.storeProductCategory(payload);
        },

        /**
         * Удаление категории (оптимистично)
         */
        async removeProductCategory(payload) {
            const categoryId = payload?.category_id;
            if (!categoryId) throw new Error('Не указан ID категории');

            this.categoryActions[String(categoryId)] = 'delete';

            // Сохраняем для отката
            const previousCategories = [...this.categories];
            const removedIndex = this.categories.findIndex(c => String(c.id) === String(categoryId));
            const removedCategory = removedIndex !== -1 ? this.categories[removedIndex] : null;

            if (removedIndex !== -1) {
                this.categories.splice(removedIndex, 1);
            }

            try {
                const response = await axios.delete(`${BASE}/remove-category/${categoryId}`);
                return response.data;
            } catch (err) {
                // Откат
                if (removedCategory && removedIndex !== -1) {
                    this.categories.splice(removedIndex, 0, removedCategory);
                }
                console.error('[Products Store] Ошибка удаления категории:', err);
                throw err;
            } finally {
                delete this.categoryActions[String(categoryId)];
            }
        },

        /**
         * Изменение статуса категории (оптимистично)
         */
        async changeProductCategoryStatus(payload) {
            const categoryId = payload;
            if (!categoryId) throw new Error('Не указан ID категории');

            this.categoryActions[String(categoryId)] = 'status';

            // Сохраняем предыдущее состояние
            const category = this.getCategoryById(categoryId);
            const previousState = category?.is_active;

            if (category) {
                category.is_active = !category.is_active;
            }

            try {
                const response = await axios.post(`${BASE}/categories/status/${categoryId}`);
                const updated = response.data?.data || response.data;

                if (category && updated) {
                    Object.assign(category, updated);
                }

                return updated;
            } catch (err) {
                if (category) {
                    category.is_active = previousState;
                }
                console.error('[Products Store] Ошибка изменения статуса категории:', err);
                throw err;
            } finally {
                delete this.categoryActions[String(categoryId)];
            }
        },

        /**
         * Изменение статуса рекомендации категории (оптимистично)
         */
        async changeCategoryRecommendationStatus(payload) {
            const categoryId = payload?.category_id || payload?.id;
            if (!categoryId) throw new Error('Не указан ID категории');

            this.categoryActions[String(categoryId)] = 'recommend';

            const category = this.getCategoryById(categoryId);
            const previousState = category?.is_recommended;

            if (category) {
                category.is_recommended = !category.is_recommended;
            }

            try {
                const response = await axios.post(`${BASE}/categories/recommendation-status`, payload);
                const updated = response.data?.data || response.data;

                if (category && updated) {
                    Object.assign(category, updated);
                }

                return updated;
            } catch (err) {
                if (category) {
                    category.is_recommended = previousState;
                }
                console.error('[Products Store] Ошибка рекомендации категории:', err);
                throw err;
            } finally {
                delete this.categoryActions[String(categoryId)];
            }
        },

        /**
         * 🆕 Загрузка коллекций (комбо-меню)
         */
        async loadCollections(page = 1, partnerId = null) {
            this.isLoading = true;
            try {
                const response = await axios.get('/shop/collections', {
                    params: {page, partner_id: partnerId}
                });
                this.collections = response.data.data || [];
                this.collectionsPaginate = response.data.meta || null;
            } catch (error) {
                console.error('[Products Store] Ошибка загрузки коллекций:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },


        /**
         * 🆕 Установка выбранного партнёра и его наценки
         */
        setPartner(partner) {
            this.selectedPartner = partner;
            this.extraCharge = partner?.extra_charge || 0;
        },

        /**
         * 🆕 Установка поискового запроса
         */
        setSearch(query) {
            this.searchQuery = query;
        },

        /**
         * 🆕 Очистка данных меню (при смене партнёра или выходе)
         */
        clearMenuData() {
            this.products = [];
            this.collections = [];

            this.categories = [];
            this.selectedPartner = null;
            this.extraCharge = 0;
            this.searchQuery = '';
            this.selectedCategory = null;
        },

        // ... (ваши существующие методы saveProduct, removeShopProduct, loadCategories и т.д. остаются без изменений) ...

        // ==========================================
        // СБРОС
        // ==========================================
        $reset() {
            this.products = [];
            this.categories = [];
            this.collections = [];

            this.products_paginate_object = null;
            this.categories_paginate_object = null;
            this.collectionsPaginate = null;

            this.isLoading = false;
            this.isHydrated = false;
            this.isCategoriesLoading = false;
            this.isCategoriesHydrated = false;
            this.isRandomLoading = false;
            this.isRecommendedLoading = false;
            this.isByCategoryLoading = false;
            this.isInCategoryLoading = false;
            this.isModuleDataLoading = false;
            this.isLoadingMore = false;

            this.productActions = {};
            this.categoryActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            this.searchQuery = '';
            this.selectedCategory = null;
            this.selectedPartner = null;
            this.extraCharge = 0;
        },
    },
});
