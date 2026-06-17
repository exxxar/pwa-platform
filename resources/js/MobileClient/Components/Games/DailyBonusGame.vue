<template>
    <div class="daily-bonus-page">

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
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">✨</div>
                    <div class="hero-sparkle sparkle-2">🎁</div>
                </div>
                <h1 class="hero-title">Ежедневный бонус</h1>
                <p class="hero-subtitle">Заходи каждый день и открывай сундучки!</p>

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
            <!-- СЕРИЯ ДНЕЙ (ПРОГРЕСС) -->
            <!-- ========================================== -->
            <div class="streak-section">
                <div class="streak-header">
                    <h6 class="streak-title">Прогресс серии</h6>
                    <span class="streak-badge" v-if="currentStreak >= 7">
                        <i class="fa-solid fa-crown"></i>
                        <span>Максимум!</span>
                    </span>
                </div>
                <div class="streak-days">
                    <div
                        v-for="day in 7"
                        :key="day"
                        class="streak-day"
                        :class="{
                            'is-completed': day <= currentStreak,
                            'is-current': day === currentStreak + 1 && canOpenToday,
                            'is-jackpot': day === 7
                        }"
                    >
                        <div class="day-circle">
                            <i v-if="day <= currentStreak" class="fa-solid fa-check"></i>
                            <span v-else>{{ day }}</span>
                        </div>
                        <div class="day-reward">
                            <span v-if="day === 7">👑</span>
                            <span v-else-if="day % 3 === 0">🎁</span>
                            <span v-else>💰</span>
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

                            <!-- 🎁 НОВЫЙ ЭЛЕМЕНТ: Приз, который вылетает при открытии -->
                            <div class="chest-prize-reveal" :class="{ 'is-visible': isOpening || todayOpened }">
                                <i class="fa-solid fa-trophy"></i>
                                <!-- Если хотите монетки, замените fa-trophy на fa-coins -->
                            </div>

                            <!-- Крышка -->
                            <div class="chest-lid" :class="{ 'is-open': isOpening || todayOpened }">
                                <div class="chest-lock">
                                    <i class="fa-solid fa-lock" v-if="!isOpening && !todayOpened"></i>
                                    <i class="fa-solid fa-lock-open" v-else></i>
                                </div>
                            </div>

                            <!-- Основание -->
                            <div class="chest-base">
                                <div class="chest-glow" v-if="canOpenToday || todayOpened"></div>
                                <div class="chest-sparkles" v-if="todayOpened">
                                    <span v-for="i in 8" :key="i" class="sparkle" :style="chestSparkleStyle(i)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Статус сундучка -->
                    <div class="chest-status">
                        <div v-if="todayOpened && todayPrize" class="prize-reveal">
                            <div class="prize-icon" :class="'type-' + todayPrize.type">
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

                <!-- Кнопка обналичивания -->
                <button
                    v-if="todayOpened && todayPrize && todayPrize.type !== 'bonus' && !todayPrize.claimed"
                    class="claim-btn"
                    @click="claimPrize"
                >
                    <i class="fa-solid fa-gift"></i>
                    <span>Забрать приз сейчас</span>
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
                            <div class="date-day">{{ formatDate(prize.date).day }}</div>
                            <div class="date-month">{{ formatDate(prize.date).month }}</div>
                        </div>
                        <div class="history-icon" :class="'type-' + prize.type">
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
                            <button class="admin-btn" @click="adminResetStreak">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Сбросить серию</span>
                            </button>
                            <button class="admin-btn" @click="adminResetToday">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <span>Сбросить сегодня</span>
                            </button>
                            <button class="admin-btn" @click="adminAddStreak">
                                <i class="fa-solid fa-plus"></i>
                                <span>+1 день серии</span>
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
        <!-- МОДАЛКА: ПРИЗ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showPrizeModal && todayPrize" class="modal-overlay" @click.self="closePrizeModal">
                <div class="modal-container prize-modal">
                    <div class="prize-confetti">
                        <span v-for="i in 50" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                    </div>
                    <div class="prize-content">
                        <div class="prize-rarity-badge" :class="'type-' + todayPrize.type">
                            {{ prizeTypeText(todayPrize.type) }}
                        </div>
                        <div class="prize-icon-wrapper">
                            <div class="prize-icon-large" :class="'type-' + todayPrize.type">
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

    </div>
</template>

<script>
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

export default {
    name: "DailyBonusGame",

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            // Состояние игры
            currentStreak: 0,
            bestStreak: 0,
            lastOpenDate: null,
            todayOpened: false,
            todayPrize: null,
            isOpening: false,

            // UI
            showAdmin: false,
            showPrizeModal: false,
            timeUntilEndOfDay: '',
            timerInterval: null,

            // История
            prizeHistory: [],

            // Таблица призов по дням серии
            dailyRewards: [
                // День 1
                { type: 'bonus', min: 5, max: 15, icon: 'fa-solid fa-coins', title: 'Бонусы' },
                // День 2
                { type: 'bonus', min: 15, max: 30, icon: 'fa-solid fa-coins', title: 'Бонусы' },
                // День 3
                { type: 'discount', min: 5, max: 15, icon: 'fa-solid fa-percent', title: 'Скидка на заказ' },
                // День 4
                { type: 'bonus', min: 30, max: 70, icon: 'fa-solid fa-gem', title: 'Бонусы' },
                // День 5
                { type: 'bonus', min: 70, max: 150, icon: 'fa-solid fa-gem', title: 'Бонусы' },
                // День 6
                { type: 'product', products: ['Пицца Маргарита', 'Ролл Филадельфия', 'Гонконгская вафля'], icon: 'fa-solid fa-gift', title: 'Ценный приз' },
                // День 7 (ДЖЕКПОТ)
                { type: 'jackpot', options: [
                        { type: 'bonus', value: 500, icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ! 500 бонусов' },
                        { type: 'discount', value: 50, icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ! Скидка 50%' }
                    ], icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ' }
            ],
        };
    },

    computed: {
        isAdmin() {
            const user = window.TenantUser;
            return user?.role === 'admin' || user?.is_admin === true;
        },

        canOpenToday() {
            if (this.todayOpened) return false;

            const today = this.getTodayString();
            if (this.lastOpenDate === today) return false;

            // Проверяем, не пропустил ли день
            if (this.lastOpenDate) {
                const lastDate = new Date(this.lastOpenDate);
                const todayDate = new Date(today);
                const diffDays = Math.floor((todayDate - lastDate) / (1000 * 60 * 60 * 24));

                if (diffDays > 1) {
                    // Пропустил день — серия сбрасывается
                    this.currentStreak = 0;
                    this.saveState();
                }
            }

            return true;
        },

        currentDayReward() {
            const dayIndex = Math.min(this.currentStreak, 6); // 0-6
            return this.dailyRewards[dayIndex];
        },
    },

    async mounted() {
        await this.loadState();
        this.checkTodayStatus();
        this.startTimer();
    },

    beforeUnmount() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
    },

    methods: {
        // ==========================================
        // ОСНОВНАЯ ЛОГИКА
        // ==========================================
        getTodayString() {
            const today = new Date();
            return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        },

        async loadState() {
            try {
                // TODO: Замени на реальный API
                // const response = await this.basketStore.loadDailyBonusState();
                // this.currentStreak = response.current_streak || 0;
                // this.bestStreak = response.best_streak || 0;
                // this.lastOpenDate = response.last_open_date || null;
                // this.prizeHistory = response.prize_history || [];

                // Загрузка из localStorage (для демо)
                const saved = localStorage.getItem('dailyBonusState');
                if (saved) {
                    const state = JSON.parse(saved);
                    this.currentStreak = state.currentStreak || 0;
                    this.bestStreak = state.bestStreak || 0;
                    this.lastOpenDate = state.lastOpenDate || null;
                    this.prizeHistory = state.prizeHistory || [];
                }
            } catch (error) {
                console.error('Ошибка загрузки состояния:', error);
            }
        },

        saveState() {
            const state = {
                currentStreak: this.currentStreak,
                bestStreak: this.bestStreak,
                lastOpenDate: this.lastOpenDate,
                prizeHistory: this.prizeHistory,
            };
            localStorage.setItem('dailyBonusState', JSON.stringify(state));

            // TODO: Замени на реальный API
            // await this.basketStore.saveDailyBonusState(state);
        },

        checkTodayStatus() {
            const today = this.getTodayString();

            // Проверяем, открывал ли сегодня
            const todayPrize = this.prizeHistory.find(p => p.date === today);
            if (todayPrize) {
                this.todayOpened = true;
                this.todayPrize = todayPrize;
            }

            // Проверяем просроченные призы
            this.prizeHistory.forEach(prize => {
                if (prize.type !== 'bonus' && !prize.claimed && !prize.expired) {
                    if (prize.date < today) {
                        prize.expired = true;
                    }
                }
            });

            this.saveState();
        },

        async openChest() {
            if (!this.canOpenToday || this.isOpening) return;

            this.isOpening = true;

            // Генерируем приз
            await new Promise(resolve => setTimeout(resolve, 800)); // Анимация открытия

            const prize = this.generatePrize();
            this.todayPrize = prize;
            this.todayOpened = true;

            // Обновляем серию
            this.currentStreak++;
            if (this.currentStreak > this.bestStreak) {
                this.bestStreak = this.currentStreak;
            }
            this.lastOpenDate = this.getTodayString();

            // Добавляем в историю
            const historyItem = {
                id: Date.now(),
                date: this.getTodayString(),
                ...prize,
                claimed: prize.type === 'bonus', // Бонусы начисляются сразу
                expired: false,
            };
            this.prizeHistory.unshift(historyItem);

            // Начисляем бонусы если это бонус
            if (prize.type === 'bonus') {
                // TODO: Замени на реальный API
                // await this.basketStore.creditBonus({ amount: prize.value });
            }

            this.saveState();

            // Показываем модалку с призом
            setTimeout(() => {
                this.isOpening = false;
                this.showPrizeModal = true;
            }, 500);
        },

        generatePrize() {
            const reward = this.currentDayReward;
            const prize = {
                type: reward.type,
                icon: reward.icon,
            };

            if (reward.type === 'bonus') {
                prize.value = Math.floor(Math.random() * (reward.max - reward.min + 1)) + reward.min;
                prize.title = `${prize.value} бонусов`;
            } else if (reward.type === 'discount') {
                prize.value = Math.floor(Math.random() * (reward.max - reward.min + 1)) + reward.min;
                prize.title = `Скидка ${prize.value}%`;
            } else if (reward.type === 'product') {
                const product = reward.products[Math.floor(Math.random() * reward.products.length)];
                prize.productName = product;
                prize.title = product;
            } else if (reward.type === 'jackpot') {
                const option = reward.options[Math.floor(Math.random() * reward.options.length)];
                prize.type = option.type;
                prize.value = option.value;
                prize.icon = option.icon;
                prize.title = option.title;
            }

            return prize;
        },

        async claimPrize() {
            if (!this.todayPrize || this.todayPrize.claimed) return;

            // TODO: Замени на реальный API
            // await this.basketStore.claimDailyPrize({ prizeId: this.todayPrize.id });

            this.todayPrize.claimed = true;

            // Обновляем в истории
            const historyItem = this.prizeHistory.find(p => p.date === this.getTodayString());
            if (historyItem) {
                historyItem.claimed = true;
            }

            this.saveState();

            this.$notify?.({
                title: 'Приз обналичен!',
                text: `${this.todayPrize.title} добавлен в ваш заказ`,
                type: 'success',
            });
        },

        // ==========================================
        // ТАЙМЕР
        // ==========================================
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

        // ==========================================
        // ФОРМАТИРОВАНИЕ
        // ==========================================
        formatPrizeValue(prize) {
            if (!prize) return '';
            if (prize.type === 'bonus') return `+${prize.value} бонусов`;
            if (prize.type === 'discount') return `−${prize.value}%`;
            if (prize.type === 'product') return prize.productName;
            return prize.title;
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
            return {
                day: date.getDate(),
                month: months[date.getMonth()],
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

        // ==========================================
        // UI МЕТОДЫ
        // ==========================================
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

        // ==========================================
        // АДМИН-ФУНКЦИИ
        // ==========================================
        adminResetStreak() {
            if (!confirm('Сбросить серию дней?')) return;
            this.currentStreak = 0;
            this.saveState();
            this.$notify?.({ title: 'Админ', text: 'Серия сброшена', type: 'success' });
        },

        adminResetToday() {
            if (!confirm('Сбросить сегодняшний сундучок?')) return;
            this.todayOpened = false;
            this.todayPrize = null;
            this.lastOpenDate = null;
            const today = this.getTodayString();
            this.prizeHistory = this.prizeHistory.filter(p => p.date !== today);
            this.saveState();
            this.$notify?.({ title: 'Админ', text: 'Сегодня сброшен', type: 'success' });
        },

        adminAddStreak() {
            this.currentStreak++;
            if (this.currentStreak > this.bestStreak) {
                this.bestStreak = this.currentStreak;
            }
            this.saveState();
            this.$notify?.({ title: 'Админ', text: '+1 день серии', type: 'success' });
        },

        adminClearHistory() {
            if (!confirm('Очистить всю историю призов?')) return;
            this.prizeHistory = [];
            this.saveState();
            this.$notify?.({ title: 'Админ', text: 'История очищена', type: 'success' });
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
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.2) 0%, transparent 50%),
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

.sparkle-1 { top: -10px; right: -10px; animation-delay: 0s; }
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
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
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
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-3deg); }
    75% { transform: rotate(3deg); }
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
    z-index: 2;
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
}

.chest-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at center, rgba(255, 215, 0, 0.6) 0%, transparent 70%);
    animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
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
    0% { opacity: 1; transform: translate(0, 0) scale(1); }
    100% { opacity: 0; transform: translate(var(--x), var(--y)) scale(0); }
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

.prize-icon.type-bonus { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }
.prize-icon.type-discount { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }
.prize-icon.type-product { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.prize-icon.type-jackpot { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); }

.prize-info { text-align: left; }

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
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-15deg); }
    75% { transform: rotate(15deg); }
}

.already-opened i {
    color: #198754;
}

/* Таймер */
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

.timer-info { flex: 1; }

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

/* Кнопка обналичивания */
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

.history-icon.type-bonus { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }
.history-icon.type-discount { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }
.history-icon.type-product { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.history-icon.type-jackpot { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); }

.history-info { flex: 1; min-width: 0; }

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
   АДМИН-ПАНЕЛЬ
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
    transition: all 0.2s ease;
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
    flex-shrink: 0;
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
    transition: all 0.2s ease;
    color: var(--bs-body-color);
}

.admin-btn:hover {
    border-color: #fa709a;
    background: rgba(250, 112, 154, 0.05);
}

.admin-btn i { font-size: 1.2rem; color: #fa709a; }
.admin-btn span { font-size: 0.8rem; font-weight: 600; }

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
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.prize-modal { position: relative; overflow: visible; }

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
    0% { transform: translateY(-50px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(600px) rotate(720deg); opacity: 0; }
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

.prize-rarity-badge.type-bonus {
    background: rgba(255, 215, 0, 0.2);
    color: #b8860b;
    border: 1px solid rgba(255, 215, 0, 0.3);
}

.prize-rarity-badge.type-discount {
    background: rgba(255, 107, 107, 0.2);
    color: #ff6b6b;
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.prize-rarity-badge.type-product {
    background: rgba(79, 172, 254, 0.2);
    color: #4facfe;
    border: 1px solid rgba(79, 172, 254, 0.3);
}

.prize-rarity-badge.type-jackpot {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(255, 152, 0, 0.3) 100%);
    color: #b8860b;
    border: 1px solid rgba(255, 215, 0, 0.4);
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
    0% { transform: scale(0) rotate(-180deg); }
    70% { transform: scale(1.1) rotate(10deg); }
    100% { transform: scale(1) rotate(0deg); }
}

.prize-icon-large.type-bonus { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }
.prize-icon-large.type-discount { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }
.prize-icon-large.type-product { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.prize-icon-large.type-jackpot { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }

.prize-glow {
    position: absolute;
    inset: -20px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(250, 112, 154, 0.3) 0%, transparent 70%);
    animation: glowPulse 2s ease-in-out infinite;
}

@keyframes glowPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0.8; }
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
    .stat-value { font-size: 1.1rem; }

    .chest-body { width: 150px; height: 130px; }
    .chest-lid { height: 65px; }
    .chest-base { height: 75px; }

    .day-circle { width: 30px; height: 30px; font-size: 0.75rem; }
    .day-reward { font-size: 0.85rem; }

    .prize-icon-large { width: 100px; height: 100px; font-size: 2.5rem; }
    .prize-title-large { font-size: 1.3rem; }
    .prize-value-large { font-size: 1.1rem; }
}

/* ==========================================
   АНИМАЦИЯ ПРИЗА ВНУТРИ СУНДУЧКА
   ========================================== */
.chest-prize-reveal {
    position: absolute;
    top: 35%; /* Позиция внутри сундука */
    left: 50%;
    transform: translate(-50%, 30px) scale(0.3); /* Спрятан внизу и уменьшен */
    font-size: 3rem;
    color: #ffd700; /* Золотой цвет */
    opacity: 0;
    z-index: 2; /* Выше основания, но ниже закрытой крышки (у неё z-index: 10) */
    transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); /* Пружинящая анимация */
    pointer-events: none;
    filter: drop-shadow(0 0 15px rgba(255, 215, 0, 0.8)); /* Свечение приза */
}

/* Состояние, когда сундук открыт: приз вылетает вверх и увеличивается */
.chest-prize-reveal.is-visible {
    transform: translate(-50%, -60px) scale(1.2);
    opacity: 1;
}

/* Убедимся, что крышка перекрывает приз, когда сундук закрыт */
.chest-lid {
    z-index: 10;
    /* ... ваши существующие стили для .chest-lid ... */
}

.chest-base {
    z-index: 5;
    /* ... ваши существующие стили для .chest-base ... */
}
</style>
