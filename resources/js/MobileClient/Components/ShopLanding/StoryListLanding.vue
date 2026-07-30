<template>
    <div class="landing-stories-card">
        <!-- Декоративная градиентная линия сверху -->
        <div class="card-accent-line"></div>

        <!-- Состояние загрузки -->
        <div v-if="isLoading && !isHydrated" class="stories-loading">
            <div v-for="i in 5" :key="i" class="story-skeleton">
                <div class="skeleton-circle shimmer"></div>
                <div class="skeleton-text shimmer"></div>
            </div>
        </div>

        <!-- Список историй -->
        <div v-else-if="displayedStories.length > 0 || isAdmin" class="stories-scroll-wrapper">
            <div class="stories-scroll">

                <!-- Кнопка создания (только для админа) -->
                <div v-if="isAdmin" class="story-item create-story-btn" @click="$emit('create-story')">
                    <div class="story-avatar create-avatar">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <span class="story-name">Создать</span>
                </div>

                <!-- Элементы историй -->
                <StoryItem
                    v-for="(story, index) in displayedStories"
                    :key="story.id"
                    :story="story"
                    :is-viewed="isViewed(story.id)"
                    @open-story="openStory(story, index)"
                />
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="stories-empty">
            <p>Историй пока нет</p>
        </div>

        <!-- Модалка просмотра -->
        <StoryModal
            v-if="showModal"
            :stories="displayedStories"
            :initial-index="currentStoryIndex"
            @close="closeModal"
            @story-viewed="onStoryViewed"
        />
    </div>
</template>

<script>
import { useStories } from '@/MobileClient/Composables/useStories.js';
import StoryItem from '@/MobileClient/Components/Shop/Stories/StoryItem.vue';
import StoryModal from '@/MobileClient/Components/Shop/Stories/StoryModal.vue';

export default {
    name: 'StoryListLanding',
    components: { StoryItem, StoryModal },

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
            isLoading: stories.isLoading,
            isHydrated: stories.isHydrated,
            activeStories: stories.activeStories,
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
            return this.activeStories || [];
        },
    },

    async mounted() {
        if (!this.isHydrated) {
            try {
                await this.loadStories();
            } catch (error) {
                console.error('[StoryListLanding] Ошибка загрузки:', error);
            }
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
$primary: var(--bs-primary, #667eea);
$bg: #42332f70;
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #333333);

.landing-stories-card {

    border-radius: 12px;
    padding: 20px 0px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
    width: 100%;

    // Декоративная полоса сверху
  /*  &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        width: 4px;
        background: linear-gradient(90deg, #FF9800 0%, var(--bs-accent, #bb038b) 100%);
        opacity: 0.9;
    }*/
}

.stories-scroll-wrapper {
    width: 100%;
    overflow: hidden; // Скрываем вылезающие тени крайних элементов
}

.stories-scroll {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 4px 8px 8px 8px; // Отступы, чтобы тени и рамки не обрезались
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;

    // Полное скрытие скроллбара для чистоты дизайна
    scrollbar-width: none;
    -ms-overflow-style: none;
    &::-webkit-scrollbar {
        display: none;
    }
}

// Skeleton загрузка
.stories-loading {
    display: flex;
    gap: 16px;
    padding: 4px 8px 8px 8px;
}

.story-skeleton {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.skeleton-circle {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: var(--bs-secondary-bg, #f3f4f6);
}

.skeleton-text {
    width: 56px;
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

// Кнопка создания истории
.create-story-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    cursor: pointer;
    transition: transform 0.2s;
    padding-top: 4px; // Выравнивание с рамкой историй

    &:active { transform: scale(0.95); }

    .create-avatar {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        background: $primary;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        border: 2px dashed rgba(255, 255, 255, 0.6);
        box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb, 102, 126, 234), 0.2);
    }

    .story-name {
        font-size: 0.75rem;
        color: $text;
        font-weight: 600;
    }
}

.stories-empty {
    text-align: center;
    padding: 20px;
    color: var(--bs-secondary-color, #6c757d);
    font-size: 0.9rem;
}

// Адаптив для мобильных
@media (max-width: 576px) {
    .landing-stories-card {
        border-radius: 20px;
        padding: 1.25rem 0.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    }
}
</style>
