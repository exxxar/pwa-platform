import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/profile-form';

export const useProfileFormStore = defineStore('profileForm', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные профиля
        profileData: null,

        // Состояние загрузки
        isLoading: false,
        isHydrated: false,
        isSaving: false,

        // Ошибки
        lastError: null,
        errors: [],

        // Время последней синхронизации
        lastSyncAt: null,

        // Отслеживание изменений (для индикации "несохранённых данных")
        isDirty: false,
        _originalData: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Данные профиля
         */
        getProfileData: (state) => state.profileData,

        /**
         * Имя пользователя
         */
        userName: (state) => {
            return state.profileData?.name
                || state.profileData?.fio
                || state.profileData?.fio_from_telegram
                || null;
        },

        /**
         * Телефон пользователя
         */
        userPhone: (state) => state.profileData?.phone || null,

        /**
         * Email пользователя
         */
        userEmail: (state) => state.profileData?.email || null,

        /**
         * Адрес пользователя
         */
        userAddress: (state) => state.profileData?.address || null,

        /**
         * Дата рождения
         */
        userBirthday: (state) => state.profileData?.birthday || null,

        /**
         * Пол
         */
        userSex: (state) => state.profileData?.sex ?? null,

        /**
         * Город
         */
        userCity: (state) => state.profileData?.city || null,

        /**
         * Страна
         */
        userCountry: (state) => state.profileData?.country || null,

        /**
         * Заполнен ли профиль (минимальный набор данных)
         */
        isProfileComplete: (state) => {
            const data = state.profileData;
            if (!data) return false;
            return !!(data.name || data.phone || data.email);
        },

        /**
         * Процент заполненности профиля
         */
        profileCompletionPercent: (state) => {
            const data = state.profileData;
            if (!data) return 0;

            const fields = [
                'name', 'phone', 'email', 'birthday',
                'sex', 'city', 'country', 'address'
            ];
            const filled = fields.filter(field => {
                const value = data[field];
                return value !== null && value !== undefined && value !== '';
            }).length;

            return Math.round((filled / fields.length) * 100);
        },

        /**
         * Есть ли несохранённые изменения
         */
        hasUnsavedChanges: (state) => state.isDirty,
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка данных профиля
         */
        async loadProfileFormData() {
            this.isLoading = true;
            this.lastError = null;
            this.errors = [];

            try {
                const response = await axios.post(`${BASE}/load-profile-data`);
                const data = response.data?.data || response.data;

                this.profileData = data;
                this._originalData = data ? { ...data } : null;
                this.isHydrated = true;
                this.isDirty = false;
                this.lastSyncAt = new Date();

                // Сохраняем в localStorage
                localStorage.setItem('cashman_profile_data', JSON.stringify(data));

                return data;
            } catch (err) {
                console.error('[ProfileForm Store] Ошибка загрузки профиля:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить данные профиля';
                this.errors = err.response?.data?.errors || [];

                // Fallback: пробуем загрузить из localStorage
                const cached = localStorage.getItem('cashman_profile_data');
                if (cached && !this.profileData) {
                    try {
                        const parsed = JSON.parse(cached);
                        this.profileData = parsed;
                        this._originalData = parsed ? { ...parsed } : null;
                    } catch {
                        this.profileData = null;
                    }
                }

                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        // ==========================================
        // СОХРАНЕНИЕ
        // ==========================================

        /**
         * Сохранение данных профиля (оптимистично)
         */
        async saveProfileFormData(payload = { dataObject: null }) {
            this.isSaving = true;
            this.lastError = null;

            // Сохраняем предыдущее состояние для отката
            const previousData = this.profileData ? { ...this.profileData } : null;

            // Оптимистично обновляем
            if (payload.dataObject) {
                this.profileData = {
                    ...this.profileData,
                    ...payload.dataObject
                };
            }

            try {
                const response = await axios.post(
                    `${BASE}/store-profile-data`,
                    payload.dataObject
                );
                const savedData = response.data?.data || response.data;

                // Синхронизируем с ответом сервера
                if (savedData) {
                    this.profileData = { ...this.profileData, ...savedData };
                    this._originalData = { ...this.profileData };
                }

                this.isDirty = false;
                this.lastSyncAt = new Date();

                // Сохраняем в localStorage
                localStorage.setItem('cashman_profile_data', JSON.stringify(this.profileData));

                return savedData;
            } catch (err) {
                // Откат
                if (previousData) {
                    this.profileData = previousData;
                }
                console.error('[ProfileForm Store] Ошибка сохранения профиля:', err);
                this.lastError = err.response?.data?.message || 'Не удалось сохранить данные';
                this.errors = err.response?.data?.errors || [];
                throw err;
            } finally {
                this.isSaving = false;
            }
        },

        /**
         * Обновление локальных данных (без сохранения на сервер)
         * Используется для формы редактирования
         */
        updateLocalProfileData(data) {
            if (!data) return;
            this.profileData = { ...this.profileData, ...data };
            this.isDirty = true;
        },

        /**
         * Отмена изменений (возврат к исходным данным)
         */
        discardChanges() {
            if (this._originalData) {
                this.profileData = { ...this._originalData };
            }
            this.isDirty = false;
        },

        /**
         * Обновление конкретного поля
         */
        updateField(field, value) {
            if (!this.profileData) {
                this.profileData = {};
            }
            this.profileData[field] = value;
            this.isDirty = true;
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.profileData = null;
            this._originalData = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isSaving = false;
            this.isDirty = false;
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('cashman_profile_data');
        },
    },
});
