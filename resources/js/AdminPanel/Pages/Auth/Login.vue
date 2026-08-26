<script setup>
import { useForm, Head } from '@inertiajs/vue3'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Вход в систему" />

    <!-- Больше никаких оберток, просто форма -->
    <form @submit.prevent="submit" class="login-form">
        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                class="form-input"
                :class="{ 'has-error': form.errors.email }"
                placeholder="admin@example.com"
                autocomplete="email"
                required
            />
            <span v-if="form.errors.email" class="error-message">{{ form.errors.email }}</span>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Пароль</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                class="form-input"
                :class="{ 'has-error': form.errors.password }"
                placeholder="••••••••"
                autocomplete="current-password"
                required
            />
            <span v-if="form.errors.password" class="error-message">{{ form.errors.password }}</span>
        </div>

        <div class="form-group checkbox-group">
            <label class="checkbox-label">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="checkbox-input"
                />
                <span class="checkbox-text">Запомнить меня</span>
            </label>
        </div>

        <button
            type="submit"
            class="submit-btn"
            :disabled="form.processing"
        >
            <span v-if="form.processing" class="spinner"></span>
            <span v-else>Войти</span>
        </button>
    </form>
</template>



<style scoped>
.login-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
}

.form-input {
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    outline: none;
}

.form-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input.has-error {
    border-color: #f56565;
}

.form-input.has-error:focus {
    box-shadow: 0 0 0 3px rgba(245, 101, 101, 0.1);
}

.error-message {
    font-size: 12px;
    color: #f56565;
}

.checkbox-group {
    flex-direction: row;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-text {
    font-size: 14px;
    color: #4a5568;
}

.submit-btn {
    padding: 14px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
