<template>
    <div class="tab-content fade-in">
        <div class="section-header-page">
            <div>
                <h2>Финансы</h2>
                <p>Управление балансом и выплатами</p>
            </div>
            <div class="page-actions">
                <button class="btn-secondary-modern" @click="$emit('open-invoice')">
                    <i class="fa-solid fa-file-invoice"></i> Выставить счёт
                </button>
                <button class="btn-primary-modern" @click="showPayoutModal = true"><i
                    class="fa-solid fa-money-bill-transfer"></i> Запросить выплату
                </button>
            </div>
        </div>

        <div class="finance-summary">
            <div class="finance-card available">
                <div class="finance-label">Доступно к выводу</div>
                <div class="finance-value">{{ formatPrice(agent.balance) }}</div>
                <button class="finance-action" @click="showPayoutModal = true">Вывести</button>
            </div>
            <div class="finance-card pending">
                <div class="finance-label">Ожидает подтверждения</div>
                <div class="finance-value">{{ formatPrice(agent.pending_balance) }}</div>
                <div class="finance-hint">После подтверждения клиентом</div>
            </div>
            <div class="finance-card total">
                <div class="finance-label">Всего заработано</div>
                <div class="finance-value">{{ formatPrice(agent.total_earned) }}</div>
                <div class="finance-hint">За всё время</div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h3>История операций</h3>
                <select v-model="filter" class="select-modern">
                    <option value="all">Все операции</option>
                    <option value="income">Начисления</option>
                    <option value="payout">Выплаты</option>
                </select>
            </div>
            <div class="transactions-list">
                <div v-for="tx in filteredTransactions" :key="tx.id" class="transaction-item">
                    <div class="tx-icon" :class="'type-' + tx.type"><i :class="tx.icon"></i></div>
                    <div class="tx-info">
                        <div class="tx-title">{{ tx.title }}</div>
                        <div class="tx-date">{{ tx.date }}</div>
                    </div>
                    <div class="tx-amount" :class="tx.amountClass">{{ tx.amount }}</div>
                </div>
            </div>
        </div>

        <!-- Модалка выплаты (упрощенная) -->
        <div v-if="showPayoutModal" class="modal-overlay" @click.self="showPayoutModal = false">
            <div class="modal-container small">
                <div class="modal-header">
                    <h3>Запрос выплаты</h3>
                    <button class="modal-close" @click="showPayoutModal = false"><i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Сумма к выводу, ₽</label>
                        <input type="number" v-model.number="payoutAmount" class="form-input" :max="agent.balance"
                               placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary-modern" @click="showPayoutModal = false">Отмена</button>
                    <button class="btn-primary-modern" @click="submitPayout" :disabled="!payoutAmount">Запросить
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AgentFinance",
    props: {agent: Object, transactions: Array},
    emits: ['payout-requested', 'open-invoice'],
    data() {
        return {filter: 'all', showPayoutModal: false, payoutAmount: null};
    },
    computed: {
        filteredTransactions() {
            if (this.filter === 'all') return this.transactions;
            return this.transactions.filter(t => t.type === this.filter);
        }
    },
    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(price || 0);
        },
        submitPayout() {
            if (!this.payoutAmount || this.payoutAmount > this.agent.balance) return;
            this.$emit('payout-requested', {amount: this.payoutAmount});
            this.showPayoutModal = false;
            this.payoutAmount = null;
        }
    }
};
</script>

<style lang="scss" scoped>
/* Стили для финансов (сокращенно, возьмите основные классы из предыдущего ответа) */
.tab-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-header-page {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.section-header-page h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: #1f2937;
}

.section-header-page p {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.btn-primary-modern, .btn-secondary-modern {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

.btn-primary-modern {
    background: #3b82f6;
    color: white;
}

.btn-secondary-modern {
    background: #f9fafb;
    color: #1f2937;
    border: 1px solid #e5e7eb;
}

.finance-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.finance-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.finance-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.finance-card.available::before {
    background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
}

.finance-card.pending::before {
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.finance-card.total::before {
    background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
}

.finance-label {
    font-size: 0.8rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.finance-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 8px;
}

.finance-hint {
    font-size: 0.8rem;
    color: #6b7280;
}

.finance-action {
    margin-top: 12px;
    padding: 8px 16px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.section-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.section-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}

.select-modern {
    padding: 8px 32px 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.85rem;
    background: white;
}

.transactions-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.transaction-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px;
    border-radius: 10px;
    transition: background 0.2s;
}

.transaction-item:hover {
    background: #f9fafb;
}

.tx-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.tx-icon.type-income {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.tx-icon.type-payout {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.tx-info {
    flex: 1;
    min-width: 0;
}

.tx-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1f2937;
    margin-bottom: 2px;
}

.tx-date {
    font-size: 0.8rem;
    color: #6b7280;
}

.tx-amount {
    font-weight: 700;
    font-size: 0.95rem;
}

.tx-amount.income {
    color: #10b981;
}

.tx-amount.expense {
    color: #ef4444;
}

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
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    overflow: hidden;
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f9fafb;
    border: none;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background: #ef4444;
    color: white;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    display: flex;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.modal-footer button {
    flex: 1;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
