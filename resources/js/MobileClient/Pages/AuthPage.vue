<template>
    <div class="auth-page min-vh-100 d-flex flex-column justify-content-center px-3 py-4">

        <!-- Логотип и приветствие -->
        <div class="text-center mb-4 fade-in">
            <div class="auth-logo mx-auto mb-3 d-flex align-items-center justify-content-center">
                <i class="fa-solid fa-store fa-2x text-white"></i>
            </div>
            <h2 class="fw-bold mb-1">Добро пожаловать!</h2>
            <p class="text-muted">Войдите, чтобы управлять заказами</p>
        </div>

        <!-- Карточка формы -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden fade-in" style="animation-delay: 0.1s;">

            <!-- Переключатель Вход / Регистрация -->
            <div class="d-flex bg-light p-1 m-3 mb-0 rounded-3">
                <button
                    class="flex-grow-1 btn py-2 rounded-3 fw-semibold transition-all"
                    :class="mode === 'login' ? 'btn-warning text-white shadow-sm' : 'btn-light text-muted'"
                    @click="switchMode('login')"
                >
                    Вход
                </button>
                <button
                    class="flex-grow-1 btn py-2 rounded-3 fw-semibold transition-all"
                    :class="mode === 'register' ? 'btn-warning text-white shadow-sm' : 'btn-light text-muted'"
                    @click="switchMode('register')"
                >
                    Регистрация
                </button>
            </div>

            <div class="card-body p-4 pt-3">

                <!-- Сообщения об ошибках -->
                <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center py-2 mb-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <small>{{ errorMessage }}</small>
                </div>

                <!-- Анимация переключения форм -->
                <transition name="fade" mode="out-in">

                    <!-- ФОРМА ВХОДА -->
                    <form v-if="mode === 'login'" key="login" @submit.prevent="submitForm">
                        <div class="form-floating mb-3">
                            <input
                                v-model="form.identifier"
                                type="text"
                                class="form-control rounded-3"
                                id="loginId"
                                placeholder="Телефон или Email"
                                required
                            >
                            <label for="loginId"><i class="fa-solid fa-user me-1"></i> Телефон или Email</label>
                        </div>

                        <div class="form-floating position-relative mb-3">
                            <input
                                v-model="form.password"
                                :type="showLoginPassword ? 'text' : 'password'"
                                class="form-control rounded-3 pe-5"
                                id="loginPassword"
                                placeholder="Пароль"
                                required
                            >
                            <label for="loginPassword"><i class="fa-solid fa-lock me-1"></i> Пароль</label>
                            <button
                                type="button"
                                class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted pe-3"
                                @click="showLoginPassword = !showLoginPassword"
                                style="z-index: 5; text-decoration: none;"
                                tabindex="-1"
                            >
                                <i :class="showLoginPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
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

                    <!-- ФОРМА РЕГИСТРАЦИИ -->
                    <form v-else key="register" @submit.prevent="submitForm">
                        <div class="form-floating mb-3">
                            <input
                                v-model="form.name"
                                type="text"
                                class="form-control rounded-3"
                                id="regName"
                                placeholder="Ваше имя"
                                required
                            >
                            <label for="regName"><i class="fa-solid fa-signature me-1"></i> Ваше имя</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="form-control rounded-3"
                                id="regPhone"
                                placeholder="+7 (999) 000-00-00"
                                required
                            >
                            <label for="regPhone"><i class="fa-solid fa-phone me-1"></i> Телефон</label>
                        </div>

                        <div class="form-floating position-relative mb-3">
                            <input
                                v-model="form.password"
                                :type="showRegisterPassword ? 'text' : 'password'"
                                class="form-control rounded-3 pe-5"
                                id="regPassword"
                                placeholder="Пароль"
                                minlength="6"
                                required
                            >
                            <label for="regPassword"><i class="fa-solid fa-lock me-1"></i> Пароль</label>
                            <button
                                type="button"
                                class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted pe-3"
                                @click="showRegisterPassword = !showRegisterPassword"
                                style="z-index: 5; text-decoration: none;"
                                tabindex="-1"
                            >
                                <i :class="showRegisterPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </div>

                        <div class="form-floating position-relative mb-3">
                            <input
                                v-model="form.password_confirm"
                                :type="showRegisterPassword ? 'text' : 'password'"
                                class="form-control rounded-3 pe-5"
                                id="regPasswordConfirm"
                                placeholder="Подтвердите пароль"
                                minlength="6"
                                required
                                :class="{ 'is-invalid': form.password_confirm && form.password !== form.password_confirm }"
                            >
                            <label for="regPasswordConfirm"><i class="fa-solid fa-shield-halved me-1"></i> Подтвердите пароль</label>
                            <button
                                type="button"
                                class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted pe-3"
                                @click="showRegisterPassword = !showRegisterPassword"
                                style="z-index: 5; text-decoration: none;"
                                tabindex="-1"
                            >
                                <i :class="showRegisterPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                            <div class="invalid-feedback" v-if="form.password_confirm && form.password !== form.password_confirm">
                                Пароли не совпадают
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm rounded-3"
                                @click="generatePassword"
                            >
                                <i class="fa-solid fa-wand-magic-sparkles me-2"></i>
                                Сгенерировать надежный пароль
                            </button>
                        </div>

                        <!-- 🆕 Блок обязательного согласия со всеми документами -->
                        <div class="agreement-box mb-4 p-3 bg-light rounded-3 border">
                            <div class="form-check">
                                <input
                                    class="form-check-input mt-1"
                                    type="checkbox"
                                    id="agreeAll"
                                    v-model="form.agreeToPolicies"
                                    required
                                >
                                <label class="form-check-label small text-muted lh-sm" for="agreeAll">
                                    Я принимаю и соглашаюсь с
                                    <router-link :to="{ name: 'PrivacyPolicy' }" class="text-primary fw-semibold" target="_blank">Политикой конфиденциальности</router-link>,
                                    <router-link :to="{ name: 'TermsOfService' }" class="text-primary fw-semibold" target="_blank">Условиями использования</router-link> и
                                    <router-link :to="{ name: 'CookiePolicy' }" class="text-primary fw-semibold" target="_blank">Политикой cookie</router-link>.
                                </label>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-warning btn-lg w-100 rounded-3 fw-bold text-white shadow-sm"
                            :disabled="isLoading"
                        >
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                            {{ isLoading ? 'Создаем аккаунт...' : 'Зарегистрироваться' }}
                        </button>
                    </form>

                </transition>

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
            mode: 'login',
            showLoginPassword: false,
            showRegisterPassword: false,
            form: {
                identifier: '',
                name: '',
                phone: '',
                password: '',
                password_confirm: '',
                agreeToPolicies: false, // 🆕 Новое поле для согласия
            }
        };
    },

    methods: {
        switchMode(newMode) {
            this.mode = newMode;
            this.clearError();
            this.showLoginPassword = false;
            this.showRegisterPassword = false;
            this.form.agreeToPolicies = false; // Сбрасываем согласие при переключении
        },

        generatePassword() {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let pass = "";
            for (let i = 0; i < 12; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.form.password = pass;
            this.form.password_confirm = pass;
            this.showRegisterPassword = true;

            this.$notify?.({
                title: 'Пароль создан',
                text: 'Не забудьте его сохранить!',
                type: 'info'
            });
        },

        async submitForm() {
            this.clearError();

            // 🆕 Строгая валидация для регистрации
            if (this.mode === 'register') {
                if (!this.form.agreeToPolicies) {
                    this.errorMessage = 'Необходимо принять все условия использования';
                    return;
                }
                if (this.form.password !== this.form.password_confirm) {
                    this.errorMessage = 'Пароли не совпадают';
                    return;
                }
                if (this.form.password.length < 6) {
                    this.errorMessage = 'Пароль должен быть не менее 6 символов';
                    return;
                }
            }

            try {
                if (this.mode === 'login') {
                    await this.login({
                        identifier: this.form.identifier,
                        password: this.form.password
                    });
                } else {
                    await this.register({
                        name: this.form.name,
                        phone: this.form.phone,
                        password: this.form.password
                    });
                }

                this.$router.push('/menu');

            } catch (error) {
                console.error('Auth error:', error);
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

/* Плавные переходы для кнопок переключения */
.transition-all {
    transition: all 0.3s ease;
}

/* Анимация переключения форм */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

/* Кастомизация фокуса инпутов под оранжевую тему */
.form-control:focus {
    border-color: #ff8a00;
    box-shadow: 0 0 0 0.25rem rgba(255, 138, 0, 0.15);
}

.form-check-input:checked {
    background-color: #ff8a00;
    border-color: #ff8a00;
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

.auth-page {
    background: linear-gradient(135deg, #fffdf8 0%, #fff3df 100%);
}

.auth-logo {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff7a00 0%, #ffb300 100%);
    box-shadow: 0 8px 20px rgba(255, 138, 0, 0.25);
}

.transition-all {
    transition: all 0.3s ease;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

.form-control:focus {
    border-color: #ff8a00;
    box-shadow: 0 0 0 0.25rem rgba(255, 138, 0, 0.15);
}

.form-check-input:checked {
    background-color: #ff8a00;
    border-color: #ff8a00;
}

.form-floating .btn-link {
    color: #6c757d;
    transition: color 0.2s;
}

.form-floating .btn-link:hover {
    color: #ff8a00;
}

/* 🆕 Стили для блока согласия */
.agreement-box {
    transition: all 0.2s ease;
}

.agreement-box .form-check-input {
    cursor: pointer;
    width: 1.2em;
    height: 1.2em;
    margin-top: 0.15em;
}

.agreement-box .form-check-label {
    cursor: pointer;
    line-height: 1.5;
}

.agreement-box .form-check-label a {
    color: #667eea; /* Ваш primary цвет */
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.agreement-box .form-check-label a:hover {
    color: #5a67d8; /* Ваш primary-dark цвет */
    text-decoration: underline;
}

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
