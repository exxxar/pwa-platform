<template>
    <div class="cashback-page">

        <!-- ========================================== -->
        <!-- ЗАГОЛОВОК -->
        <!-- ========================================== -->
        <div class="page-header">
            <div class="header-icon">
                <i class="fa-solid fa-coins"></i>
            </div>
            <h2 class="header-title">CashBack</h2>
        </div>

        <!-- ========================================== -->
        <!-- КАРТОЧКА БАЛАНСА -->
        <!-- ========================================== -->
        <div v-if="self" class="balance-section">
            <div class="balance-card">
                <div class="balance-label">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Ваш текущий баланс</span>
                </div>
                <div class="balance-amount">
                    {{ formatPrice(self.cashBack?.amount || 0) }}
                </div>
                <div class="balance-decoration">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СПЕЦИАЛЬНЫЕ НАЧИСЛЕНИЯ -->
        <!-- ========================================== -->
        <div v-if="self && specialSubs.length > 0" class="special-section">
            <div class="section-header">
                <i class="fa-solid fa-star"></i>
                <h3>Специальные начисления</h3>
            </div>

            <div class="special-list">
                <div
                    v-for="(sub, idx) in specialSubs"
                    :key="'sub-' + idx"
                    class="special-item"
                >
                    <div class="special-info">
                        <div class="special-icon">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <span class="special-title">{{ sub.title || 'Начисление' }}</span>
                    </div>
                    <div class="special-amount">
                        +{{ formatPrice(sub.amount || 0) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ИСТОРИЯ ОПЕРАЦИЙ -->
        <!-- ========================================== -->
        <div v-if="self" class="history-section">
            <div class="section-header">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h3>История операций</h3>
            </div>

            <CashBackList
                :bot-user="self"
            />
        </div>

        <!-- ========================================== -->
        <!-- ПУСТОЕ СОСТОЯНИЕ -->
        <!-- ========================================== -->
        <div v-if="!self" class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-coins"></i>
            </div>
            <h3>Данные не загружены</h3>
            <p>Пожалуйста, авторизуйтесь в Telegram</p>
        </div>

    </div>
</template>

<script>
import { mapState } from 'pinia'
import CashBackList from '@/MobileClient/Components/CashBack/CashBackList.vue'

export default {
    name: 'CashBackPage',

    components: {
        CashBackList,
    },

    computed: {
        ...mapState('cashback', ['getSelf']),

        self() {
            return this.getSelf
        },

        specialSubs() {
            return this.self?.cashBack?.subs || []
        },
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0)
        },
    },
}
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-warning: #f59e0b;
$admin-gold: #fbbf24;

.cashback-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ЗАГОЛОВОК
// ==========================================
.page-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba($admin-gold, 0.2) 0%, rgba($admin-gold, 0.1) 100%);
    color: $admin-gold;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.header-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0;
}

// ==========================================
// КАРТОЧКА БАЛАНСА
// ==========================================
.balance-section {
    padding: 16px;
}

.balance-card {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.balance-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 12px;

    i {
        font-size: 1rem;
    }
}

.balance-amount {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 8px;
}

.balance-decoration {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 4rem;
    opacity: 0.15;
}

// ==========================================
// СПЕЦИАЛЬНЫЕ НАЧИСЛЕНИЯ
// ==========================================
.special-section {
    padding: 0 16px 16px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;

    i {
        color: $admin-gold;
        font-size: 1.1rem;
    }

    h3 {
        font-size: 1rem;
        font-weight: 600;
        color: $admin-text;
        margin: 0;
    }
}

.special-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.special-item {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 10px;
    padding: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.special-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.special-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba($admin-gold, 0.1);
    color: $admin-gold;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.special-title {
    font-size: 0.9rem;
    font-weight: 500;
    color: $admin-text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.special-amount {
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-success;
    flex-shrink: 0;
}

// ==========================================
// ИСТОРИЯ ОПЕРАЦИЙ
// ==========================================
.history-section {
    padding: 0 16px 16px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $admin-text-muted;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: $admin-bg;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 16px;
    }

    h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: $admin-text;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .page-header {
        padding: 24px;
    }

    .header-title {
        font-size: 1.25rem;
    }

    .balance-section,
    .special-section,
    .history-section {
        max-width: 700px;
        margin: 0 auto;
        padding: 16px 24px;
    }

    .balance-amount {
        font-size: 3rem;
    }
}
</style>
