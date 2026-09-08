<!-- resources/js/AdminPanel/Pages/Transactions/Show.vue -->
<template>
    <div class="transaction-show">
        <div class="back-link">
            <router-link :to="{ name: 'admin.transactions.index' }">
                ← Назад к списку
            </router-link>
        </div>

        <div class="tx-card" v-if="transaction">
            <div class="tx-header">
                <div>
                    <h1>Транзакция #{{ transaction.id }}</h1>
                    <span class="status-badge" :class="transaction.status">
                        {{ statusLabel(transaction.status) }}
                    </span>
                </div>
                <div class="tx-amount" :class="transaction.amount > 0 ? 'positive' : 'negative'">
                    {{ transaction.amount > 0 ? '+' : '' }}{{ formatCurrency(transaction.amount) }}
                </div>
            </div>

            <div class="tx-grid">
                <div class="info-section">
                    <h3>📋 Основная информация</h3>
                    <div class="info-row">
                        <span class="label">Тип:</span>
                        <span class="type-badge" :class="transaction.type">{{ typeLabel(transaction.type) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Тенант:</span>
                        <span>{{ transaction.tenantName }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Метод оплаты:</span>
                        <span>{{ transaction.method }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Время создания:</span>
                        <span>{{ formatDate(transaction.created_at) }}</span>
                    </div>
                </div>

                <div class="info-section">
                    <h3>💳 Платёжные данные</h3>
                    <div class="info-row">
                        <span class="label">Внешний ID:</span>
                        <span class="mono">TX-{{ transaction.id }}-{{ transaction.externalId }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Комиссия:</span>
                        <span>{{ formatCurrency(transaction.fee || 0) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Итоговая сумма:</span>
                        <span class="final-amount">{{ formatCurrency(transaction.amount - (transaction.fee || 0)) }}</span>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button v-if="transaction.status === 'pending'" class="btn-primary">
                    ✅ Подтвердить
                </button>
                <button v-if="transaction.status === 'pending'" class="btn-danger">
                    ❌ Отклонить
                </button>
                <button class="btn-secondary">
                    📄 Скачать чек
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const transaction = ref(null)

onMounted(async () => {
    // Здесь будет fetch к API: `/api/admin/transactions/${route.params.id}`
    // Пока мок-данные:
    transaction.value = {
        id: route.params.id,
        tenantName: 'Fastoran',
        type: 'payment',
        amount: 15000,
        status: 'completed',
        method: 'СБП Тинькофф',
        created_at: '2026-09-08 14:23:00',
        externalId: 'a1b2c3d4',
        fee: 150,
    }
})

const formatCurrency = (n) => new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(n)
const formatDate = (d) => new Date(d).toLocaleString('ru-RU')
const typeLabel = (t) => ({ payment: 'Платёж', withdrawal: 'Вывод', subscription: 'Подписка', tax: 'Налог' }[t] || t)
const statusLabel = (s) => ({ completed: 'Завершён', pending: 'В обработке', failed: 'Отклонён' }[s] || s)
</script>

<style scoped>
.transaction-show { padding: 24px; }
.back-link { margin-bottom: 20px; }
.back-link a { color: #667eea; text-decoration: none; font-size: 14px; }
.back-link a:hover { text-decoration: underline; }

.tx-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.tx-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #eee; }
.tx-header h1 { margin: 0 0 8px; font-size: 28px; }

.tx-amount { font-size: 32px; font-weight: 700; font-family: 'SF Mono', Monaco, monospace; }
.tx-amount.positive { color: #28a745; }
.tx-amount.negative { color: #dc3545; }

.tx-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 32px; }
.info-section h3 { margin: 0 0 16px; font-size: 16px; color: #333; }
.info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
.info-row:last-child { border-bottom: none; }
.label { color: #888; font-size: 14px; }
.mono { font-family: 'SF Mono', Monaco, monospace; font-size: 13px; }
.final-amount { font-weight: 600; font-size: 16px; }

.type-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.type-badge.payment { background: #d1ecf1; color: #0c5460; }

.status-badge { padding: 6px 14px; border-radius: 16px; font-size: 13px; font-weight: 500; }
.status-badge.completed { background: #d4edda; color: #155724; }

.actions { display: flex; gap: 12px; flex-wrap: wrap; }
.btn-primary, .btn-danger, .btn-secondary { padding: 12px 24px; border-radius: 10px; border: none; font-size: 14px; font-weight: 500; cursor: pointer; transition: opacity 0.2s; }
.btn-primary { background: #28a745; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-secondary { background: #f0f0f0; color: #333; }
.btn-primary:hover, .btn-danger:hover, .btn-secondary:hover { opacity: 0.9; }
</style>
