import { storeToRefs } from 'pinia';
import { useTablesStore } from '@/MobileClient/stores/Shop/tables.js';

/**
 * Composable для работы со столами и бронированиями
 */
export function useTables() {
    const store = useTablesStore();

    // ✅ ИСПРАВЛЕНИЕ 1: Извлекаем ВСЁ (состояние и геттеры) через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность.
    const {
        // --- Состояние (State) ---
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

        // --- Геттеры (Getters) ---
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
    } = storeToRefs(store);

    // ==========================================
    // ПАРАМЕТРИЗИРОВАННЫЕ ГЕТТЕРЫ / ХЕЛПЕРЫ
    // ==========================================
    const isTableLoading = (tableId) => store.isTableLoading(tableId);
    const isBookingLoading = (bookingId) => store.isBookingLoading(bookingId);
    const getTableById = (id) => store.getTableById(id);
    const getBookingById = (id) => store.getBookingById(id);

    // ==========================================
    // МЕТОДЫ (ACTIONS) С ОБРАБОТКОЙ ОШИБОК
    // ==========================================

    // --- Столы ---
    const loadTables = async (payload = {}) => {
        try { return await store.loadTables(payload); }
        catch (error) { console.error('[useTables] Ошибка загрузки столов:', error); throw error; }
    };

    const loadTableData = async (dataObject = {}) => {
        try { return await store.loadTableData({ dataObject }); }
        catch (error) { console.error('[useTables] Ошибка загрузки данных стола:', error); throw error; }
    };

    const loadCurrentTableData = async () => {
        try { return await store.loadCurrentTableData(); }
        catch (error) { console.error('[useTables] Ошибка загрузки текущего стола:', error); throw error; }
    };

    const createTable = async (tableForm) => {
        try { return await store.storeTables({ promoCodeForm: tableForm }); }
        catch (error) { console.error('[useTables] Ошибка создания стола:', error); throw error; }
    };

    const removeTable = async (tableId) => {
        try { return await store.removeTables({ promoCodeId: tableId }); }
        catch (error) { console.error('[useTables] Ошибка удаления стола:', error); throw error; }
    };

    // --- Бронирования ---
    const loadBookings = async (payload = {}) => {
        try { return await store.bookingList(payload); }
        catch (error) { console.error('[useTables] Ошибка загрузки бронирований:', error); throw error; }
    };

    const loadNearestBookings = async (payload = {}) => {
        try { return await store.nearestBookingList(payload); }
        catch (error) { console.error('[useTables] Ошибка загрузки ближайших бронирований:', error); throw error; }
    };

    const loadMyBookings = async () => {
        try { return await store.myUpcomingBookings(); }
        catch (error) { console.error('[useTables] Ошибка загрузки моих бронирований:', error); throw error; }
    };

    const bookTable = async (dataObject) => {
        try { return await store.bookATable({ dataObject }); }
        catch (error) { console.error('[useTables] Ошибка бронирования:', error); throw error; }
    };

    const cancelBooking = async (bookingId) => {
        try { return await store.cancelBookingTable({ bookingId }); }
        catch (error) { console.error('[useTables] Ошибка отмены бронирования:', error); throw error; }
    };

    // --- Заказы и официанты ---
    const callWaiter = async (dataObject = {}) => {
        try { return await store.requestWaiterComing({ dataObject }); }
        catch (error) { console.error('[useTables] Ошибка вызова официанта:', error); throw error; }
    };

    const changeWaiter = async (dataObject = {}) => {
        try { return await store.changeTableWaiter({ dataObject }); }
        catch (error) { console.error('[useTables] Ошибка смены официанта:', error); throw error; }
    };

    const closeTable = async (dataObject = {}) => {
        try { return await store.closeTableOrder({ dataObject }); }
        catch (error) { console.error('[useTables] Ошибка закрытия стола:', error); throw error; }
    };

    // ==========================================
    // ВОЗВРАЩАЕМЫЕ ЗНАЧЕНИЯ
    // ==========================================
    return {
        // Состояние (Refs)
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

        // Геттеры (Refs)
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

        // Параметризированные хелперы
        isTableLoading,
        isBookingLoading,
        getTableById,
        getBookingById,

        // Методы: Столы
        loadTables,
        loadTableData,
        loadCurrentTableData,
        clearCurrentTable: store.clearCurrentTable,
        createTable,
        removeTable,

        // Методы: Бронирования
        loadBookings,
        loadNearestBookings,
        loadMyBookings,
        bookTable,
        cancelBooking,
        exportNearestBookings: store.exportNearestBookings,

        // Методы: Заказы
        loadApprovedSelfTableBasket: store.loadApprovedSelfTableBasket,
        // ⚠️ ИСПРАВЛЕНИЕ 2: Проверьте опечатку в сторе. Было 'acceptTableOder', исправлено на 'acceptTableOrder'
        acceptTableOrder: store.acceptTableOrder || store.acceptTableOder,
        requestApproveTable: store.requestApproveTable,
        startTablePay: store.startTablePay,
        sendOrderToMyChat: store.sendOrderToMyChat,
        closeTable,

        // Методы: Официанты и услуги
        callWaiter,
        changeWaiter,
        storeTableAdditionalServices: store.storeTableAdditionalServices,

        // Сброс стора
        $reset: store.$reset,
    };
}
