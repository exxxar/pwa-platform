<template>
    <button
        type="button"
        class="favorites-fab"
        :class="{ 'has-items': count > 0, 'is-pulsing': isPulsing }"
        @click="$emit('click')"
        :title="`Избранное (${count})`"
    >
        <!-- Иконка сердца -->
        <div class="fab-icon">
            <i class="fa-solid fa-heart"></i>
        </div>

        <!-- Бейдж счётчика -->
        <transition name="badge-pop">
            <div v-if="count > 0" class="fab-badge">
                {{ count > 99 ? '99+' : count }}
            </div>
        </transition>

        <!-- Пульсирующие кольца (когда есть товары) -->
        <div v-if="count > 0" class="fab-pulse">
            <div class="pulse-ring ring-1"></div>
            <div class="pulse-ring ring-2"></div>
        </div>
    </button>
</template>

<script>
export default {
    name: "FavoritesButton",

    props: {
        count: {
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
        count(newValue, oldValue) {
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
.favorites-fab {
    position: fixed;
    right: 20px;
    bottom: 100px; /* Отступ от нижней навигации */
    z-index: 999;

    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 20px rgba(238, 9, 121, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: visible;
}

/* Hover эффект */
.favorites-fab:hover {
    transform: scale(1.1) translateY(-4px);
    box-shadow: 0 10px 28px rgba(238, 9, 121, 0.5);
}

/* Active эффект */
.favorites-fab:active {
    transform: scale(1.05) translateY(-2px);
}

/* Фокус для доступности */
.favorites-fab:focus-visible {
    outline: 3px solid rgba(238, 9, 121, 0.5);
    outline-offset: 4px;
}

/* ==========================================
   ИКОНКА
   ========================================== */
.fab-icon {
    font-size: 1.4rem;
    transition: transform 0.3s ease;
}

.favorites-fab:hover .fab-icon {
    transform: scale(1.15);
}

/* Пульсация при добавлении товара */
.favorites-fab.is-pulsing .fab-icon {
    animation: heartBeat 0.6s ease;
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(0.9); }
    75% { transform: scale(1.15); }
    100% { transform: scale(1); }
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
    color: #ee0979;
    border-radius: 11px;
    font-size: 0.7rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    border: 2px solid #ee0979;
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
    border: 2px solid rgba(238, 9, 121, 0.6);
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
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .favorites-fab {
        right: 16px;
        bottom: 90px;
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
}

/* Для очень маленьких экранов */
@media (max-width: 360px) {
    .favorites-fab {
        right: 12px;
        bottom: 80px;
        width: 46px;
        height: 46px;
    }

    .fab-icon {
        font-size: 1.1rem;
    }
}
</style>
