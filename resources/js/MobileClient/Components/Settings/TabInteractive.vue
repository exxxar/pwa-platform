<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title">
                <i class="fa-solid fa-mug-hot"></i>
                Кофе в подарок
            </h3>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Бонусная программа кофе</h4>
                    <p>Система отметок за каждую покупку кофе</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.enabled" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <template v-if="form.enabled">
                <div class="form-field">
                    <label>Необходимое количество покупок</label>
                    <input type="number" v-model="form.max" min="1" @input="emitDirty">
                </div>
                <div class="form-field full-width">
                    <label>Правила программы</label>
                    <textarea v-model="form.rules" rows="6" maxlength="4000" @input="emitDirty"></textarea>
                </div>
            </template>

            <div v-if="!form.enabled" class="empty-state">
                <i class="fa-solid fa-mug-hot"></i>
                <p>Программа отключена. Включите её, чтобы настроить параметры.</p>
            </div>
        </div>

        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить' }}</span>
        </button>
    </form>
</template>

<script>
export default {
    name: 'TabInteractive',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'interactive');
        },

        onSubmit() {
            // Оборачиваем в объект { coffee: ... }, как ожидает бэкенд
            this.$emit('save', { coffee: this.form });
        },
    },
};
</script>

<style lang="scss" scoped>
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: var(--text-muted, #6b7280);
    text-align: center;
    background: var(--bs-secondary-bg, #f8f9fa);
    border-radius: 12px;
    border: 1px dashed var(--border, #e5e7eb);
    margin-top: 16px;

    i {
        font-size: 2.5rem;
        margin-bottom: 12px;
        opacity: 0.3;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
    }
}
</style>
