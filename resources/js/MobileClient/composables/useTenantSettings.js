import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import axios from 'axios';
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
    // ЛОКАЛЬНОЕ СОСТОЯНИЕ ДЛЯ СОХРАНЕНИЯ
    // ==========================================
    // Используем объект для отслеживания сохранения нескольких секций одновременно,
    // если в сторе хранится только одна активная секция
    const savingSections = ref({});

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

    const hasUnsavedChanges = computed(() => {
        // Если dirtySections это Set
        if (dirtySections.value instanceof Set) {
            return dirtySections.value.size > 0;
        }
        // Если это объект
        return Object.values(dirtySections.value || {}).some(val => val === true);
    });

    // ==========================================
    // ФИЛЬТРЫ ПО КАТЕГОРИЯМ
    // ==========================================
    const calculatorsByCategory = (category) => {
        return store.calculatorsByCategory ? store.calculatorsByCategory(category) : [];
    };

    const gamesByCategory = (category) => {
        return store.gamesByCategory ? store.gamesByCategory(category) : [];
    };

    // ==========================================
    // ВСПОМОГАТЕЛЬНАЯ ФУНКЦИЯ СОХРАНЕНИЯ
    // ==========================================
    const executeSave = async (section, url, payload) => {
        savingSections.value[section] = true;
        store.lastError = null;

        try {
            await axios.put(url, payload);

            // Очищаем флаг "грязной" секции
            if (dirtySections.value instanceof Set) {
                dirtySections.value.delete(section);
            } else if (dirtySections.value) {
                dirtySections.value[section] = false;
            }

            return true;
        } catch (error) {
            console.error(`[TenantSettings] Ошибка сохранения ${section}:`, error);
            store.lastError = error.response?.data?.message || 'Не удалось сохранить данные';
            throw error;
        } finally {
            savingSections.value[section] = false;
        }
    };

    // ==========================================
    // МЕТОДЫ СОХРАНЕНИЯ (API)
    // ==========================================

    const saveBasicInfo = async (data) => {
        return executeSave('basic', '/admin/tenant-settings/basic', data);
    };

    // Алиас для совместимости со старым названием в компоненте
    const saveCompanyInfo = async (data) => {
        return saveBasicInfo(data);
    };

    const saveShopSettings = async (data) => {
        return executeSave('shop', '/admin/tenant-settings/shop', data);
    };

    const saveCashbackSettings = async (data) => {
        return executeSave('cashback', '/admin/tenant-settings/cashback', data);
    };

    const saveInteractiveSettings = async (data) => {
        return executeSave('interactive', '/admin/tenant-settings/interactive', data);
    };

    const saveTablesSettings = async (data) => {
        return executeSave('tables', '/admin/tenant-settings/tables', data);
    };

    const saveMenuSettings = async (data) => {
        return executeSave('menu', '/admin/tenant-settings/menu', { menu_items: data });
    };

    const saveCalculatorsSettings = async (data) => {
        return executeSave('calculators', '/admin/tenant-settings/calculators', { food_calculators: data });
    };

    const saveGamesSettings = async (data) => {
        return executeSave('games', '/admin/tenant-settings/games', { bonus_games: data });
    };

    const saveCrmSettings = async (data) => {
        return executeSave('crm', '/admin/tenant-settings/crm', { crm: data });
    };

    const savePwaSettings = async (data) => {
        return executeSave('pwa', '/admin/tenant-settings/pwa', data);
    };

    // ==========================================
    // МЕТОДЫ ЗАГРУЗКИ И УПРАВЛЕНИЯ
    // ==========================================

    const loadSettings = async () => {
        try {
            return await store.loadSettings();
        } catch (error) {
            console.error('Ошибка загрузки настроек:', error);
            throw error;
        }
    };

    const saveFoodCalculatorsSettings = async (calculators) => {
        return saveCalculatorsSettings(calculators);
    };

    const saveBonusGamesSettings = async (games) => {
        return saveGamesSettings(games);
    };

    const saveIconsSettings = async (formData) => {
        // Для иконок используем POST и FormData, так как там файлы
        try {
            const response = await axios.post('/admin/tenant-settings/pwa/upload-icon', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            return response.data;
        } catch (error) {
            console.error('Ошибка сохранения иконок:', error);
            throw error;
        }
    };

    const isSectionSaving = (section) => {
        return !!savingSections.value[section];
    };

    const saveMainMenuSettings = async (data) => {
        savingSections.value.main_menu_items = true;
        try {
            await axios.put('/admin/tenant-settings/main-menu', { main_menu_items: data });
            if (dirtySections.value instanceof Set) {
                dirtySections.value.delete('main_menu_items');
            } else {
                dirtySections.value.main_menu_items = false;
            }
            return true;
        } catch (error) {
            console.error('Ошибка сохранения главного меню:', error);
            store.lastError = error.response?.data?.message || 'Не удалось сохранить';
            throw error;
        } finally {
            savingSections.value.main_menu_items = false;
        }
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
            dirtySections.value[section] = true;
        }
    };

    const markClean = (section) => {
        if (dirtySections.value instanceof Set) {
            dirtySections.value.delete(section);
        } else {
            dirtySections.value[section] = false;
        }
    };

    // ==========================================
    // ВОЗВРАЩАЕМЫЕ ЗНАЧЕНИЯ
    // ==========================================
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

        // Методы загрузки
        loadSettings,

        // Методы сохранения (унифицированные)
        saveCompanyInfo,      // Алиас для saveBasicInfo
        saveBasicInfo,
        saveShopSettings,
        saveCashbackSettings,
        saveInteractiveSettings,
        saveTablesSettings,
        saveMenuSettings,
        saveCalculatorsSettings,
        saveFoodCalculatorsSettings, // Алиас
        saveGamesSettings,
        saveBonusGamesSettings,      // Алиас
        saveCrmSettings,
        savePwaSettings,
        saveIconsSettings,
        saveMainMenuSettings,

        // Управление состоянием
        isSectionSaving,
        isSectionDirty,
        markDirty,
        markClean,

        $reset: store.$reset,
    };
}
