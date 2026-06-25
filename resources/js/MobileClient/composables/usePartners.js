import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { usePartnersStore } from '@/stores/partners.js';

/**
 * Composable для работы с партнёрами
 */
export function usePartners() {
    const store = usePartnersStore();

    // Реактивные ссылки на состояние
    const {
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
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedPartners = computed(() => store.sortedPartners);
    const activePartners = computed(() => store.activePartners);
    const favoritePartners = computed(() => store.favoritePartners);
    const partnersCount = computed(() => store.partnersCount);
    const getCategories = computed(() => store.getCategories);

    /**
     * Проверка, является ли партнёр избранным (реактивно)
     */
    const isFavorite = (partnerId) => {
        return store.isFavorite(partnerId);
    };

    /**
     * Проверка, загружается ли конкретный партнёр
     */
    const isPartnerLoading = (partnerId) => {
        return store.isPartnerLoading(partnerId);
    };

    /**
     * Безопасная загрузка партнёров
     */
    const loadPartners = async (payload = {}) => {
        try {
            return await store.loadPartners(payload);
        } catch (error) {
            console.error('Ошибка загрузки партнёров:', error);
            throw error;
        }
    };

    /**
     * Безопасная загрузка категорий
     */
    const loadCategories = async (payload = {}) => {
        try {
            return await store.loadCategories(payload);
        } catch (error) {
            console.error('Ошибка загрузки категорий:', error);
            throw error;
        }
    };

    /**
     * Безопасное переключение избранного
     */
    const toggleFavorite = async (partnerId) => {
        try {
            return await store.togglePartnerInFavorites({
                form: { id: partnerId, partner_id: partnerId },
            });
        } catch (error) {
            console.error('Ошибка переключения избранного:', error);
            throw error;
        }
    };

    /**
     * Безопасное удаление партнёра
     */
    const removePartner = async (partnerId) => {
        try {
            return await store.removePartner({ partnerId });
        } catch (error) {
            console.error('Ошибка удаления партнёра:', error);
            throw error;
        }
    };

    /**
     * Переключение активности
     */
    const toggleActive = async (partnerId) => {
        try {
            return await store.updatePartnersActiveStatus({
                id: partnerId,
                partner_id: partnerId,
            });
        } catch (error) {
            console.error('Ошибка переключения активности:', error);
            throw error;
        }
    };

    return {
        // Состояние
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

        // Геттеры
        sortedPartners,
        activePartners,
        favoritePartners,
        partnersCount,
        getCategories,
        isFavorite,
        isPartnerLoading,
        getPartnerById: store.getPartnerById,

        // Методы
        loadPartners,
        loadCategories,
        loadSelfPartner: store.loadSelfPartner,
        storePartner: store.storePartner,
        updatePartner: store.updatePartner,
        updateSelfPartner: store.updateSelfPartner,
        removePartner,
        toggleFavorite,
        toggleActive,
        updatePartnersSettings: store.updatePartnersSettings,
        changePartnerProductStatus: store.changePartnerProductStatus,

        // Сброс
        $reset: store.$reset,
    };
}
