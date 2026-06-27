<template>
    <div class="cost-calculator">

        <!-- ========================================== -->
        <!-- ПРОГРЕСС-БАР -->
        <!-- ========================================== -->
        <div class="stepper-progress">
            <div class="progress-line">
                <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
            </div>
            <div class="steps-indicators">
                <div
                    v-for="(step, index) in steps"
                    :key="index"
                    class="step-dot"
                    :class="{
                        'is-active': currentStep === index,
                        'is-completed': currentStep > index
                    }"
                    @click="goToStep(index)"
                >
                    <div class="dot-inner">
                        <i v-if="currentStep > index" class="fa-solid fa-check"></i>
                        <span v-else>{{ index + 1 }}</span>
                    </div>
                    <span class="dot-label">{{ step.shortLabel }}</span>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОНТЕНТ ШАГОВ -->
        <!-- ========================================== -->
        <div class="calculator-body">
            <transition :name="transitionName" mode="out-in">

                <!-- ШАГ 1: Тип бизнеса -->
                <div v-if="currentStep === 0" key="step-0" class="step-content">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <h2>Какой у вас бизнес?</h2>
                        <p>Выберите наиболее подходящую категорию</p>
                    </div>

                    <div class="business-grid">
                        <button
                            v-for="biz in businessTypes"
                            :key="biz.id"
                            class="business-card"
                            :class="{ 'is-selected': formData.businessType === biz.id }"
                            @click="selectBusiness(biz.id)"
                        >
                            <div class="biz-icon" :style="{ background: biz.gradient }">
                                <i :class="biz.icon"></i>
                            </div>
                            <div class="biz-info">
                                <strong>{{ biz.name }}</strong>
                                <span>{{ biz.description }}</span>
                            </div>
                            <div class="biz-check">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- ШАГ 2: Функционал -->
                <div v-else-if="currentStep === 1" key="step-1" class="step-content">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fa-solid fa-puzzle-piece"></i>
                        </div>
                        <h2>Какие функции нужны?</h2>
                        <p>Отметьте всё, что хотите включить в приложение</p>
                    </div>

                    <div class="features-grid">
                        <label
                            v-for="feature in features"
                            :key="feature.id"
                            class="feature-card"
                            :class="{ 'is-selected': formData.features.includes(feature.id) }"
                        >
                            <input
                                type="checkbox"
                                :value="feature.id"
                                v-model="formData.features"
                                class="hidden-checkbox"
                            >
                            <div class="feature-icon">
                                <i :class="feature.icon"></i>
                            </div>
                            <div class="feature-info">
                                <strong>{{ feature.name }}</strong>
                                <span class="feature-price">+{{ formatPrice(feature.price) }}</span>
                            </div>
                            <div class="feature-check">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- ШАГ 3: Интеграции -->
                <div v-else-if="currentStep === 2" key="step-2" class="step-content">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fa-solid fa-plug"></i>
                        </div>
                        <h2>Нужны ли интеграции?</h2>
                        <p>Подключение к внешним сервисам и системам</p>
                    </div>

                    <div class="integrations-list">
                        <label
                            v-for="integration in integrations"
                            :key="integration.id"
                            class="integration-card"
                            :class="{ 'is-selected': formData.integrations.includes(integration.id) }"
                        >
                            <input
                                type="checkbox"
                                :value="integration.id"
                                v-model="formData.integrations"
                                class="hidden-checkbox"
                            >
                            <div class="integration-icon" :style="{ background: integration.color }">
                                <i :class="integration.icon"></i>
                            </div>
                            <div class="integration-info">
                                <strong>{{ integration.name }}</strong>
                                <span>{{ integration.description }}</span>
                            </div>
                            <div class="integration-price">
                                +{{ formatPrice(integration.price) }}
                            </div>
                            <div class="integration-check">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- ШАГ 4: Дизайн и товары -->
                <div v-else-if="currentStep === 3" key="step-3" class="step-content">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fa-solid fa-palette"></i>
                        </div>
                        <h2>Дизайн и каталог</h2>
                        <p>Выберите стиль оформления и размер каталога</p>
                    </div>

                    <div class="selection-block">
                        <h3>Уровень дизайна</h3>
                        <div class="radio-cards">
                            <label
                                v-for="design in designOptions"
                                :key="design.id"
                                class="radio-card"
                                :class="{ 'is-selected': formData.design === design.id }"
                            >
                                <input
                                    type="radio"
                                    name="design"
                                    :value="design.id"
                                    v-model="formData.design"
                                    class="hidden-radio"
                                >
                                <div class="radio-icon">
                                    <i :class="design.icon"></i>
                                </div>
                                <div class="radio-info">
                                    <strong>{{ design.name }}</strong>
                                    <span>{{ design.description }}</span>
                                </div>
                                <div class="radio-price">
                                    {{ design.price > 0 ? '+' + formatPrice(design.price) : 'Бесплатно' }}
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="selection-block">
                        <h3>Количество товаров в каталоге</h3>
                        <div class="radio-cards compact">
                            <label
                                v-for="catalog in catalogOptions"
                                :key="catalog.id"
                                class="radio-card compact"
                                :class="{ 'is-selected': formData.catalog === catalog.id }"
                            >
                                <input
                                    type="radio"
                                    name="catalog"
                                    :value="catalog.id"
                                    v-model="formData.catalog"
                                    class="hidden-radio"
                                >
                                <strong>{{ catalog.name }}</strong>
                                <span class="radio-price">
                                    {{ catalog.price > 0 ? '+' + formatPrice(catalog.price) : 'Включено' }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ШАГ 5: Доп. услуги и сроки -->
                <div v-else-if="currentStep === 4" key="step-4" class="step-content">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fa-solid fa-rocket"></i>
                        </div>
                        <h2>Дополнительные услуги</h2>
                        <p>Поможем с запуском и продвижением</p>
                    </div>

                    <div class="features-grid">
                        <label
                            v-for="service in additionalServices"
                            :key="service.id"
                            class="feature-card"
                            :class="{ 'is-selected': formData.services.includes(service.id) }"
                        >
                            <input
                                type="checkbox"
                                :value="service.id"
                                v-model="formData.services"
                                class="hidden-checkbox"
                            >
                            <div class="feature-icon">
                                <i :class="service.icon"></i>
                            </div>
                            <div class="feature-info">
                                <strong>{{ service.name }}</strong>
                                <span class="feature-price">
                                    {{ service.recurring ? formatPrice(service.price) + '/мес' : '+' + formatPrice(service.price) }}
                                </span>
                            </div>
                            <div class="feature-check">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </label>
                    </div>

                    <div class="selection-block">
                        <h3>Срок запуска</h3>
                        <div class="deadline-cards">
                            <button
                                v-for="deadline in deadlines"
                                :key="deadline.id"
                                class="deadline-card"
                                :class="{ 'is-selected': formData.deadline === deadline.id }"
                                @click="formData.deadline = deadline.id"
                            >
                                <div class="deadline-badge" :style="{ background: deadline.color }">
                                    {{ deadline.badge }}
                                </div>
                                <strong>{{ deadline.name }}</strong>
                                <span>{{ deadline.description }}</span>
                                <div class="deadline-multiplier">
                                    {{ deadline.multiplierLabel }}
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ШАГ 6: Результат -->
                <div v-else-if="currentStep === 5" key="step-5" class="step-content result-step">
                    <div class="result-hero">
                        <div class="result-icon">
                            <i class="fa-solid fa-party-horn"></i>
                        </div>
                        <h2>Ваше приложение готово к запуску!</h2>
                        <p>Вот итоговая стоимость и сроки разработки</p>
                    </div>

                    <div class="result-card">
                        <div class="result-total">
                            <span class="result-label">Разовая стоимость</span>
                            <div class="result-price">
                                <span class="price-value">{{ animatedTotal.toLocaleString('ru-RU') }}</span>
                                <span class="price-currency">₽</span>
                            </div>
                            <span class="result-subtitle">+ {{ formatPrice(monthlyTotal) }}/мес абонентская плата</span>
                        </div>

                        <div class="result-breakdown">
                            <div class="breakdown-title">Из чего складывается цена:</div>
                            <div class="breakdown-list">
                                <div class="breakdown-item">
                                    <span>Базовая разработка ({{ selectedBusiness?.name }})</span>
                                    <strong>{{ formatPrice(basePrice) }}</strong>
                                </div>
                                <div v-if="featuresTotal > 0" class="breakdown-item">
                                    <span>Доп. функции ({{ formData.features.length }})</span>
                                    <strong>+{{ formatPrice(featuresTotal) }}</strong>
                                </div>
                                <div v-if="integrationsTotal > 0" class="breakdown-item">
                                    <span>Интеграции ({{ formData.integrations.length }})</span>
                                    <strong>+{{ formatPrice(integrationsTotal) }}</strong>
                                </div>
                                <div v-if="designTotal > 0" class="breakdown-item">
                                    <span>Дизайн</span>
                                    <strong>+{{ formatPrice(designTotal) }}</strong>
                                </div>
                                <div v-if="catalogTotal > 0" class="breakdown-item">
                                    <span>Каталог товаров</span>
                                    <strong>+{{ formatPrice(catalogTotal) }}</strong>
                                </div>
                                <div v-if="servicesTotal > 0" class="breakdown-item">
                                    <span>Доп. услуги</span>
                                    <strong>+{{ formatPrice(servicesTotal) }}</strong>
                                </div>
                                <div v-if="deadlineMultiplier > 1" class="breakdown-item urgency">
                                    <span>Ускоренный срок (×{{ deadlineMultiplier }})</span>
                                    <strong>+{{ formatPrice(urgencyFee) }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="result-stats">
                            <div class="stat-block">
                                <i class="fa-solid fa-clock"></i>
                                <div>
                                    <strong>{{ selectedDeadline?.days }}</strong>
                                    <span>дней запуска</span>
                                </div>
                            </div>
                            <div class="stat-block savings">
                                <i class="fa-solid fa-piggy-bank"></i>
                                <div>
                                    <strong>~500 000 ₽</strong>
                                    <span>экономия vs нативное приложение</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="result-actions">
                        <button class="action-btn primary" @click="submitOrder">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Оставить заявку</span>
                        </button>
                        <button class="action-btn outline" @click="sendToEmail">
                            <i class="fa-solid fa-envelope"></i>
                            <span>Отправить на email</span>
                        </button>
                        <button class="action-btn ghost" @click="resetCalculator">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Рассчитать заново</span>
                        </button>
                    </div>
                </div>

            </transition>
        </div>

        <!-- ========================================== -->
        <!-- НИЖНЯЯ ПАНЕЛЬ С ИТОГОМ -->
        <!-- ========================================== -->
        <div v-if="currentStep < 5" class="calculator-footer">
            <div class="footer-summary">
                <div class="summary-info">
                    <span class="summary-label">Предварительная стоимость:</span>
                    <span class="summary-value">{{ formatPrice(totalPrice) }}</span>
                </div>
                <div class="summary-meta">
                    <span>Срок: {{ selectedDeadline?.days }} дн.</span>
                </div>
            </div>
            <div class="footer-actions">
                <button
                    class="nav-btn back"
                    :disabled="currentStep === 0"
                    @click="prevStep"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Назад</span>
                </button>
                <button
                    class="nav-btn next"
                    @click="nextStep"
                >
                    <span>{{ currentStep === 4 ? 'Рассчитать' : 'Далее' }}</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: 'CostCalculator',

    emits: ['submit', 'send-email'],

    data() {
        return {
            currentStep: 0,
            transitionName: 'slide-left',
            animatedTotal: 0,
            animationFrame: null,

            formData: {
                businessType: null,
                features: [],
                integrations: [],
                design: 'template',
                catalog: 'small',
                services: [],
                deadline: 'standard',
            },

            steps: [
                { shortLabel: 'Бизнес' },
                { shortLabel: 'Функции' },
                { shortLabel: 'Интеграции' },
                { shortLabel: 'Дизайн' },
                { shortLabel: 'Услуги' },
                { shortLabel: 'Итог' },
            ],

            businessTypes: [
                {
                    id: 'coffee',
                    name: 'Кофейня',
                    description: 'Кафе, бар, ресторан',
                    icon: 'fa-solid fa-mug-hot',
                    gradient: 'linear-gradient(135deg, #8B4513 0%, #D2691E 100%)',
                    basePrice: 55000,
                },
                {
                    id: 'shop',
                    name: 'Магазин',
                    description: 'Одежда, продукты, электроника',
                    icon: 'fa-solid fa-store',
                    gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    basePrice: 50000,
                },
                {
                    id: 'beauty',
                    name: 'Салон красоты',
                    description: 'Парикмахерская, барбершоп, SPA',
                    icon: 'fa-solid fa-scissors',
                    gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    basePrice: 55000,
                },
                {
                    id: 'fitness',
                    name: 'Фитнес-клуб',
                    description: 'Спортзал, йога, танцы',
                    icon: 'fa-solid fa-dumbbell',
                    gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                    basePrice: 60000,
                },
                {
                    id: 'hotel',
                    name: 'Отель',
                    description: 'Гостиница, хостел, апартаменты',
                    icon: 'fa-solid fa-hotel',
                    gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                    basePrice: 70000,
                },
                {
                    id: 'auto',
                    name: 'Автосервис',
                    description: 'СТО, мойка, шиномонтаж',
                    icon: 'fa-solid fa-car',
                    gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                    basePrice: 55000,
                },
                {
                    id: 'delivery',
                    name: 'Доставка',
                    description: 'Курьерская служба, такси',
                    icon: 'fa-solid fa-motorcycle',
                    gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                    basePrice: 75000,
                },
                {
                    id: 'other',
                    name: 'Другое',
                    description: 'Любой другой бизнес',
                    icon: 'fa-solid fa-briefcase',
                    gradient: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                    basePrice: 50000,
                },
            ],

            features: [
                { id: 'catalog', name: 'Каталог товаров', icon: 'fa-solid fa-list', price: 0 },
                { id: 'cart', name: 'Корзина и заказы', icon: 'fa-solid fa-cart-shopping', price: 0 },
                { id: 'payment', name: 'Онлайн-оплата', icon: 'fa-solid fa-credit-card', price: 0 },
                { id: 'profile', name: 'Личный кабинет', icon: 'fa-solid fa-user', price: 0 },
                { id: 'push', name: 'Push-уведомления', icon: 'fa-solid fa-bell', price: 5000 },
                { id: 'booking', name: 'Бронирование столиков', icon: 'fa-solid fa-calendar-check', price: 15000 },
                { id: 'loyalty', name: 'Программа лояльности', icon: 'fa-solid fa-gift', price: 10000 },
                { id: 'wheel', name: 'Колесо фортуны', icon: 'fa-solid fa-dice', price: 5000 },
                { id: 'chat', name: 'Чат с поддержкой', icon: 'fa-solid fa-comments', price: 7000 },
                { id: 'reviews', name: 'Отзывы и рейтинги', icon: 'fa-solid fa-star', price: 5000 },
                { id: 'favorites', name: 'Избранное', icon: 'fa-solid fa-heart', price: 3000 },
                { id: 'history', name: 'История заказов', icon: 'fa-solid fa-clock-rotate-left', price: 2000 },
                { id: 'geo', name: 'Геолокация и доставка', icon: 'fa-solid fa-location-dot', price: 12000 },
                { id: 'qr', name: 'QR-меню для ресторана', icon: 'fa-solid fa-qrcode', price: 8000 },
                { id: 'coffee-program', name: 'Кофейная программа', icon: 'fa-solid fa-mug-saucer', price: 6000 },
                { id: 'split-bill', name: 'Раздельный счёт', icon: 'fa-solid fa-receipt', price: 4000 },
                { id: 'waiter', name: 'Вызов официанта', icon: 'fa-solid fa-bell-concierge', price: 3000 },
            ],

            integrations: [
                {
                    id: 'iiko',
                    name: 'iiko',
                    description: 'Для ресторанов и кафе',
                    icon: 'fa-solid fa-utensils',
                    color: 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)',
                    price: 20000,
                },
                {
                    id: 'frontpad',
                    name: 'FrontPad',
                    description: 'Система автоматизации',
                    icon: 'fa-solid fa-tablet-screen-button',
                    color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                    price: 15000,
                },
                {
                    id: '1c',
                    name: '1С',
                    description: 'Синхронизация с 1С',
                    icon: 'fa-solid fa-database',
                    color: 'linear-gradient(135deg, #ffcc33 0%, #ff9500 100%)',
                    price: 25000,
                },
                {
                    id: 'crm',
                    name: 'CRM-система',
                    description: 'Битрикс24, amoCRM и др.',
                    icon: 'fa-solid fa-users',
                    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    price: 18000,
                },
                {
                    id: 'telegram',
                    name: 'Telegram-бот',
                    description: 'Уведомления и заказы',
                    icon: 'fa-brands fa-telegram',
                    color: 'linear-gradient(135deg, #0088cc 0%, #00a0dc 100%)',
                    price: 10000,
                },
                {
                    id: 'social',
                    name: 'Instagram / VK',
                    description: 'Интеграция с соцсетями',
                    icon: 'fa-brands fa-instagram',
                    color: 'linear-gradient(135deg, #f09433 0%, #dc2743 100%)',
                    price: 8000,
                },
            ],

            designOptions: [
                {
                    id: 'template',
                    name: 'Готовый шаблон',
                    description: 'Быстрый старт с готовым дизайном',
                    icon: 'fa-solid fa-wand-magic-sparkles',
                    price: 0,
                },
                {
                    id: 'custom',
                    name: 'Кастомизация',
                    description: 'Настройка под ваш бренд',
                    icon: 'fa-solid fa-palette',
                    price: 15000,
                },
                {
                    id: 'unique',
                    name: 'Индивидуальный дизайн',
                    description: 'Эксклюзивный дизайн с нуля',
                    icon: 'fa-solid fa-pen-ruler',
                    price: 40000,
                },
            ],

            catalogOptions: [
                { id: 'small', name: 'До 50 товаров', price: 0 },
                { id: 'medium', name: '50-200 товаров', price: 5000 },
                { id: 'large', name: '200-1000 товаров', price: 10000 },
                { id: 'huge', name: 'Более 1000 товаров', price: 15000 },
            ],

            additionalServices: [
                { id: 'setup', name: 'Запуск «под ключ»', icon: 'fa-solid fa-rocket', price: 15000, recurring: false },
                { id: 'training', name: 'Обучение персонала', icon: 'fa-solid fa-graduation-cap', price: 8000, recurring: false },
                { id: 'upload', name: 'Загрузка товаров (до 100 шт.)', icon: 'fa-solid fa-upload', price: 5000, recurring: false },
                { id: 'photo', name: 'Фотосъёмка товаров', icon: 'fa-solid fa-camera', price: 20000, recurring: false },
                { id: 'seo', name: 'SEO-оптимизация', icon: 'fa-solid fa-magnifying-glass-chart', price: 12000, recurring: false },
                { id: 'support', name: 'Техподдержка 24/7', icon: 'fa-solid fa-headset', price: 5000, recurring: true },
            ],

            deadlines: [
                {
                    id: 'standard',
                    name: 'Стандартный',
                    description: 'Спокойный запуск без спешки',
                    days: '7-14',
                    multiplier: 1.0,
                    multiplierLabel: '×1.0',
                    color: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                    badge: 'Рекомендуем',
                },
                {
                    id: 'fast',
                    name: 'Ускоренный',
                    description: 'Запустим в кратчайшие сроки',
                    days: '3-5',
                    multiplier: 1.3,
                    multiplierLabel: '+30%',
                    color: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                    badge: 'Популярно',
                },
                {
                    id: 'urgent',
                    name: 'Срочный',
                    description: 'Нужно прямо сейчас!',
                    days: '1-2',
                    multiplier: 1.5,
                    multiplierLabel: '+50%',
                    color: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                    badge: 'Срочно',
                },
            ],
        };
    },

    computed: {
        progressPercent() {
            return (this.currentStep / (this.steps.length - 1)) * 100;
        },

        selectedBusiness() {
            return this.businessTypes.find(b => b.id === this.formData.businessType);
        },

        selectedDeadline() {
            return this.deadlines.find(d => d.id === this.formData.deadline);
        },

        basePrice() {
            return this.selectedBusiness?.basePrice || 50000;
        },

        featuresTotal() {
            return this.features
                .filter(f => this.formData.features.includes(f.id))
                .reduce((sum, f) => sum + f.price, 0);
        },

        integrationsTotal() {
            return this.integrations
                .filter(i => this.formData.integrations.includes(i.id))
                .reduce((sum, i) => sum + i.price, 0);
        },

        designTotal() {
            const design = this.designOptions.find(d => d.id === this.formData.design);
            return design?.price || 0;
        },

        catalogTotal() {
            const catalog = this.catalogOptions.find(c => c.id === this.formData.catalog);
            return catalog?.price || 0;
        },

        servicesTotal() {
            return this.additionalServices
                .filter(s => this.formData.services.includes(s.id) && !s.recurring)
                .reduce((sum, s) => sum + s.price, 0);
        },

        monthlyServicesTotal() {
            return this.additionalServices
                .filter(s => this.formData.services.includes(s.id) && s.recurring)
                .reduce((sum, s) => sum + s.price, 0);
        },

        subtotal() {
            return this.basePrice +
                this.featuresTotal +
                this.integrationsTotal +
                this.designTotal +
                this.catalogTotal +
                this.servicesTotal;
        },

        deadlineMultiplier() {
            return this.selectedDeadline?.multiplier || 1.0;
        },

        urgencyFee() {
            if (this.deadlineMultiplier <= 1) return 0;
            return Math.round(this.subtotal * (this.deadlineMultiplier - 1));
        },

        totalPrice() {
            return Math.round(this.subtotal * this.deadlineMultiplier);
        },

        monthlyTotal() {
            return this.monthlyServicesTotal;
        },
    },

    watch: {
        totalPrice(newValue) {
            this.animateNumber(newValue);
        },
    },

    mounted() {
        // Восстановление из localStorage
        this.loadFromStorage();
        this.animatedTotal = this.totalPrice;
    },

    methods: {
        selectBusiness(id) {
            this.formData.businessType = id;
        },

        nextStep() {
            if (this.currentStep < this.steps.length - 1) {
                this.transitionName = 'slide-left';
                this.currentStep++;
                this.saveToStorage();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prevStep() {
            if (this.currentStep > 0) {
                this.transitionName = 'slide-right';
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        goToStep(index) {
            // Можно переходить только на пройденные шаги или следующий
            if (index <= this.currentStep) {
                this.transitionName = index > this.currentStep ? 'slide-left' : 'slide-right';
                this.currentStep = index;
            }
        },

        animateNumber(target) {
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
            }

            const start = this.animatedTotal;
            const diff = target - start;
            const duration = 400;
            const startTime = performance.now();

            const step = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Easing function
                const easeOut = 1 - Math.pow(1 - progress, 3);

                this.animatedTotal = Math.round(start + diff * easeOut);

                if (progress < 1) {
                    this.animationFrame = requestAnimationFrame(step);
                }
            };

            this.animationFrame = requestAnimationFrame(step);
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price || 0) + ' ₽';
        },

        saveToStorage() {
            try {
                localStorage.setItem('calculator_form', JSON.stringify(this.formData));
            } catch (e) {
                console.warn('Не удалось сохранить в localStorage');
            }
        },

        loadFromStorage() {
            try {
                const saved = localStorage.getItem('calculator_form');
                if (saved) {
                    const parsed = JSON.parse(saved);
                    this.formData = { ...this.formData, ...parsed };
                }
            } catch (e) {
                console.warn('Не удалось загрузить из localStorage');
            }
        },

        submitOrder() {
            this.$emit('submit', {
                formData: this.formData,
                total: this.totalPrice,
                monthly: this.monthlyTotal,
            });
        },

        sendToEmail() {
            this.$emit('send-email', {
                formData: this.formData,
                total: this.totalPrice,
            });
        },

        resetCalculator() {
            this.formData = {
                businessType: null,
                features: [],
                integrations: [],
                design: 'template',
                catalog: 'small',
                services: [],
                deadline: 'standard',
            };
            this.currentStep = 0;
            localStorage.removeItem('calculator_form');
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
.cost-calculator {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    padding-bottom: 160px;
}

// ==========================================
// ПРОГРЕСС-БАР
// ==========================================
.stepper-progress {
    position: sticky;
    top: 0;
    z-index: 10;
    background: $card-bg;
    padding: 20px 16px 16px;
    border-radius: 20px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid $border;
}

.progress-line {
    position: absolute;
    top: 40px;
    left: 40px;
    right: 40px;
    height: 3px;
    background: $border;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, $primary 0%, $primary-light 100%);
    border-radius: 2px;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.steps-indicators {
    display: flex;
    justify-content: space-between;
    position: relative;
    z-index: 1;
}

.step-dot {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.dot-inner {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: $card-bg;
    border: 2px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    color: $text-muted;
    transition: all 0.3s;
}

.step-dot.is-active .dot-inner {
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    border-color: $primary;
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba($primary, 0.4);
}

.step-dot.is-completed .dot-inner {
    background: $success;
    border-color: $success;
    color: white;
}

.dot-label {
    font-size: 0.7rem;
    color: $text-muted;
    font-weight: 500;
    text-align: center;
    max-width: 70px;
}

.step-dot.is-active .dot-label {
    color: $primary;
    font-weight: 700;
}

// ==========================================
// КОНТЕНТ ШАГОВ
// ==========================================
.calculator-body {
    min-height: 400px;
}

.step-content {
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.step-header {
    text-align: center;
    margin-bottom: 32px;
}

.step-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    box-shadow: 0 8px 24px rgba($primary, 0.3);
}

.step-header h2 {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0 0 8px;
    color: $text;
}

.step-header p {
    font-size: 1rem;
    color: $text-muted;
    margin: 0;
}

// ==========================================
// ТИП БИЗНЕСА
// ==========================================
.business-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 12px;
}

.business-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: $card-bg;
    border: 2px solid $border;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: left;
    width: 100%;

    &:hover {
        border-color: $primary;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.05);
        box-shadow: 0 8px 24px rgba($primary, 0.2);
    }
}

.biz-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
    flex-shrink: 0;
}

.biz-info {
    flex: 1;
    min-width: 0;

    strong {
        display: block;
        font-size: 0.95rem;
        margin-bottom: 2px;
        color: $text;
    }

    span {
        font-size: 0.8rem;
        color: $text-muted;
    }
}

.biz-check {
    color: $primary;
    font-size: 1.2rem;
    opacity: 0;
    transition: opacity 0.3s;

    .is-selected & {
        opacity: 1;
    }
}

// ==========================================
// ФУНКЦИИ И УСЛУГИ
// ==========================================
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
}

.feature-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: $card-bg;
    border: 2px solid $border;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
        border-color: $primary;
        transform: translateY(-2px);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.05);
    }
}

.hidden-checkbox,
.hidden-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.feature-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all 0.3s;

    .is-selected & {
        background: $primary;
        color: white;
    }
}

.feature-info {
    flex: 1;
    min-width: 0;

    strong {
        display: block;
        font-size: 0.9rem;
        margin-bottom: 2px;
        color: $text;
    }

    .feature-price {
        font-size: 0.8rem;
        color: $primary;
        font-weight: 600;
    }
}

.feature-check {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: $primary;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s;

    .is-selected & {
        opacity: 1;
        transform: scale(1);
    }
}

// ==========================================
// ИНТЕГРАЦИИ
// ==========================================
.integrations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.integration-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: $card-bg;
    border: 2px solid $border;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
        border-color: $primary;
        transform: translateX(4px);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.05);
    }
}

.integration-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: white;
    flex-shrink: 0;
}

.integration-info {
    flex: 1;
    min-width: 0;

    strong {
        display: block;
        font-size: 1rem;
        margin-bottom: 2px;
        color: $text;
    }

    span {
        font-size: 0.85rem;
        color: $text-muted;
    }
}

.integration-price {
    font-size: 0.95rem;
    font-weight: 700;
    color: $primary;
    flex-shrink: 0;
}

.integration-check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: $primary;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s;

    .is-selected & {
        opacity: 1;
        transform: scale(1);
    }
}

// ==========================================
// РАДИО-КАРТОЧКИ
// ==========================================
.selection-block {
    margin-bottom: 32px;

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 16px;
        color: $text;
    }
}

.radio-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;

    &.compact {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
}

.radio-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    background: $card-bg;
    border: 2px solid $border;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
        border-color: $primary;
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.05);
    }

    &.compact {
        flex-direction: column;
        text-align: center;
        padding: 14px;

        strong {
            font-size: 0.9rem;
        }
    }
}

.radio-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    transition: all 0.3s;

    .is-selected & {
        background: $primary;
        color: white;
    }
}

.radio-info {
    flex: 1;
    min-width: 0;

    strong {
        display: block;
        font-size: 1rem;
        margin-bottom: 2px;
        color: $text;
    }

    span {
        font-size: 0.8rem;
        color: $text-muted;
    }
}

.radio-price {
    font-size: 0.9rem;
    font-weight: 700;
    color: $primary;
    flex-shrink: 0;
}

// ==========================================
// СРОКИ
// ==========================================
.deadline-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.deadline-card {
    position: relative;
    padding: 20px 16px;
    background: $card-bg;
    border: 2px solid $border;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;

    &:hover {
        border-color: $primary;
        transform: translateY(-4px);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.05);
        box-shadow: 0 8px 24px rgba($primary, 0.2);
    }

    strong {
        display: block;
        font-size: 1.1rem;
        margin: 12px 0 4px;
        color: $text;
    }

    > span {
        font-size: 0.85rem;
        color: $text-muted;
        display: block;
        margin-bottom: 12px;
    }
}

.deadline-badge {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    padding: 4px 12px;
    border-radius: 20px;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}

.deadline-multiplier {
    display: inline-block;
    padding: 4px 12px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

// ==========================================
// РЕЗУЛЬТАТ
// ==========================================
.result-step {
    text-align: center;
}

.result-hero {
    margin-bottom: 32px;
}

.result-icon {
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

@keyframes celebrate {
    0% { transform: scale(0) rotate(-180deg); }
    50% { transform: scale(1.2) rotate(10deg); }
    100% { transform: scale(1) rotate(0); }
}

.result-card {
    background: $card-bg;
    border-radius: 24px;
    padding: 32px 24px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
    border: 1px solid $border;
    margin-bottom: 24px;
}

.result-total {
    padding-bottom: 24px;
    border-bottom: 1px solid $border;
    margin-bottom: 24px;
}

.result-label {
    display: block;
    font-size: 0.9rem;
    color: $text-muted;
    margin-bottom: 8px;
}

.result-price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 4px;
    margin-bottom: 8px;
}

.price-value {
    font-size: 3rem;
    font-weight: 900;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1;
}

.price-currency {
    font-size: 1.5rem;
    font-weight: 700;
    color: $primary;
}

.result-subtitle {
    font-size: 0.9rem;
    color: $text-muted;
}

.result-breakdown {
    text-align: left;
    margin-bottom: 24px;
}

.breakdown-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: $bg;
    border-radius: 10px;
    font-size: 0.9rem;

    span {
        color: $text-muted;
    }

    strong {
        color: $text;
        font-weight: 700;
    }

    &.urgency {
        background: rgba($warning, 0.1);

        span, strong {
            color: darken($warning, 15%);
        }
    }
}

.result-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.stat-block {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: $bg;
    border-radius: 14px;
    text-align: left;

    i {
        font-size: 1.5rem;
        color: $primary;
    }

    strong {
        display: block;
        font-size: 1.1rem;
        color: $text;
        margin-bottom: 2px;
    }

    span {
        font-size: 0.75rem;
        color: $text-muted;
    }

    &.savings {
        background: rgba($success, 0.1);

        i {
            color: $success;
        }
    }
}

.result-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    border: none;

    &.primary {
        background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
        color: white;
        box-shadow: 0 8px 24px rgba($primary, 0.3);

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba($primary, 0.4);
        }
    }

    &.outline {
        background: $card-bg;
        color: $primary;
        border: 2px solid $primary;

        &:hover {
            background: rgba($primary, 0.05);
        }
    }

    &.ghost {
        background: transparent;
        color: $text-muted;

        &:hover {
            color: $primary;
        }
    }
}

// ==========================================
// НИЖНЯЯ ПАНЕЛЬ
// ==========================================
.calculator-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: $card-bg;
    border-top: 1px solid $border;
    padding: 16px 20px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    z-index: 100;
    backdrop-filter: blur(10px);
}

.footer-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}

.summary-info {
    display: flex;
    flex-direction: column;
}

.summary-label {
    font-size: 0.75rem;
    color: $text-muted;
    margin-bottom: 2px;
}

.summary-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: $primary;
}

.summary-meta {
    font-size: 0.85rem;
    color: $text-muted;
}

.footer-actions {
    display: flex;
    gap: 10px;
    max-width: 900px;
    margin: 0 auto;
}

.nav-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    border: none;

    &.back {
        background: $bg;
        color: $text;
        border: 1px solid $border;

        &:hover:not(:disabled) {
            background: $border;
        }

        &:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
    }

    &.next {
        background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
        color: white;
        box-shadow: 0 4px 16px rgba($primary, 0.3);

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba($primary, 0.4);
        }
    }
}

// ==========================================
// АНИМАЦИИ ПЕРЕХОДОВ
// ==========================================
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-left-enter-from {
    opacity: 0;
    transform: translateX(40px);
}

.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-40px);
}

.slide-right-enter-from {
    opacity: 0;
    transform: translateX(-40px);
}

.slide-right-leave-to {
    opacity: 0;
    transform: translateX(40px);
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .cost-calculator {
        padding: 12px;
        padding-bottom: 180px;
    }

    .step-header h2 {
        font-size: 1.4rem;
    }

    .step-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }

    .dot-label {
        font-size: 0.65rem;
        max-width: 50px;
    }

    .dot-inner {
        width: 34px;
        height: 34px;
        font-size: 0.8rem;
    }

    .business-grid,
    .features-grid {
        grid-template-columns: 1fr;
    }

    .price-value {
        font-size: 2.2rem;
    }

    .result-stats {
        grid-template-columns: 1fr;
    }

    .footer-summary {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .summary-value {
        font-size: 1.3rem;
    }
}
</style>
