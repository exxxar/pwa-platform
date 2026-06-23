import { defineStore } from 'pinia';
import { apiRequest } from '../utils/api.js';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        favorites: [],
        isLoading: false,
        isHydrated: false,
        lastError: null,
    }),

    getters: {
        /**
         * Проверка, находится ли товар в избранном
         */
        inFav: (state) => (id) => {
            if (id === null || id === undefined) return false;

            const targetId = String(id);
            return state.favorites.some(item => String(item.id) === targetId);
        },

        /**
         * Все избранные товары
         */
        getFavorites: (state) => state.favorites,

        /**
         * Количество избранных
         */
        favoritesCount: (state) => state.favorites.length || 0,

        /**
         * Список ID избранных товаров
         */
        favoritesIds: (state) => state.favorites.map(item => item.id),
    },

    actions: {
        // ==========================================
        // СИНХРОНИЗАЦИЯ
        // ==========================================

        /**
         * Загрузка избранного с сервера (основной источник истины)
         */
        async loadFavoritesFromServer() {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await apiRequest('/shop/products/fav-list', 'POST');
                const favorites = response.data?.favorites || response.data || [];

                this.favorites = favorites.data;
                this.isHydrated = true;

                // Синхронизируем с localStorage
                localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites));

                return this.favorites;
            } catch (err) {
                console.error('[Favorites Store] Ошибка загрузки:', err);
                this.lastError = err.response?.data?.message || 'Ошибка загрузки избранного';

                // Fallback: используем данные из localStorage
                const cached = localStorage.getItem('mypwa_favorites');
                if (cached) {
                    try {
                        this.favorites = JSON.parse(cached);
                    } catch {
                        this.favorites = [];
                    }
                }

                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Переключение товара в избранном (основной метод)
         * Отправляет запрос на бэк и обновляет локальное состояние
         */
        async toggleFavorite(product) {
            const productId = product.id || product;
            const isInFavorites = this.inFav(productId);

            // Оптимистично обновляем UI
            const previousFavorites = [...this.favorites];

            if (isInFavorites) {
                this.favorites = this.favorites.filter(item => item.id !== productId);
            } else {
                // Если передали только ID, создаём минимальный объект
                const productObj = typeof product === 'object'
                    ? product
                    : { id: productId };
                this.favorites.push(productObj);
            }

            // Сохраняем в localStorage
            localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites));

            try {
                // Отправляем запрос на бэкенд
                const response = await apiRequest(
                    '/shop/products/toggle-favorite',
                    'POST',
                    { id: productId }
                );

                // Синхронизируем с ответом сервера (на случай, если бэк вернул обновлённый список)
                if (response.data?.favorites) {
                    this.favorites = response.data.favorites || [];
                    localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites));
                }

                return {
                    success: true,
                    isFavorite: !isInFavorites,
                    product: response.data?.product || product,
                };
            } catch (err) {
                // Откатываем изменения при ошибке
                this.favorites = previousFavorites;
                localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites));

                console.error('[Favorites Store] Ошибка переключения:', err);
                throw err;
            }
        },

        // ==========================================
        // ЛОКАЛЬНЫЕ ДЕЙСТВИЯ (legacy, для совместимости)
        // ==========================================

        setFavoritesItems(favorites) {
            this.favorites = favorites || [];
            localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites));
        },

        pushProductToFav(product) {
            const exists = this.favorites.find(item => item.id === product.id);
            if (!exists) {
                this.favorites.push(product);
                localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites));
            }
        },

        removeProductFromFav(id) {
            this.favorites = this.favorites.filter(item => item.id !== id);
            localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites));
        },

        clearAllFavorites() {
            this.favorites = [];
            localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites));
        },

        async loadActualPriceInFav() {
            try {
                const ids = this.favorites.map(item => item.id);
                if (ids.length === 0) return;

                const response = await apiRequest(
                    '/shop/products/load-actual',
                    'POST',
                    { ids }
                );
                const products = response.data || [];

                const updated = this.favorites
                    .map(fav => products.find(sub => sub.id === fav.id) || fav)
                    .filter(Boolean);

                this.setFavoritesItems(updated);
            } catch (err) {
                console.error('[Favorites Store] Ошибка обновления цен:', err);
                throw err;
            }
        },

        // Алиасы
        addToFavorites(product) {
            this.pushProductToFav(product);
        },
        removeFromFavorites(id) {
            this.removeProductFromFav(id);
        },
        clearFavorites() {
            this.clearAllFavorites();
        },

        /**
         * Полный сброс состояния
         */
        $reset() {
            this.favorites = [];
            this.isLoading = false;
            this.isHydrated = false;
            this.lastError = null;
            localStorage.removeItem('mypwa_favorites');
        },
    },
});
