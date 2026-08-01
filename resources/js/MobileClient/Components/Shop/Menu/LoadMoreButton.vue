<template>
    <div class="load-more-container">
        <button
            ref="button"
            class="load-more-btn"
            :class="{
                'is-loading': isLoading,
                'is-pressed': isPressed,
                'has-ripple': rippleActive
            }"
            type="button"
            :disabled="isLoading || disabled"
            @click="handleClick"
            @mousedown="isPressed = true"
            @mouseup="isPressed = false"
            @mouseleave="isPressed = false"
        >
            <!-- 🌊 Ripple-эффект (волна от клика) -->
            <span
                v-if="rippleActive"
                class="ripple"
                :style="rippleStyle"
            ></span>

            <!-- ✨ Shimmer-эффект (мерцание во время загрузки) -->
            <div v-if="isLoading" class="shimmer"></div>

            <div class="btn-content">
                <div class="btn-icon">
                    <transition name="icon-flip" mode="out-in">
                        <i v-if="!isLoading" key="arrow" class="fa-solid fa-arrow-rotate-right"></i>
                        <div v-else key="spinner" class="loading-spinner"></div>
                    </transition>
                </div>

                <div class="btn-text">
                    <transition name="text-fade" mode="out-in">
                        <span v-if="!isLoading" key="idle">
                            Загрузить ещё
                            <span class="remaining-badge">
                                <transition name="count-flip" mode="out-in">
                                    <span :key="remaining">{{ remaining }}</span>
                                </transition>
                            </span>
                        </span>
                        <span v-else key="loading">Загружаем товары...</span>
                    </transition>
                </div>
            </div>

            <div class="btn-glow"></div>
        </button>
    </div>
</template>

<script>
export default {
    name: 'LoadMoreButton',

    props: {
        remaining: Number,
        isLoading: Boolean,
        disabled: Boolean,
    },

    emits: ['load-more'],

    data() {
        return {
            rippleActive: false,
            rippleStyle: {},
            isPressed: false,
        };
    },

    methods: {
        handleClick(e) {
            // 🌊 Создаем ripple-эффект в точке клика
            const button = this.$refs.button;
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Размер ripple должен быть равен диагонали кнопки * 2
            const size = Math.max(rect.width, rect.height) * 2;

            this.rippleStyle = {
                left: `${x}px`,
                top: `${y}px`,
                width: `${size}px`,
                height: `${size}px`,
            };

            this.rippleActive = true;

            // Убираем ripple через 600ms
            setTimeout(() => {
                this.rippleActive = false;
            }, 600);

            this.$emit('load-more');
        },
    },
};
</script>

<style scoped>
.load-more-container {
    display: flex;
    justify-content: center;
    width: 100%;
    padding: 1rem 0;
}

.load-more-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.1) 100%);
    border: 2px solid rgba(var(--bs-primary-rgb), 0.3);
    border-radius: 12px;
    color: var(--bs-primary);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    width: 100%;
    max-width: 400px;
    isolation: isolate; /* Создаем новый контекст наложения */
}

/* 🎯 Pulse-эффект при нажатии */
.load-more-btn.is-pressed:not(:disabled) {
    transform: scale(0.97);
}

/* 🌊 Ripple-эффект (волна от клика) */
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.4);
    transform: translate(-50%, -50%) scale(0);
    animation: ripple-expand 0.6s ease-out;
    pointer-events: none;
    z-index: 1;
}

@keyframes ripple-expand {
    to {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0;
    }
}

/* ✨ Shimmer-эффект во время загрузки */
.shimmer {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(var(--bs-primary-rgb), 0.15) 50%,
        transparent 100%
    );
    animation: shimmer-slide 1.5s infinite;
    z-index: 0;
}

@keyframes shimmer-slide {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

/* 🎨 Фоновый градиент */
.load-more-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--bs-primary) 0%, rgba(var(--bs-primary-rgb), 0.8) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 0;
}

.load-more-btn:hover:not(:disabled)::before {
    opacity: 1;
}

.load-more-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3);
    border-color: var(--bs-primary);
}

.load-more-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.load-more-btn.is-loading {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.15) 100%);
}

/* 📦 Контент кнопки */
.btn-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: color 0.3s ease;
}

.load-more-btn:hover:not(:disabled) .btn-content,
.load-more-btn.is-loading .btn-content {
    color: white;
}

/* 🔄 Иконка */
.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    font-size: 1.1rem;
}

.load-more-btn:hover:not(:disabled) .btn-icon {
    transform: rotate(180deg);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 🎬 Анимация смены иконки */
.icon-flip-enter-active,
.icon-flip-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.icon-flip-enter-from {
    opacity: 0;
    transform: rotate(-90deg) scale(0.5);
}

.icon-flip-leave-to {
    opacity: 0;
    transform: rotate(90deg) scale(0.5);
}

/* 🌀 Спиннер */
.loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* 📝 Текст */
.btn-text {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* 🎬 Анимация смены текста */
.text-fade-enter-active,
.text-fade-leave-active {
    transition: all 0.25s ease;
}

.text-fade-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.text-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

/* 🔢 Badge с количеством */
.remaining-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    background: var(--bs-primary);
    color: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    transition: all 0.3s ease;
}

.load-more-btn:hover:not(:disabled) .remaining-badge {
    background: white;
    color: var(--bs-primary);
    transform: scale(1.1);
}

/* 🎬 Анимация изменения числа */
.count-flip-enter-active,
.count-flip-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.count-flip-enter-from {
    opacity: 0;
    transform: translateY(-100%) scale(0.5);
}

.count-flip-leave-to {
    opacity: 0;
    transform: translateY(100%) scale(0.5);
}

/* ✨ Свечение при наведении */
.btn-glow {
    position: absolute;
    inset: -2px;
    background: linear-gradient(135deg, var(--bs-primary), rgba(var(--bs-primary-rgb), 0.5));
    border-radius: 14px;
    opacity: 0;
    filter: blur(20px);
    transition: opacity 0.3s ease;
    z-index: -1;
}

.load-more-btn:hover:not(:disabled) .btn-glow {
    opacity: 0.4;
}

/* 📱 Адаптив */
@media (max-width: 576px) {
    .load-more-btn {
        padding: 0.875rem 1.5rem;
        font-size: 0.9rem;
    }

    .remaining-badge {
        min-width: 24px;
        height: 24px;
        font-size: 0.8rem;
    }
}
</style>
