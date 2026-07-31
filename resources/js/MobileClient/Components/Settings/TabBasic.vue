<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-building"></i> Информация о заведении</h3>
            <div class="form-grid">
                <div class="form-field full-width">
                    <label>Название</label>
                    <input type="text" v-model="form.title" maxlength="255" @input="emitDirty">
                </div>
                <div class="form-field full-width">
                    <label>Описание</label>
                    <textarea v-model="form.description" maxlength="512" rows="4" @input="emitDirty"></textarea>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-address-book"></i> Контакты</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label><i class="fa-solid fa-phone"></i> Телефон</label>
                    <input type="tel" v-model="form.phones[0]" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label><i class="fa-solid fa-envelope"></i> Email</label>
                    <input type="email" v-model="form.email" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label><i class="fa-brands fa-instagram"></i> Instagram</label>
                    <input type="text" v-model="form.links.inst" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label><i class="fa-brands fa-vk"></i> ВКонтакте</label>
                    <input type="text" v-model="form.links.vk" @input="emitDirty">
                </div>
                <div class="form-field full-width">
                    <label><i class="fa-solid fa-globe"></i> Сайт</label>
                    <input type="url" v-model="form.links.site" @input="emitDirty">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-clock"></i> График работы</h3>
            <div class="schedule-list">
                <div v-for="(day, i) in form.schedule" :key="i" class="schedule-day" :class="{ 'is-closed': day.closed }">
                    <div class="schedule-day-name">{{ day.day }}</div>
                    <label class="toggle-switch">
                        <input type="checkbox" v-model="day.closed" @change="emitDirty">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">{{ day.closed ? 'Закрыто' : 'Открыто' }}</span>
                    </label>
                    <template v-if="!day.closed">
                        <div class="time-inputs">
                            <input type="time" v-model="day.start_at" @input="emitDirty">
                            <span>—</span>
                            <input type="time" v-model="day.end_at" @input="emitDirty">
                        </div>
                    </template>
                    <template v-else>
                        <input type="text" v-model="day.closed_comment" placeholder="Причина выходного" class="closed-comment-input" @input="emitDirty">
                    </template>
                </div>
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
    name: 'TabBasic',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'company');
        },

        onSubmit() {
            const payload = {
                name: this.form.title,
                description: this.form.description,
                meta: {
                    company: {
                        phones: this.form.phones,
                        email: this.form.email,
                        links: this.form.links,
                        schedule: this.form.schedule
                    }
                }
            };
            this.$emit('save', payload);
        },
    },
};
</script>
