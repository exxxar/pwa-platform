import { storeToRefs } from 'pinia';
import { useCollectionsStore } from '@/MobileClient/stores/Shop/collections.js';

export function useCollections() {
    const store = useCollectionsStore();

    const {
        // State
        collections,
        collectionsPaginate,
        currentCollection,
        isLoading,
        isHydrated,
        isSingleLoading,
        isLoadingMore,
        isListLoading,
        collectionActions,
        categoryActions,
        cartVariants,
        searchQuery,
        lastError,
        errors,
        lastSyncAt,

        // Getters
        sortedCollections,
        activeCollections,
        filteredCollections,
        collectionsCount,
        totalCartVariantsCount,
        totalCartVariantsPrice,
    } = storeToRefs(store);

    // Инициализация корзины из localStorage при первом обращении
    if (!store.isHydrated) {
        store.initCartFromStorage();
    }

    return {
        // ==========================================
        // STATE
        // ==========================================
        collections,
        collectionsPaginate,
        currentCollection,
        isLoading,
        isHydrated,
        isSingleLoading,
        isLoadingMore,
        isListLoading,
        collectionActions,
        categoryActions,
        cartVariants,
        searchQuery,
        lastError,
        errors,
        lastSyncAt,

        // ==========================================
        // GETTERS
        // ==========================================
        sortedCollections,
        activeCollections,
        filteredCollections,
        collectionsCount,
        totalCartVariantsCount,
        totalCartVariantsPrice,

        // Параметризованные геттеры
        getCollectionById: (id) => store.getCollectionById(id),
        getVariantsForCollection: (id) => store.getVariantsForCollection(id),
        isCollectionLoading: (id) => store.isCollectionLoading(id),
        isCategoryLoading: (id) => store.isCategoryLoading(id),

        // ==========================================
        // ЗАГРУЗКА
        // ==========================================
        loadCollections: (payload = {}) => store.loadCollections(payload),
        loadCollectionsList: (payload = {}) => store.loadCollectionsList(payload),
        loadCollection: (payload) => store.loadCollection(payload),
        loadMoreCollections: (payload = {}) => store.loadMoreCollections(payload),

        // ==========================================
        // CRUD: КОЛЛЕКЦИИ
        // ==========================================
        createCollection: (formData) => store.createCollection(formData),
        updateCollection: (formData) => store.updateCollection(formData),
        destroyCollection: (id) => store.destroyCollection(id),
        toggleActive: (id) => store.toggleActive(id),
        toggleStopList: (id) => store.toggleStopList(id),
        duplicateCollection: (id) => store.duplicateCollection(id),
        removeAllCollections: () => store.removeAllCollections(),

        // ==========================================
        // КАТЕГОРИИ
        // ==========================================
        addCategory: (collectionId, data) => store.addCategory(collectionId, data),
        updateCategory: (categoryId, data) => store.updateCategory(categoryId, data),
        removeCategory: (categoryId) => store.removeCategory(categoryId),

        // ==========================================
        // ТОВАРЫ В КАТЕГОРИЯХ
        // ==========================================
        addProductsToCategory: (categoryId, productIds) =>
            store.addProductsToCategory(categoryId, productIds),
        removeProductFromCategory: (categoryId, productId) =>
            store.removeProductFromCategory(categoryId, productId),
        reorderProducts: (categoryId, order) =>
            store.reorderProducts(categoryId, order),

        // ==========================================
        // КОРЗИНА ВАРИАНТОВ
        // ==========================================
        addVariantToCart: (variant) => store.addVariantToCart(variant),
        removeVariantFromCart: (collectionId, variantId) =>
            store.removeVariantFromCart(collectionId, variantId),
        removeAllVariantsForCollection: (collectionId) =>
            store.removeAllVariantsForCollection(collectionId),
        clearAllVariants: () => store.clearAllVariants(),

        // ==========================================
        // РАСЧЁТЫ
        // ==========================================
        calculateCollectionPrice: (collection, selections) =>
            store.calculateCollectionPrice(collection, selections),
        validateCollectionSelection: (collection, selections) =>
            store.validateCollectionSelection(collection, selections),

        // ==========================================
        // UI
        // ==========================================
        setSearch: (query) => store.setSearch(query),
        clearCollections: () => store.clearCollections(),

        // Сброс
        $reset: store.$reset.bind(store),
    };
}
