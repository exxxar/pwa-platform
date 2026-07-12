<template>
    <div class="profile-page">
        <div class="container py-3 pb-5">

            <!-- Шапка профиля -->
            <div class="profile-header text-center mb-4">
                <div class="avatar-wrapper mb-3">
                    <div class="avatar-circle">
                        <img
                            v-if="avatarUrl"
                            :src="avatarUrl"
                            alt="Аватар"
                            class="avatar-img"
                        >
                        <i v-else class="fa-solid fa-user avatar-placeholder"></i>
                    </div>
                    <button
                        v-if="isOwnProfile"
                        class="avatar-edit-btn"
                        @click="openAvatarModal"
                        title="Изменить фото"
                    >
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>

                <h3 class="fw-bold mb-1">{{ self?.name || 'Пользователь' }}</h3>
                <p class="text-muted mb-2">
                    {{ self?.phone || 'Телефон не указан' }}
                    <span v-if="self?.phone_verified" class="verified-badge">
                        <i class="fa-solid fa-circle-check"></i> Подтверждён
                    </span>
                </p>

                <!-- Уровень лояльности -->
                <div v-if="self?.loyalty_level" class="loyalty-badge">
                    <i class="fa-solid fa-crown"></i>
                    <span>{{ self.loyalty_level }}</span>
                </div>
            </div>

            <!-- Личные данные -->
            <div class="section-card mb-3">
                <div class="section-header">
                    <i class="fa-solid fa-user-pen"></i>
                    <h6 class="mb-0">Личные данные</h6>
                </div>

                <div class="profile-fields">
                    <!-- Имя -->
                    <button
                        v-if="isOwnProfile"
                        class="profile-field"
                        @click="openEditModal('name')"
                    >
                        <div class="field-icon name-icon">
                            <i class="fa-solid fa-signature"></i>
                        </div>
                        <div class="field-content">
                            <div class="field-label">Имя</div>
                            <div class="field-value" :class="{'text-muted': !self?.name}">
                                {{ self?.name || 'Указать имя' }}
                            </div>
                        </div>
                        <div class="field-action">
                            <i class="fa-solid fa-pen"></i>
                        </div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon name-icon">
                            <i class="fa-solid fa-signature"></i>
                        </div>
                        <div class="field-content">
                            <div class="field-label">Имя</div>
                            <div class="field-value">{{ self?.name || 'не указано' }}</div>
                        </div>
                    </div>

                    <!-- Телефон -->
                    <button
                        v-if="isOwnProfile"
                        class="profile-field"
                        @click="openPhoneModal"
                    >
                        <div class="field-icon phone-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="field-content">
                            <div class="field-label">Телефон</div>
                            <div class="field-value" :class="{'text-primary': !self?.phone}">
                                {{ self?.phone || 'Добавить номер' }}
                                <span v-if="self?.phone && !self?.phone_verified" class="unverified-hint">
                                    (не подтверждён)
                                </span>
                            </div>
                        </div>
                        <div class="field-action">
                            <i :class="self?.phone ? 'fa-solid fa-pen' : 'fa-solid fa-plus'"></i>
                        </div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon phone-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="field-content">
                            <div class="field-label">Телефон</div>
                            <div class="field-value">{{ self?.phone || 'не указан' }}</div>
                        </div>
                    </div>

                    <!-- День рождения -->
                    <button
                        v-if="isOwnProfile"
                        class="profile-field"
                        @click="openEditModal('birthday')"
                    >
                        <div class="field-icon birthday-icon">
                            <i class="fa-solid fa-cake-candles"></i>
                        </div>
                        <div class="field-content">
                            <div class="field-label">День рождения</div>
                            <div class="field-value" :class="{'text-muted': !self?.birthday}">
                                {{ formatBirthday(self?.birthday) || 'Указать дату' }}
                            </div>
                        </div>
                        <div class="field-action">
                            <i class="fa-solid fa-pen"></i>
                        </div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon birthday-icon">
                            <i class="fa-solid fa-cake-candles"></i>
                        </div>
                        <div class="field-content">
                            <div class="field-label">День рождения</div>
                            <div class="field-value">{{ formatBirthday(self?.birthday) || 'не указан' }}</div>
                        </div>
                    </div>

                    <!-- Город -->
                    <div class="profile-field readonly">
                        <div class="field-icon city-icon">
                            <i class="fa-solid fa-city"></i>
                        </div>
                        <div class="field-content">
                            <div class="field-label">Город</div>
                            <div class="field-value" :class="{'text-muted': !self?.city}">
                                {{ self?.city || 'не указан' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="section-card mb-3">
                <div class="section-header">
                    <i class="fa-solid fa-chart-simple"></i>
                    <h6 class="mb-0">Статистика</h6>
                </div>

                <div class="stats-grid">
                    <button class="stat-item" @click="goToOrders">
                        <div class="stat-icon">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </div>
                        <div class="stat-value">{{ self?.order_count || 0 }}</div>
                        <div class="stat-label">Заказов</div>
                    </button>

                    <button class="stat-item" @click="goToFriends">
                        <div class="stat-icon">
                            <i class="fa-solid fa-user-group"></i>
                        </div>
                        <div class="stat-value">{{ self?.friends_count || 0 }}</div>
                        <div class="stat-label">Друзей</div>
                    </button>

                    <button class="stat-item" @click="goToCashback">
                        <div class="stat-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="stat-value">{{ self?.cashBack?.amount || 0 }}</div>
                        <div class="stat-label">Баллов</div>
                    </button>
                </div>
            </div>

            <!-- Специальные начисления -->
            <div
                v-if="self?.cashBack?.subs?.length > 0"
                class="section-card mb-3"
            >
                <div class="section-header">
                    <i class="fa-solid fa-gift"></i>
                    <h6 class="mb-0">Специальные начисления</h6>
                </div>

                <div class="subs-list">
                    <div
                        v-for="(sub, index) in self.cashBack.subs"
                        :key="index"
                        class="sub-item"
                    >
                        <div class="sub-title">{{ sub.title || 'Начисление' }}</div>
                        <div class="sub-amount">+{{ sub.amount || 0 }} ₽</div>
                    </div>
                </div>
            </div>

            <ProfileQRCode/>

            <!-- Кнопка редактирования -->
            <EditProfileButton
                class="mb-2"
                @click="$emit('profile-edit')" />
            <AppDivider text="Выход из системы" />
            <!-- Выход -->
            <button
                v-if="isOwnProfile"
                class="btn-logout"
                @click="logout"
            >
                <i class="fa-solid fa-right-from-bracket me-2"></i>
                Выйти
            </button>

        </div>

        <!-- ==================== МОДАЛКИ РЕДАКТИРОВАНИЯ ==================== -->

        <!-- Модалка: Имя -->
        <div class="modal fade" id="editNameModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon name-icon">
                            <i class="fa-solid fa-signature"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Изменить имя</h5>
                            <small class="text-muted">Как к вам обращаться?</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating">
                            <input
                                v-model="editForms.name"
                                type="text"
                                class="form-control"
                                id="edit-name-input"
                                placeholder="Ваше имя"
                                maxlength="50"
                            >
                            <label for="edit-name-input">Имя</label>
                        </div>
                        <div class="form-hint">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Это имя будет отображаться в заказах и профиле
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!editForms.name.trim() || saving"
                            @click="saveField('name')"
                        >
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                            Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модалка: День рождения -->
        <div class="modal fade" id="editBirthdayModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon birthday-icon">
                            <i class="fa-solid fa-cake-candles"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">День рождения</h5>
                            <small class="text-muted">Получите подарок в ваш праздник 🎁</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating">
                            <input
                                v-model="editForms.birthday"
                                type="date"
                                class="form-control"
                                id="edit-birthday-input"
                                :max="maxBirthdayDate"
                            >
                            <label for="edit-birthday-input">Дата рождения</label>
                        </div>
                        <div class="form-hint">
                            <i class="fa-solid fa-gift me-1"></i>
                            В день рождения вам будут начислены бонусные баллы
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!editForms.birthday || saving"
                            @click="saveField('birthday')"
                        >
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                            Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== МОДАЛКА ТЕЛЕФОНА (2 ШАГА) ==================== -->
        <div class="modal fade" id="phoneModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content phone-modal">

                    <!-- ШАГ 1: Ввод номера -->
                    <template v-if="phoneStep === 1">
                        <div class="modal-header">
                            <div class="modal-icon phone-icon">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="modal-title mb-0">Номер телефона</h5>
                                <small class="text-muted">Для подтверждения заказов и уведомлений</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-floating">
                                <input
                                    v-model="phoneForm.phone"
                                    type="tel"
                                    class="form-control"
                                    id="phone-input"
                                    placeholder="+7 (999) 123-45-67"
                                    @input="formatPhoneInput"
                                    :disabled="phoneSending"
                                >
                                <label for="phone-input">Номер телефона</label>
                            </div>
                            <div v-if="phoneError" class="error-message mt-2">
                                <i class="fa-solid fa-circle-exclamation me-1"></i>
                                {{ phoneError }}
                            </div>
                            <div class="form-hint">
                                <i class="fa-solid fa-shield-halved me-1"></i>
                                На этот номер будет отправлен SMS-код для подтверждения
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                :disabled="!isValidPhone || phoneSending"
                                @click="sendSmsCode"
                            >
                                <span v-if="phoneSending" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="fa-solid fa-paper-plane me-2"></i>
                                Получить код
                            </button>
                        </div>
                    </template>

                    <!-- ШАГ 2: Ввод кода -->
                    <template v-else-if="phoneStep === 2">
                        <div class="modal-header">
                            <div class="modal-icon code-icon">
                                <i class="fa-solid fa-key"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="modal-title mb-0">Подтверждение</h5>
                                <small class="text-muted">Код отправлен на {{ phoneForm.phone }}</small>
                            </div>
                            <button type="button" class="btn-close" @click="closePhoneModal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Поле ввода кода -->
                            <div class="code-input-wrapper">
                                <input
                                    v-model="phoneForm.code"
                                    type="text"
                                    class="code-input"
                                    placeholder="000000"
                                    maxlength="6"
                                    @input="formatCode"
                                    :disabled="codeVerifying"
                                    autofocus
                                >
                            </div>

                            <!-- Таймер повторной отправки -->
                            <div class="resend-section">
                                <div v-if="resendTimer > 0" class="resend-timer">
                                    <i class="fa-solid fa-clock me-1"></i>
                                    Повторная отправка через {{ formatTime(resendTimer) }}
                                </div>
                                <button
                                    v-else
                                    class="btn-resend"
                                    @click="sendSmsCode"
                                    :disabled="phoneSending"
                                >
                                    <span v-if="phoneSending" class="spinner-border spinner-border-sm me-2"></span>
                                    <i v-else class="fa-solid fa-rotate-right me-1"></i>
                                    Отправить код повторно
                                </button>
                            </div>

                            <div v-if="codeError" class="error-message mt-3">
                                <i class="fa-solid fa-circle-exclamation me-1"></i>
                                {{ codeError }}
                            </div>

                            <div class="form-hint">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                Введите 6-значный код из SMS. Код действителен 5 минут.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" @click="backToPhoneStep">
                                <i class="fa-solid fa-arrow-left me-1"></i> Назад
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                :disabled="phoneForm.code.length !== 6 || codeVerifying"
                                @click="verifyCode"
                            >
                                <span v-if="codeVerifying" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="fa-solid fa-check me-2"></i>
                                Подтвердить
                            </button>
                        </div>
                    </template>

                    <!-- ШАГ 3: Успех -->
                    <template v-else-if="phoneStep === 3">
                        <div class="modal-body text-center py-5">
                            <div class="success-icon mb-3">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Номер подтверждён!</h5>
                            <p class="text-muted mb-0">
                                Телефон {{ phoneForm.phone }} успешно добавлен в ваш профиль
                            </p>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-primary px-5" @click="closePhoneModal">
                                Отлично!
                            </button>
                        </div>
                    </template>

                </div>
            </div>
        </div>

        <!-- Уведомления -->
        <div v-if="notification" class="notification-toast" :class="notification.type">
            <i :class="notification.icon"></i>
            <span>{{ notification.text }}</span>
        </div>
    </div>
</template>

<script>

import EditProfileButton from "@/MobileClient/Components/Shop/EditProfileButton.vue";
import ProfileQRCode from "@/MobileClient/Components/Shop/ProfileQRCode.vue";
import AppDivider from "@/MobileClient/Components/AppDivider.vue";
export default {
    name: "ProfilePage",
    components:{
        EditProfileButton,
        ProfileQRCode,
        AppDivider
    },
    data() {
        return {
            saving: false,
            notification: null,
            editForms: {
                name: '',
                birthday: '',
            },

            // Телефон
            phoneStep: 1, // 1 - ввод номера, 2 - ввод кода, 3 - успех
            phoneSending: false,
            codeVerifying: false,
            resendTimer: 0,
            resendInterval: null,
            phoneError: '',
            codeError: '',
            phoneForm: {
                phone: '',
                code: '',
                sessionId: null, // ID сессии верификации с бэка
            },

            modals: {},
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        tenant() {
            return window.Tenant || null;
        },

        isOwnProfile() {
            return true;
        },

        avatarUrl() {
            return this.self?.avatar || this.self?.photo || null;
        },

        isValidPhone() {
            const digits = this.phoneForm.phone.replace(/\D/g, '');
            return digits.length === 11;
        },

        maxBirthdayDate() {
            const today = new Date();
            return today.toISOString().split('T')[0];
        },
    },

    mounted() {
        this.initModals();
        this.prefillEditForms();
    },

    beforeUnmount() {
        Object.values(this.modals).forEach(modal => {
            if (modal && typeof modal.dispose === 'function') {
                modal.dispose();
            }
        });
        this.clearResendTimer();
    },

    methods: {
        // Инициализация модалок
        initModals() {
            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    this.modals.name = new bootstrap.Modal(document.getElementById('editNameModal'));
                    this.modals.birthday = new bootstrap.Modal(document.getElementById('editBirthdayModal'));
                    this.modals.phone = new bootstrap.Modal(document.getElementById('phoneModal'));
                }
            });
        },

        prefillEditForms() {
            if (!this.self) return;
            this.editForms.name = this.self.name || '';
            this.editForms.birthday = this.self.birthday || '';
        },

        // Открытие модалок
        openEditModal(field) {
            if (field === 'name') {
                this.editForms.name = this.self?.name || '';
            } else if (field === 'birthday') {
                this.editForms.birthday = this.self?.birthday || '';
            }

            if (this.modals[field]) {
                this.modals[field].show();
            }
        },

        openPhoneModal() {
            this.phoneStep = 1;
            this.phoneForm.phone = this.self?.phone || '';
            this.phoneForm.code = '';
            this.phoneForm.sessionId = null;
            this.phoneError = '';
            this.codeError = '';
            this.clearResendTimer();

            if (this.modals.phone) {
                this.modals.phone.show();
            }
        },

        closePhoneModal() {
            if (this.modals.phone) {
                this.modals.phone.hide();
            }
            this.clearResendTimer();
        },

        backToPhoneStep() {
            this.phoneStep = 1;
            this.codeError = '';
            this.clearResendTimer();
        },

        // Форматирование телефона
        formatPhoneInput() {
            let value = this.phoneForm.phone.replace(/\D/g, '');

            if (value.startsWith('8')) {
                value = '7' + value.slice(1);
            }
            if (!value.startsWith('7') && value.length > 0) {
                value = '7' + value;
            }

            let formatted = '';
            if (value.length > 0) formatted = '+7';
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 5) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 8) formatted += '-' + value.slice(7, 9);
            if (value.length >= 10) formatted += '-' + value.slice(9, 11);

            this.phoneForm.phone = formatted;
            this.phoneError = '';
        },

        // Форматирование кода (только цифры)
        formatCode() {
            this.phoneForm.code = this.phoneForm.code.replace(/\D/g, '').slice(0, 6);
            this.codeError = '';
        },

        // Форматирование времени таймера
        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },

        // Отправка SMS-кода
        async sendSmsCode() {
            if (!this.isValidPhone) {
                this.phoneError = 'Введите корректный номер телефона';
                return;
            }

            this.phoneSending = true;
            this.phoneError = '';

            try {
                // TODO: Замени на реальный API-запрос
                // const response = await axios.post('/api/phone/send-code', {
                //     phone: this.phoneForm.phone
                // });
                // this.phoneForm.sessionId = response.data.session_id;

                // Имитация запроса (удали после подключения API)
                await new Promise(resolve => setTimeout(resolve, 1500));
                this.phoneForm.sessionId = 'test-session-' + Date.now();

                // Переходим к шагу ввода кода
                this.phoneStep = 2;
                this.startResendTimer();

            } catch (error) {
                console.error('Ошибка отправки кода:', error);
                this.phoneError = error.response?.data?.message || 'Ошибка отправки кода. Попробуйте позже.';
            } finally {
                this.phoneSending = false;
            }
        },

        // Подтверждение кода
        async verifyCode() {
            if (this.phoneForm.code.length !== 6) {
                this.codeError = 'Введите 6-значный код';
                return;
            }

            this.codeVerifying = true;
            this.codeError = '';

            try {
                // TODO: Замени на реальный API-запрос
                // const response = await axios.post('/api/phone/verify-code', {
                //     session_id: this.phoneForm.sessionId,
                //     code: this.phoneForm.code
                // });

                // Имитация запроса (удали после подключения API)
                await new Promise(resolve => setTimeout(resolve, 1500));

                // Обновляем профиль
                if (window.TenantUser) {
                    window.TenantUser.phone = this.phoneForm.phone;
                    window.TenantUser.phone_verified = true;
                }

                // Переходим к шагу успеха
                this.phoneStep = 3;
                this.clearResendTimer();

                this.showNotification('success', 'Номер телефона подтверждён', 'fa-solid fa-check-circle');

            } catch (error) {
                console.error('Ошибка подтверждения:', error);
                this.codeError = error.response?.data?.message || 'Неверный код. Попробуйте ещё раз.';
            } finally {
                this.codeVerifying = false;
            }
        },

        // Таймер повторной отправки
        startResendTimer() {
            this.resendTimer = 60; // 60 секунд
            this.clearResendTimer();

            this.resendInterval = setInterval(() => {
                if (this.resendTimer > 0) {
                    this.resendTimer--;
                } else {
                    this.clearResendTimer();
                }
            }, 1000);
        },

        clearResendTimer() {
            if (this.resendInterval) {
                clearInterval(this.resendInterval);
                this.resendInterval = null;
            }
            this.resendTimer = 0;
        },

        // Сохранение других полей
        async saveField(field) {
            this.saving = true;

            try {
                const payload = {};

                if (field === 'name') {
                    payload.name = this.editForms.name.trim();
                } else if (field === 'birthday') {
                    payload.birthday = this.editForms.birthday;
                }

                // TODO: Замени на реальный API-запрос
                // await axios.put('/api/user/profile', payload);

                await new Promise(resolve => setTimeout(resolve, 800));

                if (window.TenantUser) {
                    Object.assign(window.TenantUser, payload);
                }

                if (this.modals[field]) {
                    this.modals[field].hide();
                }

                this.showNotification('success', 'Данные успешно обновлены', 'fa-solid fa-check-circle');

            } catch (error) {
                console.error('Ошибка сохранения:', error);
                this.showNotification('error', error.response?.data?.message || 'Ошибка при сохранении', 'fa-solid fa-circle-exclamation');
            } finally {
                this.saving = false;
            }
        },

        openAvatarModal() {
            this.showNotification('info', 'Функция в разработке', 'fa-solid fa-info-circle');
        },

        formatBirthday(dateStr) {
            if (!dateStr) return null;
            try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return dateStr;
                return date.toLocaleDateString('ru-RU', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            } catch (e) {
                return dateStr;
            }
        },

        goToOrders() {
            this.$router.push({ name: 'Orders' });
        },

        goToFriends() {
            this.$router.push({ name: 'ReferralsPage' });
        },

        goToCashback() {
            this.$router.push({ name: 'Cashback' });
        },

        logout() {
            if (!confirm('Вы уверены, что хотите выйти?')) return;
            window.TenantUser = null;
            localStorage.removeItem('token');
            this.$router.push({ name: 'Auth' });
        },

        showNotification(type, text, icon) {
            this.notification = { type, text, icon };
            setTimeout(() => {
                this.notification = null;
            }, 3000);
        },
    },
};
</script>

<style scoped>
.profile-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* Шапка профиля */
.profile-header {
    padding: 20px 0;
}

.avatar-wrapper {
    position: relative;
    display: inline-block;
}

.avatar-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3);
    border: 4px solid var(--bs-body-bg);
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    font-size: 3rem;
    color: white;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    border: 3px solid var(--bs-body-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.avatar-edit-btn:hover {
    transform: scale(1.1);
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-left: 8px;
    padding: 2px 8px;
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.unverified-hint {
    font-size: 0.75rem;
    color: #dc3545;
    font-weight: normal;
}

.loyalty-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1a1a1a;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
}

/* Секции */
.section-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 20px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-bottom: 1px solid var(--bs-border-color);
    color: var(--bs-primary);
    font-weight: 600;
}

/* Поля профиля */
.profile-fields {
    padding: 8px;
}

.profile-field {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 12px;
    width: 100%;
    background: transparent;
    border: none;
    border-radius: 10px;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--bs-body-color);
}

.profile-field:hover:not(.readonly) {
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.profile-field.readonly {
    cursor: default;
}

.field-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.name-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.phone-icon { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.birthday-icon { background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); }
.city-icon { background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%); }

.field-content {
    flex: 1;
    min-width: 0;
}

.field-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.field-value {
    font-weight: 600;
    font-size: 0.95rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.field-action {
    color: var(--bs-primary);
    opacity: 0.6;
    transition: opacity 0.2s ease;
}

.profile-field:hover .field-action {
    opacity: 1;
}

/* Статистика */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 16px;
}

.stat-item {
    background: transparent;
    border: none;
    padding: 16px 8px;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--bs-body-color);
}

.stat-item:hover {
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 1.1rem;
}

.stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--bs-primary);
    margin-bottom: 2px;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* Специальные начисления */
.subs-list {
    padding: 8px;
}

.sub-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.sub-item:hover {
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.sub-title {
    color: var(--bs-body-color);
    font-size: 0.9rem;
}

.sub-amount {
    color: var(--bs-primary);
    font-weight: 700;
}

/* Кнопка выхода */
.btn-logout {
    width: 100%;
    padding: 14px;
    background: transparent;
    border: 2px solid #dc3545;
    border-radius: 12px;
    color: #dc3545;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-logout:hover {
    background: #dc3545;
    color: white;
}

/* Модалки */
.edit-modal,
.phone-modal {
    border-radius: 16px;
    border: none;
    overflow: hidden;
}

.edit-modal .modal-header,
.phone-modal .modal-header {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.code-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.edit-modal .modal-body,
.phone-modal .modal-body {
    padding: 20px;
}

.form-hint {
    margin-top: 12px;
    padding: 10px 14px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

.edit-modal .modal-footer,
.phone-modal .modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--bs-border-color);
    gap: 8px;
}

.edit-modal .modal-footer .btn,
.phone-modal .modal-footer .btn {
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 600;
}

/* Ввод кода */
.code-input-wrapper {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

.code-input {
    width: 100%;
    max-width: 200px;
    padding: 16px;
    font-size: 2rem;
    font-weight: 700;
    text-align: center;
    letter-spacing: 8px;
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    transition: all 0.2s ease;
}

.code-input:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

/* Таймер повторной отправки */
.resend-section {
    text-align: center;
    margin-top: 16px;
}

.resend-timer {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-radius: 20px;
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
}

.btn-resend {
    background: transparent;
    border: none;
    color: var(--bs-primary);
    font-weight: 600;
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.btn-resend:hover:not(:disabled) {
    background: rgba(var(--bs-primary-rgb), 0.1);
}

/* Ошибки */
.error-message {
    padding: 10px 14px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 8px;
    color: #dc3545;
    font-size: 0.9rem;
}

/* Успех */
.success-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    animation: successPop 0.5s ease;
}

.success-icon i {
    font-size: 2.5rem;
    color: white;
}

@keyframes successPop {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Уведомления */
.notification-toast {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 14px 20px;
    border-radius: 12px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 9999;
    animation: slideDown 0.3s ease;
    max-width: 90%;
}

.notification-toast.success {
    border-color: #198754;
    color: #198754;
}

.notification-toast.error {
    border-color: #dc3545;
    color: #dc3545;
}

.notification-toast.info {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translate(-50%, -20px);
    }
    to {
        opacity: 1;
        transform: translate(-50%, 0);
    }
}

@media (max-width: 576px) {
    .stats-grid {
        gap: 4px;
    }

    .stat-value {
        font-size: 1.1rem;
    }

    .code-input {
        font-size: 1.5rem;
        letter-spacing: 6px;
    }
}
</style>
