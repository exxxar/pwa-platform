<template>
    <section class="shop-loyalty">
        <div class="container">
            <!-- Заголовок -->
            <div class="section-header">
                <span class="section-badge">
                    <i class="fa-solid fa-crown"></i> Программа лояльности
                </span>
                <h2 class="section-title">Ваши привилегии растут с каждым заказом</h2>
                <p class="section-subtitle">Копите баллы, повышайте статус и получайте эксклюзивные бонусы</p>
            </div>

            <!-- 🌟 ГЛАВНЫЙ БЛОК: Прогресс пользователя (Hero Card) -->
            <div class="loyalty-hero">
                <div class="hero-glow"></div>
                <div class="hero-content">
                    <div class="hero-left">
                        <div class="current-level-badge" :style="{ background: currentLevel.color }">
                            <i :class="currentLevel.icon"></i>
                        </div>
                        <div class="level-info">
                            <span class="level-label">Ваш текущий статус</span>
                            <h3 class="level-name">{{ currentLevel.name }}</h3>
                            <p class="level-desc">{{ currentLevel.description }}</p>
                        </div>
                    </div>

                    <div class="hero-right">
                        <div class="points-display">
                            <span class="points-value">{{ formattedPoints }}</span>
                            <span class="points-label">бонусных баллов</span>
                        </div>

                        <div class="progress-container">
                            <div class="progress-header">
                                <span>До уровня <strong>{{ nextLevel.name }}</strong></span>
                                <span class="points-needed">осталось {{ nextLevel.points - userPoints }} б.</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" :style="{ width: progressPercent + '%' }">
                                    <div class="progress-glow"></div>
                                </div>
                                <div class="progress-thumb" :style="{ left: progressPercent + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Сетка уровней -->
            <div class="levels-grid">
                <div
                    v-for="level in levels"
                    :key="level.id"
                    class="level-card"
                    :class="{
                        'is-current': level.id === currentLevel.id,
                        'is-locked': userPoints < level.points,
                        'is-next': level.id === nextLevel.id
                    }"
                >
                    <div class="card-glow" :style="{ background: level.color }"></div>

                    <div class="card-header" :style="{ background: level.color }">
                        <i :class="level.icon" class="header-icon"></i>
                        <div class="header-points">{{ level.points }} баллов</div>
                        <div v-if="userPoints < level.points" class="lock-overlay">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>

                    <div class="card-body">
                        <h4>{{ level.name }}</h4>
                        <div class="cashback-pill">
                            <i class="fa-solid fa-percent"></i>
                            Кэшбэк {{ level.cashback }}%
                        </div>
                        <ul class="perks-list">
                            <li v-for="(perk, idx) in level.perks" :key="idx">
                                <i class="fa-solid fa-check"></i>
                                <span>{{ perk }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Как это работает (Modern Steps) -->
            <div class="how-it-works">
                <h3>Как это работает?</h3>
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-icon">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </div>
                        <h5>1. Делайте заказы</h5>
                        <p>С каждой покупки вам начисляется до 10% бонусами в зависимости от статуса.</p>
                    </div>

                    <div class="step-connector">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>

                    <div class="step-card">
                        <div class="step-icon">
                            <i class="fa-solid fa-piggy-bank"></i>
                        </div>
                        <h5>2. Копите баллы</h5>
                        <p>1 балл = 1 рубль. Копите их и тратьте на оплату любых будущих заказов.</p>
                    </div>

                    <div class="step-connector">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>

                    <div class="step-card">
                        <div class="step-icon">
                            <i class="fa-solid fa-rocket"></i>
                        </div>
                        <h5>3. Повышайте статус</h5>
                        <p>Чем больше баллов — тем выше ваш уровень и эксклюзивнее привилегии.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopLoyalty",
    props: {
        userPoints: { type: Number, default: 2450 }
    },
    data() {
        return {
            levels: [
                {
                    id: 'bronze',
                    name: 'Bronze',
                    icon: 'fa-solid fa-medal',
                    color: 'linear-gradient(135deg, #cd7f32 0%, #a0522d 100%)',
                    points: 0,
                    cashback: 3,
                    description: 'Отличный старт для новых гостей',
                    perks: ['Кэшбэк 3% с каждого заказа', 'Приветственные 100 баллов', 'Доступ к базовым акциям']
                },
                {
                    id: 'silver',
                    name: 'Silver',
                    icon: 'fa-solid fa-award',
                    color: 'linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%)',
                    points: 1000,
                    cashback: 5,
                    description: 'Для наших постоянных клиентов',
                    perks: ['Кэшбэк 5% с заказов', 'Бесплатная доставка', 'Приоритетная поддержка', '+500 баллов в День рождения']
                },
                {
                    id: 'gold',
                    name: 'Gold',
                    icon: 'fa-solid fa-trophy',
                    color: 'linear-gradient(135deg, #fbbf24 0%, #d97706 100%)',
                    points: 3000,
                    cashback: 7,
                    description: 'Для настоящих ценителей',
                    perks: ['Кэшбэк 7% с заказов', 'Бесплатная доставка всегда', 'Эксклюзивные закрытые акции', 'Подарок раз в месяц']
                },
                {
                    id: 'platinum',
                    name: 'Platinum',
                    icon: 'fa-solid fa-crown',
                    color: 'linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%)',
                    points: 7000,
                    cashback: 10,
                    description: 'Максимальные привилегии',
                    perks: ['Кэшбэк 10% с заказов', 'Персональный менеджер', 'Приглашения на ивенты', 'Максимальный приоритет']
                }
            ]
        };
    },
    computed: {
        formattedPoints() {
            return new Intl.NumberFormat('ru-RU').format(this.userPoints);
        },
        currentLevel() {
            return [...this.levels].reverse().find(l => this.userPoints >= l.points) || this.levels[0];
        },
        nextLevel() {
            const idx = this.levels.findIndex(l => l.id === this.currentLevel.id);
            return this.levels[idx + 1] || this.levels[this.levels.length - 1];
        },
        progressPercent() {
            if (this.currentLevel.id === this.nextLevel.id) return 100;
            const range = this.nextLevel.points - this.currentLevel.points;
            const progress = this.userPoints - this.currentLevel.points;
            return Math.min(100, Math.max(0, (progress / range) * 100));
        }
    }
};
</script>

<style lang="scss" scoped>
// Переменные
$primary: var(--primary, #ff8a00);
$primary-light: var(--primary-light, #ffb347);
$dark: var(--dark, #0f172a);
$gray: var(--gray, #64748b);
$light: var(--light, #f8fafc);
$border: var(--border, #e2e8f0);

.shop-loyalty {
    padding: 100px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    position: relative;
    overflow: hidden;
}

.container {
    position: relative;
    z-index: 1;
}

// ==========================================
// ЗАГОЛОВОК
// ==========================================
.section-header {
    text-align: center;
    margin-bottom: 3.5rem;
}

.section-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba($primary, 0.1);
    color: $primary;
    padding: 8px 20px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.85rem;
    margin-bottom: 1rem;
    border: 1px solid rgba($primary, 0.2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 1rem;
    color: $dark;
    letter-spacing: -0.02em;
}

.section-subtitle {
    font-size: 1.15rem;
    color: $gray;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

// ==========================================
// HERO CARD (Прогресс пользователя)
// ==========================================
.loyalty-hero {
    position: relative;
    background: $dark;
    border-radius: 24px;
    padding: 2.5rem;
    margin-bottom: 4rem;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    color: white;
}

.hero-glow {
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba($primary, 0.3) 0%, transparent 70%);
    filter: blur(60px);
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 40px;

    @media (max-width: 768px) {
        flex-direction: column;
        align-items: flex-start;
    }
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.current-level-badge {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    flex-shrink: 0;
}

.level-info {
    .level-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.7;
        margin-bottom: 4px;
        display: block;
    }
    .level-name {
        font-size: 2rem;
        font-weight: 800;
        margin: 0 0 4px 0;
        line-height: 1;
    }
    .level-desc {
        font-size: 0.95rem;
        opacity: 0.8;
        margin: 0;
    }
}

.hero-right {
    flex: 1;
    max-width: 500px;
}

.points-display {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 16px;

    .points-value {
        font-size: 3rem;
        font-weight: 900;
        background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.7) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        line-height: 1;
    }
    .points-label {
        font-size: 1rem;
        opacity: 0.7;
    }
}

.progress-container {
    .progress-header {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        margin-bottom: 8px;
        opacity: 0.9;

        strong { color: $primary-light; }
        .points-needed { color: $primary-light; font-weight: 600; }
    }
}

.progress-track {
    position: relative;
    height: 12px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: visible; // Чтобы thumb мог выходить за границы
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, $primary 0%, $primary-light 100%);
    border-radius: 10px;
    position: relative;
    transition: width 1.5s cubic-bezier(0.22, 1, 0.36, 1);

    .progress-glow {
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: shimmer 2.5s infinite;
    }
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.progress-thumb {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    background: white;
    border: 3px solid $primary;
    border-radius: 50%;
    box-shadow: 0 0 15px rgba($primary, 0.6);
    transition: left 1.5s cubic-bezier(0.22, 1, 0.36, 1);
    z-index: 3;
}

// ==========================================
// СЕТКА УРОВНЕЙ
// ==========================================
.levels-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 5rem;

    @media (max-width: 1100px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 600px) { grid-template-columns: 1fr; }
}

.level-card {
    position: relative;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid $border;
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);

    &:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
    }

    &.is-current {
        border-color: $primary;
        box-shadow: 0 20px 40px rgba($primary, 0.15);
        transform: scale(1.02);
        z-index: 2;
    }

    &.is-locked {
        opacity: 0.6;
        filter: grayscale(0.8);

        &:hover {
            transform: translateY(-4px);
            filter: grayscale(0.6);
        }
    }
}

.card-glow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.4s;
    pointer-events: none;
}

.is-current .card-glow {
    opacity: 0.05;
}

.card-header {
    padding: 1.5rem;
    color: white;
    text-align: center;
    position: relative;

    .header-icon {
        font-size: 2.5rem;
        margin-bottom: 8px;
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
    }

    .header-points {
        font-size: 0.85rem;
        font-weight: 700;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .lock-overlay {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 28px;
        height: 28px;
        background: rgba(0,0,0,0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }
}

.card-body {
    padding: 1.5rem;
}

.card-body h4 {
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0 0 12px 0;
    color: $dark;
}

.cashback-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba($primary, 0.1);
    color: $primary;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 1.2rem;
}

.perks-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.perks-list li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.9rem;
    color: #475569;
    line-height: 1.4;

    i {
        color: $primary;
        margin-top: 3px;
        font-size: 0.75rem;
        flex-shrink: 0;
        background: rgba($primary, 0.1);
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

// ==========================================
// КАК ЭТО РАБОТАЕТ
// ==========================================
.how-it-works {
    background: white;
    border-radius: 24px;
    padding: 3rem;
    border: 1px solid $border;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);

    h3 {
        text-align: center;
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 3rem;
        color: $dark;
    }
}

.steps-grid {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;

    @media (max-width: 900px) {
        flex-direction: column;
        gap: 30px;
    }
}

.step-card {
    flex: 1;
    text-align: center;
    padding: 2rem 1.5rem;
    border-radius: 20px;
    background: $light;
    border: 1px solid transparent;
    transition: all 0.3s;

    &:hover {
        background: white;
        border-color: $border;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
}

.step-icon {
    width: 70px;
    height: 70px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba($primary, 0.1) 0%, rgba($primary-light, 0.1) 100%);
    color: $primary;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    transition: transform 0.3s;
}

.step-card:hover .step-icon {
    transform: scale(1.1) rotate(5deg);
}

.step-card h5 {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 0.8rem;
    color: $dark;
}

.step-card p {
    font-size: 0.95rem;
    color: $gray;
    line-height: 1.6;
    margin: 0;
}

.step-connector {
    color: $border;
    font-size: 1.2rem;

    @media (max-width: 900px) {
        transform: rotate(90deg);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .shop-loyalty { padding: 60px 0; }
    .section-title { font-size: 2rem; }

    .loyalty-hero {
        padding: 1.5rem;
    }

    .points-display .points-value {
        font-size: 2.2rem;
    }

    .how-it-works {
        padding: 2rem 1.5rem;
    }
}
</style>
