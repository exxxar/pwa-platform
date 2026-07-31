<template>
    <section class="shop-faq">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">
                    <i class="fa-solid fa-circle-question"></i> FAQ
                </span>
                <h2 class="section-title">Часто задаваемые вопросы</h2>
                <p class="section-subtitle">Не нашли ответ? Свяжитесь с нами — мы всегда на связи</p>
            </div>

            <div class="faq-wrapper">
                <div class="faq-list">
                    <div
                        v-for="(item, idx) in mappedFaqItems"
                        :key="item.id || idx"
                        class="faq-item"
                        :class="{ 'open': openIndex === idx }"
                    >
                        <button class="faq-question" @click="toggle(idx)">
                            <div class="question-content">
                                <i :class="item.icon" class="question-icon"></i>
                                <span>{{ item.question }}</span>
                            </div>
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </button>
                        <transition name="accordion">
                            <div v-show="openIndex === idx" class="faq-answer">
                                <p>{{ item.answer }}</p>
                            </div>
                        </transition>
                    </div>
                </div>

                <div class="faq-cta">
                    <div class="cta-icon">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h4>Остались вопросы?</h4>
                    <p>Наша поддержка работает 24/7 и ответит в течение 5 минут</p>
                    <button class="cta-btn" @click="$emit('open-feedback')">
                        <i class="fa-solid fa-comments"></i>
                        Написать в поддержку
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopFaq",
    emits: ['open-feedback'],

    // ❌ Пропс faqData больше не нужен, удаляем его

    data() {
        return {
            openIndex: 0,
            // 🛡️ Дефолтные значения (если в настройках пусто или ничего не пришло)
            defaultFaqItems: [
                {
                    id: 1, icon: 'fa-solid fa-clock', is_visible: true,
                    question: 'Сколько времени занимает доставка?',
                    answer: 'Среднее время доставки — 40-60 минут в зависимости от вашего района и загрузки кухни. В часы пик время может увеличиться на 10-15 минут.'
                },
                {
                    id: 2, icon: 'fa-solid fa-ruble-sign', is_visible: true,
                    question: 'Какая минимальная сумма заказа?',
                    answer: 'Минимальная сумма заказа — 500 рублей для доставки в центр и 1000 рублей для отдалённых районов. При заказе от 2000 рублей доставка бесплатная.'
                },
                {
                    id: 3, icon: 'fa-solid fa-credit-card', is_visible: true,
                    question: 'Какие способы оплаты доступны?',
                    answer: 'Мы принимаем банковские карты (Visa, MasterCard, МИР), оплату через СБП, наличные курьеру, Apple Pay и Google Pay.'
                },
                {
                    id: 4, icon: 'fa-solid fa-coins', is_visible: true,
                    question: 'Как работают бонусы и кэшбэк?',
                    answer: 'С каждого заказа вам начисляется от 3% до 10% бонусами. 1 бонус = 1 рубль. Бонусами можно оплатить до 50% следующего заказа.'
                },
                {
                    id: 5, icon: 'fa-solid fa-rotate-left', is_visible: true,
                    question: 'Можно ли вернуть или обменять заказ?',
                    answer: 'Если качество блюда вас не устроило, мы вернём деньги или заменим заказ бесплатно. Свяжитесь с поддержкой в течение 30 минут.'
                },
                {
                    id: 6, icon: 'fa-solid fa-utensils', is_visible: true,
                    question: 'Можно ли внести изменения в заказ после оформления?',
                    answer: 'Изменить заказ можно в течение 5 минут после оформления, пока он не передан на кухню. Свяжитесь с поддержкой.'
                },
                {
                    id: 7, icon: 'fa-solid fa-gift', is_visible: true,
                    question: 'Есть ли акции и промокоды?',
                    answer: 'Да! Мы регулярно проводим акции и раздаём промокоды в нашем Telegram-канале. Также действует реферальная программа.'
                }
            ]
        };
    },

    computed: {
        // 🆕 Безопасный доступ к глобальному объекту Tenant
        tenant() {
            return window.Tenant || null;
        },

        // 🆕 Берем данные напрямую из tenant.settings.faq
        mappedFaqItems() {
            // Безопасно извлекаем массив FAQ из настроек
            const source = this.tenant?.settings?.faq;

            // Проверяем, что это массив и он не пустой
            const hasData = Array.isArray(source) && source.length > 0;

            // Если данных нет, используем дефолтные
            const finalSource = hasData ? source : this.defaultFaqItems;

            return finalSource
                .filter(item => item.is_visible !== false) // Показываем только те, где is_visible !== false
                .map(item => ({
                    id: item.id,
                    icon: item.icon || 'fa-solid fa-circle-question',
                    question: item.question || 'Вопрос',
                    answer: item.answer || 'Ответ'
                }));
        }
    },

    methods: {
        toggle(idx) {
            this.openIndex = this.openIndex === idx ? null : idx;
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-faq { padding: 80px 0; background: var(--light); }
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

.faq-wrapper {
    display: grid; grid-template-columns: 2fr 1fr; gap: 40px;
    @media (max-width: 992px) { grid-template-columns: 1fr; }
}

.faq-list { display: flex; flex-direction: column; gap: 12px; }

.faq-item {
    background: white; border-radius: 16px; overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: 0.3s;
    border: 1px solid transparent;

    &.open { border-color: var(--primary); box-shadow: 0 10px 30px rgba(255, 122, 0, 0.1); }
}

.faq-question {
    width: 100%; background: transparent; border: none;
    padding: 1.2rem 1.5rem; cursor: pointer;
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; text-align: left; transition: 0.2s;

    &:hover { background: var(--light); }
}

.question-content { display: flex; align-items: center; gap: 12px; flex: 1; }
.question-icon { color: var(--primary); font-size: 1.2rem; width: 24px; text-align: center; }
.question-content span { font-size: 1rem; font-weight: 600; color: var(--dark); }

.toggle-icon {
    color: var(--primary); transition: transform 0.3s ease;

    .open & { transform: rotate(180deg); }
}

.faq-answer {
    padding: 0 1.5rem 1.5rem 4rem;

    p { color: var(--gray); line-height: 1.7; margin: 0; font-size: 0.95rem; }
}

.accordion-enter-active, .accordion-leave-active {
    transition: all 0.3s ease;
    max-height: 500px; overflow: hidden;
}
.accordion-enter-from, .accordion-leave-to {
    max-height: 0; opacity: 0;
}

.faq-cta {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    border-radius: 24px; padding: 2.5rem; color: white;
    text-align: center; position: sticky; top: 100px; height: fit-content;
}
.cta-icon {
    width: 70px; height: 70px; border-radius: 50%;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; margin: 0 auto 1.5rem;
}
.faq-cta h4 { font-size: 1.5rem; font-weight: 800; margin-bottom: 0.8rem; }
.faq-cta p { font-size: 1rem; opacity: 0.95; margin-bottom: 1.5rem; line-height: 1.5; }
.cta-btn {
    background: white; color: var(--primary); border: none;
    padding: 14px 28px; border-radius: 50px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
    transition: 0.3s; font-size: 1rem;

    &:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
}
</style>
