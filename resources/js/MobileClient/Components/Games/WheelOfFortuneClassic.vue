<template>
    <div class="wheel-page">

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА -->
        <!-- ========================================== -->
        <div v-if="!wheelDataLoaded" class="loading-state">
            <div class="loading-content">
                <div class="loading-icon">
                    <i class="fa-solid fa-dharmachakra"></i>
                </div>
                <h5 class="loading-title">Подготавливаем колесо...</h5>
                <p class="loading-text">
                    {{ loadError ? 'Ошибка загрузки. Попробуйте обновить страницу.' : 'Загружаем призы и настройки' }}
                </p>
                <div v-if="!loadError" class="loading-spinner"></div>
                <button v-else class="retry-btn" @click="reloadPage">
                    <i class="fa-solid fa-rotate-right me-2"></i>
                    Обновить страницу
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ОСНОВНОЙ КОНТЕНТ -->
        <!-- ========================================== -->
        <template v-else>

            <!-- HERO СЕКЦИЯ -->
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
                    <p class="hero-subtitle">Испытай удачу и выиграй призы!</p>

                    <!-- Счётчик попыток в hero -->
                    <div v-if="action" class="hero-attempts">
                        <div class="attempts-info">
                            <span class="attempts-current">{{ action.current_attempts || 0 }}</span>
                            <span class="attempts-divider">/</span>
                            <span class="attempts-max">{{ action.max_attempts || 1 }}</span>
                        </div>
                        <div class="attempts-label">
                            {{ canPlay ? 'попыток осталось' : 'попыток использовано' }}
                        </div>
                        <div class="attempts-bar">
                            <div
                                class="attempts-fill"
                                :class="{ 'is-empty': !canPlay }"
                                :style="{ width: attemptsPercent + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wheel-content">

                <!-- Кнопка редактора (для админов) -->
                <button
                    type="button"
                    class="editor-toggle"
                    @click="tab = tab === 'main' ? 'config' : 'main'"
                >
                    <i :class="tab === 'config' ? 'fa-solid fa-dharmachakra' : 'fa-solid fa-pen-to-square'"></i>
                    <span>{{ tab === 'config' ? 'Вернуться к игре' : 'Редактор скрипта' }}</span>
                </button>

                <!-- ========================================== -->
                <!-- ВКЛАДКА: ГЛАВНАЯ -->
                <!-- ========================================== -->
                <template v-if="tab === 'main'">

                    <!-- ШАГ 0: Предварительные формы -->
                    <template v-if="step === 0">
                        <div class="pre-form-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                </div>
                                <div>
                                    <h6 class="section-title">Перед игрой</h6>
                                    <p class="section-subtitle">Заполните форму, чтобы получить попытку</p>
                                </div>
                            </div>

                            <div class="pre-form-card">
                                <WheelMarketPlaceForm v-if="before_script === 'marketplace_1'" @callback="formCallback" />
                                <WheelAuthForm v-if="before_script === 'auth_1'" @callback="formCallback" />
                                <WheelReviewForm v-if="before_script === 'review_1'" @callback="formCallback" />
                            </div>
                        </div>
                    </template>

                    <!-- ШАГ 1: Игра -->
                    <template v-if="step === 1">

                        <!-- Правила -->
                        <div v-if="rules" class="rules-section">
                            <button class="rules-toggle" @click="showRules = !showRules">
                                <div class="rules-toggle-content">
                                    <div class="rules-icon">
                                        <i class="fa-solid fa-scroll"></i>
                                    </div>
                                    <div class="rules-info">
                                        <span class="rules-title">Правила игры</span>
                                        <span class="rules-hint">Нажмите, чтобы прочитать</span>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-down rules-arrow" :class="{ 'rotated': showRules }"></i>
                            </button>
                            <transition name="slide-down">
                                <div v-if="showRules" class="rules-content">
                                    <div class="rules-text" v-text="rules"></div>
                                </div>
                            </transition>
                        </div>

                        <!-- Статус попыток -->
                        <div class="attempts-section">
                            <div v-if="canPlay" class="attempts-card ready">
                                <div class="attempts-card-icon">
                                    <i class="fa-solid fa-ticket"></i>
                                </div>
                                <div class="attempts-card-info">
                                    <div class="attempts-card-title">У вас есть попытки!</div>
                                    <div class="attempts-card-subtitle">
                                        Осталось <strong>{{ action.max_attempts - action.current_attempts }}</strong>
                                        {{ pluralize(action.max_attempts - action.current_attempts, 'попытка', 'попытки', 'попыток') }}
                                    </div>
                                </div>
                                <div class="attempts-card-badge">
                                    {{ action.max_attempts - action.current_attempts }}
                                </div>
                            </div>
                            <div v-else class="attempts-card exhausted">
                                <div class="attempts-card-icon">
                                    <i class="fa-solid fa-ban"></i>
                                </div>
                                <div class="attempts-card-info">
                                    <div class="attempts-card-title">Попытки закончились</div>
                                    <div class="attempts-card-subtitle">Вы израсходовали все попытки</div>
                                </div>
                            </div>
                        </div>

                        <!-- Последний результат -->
                        <div v-if="sortedActionData.length > 0" class="last-result-section">
                            <div class="section-header">
                                <div class="section-icon result-icon">
                                    <i class="fa-solid fa-trophy"></i>
                                </div>
                                <div>
                                    <h6 class="section-title">Последний результат</h6>
                                    <p class="section-subtitle">Ваш последний розыгрыш</p>
                                </div>
                            </div>

                            <div class="result-card">
                                <div class="result-header">
                                    <div class="result-prize">
                                        <i class="fa-solid fa-gift"></i>
                                        <span>{{ sortedActionData[0].description || 'Приз' }}</span>
                                    </div>
                                    <div class="result-date">
                                        <i class="fa-solid fa-calendar"></i>
                                        <span>{{ formatDate(sortedActionData[0].played_at) }}</span>
                                    </div>
                                </div>
                                <div class="result-details">
                                    <div class="result-detail">
                                        <i class="fa-solid fa-user"></i>
                                        <span>{{ sortedActionData[0].name || 'Не указано' }}</span>
                                    </div>
                                    <div class="result-detail">
                                        <i class="fa-solid fa-phone"></i>
                                        <span>{{ sortedActionData[0].phone || 'Не указано' }}</span>
                                    </div>
                                </div>
                                <div v-if="script_data?.callback_message" class="result-hint">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span v-text="script_data.callback_message"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Список призов -->
                        <div class="prizes-section">
                            <button class="prizes-toggle" @click="showPrizes = !showPrizes">
                                <div class="prizes-toggle-content">
                                    <div class="prizes-icon">
                                        <i class="fa-solid fa-gifts"></i>
                                    </div>
                                    <div class="prizes-info">
                                        <span class="prizes-title">Список призов</span>
                                        <span class="prizes-hint">{{ items.length }} {{ pluralize(items.length, 'приз', 'приза', 'призов') }}</span>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-down prizes-arrow" :class="{ 'rotated': showPrizes }"></i>
                            </button>
                            <transition name="slide-down">
                                <div v-if="showPrizes" class="prizes-grid">
                                    <div
                                        v-for="(item, index) in items"
                                        :key="item.id || index"
                                        class="prize-card"
                                        :class="{ 'selected': selectedPrize?.id === item.id }"
                                        @click="selectPrize(index)"
                                    >
                                        <div class="prize-emoji">{{ item.value }}</div>
                                        <div class="prize-name">{{ item.description }}</div>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <!-- КОЛЕСО -->
                        <div class="wheel-section">
                            <div class="wheel-wrapper">
                                <div class="wheel-decoration decoration-1"></div>
                                <div class="wheel-decoration decoration-2"></div>

                                <div class="wheel-container">
                                    <FortuneWheel
                                        :gift="gift"
                                        :imgParams="logo"
                                        @done="done"
                                        ref="wheel"
                                        v-model="items"
                                    />
                                </div>

                                <!-- Кнопка запуска -->
                                <button
                                    v-if="canPlay"
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
                                    <span>Нет доступных попыток</span>
                                </div>
                            </div>
                        </div>

                        <!-- Результат выигрыша -->
                        <transition name="win-popup">
                            <div v-if="winForm.win" class="win-section">
                                <div class="win-card">
                                    <div class="win-confetti">
                                        <span v-for="i in 30" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                                    </div>
                                    <div class="win-icon">
                                        <i class="fa-solid fa-trophy"></i>
                                    </div>
                                    <h4 class="win-title">Поздравляем!</h4>
                                    <p class="win-subtitle">Вы выиграли приз</p>
                                    <div class="win-prize-name">{{ winForm.win.description || 'Приз' }}</div>
                                    <div class="win-prize-id">#{{ winForm.win.id }}</div>
                                </div>
                            </div>
                        </transition>

                        <!-- История розыгрышей -->
                        <div v-if="sortedActionData.length > 0" class="history-section">
                            <div class="section-header">
                                <div class="section-icon history-icon">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </div>
                                <div>
                                    <h6 class="section-title">История розыгрышей</h6>
                                    <p class="section-subtitle">
                                        {{ sortedActionData.length }}
                                        {{ pluralize(sortedActionData.length, 'розыгрыш', 'розыгрыша', 'розыгрышей') }}
                                    </p>
                                </div>
                            </div>

                            <div class="history-list">
                                <div
                                    v-for="item in sortedActionData"
                                    :key="item.id"
                                    class="history-card"
                                >
                                    <div class="history-date">
                                        <i class="fa-solid fa-calendar"></i>
                                        <span>{{ formatDate(item.played_at) }}</span>
                                    </div>
                                    <div class="history-prize">
                                        <i class="fa-solid fa-gift"></i>
                                        <span>{{ item.description || 'Приз' }}</span>
                                    </div>
                                    <div class="history-details">
                                        <div class="history-detail">
                                            <i class="fa-solid fa-user"></i>
                                            <span>{{ item.name || 'Не указано' }}</span>
                                        </div>
                                        <div class="history-detail">
                                            <i class="fa-solid fa-phone"></i>
                                            <span>{{ item.phone || 'Не указано' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </template>
                </template>

                <!-- ========================================== -->
                <!-- ВКЛАДКА: РЕДАКТОР -->
                <!-- ========================================== -->
                <template v-if="tab === 'config'">
                    <div class="config-section">
                        <div class="section-header">
                            <div class="section-icon config-icon">
                                <i class="fa-solid fa-code"></i>
                            </div>
                            <div>
                                <h6 class="section-title">Редактор скрипта</h6>
                                <p class="section-subtitle">Настройка логики игры</p>
                            </div>
                        </div>
                        <WheelCustomScriptEditor v-if="script_data" v-model="script_data" />
                    </div>
                </template>

            </div>
        </template>
    </div>
</template>

<script>
import { FortuneWheel } from 'vue3-fortune-wheel';
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';
import WheelCustomScriptEditor from '@/MobileClient/Components/Games/WheelOfFortune/WheelScriptEditor.vue';
import WheelMarketPlaceForm from '@/MobileClient/Components/Games/WheelOfFortune/WheelMarketPlaceForm.vue';
import WheelReviewForm from '@/MobileClient/Components/Games/WheelOfFortune/WheelReviewForm.vue';
import WheelAuthForm from '@/MobileClient/Components/Games/WheelOfFortune/WheelAuthForm.vue';

export default {
    name: "WheelOfFortune",

    components: {
        FortuneWheel,
        WheelCustomScriptEditor,
        WheelMarketPlaceForm,
        WheelReviewForm,
        WheelAuthForm,
    },

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            tab: 'main',
            step: 1,
            started: false,
            afterScript: null,
            beforeScript: null,
            scriptData: null,
            rules: null,
            action: null,
            selectedPrize: null,
            showPrizes: false,
            showRules: false,
            wheelDataLoaded: false,
            loadError: false,
            winForm: { win: null },
            gift: 0,
            logo: {
                src: '/wheel.png',
                width: 120,
                height: 120,
            },
            smiles: ['💙', '💜', '💚', '💰', '👑', '🍩', '⚽', '🦖', '🌺', '🌷', '🐾', '⏳', '💊', '💡', '🚀', '⭐', '💎', '☘', '🏆', '🎁'],
            items: [],
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        sortedActionData() {
            if (!this.action?.data?.length) return [];
            return [...this.action.data].sort((a, b) => new Date(b.played_at) - new Date(a.played_at));
        },

        canPlay() {
            if (!this.action) return false;
            return this.action.current_attempts < this.action.max_attempts;
        },

        attemptsPercent() {
            if (!this.action) return 0;
            const max = this.action.max_attempts || 1;
            const current = this.action.current_attempts || 0;
            return (current / max) * 100;
        },
    },

    async mounted() {
        try {
            await this.loadServiceData();
            await this.prepareUserData();
            this.step = this.beforeScript != null ? 0 : 1;
            this.wheelDataLoaded = true;
        } catch (error) {
            console.error('Ошибка загрузки данных:', error);
            this.loadError = true;
            this.$notify?.({
                title: 'Колесо фортуны',
                text: 'Ошибка загрузки данных',
                type: 'error',
            });
        }
    },

    methods: {
        reloadPage() {
            window.location.reload();
        },

        // Стиль для частиц в hero
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

        // Стиль для конфетти при выигрыше
        confettiStyle(i) {
            const colors = ['#ff6b6b', '#feca57', '#48dbfb', '#ff9ff3', '#54a0ff', '#5f27cd'];
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

        shuffle(array) {
            let currentIndex = array.length;
            while (currentIndex > 0) {
                const randomIndex = Math.floor(Math.random() * currentIndex);
                currentIndex--;
                [array[currentIndex], array[randomIndex]] = [array[randomIndex], array[currentIndex]];
            }
            return array;
        },

        launchWheel() {
            if (!this.canPlay) {
                this.$notify?.({
                    title: 'Колесо фортуны',
                    text: 'Вы израсходовали все попытки!',
                    type: 'error',
                });
                return;
            }
            this.gift = Math.floor(Math.random() * this.items.length) + 1;
            this.started = true;
            this.$refs.wheel.spin();
        },

        formCallback(e) {
            this.step = 1;
            this.winForm = { ...this.winForm, ...e };
        },

        done(r) {
            this.winForm.win = r;
            this.$notify?.({
                title: 'Колесо фортуны',
                text: 'Поздравляем! Вы выиграли!',
                type: 'success',
            });
            this.action.current_attempts++;
            this.started = false;

            this.$nextTick(() => {
                const resultEl = document.querySelector('.win-section');
                if (resultEl) {
                    resultEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                setTimeout(() => this.submit(), 2500);
            });
        },

        async prepareUserData() {
            const response = await this.basketStore.wheelOfFortuneCustomPrepare();
            this.action = response.action;
        },

        selectPrize(index) {
            this.selectedPrize = this.items[index];
        },

        async loadServiceData() {
            const response = await this.basketStore.wheelOfFortuneCustomLoadData();
            this.scriptData = response;
            this.rules = response.rules;
            this.afterScript = response.after_script || null;
            this.beforeScript = response.before_script || null;

            const wheels = this.shuffle(response.wheels);
            const getRandomInt = (min, max) => Math.floor(Math.random() * (Math.floor(max) - Math.ceil(min) + 1)) + Math.ceil(min);

            this.items = [];
            const tmpValues = [];

            wheels.forEach((item, index) => {
                let value;
                let success = false;
                while (!success) {
                    value = this.smiles[getRandomInt(0, this.smiles.length - 1)];
                    if (!tmpValues.includes(value)) {
                        tmpValues.push(value);
                        success = true;
                    }
                }
                this.items.push({
                    id: index + 1,
                    value: item.smile ?? value,
                    bgColor: item.bg_color ?? (index % 2 === 0 ? '#9a1717' : '#ffffff'),
                    color: '#9a1717',
                    description: item.value,
                });
            });
        },

        async submit() {
            const data = new FormData();
            Object.keys(this.winForm).forEach(key => {
                const item = this.winForm[key] ?? '';
                if (typeof item === 'object' && item !== null) {
                    data.append(key, JSON.stringify(item));
                } else {
                    data.append(key, item);
                }
            });
            data.set('file_1', this.winForm.file_1);
            data.set('file_2', this.winForm.file_2);
            data.append('description', this.winForm.win?.description || 'Без описания');

            try {
                await this.basketStore.wheelOfFortuneCustomWin({ winForm: data });

                this.$notify?.({
                    title: 'Колесо фортуны',
                    text: 'Вы выиграли приз ' + (this.winForm.win?.description || 'Что-то интересное...'),
                    type: 'success',
                });

                this.winForm.win = null;
                await this.prepareUserData();

            } catch (error) {
                console.error('Ошибка сохранения результата:', error);
                this.$notify?.({
                    title: 'Колесо фортуны',
                    text: 'Ошибка сохранения результата',
                    type: 'error',
                });
            }
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
    },
};
</script>

<style scoped>
.wheel-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   ЗАГРУЗКА
   ========================================== */
.loading-state {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.loading-content {
    text-align: center;
    max-width: 320px;
}

.loading-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    animation: spin 3s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-title {
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.loading-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 24px;
    line-height: 1.5;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--bs-border-color);
    border-top-color: var(--bs-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto;
}

.retry-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    background: var(--bs-primary);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.retry-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
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
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100vh) rotate(360deg);
        opacity: 0;
    }
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
    letter-spacing: -0.5px;
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0 0 24px 0;
}

/* Счётчик попыток в hero */
.hero-attempts {
    max-width: 280px;
    margin: 0 auto;
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
}

.attempts-info {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 4px;
    margin-bottom: 4px;
}

.attempts-current {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
}

.attempts-divider {
    font-size: 1.2rem;
    opacity: 0.7;
}

.attempts-max {
    font-size: 1.2rem;
    opacity: 0.9;
}

.attempts-label {
    font-size: 0.75rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.attempts-bar {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
    overflow: hidden;
}

.attempts-fill {
    height: 100%;
    background: linear-gradient(90deg, #ffd700 0%, #ffed4e 100%);
    border-radius: 3px;
    transition: width 0.5s ease;
    box-shadow: 0 0 8px rgba(255, 215, 0, 0.5);
}

.attempts-fill.is-empty {
    background: rgba(255, 255, 255, 0.5);
    box-shadow: none;
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.wheel-content {
    padding: 20px 16px;
}

.editor-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 20px;
}

.editor-toggle:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.editor-toggle i {
    color: var(--bs-primary);
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
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

.section-icon.result-icon {
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.section-icon.history-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
}

.section-icon.config-icon {
    background: linear-gradient(135deg, #2c3e50 0%, #4a6278 100%);
    box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
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
   ПРЕДВАРИТЕЛЬНАЯ ФОРМА
   ========================================== */
.pre-form-section,
.rules-section,
.attempts-section,
.last-result-section,
.prizes-section,
.wheel-section,
.win-section,
.history-section,
.config-section {
    margin-bottom: 24px;
}

.pre-form-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
}

/* ==========================================
   ПРАВИЛА
   ========================================== */
.rules-toggle,
.prizes-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.rules-toggle:hover,
.prizes-toggle:hover {
    border-color: var(--bs-primary);
}

.rules-toggle-content,
.prizes-toggle-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rules-icon,
.prizes-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.rules-info,
.prizes-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.rules-title,
.prizes-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.rules-hint,
.prizes-hint {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.rules-arrow,
.prizes-arrow {
    color: var(--bs-secondary-color);
    transition: transform 0.3s ease;
}

.rules-arrow.rotated,
.prizes-arrow.rotated {
    transform: rotate(180deg);
}

.rules-content {
    margin-top: 12px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
}

.rules-text {
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--bs-body-color);
    white-space: pre-wrap;
}

/* ==========================================
   СТАТУС ПОПЫТОК
   ========================================== */
.attempts-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-radius: 16px;
    border: 1px solid;
}

.attempts-card.ready {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.08) 0%, rgba(32, 201, 151, 0.03) 100%);
    border-color: rgba(25, 135, 84, 0.2);
}

.attempts-card.exhausted {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.08) 0%, rgba(200, 35, 51, 0.03) 100%);
    border-color: rgba(220, 53, 69, 0.2);
}

.attempts-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.attempts-card.ready .attempts-card-icon {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
}

.attempts-card.exhausted .attempts-card-icon {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.attempts-card-info {
    flex: 1;
    min-width: 0;
}

.attempts-card-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.attempts-card-subtitle {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.attempts-card-subtitle strong {
    color: var(--bs-primary);
}

.attempts-card-badge {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

/* ==========================================
   РЕЗУЛЬТАТ
   ========================================== */
.result-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
}

.result-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
    border-bottom: 1px solid var(--bs-border-color);
}

.result-prize {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.result-prize i {
    color: #ffc107;
}

.result-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.result-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 14px 16px;
}

.result-detail {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.result-detail i {
    color: var(--bs-primary);
    width: 16px;
    text-align: center;
}

.result-hint {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-top: 1px solid var(--bs-border-color);
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    font-style: italic;
}

.result-hint i {
    color: var(--bs-primary);
    margin-top: 2px;
    flex-shrink: 0;
}

/* ==========================================
   ПРИЗЫ
   ========================================== */
.prizes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    margin-top: 12px;
}

.prize-card {
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
    text-align: center;
}

.prize-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.1);
}

.prize-card.selected {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
}

.prize-emoji {
    font-size: 2rem;
    line-height: 1;
}

.prize-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-body-color);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ==========================================
   КОЛЕСО
   ========================================== */
.wheel-section {
    margin-bottom: 32px;
}

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
    font-size: 26px;
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
   ВЫИГРЫШ
   ========================================== */
.win-section {
    position: relative;
    margin-bottom: 32px;
}

.win-card {
    position: relative;
    padding: 40px 24px;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 50%, #ff5722 100%);
    border-radius: 24px;
    text-align: center;
    color: white;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(255, 152, 0, 0.4);
}

.win-confetti {
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
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(400px) rotate(720deg);
        opacity: 0;
    }
}

.win-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
    animation: trophyBounce 1s ease-in-out infinite;
}

@keyframes trophyBounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.win-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 4px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.win-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin-bottom: 16px;
}

.win-prize-name {
    font-size: 1.3rem;
    font-weight: 700;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 14px;
    display: inline-block;
    margin-bottom: 8px;
}

.win-prize-id {
    font-size: 0.85rem;
    opacity: 0.8;
}

/* Анимация появления выигрыша */
.win-popup-enter-active {
    animation: winPopup 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.win-popup-leave-active {
    animation: winPopup 0.4s cubic-bezier(0.4, 0, 0.2, 1) reverse;
}

@keyframes winPopup {
    0% {
        opacity: 0;
        transform: scale(0.5);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

/* ==========================================
   ИСТОРИЯ
   ========================================== */
.history-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.history-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 14px;
    transition: all 0.2s ease;
}

.history-card:hover {
    border-color: rgba(var(--bs-primary-rgb), 0.3);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.08);
}

.history-date {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    margin-bottom: 8px;
}

.history-date i {
    color: var(--bs-primary);
}

.history-prize {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 10px;
}

.history-prize i {
    color: #ffc107;
}

.history-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.history-detail {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.history-detail i {
    color: var(--bs-primary);
    width: 14px;
    text-align: center;
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 1000px;
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

    .attempts-current {
        font-size: 1.6rem;
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

    .win-title {
        font-size: 1.5rem;
    }

    .win-prize-name {
        font-size: 1.1rem;
    }

    .prizes-grid {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    }

    .prize-emoji {
        font-size: 1.6rem;
    }

    .history-details {
        grid-template-columns: 1fr;
    }
}
</style>
