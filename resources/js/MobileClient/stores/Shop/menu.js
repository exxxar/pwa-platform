import { defineStore } from 'pinia';
import axios from 'axios';

export const useMenuStore = defineStore('menu', {
    state: () => ({
        // Данные
        products: [],
        collections: [],
        stories: [],
        categories: [],

        // Пагинация
        productsPaginate: null,
        collectionsPaginate: null,

        // UI состояние
        isLoading: false,
        isLoadingMore: false,
        searchQuery: '',
        selectedCategory: null,

        // Партнёр
        selectedPartner: null,
        extraCharge: 0,
    }),

    getters: {
        // Фильтрация товаров по поиску
        filteredProducts: (state) => {
            if (!state.searchQuery) return state.products;

            const query = state.searchQuery.toLowerCase();
            return state.products
                .map(category => {
                    const filteredProducts = category.products.filter(product =>
                        product.name?.toLowerCase().includes(query)
                    );
                    return { ...category, products: filteredProducts };
                })
                .filter(category => category.products.length > 0);
        },

        // Общее количество товаров
        totalProductsCount: (state) => {
            return state.products.reduce((sum, cat) => sum + (cat.products_count || 0), 0);
        },
    },

    actions: {
        // Загрузка товаров по категориям
        async loadProductsByCategory(partnerId = null) {
            this.isLoading = true;
            try {
                const response = await axios.post('/shop/products/by-category', {
                    params: { partner_id: partnerId }
                });
                this.products = response.data.data;
            } catch (error) {
                console.error('Ошибка загрузки товаров:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        // Загрузка дополнительных товаров (пагинация внутри категории)
        async loadMoreProducts(categoryId, offset, partnerId = null) {
            this.isLoadingMore = true;
            try {
                const response = await axios.get('/api/products/more', {
                    params: {
                        category_id: categoryId,
                        offset: offset,
                        partner_id: partnerId
                    }
                });

                const newProducts = response.data.data;
                const category = this.products.find(p => p.id === categoryId);

                if (category) {
                    category.products.push(...newProducts);
                }

                return newProducts.length;
            } catch (error) {
                console.error('Ошибка загрузки доп. товаров:', error);
                throw error;
            } finally {
                this.isLoadingMore = false;
            }
        },

        // Загрузка коллекций (комбо-меню)
        async loadCollections(page = 1, partnerId = null) {
            this.isLoading = true;
            try {
                const response = await axios.get('/api/collections', {
                    params: { page, partner_id: partnerId }
                });
                this.collections = response.data.data;
                this.collectionsPaginate = response.data.meta;
            } catch (error) {
                console.error('Ошибка загрузки коллекций:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },



        // Установка партнёра
        setPartner(partner) {
            this.selectedPartner = partner;
            this.extraCharge = partner?.extra_charge || 0;
        },

        // Очистка данных
        clearData() {
            this.products = [];
            this.collections = [];
            this.stories = [];
            this.selectedPartner = null;
            this.extraCharge = 0;
        },

        // Установка поискового запроса
        setSearch(query) {
            this.searchQuery = query;
        },
    }
});
