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
                    @click="mode = 'login'"
                >
                    Вход
                </button>
                <button
                    class="flex-grow-1 btn py-2 rounded-3 fw-semibold transition-all"
                    :class="mode === 'register' ? 'btn-warning text-white shadow-sm' : 'btn-light text-muted'"
                    @click="mode = 'register'"
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

                        <div class="form-floating mb-3">
                            <input
                                v-model="form.password"
                                type="password"
                                class="form-control rounded-3"
                                id="loginPassword"
                                placeholder="Пароль"
                                required
                            >
                            <label for="loginPassword"><i class="fa-solid fa-lock me-1"></i> Пароль</label>
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

                        <div class="form-floating mb-3">
                            <input
                                v-model="form.password"
                                type="password"
                                class="form-control rounded-3"
                                id="regPassword"
                                placeholder="Пароль"
                                minlength="6"
                                required
                            >
                            <label for="regPassword"><i class="fa-solid fa-lock me-1"></i> Пароль (мин. 6 символов)</label>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label small text-muted" for="agreeTerms">
                                Я согласен с <a href="#" class="text-decoration-none">условиями использования</a>
                            </label>
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

                <!-- Разделитель (опционально, для соц. сетей) -->
                <div class="text-center my-4 text-muted small">
                    <span class="bg-white px-2">или</span>
                </div>

                <!-- Кнопка входа через Telegram (пример) -->
                <button class="btn btn-outline-primary w-100 rounded-3 py-2 fw-semibold">
                    <i class="fa-brands fa-telegram me-2"></i> Войти через Telegram
                </button>

            </div>
        </div>

        <!-- Футер -->
        <div class="text-center mt-4 text-muted small fade-in" style="animation-delay: 0.2s;">
            <router-link to="/about" class="text-decoration-none text-muted">
                О платформе и разработчике
            </router-link>
        </div>

    </div>
</template>

<script>
import axios from 'axios';
// import { useAuthStore } from '@/MobileClient/stores/auth.js'; // Раскомментируй при использовании Pinia

export default {
    name: "AuthPage",

    data() {
        return {
            mode: 'login', // 'login' или 'register'
            isLoading: false,
            errorMessage: '',
            form: {
                identifier: '', // Для входа (email или телефон)
                name: '',       // Для регистрации
                phone: '',      // Для регистрации
                password: ''
            }
        }
    },

    methods: {
        async submitForm() {
            this.errorMessage = '';
            this.isLoading = true;

            try {
                if (this.mode === 'login') {
                    await this.handleLogin();
                } else {
                    await this.handleRegister();
                }

                // Успешный вход: редирект на главную или в профиль
                this.$router.push('/menu');

            } catch (error) {
                console.error('Auth error:', error);
                this.errorMessage = error.response?.data?.message || 'Произошла ошибка. Проверьте данные.';
            } finally {
                this.isLoading = false;
            }
        },

        async handleLogin() {
            // Пример запроса. Замени на свой API endpoint или вызов Pinia store
            const response = await axios.post('/api/auth/login', {
                identifier: this.form.identifier,
                password: this.form.password
            });

            // Сохранение токена (пример)
            // localStorage.setItem('token', response.data.token);
            // this.$pinia.use(AuthStore).setUser(response.data.user);
        },

        async handleRegister() {
            // Пример запроса. Замени на свой API endpoint
            const response = await axios.post('/api/auth/register', {
                name: this.form.name,
                phone: this.form.phone,
                password: this.form.password
            });

            // Автоматический вход после регистрации или сообщение об успехе
        }
    }
}
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
