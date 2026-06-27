import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useTenantSettingsStore } from '@/MobileClient/stores/tenantSettings.js';

export function useTenantSettings() {
    const store = useTenantSettingsStore();

    const {
        settings,
        company,
        isLoading,
        isHydrated,
        isSaving,
        savingSection,
        lastError,
        errors,
        dirtySections,
    } = storeToRefs(store);

    // ==========================================
    // ГЕТТЕРЫ
    // ==========================================

    const menuItems = computed(() => store.menuItems);
    const foodCalculators = computed(() => store.foodCalculators);
    const bonusGames = computed(() => store.bonusGames);
    const visibleFoodCalculators = computed(() => store.visibleFoodCalculators);
    const visibleBonusGames = computed(() => store.visibleBonusGames);
    const coffeeSettings = computed(() => store.coffeeSettings);
    const deliverySettings = computed(() => store.deliverySettings);
    const cashbackSettings = computed(() => store.cashbackSettings);
    const features = computed(() => store.features);
    const icons = computed(() => store.icons);
    const hasUnsavedChanges = computed(() => store.hasUnsavedChanges);

    // ==========================================
    // ФИЛЬТРЫ ПО КАТЕГОРИЯМ
    // ==========================================

    const calculatorsByCategory = (category) => {
        return store.calculatorsByCategory(category);
    };

    const gamesByCategory = (category) => {
        return store.gamesByCategory(category);
    };

    // ==========================================
    // МЕТОДЫ
    // ==========================================

    const savePwaSettings = async (pwaForm) => {
        try {
            return await store.savePwaSettings(pwaForm);
        } catch (error) {
            console.error('Ошибка сохранения PWA:', error);
            throw error;
        }
    };

    const loadSettings = async () => {
        try {
            return await store.loadSettings();
        } catch (error) {
            console.error('Ошибка загрузки настроек:', error);
            throw error;
        }
    };

    const saveCompanyInfo = async (form) => {
        try {
            return await store.saveCompanyInfo(form);
        } catch (error) {
            console.error('Ошибка сохранения компании:', error);
            throw error;
        }
    };

    const saveCashbackSettings = async (form) => {
        try {
            return await store.saveCashbackSettings(form);
        } catch (error) {
            console.error('Ошибка сохранения кэшбэка:', error);
            throw error;
        }
    };

    const saveMenuSettings = async (menuItems) => {
        try {
            return await store.saveMenuSettings(menuItems);
        } catch (error) {
            console.error('Ошибка сохранения меню:', error);
            throw error;
        }
    };

    const saveFoodCalculatorsSettings = async (calculators) => {
        try {
            return await store.saveFoodCalculatorsSettings(calculators);
        } catch (error) {
            console.error('Ошибка сохранения калькуляторов:', error);
            throw error;
        }
    };

    const saveBonusGamesSettings = async (games) => {
        try {
            return await store.saveBonusGamesSettings(games);
        } catch (error) {
            console.error('Ошибка сохранения игр:', error);
            throw error;
        }
    };

    const saveIconsSettings = async (formData) => {
        try {
            return await store.saveIconsSettings(formData);
        } catch (error) {
            console.error('Ошибка сохранения иконок:', error);
            throw error;
        }
    };

    const isSectionSaving = (section) => {
        return isSaving.value && savingSection.value === section;
    };

    const isSectionDirty = (section) => {
        return dirtySections.value.has(section);
    };

    return {
        // Состояние
        settings,
        company,
        isLoading,
        isHydrated,
        isSaving,
        savingSection,
        lastError,
        errors,
        dirtySections,

        // Геттеры
        menuItems,
        foodCalculators,
        bonusGames,
        visibleFoodCalculators,
        visibleBonusGames,
        coffeeSettings,
        deliverySettings,
        cashbackSettings,
        features,
        icons,
        hasUnsavedChanges,

        // Фильтры
        calculatorsByCategory,
        gamesByCategory,

        // Методы
        loadSettings,
        saveCompanyInfo,
        saveCashbackSettings,
        saveMenuSettings,
        saveFoodCalculatorsSettings,
        saveBonusGamesSettings,
        saveIconsSettings,
        isSectionSaving,
        isSectionDirty,
        savePwaSettings,
        markDirty: store.markDirty,
        markClean: store.markClean,

        $reset: store.$reset,
    };
}
