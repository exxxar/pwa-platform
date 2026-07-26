import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useTablesStore } from '@/MobileClient/stores/Shop/tables.js';

/**
 * Composable для работы со столами и бронированиями
 */
export function useTables() {
    const store = useTablesStore();

    // Реактивные ссылки на состояние
    const {
        tables,
        tables_paginate_object,
        currentTable,
        bookings,
        myBookings,
        nearestBookings,
        additionalServices,
        isLoading,
        isHydrated,
        isTablesLoading,
        isBookingsLoading,
        isMyBookingsLoading,
        isNearestBookingsLoading,
        isCurrentTableLoading,
        isOrdersLoading,
        isWaiterLoading,
        tableActions,
        bookingActions,
        lastError,
        errors,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedTables = computed(() => store.tables);
    const availableTables = computed(() => store.availableTables);
    const occupiedTables = computed(() => store.occupiedTables);
    const closedTables = computed(() => store.closedTables);
    const upcomingBookings = computed(() => store.upcomingBookings);
    const todayBookings = computed(() => store.todayBookings);
    const activeBookings = computed(() => store.activeBookings);
    const tablesCount = computed(() => store.tablesCount);
    const availableTablesCount = computed(() => store.availableTablesCount);
    const upcomingBookingsCount = computed(() => store.upcomingBookingsCount);

    /**
     * Проверка, загружается ли стол
     */
    const isTableLoading = (tableId) => {
        return store.isTableLoading(tableId);
    };

    /**
     * Проверка, загружается ли бронирование
     */
    const isBookingLoading = (bookingId) => {
        return store.isBookingLoading(bookingId);
    };

    // ==========================================
    // Безопасные методы: Столы
    // ==========================================

    const loadTables = async (payload = {}) => {
        try {
            return await store.loadTables(payload);
        } catch (error) {
            console.error('Ошибка загрузки столов:', error);
            throw error;
        }
    };

    const loadTableData = async (dataObject = {}) => {
        try {
            return await store.loadTableData({ dataObject });
        } catch (error) {
            console.error('Ошибка загрузки данных стола:', error);
            throw error;
        }
    };

    const loadCurrentTableData = async () => {
        try {
            return await store.loadCurrentTableData();
        } catch (error) {
            console.error('Ошибка загрузки текущего стола:', error);
            throw error;
        }
    };

    const createTable = async (tableForm) => {
        try {
            return await store.storeTables({ promoCodeForm: tableForm });
        } catch (error) {
            console.error('Ошибка создания стола:', error);
            throw error;
        }
    };

    const removeTable = async (tableId) => {
        try {
            return await store.removeTables({ promoCodeId: tableId });
        } catch (error) {
            console.error('Ошибка удаления стола:', error);
            throw error;
        }
    };

    // ==========================================
    // Безопасные методы: Бронирования
    // ==========================================

    const loadBookings = async (payload = {}) => {
        try {
            return await store.bookingList(payload);
        } catch (error) {
            console.error('Ошибка загрузки бронирований:', error);
            throw error;
        }
    };

    const loadNearestBookings = async (payload = {}) => {
        try {
            return await store.nearestBookingList(payload);
        } catch (error) {
            console.error('Ошибка загрузки ближайших бронирований:', error);
            throw error;
        }
    };

    const loadMyBookings = async () => {
        try {
            return await store.myUpcomingBookings();
        } catch (error) {
            console.error('Ошибка загрузки моих бронирований:', error);
            throw error;
        }
    };

    const bookTable = async (dataObject) => {
        try {
            return await store.bookATable({ dataObject });
        } catch (error) {
            console.error('Ошибка бронирования:', error);
            throw error;
        }
    };

    const cancelBooking = async (bookingId) => {
        try {
            return await store.cancelBookingTable({ bookingId });
        } catch (error) {
            console.error('Ошибка отмены бронирования:', error);
            throw error;
        }
    };

    // ==========================================
    // Безопасные методы: Заказы и официанты
    // ==========================================

    const callWaiter = async (dataObject = {}) => {
        try {
            return await store.requestWaiterComing({ dataObject });
        } catch (error) {
            console.error('Ошибка вызова официанта:', error);
            throw error;
        }
    };

    const changeWaiter = async (dataObject = {}) => {
        try {
            return await store.changeTableWaiter({ dataObject });
        } catch (error) {
            console.error('Ошибка смены официанта:', error);
            throw error;
        }
    };

    const closeTable = async (dataObject = {}) => {
        try {
            return await store.closeTableOrder({ dataObject });
        } catch (error) {
            console.error('Ошибка закрытия стола:', error);
            throw error;
        }
    };

    return {
        // Состояние
        tables,
        tables_paginate_object,
        currentTable,
        bookings,
        myBookings,
        nearestBookings,
        additionalServices,
        isLoading,
        isHydrated,
        isTablesLoading,
        isBookingsLoading,
        isMyBookingsLoading,
        isNearestBookingsLoading,
        isCurrentTableLoading,
        isOrdersLoading,
        isWaiterLoading,
        tableActions,
        bookingActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры
        sortedTables,
        availableTables,
        occupiedTables,
        closedTables,
        upcomingBookings,
        todayBookings,
        activeBookings,
        tablesCount,
        availableTablesCount,
        upcomingBookingsCount,
        getTableById: store.getTableById,
        getBookingById: store.getBookingById,
        isTableLoading,
        isBookingLoading,

        // Столы
        loadTables,
        loadTableData,
        loadCurrentTableData,
        clearCurrentTable: store.clearCurrentTable,
        createTable,
        removeTable,

        // Бронирования
        loadBookings,
        loadNearestBookings,
        loadMyBookings,
        bookTable,
        cancelBooking,
        exportNearestBookings: store.exportNearestBookings,

        // Заказы
        loadApprovedSelfTableBasket: store.loadApprovedSelfTableBasket,
        acceptTableOrder: store.acceptTableOder,
        requestApproveTable: store.requestApproveTable,
        startTablePay: store.startTablePay,
        sendOrderToMyChat: store.sendOrderToMyChat,
        closeTable,

        // Официанты
        callWaiter,
        changeWaiter,

        // Доп. услуги
        storeTableAdditionalServices: store.storeTableAdditionalServices,

        // Сброс
        $reset: store.$reset,
    };
}
