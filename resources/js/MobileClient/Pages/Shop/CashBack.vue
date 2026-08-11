<template>
    <div class="cashback-page pb-5 pt-3">
        <!-- Состояние загрузки -->
        <div v-if="loading" class="loading-state">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>

        <template v-else>
            <!-- ===== HERO: ГЛАВНЫЙ БАЛАНС ===== -->
            <div class="balance-hero">
                <div class="balance-background-pattern"></div>
                <div class="balance-content">
                    <div class="balance-label">
                        <i class="fa-solid fa-wallet me-2"></i>
                        Ваш баланс CashBack
                    </div>
                    <div class="balance-amount">
                        {{ self?.cashback_balance }} ₽
                    </div>
                    <div class="balance-hint">
                        Баллы можно использовать для оплаты следующих заказов
                    </div>

                    <div class="balance-actions">
                        <button class="btn-redeem" @click="goToCatalog">
                            <i class="fa-solid fa-bag-shopping me-2"></i>
                            Потратить баллы
                        </button>
                        <button class="btn-download" @click="downloadHistory">
                            <i class="fa-solid fa-download me-2"></i>
                            Скачать историю
                        </button>
                        <!-- 🆕 КНОПКА УПРАВЛЕНИЯ БАЛЛАМИ (только для админов) -->
                        <button v-if="isAdmin" class="btn-manage" @click="openCashbackModal">
                            <i class="fa-solid fa-coins me-2"></i>
                            Управление баллами
                        </button>
                    </div>
                </div>
            </div>

            <div class="container px-3">
                <!-- ===== СПЕЦИАЛЬНЫЕ НАЧИСЛЕНИЯ ===== -->
                <div v-if="hasSpecialSubs" class="section-block mt-4">
                    <div class="section-header">
                        <div class="section-icon gift-icon">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <h6 class="section-title">Специальные начисления</h6>
                    </div>

                    <div class="subs-grid">
                        <div
                            v-for="(sub, index) in specialSubs"
                            :key="index"
                            class="sub-card"
                        >
                            <div class="sub-info">
                                <div class="sub-title">{{ sub.sub_title || 'Бонус' }}</div>
                                <div class="sub-desc">Реферальная программа</div>
                            </div>
                            <div class="sub-amount">
                                +{{ formatCurrency(sub.total) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== ИСТОРИЯ ОПЕРАЦИЙ ===== -->
                <div class="section-block mt-4">
                    <div class="section-header">
                        <div class="section-icon history-icon">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <h6 class="section-title">История операций</h6>
                    </div>

                    <div class="history-wrapper">
                        <CashBackList />
                    </div>
                </div>
            </div>
        </template>

        <!-- 🆕 МОДАЛКА: УПРАВЛЕНИЕ БАЛЛАМИ (НАЧИСЛЕНИЕ / СПИСАНИЕ) -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showCashbackModal" class="modal-overlay" @click.self="showCashbackModal = false">
                    <div class="action-modal">
                        <div class="modal-icon" :class="cashbackForm.type === 'credit' ? 'bonus' : 'danger'">
                            <i :class="cashbackForm.type === 'credit' ? 'fa-solid fa-coins' : 'fa-solid fa-coins fa-flip-horizontal'"></i>
                        </div>
                        <h3>{{ cashbackForm.type === 'credit' ? 'Начислить баллы' : 'Списать баллы' }}</h3>
                        <p class="modal-subtitle">
                            Пользователь: <strong>{{ self?.name || 'Вы' }}</strong><br>
                            Текущий баланс: <strong>{{ self?.cashback_balance || 0 }}</strong> баллов
                        </p>

                        <!-- Переключатель типа операции -->
                        <div class="operation-toggle">
                            <button
                                class="toggle-btn"
                                :class="{ 'active': cashbackForm.type === 'credit' }"
                                @click="cashbackForm.type = 'credit'"
                            >
                                <i class="fa-solid fa-plus"></i> Начислить
                            </button>
                            <button
                                class="toggle-btn"
                                :class="{ 'active': cashbackForm.type === 'debit' }"
                                @click="cashbackForm.type = 'debit'"
                            >
                                <i class="fa-solid fa-minus"></i> Списать
                            </button>
                        </div>

                        <div class="form-field">
                            <label>Количество баллов</label>
                            <input
                                type="number"
                                v-model.number="cashbackForm.amount"
                                min="1"
                                :max="cashbackForm.type === 'debit' ? (self?.cashback_balance || 0) : 1000000"
                                placeholder="Например: 100"
                            >
                        </div>
                        <div class="form-field">
                            <label>Причина (опционально)</label>
                            <input
                                type="text"
                                v-model="cashbackForm.description"
                                :placeholder="cashbackForm.type === 'credit' ? 'За отзыв, компенсация...' : 'Ошибка начисления, списание за...'"
                            >
                        </div>

                        <div class="quick-amounts">
                            <button
                                v-for="amount in [50, 100, 250, 500]"
                                :key="amount"
                                @click="cashbackForm.amount = amount"
                            >
                                {{ amount }}
                            </button>
                        </div>

                        <div class="modal-actions">
                            <button class="btn-cancel" @click="showCashbackModal = false">Отмена</button>
                            <button
                                class="btn-confirm"
                                :class="cashbackForm.type === 'credit' ? 'bonus' : 'danger'"
                                @click="confirmCashback"
                                :disabled="isProcessing || !cashbackForm.amount || cashbackForm.amount <= 0"
                            >
                                <span v-if="isProcessing" class="btn-spinner"></span>
                                <span v-else>{{ cashbackForm.type === 'credit' ? 'Начислить' : 'Списать' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script>
import axios from 'axios';
import CashBackList from '@/MobileClient/Components/Shop/CashBack/CashBackList.vue';
import { useCashback } from '@/MobileClient/composables/useCashback';
import { usePermissions } from '@/MobileClient/Composables/usePermissions.js';
import { onMounted } from 'vue';

export default {
    name: "CashBackPage",

    components: {
        CashBackList
    },

    setup() {
        const {
            loading,
            specialSubs,
            formattedBalance,
            hasSpecialSubs,
            fetchCashbackData,
            downloadHistory,
        } = useCashback();

        const { isAdmin } = usePermissions();

        onMounted(() => {
            fetchCashbackData();
        });

        return {
            loading,
            specialSubs,
            formattedBalance,
            hasSpecialSubs,
            downloadHistory,
            fetchCashbackData,
            isAdmin,
        };
    },

    data() {
        return {
            // 🆕 Модалка управления баллами
            showCashbackModal: false,
            isProcessing: false,
            cashbackForm: { type: 'credit', amount: null, description: '' },
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },
    },

    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value);
        },

        goToCatalog() {
            this.$router.push({ name: 'Catalog' });
        },

        // ===== 🆕 УПРАВЛЕНИЕ БАЛЛАМИ =====
        openCashbackModal() {
            this.cashbackForm = { type: 'credit', amount: null, description: '' };
            this.showCashbackModal = true;
        },

        async confirmCashback() {
            if (!this.cashbackForm.amount || this.cashbackForm.amount <= 0) return;

            // 🛡️ Защита от списания большего количества баллов, чем есть на балансе
            if (this.cashbackForm.type === 'debit') {
                const currentBalance = this.self?.cashback_balance || 0;
                if (this.cashbackForm.amount > currentBalance) {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: `Недостаточно баллов. Доступно: ${currentBalance}`,
                        type: 'error'
                    });
                    return;
                }
            }

            this.isProcessing = true;
            try {
                const payload = {
                    amount: this.cashbackForm.amount,
                    description: this.cashbackForm.description || (this.cashbackForm.type === 'credit' ? 'Ручное начисление' : 'Ручное списание'),
                    type: this.cashbackForm.type, // 'credit' или 'debit'
                    user_id: this.self?.id,
                };

                // 🔄 Запрос к API управления баллами
                // При необходимости замените эндпоинт на актуальный
                await axios.post(`/admin/users/${this.self.id}/add-cashback`, payload);

                // 🔄 Обновляем баланс локально для мгновенного отклика UI
                if (window.TenantUser) {
                    const currentBalance = window.TenantUser.cashback_balance || 0;
                    if (this.cashbackForm.type === 'credit') {
                        window.TenantUser.cashback_balance = currentBalance + this.cashbackForm.amount;
                    } else {
                        window.TenantUser.cashback_balance = Math.max(0, currentBalance - this.cashbackForm.amount);
                    }
                }

                this.$notify?.({
                    title: 'Успех',
                    text: `${this.cashbackForm.type === 'credit' ? 'Начислено' : 'Списано'} ${this.cashbackForm.amount} баллов`,
                    type: 'success'
                });

                this.showCashbackModal = false;

                // Перезагружаем данные для обновления истории операций
                await this.fetchCashbackData();
            } catch (e) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: e.response?.data?.message || 'Не удалось выполнить операцию',
                    type: 'error'
                });
            } finally {
                this.isProcessing = false;
            }
        },
    }
};
</script>

<style scoped>
.cashback-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

.balance-hero {
    position: relative;
    margin: 0 16px 24px;
    padding: 32px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border-radius: 24px;
    color: white;
    box-shadow: 0 12px 32px rgba(var(--bs-primary-rgb, 255, 138, 0), 0.3);
    overflow: hidden;
}

.balance-background-pattern {
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.balance-content {
    position: relative;
    z-index: 1;
    text-align: center;
}

.balance-label {
    font-size: 0.9rem;
    font-weight: 500;
    opacity: 0.9;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.balance-amount {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 12px;
    text-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    letter-spacing: -1px;
}

.balance-hint {
    font-size: 0.85rem;
    opacity: 0.85;
    margin-bottom: 24px;
    line-height: 1.4;
}

.balance-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-redeem,
.btn-download,
.btn-manage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 24px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-redeem:hover,
.btn-download:hover,
.btn-manage:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

/* 🆕 Выделение кнопки управления баллами для админа */
.btn-manage {
    background: rgba(255, 215, 0, 0.25);
    border-color: rgba(255, 215, 0, 0.5);
}

.btn-manage:hover {
    background: rgba(255, 215, 0, 0.4);
    border-color: rgba(255, 215, 0, 0.7);
}

.section-block {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.gift-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
}

.history-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

.subs-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.sub-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    transition: all 0.2s ease;
}

.sub-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.08);
    transform: translateY(-2px);
}

.sub-info {
    flex: 1;
    min-width: 0;
}

.sub-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.sub-desc {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.sub-amount {
    font-weight: 700;
    font-size: 1.1rem;
    color: #198754;
    white-space: nowrap;
    margin-left: 12px;
}

.history-wrapper {
    background: var(--bs-body-bg);
    border-radius: 16px;
    border: 1px solid var(--bs-border-color);
    overflow: hidden;
}

.loading-state {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bs-body-bg);
}

/* ===== 🆕 СТИЛИ МОДАЛКИ УПРАВЛЕНИЯ БАЛЛАМИ ===== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 3500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.action-modal {
    background: var(--bs-body-bg);
    border-radius: 20px;
    padding: 28px 24px;
    width: 100%;
    max-width: 400px;
    text-align: center;
    animation: modalSlideUp 0.3s ease;
    color: var(--bs-body-color);
}

.action-modal h3 {
    font-size: 1.2rem;
    margin: 0 0 8px;
}

.modal-subtitle {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin: 0 0 20px;
    line-height: 1.5;
}

.modal-subtitle strong {
    color: var(--bs-body-color);
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    margin: 0 auto 16px;
}

.modal-icon.bonus {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

.modal-icon.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.operation-toggle {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    background: var(--bs-tertiary-bg, #f3f4f6);
    padding: 4px;
    border-radius: 10px;
}

.toggle-btn {
    flex: 1;
    padding: 10px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.toggle-btn.active {
    background: var(--bs-body-bg);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.toggle-btn:first-child.active {
    color: #10b981;
}

.toggle-btn:last-child.active {
    color: #ef4444;
}

.form-field {
    text-align: left;
    margin-bottom: 12px;
}

.form-field label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    margin-bottom: 4px;
}

.form-field input,
.form-field textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
}

.form-field input:focus,
.form-field textarea:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
}

.quick-amounts {
    display: flex;
    gap: 6px;
    margin-bottom: 20px;
}

.quick-amounts button {
    flex: 1;
    padding: 8px;
    background: var(--bs-tertiary-bg, #f3f4f6);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--bs-body-color);
}

.quick-amounts button:hover {
    background: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.modal-actions button {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-cancel {
    background: var(--bs-tertiary-bg, #f3f4f6);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color) !important;
}

.btn-cancel:hover {
    background: var(--bs-border-color);
}

.btn-confirm {
    color: white;
}

.btn-confirm.bonus {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

.btn-confirm.danger {
    background: #ef4444;
}

.btn-confirm.danger:hover {
    background: #dc2626;
}

.btn-confirm:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (max-width: 576px) {
    .balance-amount {
        font-size: 2.8rem;
    }

    .balance-hero {
        padding: 24px 20px;
    }

    .balance-actions {
        flex-direction: column;
    }

    .btn-redeem,
    .btn-download,
    .btn-manage {
        width: 100%;
    }
}
</style>
