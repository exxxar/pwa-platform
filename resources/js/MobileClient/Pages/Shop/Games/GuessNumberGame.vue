<template>
    <div class="guess-number-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="game-hero">
            <div class="hero-background"></div>
            <div class="hero-particles">
                <span v-for="i in 20" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">🔢</div>
                    <div class="hero-sparkle sparkle-2">🎯</div>
                </div>
                <h1 class="hero-title">Угадай число</h1>
                <p class="hero-subtitle">Угадай число от 1 до 100 за минимум попыток!</p>

                <div class="hero-stats">
                    <div class="stat-block">
                        <div class="stat-icon fire-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ Math.round(balance) }}₽</div>
                            <div class="stat-label">Кэшбэк</div>
                        </div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-block">
                        <div class="stat-icon trophy-icon">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ wins }}</div>
                            <div class="stat-label">Побед</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="game-content">

            <!-- ========================================== -->
            <!-- РЕЖИМ ИГРЫ -->
            <!-- ========================================== -->
            <div class="mode-section">
                <div class="mode-tabs">
                    <button
                        class="mode-tab"
                        :class="{ active: gameMode === 'classic' }"
                        @click="selectMode('classic')"
                    >
                        <i class="fa-solid fa-dice"></i>
                        <span>Классика</span>
                        <small>1-100 · 10 попыток</small>
                    </button>
                    <button
                        class="mode-tab"
                        :class="{ active: gameMode === 'jackpot' }"
                        @click="selectMode('jackpot')"
                    >
                        <i class="fa-solid fa-crown"></i>
                        <span>Джекпот</span>
                        <small>1-1000 · 15 попыток</small>
                    </button>
                    <button
                        class="mode-tab"
                        :class="{ active: gameMode === 'challenge' }"
                        @click="selectMode('challenge')"
                    >
                        <i class="fa-solid fa-fire"></i>
                        <span>Вызов</span>
                        <small>1-100 · 3 попытки</small>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ИГРОВОЕ ПОЛЕ -->
            <!-- ========================================== -->
            <div v-if="!gameStarted" class="start-section">
                <div class="game-preview">
                    <div class="preview-icon" :class="gameMode">
                        <i :class="modeIcon"></i>
                    </div>
                    <h3 class="preview-title">{{ modeTitle }}</h3>
                    <p class="preview-desc">{{ modeDesc }}</p>

                    <div class="preview-rewards">
                        <div class="reward-row" v-for="(reward, i) in modeRewards" :key="i">
                            <div class="reward-attempts">
                                <i class="fa-solid fa-hand-pointer"></i>
                                <span>{{ reward.attempts }}</span>
                            </div>
                            <div class="reward-arrow">→</div>
                            <div class="reward-value">
                                <i class="fa-solid fa-coins"></i>
                                <span>{{ reward.value }}</span>
                            </div>
                        </div>
                    </div>

                    <button
                        class="start-btn"
                        @click="startGame"
                        :disabled="balance < gameCost || isProcessing"
                    >
                        <i :class="isProcessing ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-play'"></i>
                        <span>Начать игру (−{{ gameCost }}₽ кэшбэка)</span>
                    </button>
                    <p v-if="balance < modeCost" class="insufficient">Недостаточно бонусов</p>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- АКТИВНАЯ ИГРА -->
            <!-- ========================================== -->
            <div v-else class="active-game">

                <!-- Индикатор попыток -->
                <div class="attempts-bar">
                    <div class="attempts-info">
                        <span>Попыток:</span>
                        <strong>{{ attempts }} / {{ maxAttempts }}</strong>
                    </div>
                    <div class="attempts-dots">
                        <span
                            v-for="i in maxAttempts"
                            :key="i"
                            class="attempt-dot"
                            :class="{
                                'used': i <= attempts,
                                'current': i === attempts + 1 && !gameOver
                            }"
                        ></span>
                    </div>
                </div>

                <!-- Термометр горячо/холодно -->
                <div v-if="attempts > 0 && !gameOver" class="temperature-meter">
                    <div class="temp-label">
                        <i class="fa-solid fa-snowflake"></i>
                        <span>Холодно</span>
                    </div>
                    <div class="temp-bar">
                        <div class="temp-indicator" :style="{ left: temperature + '%' }"></div>
                    </div>
                    <div class="temp-label">
                        <span>Горячо</span>
                        <i class="fa-solid fa-fire"></i>
                    </div>
                </div>

                <!-- История попыток -->
                <div class="guess-history" v-if="guesses.length > 0">
                    <transition-group name="history-slide">
                        <div
                            v-for="(guess, i) in guesses"
                            :key="i"
                            class="guess-item"
                            :class="'hint-' + guess.hint"
                        >
                            <div class="guess-number">{{ guess.value }}</div>
                            <div class="guess-hint">
                                <i :class="guess.icon"></i>
                                <span>{{ guess.text }}</span>
                            </div>
                            <div class="guess-distance" v-if="guess.distance !== null">
                                {{ guess.distance }}
                            </div>
                        </div>
                    </transition-group>
                </div>

                <!-- Панель ввода -->
                <div v-if="!gameOver" class="input-panel">
                    <div class="range-display">
                        <div class="range-value">{{ rangeMin }}</div>
                        <div class="range-line">
                            <div class="range-marker" :style="{ left: markerPosition + '%' }"></div>
                        </div>
                        <div class="range-value">{{ rangeMax }}</div>
                    </div>

                    <div class="number-input-wrapper">
                        <input
                            type="number"
                            v-model.number="currentGuess"
                            :min="rangeMin"
                            :max="rangeMax"
                            class="number-input"
                            placeholder="?"
                            @keyup.enter="makeGuess"
                        />
                    </div>

                    <div class="quick-numbers">
                        <button
                            v-for="n in quickNumbers"
                            :key="n"
                            class="quick-btn"
                            :class="{ disabled: isNumberImpossible(n) }"
                            @click="currentGuess = n"
                        >
                            {{ n }}
                        </button>
                    </div>

                    <button
                        class="guess-btn"
                        @click="makeGuess"
                        :disabled="!canGuess"
                    >
                        <i class="fa-solid fa-check"></i>
                        <span>Угадать!</span>
                    </button>

                    <button class="give-up-btn" @click="giveUp">
                        <i class="fa-solid fa-flag"></i>
                        <span>Сдаться</span>
                    </button>
                </div>

                <!-- Финальный экран -->
                <div v-if="gameOver" class="game-result" :class="gameWon ? 'won' : 'lost'">
                    <div class="result-icon">
                        <i :class="gameWon ? 'fa-solid fa-trophy' : 'fa-solid fa-heart-crack'"></i>
                    </div>
                    <h3 class="result-title">{{ gameWon ? 'Победа!' : 'Не угадал' }}</h3>
                    <p class="result-number" v-if="!gameWon">Загадано: <strong>{{ secretNumber }}</strong></p>
                    <div v-if="gameWon" class="result-reward">
                        <i class="fa-solid fa-gift"></i>
                        <span>+{{ currentReward }} бонусов</span>
                    </div>
                    <div class="result-stats">
                        <div class="result-stat">
                            <div class="result-stat-value">{{ attempts }}</div>
                            <div class="result-stat-label">Попыток</div>
                        </div>
                        <div class="result-stat-divider"></div>
                        <div class="result-stat">
                            <div class="result-stat-value">{{ currentStreak }}</div>
                            <div class="result-stat-label">Серия побед</div>
                        </div>
                    </div>
                    <button class="play-again-btn" @click="resetGame">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Сыграть ещё</span>
                    </button>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- ИСТОРИЯ ИГР -->
            <!-- ========================================== -->
            <div class="history-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h6 class="section-title">История игр</h6>
                        <p class="section-subtitle">Ваши последние результаты</p>
                    </div>
                </div>

                <div v-if="gamesHistory.length === 0" class="empty-history">
                    <div class="empty-icon">🎲</div>
                    <p>Пока нет сыгранных игр</p>
                    <p class="empty-hint">Начните первую игру!</p>
                </div>

                <div v-else class="history-list">
                    <div
                        v-for="game in gamesHistory"
                        :key="game.id"
                        class="history-item"
                        :class="{ won: game.won }"
                    >
                        <div class="history-date">
                            <div class="date-day">{{ formatDate(game.date).day }}</div>
                            <div class="date-month">{{ formatDate(game.date).month }}</div>
                        </div>
                        <div class="history-mode" :class="'mode-' + game.mode">
                            <i :class="modeIcons[game.mode]"></i>
                        </div>
                        <div class="history-info">
                            <div class="history-title">
                                {{ modeTitles[game.mode] }}
                            </div>
                            <div class="history-value">
                                {{ game.attempts }} попыток · Число: {{ game.secret }}
                            </div>
                        </div>
                        <div class="history-result" :class="game.won ? 'win' : 'loss'">
                            <i :class="game.won ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
                            <span>{{ game.won ? '+' + game.reward : '−' + game.cost }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- АДМИН-ПАНЕЛЬ -->
            <!-- ========================================== -->
            <div v-if="isAdmin" class="admin-section">
                <button class="admin-toggle" @click="showAdmin = !showAdmin">
                    <div class="admin-toggle-content">
                        <div class="admin-icon">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <div class="admin-info">
                            <span class="admin-title">Панель администратора</span>
                            <span class="admin-hint">Управление игрой</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down admin-arrow" :class="{ 'rotated': showAdmin }"></i>
                </button>
                <transition name="slide-down">
                    <div v-if="showAdmin" class="admin-content">
                        <div class="admin-grid">
                            <button class="admin-btn" @click="adminAddBalance(100)">
                                <i class="fa-solid fa-coins"></i>
                                <span>+100 бонусов</span>
                            </button>
                            <button class="admin-btn" @click="adminResetStats">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Сброс статистики</span>
                            </button>
                            <button class="admin-btn" @click="adminWinGame">
                                <i class="fa-solid fa-trophy"></i>
                                <span>Победа в игре</span>
                            </button>
                            <button class="admin-btn" @click="adminClearHistory">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Очистить историю</span>
                            </button>
                        </div>
                    </div>
                </transition>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА РЕЗУЛЬТАТА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showResultModal" class="modal-overlay" @click.self="closeResultModal">
                <div class="modal-container result-modal">
                    <div class="result-confetti" v-if="gameWon">
                        <span v-for="i in 50" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                    </div>
                    <div class="result-content">
                        <div class="result-icon-large" :class="gameWon ? 'won' : 'lost'">
                            <i :class="gameWon ? 'fa-solid fa-trophy' : 'fa-solid fa-heart-crack'"></i>
                        </div>
                        <h2 class="result-title-large">{{ gameWon ? 'Победа!' : 'Не повезло' }}</h2>
                        <p class="result-desc">
                            {{ gameWon
                            ? `Угадал за ${attempts} ${pluralize(attempts, 'попытку', 'попытки', 'попыток')}`
                            : `Загаданное число: ${secretNumber}`
                            }}
                        </p>
                        <div v-if="gameWon" class="reward-display">
                            <div class="reward-amount">+{{ currentReward }}</div>
                            <div class="reward-label">бонусов</div>
                        </div>
                        <button class="result-btn" @click="closeResultModal">
                            <i class="fa-solid fa-check"></i>
                            <span>Отлично!</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

export default {
    name: "GuessNumberGame",

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            // Состояние с сервера
            balance: 0,
            gameCost: 100,
            stats: { wins: 0, current_streak: 0, best_streak: 0 },
            gamesHistory: [],
            modes: {},

            // Режимы
            gameMode: 'classic',

            // Активная игра
            activeGame: null,
            gameToken: null,
            gameStarted: false,
            gameOver: false,
            gameWon: false,

            // Игровое состояние
            currentGuess: null,
            attempts: 0,
            maxAttempts: 10,
            rangeMin: 1,
            rangeMax: 100,
            guesses: [],
            secretNumber: null, // Раскрывается ТОЛЬКО в конце игры!
            currentReward: 0,

            // UI
            isProcessing: false,
            showResultModal: false,

            modeIcons: {
                classic: 'fa-solid fa-dice',
                jackpot: 'fa-solid fa-crown',
                challenge: 'fa-solid fa-fire',
            },

            modeTitles: {
                classic: 'Классика',
                jackpot: 'Джекпот',
                challenge: 'Вызов',
            },
        };
    },

    computed: {
        isAdmin() {
            const user = window.TenantUser;
            return user?.role === 'admin' || user?.is_admin === true;
        },

        modeConfig() {
            return this.modes[this.gameMode] || this.modes.classic || {};
        },

        modeCost() {
            return this.gameCost;
        },

        modeIcon() {
            return this.modeIcons[this.gameMode];
        },

        modeTitle() {
            return this.modeTitles[this.gameMode];
        },

        modeDesc() {
            return this.modeConfig.desc || '';
        },

        modeRewards() {
            return this.modeConfig.rewards || [];
        },

        currentStreak() {
            return this.stats.current_streak || 0;
        },

        wins() {
            return this.stats.wins || 0;
        },

        temperature() {
            if (this.guesses.length === 0) return 0;
            const lastGuess = this.guesses[this.guesses.length - 1];
            if (!lastGuess || lastGuess.distance_num === null) return 0;
            const maxDist = this.rangeMax - this.rangeMin;
            const percent = Math.max(0, 100 - (lastGuess.distance_num / maxDist) * 100);
            return Math.min(100, percent);
        },

        markerPosition() {
            if (!this.currentGuess) return 50;
            const percent = ((this.currentGuess - this.rangeMin) / (this.rangeMax - this.rangeMin)) * 100;
            return Math.max(0, Math.min(100, percent));
        },

        quickNumbers() {
            const step = Math.max(1, Math.floor((this.rangeMax - this.rangeMin) / 4));
            const nums = [];
            for (let i = 0; i <= 4; i++) {
                nums.push(Math.min(this.rangeMax, this.rangeMin + step * i));
            }
            return nums;
        },

        canGuess() {
            return this.currentGuess !== null
                && this.currentGuess >= this.rangeMin
                && this.currentGuess <= this.rangeMax
                && !this.gameOver
                && this.attempts < this.maxAttempts
                && !this.isProcessing;
        },
    },

    async mounted() {
        await this.loadState();
    },

    methods: {
        async loadState() {
            try {
                const response = await axios.get('/guess-number/state');

                if (response.data?.success) {
                    this.balance = response.data.balance ?? 0;
                    this.gameCost = response.data.game_cost ?? 100;
                    this.modes = response.data.modes || {};
                    this.stats = response.data.stats || this.stats;
                    this.gamesHistory = response.data.history || [];

                    // Восстановление активной игры
                    if (response.data.active_game) {
                        this.restoreActiveGame(response.data.active_game);
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки состояния:', error);
            }
        },

        restoreActiveGame(activeGame) {
            this.activeGame = activeGame;
            this.gameToken = activeGame.token;
            this.gameMode = activeGame.mode;
            this.rangeMin = activeGame.min;
            this.rangeMax = activeGame.max;
            this.maxAttempts = activeGame.max_attempts;
            this.attempts = activeGame.attempts;
            this.guesses = activeGame.guesses || [];
            this.gameStarted = true;
            this.gameOver = activeGame.finished;
            this.gameWon = activeGame.won;
            this.currentReward = activeGame.reward || 0;
        },

        saveState() {
            const state = {
                balance: this.balance,
                wins: this.wins,
                currentStreak: this.currentStreak,
                bestStreak: this.bestStreak,
                gamesHistory: this.gamesHistory,
            };
            localStorage.setItem('guessNumberState', JSON.stringify(state));
        },

        // ==========================================
        // ИГРОВАЯ ЛОГИКА
        // ==========================================
        selectMode(mode) {
            if (this.gameStarted) return;
            this.gameMode = mode;
        },

        async startGame() {
            if (this.balance < this.gameCost || this.isProcessing) {
                this.$notify?.({ title: 'Ошибка', text: 'Недостаточно кэшбэка', type: 'error' });
                return;
            }

            this.isProcessing = true;

            try {
                const response = await axios.post('/guess-number/start', {
                    mode: this.gameMode,
                });

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка старта');
                }

                // Обновляем баланс
                this.balance = response.data.balance;
                if (window.TenantUser) {
                    window.TenantUser.cashback_balance = response.data.balance;
                }

                // Инициализируем игру
                this.gameToken = response.data.token;
                const config = response.data.mode_config;
                this.rangeMin = config.min;
                this.rangeMax = config.max;
                this.maxAttempts = config.max_attempts;
                this.currentGuess = null;
                this.attempts = 0;
                this.guesses = [];
                this.gameStarted = true;
                this.gameOver = false;
                this.gameWon = false;
                this.secretNumber = null; // НЕ знаем число!
                this.currentReward = 0;

            } catch (error) {
                console.error('Ошибка старта:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось начать игру',
                    type: 'error',
                });
            } finally {
                this.isProcessing = false;
            }
        },

        async makeGuess() {
            if (!this.canGuess) return;

            const guess = this.currentGuess;
            this.isProcessing = true;

            try {
                const response = await axios.post('/guess-number/guess', {
                    token: this.gameToken,
                    number: guess,
                });

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка попытки');
                }

                const data = response.data;

                // Добавляем попытку в историю (из ответа сервера)
                this.guesses.push({
                    value: data.guess.value,
                    hint: data.guess.hint,
                    icon: data.guess.icon,
                    text: data.guess.text,
                    distance: data.guess.distance,
                    distance_num: data.guess.distance_num,
                });

                // Обновляем состояние
                this.attempts = data.attempts;
                this.maxAttempts = data.max_attempts;
                this.balance = data.balance;
                this.stats = data.stats;

                if (window.TenantUser) {
                    window.TenantUser.cashback_balance = data.balance;
                }

                // Игра окончена?
                if (data.game_over) {
                    this.gameOver = true;
                    this.gameWon = data.game_won;
                    this.currentReward = data.reward || 0;
                    this.secretNumber = data.secret_number; // Теперь сервер раскрыл число

                    // Обновляем локальную историю
                    await this.loadState();

                    setTimeout(() => {
                        this.showResultModal = true;
                    }, 500);
                }

                this.currentGuess = null;

            } catch (error) {
                console.error('Ошибка попытки:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось сделать попытку',
                    type: 'error',
                });
            } finally {
                this.isProcessing = false;
            }
        },

        calculateReward() {
            const mode = this.gameMode;
            let base;

            if (mode === 'classic') {
                if (this.attempts === 1) base = 500;
                else if (this.attempts <= 3) base = 200;
                else if (this.attempts <= 5) base = 100;
                else if (this.attempts <= 7) base = 50;
                else base = 20;
            } else if (mode === 'jackpot') {
                if (this.attempts <= 3) base = 5000;
                else if (this.attempts <= 7) base = 2000;
                else if (this.attempts <= 12) base = 500;
                else base = 100;
            } else if (mode === 'challenge') {
                if (this.attempts === 1) base = 1000;
                else if (this.attempts === 2) base = 500;
                else base = 300;
            }

            // Бонус за серию побед
            if (this.currentStreak >= 3) {
                base = Math.floor(base * 1.5);
            }

            return base;
        },

        async giveUp() {
            if (!confirm('Сдаться? Игра будет проиграна.') || this.isProcessing) return;

            this.isProcessing = true;

            try {
                const response = await axios.post('/guess-number/give-up', {
                    token: this.gameToken,
                });

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка');
                }

                this.secretNumber = response.data.secret_number;
                this.balance = response.data.balance;
                this.stats = response.data.stats;
                this.gameOver = true;
                this.gameWon = false;

                if (window.TenantUser) {
                    window.TenantUser.cashback_balance = response.data.balance;
                }

                await this.loadState();
                this.showResultModal = true;

            } catch (error) {
                console.error('Ошибка сдачи:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось сдаться',
                    type: 'error',
                });
            } finally {
                this.isProcessing = false;
            }
        },

        resetGame() {
            this.gameStarted = false;
            this.gameOver = false;
            this.gameWon = false;
            this.currentGuess = null;
            this.gameToken = null;
            this.activeGame = null;
            this.secretNumber = null;
            this.currentReward = 0;
        },

        closeResultModal() {
            this.showResultModal = false;
            if (this.gameOver) {
                this.resetGame();
            }
        },

        isNumberImpossible(n) {
            for (const g of this.guesses) {
                if (g.hint === 'higher' && n <= g.value) return true;
                if (g.hint === 'lower' && n >= g.value) return true;
            }
            return false;
        },





        addGameToHistory(won, reward) {
            const now = new Date();
            const dateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            this.gamesHistory.unshift({
                id: Date.now(),
                date: dateStr,
                mode: this.gameMode,
                attempts: this.attempts,
                secret: this.secretNumber,
                won,
                reward: reward || 0,
                cost: this.modeCost,
            });
            if (this.gamesHistory.length > 20) this.gamesHistory.pop();
        },



        // ==========================================
        // UI
        // ==========================================
        particleStyle(i) {
            const size = Math.random() * 6 + 3;
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

        confettiStyle(i) {
            const colors = ['#43e97b', '#38f9d7', '#ffd700', '#fa709a', '#4facfe'];
            const color = colors[Math.floor(Math.random() * colors.length)];
            return {
                background: color,
                left: `${Math.random() * 100}%`,
                animationDelay: `${Math.random() * 2}s`,
                animationDuration: `${Math.random() * 2 + 2}s`,
                transform: `rotate(${Math.random() * 360}deg)`,
            };
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
            return {
                day: date.getDate(),
                month: months[date.getMonth()],
            };
        },

        pluralize(n, one, two, five) {
            let abs = Math.abs(n) % 100;
            const n1 = abs % 10;
            if (abs > 10 && abs < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },

        // ==========================================
        // АДМИН
        // ==========================================
        adminAddBalance(amount) {
            this.balance += amount;
            this.saveState();
            this.$notify?.({ title: 'Админ', text: `+${amount} бонусов`, type: 'success' });
        },

        adminResetStats() {
            if (!confirm('Сбросить всю статистику?')) return;
            this.wins = 0;
            this.currentStreak = 0;
            this.bestStreak = 0;
            this.saveState();
            this.$notify?.({ title: 'Админ', text: 'Статистика сброшена', type: 'success' });
        },

        adminWinGame() {
            if (!this.gameStarted) {
                this.$notify?.({ title: 'Админ', text: 'Сначала начните игру', type: 'warning' });
                return;
            }
            this.currentGuess = this.secretNumber;
            this.makeGuess();
        },

        adminClearHistory() {
            if (!confirm('Очистить историю?')) return;
            this.gamesHistory = [];
            this.saveState();
            this.$notify?.({ title: 'Админ', text: 'История очищена', type: 'success' });
        },
    },
};
</script>

<style scoped>
.guess-number-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO
   ========================================== */
.game-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.25) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(56, 249, 215, 0.3) 0%, transparent 50%);
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
    background: rgba(255, 255, 255, 0.7);
    border-radius: 50%;
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% { transform: translateY(0) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
}

.hero-content { position: relative; z-index: 1; }

.hero-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 16px;
}

.hero-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.hero-sparkle {
    position: absolute;
    font-size: 1.3rem;
    animation: sparkle 2s ease-in-out infinite;
}

.sparkle-1 { top: -10px; right: -10px; }
.sparkle-2 { bottom: -10px; left: -10px; animation-delay: 0.7s; }

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0.5); }
    50% { opacity: 1; transform: scale(1.2); }
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 6px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0 0 20px 0;
}

.hero-stats {
    display: inline-flex;
    align-items: center;
    gap: 16px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
}

.stat-block { display: flex; align-items: center; gap: 10px; }

.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.stat-icon.trophy-icon {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
}

.stat-info { text-align: left; }

.stat-value {
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1;
}

.stat-label {
    font-size: 0.65rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-divider {
    width: 1px;
    height: 30px;
    background: rgba(255, 255, 255, 0.3);
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.game-content { padding: 20px 16px; }

/* ==========================================
   РЕЖИМЫ
   ========================================== */
.mode-section { margin-bottom: 20px; }

.mode-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.mode-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 14px 8px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: var(--bs-body-color);
}

.mode-tab:hover:not(:disabled) {
    border-color: #43e97b;
}

.mode-tab.active {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border-color: #43e97b;
    color: white;
    box-shadow: 0 4px 16px rgba(67, 233, 123, 0.4);
}

.mode-tab i { font-size: 1.3rem; }
.mode-tab span { font-weight: 700; font-size: 0.85rem; }
.mode-tab small { font-size: 0.65rem; opacity: 0.8; }

/* ==========================================
   СТАРТ ИГРЫ
   ========================================== */
.start-section { margin-bottom: 24px; }

.game-preview {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    padding: 24px;
    text-align: center;
}

.preview-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    margin: 0 auto 16px;
}

.preview-icon.classic { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.preview-icon.jackpot { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }
.preview-icon.challenge { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

.preview-title {
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0 0 8px 0;
    color: var(--bs-body-color);
}

.preview-desc {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 20px;
    line-height: 1.4;
}

.preview-rewards {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}

.reward-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
    font-size: 0.85rem;
}

.reward-attempts {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--bs-body-color);
    font-weight: 600;
}

.reward-arrow {
    color: var(--bs-secondary-color);
}

.reward-value {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #43e97b;
    font-weight: 700;
}

.start-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(67, 233, 123, 0.3);
}

.start-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(67, 233, 123, 0.4);
}

.start-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.insufficient {
    margin-top: 10px;
    color: #dc3545;
    font-size: 0.85rem;
}

/* ==========================================
   АКТИВНАЯ ИГРА
   ========================================== */
.active-game {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
}

.attempts-bar {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 14px 16px;
}

.attempts-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

.attempts-info strong {
    color: var(--bs-body-color);
}

.attempts-dots {
    display: flex;
    gap: 4px;
    justify-content: center;
}

.attempt-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    transition: all 0.3s ease;
}

.attempt-dot.used {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border-color: #43e97b;
}

.attempt-dot.current {
    background: #ffd700;
    border-color: #ff9800;
    animation: pulse 1.5s ease-in-out infinite;
    transform: scale(1.3);
}

@keyframes pulse {
    0%, 100% { transform: scale(1.3); }
    50% { transform: scale(1.6); }
}

/* Термометр */
.temperature-meter {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
}

.temp-label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    white-space: nowrap;
}

.temp-label:first-child i { color: #4facfe; }
.temp-label:last-child i { color: #ff6b6b; }

.temp-bar {
    flex: 1;
    height: 8px;
    background: linear-gradient(90deg, #4facfe 0%, #ffd700 50%, #ff6b6b 100%);
    border-radius: 4px;
    position: relative;
}

.temp-indicator {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 14px;
    height: 14px;
    background: white;
    border: 2px solid #1a1a1a;
    border-radius: 50%;
    transition: left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* История попыток */
.guess-history {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.guess-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

.guess-item.hint-higher { border-left: 4px solid #4facfe; }
.guess-item.hint-lower { border-left: 4px solid #ff6b6b; }
.guess-item.hint-correct { border-left: 4px solid #43e97b; background: rgba(67, 233, 123, 0.05); }

.guess-number {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: var(--bs-secondary-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.2rem;
    color: var(--bs-body-color);
    flex-shrink: 0;
}

.hint-correct .guess-number {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.guess-hint {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.hint-higher .guess-hint i { color: #4facfe; }
.hint-lower .guess-hint i { color: #ff6b6b; }
.hint-correct .guess-hint i { color: #43e97b; }

.guess-distance {
    padding: 4px 10px;
    background: var(--bs-secondary-bg);
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.history-slide-enter-active { transition: all 0.3s ease; }
.history-slide-enter-from { opacity: 0; transform: translateX(-20px); }

/* Панель ввода */
.input-panel {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.range-display {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
}

.range-value {
    font-weight: 800;
    color: var(--bs-body-color);
    font-size: 0.9rem;
    min-width: 40px;
    text-align: center;
}

.range-line {
    flex: 1;
    height: 4px;
    background: var(--bs-border-color);
    border-radius: 2px;
    position: relative;
}

.range-marker {
    position: absolute;
    top: 50%;
    width: 12px;
    height: 12px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: left 0.3s ease;
    box-shadow: 0 0 10px rgba(67, 233, 123, 0.6);
}

.number-input-wrapper {
    display: flex;
    justify-content: center;
}

.number-input {
    width: 160px;
    padding: 14px;
    font-size: 2rem;
    font-weight: 800;
    text-align: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-body-color);
    transition: all 0.3s ease;
    -moz-appearance: textfield;
}

.number-input::-webkit-outer-spin-button,
.number-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.number-input:focus {
    outline: none;
    border-color: #43e97b;
    box-shadow: 0 0 0 3px rgba(67, 233, 123, 0.2);
}

.quick-numbers {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 6px;
}

.quick-btn {
    padding: 10px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 10px;
    font-weight: 700;
    color: var(--bs-body-color);
    cursor: pointer;
    transition: all 0.2s ease;
}

.quick-btn:hover:not(.disabled) {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    border-color: #43e97b;
}

.quick-btn.disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.guess-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(67, 233, 123, 0.3);
}

.guess-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(67, 233, 123, 0.4);
}

.guess-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.give-up-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    background: transparent;
    border: 1px solid var(--bs-border-color);
    border-radius: 10px;
    color: var(--bs-secondary-color);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.give-up-btn:hover {
    border-color: #dc3545;
    color: #dc3545;
}

/* Финальный экран */
.game-result {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
}

.game-result.won {
    border-color: #43e97b;
    background: linear-gradient(135deg, rgba(67, 233, 123, 0.05) 0%, rgba(56, 249, 215, 0.05) 100%);
}

.game-result.lost {
    border-color: #dc3545;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.05) 0%, rgba(255, 107, 107, 0.05) 100%);
}

.result-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto 16px;
}

.won .result-icon {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.lost .result-icon {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
}

.result-title {
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0 0 8px 0;
    color: var(--bs-body-color);
}

.result-number {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 16px;
}

.result-number strong {
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

.result-reward {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    border-radius: 20px;
    font-weight: 700;
    margin-bottom: 16px;
}

.result-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-bottom: 16px;
    padding: 12px;
    background: var(--bs-secondary-bg);
    border-radius: 12px;
}

.result-stat { text-align: center; }
.result-stat-value { font-size: 1.3rem; font-weight: 800; color: var(--bs-body-color); }
.result-stat-label { font-size: 0.7rem; color: var(--bs-secondary-color); text-transform: uppercase; }
.result-stat-divider { width: 1px; height: 30px; background: var(--bs-border-color); }

.play-again-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.play-again-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(67, 233, 123, 0.4);
}

/* ==========================================
   ИСТОРИЯ
   ========================================== */
.history-section { margin-bottom: 24px; }

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(67, 233, 123, 0.3);
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

.empty-history {
    text-align: center;
    padding: 40px 20px;
    color: var(--bs-secondary-color);
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 12px;
}

.empty-hint { font-size: 0.85rem; opacity: 0.7; }

.history-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.history-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
}

.history-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 12px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
    min-width: 50px;
}

.date-day {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--bs-body-color);
    line-height: 1;
}

.date-month {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    margin-top: 2px;
}

.history-mode {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
    flex-shrink: 0;
}

.history-mode.mode-classic { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.history-mode.mode-jackpot { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }
.history-mode.mode-challenge { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

.history-info { flex: 1; min-width: 0; }

.history-title {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.history-value {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.history-result {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
}

.history-result.win {
    background: rgba(67, 233, 123, 0.15);
    color: #2ea85a;
}

.history-result.loss {
    background: rgba(220, 53, 69, 0.15);
    color: #dc3545;
}

/* ==========================================
   АДМИН
   ========================================== */
.admin-section { margin-top: 32px; }

.admin-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
}

.admin-toggle-content { display: flex; align-items: center; gap: 12px; }

.admin-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #2c3e50 0%, #4a6278 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.admin-info { display: flex; flex-direction: column; text-align: left; }
.admin-title { font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); }
.admin-hint { font-size: 0.75rem; color: var(--bs-secondary-color); }

.admin-arrow { color: var(--bs-secondary-color); transition: transform 0.3s ease; }
.admin-arrow.rotated { transform: rotate(180deg); }

.admin-content {
    margin-top: 12px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
}

.admin-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }

.admin-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    color: var(--bs-body-color);
    transition: all 0.2s ease;
}

.admin-btn:hover {
    border-color: #43e97b;
    background: rgba(67, 233, 123, 0.05);
}

.admin-btn i { font-size: 1.2rem; color: #43e97b; }
.admin-btn span { font-size: 0.8rem; font-weight: 600; }

/* ==========================================
   МОДАЛКА
   ========================================== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(6px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: var(--bs-body-bg);
    border-radius: 24px;
    width: 100%;
    max-width: 420px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.result-modal { position: relative; overflow: visible; }

.result-confetti {
    position: absolute;
    inset: -50px;
    pointer-events: none;
    overflow: visible;
}

.confetti-piece {
    position: absolute;
    top: 0;
    width: 10px;
    height: 10px;
    animation: confettiFall linear infinite;
}

@keyframes confettiFall {
    0% { transform: translateY(-50px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(600px) rotate(720deg); opacity: 0; }
}

.result-content {
    padding: 32px 24px;
    text-align: center;
    position: relative;
    z-index: 1;
}

.result-icon-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
    margin: 0 auto 20px;
    animation: iconPop 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes iconPop {
    0% { transform: scale(0) rotate(-180deg); }
    70% { transform: scale(1.1) rotate(10deg); }
    100% { transform: scale(1) rotate(0deg); }
}

.result-icon-large.won { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.result-icon-large.lost { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }

.result-title-large {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--bs-body-color);
    margin: 0 0 8px 0;
}

.result-desc {
    font-size: 0.95rem;
    color: var(--bs-secondary-color);
    margin-bottom: 20px;
}

.reward-display {
    display: inline-block;
    padding: 16px 28px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border-radius: 16px;
    margin-bottom: 20px;
}

.reward-amount {
    font-size: 2rem;
    font-weight: 800;
    color: white;
    line-height: 1;
}

.reward-label {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 4px;
}

.result-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.result-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(67, 233, 123, 0.4);
}

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; max-height: 500px; }

@media (max-width: 576px) {
    .hero-title { font-size: 1.5rem; }
    .hero-icon { width: 64px; height: 64px; font-size: 1.8rem; }
    .hero-stats { gap: 12px; padding: 10px 16px; }
    .stat-value { font-size: 1.1rem; }

    .number-input { width: 130px; font-size: 1.7rem; }
    .result-icon-large { width: 100px; height: 100px; font-size: 2.5rem; }
}
</style>
