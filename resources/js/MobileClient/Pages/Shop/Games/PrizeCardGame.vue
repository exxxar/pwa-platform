<template>
    <div class="card-game-page">

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
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">✨</div>
                    <div class="hero-sparkle sparkle-2">✨</div>
                </div>
                <h1 class="hero-title">Карточная игра</h1>
                <p class="hero-subtitle">Выбери карту и получи приз!</p>

                <!-- 🎰 ОДИН БЛОК СТАТИСТИКИ (внутри hero-content) -->
                <div class="hero-stats-grid">
                    <div class="hero-stat-card balance" :class="{ 'insufficient': !hasEnoughCashback }">
                        <div class="hero-stat-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="hero-stat-info">
                            <div class="hero-stat-value">{{ Math.round(userBalance) }}₽</div>
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
                            <div class="hero-stat-value">{{ moveCost }}₽</div>
                            <div class="hero-stat-label">Ставка</div>
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
                            <span class="rules-title">Правила игры</span>
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
                                <div class="rule-text">Перед вами {{ totalCards }} закрытых карт — за каждой спрятан приз</div>
                            </div>
                            <div class="rule-item">
                                <div class="rule-number">2</div>
                                <div class="rule-text">Стоимость одного хода — <strong>{{ moveCost }} бонусов</strong></div>
                            </div>
                            <div class="rule-item">
                                <div class="rule-number">3</div>
                                <div class="rule-text">Призы: бонусы, товары, скидки на товары и доставку</div>
                            </div>
                            <div class="rule-item">
                                <div class="rule-number">4</div>
                                <div class="rule-text">1 попытка в сутки. Чем реже приз — тем он ценнее!</div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- 🆕 ПРЕДУПРЕЖДЕНИЕ О НЕХВАТКЕ КЭШБЭКА -->
            <transition name="slide-down">
                <div v-if="!hasEnoughCashback && !gameFinished" class="cashback-warning">
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
                            Делайте заказы для пополнения кэшбэка
                        </div>
                    </div>
                </div>
            </transition>

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
                            {{ gameFinished ? 'Игра завершена' : 'Ваш ход' }}
                        </div>
                        <div class="status-subtitle">
                            {{ gameFinished
                            ? 'Сыграйте ещё раз завтра'
                            : `Осталось попыток: ${attemptsLeft}` }}
                        </div>
                    </div>
                    <div class="status-badge" :class="{ 'finished': gameFinished }">
                        {{ attemptsLeft }}
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КНОПКА: ЧТО МОЖЕТ ВЫПАСТЬ -->
            <!-- ========================================== -->
            <button class="prizes-preview-btn" @click="openPrizesModal">
                <i class="fa-solid fa-eye"></i>
                <span>Посмотреть все призы</span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <!-- ========================================== -->
            <!-- СЕТКА КАРТОЧЕК -->
            <!-- ========================================== -->
            <div class="cards-section">
                <div class="cards-grid" :class="`grid-${gridColumns}x${gridRows}`">
                    <div
                        v-for="(card, index) in cards"
                        :key="card.id"
                        class="game-card"
                        :class="{
                            'is-flipped': card.flipped,
                            'is-selected': card.selected,
                            'is-disabled': gameFinished || card.flipped || isProcessing
                        }"
                        @click="selectCard(index)"
                    >
                        <div class="card-inner">
                            <!-- Рубашка карты -->
                            <div class="card-face card-back">
                                <div class="card-back-pattern"></div>
                                <div class="card-back-content">
                                    <i class="fa-solid fa-question"></i>
                                </div>
                                <div class="card-back-shine"></div>
                            </div>

                            <!-- Лицевая сторона -->
                            <div class="card-face card-front" :class="[
                                'rarity-' + (card.prize?.rarity || 'common'),
                                'type-' + (card.prize?.type || 'bonus')
                            ]">
                                <div class="card-rarity-indicator"></div>
                                <div class="card-prize-icon">
                                    <i :class="card.prize?.icon || 'fa-solid fa-gift'"></i>
                                </div>
                                <div class="card-prize-value">
                                    {{ formatPrizeValue(card.prize) }}
                                </div>
                                <div class="card-prize-name">
                                    {{ card.prize?.title || 'Приз' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КНОПКА "ИГРАТЬ ЕЩЁ" -->
            <!-- ========================================== -->
            <div v-if="gameFinished" class="play-again-section">
                <button
                    class="play-again-btn"
                    :disabled="attemptsLeft <= 0"
                    @click="resetGame"
                >
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>{{ attemptsLeft > 0 ? 'Играть ещё' : 'Попытки закончились' }}</span>
                </button>
            </div>


        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: РЕЗУЛЬТАТ -->
        <!-- ========================================== -->
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
                            <div class="result-icon" :class="[
                                'rarity-' + selectedPrize?.rarity,
                                'type-' + selectedPrize?.type
                            ]">
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

                            <div class="detail-row">
                                <i class="fa-solid fa-ticket"></i>
                                <span>Ставка: <strong>-{{ moveCost }}</strong></span>
                            </div>
                            <div class="detail-row" :style="{ color: lastNetProfit >= 0 ? '#198754' : '#dc3545' }">
                                <i :class="lastNetProfit >= 0 ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down'"></i>
                                <span>Итого: <strong>{{ lastNetProfit >= 0 ? '+' : '' }}{{ lastNetProfit }}</strong></span>
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

        <!-- ========================================== -->
        <!-- МОДАЛКА: ВСЕ ПРИЗЫ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showPrizesModal" class="modal-overlay" @click.self="showPrizesModal = false">
                <div class="modal-container prizes-modal">
                    <div class="modal-header">
                        <div class="modal-icon">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                        <div class="modal-title-wrapper">
                            <h5 class="modal-title">Возможные призы</h5>
                            <span class="modal-subtitle">Что может выпасть из карт</span>
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
                                :class="['rarity-' + prize.rarity, 'type-' + prize.type]"
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
    name: "PrizeCardGame",
    data() {
        return {
            // Настройки
            gameSettings: {
                can_play: true,
                interval: 1,
                attempts_per_period: 1,
                spin_cost: 500,
                grid_columns: 4,
                grid_rows: 3,
                rules: '',
                prizes: [],
                rarity_chances: {},
            },

            // Состояние
            attemptsLeft: 0,
            userBalance: 0,
            gameFinished: false,
            isProcessing: false,
            lastNetProfit: 0,
            // UI
            showRules: false,
            showAdmin: false,
            showResultModal: false,
            showPrizesModal: false,
            selectedPrize: null,

            // Данные
            cards: [],
            allPrizes: [],
        };
    },

    computed: {
        isAdmin() {
            const user = window.TenantUser;
            return user?.role === 'admin' || user?.is_admin === true;
        },

        gridColumns() {
            return this.gameSettings.grid_columns || 4;
        },

        gridRows() {
            return this.gameSettings.grid_rows || 3;
        },

        totalCards() {
            return this.gridColumns * this.gridRows;
        },

        moveCost() {
            return this.gameSettings.spin_cost || 500;
        },

        hasEnoughCashback() {
            return this.userBalance >= this.moveCost;
        },

        canPlay() {
            return this.hasEnoughCashback
                && this.attemptsLeft > 0
                && !this.gameFinished
                && !this.isProcessing;
        },
    },

    async mounted() {
        await this.loadGameData();
        this.initGame();
    },

    methods: {
        async loadGameData() {
            try {
                const [settingsRes, stateRes] = await Promise.all([
                    axios.get('/prize-card/settings'),
                    axios.get('/prize-card/state'),
                ]);

                if (settingsRes.data?.success && settingsRes.data.prize_card) {
                    this.gameSettings = { ...this.gameSettings, ...settingsRes.data.prize_card };
                    this.allPrizes = this.gameSettings.prizes || [];
                } else {
                    this.allPrizes = this.getDefaultPrizes();
                }

                if (stateRes.data?.success) {
                    this.attemptsLeft = stateRes.data.attempts_left ?? 1;
                    this.userBalance = stateRes.data.balance ?? (window.TenantUser?.cashback_balance || 0);
                    this.gameFinished = stateRes.data.game_finished || false;
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
                { id: 5, type: 'bonus', title: '350 бонусов', icon: 'fa-solid fa-gem', value: 350, rarity: 'rare' },
                { id: 9, type: 'bonus', title: '1000 бонусов', icon: 'fa-solid fa-gem', value: 1000, rarity: 'epic' },
                { id: 15, type: 'bonus', title: '5000 бонусов', icon: 'fa-solid fa-trophy', value: 5000, rarity: 'legendary' },
            ];
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

        initGame() {
            this.gameFinished = false;
            this.selectedPrize = null;
            this.lastNetProfit = 0;

            this.cards = Array.from({ length: this.totalCards }, (_, i) => ({
                id: i + 1,
                flipped: false,
                selected: false,
                prize: null,
            }));
        },

        async selectCard(index) {
            if (this.gameFinished || this.isProcessing) return;
            if (this.cards[index].flipped) return;

            if (!this.hasEnoughCashback) {
                this.$notify?.({
                    title: '💰 Недостаточно кэшбэка',
                    text: `Для хода нужно ${this.moveCost}₽. Ваш баланс: ${Math.round(this.userBalance)}₽`,
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

            const card = this.cards[index];
            this.isProcessing = true;

            try {
                const response = await axios.post('/prize-card/play');

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка игры');
                }

                const prize = response.data.prize;

                if (response.data.balance !== undefined) {
                    this.userBalance = response.data.balance;
                    if (window.TenantUser) {
                        window.TenantUser.cashback_balance = response.data.balance;
                    }
                }

                if (response.data.attempts_left !== undefined) {
                    this.attemptsLeft = response.data.attempts_left;
                }

                // 🎴 Переворачиваем карту ТОЛЬКО после успешного ответа
                card.prize = prize;
                card.flipped = true;
                card.selected = true;

                this.selectedPrize = prize;
                this.gameFinished = this.attemptsLeft <= 0;

                // 💾 Сохраняем net profit для модалки
                const netProfit = response.data.net_profit ?? 0;
                this.lastNetProfit = netProfit;

                const profitText = netProfit >= 0 ? `+${netProfit}` : `${netProfit}`;

                this.$notify?.({
                    title: netProfit >= 0 ? '🎉 Победа!' : '🎴 Приз получен',
                    text: `${prize.title}: ${profitText} (ставка: ${this.moveCost}₽)`,
                    type: netProfit >= 0 ? 'success' : 'info',
                });

                setTimeout(() => {
                    this.showResultModal = true;
                }, 1000);

            } catch (error) {
                console.error('Ошибка игры:', error);

                if (error.response?.status === 403) {
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
                        text: error.response?.data?.message || 'Не удалось получить приз',
                        type: 'error',
                    });
                }
            } finally {
                // 🔓 Разблокировка ВСЕГДА (даже при ошибке)
                this.isProcessing = false;
            }
        },

        resetGame() {
            this.initGame();
            this.loadGameData();  // Перезагружаем состояние с сервера
        },

        closeResultModal() {
            this.showResultModal = false;
        },

        openPrizesModal() {
            this.showPrizesModal = true;
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
                case 'bonus':
                    return `+${prize.value}`;
                case 'product':
                    return 'Товар';
                case 'product_discount':
                    return `−${prize.value}%`;
                case 'delivery_discount':
                    return prize.isPercent ? `−${prize.value}%` : `−${this.formatPrice(prize.value)}`;
                case 'order_discount':
                    return `−${prize.value}%`;
                default:
                    return prize.value;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
        },
    },
};
</script>

<style scoped>
/* Все стили из предыдущей версии остаются без изменений */
/* Добавляем только новые стили для разных типов призов */

.card-game-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* HERO СЕКЦИЯ */
.game-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #000000 0%, #8b0000 50%, #ff0000 100%);

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

/* КОНТЕНТ */
.game-content {
    padding: 20px 16px;
}

/* ПРАВИЛА */
.rules-section{
    margin-bottom: 20px;
}

.rules-toggle,
{
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

.rules-toggle:hover{
    border-color: var(--bs-primary);
}

.rules-toggle-content{
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
}

.rules-icon {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}


.rules-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.rules-title{
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

.rules-content{
    margin-top: 12px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
}

.rules-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rule-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.rule-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

/* СТАТУС ИГРЫ */
.game-status {
    margin-bottom: 16px;
}

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

.status-info {
    flex: 1;
}

.status-title {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.status-subtitle {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

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

.status-badge.finished {
    background: #6c757d;
}

/* КНОПКА ПРОСМОТРА ПРИЗОВ */
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
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.prizes-preview-btn i:first-child {
    color: var(--bs-primary);
    margin-right: 10px;
}

.prizes-preview-btn i:last-child {
    color: var(--bs-secondary-color);
    font-size: 0.8rem;
}

/* СЕТКА КАРТОЧЕК */
.cards-section {
    margin-bottom: 24px;
}

.cards-grid {
    display: grid;
    gap: 10px;
    justify-content: center;
}

.cards-grid.grid-4x3 {
    grid-template-columns: repeat(3, 1fr);
    max-width: 400px;
    margin: 0 auto;
}

/* ИГРОВАЯ КАРТОЧКА */
.game-card {
    aspect-ratio: 3/4;
    perspective: 1000px;
    cursor: pointer;
}

.game-card.is-disabled {
    cursor: not-allowed;
}

.card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    transform-style: preserve-3d;
}

.game-card.is-flipped .card-inner {
    transform: rotateY(180deg);
}

.card-face {
    position: absolute;
    inset: 0;
    backface-visibility: hidden;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* РУБАШКА КАРТЫ */
.card-back {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.card-back-pattern {
    position: absolute;
    inset: 0;
    background-image:
        repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.05) 0, rgba(255, 255, 255, 0.05) 1px, transparent 1px, transparent 10px),
        repeating-linear-gradient(-45deg, rgba(255, 255, 255, 0.05) 0, rgba(255, 255, 255, 0.05) 1px, transparent 1px, transparent 10px);
}

.card-back-content {
    position: relative;
    z-index: 1;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    transition: transform 0.3s ease;
}

.game-card:hover:not(.is-disabled) .card-back-content {
    transform: scale(1.1) rotate(10deg);
}

.card-back-shine {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        45deg,
        transparent 30%,
        rgba(255, 255, 255, 0.2) 50%,
        transparent 70%
    );
    animation: shine 3s ease-in-out infinite;
}

@keyframes shine {
    0% { transform: translateX(-100%) translateY(-100%); }
    100% { transform: translateX(100%) translateY(100%); }
}

/* ЛИЦЕВАЯ СТОРОНА КАРТЫ */
.card-front {
    transform: rotateY(180deg);
    background: var(--bs-body-bg);
    border: 2px solid;
    padding: 10px 8px;
    gap: 6px;
}

.card-rarity-indicator {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

/* Цвета по редкости */
.card-front.rarity-common {
    border-color: #6c757d;
}
.card-front.rarity-common .card-rarity-indicator {
    background: linear-gradient(90deg, #6c757d 0%, #adb5bd 100%);
}
.card-front.rarity-common .card-prize-icon {
    background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
    color: white;
}

.card-front.rarity-rare {
    border-color: #0d6efd;
    box-shadow: 0 0 20px rgba(13, 110, 253, 0.3);
}
.card-front.rarity-rare .card-rarity-indicator {
    background: linear-gradient(90deg, #0d6efd 0%, #6610f2 100%);
}
.card-front.rarity-rare .card-prize-icon {
    background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
    color: white;
}

.card-front.rarity-epic {
    border-color: #6f42c1;
    box-shadow: 0 0 25px rgba(111, 66, 193, 0.4);
}
.card-front.rarity-epic .card-rarity-indicator {
    background: linear-gradient(90deg, #6f42c1 0%, #d63384 100%);
}
.card-front.rarity-epic .card-prize-icon {
    background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);
    color: white;
    animation: epicPulse 2s ease-in-out infinite;
}

@keyframes epicPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(111, 66, 193, 0.5); }
    50% { box-shadow: 0 0 20px 5px rgba(111, 66, 193, 0.3); }
}

.card-front.rarity-legendary {
    border-color: #ffc107;
    box-shadow: 0 0 30px rgba(255, 193, 7, 0.5);
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
}
.card-front.rarity-legendary .card-rarity-indicator {
    background: linear-gradient(90deg, #ffc107 0%, #ff9800 50%, #ff5722 100%);
    height: 6px;
}
.card-front.rarity-legendary .card-prize-icon {
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
    animation: legendaryGlow 1.5s ease-in-out infinite;
}

@keyframes legendaryGlow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 0 40px rgba(255, 215, 0, 0.8);
        transform: scale(1.05);
    }
}

/* Цвета иконок по типу приза */
.card-front.type-product .card-prize-icon {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.card-front.type-product_discount .card-prize-icon,
.card-front.type-delivery_discount .card-prize-icon,
.card-front.type-order_discount .card-prize-icon {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.card-prize-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    margin-top: 8px;
}

.card-prize-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--bs-primary);
    line-height: 1;
}

.card-prize-name {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--bs-body-color);
    text-align: center;
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* КНОПКА "ИГРАТЬ ЕЩЁ" */
.play-again-section {
    margin-bottom: 24px;
}

.play-again-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.play-again-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.play-again-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}


/* МОДАЛКА РЕЗУЛЬТАТА */
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

.result-modal {
    position: relative;
    overflow: visible;
}

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

.result-rarity-badge.rarity-common {
    background: rgba(108, 117, 125, 0.15);
    color: #6c757d;
}
.result-rarity-badge.rarity-rare {
    background: rgba(13, 110, 253, 0.15);
    color: #0d6efd;
}
.result-rarity-badge.rarity-epic {
    background: rgba(111, 66, 193, 0.15);
    color: #6f42c1;
}
.result-rarity-badge.rarity-legendary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%);
    color: #b8860b;
    border: 1px solid rgba(255, 215, 0, 0.3);
}

.result-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

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

/* Цвета иконок по типу приза в модалке */
.result-icon.type-product { background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; }
.result-icon.type-product_discount,
.result-icon.type-delivery_discount,
.result-icon.type-order_discount { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; }

.result-glow {
    position: absolute;
    inset: -20px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(var(--bs-primary-rgb), 0.3) 0%, transparent 70%);
    animation: glowPulse 2s ease-in-out infinite;
}

@keyframes glowPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0.8; }
}

.result-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--bs-body-color);
    margin: 0 0 8px 0;
}

.result-description {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    line-height: 1.5;
    margin: 0 0 20px 0;
}

/* Детали приза */
.result-details {
    background: rgba(var(--bs-primary-rgb), 0.05);
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

.detail-row i {
    color: var(--bs-primary);
    font-size: 1.1rem;
}

.detail-row strong {
    color: var(--bs-primary);
}

.result-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.result-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

/* МОДАЛКА ПРИЗОВ */
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
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.modal-title-wrapper {
    flex: 1;
}

.modal-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

.modal-subtitle {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

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

.modal-close:hover {
    background: #dc3545;
    color: white;
    transform: rotate(90deg);
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    max-height: 60vh;
}

.prizes-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

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

.prize-item:hover {
    transform: translateX(4px);
}

.prize-item.rarity-legendary {
    border-color: rgba(255, 215, 0, 0.3);
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, transparent 100%);
}

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
}

.prize-icon.rarity-common { background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%); }
.prize-icon.rarity-rare { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); }
.prize-icon.rarity-epic { background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%); }
.prize-icon.rarity-legendary {
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
}

/* Цвета иконок по типу приза в списке */
.prize-icon.type-product { background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; }
.prize-icon.type-product_discount,
.prize-icon.type-delivery_discount,
.prize-icon.type-order_discount { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; }

.prize-info {
    flex: 1;
    min-width: 0;
}

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

.prize-description {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    line-height: 1.4;
}

.prize-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--bs-primary);
    flex-shrink: 0;
}

/* АНИМАЦИИ */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

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
    max-height: 500px;
}

/* АДАПТИВ */
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
        font-size: 1rem;
    }

    .cards-grid.grid-4x3 {
        gap: 8px;
    }

    .card-prize-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .card-prize-value {
        font-size: 1rem;
    }

    .card-prize-name {
        font-size: 0.65rem;
    }

    .result-icon {
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }

    .result-title {
        font-size: 1.3rem;
    }
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
