<template>
    <div class="scratch-game-page">

        <div class="game-hero">
            <div class="hero-background"></div>
            <div class="hero-particles">
                <span v-for="i in 15" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">✨</div>
                    <div class="hero-sparkle sparkle-2">💰</div>
                </div>
                <h1 class="hero-title">Скретч-карта</h1>
                <p class="hero-subtitle">Сотри защитный слой и узнай свой приз!</p>

                <!-- 🎰 СЕТКА: БАЛАНС + СТАВКА -->
                <div class="hero-stats-grid">
                    <div class="hero-stat-card balance" :class="{ 'insufficient': !hasEnoughCashback }">
                        <div class="hero-stat-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="hero-stat-info">
                            <div class="hero-stat-value">
                                {{ Math.round(userBalance) }}₽
                            </div>
                            <div class="hero-stat-label">Кэшбэк</div>
                        </div>
                        <div class="hero-stat-hint" v-if="!hasEnoughCashback">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Мало
                        </div>
                    </div>

                    <div class="hero-stat-card cost">
                        <div class="hero-stat-icon cost-icon">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div class="hero-stat-info">
                            <div class="hero-stat-value">
                                {{ moveCost }}₽
                            </div>
                            <div class="hero-stat-label">Ставка</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="game-content">

            <!-- ПРАВИЛА ИГРЫ -->
            <div class="rules-section">
                <button class="rules-toggle" @click="showRules = !showRules">
                    <div class="rules-toggle-content">
                        <div class="rules-icon">
                            <i class="fa-solid fa-scroll"></i>
                        </div>
                        <div class="rules-info">
                            <span class="rules-title">Как играть?</span>
                            <span class="rules-hint">Нажмите, чтобы прочитать</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down rules-arrow" :class="{ 'rotated': showRules }"></i>
                </button>
                <transition name="slide-down">
                    <div v-if="showRules" class="rules-content">
                        <div class="rules-list">
                            <div class="rule-item">
                                <div class="rule-number">1</div>
                                <div class="rule-text">Стоимость scratching — <strong>{{ moveCost }} бонусов</strong></div>
                            </div>
                            <div class="rule-item">
                                <div class="rule-number">2</div>
                                <div class="rule-text">Проведите пальцем или мышкой по карте, чтобы стереть защитный слой</div>
                            </div>
                            <div class="rule-item">
                                <div class="rule-number">3</div>
                                <div class="rule-text">Когда стёрто достаточно — приз откроется автоматически</div>
                            </div>
                            <div class="rule-item">
                                <div class="rule-number">4</div>
                                <div class="rule-text">1 попытка в сутки. Призы: бонусы, товары, скидки!</div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- СТАТУС ИГРЫ -->
            <div class="game-status">
                <div class="status-card">
                    <div class="status-icon">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div class="status-info">
                        <div class="status-title">
                            {{ gameFinished ? 'Игра завершена' : 'Сотри карту!' }}
                        </div>
                        <div class="status-subtitle">
                            {{ gameFinished
                            ? 'Возвращайтесь завтра'
                            : `Осталось попыток: ${attemptsLeft}` }}
                        </div>
                    </div>
                    <div class="status-badge" :class="{ 'finished': gameFinished }">
                        {{ attemptsLeft }}
                    </div>
                </div>
            </div>

            <!-- КНОПКА: ЧТО МОЖЕТ ВЫПАСТЬ -->
            <button class="prizes-preview-btn" @click="openPrizesModal">
                <i class="fa-solid fa-eye"></i>
                <span>Посмотреть все призы</span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <!-- СКРЕТЧ-КАРТА -->
            <div class="scratch-section">
                <div class="scratch-card-wrapper" :class="{ 'is-revealed': isRevealed }">

                    <!-- ВСЕГДА рендерим приз (не v-if!) -->
                    <div class="prize-underneath">
                        <div v-if="selectedPrize" class="prize-reveal-content">
                            <div class="reveal-icon" :class="'type-' + selectedPrize.type">
                                <i :class="selectedPrize.icon"></i>
                            </div>
                            <div class="reveal-title">{{ selectedPrize.title }}</div>
                            <div class="reveal-value">{{ formatPrizeValue(selectedPrize) }}</div>
                        </div>
                        <div v-else class="prize-placeholder">
                            <i class="fa-solid fa-gift"></i>
                            <span>Приз скрыт</span>
                        </div>
                    </div>

                    <!-- Canvas для стирания -->
                    <canvas
                        ref="scratchCanvas"
                        class="scratch-canvas"
                        :class="{ 'hidden': isRevealed }"
                        @mousedown="startScratch"
                        @mousemove="scratch"
                        @mouseup="endScratch"
                        @mouseleave="endScratch"
                        @touchstart.prevent="startScratch"
                        @touchmove.prevent="scratch"
                        @touchend="endScratch"
                    ></canvas>

                    <!-- Индикатор прогресса -->
                    <div v-if="!isRevealed && !gameFinished" class="scratch-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" :style="{ width: scratchProgress + '%' }"></div>
                        </div>
                        <div class="progress-text">
                            <i class="fa-solid fa-hand-pointer"></i>
                            <span>{{ scratchProgress < 10 ? 'Проведите по карте' : `Стерто: ${Math.round(scratchProgress)}%` }}</span>
                        </div>
                    </div>

                    <!-- Overlay "Игра окончена" -->
                    <div v-if="gameFinished && !isRevealed" class="game-over-overlay">
                        <i class="fa-solid fa-lock"></i>
                        <span>Попытки закончились</span>
                    </div>

                </div>

                <!-- Подсказка -->
                <div v-if="!gameFinished && !isRevealed" class="scratch-hint">
                    <i class="fa-solid fa-hand-pointer"></i>
                    <span>Проведите пальцем или мышкой по серебристому полю</span>
                </div>
            </div>

            <!-- 🆕 ПРЕДУПРЕЖДЕНИЕ О НЕХВАТКЕ КЭШБЭКА -->
            <transition name="slide-down">
                <div v-if="!hasEnoughCashback && !isRevealed" class="cashback-warning">
                    <div class="warning-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="warning-content">
                        <div class="warning-title">Недостаточно кэшбэка</div>
                        <div class="warning-text">
                            Для игры нужно <strong>{{ moveCost }}₽</strong>.
                            Ваш баланс: <strong>{{ Math.round(userBalance) }}₽</strong>
                        </div>
                        <div class="warning-hint">
                            <i class="fa-solid fa-lightbulb"></i>
                            Делайте заказы или приглашайте друзей для пополнения кэшбэка
                        </div>
                    </div>
                </div>
            </transition>

            <!-- КНОПКА "ИГРАТЬ ЕЩЁ" -->
            <div v-if="gameFinished" class="play-again-section">
                <button
                    class="play-again-btn"
                    :disabled="attemptsLeft <= 0 || userBalance < moveCost"
                    @click="startNewGame"
                >
                    <i class="fa-solid fa-rotate-right"></i>
                    <span v-if="attemptsLeft > 0 && userBalance >= moveCost">Новая карта ({{ moveCost }} бонусов)</span>
                    <span v-else-if="attemptsLeft <= 0">Попытки закончились</span>
                    <span v-else>Недостаточно бонусов</span>
                </button>
            </div>

            <!-- АДМИН-ПАНЕЛЬ -->
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
                            <button class="admin-btn" @click="adminShowStats">
                                <i class="fa-solid fa-chart-simple"></i>
                                <span>Статистика</span>
                            </button>
                        </div>
                    </div>
                </transition>
            </div>

        </div>

        <!-- МОДАЛКА: РЕЗУЛЬТАТ -->
        <transition name="modal-fade">
            <div v-if="showResultModal" class="modal-overlay" @click.self="closeResultModal">
                <div class="modal-container result-modal">
                    <div class="result-confetti" v-if="selectedPrize?.rarity === 'legendary'">
                        <span v-for="i in 40" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                    </div>
                    <div class="result-content">
                        <div class="result-rarity-badge" :class="'rarity-' + selectedPrize?.rarity">
                            {{ rarityText(selectedPrize?.rarity) }}
                        </div>
                        <div class="result-icon-wrapper">
                            <div class="result-icon" :class="['rarity-' + selectedPrize?.rarity, 'type-' + selectedPrize?.type]">
                                <i :class="selectedPrize?.icon"></i>
                            </div>
                            <div class="result-glow"></div>
                        </div>
                        <h3 class="result-title">{{ selectedPrize?.title }}</h3>
                        <p class="result-description">{{ selectedPrize?.description }}</p>

                        <div class="result-details">
                            <div class="detail-row" v-if="selectedPrize?.type === 'bonus'">
                                <i class="fa-solid fa-coins"></i>
                                <span>Вам начислено <strong>+{{ selectedPrize.value }} бонусов</strong></span>
                            </div>
                            <div class="detail-row" v-else-if="selectedPrize?.type === 'product'">
                                <i class="fa-solid fa-box"></i>
                                <span>Товар: <strong>{{ selectedPrize.productName }}</strong></span>
                            </div>
                            <div class="detail-row" v-else-if="selectedPrize?.type === 'product_discount'">
                                <i class="fa-solid fa-percent"></i>
                                <span>Скидка <strong>{{ selectedPrize.value }}%</strong> на {{ selectedPrize.productName }}</span>
                            </div>
                            <div class="detail-row" v-else-if="selectedPrize?.type === 'delivery_discount'">
                                <i class="fa-solid fa-truck"></i>
                                <span>Скидка на доставку: <strong>{{ selectedPrize.isPercent ? selectedPrize.value + '%' : formatPrice(selectedPrize.value) }}</strong></span>
                            </div>
                            <div class="detail-row" v-else-if="selectedPrize?.type === 'order_discount'">
                                <i class="fa-solid fa-receipt"></i>
                                <span>Скидка <strong>{{ selectedPrize.value }}%</strong> на заказ от {{ formatPrice(selectedPrize.minOrderAmount) }}</span>
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

        <!-- МОДАЛКА: ВСЕ ПРИЗЫ -->
        <transition name="modal-fade">
            <div v-if="showPrizesModal" class="modal-overlay" @click.self="showPrizesModal = false">
                <div class="modal-container prizes-modal">
                    <div class="modal-header">
                        <div class="modal-icon">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                        <div class="modal-title-wrapper">
                            <h5 class="modal-title">Возможные призы</h5>
                            <span class="modal-subtitle">Что может быть скрыто под слоем</span>
                        </div>
                        <button class="modal-close" @click="showPrizesModal = false">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="prizes-list">
                            <div
                                v-for="prize in allPrizes"
                                :key="prize.id"
                                class="prize-item"
                            >
                                <div class="prize-icon" :class="['rarity-' + prize.rarity, 'type-' + prize.type]">
                                    <i :class="prize.icon"></i>
                                </div>
                                <div class="prize-info">
                                    <div class="prize-title">
                                        {{ prize.title }}
                                        <span class="prize-rarity-tag" :class="'rarity-' + prize.rarity">
                                            {{ rarityText(prize.rarity) }}
                                        </span>
                                    </div>
                                    <div class="prize-description">{{ prize.description }}</div>
                                </div>
                                <div class="prize-value">
                                    {{ formatPrizeValue(prize) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>

export default {
    name: "ScratchCardGame",

    data() {
        return {

            scratchCheckTimeout: null,
            // 🎰 Настройки
            gameSettings: {
                can_play: true,
                interval: 1,
                attempts_per_period: 1,
                spin_cost: 500,
                reveal_threshold: 55,
                rules: '',
                prizes: [],
            },

            // Состояние игры
            attemptsLeft: 0,
            userBalance: 0,
            gameFinished: false,
            isProcessing: false,

            // 🎴 Текущая игра
            currentGameToken: null,
            currentPrize: null,

            // Скретч
            isScratching: false,
            scratchProgress: 0,
            isRevealed: false,
            brushSize: 30,
            gameStarted: false, // 🆕 Начата ли текущая игра (ставка списана)

            // UI
            showRules: false,
            showAdmin: false,
            showResultModal: false,
            showPrizesModal: false,
            selectedPrize: null,

            // Canvas
            canvas: null,
            ctx: null,
            canvasWidth: 0,
            canvasHeight: 0,

            allPrizes: [],
        };
    },

    computed: {
        isAdmin() {
            const user = window.TenantUser;
            return user?.role === 'admin' || user?.is_admin === true;
        },

        // 🎰 Достаточно ли кэшбэка
        hasEnoughCashback() {
            return this.userBalance >= this.moveCost;
        },

        // 🎰 Стоимость хода
        moveCost() {
            return this.gameSettings.spin_cost || 500;
        },

        canPlay() {
            return this.userBalance >= this.moveCost
                && this.attemptsLeft > 0
                && !this.gameFinished;
        },
    },

    async mounted() {
        await this.loadGameData();


        // Ждём, пока приз отрендерится, затем инициализируем canvas
        await this.$nextTick();
        this.initCanvas();

        window.addEventListener('resize', this.handleResize);
    },

    beforeUnmount() {
        window.removeEventListener('resize', this.handleResize);

        if (this.scratchCheckTimeout) {
            clearTimeout(this.scratchCheckTimeout);
        }
    },

    methods: {
        initCanvas() {
            this.canvas = this.$refs.scratchCanvas;
            if (!this.canvas) return;

            this.ctx = this.canvas.getContext('2d');

            const wrapper = this.canvas.parentElement;
            this.canvasWidth = wrapper.clientWidth;
            this.canvasHeight = wrapper.clientHeight;

            this.canvas.width = this.canvasWidth;
            this.canvas.height = this.canvasHeight;

            this.drawScratchLayer();
        },

        drawScratchLayer() {
            const ctx = this.ctx;
            const w = this.canvasWidth;
            const h = this.canvasHeight;

            ctx.globalCompositeOperation = 'source-over';

            // Серебристый градиент
            const gradient = ctx.createLinearGradient(0, 0, w, h);
            gradient.addColorStop(0, '#c0c0c0');
            gradient.addColorStop(0.3, '#e8e8e8');
            gradient.addColorStop(0.5, '#d0d0d0');
            gradient.addColorStop(0.7, '#e8e8e8');
            gradient.addColorStop(1, '#b8b8b8');

            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, w, h);

            // Паттерн "звёзды"
            ctx.fillStyle = 'rgba(255, 255, 255, 0.3)';
            const patternSize = 30;
            for (let y = 0; y < h; y += patternSize) {
                for (let x = 0; x < w; x += patternSize) {
                    ctx.beginPath();
                    ctx.arc(x + patternSize / 2, y + patternSize / 2, 4, 0, Math.PI * 2);
                    ctx.fill();
                }
            }

            // Текст "Сотри здесь"
            ctx.fillStyle = 'rgba(100, 100, 100, 0.6)';
            ctx.font = 'bold 18px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('✦ СОТРИ ЗДЕСЬ ✦', w / 2, h / 2);

            // Рамка
            ctx.strokeStyle = 'rgba(150, 150, 150, 0.5)';
            ctx.lineWidth = 2;
            ctx.strokeRect(10, 10, w - 20, h - 20);
        },

        handleResize() {
            if (this.isRevealed || this.gameFinished) return;
            this.initCanvas();
        },

        getEventPosition(event) {
            const rect = this.canvas.getBoundingClientRect();
            let x, y;

            if (event.type.startsWith('touch')) {
                const touch = event.touches[0] || event.changedTouches[0];
                x = touch.clientX - rect.left;
                y = touch.clientY - rect.top;
            } else {
                x = event.clientX - rect.left;
                y = event.clientY - rect.top;
            }

            const scaleX = this.canvas.width / rect.width;
            const scaleY = this.canvas.height / rect.height;

            return {
                x: x * scaleX,
                y: y * scaleY,
            };
        },

        async startScratch(event) {
            if (this.gameFinished || this.isRevealed || this.isProcessing) return;

            // 🆕 Если игра ещё не начата — начинаем (списываем ставку)
            if (!this.gameStarted) {
                // Локальная проверка баланса
                if (!this.hasEnoughCashback) {
                    this.$notify?.({
                        title: '💰 Недостаточно кэшбэка',
                        text: `Для игры нужно ${this.moveCost}₽. Ваш баланс: ${Math.round(this.userBalance)}₽`,
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

                try {
                    const response = await axios.post('/scratch-card/start');

                    if (!response.data.success) {
                        throw new Error(response.data.message || 'Ошибка старта');
                    }

                    // 💾 Сохраняем токен и приз
                    this.currentGameToken = response.data.token;
                    this.currentPrize = response.data.prize;
                    this.selectedPrize = response.data.prize;

                    // 💰 Обновляем баланс и попытки
                    if (response.data.balance !== undefined) {
                        this.userBalance = response.data.balance;
                        if (window.TenantUser) {
                            window.TenantUser.cashback_balance = response.data.balance;
                        }
                    }
                    if (response.data.attempts_left !== undefined) {
                        this.attemptsLeft = response.data.attempts_left;
                    }

                    this.gameStarted = true;

                } catch (error) {
                    console.error('Ошибка старта игры:', error);

                    if (error.response?.status === 403) {
                        if (error.response.data?.balance !== undefined) {
                            this.userBalance = error.response.data.balance;
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
                            text: error.response?.data?.message || 'Не удалось начать игру',
                            type: 'error',
                        });
                    }

                    this.isProcessing = false;
                    return; // Не даём стирать карту
                }

                this.isProcessing = false;
            }

            // Теперь можно стирать
            this.isScratching = true;
            const pos = this.getEventPosition(event);
            this.scratchAt(pos.x, pos.y);
        },

        scratch(event) {
            if (!this.isScratching || this.gameFinished || this.isRevealed) return;
            const pos = this.getEventPosition(event);
            this.scratchAt(pos.x, pos.y);
        },

        endScratch() {
            this.isScratching = false;
        },

        scratchAt(x, y) {
            const ctx = this.ctx;

            ctx.globalCompositeOperation = 'destination-out';

            const gradient = ctx.createRadialGradient(x, y, 0, x, y, this.brushSize);
            gradient.addColorStop(0, 'rgba(0, 0, 0, 1)');
            gradient.addColorStop(0.5, 'rgba(0, 0, 0, 0.8)');
            gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');

            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(x, y, this.brushSize, 0, Math.PI * 2);
            ctx.fill();

            ctx.globalCompositeOperation = 'source-over';

            // ✅ Debounce: не чаще раза в 100ms
            if (this.scratchCheckTimeout) {
                clearTimeout(this.scratchCheckTimeout);
            }
            this.scratchCheckTimeout = setTimeout(() => {
                this.checkScratchProgress();
            }, 100);
        },
        checkScratchProgress() {
            if (Math.random() > 0.15) return;

            const tempCanvas = document.createElement('canvas');
            const sampleSize = 50;
            tempCanvas.width = sampleSize;
            tempCanvas.height = sampleSize;
            const tempCtx = tempCanvas.getContext('2d');

            tempCtx.drawImage(this.canvas, 0, 0, sampleSize, sampleSize);
            const imageData = tempCtx.getImageData(0, 0, sampleSize, sampleSize);

            let transparentPixels = 0;
            const totalPixels = imageData.data.length / 4;

            for (let i = 3; i < imageData.data.length; i += 4) {
                if (imageData.data[i] === 0) {
                    transparentPixels++;
                }
            }

            this.scratchProgress = (transparentPixels / totalPixels) * 100;

            // ✅ ИСПРАВЛЕНО: берём из gameSettings
            const threshold = this.gameSettings.reveal_threshold || 55;
            if (this.scratchProgress >= threshold && !this.isRevealed) {
                this.revealPrize();
            }
        },

        async revealPrize() {
            if (this.isRevealed) return;

            this.isRevealed = true;
            this.isProcessing = true;

            const fadeOut = () => {
                return new Promise(resolve => {
                    let opacity = 1;
                    const fade = () => {
                        opacity -= 0.1;
                        if (opacity <= 0) {
                            this.canvas.classList.add('hidden');
                            resolve();
                            return;
                        }
                        this.canvas.style.opacity = opacity;
                        requestAnimationFrame(fade);
                    };
                    fade();
                });
            };

            await fadeOut();

            // 🆕 ПОДТВЕРЖДЕНИЕ ПРИЗА НА СЕРВЕРЕ
            if (this.currentGameToken) {
                try {
                    const response = await axios.post('/scratch-card/confirm', {
                        token: this.currentGameToken,
                    });

                    if (response.data.success) {
                        // Обновляем баланс после начисления
                        if (response.data.balance !== undefined) {
                            this.userBalance = response.data.balance;
                            if (window.TenantUser) {
                                window.TenantUser.cashback_balance = response.data.balance;
                            }
                        }

                        // Показываем уведомление
                        const prize = this.currentPrize;
                        const netProfit = (prize.type === 'bonus' ? prize.value : 0) - this.moveCost;
                        const profitText = netProfit >= 0 ? `+${netProfit}` : `${netProfit}`;

                        this.$notify?.({
                            title: netProfit >= 0 ? '🎉 Победа!' : '🎴 Приз получен',
                            text: `${prize.title}: ${profitText} бонусов (ставка: ${this.moveCost}₽)`,
                            type: netProfit >= 0 ? 'success' : 'info',
                        });
                    }
                } catch (error) {
                    console.error('Ошибка подтверждения приза:', error);
                    this.$notify?.({
                        title: 'Ошибка',
                        text: 'Не удалось подтвердить приз. Свяжитесь с поддержкой.',
                        type: 'error',
                    });
                }
            }

            setTimeout(() => {
                this.showResultModal = true;
                this.gameFinished = this.attemptsLeft <= 0;
                this.isProcessing = false;
            }, 300);
        },

        async loadGameData() {
            try {
                const [settingsRes, stateRes] = await Promise.all([
                    axios.get('/scratch-card/settings'),
                    axios.get('/scratch-card/state'),
                ]);

                if (settingsRes.data?.success && settingsRes.data.scratch_card) {
                    this.gameSettings = { ...this.gameSettings, ...settingsRes.data.scratch_card };
                    this.allPrizes = this.gameSettings.prizes || [];
                } else {
                    this.allPrizes = this.getDefaultPrizes();
                }

                if (stateRes.data?.success) {
                    this.attemptsLeft = stateRes.data.attempts_left ?? this.gameSettings.attempts_per_period;
                    this.userBalance = stateRes.data.balance ?? (window.TenantUser?.cashback_balance || 0);
                    this.gameFinished = stateRes.data.game_finished || false;

                    // 🆕 Проверяем наличие незавершённой игры на сервере
                    // (если backend возвращает pending_game_token)
                    if (stateRes.data.pending_game_token) {
                        this.currentGameToken = stateRes.data.pending_game_token;
                        this.currentPrize = stateRes.data.pending_prize;
                        this.selectedPrize = stateRes.data.pending_prize;
                        this.gameStarted = true;

                        this.$notify?.({
                            title: '🎴 Незавершённая игра',
                            text: 'У вас есть начатая карта — сотрите её, чтобы получить приз!',
                            type: 'info',
                        });
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки данных игры:', error);
                this.allPrizes = this.getDefaultPrizes();
                this.userBalance = window.TenantUser?.cashback_balance || 0;
            }
        },

        getDefaultPrizes() {
            return [
                { id: 1, type: 'bonus', title: '50 бонусов', icon: 'fa-solid fa-coins', value: 50, rarity: 'common' },
                { id: 2, type: 'bonus', title: '100 бонусов', icon: 'fa-solid fa-coins', value: 100, rarity: 'common' },
                { id: 3, type: 'bonus', title: '350 бонусов', icon: 'fa-solid fa-gem', value: 350, rarity: 'rare' },
                { id: 4, type: 'bonus', title: '1000 бонусов', icon: 'fa-solid fa-gem', value: 1000, rarity: 'epic' },
                { id: 5, type: 'bonus', title: '5000 бонусов', icon: 'fa-solid fa-trophy', value: 5000, rarity: 'legendary' },
            ];
        },






        async startNewGame() {
            if (this.attemptsLeft <= 0 || !this.hasEnoughCashback) return;

            // Сброс состояния
            this.isRevealed = false;
            this.scratchProgress = 0;
            this.gameFinished = false;
            this.gameStarted = false;
            this.currentGameToken = null;
            this.currentPrize = null;
            this.selectedPrize = null;
            this.showResultModal = false;

            await this.$nextTick();
            this.initCanvas();
        },

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

        rarityText(rarity) {
            const texts = {
                common: 'Обычный',
                rare: 'Редкий',
                epic: 'Эпический',
                legendary: 'Легендарный',
            };
            return texts[rarity] || 'Обычный';
        },

        formatPrizeValue(prize) {
            if (!prize) return '';
            switch (prize.type) {
                case 'bonus': return `+${prize.value}`;
                case 'product': return 'Товар';
                case 'product_discount': return `−${prize.value}%`;
                case 'delivery_discount': return prize.isPercent ? `−${prize.value}%` : `−${this.formatPrice(prize.value)}`;
                case 'order_discount': return `−${prize.value}%`;
                default: return prize.value;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
        },

        closeResultModal() {
            this.showResultModal = false;
        },

        openPrizesModal() {
            this.showPrizesModal = true;
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

        adminShowStats() {
            this.$notify?.({ title: 'Статистика', text: 'В разработке', type: 'info' });
        },
    },
};
</script>

<style scoped>
.scratch-game-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

.game-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

.rules-toggle:hover, .admin-toggle:hover { border-color: var(--bs-primary); }

.rules-toggle-content, .admin-toggle-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rules-icon, .admin-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.rules-icon { background: rgba(240, 147, 251, 0.1); color: #f093fb; }
.admin-icon { background: linear-gradient(135deg, #2c3e50 0%, #4a6278 100%); color: white; }

.rules-info, .admin-info { display: flex; flex-direction: column; text-align: left; }
.rules-title, .admin-title { font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); }
.rules-hint, .admin-hint { font-size: 0.75rem; color: var(--bs-secondary-color); }

.rules-arrow, .admin-arrow { color: var(--bs-secondary-color); transition: transform 0.3s ease; }
.rules-arrow.rotated, .admin-arrow.rotated { transform: rotate(180deg); }

.rules-content, .admin-content {
    margin-top: 12px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
}

.rules-list { display: flex; flex-direction: column; gap: 12px; }

.rule-item { display: flex; gap: 12px; align-items: flex-start; }

.rule-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
}

.rule-text {
    flex: 1;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    line-height: 1.5;
    padding-top: 3px;
}

.game-status { margin-bottom: 16px; }

.status-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.08) 0%, rgba(32, 201, 151, 0.03) 100%);
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 16px;
}

.status-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.status-info { flex: 1; }
.status-title { font-weight: 700; font-size: 1rem; color: var(--bs-body-color); margin-bottom: 2px; }
.status-subtitle { font-size: 0.8rem; color: var(--bs-secondary-color); }

.status-badge {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.status-badge.finished { background: #6c757d; }

.prizes-preview-btn {
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
    margin-bottom: 20px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.9rem;
}

.prizes-preview-btn:hover {
    border-color: #f5576c;
    background: rgba(245, 87, 108, 0.03);
}

.prizes-preview-btn i:first-child { color: #f5576c; margin-right: 10px; }
.prizes-preview-btn i:last-child { color: var(--bs-secondary-color); font-size: 0.8rem; }

.scratch-section { margin-bottom: 24px; }

.scratch-card-wrapper {
    position: relative;
    width: 100%;
    max-width: 400px;
    height: 240px;
    margin: 0 auto;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    background: linear-gradient(135deg, #fff5f7 0%, #ffe8ec 100%);
    border: 3px solid #f5576c;
}

.scratch-card-wrapper.is-revealed {
    border-color: #198754;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

/* ИСПРАВЛЕНИЕ: Приз ВСЕГДА рендерится, даже если selectedPrize = null */
.prize-underneath {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
    background: linear-gradient(135deg, #fff5f7 0%, #ffe8ec 100%);
}

.prize-reveal-content {
    text-align: center;
    animation: prizeReveal 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes prizeReveal {
    0% { opacity: 0; transform: scale(0.5) rotate(-10deg); }
    50% { transform: scale(1.1) rotate(5deg); }
    100% { opacity: 1; transform: scale(1) rotate(0deg); }
}

.reveal-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 12px;
    box-shadow: 0 8px 24px rgba(245, 87, 108, 0.4);
}

.reveal-icon.type-product { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); box-shadow: 0 8px 24px rgba(40, 167, 69, 0.4); }
.reveal-icon.type-product_discount,
.reveal-icon.type-delivery_discount,
.reveal-icon.type-order_discount { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); box-shadow: 0 8px 24px rgba(220, 53, 69, 0.4); }

.reveal-title {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.reveal-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #f5576c;
}

/* Placeholder когда приза ещё нет */
.prize-placeholder {
    text-align: center;
    color: var(--bs-secondary-color);
    opacity: 0.5;
}

.prize-placeholder i {
    font-size: 3rem;
    margin-bottom: 8px;
    display: block;
}

.prize-placeholder span {
    font-size: 0.9rem;
    font-weight: 600;
}

.scratch-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    cursor: grab;
    touch-action: none;
    transition: opacity 0.5s ease;
}

.scratch-canvas:active { cursor: grabbing; }

.scratch-canvas.hidden {
    opacity: 0;
    pointer-events: none;
}

.scratch-progress {
    position: absolute;
    bottom: 12px;
    left: 12px;
    right: 12px;
    z-index: 3;
    pointer-events: none;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 6px;
    backdrop-filter: blur(4px);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
    border-radius: 3px;
    transition: width 0.2s ease;
    box-shadow: 0 0 8px rgba(245, 87, 108, 0.6);
}

.progress-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
    padding: 4px 10px;
    border-radius: 10px;
    width: fit-content;
    margin: 0 auto;
}

.progress-text i { font-size: 0.8rem; }

.game-over-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: white;
    z-index: 5;
    font-size: 1rem;
    font-weight: 700;
}

.game-over-overlay i {
    font-size: 2.5rem;
    opacity: 0.8;
}

.scratch-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 16px;
    padding: 10px 16px;
    background: rgba(245, 87, 108, 0.08);
    border: 1px dashed rgba(245, 87, 108, 0.3);
    border-radius: 12px;
    font-size: 0.85rem;
    color: #f5576c;
    font-weight: 600;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
    animation: hintPulse 2s ease-in-out infinite;
}

@keyframes hintPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.scratch-hint i {
    font-size: 1.1rem;
    animation: handWave 1.5s ease-in-out infinite;
}

@keyframes handWave {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-15deg); }
    75% { transform: rotate(15deg); }
}

.play-again-section { margin-bottom: 24px; }

.play-again-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(245, 87, 108, 0.3);
}

.play-again-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(245, 87, 108, 0.4);
}

.play-again-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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
    transition: all 0.2s ease;
    color: var(--bs-body-color);
}

.admin-btn:hover {
    border-color: #f5576c;
    background: rgba(245, 87, 108, 0.05);
}

.admin-btn i { font-size: 1.2rem; color: #f5576c; }
.admin-btn span { font-size: 0.8rem; font-weight: 600; }

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
    display: flex;
    flex-direction: column;
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

.result-content { padding: 32px 24px; text-align: center; position: relative; z-index: 1; }

.result-rarity-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 20px;
}

.result-rarity-badge.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.result-rarity-badge.rarity-rare { background: rgba(13, 110, 253, 0.15); color: #0d6efd; }
.result-rarity-badge.rarity-epic { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.result-rarity-badge.rarity-legendary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%);
    color: #b8860b;
    border: 1px solid rgba(255, 215, 0, 0.3);
}

.result-icon-wrapper { position: relative; display: inline-block; margin-bottom: 20px; }

.result-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    position: relative;
    z-index: 1;
    animation: resultIconPop 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

@keyframes resultIconPop {
    0% { transform: scale(0) rotate(-180deg); }
    70% { transform: scale(1.1) rotate(10deg); }
    100% { transform: scale(1) rotate(0deg); }
}

.result-icon.rarity-common { background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%); }
.result-icon.rarity-rare { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); }
.result-icon.rarity-epic { background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%); }
.result-icon.rarity-legendary {
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
}

.result-icon.type-product { background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; }
.result-icon.type-product_discount,
.result-icon.type-delivery_discount,
.result-icon.type-order_discount { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; }

.result-glow {
    position: absolute;
    inset: -20px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(245, 87, 108, 0.3) 0%, transparent 70%);
    animation: glowPulse 2s ease-in-out infinite;
}

@keyframes glowPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0.8; }
}

.result-title { font-size: 1.5rem; font-weight: 800; color: var(--bs-body-color); margin: 0 0 8px 0; }
.result-description { font-size: 0.9rem; color: var(--bs-secondary-color); line-height: 1.5; margin: 0 0 20px 0; }

.result-details {
    background: rgba(245, 87, 108, 0.05);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.detail-row i { color: #f5576c; font-size: 1.1rem; }
.detail-row strong { color: #f5576c; }

.result-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(245, 87, 108, 0.3);
}

.result-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(245, 87, 108, 0.4); }

.prizes-modal .modal-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.modal-title-wrapper { flex: 1; }
.modal-title { margin: 0; font-weight: 700; font-size: 1.1rem; color: var(--bs-body-color); }
.modal-subtitle { font-size: 0.8rem; color: var(--bs-secondary-color); }

.modal-close {
    width: 40px;
    height: 40px;
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

.modal-close:hover { background: #dc3545; color: white; transform: rotate(90deg); }

.modal-body { padding: 20px; overflow-y: auto; max-height: 60vh; }

.prizes-list { display: flex; flex-direction: column; gap: 10px; }

.prize-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    transition: all 0.2s ease;
}

.prize-item:hover { transform: translateX(4px); }

.prize-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.prize-icon.type-product { background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; }
.prize-icon.type-product_discount,
.prize-icon.type-delivery_discount,
.prize-icon.type-order_discount { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; }

.prize-info { flex: 1; min-width: 0; }

.prize-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    flex-wrap: wrap;
}

.prize-rarity-tag {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.prize-rarity-tag.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.prize-rarity-tag.rarity-rare { background: rgba(13, 110, 253, 0.15); color: #0d6efd; }
.prize-rarity-tag.rarity-epic { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.prize-rarity-tag.rarity-legendary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%);
    color: #b8860b;
}

.prize-description { font-size: 0.8rem; color: var(--bs-secondary-color); line-height: 1.4; }
.prize-value { font-size: 1.1rem; font-weight: 800; color: #f5576c; flex-shrink: 0; }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; max-height: 500px; }

@media (max-width: 576px) {
    .hero-title { font-size: 1.5rem; }
    .hero-icon { width: 64px; height: 64px; font-size: 1.8rem; }


    .scratch-card-wrapper { height: 200px; }
    .reveal-icon { width: 60px; height: 60px; font-size: 1.5rem; }
    .reveal-title { font-size: 1rem; }
    .reveal-value { font-size: 1.2rem; }

    .result-icon { width: 80px; height: 80px; font-size: 2rem; }
    .result-title { font-size: 1.3rem; }
}

/* ==========================================
   🎰 СЕТКА СТАТИСТИКИ В HERO
   ========================================== */
.hero-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    max-width: 360px;
    margin: 0 auto;
}

.hero-stat-card {
    padding: 14px 12px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    position: relative;
    transition: all 0.3s ease;
}

.hero-stat-card.balance {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.25) 0%, rgba(255, 152, 0, 0.15) 100%);
    border-color: rgba(255, 215, 0, 0.4);
}

.hero-stat-card.cost {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
    border-color: rgba(255, 255, 255, 0.3);
}

.hero-stat-card.balance.insufficient {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.25) 0%, rgba(200, 35, 51, 0.15) 100%);
    border-color: rgba(220, 53, 69, 0.5);
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.hero-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.hero-stat-icon.cost-icon {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
}

.hero-stat-info { text-align: center; }

.hero-stat-value {
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1.1;
    color: white;
    margin-bottom: 2px;
}

.hero-stat-label {
    font-size: 0.7rem;
    opacity: 0.85;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.hero-stat-hint {
    position: absolute;
    top: 6px;
    right: 6px;
    padding: 2px 6px;
    background: rgba(220, 53, 69, 0.9);
    border-radius: 8px;
    font-size: 0.6rem;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    gap: 3px;
}

/* ==========================================
   ⚠️ ПРЕДУПРЕЖДЕНИЕ О НЕХВАТКЕ КЭШБЭКА
   ========================================== */
.cashback-warning {
    margin-top: 16px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.08) 0%, rgba(255, 152, 0, 0.05) 100%);
    border: 1.5px solid rgba(220, 53, 69, 0.3);
    border-radius: 16px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
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

/* Адаптив */
@media (max-width: 400px) {
    .hero-stats-grid {
        gap: 8px;
    }
    .hero-stat-card {
        padding: 10px 8px;
    }
    .hero-stat-value {
        font-size: 1rem;
    }
}
</style>
