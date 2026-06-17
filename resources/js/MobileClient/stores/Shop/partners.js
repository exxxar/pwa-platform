import { defineStore } from 'pinia'
import { apiRequest } from '../utils/api.js'

const BASE_PARTNERS_LINK = '/partners'

export const usePartnersStore = defineStore('partners', {
    state: () => ({
        partners: [],
        partners_paginate_object: null,
        errors: []
    }),

    getters: {
        getPartners: (state) => state.partners || [],
        getPartnersPaginateObject: (state) => state.partners_paginate_object || null,
    },

    actions: {

        setErrors(errors = []) {
            this.errors = errors
        },

        setPartners(payload) {
            this.partners = payload || []
            localStorage.setItem('mypwa_partners', JSON.stringify(this.partners))
        },

        setPartnersPaginateObject(payload) {
            this.partners_paginate_object = payload || null
            localStorage.setItem('mypwa_partners_paginate_object', JSON.stringify(this.partners_paginate_object))
        },

        async loadPartners(payload) {
            try {
                const response = await apiRequest(BASE_PARTNERS_LINK, 'POST', payload)

                const dataObject = response.data

                this.setPartners(dataObject.data)
                delete dataObject.data
                this.setPartnersPaginateObject(dataObject)

                return dataObject
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async updatePartnersSettings(payload) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/update-settings`, 'POST', payload)
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async updatePartnersActiveStatus(payload) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/update-active-status`, 'POST', payload)
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async updateSelfPartner(payload) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/update-self`, 'POST', payload.form)
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async updatePartner(payload) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/update`, 'POST', payload.form)
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async togglePartnerInFavorites(payload = { form: null }) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/toggle-favorite`, 'POST', payload.form)
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async listOfPartnersCategories(payload) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/partners-categories`, 'POST', payload)
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async changePartnerProductStatus(payload) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/change-status`, 'POST', payload)
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async storePartner(payload) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/store`, 'POST', payload.form)
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },

        async removePartner(payload = { partnerId: null }) {
            try {
                const res = await apiRequest(`${BASE_PARTNERS_LINK}/remove/${payload.partnerId}`, 'POST')
                return res.data
            } catch (err) {
                this.setErrors(err?.response?.data?.errors || [])
                throw err
            }
        },
    }
})
