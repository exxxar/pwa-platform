import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useAddressesStore } from '@/stores/addresses.js';

/**
 * Composable для работы с адресами
 */
export function useAddresses() {
    const store = useAddressesStore();

    // Реактивные ссылки на состояние
    const {
        addresses,
        isLoading,
        isHydrated,
        addressActions,
        lastError,
        errors,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedAddresses = computed(() => store.sortedAddresses);
    const activeAddresses = computed(() => store.activeAddresses);
    const defaultAddress = computed(() => store.getDefaultAddress);
    const addressesCount = computed(() => store.addressesCount);
    const hasDefaultAddress = computed(() => store.hasDefaultAddress);

    /**
     * Проверка, загружается ли адрес
     */
    const isAddressLoading = (addressId) => {
        return store.isAddressLoading(addressId);
    };

    // ==========================================
    // Безопасные методы
    // ==========================================

    /**
     * Загрузка адресов
     */
    const loadAddresses = async () => {
        try {
            return await store.loadAddresses();
        } catch (error) {
            console.error('Ошибка загрузки адресов:', error);
            throw error;
        }
    };

    /**
     * Создание адреса
     */
    const createAddress = async (form) => {
        try {
            return await store.storeAddress({ form });
        } catch (error) {
            console.error('Ошибка создания адреса:', error);
            throw error;
        }
    };

    /**
     * Обновление адреса
     */
    const updateAddress = async (id, form) => {
        try {
            return await store.updateAddress({ id, form });
        } catch (error) {
            console.error('Ошибка обновления адреса:', error);
            throw error;
        }
    };

    /**
     * Удаление адреса
     */
    const removeAddress = async (id) => {
        try {
            return await store.removeAddress({ id });
        } catch (error) {
            console.error('Ошибка удаления адреса:', error);
            throw error;
        }
    };

    /**
     * Сделать адрес дефолтным
     */
    const setDefault = async (id) => {
        try {
            return await store.setDefaultAddress({ id });
        } catch (error) {
            console.error('Ошибка установки дефолтного адреса:', error);
            throw error;
        }
    };

    return {
        // Состояние
        addresses,
        isLoading,
        isHydrated,
        addressActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры
        sortedAddresses,
        activeAddresses,
        defaultAddress,
        addressesCount,
        hasDefaultAddress,
        getAddressById: store.getAddressById,
        isAddressLoading,

        // Методы
        loadAddresses,
        createAddress,
        updateAddress,
        removeAddress,
        setDefault,

        // Инициализация
        initStore: store.initStore,

        // Сброс
        $reset: store.$reset,
    };
}
