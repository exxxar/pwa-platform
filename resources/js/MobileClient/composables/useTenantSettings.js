import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import axios from 'axios';
import { useTenantSettingsStore } from '@/MobileClient/stores/tenantSettings.js';

export function useTenantSettings() {
    const store = useTenantSettingsStore();

    // ✅ ИСПРАВЛЕНИЕ 1: Извлекаем ВСЁ (состояние и геттеры) через storeToRefs.
    // Импорт 'ref' для savingSections больше не нужен, используем состояние стора.
    const {
        // --- Состояние (State) ---
        settings,
        company,
        isLoading,
        isHydrated,
        isSaving,       // Используем флаг стора вместо локального ref
        savingSection,  // Используем флаг стора вместо локального ref
        lastError,
        errors,
        dirtySections,

        // --- Геттеры (Getters) ---
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
    } = storeToRefs(store);

    // ==========================================
    // ВЫЧИСЛЯЕМЫЕ СВОЙСТВА (CUSTOM GETTERS)
    // ==========================================

    const hasUnsavedChanges = computed(() => {
        if (dirtySections.value instanceof Set) {
            return dirtySections.value.size > 0;
        }
        return Object.values(dirtySections.value || {}).some(val => val === true);
    });

    // ==========================================
    // ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ (Helpers)
    // ==========================================

    const calculatorsByCategory = (category) => {
        return store.calculatorsByCategory ? store.calculatorsByCategory(category) : [];
    };

    const gamesByCategory = (category) => {
        return store.gamesByCategory ? store.gamesByCategory(category) : [];
    };

    const isSectionDirty = (section) => {
        if (dirtySections.value instanceof Set) {
            return dirtySections.value.has(section);
        }
        return !!dirtySections.value?.[section];
    };

    const markDirty = (section) => {
        if (dirtySections.value instanceof Set) {
            dirtySections.value.add(section);
        } else {
            // ✅ Безопасная мутация реактивного объекта
            dirtySections.value = { ...dirtySections.value, [section]: true };
        }
    };

    const markClean = (section) => {
        if (dirtySections.value instanceof Set) {
            dirtySections.value.delete(section);
        } else if (dirtySections.value) {
            // ✅ Безопасная мутация реактивного объекта
            dirtySections.value = { ...dirtySections.value, [section]: false };
        }
    };

    // ==========================================
    // ЯДРО СОХРАНЕНИЯ (DRY Principle)
    // ==========================================

    const executeSave = async (section, url, payload, method = 'put') => {
        // ✅ Используем реактивные свойства стора вместо локальных переменных
        store.savingSection = section;
        store.lastError = null;

        try {
            const config = method === 'post' && payload instanceof FormData
                ? { headers: { 'Content-Type': 'multipart/form-data' } }
                : {};

            const request = method === 'post' ? axios.post(url, payload, config) : axios.put(url, payload, config);
            const response = await request;

            markClean(section);
            return response.data;
        } catch (error) {
            console.error(`[TenantSettings] Ошибка сохранения ${section}:`, error);
            store.lastError = error.response?.data?.message || 'Не удалось сохранить данные';
            throw error;
        } finally {
            store.savingSection = null;
        }
    };

    // ==========================================
    // МЕТОДЫ СОХРАНЕНИЯ (API Wrappers)
    // ==========================================
    // Примечание: В идеале эти запросы должны быть внутри actions самого Pinia store.
    // Но если архитектура требует их здесь, этот подход максимально чистый.

    const saveBasicInfo = (data) => executeSave('basic', '/admin/tenant-settings/basic', data);
    const saveCompanyInfo = (data) => saveBasicInfo(data); // Алиас

    const saveShopSettings = (data) => executeSave('shop', '/admin/tenant-settings/shop', data);
    const saveCashbackSettings = (data) => executeSave('cashback', '/admin/tenant-settings/cashback', data);
    const saveInteractiveSettings = (data) => executeSave('interactive', '/admin/tenant-settings/interactive', data);
    const saveTablesSettings = (data) => executeSave('tables', '/admin/tenant-settings/tables', data);

    const saveMenuSettings = (data) => executeSave('menu', '/admin/tenant-settings/menu', { menu_items: data });
    const saveCalculatorsSettings = (data) => executeSave('calculators', '/admin/tenant-settings/calculators', { food_calculators: data });
    const saveFoodCalculatorsSettings = (data) => saveCalculatorsSettings(data); // Алиас

    const saveGamesSettings = (data) => executeSave('games', '/admin/tenant-settings/games', { bonus_games: data });
    const saveBonusGamesSettings = (data) => saveGamesSettings(data); // Алиас

    const saveCrmSettings = (data) => executeSave('crm', '/admin/tenant-settings/crm', { crm: data });
    const savePwaSettings = (data) => executeSave('pwa', '/admin/tenant-settings/pwa', data);

    const saveIconsSettings = (formData) => executeSave('icons', '/admin/tenant-settings/pwa/upload-icon', formData, 'post');

    const saveMainMenuSettings = (data) => executeSave('main_menu_items', '/admin/tenant-settings/main-menu', { main_menu_items: data });

    // ==========================================
    // МЕТОДЫ ЗАГРУЗКИ
    // ==========================================

    const loadSettings = async () => {
        try {
            return await store.loadSettings();
        } catch (error) {
            console.error('[TenantSettings] Ошибка загрузки настроек:', error);
            throw error;
        }
    };

    // ==========================================
    // ВОЗВРАЩАЕМЫЕ ЗНАЧЕНИЯ
    // ==========================================
    return {
        // Состояние (Refs)
        settings,
        company,
        isLoading,
        isHydrated,
        isSaving,
        savingSection,
        lastError,
        errors,
        dirtySections,

        // Геттеры (Refs)
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

        // Управление состоянием "грязных" секций
        isSectionDirty,
        markDirty,
        markClean,

        // Методы
        loadSettings,
        saveBasicInfo,
        saveCompanyInfo,
        saveShopSettings,
        saveCashbackSettings,
        saveInteractiveSettings,
        saveTablesSettings,
        saveMenuSettings,
        saveCalculatorsSettings,
        saveFoodCalculatorsSettings,
        saveGamesSettings,
        saveBonusGamesSettings,
        saveCrmSettings,
        savePwaSettings,
        saveIconsSettings,
        saveMainMenuSettings,

        // Сброс
        $reset: store.$reset,
    };
}
