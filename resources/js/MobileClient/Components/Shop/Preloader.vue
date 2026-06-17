<template>
    <transition name="preloader-fade">
        <div
            v-if="isVisible"
            class="preloader-overlay"
            :class="{
                'is-fullscreen': fullscreen,
                'is-inline': !fullscreen,
                'has-backdrop': backdrop
            }"
        >
            <!-- Размытие фона -->
            <div v-if="backdrop" class="preloader-backdrop"></div>

            <!-- Контент прелоадера -->
            <div class="preloader-content">

                <!-- Анимированная иконка -->
                <div class="preloader-icon-wrapper">
                    <!-- Пульсирующие кольца -->
                    <div class="pulse-ring ring-1"></div>
                    <div class="pulse-ring ring-2"></div>
                    <div class="pulse-ring ring-3"></div>

                    <!-- Центральная иконка -->
                    <div class="preloader-icon">
                        <i :class="icon" class="icon-animated"></i>
                    </div>
                </div>

                <!-- Текст загрузки -->
                <div v-if="showText" class="preloader-text">
                    <div class="loading-dots">
                        <span>{{ text }}</span>
                        <span class="dot-1">.</span>
                        <span class="dot-2">.</span>
                        <span class="dot-3">.</span>
                    </div>
                </div>

                <!-- Прогресс-бар (опционально) -->
                <div v-if="showProgress" class="preloader-progress">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                </div>

            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "Preloader",

    props: {
        // Видимость прелоадера
        visible: {
            type: Boolean,
            default: false,
        },
        // На весь экран или внутри контейнера
        fullscreen: {
            type: Boolean,
            default: true,
        },
        // Затемнение фона
        backdrop: {
            type: Boolean,
            default: true,
        },
        // Иконка FontAwesome
        icon: {
            type: String,
            default: 'fa-solid fa-spinner',
        },
        // Текст загрузки
        text: {
            type: String,
            default: 'Загрузка',
        },
        // Показывать текст
        showText: {
            type: Boolean,
            default: true,
        },
        // Показывать прогресс-бар
        showProgress: {
            type: Boolean,
            default: false,
        },
        // Минимальное время показа (мс)
        minDuration: {
            type: Number,
            default: 0,
        },
    },

    emits: ['complete'],

    data() {
        return {
            isVisible: false,
            showStartTime: null,
            minDurationTimer: null,
        };
    },

    watch: {
        visible: {
            immediate: true,
            handler(newValue) {
                if (newValue) {
                    this.show();
                } else {
                    this.hide();
                }
            },
        },
    },

    mounted() {
        // Слушаем глобальные события
        window.addEventListener('preloader-show', this.onGlobalShow);
        window.addEventListener('preloader-hide', this.onGlobalHide);
    },

    beforeUnmount() {
        window.removeEventListener('preloader-show', this.onGlobalShow);
        window.removeEventListener('preloader-hide', this.onGlobalHide);
        this.clearTimers();
    },

    methods: {
        show() {
            this.showStartTime = Date.now();
            this.isVisible = true;

            // Если задан minDuration, не скрываем раньше
            if (this.minDuration > 0) {
                this.minDurationTimer = setTimeout(() => {
                    this.minDurationTimer = null;
                }, this.minDuration);
            }
        },

        hide() {
            // Если работает minDuration, ждём его окончания
            if (this.minDurationTimer) {
                const elapsed = Date.now() - this.showStartTime;
                const remaining = this.minDuration - elapsed;

                if (remaining > 0) {
                    setTimeout(() => {
                        this.isVisible = false;
                        this.$emit('complete');
                    }, remaining);
                    return;
                }
            }

            this.isVisible = false;
            this.$emit('complete');
        },

        clearTimers() {
            if (this.minDurationTimer) {
                clearTimeout(this.minDurationTimer);
            }
        },

        // Глобальные события
        onGlobalShow(event) {
            if (this.fullscreen) {
                this.show();
            }
        },

        onGlobalHide() {
            if (this.fullscreen) {
                this.hide();
            }
        },
    },
};
</script>

<style scoped>
/* ==========================================
   OVERLAY
   ========================================== */
.preloader-overlay {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

/* Полноэкранный режим */
.preloader-overlay.is-fullscreen {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100vh;
}

/* Локальный режим (внутри контейнера) */
.preloader-overlay.is-inline {
    min-height: 200px;
    width: 100%;
}

/* Фон с затемнением */
.preloader-overlay.has-backdrop {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* Размытие контента под прелоадером */
.preloader-backdrop {
    position: absolute;
    inset: 0;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.preloader-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
}

/* ==========================================
   ИКОНКА С КОЛЬЦАМИ
   ========================================== */
.preloader-icon-wrapper {
    position: relative;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Пульсирующие кольца */
.pulse-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid var(--bs-primary);
    opacity: 0;
    animation: pulseRing 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.pulse-ring.ring-1 {
    animation-delay: 0s;
}

.pulse-ring.ring-2 {
    animation-delay: 0.4s;
}

.pulse-ring.ring-3 {
    animation-delay: 0.8s;
}

@keyframes pulseRing {
    0% {
        transform: scale(0.5);
        opacity: 0.8;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

/* Центральная иконка */
.preloader-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
    position: relative;
    z-index: 2;
}

.icon-animated {
    font-size: 1.5rem;
    color: white;
    animation: iconSpin 1.5s linear infinite;
}

@keyframes iconSpin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Если иконка не спиннер — другая анимация */
.icon-animated:not(.fa-spinner) {
    animation: iconPulse 1.5s ease-in-out infinite;
}

@keyframes iconPulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

/* ==========================================
   ТЕКСТ ЗАГРУЗКИ
   ========================================== */
.preloader-text {
    text-align: center;
}

.loading-dots {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    font-size: 1rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.loading-dots span:first-child {
    margin-right: 4px;
}

.dot-1,
.dot-2,
.dot-3 {
    animation: dotBlink 1.4s infinite;
    opacity: 0;
}

.dot-1 {
    animation-delay: 0s;
}

.dot-2 {
    animation-delay: 0.2s;
}

.dot-3 {
    animation-delay: 0.4s;
}

@keyframes dotBlink {
    0%, 20% {
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}

/* ==========================================
   ПРОГРЕСС-БАР
   ========================================== */
.preloader-progress {
    width: 200px;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background: rgba(var(--bs-primary-rgb), 0.2);
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill {
    width: 30%;
    height: 100%;
    background: linear-gradient(90deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border-radius: 2px;
    animation: progressSlide 1.5s ease-in-out infinite;
}

@keyframes progressSlide {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(400%);
    }
}

/* ==========================================
   АНИМАЦИИ ПОЯВЛЕНИЯ/ИСЧЕЗАНИЯ
   ========================================== */
.preloader-fade-enter-active,
.preloader-fade-leave-active {
    transition: opacity 0.3s ease;
}

.preloader-fade-enter-from,
.preloader-fade-leave-to {
    opacity: 0;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .preloader-icon-wrapper {
        width: 64px;
        height: 64px;
    }

    .preloader-icon {
        width: 48px;
        height: 48px;
    }

    .icon-animated {
        font-size: 1.2rem;
    }

    .loading-dots {
        font-size: 0.9rem;
    }

    .preloader-progress {
        width: 160px;
    }
}
</style>
