import { storeToRefs } from 'pinia';
import { usePartnersStore } from '@/MobileClient/stores/Shop/partners.js';

/**
 * Composable для работы с партнёрами
 */
export function usePartners() {
    const store = usePartnersStore();

    // ✅ ИСПРАВЛЕНИЕ 1: Извлекаем ВСЁ (состояние и геттеры) через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность.
    const {
        // --- Состояние (State) ---
        partners,
        partners_paginate_object,
        categories,
        selfPartner,
        isLoading,
        isHydrated,
        isCategoriesLoading,
        partnerActions,
        lastError,
        errors,
        lastSyncAt,

        // --- Геттеры (Getters) ---
        sortedPartners,
        activePartners,
        favoritePartners,
        partnersCount,
        getCategories,
        totalPartnerProducts,
        totalPartnerCategories,
        totalPartnerProductsSum,
        isPartnersStatsLoading,
    } = storeToRefs(store);

    // ==========================================
    // ПАРАМЕТРИЗИРОВАННЫЕ ГЕТТЕРЫ / ХЕЛПЕРЫ
    // ==========================================
    const isFavorite = (partnerId) => store.isFavorite(partnerId);
    const isPartnerLoading = (partnerId) => store.isPartnerLoading(partnerId);
    const getPartnerById = (id) => store.getPartnerById(id);
    const getPartnerProducts = (partnerId) => store.getPartnerProducts(partnerId);

    // ==========================================
    // МЕТОДЫ (ACTIONS) С ОБРАБОТКОЙ ОШИБОК
    // ==========================================

    const loadPartners = async (payload = {}) => {
        try {
            return await store.loadPartners(payload);
        } catch (error) {
            console.error('[usePartners] Ошибка загрузки партнёров:', error);
            throw error;
        }
    };

    const loadCategories = async (payload = {}) => {
        try {
            return await store.loadCategories(payload);
        } catch (error) {
            console.error('[usePartners] Ошибка загрузки категорий:', error);
            throw error;
        }
    };

    const loadPartnersStats = async () => {
        try {
            return await store.loadPartnersStats();
        } catch (error) {
            console.error('[usePartners] Ошибка загрузки статистики:', error);
            throw error;
        }
    };

    const toggleFavorite = async (partnerId) => {
        try {
            return await store.togglePartnerInFavorites({
                form: { id: partnerId, partner_id: partnerId },
            });
        } catch (error) {
            console.error('[usePartners] Ошибка переключения избранного:', error);
            throw error;
        }
    };

    const removePartner = async (partnerId) => {
        try {
            return await store.removePartner({ partnerId });
        } catch (error) {
            console.error('[usePartners] Ошибка удаления партнёра:', error);
            throw error;
        }
    };

    const toggleActive = async (partnerId) => {
        try {
            return await store.updatePartnersActiveStatus({
                id: partnerId,
                partner_id: partnerId,
            });
        } catch (error) {
            console.error('[usePartners] Ошибка переключения активности:', error);
            throw error;
        }
    };

    const loadProductsByCategory = async (payload = {}) => {
        try {
            return await store.loadProductsByCategory(payload);
        } catch (error) {
            console.error('[usePartners] Ошибка загрузки товаров партнёра:', error);
            throw error;
        }
    };

    const loadMoreProductsByCategory = async (payload = {}) => {
        try {
            return await store.loadMoreProductsByCategory(payload);
        } catch (error) {
            console.error('[usePartners] Ошибка дозагрузки товаров:', error);
            throw error;
        }
    };

    const changePartnerProductStatus = async (payload = {}) => {
        try {
            return await store.changePartnerProductStatus(payload);
        } catch (error) {
            console.error('[usePartners] Ошибка изменения статуса товара:', error);
            throw error;
        }
    };

    // ==========================================
    // ВОЗВРАЩАЕМЫЕ ЗНАЧЕНИЯ
    // ==========================================
    return {
        // Состояние (Refs)
        partners,
        partners_paginate_object,
        categories,
        selfPartner,
        isLoading,
        isHydrated,
        isCategoriesLoading,
        partnerActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры (Refs)
        sortedPartners,
        activePartners,
        favoritePartners,
        partnersCount,
        getCategories,
        totalPartnerProducts,
        totalPartnerCategories,
        totalPartnerProductsSum,
        isPartnersStatsLoading,

        // Параметризированные геттеры / Хелперы
        isFavorite,
        isPartnerLoading,
        getPartnerById,
        getPartnerProducts,

        // Методы (Actions)
        loadPartners,
        loadCategories,
        loadPartnersStats,
        loadSelfPartner: store.loadSelfPartner,
        storePartner: store.storePartner,
        updatePartner: store.updatePartner,
        updateSelfPartner: store.updateSelfPartner,
        updatePartnersSettings: store.updatePartnersSettings,
        recalculatePartnersStats: store.recalculatePartnersStats,

        removePartner,
        toggleFavorite,
        toggleActive,
        loadProductsByCategory,
        loadMoreProductsByCategory,
        changePartnerProductStatus,

        // Сброс стора
        $reset: store.$reset,
    };
}
