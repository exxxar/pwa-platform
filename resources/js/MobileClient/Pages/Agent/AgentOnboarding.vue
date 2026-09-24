<template>
    <div class="onboarding-page">
        <div class="onboarding-card">
            <div class="onboarding-header">
                <div class="header-icon">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <h2>Настройка агентского профиля</h2>
                <p>Заполните реквизиты, чтобы начать получать заказы, выставлять счета и выводить заработок</p>
            </div>

            <form @submit.prevent="submitForm" class="onboarding-form">
                <!-- 1. Юридический статус -->
                <div class="form-section">
                    <h4 class="section-title">Юридический статус</h4>
                    <div class="legal-types">
                        <div class="legal-card" :class="{ 'is-active': form.legal_type === 'self_employed' }" @click="form.legal_type = 'self_employed'">
                            <i class="fa-solid fa-user"></i>
                            <span>Самозанятый</span>
                        </div>
                        <div class="legal-card" :class="{ 'is-active': form.legal_type === 'ip' }" @click="form.legal_type = 'ip'">
                            <i class="fa-solid fa-briefcase"></i>
                            <span>ИП</span>
                        </div>
                        <div class="legal-card" :class="{ 'is-active': form.legal_type === 'legal_entity' }" @click="form.legal_type = 'legal_entity'">
                            <i class="fa-solid fa-building"></i>
                            <span>Юр. лицо</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>ИНН <span class="required">*</span></label>
                            <input type="text" v-model="form.inn" class="form-input" placeholder="10 или 12 цифр" required maxlength="12">
                        </div>
                        <div class="form-group" v-if="form.legal_type !== 'self_employed'">
                            <label>{{ form.legal_type === 'ip' ? 'ОГРНИП' : 'ОГРН' }} <span class="required">*</span></label>
                            <input type="text" v-model="form.ogrn" class="form-input" placeholder="13 или 15 цифр" required maxlength="15">
                        </div>
                    </div>
                </div>

                <!-- 2. Банковские реквизиты -->
                <div class="form-section">
                    <h4 class="section-title">Куда выводить заработок</h4>
                    <div class="form-group">
                        <label>Расчетный счет <span class="required">*</span></label>
                        <input type="text" v-model="form.bank_account" class="form-input" placeholder="20 цифр" required maxlength="20">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>БИК банка <span class="required">*</span></label>
                            <input type="text" v-model="form.bik" class="form-input" placeholder="9 цифр" required maxlength="9">
                        </div>
                        <div class="form-group">
                            <label>Название банка <span class="required">*</span></label>
                            <input type="text" v-model="form.bank_name" class="form-input" placeholder="Например: ПАО Сбербанк" required>
                        </div>
                    </div>
                </div>


                <div class="form-actions">
                    <!-- 🆕 Подсказка валидации (показываем, если форма не валидна и пользователь уже начал вводить данные) -->
                    <div v-if="!isFormValid && (form.inn || form.bank_account)" class="validation-hint mb-3">
                        {{ validationHint }}
                    </div>

                    <button type="submit" class="btn-primary-modern btn-large" :disabled="isLoading || !isFormValid">
                        <i v-if="isLoading" class="fa-solid fa-circle-notch fa-spin"></i>
                        <span v-else><i class="fa-solid fa-rocket"></i> Завершить регистрацию</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAgent } from '@/MobileClient/composables/useAgent.js';

const router = useRouter();
const { createProfile, isLoading, notify } = useAgent();

const form = ref({
    legal_type: 'self_employed',
    inn: '',
    ogrn: '',
    bank_account: '',
    bik: '',
    bank_name: ''
});

// 🆕 УЛУЧШЕННАЯ ВАЛИДАЦИЯ: убираем пробелы и проверяем точные длины
const isFormValid = computed(() => {
    const inn = form.value.inn.replace(/\s/g, '');
    const bankAccount = form.value.bank_account.replace(/\s/g, '');
    const bik = form.value.bik.replace(/\s/g, '');
    const bankName = form.value.bank_name.trim();
    const ogrn = form.value.ogrn.replace(/\s/g, '');

    // Проверки длин (ИНН: 10 или 12, Счет: ровно 20, БИК: ровно 9)
    const isInnValid = inn.length === 10 || inn.length === 12;
    const isAccountValid = bankAccount.length === 20;
    const isBikValid = bik.length === 9;
    const isBankNameValid = bankName.length >= 4;

    let isValid = isInnValid && isAccountValid && isBikValid && isBankNameValid;

    if (form.value.legal_type !== 'self_employed') {
        // ОГРН: 13, ОГРНИП: 15
        const isOgrnValid = ogrn.length === 13 || ogrn.length === 15;
        isValid = isValid && isOgrnValid;
    }

    return isValid;
});

// 🆕 ПОДСКАЗКА: чтобы пользователь понимал, почему кнопка серая
const validationHint = computed(() => {
    const hints = [];
    const inn = form.value.inn.replace(/\s/g, '');
    if (inn.length > 0 && inn.length !== 10 && inn.length !== 12) hints.push(`ИНН: нужно 10 или 12 цифр (сейчас ${inn.length})`);

    const acc = form.value.bank_account.replace(/\s/g, '');
    if (acc.length > 0 && acc.length !== 20) hints.push(`Счет: нужно ровно 20 цифр (сейчас ${acc.length})`);

    const bik = form.value.bik.replace(/\s/g, '');
    if (bik.length > 0 && bik.length !== 9) hints.push(`БИК: нужно ровно 9 цифр (сейчас ${bik.length})`);

    if (form.value.legal_type !== 'self_employed') {
        const ogrn = form.value.ogrn.replace(/\s/g, '');
        if (ogrn.length > 0 && ogrn.length !== 13 && ogrn.length !== 15) hints.push(`ОГРН(ИП): нужно 13 или 15 цифр`);
    }

    return hints.length > 0 ? '⚠️ ' + hints.join('; ') : 'Все данные заполнены верно';
});

const submitForm = async () => {
    if (!isFormValid.value) return; // Дополнительная защита

    try {
        // Очищаем данные от пробелов перед отправкой на бэк
        const payload = {
            ...form.value,
            inn: form.value.inn.replace(/\s/g, ''),
            bank_account: form.value.bank_account.replace(/\s/g, ''),
            bik: form.value.bik.replace(/\s/g, ''),
            ogrn: form.value.ogrn.replace(/\s/g, ''),
        };

        await createProfile(payload);
        notify('success', 'Профиль успешно создан! Перенаправляем в панель...');

        setTimeout(() => {
            router.push({ name: 'AgentDashboard' });
        }, 1000);
    } catch (e) {
        console.error('Ошибка создания профиля:', e);
        notify('error', e.message || 'Проверьте правильность введенных данных');
    }
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

$primary: #3b82f6;
$primary-dark: #2563eb;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$card-bg: #ffffff;

.onboarding-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.onboarding-card {
    background: $card-bg;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
    width: 100%;
    max-width: 650px;
    overflow: hidden;
}

.onboarding-header {
    text-align: center;
    padding: 40px 32px 24px;
    background: linear-gradient(135deg, rgba($primary, 0.05) 0%, rgba($primary, 0.02) 100%);
    border-bottom: 1px solid $border;

    .header-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 16px;
        box-shadow: 0 8px 20px rgba($primary, 0.3);
    }

    h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: $text;
        margin: 0 0 8px;
    }

    p {
        font-size: 0.95rem;
        color: $text-muted;
        margin: 0;
        max-width: 400px;
        margin-inline: auto;
    }
}

.onboarding-form {
    padding: 32px;
}

.form-section {
    margin-bottom: 32px;
}

.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;

    &::before {
        content: '';
        display: block;
        width: 4px;
        height: 16px;
        background: $primary;
        border-radius: 2px;
    }
}

.required {
    color: #ef4444;
}

.legal-types {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.legal-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 8px;
    border: 2px solid $border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    color: $text-muted;

    i { font-size: 1.3rem; }
    span { font-size: 0.8rem; font-weight: 600; text-align: center; }

    &:hover { border-color: $primary; color: $primary; }

    &.is-active {
        border-color: $primary;
        background: rgba($primary, 0.05);
        color: $primary;
        box-shadow: 0 4px 12px rgba($primary, 0.1);
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group {
    margin-bottom: 16px;

    label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;
    }
}

.form-input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }
}

.form-actions {
    margin-top: 32px;
    text-align: center;
}

.btn-primary-modern {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 32px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    border: none;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
    box-shadow: 0 4px 12px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba($primary, 0.4);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
}

@media (max-width: 640px) {
    .legal-types { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .onboarding-header { padding: 32px 20px 20px; }
    .onboarding-form { padding: 24px 20px; }
}

.validation-hint {
    font-size: 0.85rem;
    color: #f59e0b; /* Оранжевый цвет внимания */
    background: rgba(245, 158, 11, 0.1);
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid rgba(245, 158, 11, 0.3);
    text-align: center;
    animation: fadeIn 0.3s ease;
}
</style>
