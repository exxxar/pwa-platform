<template>
    <form @submit.prevent="onSubmit" class="settings-form">

        <!-- Включение функции -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-brands fa-telegram"></i> Уведомления в Telegram</h3>
            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Включите эту опцию, чтобы получать мгновенные уведомления о новых заказах в ваш Telegram-канал или группу.
            </div>
            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Отправлять уведомления о заказах</h4>
                    <p>Новые заказы будут дублироваться в указанный канал</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.enabled" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- Настройки бота -->
        <div class="form-section" v-if="form.enabled">
            <h3 class="section-title"><i class="fa-solid fa-robot"></i> Параметры подключения</h3>

            <div class="form-grid">
                <div class="form-field full-width">
                    <label>
                        <i class="fa-solid fa-key"></i> Токен бота (Bot Token)
                    </label>
                    <input
                        type="text"
                        v-model="form.token"
                        placeholder="Например: 123456789:AAHxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        @input="emitDirty"
                    >
                    <span class="field-hint">Получите у @BotFather при создании бота</span>
                </div>

                <div class="form-field full-width">
                    <label>
                        <i class="fa-solid fa-hashtag"></i> ID канала или группы (Chat ID)
                    </label>
                    <input
                        type="text"
                        v-model="form.channel_id"
                        placeholder="Например: -1001234567890"
                        @input="emitDirty"
                    >
                    <span class="field-hint">Для групп и каналов ID всегда начинается с <b>-100</b>. Узнайте его через бота @getidsbot</span>
                </div>

                <div class="form-field full-width">
                    <label>
                        <i class="fa-solid fa-layer-group"></i> ID темы (Thread ID) <span style="color: var(--text-muted); font-weight: 400;">(необязательно)</span>
                    </label>
                    <input
                        type="text"
                        v-model="form.thread_id"
                        placeholder="Например: 42"
                        @input="emitDirty"
                    >
                    <span class="field-hint">Заполните только если в вашей группе включены "Темы" (Topics) и вы хотите, чтобы заказы падали в конкретную тему.</span>
                </div>
            </div>
        </div>

        <!-- Кнопка сохранения -->
        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить настройки Telegram' }}</span>
        </button>
    </form>
</template>

<script>
export default {
    name: 'TabTelegram',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'telegram');
        },

        onSubmit() {
            this.$emit('save', this.form);
        },
    },
};
</script>

<style scoped>
/* Стили не нужны, компонент использует глобальные классы из родительского файла (form-section, form-grid и т.д.) */
</style>
