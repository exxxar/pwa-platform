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
                <p class="hero-subtitle">Выбери карту и получи бонус!</p>

                <!-- Баланс бонусов -->
                <div class="hero-balance">
                    <div class="balance-icon">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="balance-info">
                        <div class="balance-value">{{ userBalance }}</div>
                        <div class="balance-label">Ваши бонусы</div>
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
                                <div class="rule-text">Выберите одну карту кликом — приз ваш гарантированно!</div>
                            </div>
                            <div class="rule-item">
                                <div class="rule-number">3</div>
                                <div class="rule-text">Бонусы автоматически зачислятся на ваш баланс</div>
                            </div>
                            <div class="rule-item">
                                <div class="rule-number">4</div>
                                <div class="rule-text">Чем реже приз — тем он ценнее. Удачи!</div>
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
                            'is-disabled': gameFinished || card.flipped
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
                            <div class="card-face card-front" :class="'rarity-' + (card.prize?.rarity || 'common')">
                                <div class="card-rarity-indicator"></div>
                                <div class="card-prize-icon">
                                    <i :class="card.prize?.icon || 'fa-solid fa-gift'"></i>
                                </div>
                                <div class="card-prize-value">
                                    +{{ card.prize?.value || 0 }}
                                </div>
                                <div class="card-prize-name">
                                    {{ card.prize?.title || 'Бонус' }}
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
                            <button class="admin-btn" @click="adminShowStats">
                                <i class="fa-solid fa-chart-simple"></i>
                                <span>Статистика</span>
                            </button>
                            <button class="admin-btn" @click="adminEditPrizes">
                                <i class="fa-solid fa-pen"></i>
                                <span>Редакт. призы</span>
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
                    <div class="result-confetti" v-if="selectedPrize?.rarity === 'legendary'">
                        <span v-for="i in 40" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                    </div>
                    <div class="result-content">
                        <div class="result-rarity-badge" :class="'rarity-' + selectedPrize?.rarity">
                            {{ rarityText(selectedPrize?.rarity) }}
                        </div>
                        <div class="result-icon-wrapper">
                            <div class="result-icon" :class="'rarity-' + selectedPrize?.rarity">
                                <i :class="selectedPrize?.icon"></i>
                            </div>
                            <div class="result-glow"></div>
                        </div>
                        <h3 class="result-title">{{ selectedPrize?.title }}</h3>
                        <p class="result-description">{{ selectedPrize?.description }}</p>
                        <div class="result-value">
                            <span class="value-label">Вам начислено</span>
                            <span class="value-amount">+{{ selectedPrize?.value }} бонусов</span>
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
                                :class="'rarity-' + prize.rarity"
                            >
                                <div class="prize-icon" :class="'rarity-' + prize.rarity">
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
                                    +{{ prize.value }}
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
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

export default {
    name: "CashbackCardGame",

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            // Настройки игры
            gridColumns: 4,
            gridRows: 3,
            attemptsLeft: 1,
            userBalance: 0,
            gameFinished: false,

            // UI состояния
            showRules: false,
            showAdmin: false,
            showResultModal: false,
            showPrizesModal: false,
            selectedPrize: null,

            // Данные
            cards: [],
            allPrizes: [],

            // Призы с системой редкости
            defaultPrizes: [
                // Обычные (70% шанс)
                { id: 1, title: 'Монетка', description: 'Небольшой бонус к вашему балансу', icon: 'fa-solid fa-coins', value: 10, rarity: 'common' },
                { id: 2, title: 'Искорка', description: 'Маленький приятный бонус', icon: 'fa-solid fa-sparkles', value: 15, rarity: 'common' },
                { id: 3, title: 'Капля', description: 'Скромное пополнение баланса', icon: 'fa-solid fa-droplet', value: 20, rarity: 'common' },
                { id: 4, title: 'Листок', description: 'Свежий бонус на ваш счёт', icon: 'fa-solid fa-leaf', value: 25, rarity: 'common' },
                { id: 5, title: 'Звёздочка', description: 'Маленькая звёздная награда', icon: 'fa-solid fa-star', value: 30, rarity: 'common' },

                // Редкие (20% шанс)
                { id: 6, title: 'Кристалл', description: 'Редкий кристалл с ценным бонусом', icon: 'fa-solid fa-gem', value: 50, rarity: 'rare' },
                { id: 7, title: 'Пламя', description: 'Горячее предложение с хорошим бонусом', icon: 'fa-solid fa-fire', value: 75, rarity: 'rare' },
                { id: 8, title: 'Молния', description: 'Быстрый и мощный бонус', icon: 'fa-solid fa-bolt', value: 100, rarity: 'rare' },

                // Эпические (8% шанс)
                { id: 9, title: 'Корона', description: 'Королевский бонус для избранных', icon: 'fa-solid fa-crown', value: 200, rarity: 'epic' },
                { id: 10, title: 'Ракета', description: 'Огромный бонус, улетающий в космос', icon: 'fa-solid fa-rocket', value: 300, rarity: 'epic' },

                // Легендарные (2% шанс)
                { id: 11, title: 'Джекпот', description: 'ЛЕГЕНДАРНЫЙ ПРИЗ! Максимальный бонус!', icon: 'fa-solid fa-trophy', value: 1000, rarity: 'legendary' },
            ],
        };
    },

    computed: {
        totalCards() {
            return this.gridColumns * this.gridRows;
        },

        isAdmin() {
            const user = window.TenantUser;
            return user?.role === 'admin' || user?.is_admin === true;
        },
    },

    async mounted() {
        await this.loadGameData();
        this.initGame();
    },

    methods: {
        // Стиль для частиц в hero
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

        // Текст редкости
        rarityText(rarity) {
            const texts = {
                common: 'Обычный',
                rare: 'Редкий',
                epic: 'Эпический',
                legendary: 'Легендарный',
            };
            return texts[rarity] || 'Обычный';
        },

        // Загрузка данных игры с бэка
        async loadGameData() {
            try {
                // TODO: Замени на реальный API
                // const response = await this.basketStore.loadCardGameData();
                // this.allPrizes = response.prizes || this.defaultPrizes;
                // this.attemptsLeft = response.attempts_left || 1;
                // this.userBalance = response.user_balance || 0;

                // Имитация запроса
                await new Promise(resolve => setTimeout(resolve, 500));
                this.allPrizes = this.defaultPrizes;
                this.attemptsLeft = 1;
                this.userBalance = window.TenantUser?.cashBack?.amount || 0;
            } catch (error) {
                console.error('Ошибка загрузки данных игры:', error);
                this.allPrizes = this.defaultPrizes;
            }
        },

        // Инициализация игры
        initGame() {
            this.gameFinished = false;
            this.selectedPrize = null;

            // Создаём массив карточек
            this.cards = Array.from({ length: this.totalCards }, (_, i) => ({
                id: i + 1,
                flipped: false,
                selected: false,
                prize: null,
            }));
        },

        // Выбор карты
        async selectCard(index) {
            if (this.gameFinished) return;
            if (this.cards[index].flipped) return;
            if (this.attemptsLeft <= 0) {
                this.$notify?.({
                    title: 'Игра',
                    text: 'Попытки закончились',
                    type: 'warning',
                });
                return;
            }

            const card = this.cards[index];
            card.flipped = true;
            card.selected = true;

            try {
                // Запрос приза с бэка
                // TODO: Замени на реальный API
                // const response = await this.basketStore.claimCardPrize({ cardId: card.id });
                // const prize = response.prize;

                // Имитация запроса с системой редкости
                await new Promise(resolve => setTimeout(resolve, 800));
                const prize = this.getRandomPrize();
                card.prize = prize;

                // Начисляем бонус
                await this.creditBonus(prize.value);

                this.selectedPrize = prize;
                this.gameFinished = true;
                this.attemptsLeft--;

                // Показываем модалку результата с задержкой (чтобы успела проиграться анимация flip)
                setTimeout(() => {
                    this.showResultModal = true;
                }, 1000);

            } catch (error) {
                console.error('Ошибка получения приза:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось получить приз',
                    type: 'error',
                });
                card.flipped = false;
                card.selected = false;
            }
        },

        // Получение случайного приза с учётом редкости
        getRandomPrize() {
            const rand = Math.random() * 100;
            let targetRarity;

            if (rand < 2) targetRarity = 'legendary';
            else if (rand < 10) targetRarity = 'epic';
            else if (rand < 30) targetRarity = 'rare';
            else targetRarity = 'common';

            const pool = this.allPrizes.filter(p => p.rarity === targetRarity);
            return pool[Math.floor(Math.random() * pool.length)];
        },

        // Начисление бонуса
        async creditBonus(amount) {
            try {
                // TODO: Замени на реальный API
                // await this.basketStore.creditBonus({ amount });

                this.userBalance += amount;

                this.$notify?.({
                    title: 'Поздравляем!',
                    text: `Вам начислено ${amount} бонусов`,
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка начисления бонуса:', error);
            }
        },

        // Сброс игры
        resetGame() {
            this.initGame();
        },

        // Закрытие модалки результата
        closeResultModal() {
            this.showResultModal = false;
        },

        // Открытие модалки призов
        openPrizesModal() {
            this.showPrizesModal = true;
        },

        // ==========================================
        // АДМИН-ФУНКЦИИ
        // ==========================================
        adminResetAll() {
            if (!confirm('Сбросить игру всем пользователям?')) return;
            this.$notify?.({
                title: 'Админ',
                text: 'Игра сброшена для всех',
                type: 'success',
            });
        },

        adminAddAttempts() {
            this.attemptsLeft++;
            this.gameFinished = false;
            this.$notify?.({
                title: 'Админ',
                text: 'Добавлена 1 попытка',
                type: 'success',
            });
        },

        adminShowStats() {
            this.$notify?.({
                title: 'Статистика',
                text: 'Функция в разработке',
                type: 'info',
            });
        },

        adminEditPrizes() {
            this.$notify?.({
                title: 'Редактор',
                text: 'Функция в разработке',
                type: 'info',
            });
        },
    },
};
</script>

<style scoped>
.card-game-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.game-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

/* Баланс бонусов */
.hero-balance {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
}

.balance-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.balance-info {
    text-align: left;
}

.balance-value {
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1;
}

.balance-label {
    font-size: 0.7rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
.rules-section,
.admin-section {
    margin-bottom: 20px;
}

.rules-toggle,
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

.rules-toggle:hover,
.admin-toggle:hover {
    border-color: var(--bs-primary);
}

.rules-toggle-content,
.admin-toggle-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rules-icon,
.admin-icon {
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

.admin-icon {
    background: linear-gradient(135deg, #2c3e50 0%, #4a6278 100%);
    color: white;
}

.rules-info,
.admin-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.rules-title,
.admin-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.rules-hint,
.admin-hint {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.rules-arrow,
.admin-arrow {
    color: var(--bs-secondary-color);
    transition: transform 0.3s ease;
}

.rules-arrow.rotated,
.admin-arrow.rotated {
    transform: rotate(180deg);
}

.rules-content,
.admin-content {
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

/* ==========================================
   СТАТУС ИГРЫ
   ========================================== */
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

/* ==========================================
   КНОПКА ПРОСМОТРА ПРИЗОВ
   ========================================== */
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

/* ==========================================
   СЕТКА КАРТОЧЕК
   ========================================== */
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

.cards-grid.grid-3x3 {
    grid-template-columns: repeat(3, 1fr);
    max-width: 320px;
    margin: 0 auto;
}

/* ==========================================
   ИГРОВАЯ КАРТОЧКА
   ========================================== */
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

/* ==========================================
   РУБАШКА КАРТЫ
   ========================================== */
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

/* ==========================================
   ЛИЦЕВАЯ СТОРОНА КАРТЫ
   ========================================== */
.card-front {
    transform: rotateY(180deg);
    background: var(--bs-body-bg);
    border: 2px solid;
    padding: 10px 8px;
    gap: 6px;
}

/* Индикатор редкости */
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

/* ==========================================
   КНОПКА "ИГРАТЬ ЕЩЁ"
   ========================================== */
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

/* ==========================================
   АДМИН-ПАНЕЛЬ
   ========================================== */
.admin-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

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
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.admin-btn i {
    font-size: 1.2rem;
    color: var(--bs-primary);
}

.admin-btn span {
    font-size: 0.8rem;
    font-weight: 600;
}

/* ==========================================
   МОДАЛКА РЕЗУЛЬТАТА
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

.result-value {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-radius: 14px;
    margin-bottom: 20px;
}

.value-label {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.value-amount {
    font-size: 1.5rem;
    font-weight: 800;
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

/* ==========================================
   МОДАЛКА ПРИЗОВ
   ========================================== */
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

/* ==========================================
   АНИМАЦИИ
   ========================================== */
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

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.5rem;
    }

    .hero-icon {
        width: 64px;
        height: 64px;
        font-size: 1.8rem;
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
</style>
