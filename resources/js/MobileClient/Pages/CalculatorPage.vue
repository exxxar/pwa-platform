<template>
    <div class="calculator-page">

        <!-- ========================================== -->
        <!-- РЕЖИМ FAQ (информационный) -->
        <!-- ========================================== -->
        <transition name="mode-fade" mode="out-in">
            <div v-if="faqMode" key="faq-mode" class="faq-mode">

                <!-- HERO СЕКЦИЯ -->
                <div class="hero-section">
                    <div class="hero-bg">
                        <div class="blob blob-1"></div>
                        <div class="blob blob-2"></div>
                        <div class="blob blob-3"></div>
                        <div class="grid-overlay"></div>
                    </div>

                    <div class="hero-content">
                        <div class="hero-badge">
                            <i class="fa-solid fa-calculator"></i>
                            <span>Онлайн-калькулятор</span>
                        </div>
                        <h1 class="hero-title">
                            Рассчитайте стоимость<br>
                            <span class="gradient-text">вашего приложения</span>
                        </h1>
                        <p class="hero-subtitle">
                            Узнайте точную цену и сроки разработки за 2 минуты.
                            Никаких скрытых платежей — только прозрачные расчёты.
                        </p>

                        <div class="hero-features">
                            <div class="hero-feature">
                                <i class="fa-solid fa-bolt"></i>
                                <span>Расчёт за 2 минуты</span>
                            </div>
                            <div class="hero-feature">
                                <i class="fa-solid fa-shield-halved"></i>
                                <span>Без скрытых платежей</span>
                            </div>
                            <div class="hero-feature">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                <span>Фиксированная цена</span>
                            </div>
                        </div>

                        <button class="scroll-btn" @click="scrollToCalculator">
                            <i class="fa-solid fa-arrow-down"></i>
                            <span>Начать расчёт</span>
                        </button>
                    </div>
                </div>

                <!-- ПРЕИМУЩЕСТВА -->
                <div class="benefits-section">
                    <div class="benefits-grid">
                        <div class="benefit-card" v-for="(benefit, index) in benefits" :key="index">
                            <div class="benefit-icon" :style="{ background: benefit.gradient }">
                                <i :class="benefit.icon"></i>
                            </div>
                            <h3>{{ benefit.title }}</h3>
                            <p>{{ benefit.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- БОЛЬШАЯ КНОПКА "НАЧАТЬ РАСЧЁТ" -->
                <div class="start-calc-section">
                    <button class="start-calc-button" @click="startCalculation">
                        <div class="start-calc-bg">
                            <div class="calc-blob calc-blob-1"></div>
                            <div class="calc-blob calc-blob-2"></div>
                            <div class="calc-grid"></div>
                            <div class="calc-particles">
                                <span v-for="i in 15" :key="i" class="calc-particle" :style="particleStyle(i)"></span>
                            </div>
                        </div>

                        <div class="start-calc-content">
                            <div class="start-calc-icon">
                                <i class="fa-solid fa-calculator"></i>
                                <div class="icon-pulse"></div>
                            </div>
                            <div class="start-calc-text">
                                <div class="start-calc-badge">
                                    <i class="fa-solid fa-sparkles"></i>
                                    <span>Готовы узнать цену?</span>
                                </div>
                                <h2>Начать расчёт стоимости</h2>
                                <p>Ответьте на 6 простых вопросов и получите точную стоимость вашего приложения</p>
                            </div>
                            <div class="start-calc-arrow">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>
                        </div>

                        <div class="start-calc-price">
                            <span class="price-from">от</span>
                            <span class="price-value">50 000 ₽</span>
                        </div>
                    </button>
                </div>

                <!-- CTA СЕКЦИЯ -->
                <div class="cta-section">
                    <div class="cta-card">
                        <div class="cta-icon">
                            <i class="fa-solid fa-rocket"></i>
                        </div>
                        <h2>Готовы начать?</h2>
                        <p>
                            Оставьте заявку, и мы свяжемся с вами в течение 15 минут.
                            Обсудим детали и запустим ваш проект!
                        </p>
                        <div class="cta-actions">
                            <button class="cta-btn primary" @click="openContactModal">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Оставить заявку</span>
                            </button>
                            <a href="https://t.me/your_username" target="_blank" class="cta-btn telegram">
                                <i class="fa-brands fa-telegram"></i>
                                <span>Написать в Telegram</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="faq-section">
                    <div class="section-header">
                        <h2>Частые вопросы</h2>
                        <p>Ответы на популярные вопросы о разработке</p>
                    </div>

                    <div class="faq-list">
                        <div
                            v-for="(faq, index) in faqs"
                            :key="index"
                            class="faq-item"
                            :class="{ 'is-open': openFaq === index }"
                        >
                            <button class="faq-question" @click="toggleFaq(index)">
                                <div class="faq-question-content">
                                    <i :class="faq.icon"></i>
                                    <span>{{ faq.question }}</span>
                                </div>
                                <i class="fa-solid fa-chevron-down faq-toggle"></i>
                            </button>
                            <transition name="accordion">
                                <div v-show="openFaq === index" class="faq-answer">
                                    <p>{{ faq.answer }}</p>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- РЕЖИМ КАЛЬКУЛЯТОРА -->
            <!-- ========================================== -->
            <div v-else key="calc-mode" class="calc-mode">

                <!-- Компактный header с кнопкой возврата -->
                <div class="calc-header">
                    <button class="back-btn" @click="backToFAQ">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>К информации</span>
                    </button>
                    <div class="calc-header-title">
                        <i class="fa-solid fa-calculator"></i>
                        <span>Калькулятор стоимости</span>
                    </div>
                    <div class="calc-header-spacer"></div>
                </div>

                <!-- Сам калькулятор -->
                <div class="calculator-section full-mode">
                    <CostCalculator
                        @submit="handleSubmit"
                        @send-email="handleEmail"
                    />
                </div>

            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА ЗАЯВКИ (всегда доступна) -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showContactModal" class="modal-overlay" @click.self="showContactModal = false">
                <div class="contact-modal">
                    <button class="modal-close" @click="showContactModal = false">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <div class="modal-header">
                        <div class="modal-icon">
                            <i class="fa-solid fa-paper-plane"></i>
                        </div>
                        <h2>Оставьте заявку</h2>
                        <p>Мы свяжемся с вами в течение 15 минут</p>
                    </div>

                    <form @submit.prevent="submitContactForm" class="contact-form">
                        <div class="form-group">
                            <label>
                                <i class="fa-solid fa-user"></i>
                                Ваше имя
                            </label>
                            <input
                                v-model="contactForm.name"
                                type="text"
                                placeholder="Иван Иванов"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fa-solid fa-phone"></i>
                                Телефон
                            </label>
                            <input
                                v-model="contactForm.phone"
                                type="tel"
                                placeholder="+7 (999) 123-45-67"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fa-solid fa-envelope"></i>
                                Email
                            </label>
                            <input
                                v-model="contactForm.email"
                                type="email"
                                placeholder="your@email.com"
                            >
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fa-solid fa-comment"></i>
                                Комментарий
                            </label>
                            <textarea
                                v-model="contactForm.comment"
                                placeholder="Расскажите о вашем проекте..."
                                rows="3"
                            ></textarea>
                        </div>

                        <div v-if="calculationSummary" class="calculation-summary">
                            <div class="summary-title">
                                <i class="fa-solid fa-receipt"></i>
                                Ваш расчёт:
                            </div>
                            <div class="summary-price">
                                {{ formatPrice(calculationSummary.total) }}
                            </div>
                            <button
                                type="button"
                                class="reset-summary"
                                @click="calculationSummary = null"
                            >
                                <i class="fa-solid fa-xmark"></i>
                                Убрать
                            </button>
                        </div>

                        <button
                            type="submit"
                            class="submit-btn"
                            :disabled="isSubmitting"
                        >
                            <span v-if="isSubmitting" class="btn-spinner"></span>
                            <template v-else>
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Отправить заявку</span>
                            </template>
                        </button>

                        <p class="form-hint">
                            <i class="fa-solid fa-shield-halved"></i>
                            Нажимая кнопку, вы соглашаетесь с политикой конфиденциальности
                        </p>
                    </form>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- УВЕДОМЛЕНИЕ ОБ УСПЕХЕ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showSuccessModal" class="modal-overlay" @click.self="showSuccessModal = false">
                <div class="success-modal">
                    <div class="success-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2>Заявка отправлена!</h2>
                    <p>Мы свяжемся с вами в течение 15 минут</p>
                    <button class="success-btn" @click="showSuccessModal = false">
                        Отлично!
                    </button>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>
import CostCalculator from '@/MobileClient/Components/CostCalculator.vue';

export default {
    name: 'CalculatorPage',

    components: {
        CostCalculator,
    },

    data() {
        return {
            faqMode: true,
            openFaq: null,
            showContactModal: false,
            showSuccessModal: false,
            isSubmitting: false,
            calculationSummary: null,

            contactForm: {
                name: '',
                phone: '',
                email: '',
                comment: '',
            },

            benefits: [
                {
                    icon: 'fa-solid fa-bolt',
                    title: 'Быстрый расчёт',
                    description: 'Всего 6 простых шагов — и вы получите точную стоимость',
                    gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
                },
                {
                    icon: 'fa-solid fa-eye',
                    title: 'Прозрачность',
                    description: 'Видно, из чего складывается цена — никаких сюрпризов',
                    gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
                },
                {
                    icon: 'fa-solid fa-floppy-disk',
                    title: 'Сохранение прогресса',
                    description: 'Вернитесь к расчёту в любое время — данные не пропадут',
                    gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
                },
                {
                    icon: 'fa-solid fa-hand-holding-dollar',
                    title: 'Фиксированная цена',
                    description: 'После согласования стоимость не изменится',
                    gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'
                },
            ],

            faqs: [
                {
                    icon: 'fa-solid fa-circle-question',
                    question: 'Насколько точен расчёт калькулятора?',
                    answer: 'Калькулятор даёт предварительную оценку с точностью до 90%. Финальная стоимость может незначительно отличаться после детального обсуждения проекта с менеджером, но мы гарантируем, что она не превысит расчётную более чем на 10%.'
                },
                {
                    icon: 'fa-solid fa-clock',
                    question: 'Сколько времени занимает разработка?',
                    answer: 'Стандартный срок — 7-14 дней. Ускоренный — 3-5 дней. Срочный — 1-2 дня. Точные сроки зависят от выбранного функционала и сложности интеграций.'
                },
                {
                    icon: 'fa-solid fa-credit-card',
                    question: 'Как происходит оплата?',
                    answer: 'Оплата производится в два этапа: 50% предоплата перед началом разработки и 50% после сдачи проекта. Принимаем оплату картой, по СБП, безналичным переводом для юрлиц.'
                },
                {
                    icon: 'fa-solid fa-shield-halved',
                    question: 'Есть ли гарантия на работу приложения?',
                    answer: 'Да, мы предоставляем гарантию 3 месяца на все разработанные функции. В течение этого времени бесплатно исправляем любые баги. Также доступна расширенная техподдержка 24/7.'
                },
                {
                    icon: 'fa-solid fa-arrows-rotate',
                    question: 'Можно ли изменить функционал после запуска?',
                    answer: 'Конечно! PWA-приложение легко обновляется. Вы можете добавлять новые функции, изменять дизайн, подключать интеграции в любое время. Стоимость доработок рассчитывается отдельно.'
                },
                {
                    icon: 'fa-solid fa-mobile-screen',
                    question: 'Нужно ли публиковать приложение в App Store и Google Play?',
                    answer: 'Нет! PWA (Progressive Web App) работает через браузер и устанавливается на телефон как обычное приложение через кнопку "Добавить на главный экран". Это экономит время и деньги на публикации.'
                },
                {
                    icon: 'fa-solid fa-users',
                    question: 'Сколько пользователей сможет одновременно работать с приложением?',
                    answer: 'Наши серверы выдерживают до 10 000 одновременных пользователей. Для крупных проектов с большей нагрузкой предлагаем индивидуальные решения с масштабированием.'
                },
                {
                    icon: 'fa-solid fa-headset',
                    question: 'Что если мне нужна помощь после запуска?',
                    answer: 'Мы предоставляем бесплатную поддержку в течение первого месяца. Далее доступна абонентская плата от 5 000 ₽/мес за техподдержку 24/7, обновления и консультации.'
                },
            ],
        };
    },

    methods: {
        /**
         * Генерация стилей для частиц кнопки
         */
        particleStyle(i) {
            const size = Math.random() * 4 + 2;
            return {
                width: `${size}px`,
                height: `${size}px`,
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
                animationDelay: `${Math.random() * 3}s`,
                animationDuration: `${Math.random() * 3 + 3}s`,
            };
        },

        /**
         * Скролл к кнопке "Начать расчёт"
         */
        scrollToCalculator() {
            const element = document.querySelector('.start-calc-section');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },

        /**
         * Переход в режим калькулятора
         */
        startCalculation() {
            this.faqMode = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /**
         * Возврат к FAQ-режиму
         */
        backToFAQ() {
            this.faqMode = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        toggleFaq(index) {
            this.openFaq = this.openFaq === index ? null : index;
        },

        handleSubmit(data) {
            this.calculationSummary = data;
            this.openContactModal();
        },

        handleEmail(data) {
            this.calculationSummary = data;
            this.openContactModal();
        },

        openContactModal() {
            this.showContactModal = true;
        },

        async submitContactForm() {
            if (!this.contactForm.name || !this.contactForm.phone) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Заполните обязательные поля',
                    type: 'error',
                });
                return;
            }

            this.isSubmitting = true;

            try {
                await new Promise(resolve => setTimeout(resolve, 1500));

                this.showContactModal = false;
                this.showSuccessModal = true;

                this.contactForm = {
                    name: '',
                    phone: '',
                    email: '',
                    comment: '',
                };
                this.calculationSummary = null;

            } catch (error) {
                console.error('Ошибка отправки заявки:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить заявку. Попробуйте позже.',
                    type: 'error',
                });
            } finally {
                this.isSubmitting = false;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price || 0) + ' ₽';
        },
    },
};
</script>

<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #667eea;
$primary-dark: #5a67d8;
$primary-light: #7c8cf5;
$accent: #f093fb;
$accent-2: #f5576c;
$gold: #ffd89b;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.calculator-page {
    min-height: 100vh;
    background: $bg;
}

// ==========================================
// ПЕРЕХОД МЕЖДУ РЕЖИМАМИ
// ==========================================
.mode-fade-enter-active,
.mode-fade-leave-active {
    transition: opacity 0.4s ease;
}

.mode-fade-enter-from,
.mode-fade-leave-to {
    opacity: 0;
}

// ==========================================
// РЕЖИМ КАЛЬКУЛЯТОРА - HEADER
// ==========================================
.calc-mode {
    min-height: 100vh;
    background: $bg;
}

.calc-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: $card-bg;
    border-bottom: 1px solid $border;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    backdrop-filter: blur(10px);
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 50px;
    color: $text;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $border;
        transform: translateX(-2px);
    }

    i {
        font-size: 0.8rem;
    }
}

.calc-header-title {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 1rem;
    color: $text;

    i {
        color: $primary;
        font-size: 1.1rem;
    }
}

.calc-header-spacer {
    width: 100px;
}

.calculator-section.full-mode {
    padding-top: 20px;
}

// ==========================================
// HERO СЕКЦИЯ
// ==========================================
.hero-section {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 20px 100px;
    overflow: hidden;
    color: white;
    text-align: center;
}

.hero-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;

    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.3;
    }

    .blob-1 {
        width: 500px;
        height: 500px;
        background: rgba(255, 255, 255, 0.2);
        top: -150px;
        right: -100px;
        animation: float 20s ease-in-out infinite;
    }

    .blob-2 {
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.15);
        bottom: -100px;
        left: -100px;
        animation: float 25s ease-in-out infinite reverse;
    }

    .blob-3 {
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        top: 50%;
        left: 50%;
        animation: float 18s ease-in-out infinite;
    }

    .grid-overlay {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
        background-size: 50px 50px;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
        -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
    }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(30px, -30px) scale(1.1); }
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 24px;
    animation: fadeInDown 0.6s ease;
}

.hero-title {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: 20px;
    animation: fadeInUp 0.6s ease 0.1s both;
}

.gradient-text {
    background: linear-gradient(135deg, #ffd89b 0%, #ff6a00 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-subtitle {
    font-size: 1.15rem;
    opacity: 0.95;
    line-height: 1.6;
    margin-bottom: 32px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    animation: fadeInUp 0.6s ease 0.2s both;
}

.hero-features {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
    margin-bottom: 40px;
    animation: fadeInUp 0.6s ease 0.3s both;
}

.hero-feature {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 500;

    i {
        font-size: 1.1rem;
        color: #ffd89b;
    }
}

.scroll-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    background: white;
    color: $primary;
    border: none;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    animation: fadeInUp 0.6s ease 0.4s both;

    &:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
    }

    i {
        animation: bounce 2s ease-in-out infinite;
    }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(5px); }
}

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

// ==========================================
// ПРЕИМУЩЕСТВА
// ==========================================
.benefits-section {
    padding: 60px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}

.benefit-card {
    background: $card-bg;
    border-radius: 20px;
    padding: 28px 24px;
    text-align: center;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    transition: all 0.3s;
    border: 1px solid $border;

    &:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
    }

    .benefit-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: white;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    h3 {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 8px;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        line-height: 1.5;
        margin: 0;
    }
}

// ==========================================
// БОЛЬШАЯ КНОПКА "НАЧАТЬ РАСЧЁТ"
// ==========================================
.start-calc-section {
    padding: 20px 20px 80px;
    max-width: 900px;
    margin: 0 auto;
}

.start-calc-button {
    position: relative;
    display: block;
    width: 100%;
    padding: 40px 36px;
    background: linear-gradient(135deg, $primary 0%, $accent 50%, $accent-2 100%);
    border: none;
    border-radius: 28px;
    color: white;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 12px 40px rgba($primary, 0.3);
    text-align: left;

    &:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 60px rgba($primary, 0.4);

        .start-calc-arrow {
            transform: translateX(8px);
            background: white;
            color: $primary;
        }

        .calc-blob {
            transform: scale(1.3);
        }
    }

    &:active {
        transform: translateY(-3px) scale(0.99);
    }
}

.start-calc-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.calc-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(40px);
    opacity: 0.4;
    transition: transform 0.6s ease;

    &.calc-blob-1 {
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.3);
        top: -80px;
        right: -60px;
    }

    &.calc-blob-2 {
        width: 250px;
        height: 250px;
        background: rgba(255, 255, 255, 0.2);
        bottom: -60px;
        left: -40px;
    }
}

.calc-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
    background-size: 40px 40px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
}

.calc-particles {
    position: absolute;
    inset: 0;
}

.calc-particle {
    position: absolute;
    background: white;
    border-radius: 50%;
    opacity: 0.5;
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% { transform: translate(0, 0); opacity: 0; }
    10% { opacity: 0.5; }
    90% { opacity: 0.5; }
    100% { transform: translate(30px, -40px); opacity: 0; }
}

.start-calc-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 24px;
}

.start-calc-icon {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    flex-shrink: 0;
    animation: iconFloat 3s ease-in-out infinite;
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

.icon-pulse {
    position: absolute;
    inset: -8px;
    border-radius: 28px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    animation: pulseRing 2s ease-in-out infinite;
}

@keyframes pulseRing {
    0% { transform: scale(1); opacity: 0.6; }
    100% { transform: scale(1.3); opacity: 0; }
}

.start-calc-text {
    flex: 1;
    min-width: 0;
}

.start-calc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    i {
        color: $gold;
        font-size: 0.7rem;
    }
}

.start-calc-text h2 {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0 0 8px;
    line-height: 1.2;
}

.start-calc-text p {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0;
    line-height: 1.5;
}

.start-calc-arrow {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.start-calc-price {
    position: absolute;
    top: 24px;
    right: 28px;
    display: flex;
    align-items: baseline;
    gap: 6px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    z-index: 2;

    .price-from {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .price-value {
        font-size: 1.1rem;
        font-weight: 800;
    }
}

// ==========================================
// CTA СЕКЦИЯ
// ==========================================
.cta-section {
    padding: 40px 20px 80px;
    max-width: 900px;
    margin: 0 auto;
}

.cta-card {
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    border-radius: 24px;
    padding: 48px 32px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;

    &::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
        border-radius: 50%;
    }

    .cta-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 20px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        position: relative;
    }

    h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 12px;
        position: relative;
    }

    p {
        font-size: 1.05rem;
        opacity: 0.95;
        margin-bottom: 32px;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
        position: relative;
    }
}

.cta-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
}

.cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    border: none;

    &.primary {
        background: white;
        color: $primary;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);

        &:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
        }
    }

    &.telegram {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;

        &:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
        }
    }
}

// ==========================================
// FAQ
// ==========================================
.faq-section {
    padding: 60px 20px 80px;
    max-width: 900px;
    margin: 0 auto;
}

.section-header {
    text-align: center;
    margin-bottom: 40px;

    h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 8px;
        color: $text;
    }

    p {
        font-size: 1.05rem;
        color: $text-muted;
        margin: 0;
    }
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.faq-item {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s;

    &.is-open {
        border-color: $primary;
        box-shadow: 0 8px 24px rgba($primary, 0.1);
    }
}

.faq-question {
    width: 100%;
    padding: 18px 20px;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    text-align: left;
    transition: all 0.2s;

    &:hover {
        background: rgba($primary, 0.03);
    }
}

.faq-question-content {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;

    i {
        color: $primary;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    span {
        font-size: 1rem;
        font-weight: 600;
        color: $text;
    }
}

.faq-toggle {
    color: $primary;
    transition: transform 0.3s;
    flex-shrink: 0;

    .is-open & {
        transform: rotate(180deg);
    }
}

.faq-answer {
    padding: 0 20px 20px 52px;

    p {
        font-size: 0.95rem;
        color: $text-muted;
        line-height: 1.6;
        margin: 0;
    }
}

.accordion-enter-active,
.accordion-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.accordion-enter-from,
.accordion-leave-to {
    max-height: 0;
    opacity: 0;
}

// ==========================================
// КАЛЬКУЛЯТОР (секция)
// ==========================================
.calculator-section {
    padding: 40px 20px 80px;
    max-width: 1200px;
    margin: 0 auto;
}

// ==========================================
// МОДАЛКА ЗАЯВКИ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.contact-modal {
    background: $card-bg;
    border-radius: 24px;
    padding: 32px 28px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $danger;
        color: white;
    }
}

.modal-header {
    text-align: center;
    margin-bottom: 24px;

    .modal-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        box-shadow: 0 8px 24px rgba($primary, 0.3);
    }

    h2 {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 8px;
        color: $text;
    }

    p {
        font-size: 0.95rem;
        color: $text-muted;
        margin: 0;
    }
}

.contact-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;

    label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;

        i {
            color: $primary;
            font-size: 0.8rem;
        }
    }

    input,
    textarea {
        padding: 12px 14px;
        border: 2px solid $border;
        border-radius: 12px;
        font-size: 0.95rem;
        background: $card-bg;
        color: $text;
        transition: all 0.2s;
        font-family: inherit;
        resize: vertical;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 4px rgba($primary, 0.1);
        }

        &::placeholder {
            color: #9ca3af;
        }
    }
}

.calculation-summary {
    background: rgba($primary, 0.05);
    border: 1px solid rgba($primary, 0.2);
    border-radius: 14px;
    padding: 16px;
    position: relative;

    .summary-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: $text-muted;
        margin-bottom: 8px;

        i {
            color: $primary;
        }
    }

    .summary-price {
        font-size: 1.8rem;
        font-weight: 800;
        color: $primary;
    }

    .reset-summary {
        position: absolute;
        top: 12px;
        right: 12px;
        background: transparent;
        border: none;
        color: $text-muted;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 6px;
        transition: all 0.2s;

        &:hover {
            background: rgba($danger, 0.1);
            color: $danger;
        }
    }
}

.submit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    border: none;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 8px 24px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba($primary, 0.4);
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
}

.btn-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.form-hint {
    font-size: 0.75rem;
    color: $text-muted;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin: 0;

    i {
        color: $success;
    }
}

// ==========================================
// МОДАЛКА УСПЕХА
// ==========================================
.success-modal {
    background: $card-bg;
    border-radius: 24px;
    padding: 48px 32px;
    max-width: 400px;
    width: 100%;
    text-align: center;
    animation: modalSlideUp 0.3s ease;

    .success-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        background: linear-gradient(135deg, $success 0%, #34d399 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        box-shadow: 0 12px 32px rgba($success, 0.3);
        animation: celebrate 0.6s ease;
    }

    h2 {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 8px;
        color: $text;
    }

    p {
        font-size: 1rem;
        color: $text-muted;
        margin-bottom: 24px;
    }
}

@keyframes celebrate {
    0% { transform: scale(0) rotate(-180deg); }
    50% { transform: scale(1.2) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}

.success-btn {
    padding: 14px 32px;
    background: linear-gradient(135deg, $success 0%, #34d399 100%);
    color: white;
    border: none;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 8px 24px rgba($success, 0.3);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba($success, 0.4);
    }
}

// ==========================================
// АНИМАЦИИ МОДАЛОК
// ==========================================
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .start-calc-button {
        padding: 28px 24px;
    }

    .start-calc-content {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }

    .start-calc-icon {
        width: 72px;
        height: 72px;
        font-size: 1.8rem;
    }

    .start-calc-text h2 {
        font-size: 1.4rem;
    }

    .start-calc-text p {
        font-size: 0.9rem;
    }

    .start-calc-arrow {
        width: 48px;
        height: 48px;
    }

    .start-calc-price {
        position: static;
        margin-top: 16px;
        align-self: center;
    }
}

@media (max-width: 640px) {
    .hero-section {
        padding: 60px 16px 80px;
    }

    .hero-features {
        gap: 16px;
    }

    .hero-feature {
        font-size: 0.85rem;
    }

    .benefits-section,
    .calculator-section,
    .cta-section,
    .faq-section {
        padding-left: 16px;
        padding-right: 16px;
    }

    .section-header h2 {
        font-size: 1.6rem;
    }

    .cta-card {
        padding: 36px 20px;
    }

    .cta-card h2 {
        font-size: 1.5rem;
    }

    .cta-actions {
        flex-direction: column;
    }

    .cta-btn {
        width: 100%;
        justify-content: center;
    }

    .contact-modal {
        padding: 28px 20px;
    }

    .faq-question {
        padding: 14px 16px;
    }

    .faq-answer {
        padding: 0 16px 16px 44px;
    }

    .calc-header {
        padding: 10px 16px;
    }

    .back-btn span {
        display: none;
    }

    .calc-header-title {
        font-size: 0.9rem;
    }
}
</style>
