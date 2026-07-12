import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/favorites';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        favoriteIds: [],
        favoriteProducts: [],
        isLoading: false,
        isLoadingProducts: false,
        isHydrated: false,
        lastError: null,
    }),

    getters: {
        getFavorites: (state) => state.favoriteIds,
        getFavoriteProducts: (state) => state.favoriteProducts,
        count: (state) => state.favoriteIds.length,
        isFavorite: (state) => (productId) => {
            return state.favoriteIds.includes(productId);
        },
    },

    actions: {
        async loadFavorites() {
            this.isLoading = true;
            this.lastError = null;

            try {
                console.log('[FavoritesStore] GET', BASE);
                const response = await axios.get(BASE);

                console.log('[FavoritesStore] Response:', response.data);

                const ids = response.data?.data?.ids || [];
                this.favoriteIds = Array.isArray(ids) ? ids : [];
                this.isHydrated = true;

                console.log('[FavoritesStore] Loaded IDs:', this.favoriteIds);

                return this.favoriteIds;
            } catch (error) {
                console.error('[FavoritesStore] Ошибка загрузки:', error);
                this.lastError = error.response?.data?.message || 'Ошибка загрузки';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        async loadFavoriteProducts() {
            if (this.favoriteIds.length === 0) {
                this.favoriteProducts = [];
                return [];
            }

            this.isLoadingProducts = true;

            try {
                const response = await axios.get(`${BASE}/products`);

                // 🆕 Гарантируем что это массив
                const data = response.data?.data;


                this.favoriteProducts = Array.isArray(data) ? data : [];

                return this.favoriteProducts;
            } catch (error) {
                console.error('[FavoritesStore] Ошибка:', error);
                this.favoriteProducts = [];
                throw error;
            } finally {
                this.isLoadingProducts = false;
            }
        },

        async addFavorite(productId) {
            if (this.favoriteIds.includes(productId)) {
                console.log('[FavoritesStore] Уже в избранном:', productId);
                return false;
            }

            try {
                await axios.post(`${BASE}`, { product_id: productId });
                this.favoriteIds.push(productId);
                console.log('[FavoritesStore] Добавлено:', productId);
                return true;
            } catch (error) {
                console.error('[FavoritesStore] Ошибка добавления:', error);
                throw error;
            }
        },

        async removeFavorite(productId) {
            try {
                await axios.delete(`${BASE}/${productId}`);
                this.favoriteIds = this.favoriteIds.filter(id => id !== productId);
                this.favoriteProducts = this.favoriteProducts.filter(p => p.id !== productId);
                console.log('[FavoritesStore] Удалено:', productId);
                return true;
            } catch (error) {
                console.error('[FavoritesStore] Ошибка удаления:', error);
                throw error;
            }
        },

        async toggleFavorite(productId) {
            if (this.favoriteIds.includes(productId)) {
                await this.removeFavorite(productId);
                return false;
            } else {
                await this.addFavorite(productId);
                return true;
            }
        },

        async clearFavorites() {
            try {
                await axios.delete(`${BASE}/clear`);
                this.favoriteIds = [];
                this.favoriteProducts = [];
                return true;
            } catch (error) {
                console.error('[FavoritesStore] Ошибка очистки:', error);
                throw error;
            }
        },

        setFavoriteIds(ids) {
            this.favoriteIds = Array.isArray(ids) ? ids : [];
            this.isHydrated = true;
        },

        $reset() {
            this.favoriteIds = [];
            this.favoriteProducts = [];
            this.isLoading = false;
            this.isLoadingProducts = false;
            this.isHydrated = false;
            this.lastError = null;
        },
    },
});
