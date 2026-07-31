<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title">
                <i class="fa-solid fa-hive"></i>
                Калькуляторы еды (Собери сам)
            </h3>

            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Включайте и отключайте конструкторы блюд. Пользователи смогут собирать свои комбинации из ингредиентов.
            </div>

            <div class="cards-grid">
                <div
                    v-for="(calc, key) in form"
                    :key="key"
                    class="feature-card"
                    :class="{ 'is-disabled': !calc.is_visible }"
                    :style="{ backgroundImage: calc.gradient }"
                >
                    <div class="card-emoji">{{ calc.emoji }}</div>
                    <div class="card-info">
                        <h4>{{ calc.title }}</h4>
                        <p>{{ calc.description }}</p>
                        <div class="card-meta">
                            <span>🥘 {{ calc.ingredientsCount }}</span>
                            <span>⏱️ {{ calc.time }}</span>
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" v-model="calc.is_visible" @change="emitDirty">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div v-if="!form || Object.keys(form).length === 0" class="empty-state">
                <i class="fa-solid fa-hive"></i>
                <p>Калькуляторы пока не настроены</p>
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
    name: 'TabCalculators',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'calculators');
        },

        onSubmit() {
            this.$emit('save', this.form);
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
    padding: 60px 20px;
    color: var(--text-muted, #6b7280);
    text-align: center;

    i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }

    p {
        margin: 0;
        font-size: 0.95rem;
    }
}
</style>
