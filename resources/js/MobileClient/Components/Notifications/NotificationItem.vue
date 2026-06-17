<template>
    <transition :name="transitionName">
        <div
            v-if="isVisible"
            class="notification-item"
            :class="[`notification-${type}`, { 'is-leaving': isLeaving }]"
            @click="handleClick"
        >
            <!-- Прогресс-бар -->
            <div v-if="duration > 0" class="notification-progress">
                <div
                    class="progress-bar"
                    :style="{ animationDuration: duration + 'ms' }"
                    :class="{ 'is-paused': isPaused }"
                ></div>
            </div>

            <!-- Контент -->
            <div class="notification-content">
                <!-- Иконка -->
                <div class="notification-icon">
                    <i :class="iconClass"></i>
                </div>

                <!-- Текст -->
                <div class="notification-text">
                    <div v-if="title" class="notification-title">{{ title }}</div>
                    <div class="notification-message">{{ text }}</div>
                </div>

                <!-- Кнопка закрытия -->
                <button
                    v-if="closable"
                    class="notification-close"
                    @click.stop="close"
                    @mouseenter="pauseTimer"
                    @mouseleave="resumeTimer"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "NotificationItem",

    props: {
        id: {
            type: [String, Number],
            required: true,
        },
        type: {
            type: String,
            default: 'info',
            validator: (value) => ['success', 'error', 'warning', 'info'].includes(value),
        },
        title: {
            type: String,
            default: '',
        },
        text: {
            type: String,
            required: true,
        },
        duration: {
            type: Number,
            default: 5000,
        },
        closable: {
            type: Boolean,
            default: true,
        },
        position: {
            type: String,
            default: 'top-right',
        },
        onClick: {
            type: Function,
            default: null,
        },
    },

    emits: ['close'],

    data() {
        return {
            isVisible: true,
            isLeaving: false,
            isPaused: false,
            timerId: null,
            remainingTime: this.duration,
            startTime: null,
        };
    },

    computed: {
        iconClass() {
            const icons = {
                success: 'fa-solid fa-circle-check',
                error: 'fa-solid fa-circle-exclamation',
                warning: 'fa-solid fa-triangle-exclamation',
                info: 'fa-solid fa-circle-info',
            };
            return icons[this.type] || icons.info;
        },

        transitionName() {
            const positions = {
                'top-right': 'slide-right',
                'top-left': 'slide-left',
                'bottom-right': 'slide-right',
                'bottom-left': 'slide-left',
                'top-center': 'slide-down',
                'bottom-center': 'slide-up',
            };
            return positions[this.position] || 'slide-right';
        },
    },

    mounted() {
        if (this.duration > 0) {
            this.startTimer();
        }
    },

    beforeUnmount() {
        this.clearTimer();
    },

    methods: {
        startTimer() {
            this.startTime = Date.now();
            this.timerId = setTimeout(() => {
                this.close();
            }, this.remainingTime);
        },

        pauseTimer() {
            if (this.timerId) {
                clearTimeout(this.timerId);
                this.timerId = null;
                this.isPaused = true;
                const elapsed = Date.now() - this.startTime;
                this.remainingTime -= elapsed;
            }
        },

        resumeTimer() {
            if (this.remainingTime > 0) {
                this.isPaused = false;
                this.startTimer();
            }
        },

        clearTimer() {
            if (this.timerId) {
                clearTimeout(this.timerId);
                this.timerId = null;
            }
        },

        close() {
            this.isLeaving = true;
            this.clearTimer();

            setTimeout(() => {
                this.isVisible = false;
                this.$emit('close', this.id);
            }, 300);
        },

        handleClick() {
            if (this.onClick) {
                this.onClick();
                this.close();
            }
        },
    },
};
</script>

<style scoped>
.notification-item {
    position: relative;
    width: 360px;
    max-width: calc(100vw - 32px);
    background: var(--bs-body-bg);
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--bs-border-color);
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
}

.notification-item.is-leaving {
    opacity: 0;
    transform: translateX(100%);
}

/* Прогресс-бар */
.notification-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    width: 100%;
    transform-origin: left;
    animation: progressShrink linear forwards;
}

.progress-bar.is-paused {
    animation-play-state: paused;
}

@keyframes progressShrink {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}

/* Типы уведомлений */
.notification-success .progress-bar {
    background: linear-gradient(90deg, #198754 0%, #20c997 100%);
}

.notification-error .progress-bar {
    background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
}

.notification-warning .progress-bar {
    background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);
}

.notification-info .progress-bar {
    background: linear-gradient(90deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
}

/* Контент */
.notification-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
}

/* Иконка */
.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;
}

.notification-success .notification-icon {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
}

.notification-error .notification-icon {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.notification-warning .notification-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #1a1a1a;
}

.notification-info .notification-icon {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
}

/* Текст */
.notification-text {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    line-height: 1.3;
}

.notification-message {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    line-height: 1.4;
    word-wrap: break-word;
}

/* Кнопка закрытия */
.notification-close {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.notification-close:hover {
    background: var(--bs-primary);
    color: white;
    transform: rotate(90deg);
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */

/* Slide справа */
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-right-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.slide-right-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

/* Slide слева */
.slide-left-enter-active,
.slide-left-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-left-enter-from {
    opacity: 0;
    transform: translateX(-100%);
}

.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-100%);
}

/* Slide сверху */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-down-enter-from {
    opacity: 0;
    transform: translateY(-100%);
}

.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-100%);
}

/* Slide снизу */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from {
    opacity: 0;
    transform: translateY(100%);
}

.slide-up-leave-to {
    opacity: 0;
    transform: translateY(100%);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .notification-item {
        width: calc(100vw - 24px);
    }

    .notification-content {
        padding: 14px;
        gap: 10px;
    }

    .notification-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }

    .notification-title {
        font-size: 0.9rem;
    }

    .notification-message {
        font-size: 0.8rem;
    }
}
</style>
