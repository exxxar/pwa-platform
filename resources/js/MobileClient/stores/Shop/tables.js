import {defineStore} from 'pinia'
import {apiRequest} from '../utils/api.js'

const BASE_TABLES_LINK = '/tables'

export const useTablesStore = defineStore('tables', {
    state: () => ({
        tables: [],
        tables_paginate_object: null,
    }),

    getters: {
        getTables: (state) => state.tables || [],
        getTablesPaginateObject: (state) => state.tables_paginate_object || null,
    },

    actions: {
        setTables(payload) {
            this.tables = payload || []
            localStorage.setItem('cashman_tables', JSON.stringify(payload))
        },

        setTablesPaginateObject(payload) {
            this.tables_paginate_object = payload || null
            localStorage.setItem('cashman_tables_paginate_object', JSON.stringify(payload))
        },

        async loadApprovedSelfTableBasket() {
            let link = `${BASE_TABLES_LINK}/approved-self-basket`
            return apiRequest(link, 'POST')
                .then(res => res.data)
        },

        async nearestBookingList(payload = {start_date: null, end_date: null}) {
            let link = `${BASE_TABLES_LINK}/nearest-booking-list`
            return apiRequest(link, 'POST', payload)
                .then(res => res.data)
        },

        async myUpcomingBookings() {
            let link = `${BASE_TABLES_LINK}/my-upcoming-bookings`
            return apiRequest(link, 'POST')
                .then(res => res.data)
        },

        async bookingList(payload = {date: null, number: null}) {
            let link = `${BASE_TABLES_LINK}/booking-list`
            return apiRequest(link, 'POST', payload)
                .then(res => res.data)
        },

        async bookATable(payload = {dataObject: {}}) {
            let link = `${BASE_TABLES_LINK}/book-table`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async exportNearestBookings(payload = {start_date: null, end_date: null}) {
            let link = `${BASE_TABLES_LINK}/export-nearest-bookings`
            return apiRequest(link, 'POST', payload)
                .then(res => res.data)
        },

        async cancelBookingTable(payload = {bookingId: null}) {
            let link = `${BASE_TABLES_LINK}/cancel-booking/${payload.bookingId}`
            return apiRequest(link, 'DELETE')
                .then(res => res.data)
        },

        async storeTableAdditionalServices(payload = {dataObject: null}) {
            let link = `${BASE_TABLES_LINK}/store-additional-service`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async acceptTableOder(payload = {dataObject: {}}) {
            let link = `${BASE_TABLES_LINK}/accept-table-order`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async requestApproveTable(payload = {dataObject: {}}) {
            let link = `${BASE_TABLES_LINK}/request-approve-table`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async startTablePay(payload) {
            let link = `${BASE_TABLES_LINK}/table-pay`
            return apiRequest(link, 'POST', payload)
                .then(res => res.data)
        },

        async sendOrderToMyChat(payload = {dataObject: {}}) {
            let link = `${BASE_TABLES_LINK}/send-order-to-my-chat`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async requestWaiterComing(payload = {dataObject: {}}) {
            let link = `${BASE_TABLES_LINK}/call-waiter`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async changeTableWaiter(payload = {dataObject: {}}) {
            let link = `${BASE_TABLES_LINK}/change-table-waiter`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async closeTableOrder(payload = {dataObject: {}}) {
            let link = `${BASE_TABLES_LINK}/close-table`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async loadTableData(payload = {dataObject: {}}) {
            let link = `${BASE_TABLES_LINK}/table-data`
            return apiRequest(link, 'POST', payload.dataObject)
                .then(res => res.data)
        },

        async loadCurrentTableData() {
            let storedTable = localStorage.getItem("cashman_current_active_table")
            storedTable = storedTable ? JSON.parse(storedTable) : null

            let link = `${BASE_TABLES_LINK}/current`
            return apiRequest(link, 'POST', {
                table_id: storedTable?.id || null
            }).then(res => res.data)
        },

        async loadTables(payload = {dataObject: null, page: 0, size: 50}) {
            let page = payload.page || 0
            let size = payload.size || 50

            let link = `${BASE_TABLES_LINK}/waiter-tables?page=${page}&size=${size}`

            return apiRequest(link, 'POST', payload.dataObject)
                .then((response) => {
                    let dataObject = response.data
                    this.setTables(dataObject.data)
                    delete dataObject.data
                    this.setTablesPaginateObject(dataObject)
                })
        },

        async storeTables(payload = {promoCodeForm: null}) {
            let link = `${BASE_TABLES_LINK}/store`
            return apiRequest(link, 'POST', payload.promoCodeForm)
                .then(res => res.data)
        },

        async removeTables(payload = {promoCodeId: null}) {
            let link = `${BASE_TABLES_LINK}/${payload.promoCodeId}`
            return apiRequest(link, 'DELETE')
                .then(res => res.data)
        },
    }
})
