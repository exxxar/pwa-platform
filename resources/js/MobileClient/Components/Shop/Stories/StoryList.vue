<template>
    <div class="stories-container">
        <!-- Состояние загрузки -->
        <div v-if="isLoading && !isHydrated" class="stories-loading">
            <div v-for="i in 5" :key="i" class="story-skeleton">
                <div class="skeleton-circle shimmer"></div>
                <div class="skeleton-text shimmer"></div>
            </div>
        </div>

        <!-- Список историй -->
        <template v-else-if="displayedStories.length > 0 || isAdmin">
            <div class="stories-scroll">

                <!-- 🆕 Кнопка создания истории (только для админа) -->
                <div v-if="isAdmin" class="story-item create-story-btn" @click="$emit('create-story')">
                    <div class="story-avatar create-avatar">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <span class="story-name">Создать</span>
                </div>

                <StoryItem
                    v-for="(story, index) in displayedStories"
                    :key="story.id"
                    :story="story"
                    :is-viewed="isViewed(story.id)"
                    @open-story="openStory(story, index)"
                />
            </div>

            <!-- Модалка просмотра историй -->
            <StoryModal
                v-if="showModal"
                :stories="displayedStories"
                :initial-index="currentStoryIndex"
                @close="closeModal"
                @story-viewed="onStoryViewed"
            />
        </template>
    </div>
</template>

<script>
import { useStories } from '@/MobileClient/Composables/useStories.js';
import StoryItem from '@/MobileClient/Components/Shop/Stories/StoryItem.vue';
import StoryModal from '@/MobileClient/Components/Shop/Stories/StoryModal.vue';

export default {
    name: 'StoryList',
    components: { StoryItem, StoryModal },

    // 🆕 Добавляем проп и emits
    props: {
        isAdmin: {
            type: Boolean,
            default: false
        }
    },
    emits: ['create-story'],

    setup() {
        const stories = useStories();
        return {
            storiesList: stories.stories,
            isLoading: stories.isLoading,
            isHydrated: stories.isHydrated,
            sortedStories: stories.sortedStories,
            activeStories: stories.activeStories,
            recentStories: stories.recentStories,
            storiesCount: stories.storiesCount,
            loadStories: stories.loadStories,
            isViewed: stories.isViewed,
            markAsViewed: stories.markAsViewed,
        };
    },

    data() {
        return {
            showModal: false,
            currentStoryIndex: 0,
        };
    },

    computed: {
        displayedStories() {
            return this.activeStories;
        },
    },

    async mounted() {
        if (!this.isHydrated) {
            try { await this.loadStories(); }
            catch (error) { console.error('[StoryList] Ошибка загрузки историй:', error); }
        }
    },

    methods: {
        openStory(story, index) {
            if (typeof index !== 'number') {
                index = this.displayedStories.findIndex(s => s.id === story.id);
            }
            if (index === -1) return;
            this.currentStoryIndex = index;
            this.showModal = true;
            if (story && !this.isViewed(story.id)) {
                this.markAsViewed(story.id);
            }
        },
        onStoryViewed(storyId) {
            if (!this.isViewed(storyId)) this.markAsViewed(storyId);
        },
        closeModal() {
            this.showModal = false;
            this.currentStoryIndex = 0;
        },
    },
};
</script>


<style lang="scss" scoped>
$bg: var(--bs-body-bg, #ffffff);
$border: var(--bs-border-color, #e5e7eb);
$primary: var(--bs-primary, #667eea);

.stories-container {
    padding: 12px 0;
    background: $bg;
    border-bottom: 1px solid $border;
}

.stories-scroll {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding: 0 16px;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;

    &::-webkit-scrollbar {
        height: 4px;
    }

    &::-webkit-scrollbar-track {
        background: transparent;
    }

    &::-webkit-scrollbar-thumb {
        background: $border;
        border-radius: 2px;

        &:hover {
            background: $primary;
        }
    }
}

.stories-loading {
    display: flex;
    gap: 12px;
    padding: 0 16px;
    overflow: hidden;
}

.story-skeleton {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.skeleton-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--bs-secondary-bg, #f3f4f6);
}

.skeleton-text {
    width: 50px;
    height: 10px;
    border-radius: 5px;
    background: var(--bs-secondary-bg, #f3f4f6);
}

.shimmer {
    background: linear-gradient(
            90deg,
            var(--bs-secondary-bg, #f3f4f6) 0%,
            var(--bs-border-color, #e5e7eb) 50%,
            var(--bs-secondary-bg, #f3f4f6) 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* 🆕 Стили для кнопки создания */
.create-story-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    cursor: pointer;
    transition: transform 0.2s;

    &:active {
        transform: scale(0.95);
    }

    .create-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: var(--bs-primary, #667eea);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        border: 2px dashed rgba(255, 255, 255, 0.6);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .story-name {
        font-size: 0.75rem;
        color: var(--bs-body-color, #333);
        font-weight: 500;
    }
}
</style>
