<template>
    <div class="about-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="hero-section">
            <div class="hero-bg">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>

            <div class="hero-content">
                <div class="hero-logo">
                    <i class="fa-solid fa-rocket"></i>
                </div>
                <h1 class="hero-title">О платформе PWA Store</h1>
                <p class="hero-subtitle">
                    Современная платформа для создания мобильных приложений
                    малого и среднего бизнеса без публикации в App Store и Google Play
                </p>

                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">1</div>
                        <div class="stat-label">день запуска</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">кастомизация</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">поддержка</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КЛЮЧЕВЫЕ ПРЕИМУЩЕСТВА -->
        <!-- ========================================== -->
        <div class="section">
            <h2 class="section-title">Почему выбирают нас</h2>
            <p class="section-subtitle">Всё необходимое для вашего бизнеса в одном месте</p>

            <div class="advantages-grid">
                <div class="advantage-card" v-for="(adv, index) in advantages" :key="index"
                     :style="{ animationDelay: `${index * 0.1}s` }">
                    <div class="advantage-icon" :style="{ background: adv.gradient }">
                        <i :class="adv.icon"></i>
                    </div>
                    <h3 class="advantage-title">{{ adv.title }}</h3>
                    <p class="advantage-desc">{{ adv.description }}</p>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ФУНКЦИОНАЛ ПЛАТФОРМЫ -->
        <!-- ========================================== -->
        <div class="section features-section">
            <h2 class="section-title">Возможности платформы</h2>
            <p class="section-subtitle">Полный набор инструментов для управления бизнесом</p>

            <div class="features-list">

                <!-- Магазин и каталог -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper shop-icon">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Интернет-магазин</h3>
                            <p>Полноценный каталог товаров с категориями и фильтрами</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Каталог товаров с фотографиями и описаниями</li>
                            <li><i class="fa-solid fa-check"></i> Категории и подкатегории товаров</li>
                            <li><i class="fa-solid fa-check"></i> Поиск и фильтрация по параметрам</li>
                            <li><i class="fa-solid fa-check"></i> Избранные товары с синхронизацией</li>
                            <li><i class="fa-solid fa-check"></i> Весовые товары с расчётом цены</li>
                            <li><i class="fa-solid fa-check"></i> Акции и скидки на товары</li>
                            <li><i class="fa-solid fa-check"></i> Рекомендации и популярные товары</li>
                        </ul>
                    </div>
                </div>

                <!-- Корзина и заказы -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper cart-icon">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Корзина и оформление заказов</h3>
                            <p>Удобный процесс покупки с множеством опций</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Корзина с автоматическим подсчётом суммы</li>
                            <li><i class="fa-solid fa-check"></i> Изменение количества товаров</li>
                            <li><i class="fa-solid fa-check"></i> Промокоды и купоны на скидку</li>
                            <li><i class="fa-solid fa-check"></i> Выбор способа доставки или самовывоза</li>
                            <li><i class="fa-solid fa-check"></i> Расчёт стоимости доставки по адресу</li>
                            <li><i class="fa-solid fa-check"></i> Добавление комментариев к заказу</li>
                            <li><i class="fa-solid fa-check"></i> История заказов с возможностью повтора</li>
                            <li><i class="fa-solid fa-check"></i> Отслеживание статуса заказа</li>
                        </ul>
                    </div>
                </div>

                <!-- Оплата -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper payment-icon">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Оплата заказов</h3>
                            <p>Различные способы оплаты для удобства клиентов</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Оплата банковской картой онлайн</li>
                            <li><i class="fa-solid fa-check"></i> Система быстрых платежей (СБП)</li>
                            <li><i class="fa-solid fa-check"></i> Оплата при получении (наличные/карта)</li>
                            <li><i class="fa-solid fa-check"></i> Оплата переводом на карту</li>
                            <li><i class="fa-solid fa-check"></i> Использование кэшбэка для оплаты</li>
                            <li><i class="fa-solid fa-check"></i> Безопасные платежи с защитой данных</li>
                        </ul>
                    </div>
                </div>

                <!-- Бронирование столиков -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper booking-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Бронирование столиков</h3>
                            <p>Идеально для ресторанов, кафе и баров</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Онлайн-бронирование столиков</li>
                            <li><i class="fa-solid fa-check"></i> Выбор даты, времени и количества гостей</li>
                            <li><i class="fa-solid fa-check"></i> Автоматические подтверждения</li>
                            <li><i class="fa-solid fa-check"></i> История бронирований</li>
                            <li><i class="fa-solid fa-check"></i> Отмена и перенос бронирований</li>
                            <li><i class="fa-solid fa-check"></i> Уведомления о предстоящих визитах</li>
                        </ul>
                    </div>
                </div>

                <!-- Система лояльности -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper loyalty-icon">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Программа лояльности</h3>
                            <p>Удержание клиентов через бонусы и акции</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Накопительный кэшбэк с каждой покупки</li>
                            <li><i class="fa-solid fa-check"></i> Оплата бонусами до 30% от суммы заказа</li>
                            <li><i class="fa-solid fa-check"></i> Персональные промокоды</li>
                            <li><i class="fa-solid fa-check"></i> Акции и специальные предложения</li>
                            <li><i class="fa-solid fa-check"></i> Подарки на день рождения</li>
                            <li><i class="fa-solid fa-check"></i> Колесо фортуны с призами</li>
                            <li><i class="fa-solid fa-check"></i> Реферальная программа</li>
                        </ul>
                    </div>
                </div>

                <!-- Профиль пользователя -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper profile-icon">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Личный кабинет</h3>
                            <p>Все данные в одном месте</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Профиль с персональными данными</li>
                            <li><i class="fa-solid fa-check"></i> История всех заказов</li>
                            <li><i class="fa-solid fa-check"></i> Сохранённые адреса доставки</li>
                            <li><i class="fa-solid fa-check"></i> Баланс кэшбэка и бонусов</li>
                            <li><i class="fa-solid fa-check"></i> Избранные товары</li>
                            <li><i class="fa-solid fa-check"></i> Настройки уведомлений</li>
                            <li><i class="fa-solid fa-check"></i> Отзывы о товарах</li>
                        </ul>
                    </div>
                </div>

                <!-- Чат и поддержка -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper chat-icon">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Чат и поддержка</h3>
                            <p>Быстрая связь с клиентами и поддержкой</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Встроенный чат с менеджером</li>
                            <li><i class="fa-solid fa-check"></i> История переписки</li>
                            <li><i class="fa-solid fa-check"></i> Отправка фотографий и файлов</li>
                            <li><i class="fa-solid fa-check"></i> Уведомления о новых сообщениях</li>
                            <li><i class="fa-solid fa-check"></i> Быстрые ответы на вопросы</li>
                            <li><i class="fa-solid fa-check"></i> Форма обратной связи</li>
                        </ul>
                    </div>
                </div>

                <!-- Для рестораторов -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper restaurant-icon">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Для ресторанов и кафе</h3>
                            <p>Специальные функции для HoReCa</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Заказ за столиком через QR-код</li>
                            <li><i class="fa-solid fa-check"></i> Вызов официанта одним нажатием</li>
                            <li><i class="fa-solid fa-check"></i> Раздельный счёт для компании</li>
                            <li><i class="fa-solid fa-check"></i> Предзаказ блюд к определённому времени</li>
                            <li><i class="fa-solid fa-check"></i> Интеграция с iiko и FrontPad</li>
                            <li><i class="fa-solid fa-check"></i> Управление меню в реальном времени</li>
                        </ul>
                    </div>
                </div>

                <!-- Кофейная программа -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper coffee-icon">
                            <i class="fa-solid fa-mug-hot"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Кофейная программа</h3>
                            <p>Специально для кофеен</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Учёт собранных чашек кофе</li>
                            <li><i class="fa-solid fa-check"></i> Автоматический подсчёт для бесплатной чашки</li>
                            <li><i class="fa-solid fa-check"></i> Визуальный прогресс-бар</li>
                            <li><i class="fa-solid fa-check"></i> Награды за лояльность</li>
                            <li><i class="fa-solid fa-check"></i> Статистика посещений</li>
                        </ul>
                    </div>
                </div>

                <!-- Интеграции -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper integrations-icon">
                            <i class="fa-solid fa-plug"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Интеграции и автоматизация</h3>
                            <p>Подключение к популярным сервисам</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Синхронизация с 1С</li>
                            <li><i class="fa-solid fa-check"></i> Интеграция с iiko для ресторанов</li>
                            <li><i class="fa-solid fa-check"></i> Подключение FrontPad</li>
                            <li><i class="fa-solid fa-check"></i> Интеграция с CRM-системами</li>
                            <li><i class="fa-solid fa-check"></i> Автоматическая загрузка товаров из ВК</li>
                            <li><i class="fa-solid fa-check"></i> Экспорт заказов в Excel</li>
                            <li><i class="fa-solid fa-check"></i> Уведомления в Telegram-бота</li>
                        </ul>
                    </div>
                </div>

                <!-- Аналитика -->
                <div class="feature-block">
                    <div class="feature-header">
                        <div class="feature-icon-wrapper analytics-icon">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="feature-header-text">
                            <h3>Аналитика и отчётность</h3>
                            <p>Данные для принятия решений</p>
                        </div>
                    </div>
                    <div class="feature-content">
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-check"></i> Статистика продаж по периодам</li>
                            <li><i class="fa-solid fa-check"></i> Анализ популярных товаров</li>
                            <li><i class="fa-solid fa-check"></i> Отчёты по клиентам</li>
                            <li><i class="fa-solid fa-check"></i> Конверсия и воронка продаж</li>
                            <li><i class="fa-solid fa-check"></i> Экспорт данных в различных форматах</li>
                            <li><i class="fa-solid fa-check"></i> Дашборд с ключевыми метриками</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- КАК ЭТО РАБОТАЕТ -->
        <!-- ========================================== -->
        <div class="section how-it-works">
            <h2 class="section-title">Как это работает</h2>
            <p class="section-subtitle">Простой процесс запуска вашего приложения</p>

            <div class="steps-container">
                <div class="step-item" v-for="(step, index) in steps" :key="index">
                    <div class="step-number">{{ index + 1 }}</div>
                    <div class="step-content">
                        <h3 class="step-title">{{ step.title }}</h3>
                        <p class="step-description">{{ step.description }}</p>
                    </div>
                    <div class="step-connector" v-if="index < steps.length - 1"></div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ТЕХНОЛОГИИ -->
        <!-- ========================================== -->
        <div class="section technologies-section">
            <h2 class="section-title">Современные технологии</h2>
            <p class="section-subtitle">Надёжная и быстрая платформа</p>

            <div class="tech-grid">
                <div class="tech-item" v-for="(tech, index) in technologies" :key="index">
                    <div class="tech-icon" :style="{ background: tech.color }">
                        <i :class="tech.icon"></i>
                    </div>
                    <div class="tech-info">
                        <h4>{{ tech.name }}</h4>
                        <p>{{ tech.description }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОНТАКТЫ -->
        <!-- ========================================== -->
        <div class="section contacts-section">
            <div class="contacts-card">
                <div class="contacts-header">
                    <div class="contacts-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <h2>Свяжитесь с нами</h2>
                    <p>Есть вопросы или хотите начать работу? Мы всегда на связи!</p>
                </div>

                <div class="contacts-list">
                    <a :href="contacts.telegram" target="_blank" class="contact-card telegram-card">
                        <div class="contact-icon">
                            <i class="fa-brands fa-telegram"></i>
                        </div>
                        <div class="contact-info">
                            <h4>Telegram</h4>
                            <p>{{ contacts.telegramLabel }}</p>
                        </div>
                        <i class="fa-solid fa-arrow-right contact-arrow"></i>
                    </a>

                    <a :href="'mailto:' + contacts.email" class="contact-card email-card">
                        <div class="contact-icon">
                            <i class="fa-solid fa-at"></i>
                        </div>
                        <div class="contact-info">
                            <h4>Email</h4>
                            <p>{{ contacts.email }}</p>
                        </div>
                        <i class="fa-solid fa-arrow-right contact-arrow"></i>
                    </a>

                    <a :href="'tel:' + contacts.phoneClean" class="contact-card phone-card">
                        <div class="contact-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="contact-info">
                            <h4>Телефон</h4>
                            <p>{{ contacts.phone }}</p>
                        </div>
                        <i class="fa-solid fa-arrow-right contact-arrow"></i>
                    </a>
                </div>

                <a :href="contacts.website" target="_blank" class="website-button">
                    <i class="fa-solid fa-globe"></i>
                    <span>Перейти на сайт разработчика</span>
                </a>
            </div>
        </div>

        <div class="p-3">
            <CalculatorButton
                mode="card"
                title="Создайте своё приложение"
                description="Рассчитайте стоимость разработки онлайн"
                badge="Онлайн-калькулятор"
                :show-price="true"
            />
        </div>


    </div>
</template>

<script>
import CalculatorButton from "@/MobileClient/Components/CalculatorButton.vue";
export default {
    name: "AboutPlatform",
    components:{
        CalculatorButton
    },
    data() {
        return {
            currentYear: new Date().getFullYear(),

            contacts: {
                telegram: 'https://t.me/your_username',
                telegramLabel: '@your_username',
                email: 'support@pwa-store.ru',
                phone: '+7 (999) 123-45-67',
                phoneClean: '+79991234567',
                website: 'https://your-website.ru'
            },

            advantages: [
                {
                    icon: 'fa-solid fa-bolt',
                    title: 'Быстрый запуск',
                    description: 'Запустите своё приложение за 1 день без сложной разработки',
                    gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
                },
                {
                    icon: 'fa-solid fa-mobile-screen',
                    title: 'PWA технология',
                    description: 'Работает как нативное приложение без установки из сторов',
                    gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
                },
                {
                    icon: 'fa-solid fa-palette',
                    title: 'Полная кастомизация',
                    description: 'Настройте дизайн и цвета под ваш бренд',
                    gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
                },
                {
                    icon: 'fa-solid fa-shield-halved',
                    title: 'Безопасность',
                    description: 'Защита данных и безопасные платежи',
                    gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'
                },
                {
                    icon: 'fa-solid fa-headset',
                    title: 'Поддержка 24/7',
                    description: 'Всегда готовы помочь с любыми вопросами',
                    gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
                },
                {
                    icon: 'fa-solid fa-chart-line',
                    title: 'Аналитика',
                    description: 'Подробная статистика для развития бизнеса',
                    gradient: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'
                }
            ],

            steps: [
                {
                    title: 'Оставьте заявку',
                    description: 'Свяжитесь с нами любым удобным способом и расскажите о вашем бизнесе'
                },
                {
                    title: 'Обсуждение деталей',
                    description: 'Мы обсудим ваши требования, функционал и дизайн будущего приложения'
                },
                {
                    title: 'Настройка платформы',
                    description: 'Мы настроим каталог, оплату, интеграции и все необходимые функции'
                },
                {
                    title: 'Тестирование',
                    description: 'Проведём полное тестирование всех функций вместе с вами'
                },
                {
                    title: 'Запуск!',
                    description: 'Ваше приложение готово к работе. Клиенты могут делать заказы!'
                }
            ],

            technologies: [
                {
                    icon: 'fa-brands fa-vuejs',
                    name: 'Vue.js 3',
                    description: 'Современный фреймворк для быстрого UI',
                    color: 'linear-gradient(135deg, #42b883 0%, #35495e 100%)'
                },
                {
                    icon: 'fa-solid fa-database',
                    name: 'Pinia',
                    description: 'Надёжное управление состоянием',
                    color: 'linear-gradient(135deg, #ffd859 0%, #ff7b54 100%)'
                },
                {
                    icon: 'fa-solid fa-server',
                    name: 'Laravel',
                    description: 'Мощный backend на PHP',
                    color: 'linear-gradient(135deg, #ff2d20 0%, #eb4432 100%)'
                },
                {
                    icon: 'fa-solid fa-mobile-alt',
                    name: 'PWA',
                    description: 'Progressive Web Apps технология',
                    color: 'linear-gradient(135deg, #5a67d8 0%, #7c3aed 100%)'
                },
                {
                    icon: 'fa-solid fa-bolt',
                    name: 'Vite',
                    description: 'Молниеносная сборка и hot-reload',
                    color: 'linear-gradient(135deg, #646cff 0%, #9333ea 100%)'
                },
                {
                    icon: 'fa-solid fa-lock',
                    name: 'HTTPS',
                    description: 'Безопасное шифрование данных',
                    color: 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
                }
            ]
        };
    }
};
</script>

<style lang="scss" scoped>
.about-page {
    min-height: 100vh;
    background: #f8f9fa;
}

// ==========================================
// HERO СЕКЦИЯ
// ==========================================
.hero-section {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 20px 80px;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;

    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: 0.3;
    }

    .blob-1 {
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.2);
        top: -100px;
        right: -100px;
        animation: float 20s ease-in-out infinite;
    }

    .blob-2 {
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.15);
        bottom: -50px;
        left: -50px;
        animation: float 25s ease-in-out infinite reverse;
    }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(30px, -30px) scale(1.1); }
}

.hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    color: white;
    max-width: 800px;
    margin: 0 auto;
}

.hero-logo {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 16px;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.1rem;
    opacity: 0.95;
    line-height: 1.6;
    margin-bottom: 32px;
}

.hero-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: rgba(255, 255, 255, 0.3);
}

// ==========================================
// СЕКЦИИ
// ==========================================
.section {
    padding: 60px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.section-title {
    font-size: 2rem;
    font-weight: 800;
    text-align: center;
    margin-bottom: 12px;
    color: #1f2937;
}

.section-subtitle {
    font-size: 1.1rem;
    text-align: center;
    color: #6b7280;
    margin-bottom: 48px;
}

// ==========================================
// ПРЕИМУЩЕСТВА
// ==========================================
.advantages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.advantage-card {
    background: white;
    border-radius: 20px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    animation: fadeInUp 0.6s ease-out forwards;
    opacity: 0;

    &:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.advantage-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 20px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.advantage-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: #1f2937;
}

.advantage-desc {
    font-size: 0.95rem;
    color: #6b7280;
    line-height: 1.6;
}

// ==========================================
// ФУНКЦИОНАЛ
// ==========================================
.features-section {
    background: white;
}

.features-list {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.feature-block {
    background: #f9fafb;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;

    &:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
}

.feature-header {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
}

.feature-icon-wrapper {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
}

.shop-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.cart-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.payment-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.booking-icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.loyalty-icon { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
.profile-icon { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
.chat-icon { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
.restaurant-icon { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }
.coffee-icon { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); }
.integrations-icon { background: linear-gradient(135deg, #5a67d8 0%, #7c3aed 100%); }
.analytics-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }

.feature-header-text {
    flex: 1;

    h3 {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1f2937;
    }

    p {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0;
    }
}

.feature-content {
    padding: 24px;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;

    li {
        display: flex;
        align-items: start;
        gap: 12px;
        font-size: 0.95rem;
        color: #374151;
        line-height: 1.5;

        i {
            color: #10b981;
            font-size: 0.9rem;
            margin-top: 4px;
            flex-shrink: 0;
        }
    }
}

// ==========================================
// КАК ЭТО РАБОТАЕТ
// ==========================================
.how-it-works {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;

    .section-title,
    .section-subtitle {
        color: white;
    }

    .section-subtitle {
        opacity: 0.95;
    }
}

.steps-container {
    max-width: 700px;
    margin: 0 auto;
    position: relative;
}

.step-item {
    position: relative;
    padding-left: 80px;
    padding-bottom: 40px;

    &:last-child {
        padding-bottom: 0;
    }
}

.step-number {
    position: absolute;
    left: 0;
    top: 0;
    width: 56px;
    height: 56px;
    background: white;
    color: #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 800;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.step-content {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 24px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.step-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.step-description {
    font-size: 0.95rem;
    opacity: 0.95;
    line-height: 1.6;
    margin: 0;
}

.step-connector {
    position: absolute;
    left: 27px;
    top: 56px;
    bottom: 0;
    width: 2px;
    background: rgba(255, 255, 255, 0.3);
}

// ==========================================
// ТЕХНОЛОГИИ
// ==========================================
.technologies-section {
    background: #f9fafb;
}

.tech-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.tech-item {
    background: white;
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
}

.tech-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.tech-info {
    flex: 1;

    h4 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1f2937;
    }

    p {
        font-size: 0.85rem;
        color: #6b7280;
        margin: 0;
        line-height: 1.4;
    }
}

// ==========================================
// КОНТАКТЫ
// ==========================================
.contacts-section {
    background: white;
}

.contacts-card {
    max-width: 700px;
    margin: 0 auto;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 48px 32px;
    box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3);
}

.contacts-header {
    text-align: center;
    color: white;
    margin-bottom: 40px;
}

.contacts-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 20px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.contacts-header h2 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 12px;
}

.contacts-header p {
    font-size: 1.05rem;
    opacity: 0.95;
    margin: 0;
}

.contacts-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 32px;
}

.contact-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: white;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s ease;

    &:hover {
        transform: translateX(8px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
}

.contact-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.telegram-card .contact-icon {
    background: linear-gradient(135deg, #0088cc 0%, #00a0dc 100%);
    color: white;
}

.email-card .contact-icon {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
    color: white;
}

.phone-card .contact-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.contact-info {
    flex: 1;

    h4 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1f2937;
    }

    p {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0;
    }
}

.contact-arrow {
    color: #9ca3af;
    font-size: 0.9rem;
    transition: transform 0.3s ease;
}

.contact-card:hover .contact-arrow {
    transform: translateX(4px);
}

.website-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 18px;
    background: white;
    color: #667eea;
    border-radius: 16px;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.05rem;
    transition: all 0.3s ease;

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
    }

    i {
        font-size: 1.2rem;
    }
}

// ==========================================
// ФУТЕР
// ==========================================
.about-footer {
    text-align: center;
    padding: 40px 20px;
    background: #1f2937;
    color: white;

    p {
        margin: 0 0 8px;
        font-size: 0.95rem;
    }

    .footer-subtitle {
        font-size: 0.85rem;
        opacity: 0.7;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }

    .hero-stats {
        gap: 16px;
    }

    .stat-divider {
        display: none;
    }

    .section {
        padding: 40px 16px;
    }

    .section-title {
        font-size: 1.75rem;
    }

    .advantages-grid {
        grid-template-columns: 1fr;
    }

    .feature-header {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }

    .feature-list {
        grid-template-columns: 1fr;
    }

    .step-item {
        padding-left: 70px;
    }

    .step-number {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }

    .step-connector {
        left: 23px;
        top: 48px;
    }

    .tech-grid {
        grid-template-columns: 1fr;
    }

    .contacts-card {
        padding: 32px 20px;
    }

    .contacts-header h2 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: 40px 16px 60px;
    }

    .hero-title {
        font-size: 1.75rem;
    }

    .hero-subtitle {
        font-size: 1rem;
    }

    .stat-number {
        font-size: 1.5rem;
    }

    .advantage-card {
        padding: 24px 20px;
    }

    .feature-header {
        padding: 20px 16px;
    }

    .feature-content {
        padding: 20px 16px;
    }

    .contact-card {
        padding: 16px;
    }

    .contact-icon {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }
}
</style>
