import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

/**
 * Composable для работы с корзиной
 */
export function useBasket() {
    const store = useBasketStore();

    // Реактивные ссылки на состояние
    const {
        basket_items,
        basket_items_paginate_object,
        isLoading,
        isHydrated,
        isSending,
        productActions,
        lastError,
        errors,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const cartTotalCount = computed(() => store.cartTotalCount);
    const cartTotalPrice = computed(() => store.cartTotalPrice);
    const isEmpty = computed(() => store.isEmpty);

    /**
     * Проверка, загружается ли товар
     */
    const isProductLoading = (productId) => {
        return store.isProductLoading(productId);
    };

    /**
     * Безопасное добавление товара
     */
    const addProduct = async (productId) => {
        if (isProductLoading(productId)) return;

        try {
            return await store.addProductToCart(productId);
        } catch (error) {
            console.error('Ошибка добавления товара:', error);
            throw error;
        }
    };

    /**
     * Безопасное удаление товара (уменьшение количества)
     */
    const removeProduct = async (productId) => {
        if (isProductLoading(productId)) return;

        try {
            return await store.removeProductFromCart(productId);
        } catch (error) {
            console.error('Ошибка удаления товара:', error);
            throw error;
        }
    };

    /**
     * Полное удаление товара из корзины
     */
    const removeProductCompletely = async (productId) => {
        if (isProductLoading(productId)) return;

        try {
            return await store.removeProduct(productId);
        } catch (error) {
            console.error('Ошибка полного удаления товара:', error);
            throw error;
        }
    };

    /**
     * Увеличение количества товара
     */
    const incrementQuantity = async (productId) => {
        if (isProductLoading(productId)) return;

        try {
            return await store.incQuantity(productId);
        } catch (error) {
            console.error('Ошибка увеличения количества:', error);
            throw error;
        }
    };

    /**
     * Уменьшение количества товара
     */
    const decrementQuantity = async (productId) => {
        if (isProductLoading(productId)) return;

        try {
            return await store.decQuantity(productId);
        } catch (error) {
            console.error('Ошибка уменьшения количества:', error);
            throw error;
        }
    };

    /**
     * Добавление подборки
     */
    const addCollection = async (collection) => {
        try {
            return await store.addCollectionToCart(collection);
        } catch (error) {
            console.error('Ошибка добавления подборки:', error);
            throw error;
        }
    };

    /**
     * Увеличение количества подборки
     */
    const incrementCollection = async (payload) => {
        try {
            return await store.incCollectionQuantity(payload);
        } catch (error) {
            console.error('Ошибка увеличения подборки:', error);
            throw error;
        }
    };

    /**
     * Уменьшение количества подборки
     */
    const decrementCollection = async (payload) => {
        try {
            return await store.decCollectionQuantity(payload);
        } catch (error) {
            console.error('Ошибка уменьшения подборки:', error);
            throw error;
        }
    };

    /**
     * Удаление подборки
     */
    const removeCollection = async (payload) => {
        try {
            return await store.removeCollectionFromCart(payload);
        } catch (error) {
            console.error('Ошибка удаления подборки:', error);
            throw error;
        }
    };

    /**
     * Добавление комментария
     */
    const addComment = async (payload) => {
        try {
            return await store.addCommentToProduct(payload);
        } catch (error) {
            console.error('Ошибка добавления комментария:', error);
            throw error;
        }
    };

    /**
     * Очистка корзины
     */
    const clearCart = async () => {
        try {
            await store.clearCart();
        } catch (error) {
            console.error('Ошибка очистки корзины:', error);
            throw error;
        }
    };

    /**
     * Загрузка корзины
     */
    const loadProductsInBasket = async (payload = {}) => {
        try {
            return await store.loadProductsInBasket(payload);
        } catch (error) {
            console.error('Ошибка загрузки корзины:', error);
            throw error;
        }
    };

    /**
     * Оформление заказа
     */
    const startCheckout = async (payload) => {
        try {
            return await store.startCheckout(payload);
        } catch (error) {
            console.error('Ошибка оформления заказа:', error);
            throw error;
        }
    };

    /**
     * Создание ссылки на оплату
     */
    const createCheckoutLink = async (payload) => {
        try {
            return await store.createCheckoutLink(payload);
        } catch (error) {
            console.error('Ошибка создания ссылки:', error);
            throw error;
        }
    };

    return {
        // Состояние
        basket_items,
        basket_items_paginate_object,
        isLoading,
        isHydrated,
        isSending,
        productActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры
        cartTotalCount,
        cartTotalPrice,
        isEmpty,
        inCart: store.inCart,
        inCollectionCart: store.inCollectionCart,
        getItemById: store.getItemById,
        getCollectionById: store.getCollectionById,
        isProductLoading,

        // Товары
        addProduct,
        removeProduct,
        removeProductCompletely,
        incrementQuantity,
        decrementQuantity,

        // Подборки
        addCollection,
        incrementCollection,
        decrementCollection,
        removeCollection,

        // Комментарии
        addComment,

        // Оформление
        loadProductsInBasket,
        startCheckout,
        createCheckoutLink,
        useWheelOfFortunePrize: store.useWheelOfFortunePrize,

        // Очистка
        clearCart,

        // Сброс
        $reset: store.$reset,
    };
}
