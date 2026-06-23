import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

/**
 * Composable для работы с корзиной в компонентах
 * Предоставляет реактивные ссылки и удобные методы
 */
export function useBasket() {
    const store = useBasketStore();

    // Реактивные ссылки на состояние
    const {
        basket_items,
        isLoading,
        isHydrated,
        lastError,
        productActions,
    } = storeToRefs(store);

    // Реактивные геттеры
    const cartTotalCount = computed(() => store.cartTotalCount);
    const cartTotalPrice = computed(() => store.cartTotalPrice);
    const isEmpty = computed(() => store.isEmpty);

    /**
     * Проверка, загружается ли конкретный товар
     */
    const isProductLoading = (productId) => {
        return !!productActions.value[productId];
    };

    /**
     * Получить текущее действие над товаром ('inc' | 'dec' | 'remove' | null)
     */
    const getProductAction = (productId) => {
        return productActions.value[productId] || null;
    };

    /**
     * Безопасное добавление товара с обработкой ошибок
     */
    const addProduct = async (productId) => {
        // Если уже идёт действие — игнорируем
        if (isProductLoading(productId)) return;

        try {
            await store.addProductToCart(productId);
        } catch (err) {
            // Здесь можно показать уведомление
            console.error('Не удалось добавить товар:', err);
            throw err;
        }
    };

    /**
     * Безопасное удаление товара с обработкой ошибок
     */
    const removeProduct = async (productId) => {
        if (isProductLoading(productId)) return;

        try {
            await store.removeProductFromCart(productId);
        } catch (err) {
            console.error('Не удалось удалить товар:', err);
            throw err;
        }
    };

    return {
        // Состояние
        basket_items,
        isLoading,
        isHydrated,
        lastError,
        productActions,

        // Геттеры
        cartTotalCount,
        cartTotalPrice,
        isEmpty,
        inCart: store.inCart,
        getItemById: store.getItemById,

        // Методы
        addProduct,
        removeProduct,
        clearCart: store.clearCart,
        loadProductsInBasket: store.loadProductsInBasket,
        isProductLoading,
        getProductAction,
    };
}
