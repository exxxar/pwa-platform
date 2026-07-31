<template>
    <transition name="notification">
        <div v-if="achievement" class="unlocked-notification" @click="$emit('close')">
            <div class="notification-bg"></div>
            <div class="notification-content">
                <div class="notification-icon">
                    <i :class="achievement.icon || 'fa-solid fa-trophy'"></i>
                    <div class="sparkles">
                        <span v-for="i in 8" :key="i" class="sparkle"></span>
                    </div>
                </div>
                <div class="notification-info">
                    <div class="notification-label">🏆 Достижение разблокировано!</div>
                    <div class="notification-title">{{ achievement.title }}</div>
                    <div v-if="achievement.description" class="notification-desc">
                        {{ achievement.description }}
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: 'UnlockedNotification',

    props: {
        achievement: { type: Object, default: null },
    },

    emits: ['close'],

    mounted() {
        // Автоматическое закрытие через 5 секунд
        setTimeout(() => {
            this.$emit('close');
        }, 5000);
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

$success: #22c55e;
$warning: #f59e0b;

.unlocked-notification {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 32px);
    max-width: 420px;
    z-index: 9999;
    cursor: pointer;
}

.notification-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 16px;
    border: 2px solid $warning;
    box-shadow: 0 12px 40px rgba(245, 158, 11, 0.3);
}

.notification-content {
    position: relative;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
}

.notification-icon {
    position: relative;
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: linear-gradient(135deg, $warning 0%, #d97706 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.6rem;
    flex-shrink: 0;
    animation: iconBounce 0.6s ease;
}

@keyframes iconBounce {
    0% { transform: scale(0) rotate(-180deg); }
    50% { transform: scale(1.2) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}

.sparkles {
    position: absolute;
    inset: -10px;
    pointer-events: none;
}

.sparkle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: $warning;
    border-radius: 50%;
    animation: sparkle 1.5s ease-in-out infinite;

    @for $i from 1 through 8 {
        &:nth-child(#{$i}) {
            $angle: ($i - 1) * 45deg;
            $distance: 20px;
            left: calc(50% + cos($angle) * #{$distance});
            top: calc(50% + sin($angle) * #{$distance});
            animation-delay: #{$i * 0.1}s;
        }
    }
}

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0); }
    50% { opacity: 1; transform: scale(1); }
}

.notification-info {
    flex: 1;
    min-width: 0;
}

.notification-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: color.adjust($warning, $lightness: -20%);
    margin-bottom: 2px;
}

.notification-title {
    font-size: 1rem;
    font-weight: 700;
    color: #78350f;
    line-height: 1.2;
    margin-bottom: 2px;
}

.notification-desc {
    font-size: 0.8rem;
    color: #92400e;
    opacity: 0.8;
}

// Анимации
.notification-enter-active {
    animation: slideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.notification-leave-active {
    animation: slideUp 0.3s ease-in;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translate(-50%, -100%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, 0);
    }
}

@keyframes slideUp {
    from {
        opacity: 1;
        transform: translate(-50%, 0);
    }
    to {
        opacity: 0;
        transform: translate(-50%, -100%);
    }
}
</style>
