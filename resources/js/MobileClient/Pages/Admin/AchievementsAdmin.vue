<template>
    <div class="achievements-admin-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="page-hero">
            <div class="hero-bg">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <h1 class="hero-title">Управление достижениями</h1>
                <p class="hero-subtitle">
                    Мотивируйте клиентов выполнять целевые действия и получайте лояльность
                </p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СТАТИСТИКА -->
        <!-- ========================================== -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.total }}</div>
                        <div class="stat-label">Всего создано</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.active }}</div>
                        <div class="stat-label">Активных</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.total_unlocked }}</div>
                        <div class="stat-label">Всего разблокировано</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.total_rewards_given }}</div>
                        <div class="stat-label">Наград выдано</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ДОСТИЖЕНИЙ -->
        <!-- ========================================== -->
        <div class="list-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-list"></i>
                    Список достижений
                </h2>
                <button class="add-btn" @click="openModal()">
                    <i class="fa-solid fa-plus"></i>
                    <span>Добавить достижение</span>
                </button>
            </div>

            <div v-if="isLoading" class="loading-state">
                <div class="loader-spinner"></div>
                <p>Загрузка достижений...</p>
            </div>

            <div v-else-if="achievements.length === 0" class="empty-state">
                <i class="fa-solid fa-trophy"></i>
                <h3>Достижений пока нет</h3>
                <p>Создайте первое достижение, чтобы начать мотивировать клиентов</p>
            </div>

            <div v-else class="achievements-grid">
                <div v-for="item in achievements" :key="item.id" class="achievement-card" :class="{ 'is-inactive': !item.is_active }">
                    <div class="card-header">
                        <div class="achievement-icon" :style="{ background: item.is_active ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' : '#e5e7eb' }">
                            <i :class="item.icon || 'fa-solid fa-trophy'"></i>
                        </div>
                        <div class="achievement-info">
                            <h4 class="achievement-title">{{ item.title }}</h4>
                            <p class="achievement-desc">{{ item.description }}</p>
                        </div>
                        <div class="card-actions">
                            <button class="icon-btn edit" @click="openModal(item)" title="Редактировать">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="icon-btn delete" @click="deleteAchievement(item.id)" title="Удалить">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="condition-badge">
                            <span class="label">Условие:</span>
                            <span class="value">{{ formatCondition(item.condition_type, item.condition_value) }}</span>
                        </div>
                        <div class="reward-badge">
                            <span class="label">Награда:</span>
                            <span class="value">{{ formatReward(item.reward_type, item.reward_value) }}</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="toggle-wrapper">
                            <span class="toggle-label">{{ item.is_active ? 'Активно' : 'Неактивно' }}</span>
                            <label class="switch-control">
                                <input type="checkbox" v-model="item.is_active" @change="toggleActive(item)">
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                        <div class="sort-info">
                            <i class="fa-solid fa-arrow-down-short-wide"></i>
                            Позиция: {{ item.sort_order }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: СОЗДАНИЕ / РЕДАКТИРОВАНИЕ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <button class="modal-close" @click="closeModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fa-solid fa-trophy"></i>
                            </div>
                            <div>
                                <h3 class="modal-title">{{ isEditing ? 'Редактировать достижение' : 'Новое достижение' }}</h3>
                                <p class="modal-subtitle">Заполните параметры условия и награды</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body">
                        <form @submit.prevent="saveAchievement" class="achievement-form">
                            <div class="form-grid">
                                <div class="form-field full-width">
                                    <label>Название достижения</label>
                                    <input type="text" v-model="form.title" required placeholder="Например: Первый заказ">
                                </div>
                                <div class="form-field full-width">
                                    <label>Описание</label>
                                    <textarea v-model="form.description" rows="2" placeholder="Сделайте свой первый заказ в нашем магазине"></textarea>
                                </div>
                                <div class="form-field">
                                    <label>Иконка (FontAwesome класс)</label>
                                    <input type="text" v-model="form.icon" placeholder="fa-solid fa-coffee">
                                </div>
                                <div class="form-field">
                                    <label>Порядок сортировки</label>
                                    <input type="number" v-model="form.sort_order" min="0">
                                </div>

                                <div class="form-field">
                                    <label>Тип условия</label>
                                    <select v-model="form.condition_type" required>
                                        <option v-for="(label, key) in conditionTypes" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Значение условия</label>
                                    <input type="number" v-model="form.condition_value" required min="1" placeholder="Например: 1">
                                </div>

                                <div class="form-field">
                                    <label>Тип награды</label>
                                    <select v-model="form.reward_type" required>
                                        <option v-for="(label, key) in rewardTypes" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Значение награды</label>
                                    <input type="number" v-model="form.reward_value" required min="0" placeholder="Например: 100">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn-cancel" @click="closeModal">Отмена</button>
                                <button type="submit" class="btn-save" :disabled="isSaving">
                                    <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                                    <span>{{ isSaving ? 'Сохранение...' : 'Сохранить' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>
export default {
    name: 'AchievementsAdmin',

    data() {
        return {
            isLoading: false,
            isSaving: false,
            showModal: false,
            isEditing: false,

            stats: {
                total: 0,
                active: 0,
                total_unlocked: 0,
                total_rewards_given: 0
            },

            achievements: [],

            // Заполняется с бэкенда для соответствия модели
            conditionTypes: {},
            rewardTypes: {},

            form: {
                id: null,
                title: '',
                description: '',
                icon: 'fa-solid fa-trophy',
                condition_type: 'orders_count',
                condition_value: 1,
                reward_type: 'cashback',
                reward_value: 100,
                sort_order: 0,
                is_active: true
            }
        }
    },

    async mounted() {
        await this.loadData();
    },

    methods: {
        async loadData() {
            this.isLoading = true;
            try {
                const response = await axios.get('/admin/achievements/data');
                this.achievements = response.data.achievements;
                this.stats = response.data.stats;
                this.conditionTypes = response.data.condition_types;
                this.rewardTypes = response.data.reward_types;
            } catch (error) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить данные', type: 'error' });
            } finally {
                this.isLoading = false;
            }
        },

        openModal(achievement = null) {
            this.isEditing = !!achievement;
            if (achievement) {
                this.form = { ...achievement };
            } else {
                this.form = {
                    id: null,
                    title: '',
                    description: '',
                    icon: 'fa-solid fa-trophy',
                    condition_type: 'orders_count',
                    condition_value: 1,
                    reward_type: 'cashback',
                    reward_value: 100,
                    sort_order: this.achievements.length,
                    is_active: true
                };
            }
            this.showModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.showModal = false;
            document.body.style.overflow = '';
        },

        async saveAchievement() {
            this.isSaving = true;
            try {
                const url = this.isEditing ? `/admin/achievements/${this.form.id}` : '/admin/achievements';
                const method = this.isEditing ? 'put' : 'post';

                await axios[method](url, this.form);

                this.$notify?.({
                    title: 'Успех',
                    text: this.isEditing ? 'Достижение обновлено' : 'Достижение создано',
                    type: 'success'
                });

                this.closeModal();
                await this.loadData();
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось сохранить',
                    type: 'error'
                });
            } finally {
                this.isSaving = false;
            }
        },

        async toggleActive(achievement) {
            try {
                await axios.post(`/admin/achievements/${achievement.id}/toggle`, {
                    is_active: achievement.is_active
                });
                this.$notify?.({ title: 'Успех', text: 'Статус изменён', type: 'success' });
                await this.loadData(); // Перезагружаем для обновления статистики
            } catch (error) {
                achievement.is_active = !achievement.is_active; // Откат
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось изменить статус', type: 'error' });
            }
        },

        async deleteAchievement(id) {
            if (!confirm('Вы уверены, что хотите удалить это достижение? Это действие нельзя отменить.')) return;

            try {
                await axios.delete(`/admin/achievements/${id}`);
                this.$notify?.({ title: 'Успех', text: 'Достижение удалено', type: 'success' });
                await this.loadData();
            } catch (error) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось удалить', type: 'error' });
            }
        },

        formatCondition(type, value) {
            const typeName = this.conditionTypes[type] || type;
            return `${typeName}: ${value}`;
        },

        formatReward(type, value) {
            if (type === 'none') return 'Без награды';
            const typeName = this.rewardTypes[type] || type;
            return `${value} ${typeName}`;
        }
    }
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
$admin-danger: #ef4444;

.achievements-admin-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// HERO СЕКЦИЯ (копия из референса)
// ==========================================
.page-hero {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px 50px;
    color: white;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;
    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: 0.3;
        &.blob-1 {
            width: 300px; height: 300px;
            background: rgba(255, 255, 255, 0.3);
            top: -100px; right: -50px;
            animation: float 20s ease-in-out infinite;
        }
        &.blob-2 {
            width: 250px; height: 250px;
            background: rgba(255, 255, 255, 0.2);
            bottom: -80px; left: -30px;
            animation: float 25s ease-in-out infinite reverse;
        }
    }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(20px, -20px) scale(1.1); }
}

.hero-content {
    position: relative; z-index: 1; max-width: 900px; margin: 0 auto; text-align: center;
}

.hero-icon {
    width: 72px; height: 72px; margin: 0 auto 16px;
    background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 20px;
    display: flex; align-items: center; justify-content: center; font-size: 2rem;
}

.hero-title { font-size: 2rem; font-weight: 800; margin: 0 0 8px; }
.hero-subtitle { font-size: 1rem; opacity: 0.9; margin: 0; }

// ==========================================
// СТАТИСТИКА
// ==========================================
.stats-section { padding: 10px; }
.stats-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 6px;
}
.stat-card {
    display: flex; align-items: center; gap: 12px; padding: 16px;
    background: #ffffff; border: 1px solid #e9ecef; border-radius: 14px;
    transition: all 0.2s;
    &:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06); }
}
.stat-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1.3rem; flex-shrink: 0;
}
.stat-info { flex: 1; min-width: 0; }
.stat-value { font-size: 1.4rem; font-weight: 800; color: #2c3e50; line-height: 1.2; }
.stat-label { font-size: 0.75rem; color: #6c757d; margin-top: 2px; line-height: 100%; }

// ==========================================
// СПИСОК
// ==========================================
.list-section { padding: 0 16px 20px; max-width: 900px; margin: 0 auto; }
.section-header {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;
}
.section-title {
    display: flex; align-items: center; gap: 10px; margin: 0;
    font-size: 1.2rem; font-weight: 700; color: $admin-text;
    i { color: $admin-primary; }
}

.add-btn {
    display: flex; align-items: center; gap: 8px; padding: 10px 20px;
    background: $admin-primary; color: white; border: none; border-radius: 10px;
    font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
    &:hover { background: darken($admin-primary, 10%); transform: translateY(-1px); }
}

.achievements-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;
}

.achievement-card {
    background: $admin-card-bg; border: 1px solid $admin-border;
    border-radius: 14px; padding: 16px; transition: all 0.2s;
    &.is-inactive { opacity: 0.6; background: #f8f9fa; }
    &:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06); }
}

.card-header { display: flex; gap: 12px; margin-bottom: 12px; }
.achievement-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1.2rem; flex-shrink: 0;
}
.achievement-info { flex: 1; min-width: 0; }
.achievement-title { margin: 0 0 4px; font-size: 1rem; font-weight: 700; color: $admin-text; }
.achievement-desc { margin: 0; font-size: 0.8rem; color: $admin-text-muted; line-height: 1.3; }

.card-actions { display: flex; gap: 8px; }
.icon-btn {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid $admin-border;
    background: white; color: $admin-text-muted; cursor: pointer;
    display: flex; align-items: center; justify-content: center; transition: all 0.2s;
    &.edit:hover { background: $admin-primary; color: white; border-color: $admin-primary; }
    &.delete:hover { background: $admin-danger; color: white; border-color: $admin-danger; }
}

.card-body {
    display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px;
    padding: 12px; background: $admin-bg; border-radius: 10px;
}
.condition-badge, .reward-badge {
    display: flex; justify-content: space-between; font-size: 0.85rem;
    .label { color: $admin-text-muted; font-weight: 500; }
    .value { color: $admin-text; font-weight: 700; }
}

.card-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 12px; border-top: 1px solid $admin-border;
}
.toggle-wrapper { display: flex; align-items: center; gap: 10px; }
.toggle-label { font-size: 0.8rem; font-weight: 600; color: $admin-text-muted; }
.sort-info { font-size: 0.75rem; color: $admin-text-muted; display: flex; align-items: center; gap: 4px; }

// ==========================================
// SWITCH
// ==========================================
.switch-control {
    position: relative; width: 44px; height: 24px; flex-shrink: 0;
    input { opacity: 0; width: 0; height: 0; }
    input:checked + .switch-slider { background: $admin-success; }
    input:checked + .switch-slider::before { transform: translateX(20px); }
}
.switch-slider {
    position: absolute; cursor: pointer; inset: 0;
    background: $admin-border; transition: 0.3s; border-radius: 24px;
    &::before {
        position: absolute; content: ''; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background: white; transition: 0.3s;
        border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
}

// ==========================================
// МОДАЛКА И ФОРМА
// ==========================================
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px); z-index: 9999;
    display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-container {
    background: $admin-card-bg; width: 100%; max-width: 600px; max-height: 90vh;
    border-radius: 20px; overflow: hidden; display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.modal-header {
    padding: 20px; border-bottom: 1px solid $admin-border; position: relative;
}
.modal-close {
    position: absolute; top: 16px; right: 16px; width: 36px; height: 36px;
    border-radius: 50%; background: $admin-bg; border: none; color: $admin-text;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    &:hover { background: $admin-danger; color: white; }
}
.modal-header-content { display: flex; align-items: center; gap: 14px; }
.modal-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, $admin-warning 0%, #d97706 100%);
    color: white; display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.modal-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 2px; color: $admin-text; }
.modal-subtitle { font-size: 0.85rem; color: $admin-text-muted; margin: 0; }
.modal-body { padding: 20px; overflow-y: auto; flex: 1; }

.achievement-form { display: flex; flex-direction: column; gap: 16px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-field.full-width { grid-column: 1 / -1; }
.form-field label { font-size: 0.85rem; font-weight: 600; color: $admin-text; }
.form-field input, .form-field select, .form-field textarea {
    padding: 10px 14px; background: $admin-bg; border: 1px solid $admin-border;
    border-radius: 10px; font-size: 0.9rem; color: $admin-text;
    &:focus { outline: none; border-color: $admin-primary; box-shadow: 0 0 0 3px rgba($admin-primary, 0.1); }
}

.form-actions {
    display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px; padding-top: 16px; border-top: 1px solid $admin-border;
}
.btn-cancel {
    padding: 10px 20px; background: transparent; border: 1px solid $admin-border;
    border-radius: 10px; color: $admin-text; font-weight: 600; cursor: pointer;
    &:hover { background: $admin-bg; }
}
.btn-save {
    padding: 10px 24px; background: $admin-primary; border: none; border-radius: 10px;
    color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;
    &:hover:not(:disabled) { background: darken($admin-primary, 10%); }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ И ЗАГРУЗКА
// ==========================================
.loading-state, .empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 60px 20px; background: $admin-card-bg; border: 1px solid $admin-border;
    border-radius: 14px; color: $admin-text-muted; text-align: center;
    .loader-spinner {
        width: 40px; height: 40px; border: 3px solid $admin-border;
        border-top-color: $admin-primary; border-radius: 50%;
        animation: spin 0.8s linear infinite; margin-bottom: 16px;
    }
    i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
    h3 { margin: 0 0 8px; color: $admin-text; }
    p { margin: 0; font-size: 0.9rem; }
}
@keyframes spin { to { transform: rotate(360deg); } }

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .form-grid { grid-template-columns: 1fr; }
    .achievements-grid { grid-template-columns: 1fr; }
    .modal-container { max-width: 100%; max-height: 100vh; border-radius: 0; }
}
</style>
