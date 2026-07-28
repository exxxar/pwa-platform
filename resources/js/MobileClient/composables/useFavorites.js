import { storeToRefs } from 'pinia';
import { useFavoritesStore } from '@/MobileClient/stores/Shop/favorites.js';

/**
 * Composable для работы с избранным.
 * Оптимизирован: убраны лишние computed-обертки для геттеров Pinia.
 */
export function useFavorites() {
    const store = useFavoritesStore();

    // ==========================================
    // ЕДИНАЯ ДЕСТРУКТУРИЗАЦИЯ (State + Getters)
    // ==========================================
    const {
        // Состояние (State)
        favoriteIds,
        isLoading,
        isLoadingProducts,
        isHydrated,
        lastError,

        // Геттеры (Getters) - они УЖЕ реактивны, берем их напрямую!
        count,
        isFavorite,
        // Используем алиас, чтобы в компонентах писать favoriteProducts, а не getFavoriteProducts
        getFavoriteProducts: favoriteProducts,
    } = storeToRefs(store);

    // ==========================================
    // ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
    // ==========================================
    return {
        // Состояние
        favoriteIds,
        favoriteProducts, // Теперь это прямая реактивная ссылка на геттер стора
        isLoading,
        isLoadingProducts,
        isHydrated,
        lastError,

        // Геттеры
        count,
        isFavorite,

        // Действия (Actions) - прямые ссылки на методы стора
        loadFavorites: store.loadFavorites,
        loadPartnersFavorites: store.loadPartnersFavorites,
        loadFavoriteProducts: store.loadFavoriteProducts,
        addFavorite: store.addFavorite,
        removeFavorite: store.removeFavorite,
        toggleFavorite: store.toggleFavorite,
        clearFavorites: store.clearFavorites,
        setFavoriteIds: store.setFavoriteIds,

        // Сброс стора
        $reset: store.$reset,
    };
}
