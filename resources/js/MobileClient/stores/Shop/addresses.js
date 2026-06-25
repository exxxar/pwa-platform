import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/addresses';

export const useAddressesStore = defineStore('addresses', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        addresses: [],

        // Состояние загрузки
        isLoading: false,
        isHydrated: false,

        // Действия над адресами: { [addressId]: 'update' | 'delete' | 'set-default' }
        addressActions: {},

        // Ошибки
        lastError: null,
        errors: [],

        // Время последней синхронизации
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Все адреса
         */
        getAddresses: (state) => state.addresses || [],

        /**
         * Адрес по умолчанию
         */
        getDefaultAddress: (state) => {
            return (state.addresses || []).find(a => a.is_default) || null;
        },

        /**
         * Найти адрес по ID
         */
        getAddressById: (state) => (id) => {
            return (state.addresses || []).find(a => String(a.id) === String(id)) || null;
        },

        /**
         * Отсортированные адреса (дефолтный сверху, затем по названию)
         */
        sortedAddresses: (state) => {
            return [...(state.addresses || [])].sort((a, b) => {
                // Дефолтный адрес всегда сверху
                if (a.is_default && !b.is_default) return -1;
                if (!a.is_default && b.is_default) return 1;
                // Затем по названию
                const nameA = (a.title || a.name || a.address || '').toLowerCase();
                const nameB = (b.title || b.name || b.address || '').toLowerCase();
                return nameA.localeCompare(nameB);
            });
        },

        /**
         * Активные адреса (не удалённые)
         */
        activeAddresses: (state) => {
            return (state.addresses || []).filter(a => !a.is_deleted && a.is_active !== false);
        },

        /**
         * Проверка, загружается ли конкретный адрес
         */
        isAddressLoading: (state) => (id) => {
            return !!state.addressActions[String(id)];
        },

        /**
         * Количество адресов
         */
        addressesCount: (state) => state.addresses?.length || 0,

        /**
         * Есть ли адрес по умолчанию
         */
        hasDefaultAddress: (state) => {
            return (state.addresses || []).some(a => a.is_default);
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка списка адресов
         */
        async loadAddresses() {
            this.isLoading = true;
            this.lastError = null;
            this.errors = [];

            try {
                const response = await axios.get(BASE);
                const data = response.data?.data || response.data || [];

                this.addresses = Array.isArray(data) ? data : [];
                this.isHydrated = true;
                this.lastSyncAt = new Date();

                // Сохраняем в localStorage как fallback
                localStorage.setItem('mypwa_addresses', JSON.stringify(this.addresses));

                return this.addresses;
            } catch (err) {
                console.error('[Addresses Store] Ошибка загрузки адресов:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить адреса';
                this.errors = err.response?.data?.errors || [];

                // Fallback: пробуем загрузить из localStorage
                const cached = localStorage.getItem('mypwa_addresses');
                if (cached && this.addresses.length === 0) {
                    try {
                        this.addresses = JSON.parse(cached);
                    } catch {
                        this.addresses = [];
                    }
                }

                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Локальная инициализация (из localStorage)
         */
        initStore() {
            const stored = localStorage.getItem('mypwa_addresses');
            if (stored) {
                try {
                    this.addresses = JSON.parse(stored);
                } catch {
                    this.addresses = [];
                }
            }
        },

        // ==========================================
        // CRUD
        // ==========================================

        /**
         * Создание адреса
         */
        async storeAddress(payload = { form: null }) {
            this.lastError = null;

            try {
                const response = await axios.post(BASE, payload.form);
                const newAddress = response.data?.data || response.data;

                // Добавляем в начало списка (или в конец, если есть дефолтный)
                if (newAddress?.id) {
                    if (newAddress.is_default) {
                        // Снимаем флаг дефолтного с остальных
                        this.addresses.forEach(a => { a.is_default = false; });
                        this.addresses.unshift(newAddress);
                    } else {
                        this.addresses.push(newAddress);
                    }

                    // Сохраняем в localStorage
                    localStorage.setItem('mypwa_addresses', JSON.stringify(this.addresses));
                }

                return newAddress;
            } catch (err) {
                console.error('[Addresses Store] Ошибка создания адреса:', err);
                this.lastError = err.response?.data?.message || 'Не удалось создать адрес';
                this.errors = err.response?.data?.errors || [];
                throw err;
            }
        },

        /**
         * Обновление адреса (оптимистично)
         */
        async updateAddress(payload = { id: null, form: null }) {
            if (!payload.id) throw new Error('Не указан ID адреса');

            this.addressActions[String(payload.id)] = 'update';

            // Сохраняем предыдущее состояние для отката
            const address = this.getAddressById(payload.id);
            const previousState = address ? { ...address } : null;
            const index = this.addresses.findIndex(a => String(a.id) === String(payload.id));

            // Оптимистично обновляем
            if (address) {
                Object.assign(address, payload.form);
            }

            try {
                const response = await axios.put(`${BASE}/${payload.id}`, payload.form);
                const updated = response.data?.data || response.data;

                // Синхронизируем с ответом сервера
                if (address && updated) {
                    Object.assign(address, updated);
                }

                // Сохраняем в localStorage
                localStorage.setItem('mypwa_addresses', JSON.stringify(this.addresses));

                return updated;
            } catch (err) {
                // Откат
                if (address && previousState && index !== -1) {
                    this.addresses[index] = previousState;
                }
                console.error('[Addresses Store] Ошибка обновления адреса:', err);
                this.lastError = err.response?.data?.message || 'Не удалось обновить адрес';
                throw err;
            } finally {
                delete this.addressActions[String(payload.id)];
            }
        },

        /**
         * Удаление адреса (оптимистично)
         */
        async removeAddress(payload = { id: null }) {
            if (!payload.id) throw new Error('Не указан ID адреса');

            this.addressActions[String(payload.id)] = 'delete';

            // Сохраняем для отката
            const removedIndex = this.addresses.findIndex(a => String(a.id) === String(payload.id));
            const removedAddress = removedIndex !== -1 ? this.addresses[removedIndex] : null;

            // Оптимистично удаляем
            if (removedIndex !== -1) {
                this.addresses.splice(removedIndex, 1);
            }

            try {
                const response = await axios.delete(`${BASE}/${payload.id}`);

                // Сохраняем в localStorage
                localStorage.setItem('mypwa_addresses', JSON.stringify(this.addresses));

                return response.data;
            } catch (err) {
                // Откат
                if (removedAddress && removedIndex !== -1) {
                    this.addresses.splice(removedIndex, 0, removedAddress);
                }
                console.error('[Addresses Store] Ошибка удаления адреса:', err);
                this.lastError = err.response?.data?.message || 'Не удалось удалить адрес';
                throw err;
            } finally {
                delete this.addressActions[String(payload.id)];
            }
        },

        /**
         * Сделать адрес дефолтным (оптимистично)
         */
        async setDefaultAddress(payload = { id: null }) {
            if (!payload.id) throw new Error('Не указан ID адреса');

            this.addressActions[String(payload.id)] = 'set-default';

            // Сохраняем предыдущее состояние для отката
            const previousDefaults = this.addresses.map(a => ({
                id: a.id,
                is_default: a.is_default,
            }));

            // Оптимистично переключаем
            this.addresses.forEach(a => {
                a.is_default = String(a.id) === String(payload.id);
            });

            try {
                const response = await axios.post(`${BASE}/${payload.id}/default`);
                const updated = response.data?.data || response.data;

                // Синхронизируем с сервером
                if (updated) {
                    const index = this.addresses.findIndex(a => String(a.id) === String(payload.id));
                    if (index !== -1) {
                        Object.assign(this.addresses[index], updated);
                    }
                }

                // Сохраняем в localStorage
                localStorage.setItem('mypwa_addresses', JSON.stringify(this.addresses));

                return updated;
            } catch (err) {
                // Откат
                previousDefaults.forEach(({ id, is_default }) => {
                    const address = this.getAddressById(id);
                    if (address) address.is_default = is_default;
                });
                console.error('[Addresses Store] Ошибка установки дефолтного адреса:', err);
                this.lastError = err.response?.data?.message || 'Не удалось установить адрес по умолчанию';
                throw err;
            } finally {
                delete this.addressActions[String(payload.id)];
            }
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.addresses = [];
            this.isLoading = false;
            this.isHydrated = false;
            this.addressActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('mypwa_addresses');
        },
    },
});
