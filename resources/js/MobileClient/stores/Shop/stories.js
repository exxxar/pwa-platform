// stores/stories.js
import {defineStore} from 'pinia'
import {apiRequest} from '../utils/api.js'

const BASE_STORY_LINK = '/stories'

export const useStoriesStore = defineStore('stories', {
    state: () => ({
        stories: [],
        stories_paginate_object: null,
    }),

    getters: {
        getStories: (state) => state.stories || [],
        getStoryById: (state) => (id) => {
            return state.stories.find(item => item.id === id)
        },
        getStoriesPaginateObject: (state) => state.stories_paginate_object || null,
    },

    actions: {
        setStories(payload) {
            this.stories = payload || []
            localStorage.setItem('cashman_stories', JSON.stringify(payload))
        },

        setStoriesPaginateObject(payload) {
            this.stories_paginate_object = payload || null
            localStorage.setItem('cashman_stories_paginate_object', JSON.stringify(payload))
        },

        async loadStories(payload = { page: 1, size: 20 }) {
            let page = payload.page || 1
            let size = payload.size || 20

            let link = `${BASE_STORY_LINK}?page=${page}&size=${size}`

            return apiRequest(link, 'GET')
                .then((response) => {
                    let dataObject = response.data

                    this.setStories(dataObject.data)
                    delete dataObject.data
                    this.setStoriesPaginateObject(dataObject)

                    return dataObject
                })
                .catch(err => {
                    throw err
                })
        },

        async fetchStory(payload = { id: null }) {
            let link = `${BASE_STORY_LINK}/${payload.id}`

            return apiRequest(link, 'GET')
                .then(res => res.data)
                .catch(err => {
                    throw err
                })
        },

        async saveStory(payload = { storyForm: {} }) {
            return apiRequest(BASE_STORY_LINK, 'POST', payload.storyForm)
                .then(res => res.data)
                .catch(err => {
                    throw err
                })
        },

        async deleteStory(payload = { id: null }) {
            let link = `${BASE_STORY_LINK}/${payload.id}`

            return apiRequest(link, 'DELETE')
                .then(res => res.data)
                .catch(err => {
                    throw err
                })
        },
    }
})
