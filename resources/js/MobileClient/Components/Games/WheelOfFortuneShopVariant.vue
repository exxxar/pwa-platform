<template>
    <div class="wheel-game-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="wheel-hero">
            <div class="hero-background"></div>
            <div class="hero-particles">
                <span v-for="i in 20" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">
                        <i class="fa-solid fa-dharmachakra"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">✨</div>
                    <div class="hero-sparkle sparkle-2">✨</div>
                    <div class="hero-sparkle sparkle-3">✨</div>
                </div>
                <h1 class="hero-title">Колесо Фортуны</h1>
                <p class="hero-subtitle">Испытай удачу и выиграй приз!</p>

                <!-- Статус возможностей -->
                <div class="hero-status">
                    <div v-if="canPlay" class="status-badge ready">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Готово к игре</span>
                    </div>
                    <div v-else class="status-badge disabled">
                        <i class="fa-solid fa-ban"></i>
                        <span>Попытки закончились</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="wheel-content">

            <!-- ========================================== -->
            <!-- ВЫБОР ПРИЗА (ДО ВРАЩЕНИЯ) -->
            <!-- ========================================== -->
            <div v-if="!selectedPrize && !form.win" class="prizes-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Возможные призы</h6>
                        <p class="section-subtitle">Нажмите на приз, чтобы узнать подробности</p>
                    </div>
                </div>

                <div class="prizes-preview-grid">
                    <button
                        v-for="(item, index) in settings"
                        :key="item.id"
                        type="button"
                        class="prize-preview-card"
                        @click="selectPrize(index)"
                    >
                        <div class="prize-preview-emoji">{{ item.value }}</div>
                        <div class="prize-preview-name">Приз #{{ item.id }}</div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ДЕТАЛИ ВЫБРАННОГО ПРИЗА -->
            <!-- ========================================== -->
            <transition name="fade-up">
                <div v-if="selectedPrize" class="selected-prize-card" @click="selectedPrize = null">
                    <div class="selected-prize-header">
                        <div class="selected-prize-icon">
                            {{ selectedPrize.value }}
                        </div>
                        <div class="selected-prize-info">
                            <h6 class="selected-prize-title">Приз #{{ selectedPrize.id }}</h6>
                            <p class="selected-prize-desc">{{ selectedPrize.description || 'Описание отсутствует' }}</p>
                        </div>
                        <button class="close-details-btn">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="selected-prize-footer">
                        <div class="prize-mark">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Способ получения: <strong>{{ selectedPrize.mark || 'не указано' }}</strong></span>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- ========================================== -->
            <!-- КОЛЕСО -->
            <!-- ========================================== -->
            <div v-if="loaded" class="wheel-section">
                <div class="wheel-wrapper">
                    <div class="wheel-decoration decoration-1"></div>
                    <div class="wheel-decoration decoration-2"></div>

                    <div class="wheel-container">
                        <Wheel
                            :gift="gift"
                            :imgParams="logo"
                            @done="done"
                            ref="wheel"
                            v-model="settings"
                        />
                    </div>

                    <!-- Кнопка запуска -->
                    <button
                        v-if="enabledToPlay"
                        type="button"
                        class="spin-btn"
                        :class="{ 'is-spinning': started }"
                        :disabled="started"
                        @click="launchWheel"
                    >
                        <div class="spin-btn-content">
                            <i class="fa-solid fa-play"></i>
                            <span>{{ started ? 'Крутится...' : 'Крутить!' }}</span>
                        </div>
                    </button>
                    <div v-else class="spin-disabled">
                        <i class="fa-solid fa-lock"></i>
                        <span>{{ started ? 'Подождите...' : 'Нет доступных попыток' }}</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- РЕЗУЛЬТАТ -->
            <!-- ========================================== -->
            <transition name="result-popup">
                <div v-if="form.win" class="result-section">
                    <div class="result-card" :class="{ 'is-new': completed_at }">

                        <!-- Конфетти для нового выигрыша -->
                        <div v-if="completed_at" class="result-confetti">
                            <span v-for="i in 30" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                        </div>

                        <div class="result-header">
                            <div class="result-icon">
                                <i class="fa-solid fa-trophy"></i>
                            </div>
                            <div class="result-title-wrapper">
                                <h5 class="result-title">
                                    {{ completed_at ? 'Ваш выигрыш!' : 'Ваш прошлый выигрыш' }}
                                </h5>
                                <p class="result-subtitle">
                                    {{ completed_at ? 'Поздравляем с победой!' : 'Результат предыдущей игры' }}
                                </p>
                            </div>
                        </div>

                        <div class="result-body">
                            <div class="result-prize-display">
                                <div class="result-prize-emoji">{{ form.win.value }}</div>
                                <div class="result-prize-info">
                                    <div class="result-prize-name">Приз #{{ form.win.id }}</div>
                                    <div class="result-prize-desc">{{ form.win.description || 'не указано' }}</div>
                                </div>
                            </div>

                            <!-- Информация о получении (только для нового выигрыша) -->
                            <div v-if="completed_at" class="result-claim-info">
                                <div class="claim-method">
                                    <i class="fa-solid fa-hand-holding-heart"></i>
                                    <div class="claim-content">
                                        <div class="claim-label">Способ получения</div>
                                        <div class="claim-value">{{ form.win.mark || 'не указано' }}</div>
                                    </div>
                                </div>

                                <div class="claim-deadline">
                                    <i class="fa-solid fa-clock"></i>
                                    <div class="claim-content">
                                        <div class="claim-label">Получить в течение</div>
                                        <div class="claim-value">
                                            {{ preparedInterval }}
                                            <span class="deadline-date">(до {{ formatDate(completed_at) }})</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="claim-warning">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>Успейте получить приз в указанный срок!</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </transition>

        </div>
    </div>
</template>

<script>
import { Wheel } from 'vue3-fortune-wheel';

export default {
    name: "WheelOfFortune",

    components: {
        Wheel,
    },

    props: {
        modelValue: {
            type: Array,
            required: true,
        },
        canPlay: {
            type: Boolean,
            default: false,
        },
        actionData: {
            type: Object,
            default: null,
        },
        isAdmin: {
            type: Boolean,
            default: false,
        },
        interval: {
            type: Number,
            default: 1,
        },
    },

    emits: ['win'],

    data() {
        return {
            loaded: false,
            selectedPrize: null,
            started: false,
            completed_at: null,
            gift: 0,
            form: {
                win: null,
            },
            logo: {
                src: '/wheel.png',
                width: 120,
                height: 120,
            },
            settings: [],
        };
    },

    computed: {
        preparedInterval() {
            const intervals = {
                1: '24 часа',
                7: '7 дней',
                30: '30 дней',
            };
            return intervals[this.interval] || '24 часа';
        },

        enabledToPlay() {
            if (this.isAdmin) return true;
            return !this.started && this.canPlay;
        },
    },

    mounted() {
        this.$nextTick(() => {
            this.settings = this.modelValue;

            if (this.actionData) {
                const data = this.actionData.data || [];
                this.form.win = data.length > 0 ? data[data.length - 1] : null;
                this.completed_at = this.actionData.completed_at || null;
            }

            this.loaded = true;
        });
    },

    methods: {
        // Стиль для частиц
        particleStyle(i) {
            const size = Math.random() * 8 + 4;
            const left = Math.random() * 100;
            const delay = Math.random() * 5;
            const duration = Math.random() * 10 + 10;
            return {
                width: `${size}px`,
                height: `${size}px`,
                left: `${left}%`,
                animationDelay: `${delay}s`,
                animationDuration: `${duration}s`,
            };
        },

        // Стиль для конфетти
        confettiStyle(i) {
            const colors = ['#ffd700', '#ff6b6b', '#4ecdc4', '#ff9ff3', '#54a0ff', '#5f27cd'];
            const color = colors[Math.floor(Math.random() * colors.length)];
            const left = Math.random() * 100;
            const delay = Math.random() * 2;
            const duration = Math.random() * 2 + 2;
            const rotation = Math.random() * 360;
            return {
                background: color,
                left: `${left}%`,
                animationDelay: `${delay}s`,
                animationDuration: `${duration}s`,
                transform: `rotate(${rotation}deg)`,
            };
        },

        // Форматирование даты
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        // Выбор приза для просмотра деталей
        selectPrize(index) {
            this.selectedPrize = this.settings[index];
        },

        // Запуск колеса
        launchWheel() {
            if (!this.enabledToPlay) return;

            this.gift = Math.floor(Math.random() * this.settings.length) + 1;
            this.started = true;
            this.$refs.wheel.spin();
        },

        // Завершение вращения
        done(result) {
            this.form.win = result;

            if (!this.isAdmin) {
                this.$emit('win', this.form);
            }

            this.$notify?.({
                title: 'Колесо фортуны',
                text: 'Поздравляем! Вы выиграли!',
                type: 'success',
            });

            this.completed_at = new Date();

            // Прокрутка к результату
            this.$nextTick(() => {
                const resultEl = document.querySelector('.result-section');
                if (resultEl) {
                    resultEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        },
    },
};
</script>

<style scoped>
.wheel-game-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.wheel-hero {
    position: relative;
    padding: 48px 24px 40px;
    background: linear-gradient(135deg, #9a1717 0%, #c0392b 50%, #e74c3c 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 215, 0, 0.1) 0%, transparent 50%);
}

.hero-particles {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.particle {
    position: absolute;
    bottom: -10px;
    background: rgba(255, 215, 0, 0.6);
    border-radius: 50%;
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% { transform: translateY(0) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.hero-icon {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    animation: wheelSpin 8s linear infinite;
}

@keyframes wheelSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.hero-sparkle {
    position: absolute;
    font-size: 1.2rem;
    animation: sparkle 2s ease-in-out infinite;
}

.sparkle-1 { top: -8px; right: -8px; animation-delay: 0s; }
.sparkle-2 { bottom: -8px; left: -8px; animation-delay: 0.7s; }
.sparkle-3 { top: 50%; right: -16px; animation-delay: 1.4s; }

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0.5); }
    50% { opacity: 1; transform: scale(1); }
}

.hero-title {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 8px;
    text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0 0 24px 0;
}

/* Статус */
.hero-status {
    display: flex;
    justify-content: center;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.status-badge.ready {
    background: rgba(25, 135, 84, 0.2);
    border: 1px solid rgba(25, 135, 84, 0.4);
    color: #d4edda;
}

.status-badge.disabled {
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid rgba(220, 53, 69, 0.4);
    color: #f8d7da;
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.wheel-content {
    padding: 20px 16px;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.prizes-section,
.wheel-section,
.result-section {
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ПРЕДПРОСМОТР ПРИЗОВ
   ========================================== */
.prizes-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
}

.prize-preview-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 8px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.prize-preview-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.15);
}

.prize-preview-emoji {
    font-size: 2rem;
    line-height: 1;
}

.prize-preview-name {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ВЫБРАННЫЙ ПРИЗ
   ========================================== */
.selected-prize-card {
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-primary);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 20px;
    cursor: pointer;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.selected-prize-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
}

.selected-prize-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
}

.selected-prize-info {
    flex: 1;
}

.selected-prize-title {
    margin: 0 0 4px 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.selected-prize-desc {
    margin: 0;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    line-height: 1.4;
}

.close-details-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.close-details-btn:hover {
    background: #dc3545;
    color: white;
}

.selected-prize-footer {
    padding: 12px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-top: 1px solid var(--bs-border-color);
}

.prize-mark {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.prize-mark i {
    color: var(--bs-primary);
}

.prize-mark strong {
    color: var(--bs-primary);
}

/* ==========================================
   КОЛЕСО
   ========================================== */
.wheel-wrapper {
    position: relative;
    padding: 40px 20px;
    background: linear-gradient(135deg, rgba(154, 23, 23, 0.05) 0%, rgba(231, 76, 60, 0.03) 100%);
    border: 1px solid rgba(154, 23, 23, 0.15);
    border-radius: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
    overflow: hidden;
}

.wheel-decoration {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 215, 0, 0.2) 0%, transparent 70%);
    pointer-events: none;
}

.wheel-decoration.decoration-1 {
    width: 200px;
    height: 200px;
    top: -50px;
    right: -50px;
}

.wheel-decoration.decoration-2 {
    width: 150px;
    height: 150px;
    bottom: -30px;
    left: -30px;
}

.wheel-container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 320px;
}

.wheel-container :deep(div#wheel svg) {
    font-size: 32px;
}

/* Кнопка запуска */
.spin-btn {
    position: relative;
    z-index: 1;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: 6px solid white;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow:
        0 8px 24px rgba(var(--bs-primary-rgb), 0.4),
        inset 0 -4px 8px rgba(0, 0, 0, 0.2);
    animation: pulseBtn 2s ease-in-out infinite;
}

@keyframes pulseBtn {
    0%, 100% {
        box-shadow:
            0 8px 24px rgba(var(--bs-primary-rgb), 0.4),
            inset 0 -4px 8px rgba(0, 0, 0, 0.2);
    }
    50% {
        box-shadow:
            0 12px 32px rgba(var(--bs-primary-rgb), 0.6),
            inset 0 -4px 8px rgba(0, 0, 0, 0.2);
    }
}

.spin-btn:hover:not(:disabled) {
    transform: scale(1.05);
}

.spin-btn:active:not(:disabled) {
    transform: scale(0.95);
}

.spin-btn:disabled {
    cursor: not-allowed;
    animation: none;
}

.spin-btn.is-spinning {
    animation: spinBtn 2s linear infinite;
}

@keyframes spinBtn {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.spin-btn-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.spin-btn-content i {
    font-size: 1.5rem;
}

.spin-btn-content span {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spin-disabled {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: 0.9rem;
}

/* ==========================================
   РЕЗУЛЬТАТ
   ========================================== */
.result-card {
    position: relative;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 20px;
    overflow: hidden;
}

.result-card.is-new {
    border-color: #ffc107;
    box-shadow: 0 8px 32px rgba(255, 193, 7, 0.2);
}

.result-confetti {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.confetti-piece {
    position: absolute;
    top: -10px;
    width: 10px;
    height: 10px;
    animation: confettiFall linear infinite;
}

@keyframes confettiFall {
    0% { transform: translateY(0) rotate(0deg); opacity: 1; }
    100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
}

.result-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
    border-bottom: 1px solid var(--bs-border-color);
}

.result-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.result-title-wrapper {
    flex: 1;
}

.result-title {
    margin: 0 0 4px 0;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

.result-subtitle {
    margin: 0;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.result-body {
    padding: 20px;
}

.result-prize-display {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-radius: 14px;
    margin-bottom: 16px;
}

.result-prize-emoji {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    flex-shrink: 0;
}

.result-prize-info {
    flex: 1;
}

.result-prize-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.result-prize-desc {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    line-height: 1.4;
}

/* Информация о получении */
.result-claim-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.claim-method,
.claim-deadline {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
}

.claim-method i,
.claim-deadline i {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.claim-content {
    flex: 1;
}

.claim-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.claim-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.deadline-date {
    display: block;
    font-size: 0.75rem;
    font-weight: 400;
    color: var(--bs-secondary-color);
    margin-top: 2px;
}

.claim-warning {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: rgba(255, 193, 7, 0.08);
    border: 1px solid rgba(255, 193, 7, 0.2);
    border-radius: 12px;
    font-size: 0.85rem;
    color: #856404;
}

.claim-warning i {
    color: #ffc107;
    flex-shrink: 0;
}

:root[data-bs-theme="dark"] .claim-warning {
    color: #ffda6a;
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.fade-up-enter-active,
.fade-up-leave-active {
    transition: all 0.4s ease;
}

.fade-up-enter-from,
.fade-up-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.result-popup-enter-active {
    animation: resultPopup 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes resultPopup {
    0% { opacity: 0; transform: scale(0.8); }
    50% { transform: scale(1.05); }
    100% { opacity: 1; transform: scale(1); }
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.6rem;
    }

    .hero-icon {
        width: 72px;
        height: 72px;
        font-size: 2rem;
    }

    .spin-btn {
        width: 120px;
        height: 120px;
    }

    .spin-btn-content i {
        font-size: 1.2rem;
    }

    .spin-btn-content span {
        font-size: 0.75rem;
    }

    .prizes-preview-grid {
        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    }

    .prize-preview-emoji {
        font-size: 1.6rem;
    }

    .result-prize-emoji {
        width: 56px;
        height: 56px;
        font-size: 1.6rem;
    }
}
</style>
