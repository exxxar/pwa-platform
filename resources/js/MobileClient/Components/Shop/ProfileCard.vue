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
                    <button v-if="isOwnProfile" class="profile-field" @click="openEditModal('name')">
                        <div class="field-icon name-icon"><i class="fa-solid fa-signature"></i></div>
                        <div class="field-content">
                            <div class="field-label">Имя</div>
                            <div class="field-value" :class="{'text-muted': !self?.name}">{{ self?.name || 'Указать имя' }}</div>
                        </div>
                        <div class="field-action"><i class="fa-solid fa-pen"></i></div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon name-icon"><i class="fa-solid fa-signature"></i></div>
                        <div class="field-content">
                            <div class="field-label">Имя</div>
                            <div class="field-value">{{ self?.name || 'не указано' }}</div>
                        </div>
                    </div>

                    <!-- Телефон -->
                    <button v-if="isOwnProfile" class="profile-field" @click="openPhoneModal">
                        <div class="field-icon phone-icon"><i class="fa-solid fa-phone"></i></div>
                        <div class="field-content">
                            <div class="field-label">Телефон</div>
                            <div class="field-value" :class="{'text-primary': !self?.phone}">
                                {{ self?.phone || 'Добавить номер' }}
                                <span v-if="self?.phone && !self?.phone_verified" class="unverified-hint">(не подтверждён)</span>
                            </div>
                        </div>
                        <div class="field-action"><i :class="self?.phone ? 'fa-solid fa-pen' : 'fa-solid fa-plus'"></i></div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon phone-icon"><i class="fa-solid fa-phone"></i></div>
                        <div class="field-content">
                            <div class="field-label">Телефон</div>
                            <div class="field-value">{{ self?.phone || 'не указан' }}</div>
                        </div>
                    </div>

                    <!-- 🆕 Email -->
                    <button v-if="isOwnProfile" class="profile-field" @click="openEditModal('email')">
                        <div class="field-icon email-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div class="field-content">
                            <div class="field-label">Email</div>
                            <div class="field-value" :class="{'text-muted': !self?.email}">{{ self?.email || 'Указать email' }}</div>
                        </div>
                        <div class="field-action"><i class="fa-solid fa-pen"></i></div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon email-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div class="field-content">
                            <div class="field-label">Email</div>
                            <div class="field-value">{{ self?.email || 'не указан' }}</div>
                        </div>
                    </div>

                    <!-- День рождения -->
                    <button v-if="isOwnProfile" class="profile-field" @click="openEditModal('birthday')">
                        <div class="field-icon birthday-icon"><i class="fa-solid fa-cake-candles"></i></div>
                        <div class="field-content">
                            <div class="field-label">День рождения</div>
                            <div class="field-value" :class="{'text-muted': !self?.birthday}">{{ formatBirthday(self?.birthday) || 'Указать дату' }}</div>
                        </div>
                        <div class="field-action"><i class="fa-solid fa-pen"></i></div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon birthday-icon"><i class="fa-solid fa-cake-candles"></i></div>
                        <div class="field-content">
                            <div class="field-label">День рождения</div>
                            <div class="field-value">{{ formatBirthday(self?.birthday) || 'не указан' }}</div>
                        </div>
                    </div>

                    <!-- 🆕 Город (теперь редактируемый) -->
                    <button v-if="isOwnProfile" class="profile-field" @click="openEditModal('city')">
                        <div class="field-icon city-icon"><i class="fa-solid fa-city"></i></div>
                        <div class="field-content">
                            <div class="field-label">Город</div>
                            <div class="field-value" :class="{'text-muted': !self?.city}">{{ self?.city || 'Указать город' }}</div>
                        </div>
                        <div class="field-action"><i class="fa-solid fa-pen"></i></div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon city-icon"><i class="fa-solid fa-city"></i></div>
                        <div class="field-content">
                            <div class="field-label">Город</div>
                            <div class="field-value">{{ self?.city || 'не указан' }}</div>
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
                        <div class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                        <div class="stat-value">{{ self?.order_count || 0 }}</div>
                        <div class="stat-label">Заказов</div>
                    </button>
                    <button class="stat-item" @click="goToFriends">
                        <div class="stat-icon"><i class="fa-solid fa-user-group"></i></div>
                        <div class="stat-value">{{ self?.friends_count || 0 }}</div>
                        <div class="stat-label">Друзей</div>
                    </button>
                    <button class="stat-item" @click="goToCashback">
                        <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
                        <div class="stat-value">{{ self?.cashBack?.amount || 0 }}</div>
                        <div class="stat-label">Баллов</div>
                    </button>
                </div>
            </div>

            <!-- 🆕 ДОСТИЖЕНИЯ (Красивая кнопка-карточка) -->
            <button class="achievements-card mb-3" @click="goToAchievements">
                <div class="achievements-icon">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div class="achievements-content">
                    <h6 class="mb-1">Мои достижения</h6>
                    <p class="mb-0">Открывайте новые уровни и получайте награды</p>
                </div>
                <div class="achievements-arrow">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </button>

            <!-- Специальные начисления -->
            <div v-if="self?.cashBack?.subs?.length > 0" class="section-card mb-3">
                <div class="section-header">
                    <i class="fa-solid fa-gift"></i>
                    <h6 class="mb-0">Специальные начисления</h6>
                </div>
                <div class="subs-list">
                    <div v-for="(sub, index) in self.cashBack.subs" :key="index" class="sub-item">
                        <div class="sub-title">{{ sub.title || 'Начисление' }}</div>
                        <div class="sub-amount">+{{ sub.amount || 0 }} ₽</div>
                    </div>
                </div>
            </div>


            <ProfileQRCode/>

            <EditProfileButton class="mb-2" @click="$emit('profile-edit')" />


            <AppDivider text="Управление аккаунтом" />

            <!-- 🆕 Сменить аккаунт -->
            <button class="btn-change-account mb-2" @click="changeAccount">
                <i class="fa-solid fa-user-switch me-2"></i> Сменить аккаунт
            </button>

            <button v-if="isOwnProfile" class="btn-logout" @click="logout" :disabled="isLoggingOut">
                <span v-if="isLoggingOut" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fa-solid fa-right-from-bracket me-2"></i>
                {{ isLoggingOut ? 'Выходим...' : 'Выйти' }}
            </button>
        </div>

        <!-- ==================== МОДАЛКИ РЕДАКТИРОВАНИЯ ==================== -->

        <!-- 🆕 Модалка: Аватар -->
        <div class="modal fade" id="editAvatarModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon name-icon">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Фото профиля</h5>
                            <small class="text-muted">Загрузите новое фото</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="avatar-preview-wrapper mb-3">
                            <img v-if="avatarPreview" :src="avatarPreview" class="avatar-preview-img" alt="Preview">
                            <div v-else class="avatar-preview-placeholder">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        </div>

                        <label class="btn btn-outline-primary mb-2">
                            <i class="fa-solid fa-upload me-2"></i> Выбрать фото
                            <input type="file" accept="image/*" class="d-none" @change="handleAvatarFileChange">
                        </label>
                        <div class="form-hint text-start">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Рекомендуемый размер: 400x400 px. Максимум 2 МБ.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!avatarFile || avatarUploading" @click="uploadAvatar">
                            <span v-if="avatarUploading" class="spinner-border spinner-border-sm me-2"></span>
                            Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модалка: Имя -->
        <div class="modal fade" id="editNameModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon name-icon"><i class="fa-solid fa-signature"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Изменить имя</h5>
                            <small class="text-muted">Как к вам обращаться?</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating">
                            <input v-model="editForms.name" type="text" class="form-control" id="edit-name-input" placeholder="Ваше имя" maxlength="50">
                            <label for="edit-name-input">Имя</label>
                        </div>
                        <div class="form-hint"><i class="fa-solid fa-info-circle me-1"></i> Это имя будет отображаться в заказах и профиле</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!editForms.name.trim() || saving" @click="saveField('name')">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span> Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🆕 Модалка: Email -->
        <div class="modal fade" id="editEmailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon email-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Email</h5>
                            <small class="text-muted">Для получения чеков и уведомлений</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating">
                            <input v-model="editForms.email" type="email" class="form-control" id="edit-email-input" placeholder="example@mail.ru">
                            <label for="edit-email-input">Email</label>
                        </div>
                        <div class="form-hint"><i class="fa-solid fa-info-circle me-1"></i> На этот адрес будут приходить электронные чеки</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!editForms.email.trim() || saving" @click="saveField('email')">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span> Сохранить
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
                        <div class="modal-icon birthday-icon"><i class="fa-solid fa-cake-candles"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">День рождения</h5>
                            <small class="text-muted">Получите подарок в ваш праздник 🎁</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating">
                            <input v-model="editForms.birthday" type="date" class="form-control" id="edit-birthday-input" :max="maxBirthdayDate">
                            <label for="edit-birthday-input">Дата рождения</label>
                        </div>
                        <div class="form-hint"><i class="fa-solid fa-gift me-1"></i> В день рождения вам будут начислены бонусные баллы</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!editForms.birthday || saving" @click="saveField('birthday')">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span> Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🆕 Модалка: Город -->
        <div class="modal fade" id="editCityModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon city-icon"><i class="fa-solid fa-city"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Город</h5>
                            <small class="text-muted">Для автоматического подбора адреса доставки</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating">
                            <input v-model="editForms.city" type="text" class="form-control" id="edit-city-input" placeholder="Москва">
                            <label for="edit-city-input">Город</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!editForms.city.trim() || saving" @click="saveField('city')">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span> Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модалка: Телефон (2 шага) -->
        <div class="modal fade" id="phoneModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content phone-modal">
                    <!-- ШАГ 1: Ввод номера -->
                    <template v-if="phoneStep === 1">
                        <div class="modal-header">
                            <div class="modal-icon phone-icon"><i class="fa-solid fa-phone"></i></div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="modal-title mb-0">Номер телефона</h5>
                                <small class="text-muted">Для подтверждения заказов и уведомлений</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-floating">
                                <input v-model="phoneForm.phone" type="tel" class="form-control" id="phone-input" placeholder="+7 (999) 123-45-67" @input="formatPhoneInput" :disabled="phoneSending">
                                <label for="phone-input">Номер телефона</label>
                            </div>
                            <div v-if="phoneError" class="error-message mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i> {{ phoneError }}</div>
                            <div class="form-hint"><i class="fa-solid fa-shield-halved me-1"></i> На этот номер будет отправлен SMS-код для подтверждения</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="btn btn-primary" :disabled="!isValidPhone || phoneSending" @click="sendSmsCode">
                                <span v-if="phoneSending" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="fa-solid fa-paper-plane me-2"></i> Получить код
                            </button>
                        </div>
                    </template>

                    <!-- ШАГ 2: Ввод кода -->
                    <template v-else-if="phoneStep === 2">
                        <div class="modal-header">
                            <div class="modal-icon code-icon"><i class="fa-solid fa-key"></i></div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="modal-title mb-0">Подтверждение</h5>
                                <small class="text-muted">Код отправлен на {{ phoneForm.phone }}</small>
                            </div>
                            <button type="button" class="btn-close" @click="closePhoneModal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="code-input-wrapper">
                                <input v-model="phoneForm.code" type="text" class="code-input" placeholder="000000" maxlength="6" @input="formatCode" :disabled="codeVerifying" autofocus>
                            </div>
                            <div class="resend-section">
                                <div v-if="resendTimer > 0" class="resend-timer">
                                    <i class="fa-solid fa-clock me-1"></i> Повторная отправка через {{ formatTime(resendTimer) }}
                                </div>
                                <button v-else class="btn-resend" @click="sendSmsCode" :disabled="phoneSending">
                                    <span v-if="phoneSending" class="spinner-border spinner-border-sm me-2"></span>
                                    <i v-else class="fa-solid fa-rotate-right me-1"></i> Отправить код повторно
                                </button>
                            </div>
                            <div v-if="codeError" class="error-message mt-3"><i class="fa-solid fa-circle-exclamation me-1"></i> {{ codeError }}</div>
                            <div class="form-hint"><i class="fa-solid fa-info-circle me-1"></i> Введите 6-значный код из SMS. Код действителен 5 минут.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" @click="backToPhoneStep"><i class="fa-solid fa-arrow-left me-1"></i> Назад</button>
                            <button type="button" class="btn btn-primary" :disabled="phoneForm.code.length !== 6 || codeVerifying" @click="verifyCode">
                                <span v-if="codeVerifying" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="fa-solid fa-check me-2"></i> Подтвердить
                            </button>
                        </div>
                    </template>

                    <!-- ШАГ 3: Успех -->
                    <template v-else-if="phoneStep === 3">
                        <div class="modal-body text-center py-5">
                            <div class="success-icon mb-3"><i class="fa-solid fa-circle-check"></i></div>
                            <h5 class="fw-bold mb-2">Номер подтверждён!</h5>
                            <p class="text-muted mb-0">Телефон {{ phoneForm.phone }} успешно добавлен в ваш профиль</p>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-primary px-5" @click="closePhoneModal">Отлично!</button>
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
    components: {
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
                email: '',    // 🆕
                city: '',     // 🆕
            },
            isLoggingOut: false,
            // 🆕 Аватар
            avatarFile: null,
            avatarPreview: null,
            avatarUploading: false,

            // Телефон
            phoneStep: 1,
            phoneSending: false,
            codeVerifying: false,
            resendTimer: 0,
            resendInterval: null,
            phoneError: '',
            codeError: '',
            phoneForm: {
                phone: '',
                code: '',
                sessionId: null,
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
        goToAchievements(){
            this.$router.push({ name: 'Achievements' })
        },
        changeAccount(){
            this.$router.push({ name: 'Auth' })
        },
        initModals() {
            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    this.modals.name = new bootstrap.Modal(document.getElementById('editNameModal'));
                    this.modals.email = new bootstrap.Modal(document.getElementById('editEmailModal'));
                    this.modals.birthday = new bootstrap.Modal(document.getElementById('editBirthdayModal'));
                    this.modals.city = new bootstrap.Modal(document.getElementById('editCityModal'));
                    this.modals.phone = new bootstrap.Modal(document.getElementById('phoneModal'));
                    this.modals.avatar = new bootstrap.Modal(document.getElementById('editAvatarModal'));
                }
            });
        },

        prefillEditForms() {
            if (!this.self) return;
            this.editForms.name = this.self.name || '';
            this.editForms.birthday = this.self.birthday || '';
            this.editForms.email = this.self.email || '';
            this.editForms.city = this.self.city || '';
        },

        openEditModal(field) {
            if (field === 'name') this.editForms.name = this.self?.name || '';
            else if (field === 'email') this.editForms.email = this.self?.email || '';
            else if (field === 'birthday') this.editForms.birthday = this.self?.birthday || '';
            else if (field === 'city') this.editForms.city = this.self?.city || '';

            if (this.modals[field]) {
                this.modals[field].show();
            }
        },

        // 🆕 Методы для аватара
        openAvatarModal() {
            this.avatarFile = null;
            this.avatarPreview = this.avatarUrl;
            if (this.modals.avatar) {
                this.modals.avatar.show();
            }
        },

        handleAvatarFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                this.showNotification('error', 'Файл слишком большой (макс. 2 МБ)', 'fa-solid fa-circle-exclamation');
                return;
            }

            this.avatarFile = file;
            this.avatarPreview = URL.createObjectURL(file);
        },

        async uploadAvatar() {
            if (!this.avatarFile) return;
            this.avatarUploading = true;

            try {
                // 🆕 РЕАЛЬНЫЙ API-ЗАПРОС ДЛЯ ФАЙЛА
                const formData = new FormData();
                formData.append('avatar', this.avatarFile);

                const response = await axios.post('/profile/avatar', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                // Обновляем глобальный объект и превью
                if (window.TenantUser && response.data.data) {
                    window.TenantUser.avatar = response.data.data.avatar_url;
                }

                if (this.modals.avatar) {
                    this.modals.avatar.hide();
                }
                this.showNotification('success', 'Фото профиля обновлено', 'fa-solid fa-check-circle');
            } catch (error) {
                console.error('Ошибка загрузки аватара:', error);
                const errorMsg = error.response?.data?.errors
                    ? Object.values(error.response.data.errors)[0][0]
                    : 'Ошибка при загрузке фото';

                this.showNotification('error', errorMsg, 'fa-solid fa-circle-exclamation');
            } finally {
                this.avatarUploading = false;
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
            if (this.modals.phone) this.modals.phone.show();
        },

        closePhoneModal() {
            if (this.modals.phone) this.modals.phone.hide();
            this.clearResendTimer();
        },

        backToPhoneStep() {
            this.phoneStep = 1;
            this.codeError = '';
            this.clearResendTimer();
        },

        formatPhoneInput() {
            let value = this.phoneForm.phone.replace(/\D/g, '');
            if (value.startsWith('8')) value = '7' + value.slice(1);
            if (!value.startsWith('7') && value.length > 0) value = '7' + value;

            let formatted = '';
            if (value.length > 0) formatted = '+7';
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 5) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 8) formatted += '-' + value.slice(7, 9);
            if (value.length >= 10) formatted += '-' + value.slice(9, 11);

            this.phoneForm.phone = formatted;
            this.phoneError = '';
        },

        formatCode() {
            this.phoneForm.code = this.phoneForm.code.replace(/\D/g, '').slice(0, 6);
            this.codeError = '';
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },

        async sendSmsCode() {
            if (!this.isValidPhone) {
                this.phoneError = 'Введите корректный номер телефона';
                return;
            }
            this.phoneSending = true;
            this.phoneError = '';
            try {
                await new Promise(resolve => setTimeout(resolve, 1500));
                this.phoneForm.sessionId = 'test-session-' + Date.now();
                this.phoneStep = 2;
                this.startResendTimer();
            } catch (error) {
                this.phoneError = error.response?.data?.message || 'Ошибка отправки кода.';
            } finally {
                this.phoneSending = false;
            }
        },

        async verifyCode() {
            if (this.phoneForm.code.length !== 6) {
                this.codeError = 'Введите 6-значный код';
                return;
            }
            this.codeVerifying = true;
            this.codeError = '';
            try {
                await new Promise(resolve => setTimeout(resolve, 1500));
                if (window.TenantUser) {
                    window.TenantUser.phone = this.phoneForm.phone;
                    window.TenantUser.phone_verified = true;
                }
                this.phoneStep = 3;
                this.clearResendTimer();
                this.showNotification('success', 'Номер телефона подтверждён', 'fa-solid fa-check-circle');
            } catch (error) {
                this.codeError = error.response?.data?.message || 'Неверный код.';
            } finally {
                this.codeVerifying = false;
            }
        },

        startResendTimer() {
            this.resendTimer = 60;
            this.clearResendTimer();
            this.resendInterval = setInterval(() => {
                if (this.resendTimer > 0) this.resendTimer--;
                else this.clearResendTimer();
            }, 1000);
        },

        clearResendTimer() {
            if (this.resendInterval) {
                clearInterval(this.resendInterval);
                this.resendInterval = null;
            }
            this.resendTimer = 0;
        },

        async saveField(field) {
            this.saving = true;
            try {
                const payload = {};
                if (field === 'name') payload.name = this.editForms.name.trim();
                else if (field === 'email') payload.email = this.editForms.email.trim();
                else if (field === 'birthday') payload.birthday = this.editForms.birthday;
                else if (field === 'city') payload.city = this.editForms.city.trim();

                // 🆕 РЕАЛЬНЫЙ API-ЗАПРОС
                const response = await axios.put('/profile', payload);

                // Обновляем глобальный объект TenantUser новыми данными с бэка
                if (window.TenantUser && response.data.data) {
                    Object.assign(window.TenantUser, response.data.data);
                }

                if (this.modals[field]) {
                    this.modals[field].hide();
                }
                this.showNotification('success', 'Данные успешно обновлены', 'fa-solid fa-check-circle');
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                const errorMsg = error.response?.data?.errors
                    ? Object.values(error.response.data.errors)[0][0]
                    : (error.response?.data?.message || 'Ошибка при сохранении');

                this.showNotification('error', errorMsg, 'fa-solid fa-circle-exclamation');
            } finally {
                this.saving = false;
            }
        },

        formatBirthday(dateStr) {
            if (!dateStr) return null;
            try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return dateStr;
                return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
            } catch (e) {
                return dateStr;
            }
        },

        goToOrders() { this.$router.push({ name: 'Orders' }); },
        goToFriends() { this.$router.push({ name: 'ReferralsPage' }); },
        goToCashback() { this.$router.push({ name: 'Cashback' }); },

        async logout() {
            if (!confirm('Вы уверены, что хотите выйти?')) return;

            this.isLoggingOut = true;

            try {
                // 🆕 Вызываем серверный logout
                await axios.post('/auth/logout');
            } catch (error) {
                console.error('Ошибка при выходе с сервера:', error);
                // Даже если сервер вернул ошибку, мы всё равно должны
                // очистить локальные данные, чтобы не застрять в сломанном состоянии
            } finally {
                // 🆕 Очистка локальных данных
                window.TenantUser = null;
                localStorage.removeItem('token'); // Или 'auth_token', в зависимости от того, как вы его сохраняете

                // 🆕 Перенаправление на страницу входа
                window.location.href = "/auth/login"

                this.isLoggingOut = false;
            }
        },

        showNotification(type, text, icon) {
            this.notification = { type, text, icon };
            setTimeout(() => { this.notification = null; }, 3000);
        },
    },
};
</script>

<style scoped>
.profile-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

.profile-header { padding: 20px 0; }
.avatar-wrapper { position: relative; display: inline-block; }
.avatar-circle {
    width: 120px; height: 120px; border-radius: 50%; overflow: hidden;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3); border: 4px solid var(--bs-body-bg);
}
.avatar-img { width: 100%; height: 100%; object-fit: cover; }
.avatar-placeholder { font-size: 3rem; color: white; }
.avatar-edit-btn {
    position: absolute; bottom: 0; right: 0; width: 36px; height: 36px; border-radius: 50%;
    background: var(--bs-primary); color: white; border: 3px solid var(--bs-body-bg);
    display: flex; align-items: center; justify-content: center; cursor: pointer;
    transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
.avatar-edit-btn:hover { transform: scale(1.1); }

.verified-badge {
    display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; padding: 2px 8px;
    background: rgba(25, 135, 84, 0.1); color: #198754; border-radius: 12px; font-size: 0.75rem; font-weight: 600;
}
.unverified-hint { font-size: 0.75rem; color: #dc3545; font-weight: normal; }
.loyalty-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #1a1a1a;
    border-radius: 20px; font-size: 0.85rem; font-weight: 600; box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
}

.section-card { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 16px; overflow: hidden; }
.section-header {
    display: flex; align-items: center; gap: 10px; padding: 16px 20px;
    background: rgba(var(--bs-primary-rgb), 0.05); border-bottom: 1px solid var(--bs-border-color);
    color: var(--bs-primary); font-weight: 600;
}

.profile-fields { padding: 8px; }
.profile-field {
    display: flex; align-items: center; gap: 14px; padding: 14px 12px; width: 100%;
    background: transparent; border: none; border-radius: 10px; text-align: left;
    cursor: pointer; transition: all 0.2s ease; color: var(--bs-body-color);
}
.profile-field:hover:not(.readonly) { background: rgba(var(--bs-primary-rgb), 0.05); }
.profile-field.readonly { cursor: default; }

.field-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
.name-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.phone-icon { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.email-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); } /* 🆕 */
.birthday-icon { background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); }
.city-icon { background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%); }

.field-content { flex: 1; min-width: 0; }
.field-label { font-size: 0.75rem; color: var(--bs-secondary-color); margin-bottom: 2px; }
.field-value { font-weight: 600; font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.field-action { color: var(--bs-primary); opacity: 0.6; transition: opacity 0.2s ease; }
.profile-field:hover .field-action { opacity: 1; }

.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 16px; }
.stat-item {
    background: transparent; border: none; padding: 16px 8px; border-radius: 12px;
    text-align: center; cursor: pointer; transition: all 0.2s ease; color: var(--bs-body-color);
}
.stat-item:hover { background: rgba(var(--bs-primary-rgb), 0.05); }
.stat-icon {
    width: 44px; height: 44px; border-radius: 50%; background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary); display: flex; align-items: center; justify-content: center;
    margin: 0 auto 8px; font-size: 1.1rem;
}
.stat-value { font-size: 1.3rem; font-weight: 700; color: var(--bs-primary); margin-bottom: 2px; }
.stat-label { font-size: 0.75rem; color: var(--bs-secondary-color); }

.subs-list { padding: 8px; }
.sub-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: 8px; transition: background 0.2s ease; }
.sub-item:hover { background: rgba(var(--bs-primary-rgb), 0.03); }
.sub-title { color: var(--bs-body-color); font-size: 0.9rem; }
.sub-amount { color: var(--bs-primary); font-weight: 700; }

.btn-logout {
    width: 100%; padding: 14px; background: transparent; border: 2px solid #dc3545; border-radius: 12px;
    color: #dc3545; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
    display: flex; align-items: center; justify-content: center;
}
.btn-logout:hover { background: #dc3545; color: white; }

.edit-modal, .phone-modal { border-radius: 16px; border: none; overflow: hidden; }
.edit-modal .modal-header, .phone-modal .modal-header {
    display: flex; align-items: center; padding: 20px; border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
}
.modal-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; flex-shrink: 0; }
.code-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.edit-modal .modal-body, .phone-modal .modal-body { padding: 20px; }
.form-hint { margin-top: 12px; padding: 10px 14px; background: rgba(var(--bs-primary-rgb), 0.05); border-radius: 8px; font-size: 0.85rem; color: var(--bs-secondary-color); }
.edit-modal .modal-footer, .phone-modal .modal-footer { padding: 16px 20px; border-top: 1px solid var(--bs-border-color); gap: 8px; }
.edit-modal .modal-footer .btn, .phone-modal .modal-footer .btn { border-radius: 10px; padding: 10px 20px; font-weight: 600; }

/* 🆕 Стили для аватара */
.avatar-preview-wrapper {
    width: 150px; height: 150px; border-radius: 50%; overflow: hidden; margin: 0 auto;
    border: 3px dashed var(--bs-border-color); display: flex; align-items: center; justify-content: center;
    background: var(--bs-body-bg);
}
.avatar-preview-img { width: 100%; height: 100%; object-fit: cover; }
.avatar-preview-placeholder { font-size: 3rem; color: var(--bs-secondary-color); }

.code-input-wrapper { display: flex; justify-content: center; margin: 20px 0; }
.code-input {
    width: 100%; max-width: 200px; padding: 16px; font-size: 2rem; font-weight: 700; text-align: center;
    letter-spacing: 8px; border: 2px solid var(--bs-border-color); border-radius: 12px;
    background: var(--bs-body-bg); color: var(--bs-body-color); transition: all 0.2s ease;
}
.code-input:focus { outline: none; border-color: var(--bs-primary); box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1); }

.resend-section { text-align: center; margin-top: 16px; }
.resend-timer {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05); border-radius: 20px; color: var(--bs-secondary-color); font-size: 0.9rem;
}
.btn-resend {
    background: transparent; border: none; color: var(--bs-primary); font-weight: 600; cursor: pointer;
    padding: 8px 16px; border-radius: 8px; transition: all 0.2s ease;
}
.btn-resend:hover:not(:disabled) { background: rgba(var(--bs-primary-rgb), 0.1); }

.error-message {
    padding: 10px 14px; background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 8px; color: #dc3545; font-size: 0.9rem;
}

.success-icon {
    width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    display: flex; align-items: center; justify-content: center; margin: 0 auto; animation: successPop 0.5s ease;
}
.success-icon i { font-size: 2.5rem; color: white; }
@keyframes successPop { 0% { transform: scale(0); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }

.notification-toast {
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 14px 20px;
    border-radius: 12px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15); display: flex; align-items: center; gap: 10px;
    z-index: 9999; animation: slideDown 0.3s ease; max-width: 90%;
}
.notification-toast.success { border-color: #198754; color: #198754; }
.notification-toast.error { border-color: #dc3545; color: #dc3545; }
.notification-toast.info { border-color: var(--bs-primary); color: var(--bs-primary); }
@keyframes slideDown { from { opacity: 0; transform: translate(-50%, -20px); } to { opacity: 1; transform: translate(-50%, 0); } }

@media (max-width: 576px) {
    .stats-grid { gap: 4px; }
    .stat-value { font-size: 1.1rem; }
    .code-input { font-size: 1.5rem; letter-spacing: 6px; }
}

/* 🆕 СТИЛИ ДЛЯ ДОСТИЖЕНИЙ */
.achievements-card {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 16px;
    color: white;
    text-align: left;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(118, 75, 162, 0.3);
}

.achievements-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(118, 75, 162, 0.4);
}

.achievements-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.achievements-content {
    flex: 1;
}

.achievements-content h6 {
    font-weight: 700;
    font-size: 1rem;
    margin: 0;
}

.achievements-content p {
    font-size: 0.8rem;
    opacity: 0.9;
    margin: 0;
}

.achievements-arrow {
    font-size: 1.1rem;
    opacity: 0.8;
    transition: transform 0.2s ease;
}

.achievements-card:hover .achievements-arrow {
    transform: translateX(4px);
}

.subs-list { padding: 8px; }
.sub-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: 8px; transition: background 0.2s ease; }
.sub-item:hover { background: rgba(var(--bs-primary-rgb), 0.03); }
.sub-title { color: var(--bs-body-color); font-size: 0.9rem; }
.sub-amount { color: var(--bs-primary); font-weight: 700; }

/* 🆕 СТИЛИ ДЛЯ СМЕНЫ АККАУНТА */
.btn-change-account {
    width: 100%;
    padding: 14px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
    border-radius: 12px;
    color: var(--bs-primary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-change-account:hover {
    background: rgba(var(--bs-primary-rgb), 0.1);
    border-color: var(--bs-primary);
}

.btn-logout {
    width: 100%; padding: 14px; background: transparent; border: 2px solid #dc3545; border-radius: 12px;
    color: #dc3545; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
    display: flex; align-items: center; justify-content: center;
}
.btn-logout:hover { background: #dc3545; color: white; }

.edit-modal, .phone-modal { border-radius: 16px; border: none; overflow: hidden; }
.edit-modal .modal-header, .phone-modal .modal-header {
    display: flex; align-items: center; padding: 20px; border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
}
.modal-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; flex-shrink: 0; }
.code-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.edit-modal .modal-body, .phone-modal .modal-body { padding: 20px; }
.form-hint { margin-top: 12px; padding: 10px 14px; background: rgba(var(--bs-primary-rgb), 0.05); border-radius: 8px; font-size: 0.85rem; color: var(--bs-secondary-color); }
.edit-modal .modal-footer, .phone-modal .modal-footer { padding: 16px 20px; border-top: 1px solid var(--bs-border-color); gap: 8px; }
.edit-modal .modal-footer .btn, .phone-modal .modal-footer .btn { border-radius: 10px; padding: 10px 20px; font-weight: 600; }

.avatar-preview-wrapper {
    width: 150px; height: 150px; border-radius: 50%; overflow: hidden; margin: 0 auto;
    border: 3px dashed var(--bs-border-color); display: flex; align-items: center; justify-content: center;
    background: var(--bs-body-bg);
}
.avatar-preview-img { width: 100%; height: 100%; object-fit: cover; }
.avatar-preview-placeholder { font-size: 3rem; color: var(--bs-secondary-color); }

.code-input-wrapper { display: flex; justify-content: center; margin: 20px 0; }
.code-input {
    width: 100%; max-width: 200px; padding: 16px; font-size: 2rem; font-weight: 700; text-align: center;
    letter-spacing: 8px; border: 2px solid var(--bs-border-color); border-radius: 12px;
    background: var(--bs-body-bg); color: var(--bs-body-color); transition: all 0.2s ease;
}
.code-input:focus { outline: none; border-color: var(--bs-primary); box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1); }

.resend-section { text-align: center; margin-top: 16px; }
.resend-timer {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05); border-radius: 20px; color: var(--bs-secondary-color); font-size: 0.9rem;
}
.btn-resend {
    background: transparent; border: none; color: var(--bs-primary); font-weight: 600; cursor: pointer;
    padding: 8px 16px; border-radius: 8px; transition: all 0.2s ease;
}
.btn-resend:hover:not(:disabled) { background: rgba(var(--bs-primary-rgb), 0.1); }

.error-message {
    padding: 10px 14px; background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 8px; color: #dc3545; font-size: 0.9rem;
}

.success-icon {
    width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    display: flex; align-items: center; justify-content: center; margin: 0 auto; animation: successPop 0.5s ease;
}
.success-icon i { font-size: 2.5rem; color: white; }
@keyframes successPop { 0% { transform: scale(0); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }

.notification-toast {
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 14px 20px;
    border-radius: 12px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15); display: flex; align-items: center; gap: 10px;
    z-index: 9999; animation: slideDown 0.3s ease; max-width: 90%;
}
.notification-toast.success { border-color: #198754; color: #198754; }
.notification-toast.error { border-color: #dc3545; color: #dc3545; }
.notification-toast.info { border-color: var(--bs-primary); color: var(--bs-primary); }
@keyframes slideDown { from { opacity: 0; transform: translate(-50%, -20px); } to { opacity: 1; transform: translate(-50%, 0); } }

@media (max-width: 576px) {
    .stats-grid { gap: 4px; }
    .stat-value { font-size: 1.1rem; }
    .code-input { font-size: 1.5rem; letter-spacing: 6px; }
}
</style>




