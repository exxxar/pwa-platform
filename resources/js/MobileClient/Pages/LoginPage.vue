<template>
    <div class="auth-page min-vh-100 d-flex flex-column justify-content-center px-3 py-4">

        <!-- Логотип и приветствие -->
        <div class="text-center mb-4 fade-in">
            <div class="auth-logo mx-auto mb-3 d-flex align-items-center justify-content-center">
                <i class="fa-solid fa-store fa-2x text-white"></i>
            </div>
            <h2 class="fw-bold mb-1">Добро пожаловать!</h2>
            <p class="text-muted">Войдите в систему</p>
        </div>

        <!-- Карточка формы -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden fade-in" style="animation-delay: 0.1s;">
            <div class="card-body p-4 pt-4">

                <!-- Сообщения об ошибках -->
                <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center py-2 mb-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <small>{{ errorMessage }}</small>
                </div>

                <!-- ФОРМА ВХОДА -->
                <form @submit.prevent="submitForm">
                    <div class="form-floating mb-3">
                        <input
                            v-model="form.identifier"
                            type="text"
                            class="form-control rounded-3"
                            id="loginId"
                            placeholder="Телефон или Email"
                            required
                            autocomplete="username"
                        >
                        <label for="loginId"><i class="fa-solid fa-user me-1"></i> Телефон или Email</label>
                    </div>

                    <div class="form-floating position-relative mb-3">
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="form-control rounded-3 pe-5"
                            id="loginPassword"
                            placeholder="Пароль"
                            required
                            autocomplete="current-password"
                        >
                        <label for="loginPassword"><i class="fa-solid fa-lock me-1"></i> Пароль</label>
                        <button
                            type="button"
                            class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted pe-3"
                            @click="showPassword = !showPassword"
                            style="z-index: 5; text-decoration: none;"
                            tabindex="-1"
                        >
                            <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>

                    <div class="d-flex justify-content-end mb-4">
                        <a href="#" class="text-decoration-none small text-muted">Забыли пароль?</a>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-warning btn-lg w-100 rounded-3 fw-bold text-white shadow-sm"
                        :disabled="isLoading"
                    >
                        <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                        {{ isLoading ? 'Входим...' : 'Войти' }}
                    </button>
                </form>

            </div>
        </div>



    </div>
</template>

<script>
import { useAuth } from '@/MobileClient/Composables/useAuth.js';

export default {
    name: "AuthPage",

    setup() {
        const auth = useAuth();
        return { ...auth };
    },

    data() {
        return {
            showPassword: false,
            form: {
                identifier: '',
                password: '',
            }
        };
    },

    methods: {
        async submitForm() {
            this.clearError();

            try {
                await this.login({
                    identifier: this.form.identifier,
                    password: this.form.password
                });

                // Редирект на /pwa после успешного входа
               window.location.href='/pwa#/catalog';

            } catch (error) {
                console.error('Auth error:', error);
                // Ошибка уже записана в this.errorMessage внутри стора
            }
        }
    }
};
</script>

<style scoped>
/* Фон страницы с легким градиентом */
.auth-page {
    background: linear-gradient(135deg, #fffdf8 0%, #fff3df 100%);
}

/* Логотип */
.auth-logo {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff7a00 0%, #ffb300 100%);
    box-shadow: 0 8px 20px rgba(255, 138, 0, 0.25);
}

/* Кастомизация фокуса инпутов под оранжевую тему */
.form-control:focus {
    border-color: #ff8a00;
    box-shadow: 0 0 0 0.25rem rgba(255, 138, 0, 0.15);
}

/* Стили для иконки глаза внутри form-floating */
.form-floating .btn-link {
    color: #6c757d;
    transition: color 0.2s;
}

.form-floating .btn-link:hover {
    color: #ff8a00;
}

/* Общая анимация появления */
.fade-in {
    opacity: 0;
    animation: fadeUp 0.6s ease-out forwards;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
