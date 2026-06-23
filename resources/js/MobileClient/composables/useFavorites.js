import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useFavoritesStore } from '@/MobileClient/stores/Shop/favorites.js';

/**
 * Composable для работы с избранным
 */
export function useFavorites() {
    const store = useFavoritesStore();

    // Реактивные ссылки на состояние
    const {
        favorites,
        isLoading,
        isHydrated,
        lastError,
    } = storeToRefs(store);

    // Реактивные геттеры
    const favoritesCount = computed(() => store.favoritesCount);
    const favoritesIds = computed(() => store.favoritesIds);

    /**
     * Проверка, находится ли товар в избранном (реактивно)
     */
    const isInFavorites = (productId) => {
        return store.inFav(productId);
    };

    /**
     * Безопасное переключение избранного с обработкой ошибок
     */
    const toggleFavorite = async (product) => {
        try {
            const result = await store.toggleFavorite(product);

            // Уведомление можно вызвать здесь или в компоненте
            return result;
        } catch (error) {
            console.error('Ошибка переключения избранного:', error);
            throw error;
        }
    };

    /**
     * Загрузка избранного с сервера
     */
    const loadFavorites = async () => {
        try {
            await store.loadFavoritesFromServer();
        } catch (error) {
            console.error('Ошибка загрузки избранного:', error);
        }
    };

    /**
     * Обновление актуальных цен
     */
    const refreshPrices = async () => {
        try {
            await store.loadActualPriceInFav();
        } catch (error) {
            console.error('Ошибка обновления цен:', error);
        }
    };

    return {
        // Состояние
        favorites,
        isLoading,
        isHydrated,
        lastError,

        // Геттеры
        favoritesCount,
        favoritesIds,
        isInFavorites,

        // Методы
        toggleFavorite,
        loadFavorites,
        refreshPrices,
        clearFavorites: store.clearFavorites,
    };
}
