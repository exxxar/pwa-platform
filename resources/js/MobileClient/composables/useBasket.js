import { storeToRefs } from 'pinia';
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

/**
 * Composable для работы с корзиной.
 * Оптимизирован: убрана лишняя реактивность и шаблонный код.
 */
export function useBasket() {
    const store = useBasketStore();

    // ==========================================
    // 1. ЕДИНАЯ ДЕСТРУКТУРИЗАЦИЯ (State + Getters)
    // ==========================================
    // Геттеры Pinia уже реактивны, берем их напрямую через storeToRefs
    const {
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

        // Геттеры (без лишних computed!)
        cartTotalCount,
        cartTotalPrice,
        isEmpty,
        inCart,
        inCollectionCart,

        getCollectionById,
    } = storeToRefs(store);

    // ==========================================
    // 2. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
    // ==========================================
    const isProductLoading = (productId) => store.isProductLoading(productId);

    // Фабрика для создания методов с защитой от двойного клика
    const withLoadingGuard = (actionFn) => (productId) => {
        if (isProductLoading(productId)) return Promise.resolve();
        return actionFn(productId);
    };

    // ==========================================
    // 3. МЕТОДЫ (Без избыточного try/catch)
    // ==========================================
    // Мы просто возвращаем Promise из стора.
    // Обработкой ошибок (показом тостов) должен заниматься компонент или глобальный перехватчик.

    // Товары (с защитой от спама кликами)
    const getItemById  = (payload) => store.getItemById (payload);
    const addProduct = withLoadingGuard(store.addProductToCart);
    const removeProduct = withLoadingGuard(store.removeProductFromCart);
    const removeProductCompletely = withLoadingGuard(store.removeProduct);
    const incrementQuantity = withLoadingGuard(store.incQuantity);
    const decrementQuantity = withLoadingGuard(store.decQuantity);

    // Подборки (коллекции)
    const addCollection = (collection) => store.addCollectionToCart(collection);

    const incrementCollection = (payload) => store.incCollectionQuantity(payload);
    const decrementCollection = (payload) => store.decCollectionQuantity(payload);
    const removeCollection = (payload) => store.removeCollectionFromCart(payload);

    // Прочие действия
    const addComment = (payload) => store.addCommentToProduct(payload);
    const clearCart = () => store.clearCart();
    const loadProductsInBasket = (payload = {}) => store.loadProductsInBasket(payload);
    const startCheckout = (payload) => store.startCheckout(payload);
    const createCheckoutLink = (payload) => store.createCheckoutLink(payload);

    // ==========================================
    // 4. ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
    // ==========================================
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
        inCart,
        inCollectionCart,

        getCollectionById,
        isProductLoading,

        // Методы: Товары
        addProduct,
        getItemById,
        removeProduct,
        removeProductCompletely,
        incrementQuantity,
        decrementQuantity,

        // Методы: Подборки
        addCollection,
        incrementCollection,
        decrementCollection,
        removeCollection,

        // Методы: Общие
        addComment,
        loadProductsInBasket,
        startCheckout,
        createCheckoutLink,
        clearCart,

        // Прямые ссылки на методы стора, не требующие обертки
        useWheelOfFortunePrize: store.useWheelOfFortunePrize,
        $reset: store.$reset,
    };
}
