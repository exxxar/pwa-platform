<template>
    <div class="edit-modal" :class="{ 'is-mobile': isMobile }">

        <!-- Заголовок -->
        <div class="edit-header">
            <button class="close-btn" @click="$emit('close')">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="header-info">
                <div class="header-icon">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <h3 class="header-title">Редактирование профиля</h3>
                    <p class="header-subtitle">{{ user?.name || 'Пользователь' }}</p>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Загружаем данные...</p>
        </div>

        <!-- Форма -->
        <form v-else @submit.prevent="saveChanges" class="edit-form">

            <!-- Табы -->
            <div class="edit-tabs">
                <button
                    type="button"
                    class="edit-tab"
                    :class="{ 'is-active': activeTab === 'main' }"
                    @click="activeTab = 'main'"
                >
                    <i class="fa-solid fa-user"></i>
                    <span>Основное</span>
                </button>
                <button
                    type="button"
                    class="edit-tab"
                    :class="{ 'is-active': activeTab === 'status' }"
                    @click="activeTab = 'status'"
                >
                    <i class="fa-solid fa-toggle-on"></i>
                    <span>Статусы</span>
                </button>
                <button
                    type="button"
                    class="edit-tab"
                    :class="{ 'is-active': activeTab === 'roles' }"
                    @click="activeTab = 'roles'"
                >
                    <i class="fa-solid fa-user-tag"></i>
                    <span>Роли</span>
                </button>
                <button
                    type="button"
                    class="edit-tab"
                    :class="{ 'is-active': activeTab === 'security' }"
                    @click="activeTab = 'security'"
                >
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Безопасность</span>
                </button>
            </div>

            <!-- Контент табов -->
            <div class="edit-content">

                <!-- ===== ТАБ: ОСНОВНОЕ ===== -->
                <div v-if="activeTab === 'main'" class="tab-panel">
                    <!-- ... (без изменений, как было) ... -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fa-solid fa-id-card"></i>
                            Личные данные
                        </h4>

                        <div class="form-field">
                            <label>
                                Имя
                                <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                placeholder="Введите имя"
                                :class="{ 'has-error': errors.name }"
                            >
                            <span v-if="errors.name" class="field-error">{{ errors.name }}</span>
                        </div>

                        <div class="form-row">
                            <div class="form-field">
                                <label>Телефон</label>
                                <input
                                    type="tel"
                                    v-model="form.phone"
                                    placeholder="+7 (___) ___-__-__"
                                    @input="formatPhone"
                                >
                            </div>

                            <div class="form-field">
                                <label>Email</label>
                                <input
                                    type="email"
                                    v-model="form.email"
                                    placeholder="email@example.com"
                                    :class="{ 'has-error': errors.email }"
                                >
                                <span v-if="errors.email" class="field-error">{{ errors.email }}</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-field">
                                <label>Дата рождения</label>
                                <input
                                    type="date"
                                    v-model="form.birthday"
                                    :max="maxBirthday"
                                >
                            </div>

                            <div class="form-field">
                                <label>Пол</label>
                                <select v-model="form.sex">
                                    <option value="">Не указан</option>
                                    <option value="male">Мужской</option>
                                    <option value="female">Женский</option>
                                    <option value="other">Другой</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== ТАБ: СТАТУСЫ ===== -->
                <div v-if="activeTab === 'status'" class="tab-panel">
                    <!-- ... (без изменений, как было) ... -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fa-solid fa-circle-check"></i>
                            Статусы аккаунта
                        </h4>

                        <div class="toggle-card">
                            <div class="toggle-info">
                                <div class="toggle-icon active">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                                <div>
                                    <h5>Активный аккаунт</h5>
                                    <p>Пользователь может пользоваться сервисом</p>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" v-model="form.is_active">
                                <span class="switch-slider"></span>
                            </label>
                        </div>

                        <div class="toggle-card" :class="{ 'is-vip': form.is_vip }">
                            <div class="toggle-info">
                                <div class="toggle-icon vip">
                                    <i class="fa-solid fa-crown"></i>
                                </div>
                                <div>
                                    <h5>VIP статус</h5>
                                    <p>Расширенные возможности и привилегии</p>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" v-model="form.is_vip">
                                <span class="switch-slider"></span>
                            </label>
                        </div>

                        <transition name="fade">
                            <div v-if="form.is_vip" class="vip-settings">
                                <div class="form-field">
                                    <label>Дата окончания VIP</label>
                                    <input
                                        type="date"
                                        v-model="form.vip_expires_at"
                                        :min="minVipDate"
                                    >
                                    <span class="field-hint">Оставьте пустым для бессрочного VIP</span>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- ===== ТАБ: РОЛИ (НОВЫЙ) ===== -->
                <div v-if="activeTab === 'roles'" class="tab-panel">
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fa-solid fa-user-tag"></i>
                            Роли и доступы
                        </h4>

                        <!-- Индикатор загрузки ролей -->
                        <div v-if="loadingRoles" class="roles-loading">
                            <div class="loading-spinner small"></div>
                            <span>Загружаем роли...</span>
                        </div>

                        <!-- Текущая роль пользователя -->
                        <div v-else>
                            <p class="roles-hint">
                                Выберите роли для этого пользователя. Пользователь получит все права, связанные с выбранными ролями.
                            </p>

                            <!-- Список ролей как чипы -->
                            <div class="roles-list">
                                <div
                                    v-for="role in availableRoles"
                                    :key="role.id"
                                    class="role-chip"
                                    :class="{ 'is-selected': form.role_ids.includes(role.id) }"
                                    @click="toggleRole(role.id)"
                                >
                                    <div class="role-icon">
                                        <i class="fa-solid" :class="getRoleIcon(role.name)"></i>
                                    </div>
                                    <div class="role-info">
                                        <span class="role-name">{{ role.label }}</span>
                                        <span class="role-count">{{ role.permissions_count || 0 }} прав</span>
                                    </div>
                                    <div class="role-check">
                                        <i v-if="form.role_ids.includes(role.id)" class="fa-solid fa-check"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Предупреждение о суперадмине -->
                            <div v-if="form.role_ids.some(id => isSuperAdminRole(id))" class="warning-box">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <div>
                                    <strong>Суперадмин</strong>
                                    <p>Этот пользователь получит полный доступ ко всем функциям системы.</p>
                                </div>
                            </div>

                            <!-- Пустое состояние -->
                            <div v-if="availableRoles.length === 0" class="empty-roles">
                                <i class="fa-solid fa-user-slash"></i>
                                <p>Роли не найдены. Создайте роли в разделе "Управление ролями".</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== ТАБ: БЕЗОПАСНОСТЬ ===== -->
                <div v-if="activeTab === 'security'" class="tab-panel">
                    <!-- ... (без изменений, как было) ... -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fa-solid fa-ban"></i>
                            Блокировка
                        </h4>

                        <div class="toggle-card" :class="{ 'is-danger': form.blocked }">
                            <div class="toggle-info">
                                <div class="toggle-icon danger">
                                    <i class="fa-solid fa-user-slash"></i>
                                </div>
                                <div>
                                    <h5>Заблокировать пользователя</h5>
                                    <p>Пользователь не сможет пользоваться сервисом</p>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" v-model="form.blocked">
                                <span class="switch-slider"></span>
                            </label>
                        </div>

                        <transition name="fade">
                            <div v-if="form.blocked" class="block-settings">
                                <div class="form-field">
                                    <label>Причина блокировки</label>
                                    <textarea
                                        v-model="form.blocked_message"
                                        rows="3"
                                        placeholder="Укажите причину блокировки..."
                                    ></textarea>
                                </div>

                                <div class="warning-box">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <div>
                                        <strong>Внимание!</strong>
                                        <p>После блокировки пользователь не сможет войти в систему и совершать действия.</p>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

            </div>

            <!-- Индикатор изменений -->
            <div v-if="hasChanges" class="changes-indicator">
                <i class="fa-solid fa-circle-info"></i>
                <span>У вас есть несохранённые изменения</span>
            </div>

            <!-- Действия -->
            <div class="edit-actions">
                <button
                    type="button"
                    class="action-btn cancel"
                    @click="$emit('close')"
                    :disabled="isSaving"
                >
                    Отмена
                </button>
                <button
                    type="submit"
                    class="action-btn save"
                    :disabled="isSaving || !hasChanges"
                >
                    <span v-if="isSaving" class="btn-spinner"></span>
                    <i v-else class="fa-solid fa-check"></i>
                    <span>{{ isSaving ? 'Сохранение...' : 'Сохранить изменения' }}</span>
                </button>
            </div>

        </form>

    </div>
</template>

<script>
export default {
    name: 'UserEditModal',

    props: {
        user: {
            type: Object,
            required: true,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['close', 'saved', 'save', 'toggle-block'],

    data() {
        return {
            activeTab: 'main',
            isMobile: window.innerWidth < 640,
            isSaving: false,
            loadingRoles: false,

            form: {
                name: '',
                email: '',
                phone: '',
                birthday: '',
                sex: '',
                is_active: true,
                is_vip: false,
                vip_expires_at: '',
                blocked: false,
                blocked_message: '',
                role_ids: [], // Массив ID выбранных ролей
            },

            initialForm: {},
            errors: {},
            availableRoles: [], // Список всех доступных ролей
        };
    },

    computed: {
        hasChanges() {
            return JSON.stringify(this.form) !== JSON.stringify(this.initialForm);
        },

        maxBirthday() {
            return new Date().toISOString().split('T')[0];
        },

        minVipDate() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            return tomorrow.toISOString().split('T')[0];
        },
    },

    watch: {
        user: {
            immediate: true,
            handler(user) {
                if (user) {
                    this.initializeForm(user);
                    this.loadRoles();
                }
            },
        },
    },

    methods: {
        initializeForm(user) {
            this.form = {
                name: user.name || '',
                email: user.email || '',
                phone: user.phone || '',
                birthday: user.birthday ? user.birthday.split(' ')[0] : '',
                sex: user.sex || '',
                is_active: user.is_active ?? true,
                is_vip: user.is_vip ?? false,
                vip_expires_at: user.vip_expires_at ? user.vip_expires_at.split(' ')[0] : '',
                blocked: !!user.blocked_at,
                blocked_message: user.blocked_message || '',
                role_ids: user.roles ? user.roles.map(r => r.id) : [],
            };

            this.initialForm = { ...this.form };
        },

        /**
         * Загрузка списка доступных ролей
         */
        async loadRoles() {
            this.loadingRoles = true;
            try {
                const response = await axios.get('/admin/roles');
                this.availableRoles = response.data.data || response.data;
            } catch (error) {
                console.error('Ошибка загрузки ролей:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить список ролей',
                    type: 'error',
                });
            } finally {
                this.loadingRoles = false;
            }
        },

        /**
         * Переключение роли (добавить/убрать)
         */
        toggleRole(roleId) {
            const index = this.form.role_ids.indexOf(roleId);
            if (index === -1) {
                this.form.role_ids.push(roleId);
            } else {
                this.form.role_ids.splice(index, 1);
            }
        },

        /**
         * Проверка, является ли роль суперадмином
         */
        isSuperAdminRole(roleId) {
            const role = this.availableRoles.find(r => r.id === roleId);
            return role && role.name === 'super_admin';
        },

        /**
         * Получение иконки для роли
         */
        getRoleIcon(roleName) {
            const icons = {
                'super_admin': 'fa-crown',
                'admin': 'fa-user-shield',
                'worker': 'fa-user-gear',
                'user': 'fa-user',
                'delivery': 'fa-motorcycle',
            };
            return icons[roleName] || 'fa-user-tag';
        },

        formatPhone(e) {
            let value = e.target.value.replace(/\D/g, '');

            if (value.startsWith('8')) {
                value = '7' + value.slice(1);
            }
            if (!value.startsWith('7') && value.length > 0) {
                value = '7' + value;
            }

            let formatted = '';
            if (value.length > 0) formatted = '+' + value[0];
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 4) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 7) formatted += '-' + value.slice(7, 9);
            if (value.length >= 9) formatted += '-' + value.slice(9, 11);

            this.form.phone = formatted;
        },

        validate() {
            this.errors = {};

            if (!this.form.name || this.form.name.trim().length < 2) {
                this.errors.name = 'Имя должно содержать минимум 2 символа';
            }

            if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                this.errors.email = 'Некорректный email';
            }

            return Object.keys(this.errors).length === 0;
        },

        async saveChanges() {
            if (!this.validate()) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Проверьте правильность заполнения полей',
                    type: 'error',
                });
                return;
            }

            this.isSaving = true;

            try {
                const data = {
                    name: this.form.name,
                    email: this.form.email || null,
                    phone: this.form.phone || null,
                    birthday: this.form.birthday || null,
                    sex: this.form.sex || null,
                    is_active: this.form.is_active,
                    is_vip: this.form.is_vip,
                    vip_expires_at: this.form.vip_expires_at || null,
                    role_ids: this.form.role_ids, // Отправляем выбранные роли
                };

                // Обновляем основные данные (включая роли)
                await this.$emit('save', data);

                // Если изменилась блокировка — отдельный запрос
                if (this.form.blocked !== !!this.user.blocked_at) {
                    await this.$emit('toggle-block', {
                        block: this.form.blocked,
                        message: this.form.blocked_message,
                    });
                }

                this.initialForm = { ...this.form };

                this.$notify?.({
                    title: 'Успешно',
                    text: 'Профиль обновлён',
                    type: 'success',
                });

            } catch (error) {
                console.error('[UserEdit] Ошибка:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось сохранить изменения',
                    type: 'error',
                });
            } finally {
                this.isSaving = false;
            }
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$purple: #8b5cf6;
$bg: #ffffff;
$bg-secondary: #f9fafb;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;

.edit-modal {
    background: $bg;
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s ease;

    &.is-mobile {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 20px 20px 0 0;
    }
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

// ==========================================
// ШАПКА
// ==========================================
.edit-header {
    position: relative;
    padding: 20px 20px 16px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
}

.close-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: all 0.2s;
    z-index: 2;

    &:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.1);
    }
}

.header-info {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-right: 32px;
}

.header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.header-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 2px;
}

.header-subtitle {
    font-size: 0.85rem;
    opacity: 0.9;
    margin: 0;
}

// ==========================================
// ЗАГРУЗКА
// ==========================================
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    color: $text-muted;

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 16px;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// ФОРМА
// ==========================================
.edit-form {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
}

// Табы
.edit-tabs {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
    background: $bg-secondary;
    border-bottom: 1px solid $border;
}

.edit-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(.is-active) {
        color: $text;
        background: rgba($primary, 0.05);
    }

    &.is-active {
        background: $bg;
        color: $primary;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    i {
        font-size: 0.9rem;
    }
}

// Контент
.edit-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: $bg-secondary;
}

.tab-panel {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid $border;

    i {
        color: $primary;
    }
}

// Поля
.form-field {
    margin-bottom: 14px;

    &:last-child {
        margin-bottom: 0;
    }

    label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;

        .required {
            color: $danger;
        }
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 10px 12px;
        background: $bg;
        border: 1.5px solid $border;
        border-radius: 10px;
        font-size: 0.9rem;
        color: $text;
        font-family: inherit;
        transition: all 0.2s;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }

        &.has-error {
            border-color: $danger;
        }

        &::placeholder {
            color: $text-muted;
        }
    }

    textarea {
        resize: vertical;
        min-height: 80px;
    }

    .field-error {
        display: block;
        margin-top: 4px;
        font-size: 0.75rem;
        color: $danger;
    }

    .field-hint {
        display: block;
        margin-top: 4px;
        font-size: 0.75rem;
        color: $text-muted;
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

// Toggle-карточки
.toggle-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    margin-bottom: 10px;
    transition: all 0.2s;

    &.is-vip {
        background: rgba($warning, 0.05);
        border-color: rgba($warning, 0.3);
    }

    &.is-danger {
        background: rgba($danger, 0.05);
        border-color: rgba($danger, 0.3);
    }
}

.toggle-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;

    h5 {
        margin: 0 0 2px;
        font-size: 0.9rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.75rem;
        color: $text-muted;
    }
}

.toggle-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;

    &.active {
        background: linear-gradient(135deg, $success, #059669);
    }

    &.vip {
        background: linear-gradient(135deg, $warning, #d97706);
    }

    &.danger {
        background: linear-gradient(135deg, $danger, #dc2626);
    }
}

// Switch
.switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 28px;
    flex-shrink: 0;

    input {
        opacity: 0;
        width: 0;
        height: 0;

        &:checked + .switch-slider {
            background: $primary;

            &::before {
                transform: translateX(20px);
            }
        }
    }
}

.switch-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: $border;
    border-radius: 28px;
    transition: 0.3s;

    &::before {
        position: absolute;
        content: '';
        height: 22px;
        width: 22px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
}

// Настройки VIP/блокировки
.vip-settings,
.block-settings {
    margin-top: 12px;
    padding: 14px;
    background: $bg-secondary;
    border-radius: 12px;
    border: 1px solid $border;
}

.warning-box {
    display: flex;
    gap: 10px;
    padding: 12px;
    background: rgba($warning, 0.08);
    border: 1px solid rgba($warning, 0.2);
    border-radius: 10px;
    margin-top: 12px;

    i {
        color: $warning;
        font-size: 1.1rem;
        margin-top: 2px;
        flex-shrink: 0;
    }

    strong {
        display: block;
        color: $text;
        margin-bottom: 4px;
        font-size: 0.85rem;
    }

    p {
        margin: 0;
        font-size: 0.8rem;
        color: $text-muted;
        line-height: 1.4;
    }
}

// Индикатор изменений
.changes-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba($primary, 0.08);
    border-top: 1px solid rgba($primary, 0.15);
    font-size: 0.8rem;
    color: $primary;

    i {
        font-size: 0.9rem;
    }
}

// Действия
.edit-actions {
    display: flex;
    gap: 10px;
    padding: 14px 16px;
    background: $bg;
    border-top: 1px solid $border;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &.cancel {
        background: $bg-secondary;
        color: $text;
        border: 1px solid $border;

        &:hover:not(:disabled) {
            border-color: $danger;
            color: $danger;
        }
    }

    &.save {
        background: linear-gradient(135deg, $primary, $primary-dark);
        color: white;
        box-shadow: 0 4px 12px rgba($primary, 0.3);

        &:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba($primary, 0.4);
        }
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

// Анимации
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

// Адаптив
@media (max-width: 640px) {
    .edit-header {
        padding: 16px 14px;
    }

    .header-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .header-title {
        font-size: 1rem;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .edit-tab span {
        display: none;
    }
}

.roles-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px;
    color: $text-muted;
    font-size: 0.9rem;

    .loading-spinner.small {
        width: 20px;
        height: 20px;
        border-width: 2px;
    }
}

.roles-hint {
    font-size: 0.85rem;
    color: $text-muted;
    margin: 0 0 16px;
    line-height: 1.5;
}

.roles-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.role-chip {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: $bg-secondary;
    border: 1.5px solid $border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        background: rgba($primary, 0.02);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.08);
    }
}

.role-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: linear-gradient(135deg, $primary, $primary-dark);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.role-info {
    flex: 1;
    min-width: 0;
}

.role-name {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    color: $text;
    margin-bottom: 2px;
}

.role-count {
    display: block;
    font-size: 0.75rem;
    color: $text-muted;
}

.role-check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: $primary;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    opacity: 0;
    transition: opacity 0.2s;

    .is-selected & {
        opacity: 1;
    }
}

.empty-roles {
    text-align: center;
    padding: 30px 20px;
    color: $text-muted;

    i {
        font-size: 2rem;
        margin-bottom: 10px;
        opacity: 0.5;
    }

    p {
        font-size: 0.85rem;
        margin: 0;
    }
}
</style>
