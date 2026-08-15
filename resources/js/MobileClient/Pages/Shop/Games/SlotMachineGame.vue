<template>
    <div class="slot-game-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="game-hero">
            <div class="hero-background"></div>
            <div class="hero-particles">
                <span v-for="i in 15" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">
                        <i class="fa-solid fa-dice"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">✨</div>
                    <div class="hero-sparkle sparkle-2">💰</div>
                </div>
                <h1 class="hero-title">Слот-машина</h1>
                <p class="hero-subtitle">Крути барабаны и собирай выигрышные комбинации!</p>

                <div class="hero-stats">
                    <div class="stat-block">
                        <div class="stat-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ Math.round(userBalance) }}₽</div>
                            <div class="stat-label">Кэшбэк</div>
                        </div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-block">
                        <div class="stat-icon cost-icon">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ moveCost }}₽</div>
                            <div class="stat-label">Цена спина</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="game-content">

            <!-- ========================================== -->
            <!-- ПРАВИЛА ИГРЫ -->
            <!-- ========================================== -->
            <div class="rules-section">
                <button class="rules-toggle" @click="showRules = !showRules">
                    <div class="rules-toggle-content">
                        <div class="rules-icon">
                            <i class="fa-solid fa-scroll"></i>
                        </div>
                        <div class="rules-info">
                            <span class="rules-title">Таблица выплат</span>
                            <span class="rules-hint">Нажмите, чтобы посмотреть</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down rules-arrow" :class="{ 'rotated': showRules }"></i>
                </button>
                <transition name="slide-down">
                    <div v-if="showRules" class="rules-content">
                        <div class="payout-table">
                            <div class="payout-row jackpot">
                                <span>7️⃣ 7️⃣ 7️⃣</span>
                                <span class="payout-value">ДЖЕКПОТ! (Легендарный приз)</span>
                            </div>
                            <div class="payout-row epic">
                                <span>💎 💎 💎</span>
                                <span class="payout-value">Эпический приз (до 500 бонусов)</span>
                            </div>
                            <div class="payout-row rare">
                                <span>🔔 🔔 🔔</span>
                                <span class="payout-value">Редкий приз (до 200 бонусов)</span>
                            </div>
                            <div class="payout-row common">
                                <span>🍊 🍊 🍊 / 🍋 🍋 🍋 / 🍒 🍒 🍒</span>
                                <span class="payout-value">Обычный приз (до 50 бонусов)</span>
                            </div>
                            <div class="payout-row consolation">
                                <span>Два одинаковых символа</span>
                                <span class="payout-value">Утешительный приз (10 бонусов)</span>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- СТАТУС ИГРЫ -->
            <!-- ========================================== -->
            <div class="game-status">
                <div class="status-card">
                    <div class="status-icon">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div class="status-info">
                        <div class="status-title">
                            {{ gameFinished ? 'Игра завершена' : 'Испытай удачу!' }}
                        </div>
                        <div class="status-subtitle">
                            {{ gameFinished ? 'Возвращайтесь завтра' : `Осталось попыток: ${attemptsLeft}` }}
                        </div>
                    </div>
                    <div class="status-badge" :class="{ 'finished': gameFinished }">
                        {{ attemptsLeft }}
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- СЛОТ-МАШИНА -->
            <!-- ========================================== -->
            <div class="slot-machine-section">
                <div class="slot-machine-frame">
                    <!-- Декоративные лампочки -->
                    <div class="lights-top">
                        <span v-for="i in 8" :key="'lt'+i" class="light" :style="{ animationDelay: i * 0.1 + 's' }"></span>
                    </div>

                    <!-- Окно с барабанами -->
                    <div class="reels-window">
                        <div class="reel" :class="{ 'is-spinning': reel1Spinning }">
                            <div class="reel-strip" :style="{ transform: `translateY(${reel1Offset}px)` }">
                                <div v-for="(sym, idx) in reel1Symbols" :key="'r1'+idx" class="symbol">
                                    {{ sym }}
                                </div>
                            </div>
                        </div>
                        <div class="reel" :class="{ 'is-spinning': reel2Spinning }">
                            <div class="reel-strip" :style="{ transform: `translateY(${reel2Offset}px)` }">
                                <div v-for="(sym, idx) in reel2Symbols" :key="'r2'+idx" class="symbol">
                                    {{ sym }}
                                </div>
                            </div>
                        </div>
                        <div class="reel" :class="{ 'is-spinning': reel3Spinning }">
                            <div class="reel-strip" :style="{ transform: `translateY(${reel3Offset}px)` }">
                                <div v-for="(sym, idx) in reel3Symbols" :key="'r3'+idx" class="symbol">
                                    {{ sym }}
                                </div>
                            </div>
                        </div>

                        <!-- Линия выигрыша -->
                        <div class="win-line"></div>
                    </div>

                    <!-- Декоративные лампочки снизу -->
                    <div class="lights-bottom">
                        <span v-for="i in 8" :key="'lb'+i" class="light" :style="{ animationDelay: i * 0.1 + 's' }"></span>
                    </div>
                </div>

                <!-- Кнопка SPIN -->
                <button
                    class="spin-button"
                    :class="{ 'disabled': isSpinning || !canPlay }"
                    :disabled="isSpinning || !canPlay"
                    @click="spin"
                >
                    <span v-if="!isSpinning">КРУТИТЬ!</span>
                    <span v-else class="spinning-text">УДАЧИ...</span>
                </button>


                <!-- После кнопки SPIN -->
                <transition name="slide-down">
                    <div v-if="!hasEnoughCashback && !isSpinning" class="cashback-warning">
                        <div class="warning-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="warning-content">
                            <div class="warning-title">Недостаточно кэшбэка</div>
                            <div class="warning-text">
                                Для спина нужно <strong>{{ moveCost }}₽</strong>.
                                Ваш баланс: <strong>{{ Math.round(userBalance) }}₽</strong>
                            </div>
                            <div class="warning-hint">
                                <i class="fa-solid fa-lightbulb"></i>
                                Делайте заказы для пополнения кэшбэка
                            </div>
                        </div>
                    </div>
                </transition>

                <div v-if="!canPlay && !gameFinished" class="no-balance-hint">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>Недостаточно бонусов для спина</span>
                </div>
            </div>


            <!-- ========================================== -->
            <!-- КНОПКА "ИГРАТЬ ЕЩЁ" -->
            <!-- ========================================== -->
            <div v-if="gameFinished" class="play-again-section">
                <button
                    class="play-again-btn"
                    :disabled="attemptsLeft <= 0 || userBalance < moveCost"
                    @click="startNewGame"
                >
                    <i class="fa-solid fa-rotate-right"></i>
                    <span v-if="attemptsLeft > 0 && userBalance >= moveCost">Новый спин ({{ moveCost }} бонусов)</span>
                    <span v-else-if="attemptsLeft <= 0">Попытки закончились</span>
                    <span v-else>Недостаточно бонусов</span>
                </button>
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
                            <button class="admin-btn" @click="adminResetAll">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Сбросить всем</span>
                            </button>
                            <button class="admin-btn" @click="adminAddAttempts">
                                <i class="fa-solid fa-plus"></i>
                                <span>+1 попытка</span>
                            </button>
                            <button class="admin-btn" @click="adminAddBalance">
                                <i class="fa-solid fa-coins"></i>
                                <span>+500 бонусов</span>
                            </button>
                        </div>
                    </div>
                </transition>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: РЕЗУЛЬТАТ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showResultModal" class="modal-overlay" @click.self="closeResultModal">
                <div class="modal-container result-modal">
                    <div class="result-confetti" v-if="winTier === 'legendary' || winTier === 'epic'">
                        <span v-for="i in 40" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                    </div>
                    <div class="result-content">
                        <div class="result-rarity-badge" :class="'rarity-' + winTier">
                            {{ winTierText }}
                        </div>
                        <div class="result-icon-wrapper">
                            <div class="result-icon" :class="'rarity-' + winTier">
                                <i :class="resultIcon"></i>
                            </div>
                            <div class="result-glow"></div>
                        </div>
                        <h3 class="result-title">{{ resultTitle }}</h3>
                        <p class="result-description">{{ resultDescription }}</p>

                        <div class="result-details">
                            <div class="detail-row">
                                <i class="fa-solid fa-coins"></i>
                                <span>Выигрыш: <strong>+{{ winAmount }} бонусов</strong></span>
                            </div>
                            <div class="detail-row">
                                <i class="fa-solid fa-ticket"></i>
                                <span>Ставка: <strong>-{{ moveCost }}</strong></span>
                            </div>
                            <div class="detail-row" :style="{ color: netProfit >= 0 ? '#198754' : '#dc3545' }">
                                <i :class="netProfit >= 0 ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down'"></i>
                                <span>Итого: <strong>{{ netProfit >= 0 ? '+' : '' }}{{ netProfit }}</strong></span>
                            </div>
                        </div>

                        <button class="result-btn" @click="closeResultModal">
                            <i class="fa-solid fa-check"></i>
                            <span>Забрать приз</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>


export default {
    name: "SlotMachineGame",



    data() {
        return {
            // 🎰 Настройки
            gameSettings: {
                can_play: true,
                interval: 1,
                attempts_per_period: 1,
                spin_cost: 1000,  // ✅ ИСПРАВЛЕНО: ставка 1000
                symbols: [],
            },

            // Состояние
            attemptsLeft: 0,
            userBalance: 0,
            gameFinished: false,
            isSpinning: false,
            isProcessing: false, // 🆕 Защита от race condition

            // UI
            showRules: false,
            showAdmin: false,
            showResultModal: false,

            // Барабаны
            reel1Spinning: false,
            reel2Spinning: false,
            reel3Spinning: false,
            reel1Offset: 0,
            reel2Offset: 0,
            reel3Offset: 0,

            reel1Symbols: ['🍒', '🍋', '🍊', '🔔', '💎', '7️⃣'],
            reel2Symbols: ['🍒', '🍋', '🍊', '🔔', '💎', '7️⃣'],
            reel3Symbols: ['🍒', '🍋', '🍊', '🔔', '💎', '7️⃣'],

            // Таймеры для очистки (защита от утечек)
            animationTimers: [],

            // Результат
            winTier: 'common',
            winAmount: 0,
            netProfit: 0,
            resultTitle: '',
            resultDescription: '',
            resultIcon: 'fa-solid fa-gift',

            // История
            spinHistory: [],

            // Статистика
            stats: {
                total_spins: 0,
                total_won: 0,
                total_spent: 0,
                biggest_win: 0,
            },
        };
    },

    computed: {
        isAdmin() {
            const user = window.TenantUser;
            return user?.role === 'admin' || user?.is_admin === true;
        },

        // 🎰 Стоимость спина из настроек
        moveCost() {
            return this.gameSettings.spin_cost || 1000;
        },

        // 🎰 Достаточно ли кэшбэка
        hasEnoughCashback() {
            return this.userBalance >= this.moveCost;
        },

        canPlay() {
            return this.hasEnoughCashback
                && this.attemptsLeft > 0
                && !this.gameFinished
                && !this.isProcessing;
        },

        winTierText() {
            const texts = {
                common: 'Обычный выигрыш',
                rare: 'Редкий выигрыш',
                epic: 'Эпический выигрыш',
                legendary: 'ДЖЕКПОТ!',
                consolation: 'Утешительный приз',
                loss: 'Попробуйте ещё раз'
            };
            return texts[this.winTier] || 'Выигрыш';
        },
    },

    async mounted() {
        await this.loadGameData();
    },
    beforeUnmount() {
        // Очищаем все таймеры анимации
        this.animationTimers.forEach(t => clearInterval(t));
        this.animationTimers = [];
    },

    methods: {
        // ==========================================
        // ЛОГИКА ИГРЫ
        // ==========================================
        async loadGameData() {
            try {
                const [settingsRes, stateRes] = await Promise.all([
                    axios.get('/slot-machine/settings'),
                    axios.get('/slot-machine/state'),
                ]);

                // Настройки
                if (settingsRes.data?.success && settingsRes.data.slot_machine) {
                    this.gameSettings = { ...this.gameSettings, ...settingsRes.data.slot_machine };
                }

                // Состояние
                if (stateRes.data?.success) {
                    this.attemptsLeft = stateRes.data.attempts_left ?? 1;
                    this.userBalance = stateRes.data.balance ?? (window.TenantUser?.cashback_balance || 0);
                    this.gameFinished = stateRes.data.game_finished || false;
                    this.spinHistory = stateRes.data.history || [];
                    this.stats = stateRes.data.stats || this.stats;
                }
            } catch (error) {
                console.error('Ошибка загрузки данных игры:', error);
                this.userBalance = window.TenantUser?.cashback_balance || 0;
            }
        },


        async spin() {
            // 🛡️ Защита от множественных кликов
            if (!this.canPlay || this.isProcessing) return;

            // Локальные проверки
            if (!this.hasEnoughCashback) {
                this.$notify?.({
                    title: '💰 Недостаточно кэшбэка',
                    text: `Для спина нужно ${this.moveCost}₽. Ваш баланс: ${Math.round(this.userBalance)}₽`,
                    type: 'warning',
                });
                return;
            }

            if (this.attemptsLeft <= 0) {
                this.$notify?.({
                    title: 'Игра',
                    text: 'Попытки закончились',
                    type: 'warning',
                });
                return;
            }

            this.isProcessing = true;
            this.isSpinning = true;

            try {
                // 1. Отправляем запрос на сервер (списание + генерация результата)
                const response = await axios.post('/slot-machine/spin');

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка спина');
                }

                const { reels, win_tier, win_amount, combination, net_profit, balance, attempts_left } = response.data;

                // 2. Запускаем анимацию с финальными символами из ответа
                await this.animateReels(reels);

                // 3. Обновляем локальное состояние из ответа сервера
                if (balance !== undefined) {
                    this.userBalance = balance;
                    if (window.TenantUser) {
                        window.TenantUser.cashback_balance = balance;
                    }
                }

                if (attempts_left !== undefined) {
                    this.attemptsLeft = attempts_left;
                }

                // 4. Показываем результат
                this.evaluateWinLocally(reels[0], reels[1], reels[2], win_tier, win_amount, combination);
                this.netProfit = net_profit;

                this.gameFinished = this.attemptsLeft <= 0;

                // 5. Показываем модалку
                setTimeout(() => {
                    this.showResultModal = true;
                }, 300);

            } catch (error) {
                console.error('Ошибка спина:', error);

                if (error.response?.status === 403) {
                    // Недостаточно кэшбэка или попытки кончились
                    if (error.response.data?.balance !== undefined) {
                        this.userBalance = error.response.data.balance;
                        if (window.TenantUser) {
                            window.TenantUser.cashback_balance = error.response.data.balance;
                        }
                    }

                    this.$notify?.({
                        title: '💰 ' + (error.response.data?.message || 'Невозможно сыграть'),
                        text: error.response.data?.shortage
                            ? `Не хватает: ${error.response.data.shortage}₽`
                            : 'Проверьте баланс или попробуйте позже',
                        type: 'warning',
                    });
                } else {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: error.response?.data?.message || 'Не удалось выполнить спин',
                        type: 'error',
                    });
                }
            } finally {
                // 🔓 Разблокировка ВСЕГДА (даже при ошибке)
                this.isSpinning = false;
                this.isProcessing = false;
            }
        },

        async animateReels(finalReels) {
            // Очищаем предыдущие таймеры
            this.animationTimers.forEach(t => clearInterval(t));
            this.animationTimers = [];

            const animateReel = (reelNum, finalSymbol, duration) => {
                const reelKey = `reel${reelNum}`;
                this[`${reelKey}Spinning`] = true;

                // Быстрая смена символов
                const symbols = this.gameSettings.symbols?.length
                    ? this.gameSettings.symbols.map(s => s.icon)
                    : ['🍒', '🍋', '🍊', '🔔', '💎', '7️⃣'];

                const interval = setInterval(() => {
                    const randomSym = symbols[Math.floor(Math.random() * symbols.length)];
                    this[`${reelKey}Symbols`] = [randomSym, randomSym, randomSym, randomSym, randomSym];
                }, 50);

                this.animationTimers.push(interval);

                return new Promise(resolve => {
                    setTimeout(() => {
                        clearInterval(interval);
                        this[`${reelKey}Spinning`] = false;
                        // Показываем финальный символ из ответа сервера
                        this[`${reelKey}Symbols`] = ['🍒', finalSymbol, '🍋', '🔔', '💎'];
                        resolve();
                    }, duration);
                });
            };

            // Каскадная остановка барабанов
            await Promise.all([
                animateReel(1, finalReels[0], 1000),
                animateReel(2, finalReels[1], 1800),
                animateReel(3, finalReels[2], 2600),
            ]);
        },

// Локальная визуализация результата (данные уже с сервера!)
        evaluateWinLocally(s1, s2, s3, winTier, winAmount, combination) {
            this.winTier = winTier;
            this.winAmount = winAmount;

            if (winTier === 'legendary') {
                this.resultTitle = '🎉 ДЖЕКПОТ!';
                this.resultDescription = combination;
                this.resultIcon = 'fa-solid fa-crown';
            } else if (winTier === 'epic') {
                this.resultTitle = 'Эпический выигрыш!';
                this.resultDescription = combination;
                this.resultIcon = 'fa-solid fa-gem';
            } else if (winTier === 'rare') {
                this.resultTitle = 'Редкий выигрыш!';
                this.resultDescription = combination;
                this.resultIcon = 'fa-solid fa-bell';
            } else if (winTier === 'common') {
                this.resultTitle = 'Хороший выигрыш!';
                this.resultDescription = combination;
                this.resultIcon = 'fa-solid fa-star';
            } else if (winTier === 'consolation') {
                this.resultTitle = 'Утешительный приз';
                this.resultDescription = combination;
                this.resultIcon = 'fa-solid fa-hand-holding-heart';
            } else {
                this.resultTitle = 'Не повезло';
                this.resultDescription = 'В следующий раз обязательно повезёт!';
                this.resultIcon = 'fa-solid fa-face-sad-tear';
            }
        },

        startReelAnimation(reelNum, finalSymbol, duration) {
            const reelKey = `reel${reelNum}`;
            this[`${reelKey}Spinning`] = true;
            this[`${reelKey}Offset`] = 0;

            // Быстрая смена символов для эффекта вращения
            const interval = setInterval(() => {
                const randomSym = this.symbols[Math.floor(Math.random() * this.symbols.length)].icon;
                this[`${reelKey}Symbols`] = [randomSym, randomSym, randomSym, randomSym, randomSym];
            }, 50);

            // Остановка
            setTimeout(() => {
                clearInterval(interval);
                this[`${reelKey}Spinning`] = false;
                // Показываем финальный символ по центру
                this[`${reelKey}Symbols`] = ['🍒', finalSymbol, '🍋', '🔔', '💎'];
                // Примечание: в реальном проекте здесь лучше использовать CSS transform translateY
                // для плавной прокрутки длинной ленты символов.
                // Для простоты здесь мы просто подменяем массив, а CSS класс .is-spinning убирает blur.
            }, duration);
        },

        evaluateWin(s1, s2, s3) {
            if (s1 === s2 && s2 === s3) {
                // 3 одинаковых
                const symbolData = this.symbols.find(s => s.icon === s1);
                this.winAmount = symbolData.prize;

                if (s1 === '7️⃣') {
                    this.winTier = 'legendary';
                    this.resultTitle = 'ДЖЕКПОТ!';
                    this.resultDescription = 'Невероятная удача! Три семёрки!';
                    this.resultIcon = 'fa-solid fa-crown';
                } else if (s1 === '💎') {
                    this.winTier = 'epic';
                    this.resultTitle = 'Эпический выигрыш!';
                    this.resultDescription = 'Три алмаза сияют для вас!';
                    this.resultIcon = 'fa-solid fa-gem';
                } else if (s1 === '🔔') {
                    this.winTier = 'rare';
                    this.resultTitle = 'Редкий выигрыш!';
                    this.resultDescription = 'Звон колокольчиков приносит удачу!';
                    this.resultIcon = 'fa-solid fa-bell';
                } else {
                    this.winTier = 'common';
                    this.resultTitle = 'Хороший выигрыш!';
                    this.resultDescription = `Три ${s1} приносят свои плоды!`;
                    this.resultIcon = 'fa-solid fa-star';
                }
            } else if (s1 === s2 || s2 === s3 || s1 === s3) {
                // 2 одинаковых
                this.winTier = 'consolation';
                this.winAmount = 10;
                this.resultTitle = 'Утешительный приз';
                this.resultDescription = 'Почти получилось! Держите небольшой бонус.';
                this.resultIcon = 'fa-solid fa-hand-holding-heart';
            } else {
                // Проигрыш (но мы всё равно что-то дадим для удержания, или 0)
                this.winTier = 'loss';
                this.winAmount = 0;
                this.resultTitle = 'Не повезло';
                this.resultDescription = 'В следующий раз обязательно повезёт!';
                this.resultIcon = 'fa-solid fa-face-sad-tear';
            }

            if (this.winAmount > 0) {
                this.userBalance += this.winAmount;
            }
        },

        async startNewGame() {
            if (this.attemptsLeft <= 0 || this.userBalance < this.moveCost) return;

            this.gameFinished = false;
            this.showResultModal = false;
            this.winTier = 'common';
            this.winAmount = 0;

            // Сброс визуала барабанов
            this.reel1Symbols = ['🍒', '🍋', '🍊', '🔔', '💎', '7️⃣'];
            this.reel2Symbols = ['🍒', '🍋', '🍊', '🔔', '💎', '7️⃣'];
            this.reel3Symbols = ['🍒', '🍋', '🍊', '🔔', '💎', '7️⃣'];
        },

        // ==========================================
        // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
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

        closeResultModal() {
            this.showResultModal = false;
        },

        adminResetAll() {
            if (!confirm('Сбросить игру всем пользователям?')) return;
            this.$notify?.({ title: 'Админ', text: 'Игра сброшена', type: 'success' });
        },

        adminAddAttempts() {
            this.attemptsLeft++;
            this.gameFinished = false;
            this.$notify?.({ title: 'Админ', text: 'Добавлена попытка', type: 'success' });
        },

        adminAddBalance() {
            this.userBalance += 500;
            this.$notify?.({ title: 'Админ', text: '+500 бонусов', type: 'success' });
        },
    },
};
</script>

<style scoped>
.slot-game-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.game-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
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
    background: rgba(255, 255, 255, 0.6);
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
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.hero-sparkle {
    position: absolute;
    font-size: 1.2rem;
    animation: sparkle 2s ease-in-out infinite;
}

.sparkle-1 { top: -8px; right: -8px; animation-delay: 0s; }
.sparkle-2 { bottom: -8px; left: -8px; animation-delay: 0.7s; }

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0.5); }
    50% { opacity: 1; transform: scale(1); }
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
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
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

.stat-icon.cost-icon {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
}

.stat-info { text-align: left; }
.stat-value { font-size: 1.2rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.65rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-divider { width: 1px; height: 30px; background: rgba(255, 255, 255, 0.3); }

/* ==========================================
   КОНТЕНТ
   ========================================== */
.game-content { padding: 20px 16px; }

.rules-section, .admin-section { margin-bottom: 20px; }

.rules-toggle, .admin-toggle {
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

.rules-toggle:hover, .admin-toggle:hover { border-color: #4facfe; }

.rules-toggle-content, .admin-toggle-content { display: flex; align-items: center; gap: 12px; }

.rules-icon, .admin-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}

.rules-icon { background: rgba(79, 172, 254, 0.1); color: #4facfe; }
.admin-icon { background: linear-gradient(135deg, #2c3e50 0%, #4a6278 100%); color: white; }

.rules-info, .admin-info { display: flex; flex-direction: column; text-align: left; }
.rules-title, .admin-title { font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); }
.rules-hint, .admin-hint { font-size: 0.75rem; color: var(--bs-secondary-color); }

.rules-arrow, .admin-arrow { color: var(--bs-secondary-color); transition: transform 0.3s ease; }
.rules-arrow.rotated, .admin-arrow.rotated { transform: rotate(180deg); }

.rules-content, .admin-content {
    margin-top: 12px; padding: 16px;
    background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 14px;
}

.payout-table { display: flex; flex-direction: column; gap: 10px; }

.payout-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 14px; background: var(--bs-secondary-bg); border-radius: 10px;
    font-size: 0.9rem; font-weight: 600;
}

.payout-row.jackpot { background: rgba(255, 215, 0, 0.15); color: #b8860b; border: 1px solid rgba(255, 215, 0, 0.3); }
.payout-row.epic { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
.payout-row.rare { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
.payout-row.common { color: var(--bs-body-color); }
.payout-row.consolation { color: var(--bs-secondary-color); font-size: 0.85rem; }

/* СТАТУС ИГРЫ */
.game-status { margin-bottom: 24px; }

.status-card {
    display: flex; align-items: center; gap: 14px; padding: 16px;
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.08) 0%, rgba(32, 201, 151, 0.03) 100%);
    border: 1px solid rgba(25, 135, 84, 0.2); border-radius: 16px;
}

.status-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
}

.status-info { flex: 1; }
.status-title { font-weight: 700; font-size: 1rem; color: var(--bs-body-color); margin-bottom: 2px; }
.status-subtitle { font-size: 0.8rem; color: var(--bs-secondary-color); }

.status-badge {
    width: 48px; height: 48px; border-radius: 50%;
    background: var(--bs-primary); color: white;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 800; box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}
.status-badge.finished { background: #6c757d; }

/* ==========================================
   СЛОТ-МАШИНА
   ========================================== */
.slot-machine-section {
    display: flex; flex-direction: column; align-items: center; gap: 24px; margin-bottom: 32px;
}

.slot-machine-frame {
    position: relative;
    width: 100%;
    max-width: 360px;
    background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
    border-radius: 24px;
    padding: 20px 16px;
    box-shadow:
        0 20px 50px rgba(0, 0, 0, 0.3),
        inset 0 2px 4px rgba(255, 255, 255, 0.1);
    border: 4px solid #34495e;
}

/* Лампочки */
.lights-top, .lights-bottom {
    display: flex; justify-content: space-between; padding: 0 10px; margin-bottom: 12px;
}
.lights-bottom { margin-top: 12px; margin-bottom: 0; }

.light {
    width: 12px; height: 12px; border-radius: 50%;
    background: #ffd700;
    box-shadow: 0 0 8px #ffd700;
    animation: blink 1s infinite alternate;
}

@keyframes blink {
    0% { opacity: 0.4; transform: scale(0.9); }
    100% { opacity: 1; transform: scale(1.1); box-shadow: 0 0 12px #ffd700; }
}

/* Окно барабанов */
.reels-window {
    display: flex;
    gap: 8px;
    background: #000;
    padding: 8px;
    border-radius: 12px;
    border: 3px solid #ffd700;
    box-shadow: inset 0 0 20px rgba(0,0,0,0.8);
    position: relative;
    overflow: hidden;
    height: 120px; /* Высота одного символа */
}

.reel {
    flex: 1;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.reel.is-spinning .symbol {
    filter: blur(4px);
    animation: reelBlur 0.1s infinite;
}

@keyframes reelBlur {
    0% { transform: translateY(-5px); }
    50% { transform: translateY(5px); }
    100% { transform: translateY(-5px); }
}

.symbol {
    font-size: 3.5rem;
    line-height: 1;
    text-align: center;
    width: 100%;
}

/* Линия выигрыша */
.win-line {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 4px;
    background: rgba(255, 0, 0, 0.6);
    transform: translateY(-50%);
    box-shadow: 0 0 10px rgba(255, 0, 0, 0.8);
    z-index: 10;
    pointer-events: none;
}

/* Кнопка SPIN */
.spin-button {
    width: 100%;
    max-width: 360px;
    padding: 18px;
    background: linear-gradient(180deg, #ff4757 0%, #ff6b81 100%);
    border: none;
    border-radius: 16px;
    color: white;
    font-size: 1.5rem;
    font-weight: 900;
    letter-spacing: 2px;
    cursor: pointer;
    box-shadow:
        0 8px 0 #c41e3a,
        0 15px 20px rgba(0,0,0,0.3);
    transition: all 0.1s ease;
    text-transform: uppercase;
}

.spin-button:active:not(.disabled) {
    transform: translateY(8px);
    box-shadow:
        0 0 0 #c41e3a,
        0 5px 10px rgba(0,0,0,0.3);
}

.spin-button.disabled {
    background: #95a5a6;
    box-shadow: 0 8px 0 #7f8c8d;
    cursor: not-allowed;
    opacity: 0.7;
}

.spinning-text {
    animation: pulseText 0.5s infinite alternate;
}

@keyframes pulseText {
    from { opacity: 0.7; }
    to { opacity: 1; }
}

.no-balance-hint {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    color: #ff4757; font-size: 0.9rem; font-weight: 600; margin-top: 8px;
}

/* КНОПКА "ИГРАТЬ ЕЩЁ" */
.play-again-section { margin-bottom: 24px; }

.play-again-btn {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 16px 24px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none; border-radius: 14px; color: white; font-weight: 700; font-size: 1rem;
    cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(79, 172, 254, 0.3);
}

.play-again-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(79, 172, 254, 0.4); }
.play-again-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* АДМИН-ПАНЕЛЬ */
.admin-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.admin-btn {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 14px 10px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color);
    border-radius: 12px; cursor: pointer; transition: all 0.2s ease; color: var(--bs-body-color);
}
.admin-btn:hover { border-color: #4facfe; background: rgba(79, 172, 254, 0.05); }
.admin-btn i { font-size: 1.2rem; color: #4facfe; }
.admin-btn span { font-size: 0.8rem; font-weight: 600; }

/* ==========================================
   МОДАЛКА РЕЗУЛЬТАТА
   ========================================== */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(6px);
    z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
}

.modal-container {
    background: var(--bs-body-bg); border-radius: 24px; width: 100%; max-width: 420px;
    max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: modalSlideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.result-modal { position: relative; overflow: visible; }

.result-confetti { position: absolute; inset: -50px; pointer-events: none; overflow: visible; }
.confetti-piece { position: absolute; top: 0; width: 10px; height: 10px; animation: confettiFall linear infinite; }

@keyframes confettiFall {
    0% { transform: translateY(-50px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(600px) rotate(720deg); opacity: 0; }
}

.result-content { padding: 32px 24px; text-align: center; position: relative; z-index: 1; }

.result-rarity-badge {
    display: inline-block; padding: 4px 14px; border-radius: 20px;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px;
}

.result-rarity-badge.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.result-rarity-badge.rarity-rare { background: rgba(13, 110, 253, 0.15); color: #0d6efd; }
.result-rarity-badge.rarity-epic { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.result-rarity-badge.rarity-legendary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%);
    color: #b8860b; border: 1px solid rgba(255, 215, 0, 0.3);
}
.result-rarity-badge.rarity-consolation { background: rgba(79, 172, 254, 0.15); color: #4facfe; }
.result-rarity-badge.rarity-loss { background: rgba(108, 117, 125, 0.1); color: #95a5a6; }

.result-icon-wrapper { position: relative; display: inline-block; margin-bottom: 20px; }

.result-icon {
    width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; color: white; position: relative; z-index: 1;
    animation: resultIconPop 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

@keyframes resultIconPop {
    0% { transform: scale(0) rotate(-180deg); }
    70% { transform: scale(1.1) rotate(10deg); }
    100% { transform: scale(1) rotate(0deg); }
}

.result-icon.rarity-common { background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%); }
.result-icon.rarity-rare { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); }
.result-icon.rarity-epic { background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%); }
.result-icon.rarity-legendary { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }

.result-glow {
    position: absolute; inset: -20px; border-radius: 50%;
    background: radial-gradient(circle, rgba(79, 172, 254, 0.3) 0%, transparent 70%);
    animation: glowPulse 2s ease-in-out infinite;
}

@keyframes glowPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0.8; }
}

.result-title { font-size: 1.5rem; font-weight: 800; color: var(--bs-body-color); margin: 0 0 8px 0; }
.result-description { font-size: 0.9rem; color: var(--bs-secondary-color); line-height: 1.5; margin: 0 0 20px 0; }

.result-details { background: rgba(79, 172, 254, 0.05); border-radius: 14px; padding: 16px; margin-bottom: 20px; }

.detail-row { display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 1rem; color: var(--bs-body-color); }
.detail-row i { color: #4facfe; font-size: 1.2rem; }
.detail-row strong { color: #4facfe; font-size: 1.2rem; }

.result-btn {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 16px 24px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none; border-radius: 14px; color: white; font-weight: 700; font-size: 1rem;
    cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(79, 172, 254, 0.3);
}
.result-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(79, 172, 254, 0.4); }

/* АНИМАЦИИ */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; max-height: 500px; }

/* АДАПТИВ */
@media (max-width: 576px) {
    .hero-title { font-size: 1.5rem; }
    .hero-icon { width: 64px; height: 64px; font-size: 1.8rem; }
    .hero-stats { gap: 12px; padding: 10px 16px; }
    .stat-value { font-size: 1rem; }

    .symbol { font-size: 2.8rem; }
    .reels-window { height: 100px; }
    .spin-button { font-size: 1.2rem; padding: 16px; }

    .result-icon { width: 80px; height: 80px; font-size: 2rem; }
    .result-title { font-size: 1.3rem; }
}

.cashback-warning {
    margin-top: 16px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.08) 0%, rgba(255, 152, 0, 0.05) 100%);
    border: 1.5px solid rgba(220, 53, 69, 0.3);
    border-radius: 16px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    width: 100%;
    max-width: 360px;
}

.warning-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.warning-content { flex: 1; min-width: 0; }

.warning-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #dc3545;
    margin-bottom: 4px;
}

.warning-text {
    font-size: 0.85rem;
    color: var(--bs-body-color);
    line-height: 1.5;
    margin-bottom: 8px;
}

.warning-text strong { color: var(--bs-primary); }

.warning-hint {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 0.78rem;
    color: var(--bs-secondary-color);
    padding: 8px 10px;
    background: rgba(255, 193, 7, 0.08);
    border-radius: 8px;
    border-left: 3px solid #ffc107;
}

.warning-hint i {
    color: #ffc107;
    margin-top: 2px;
    flex-shrink: 0;
}
</style>
