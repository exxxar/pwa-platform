import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { usePromocodesStore } from '@/MobileClient/stores/Shop/promocodes.js';

/**
 * Composable для работы с промокодами
 */
export function usePromocodes() {
    const store = usePromocodesStore();

    // Реактивные ссылки на состояние
    const {
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
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedPromocodes = computed(() => store.sortedPromocodes);
    const activePromocodes = computed(() => store.activePromocodes);
    const expiredPromocodes = computed(() => store.expiredPromocodes);
    const upcomingPromocodes = computed(() => store.upcomingPromocodes);
    const almostExhaustedPromocodes = computed(() => store.almostExhaustedPromocodes);
    const promocodesCount = computed(() => store.promocodesCount);
    const activePromocodesCount = computed(() => store.activePromocodesCount);

    /**
     * Проверка, загружается ли промокод
     */
    const isPromocodeLoading = (promocodeId) => {
        return store.isPromocodeLoading(promocodeId);
    };

    // ==========================================
    // Безопасные методы
    // ==========================================

    const loadPromocodes = async (payload = {}) => {
        try {
            return await store.loadPromoCodes(payload);
        } catch (error) {
            console.error('Ошибка загрузки промокодов:', error);
            throw error;
        }
    };

    const activatePromocode = async (promocodeForm) => {
        try {
            return await store.activatePromocode({ promocodeForm });
        } catch (error) {
            console.error('Ошибка активации промокода:', error);
            throw error;
        }
    };

    const activateShopDiscount = async (promocodeForm) => {
        try {
            return await store.activateShopDiscountPromocode({ promocodeForm });
        } catch (error) {
            console.error('Ошибка активации скидки:', error);
            throw error;
        }
    };

    const createPromocode = async (promoCodeForm) => {
        try {
            return await store.storePromoCodes({ promoCodeForm });
        } catch (error) {
            console.error('Ошибка создания промокода:', error);
            throw error;
        }
    };

    const removePromocode = async (promocodeId) => {
        try {
            return await store.removePromoCodes({ promoCodeId: promocodeId });
        } catch (error) {
            console.error('Ошибка удаления промокода:', error);
            throw error;
        }
    };

    return {
        // Состояние
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

        // Геттеры
        sortedPromocodes,
        activePromocodes,
        expiredPromocodes,
        upcomingPromocodes,
        almostExhaustedPromocodes,
        promocodesCount,
        activePromocodesCount,
        getPromocodeById: store.getPromocodeById,
        getPromocodeByCode: store.getPromocodeByCode,
        promocodesByType: store.promocodesByType,
        isPromocodeLoading,

        // Методы
        loadPromocodes,
        activatePromocode,
        activateShopDiscount,
        createPromocode,
        removePromocode,

        // Сброс
        $reset: store.$reset,
    };
}
