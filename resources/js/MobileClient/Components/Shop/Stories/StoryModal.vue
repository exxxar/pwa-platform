<template>
    <transition name="story-fade">
        <div v-if="isVisible" class="story-modal-overlay" @click="closeModal">
            <div
                class="story-modal-content"
                @click.stop
                @touchstart="handleTouchStart"
                @touchend="handleTouchEnd"
                @mousedown="handleTouchStart"
                @mouseup="handleTouchEnd"
                @mouseleave="handleTouchEnd"
            >

                <!-- Прогресс-бар -->
                <div class="story-progress-container">
                    <div class="story-progress-bg">
                        <div
                            class="story-progress-fill"
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Шапка истории -->
                <div class="story-header">
                    <div class="story-user-info">
                        <div class="story-user-avatar">
                            <img v-lazy="currentStory.avatar || '/images/shop-v2/self.png'" alt="">
                        </div>
                        <div class="story-user-details">
                            <span class="story-username">{{ currentStory.author || 'Магазин' }}</span>
                            <span class="story-time">{{ currentStory.time || 'Только что' }}</span>
                        </div>
                    </div>
                    <button class="story-close-btn" @click="closeModal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Контент истории (Изображение) -->
                <div class="story-media-container">
                    <img
                        :src="currentStory.image || currentStory.thumbnail"
                        :alt="currentStory.title"
                        class="story-image"
                    >
                </div>

                <!-- 🌟 РАСКРЫВАЮЩИЙСЯ ТЕКСТ ВНИЗУ -->
                <div
                    v-if="currentStory.description"
                    class="story-text-overlay"
                    :class="{ 'is-expanded': isTextExpanded }"
                    @click.stop="toggleText"
                >
                    <div class="text-content">
                        <p>{{ currentStory.description }}</p>
                    </div>

                    <!-- Кнопка переключения -->
                    <div class="text-toggle-indicator">
                        <span>{{ isTextExpanded ? 'Свернуть' : 'Читать далее' }}</span>
                        <i :class="isTextExpanded ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'"></i>
                    </div>
                </div>

                <!-- Зоны для тапа (невидимые, работают только если текст НЕ раскрыт) -->
                <div v-if="!isTextExpanded" class="tap-zone tap-zone-left" @click.stop="prevStory"></div>
                <div v-if="!isTextExpanded" class="tap-zone tap-zone-right" @click.stop="nextStory"></div>

            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: 'StoryModal',

    props: {
        stories: {
            type: Array,
            required: true,
        },
        initialIndex: {
            type: Number,
            default: 0,
        },
    },

    emits: ['close'],

    data() {
        return {
            currentIndex: this.initialIndex,
            progress: 0,
            isPaused: false,
            isVisible: false,
            isTextExpanded: false, // 🌟 Новое состояние
            storyDuration: 5000,
            timer: null,
            lastFrameTime: 0,
        };
    },

    computed: {
        currentStory() {
            return this.stories[this.currentIndex] || {};
        },
    },

    mounted() {
        setTimeout(() => {
            this.isVisible = true;
            this.startTimer();
        }, 50);

        window.addEventListener('keydown', this.handleKeydown);
    },

    beforeUnmount() {
        this.stopTimer();
        window.removeEventListener('keydown', this.handleKeydown);
    },

    methods: {
        // 🌟 Переключение состояния текста
        toggleText() {
            this.isTextExpanded = !this.isTextExpanded;

            // Если раскрываем текст, ставим историю на паузу, чтобы пользователь успел прочитать
            if (this.isTextExpanded) {
                this.isPaused = true;
            } else {
                this.isPaused = false;
                this.lastFrameTime = performance.now();
            }
        },

        startTimer() {
            this.stopTimer();
            this.progress = 0;
            this.lastFrameTime = performance.now();

            const animate = (currentTime) => {
                if (this.isPaused || !this.isVisible) {
                    this.lastFrameTime = currentTime;
                    this.timer = requestAnimationFrame(animate);
                    return;
                }

                const deltaTime = currentTime - this.lastFrameTime;
                this.lastFrameTime = currentTime;

                const progressIncrement = (deltaTime / this.storyDuration) * 100;
                this.progress += progressIncrement;

                if (this.progress >= 100) {
                    this.nextStory();
                } else {
                    this.timer = requestAnimationFrame(animate);
                }
            };

            this.timer = requestAnimationFrame(animate);
        },

        stopTimer() {
            if (this.timer) {
                cancelAnimationFrame(this.timer);
                this.timer = null;
            }
        },

        nextStory() {
            if (this.currentIndex < this.stories.length - 1) {
                this.currentIndex++;
                this.isTextExpanded = false; // Сбрасываем текст при переключении
                this.startTimer();
            } else {
                this.closeModal();
            }
        },

        prevStory() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.isTextExpanded = false; // Сбрасываем текст при переключении
                this.startTimer();
            } else {
                this.startTimer();
            }
        },

        handleTouchStart() {
            this.isPaused = true;
        },

        handleTouchEnd() {
            this.isPaused = false;
            this.lastFrameTime = performance.now();
        },

        handleKeydown(e) {
            if (e.key === 'ArrowRight') this.nextStory();
            if (e.key === 'ArrowLeft') this.prevStory();
            if (e.key === 'Escape') this.closeModal();
        },

        closeModal() {
            this.isVisible = false;
            this.stopTimer();
            setTimeout(() => {
                this.$emit('close');
            }, 300);
        },
    },
};
</script>

<style scoped>
/* ... (все предыдущие стили для overlay, content, progress, header остаются без изменений) ... */

.story-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.95);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.story-modal-content {
    position: relative;
    width: 100%;
    height: 100%;
    max-width: 500px;
    background: #000;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

@media (min-width: 768px) {
    .story-modal-content {
        height: 90vh;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }
}

.story-progress-container {
    position: absolute;
    top: 12px;
    left: 12px;
    right: 12px;
    z-index: 30; /* Выше всего */
}

.story-progress-bg {
    width: 100%;
    height: 3px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    overflow: hidden;
}

.story-progress-fill {
    height: 100%;
    background: var(--bs-primary, #ff8a00);
    border-radius: 2px;
    box-shadow: 0 0 8px var(--bs-primary, #ff8a00);
}

.story-header {
    position: absolute;
    top: 24px;
    left: 12px;
    right: 12px;
    z-index: 30;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.story-user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.story-user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.5);
    overflow: hidden;
}

.story-user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.story-user-details {
    display: flex;
    flex-direction: column;
}

.story-username {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.story-time {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.75rem;
}

.story-close-btn {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    backdrop-filter: blur(5px);
    transition: background 0.2s;
}

.story-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.story-media-container {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #111;
}

.story-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* ==========================================
   🌟 СТИЛИ РАСКРЫВАЮЩЕГОСЯ ТЕКСТА
   ========================================== */
.story-text-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 25; /* Ниже хедера, но выше зон тапа */

    /* Градиент для плавного перехода в картинку */
    background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.7) 60%, transparent 100%);
    color: white;
    padding: 40px 20px 20px;

    /* Состояние: свернуто */
    max-height: 110px;
    overflow: hidden;
    cursor: pointer;

    /* Плавная анимация раскрытия */
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1),
    background 0.4s ease,
    padding 0.4s ease;
}

/* Состояние: раскрыто на всю высоту */
.story-text-overlay.is-expanded {
    max-height: 100vh; /* Или calc(100% - 60px), чтобы не перекрывать хедер */
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(15px);
    overflow-y: auto;
    padding-top: 80px; /* Отступ сверху, чтобы текст не наезжал на хедер */
}

.text-content p {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.5;
    white-space: pre-wrap; /* Сохраняет переносы строк из текста */
}

.text-toggle-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
    transition: color 0.2s;
}

.story-text-overlay:hover .text-toggle-indicator {
    color: white;
}

.text-toggle-indicator i {
    transition: transform 0.3s ease;
}

.story-text-overlay.is-expanded .text-toggle-indicator i {
    transform: rotate(180deg);
}

/* Зоны тапа */
.tap-zone {
    position: absolute;
    top: 0;
    bottom: 0;
    z-index: 20;
}

.tap-zone-left {
    left: 0;
    width: 30%;
}

.tap-zone-right {
    right: 0;
    width: 70%;
}

/* Анимации модалки */
.story-fade-enter-active,
.story-fade-leave-active {
    transition: opacity 0.3s ease;
}

.story-fade-enter-from,
.story-fade-leave-to {
    opacity: 0;
}
</style>
