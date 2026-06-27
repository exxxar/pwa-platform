<template>
    <router-link
        :to="{ name: 'Calculator' }"
        class="calculator-button"
        :class="[
            `mode-${mode}`,
            { 'has-glow': glow, 'is-animated': animated }
        ]"
    >
        <!-- Фоновые эффекты -->
        <div class="button-effects">
            <div class="effect-blob blob-1"></div>
            <div class="effect-blob blob-2"></div>
            <div v-if="mode === 'banner'" class="effect-grid"></div>
            <div v-if="mode === 'banner'" class="effect-particles">
                <span v-for="i in 8" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
        </div>

        <!-- Контент -->
        <div class="button-content">

            <!-- Иконка -->
            <div class="button-icon">
                <i class="fa-solid fa-calculator"></i>
                <div class="icon-ring"></div>
            </div>

            <!-- Текст -->
            <div class="button-text">
                <div v-if="badge" class="button-badge">
                    <i class="fa-solid fa-sparkles"></i>
                    <span>{{ badge }}</span>
                </div>
                <h3 class="button-title">{{ title }}</h3>
                <p v-if="description" class="button-description">{{ description }}</p>
            </div>

            <!-- Стрелка -->
            <div class="button-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>

        </div>

        <!-- Декоративная цена (для banner) -->
        <div v-if="mode === 'banner' && showPrice" class="button-price">
            <span class="price-from">от</span>
            <span class="price-value">50 000 ₽</span>
        </div>

    </router-link>
</template>

<script>
export default {
    name: 'CalculatorButton',

    props: {
        mode: {
            type: String,
            default: 'card',
            validator: (v) => ['button', 'card', 'banner', 'fab'].includes(v),
        },
        title: {
            type: String,
            default: 'Рассчитать стоимость',
        },
        description: {
            type: String,
            default: 'Узнайте точную цену за 2 минуты',
        },
        badge: {
            type: String,
            default: 'Бесплатно',
        },
        glow: {
            type: Boolean,
            default: true,
        },
        animated: {
            type: Boolean,
            default: true,
        },
        showPrice: {
            type: Boolean,
            default: true,
        },
    },

    methods: {
        particleStyle(i) {
            return {
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
                animationDelay: `${Math.random() * 3}s`,
                animationDuration: `${Math.random() * 3 + 3}s`,
            };
        },
    },
};
</script>

<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #667eea;
$primary-dark: #5a67d8;
$primary-light: #7c8cf5;
$accent: #f093fb;
$accent-2: #f5576c;
$gold: #ffd89b;
$orange: #ff6a00;
$white: #ffffff;
$text: #1f2937;
$text-muted: #6b7280;

// ==========================================
// БАЗА
// ==========================================
.calculator-button {
    position: relative;
    display: flex;
    align-items: center;
    text-decoration: none;
    color: white;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    border: none;
    font-family: inherit;

    &:hover {
        transform: translateY(-4px);

        .button-arrow {
            transform: translateX(6px);
        }

        .effect-blob {
            transform: scale(1.2);
        }
    }

    &:active {
        transform: translateY(-2px) scale(0.98);
    }
}

// ==========================================
// ФОНОВЫЕ ЭФФЕКТЫ
// ==========================================
.button-effects {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.effect-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(30px);
    opacity: 0.5;
    transition: transform 0.6s ease;

    &.blob-1 {
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.2);
        top: -50px;
        right: -50px;
    }

    &.blob-2 {
        width: 150px;
        height: 150px;
        background: rgba($accent, 0.3);
        bottom: -40px;
        left: -40px;
    }
}

.effect-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    background-size: 40px 40px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
}

.effect-particles {
    position: absolute;
    inset: 0;
}

.particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: white;
    border-radius: 50%;
    opacity: 0.6;
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% { transform: translate(0, 0); opacity: 0; }
    10% { opacity: 0.6; }
    90% { opacity: 0.6; }
    100% { transform: translate(20px, -30px); opacity: 0; }
}

// ==========================================
// КОНТЕНТ
// ==========================================
.button-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 16px;
    width: 100%;
}

.button-icon {
    position: relative;
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
    transition: all 0.3s;

    .is-animated & {
        animation: iconPulse 3s ease-in-out infinite;
    }
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.icon-ring {
    position: absolute;
    inset: -4px;
    border-radius: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    opacity: 0;

    .has-glow & {
        animation: ringPulse 2s ease-in-out infinite;
    }
}

@keyframes ringPulse {
    0% { transform: scale(1); opacity: 0.6; }
    100% { transform: scale(1.3); opacity: 0; }
}

.button-text {
    flex: 1;
    min-width: 0;
}

.button-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 700;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    i {
        color: $gold;
        font-size: 0.65rem;
    }
}

.button-title {
    font-size: 1.1rem;
    font-weight: 800;
    margin: 0 0 4px;
    line-height: 1.2;
}

.button-description {
    font-size: 0.85rem;
    opacity: 0.9;
    margin: 0;
    line-height: 1.4;
}

.button-arrow {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
    transition: transform 0.3s ease;
}

// ==========================================
// РЕЖИМ: КНОПКА (компактная)
// ==========================================
.mode-button {
    padding: 12px 20px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    border-radius: 50px;
    box-shadow: 0 6px 20px rgba($primary, 0.3);

    .button-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        border-radius: 12px;
    }

    .button-title {
        font-size: 0.95rem;
        margin: 0;
    }

    .button-description,
    .button-badge {
        display: none;
    }

    .button-arrow {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }

    &:hover {
        box-shadow: 0 10px 28px rgba($primary, 0.4);
    }
}

// ==========================================
// РЕЖИМ: КАРТОЧКА (стандартный)
// ==========================================
.mode-card {
    padding: 20px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba($primary, 0.3);

    &:hover {
        box-shadow: 0 12px 32px rgba($primary, 0.4);
    }
}

// ==========================================
// РЕЖИМ: БАННЕР (большой)
// ==========================================
.mode-banner {
    padding: 32px;
    background: linear-gradient(135deg, $primary 0%, $accent 50%, $accent-2 100%);
    border-radius: 24px;
    box-shadow: 0 12px 40px rgba($primary, 0.3);

    .button-icon {
        width: 72px;
        height: 72px;
        font-size: 1.8rem;
        border-radius: 20px;
    }

    .button-title {
        font-size: 1.6rem;
        margin-bottom: 8px;
    }

    .button-description {
        font-size: 1rem;
    }

    .button-arrow {
        width: 52px;
        height: 52px;
        font-size: 1.1rem;
    }

    &:hover {
        box-shadow: 0 16px 48px rgba($primary, 0.4);
    }
}

.button-price {
    position: absolute;
    top: 20px;
    right: 24px;
    display: flex;
    align-items: baseline;
    gap: 6px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    z-index: 2;

    .price-from {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .price-value {
        font-size: 1.1rem;
        font-weight: 800;
    }
}

// ==========================================
// РЕЖИМ: FAB (плавающая кнопка)
// ==========================================
.mode-fab {
    position: fixed;
    right: 20px;
    bottom: 240px;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    box-shadow: 0 8px 24px rgba($primary, 0.4);
    z-index: 998;

    .button-content {
        justify-content: center;
        gap: 0;
    }

    .button-icon {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: transparent;
        border: none;
        font-size: 1.5rem;
    }

    .button-text,
    .button-arrow,
    .button-badge,
    .icon-ring {
        display: none;
    }

    // Tooltip при наведении
    &::after {
        content: 'Рассчитать стоимость';
        position: absolute;
        right: calc(100% + 12px);
        top: 50%;
        transform: translateY(-50%) translateX(10px);
        padding: 8px 14px;
        background: $text;
        color: white;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s ease;
    }

    &:hover::after {
        opacity: 1;
        transform: translateY(-50%) translateX(0);
    }

    &:hover {
        transform: scale(1.1);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .mode-banner {
        padding: 24px 20px;

        .button-icon {
            width: 56px;
            height: 56px;
            font-size: 1.4rem;
        }

        .button-title {
            font-size: 1.25rem;
        }

        .button-description {
            font-size: 0.9rem;
        }

        .button-price {
            position: static;
            margin-top: 16px;
            align-self: flex-start;
        }

        .button-content {
            flex-wrap: wrap;
        }
    }

    .mode-fab {
        right: 16px;
        width: 56px;
        height: 56px;
    }
}
</style>
