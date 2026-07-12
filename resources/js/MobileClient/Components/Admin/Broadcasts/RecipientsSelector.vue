<template>
    <div class="recipients-selector">

        <!-- Типы получателей -->
        <div class="recipient-types">
            <label
                v-for="type in recipientTypes"
                :key="type.key"
                class="recipient-option"
                :class="{ 'active': localType === type.key }"
            >
                <input
                    type="radio"
                    :value="type.key"
                    v-model="localType"
                    @change="emitTypeUpdate"
                >
                <div class="option-content">
                    <div class="option-icon" :style="{ background: type.gradient }">
                        <i :class="type.icon"></i>
                    </div>
                    <div class="option-info">
                        <h4>{{ type.label }}</h4>
                        <p>{{ type.description }}</p>
                    </div>
                    <div class="option-count" v-if="type.count !== undefined">
                        <span class="count-value">{{ type.count }}</span>
                        <span class="count-label">чел.</span>
                    </div>
                </div>
            </label>
        </div>

        <!-- Дополнительные настройки -->
        <transition name="fade">
            <div v-if="localType === 'segment'" class="segment-settings">
                <div class="settings-header">
                    <i class="fa-solid fa-sliders"></i>
                    <h4>Фильтры сегмента</h4>
                </div>

                <div class="settings-grid">
                    <div class="setting-field">
                        <label>
                            <i class="fa-solid fa-cart-shopping"></i>
                            Минимум заказов
                        </label>
                        <input
                            type="number"
                            v-model.number="localFilters.min_orders"
                            min="0"
                            placeholder="0"
                            @input="emitFiltersUpdate"
                        >
                    </div>

                    <div class="setting-field">
                        <label>
                            <i class="fa-solid fa-ruble-sign"></i>
                            Минимальная сумма покупок
                        </label>
                        <input
                            type="number"
                            v-model.number="localFilters.min_total_spent"
                            min="0"
                            placeholder="0"
                            @input="emitFiltersUpdate"
                        >
                    </div>

                    <div class="setting-field">
                        <label>
                            <i class="fa-solid fa-calendar"></i>
                            Последний заказ (дней назад, макс.)
                        </label>
                        <input
                            type="number"
                            v-model.number="localFilters.last_order_days"
                            min="0"
                            placeholder="Неограничено"
                            @input="emitFiltersUpdate"
                        >
                    </div>

                    <div class="setting-field">
                        <label>
                            <i class="fa-solid fa-star"></i>
                            Минимальный рейтинг
                        </label>
                        <input
                            type="number"
                            v-model.number="localFilters.min_rating"
                            min="0"
                            max="5"
                            step="0.1"
                            placeholder="0"
                            @input="emitFiltersUpdate"
                        >
                    </div>
                </div>

                <!-- Превью сегмента -->
                <div class="segment-preview">
                    <i class="fa-solid fa-users"></i>
                    <span>Примерно <strong>{{ estimatedCount }}</strong> пользователей подпадают под фильтры</span>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div v-if="localType === 'custom'" class="custom-settings">
                <div class="settings-header">
                    <i class="fa-solid fa-user-check"></i>
                    <h4>Выбор конкретных пользователей</h4>
                </div>

                <div class="search-users">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Поиск по имени или телефону..."
                        >
                    </div>

                    <div class="users-list" v-if="filteredUsers.length > 0">
                        <label
                            v-for="user in filteredUsers"
                            :key="user.id"
                            class="user-option"
                            :class="{ 'selected': isSelected(user.id) }"
                        >
                            <input
                                type="checkbox"
                                :checked="isSelected(user.id)"
                                @change="toggleUser(user.id)"
                            >
                            <div class="user-avatar">
                                <img v-if="user.avatar" :src="user.avatar" :alt="user.name">
                                <span v-else>{{ getInitials(user.name) }}</span>
                            </div>
                            <div class="user-info">
                                <div class="user-name">{{ user.name }}</div>
                                <div class="user-phone">{{ user.phone }}</div>
                            </div>
                            <div class="user-check" v-if="isSelected(user.id)">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </label>
                    </div>

                    <div class="selected-summary" v-if="localFilters.user_ids?.length > 0">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Выбрано: <strong>{{ localFilters.user_ids.length }}</strong> пользователей</span>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'RecipientsSelector',

    props: {
        modelValue: {
            type: String,
            default: 'all',
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },

    emits: ['update:modelValue', 'update:filters'],

    data() {
        return {
            loadingUsers: false,
            localType: this.modelValue,
            localFilters: { ...(this.filters || {}) },
            searchQuery: '',
            allUsers: [],
            recipientTypes: [
                {
                    key: 'all',
                    label: 'Все пользователи',
                    description: 'Отправить всем зарегистрированным пользователям',
                    icon: 'fa-solid fa-users',
                    gradient: 'linear-gradient(135deg, #667eea, #5a67d8)',
                    count: null,
                },
                {
                    key: 'active',
                    label: 'Активные',
                    description: 'Только пользователи с активным статусом',
                    icon: 'fa-solid fa-user-check',
                    gradient: 'linear-gradient(135deg, #10b981, #059669)',
                    count: null,
                },
                {
                    key: 'vip',
                    label: 'VIP клиенты',
                    description: 'Пользователи с VIP статусом',
                    icon: 'fa-solid fa-crown',
                    gradient: 'linear-gradient(135deg, #fbbf24, #f59e0b)',
                    count: null,
                },
                {
                    key: 'segment',
                    label: 'Сегмент',
                    description: 'Гибкая фильтрация по параметрам',
                    icon: 'fa-solid fa-filter',
                    gradient: 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
                    count: null,
                },
                {
                    key: 'custom',
                    label: 'Выбрать вручную',
                    description: 'Укажите конкретных пользователей',
                    icon: 'fa-solid fa-user-plus',
                    gradient: 'linear-gradient(135deg, #06b6d4, #0891b2)',
                    count: null,
                },
            ],
        };
    },

    computed: {
        filteredUsers() {
            if (!this.searchQuery) {
                return this.allUsers.slice(0, 50);
            }

            const query = this.searchQuery.toLowerCase();
            return this.allUsers
                .filter(u =>
                    u.name?.toLowerCase().includes(query) ||
                    u.phone?.toLowerCase().includes(query)
                )
                .slice(0, 50);
        },

        estimatedCount() {
            // Примерная оценка на основе фильтров
            let count = this.recipientTypes.find(t => t.key === 'all')?.count || 0;

            if (this.localFilters.min_orders > 0) {
                count = Math.floor(count * 0.6);
            }
            if (this.localFilters.min_total_spent > 0) {
                count = Math.floor(count * 0.4);
            }

            return count || 0;
        },
    },

    watch: {
        modelValue(newVal) {
            this.localType = newVal;
        },
        filters: {
            handler(newVal) {
                this.localFilters = { ...(newVal || {}) };
            },
            deep: true,
        },
    },

    mounted() {
        this.loadCounts();
        if (this.localType === 'custom') {
            this.loadUsers();
        }
    },

    methods: {
        async loadCounts() {
            try {
                const response = await axios.get('/admin/broadcasts/recipients-count');
                const data = response.data.data;

                this.recipientTypes.forEach(type => {
                    if (data[type.key] !== undefined) {
                        type.count = data[type.key];
                    }
                });
            } catch (error) {
                console.error('[RecipientsSelector] Ошибка загрузки счётчиков:', error);
                // Fallback: устанавливаем нулевые значения
                this.recipientTypes.forEach(type => {
                    type.count = 0;
                });
            }
        },

        async loadUsers() {
            this.loadingUsers = true;

            try {
                const response = await axios.get('/admin/broadcasts/users', {
                    params: {
                        active_only: true,
                    },
                });

                this.allUsers = response.data.data || [];
            } catch (error) {
                console.error('[RecipientsSelector] Ошибка загрузки пользователей:', error);
                this.allUsers = [];

                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить список пользователей',
                    type: 'error',
                });
            } finally {
                this.loadingUsers = false;
            }
        },

        emitTypeUpdate() {
            this.$emit('update:modelValue', this.localType);

            if (this.localType === 'custom' && this.allUsers.length === 0) {
                this.loadUsers();
            }
        },

        emitFiltersUpdate() {
            this.$emit('update:filters', { ...this.localFilters });
        },

        isSelected(userId) {
            return (this.localFilters.user_ids || []).includes(userId);
        },

        toggleUser(userId) {
            if (!this.localFilters.user_ids) {
                this.localFilters.user_ids = [];
            }

            const index = this.localFilters.user_ids.indexOf(userId);
            if (index === -1) {
                this.localFilters.user_ids.push(userId);
            } else {
                this.localFilters.user_ids.splice(index, 1);
            }

            this.emitFiltersUpdate();
        },

        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$purple: #8b5cf6;
$cyan: #06b6d4;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.recipients-selector {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

// ==========================================
// ТИПЫ ПОЛУЧАТЕЛЕЙ
// ==========================================
.recipient-types {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.recipient-option {
    display: block;
    cursor: pointer;
    user-select: none;

    input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .option-content {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        background: $bg-secondary;
        border: 2px solid $border;
        border-radius: 12px;
        transition: all 0.2s;

        &:hover {
            border-color: rgba($primary, 0.3);
        }
    }

    input:checked ~ .option-content {
        border-color: $primary;
        background: rgba($primary, 0.05);
        box-shadow: 0 2px 8px rgba($primary, 0.1);
    }
}

.option-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.option-info {
    flex: 1;
    min-width: 0;

    h4 {
        margin: 0 0 2px;
        font-size: 0.95rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.8rem;
        color: $text-muted;
    }
}

.option-count {
    text-align: right;
    flex-shrink: 0;

    .count-value {
        display: block;
        font-size: 1.1rem;
        font-weight: 800;
        color: $primary;
        line-height: 1;
    }

    .count-label {
        font-size: 0.7rem;
        color: $text-muted;
    }
}

// ==========================================
// НАСТРОЙКИ СЕГМЕНТА
// ==========================================
.segment-settings,
.custom-settings {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 16px;
}

.settings-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;
    color: $primary;

    i {
        font-size: 1rem;
    }

    h4 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: $text;
    }
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.setting-field {
    label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: $text;

        i {
            color: $primary;
            font-size: 0.8rem;
        }
    }

    input {
        width: 100%;
        padding: 10px 12px;
        background: $bg;
        border: 1px solid $border;
        border-radius: 8px;
        font-size: 0.9rem;
        color: $text;
        transition: all 0.2s;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 2px rgba($primary, 0.1);
        }
    }
}

.segment-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: rgba($primary, 0.05);
    border-radius: 10px;
    font-size: 0.85rem;
    color: $text;

    i {
        color: $primary;
        font-size: 1.1rem;
    }

    strong {
        color: $primary;
        font-weight: 800;
    }
}

// ==========================================
// ВЫБОР ПОЛЬЗОВАТЕЛЕЙ
// ==========================================
.search-users {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 10px;
    transition: all 0.2s;

    &:focus-within {
        border-color: $primary;
        box-shadow: 0 0 0 2px rgba($primary, 0.1);
    }

    i {
        color: $text-muted;
        font-size: 0.85rem;
    }

    input {
        flex: 1;
        padding: 10px 0;
        background: transparent;
        border: none;
        font-size: 0.9rem;
        color: $text;

        &:focus {
            outline: none;
        }

        &::placeholder {
            color: $text-muted;
        }
    }
}

.users-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-height: 300px;
    overflow-y: auto;
}

.user-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: rgba($primary, 0.3);
    }

    &.selected {
        border-color: $primary;
        background: rgba($primary, 0.05);
    }

    input {
        position: absolute;
        opacity: 0;
    }
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    background: linear-gradient(135deg, $primary, $primary-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.8rem;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-phone {
    font-size: 0.75rem;
    color: $text-muted;
}

.user-check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: $primary;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.selected-summary {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: rgba($success, 0.08);
    border: 1px solid rgba($success, 0.2);
    border-radius: 10px;
    font-size: 0.85rem;
    color: $text;

    i {
        color: $success;
        font-size: 1rem;
    }

    strong {
        color: $success;
        font-weight: 800;
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

@media (max-width: 480px) {
    .option-count {
        display: none;
    }

    .settings-grid {
        grid-template-columns: 1fr;
    }
}

.loading-users,
.empty-users,
.no-results {
    text-align: center;
    padding: 40px 20px;
    color: $text-muted;

    .loader-spinner {
        width: 32px;
        height: 32px;
        margin: 0 auto 12px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    i {
        font-size: 2rem;
        opacity: 0.3;
        margin-bottom: 12px;
    }

    h5 {
        margin: 0 0 6px;
        font-size: 1rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.85rem;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
