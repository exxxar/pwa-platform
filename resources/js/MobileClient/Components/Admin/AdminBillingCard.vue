<template>
    <div class="billing-card">
        <!-- Основная информация -->
        <div class="billing-info">
            <div class="info-row">
                <span class="info-label">Текущий баланс:</span>
                <span class="info-value highlight">{{ formatPrice(currentBalance) }}
                    ( {{(currentBalance / currentTaxPerDay).toFixed(0)}} {{ pluralize((currentBalance / currentTaxPerDay).toFixed(0), 'день', 'дня', 'дней') }} )
                    </span>
            </div>
            <div class="info-row">
                <span class="info-label">Активный тариф:</span>
                <span class="info-value">{{ currentPlanName }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Цена за день:</span>
                <span class="info-value">{{  formatPrice(currentTaxPerDay) }}</span>
            </div>

        </div>

        <!-- Кнопки действий -->
        <div class="billing-actions">
            <button class="action-btn primary" @click="openTopUpModal">
                <i class="fa-solid fa-wallet"></i> Пополнить баланс
            </button>
            <button class="action-btn secondary" @click="openPlanModal">
                <i class="fa-solid fa-layer-group"></i> Сменить тариф
            </button>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОПОЛНЕНИЕ БАЛАНСА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showTopUpModal" class="modal-overlay" @click.self="showTopUpModal = false">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>Пополнение баланса</h3>
                        <button class="modal-close" @click="showTopUpModal = false">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Сумма пополнения (₽)</label>
                            <input
                                type="number"
                                v-model.number="topUpAmount"
                                min="100"
                                step="100"
                                class="form-input"
                                placeholder="Например: 1000"
                            >
                        </div>
                        <div class="modal-footer-actions">
                            <button class="btn-cancel" @click="showTopUpModal = false">Отмена</button>
                            <button
                                class="btn-confirm primary"
                                :disabled="isSubmitting || !topUpAmount || topUpAmount < 100"
                                @click="submitTopUp"
                            >
                                <i v-if="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                                <span v-else>Пополнить</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ВЫБОР ТАРИФА (с загрузкой с бэка) -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showPlanModal" class="modal-overlay" @click.self="showPlanModal = false">
                <div class="modal-container modal-large">
                    <div class="modal-header">
                        <h3>Выберите тарифный план</h3>
                        <button class="modal-close" @click="showPlanModal = false">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Состояние загрузки тарифов -->
                        <div v-if="isLoadingPlans" class="loading-state">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            <span>Загрузка тарифов...</span>
                        </div>

                        <!-- Список тарифов -->
                        <div v-else class="plans-grid">
                            <div
                                v-for="plan in pricingPlans"
                                :key="plan.slug"
                                class="plan-card"
                                :class="{ 'is-active': currentPlanSlug === plan.slug, 'is-selected': selectedPlanSlug === plan.slug }"
                                @click="selectedPlanSlug = plan.slug"
                            >
                                <div class="plan-header">
                                    <h4>{{ plan.title }}</h4>
                                    <span class="plan-price">{{ formatPrice(plan.price) }} <span
                                        class="period">/ мес</span></span>
                                </div>
                                <ul class="plan-features" v-if="plan.features && plan.features.length">
                                    <li v-for="(feature, idx) in plan.features" :key="idx">
                                        <i class="fa-solid fa-check"></i> {{ feature }}
                                    </li>
                                </ul>
                                <div class="plan-badge" v-if="currentPlanSlug === plan.slug">Текущий</div>
                            </div>
                        </div>

                        <div class="modal-footer-actions" v-if="!isLoadingPlans">
                            <button class="btn-cancel" @click="showPlanModal = false">Отмена</button>
                            <button
                                class="btn-confirm primary"
                                :disabled="isSubmitting || selectedPlanSlug === currentPlanSlug"
                                @click="submitPlanChange"
                            >
                                <i v-if="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                                <span v-else>Сохранить изменения</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'AdminBillingCard',

    data() {
        return {
            // Данные для модальных окон
            showTopUpModal: false,
            showPlanModal: false,
            topUpAmount: 1000,
            selectedPlanSlug: null,

            // Данные тарифов с бэкенда
            pricingPlans: [],
            isLoadingPlans: false,
            isSubmitting: false,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },
        currentBalance() {
            return this.tenant.balance || 0
        },
        currentPlanSlug() {
            return this.tenant.plan_slug  || 'free'
        },

        currentTaxPerDay(){
            return this.tenant?.tax_per_day || 0
        },
        currentPlanName() {
            // 1. Если массив пуст или еще не загрузился
            if (!this.pricingPlans || this.pricingPlans.length === 0) {
                return this.tenant?.tax_per_day ? `${this.tenant.tax_per_day} ₽/день` : 'Загрузка тарифов...';
            }

            // 2. Безопасно ищем текущий план
            const currentPlan = this.pricingPlans.find(p => p.slug === this.currentPlanSlug);


            // 3. Если план найден в списке, возвращаем его название.
            // Если нет (например, у пользователя старый индивидуальный тариф), показываем его текущую цену.
            return currentPlan?.title || (this.tenant?.tax_per_day ? `Индивидуальный (${this.tenant.tax_per_day} ₽/день)` : 'Не выбран');
        }

    },
    mounted() {
        if (this.pricingPlans.length === 0) {
            this.fetchPricingPlans();
        }
    },

    methods: {

        // 2. Загрузка списка тарифов с бэкенда
        async fetchPricingPlans() {
            this.isLoadingPlans = true;
            try {
                const response = await axios.get('/admin/tenant/pricing-plans');
                // Ожидаем массив объектов: [{ slug: 'start', name: 'Старт', price: 990, features: [...] }]
                this.pricingPlans = Array.isArray(response.data) ? response.data : (response.data.data || []);

            } catch (error) {
                console.error('Ошибка загрузки тарифов:', error);
                this.$notify?.({title: 'Ошибка', text: 'Не удалось загрузить тарифы', type: 'error'});
            } finally {
                this.isLoadingPlans = false;
            }
        },

        // --- Управление модальными окнами ---
        openTopUpModal() {
            this.topUpAmount = 1000; // Сброс к значению по умолчанию
            this.showTopUpModal = true;
        },

        async openPlanModal() {
            this.selectedPlanSlug = this.currentPlanSlug;
            this.showPlanModal = true;
            // Загружаем тарифы только при открытии модалки (или можно в mounted)

        },

        // --- Отправка данных на бэкенд ---
        async submitTopUp() {
            if (!this.topUpAmount || this.topUpAmount < 100) return;

            this.isSubmitting = true;
            try {
                // ⚠️ ЗАМЕНИТЕ ЭТОТ URL на ваш реальный эндпоинт пополнения баланса
                const response = await axios.post('/admin/tenant/top-up', {
                    amount: this.topUpAmount
                });

                this.currentBalance = response.data.balance || (this.currentBalance + this.topUpAmount);
                this.$notify?.({title: 'Успех', text: 'Баланс успешно пополнен', type: 'success'});
                this.showTopUpModal = false;
            } catch (error) {
                console.error('Ошибка пополнения:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось пополнить баланс',
                    type: 'error'
                });
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitPlanChange() {
            if (!this.selectedPlanSlug || this.selectedPlanSlug === this.currentPlanSlug) return;

            this.isSubmitting = true;
            try {
                const response = await axios.post('/admin/tenant/update-plan', {
                    plan_slug: this.selectedPlanSlug
                });

                // Обновляем локальное состояние после успешного ответа
                this.currentPlanSlug = this.selectedPlanSlug;

                // Находим имя нового тарифа для отображения
                const newPlan = this.pricingPlans.find(p => p.slug === this.selectedPlanSlug);
                if (newPlan) {
                    this.currentPlanName = newPlan.name;
                }

                this.$notify?.({title: 'Успех', text: 'Тарифный план успешно изменен', type: 'success'});
                this.showPlanModal = false;
            } catch (error) {
                console.error('Ошибка смены тарифа:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось изменить тариф',
                    type: 'error'
                });
            } finally {
                this.isSubmitting = false;
            }
        },
        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
        // Утилита форматирования цены
        formatPrice(price) {
            if (!price && price !== 0) return '0 ₽';
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-hover: #2563eb;
$bg-card: #ffffff;
$border: #e2e8f0;
$text: #0f172a;
$text-muted: #64748b;

$success: #10b981;
$success-light: #ecfdf5;
$bg-secondary: #f8fafc;
$primary: #3b82f6;
$primary-light: #eff6ff;
$text: #1f2937;
$text-muted: #6b7280;
$admin-text-muted: #6b7280;
$border: #e5e7eb;
$bg: #ffffff;
$admin-bg: #ffffff;


.billing-card {
    background: $bg-card;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.billing-info {
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid $border;

    &:last-child {
        border-bottom: none;
    }
}

.info-label {
    font-size: 0.9rem;
    color: $text-muted;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: $text;

    &.highlight {
        color: $primary;
        font-size: 1.1rem;
    }
}

.billing-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    border: none;

    &.primary {
        background: $primary;
        color: white;

        &:hover {
            background: $primary-hover;
        }
    }

    &.secondary {
        background: transparent;
        border: 1px solid $border;
        color: $text;

        &:hover {
            background: #f8fafc;
            border-color: $text-muted;
        }
    }
}

// ==========================================
// СТИЛИ МОДАЛЬНЫХ ОКОН
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 450px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    overflow: hidden;

    &.modal-large {
        max-width: 700px;
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid $border;

    h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: $text;
    }
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f1f5f9;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: #ef4444;
        color: white;
    }
}

.modal-body {
    padding: 20px;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: $text-muted;
    gap: 12px;
}

// Сетка тарифов
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
    max-height: 400px;
    overflow-y: auto;
    padding-right: 4px;
}

.plan-card {
    border: 2px solid $border;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;

    &:hover {
        border-color: $primary;
        background: #f8fafc;
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.05);
    }

    &.is-active {
        border-color: $text-muted;
        background: #f1f5f9;
        cursor: default;
    }
}

.plan-header {
    margin-bottom: 12px;

    h4 {
        margin: 0 0 4px 0;
        font-size: 1rem;
        font-weight: 700;
    }
}

.plan-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: $primary;

    .period {
        font-size: 0.8rem;
        font-weight: 400;
        color: $text-muted;
    }
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 12px 0;

    li {
        font-size: 0.85rem;
        color: $text-muted;
        margin-bottom: 6px;
        display: flex;
        align-items: flex-start;
        gap: 6px;

        i {
            color: $success;
            margin-top: 3px;
        }
    }
}

.plan-badge {
    position: absolute;
    top: -10px;
    right: 12px;
    background: $text-muted;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 6px;
    text-transform: uppercase;
}

// Формы и кнопки в модалках
.form-group {
    margin-bottom: 20px;

    label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 8px;
    }
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: 1rem;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }
}

.modal-footer-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-direction: column;
}

.btn-cancel, .btn-confirm {
    flex: 1;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    border: none;
}

.btn-cancel {
    background: #f1f5f9;
    color: $text;

    &:hover {
        background: #e2e8f0;
    }
}

.btn-confirm {
    background: $primary;
    color: white;

    &:hover:not(:disabled) {
        background: $primary-hover;
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

// Анимации
.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.25s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}
</style>
