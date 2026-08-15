<template>
    <div class="daily-bonus-page">

        <!-- ========================================== -->
        <!-- СОСТОЯНИЕ: ИГРА НЕДОСТУПНА -->
        <!-- ========================================== -->
        <div v-if="!gameAvailable" class="game-unavailable">
            <div class="unavailable-content">
                <div class="unavailable-icon">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h2 class="unavailable-title">Ежедневный бонус недоступен</h2>
                <p class="unavailable-text">Игра временно отключена</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ОСНОВНОЙ КОНТЕНТ -->
        <!-- ========================================== -->
        <template v-else>
            <!-- HERO СЕКЦИЯ -->
            <div class="game-hero">
                <div class="hero-background"></div>
                <div class="hero-particles">
                    <span v-for="i in 20" :key="i" class="particle" :style="particleStyle(i)"></span>
                </div>
                <div class="hero-content">
                    <div class="hero-icon-wrapper">
                        <div class="hero-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div class="hero-sparkle sparkle-1">✨</div>
                        <div class="hero-sparkle sparkle-2">🎁</div>
                    </div>
                    <h1 class="hero-title">{{ gameSettings.title || 'Ежедневный бонус' }}</h1>
                    <p class="hero-subtitle">{{
                            gameSettings.subtitle || 'Заходи каждый день и открывай сундучки!'
                        }}</p>

                    <div class="hero-stats">
                        <div class="stat-block">
                            <div class="stat-icon">
                                <i class="fa-solid fa-fire"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ currentStreak }}</div>
                                <div class="stat-label">Дней подряд</div>
                            </div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-block">
                            <div class="stat-icon trophy-icon">
                                <i class="fa-solid fa-trophy"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ bestStreak }}</div>
                                <div class="stat-label">Лучшая серия</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="game-content">

                <!-- ========================================== -->
                <!-- ПРАВИЛА ИГРЫ (из настроек) -->
                <!-- ========================================== -->
                <div v-if="gameSettings.rules" class="rules-section">
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
                            <div class="rules-text">{{ gameSettings.rules }}</div>
                        </div>
                    </transition>
                </div>

                <!-- ========================================== -->
                <!-- СЕРИЯ ДНЕЙ (ПРОГРЕСС) -->
                <!-- ========================================== -->
                <div class="streak-section">
                    <div class="streak-header">
                        <h6 class="streak-title">Прогресс серии</h6>
                        <span class="streak-badge" v-if="currentStreak >= gameSettings.streak_days">
                            <i class="fa-solid fa-crown"></i>
                            <span>Максимум!</span>
                        </span>
                    </div>
                    <div class="streak-days">
                        <div
                            v-for="day in gameSettings.streak_days"
                            :key="day"
                            class="streak-day"
                            :class="{
                                'is-completed': day <= currentStreak,
                                'is-current': day === currentStreak + 1 && canOpenToday,
                                'is-jackpot': day === gameSettings.streak_days
                            }"
                        >
                            <div class="day-circle">
                                <i v-if="day <= currentStreak" class="fa-solid fa-check"></i>
                                <span v-else>{{ day }}</span>
                            </div>
                            <div class="day-reward">
                                <i v-if="getRewardForDay(day)" :class="getRewardForDay(day).icon"></i>
                                <span v-else>🎁</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- СУНДУЧОК ДНЯ -->
                <!-- ========================================== -->
                <div class="chest-section">
                    <div class="chest-wrapper" :class="{ 'can-open': canOpenToday, 'opened': todayOpened }">

                        <!-- Сундучок -->
                        <div class="chest" @click="openChest" :class="{ 'is-shaking': canOpenToday && !isOpening }">
                            <div class="chest-body">
                                <div class="chest-prize-reveal" :class="{ 'is-visible': isOpening || todayOpened }">
                                    <i class="fa-solid fa-trophy"></i>
                                </div>

                                <div class="chest-lid" :class="{ 'is-open': isOpening || todayOpened }">
                                    <div class="chest-lock">
                                        <i class="fa-solid fa-lock" v-if="!isOpening && !todayOpened"></i>
                                        <i class="fa-solid fa-lock-open" v-else></i>
                                    </div>
                                </div>

                                <div class="chest-base">
                                    <div class="chest-glow" v-if="canOpenToday || todayOpened"></div>
                                    <div class="chest-sparkles" v-if="todayOpened">
                                        <span v-for="i in 8" :key="i" class="sparkle"
                                              :style="chestSparkleStyle(i)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Статус сундучка -->
                        <div class="chest-status">
                            <div v-if="todayOpened && todayPrize" class="prize-reveal">
                                <div class="prize-icon" :style="{ background: getTypeGradient(todayPrize.type) }">
                                    <i :class="todayPrize.icon"></i>
                                </div>
                                <div class="prize-info">
                                    <div class="prize-title">{{ todayPrize.title }}</div>
                                    <div class="prize-value">{{ formatPrizeValue(todayPrize) }}</div>
                                </div>
                            </div>
                            <div v-else-if="canOpenToday" class="open-hint">
                                <i class="fa-solid fa-hand-pointer"></i>
                                <span>Нажми, чтобы открыть!</span>
                            </div>
                            <div v-else class="already-opened">
                                <i class="fa-solid fa-check-circle"></i>
                                <span>Сундучок открыт! Возвращайся завтра</span>
                            </div>
                        </div>

                    </div>

                    <!-- Таймер до конца дня -->
                    <div v-if="todayOpened && todayPrize && todayPrize.type !== 'bonus'" class="claim-timer">
                        <div class="timer-icon">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                        <div class="timer-info">
                            <div class="timer-label">Успей обналичить до конца дня!</div>
                            <div class="timer-value">{{ timeUntilEndOfDay }}</div>
                        </div>
                    </div>

                    <button
                        v-if="todayOpened && todayPrize && todayPrize.type !== 'bonus' && !todayPrize.claimed"
                        class="claim-btn"
                        :disabled="isClaiming"
                        @click="claimPrize"
                    >
                        <i :class="isClaiming ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-gift'"></i>
                        <span>{{ isClaiming ? 'Обналичиваем...' : 'Забрать приз сейчас' }}</span>
                    </button>

                    <div v-if="todayOpened && todayPrize?.claimed" class="claimed-badge">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Приз зачислен на ваш счёт!</span>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- ИСТОРИЯ ПРИЗОВ -->
                <!-- ========================================== -->
                <div class="history-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h6 class="section-title">История призов</h6>
                            <p class="section-subtitle">Ваши награды за последние дни</p>
                        </div>
                    </div>

                    <div v-if="prizeHistory.length === 0" class="empty-history">
                        <div class="empty-icon">📭</div>
                        <p>Пока нет выигранных призов</p>
                        <p class="empty-hint">Откройте первый сундучок!</p>
                    </div>

                    <div v-else class="history-list">
                        <div
                            v-for="prize in prizeHistory"
                            :key="prize.id"
                            class="history-item"
                            :class="{
                                'is-expired': prize.expired,
                                'is-claimed': prize.claimed,
                                'is-bonus': prize.type === 'bonus'
                            }"
                        >
                            <div class="history-date">
                                <div class="date-day">{{ prize.date ? formatDate(prize.date).day : '—' }}</div>
                                <div class="date-month">{{ prize.date ? formatDate(prize.date).month : '—' }}</div>
                            </div>
                            <div class="history-icon" :style="{ background: getTypeGradient(prize.type) }">
                                <i :class="prize.icon"></i>
                            </div>
                            <div class="history-info">
                                <div class="history-title">{{ prize.title }}</div>
                                <div class="history-value">{{ formatPrizeValue(prize) }}</div>
                            </div>
                            <div class="history-status">
                                <span v-if="prize.type === 'bonus'" class="status-badge bonus">
                                    <i class="fa-solid fa-coins"></i>
                                    <span>Начислено</span>
                                </span>
                                <span v-else-if="prize.claimed" class="status-badge claimed">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Получен</span>
                                </span>
                                <span v-else-if="prize.expired" class="status-badge expired">
                                    <i class="fa-solid fa-fire"></i>
                                    <span>Сгорел</span>
                                </span>
                                <span v-else class="status-badge pending">
                                    <i class="fa-solid fa-hourglass-half"></i>
                                    <span>Активен</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- МОДАЛКА: ПРИЗ -->
            <!-- ========================================== -->
            <transition name="modal-fade">
                <div v-if="showPrizeModal && todayPrize" class="modal-overlay" @click.self="closePrizeModal">
                    <div class="modal-container prize-modal">
                        <div class="prize-confetti">
                            <span v-for="i in 50" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                        </div>
                        <div class="prize-content">
                            <div class="prize-rarity-badge"
                                 :style="{ background: getTypeColor(todayPrize.type) + '20', color: getTypeColor(todayPrize.type), border: `1px solid ${getTypeColor(todayPrize.type)}40` }">
                                {{ prizeTypeText(todayPrize.type) }}
                            </div>
                            <div class="prize-icon-wrapper">
                                <div class="prize-icon-large" :style="{ background: getTypeGradient(todayPrize.type) }">
                                    <i :class="todayPrize.icon"></i>
                                </div>
                                <div class="prize-glow"></div>
                            </div>
                            <h3 class="prize-title-large">{{ todayPrize.title }}</h3>
                            <p class="prize-value-large">{{ formatPrizeValue(todayPrize) }}</p>

                            <div v-if="todayPrize.type !== 'bonus'" class="prize-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Обналичьте приз до конца дня, иначе он сгорит!</span>
                            </div>

                            <button class="prize-btn" @click="closePrizeModal">
                                <i class="fa-solid fa-check"></i>
                                <span>{{ todayPrize.type === 'bonus' ? 'Отлично!' : 'Понятно' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </template>

    </div>
</template>

<script>
export default {
    name: "DailyBonusGame",

    data() {
        return {
            // Настройки игры (загружаются с бэкенда)
            gameSettings: {
                can_play: true,
                streak_days: 7,
                streak_reset_days: 1,
                title: 'Ежедневный бонус',
                subtitle: 'Заходи каждый день и открывай сундучки!',
                rules: '',
                type_colors: {
                    bonus: '#ffd700',
                    discount: '#ff6b6b',
                    product: '#4facfe',
                    jackpot: '#ffd700'
                },
                rewards: []
            },

            // Состояние игры
            gameAvailable: false,
            currentStreak: 0,
            bestStreak: 0,
            lastOpenDate: null,
            todayOpened: false,
            todayPrize: null,
            isOpening: false,

            // UI
            showRules: false,
            showPrizeModal: false,
            timeUntilEndOfDay: '',
            timerInterval: null,

            // История
            prizeHistory: [],

            isClaiming: false,
        };
    },

    computed: {


        currentDayReward() {
            const dayIndex = Math.min(this.currentStreak, this.gameSettings.streak_days - 1);
            return this.gameSettings.rewards[dayIndex] || null;
        },
    },

    async mounted() {
        await this.loadGameSettings();
        await this.loadUserState();

        if (this.gameAvailable) {
            this.checkTodayStatus();
            this.startTimer();
        }
    },

    beforeUnmount() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
    },

    methods: {

        canOpenToday() {
            if (this.todayOpened) return false;

            const today = this.getTodayString();
            if (this.lastOpenDate === today) return false;

            return true;
        },
        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        async loadGameSettings() {
            try {
                const response = await axios.get('/daily-bonus/settings');

                if (response.data && response.data.daily_bonus) {
                    this.gameSettings = {...this.gameSettings, ...response.data.daily_bonus};
                }

                this.gameAvailable = this.gameSettings.can_play;
            } catch (error) {
                console.error('Ошибка загрузки настроек:', error);
                this.gameAvailable = true;
                this.gameSettings.rewards = this.getDefaultRewards();
            }
        },
        async loadUserState() {
            try {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                const response = await axios.get('/daily-bonus/state', {
                    params: {timezone}
                });

                if (response.data?.success) {
                    this.currentStreak = response.data.current_streak || 0;
                    this.bestStreak = response.data.best_streak || 0;
                    this.lastOpenDate = response.data.last_open_date || null;
                    this.todayOpened = response.data.today_opened || false;

                    // 🆕 Нормализуем историю — гарантируем наличие date и id
                    this.prizeHistory = (response.data.prize_history || []).map((prize, index) => ({
                        ...prize,
                        id: prize.id || `fallback_${index}_${Date.now()}`,
                        date: prize.date || prize.opened_at?.split('T')[0] || this.getTodayString(),
                    }));

                    // Восстанавливаем pending_prize (если есть неоткрытый)
                    if (response.data.pending_prize) {
                        this.todayPrize = {
                            ...response.data.pending_prize,
                            date: response.data.pending_prize.date || this.getTodayString(),
                        };
                        this.todayOpened = true;
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки состояния:', error);
            }
        },
        getDefaultRewards() {
            return [
                {type: 'bonus', min: 5, max: 15, icon: 'fa-solid fa-coins', title: 'Бонусы'},
                {type: 'bonus', min: 15, max: 30, icon: 'fa-solid fa-coins', title: 'Бонусы'},
                {type: 'discount', min: 5, max: 15, icon: 'fa-solid fa-percent', title: 'Скидка на заказ'},
                {type: 'bonus', min: 30, max: 70, icon: 'fa-solid fa-gem', title: 'Бонусы'},
                {type: 'bonus', min: 70, max: 150, icon: 'fa-solid fa-gem', title: 'Бонусы'},
                {type: 'product', products: ['Пицца Маргарита'], icon: 'fa-solid fa-gift', title: 'Ценный приз'},
                {
                    type: 'jackpot',
                    options: [
                        {type: 'bonus', value: 500, icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ! 500 бонусов'}
                    ],
                    icon: 'fa-solid fa-crown',
                    title: 'ДЖЕКПОТ'
                }
            ];
        },

        // ==========================================
        // ЛОГИКА ИГРЫ
        // ==========================================
        getTodayString() {
            const today = new Date();
            return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        },


        checkTodayStatus() {
            const today = this.getTodayString();

            // Ищем сегодняшний приз в истории
            const todayPrize = this.prizeHistory.find(p => p.date === today);
            if (todayPrize) {
                this.todayOpened = true;
                this.todayPrize = todayPrize;
            }

            // Проверяем просроченные призы
            this.prizeHistory.forEach(prize => {
                // 🆕 Пропускаем призы без валидной даты
                if (!prize.date) return;

                if (prize.type !== 'bonus' && !prize.claimed && !prize.expired) {
                    if (prize.date < today) {
                        prize.expired = true;
                    }
                }
            });
        },

        getRewardForDay(day) {
            return this.gameSettings.rewards[day - 1] || null;
        },

        async openChest() {
            // 🛡️ Защита от множественных кликов
            if (!this.canOpenToday || this.isOpening) return;

            this.isOpening = true;

            try {
                // Ждём анимацию
                await new Promise(resolve => setTimeout(resolve, 800));

                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                const response = await axios.post('/daily-bonus/open', {
                    streak_day: this.currentStreak + 1,
                    timezone: timezone,
                });

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка открытия');
                }

                const prize = response.data.prize;

                // Обновляем локальное состояние
                this.todayPrize = prize;
                this.todayOpened = true;
                this.currentStreak = response.data.current_streak;
                this.bestStreak = response.data.best_streak;
                this.lastOpenDate = response.data.server_date;

                // Бонусы уже начислены на сервере - обновляем глобальный баланс
                if (prize.type === 'bonus' && response.data.balance !== undefined) {
                    if (window.TenantUser) {
                        window.TenantUser.cashback_balance = response.data.balance;
                    }
                }

                // Добавляем в историю (локально, для отображения)
                this.prizeHistory.unshift({
                    ...prize,
                    date: prize.date || this.getTodayString(), // 🆕 Гарантия даты
                    expired: false,
                });

                // Ограничиваем локальную историю
                if (this.prizeHistory.length > 30) {
                    this.prizeHistory = this.prizeHistory.slice(0, 30);
                }

                this.$notify?.({
                    title: prize.type === 'bonus' ? '🎉 Бонусы начислены!' : '🎁 Приз получен!',
                    text: prize.title,
                    type: 'success',
                });

                setTimeout(() => {
                    this.isOpening = false;
                    this.showPrizeModal = true;
                }, 500);

            } catch (error) {
                console.error('Ошибка открытия сундука:', error);

                // Если 403 — сундук уже открыт
                if (error.response?.status === 403) {
                    this.todayOpened = true;
                    this.$notify?.({
                        title: 'Уже открыто',
                        text: error.response.data?.message || 'Сундучок уже открыт сегодня',
                        type: 'warning',
                    });
                } else {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: error.response?.data?.message || 'Не удалось открыть сундучок',
                        type: 'error',
                    });
                }

                this.isOpening = false;
            }
        },


        async claimPrize() {
            if (!this.todayPrize || this.todayPrize.claimed || this.isClaiming) return;

            this.isClaiming = true;

            try {
                const response = await axios.post('/daily-bonus/claim', {
                    prize_id: this.todayPrize.id,  // ID пришёл с сервера!
                });

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка обналичивания');
                }

                // Обновляем локальный приз
                this.todayPrize.claimed = true;

                // Обновляем в истории
                const historyItem = this.prizeHistory.find(p => p.id === this.todayPrize.id);
                if (historyItem) {
                    historyItem.claimed = true;
                }

                this.$notify?.({
                    title: 'Приз обналичен!',
                    text: `${this.todayPrize.title} добавлен`,
                    type: 'success',
                });

            } catch (error) {
                console.error('Ошибка обналичивания:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось обналичить приз',
                    type: 'error',
                });
            } finally {
                this.isClaiming = false;
            }
        },

        // ==========================================
        // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
        // ==========================================
        getTypeColor(type) {
            return this.gameSettings.type_colors[type] || '#6c757d';
        },

        getTypeGradient(type) {
            const color = this.getTypeColor(type);
            return `linear-gradient(135deg, ${color} 0%, ${this.shadeColor(color, -20)} 100%)`;
        },

        shadeColor(color, percent) {
            const f = parseInt(color.slice(1), 16);
            const t = percent < 0 ? 0 : 255;
            const p = percent < 0 ? percent * -1 : percent;
            const R = f >> 16, G = f >> 8 & 0x00FF, B = f & 0x0000FF;
            return "#" + (0x1000000 + (Math.round((t - R) * p / 100) + R) * 0x10000 +
                (Math.round((t - G) * p / 100) + G) * 0x100 +
                (Math.round((t - B) * p / 100) + B)).toString(16).slice(1);
        },

        startTimer() {
            this.updateTimer();
            this.timerInterval = setInterval(() => {
                this.updateTimer();
            }, 1000);
        },

        updateTimer() {
            const now = new Date();
            const endOfDay = new Date(now);
            endOfDay.setHours(23, 59, 59, 999);

            const diff = endOfDay - now;
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            this.timeUntilEndOfDay = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },

        formatPrizeValue(prize) {
            if (!prize) return '';
            if (prize.type === 'bonus') return `+${prize.value} бонусов`;
            if (prize.type === 'discount') return `−${prize.value}%`;
            if (prize.type === 'product') return prize.productName;
            return prize.title;
        },

        formatDate(dateString) {
            const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

            // Если даты нет — возвращаем заглушку
            if (!dateString) {
                return {day: '—', month: '—'};
            }

            const date = new Date(dateString);

            // Проверяем валидность даты
            if (isNaN(date.getTime())) {
                return {day: '—', month: '—'};
            }

            return {
                day: date.getDate(),
                month: months[date.getMonth()] || '—',
            };
        },
        prizeTypeText(type) {
            const texts = {
                bonus: 'Бонусы',
                discount: 'Скидка',
                product: 'Ценный приз',
                jackpot: 'ДЖЕКПОТ!',
            };
            return texts[type] || 'Приз';
        },

        closePrizeModal() {
            this.showPrizeModal = false;
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
            const colors = ['#fa709a', '#fee140', '#ffd700', '#ff6b6b', '#4ecdc4'];
            const color = colors[Math.floor(Math.random() * colors.length)];
            return {
                background: color,
                left: `${Math.random() * 100}%`,
                animationDelay: `${Math.random() * 2}s`,
                animationDuration: `${Math.random() * 2 + 2}s`,
                transform: `rotate(${Math.random() * 360}deg)`,
            };
        },

        chestSparkleStyle(i) {
            const angle = (i / 8) * 360;
            const radius = 60;
            const x = Math.cos(angle * Math.PI / 180) * radius;
            const y = Math.sin(angle * Math.PI / 180) * radius;
            return {
                '--x': `${x}px`,
                '--y': `${y}px`,
                animationDelay: `${i * 0.1}s`,
            };
        },
    },
};
</script>

<style scoped>
.daily-bonus-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   СОСТОЯНИЕ: ИГРА НЕДОСТУПНА
   ========================================== */
.game-unavailable {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.unavailable-content {
    text-align: center;
    color: white;
    padding: 40px;
}

.unavailable-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    margin: 0 auto 24px;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.unavailable-title {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 12px;
}

.unavailable-text {
    font-size: 1.1rem;
    opacity: 0.9;
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.game-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.2) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(255, 215, 0, 0.15) 0%, transparent 50%);
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

.sparkle-1 {
    top: -10px;
    right: -10px;
    animation-delay: 0s;
}

.sparkle-2 {
    bottom: -10px;
    left: -10px;
    animation-delay: 0.7s;
}

@keyframes sparkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0.5);
    }
    50% {
        opacity: 1;
        transform: scale(1.2);
    }
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

.stat-block {
    display: flex;
    align-items: center;
    gap: 10px;
}

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

.stat-info {
    text-align: left;
}

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
.game-content {
    padding: 20px 16px;
}

/* ==========================================
   ПРАВИЛА
   ========================================== */
.rules-section {
    margin-bottom: 20px;
}

.rules-toggle {
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

.rules-toggle:hover {
    border-color: #fa709a;
}

.rules-toggle-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rules-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.rules-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.rules-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.rules-hint {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.rules-arrow {
    color: var(--bs-secondary-color);
    transition: transform 0.3s ease;
}

.rules-arrow.rotated {
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
    color: var(--bs-body-color);
    line-height: 1.6;
    white-space: pre-wrap;
}

/* ==========================================
   СЕРИЯ ДНЕЙ
   ========================================== */
.streak-section {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
}

.streak-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.streak-title {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.streak-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
}

.streak-days {
    display: flex;
    justify-content: space-between;
    gap: 8px;
}

.streak-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex: 1;
}

.day-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: 2px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    transition: all 0.3s ease;
}

.streak-day.is-completed .day-circle {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    border-color: #fa709a;
    color: white;
}

.streak-day.is-current .day-circle {
    border-color: #fa709a;
    color: #fa709a;
    animation: pulse 2s ease-in-out infinite;
}

.streak-day.is-jackpot .day-circle {
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    border-color: #ffd700;
    color: white;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.day-reward {
    font-size: 1rem;
}

/* ==========================================
   СУНДУЧОК
   ========================================== */
.chest-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    margin-bottom: 32px;
}

.chest-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.chest {
    cursor: pointer;
    transition: transform 0.2s ease;
}

.chest.can-open {
    cursor: pointer;
}

.chest.can-open:hover {
    transform: scale(1.05);
}

.chest.is-shaking {
    animation: chestShake 0.5s ease-in-out infinite;
}

@keyframes chestShake {
    0%, 100% {
        transform: rotate(0deg);
    }
    25% {
        transform: rotate(-3deg);
    }
    75% {
        transform: rotate(3deg);
    }
}

.chest-body {
    position: relative;
    width: 180px;
    height: 160px;
}

.chest-lid {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 80px;
    background: linear-gradient(180deg, #8b4513 0%, #654321 100%);
    border-radius: 20px 20px 0 0;
    border: 4px solid #5c3317;
    transform-origin: bottom;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10;
}

.chest-lid.is-open {
    transform: rotateX(-120deg);
}

.chest-lock {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #5c3317;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.chest-base {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 90px;
    background: linear-gradient(180deg, #a0522d 0%, #8b4513 100%);
    border-radius: 0 0 20px 20px;
    border: 4px solid #5c3317;
    border-top: none;
    overflow: hidden;
    z-index: 5;
}

.chest-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at center, rgba(255, 215, 0, 0.6) 0%, transparent 70%);
    animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}

.chest-sparkles {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.sparkle {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 8px;
    height: 8px;
    background: #ffd700;
    border-radius: 50%;
    animation: sparkleFloat 1.5s ease-out infinite;
    transform: translate(var(--x), var(--y));
}

@keyframes sparkleFloat {
    0% {
        opacity: 1;
        transform: translate(0, 0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate(var(--x), var(--y)) scale(0);
    }
}

.chest-prize-reveal {
    position: absolute;
    top: 35%;
    left: 50%;
    transform: translate(-50%, 30px) scale(0.3);
    font-size: 3rem;
    color: #ffd700;
    opacity: 0;
    z-index: 2;
    transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
    filter: drop-shadow(0 0 15px rgba(255, 215, 0, 0.8));
}

.chest-prize-reveal.is-visible {
    transform: translate(-50%, -60px) scale(1.2);
    opacity: 1;
}

.chest-status {
    text-align: center;
}

.prize-reveal {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
}

.prize-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
    flex-shrink: 0;
}

.prize-info {
    text-align: left;
}

.prize-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.prize-value {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

.open-hint, .already-opened {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
}

.open-hint i {
    color: #fa709a;
    animation: handWave 1.5s ease-in-out infinite;
}

@keyframes handWave {
    0%, 100% {
        transform: rotate(0deg);
    }
    25% {
        transform: rotate(-15deg);
    }
    75% {
        transform: rotate(15deg);
    }
}

.already-opened i {
    color: #198754;
}

.claim-timer {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    background: rgba(255, 107, 107, 0.08);
    border: 1px solid rgba(255, 107, 107, 0.2);
    border-radius: 14px;
    width: 100%;
    max-width: 400px;
}

.timer-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.timer-info {
    flex: 1;
}

.timer-label {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.timer-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: #ff6b6b;
    font-variant-numeric: tabular-nums;
}

.claim-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 32px;
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(250, 112, 154, 0.3);
    width: 100%;
    max-width: 400px;
}

.claim-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(250, 112, 154, 0.4);
}

.claimed-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: rgba(25, 135, 84, 0.08);
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 14px;
    color: #198754;
    font-weight: 600;
    font-size: 0.9rem;
    width: 100%;
    max-width: 400px;
}

.claimed-badge i {
    font-size: 1.2rem;
}

/* ==========================================
   ИСТОРИЯ
   ========================================== */
.history-section {
    margin-bottom: 24px;
}

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
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(250, 112, 154, 0.3);
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

.empty-hint {
    font-size: 0.85rem;
    opacity: 0.7;
}

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
    transition: all 0.2s ease;
}

.history-item.is-expired {
    opacity: 0.5;
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

.history-icon {
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

.history-info {
    flex: 1;
    min-width: 0;
}

.history-title {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.history-value {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.history-status {
    flex-shrink: 0;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-badge.bonus {
    background: rgba(255, 215, 0, 0.15);
    color: #b8860b;
}

.status-badge.claimed {
    background: rgba(25, 135, 84, 0.15);
    color: #198754;
}

.status-badge.expired {
    background: rgba(108, 117, 125, 0.15);
    color: #6c757d;
}

.status-badge.pending {
    background: rgba(79, 172, 254, 0.15);
    color: #4facfe;
}

/* ==========================================
   МОДАЛКА ПРИЗА
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
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.prize-modal {
    position: relative;
    overflow: visible;
}

.prize-confetti {
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
    0% {
        transform: translateY(-50px) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(600px) rotate(720deg);
        opacity: 0;
    }
}

.prize-content {
    padding: 32px 24px;
    text-align: center;
    position: relative;
    z-index: 1;
}

.prize-rarity-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 20px;
}

.prize-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.prize-icon-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
    position: relative;
    z-index: 1;
    animation: prizeIconPop 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes prizeIconPop {
    0% {
        transform: scale(0) rotate(-180deg);
    }
    70% {
        transform: scale(1.1) rotate(10deg);
    }
    100% {
        transform: scale(1) rotate(0deg);
    }
}

.prize-glow {
    position: absolute;
    inset: -20px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(250, 112, 154, 0.3) 0%, transparent 70%);
    animation: glowPulse 2s ease-in-out infinite;
}

@keyframes glowPulse {
    0%, 100% {
        transform: scale(1);
        opacity: 0.5;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
    }
}

.prize-title-large {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--bs-body-color);
    margin: 0 0 8px 0;
}

.prize-value-large {
    font-size: 1.3rem;
    font-weight: 700;
    color: #fa709a;
    margin-bottom: 20px;
}

.prize-warning {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(255, 107, 107, 0.08);
    border: 1px solid rgba(255, 107, 107, 0.2);
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 0.85rem;
    color: #ff6b6b;
}

.prize-warning i {
    font-size: 1rem;
    flex-shrink: 0;
}

.prize-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(250, 112, 154, 0.3);
}

.prize-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(250, 112, 154, 0.4);
}

.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}

.slide-down-enter-active, .slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from, .slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to, .slide-down-leave-from {
    opacity: 1;
    max-height: 500px;
}

@media (max-width: 576px) {
    .hero-title {
        font-size: 1.5rem;
    }

    .hero-icon {
        width: 64px;
        height: 64px;
        font-size: 1.8rem;
    }

    .hero-stats {
        gap: 12px;
        padding: 10px 16px;
    }

    .stat-value {
        font-size: 1.1rem;
    }

    .chest-body {
        width: 150px;
        height: 130px;
    }

    .chest-lid {
        height: 65px;
    }

    .chest-base {
        height: 75px;
    }

    .day-circle {
        width: 30px;
        height: 30px;
        font-size: 0.75rem;
    }

    .day-reward {
        font-size: 0.85rem;
    }

    .prize-icon-large {
        width: 100px;
        height: 100px;
        font-size: 2.5rem;
    }

    .prize-title-large {
        font-size: 1.3rem;
    }

    .prize-value-large {
        font-size: 1.1rem;
    }
}
</style>
