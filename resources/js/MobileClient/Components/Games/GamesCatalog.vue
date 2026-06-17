<template>
    <div class="games-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="games-hero">
            <div class="hero-background"></div>
            <div class="hero-particles">
                <span v-for="i in 25" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">
                        <i class="fa-solid fa-gamepad"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">🎮</div>
                    <div class="hero-sparkle sparkle-2">✨</div>
                    <div class="hero-sparkle sparkle-3">🏆</div>
                </div>
                <h1 class="hero-title">Игры и призы</h1>
                <p class="hero-subtitle">Играй и получай бонусы, скидки и подарки!</p>

                <!-- Статистика -->
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-value">{{ availableGamesCount }}</div>
                        <div class="stat-label">Доступно</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value">{{ totalPrizesWon }}</div>
                        <div class="stat-label">Призов выиграно</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value">{{ totalBonusEarned }}</div>
                        <div class="stat-label">Бонусов</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="games-content">

            <!-- ========================================== -->
            <!-- ФИЛЬТРЫ -->
            <!-- ========================================== -->
            <div class="filters-wrapper">
                <div class="filter-tabs">
                    <button
                        v-for="filter in filters"
                        :key="filter.id"
                        class="filter-tab"
                        :class="{ 'active': activeFilter === filter.id }"
                        @click="activeFilter = filter.id"
                    >
                        <i :class="filter.icon"></i>
                        <span>{{ filter.label }}</span>
                        <span class="filter-count">{{ filter.count }}</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- СЕТКА ИГР -->
            <!-- ========================================== -->
            <div class="games-grid">
                <div
                    v-for="game in filteredGames"
                    :key="game.id"
                    class="game-card"
                    :class="{
                        'is-available': game.available,
                        'is-locked': !game.available,
                        'is-hot': game.badge === 'hot',
                        'is-new': game.badge === 'new'
                    }"
                    @click="openGame(game)"
                >
                    <!-- Фон карточки -->
                    <div class="card-bg" :style="{ background: game.gradient }"></div>

                    <!-- Декоративные элементы -->
                    <div class="card-decorations">
                        <div class="decoration deco-1"></div>
                        <div class="decoration deco-2"></div>
                    </div>

                    <!-- Бейдж -->
                    <div v-if="game.badge" class="game-badge" :class="'badge-' + game.badge">
                        <i :class="badgeIcon(game.badge)"></i>
                        <span>{{ badgeText(game.badge) }}</span>
                    </div>

                    <!-- Overlay для заблокированных -->
                    <div v-if="!game.available" class="locked-overlay">
                        <div class="lock-icon">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div class="lock-text">Скоро</div>
                    </div>

                    <!-- Контент -->
                    <div class="card-content">
                        <div class="game-icon-wrapper">
                            <div class="game-icon" :style="{ background: game.iconBg }">
                                <i :class="game.icon"></i>
                            </div>
                        </div>

                        <div class="game-info">
                            <h6 class="game-title">{{ game.title }}</h6>
                            <p class="game-description">{{ game.description }}</p>

                            <div class="game-meta">
                                <div class="meta-item">
                                    <i class="fa-solid fa-gift"></i>
                                    <span>{{ game.prize }}</span>
                                </div>
                                <div v-if="game.attempts" class="meta-item">
                                    <i class="fa-solid fa-ticket"></i>
                                    <span>{{ game.attempts }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопка действия -->
                        <div class="game-action">
                            <template v-if="game.available">
                                <span>Играть</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </template>
                            <template v-else>
                                <span>Скоро</span>
                                <i class="fa-solid fa-bell"></i>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ПУСТОЕ СОСТОЯНИЕ -->
            <!-- ========================================== -->
            <div v-if="filteredGames.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-gamepad"></i>
                </div>
                <h5 class="empty-title">Игр в этой категории нет</h5>
                <p class="empty-text">Попробуйте выбрать другую категорию</p>
                <button class="empty-btn" @click="activeFilter = 'all'">
                    <i class="fa-solid fa-rotate-left me-2"></i>
                    Показать все игры
                </button>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "GamesCatalog",

    data() {
        return {
            activeFilter: 'all',

            // Статистика (можно брать из store/API)
            totalPrizesWon: 3,
            totalBonusEarned: 450,

            // Список всех игр
            games: [
                // ========= ДОСТУПНЫЕ ИГРЫ =========
                {
                    id: 'wheel-of-fortune',
                    title: 'Колесо Фортуны',
                    description: 'Крути колесо и выигрывай призы каждый день!',
                    icon: 'fa-solid fa-dharmachakra',
                    iconBg: 'linear-gradient(135deg, #ffd700 0%, #ff9800 100%)',
                    gradient: 'linear-gradient(135deg, #9a1717 0%, #c0392b 50%, #e74c3c 100%)',
                    prize: 'до 1000 бонусов',
                    attempts: '1 попытка/день',
                    available: true,
                    badge: 'hot',
                    route: 'WheelOfFortune',
                    category: 'daily',
                },
                {
                    id: 'card-game',
                    title: 'Карточная игра',
                    description: 'Выбери карту и получи гарантированный бонус!',
                    icon: 'fa-solid fa-layer-group',
                    iconBg: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    prize: 'до 500 бонусов',
                    attempts: '1 попытка/день',
                    available: true,
                    badge: 'new',
                    route: 'CashbackCardGame',
                    category: 'daily',
                },

                {
                    id: 'card-prizes-game',
                    title: 'Карточная игра',
                    description: 'Выбери карту и получи гарантированный приз!',
                    icon: 'fa-solid fa-layer-group',
                    iconBg: 'linear-gradient(135deg, #000000 0%, #8b0000 100%)',
                    gradient: 'linear-gradient(135deg, #000000 0%, #8b0000 50%, #ff0000 100%)',

                    prize: 'от бонусов и скидок до вкусных призов',
                    attempts: '1 попытка/день',
                    available: true,
                    badge: 'new',
                    route: 'PrizeCardGame',
                    category: 'daily',
                },

                // ========= ИГРЫ "НА БУДУЩЕЕ" =========
                {
                    id: 'scratch-card',
                    title: 'Скретч-карта',
                    description: 'Стирай защитный слой и узнай свой приз!',
                    icon: 'fa-solid fa-credit-card',
                    iconBg: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    prize: 'до 300 бонусов',
                    attempts: '1 попытка/день',
                    available: true,
                    badge: 'soon',
                    route: 'ScratchCardGame',
                    category: 'instant',
                },
                {
                    id: 'slot-machine',
                    title: 'Слот-машина',
                    description: 'Крути барабаны и собирай выигрышные комбинации!',
                    icon: 'fa-solid fa-dice',
                    iconBg: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                    gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                    prize: 'до 700 бонусов',
                    attempts: '1 попытка/день',
                    available: true,
                    badge: 'soon',
                    route: 'SlotMachineGame',
                    category: 'instant',
                },
                {
                    id: 'guess-number',
                    title: 'Угадай число',
                    description: 'Попробуй угадать загаданное число от 1 до 10!',
                    icon: 'fa-solid fa-hashtag',
                    iconBg: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                    gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                    prize: 'до 200 бонусов',
                    attempts: null,
                    available: false,
                    badge: 'soon',
                    route: null,
                    category: 'puzzle',
                },
                {
                    id: 'daily-bonus',
                    title: 'Ежедневный бонус',
                    description: 'Заходи каждый день и получай бонусы за серию!',
                    icon: 'fa-solid fa-calendar-check',
                    iconBg: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                    gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                    prize: 'до 100 бонусов',
                    attempts: '1 попытка/день',
                    available: true,
                    badge: 'new',
                    route: 'DailyBonusGame',
                    category: 'daily',
                },
                {
                    id: 'quiz',
                    title: 'Викторина',
                    description: 'Отвечай на вопросы и зарабатывай бонусы!',
                    icon: 'fa-solid fa-question',
                    iconBg: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                    gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                    prize: 'до 400 бонусов',
                    attempts: '1 попытка/день',
                    available: true,
                    badge: 'new',
                    route: 'QuizGame',
                    category: 'puzzle',
                },
                {
                    id: 'treasure-hunt',
                    title: 'Охота за сокровищами',
                    description: 'Ищи спрятанные призы по всему приложению!',
                    icon: 'fa-solid fa-map-location-dot',
                    iconBg: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                    gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                    prize: 'до 1500 бонусов',
                    attempts: null,
                    available: false,
                    badge: 'soon',
                    route: null,
                    category: 'puzzle',
                },
            ],
        };
    },

    computed: {
        availableGamesCount() {
            return this.games.filter(g => g.available).length;
        },

        filters() {
            return [
                {
                    id: 'all',
                    label: 'Все',
                    icon: 'fa-solid fa-grip',
                    count: this.games.length,
                },
                {
                    id: 'available',
                    label: 'Доступно',
                    icon: 'fa-solid fa-circle-check',
                    count: this.games.filter(g => g.available).length,
                },
                {
                    id: 'daily',
                    label: 'Ежедневные',
                    icon: 'fa-solid fa-sun',
                    count: this.games.filter(g => g.category === 'daily').length,
                },
                {
                    id: 'instant',
                    label: 'Моментальные',
                    icon: 'fa-solid fa-bolt',
                    count: this.games.filter(g => g.category === 'instant').length,
                },
                {
                    id: 'puzzle',
                    label: 'Головоломки',
                    icon: 'fa-solid fa-puzzle-piece',
                    count: this.games.filter(g => g.category === 'puzzle').length,
                },
            ];
        },

        filteredGames() {
            switch (this.activeFilter) {
                case 'available':
                    return this.games.filter(g => g.available);
                case 'daily':
                    return this.games.filter(g => g.category === 'daily');
                case 'instant':
                    return this.games.filter(g => g.category === 'instant');
                case 'puzzle':
                    return this.games.filter(g => g.category === 'puzzle');
                default:
                    return this.games;
            }
        },
    },

    methods: {
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

        badgeIcon(badge) {
            const icons = {
                hot: 'fa-solid fa-fire',
                new: 'fa-solid fa-sparkles',
                soon: 'fa-solid fa-bell',
            };
            return icons[badge] || '';
        },

        badgeText(badge) {
            const texts = {
                hot: 'HOT',
                new: 'NEW',
                soon: 'SOON',
            };
            return texts[badge] || '';
        },

        openGame(game) {
            if (!game.available) {
                this.$notify?.({
                    title: 'Игра',
                    text: 'Эта игра скоро будет доступна!',
                    type: 'info',
                });
                return;
            }

            if (game.route) {
                this.$router.push({ name: game.route });
            }
        },
    },
};
</script>

<style scoped>
.games-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.games-hero {
    position: relative;
    padding: 48px 24px 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
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
    animation: iconBounce 3s ease-in-out infinite;
}

@keyframes iconBounce {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(5deg); }
}

.hero-sparkle {
    position: absolute;
    font-size: 1.5rem;
    animation: sparkleFloat 3s ease-in-out infinite;
}

.sparkle-1 { top: -12px; right: -12px; animation-delay: 0s; }
.sparkle-2 { bottom: -12px; left: -12px; animation-delay: 1s; }
.sparkle-3 { top: 50%; right: -20px; animation-delay: 2s; }

@keyframes sparkleFloat {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.8; }
    50% { transform: translateY(-10px) scale(1.2); opacity: 1; }
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

/* Статистика */
.hero-stats {
    display: inline-flex;
    align-items: center;
    gap: 20px;
    padding: 16px 24px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.7rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-divider {
    width: 1px;
    height: 36px;
    background: rgba(255, 255, 255, 0.3);
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.games-content {
    padding: 20px 16px;
}

/* ==========================================
   ФИЛЬТРЫ
   ========================================== */
.filters-wrapper {
    margin-bottom: 20px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.filters-wrapper::-webkit-scrollbar {
    display: none;
}

.filter-tabs {
    display: flex;
    gap: 8px;
    padding: 4px 0;
    min-width: max-content;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.filter-tab:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.filter-tab.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.filter-tab i {
    font-size: 0.85rem;
}

.filter-count {
    padding: 2px 8px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.filter-tab.active .filter-count {
    background: rgba(255, 255, 255, 0.25);
    color: white;
}

/* ==========================================
   СЕТКА ИГР
   ========================================== */
.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

/* ==========================================
   КАРТОЧКА ИГРЫ
   ========================================== */
.game-card {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 240px;
    display: flex;
    flex-direction: column;
    animation: cardAppear 0.5s ease-out backwards;
}

@keyframes cardAppear {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.game-card:nth-child(1) { animation-delay: 0.05s; }
.game-card:nth-child(2) { animation-delay: 0.1s; }
.game-card:nth-child(3) { animation-delay: 0.15s; }
.game-card:nth-child(4) { animation-delay: 0.2s; }
.game-card:nth-child(5) { animation-delay: 0.25s; }
.game-card:nth-child(6) { animation-delay: 0.3s; }

.game-card.is-available:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
}

.game-card.is-available:active {
    transform: translateY(-3px) scale(0.98);
}

.game-card.is-locked {
    cursor: not-allowed;
    filter: grayscale(0.3);
}

/* Фон карточки */
.card-bg {
    position: absolute;
    inset: 0;
    opacity: 0.95;
}

/* Декоративные элементы */
.card-decorations {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.decoration {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
}

.deco-1 {
    width: 150px;
    height: 150px;
    top: -50px;
    right: -50px;
}

.deco-2 {
    width: 100px;
    height: 100px;
    bottom: -30px;
    left: -30px;
}

/* Бейдж */
.game-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 2;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.badge-hot {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
    animation: hotPulse 2s ease-in-out infinite;
}

@keyframes hotPulse {
    0%, 100% { box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4); }
    50% { box-shadow: 0 4px 20px rgba(255, 107, 107, 0.8); }
}

.badge-new {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.badge-soon {
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(10px);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Overlay для заблокированных */
.locked-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    z-index: 3;
}

.lock-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    animation: lockShake 3s ease-in-out infinite;
}

@keyframes lockShake {
    0%, 100% { transform: rotate(0deg); }
    10% { transform: rotate(-5deg); }
    20% { transform: rotate(5deg); }
    30% { transform: rotate(-5deg); }
    40% { transform: rotate(0deg); }
}

.lock-text {
    color: white;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Контент */
.card-content {
    position: relative;
    z-index: 1;
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
    color: white;
}

.game-icon-wrapper {
    margin-bottom: 16px;
}

.game-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
}

.game-card.is-available:hover .game-icon {
    transform: scale(1.1) rotate(-5deg);
}

.game-info {
    flex: 1;
    margin-bottom: 16px;
}

.game-title {
    margin: 0 0 6px 0;
    font-weight: 800;
    font-size: 1.15rem;
    color: white;
    line-height: 1.2;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.game-description {
    margin: 0 0 12px 0;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.game-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.meta-item i {
    font-size: 0.7rem;
}

/* Кнопка действия */
.game-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.game-card.is-available:hover .game-action {
    background: rgba(255, 255, 255, 0.3);
}

.game-action i {
    transition: transform 0.3s ease;
}

.game-card.is-available:hover .game-action i {
    transform: translateX(4px);
}

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
.empty-state {
    text-align: center;
    padding: 60px 24px;
}

.empty-icon {
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
}

.empty-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 24px;
}

.empty-btn {
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

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .games-hero {
        padding: 36px 16px 32px;
    }

    .hero-title {
        font-size: 1.6rem;
    }

    .hero-icon {
        width: 72px;
        height: 72px;
        font-size: 2rem;
    }

    .hero-stats {
        gap: 14px;
        padding: 12px 18px;
    }

    .stat-value {
        font-size: 1.2rem;
    }

    .games-grid {
        grid-template-columns: 1fr;
    }

    .game-card {
        min-height: 200px;
    }

    .game-title {
        font-size: 1.05rem;
    }
}

@media (min-width: 768px) {
    .games-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1200px) {
    .games-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>
