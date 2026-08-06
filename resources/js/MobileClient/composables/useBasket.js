import { storeToRefs } from 'pinia';
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

export function useBasket() {
    const store = useBasketStore();

    // ==========================================
    // 1. ЕДИНАЯ ДЕСТРУКТУРИЗАЦИЯ
    // ==========================================
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

        cartTotalCount,
        cartTotalPrice,
        isEmpty,
        inCart,
        inCollectionCart,

        getCollectionById,
    } = storeToRefs(store);

    // ==========================================
    // 2. ВСПОМОГАТЕЛЬНЫЕ
    // ==========================================
    const isProductLoading = (productId) => store.isProductLoading(productId);
    const setInitialData = (data) => store.setInitialData(data);

    const withLoadingGuard = (actionFn) => (payload) => {
        const id = typeof payload === 'object' ? payload?.id : payload;
        if (id && isProductLoading(id)) return Promise.resolve();
        return actionFn(payload);
    };

    // ==========================================
    // 3. МЕТОДЫ
    // ==========================================

    // --- Товары ---
    const getItemById = (payload) => store.getItemById(payload);
    const addProduct = withLoadingGuard(store.addProductToCart.bind(store));
    const removeProduct = withLoadingGuard(store.removeProductFromCart.bind(store));
    const removeProductCompletely = withLoadingGuard(store.removeProduct.bind(store));
    const incrementQuantity = withLoadingGuard(store.incQuantity.bind(store));
    const decrementQuantity = withLoadingGuard(store.decQuantity.bind(store));

    // --- 🆕 ВАРИАНТЫ КОЛЛЕКЦИЙ (новая логика) ---
    const addCollectionVariant = (variant) => store.addCollectionVariantToCart(variant);
    const incCollectionVariant = (variantId) => store.incCollectionVariantQuantity(variantId);
    const decCollectionVariant = (variantId) => store.decCollectionVariantQuantity(variantId);
    const removeCollectionVariant = (variantId) => store.removeCollectionVariant(variantId);
    const removeAllVariantsOfCollection = (collectionId) => store.removeAllVariantsOfCollection(collectionId);
    const replaceCollectionVariant = (variantId, newVariant) => store.replaceCollectionVariant(variantId, newVariant);
    const calculateVariantPrice = (variantId) => store.calculateVariantPrice(variantId);

    // --- Старые методы коллекций (обратная совместимость) ---
    const addCollection = (collection) => store.addCollectionToCart(collection);
    const incrementCollection = (payload) => store.incCollectionQuantity(payload);
    const decrementCollection = (payload) => store.decCollectionQuantity(payload);
    const removeCollection = (payload) => store.removeCollectionFromCart(payload);

    // --- Общие ---
    const requestDeliveryPriceNew = (payload) => store.requestDeliveryPriceNew(payload);
    const addComment = (payload) => store.addCommentToProduct(payload);
    const clearCart = () => store.clearCart();
    const loadProductsInBasket = (payload = {}) => store.loadProductsInBasket(payload);
    const startCheckout = (payload) => store.startCheckout(payload);
    const createCheckoutLink = (payload) => store.createCheckoutLink(payload);

    // ==========================================
    // 4. ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
    // ==========================================
    return {
        // State
        basket_items,
        basket_items_paginate_object,
        isLoading,
        isHydrated,
        isSending,
        productActions,
        lastError,
        errors,
        lastSyncAt,

        // Getters
        cartTotalCount,
        cartTotalPrice,
        isEmpty,
        inCart,
        inCollectionCart,
        getCollectionById,
        isProductLoading,

        // 🆕 Новые параметризованные геттеры
        getCollectionVariants: (collectionId) => store.getCollectionVariants(collectionId),
        getBasketItemByVariantId: (variantId) => store.getBasketItemByVariantId(variantId),

        // Методы: Товары
        addProduct,
        getItemById,
        setInitialData,
        removeProduct,
        removeProductCompletely,
        incrementQuantity,
        decrementQuantity,

        // 🆕 Методы: ВАРИАНТЫ коллекций
        addCollectionVariant,
        incCollectionVariant,
        decCollectionVariant,
        removeCollectionVariant,
        removeAllVariantsOfCollection,
        replaceCollectionVariant,
        calculateVariantPrice,

        // Методы: Подборки (старые, для обратной совместимости)
        addCollection,
        incrementCollection,
        decrementCollection,
        removeCollection,

        // Общие
        requestDeliveryPriceNew,
        addComment,
        loadProductsInBasket,
        startCheckout,
        createCheckoutLink,
        clearCart,

        // Прямые ссылки
        useWheelOfFortunePrize: store.useWheelOfFortunePrize.bind(store),
        $reset: store.$reset.bind(store),
    };
}
