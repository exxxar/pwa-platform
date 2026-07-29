<template>
    <!-- Обычный тег <a> для нативного перехода без Vue Router -->
    <a
        v-if="href"
        :href="href"
        target="_blank"
        class="premium-taplink-btn"
        :aria-label="label"
    >
        <!-- Анимированный градиентный фон -->
        <span class="btn-gradient-bg"></span>

        <!-- Стеклянный слой -->
        <span class="btn-glass-layer"></span>

        <!-- Контент кнопки -->
        <span class="btn-content">
            <i :class="icon" class="btn-icon"></i>
            <span class="btn-text">{{ label }}</span>
        </span>

        <!-- Эффект свечения при наведении -->
        <span class="btn-hover-glow"></span>
    </a>
</template>

<script>
export default {
    name: 'TaplinkButton',

    props: {
        // Объект tenant, который уже есть в вашем приложении
        tenant: {
            type: Object,
            default: null
        },
        // Текст кнопки
        label: {
            type: String,
            default: 'Открыть Taplink'
        },
        // Иконка (FontAwesome)
        icon: {
            type: String,
            default: 'fa-solid fa-arrow-up-right-from-square'
        }
    },

    computed: {
        href() {
            return '/taplink';
        }
    }
};
</script>

<style lang="scss" scoped>
$btn-primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
$btn-glass-bg: rgba(255, 255, 255, 0.05);
$btn-glass-border: rgba(255, 255, 255, 0.2);
$btn-text-color: #ffffff;
$btn-shadow: 0 8px 32px rgba(168, 85, 247, 0.25);

.premium-taplink-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 28px;
    border-radius: 9999px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    color: $btn-text-color;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid $btn-glass-border;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    isolation: isolate;

    .btn-gradient-bg {
        position: absolute;
        inset: 0;
        background: $btn-primary-gradient;
        background-size: 200% 200%;
        opacity: 0.8;
        transition: opacity 0.4s ease;
        animation: gradientShift 4s ease infinite;
        z-index: -2;
    }

    .btn-glass-layer {
        position: absolute;
        inset: 0;
        background: $btn-glass-bg;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: -1;
        transition: background 0.4s ease;
    }

    .btn-content {
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        z-index: 2;
    }

    .btn-icon {
        font-size: 1.1rem;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-text {
        letter-spacing: 0.02em;
    }

    .btn-hover-glow {
        position: absolute;
        inset: -2px;
        background: $btn-primary-gradient;
        border-radius: 9999px;
        opacity: 0;
        filter: blur(12px);
        z-index: -3;
        transition: opacity 0.4s ease;
    }

    &:hover {
        transform: translateY(-3px);
        box-shadow: $btn-shadow;
        border-color: rgba(255, 255, 255, 0.4);

        .btn-gradient-bg { opacity: 1; }
        .btn-glass-layer { background: rgba(255, 255, 255, 0.1); }
        .btn-hover-glow { opacity: 0.6; }
        .btn-icon { transform: translate(2px, -2px); }
    }

    &:active {
        transform: translateY(-1px) scale(0.97);
        box-shadow: 0 2px 10px rgba(168, 85, 247, 0.3);
        transition-duration: 0.1s;
    }

    &:focus-visible {
        outline: 2px solid #a855f7;
        outline-offset: 4px;
    }
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@media (max-width: 480px) {
    .premium-taplink-btn {
        padding: 12px 24px;
        font-size: 0.95rem;
        width: 100%;
        max-width: 320px;
    }
}
</style>
