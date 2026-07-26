<template>
    <div class="custom-wheel-container" :style="{ width: size + 'px', height: size + 'px' }">
        <!-- Указатель (стрелка сверху) -->
        <div class="wheel-pointer">
            <div class="pointer-glow"></div>
            <i class="fa-solid fa-caret-down"></i>
        </div>

        <!-- Вращающееся колесо -->
        <svg
            class="wheel-svg"
            :style="{ transform: `rotate(${rotation}deg)`, transition: isSpinning ? `transform ${duration}ms cubic-bezier(0.25, 0.1, 0.25, 1)` : 'none' }"
            :viewBox="`0 0 ${size} ${size}`"
            @transitionend="onSpinEnd"
        >
            <defs>
                <!-- Градиент для внешнего обода -->
                <radialGradient id="rimGradient" cx="50%" cy="50%" r="50%">
                    <stop offset="85%" stop-color="#f59e0b" />
                    <stop offset="92%" stop-color="#fbbf24" />
                    <stop offset="100%" stop-color="#d97706" />
                </radialGradient>

                <!-- Градиент для центра -->
                <radialGradient id="centerGradient" cx="30%" cy="30%" r="70%">
                    <stop offset="0%" stop-color="#ffffff" />
                    <stop offset="60%" stop-color="#f8fafc" />
                    <stop offset="100%" stop-color="#e2e8f0" />
                </radialGradient>

                <!-- Тень для объема -->
                <filter id="dropShadow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="4" stdDeviation="6" flood-opacity="0.3" />
                </filter>

                <!-- Свечение для лампочек -->
                <filter id="bulbGlow" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                    <feMerge>
                        <feMergeNode in="coloredBlur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
            </defs>

            <!-- 🎯 ВНЕШНИЙ ОБОД (золотое кольцо) -->
            <circle
                :cx="center"
                :cy="center"
                :r="radius + 15"
                fill="url(#rimGradient)"
                stroke="#92400e"
                stroke-width="2"
                filter="url(#dropShadow)"
            />

            <!-- Декоративные зубчики по периметру -->
            <g v-for="i in teethCount" :key="'tooth-' + i">
                <circle
                    :cx="center + (radius + 15) * Math.cos((i - 1) * (360 / teethCount) * Math.PI / 180)"
                    :cy="center + (radius + 15) * Math.sin((i - 1) * (360 / teethCount) * Math.PI / 180)"
                    r="4"
                    fill="#fef3c7"
                    stroke="#d97706"
                    stroke-width="1"
                />
            </g>

            <!-- 🎯 МЕРЦАЮЩИЕ ЛАМПОЧКИ (внутренний ряд) -->
            <g v-for="i in bulbsCount" :key="'bulb-' + i">
                <circle
                    :cx="center + (radius + 5) * Math.cos((i - 1) * (360 / bulbsCount) * Math.PI / 180)"
                    :cy="center + (radius + 5) * Math.sin((i - 1) * (360 / bulbsCount) * Math.PI / 180)"
                    r="3"
                    :fill="getBulbColor(i)"
                    :class="{ 'bulb-blink': i % 2 === 0 }"
                    filter="url(#bulbGlow)"
                />
            </g>

            <!-- 🎯 СЕКТОРА -->
            <g v-for="(item, index) in items" :key="item.id">
                <path
                    :d="getSectorPath(index)"
                    :fill="item.bgColor || '#ffffff'"
                    stroke="#ffffff"
                    stroke-width="3"
                />
                <!-- Эффект объема (легкая тень внутри сектора) -->
                <path
                    :d="getSectorPath(index)"
                    fill="url(#sectorShadow)"
                    opacity="0.15"
                />

                <!-- Иконка/Текст -->
                <g :transform="getIconTransform(index)">
                    <text
                        x="0"
                        y="0"
                        :fill="item.color || '#000000'"
                        :font-size="iconSize"
                        font-weight="bold"
                        text-anchor="middle"
                        dominant-baseline="central"
                        dy="0.1em"
                    >
                        {{ item.value }}
                    </text>
                </g>
            </g>

            <!-- 🎯 ЦЕНТРАЛЬНЫЙ КРУГ (красивая ступица) -->
            <circle
                :cx="center"
                :cy="center"
                :r="centerRadius + 8"
                fill="#92400e"
            />
            <circle
                :cx="center"
                :cy="center"
                :r="centerRadius"
                fill="url(#centerGradient)"
                stroke="#fbbf24"
                stroke-width="4"
            />

            <!-- Текст или иконка в центре -->
            <text
                :x="center"
                :y="center - 8"
                fill="#c0392b"
                font-size="14"
                font-weight="900"
                text-anchor="middle"
                dominant-baseline="central"
                letter-spacing="2"
            >
                КРУТИ
            </text>
            <text
                :x="center"
                :y="center + 10"
                fill="#c0392b"
                font-size="14"
                font-weight="900"
                text-anchor="middle"
                dominant-baseline="central"
                letter-spacing="2"
            >
                СМОТРИ
            </text>
        </svg>
    </div>
</template>

<script>
export default {
    name: "CustomFortuneWheel",
    props: {
        items: { type: Array, required: true },
        size: { type: Number, default: 450 },
        duration: { type: Number, default: 4000 },
        iconSize: { type: [Number, String], default: 32 },
        rotateIcons: { type: Boolean, default: false }
    },
    emits: ['done', 'spin-start'],

    data() {
        return {
            rotation: 0,
            isSpinning: false,
            targetIndex: null
        };
    },

    computed: {
        center() { return this.size / 2; },
        radius() { return (this.size / 2) - 30; }, // Увеличен отступ для обода
        centerRadius() { return this.size * 0.13; },
        step() { return 360 / (this.items.length || 1); },
        teethCount() { return 48; }, // Количество зубчиков
        bulbsCount() { return 24; } // Количество лампочек
    },

    methods: {
        degToRad(deg) {
            return (deg - 90) * (Math.PI / 180);
        },

        getSectorPath(index) {
            const startRad = this.degToRad(index * this.step);
            const endRad = this.degToRad((index + 1) * this.step);

            const x1 = this.center + this.radius * Math.cos(startRad);
            const y1 = this.center + this.radius * Math.sin(startRad);
            const x2 = this.center + this.radius * Math.cos(endRad);
            const y2 = this.center + this.radius * Math.sin(endRad);

            return `M ${this.center} ${this.center} L ${x1} ${y1} A ${this.radius} ${this.radius} 0 0 1 ${x2} ${y2} Z`;
        },

        getMidAngle(index) {
            return (index * this.step) + (this.step / 2);
        },

        getIconTransform(index) {
            const midAngle = this.getMidAngle(index);
            const distance = this.radius * 0.65;

            // Угол поворота с поправкой -90° (чтобы совпадало с секторами)
            const angle = midAngle - 90;

            // Цепочка трансформаций:
            // 1. translate(center, center) — переносим начало координат в центр колеса
            // 2. rotate(angle) — поворачиваем ось X в середину сектора
            // 3. translate(distance, 0) — сдвигаем иконку наружу вдоль оси X
            // 4. rotate(-angle) — ПОВОРАЧИВАЕМ ОБРАТНО, чтобы иконка стояла ровно
            return [
                `translate(${this.center}, ${this.center})`,
                `rotate(${angle})`,
                `translate(${distance}, 0)`,
                `rotate(${-angle})`
            ].join(' ');
        },

        // 🆕 Чередование цветов лампочек для эффекта "бегущих огней"
        getBulbColor(index) {
            const colors = ['#ef4444', '#fbbf24', '#10b981', '#3b82f6'];
            return colors[index % colors.length];
        },

        spin(winningIndex = null) {
            if (this.isSpinning || this.items.length < 2) return;

            this.isSpinning = true;
            this.$emit('spin-start');

            this.targetIndex = winningIndex !== null
                ? winningIndex
                : Math.floor(Math.random() * this.items.length);

            const spins = 360 * 5;
            const sectorCenter = (this.targetIndex * this.step) + (this.step / 2);
            const targetRotation = 360 - sectorCenter;
            const randomOffset = (Math.random() - 0.5) * (this.step * 0.6);

            const currentMod = this.rotation % 360;
            const additionalRotation = spins + targetRotation - currentMod + randomOffset;

            this.rotation += additionalRotation;
        },

        onSpinEnd() {
            if (!this.isSpinning) return;
            this.isSpinning = false;

            const winner = this.items[this.targetIndex];
            this.$emit('done', winner);
        }
    }
};
</script>

<style scoped>
.custom-wheel-container {
    position: relative;
    margin: 0 auto;
    filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.25));
}

/* 🎯 УКАЗАТЕЛЬ С ЭФФЕКТОМ СВЕЧЕНИЯ */
.wheel-pointer {
    position: absolute;
    top: -5px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    font-size: 56px;
    color: #c0392b;
    filter: drop-shadow(0 4px 8px rgba(192, 57, 43, 0.6));
    animation: pointerPulse 2s ease-in-out infinite;
}

.pointer-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 40px;
    height: 40px;
    background: radial-gradient(circle, rgba(251, 191, 36, 0.6) 0%, transparent 70%);
    border-radius: 50%;
    animation: glowPulse 2s ease-in-out infinite;
    z-index: -1;
}

@keyframes pointerPulse {
    0%, 100% { transform: translateX(-50%) scale(1); }
    50% { transform: translateX(-50%) scale(1.05); }
}

@keyframes glowPulse {
    0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
    50% { opacity: 1; transform: translate(-50%, -50%) scale(1.3); }
}

.wheel-svg {
    width: 100%;
    height: 100%;
    border-radius: 50%;
}

/* 🎯 МЕРЦАНИЕ ЛАМПОЧЕК */
.bulb-blink {
    animation: bulbBlink 1.5s ease-in-out infinite;
}

@keyframes bulbBlink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>
