<template>
    <div class="treasure-hunt-page">

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
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">🗺️</div>
                    <div class="hero-sparkle sparkle-2">💎</div>
                </div>
                <h1 class="hero-title">Охота за сокровищами</h1>
                <p class="hero-subtitle">Найди спрятанные сокровища на карте!</p>

                <div class="hero-stats">
                    <div class="stat-block">
                        <div class="stat-icon fire-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ balance }}</div>
                            <div class="stat-label">Баланс</div>
                        </div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-block">
                        <div class="stat-icon trophy-icon">
                            <i class="fa-solid fa-gem"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ treasuresFound }}</div>
                            <div class="stat-label">Сокровищ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="game-content">

            <!-- ========================================== -->
            <!-- ВЫБОР УРОВНЯ -->
            <!-- ========================================== -->
            <div class="level-section">
                <div class="level-tabs">
                    <button
                        v-for="level in levels"
                        :key="level.id"
                        class="level-tab"
                        :class="{ active: currentLevel === level.id, locked: !level.unlocked }"
                        @click="selectLevel(level.id)"
                    >
                        <i :class="level.unlocked ? level.icon : 'fa-solid fa-lock'"></i>
                        <span>{{ level.name }}</span>
                        <small>{{ level.cost }} бонусов</small>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- СТАРТ -->
            <!-- ========================================== -->
            <div v-if="!gameStarted" class="start-section">
                <div class="game-preview">
                    <div class="preview-icon" :class="'level-' + currentLevel">
                        <i class="fa-solid fa-map"></i>
                    </div>
                    <h3 class="preview-title">{{ currentLevelConfig.name }}</h3>
                    <p class="preview-desc">{{ currentLevelConfig.desc }}</p>

                    <div class="preview-map-info">
                        <div class="map-stat">
                            <i class="fa-solid fa-table-cells"></i>
                            <span>Карта {{ currentLevelConfig.size }}x{{ currentLevelConfig.size }}</span>
                        </div>
                        <div class="map-stat">
                            <i class="fa-solid fa-gem"></i>
                            <span>{{ currentLevelConfig.treasures }} сокровищ</span>
                        </div>
                        <div class="map-stat danger">
                            <i class="fa-solid fa-skull"></i>
                            <span>{{ currentLevelConfig.traps }} ловушек</span>
                        </div>
                    </div>

                    <div class="preview-rewards">
                        <div class="reward-title">Возможные сокровища:</div>
                        <div class="reward-row" v-for="(r, i) in currentLevelConfig.rewards" :key="i">
                            <div class="reward-type">
                                <span class="reward-emoji">{{ r.emoji }}</span>
                                <span>{{ r.name }}</span>
                            </div>
                            <div class="reward-value">
                                <i class="fa-solid fa-coins"></i>
                                <span>{{ r.min }}–{{ r.max }}</span>
                            </div>
                        </div>
                    </div>

                    <button
                        class="start-btn"
                        @click="startGame"
                        :disabled="balance < currentLevelConfig.cost"
                    >
                        <i class="fa-solid fa-play"></i>
                        <span>Начать (−{{ currentLevelConfig.cost }} бонусов)</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- АКТИВНАЯ ИГРА -->
            <!-- ========================================== -->
            <div v-else class="active-game">

                <!-- Панель статистики -->
                <div class="game-stats-panel">
                    <div class="game-stat">
                        <div class="game-stat-icon treasure">
                            <i class="fa-solid fa-gem"></i>
                        </div>
                        <div class="game-stat-info">
                            <div class="game-stat-value">
                                {{ foundThisRound }} / {{ currentLevelConfig.treasures }}
                            </div>
                            <div class="game-stat-label">Сокровищ</div>
                        </div>
                    </div>
                    <div class="game-stat-divider"></div>
                    <div class="game-stat">
                        <div class="game-stat-icon coins">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="game-stat-info">
                            <div class="game-stat-value">+{{ earnedThisRound }}</div>
                            <div class="game-stat-label">Заработано</div>
                        </div>
                    </div>
                    <div class="game-stat-divider"></div>
                    <div class="game-stat">
                        <div class="game-stat-icon moves">
                            <i class="fa-solid fa-shoe-prints"></i>
                        </div>
                        <div class="game-stat-info">
                            <div class="game-stat-value">{{ moves }}</div>
                            <div class="game-stat-label">Ходов</div>
                        </div>
                    </div>
                </div>

                <!-- Карта -->
                <div class="map-wrapper">
                    <div
                        class="game-map"
                        :style="{
                            gridTemplateColumns: `repeat(${currentLevelConfig.size}, 1fr)`,
                            gridTemplateRows: `repeat(${currentLevelConfig.size}, 1fr)`
                        }"
                    >
                        <div
                            v-for="(cell, i) in map"
                            :key="i"
                            class="map-cell"
                            :class="{
                                'revealed': cell.revealed,
                                'treasure': cell.revealed && cell.type === 'treasure',
                                'trap': cell.revealed && cell.type === 'trap',
                                'hint': cell.revealed && cell.type === 'hint',
                                'empty': cell.revealed && cell.type === 'empty',
                                'radar-target': cell.radarTarget && !cell.revealed,
                                'protected': cell.protected,
                                'shaking': cell.shaking,
                                'legendary': cell.revealed && cell.tier === 'legendary',
                                'rare': cell.revealed && cell.tier === 'rare'
                            }"
                            @click="revealCell(i)"
                        >
                            <div class="cell-cover">
                                <i class="fa-solid fa-question"></i>
                            </div>
                            <div class="cell-content">
                                <template v-if="cell.revealed">
                                    <span v-if="cell.type === 'treasure'" class="cell-icon">
                                        {{ cell.emoji }}
                                    </span>
                                    <span v-else-if="cell.type === 'trap'" class="cell-icon">
                                        💣
                                    </span>
                                    <span v-else-if="cell.type === 'hint'" class="cell-icon">
                                        🧭
                                    </span>
                                    <span v-else class="cell-icon empty-icon">·</span>
                                </template>
                                <span v-if="cell.protected" class="shield-icon">🛡️</span>
                            </div>
                            <div v-if="cell.radarTarget && !cell.revealed" class="radar-ping"></div>
                        </div>
                    </div>
                </div>

                <!-- Всплывающий приз -->
                <transition name="prize-popup">
                    <div v-if="lastPrize" class="prize-popup" :class="'tier-' + lastPrize.tier">
                        <div class="prize-popup-icon">{{ lastPrize.emoji }}</div>
                        <div class="prize-popup-info">
                            <div class="prize-popup-title">{{ lastPrize.name }}</div>
                            <div class="prize-popup-value">+{{ lastPrize.value }} бонусов</div>
                        </div>
                    </div>
                </transition>

                <!-- Бустеры -->
                <div class="boosters-panel">
                    <div class="boosters-title">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>Бустеры</span>
                    </div>
                    <div class="boosters-grid">
                        <button
                            class="booster-btn"
                            :class="{ active: activeBooster === 'radar', disabled: balance < 30 }"
                            @click="useBooster('radar')"
                        >
                            <div class="booster-icon">📡</div>
                            <div class="booster-name">Радар</div>
                            <div class="booster-cost">30</div>
                        </button>
                        <button
                            class="booster-btn"
                            :class="{ active: activeBooster === 'shield', disabled: balance < 50 }"
                            @click="useBooster('shield')"
                        >
                            <div class="booster-icon">🛡️</div>
                            <div class="booster-name">Щит</div>
                            <div class="booster-cost">50</div>
                        </button>
                        <button
                            class="booster-btn"
                            :class="{ active: activeBooster === 'compass', disabled: balance < 20 }"
                            @click="useBooster('compass')"
                        >
                            <div class="booster-icon">🧭</div>
                            <div class="booster-name">Компас</div>
                            <div class="booster-cost">20</div>
                        </button>
                    </div>
                </div>

                <!-- Кнопки -->
                <div class="game-actions">
                    <button class="give-up-btn" @click="giveUp">
                        <i class="fa-solid fa-flag"></i>
                        <span>Забрать выигрыш</span>
                    </button>
                </div>

                <!-- Финал -->
                <div v-if="gameOver" class="game-result" :class="gameWon ? 'won' : 'lost'">
                    <div class="result-icon">
                        <i :class="gameWon ? 'fa-solid fa-crown' : 'fa-solid fa-skull'"></i>
                    </div>
                    <h3 class="result-title">{{ gameWon ? 'Все сокровища найдены!' : 'Вы попали в ловушку' }}</h3>
                    <div class="result-summary">
                        <div class="summary-item">
                            <i class="fa-solid fa-gem"></i>
                            <span>Сокровищ: {{ foundThisRound }}</span>
                        </div>
                        <div class="summary-item">
                            <i class="fa-solid fa-coins"></i>
                            <span>Заработано: +{{ earnedThisRound }}</span>
                        </div>
                    </div>
                    <button class="play-again-btn" @click="resetGame">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Играть ещё</span>
                    </button>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- ИСТОРИЯ -->
            <!-- ========================================== -->
            <div class="history-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h6 class="section-title">История охот</h6>
                        <p class="section-subtitle">Ваши последние приключения</p>
                    </div>
                </div>

                <div v-if="gamesHistory.length === 0" class="empty-history">
                    <div class="empty-icon">🗺️</div>
                    <p>Пока нет сыгранных охот</p>
                    <p class="empty-hint">Начните первое приключение!</p>
                </div>

                <div v-else class="history-list">
                    <div
                        v-for="game in gamesHistory"
                        :key="game.id"
                        class="history-item"
                    >
                        <div class="history-date">
                            <div class="date-day">{{ formatDate(game.date).day }}</div>
                            <div class="date-month">{{ formatDate(game.date).month }}</div>
                        </div>
                        <div class="history-mode">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <div class="history-info">
                            <div class="history-title">{{ levelNames[game.level] }}</div>
                            <div class="history-value">
                                {{ game.treasures }} сокровищ · {{ game.moves }} ходов
                            </div>
                        </div>
                        <div class="history-result" :class="game.won ? 'win' : 'loss'">
                            <i :class="game.won ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
                            <span>{{ game.won ? '+' + game.earned : '−' + game.cost }}</span>
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
                            <button class="admin-btn" @click="adminAddBalance(500)">
                                <i class="fa-solid fa-coins"></i>
                                <span>+500 бонусов</span>
                            </button>
                            <button class="admin-btn" @click="adminRevealAll">
                                <i class="fa-solid fa-eye"></i>
                                <span>Открыть карту</span>
                            </button>
                            <button class="admin-btn" @click="adminUnlockLevels">
                                <i class="fa-solid fa-lock-open"></i>
                                <span>Разблок. уровни</span>
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
                            <i :class="gameWon ? 'fa-solid fa-crown' : 'fa-solid fa-skull'"></i>
                        </div>
                        <h2 class="result-title-large">
                            {{ gameWon ? 'Сокровища ваши!' : 'Попадание в ловушку' }}
                        </h2>
                        <p class="result-desc">
                            Найдено сокровищ: <strong>{{ foundThisRound }}</strong> из {{ currentLevelConfig.treasures }}
                        </p>
                        <div class="reward-display" v-if="earnedThisRound > 0">
                            <div class="reward-amount">+{{ earnedThisRound }}</div>
                            <div class="reward-label">бонусов</div>
                        </div>
                        <button class="result-btn" @click="closeResultModal">
                            <i class="fa-solid fa-check"></i>
                            <span>Продолжить</span>
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
    name: "TreasureHuntGame",

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            balance: 500,
            treasuresFound: 0,
            gamesHistory: [],
            unlockedLevels: [1],

            currentLevel: 1,
            gameStarted: false,
            gameOver: false,
            gameWon: false,

            map: [],
            moves: 0,
            foundThisRound: 0,
            earnedThisRound: 0,

            activeBooster: null,
            lastPrize: null,
            lastPrizeTimeout: null,

            showAdmin: false,
            showResultModal: false,

            levels: [
                {
                    id: 1,
                    name: 'Остров',
                    icon: 'fa-solid fa-umbrella-beach',
                    size: 4,
                    treasures: 3,
                    traps: 2,
                    hints: 2,
                    cost: 30,
                    unlocked: true,
                    desc: 'Небольшой остров с сокровищами. Идеально для начинающих!',
                    rewards: [
                        { emoji: '💰', name: 'Обычное', min: 30, max: 80, tier: 'common', weight: 70 },
                        { emoji: '💎', name: 'Редкое', min: 150, max: 250, tier: 'rare', weight: 25 },
                        { emoji: '👑', name: 'Легендарное', min: 400, max: 600, tier: 'legendary', weight: 5 },
                    ],
                },
                {
                    id: 2,
                    name: 'Пещера',
                    icon: 'fa-solid fa-mountain',
                    size: 5,
                    treasures: 5,
                    traps: 4,
                    hints: 3,
                    cost: 60,
                    unlocked: false,
                    desc: 'Тёмная пещера с большими сокровищами, но и больше ловушек!',
                    rewards: [
                        { emoji: '💰', name: 'Обычное', min: 50, max: 150, tier: 'common', weight: 60 },
                        { emoji: '💎', name: 'Редкое', min: 250, max: 450, tier: 'rare', weight: 30 },
                        { emoji: '👑', name: 'Легендарное', min: 700, max: 1200, tier: 'legendary', weight: 10 },
                    ],
                },
                {
                    id: 3,
                    name: 'Храм',
                    icon: 'fa-solid fa-landmark',
                    size: 6,
                    treasures: 8,
                    traps: 6,
                    hints: 4,
                    cost: 120,
                    unlocked: false,
                    desc: 'Древний храм с несметными богатствами. Только для опытных!',
                    rewards: [
                        { emoji: '💰', name: 'Обычное', min: 100, max: 300, tier: 'common', weight: 50 },
                        { emoji: '💎', name: 'Редкое', min: 400, max: 800, tier: 'rare', weight: 35 },
                        { emoji: '👑', name: 'Легендарное', min: 1000, max: 1500, tier: 'legendary', weight: 15 },
                    ],
                },
            ],

            levelNames: {
                1: 'Остров',
                2: 'Пещера',
                3: 'Храм',
            },
        };
    },

    computed: {
        isAdmin() {
            const user = window.TenantUser;
            return user?.role === 'admin' || user?.is_admin === true;
        },

        currentLevelConfig() {
            return this.levels.find(l => l.id === this.currentLevel);
        },
    },

    async mounted() {
        await this.loadState();
        this.updateLevelUnlock();
    },

    beforeUnmount() {
        if (this.lastPrizeTimeout) {
            clearTimeout(this.lastPrizeTimeout);
        }
    },

    methods: {
        // ==========================================
        // СОСТОЯНИЕ
        // ==========================================
        async loadState() {
            try {
                const saved = localStorage.getItem('treasureHuntState');
                if (saved) {
                    const state = JSON.parse(saved);
                    this.balance = state.balance ?? 500;
                    this.treasuresFound = state.treasuresFound ?? 0;
                    this.gamesHistory = state.gamesHistory ?? [];
                    this.unlockedLevels = state.unlockedLevels ?? [1];
                }
            } catch (error) {
                console.error('Ошибка загрузки:', error);
            }
            this.updateLevelUnlock();
        },

        saveState() {
            const state = {
                balance: this.balance,
                treasuresFound: this.treasuresFound,
                gamesHistory: this.gamesHistory,
                unlockedLevels: this.unlockedLevels,
            };
            localStorage.setItem('treasureHuntState', JSON.stringify(state));
        },

        updateLevelUnlock() {
            this.levels.forEach(level => {
                level.unlocked = this.unlockedLevels.includes(level.id);
            });
        },

        // ==========================================
        // ИГРОВАЯ ЛОГИКА
        // ==========================================
        selectLevel(id) {
            if (this.gameStarted) return;
            const level = this.levels.find(l => l.id === id);
            if (!level || !level.unlocked) {
                this.$notify?.({ title: 'Заблокировано', text: 'Этот уровень ещё не открыт', type: 'warning' });
                return;
            }
            this.currentLevel = id;
        },

        startGame() {
            if (this.balance < this.currentLevelConfig.cost) {
                this.$notify?.({ title: 'Ошибка', text: 'Недостаточно бонусов', type: 'error' });
                return;
            }

            this.balance -= this.currentLevelConfig.cost;
            this.generateMap();
            this.gameStarted = true;
            this.gameOver = false;
            this.gameWon = false;
            this.moves = 0;
            this.foundThisRound = 0;
            this.earnedThisRound = 0;
            this.activeBooster = null;
            this.saveState();
        },

        generateMap() {
            const { size, treasures, traps, hints } = this.currentLevelConfig;
            const total = size * size;
            const map = Array.from({ length: total }, () => ({
                type: 'empty',
                revealed: false,
                protected: false,
                radarTarget: false,
                shaking: false,
                tier: null,
                emoji: null,
                value: 0,
                name: '',
            }));

            // Размещаем сокровища
            this.placeRandom(map, 'treasure', treasures, true);
            // Размещаем ловушки
            this.placeRandom(map, 'trap', traps, false);
            // Размещаем подсказки
            this.placeRandom(map, 'hint', hints, false);

            this.map = map;
        },

        placeRandom(map, type, count, isTreasure = false) {
            let placed = 0;
            const indices = [...Array(map.length).keys()].sort(() => Math.random() - 0.5);

            for (const i of indices) {
                if (placed >= count) break;
                if (map[i].type === 'empty') {
                    map[i].type = type;
                    if (isTreasure) {
                        const reward = this.pickRandomReward();
                        map[i].tier = reward.tier;
                        map[i].emoji = reward.emoji;
                        map[i].name = reward.name;
                        map[i].value = Math.floor(Math.random() * (reward.max - reward.min + 1)) + reward.min;
                    }
                    placed++;
                }
            }
        },

        pickRandomReward() {
            const rewards = this.currentLevelConfig.rewards;
            const totalWeight = rewards.reduce((sum, r) => sum + r.weight, 0);
            let rand = Math.random() * totalWeight;

            for (const r of rewards) {
                if (rand < r.weight) return r;
                rand -= r.weight;
            }
            return rewards[0];
        },

        revealCell(index) {
            if (this.gameOver) return;
            const cell = this.map[index];
            if (cell.revealed) return;

            if (this.activeBooster === 'compass') {
                this.activateCompass(index);
                return;
            }

            this.moves++;
            cell.revealed = true;

            if (cell.type === 'treasure') {
                this.foundThisRound++;
                this.earnedThisRound += cell.value;
                this.treasuresFound++;
                this.showPrizePopup(cell);

                // Проверка полной победы
                if (this.foundThisRound >= this.currentLevelConfig.treasures) {
                    this.gameWon = true;
                    this.gameOver = true;
                    this.unlockNextLevel();
                    this.addGameToHistory(true);
                    this.balance += this.earnedThisRound;
                    this.saveState();
                    setTimeout(() => { this.showResultModal = true; }, 600);
                    return;
                }
            } else if (cell.type === 'trap') {
                if (cell.protected) {
                    // Щит сработал
                    cell.protected = false;
                    this.$notify?.({ title: 'Щит!', text: 'Ловушка обезврежена', type: 'success' });
                } else {
                    this.gameOver = true;
                    this.gameWon = false;
                    // Забираем половину выигрыша
                    const half = Math.floor(this.earnedThisRound / 2);
                    this.balance += half;
                    this.earnedThisRound = half;
                    this.addGameToHistory(false);
                    this.saveState();
                    setTimeout(() => { this.showResultModal = true; }, 600);
                    return;
                }
            } else if (cell.type === 'hint') {
                this.showHint(index);
            }

            this.saveState();
        },

        showHint(index) {
            const size = this.currentLevelConfig.size;
            const row = Math.floor(index / size);
            const col = index % size;

            // Находим ближайшее сокровище
            let nearest = null;
            let minDist = Infinity;

            this.map.forEach((cell, i) => {
                if (cell.type === 'treasure' && !cell.revealed) {
                    const r = Math.floor(i / size);
                    const c = i % size;
                    const dist = Math.abs(r - row) + Math.abs(c - col);
                    if (dist < minDist) {
                        minDist = dist;
                        nearest = { r, c, dist };
                    }
                }
            });

            if (nearest) {
                let direction = '';
                if (nearest.r < row) direction += '↑';
                if (nearest.r > row) direction += '↓';
                if (nearest.c < col) direction += '←';
                if (nearest.c > col) direction += '→';

                this.$notify?.({
                    title: 'Подсказка!',
                    text: `Сокровище ${direction} (${nearest.dist} шагов)`,
                    type: 'info',
                    duration: 3000,
                });
            }
        },

        giveUp() {
            if (this.earnedThisRound === 0) {
                if (!confirm('Уйти без выигрыша?')) return;
            } else {
                if (!confirm(`Забрать ${this.earnedThisRound} бонусов и закончить игру?`)) return;
            }

            this.balance += this.earnedThisRound;
            this.gameOver = true;
            this.gameWon = this.foundThisRound >= this.currentLevelConfig.treasures;
            this.addGameToHistory(this.gameWon);
            this.unlockNextLevel();
            this.saveState();
            this.showResultModal = true;
        },

        unlockNextLevel() {
            const nextId = this.currentLevel + 1;
            if (nextId <= 3 && !this.unlockedLevels.includes(nextId)) {
                this.unlockedLevels.push(nextId);
                this.$notify?.({
                    title: 'Новый уровень!',
                    text: `Открыт уровень: ${this.levelNames[nextId]}`,
                    type: 'success',
                    duration: 4000,
                });
            }
        },

        resetGame() {
            this.gameStarted = false;
            this.gameOver = false;
            this.gameWon = false;
            this.map = [];
            this.activeBooster = null;
        },

        addGameToHistory(won) {
            const now = new Date();
            const dateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            this.gamesHistory.unshift({
                id: Date.now(),
                date: dateStr,
                level: this.currentLevel,
                treasures: this.foundThisRound,
                moves: this.moves,
                won,
                earned: this.earnedThisRound,
                cost: this.currentLevelConfig.cost,
            });
            if (this.gamesHistory.length > 20) this.gamesHistory.pop();
        },

        // ==========================================
        // БУСТЕРЫ
        // ==========================================
        useBooster(type) {
            if (!this.gameStarted || this.gameOver) return;

            const costs = { radar: 30, shield: 50, compass: 20 };
            const cost = costs[type];

            if (this.balance < cost) {
                this.$notify?.({ title: 'Ошибка', text: 'Недостаточно бонусов', type: 'error' });
                return;
            }

            if (type === 'radar') {
                // Подсвечиваем одну клетку с сокровищем
                const treasures = this.map
                    .map((c, i) => ({ cell: c, i }))
                    .filter(x => x.cell.type === 'treasure' && !x.cell.revealed);
                if (treasures.length === 0) {
                    this.$notify?.({ title: 'Радар', text: 'Сокровищ больше нет', type: 'info' });
                    return;
                }
                const target = treasures[Math.floor(Math.random() * treasures.length)];
                target.cell.radarTarget = true;
                setTimeout(() => {
                    if (target.cell) target.cell.radarTarget = false;
                }, 3000);
                this.balance -= cost;
                this.$notify?.({ title: 'Радар', text: 'Сокровище обнаружено!', type: 'success' });
                this.saveState();
            } else if (type === 'shield') {
                this.activeBooster = 'shield';
                this.$notify?.({
                    title: 'Щит активен',
                    text: 'Следующая клетка защищена от ловушки',
                    type: 'info',
                });
            } else if (type === 'compass') {
                this.activeBooster = 'compass';
                this.$notify?.({
                    title: 'Компас',
                    text: 'Нажмите на клетку, чтобы узнать расстояние',
                    type: 'info',
                });
            }
        },

        activateCompass(index) {
            if (this.balance < 20) {
                this.$notify?.({ title: 'Ошибка', text: 'Недостаточно бонусов', type: 'error' });
                return;
            }

            const size = this.currentLevelConfig.size;
            const row = Math.floor(index / size);
            const col = index % size;

            let minDist = Infinity;
            this.map.forEach((cell, i) => {
                if (cell.type === 'treasure' && !cell.revealed) {
                    const r = Math.floor(i / size);
                    const c = i % size;
                    const dist = Math.abs(r - row) + Math.abs(c - col);
                    if (dist < minDist) minDist = dist;
                }
            });

            if (minDist === Infinity) {
                this.$notify?.({ title: 'Компас', text: 'Сокровищ больше нет', type: 'info' });
                this.activeBooster = null;
                return;
            }

            this.balance -= 20;
            this.$notify?.({
                title: 'Компас',
                text: `Ближайшее сокровище: ${minDist} шагов`,
                type: 'info',
                duration: 3000,
            });
            this.activeBooster = null;
            this.saveState();
        },

        showPrizePopup(cell) {
            this.lastPrize = {
                emoji: cell.emoji,
                name: cell.name,
                value: cell.value,
                tier: cell.tier,
            };
            if (this.lastPrizeTimeout) clearTimeout(this.lastPrizeTimeout);
            this.lastPrizeTimeout = setTimeout(() => {
                this.lastPrize = null;
            }, 2500);
        },

        closeResultModal() {
            this.showResultModal = false;
            this.resetGame();
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
            const colors = ['#a8edea', '#fed6e3', '#ffd700', '#4facfe', '#43e97b'];
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

        // ==========================================
        // АДМИН
        // ==========================================
        adminAddBalance(amount) {
            this.balance += amount;
            this.saveState();
            this.$notify?.({ title: 'Админ', text: `+${amount} бонусов`, type: 'success' });
        },

        adminRevealAll() {
            if (!this.gameStarted) return;
            this.map.forEach(cell => { cell.revealed = true; });
        },

        adminUnlockLevels() {
            this.unlockedLevels = [1, 2, 3];
            this.updateLevelUnlock();
            this.saveState();
            this.$notify?.({ title: 'Админ', text: 'Все уровни открыты', type: 'success' });
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
.treasure-hunt-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO
   ========================================== */
.game-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #1a1a1a;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.4) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(168, 237, 234, 0.3) 0%, transparent 50%);
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
    background: rgba(255, 255, 255, 0.8);
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
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
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
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.8;
    margin: 0 0 20px 0;
}

.hero-stats {
    display: inline-flex;
    align-items: center;
    gap: 16px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.6);
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
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-divider {
    width: 1px;
    height: 30px;
    background: rgba(0, 0, 0, 0.15);
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.game-content { padding: 20px 16px; }

/* ==========================================
   УРОВНИ
   ========================================== */
.level-section { margin-bottom: 20px; }

.level-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.level-tab {
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

.level-tab:hover:not(.locked) {
    border-color: #a8edea;
}

.level-tab.active {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    border-color: #a8edea;
    color: #1a1a1a;
    box-shadow: 0 4px 16px rgba(168, 237, 234, 0.4);
}

.level-tab.locked {
    opacity: 0.5;
    cursor: not-allowed;
}

.level-tab i { font-size: 1.3rem; }
.level-tab span { font-weight: 700; font-size: 0.85rem; }
.level-tab small { font-size: 0.65rem; opacity: 0.8; }

/* ==========================================
   СТАРТ
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

.preview-icon.level-1 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.preview-icon.level-2 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.preview-icon.level-3 { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }

.preview-title {
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0 0 8px 0;
    color: var(--bs-body-color);
}

.preview-desc {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 16px;
    line-height: 1.4;
}

.preview-map-info {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.map-stat {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.map-stat i { color: #4facfe; }
.map-stat.danger i { color: #dc3545; }

.preview-rewards {
    margin-bottom: 20px;
    text-align: left;
}

.reward-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--bs-body-color);
    margin-bottom: 8px;
}

.reward-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
    margin-bottom: 6px;
    font-size: 0.85rem;
}

.reward-type {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--bs-body-color);
    font-weight: 600;
}

.reward-emoji { font-size: 1.2rem; }

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
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    border: none;
    border-radius: 14px;
    color: #1a1a1a;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(168, 237, 234, 0.3);
}

.start-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(168, 237, 234, 0.5);
}

.start-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ==========================================
   АКТИВНАЯ ИГРА
   ========================================== */
.active-game {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
}

.game-stats-panel {
    display: grid;
    grid-template-columns: 1fr auto 1fr auto 1fr;
    gap: 12px;
    align-items: center;
    padding: 14px 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
}

.game-stat {
    display: flex;
    align-items: center;
    gap: 10px;
}

.game-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: white;
    flex-shrink: 0;
}

.game-stat-icon.treasure { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.game-stat-icon.coins { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }
.game-stat-icon.moves { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #1a1a1a; }

.game-stat-info { display: flex; flex-direction: column; }
.game-stat-value { font-size: 1rem; font-weight: 800; color: var(--bs-body-color); line-height: 1; }
.game-stat-label { font-size: 0.65rem; color: var(--bs-secondary-color); text-transform: uppercase; }

.game-stat-divider {
    width: 1px;
    height: 30px;
    background: var(--bs-border-color);
}

/* Карта */
.map-wrapper {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 12px;
}

.game-map {
    display: grid;
    gap: 6px;
    aspect-ratio: 1;
}

.map-cell {
    position: relative;
    border-radius: 10px;
    cursor: pointer;
    overflow: hidden;
    background: var(--bs-secondary-bg);
    border: 2px solid var(--bs-border-color);
    transition: all 0.3s ease;
    aspect-ratio: 1;
}

.map-cell:hover:not(.revealed) {
    transform: scale(1.05);
    border-color: #a8edea;
}

.cell-cover {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: rgba(0, 0, 0, 0.3);
    font-size: 1.2rem;
    transition: opacity 0.3s ease;
}

.map-cell.revealed .cell-cover { opacity: 0; }

.cell-content {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease 0.2s;
}

.map-cell.revealed .cell-content { opacity: 1; }

.cell-icon {
    font-size: 1.8rem;
    animation: iconBounce 0.5s ease-out;
}

@keyframes iconBounce {
    0% { transform: scale(0) rotate(-180deg); }
    70% { transform: scale(1.2) rotate(10deg); }
    100% { transform: scale(1) rotate(0deg); }
}

.empty-icon {
    color: var(--bs-secondary-color);
    font-size: 2rem;
}

.map-cell.treasure {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 152, 0, 0.15) 100%);
    border-color: #ffd700;
}

.map-cell.treasure.rare {
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.2) 0%, rgba(0, 242, 254, 0.2) 100%);
    border-color: #4facfe;
    box-shadow: 0 0 15px rgba(79, 172, 254, 0.5);
}

.map-cell.treasure.legendary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(255, 152, 0, 0.3) 100%);
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.7);
    animation: legendaryPulse 2s ease-in-out infinite;
}

@keyframes legendaryPulse {
    0%, 100% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.7); }
    50% { box-shadow: 0 0 30px rgba(255, 215, 0, 1); }
}

.map-cell.trap {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.2) 0%, rgba(255, 107, 107, 0.2) 100%);
    border-color: #dc3545;
}

.map-cell.hint {
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.15) 0%, rgba(0, 242, 254, 0.15) 100%);
    border-color: #4facfe;
}

.shield-icon {
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 0.9rem;
    z-index: 2;
}

.radar-target .cell-cover {
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    animation: radarPing 1s ease-in-out infinite;
}

@keyframes radarPing {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.radar-ping {
    position: absolute;
    inset: -4px;
    border: 2px solid #ffd700;
    border-radius: 10px;
    animation: ping 1.5s ease-out infinite;
}

@keyframes ping {
    0% { transform: scale(0.8); opacity: 1; }
    100% { transform: scale(1.3); opacity: 0; }
}

.map-cell.shaking {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Всплывающий приз */
.prize-popup {
    position: fixed;
    top: 80px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: var(--bs-body-bg);
    border: 2px solid #ffd700;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    z-index: 100;
}

.prize-popup.tier-rare { border-color: #4facfe; }
.prize-popup.tier-legendary {
    border-color: #ffd700;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
}

.prize-popup-icon { font-size: 2rem; }

.prize-popup-info { text-align: left; }
.prize-popup-title { font-weight: 700; font-size: 0.85rem; color: var(--bs-body-color); }
.prize-popup-value { font-size: 1rem; font-weight: 800; color: #43e97b; }

.prize-popup-enter-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.prize-popup-leave-active { transition: all 0.3s ease; }
.prize-popup-enter-from { opacity: 0; transform: translate(-50%, -20px) scale(0.8); }
.prize-popup-leave-to { opacity: 0; transform: translate(-50%, -20px); }

/* Бустеры */
.boosters-panel {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
}

.boosters-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 12px;
}

.boosters-title i { color: #fa709a; }

.boosters-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.booster-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 8px;
    background: var(--bs-secondary-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--bs-body-color);
}

.booster-btn:hover:not(.disabled) {
    border-color: #a8edea;
    background: rgba(168, 237, 234, 0.1);
}

.booster-btn.active {
    border-color: #43e97b;
    background: rgba(67, 233, 123, 0.1);
}

.booster-btn.disabled { opacity: 0.5; cursor: not-allowed; }

.booster-icon { font-size: 1.6rem; }
.booster-name { font-size: 0.8rem; font-weight: 700; }
.booster-cost {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #43e97b;
}

.booster-cost::before {
    content: '💰';
    font-size: 0.8rem;
}

/* Действия */
.game-actions {
    display: flex;
    gap: 10px;
}

.give-up-btn {
    flex: 1;
    padding: 14px;
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    border: none;
    border-radius: 14px;
    color: #1a1a1a;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.give-up-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(168, 237, 234, 0.4);
}

/* Финал */
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
    color: white;
}

.won .result-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.lost .result-icon { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }

.result-title {
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0 0 16px 0;
    color: var(--bs-body-color);
}

.result-summary {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 16px;
    padding: 12px;
    background: var(--bs-secondary-bg);
    border-radius: 12px;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: var(--bs-body-color);
}

.summary-item i { color: #4facfe; }

.play-again-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    border: none;
    border-radius: 14px;
    color: #1a1a1a;
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
    box-shadow: 0 8px 24px rgba(168, 237, 234, 0.5);
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
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(168, 237, 234, 0.4);
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

.empty-icon { font-size: 3rem; margin-bottom: 12px; }
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
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #1a1a1a;
    flex-shrink: 0;
}

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
    border-color: #a8edea;
    background: rgba(168, 237, 234, 0.1);
}

.admin-btn i { font-size: 1.2rem; color: #4facfe; }
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

.result-desc strong {
    color: var(--bs-body-color);
}

.reward-display {
    display: inline-block;
    padding: 16px 28px;
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    border-radius: 16px;
    margin-bottom: 20px;
}

.reward-amount {
    font-size: 2rem;
    font-weight: 800;
    color: #1a1a1a;
    line-height: 1;
}

.reward-label {
    font-size: 0.8rem;
    color: rgba(0, 0, 0, 0.6);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 4px;
}

.result-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    border: none;
    border-radius: 14px;
    color: #1a1a1a;
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
    box-shadow: 0 8px 24px rgba(168, 237, 234, 0.5);
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

    .game-stats-panel { grid-template-columns: 1fr; gap: 8px; }
    .game-stat-divider { width: 100%; height: 1px; }

    .result-icon-large { width: 100px; height: 100px; font-size: 2.5rem; }
}
</style>
