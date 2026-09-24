<template>
    <div class="container-xxl py-3">
        <div class="row g-4">

            <!-- ЛЕВАЯ КОЛОНКА: Основная информация -->
            <div class="col-lg-4">
                <div class="agent-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="position-relative d-inline-block mb-3">
                            <div class="avatar-circle-large">
                                <img v-if="user.avatar" v-lazy="user.avatar" alt="Avatar">
                                <i v-else class="fa-solid fa-user text-white"></i>
                            </div>
                            <span class="avatar-badge" title="Агентский профиль">
                                <i class="fa-solid fa-briefcase"></i>
                            </span>
                        </div>

                        <h4 class="fw-bold mb-1 text-dark">{{ user.name || 'Агент' }}</h4>
                        <p class="text-muted mb-3">{{ user.phone || 'Телефон не указан' }}</p>

                        <div class="agent-status-badge mb-4">
                            <i class="fa-solid fa-shield-halved me-1"></i>
                            {{ agentData.status === 'verified' ? 'Верифицирован' : 'На проверке' }}
                        </div>

                        <button class="btn btn-primary w-100 mb-2 agent-btn" @click="$router.push({name: 'AgentDashboard'})">
                            <i class="fa-solid fa-chart-line me-2"></i> Панель агента
                        </button>
                        <button class="btn btn-outline-secondary w-100 agent-btn" @click="$emit('edit')">
                            <i class="fa-solid fa-pen me-2"></i> Редактировать профиль
                        </button>
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ КОЛОНКА: Статистика и Рефералка -->
            <div class="col-lg-8">
                <!-- Статистика -->
                <div class="agent-card mb-4">
                    <div class="card-header-custom">
                        <h6 class="fw-bold mb-0"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Агентская статистика</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="stat-box">
                                    <div class="stat-icon bg-success-subtle text-success mb-2"><i class="fa-solid fa-wallet"></i></div>
                                    <div class="stat-value fw-bold text-dark">{{ formatPrice(agentData.balance) }}</div>
                                    <div class="stat-label small text-muted">Баланс</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-box">
                                    <div class="stat-icon bg-primary-subtle text-primary mb-2"><i class="fa-solid fa-sack-dollar"></i></div>
                                    <div class="stat-value fw-bold text-dark">{{ formatPrice(agentData.total_earned) }}</div>
                                    <div class="stat-label small text-muted">Всего заработано</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-box">
                                    <div class="stat-icon bg-info-subtle text-info mb-2"><i class="fa-solid fa-robot"></i></div>
                                    <div class="stat-value fw-bold text-dark">{{ agentData.tenant_count }}</div>
                                    <div class="stat-label small text-muted">Приложений</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-box">
                                    <div class="stat-icon bg-warning-subtle text-warning mb-2"><i class="fa-solid fa-users"></i></div>
                                    <div class="stat-value fw-bold text-dark">{{ agentData.clients_count }}</div>
                                    <div class="stat-label small text-muted">Клиентов</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Реферальная программа -->
                <div class="agent-card">
                    <div class="card-header-custom">
                        <h6 class="fw-bold mb-0"><i class="fa-solid fa-share-nodes text-success me-2"></i>Реферальная программа</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="input-group referral-input-group mb-3">
                            <span class="input-group-text"><i class="fa-solid fa-link text-muted"></i></span>
                            <input type="text" class="form-control" :value="agentData.referral_url || 'Генерация ссылки...'" readonly>
                            <button class="btn btn-outline-primary" type="button" @click="copyReferralLink">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-around text-center pt-2 border-top">
                            <div class="px-3">
                                <div class="fw-bold text-primary fs-4">{{ agentData.referrals_count || 0 }}</div>
                                <div class="small text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Переходов</div>
                            </div>
                            <div class="px-3 border-start">
                                <div class="fw-bold text-success fs-4">{{ formatPrice(agentData.referral_bonus || 0) }}</div>
                                <div class="small text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Бонусов</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AgentProfileCard",
    props: {
        user: { type: Object, required: true }
    },
    emits: ['edit'],
    computed: {
        agentData() {
            return this.user.agent_profile || {};
        }
    },
    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price || 0);
        },
        async copyReferralLink() {
            if (!this.agentData.referral_url) return;
            try {
                await navigator.clipboard.writeText(this.agentData.referral_url);
                this.$notify?.({ title: "Успех", text: "Ссылка скопирована", type: "success" });
            } catch (err) {
                console.error('Ошибка копирования', err);
            }
        }
    }
}
</script>

<style scoped>
/* ==========================================
   ОСНОВНЫЕ КАРТОЧКИ
   ========================================== */
.agent-card {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    transition: box-shadow 0.3s ease;
    overflow: hidden;
}

.agent-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}

.card-header-custom {
    padding: 20px 24px 0;
    background: transparent;
    border-bottom: none;
}

/* ==========================================
   АВАТАР (ИСПРАВЛЕНО: теперь идеально круглый)
   ========================================== */
.avatar-circle-large {
    width: 110px;
    height: 110px;
    border-radius: 50%; /* Ключевое свойство для круга */
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    border: 4px solid #ffffff;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);
    overflow: hidden; /* Обрезает всё, что выходит за границы круга */
    position: relative;
}

.avatar-circle-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%; /* Дополнительная страховка для изображения */
}

.avatar-badge {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 32px;
    height: 32px;
    background: #10b981;
    border: 3px solid #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
}

/* ==========================================
   СТАТУС И КНОПКИ
   ========================================== */
.agent-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 18px;
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
}

.agent-btn {
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.agent-btn:hover {
    transform: translateY(-1px);
}

/* ==========================================
   СТАТИСТИКА
   ========================================== */
.stat-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    transition: all 0.25s ease;
    height: 100%;
}

.stat-box:hover {
    transform: translateY(-4px);
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.04);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 1.25rem;
}

/* Явные фоны для иконок, чтобы не зависеть от версий Bootstrap */
.bg-success-subtle { background: rgba(16, 185, 129, 0.1) !important; }
.bg-primary-subtle { background: rgba(59, 130, 246, 0.1) !important; }
.bg-info-subtle { background: rgba(14, 165, 233, 0.1) !important; }
.bg-warning-subtle { background: rgba(245, 158, 11, 0.1) !important; }

.stat-value {
    font-size: 1.15rem;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 500;
}

/* ==========================================
   РЕФЕРАЛЬНЫЙ БЛОК
   ========================================== */
.referral-input-group .input-group-text {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-right: none;
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
}

.referral-input-group .form-control {
    border: 1px solid #e2e8f0;
    border-left: none;
    border-right: none;
    font-size: 0.9rem;
    color: #475569;
    background: #ffffff;
}

.referral-input-group .form-control:focus {
    box-shadow: none;
    border-color: #3b82f6;
}

.referral-input-group .btn {
    border: 1px solid #e2e8f0;
    border-left: none;
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
    background: #ffffff;
    color: #3b82f6;
}

.referral-input-group .btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
</style>
