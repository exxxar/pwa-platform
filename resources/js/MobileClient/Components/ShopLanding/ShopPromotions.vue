<template>
    <section class="shop-promotions">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">
                    <i class="fa-solid fa-fire"></i> Горячие предложения
                </span>
                <h2 class="section-title">Акции и спецпредложения</h2>
                <p class="section-subtitle">Успейте воспользоваться выгодными предложениями — время ограничено!</p>
            </div>

            <div class="promotions-grid">
                <div
                    v-for="promo in promotions"
                    :key="promo.id"
                    class="promo-card"
                    :style="{ background: promo.gradient }"
                >
                    <div class="promo-badge" v-if="promo.badge">{{ promo.badge }}</div>

                    <div class="promo-content">
                        <div class="promo-icon">
                            <i :class="promo.icon"></i>
                        </div>
                        <h3 class="promo-title">{{ promo.title }}</h3>
                        <p class="promo-desc">{{ promo.description }}</p>

                        <!-- Таймер -->
                        <div v-if="promo.endsAt" class="promo-timer">
                            <div class="timer-label">До конца акции:</div>
                            <div class="timer-blocks">
                                <div class="timer-block">
                                    <span class="timer-value">{{ getTimeLeft(promo.endsAt).days }}</span>
                                    <span class="timer-unit">дн</span>
                                </div>
                                <div class="timer-block">
                                    <span class="timer-value">{{ getTimeLeft(promo.endsAt).hours }}</span>
                                    <span class="timer-unit">ч</span>
                                </div>
                                <div class="timer-block">
                                    <span class="timer-value">{{ getTimeLeft(promo.endsAt).minutes }}</span>
                                    <span class="timer-unit">мин</span>
                                </div>
                                <div class="timer-block">
                                    <span class="timer-value">{{ getTimeLeft(promo.endsAt).seconds }}</span>
                                    <span class="timer-unit">сек</span>
                                </div>
                            </div>
                        </div>

                        <button class="promo-btn" @click="usePromo(promo)">
                            {{ promo.buttonText || 'Воспользоваться' }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopPromotions",
    props: {
        promotions: {
            type: Array,
            default: () => [
                {
                    id: 1,
                    title: '2 пиццы по цене 1',
                    description: 'Закажите любую большую пиццу и получите вторую в подарок!',
                    icon: 'fa-solid fa-pizza-slice',
                    gradient: 'linear-gradient(135deg, #ff7a00 0%, #ff9500 100%)',
                    badge: 'ХИТ',
                    endsAt: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString(),
                    buttonText: 'Заказать'
                },
                {
                    id: 2,
                    title: 'Скидка 20% на первый заказ',
                    description: 'Для новых клиентов — приятный бонус на первый заказ от 1000₽',
                    icon: 'fa-solid fa-percent',
                    gradient: 'linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%)',
                    badge: 'НОВИЧКАМ',
                    endsAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
                    buttonText: 'Активировать'
                },
                {
                    id: 3,
                    title: 'Комбо-обед за 490₽',
                    description: 'Бургер + картофель фри + напиток. Выгода 30%!',
                    icon: 'fa-solid fa-burger',
                    gradient: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                    badge: 'ВЫГОДНО',
                    endsAt: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString(),
                    buttonText: 'Выбрать комбо'
                }
            ]
        }
    },
    data() {
        return {
            now: Date.now(),
            timerInterval: null
        };
    },
    mounted() {
        this.timerInterval = setInterval(() => {
            this.now = Date.now();
        }, 1000);
    },
    beforeUnmount() {
        if (this.timerInterval) clearInterval(this.timerInterval);
    },
    methods: {
        getTimeLeft(endsAt) {
            const diff = new Date(endsAt).getTime() - this.now;
            if (diff <= 0) return { days: '00', hours: '00', minutes: '00', seconds: '00' };

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const minutes = Math.floor((diff / (1000 * 60)) % 60);
            const seconds = Math.floor((diff / 1000) % 60);

            return {
                days: String(days).padStart(2, '0'),
                hours: String(hours).padStart(2, '0'),
                minutes: String(minutes).padStart(2, '0'),
                seconds: String(seconds).padStart(2, '0')
            };
        },
        usePromo(promo) {
            this.$notify?.({
                title: 'Акция активирована!',
                text: promo.title,
                type: 'success'
            });
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-promotions { padding: 80px 0; background: var(--light); }
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

.promotions-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
    @media (max-width: 992px) { grid-template-columns: 1fr; }
}

.promo-card {
    position: relative; border-radius: 24px; padding: 2.5rem;
    color: white; overflow: hidden; transition: 0.3s;
    min-height: 380px; display: flex; flex-direction: column;

    &:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }

    &::before {
        content: ''; position: absolute; top: -50%; right: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    }
}

.promo-badge {
    position: absolute; top: 20px; right: 20px;
    background: rgba(255,255,255,0.25); backdrop-filter: blur(10px);
    padding: 6px 14px; border-radius: 20px; font-size: 0.75rem;
    font-weight: 800; letter-spacing: 1px;
}

.promo-content { position: relative; z-index: 2; display: flex; flex-direction: column; height: 100%; }
.promo-icon {
    width: 64px; height: 64px; border-radius: 16px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; margin-bottom: 1.5rem;
}
.promo-title { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.8rem; }
.promo-desc { font-size: 1rem; opacity: 0.95; line-height: 1.5; margin-bottom: 1.5rem; }

.promo-timer { margin-bottom: 1.5rem; }
.timer-label { font-size: 0.85rem; opacity: 0.9; margin-bottom: 10px; font-weight: 600; }
.timer-blocks { display: flex; gap: 8px; }
.timer-block {
    background: rgba(0,0,0,0.25); backdrop-filter: blur(10px);
    padding: 10px 12px; border-radius: 10px; text-align: center;
    min-width: 55px;
}
.timer-value { display: block; font-size: 1.4rem; font-weight: 900; line-height: 1; }
.timer-unit { font-size: 0.7rem; opacity: 0.8; margin-top: 2px; display: block; }

.promo-btn {
    margin-top: auto; background: white; color: var(--dark);
    border: none; padding: 14px 24px; border-radius: 12px;
    font-weight: 700; cursor: pointer; display: flex;
    align-items: center; justify-content: center; gap: 8px;
    transition: 0.3s; font-size: 1rem;

    &:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
}
</style>
