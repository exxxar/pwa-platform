<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title">
                <i class="fa-solid fa-bars"></i>
                Видимость пунктов бокового меню
            </h3>

            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Включайте и отключайте пункты бокового меню. Отключённые пункты не будут отображаться пользователям.
            </div>

            <div class="toggle-list">
                <div v-for="(item, key) in form" :key="key" class="toggle-row">
                    <div class="toggle-info">
                        <h4>
                            <i :class="'fa-solid ' + item.icon"></i>
                            {{ item.title }}
                        </h4>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" v-model="item.is_visible" @change="emitDirty">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div v-if="!form || Object.keys(form).length === 0" class="empty-state">
                <i class="fa-solid fa-bars"></i>
                <p>Пункты меню пока не настроены</p>
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
    name: 'TabMenu',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'sidebar-menu');
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
