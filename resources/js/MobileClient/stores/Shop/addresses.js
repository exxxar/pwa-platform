import { defineStore } from 'pinia'
import { apiRequest } from '../utils/api.js'

const BASE_ADDRESSES_LINK = '/addresses'

export const useAddressesStore = defineStore('addresses', {
    state: () => ({
        addresses: [],
        errors: []
    }),

    getters: {
        getAddresses: (state) => state.addresses || [],

        getDefaultAddress: (state) =>
            state.addresses.find(a => a.is_default) || null,
    },

    actions: {

        setErrors(errors = []) {
            this.errors = errors
        },

        setAddresses(payload) {
            this.addresses = payload || []

            localStorage.setItem('mypwa_addresses', JSON.stringify(this.addresses))
        },

        /**
         * Загрузка адресов
         */
        async loadAddresses() {
            try {
                const res = await apiRequest(BASE_ADDRESSES_LINK, 'GET')

                this.setAddresses(res.data)

                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        /**
         * Создание адреса
         */
        async storeAddress(payload = { form: null }) {
            try {
                const res = await apiRequest(BASE_ADDRESSES_LINK, 'POST', payload.form)

                await this.loadAddresses()

                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        /**
         * Обновление адреса
         */
        async updateAddress(payload = { id: null, form: null }) {
            try {
                const res = await apiRequest(
                    `${BASE_ADDRESSES_LINK}/${payload.id}`,
                    'PUT',
                    payload.form
                )

                await this.loadAddresses()

                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        /**
         * Удаление
         */
        async removeAddress(payload = { id: null }) {

            try {
                const res = await apiRequest(
                    `${BASE_ADDRESSES_LINK}/${payload.id}`,
                    'DELETE'
                )

                await this.loadAddresses()

                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        /**
         * Сделать дефолтным
         */
        async setDefaultAddress(payload = { id: null }) {
            try {
                const res = await apiRequest(
                    `${BASE_ADDRESSES_LINK}/${payload.id}/default`,
                    'POST'
                )

                await this.loadAddresses()

                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        /**
         * Локальная инициализация (из localStorage)
         */
        initStore() {
            const stored = localStorage.getItem('mypwa_addresses')

            if (stored) {
                this.addresses = JSON.parse(stored)
            }
        }
    }
})
