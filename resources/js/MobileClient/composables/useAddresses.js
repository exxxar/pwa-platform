import { storeToRefs } from 'pinia';
import { useAddressesStore } from '@/stores/addresses.js';

/**
 * Composable для работы с адресами
 */
export function useAddresses() {
    const store = useAddressesStore();

    // ✅ ИСПРАВЛЕНИЕ 1: Извлекаем ВСЁ (состояние и геттеры) через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность.
    const {
        // --- Состояние (State) ---
        addresses,
        isLoading,
        isHydrated,
        addressActions,
        lastError,
        errors,
        lastSyncAt,

        // --- Геттеры (Getters) ---
        sortedAddresses,
        activeAddresses,
        // Алиас: в сторе геттер называется getDefaultAddress, но мы возвращаем его как defaultAddress для совместимости
        getDefaultAddress: defaultAddress,
        addressesCount,
        hasDefaultAddress,
    } = storeToRefs(store);

    // ==========================================
    // ПАРАМЕТРИЗИРОВАННЫЕ ХЕЛПЕРЫ
    // ==========================================
    const isAddressLoading = (addressId) => store.isAddressLoading(addressId);
    const getAddressById = (id) => store.getAddressById(id);

    // ==========================================
    // МЕТОДЫ (ACTIONS) С ОБРАБОТКОЙ ОШИБОК
    // ==========================================

    const loadAddresses = async () => {
        try {
            return await store.loadAddresses();
        } catch (error) {
            console.error('[useAddresses] Ошибка загрузки адресов:', error);
            throw error;
        }
    };

    const createAddress = async (form) => {
        try {
            return await store.storeAddress({ form });
        } catch (error) {
            console.error('[useAddresses] Ошибка создания адреса:', error);
            throw error;
        }
    };

    const updateAddress = async (id, form) => {
        try {
            return await store.updateAddress({ id, form });
        } catch (error) {
            console.error(`[useAddresses] Ошибка обновления адреса ${id}:`, error);
            throw error;
        }
    };

    const removeAddress = async (id) => {
        try {
            return await store.removeAddress({ id });
        } catch (error) {
            console.error(`[useAddresses] Ошибка удаления адреса ${id}:`, error);
            throw error;
        }
    };

    const setDefault = async (id) => {
        try {
            return await store.setDefaultAddress({ id });
        } catch (error) {
            console.error(`[useAddresses] Ошибка установки дефолтного адреса ${id}:`, error);
            throw error;
        }
    };

    // ==========================================
    // ВОЗВРАЩАЕМЫЕ ЗНАЧЕНИЯ
    // ==========================================
    return {
        // Состояние (Refs)
        addresses,
        isLoading,
        isHydrated,
        addressActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры (Refs)
        sortedAddresses,
        activeAddresses,
        defaultAddress,
        addressesCount,
        hasDefaultAddress,

        // Параметризированные хелперы
        isAddressLoading,
        getAddressById,

        // Методы (Actions)
        loadAddresses,
        createAddress,
        updateAddress,
        removeAddress,
        setDefault,

        // Инициализация
        initStore: store.initStore,

        // Сброс стора
        $reset: store.$reset,
    };
}
