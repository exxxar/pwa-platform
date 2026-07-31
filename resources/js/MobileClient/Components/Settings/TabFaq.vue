<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-circle-question"></i> Часто задаваемые вопросы (FAQ)</h3>
            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Управляйте списком вопросов. Вы можете скрыть вопрос без удаления или добавить новый.
            </div>

            <div class="dynamic-list">
                <div v-for="(item, index) in form" :key="item.id" class="list-item-card" :class="{ 'is-disabled': !item.is_visible }">
                    <div class="list-item-header">
                        <span class="list-item-badge">Вопрос {{ index + 1 }}</span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <label class="toggle-switch" title="Показывать на сайте">
                                <input type="checkbox" v-model="item.is_visible" @change="emitDirty">
                                <span class="toggle-slider"></span>
                            </label>
                            <button type="button" class="btn-icon-danger" @click="removeFaq(item.id)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-grid" v-if="item.is_visible">
                        <div class="form-field">
                            <label>Иконка (класс FontAwesome)</label>
                            <input type="text" v-model="item.icon" placeholder="fa-solid fa-clock" @input="emitDirty">
                        </div>
                        <div class="form-field full-width">
                            <label>Текст вопроса</label>
                            <input type="text" v-model="item.question" placeholder="Сколько времени занимает доставка?" @input="emitDirty">
                        </div>
                        <div class="form-field full-width">
                            <label>Текст ответа</label>
                            <textarea v-model="item.answer" rows="4" placeholder="Развернутый ответ" @input="emitDirty"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-add-item" @click="addFaq">
                <i class="fa-solid fa-plus"></i> Добавить вопрос
            </button>
        </div>

        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить FAQ' }}</span>
        </button>
    </form>
</template>

<script>
export default {
    name: 'TabFaq',

    props: {
        form: { type: Array, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'faq');
        },

        onSubmit() {
            this.$emit('save', this.form);
        },

        addFaq() {
            this.form.push({
                id: Date.now(),
                icon: 'fa-solid fa-circle-question',
                question: '',
                answer: '',
                is_visible: true
            });
            this.emitDirty();
        },

        removeFaq(id) {
            const index = this.form.findIndex(item => item.id === id);
            if (index !== -1) {
                this.form.splice(index, 1);
                this.emitDirty();
            }
        },
    },
};
</script>
