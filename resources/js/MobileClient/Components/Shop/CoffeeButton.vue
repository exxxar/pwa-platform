<template>
    <button
        type="button"
        class="coffee-fab"
        :class="{ 'has-cups': cupsCount > 0, 'is-pulsing': isPulsing }"
        @click="$emit('click')"
        :title="`Собрано чашек: ${cupsCount}`"
    >
        <!-- Иконка кружки -->
        <div class="fab-icon">
            <i class="fa-solid fa-mug-hot"></i>
        </div>

        <!-- Бейдж счётчика -->
        <transition name="badge-pop">
            <div v-if="cupsCount > 0" class="fab-badge">
                {{ cupsCount > 99 ? '99+' : cupsCount }}
            </div>
        </transition>

        <!-- Пульсирующие кольца (когда есть чашки) -->
        <div v-if="cupsCount > 0" class="fab-pulse">
            <div class="pulse-ring ring-1"></div>
            <div class="pulse-ring ring-2"></div>
        </div>

        <!-- Пар от кофе (анимация) -->
        <div v-if="cupsCount > 0" class="steam-container">
            <div class="steam steam-1"></div>
            <div class="steam steam-2"></div>
            <div class="steam steam-3"></div>
        </div>
    </button>
</template>

<script>
export default {
    name: "CoffeeCupsButton",

    props: {
        cupsCount: {
            type: Number,
            default: 0,
        },
    },

    emits: ['click'],

    data() {
        return {
            isPulsing: false,
        };
    },

    watch: {
        cupsCount(newValue, oldValue) {
            // Анимация пульсации при изменении количества
            if (newValue > oldValue) {
                this.isPulsing = true;
                setTimeout(() => {
                    this.isPulsing = false;
                }, 600);
            }
        },
    },
};
</script>

<style scoped>
/* ==========================================
   КНОПКА FAB (FLOATING ACTION BUTTON)
   ========================================== */
.coffee-fab {
    position: fixed;
    right: 20px;
    bottom: 170px; /* Выше кнопки избранного (100px) */
    z-index: 999;

    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: visible;
}

/* Hover эффект */
.coffee-fab:hover {
    transform: scale(1.1) translateY(-4px);
    box-shadow: 0 10px 28px rgba(139, 69, 19, 0.5);
}

/* Active эффект */
.coffee-fab:active {
    transform: scale(1.05) translateY(-2px);
}

/* Фокус для доступности */
.coffee-fab:focus-visible {
    outline: 3px solid rgba(139, 69, 19, 0.5);
    outline-offset: 4px;
}

/* ==========================================
   ИКОНКА
   ========================================== */
.fab-icon {
    font-size: 1.4rem;
    transition: transform 0.3s ease;
}

.coffee-fab:hover .fab-icon {
    transform: scale(1.15);
}

/* Пульсация при добавлении чашки */
.coffee-fab.is-pulsing .fab-icon {
    animation: mugShake 0.6s ease;
}

@keyframes mugShake {
    0% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.2) rotate(-10deg); }
    50% { transform: scale(1.1) rotate(10deg); }
    75% { transform: scale(1.15) rotate(-5deg); }
    100% { transform: scale(1) rotate(0deg); }
}

/* ==========================================
   БЕЙДЖ СЧЁТЧИКА
   ========================================== */
.fab-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    background: white;
    color: #8B4513;
    border-radius: 11px;
    font-size: 0.7rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    border: 2px solid #8B4513;
    line-height: 1;
}

/* Анимация появления бейджа */
.badge-pop-enter-active {
    animation: badgePop 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge-pop-leave-active {
    animation: badgePop 0.3s cubic-bezier(0.4, 0, 0.2, 1) reverse;
}

@keyframes badgePop {
    0% {
        opacity: 0;
        transform: scale(0) rotate(-180deg);
    }
    50% {
        transform: scale(1.2) rotate(10deg);
    }
    100% {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

/* ==========================================
   ПУЛЬСИРУЮЩИЕ КОЛЬЦА
   ========================================== */
.fab-pulse {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    pointer-events: none;
}

.pulse-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid rgba(139, 69, 19, 0.6);
    animation: pulseRing 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.pulse-ring.ring-1 {
    animation-delay: 0s;
}

.pulse-ring.ring-2 {
    animation-delay: 0.6s;
}

@keyframes pulseRing {
    0% {
        transform: scale(1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1.8);
        opacity: 0;
    }
}

/* ==========================================
   ПАР ОТ КОФЕ (анимация)
   ========================================== */
.steam-container {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 20px;
    pointer-events: none;
}

.steam {
    position: absolute;
    bottom: 0;
    width: 4px;
    height: 12px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    filter: blur(2px);
    animation: steamRise 2s ease-in-out infinite;
}

.steam-1 {
    left: 6px;
    animation-delay: 0s;
}

.steam-2 {
    left: 13px;
    animation-delay: 0.4s;
}

.steam-3 {
    left: 20px;
    animation-delay: 0.8s;
}

@keyframes steamRise {
    0% {
        transform: translateY(0) scale(1);
        opacity: 0.6;
    }
    50% {
        transform: translateY(-10px) scale(1.2);
        opacity: 0.8;
    }
    100% {
        transform: translateY(-20px) scale(0.8);
        opacity: 0;
    }
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .coffee-fab {
        right: 16px;
        bottom: 160px;
        width: 50px;
        height: 50px;
    }

    .fab-icon {
        font-size: 1.2rem;
    }

    .fab-badge {
        min-width: 20px;
        height: 20px;
        font-size: 0.65rem;
        top: -3px;
        right: -3px;
    }

    .steam-container {
        top: -6px;
        width: 26px;
        height: 18px;
    }

    .steam {
        width: 3px;
        height: 10px;
    }
}

/* Для очень маленьких экранов */
@media (max-width: 360px) {
    .coffee-fab {
        right: 12px;
        bottom: 150px;
        width: 46px;
        height: 46px;
    }

    .fab-icon {
        font-size: 1.1rem;
    }

    .steam-container {
        top: -5px;
        width: 24px;
    }
}
</style>
