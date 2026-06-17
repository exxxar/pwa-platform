<template>
    <div class="stories-container">
        <div class="stories-scroll">
            <StoryItem
                v-for="(story, index) in stories"
                :key="story.id"
                :story="story"
                :is-viewed="viewedStories.includes(story.id)"
                @open-story="openStory(index)"
            />
        </div>

        <!-- Модалка историй -->
        <StoryModal
            v-if="showModal"
            :stories="stories"
            :initial-index="currentStoryIndex"
            @close="closeModal"
        />
    </div>
</template>

<script>
import StoryItem from '@/MobileClient/Components/Shop/Stories/StoryItem.vue';
import StoryModal from '@/MobileClient/Components/Shop/Stories/StoryModal.vue'; // <-- Импортируем модалку

export default {
    name: 'StoryList',

    components: {
        StoryItem,
        StoryModal,
    },

    props: {
        stories: {
            type: Array,
            required: true,
        },
    },

    data() {
        return {
            viewedStories: [],
            showModal: false,
            currentStoryIndex: 0,
        };
    },

    mounted() {
        const saved = localStorage.getItem('viewed_stories');
        if (saved) {
            this.viewedStories = JSON.parse(saved);
        }
    },

    methods: {
        openStory(index) {
            this.currentStoryIndex = index;
            this.showModal = true;

            // Отмечаем как просмотренную
            const storyId = this.stories[index].id;
            if (!this.viewedStories.includes(storyId)) {
                this.viewedStories.push(storyId);
                localStorage.setItem('viewed_stories', JSON.stringify(this.viewedStories));
            }
        },

        closeModal() {
            this.showModal = false;
        },
    },
};
</script>

<style scoped>
.stories-container {
    padding: 12px 0;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
}

.stories-scroll {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding: 0 16px;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

/* Скроллбар */
.stories-scroll::-webkit-scrollbar {
    height: 4px;
}

.stories-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.stories-scroll::-webkit-scrollbar-thumb {
    background: var(--bs-border-color);
    border-radius: 2px;
}

.stories-scroll::-webkit-scrollbar-thumb:hover {
    background: var(--bs-primary);
}
</style>
