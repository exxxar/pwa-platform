<template>
    <div class="user-info">

        <!-- ========================================== -->
        <!-- КНОПКА РЕДАКТИРОВАНИЯ -->
        <!-- ========================================== -->
        <button
            class="edit-toggle-btn"
            :class="{ 'is-active': isEdit }"
            @click="toggleEdit"
        >
            <i class="fa-solid" :class="isEdit ? 'fa-chevron-up' : 'fa-user-pen'"></i>
            <span>{{ isEdit ? 'Завершить редактирование' : 'Редактировать пользователя' }}</span>
        </button>

        <!-- ========================================== -->
        <!-- РЕЖИМ ПРОСМОТРА -->
        <!-- ========================================== -->
        <div v-if="!isEdit" class="view-mode">

            <!-- Основные данные -->
            <div class="info-section">
                <div class="section-header">
                    <div class="section-icon primary">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h3>Основные данные</h3>
                        <p>Идентификационная информация</p>
                    </div>
                </div>
                <div class="info-grid">
                    <InfoRow label="ID в системе" :value="botUser.id" icon="fa-solid fa-fingerprint" />
                    <InfoRow label="Дата регистрации" :value="formatDate(botUser.created_at)" icon="fa-solid fa-calendar" />
                    <InfoRow label="Telegram ID" :value="botUser.telegram_chat_id" icon="fa-brands fa-telegram" />
                    <InfoRow label="Имя" :value="botUser.name" icon="fa-solid fa-signature" :required="true" />
                    <InfoRow label="Имя из Telegram" :value="botUser.fio_from_telegram" icon="fa-brands fa-telegram" />
                    <InfoRow label="Домен" :value="botUser.username" icon="fa-solid fa-at" />
                    <InfoRow label="Пол" :value="botUser.sex === null ? null : (botUser.sex ? 'Мужчина' : 'Женщина')" icon="fa-solid fa-venus-mars" />
                    <InfoRow label="Возраст" :value="botUser.age" icon="fa-solid fa-cake-candles" :required="true" />
                </div>
            </div>

            <!-- Контакты и локация -->
            <div class="info-section">
                <div class="section-header">
                    <div class="section-icon success">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <h3>Контакты и адрес</h3>
                        <p>Способы связи и местоположение</p>
                    </div>
                </div>
                <div class="info-grid">
                    <InfoRow label="Телефон" :value="botUser.phone" icon="fa-solid fa-phone" :required="true" />
                    <InfoRow label="Email" :value="botUser.email" icon="fa-solid fa-envelope" />
                    <InfoRow label="Страна" :value="botUser.country" icon="fa-solid fa-globe" :required="true" />
                    <InfoRow label="Город" :value="botUser.city" icon="fa-solid fa-city" :required="true" />
                    <InfoRow label="Адрес" :value="botUser.address" icon="fa-solid fa-house" :required="true" />
                </div>
            </div>

            <!-- Статусы -->
            <div class="info-section">
                <div class="section-header">
                    <div class="section-icon warning">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3>Статусы и роли</h3>
                        <p>Права доступа и текущее состояние</p>
                    </div>
                </div>
                <div class="status-badges">
                    <StatusBadge
                        label="VIP"
                        :active="botUser.is_vip"
                        icon="fa-solid fa-crown"
                        color="gold"
                    />
                    <StatusBadge
                        label="Администратор"
                        :active="botUser.is_admin"
                        icon="fa-solid fa-user-shield"
                        color="danger"
                    />
                    <StatusBadge
                        label="Доставщик"
                        :active="botUser.is_deliveryman"
                        icon="fa-solid fa-motorcycle"
                        color="success"
                    />
                    <StatusBadge
                        label="На работе"
                        :active="botUser.is_work"
                        icon="fa-solid fa-briefcase"
                        color="primary"
                    />
                    <StatusBadge
                        label="В заведении"
                        :active="botUser.user_in_location"
                        icon="fa-solid fa-store"
                        color="info"
                    />
                </div>

                <!-- Блок блокировки -->
                <div v-if="botUser.blocked_at" class="blocked-notice">
                    <div class="blocked-icon">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div class="blocked-info">
                        <strong>Пользователь заблокирован</strong>
                        <p>{{ formatDate(botUser.blocked_at) }}</p>
                        <em v-if="botUser.blocked_message">{{ botUser.blocked_message }}</em>
                        <em v-else>Без сообщения</em>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- РЕЖИМ РЕДАКТИРОВАНИЯ -->
        <!-- ========================================== -->
        <form v-else @submit.prevent="submit" class="edit-mode">

            <!-- Персональные данные -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon primary">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h3>Персональные данные</h3>
                </div>

                <div class="form-group">
                    <label>
                        Ф.И.О. <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="botUserForm.name"
                        class="form-input"
                        placeholder="Иванов Иван Иванович"
                        required
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Телефон <span class="required">*</span></label>
                        <input
                            type="tel"
                            v-model="botUserForm.phone"
                            v-mask="'+7(###)###-##-##'"
                            class="form-input"
                            placeholder="+7(XXX) XXX-XX-XX"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input
                            type="email"
                            v-model="botUserForm.email"
                            class="form-input"
                            placeholder="example@gmail.com"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Дата рождения</label>
                    <input
                        type="date"
                        v-model="botUserForm.birthday"
                        class="form-input"
                    >
                </div>

                <div class="form-group">
                    <label>Пол</label>
                    <div class="gender-toggle">
                        <button
                            type="button"
                            class="gender-btn"
                            :class="{ 'is-active': botUserForm.sex === true }"
                            @click="botUserForm.sex = true"
                        >
                            <i class="fa-solid fa-mars"></i>
                            <span>Мужской</span>
                        </button>
                        <button
                            type="button"
                            class="gender-btn"
                            :class="{ 'is-active': botUserForm.sex === false }"
                            @click="botUserForm.sex = false"
                        >
                            <i class="fa-solid fa-venus"></i>
                            <span>Женский</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Адрес -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon success">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3>Адрес</h3>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Страна</label>
                        <input
                            type="text"
                            v-model="botUserForm.country"
                            class="form-input"
                            placeholder="Россия"
                        >
                    </div>
                    <div class="form-group">
                        <label>Город</label>
                        <input
                            type="text"
                            v-model="botUserForm.city"
                            class="form-input"
                            placeholder="Краснодар"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Адрес</label>
                    <input
                        type="text"
                        v-model="botUserForm.address"
                        class="form-input"
                        placeholder="ул. Петрова, 123, кв 45"
                    >
                </div>
            </div>

            <!-- Роли и статусы -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon warning">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3>Роли и статусы</h3>
                </div>

                <div class="toggles-grid">
                    <ToggleSwitch
                        v-model="botUserForm.is_admin"
                        label="Администратор"
                        icon="fa-solid fa-user-shield"
                        color="danger"
                    />
                    <ToggleSwitch
                        v-model="botUserForm.is_vip"
                        label="VIP-клиент"
                        icon="fa-solid fa-crown"
                        color="gold"
                    />
                    <ToggleSwitch
                        v-model="botUserForm.is_deliveryman"
                        label="Доставщик"
                        icon="fa-solid fa-motorcycle"
                        color="success"
                    />
                    <ToggleSwitch
                        v-model="botUserForm.is_manager"
                        label="Менеджер"
                        icon="fa-solid fa-user-tie"
                        color="primary"
                    />
                    <ToggleSwitch
                        v-model="botUserForm.is_work"
                        label="На работе"
                        icon="fa-solid fa-briefcase"
                        color="info"
                    />
                    <ToggleSwitch
                        v-model="botUserForm.user_in_location"
                        label="В заведении"
                        icon="fa-solid fa-store"
                        color="info"
                    />
                </div>
            </div>

            <!-- Блокировка -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon danger">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <h3>Блокировка</h3>
                </div>

                <ToggleSwitch
                    v-model="botUserForm.is_blocked"
                    label="Заблокировать пользователя"
                    icon="fa-solid fa-user-slash"
                    color="danger"
                />

                <transition name="expand">
                    <div v-if="botUserForm.is_blocked" class="blocked-form">
                        <div class="form-group">
                            <label>Причина блокировки</label>
                            <textarea
                                v-model="botUserForm.blocked_message"
                                class="form-input textarea"
                                rows="3"
                                placeholder="Опишите причину блокировки..."
                            ></textarea>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Сообщения об ошибках -->
            <transition-group name="message" tag="div" class="messages-list">
                <div
                    v-for="(message, index) in messages"
                    :key="'msg-' + index"
                    class="error-message"
                >
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ message }}</span>
                    <button type="button" class="close-message" @click="removeMessage(index)">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </transition-group>

            <!-- Кнопка сохранения (sticky) -->
            <div class="save-bar">
                <button type="submit" class="save-btn" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="btn-spinner"></span>
                    <template v-else>
                        <i class="fa-solid fa-check"></i>
                        <span>Сохранить изменения</span>
                    </template>
                </button>
            </div>

        </form>

    </div>
</template>

<script>
// Вспомогательные компоненты (можно вынести в отдельные файлы)
const InfoRow = {
    name: 'InfoRow',
    props: {
        label: String,
        value: [String, Number],
        icon: String,
        required: Boolean,
    },
    template: `
        <div class="info-row" :class="{ 'is-empty': !value && required }">
            <div class="info-icon">
                <i :class="icon"></i>
            </div>
            <div class="info-content">
                <div class="info-label">{{ label }}</div>
                <div class="info-value">{{ value || 'Не указано' }}</div>
            </div>
            <span v-if="!value && required" class="missing-badge">Нет данных</span>
        </div>
    `,
};

const StatusBadge = {
    name: 'StatusBadge',
    props: {
        label: String,
        active: Boolean,
        icon: String,
        color: String,
    },
    template: `
        <div class="status-badge" :class="[active ? 'is-active' : 'is-inactive', 'color-' + color]">
            <i :class="icon"></i>
            <span>{{ label }}</span>
            <div class="status-indicator">{{ active ? 'Да' : 'Нет' }}</div>
        </div>
    `,
};

const ToggleSwitch = {
    name: 'ToggleSwitch',
    props: {
        modelValue: Boolean,
        label: String,
        icon: String,
        color: { type: String, default: 'primary' },
    },
    emits: ['update:modelValue'],
    template: `
        <div class="toggle-switch" :class="['color-' + color, { 'is-active': modelValue }]">
            <div class="toggle-icon">
                <i :class="icon"></i>
            </div>
            <div class="toggle-info">
                <div class="toggle-label">{{ label }}</div>
            </div>
            <button
                type="button"
                class="toggle-btn"
                :class="{ 'is-on': modelValue }"
                @click="$emit('update:modelValue', !modelValue)"
            >
                <span class="toggle-knob"></span>
            </button>
        </div>
    `,
};

export default {
    name: "UserInfo",

    components: {
        InfoRow,
        StatusBadge,
        ToggleSwitch,
    },

    props: {
        botUser: {
            type: Object,
            required: true,
        },
    },

    emits: ['update'],

    data() {
        return {
            isEdit: false,
            isSubmitting: false,
            messages: [],
            botUserForm: this.getEmptyForm(),
        };
    },

    watch: {
        botUser: {
            immediate: true,
            handler() {
                if (this.isEdit) {
                    this.initForm();
                }
            },
        },
    },

    methods: {
        /**
         * Пустая форма
         */
        getEmptyForm() {
            return {
                id: null,
                is_vip: false,
                is_admin: false,
                is_work: false,
                is_manager: false,
                is_deliveryman: false,
                user_in_location: false,
                name: null,
                phone: null,
                email: null,
                birthday: null,
                city: null,
                country: null,
                address: null,
                sex: null,
                is_blocked: false,
                blocked_message: null,
            };
        },

        /**
         * Инициализация формы данными пользователя
         */
        initForm() {
            const u = this.botUser;
            this.botUserForm = {
                id: u.id,
                is_vip: !!u.is_vip,
                is_admin: !!u.is_admin,
                is_work: !!u.is_work,
                is_manager: !!u.is_manager,
                is_deliveryman: !!u.is_deliveryman,
                user_in_location: !!u.user_in_location,
                name: u.name || u.username || u.id,
                phone: u.phone,
                email: u.email,
                birthday: u.birthday || null,
                city: u.city || null,
                country: u.country || null,
                address: u.address || null,
                sex: u.sex ?? null,
                is_blocked: !!u.blocked_at,
                blocked_message: u.blocked_message || null,
            };
        },

        /**
         * Переключение режима редактирования
         */
        toggleEdit() {
            this.isEdit = !this.isEdit;
            if (this.isEdit) {
                this.initForm();
                this.messages = [];
            }
        },

        /**
         * Форматирование даты
         */
        formatDate(date) {
            if (!date) return null;
            try {
                return this.$filters?.current
                    ? this.$filters.current(date)
                    : new Date(date).toLocaleDateString('ru-RU');
            } catch {
                return date;
            }
        },

        /**
         * Добавление сообщения об ошибке
         */
        addMessage(msg) {
            this.messages.push(msg || 'Произошла ошибка');
        },

        /**
         * Удаление сообщения
         */
        removeMessage(index) {
            this.messages.splice(index, 1);
        },

        /**
         * Валидация формы
         */
        validateForm() {
            this.messages = [];

            if (!this.botUserForm.name?.trim()) {
                this.addMessage('Укажите имя пользователя');
                return false;
            }
            if (!this.botUserForm.phone?.trim()) {
                this.addMessage('Укажите телефон пользователя');
                return false;
            }
            return true;
        },

        /**
         * Отправка формы
         */
        async submit() {
            if (!this.validateForm()) return;

            this.isSubmitting = true;

            try {
                await this.$store.dispatch('updateBotUser', {
                    botUserForm: this.botUserForm,
                });

                this.isEdit = false;
                this.messages = [];

                this.$emit('update');

                this.$notify?.({
                    title: 'Успешно',
                    text: 'Данные пользователя обновлены',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка обновления:', error);
                this.addMessage('Ошибка обновления данных. Попробуйте ещё раз.');

                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось обновить данные',
                    type: 'error',
                });
            } finally {
                this.isSubmitting = false;
            }
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #3b82f6;
$primary-dark: #2563eb;
$primary-light: #60a5fa;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$gold: #f59e0b;
$info: #06b6d4;
$purple: #8b5cf6;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.user-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

// ==========================================
// КНОПКА РЕДАКТИРОВАНИЯ
// ==========================================
.edit-toggle-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    color: $primary;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;

    i {
        font-size: 1rem;
    }

    &:hover {
        background: rgba($primary, 0.05);
        border-color: $primary;
    }

    &.is-active {
        background: $primary;
        color: white;
        border-color: $primary;
    }
}

// ==========================================
// РЕЖИМ ПРОСМОТРА
// ==========================================
.view-mode {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-section {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid $border;

    h3 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0 0 2px 0;
        color: $text;
    }

    p {
        font-size: 0.8rem;
        color: $text-muted;
        margin: 0;
    }
}

.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
    flex-shrink: 0;

    &.primary { background: linear-gradient(135deg, $primary 0%, $primary-light 100%); }
    &.success { background: linear-gradient(135deg, $success 0%, #34d399 100%); }
    &.warning { background: linear-gradient(135deg, $warning 0%, #fbbf24 100%); }
    &.danger { background: linear-gradient(135deg, $danger 0%, #f87171 100%); }
}

.info-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: $bg;
    border-radius: 10px;
    transition: all 0.2s;

    &.is-empty {
        background: rgba($warning, 0.05);
        border: 1px dashed rgba($warning, 0.3);
    }
}

.info-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;

    .is-empty & {
        background: rgba($warning, 0.1);
        color: $warning;
    }
}

.info-content {
    flex: 1;
    min-width: 0;
}

.info-label {
    font-size: 0.75rem;
    color: $text-muted;
    margin-bottom: 2px;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: $text;
    word-break: break-word;

    .is-empty & {
        color: $text-muted;
        font-style: italic;
        font-weight: 500;
    }
}

.missing-badge {
    padding: 3px 8px;
    background: rgba($warning, 0.1);
    color: $warning;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    flex-shrink: 0;
}

// Статусы
.status-badges {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 10px;
    background: $bg;
    border: 1px solid $border;
    transition: all 0.2s;

    &.is-active {
        border-color: var(--badge-color);
        background: var(--badge-bg);
    }

    &.color-danger { --badge-color: #{$danger}; --badge-bg: rgba(#{$danger}, 0.05); }
    &.color-gold { --badge-color: #{$gold}; --badge-bg: rgba(#{$gold}, 0.08); }
    &.color-success { --badge-color: #{$success}; --badge-bg: rgba(#{$success}, 0.05); }
    &.color-primary { --badge-color: #{$primary}; --badge-bg: rgba(#{$primary}, 0.05); }
    &.color-info { --badge-color: #{$info}; --badge-bg: rgba(#{$info}, 0.05); }
}

.status-badge i {
    font-size: 1.1rem;
    color: var(--badge-color);
}

.status-badge .status-indicator {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: 700;
    color: $text-muted;

    .is-active & {
        color: var(--badge-color);
    }
}

// Блок блокировки
.blocked-notice {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: rgba($danger, 0.05);
    border: 1px solid rgba($danger, 0.2);
    border-radius: 12px;
    margin-top: 16px;
}

.blocked-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba($danger, 0.1);
    color: $danger;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.blocked-info {
    flex: 1;
    font-size: 0.9rem;
    color: $text;

    strong {
        display: block;
        margin-bottom: 4px;
        color: $danger;
    }

    p {
        margin: 0 0 4px 0;
        font-size: 0.85rem;
        color: $text-muted;
    }

    em {
        font-size: 0.85rem;
        color: $text-muted;
    }
}

// ==========================================
// РЕЖИМ РЕДАКТИРОВАНИЯ
// ==========================================
.edit-mode {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-section {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;

    .section-header {
        margin-bottom: 16px;
        padding-bottom: 0;
        border-bottom: none;
    }
}

.form-group {
    margin-bottom: 14px;

    &:last-child {
        margin-bottom: 0;
    }

    label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;

        .required {
            color: $danger;
        }
    }
}

.form-input {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.95rem;
    background: $card-bg;
    color: $text;
    transition: all 0.2s;
    font-family: inherit;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }

    &::placeholder {
        color: #9ca3af;
    }

    &.textarea {
        resize: vertical;
        min-height: 80px;
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

// Пол
.gender-toggle {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.gender-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: $bg;
    border: 2px solid $border;
    border-radius: 10px;
    color: $text-muted;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;

    i {
        font-size: 1rem;
    }

    &:hover {
        border-color: $primary;
        color: $primary;
    }

    &.is-active {
        background: rgba($primary, 0.08);
        border-color: $primary;
        color: $primary;
    }
}

// Toggle-свитчи
.toggles-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 10px;
    transition: all 0.2s;

    &.is-active {
        border-color: var(--toggle-color);
        background: var(--toggle-bg);
    }

    &.color-danger { --toggle-color: #{$danger}; --toggle-bg: rgba(#{$danger}, 0.05); }
    &.color-gold { --toggle-color: #{$gold}; --toggle-bg: rgba(#{$gold}, 0.08); }
    &.color-success { --toggle-color: #{$success}; --toggle-bg: rgba(#{$success}, 0.05); }
    &.color-primary { --toggle-color: #{$primary}; --toggle-bg: rgba(#{$primary}, 0.05); }
    &.color-info { --toggle-color: #{$info}; --toggle-bg: rgba(#{$info}, 0.05); }
}

.toggle-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--toggle-bg);
    color: var(--toggle-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.toggle-info {
    flex: 1;
}

.toggle-label {
    font-weight: 600;
    font-size: 0.9rem;
    color: $text;
}

.toggle-btn {
    position: relative;
    width: 48px;
    height: 28px;
    border-radius: 14px;
    background: #d1d5db;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
    flex-shrink: 0;
    padding: 0;

    &.is-on {
        background: var(--toggle-color);
    }
}

.toggle-knob {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s;

    .is-on & {
        transform: translateX(20px);
    }
}

// Блок блокировки в форме
.blocked-form {
    margin-top: 12px;
    padding: 14px;
    background: rgba($danger, 0.03);
    border: 1px dashed rgba($danger, 0.3);
    border-radius: 10px;
}

.expand-enter-active,
.expand-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
    opacity: 0;
    max-height: 0;
    margin-top: 0;
    padding: 0 14px;
}

.expand-enter-to,
.expand-leave-from {
    opacity: 1;
    max-height: 200px;
}

// Сообщения об ошибках
.messages-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.error-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: rgba($danger, 0.08);
    border: 1px solid rgba($danger, 0.2);
    border-radius: 10px;
    color: $danger;
    font-size: 0.9rem;
    font-weight: 500;

    i:first-child {
        font-size: 1rem;
        flex-shrink: 0;
    }

    span {
        flex: 1;
    }
}

.close-message {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: transparent;
    border: none;
    color: $danger;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover {
        background: rgba($danger, 0.15);
    }
}

.message-enter-active,
.message-leave-active {
    transition: all 0.3s ease;
}

.message-enter-from,
.message-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

// Кнопка сохранения
.save-bar {
    position: sticky;
    bottom: 0;
    background: linear-gradient(to top, $card-bg 70%, transparent);
    padding: 16px 0 0;
    margin-top: 8px;
}

.save-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 16px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($primary, 0.4);
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
}

.btn-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 640px) {
    .info-section,
    .form-section {
        padding: 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .status-badges {
        grid-template-columns: 1fr;
    }
}
</style>
