<template>
    <div class="story-wrapper">
        <button
            class="story-button"
            :class="{ 'viewed': isViewed }"
            @click="handleClick"
            type="button"
        >
            <div class="story-ring">
                <div class="story-avatar">
                    <img
                        v-lazy="story.thumbnail"
                        :alt="story.title"
                        class="story-image"
                    >
                    <div v-if="isViewed" class="viewed-indicator">
                        <i class="fa-solid fa-check"></i>
                    </div>
                </div>
            </div>
            <div class="story-title">{{ story.title || 'История' }}</div>
        </button>
    </div>
</template>

<script>
export default {
    name: 'StoryItem',

    props: {
        story: {
            type: Object,
            required: true,
        },
        isViewed: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['open-story'],

    methods: {
        handleClick() {
            console.log('👆 Click on story:', this.story.title);
            this.$emit('open-story', this.story);
        },
    },
};
</script>

<style scoped>
.story-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 76px;
    flex-shrink: 0;
}

/* ==========================================
   КНОПКА ИСТОРИИ
   ========================================== */
.story-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    background: transparent;
    border: none;
    padding: 0;
    cursor: pointer;
    transition: transform 0.2s ease;
    text-decoration: none;
    width: 100%;
}

.story-button:hover {
    transform: scale(1.05);
}

.story-button:active {
    transform: scale(0.95);
}

/* ==========================================
   ГРАДИЕНТНАЯ РАМКА
   ========================================== */
.story-ring {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    padding: 3px;
    background: linear-gradient(
        135deg,
        #f09433 0%,
        #e6683c 25%,
        #dc2743 50%,
        #cc2366 75%,
        #bc1888 100%
    );
    box-shadow: 0 4px 12px rgba(220, 39, 67, 0.3);
    transition: all 0.3s ease;
    position: relative;
    flex-shrink: 0;
}

/* Анимация пульсации для непросмотренных */
.story-button:not(.viewed) .story-ring {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 4px 12px rgba(220, 39, 67, 0.3);
    }
    50% {
        box-shadow: 0 4px 20px rgba(220, 39, 67, 0.5);
    }
}

/* Просмотренная история - серая рамка */
.story-button.viewed .story-ring {
    background: var(--bs-border-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    animation: none;
}

.story-button:hover .story-ring {
    box-shadow: 0 6px 20px rgba(220, 39, 67, 0.4);
}

/* ==========================================
   АВАТАР
   ========================================== */
.story-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: var(--bs-body-bg);
    padding: 2px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.story-image {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    transition: all 0.3s ease;
}

/* Просмотренная история - grayscale */
.story-button.viewed .story-image {
    filter: grayscale(100%) brightness(0.8);
}

.story-button:hover .story-image {
    transform: scale(1.1);
}

/* ==========================================
   ИНДИКАТОР ПРОСМОТРА
   ========================================== */
.viewed-indicator {
    position: absolute;
    bottom: 7px;
    /* right: 18px; */
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    border: 2px solid var(--bs-body-bg);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    animation: checkPop-9fe204a5 0.3s ease;
}

@keyframes checkPop {
    0% {
        transform: scale(0);
    }
    70% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

/* ==========================================
   НАЗВАНИЕ ИСТОРИИ
   ========================================== */
.story-title {
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--bs-body-color);
    text-align: center;
    width: 100%;
    max-width: 76px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.2;
    margin-top: 2px;
    transition: color 0.2s ease;
    display: block;
}

.story-button:hover .story-title {
    color: var(--bs-primary);
}

.story-button.viewed .story-title {
    color: var(--bs-secondary-color);
    opacity: 0.7;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .story-wrapper {
        width: 68px;
    }

    .story-ring {
        width: 64px;
        height: 64px;
    }

    .story-title {
        font-size: 0.65rem;
        max-width: 68px;
    }
}
</style>
