<template>
    <div class="tab-content fade-in">
        <div class="welcome-card">
            <div class="welcome-info">
                <h2>Добро пожаловать, {{ agent.name?.split(' ')[0] || 'партнёр' }}!</h2>
                <p>Ваш прогресс за этот месяц: <strong>+32 400 ₽</strong> и <strong>+3 приложения</strong></p>
            </div>
            <div class="welcome-actions">
                <button class="btn-primary-modern" @click="$emit('create-tenant')"><i class="fa-solid fa-plus"></i> Создать
                    приложение
                </button>
                <button class="btn-secondary-modern" @click="$emit('create-invoice')"><i
                    class="fa-solid fa-file-invoice"></i> Выставить счёт
                </button>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h3>Последние действия</h3>
                <button class="btn-text" @click="$emit('open-all-transactions')">
                    Все операции <i class="fa-solid fa-arrow-right" style="font-size: 0.8em;"></i>
                </button>
            </div>
            <div class="activity-list">
                <div v-for="activity in activities" :key="activity.id" class="activity-item">
                    <div class="activity-icon" :class="'type-' + activity.type">
                        <i :class="activity.icon"></i>
                    </div>
                    <div class="activity-info">
                        <div class="activity-title">{{ activity.title }}</div>
                        <div class="activity-date">{{ activity.date }}</div>
                    </div>
                    <div class="activity-amount" :class="activity.amountClass">{{ activity.amount }}</div>
                </div>
            </div>
        </div>

        <div v-if="notifications.length > 0" class="section-card notifications">
            <div class="section-header"><h3><i class="fa-solid fa-bell"></i> Уведомления</h3></div>
            <div class="notification-list">
                <div v-for="notif in notifications" :key="notif.id" class="notification-item"
                     :class="'type-' + notif.type">
                    <i :class="notif.icon"></i>
                    <span>{{ notif.text }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AgentOverview",
    props: {
        agent: Object,
        activities: Array,
        notifications: Array,
    },
    emits: ['create-tenant', 'create-invoice', 'switch-tab', 'open-all-transactions']
};
</script>

<style lang="scss" scoped>
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

.fade-in {
    animation: fadeIn 0.3s ease;
}

.welcome-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(96, 165, 250, 0.08) 100%);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.welcome-info h2 {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 6px 0;
    color: #1f2937;
}

.welcome-info p {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

.welcome-actions {
    display: flex;
    gap: 10px;
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
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-text {
    background: none;
    border: none;
    color: #3b82f6;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
}

.btn-text:hover {
    background: rgba(59, 130, 246, 0.1);
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px;
    border-radius: 10px;
    transition: background 0.2s;
}

.activity-item:hover {
    background: #f9fafb;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.activity-icon.type-income {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.activity-icon.type-payout {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.activity-info {
    flex: 1;
    min-width: 0;
}

.activity-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1f2937;
    margin-bottom: 2px;
}

.activity-date {
    font-size: 0.8rem;
    color: #6b7280;
}

.activity-amount {
    font-weight: 700;
    font-size: 0.95rem;
}

.activity-amount.income {
    color: #10b981;
}

.activity-amount.expense {
    color: #ef4444;
}

.notifications {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.02) 100%);
    border-color: rgba(245, 158, 11, 0.2);
}

.notification-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.notification-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: white;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #1f2937;
}

.notification-item.type-success {
    border-left: 3px solid #10b981;

    i {
        color: #10b981;
    }
}

.notification-item.type-info {
    border-left: 3px solid #3b82f6;

    i {
        color: #3b82f6;
    }
}

.btn-primary-modern {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    background: #3b82f6;
    color: white;
    cursor: pointer;
    transition: 0.2s;
}

.btn-primary-modern:hover {
    background: #2563eb;
}

.btn-secondary-modern {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    color: #1f2937;
    cursor: pointer;
    transition: 0.2s;
}

.btn-secondary-modern:hover {
    background: #f3f4f6;
    border-color: #3b82f6;
    color: #3b82f6;
}
</style>
