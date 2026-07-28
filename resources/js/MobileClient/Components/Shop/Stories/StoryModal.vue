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

                <!-- Прогресс-бары для каждой истории -->
                <div class="story-progress-container">
                    <div
                        v-for="(story, index) in stories"
                        :key="'progress-' + index"
                        class="story-progress-bg"
                    >
                        <div
                            class="story-progress-fill"
                            :style="{ width: getProgressForStory(index) + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Шапка истории -->
                <div class="story-header">
                    <div class="story-user-info">
                        <div class="story-user-avatar">
                            <img
                                v-if="currentStory"
                                v-lazy="currentStory.avatar || '/pwa-lazy.jpg'"
                                alt=""
                            >
                        </div>
                        <div class="story-user-details">
                            <span class="story-username">
                                {{ currentStory?.author || 'Магазин' }}
                            </span>
                            <span class="story-time">
                                {{ currentStory?.time || 'Только что' }}
                            </span>
                        </div>
                    </div>
                    <button class="story-close-btn" @click="closeModal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Контент истории -->
                <div class="story-media-container">
                    <img
                        v-if="currentStory"
                        :src="currentStory.image || currentStory.thumbnail || 'pwa-lazy.jpg'"
                        :alt="currentStory.title"
                        class="story-image"
                    >
                </div>

                <!-- 🌟 КНОПКА ПЕРЕХОДА ПО ССЫЛКЕ -->
                <div v-if="currentStory?.link" class="story-link-wrapper" @click.stop>
                    <a
                        :href="currentStory.link"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="story-link-btn"
                    >
                        <span>{{ currentStory.linkText || 'Подробнее' }}</span>
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>

                <!-- 🌟 РАСКРЫВАЮЩИЙСЯ ТЕКСТ -->
                <div
                    v-if="currentStory?.description"
                    class="story-text-overlay"
                    :class="{ 'is-expanded': isTextExpanded }"
                    @click.stop="toggleText"
                >
                    <div class="text-content">
                        <p>{{ currentStory.description }}</p>
                    </div>

                    <div class="text-toggle-indicator">
                        <span>{{ isTextExpanded ? 'Свернуть' : 'Читать далее' }}</span>
                        <i :class="isTextExpanded ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'"></i>
                    </div>
                </div>

                <!-- 🌟 РАСКРЫВАЮЩИЙСЯ ТЕКСТ -->
                <div
                    v-if="currentStory?.description"
                    class="story-text-overlay"
                    :class="{ 'is-expanded': isTextExpanded }"
                    @click.stop="toggleText"
                >
                    <div class="text-content">
                        <p>{{ currentStory.description }}</p>
                    </div>

                    <div class="text-toggle-indicator">
                        <span>{{ isTextExpanded ? 'Свернуть' : 'Читать далее' }}</span>
                        <i :class="isTextExpanded ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'"></i>
                    </div>
                </div>

                <!-- Зоны для тапа -->
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

    emits: ['close', 'story-viewed'],

    data() {
        return {
            currentIndex: this.initialIndex,
            progress: 0,
            timer: null,
            duration: 5000,
            isTextExpanded: false,
            touchStartTime: 0,
            isPaused: false,
        };
    },

    computed: {
        /**
         * 🆕 Видимость модалки
         */
        isVisible() {
            return true; // Модалка рендерится только когда v-if="showModal" в родителе
        },

        currentStory() {
            return this.stories[this.currentIndex] || null;
        },
    },

    watch: {
        /**
         * 🆕 Следим за изменением initialIndex (на случай обновления извне)
         */
        initialIndex(newIndex) {
            if (newIndex !== this.currentIndex) {
                this.currentIndex = newIndex;
                this.startTimer();
            }
        },
    },

    mounted() {
        console.log('🎬 StoryModal mounted, starting at index:', this.currentIndex);
        this.startTimer();
        document.addEventListener('keydown', this.handleKeydown);
        // Блокируем скролл body
        document.body.style.overflow = 'hidden';
    },

    beforeUnmount() {
        this.stopTimer();
        document.removeEventListener('keydown', this.handleKeydown);
        document.body.style.overflow = '';
    },

    methods: {
        /**
         * 🆕 Получить прогресс для конкретной истории
         */
        getProgressForStory(index) {
            if (index < this.currentIndex) return 100;
            if (index > this.currentIndex) return 0;
            return this.progress;
        },

        startTimer() {
            this.stopTimer();
            this.progress = 0;
            this.isTextExpanded = false;

            this.timer = setInterval(() => {
                if (!this.isPaused) {
                    this.progress += 100 / (this.duration / 100);

                    if (this.progress >= 100) {
                        this.nextStory();
                    }
                }
            }, 100);
        },

        stopTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        nextStory() {
            if (this.currentStory) {
                this.$emit('story-viewed', this.currentStory.id);
            }

            if (this.currentIndex < this.stories.length - 1) {
                this.currentIndex++;
                this.startTimer();
            } else {
                this.closeModal();
            }
        },

        prevStory() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.startTimer();
            }
        },

        /**
         * 🆕 Закрытие модалки
         */
        closeModal() {
            this.stopTimer();
            this.$emit('close');
        },

        /**
         * 🆕 Переключение раскрытия текста
         */
        toggleText() {
            this.isTextExpanded = !this.isTextExpanded;
            // При раскрытии ставим таймер на паузу
            this.isPaused = this.isTextExpanded;
        },

        /**
         * 🆕 Начало касания/зажатия
         */
        handleTouchStart(e) {
            this.touchStartTime = Date.now();
            this.isPaused = true; // Пауза при удержании
        },

        /**
         * 🆕 Конец касания
         */
        handleTouchEnd(e) {
            const touchDuration = Date.now() - this.touchStartTime;
            this.isPaused = false;

            // Если это был короткий тап (не удержание) — не делаем ничего
            // Навигация уже обрабатывается через tap-zone
            if (touchDuration > 500) {
                // Долгое удержание — просто возобновляем таймер
                this.isPaused = false;
            }
        },

        handleKeydown(e) {
            if (e.key === 'Escape') this.closeModal();
            if (e.key === 'ArrowRight') this.nextStory();
            if (e.key === 'ArrowLeft') this.prevStory();
        },
    },
};
</script>

<style scoped>
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

/* ==========================================
   ПРОГРЕСС-БАРЫ
   ========================================== */
.story-progress-container {
    position: absolute;
    top: 12px;
    left: 12px;
    right: 12px;
    z-index: 30;
    display: flex;
    gap: 4px;
}

.story-progress-bg {
    flex: 1;
    height: 3px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    overflow: hidden;
}

.story-progress-fill {
    height: 100%;
    background: var(--bs-primary, #667eea);
    border-radius: 2px;
    box-shadow: 0 0 8px var(--bs-primary, #667eea);
    transition: width 0.1s linear;
}

/* ==========================================
   ШАПКА
   ========================================== */
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

/* ==========================================
   МЕДИА
   ========================================== */
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
   ТЕКСТ
   ========================================== */
.story-text-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 25;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.7) 60%, transparent 100%);
    color: white;
    padding: 40px 20px 20px;
    max-height: 110px;
    overflow: hidden;
    cursor: pointer;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1),
    background 0.4s ease,
    padding 0.4s ease;
}

.story-text-overlay.is-expanded {
    max-height: 100vh;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(15px);
    overflow-y: auto;
    padding-top: 80px;
}

.text-content p {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.5;
    white-space: pre-wrap;
}

.text-toggle-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
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

/* ==========================================
   ТАП-ЗОНЫ
   ========================================== */
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

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.story-fade-enter-active,
.story-fade-leave-active {
    transition: opacity 0.3s ease;
}

.story-fade-enter-from,
.story-fade-leave-to {
    opacity: 0;
}

/* ==========================================
   🌟 КРАСИВАЯ КНОПКА ССЫЛКИ
   ========================================== */
.story-link-wrapper {
    position: absolute;
    bottom: 130px; /* Располагаем прямо над текстовым блоком */
    left: 50%;
    transform: translateX(-50%);
    z-index: 26;
    animation: linkPopIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

.story-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    background: linear-gradient(135deg, var(--bs-primary, #667eea) 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.3px;
    box-shadow:
        0 8px 24px rgba(102, 126, 234, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.15) inset;
    backdrop-filter: blur(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.2);

/* Эффект при наведении */
&:hover {
     transform: translateY(-3px) scale(1.05);
     box-shadow:
         0 12px 32px rgba(102, 126, 234, 0.6),
         0 0 0 1px rgba(255, 255, 255, 0.3) inset;
 }

/* Эффект при нажатии */
&:active {
     transform: translateY(0) scale(0.98);
     box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
 }

/* Анимация иконки */
i {
    font-size: 0.85rem;
    transition: transform 0.3s ease;
}

&:hover i {
     transform: translate(2px, -2px);
 }
}

/* Анимация появления кнопки */
@keyframes linkPopIn {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0) scale(1);
    }
}

/* Адаптация для мобильных устройств */
@media (max-width: 480px) {
    .story-link-wrapper {
        bottom: 120px;
    }

    .story-link-btn {
        padding: 12px 24px;
        font-size: 0.9rem;
    }
}
</style>
