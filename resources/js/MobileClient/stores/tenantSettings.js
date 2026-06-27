import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/admin/tenant-settings';

export const useTenantSettingsStore = defineStore('tenantSettings', {
    state: () => ({
        settings: null,
        company: null,

        isLoading: false,
        isHydrated: false,
        isSaving: false,
        savingSection: null,

        lastError: null,
        errors: {},

        dirtySections: new Set(),
    }),

    getters: {
        companySettings: (state) => state.company || {},
        tenantSettings: (state) => state.settings || {},

        // 🆕 Пункты меню
        menuItems: (state) => state.settings?.menu_items || {},

        // 🆕 Калькуляторы еды
        foodCalculators: (state) => state.settings?.food_calculators || {},

        // 🆕 Бонус-игры
        bonusGames: (state) => state.settings?.bonus_games || {},

        // 🆕 Только видимые калькуляторы (для фронта)
        visibleFoodCalculators: (state) => {
            const calculators = state.settings?.food_calculators || {};
            return Object.entries(calculators)
                .filter(([_, config]) => config.is_visible)
                .map(([key, config]) => ({ id: key, ...config }));
        },

        // 🆕 Только видимые игры (для фронта)
        visibleBonusGames: (state) => {
            const games = state.settings?.bonus_games || {};
            return Object.entries(games)
                .filter(([_, config]) => config.is_visible)
                .map(([key, config]) => ({ id: key, ...config }));
        },

        // Фильтры по категориям
        calculatorsByCategory: (state) => (category) => {
            const calculators = state.settings?.food_calculators || {};
            return Object.entries(calculators)
                .filter(([_, config]) => config.is_visible && config.category === category)
                .map(([key, config]) => ({ id: key, ...config }));
        },

        gamesByCategory: (state) => (category) => {
            const games = state.settings?.bonus_games || {};
            return Object.entries(games)
                .filter(([_, config]) => config.is_visible && config.category === category)
                .map(([key, config]) => ({ id: key, ...config }));
        },

        coffeeSettings: (state) => state.settings?.coffee || {},
        deliverySettings: (state) => state.settings?.delivery || {},
        cashbackSettings: (state) => ({
            max_percent: state.settings?.max_cashback_use_percent || 0,
            config: state.tenant?.cashback_config || [],
            warnings: state.tenant?.warnings || [],
        }),
        features: (state) => state.settings?.features || {},
        icons: (state) => state.settings?.icons || [],

        hasUnsavedChanges: (state) => state.dirtySections.size > 0,
        hasErrors: (state) => Object.keys(state.errors).length > 0,
    },

    actions: {
        async loadSettings() {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await axios.get(BASE);
                const data = response.data;

                this.settings = data.settings || {};
                this.company = data.company || {};
                this.isHydrated = true;

                return data;
            } catch (err) {
                console.error('[TenantSettings] Ошибка загрузки:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить настройки';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        async saveCompanyInfo(form) {
            return this._saveSection('company', form, `${BASE}/company`);
        },

        async saveCashbackSettings(form) {
            return this._saveSection('cashback', form, `${BASE}/cashback`);
        },

        async saveMenuSettings(menuItems) {
            return this._saveSection('menu', { menu_items: menuItems }, `${BASE}/menu`);
        },

        // 🆕 Сохранение настроек калькуляторов
        async saveFoodCalculatorsSettings(calculators) {
            return this._saveSection('food_calculators', { food_calculators: calculators }, `${BASE}/food-calculators`);
        },

        // 🆕 Сохранение настроек игр
        async saveBonusGamesSettings(games) {
            return this._saveSection('bonus_games', { bonus_games: games }, `${BASE}/bonus-games`);
        },
        async savePwaSettings(pwaForm) {
            return this._saveSection('pwa', { pwa: pwaForm }, `${BASE}/pwa`);
        },
        async saveIconsSettings(formData) {
            this.isSaving = true;
            this.savingSection = 'icons';

            try {
                const response = await axios.post(`${BASE}/icons`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                if (response.data?.settings) {
                    this.settings = { ...this.settings, ...response.data.settings };
                }

                this.dirtySections.delete('icons');
                return response.data;
            } catch (err) {
                console.error('[TenantSettings] Ошибка сохранения иконок:', err);
                this.lastError = err.response?.data?.message || 'Не удалось сохранить иконки';
                throw err;
            } finally {
                this.isSaving = false;
                this.savingSection = null;
            }
        },

        async _saveSection(section, data, url) {
            this.isSaving = true;
            this.savingSection = section;
            this.lastError = null;

            try {
                const response = await axios.post(url, data);

                if (response.data?.settings) {
                    this.settings = { ...this.settings, ...response.data.settings };
                }
                if (response.data?.company) {
                    this.company = { ...this.company, ...response.data.company };
                }
                if (response.data?.tenant && window.Tenant) {
                    Object.assign(window.Tenant, response.data.tenant);
                }

                this.dirtySections.delete(section);
                return response.data;
            } catch (err) {
                console.error(`[TenantSettings] Ошибка сохранения ${section}:`, err);
                this.lastError = err.response?.data?.message || `Не удалось сохранить ${section}`;
                this.errors[section] = err.response?.data?.errors || [];
                throw err;
            } finally {
                this.isSaving = false;
                this.savingSection = null;
            }
        },

        markDirty(section) {
            this.dirtySections.add(section);
        },

        markClean(section) {
            this.dirtySections.delete(section);
        },

        $reset() {
            this.settings = null;
            this.company = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isSaving = false;
            this.savingSection = null;
            this.lastError = null;
            this.errors = {};
            this.dirtySections = new Set();
        },
    },
});
