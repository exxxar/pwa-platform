import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useFavoritesStore } from '@/MobileClient/stores/Shop/favorites.js';

export function useFavorites() {
    const store = useFavoritesStore();

    const {
        favoriteIds,

        isLoading,
        isLoadingProducts,
        isHydrated,
        lastError,
    } = storeToRefs(store);

    const count = computed(() => store.count);
    const isFavorite = computed(() => store.isFavorite);
    const favoriteProducts = computed(() => store.getFavoriteProducts);

    return {
        // State
        favoriteIds,
        favoriteProducts,
        isLoading,
        isLoadingProducts,
        isHydrated,
        lastError,

        // Getters
        count,
        isFavorite,

        // Actions
        loadFavorites: store.loadFavorites,
        loadFavoriteProducts: store.loadFavoriteProducts,
        addFavorite: store.addFavorite,
        removeFavorite: store.removeFavorite,
        toggleFavorite: store.toggleFavorite,
        clearFavorites: store.clearFavorites,
        setFavoriteIds: store.setFavoriteIds,

        $reset: store.$reset,
    };
}
