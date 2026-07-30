import { storeToRefs } from 'pinia';
import { usePromocodesStore } from '@/MobileClient/stores/Shop/promocodes.js';

/**
 * Composable для работы с промокодами
 */
export function usePromocodes() {
    const store = usePromocodesStore();

    // ✅ ИСПРАВЛЕНИЕ 1: Извлекаем ВСЁ (состояние и геттеры) через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность.
    const {
        // --- Состояние (State) ---
        promocodes,
        promocodes_paginate_object,
        isLoading,
        isSaving,
        isHydrated,
        isActivating,
        isShopDiscountActivating,
        promocodeActions,
        lastError,
        errors,
        lastSyncAt,

        // --- Геттеры (Getters) ---
        sortedPromocodes,
        activePromocodes,
        expiredPromocodes,
        upcomingPromocodes,
        almostExhaustedPromocodes,
        promocodesCount,
        activePromocodesCount,
    } = storeToRefs(store);

    // ==========================================
    // ПАРАМЕТРИЗИРОВАННЫЕ ХЕЛПЕРЫ
    // ==========================================
    const isPromocodeLoading = (promocodeId) => store.isPromocodeLoading(promocodeId);
    const getPromocodeById = (id) => store.getPromocodeById(id);
    const getPromocodeByCode = (code) => store.getPromocodeByCode(code);
    const promocodesByType = (type) => store.promocodesByType(type);

    // ==========================================
    // МЕТОДЫ (ACTIONS) С ОБРАБОТКОЙ ОШИБОК
    // ==========================================

    const loadPromocodes = async (payload = {}) => {
        try {
            return await store.loadPromoCodes(payload);
        } catch (error) {
            console.error('[usePromocodes] Ошибка загрузки промокодов:', error);
            throw error;
        }
    };

    const activatePromocode = async (promocodeForm) => {
        try {
            return await store.activatePromocode({ promocodeForm });
        } catch (error) {
            console.error('[usePromocodes] Ошибка активации промокода:', error);
            throw error;
        }
    };

    const activateShopDiscount = async (promocodeForm) => {
        try {
            return await store.activateShopDiscountPromocode({ promocodeForm });
        } catch (error) {
            console.error('[usePromocodes] Ошибка активации скидки:', error);
            throw error;
        }
    };

    const createPromocode = async (promoCodeForm) => {
        try {
            return await store.storePromoCodes({ promoCodeForm });
        } catch (error) {
            console.error('[usePromocodes] Ошибка создания промокода:', error);
            throw error;
        }
    };

    const removePromocode = async (promocodeId) => {
        try {
            return await store.removePromoCodes({ promoCodeId: promocodeId });
        } catch (error) {
            console.error(`[usePromocodes] Ошибка удаления промокода ${promocodeId}:`, error);
            throw error;
        }
    };

    // ==========================================
    // ВОЗВРАЩАЕМЫЕ ЗНАЧЕНИЯ
    // ==========================================
    return {
        // Состояние (Refs)
        promocodes,
        promocodes_paginate_object,
        isLoading,
        isHydrated,
        isSaving,
        isActivating,
        isShopDiscountActivating,
        promocodeActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры (Refs)
        sortedPromocodes,
        activePromocodes,
        expiredPromocodes,
        upcomingPromocodes,
        almostExhaustedPromocodes,
        promocodesCount,
        activePromocodesCount,

        // Параметризированные хелперы
        isPromocodeLoading,
        getPromocodeById,
        getPromocodeByCode,
        promocodesByType,

        // Методы (Actions)
        loadPromocodes,
        activatePromocode,
        activateShopDiscount,
        createPromocode,
        removePromocode,

        // Сброс стора
        $reset: store.$reset,
    };
}
