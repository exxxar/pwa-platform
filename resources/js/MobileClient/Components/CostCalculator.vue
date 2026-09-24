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

                <!-- ШАГ 0: Тип бизнеса и названия -->
                <div v-if="currentStep === 0" key="step-0" class="step-content">
                    <div class="step-header">
                        <div class="step-icon"><i class="fa-solid fa-briefcase"></i></div>
                        <h2>Какой у вас бизнес?</h2>
                        <p>Выберите категорию и задайте имя вашему будущему приложению</p>
                    </div>

                    <div class="naming-card mb-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-modern">Название приложения <span class="text-danger">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-signature input-icon"></i>
                                    <input
                                        type="text"
                                        v-model="formData.customName"
                                        class="form-control modern-input"
                                        placeholder="Например: Кофейня У Ежа"
                                        @input="autoGenerateSystemName"
                                    >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Системное имя (URL) <span class="text-danger">*</span></label>
                                <div class="url-input-group">
                                    <span class="url-prefix">https://</span>
                                    <input
                                        type="text"
                                        v-model="formData.systemName"
                                        class="form-control modern-input url-input"
                                        placeholder="coffee-u-ezha"
                                    >
                                    <span class="url-suffix">.mypwa.ru</span>
                                </div>
                                <small class="form-hint">Только английские буквы, цифры и дефис</small>
                            </div>
                        </div>
                    </div>

                    <div class="business-grid" v-if="config.business_types">
                        <button
                            v-for="biz in config.business_types"
                            :key="biz.id"
                            class="business-card"
                            :class="{
                                'is-selected': formData.businessType === biz.id,
                                'is-disabled': !biz.enabled
                            }"
                            @click="selectBusiness(biz.id, biz.enabled)"
                            :disabled="!biz.enabled"
                        >
                            <div v-if="!biz.enabled" class="disabled-overlay">
                                <i class="fa-solid fa-lock"></i>
                                <span>Скоро</span>
                            </div>
                            <div class="biz-icon" :style="{ background: biz.gradient }">
                                <i :class="biz.icon"></i>
                            </div>
                            <div class="biz-info">
                                <strong>{{ biz.name }}</strong>
                                <span>{{ biz.description }}</span>
                            </div>
                            <div class="biz-check"><i class="fa-solid fa-circle-check"></i></div>
                        </button>
                    </div>
                </div>

                <!-- ШАГ 1: Функционал -->
                <div v-else-if="currentStep === 1" key="step-1" class="step-content">
                    <div class="step-header">
                        <div class="step-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                        <h2>Какие функции нужны?</h2>
                        <p>Отметьте всё, что хотите включить в приложение</p>
                    </div>
                    <div class="features-grid">
                        <label
                            v-for="feature in config.features"
                            :key="feature.id"
                            class="feature-card"
                            :class="{ 'is-selected': formData.features.includes(feature.id) }"
                        >
                            <input type="checkbox" :value="feature.id" v-model="formData.features" class="hidden-checkbox">
                            <div class="feature-icon"><i :class="feature.icon"></i></div>
                            <div class="feature-info">
                                <strong>{{ feature.name }}</strong>
                                <span class="feature-price">+{{ formatPrice(feature.price) }}</span>
                            </div>
                            <div class="feature-check"><i class="fa-solid fa-check"></i></div>
                        </label>
                    </div>
                </div>

                <!-- ШАГ 2: Интеграции -->
                <div v-else-if="currentStep === 2" key="step-2" class="step-content">
                    <div class="step-header">
                        <div class="step-icon"><i class="fa-solid fa-plug"></i></div>
                        <h2>Нужны ли интеграции?</h2>
                        <p>Подключение к внешним сервисам и системам</p>
                    </div>
                    <div class="integrations-list">
                        <label
                            v-for="integration in config.integrations"
                            :key="integration.id"
                            class="integration-card"
                            :class="{ 'is-selected': formData.integrations.includes(integration.id) }"
                        >
                            <input type="checkbox" :value="integration.id" v-model="formData.integrations" class="hidden-checkbox">
                            <div class="integration-icon" :style="{ background: integration.color }">
                                <i :class="integration.icon"></i>
                            </div>
                            <div class="integration-info">
                                <strong>{{ integration.name }}</strong>
                                <span>{{ integration.description }}</span>
                            </div>
                            <div class="integration-price">+{{ formatPrice(integration.price) }}</div>
                            <div class="integration-check"><i class="fa-solid fa-check"></i></div>
                        </label>
                    </div>
                </div>

                <!-- ШАГ 3: Дизайн и товары -->
                <div v-else-if="currentStep === 3" key="step-3" class="step-content">
                    <div class="step-header">
                        <div class="step-icon"><i class="fa-solid fa-palette"></i></div>
                        <h2>Дизайн и каталог</h2>
                        <p>Выберите стиль оформления и размер каталога</p>
                    </div>

                    <div class="selection-block">
                        <h3>Уровень дизайна</h3>
                        <div class="radio-cards">
                            <label
                                v-for="design in config.design_options"
                                :key="design.id"
                                class="radio-card"
                                :class="{ 'is-selected': formData.design === design.id }"
                            >
                                <input type="radio" name="design" :value="design.id" v-model="formData.design" class="hidden-radio">
                                <div class="radio-icon"><i :class="design.icon"></i></div>
                                <div class="radio-info">
                                    <strong>{{ design.name }}</strong>
                                    <span>{{ design.description }}</span>
                                </div>
                                <div class="radio-price">{{ design.price > 0 ? '+' + formatPrice(design.price) : 'Бесплатно' }}</div>
                            </label>
                        </div>
                    </div>

                    <div class="selection-block">
                        <h3>Количество товаров в каталоге</h3>
                        <div class="radio-cards compact">
                            <label
                                v-for="catalog in config.catalog_options"
                                :key="catalog.id"
                                class="radio-card compact"
                                :class="{ 'is-selected': formData.catalog === catalog.id }"
                            >
                                <input type="radio" name="catalog" :value="catalog.id" v-model="formData.catalog" class="hidden-radio">
                                <strong>{{ catalog.name }}</strong>
                                <span class="radio-price">{{ catalog.price > 0 ? '+' + formatPrice(catalog.price) : 'Включено' }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ШАГ 4: Данные клиента -->
                <div v-else-if="currentStep === 4" key="step-4" class="step-content">
                    <div class="step-header">
                        <div class="step-icon"><i class="fa-solid fa-user-tie"></i></div>
                        <h2>Для кого создаем приложение?</h2>
                        <p>Укажите контактные данные клиента. На них будет создан аккаунт суперадмина.</p>
                    </div>
                    <div class="client-data-card">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label-modern">Имя клиента / Компания <span class="text-danger">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input type="text" v-model="formData.clientName" class="form-control modern-input" placeholder="Иван Иванов или ООО 'Ромашка'">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Телефон клиента <span class="text-danger">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-phone input-icon"></i>
                                    <input type="tel" v-model="formData.clientPhone" class="form-control modern-input" placeholder="+7 (999) 000-00-00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Email клиента <span class="text-danger">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                    <input
                                        type="email"
                                        v-model="formData.clientEmail"
                                        class="form-control modern-input"
                                        placeholder="client@example.com"
                                    >
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="info-alert">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Пароль для входа по умолчанию: <strong>admin123</strong>. Клиент сможет сменить его в настройках.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ШАГ 5: Выбор тарифа -->
                <div v-else-if="currentStep === 5" key="step-5" class="step-content">
                    <div class="step-header">
                        <div class="step-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <h2>Выберите тариф обслуживания</h2>
                        <p>Стоимость разработки — отдельно. Тариф — это доступ к платформе и хостингу вашего приложения.</p>
                    </div>

                    <div class="plans-grid">
                        <button
                            v-for="plan in config.payment_plans"
                            :key="plan.id"
                            class="plan-card"
                            :class="{ 'is-selected': formData.paymentPlan === plan.id, 'is-popular': plan.popular }"
                            :style="formData.paymentPlan === plan.id ? { borderColor: plan.card_border } : {}"
                            @click="formData.paymentPlan = plan.id"
                        >
                            <div v-if="plan.badge" class="plan-badge" :style="{ background: plan.badge_color }">{{ plan.badge }}</div>
                            <div class="plan-icon" :style="{ background: plan.badge_color || 'linear-gradient(135deg, #64748b 0%, #475569 100%)' }">
                                <i :class="plan.icon"></i>
                            </div>
                            <div class="plan-info">
                                <h3 class="plan-name">{{ plan.name }}</h3>
                                <p class="plan-desc">{{ plan.description }}</p>
                                <p class="plan-details">{{ plan.details }}</p>
                            </div>
                            <div class="plan-price-block">
                                <template v-if="plan.price === 0"><span class="plan-price free">0 ₽</span></template>
                                <template v-else-if="plan.price"><span class="plan-price">{{ formatPrice(plan.price) }}</span></template>
                                <template v-else><span class="plan-price custom-label">Своя</span></template>
                            </div>
                            <div class="plan-check"><i class="fa-solid fa-circle-check"></i></div>
                        </button>
                    </div>

                    <transition name="slide-down">
                        <div v-if="formData.paymentPlan === 'custom'" class="custom-amount-card">
                            <h4><i class="fa-solid fa-sliders me-2"></i>Укажите сумму пополнения</h4>
                            <div class="custom-amount-input-group">
                                <input type="number" v-model.number="formData.customAmount" class="form-control custom-amount-input" min="500" step="100" placeholder="1000">
                                <span class="custom-amount-suffix">₽</span>
                            </div>
                            <div class="custom-amount-presets">
                                <button v-for="preset in [500, 1000, 3000, 5000, 10000]" :key="preset" class="preset-btn" :class="{ 'is-active': formData.customAmount === preset }" @click="formData.customAmount = preset">
                                    {{ formatPrice(preset) }}
                                </button>
                            </div>
                            <small class="form-hint">Минимальная сумма: 500 ₽. Средства расходуются по тарифу 10 ₽/день.</small>
                        </div>
                    </transition>
                </div>

                <!-- ШАГ 6: Результат -->
                <div v-else-if="currentStep === 6" key="step-6" class="step-content result-step">
                    <div class="result-hero">
                        <div class="result-icon"><i class="fa-solid fa-party-horn"></i></div>
                        <h2>Ваше приложение готово к запуску!</h2>
                        <p>Вот итоговая стоимость и сроки разработки</p>
                    </div>

                    <div class="result-card">
                        <div class="result-total">
                            <span class="result-label">Итого к оплате</span>
                            <div class="result-price">
                                <span class="price-value">{{ animatedTotal.toLocaleString('ru-RU') }}</span>
                                <span class="price-currency">₽</span>
                            </div>
                            <span class="result-subtitle">
                                Разработка: {{ formatPrice(totalPrice) }}
                                <template v-if="planPrice > 0"> + Тариф: {{ formatPrice(planPrice) }}</template>
                                <template v-else> + Тестовый период: 10 дней бесплатно</template>
                            </span>
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
                                <div class="breakdown-item plan-line">
                                    <span>
                                        <i class="fa-solid fa-credit-card me-1"></i>
                                        Тариф: {{ selectedPlan?.name }}
                                        <template v-if="formData.paymentPlan === 'free'">(10 дней бесплатно)</template>
                                    </span>
                                    <strong :class="{ 'text-success': planPrice === 0 }">
                                        {{ planPrice === 0 ? 'Бесплатно' : '+' + formatPrice(planPrice) }}
                                    </strong>
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
                        <button class="action-btn primary" @click="submitOrder" :disabled="isLoading">
                            <i v-if="isLoading" class="fa-solid fa-circle-notch fa-spin"></i>
                            <i v-else class="fa-solid fa-paper-plane"></i>
                            <span>{{ isLoading ? 'Создаем приложение...' : 'Оставить заявку' }}</span>
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
        <div v-if="currentStep < 6" class="calculator-footer">
            <div class="footer-summary">
                <div class="summary-info">
                    <span class="summary-label">Предварительная стоимость:</span>
                    <span class="summary-value">{{ formatPrice(grandTotal) }}</span>
                </div>
                <div class="summary-meta">
                    <span>Срок: {{ selectedDeadline?.days }} дн.</span>
                </div>
            </div>
            <div class="footer-actions">
                <button class="nav-btn back" :disabled="currentStep === 0" @click="prevStep">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Назад</span>
                </button>
                <button class="nav-btn next" @click="nextStep">
                    <span>{{ currentStep === 5 ? 'Рассчитать' : 'Далее' }}</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'CostCalculator',
    emits: ['submit', 'send-email'],

    data() {
        return {
            currentStep: 0,
            transitionName: 'slide-left',
            animatedTotal: 0,
            animationFrame: null,
            isLoadingConfig: true,
            isLoading: false,
            lastGeneratedSystemName: '',

            config: {
                business_types: [],
                features: [],
                integrations: [],
                design_options: [],
                catalog_options: [],
                additional_services: [],
                deadlines: [],
                payment_plans: []
            },

            formData: {
                customName: '',
                systemName: '',
                clientName: '',
                clientPhone: '',
                clientEmail: '', // 🆕 Добавлено,
                businessType: null,
                features: [],
                integrations: [],
                design: 'template',
                catalog: 'small',
                services: [],
                deadline: 'standard',
                paymentPlan: 'free',
                customAmount: 1000,
            },

            steps: [
                { shortLabel: 'Бизнес' },
                { shortLabel: 'Функции' },
                { shortLabel: 'Интеграции' },
                { shortLabel: 'Дизайн' },
                { shortLabel: 'Клиент' },
                { shortLabel: 'Оплата' },
                { shortLabel: 'Итог' },
            ],
        };
    },

    computed: {
        progressPercent() {
            return (this.currentStep / (this.steps.length - 1)) * 100;
        },
        selectedBusiness() {
            return this.config.business_types.find(b => b.id === this.formData.businessType);
        },
        selectedDeadline() {
            return this.config.deadlines.find(d => d.id === this.formData.deadline);
        },
        selectedPlan() {
            return this.config.payment_plans.find(p => p.id === this.formData.paymentPlan);
        },
        basePrice() {
            return this.selectedBusiness?.basePrice || 5000;
        },
        featuresTotal() {
            return this.config.features.filter(f => this.formData.features.includes(f.id)).reduce((sum, f) => sum + f.price, 0);
        },
        integrationsTotal() {
            return this.config.integrations.filter(i => this.formData.integrations.includes(i.id)).reduce((sum, i) => sum + i.price, 0);
        },
        designTotal() {
            return this.config.design_options.find(d => d.id === this.formData.design)?.price || 0;
        },
        catalogTotal() {
            return this.config.catalog_options.find(c => c.id === this.formData.catalog)?.price || 0;
        },
        servicesTotal() {
            return this.config.additional_services.filter(s => this.formData.services.includes(s.id) && !s.recurring).reduce((sum, s) => sum + s.price, 0);
        },
        monthlyServicesTotal() {
            return this.config.additional_services.filter(s => this.formData.services.includes(s.id) && s.recurring).reduce((sum, s) => sum + s.price, 0);
        },
        subtotal() {
            return this.basePrice + this.featuresTotal + this.integrationsTotal + this.designTotal + this.catalogTotal + this.servicesTotal;
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
        planPrice() {
            if (this.formData.paymentPlan === 'custom') {
                return Math.max(500, this.formData.customAmount || 0);
            }
            return this.selectedPlan?.price || 0;
        },
        planBalance() {
            if (this.formData.paymentPlan === 'custom') {
                return Math.max(500, this.formData.customAmount || 0);
            }
            return this.selectedPlan?.balance || 0;
        },
        grandTotal() {
            return this.totalPrice + this.planPrice;
        },
    },

    watch: {
        grandTotal(newValue) {
            this.animateNumber(newValue);
        },
    },

    async mounted() {
        await this.fetchCalculatorConfig();
        this.loadFromStorage();
        this.animatedTotal = this.grandTotal;
    },

    methods: {
        async fetchCalculatorConfig() {
            try {
                const response = await axios.get('/agent/calculator/config');
                if (response.data.success) {
                    this.config = response.data.data;
                }
            } catch (error) {
                console.error('Ошибка загрузки конфигурации:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить данные калькулятора', type: 'error' });
            } finally {
                this.isLoadingConfig = false;
            }
        },

        autoGenerateSystemName() {
            if (!this.formData.systemName || this.formData.systemName === this.lastGeneratedSystemName) {
                const ru = 'а-б-в-г-д-е-ё-ж-з-и-й-к-л-м-н-о-п-р-с-т-у-ф-х-ц-ч-ш-щ-ъ-ы-ь-э-ю-я'.split('-');
                const en = 'a-b-v-g-d-e-e-zh-z-i-y-k-l-m-n-o-p-r-s-t-u-f-h-ts-ch-sh-shch-y-y-y-e-yu-ya'.split('-');
                let str = this.formData.customName.toLowerCase();
                ru.forEach((letter, i) => { str = str.split(letter).join(en[i]); });
                str = str.replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
                this.formData.systemName = str;
                this.lastGeneratedSystemName = str;
            }
        },

        selectBusiness(id, enabled) {
            if (!enabled) {
                this.$notify?.({ title: 'Недоступно', text: 'Этот тип бизнеса временно недоступен', type: 'info' });
                return;
            }
            this.formData.businessType = id;
        },

        nextStep() {
            if (this.currentStep === 0) {
                if (!this.formData.businessType) {
                    this.$notify?.({ title: 'Внимание', text: 'Выберите тип бизнеса', type: 'warning' });
                    return;
                }
                const selectedBiz = this.config.business_types.find(b => b.id === this.formData.businessType);
                if (selectedBiz && !selectedBiz.enabled) {
                    this.$notify?.({ title: 'Ошибка', text: 'Выбранный тип бизнеса недоступен', type: 'error' });
                    return;
                }
                if (!this.formData.customName.trim() || !this.formData.systemName.trim()) {
                    this.$notify?.({ title: 'Внимание', text: 'Заполните название и системное имя', type: 'warning' });
                    return;
                }
                if (!/^[a-z0-9-]+$/.test(this.formData.systemName)) {
                    this.$notify?.({ title: 'Ошибка', text: 'Системное имя: только латиница, цифры и дефис', type: 'error' });
                    return;
                }
            }

            if (this.currentStep === 4) {
                if (!this.formData.clientName.trim() || !this.formData.clientPhone.trim()) {
                    this.$notify?.({ title: 'Внимание', text: 'Укажите имя и телефон клиента', type: 'warning' });
                    return;
                }
            }

            if (this.currentStep === 5) {
                if (this.formData.paymentPlan === 'custom') {
                    if (!this.formData.customAmount || this.formData.customAmount < 500) {
                        this.$notify?.({ title: 'Внимание', text: 'Минимальная сумма пополнения: 500 ₽', type: 'warning' });
                        return;
                    }
                }
            }

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
            if (index <= this.currentStep) {
                this.transitionName = index > this.currentStep ? 'slide-left' : 'slide-right';
                this.currentStep = index;
            }
        },

        animateNumber(target) {
            if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
            const start = this.animatedTotal;
            const diff = target - start;
            const duration = 400;
            const startTime = performance.now();

            const step = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                this.animatedTotal = Math.round(start + diff * easeOut);
                if (progress < 1) this.animationFrame = requestAnimationFrame(step);
            };
            this.animationFrame = requestAnimationFrame(step);
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price || 0) + ' ₽';
        },

        saveToStorage() {
            try { localStorage.setItem('calculator_form', JSON.stringify(this.formData)); } catch (e) {}
        },

        loadFromStorage() {
            try {
                const saved = localStorage.getItem('calculator_form');
                if (saved) this.formData = { ...this.formData, ...JSON.parse(saved) };
            } catch (e) {}
        },

        async submitOrder() {
            this.isLoading = true;
            const payload = {
                formData: this.formData,
                total: this.totalPrice,
                monthly: this.monthlyTotal,
                planPrice: this.planPrice,
                planBalance: this.planBalance,
            };

            try {
                const response = await axios.post('/agent/tenants', payload);
                if (response.data.success) {
                    this.$notify?.({ title: "Успех!", text: `Приложение "${response.data.data.name}" создано!`, type: "success" });
                    localStorage.removeItem('calculator_form');
                    this.$router.push({ name: 'AgentTenants' });
                }
            } catch (error) {
                const msg = error.response?.data?.message || 'Не удалось создать приложение.';
                this.$notify?.({ title: "Ошибка", text: msg, type: "error" });
            } finally {
                this.isLoading = false;
            }
        },

        async sendToEmail() {
            // Если email не заполнен в форме, запрашиваем его через prompt
            let email = this.formData.clientEmail;
            if (!email) {
                email = prompt('Пожалуйста, введите Email клиента для отправки предложения:');
                if (!email || !email.includes('@')) {
                    this.$notify?.({ title: 'Ошибка', text: 'Некорректный Email', type: 'error' });
                    return;
                }
                // Сохраняем введенный email в форму
                this.formData.clientEmail = email;
            }

            this.isLoading = true;
            try {
                const payload = {
                    formData: this.formData,
                    clientEmail: email,
                    total: this.totalPrice,
                    monthly: this.monthlyTotal,
                    planPrice: this.planPrice,
                };

                const response = await axios.post('/agent/calculator/send-estimate', payload);

                if (response.data.success) {
                    this.$notify?.({
                        title: 'Успех',
                        text: `Предложение отправлено на ${email} и в Telegram`,
                        type: 'success'
                    });
                }
            } catch (error) {
                console.error('Ошибка отправки:', error);
                const msg = error.response?.data?.message || 'Не удалось отправить предложение. Проверьте настройки почты.';
                this.$notify?.({ title: 'Ошибка', text: msg, type: 'error' });
            } finally {
                this.isLoading = false;
            }
        },

        resetCalculator() {
            this.formData = {
                customName: '', systemName: '', clientName: '', clientPhone: '',
                businessType: null, features: [], integrations: [],
                design: 'template', catalog: 'small', services: [],
                deadline: 'standard', paymentPlan: 'free', customAmount: 1000
            };
            this.currentStep = 0;
            localStorage.removeItem('calculator_form');
        },
    }
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

$primary: #667eea;
$primary-dark: #5a67d8;
$primary-light: #7c8cf5;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e2e8f0;
$bg: #f9fafb;
$card-bg: #ffffff;

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
// ПОЛЯ ВВОДА (ПРЕМИАЛЬНЫЕ)
// ==========================================
.naming-card, .client-data-card {
    background: #ffffff;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    max-width: 700px;
    margin: 0 auto 24px;
}

.form-label-modern {
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
    display: block;
}

.input-icon-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
    transition: color 0.2s;
    pointer-events: none;
}

.modern-input {
    width: 100%;
    padding: 12px 14px 12px 42px;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.95rem;
    color: $text;
    background-color: #ffffff;
    transition: all 0.2s ease;

    &:focus {
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }
}

.input-icon-wrapper:focus-within .input-icon {
    color: $primary;
}

.url-input-group {
    display: flex;
    align-items: stretch;
    border: 1px solid $border;
    border-radius: 10px;
    overflow: hidden;
    background: #ffffff;
    transition: all 0.2s ease;

    &:focus-within {
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
}

.url-prefix, .url-suffix {
    display: flex;
    align-items: center;
    padding: 0 12px;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
}

.url-prefix { border-right: 1px solid $border; }
.url-suffix { border-left: 1px solid $border; }

.url-input {
    flex: 1;
    border: none !important;
    padding: 12px 14px !important;
    box-shadow: none !important;
    text-align: center;
    font-weight: 600;
    color: $primary;

    &::placeholder {
        color: #cbd5e1;
        font-weight: 400;
    }
}

.form-hint {
    display: block;
    margin-top: 6px;
    font-size: 0.75rem;
    color: #94a3b8;
}

.info-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: rgba(59, 130, 246, 0.06);
    border: 1px solid rgba(59, 130, 246, 0.15);
    border-radius: 12px;
    color: #1e40af;
    font-size: 0.9rem;
    line-height: 1.5;

    i { margin-top: 3px; font-size: 1.1rem; }
}

// ==========================================
// КАРТОЧКИ ВЫБОРА
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
    position: relative;

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

    &.is-disabled {
        opacity: 0.5;
        cursor: not-allowed;
        filter: grayscale(70%);
        pointer-events: none;

        &:hover { transform: none; box-shadow: none; border-color: $border; }
        .biz-icon { filter: grayscale(100%); }
    }
}

.disabled-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(2px);
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    z-index: 10;
    pointer-events: auto;

    i { font-size: 1.5rem; color: #64748b; }
    span { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
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
    strong { display: block; font-size: 0.95rem; margin-bottom: 2px; color: $text; }
    span { font-size: 0.8rem; color: $text-muted; }
}

.biz-check {
    color: $primary;
    font-size: 1.2rem;
    opacity: 0;
    transition: opacity 0.3s;
    .is-selected & { opacity: 1; }
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
}

.feature-card, .integration-card, .radio-card {
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

    &:hover { border-color: $primary; transform: translateY(-2px); }
    &.is-selected { border-color: $primary; background: rgba($primary, 0.05); }
}

.hidden-checkbox, .hidden-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.feature-icon, .radio-icon {
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
    .is-selected & { background: $primary; color: white; }
}

.feature-info, .radio-info {
    flex: 1;
    min-width: 0;
    strong { display: block; font-size: 0.9rem; margin-bottom: 2px; color: $text; }
    .feature-price, .radio-price { font-size: 0.8rem; color: $primary; font-weight: 600; }
    span { font-size: 0.8rem; color: $text-muted; }
}

.feature-check, .integration-check {
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
    .is-selected & { opacity: 1; transform: scale(1); }
}

.integrations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.integration-card {
    gap: 16px;
    padding: 16px;
    border-radius: 16px;
    &:hover { transform: translateX(4px); }
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
    strong { display: block; font-size: 1rem; margin-bottom: 2px; color: $text; }
    span { font-size: 0.85rem; color: $text-muted; }
}

.integration-price {
    font-size: 0.95rem;
    font-weight: 700;
    color: $primary;
    flex-shrink: 0;
}

.selection-block {
    margin-bottom: 32px;
    h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 16px; color: $text; }
}

.radio-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    &.compact { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
}

.radio-card.compact {
    flex-direction: column;
    text-align: center;
    padding: 14px;
    strong { font-size: 0.9rem; }
}

// ==========================================
// ТАРИФНЫЕ ПЛАНЫ
// ==========================================
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.plan-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 32px 20px 24px;
    background: #ffffff;
    border: 2px solid $border;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: visible;

    &:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08); }
    &.is-selected { box-shadow: 0 12px 32px rgba(59, 130, 246, 0.15); transform: translateY(-4px); }

    &.is-popular {
        border-color: $primary;
        &::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 22px;
            background: linear-gradient(135deg, $primary 0%, #8b5cf6 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s;
        }
        &.is-selected::before { opacity: 1; }
    }
}

.plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 16px;
    border-radius: 20px;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: 0.3px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.plan-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: white;
    margin-bottom: 16px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.plan-info {
    flex: 1;
    margin-bottom: 16px;
    .plan-name { font-size: 1.15rem; font-weight: 800; color: $text; margin: 0 0 6px; }
    .plan-desc { font-size: 0.85rem; color: $text-muted; margin: 0 0 8px; line-height: 1.4; }
    .plan-details { font-size: 0.75rem; color: #94a3b8; margin: 0; }
}

.plan-price-block { margin-bottom: 8px; }

.plan-price {
    font-size: 1.8rem;
    font-weight: 900;
    color: $text;
    &.free {
        background: linear-gradient(135deg, $success 0%, #059669 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    &.custom-label { font-size: 1.3rem; color: #8b5cf6; }
}

.plan-check {
    color: $primary;
    font-size: 1.5rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    .is-selected & { opacity: 1; transform: scale(1); }
}

.custom-amount-card {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.04) 0%, rgba(139, 92, 246, 0.01) 100%);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 16px;
    padding: 24px;
    max-width: 500px;
    margin: 0 auto;
    h4 { font-size: 1rem; font-weight: 700; color: $text; margin: 0 0 16px; }
}

.custom-amount-input-group {
    display: flex;
    align-items: stretch;
    border: 2px solid $border;
    border-radius: 12px;
    overflow: hidden;
    background: #ffffff;
    margin-bottom: 16px;
    transition: all 0.2s;
    &:focus-within { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
}

.custom-amount-input {
    flex: 1;
    border: none !important;
    padding: 14px 16px !important;
    font-size: 1.3rem !important;
    font-weight: 700 !important;
    color: $text;
    text-align: center;
    background: transparent;
    box-shadow: none !important;
    &:focus { outline: none; }
    &::-webkit-inner-spin-button, &::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    -moz-appearance: textfield;
}

.custom-amount-suffix {
    display: flex;
    align-items: center;
    padding: 0 20px;
    background: #f8fafc;
    color: #64748b;
    font-size: 1.1rem;
    font-weight: 700;
    border-left: 1px solid $border;
}

.custom-amount-presets {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 12px;
}

.preset-btn {
    padding: 8px 16px;
    background: #ffffff;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    &:hover { border-color: #8b5cf6; color: #8b5cf6; background: rgba(139, 92, 246, 0.05); }
    &.is-active { background: #8b5cf6; border-color: #8b5cf6; color: white; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3); }
}

// ==========================================
// РЕЗУЛЬТАТ
// ==========================================
.result-step { text-align: center; }
.result-hero { margin-bottom: 32px; }

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
    .result-label { display: block; font-size: 0.9rem; color: $text-muted; margin-bottom: 8px; }
    .result-price { display: flex; align-items: baseline; justify-content: center; gap: 4px; margin-bottom: 8px; }
    .price-value {
        font-size: 3rem;
        font-weight: 900;
        background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        line-height: 1;
    }
    .price-currency { font-size: 1.5rem; font-weight: 700; color: $primary; }
    .result-subtitle { font-size: 0.9rem; color: $text-muted; }
}

.result-breakdown { text-align: left; margin-bottom: 24px; }
.breakdown-title { font-size: 0.85rem; font-weight: 700; color: $text-muted; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
.breakdown-list { display: flex; flex-direction: column; gap: 8px; }

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: $bg;
    border-radius: 10px;
    font-size: 0.9rem;
    span { color: $text-muted; }
    strong { color: $text; font-weight: 700; }
    &.urgency {
        background: rgba($warning, 0.1);
        span, strong { color: color.adjust($warning, $lightness: -15%); }
    }
    &.plan-line { background: rgba(59, 130, 246, 0.06); border: 1px solid rgba(59, 130, 246, 0.15); }
}

.result-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.stat-block {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: $bg;
    border-radius: 14px;
    text-align: left;
    i { font-size: 1.5rem; color: $primary; }
    strong { display: block; font-size: 1.1rem; color: $text; margin-bottom: 2px; }
    span { font-size: 0.75rem; color: $text-muted; }
    &.savings {
        background: rgba($success, 0.1);
        i { color: $success; }
    }
}

.result-actions { display: flex; flex-direction: column; gap: 10px; }

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
        &:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba($primary, 0.4); }
    }
    &.outline {
        background: $card-bg;
        color: $primary;
        border: 2px solid $primary;
        &:hover { background: rgba($primary, 0.05); }
    }
    &.ghost {
        background: transparent;
        color: $text-muted;
        &:hover { color: $primary; }
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
    z-index: 1030;
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

.summary-info { display: flex; flex-direction: column; }
.summary-label { font-size: 0.75rem; color: $text-muted; margin-bottom: 2px; }
.summary-value { font-size: 1.5rem; font-weight: 800; color: $primary; }
.summary-meta { font-size: 0.85rem; color: $text-muted; }

.footer-actions { display: flex; gap: 10px; max-width: 900px; margin: 0 auto; }

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
        &:hover:not(:disabled) { background: $border; }
        &:disabled { opacity: 0.4; cursor: not-allowed; }
    }
    &.next {
        background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
        color: white;
        box-shadow: 0 4px 16px rgba($primary, 0.3);
        &:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba($primary, 0.4); }
    }
}

// ==========================================
// АНИМАЦИИ И АДАПТИВ
// ==========================================
.slide-left-enter-active, .slide-left-leave-active, .slide-right-enter-active, .slide-right-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-left-enter-from { opacity: 0; transform: translateX(40px); }
.slide-left-leave-to { opacity: 0; transform: translateX(-40px); }
.slide-right-enter-from { opacity: 0; transform: translateX(-40px); }
.slide-right-leave-to { opacity: 0; transform: translateX(40px); }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-12px); max-height: 0; }

@media (max-width: 640px) {
    .cost-calculator { padding: 12px; padding-bottom: 180px; }
    .step-header h2 { font-size: 1.4rem; }
    .step-icon { width: 60px; height: 60px; font-size: 1.5rem; }
    .dot-label { font-size: 0.65rem; max-width: 50px; }
    .dot-inner { width: 34px; height: 34px; font-size: 0.8rem; }
    .business-grid, .features-grid { grid-template-columns: 1fr; }
    .price-value { font-size: 2.2rem; }
    .result-stats { grid-template-columns: 1fr; }
    .footer-summary { flex-direction: column; align-items: flex-start; gap: 4px; }
    .summary-value { font-size: 1.3rem; }

    .plans-grid { grid-template-columns: 1fr; }
    .plan-card { flex-direction: row; text-align: left; padding: 20px; gap: 16px; }
    .plan-icon { margin-bottom: 0; width: 48px; height: 48px; font-size: 1.2rem; }
    .plan-price { font-size: 1.4rem; }
    .custom-amount-presets { gap: 6px; }
    .preset-btn { padding: 6px 12px; font-size: 0.8rem; }
}
</style>
