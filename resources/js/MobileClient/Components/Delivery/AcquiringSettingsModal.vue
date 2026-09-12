<template>
    <transition name="modal-fade">
        <div v-if="isVisible" class="modal-overlay" @click.self="close">
            <div class="modal-container">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-building-columns text-primary me-2"></i> Выбор банка</h3>
                    <button class="modal-close" @click="close"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <p class="modal-description">
                        Выберите банк, через который будет приниматься оплата. Активируйте нужный банк и укажите его реквизиты.
                    </p>

                    <div class="banks-list">
                        <div
                            v-for="(bankConfig, bankKey) in localForm.sbp_banks"
                            :key="bankKey"
                            class="bank-card"
                            :class="{ 'is-active': bankConfig.enabled }"
                        >
                            <!-- Шапка банка с переключателем -->
                            <div class="bank-header">
                                <div class="bank-info">
                                    <div class="bank-icon" :class="bankKey">
                                        <i :class="getBankIcon(bankKey)"></i>
                                    </div>
                                    <div class="bank-name">{{ getBankName(bankKey) }}</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" v-model="bankConfig.enabled">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <!-- Поля настройки (только если банк включен) -->
                            <transition name="slide-down">
                                <div v-if="bankConfig.enabled" class="bank-fields">
                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label>Ключ терминала</label>
                                            <input type="text" v-model="bankConfig.terminal_key" placeholder="Terminal Key">
                                        </div>
                                        <div class="form-field">
                                            <label>Пароль терминала</label>
                                            <input type="password" v-model="bankConfig.terminal_password" placeholder="••••••">
                                        </div>
                                        <div class="form-field">
                                            <label>Налогообложение</label>
                                            <select v-model="bankConfig.tax">
                                                <option value="osn">ОСН</option>
                                                <option value="usn_income">УСН (доходы)</option>
                                                <option value="usn_income_outcome">УСН (дох.-расх.)</option>
                                                <option value="patent">Патент</option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label>НДС</label>
                                            <select v-model="bankConfig.vat">
                                                <option value="none">Нет</option>
                                                <option value="vat0">0%</option>
                                                <option value="vat10">10%</option>
                                                <option value="vat20">20%</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="btn-test"
                                        @click="testPayment(bankKey)"
                                        :disabled="isTestingPayment && testingBank === bankKey"
                                    >
                                        <i v-if="isTestingPayment && testingBank === bankKey" class="fa-solid fa-spinner fa-spin"></i>
                                        <i v-else class="fa-solid fa-vial"></i>
                                        <span>Тестовая оплата (100 ₽)</span>
                                    </button>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" @click="close">Отмена</button>
                    <button type="button" class="btn-primary" @click="save" :disabled="isSaving">
                        <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                        <span v-else><i class="fa-solid fa-check"></i> Сохранить</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: 'AcquiringSettingsModal',

    props: {
        isVisible: { type: Boolean, default: false },
        initialData: { type: Object, default: () => ({}) }
    },

    emits: ['close', 'save', 'test-payment'],

    data() {
        return {
            isSaving: false,
            isTestingPayment: false,
            testingBank: null,
            localForm: {
                sbp_banks: {
                    tinkoff: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'vat20' },
                    sber: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'vat20' },
                    vtb: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'vat20' },
                    yandex: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'vat20' },
                }
            }
        };
    },

    watch: {
        isVisible(newVal) {
            if (newVal && this.initialData?.sbp_banks) {
                // Аккуратно объединяем: берем дефолтную структуру и поверх накладываем реальные данные
                this.localForm.sbp_banks = {
                    ...this.localForm.sbp_banks,
                    ...this.initialData.sbp_banks
                };
            }
        }
    },

    methods: {
        close() {
            this.$emit('close');
        },

        save() {
            this.isSaving = true;
            this.$emit('save', { sbp_banks: this.localForm.sbp_banks });
            setTimeout(() => { this.isSaving = false; }, 500);
        },

        getBankName(bankKey) {
            const names = {
                'tinkoff': 'Т-Банк',
                'sber': 'Сбербанк',
                'vtb': 'ВТБ',
                'yandex': 'ЮKassa'
            };
            return names[bankKey] || bankKey;
        },

        getBankIcon(bankKey) {
            const icons = {
                'tinkoff': 'fa-solid fa-t',
                'sber': 'fa-solid fa-s',
                'vtb': 'fa-solid fa-v',
                'yandex': 'fa-solid fa-y'
            };
            return icons[bankKey] || 'fa-solid fa-building-columns';
        },

        testPayment(bankKey) {
            this.isTestingPayment = true;
            this.testingBank = bankKey;
            this.$emit('test-payment', { bankKey, amount: 100 });

            setTimeout(() => {
                this.isTestingPayment = false;
                this.testingBank = null;
            }, 3000);
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;
$success: #10b981;

.modal-overlay {
    position: fixed; inset: 0; background: rgba(31, 41, 55, 0.6); backdrop-filter: blur(4px);
    z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
}

.modal-container {
    background: $card-bg; border-radius: 20px; width: 100%; max-width: 500px; max-height: 90vh;
    display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex; align-items: center; justify-content: space-between; padding: 18px 24px;
    border-bottom: 1px solid $border;
    h3 { font-size: 1.1rem; font-weight: 700; margin: 0; color: $text; display: flex; align-items: center; }
}

.modal-close {
    width: 36px; height: 36px; border-radius: 50%; background: $bg; border: none; color: $text-muted;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    &:hover { background: #ef4444; color: white; transform: rotate(90deg); }
}

.modal-body {
    padding: 20px 24px; overflow-y: auto; flex: 1;
}

.modal-description {
    font-size: 0.85rem; color: $text-muted; margin: 0 0 20px; line-height: 1.5;
}

.banks-list {
    display: flex; flex-direction: column; gap: 12px;
}

.bank-card {
    border: 1px solid $border; border-radius: 12px; padding: 16px; transition: all 0.2s;
    &.is-active { border-color: $primary; background: rgba($primary, 0.03); }
}

.bank-header {
    display: flex; align-items: center; justify-content: space-between;
}

.bank-info {
    display: flex; align-items: center; gap: 12px;
}

.bank-icon {
    width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.1rem; color: white;
    &.tinkoff { background: #ffdd2d; color: #333; }
    &.sber { background: #21a038; }
    &.vtb { background: #003366; }
    &.yandex { background: #000000; }
}

.bank-name {
    font-weight: 700; font-size: 1rem; color: $text;
}

.toggle-switch {
    position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0;
    input { opacity: 0; width: 0; height: 0;
        &:checked + .toggle-slider { background: $primary;
            &::before { transform: translateX(22px); }
        }
    }
}

.toggle-slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background: $border; transition: 0.3s; border-radius: 26px;
    &::before {
        position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px;
        background: white; transition: 0.3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
}

.bank-fields {
    margin-top: 16px; padding-top: 16px; border-top: 1px solid $border;
}

.form-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;
}

.form-field {
    display: flex; flex-direction: column; gap: 4px;
    label { font-size: 0.8rem; font-weight: 600; color: $text-muted; }
    input, select {
        padding: 8px 12px; border: 1px solid $border; border-radius: 8px; font-size: 0.85rem;
        background: $bg; transition: all 0.2s;
        &:focus { outline: none; border-color: $primary; box-shadow: 0 0 0 2px rgba($primary, 0.1); }
    }
}

.btn-test {
    display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
    padding: 10px; background: rgba($success, 0.1); color: $success; border: 1px solid rgba($success, 0.2);
    border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;
    &:hover:not(:disabled) { background: $success; color: white; }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

.modal-footer {
    display: flex; gap: 10px; padding: 16px 24px; border-top: 1px solid $border; background: $bg;
}

.btn-primary, .btn-secondary {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px; border-radius: 10px; font-size: 0.9rem; font-weight: 600;
    border: none; cursor: pointer; transition: all 0.2s;
    &:disabled { opacity: 0.5; cursor: not-allowed; }
}

.btn-primary { background: $primary; color: white; &:hover:not(:disabled) { background: $primary-dark; } }
.btn-secondary { background: $card-bg; color: $text; border: 1px solid $border; &:hover:not(:disabled) { background: $border; } }

// Анимация раскрытия полей банка
.slide-down-enter-active, .slide-down-leave-active {
    transition: all 0.3s ease;
    max-height: 300px; overflow: hidden;
}
.slide-down-enter-from, .slide-down-leave-to {
    max-height: 0; opacity: 0;
}

@media (max-width: 640px) {
    .modal-container { max-width: 100%; max-height: 95vh; border-radius: 20px 20px 0 0; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>
