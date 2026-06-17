import { defineStore } from 'pinia'
import { apiRequest } from '@/MobileClient/stores/utils/api.js'

const BASE = '/dialogs'

export const useChatStore = defineStore('chat', {
    state: () => ({
        dialogs: [],
        messages: [],
        currentDialog: null,
        errors: []
    }),

    getters: {
        getDialogs: state => state.dialogs || [],
        getMessages: state => state.messages || [],
        getCurrentDialog: state => state.currentDialog,
    },

    actions: {

        setDialogs(data) {
            this.dialogs = data || []
        },

        setMessages(data) {
            this.messages = data || []
        },

        setCurrentDialog(dialog) {
            this.currentDialog = dialog
        },

        setErrors(errors = []) {
            this.errors = errors
        },

        async loadDialogs() {
            try {
                const res = await apiRequest(BASE, 'GET')
                this.setDialogs(res.data)
            } catch (e) {
                this.setErrors(e?.response?.data?.errors)
            }
        },

        async loadMessages(dialogId) {
            try {
                const res = await apiRequest(`${BASE}/${dialogId}/messages`, 'GET')
                this.setMessages(res.data)
            } catch (e) {
                this.setErrors(e?.response?.data?.errors)
            }
        },

        async sendMessage(dialogId, message) {
            try {
                const res = await apiRequest(`${BASE}/${dialogId}/messages`, 'POST', {
                    message,
                    dialog_id: dialogId
                })

                this.messages.push(res.data)
                return res.data
            } catch (e) {
                this.setErrors(e?.response?.data?.errors)
                throw e
            }
        },

        closeDialog() {
            this.currentDialog = null
            this.messages = []
        }
    }
})
