<template>
    <section class="shop-loyalty">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">
                    <i class="fa-solid fa-crown"></i> Программа лояльности
                </span>
                <h2 class="section-title">Копите бонусы и получайте привилегии</h2>
                <p class="section-subtitle">С каждым заказом ваш статус растёт — открывайте новые возможности</p>
            </div>

            <!-- Прогресс пользователя -->
            <div class="user-progress">
                <div class="progress-header">
                    <div class="current-level">
                        <div class="level-icon" :style="{ background: currentLevel.color }">
                            <i :class="currentLevel.icon"></i>
                        </div>
                        <div>
                            <div class="level-name">{{ currentLevel.name }}</div>
                            <div class="level-desc">{{ currentLevel.description }}</div>
                        </div>
                    </div>
                    <div class="progress-stats">
                        <span>{{ userPoints }} / {{ nextLevel.points }} баллов</span>
                        <span class="highlight">До {{ nextLevel.name }}: {{ nextLevel.points - userPoints }} баллов</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
                </div>
            </div>

            <!-- Уровни -->
            <div class="levels-grid">
                <div
                    v-for="level in levels"
                    :key="level.id"
                    class="level-card"
                    :class="{ 'current': level.id === currentLevel.id, 'locked': userPoints < level.points }"
                >
                    <div class="level-header" :style="{ background: level.color }">
                        <i :class="level.icon"></i>
                        <div class="level-points">{{ level.points }} баллов</div>
                    </div>
                    <div class="level-body">
                        <h4>{{ level.name }}</h4>
                        <p class="level-cashback">Кэшбэк {{ level.cashback }}%</p>
                        <ul class="level-perks">
                            <li v-for="(perk, idx) in level.perks" :key="idx">
                                <i class="fa-solid fa-check"></i>
                                {{ perk }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Как это работает -->
            <div class="how-it-works">
                <h3>Как это работает?</h3>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h5>Делайте заказы</h5>
                        <p>С каждой покупки начисляется до 10% бонусами</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h5>Копите баллы</h5>
                        <p>1 балл = 1 рубль. Тратьте на любые заказы</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h5>Повышайте статус</h5>
                        <p>Чем больше баллов — тем выше уровень и привилегии</p>
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
                    description: 'Стартовый уровень',
                    perks: ['Кэшбэк 3% с заказов', 'Приветственный бонус 100 баллов', 'Доступ к акциям']
                },
                {
                    id: 'silver',
                    name: 'Silver',
                    icon: 'fa-solid fa-award',
                    color: 'linear-gradient(135deg, #c0c0c0 0%, #808080 100%)',
                    points: 1000,
                    cashback: 5,
                    description: 'Для постоянных клиентов',
                    perks: ['Кэшбэк 5%', 'Бесплатная доставка', 'Приоритетная поддержка', 'День рождения: +500 баллов']
                },
                {
                    id: 'gold',
                    name: 'Gold',
                    icon: 'fa-solid fa-trophy',
                    color: 'linear-gradient(135deg, #ffd700 0%, #ffb300 100%)',
                    points: 3000,
                    cashback: 7,
                    description: 'Для настоящих гурманов',
                    perks: ['Кэшбэк 7%', 'Бесплатная доставка всегда', 'Эксклюзивные акции', 'Подарок раз в месяц', 'Ранний доступ к новинкам']
                },
                {
                    id: 'platinum',
                    name: 'Platinum',
                    icon: 'fa-solid fa-crown',
                    color: 'linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%)',
                    points: 7000,
                    cashback: 10,
                    description: 'VIP-статус',
                    perks: ['Кэшбэк 10%', 'Персональный менеджер', 'Приглашения на закрытые мероприятия', 'Подарки к праздникам', 'Максимальный приоритет']
                }
            ]
        };
    },
    computed: {
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
            return Math.min(100, (progress / range) * 100);
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-loyalty { padding: 80px 0; background: linear-gradient(180deg, var(--light) 0%, white 100%); }
.section-header { text-align: center; margin-bottom: 3rem; }
.section-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255, 122, 0, 0.1); color: var(--primary);
    padding: 8px 16px; border-radius: 50px; font-weight: 700;
    font-size: 0.9rem; margin-bottom: 1rem;
    border: 1px solid rgba(255, 122, 0, 0.2);
}
.section-title { font-size: 2.5rem; font-weight: 900; margin-bottom: 1rem; color: var(--dark); }
.section-subtitle { font-size: 1.1rem; color: var(--gray); max-width: 600px; margin: 0 auto; }

.user-progress {
    background: white; border-radius: 24px; padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 3rem;
}
.progress-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 1.5rem; flex-wrap: wrap; gap: 16px;
}
.current-level { display: flex; align-items: center; gap: 16px; }
.level-icon {
    width: 56px; height: 56px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: white;
}
.level-name { font-size: 1.3rem; font-weight: 800; color: var(--dark); }
.level-desc { font-size: 0.9rem; color: var(--gray); }
.progress-stats { text-align: right; }
.progress-stats span { display: block; font-size: 0.9rem; color: var(--gray); }
.progress-stats .highlight { color: var(--primary); font-weight: 700; margin-top: 4px; }

.progress-bar {
    height: 12px; background: var(--light); border-radius: 6px; overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
    border-radius: 6px; transition: width 1s ease;
    position: relative;

    &::after {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 2s infinite;
    }
}
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.levels-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 3rem;
    @media (max-width: 992px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 576px) { grid-template-columns: 1fr; }
}

.level-card {
    background: white; border-radius: 20px; overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s;
    border: 2px solid transparent;

    &.current { border-color: var(--primary); transform: translateY(-8px); box-shadow: 0 15px 30px rgba(255, 122, 0, 0.15); }
    &.locked { opacity: 0.7; }
    &:hover:not(.locked) { transform: translateY(-8px); }
}

.level-header {
    padding: 1.5rem; color: white; text-align: center;
    display: flex; flex-direction: column; align-items: center; gap: 8px;

    i { font-size: 2.5rem; }
    .level-points { font-size: 0.85rem; font-weight: 700; opacity: 0.9; }
}

.level-body { padding: 1.5rem; }
.level-body h4 { font-size: 1.3rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--dark); }
.level-cashback {
    display: inline-block; background: rgba(255, 122, 0, 0.1);
    color: var(--primary); padding: 4px 12px; border-radius: 20px;
    font-size: 0.85rem; font-weight: 700; margin-bottom: 1rem;
}
.level-perks { list-style: none; padding: 0; margin: 0; }
.level-perks li {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 6px 0; font-size: 0.9rem; color: var(--dark);

    i { color: var(--primary); margin-top: 3px; font-size: 0.8rem; }
}

.how-it-works {
    background: white; border-radius: 24px; padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);

    h3 { text-align: center; font-size: 1.8rem; font-weight: 800; margin-bottom: 2rem; color: var(--dark); }
}
.steps {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;
    @media (max-width: 768px) { grid-template-columns: 1fr; }
}
.step { text-align: center; }
.step-number {
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white; font-size: 1.5rem; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem; box-shadow: 0 10px 20px rgba(255, 122, 0, 0.25);
}
.step h5 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--dark); }
.step p { font-size: 0.9rem; color: var(--gray); line-height: 1.5; }
</style>
