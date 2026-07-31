<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-coins"></i> Кэшбэк и сгорание баллов</h3>
            <div class="form-field">
                <label>Макс. % списания кэшбэка</label>
                <div class="input-with-suffix">
                    <input type="number" v-model="form.max_cashback_use_percent" min="0" max="100" @input="emitDirty">
                    <span class="input-suffix">%</span>
                </div>
            </div>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed var(--border);">
                <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 16px;">Правила сгорания баллов</h4>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Период сгорания</label>
                        <select v-model="form.expiration_period" @change="emitDirty">
                            <option value="week">1 неделя</option>
                            <option value="month">1 месяц</option>
                            <option value="3_months">3 месяца</option>
                            <option value="6_months">6 месяцев</option>
                            <option value="12_months">12 месяцев</option>
                            <option value="never">Не сгорают</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Процент сгорания</label>
                        <div class="input-with-suffix">
                            <input type="number" v-model="form.expiration_percent" min="0" max="100" @input="emitDirty">
                            <span class="input-suffix">%</span>
                        </div>
                    </div>
                </div>

                <div class="toggle-row" style="margin-top: 16px;">
                    <div class="toggle-info">
                        <h4>Оповещать о сгорании</h4>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" v-model="form.notify_expiration" @change="emitDirty">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div v-if="form.notify_expiration" class="form-field" style="margin-top: 12px;">
                    <label>Оповещать за (дней)</label>
                    <select v-model="form.notify_days_before" @change="emitDirty">
                        <option :value="1">1 день</option>
                        <option :value="2">2 дня</option>
                        <option :value="3">3 дня</option>
                        <option :value="5">5 дней</option>
                        <option :value="7">7 дней</option>
                        <option :value="10">10 дней</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-users"></i> Реферальная программа</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label>Уровень 1</label>
                    <div class="input-with-suffix">
                        <input type="number" v-model="form.level_1" min="0" max="100" @input="emitDirty">
                        <span class="input-suffix">%</span>
                    </div>
                </div>
                <div class="form-field">
                    <label>Уровень 2</label>
                    <div class="input-with-suffix">
                        <input type="number" v-model="form.level_2" min="0" max="100" @input="emitDirty">
                        <span class="input-suffix">%</span>
                    </div>
                </div>
                <div class="form-field">
                    <label>Уровень 3</label>
                    <div class="input-with-suffix">
                        <input type="number" v-model="form.level_3" min="0" max="100" @input="emitDirty">
                        <span class="input-suffix">%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-gift"></i> Подарочный сертификат</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label>Название</label>
                    <input type="text" v-model="extraProps.certificateForm.title" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>Описание</label>
                    <input type="text" v-model="extraProps.certificateForm.description" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>Тип</label>
                    <select v-model="extraProps.certificateForm.type" @change="emitDirty">
                        <option value="cashback">CashBack</option>
                        <option value="discount">Скидка</option>
                        <option value="gift">Подарок</option>
                    </select>
                </div>
                <div class="form-field" v-if="extraProps.certificateForm.type !== 'gift'">
                    <label>{{ extraProps.certificateForm.type === 'cashback' ? 'Сумма, ₽' : 'Процент, %' }}</label>
                    <input type="number" v-model="extraProps.certificateForm.amount" min="0" @input="emitDirty">
                </div>
            </div>
            <div class="toggle-row">
                <div class="toggle-info"><h4>Сертификат активен</h4></div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="extraProps.certificateForm.is_active" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
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
    name: 'TabCashback',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'cashback');
        },

        onSubmit() {
            const payload = {
                ...this.form,
                init_certificate: this.extraProps.certificateForm
            };
            this.$emit('save', payload);
        },
    },
};
</script>
