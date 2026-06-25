<template>
    <div class="cashback-page pb-5 pt-3" v-if="self">

        <!-- ===== HERO: ГЛАВНЫЙ БАЛАНС ===== -->
        <div class="balance-hero">
            <div class="balance-background-pattern"></div>
            <div class="balance-content">
                <div class="balance-label">
                    <i class="fa-solid fa-wallet me-2"></i>
                    Ваш баланс CashBack
                </div>
                <div class="balance-amount">
                    {{ formatCurrency(self.cashback_balance || 0) }}
                </div>
                <div class="balance-hint">
                    Баллы можно использовать для оплаты следующих заказов
                </div>

                <!-- Кнопка действия (опционально, если есть механика списания) -->
                <button class="btn-redeem" @click="goToCatalog">
                    <i class="fa-solid fa-bag-shopping me-2"></i>
                    Потратить баллы
                </button>
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
                        v-for="(sub, index) in self.cashback_subs"
                        :key="index"
                        class="sub-card"
                    >
                        <div class="sub-info">
                            <div class="sub-title">{{ sub.sub_title || 'Бонус' }}</div>
                            <div class="sub-desc">Дополнительный кэшбэк</div>
                        </div>
                        <div class="sub-amount">
                            +{{ formatCurrency(sub.total || 0) }}
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

                <!-- Обертка для твоего компонента списка -->
                <div class="history-wrapper">
                    <CashBackList />
                </div>
            </div>

        </div>
    </div>

    <!-- Состояние загрузки или отсутствия данных -->
    <div v-else class="loading-state">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Загрузка...</span>
        </div>
    </div>
</template>

<script>
import CashBackList from '@/MobileClient/Components/Shop/CashBack/CashBackList.vue';

export default {
    name: "CashBackPage",

    components: {
        CashBackList
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        hasSpecialSubs() {
            return this.self?.cashback_subs && this.self.cashback_subs.length > 0;
        }
    },

    methods: {
        // Красивое форматирование валюты (например: "1 250 ₽")
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
        }
    }
};
</script>

<style scoped>
.cashback-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO: ГЛАВНЫЙ БАЛАНС
   ========================================== */
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

.btn-redeem {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 32px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-redeem:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
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

/* ==========================================
   КАРТОЧКИ СПЕЦИАЛЬНЫХ НАЧИСЛЕНИЙ
   ========================================== */
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
    color: #198754; /* Зеленый для плюса */
    white-space: nowrap;
    margin-left: 12px;
}

/* ==========================================
   ИСТОРИЯ
   ========================================== */
.history-wrapper {
    background: var(--bs-body-bg);
    border-radius: 16px;
    /* Если CashBackList внутри имеет свои стили, они сохранятся,
       но мы добавляем легкую обводку для целостности */
    border: 1px solid var(--bs-border-color);
    overflow: hidden;
}

/* ==========================================
   АДАПТИВ И ЗАГРУЗКА
   ========================================== */
.loading-state {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bs-body-bg);
}

@media (max-width: 576px) {
    .balance-amount {
        font-size: 2.8rem;
    }

    .balance-hero {
        padding: 24px 20px;
    }
}
</style>
