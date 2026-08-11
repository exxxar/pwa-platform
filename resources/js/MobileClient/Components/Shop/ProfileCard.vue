<template>
    <div class="profile-page">
        <div class="container py-3 pb-5">

            <!-- Шапка профиля -->
            <div class="profile-header text-center mb-4">
                <div class="avatar-wrapper mb-3">
                    <div class="avatar-circle">
                        <img
                            v-if="avatarUrl"
                            v-lazy="avatarUrl"
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
                            <div class="field-value" :class="{'text-muted': !self?.name}">{{
                                    self?.name || 'Указать имя'
                                }}
                            </div>
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
                            <div class="field-label">
                                Телефон
                                <span v-if="self?.phone" class="unverified-tag">
                                        <i class="fa-solid fa-lock"></i> без подтверждения
                                    </span>
                            </div>
                            <div class="field-value" :class="{'text-primary': !self?.phone}">
                                {{ self?.phone || 'Добавить номер' }}
                            </div>
                        </div>
                        <div class="field-action"><i :class="self?.phone ? 'fa-solid fa-pen' : 'fa-solid fa-plus'"></i>
                        </div>
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
                            <div class="field-value" :class="{'text-muted': !self?.email}">
                                {{ self?.email || 'Указать email' }}
                            </div>
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
                            <div class="field-value" :class="{'text-muted': !self?.birthday}">
                                {{ formatBirthday(self?.birthday) || 'Указать дату' }}
                            </div>
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
                            <div class="field-value" :class="{'text-muted': !self?.city}">
                                {{ self?.city || 'Указать город' }}
                            </div>
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


                    <!-- 🆕 Пароль -->
                    <button v-if="isOwnProfile" class="profile-field" @click="openPasswordModal">
                        <div class="field-icon password-icon"><i class="fa-solid fa-lock"></i></div>
                        <div class="field-content">
                            <div class="field-label">Пароль</div>
                            <div class="field-value">
                                <template v-if="hasPassword">
                                    <span class="text-success">Установлен</span>
                                    <span class="password-hint">• Сменить пароль</span>
                                </template>
                                <template v-else>
                                    <span class="text-warning">Не установлен</span>
                                    <span class="password-hint">• Установить пароль</span>
                                </template>
                            </div>
                        </div>
                        <div class="field-action">
                            <i :class="hasPassword ? 'fa-solid fa-pen' : 'fa-solid fa-plus'"></i>
                        </div>
                    </button>
                    <div v-else class="profile-field readonly">
                        <div class="field-icon password-icon"><i class="fa-solid fa-lock"></i></div>
                        <div class="field-content">
                            <div class="field-label">Пароль</div>
                            <div class="field-value">
                                <span v-if="hasPassword" class="text-success">Установлен</span>
                                <span v-else class="text-muted">Не установлен</span>
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
                        <div class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                        <div class="stat-value">{{ self?.orders_count || 0 }}</div>
                        <div class="stat-label">Заказов</div>
                    </button>
                    <button class="stat-item" @click="goToFriends">
                        <div class="stat-icon"><i class="fa-solid fa-user-group"></i></div>
                        <div class="stat-value">{{ self?.friends_count || 0 }}</div>
                        <div class="stat-label">Друзей</div>
                    </button>
                    <button class="stat-item" @click="goToCashback">
                        <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
                        <div class="stat-value">{{ self?.cashback_balance || 0 }}</div>
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

            <EditProfileButton class="mb-2" @click="$emit('profile-edit')"/>


            <AppDivider text="Управление аккаунтом"/>

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
                        <button type="button" class="btn btn-primary" :disabled="!avatarFile || avatarUploading"
                                @click="uploadAvatar">
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
                            <input v-model="editForms.name" type="text" class="form-control" id="edit-name-input"
                                   placeholder="Ваше имя" maxlength="50">
                            <label for="edit-name-input">Имя</label>
                        </div>
                        <div class="form-hint"><i class="fa-solid fa-info-circle me-1"></i> Это имя будет отображаться в
                            заказах и профиле
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!editForms.name.trim() || saving"
                                @click="saveField('name')">
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
                            <input v-model="editForms.email" type="email" class="form-control" id="edit-email-input"
                                   placeholder="example@mail.ru">
                            <label for="edit-email-input">Email</label>
                        </div>
                        <div class="form-hint"><i class="fa-solid fa-info-circle me-1"></i> На этот адрес будут
                            приходить электронные чеки
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!editForms.email.trim() || saving"
                                @click="saveField('email')">
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
                            <input v-model="editForms.birthday" type="date" class="form-control"
                                   id="edit-birthday-input" :max="maxBirthdayDate">
                            <label for="edit-birthday-input">Дата рождения</label>
                        </div>
                        <div class="form-hint"><i class="fa-solid fa-gift me-1"></i> В день рождения вам будут начислены
                            бонусные баллы
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!editForms.birthday || saving"
                                @click="saveField('birthday')">
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
                            <input v-model="editForms.city" type="text" class="form-control" id="edit-city-input"
                                   placeholder="Москва">
                            <label for="edit-city-input">Город</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" :disabled="!editForms.city.trim() || saving"
                                @click="saveField('city')">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span> Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🆕 Модалка: Пароль -->
        <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon password-icon"><i class="fa-solid fa-lock"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">
                                {{ hasPassword ? 'Сменить пароль' : 'Установить пароль' }}
                            </h5>
                            <small class="text-muted">
                                {{
                                    hasPassword
                                        ? 'Введите текущий и новый пароль'
                                        : 'Создайте пароль для входа в аккаунт'
                                }}
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Текущий пароль (ПОКАЗЫВАЕМ ТОЛЬКО ЕСЛИ ПАРОЛЬ УЖЕ ЕСТЬ) -->
                        <div v-if="hasPassword" class="form-floating mb-3">
                            <input
                                v-model="passwordForm.currentPassword"
                                type="password"
                                class="form-control"
                                id="current-password-input"
                                placeholder="Текущий пароль"
                                autocomplete="current-password"
                            >
                            <label for="current-password-input">Текущий пароль</label>
                        </div>

                        <!-- Информационный блок для новых пользователей -->
                        <div v-else class="alert alert-info mb-3 small">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <strong>Создание пароля.</strong> Ранее вы входили по SMS-коду.
                            Установите пароль, чтобы иметь альтернативный способ входа в аккаунт.
                        </div>

                        <!-- Новый пароль -->
                        <div class="form-floating mb-3">
                            <input
                                v-model="passwordForm.newPassword"
                                type="password"
                                class="form-control"
                                id="new-password-input"
                                placeholder="Новый пароль"
                                autocomplete="new-password"
                                @input="validatePasswordStrength"
                            >
                            <label for="new-password-input">Новый пароль</label>

                            <!-- Индикатор сложности пароля -->
                            <div class="password-strength mt-2">
                                <div class="strength-bars">
                                    <div class="strength-bar"
                                         :class="passwordStrength.level >= 1 ? `level-${passwordStrength.level}` : ''"></div>
                                    <div class="strength-bar"
                                         :class="passwordStrength.level >= 2 ? `level-${passwordStrength.level}` : ''"></div>
                                    <div class="strength-bar"
                                         :class="passwordStrength.level >= 3 ? `level-${passwordStrength.level}` : ''"></div>
                                    <div class="strength-bar"
                                         :class="passwordStrength.level >= 4 ? `level-${passwordStrength.level}` : ''"></div>
                                </div>
                                <div class="strength-text" :class="`text-${passwordStrength.color}`">
                                    {{ passwordStrength.text }}
                                </div>
                            </div>
                        </div>

                        <!-- Подтверждение пароля -->
                        <div class="form-floating mb-3">
                            <input
                                v-model="passwordForm.confirmPassword"
                                type="password"
                                class="form-control"
                                id="confirm-password-input"
                                placeholder="Подтвердите пароль"
                                autocomplete="new-password"
                            >
                            <label for="confirm-password-input">Подтвердите пароль</label>
                        </div>

                        <!-- Ошибки -->
                        <div v-if="passwordError" class="error-message">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> {{ passwordError }}
                        </div>

                        <!-- Подсказки -->
                        <div class="form-hint">
                            <i class="fa-solid fa-shield-halved me-1"></i>
                            Пароль должен содержать минимум 6 символов.
                            Рекомендуется использовать буквы, цифры и специальные символы.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!isPasswordFormValid || savingPassword"
                            @click="savePassword"
                        >
                            <span v-if="savingPassword" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="fa-solid fa-check me-2"></i>
                            {{ hasPassword ? 'Сменить пароль' : 'Установить пароль' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модалка: Телефон (упрощённая версия без SMS) -->
        <div class="modal fade" id="phoneModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon phone-icon"><i class="fa-solid fa-phone"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Номер телефона</h5>
                            <small class="text-muted">Для связи и уведомлений о заказах</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating mb-3">
                            <input
                                v-model="phoneForm.phone"
                                type="tel"
                                class="form-control"
                                id="phone-input"
                                placeholder="+7 (999) 123-45-67"
                                @input="formatPhoneInput"
                                :disabled="phoneSaving"
                            >
                            <label for="phone-input">Номер телефона</label>
                        </div>

                        <div v-if="phoneError" class="error-message mt-2">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> {{ phoneError }}
                        </div>

                        <!-- 🆕 Блок с замком: SMS временно недоступно -->
                        <div class="sms-disabled-notice">
                            <div class="sms-notice-icon">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <div class="sms-notice-content">
                                <div class="sms-notice-title">SMS-подтверждение временно недоступно</div>
                                <div class="sms-notice-desc">
                                    На текущий момент можно просто сохранить номер телефона.
                                    Проверка через SMS-код будет доступна позже.
                                </div>
                            </div>
                        </div>

                        <div class="form-hint mt-3">
                            <i class="fa-solid fa-shield-halved me-1"></i>
                            Номер будет использоваться для связи с вами по заказам
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!isValidPhone || phoneSaving"
                            @click="savePhone"
                        >
                            <span v-if="phoneSaving" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="fa-solid fa-floppy-disk me-2"></i>
                            {{ phoneSaving ? 'Сохраняем...' : 'Сохранить' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🆕 Модалка: Предупреждение перед выходом -->
        <div class="modal fade" id="securityCheckModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header">
                        <div class="modal-icon" style="background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%);">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Подождите!</h5>
                            <small class="text-muted">Важно сохранить данные для входа</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                @click="cancelLogoutFlow"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-3">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            Если вы сейчас выйдете, то можете <strong>потерять доступ</strong> к своему аккаунту.
                        </div>

                        <p class="mb-3 fw-semibold">Чего не хватает для безопасного входа:</p>

                        <ul class="missing-items-list mb-0">
                            <li v-if="securityCheck.needsLogin" class="missing-item">
                                <div class="missing-icon"
                                     style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                    <i class="fa-solid fa-phone"></i>
                                </div>
                                <div class="missing-content">
                                    <div class="missing-title">Телефон или Email</div>
                                    <div class="missing-desc">Нужен хотя бы один идентификатор для входа</div>
                                </div>
                            </li>
                            <li v-if="securityCheck.needsPassword" class="missing-item">
                                <div class="missing-icon"
                                     style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fa-solid fa-lock"></i>
                                </div>
                                <div class="missing-content">
                                    <div class="missing-title">Пароль</div>
                                    <div class="missing-desc">Альтернативный способ входа (кроме SMS)</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="modal-footer flex-column gap-2">
                        <button type="button" class="btn btn-primary w-100" @click="proceedToSaveAndLogout">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            Сохранить и выйти
                        </button>
                        <button type="button" class="btn btn-outline-danger w-100" @click="proceedToLogoutAnyway">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>
                            Выйти всё равно
                        </button>
                        <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal"
                                @click="cancelLogoutFlow">
                            Остаться в профиле
                        </button>
                    </div>
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
                email: '',
                city: '',
            },
            isLoggingOut: false,
            avatarFile: null,
            avatarPreview: null,
            avatarUploading: false,

            // 🆕 Упрощённые поля телефона
            phoneSaving: false,
            phoneError: '',
            phoneForm: {
                phone: '',
            },

            modals: {},
            referralLink: '',
            referralStats: {
                active_count: 0,
                total_rewards: 0
            },
            isCopying: false,

            passwordForm: {
                currentPassword: '',
                newPassword: '',
                confirmPassword: '',
            },
            passwordError: '',
            savingPassword: false,
            passwordStrength: {
                level: 0,
                text: '',
                color: 'muted',
            },

            pendingLogoutSteps: [], // Например: ['login', 'password']
            securityCheckVisible: false,

            pendingAction: null, // 'logout' | 'switchAccount'
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

        hasPassword() {
            // Проверяем, установлен ли пароль у пользователя
            // Поле has_password должно приходить с бэкенда в TenantUser
            return this.self?.has_password || false;
        },

        isPasswordFormValid() {
            const {currentPassword, newPassword, confirmPassword} = this.passwordForm;

            // 🎯 КЛЮЧЕВАЯ ПРОВЕРКА:
            // Если пароль уже установлен — требуем текущий
            if (this.hasPassword && !currentPassword.trim()) {
                return false;
            }

            // Если пароля не было — текущий не нужен, но можно проверить, что поле пустое
            if (!this.hasPassword && currentPassword.trim()) {
                // Пользователь зачем-то ввёл "текущий" — игнорируем, не блокируем
            }

            // Новый пароль минимум 6 символов
            if (!newPassword || newPassword.length < 6) return false;

            // Подтверждение должно совпадать
            if (newPassword !== confirmPassword) return false;

            return true;
        },


        needsLoginCredentials() {
            return !this.self?.phone && !this.self?.email;
        },
        securityCheck() {
            return {
                needsLogin: this.needsLoginCredentials,
                needsPassword: !this.hasPassword,
            };
        },
        hasSecurityIssues() {
            return this.securityCheck.needsLogin || this.securityCheck.needsPassword;
        },
    },

    mounted() {
        this.initModals();
        this.prefillEditForms();
        this.fetchReferralData(); // 🆕 Загружаем реферальные данные при монтировании
    },

    beforeUnmount() {
        Object.values(this.modals).forEach(modal => {
            if (modal && typeof modal.dispose === 'function') {
                modal.dispose();
            }
        });
    },

    methods: {

        async fetchReferralData() {
            try {
                // Запрашиваем ссылку и статистику параллельно
                const [linkRes, statsRes] = await Promise.all([
                    axios.get('/referrals/link').catch(() => null),
                    axios.get('/referrals/tree').catch(() => null)
                ]);

                if (linkRes?.data?.link) {
                    this.referralLink = linkRes.data.link;
                }

                if (statsRes?.data) {
                    this.referralStats = {
                        active_count: statsRes.data.active_count || 0,
                        total_rewards: statsRes.data.total_rewards || 0
                    };
                }
            } catch (error) {
                console.error('Ошибка загрузки реферальных данных:', error);
            }
        },

        async copyReferralLink() {
            if (!this.referralLink) return;
            this.isCopying = true;
            try {
                await navigator.clipboard.writeText(this.referralLink);
                this.showNotification('success', 'Ссылка скопирована в буфер обмена!', 'fa-solid fa-check-circle');
            } catch (err) {
                // Фоллбэк для старых браузеров
                const textArea = document.createElement("textarea");
                textArea.value = this.referralLink;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand("copy");
                document.body.removeChild(textArea);
                this.showNotification('success', 'Ссылка скопирована!', 'fa-solid fa-check-circle');
            } finally {
                setTimeout(() => {
                    this.isCopying = false;
                }, 1000);
            }
        },

        shareReferralLink() {
            if (navigator.share && this.referralLink) {
                navigator.share({
                    title: 'Присоединяйся!',
                    text: 'Зарегистрируйся по моей ссылке и получи бонусы!',
                    url: this.referralLink
                }).catch(console.error);
            } else {
                this.copyReferralLink();
            }
        },

        goToAchievements() {
            this.$router.push({name: 'Achievements'})
        },


        // 🆕 Пользователь выбрал "Сохранить и выйти"
        proceedToSaveAndLogout() {
            if (this.modals.securityCheck) {
                this.modals.securityCheck.hide();
            }

            const steps = [];
            if (this.securityCheck.needsLogin) steps.push('login');
            if (this.securityCheck.needsPassword) steps.push('password');

            this.pendingLogoutSteps = steps;
            this.executeNextStep();
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
                    this.modals.password = new bootstrap.Modal(document.getElementById('passwordModal'));

                    this.modals.securityCheck = new bootstrap.Modal(document.getElementById('securityCheckModal'));

                    // 🆕 Сбрасываем очередь при закрытии любой модалки без сохранения
                    ['phone', 'password', 'name', 'email'].forEach(key => {
                        const el = document.getElementById(
                            key === 'phone' ? 'phoneModal' :
                                key === 'password' ? 'passwordModal' :
                                    `edit${key.charAt(0).toUpperCase() + key.slice(1)}Modal`
                        );
                        if (el) {
                            el.addEventListener('hidden.bs.modal', () => {
                                // Если пользователь закрыл модалку крестиком/отменой — сбрасываем очередь
                                if (this.pendingLogoutSteps.length > 0) {
                                    this.cancelLogoutFlow();
                                }
                            });
                        }
                    });
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
            this.phoneForm.phone = this.self?.phone || '';
            this.phoneError = '';
            if (this.modals.phone) this.modals.phone.show();
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


        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
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

                // 🆕 Если сохраняли email в рамках выхода — переходим к следующему шагу
                if (
                    field === 'email' &&
                    this.pendingLogoutSteps.length > 0 &&
                    this.pendingLogoutSteps[0] === 'login'
                ) {
                    this.pendingLogoutSteps.shift();
                    setTimeout(() => this.executeNextStep(), 400);
                    return;
                }

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
                return date.toLocaleDateString('ru-RU', {day: 'numeric', month: 'long', year: 'numeric'});
            } catch (e) {
                return dateStr;
            }
        },

        goToOrders() {
            this.$router.push({name: 'Orders'});
        },
        goToFriends() {
            this.$router.push({name: 'ReferralsPage'});
        },
        goToCashback() {
            this.$router.push({name: 'Cashback'});
        },


        showNotification(type, text, icon) {
            this.notification = {type, text, icon};
            setTimeout(() => {
                this.notification = null;
            }, 3000);
        },


        // 🆕 Валидация сложности пароля
        validatePasswordStrength() {
            const password = this.passwordForm.newPassword;

            if (!password) {
                this.passwordStrength = {level: 0, text: '', color: 'muted'};
                return;
            }

            let score = 0;

            // Длина
            if (password.length >= 6) score++;
            if (password.length >= 10) score++;

            // Разные типы символов
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            // Нормализуем до 4 уровней
            const level = Math.min(4, Math.max(1, Math.ceil(score * 4 / 6)));

            const levels = {
                1: {text: 'Слабый', color: 'danger'},
                2: {text: 'Средний', color: 'warning'},
                3: {text: 'Хороший', color: 'info'},
                4: {text: 'Отличный', color: 'success'},
            };

            this.passwordStrength = {
                level,
                ...levels[level],
            };
        },

        async savePassword() {
            if (!this.isPasswordFormValid) return;

            this.savingPassword = true;
            this.passwordError = '';

            try {
                const payload = {
                    new_password: this.passwordForm.newPassword,
                    new_password_confirmation: this.passwordForm.confirmPassword,
                };

                // 🎯 Отправляем current_password ТОЛЬКО если он реально был
                if (this.hasPassword) {
                    payload.current_password = this.passwordForm.currentPassword;
                }

                const response = await axios.put('/profile/password', payload);

                // Обновляем статус в глобальном объекте
                if (window.TenantUser) {
                    window.TenantUser.has_password = true;
                }

                if (this.modals.password) {
                    this.modals.password.hide();
                }

                this.showNotification(
                    'success',
                    response.data.message || 'Пароль сохранён',
                    'fa-solid fa-check-circle'
                );

                // 🆕 Если это часть процесса выхода — переходим к следующему шагу
                if (this.pendingLogoutSteps.length > 0 && this.pendingLogoutSteps[0] === 'password') {
                    this.pendingLogoutSteps.shift();
                    setTimeout(() => this.executeNextStep(), 400);
                    return;
                }
            } catch (error) {
                console.error('Ошибка сохранения пароля:', error);

                const errorMsg = error.response?.data?.message
                    || error.response?.data?.errors?.current_password?.[0]
                    || error.response?.data?.errors?.new_password?.[0]
                    || 'Ошибка при сохранении пароля';

                this.passwordError = errorMsg;
            } finally {
                this.savingPassword = false;
            }
        },

        openPasswordModal() {
            this.passwordForm = {
                currentPassword: '',
                newPassword: '',
                confirmPassword: '',
            };
            this.passwordError = '';
            this.passwordStrength = {level: 0, text: '', color: 'muted'};

            if (this.modals.password) {
                this.modals.password.show();
            }
        },

        async savePhone() {
            if (!this.isValidPhone) {
                this.phoneError = 'Введите корректный номер телефона';
                return;
            }

            this.phoneSaving = true;
            this.phoneError = '';

            try {
                const response = await axios.put('/profile', {
                    phone: this.phoneForm.phone,
                });

                // Обновляем глобальный объект
                if (window.TenantUser && response.data.data) {
                    Object.assign(window.TenantUser, response.data.data);
                    // Помечаем как неподтверждённый (SMS не было)
                    window.TenantUser.phone_verified = false;
                }

                if (this.modals.phone) {
                    this.modals.phone.hide();
                }

                this.showNotification(
                    'success',
                    'Номер телефона сохранён',
                    'fa-solid fa-check-circle'
                );

                // 🆕 Если это часть процесса выхода — переходим к следующему шагу
                if (this.pendingLogoutSteps.length > 0 && this.pendingLogoutSteps[0] === 'login') {
                    this.pendingLogoutSteps.shift(); // Удаляем выполненный шаг
                    // Небольшая задержка, чтобы модалка успела закрыться
                    setTimeout(() => this.executeNextStep(), 400);
                    return;
                }
            } catch (error) {
                console.error('Ошибка сохранения телефона:', error);
                const errorMsg = error.response?.data?.errors?.phone?.[0]
                    || error.response?.data?.message
                    || 'Не удалось сохранить номер';
                this.phoneError = errorMsg;
            } finally {
                this.phoneSaving = false;
            }
        },


// 🆕 УНИВЕРСАЛЬНАЯ ПРОВЕРКА безопасности перед любым действием
        async checkSecurityAndProceed(action) {
            if (!this.hasSecurityIssues) {
                const message = 'Вы уверены, что хотите выйти из аккаунта?';
                if (confirm(message)) {
                    this.doLogout();
                }
                return;
            }

            this.pendingAction = action;
            if (this.modals.securityCheck) {
                this.modals.securityCheck.show();
            }
        },

// 🆕 Алиас для кнопки "Сменить аккаунт"
        changeAccount() {
            this.checkSecurityAndProceed('switchAccount');
        },

// 🆕 Алиас для кнопки "Выйти"
        async logout() {
            this.checkSecurityAndProceed('logout');
        },

// 🆕 ФИНАЛЬНОЕ ДЕЙСТВИЕ после всех проверок
        executeFinalAction() {
            this.doLogout();
        },

// 🆕 РЕАЛЬНЫЙ выход
        async doLogout() {
            this.isLoggingOut = true;
            try {
                await axios.post('/auth/logout');
            } catch (error) {
                console.error('Ошибка при выходе с сервера:', error);
            } finally {
                window.TenantUser = null;
                localStorage.removeItem('token');
                this.$router.push({name: 'Auth'});
                this.isLoggingOut = false;
                this.pendingAction = null;
            }
        },

// 🆕 Пользователь выбрал "Всё равно выйти/сменить"
        proceedToLogoutAnyway() {
            this.pendingLogoutSteps = [];

            if (this.modals.securityCheck) {
                const modalEl = document.getElementById('securityCheckModal');
                if (modalEl) {
                    modalEl.addEventListener('hidden.bs.modal', () => {
                        this.doLogout();
                    }, {once: true});
                }
                this.modals.securityCheck.hide();
            } else {
                this.doLogout();
            }
        },

// 🆕 Отмена процесса
        cancelLogoutFlow() {
            this.pendingLogoutSteps = [];
            this.pendingAction = null;
            this.showNotification('info', 'Действие отменено', 'fa-solid fa-circle-info');
        },

// 🆕 Выполняет следующий шаг из очереди
        executeNextStep() {
            if (this.pendingLogoutSteps.length === 0) {
                this.executeFinalAction();
                return;
            }

            const step = this.pendingLogoutSteps[0];

            if (step === 'login') {
                this.phoneForm.phone = this.self?.phone || '';
                this.phoneError = '';
                if (this.modals.phone) {
                    this.modals.phone.show();
                }
                this.showNotification('info', 'Укажите номер телефона или Email для входа', 'fa-solid fa-phone');
            } else if (step === 'password') {
                this.openPasswordModal();
                this.showNotification('info', 'Установите пароль для входа', 'fa-solid fa-lock');
            }
        },


// 🆕 СМЕНА АККАУНТА (то же самое, но семантически разделено)
        async doSwitchAccount() {
            this.isLoggingOut = true;
            try {
                await axios.post('/auth/logout');
            } catch (error) {
                console.error('Ошибка при выходе с сервера:', error);
            } finally {
                window.TenantUser = null;
                localStorage.removeItem('token');
                this.$router.push({name: 'Auth'});
                this.isLoggingOut = false;
                this.pendingAction = null;
            }
        },


    },
};
</script>

<style scoped>
.profile-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

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

.name-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.phone-icon {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.email-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

/* 🆕 */
.birthday-icon {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
}

.city-icon {
    background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
}

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

.edit-modal, .phone-modal {
    border-radius: 16px;
    border: none;
    overflow: hidden;
}

.edit-modal .modal-header, .phone-modal .modal-header {
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

.edit-modal .modal-body, .phone-modal .modal-body {
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

.edit-modal .modal-footer, .phone-modal .modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--bs-border-color);
    gap: 8px;
}

.edit-modal .modal-footer .btn, .phone-modal .modal-footer .btn {
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 600;
}

/* 🆕 Стили для аватара */
.avatar-preview-wrapper {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto;
    border: 3px dashed var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bs-body-bg);
}

.avatar-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-preview-placeholder {
    font-size: 3rem;
    color: var(--bs-secondary-color);
}

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

.error-message {
    padding: 10px 14px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 8px;
    color: #dc3545;
    font-size: 0.9rem;
}

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
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

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

.edit-modal, .phone-modal {
    border-radius: 16px;
    border: none;
    overflow: hidden;
}

.edit-modal .modal-header, .phone-modal .modal-header {
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

.edit-modal .modal-body, .phone-modal .modal-body {
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

.edit-modal .modal-footer, .phone-modal .modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--bs-border-color);
    gap: 8px;
}

.edit-modal .modal-footer .btn, .phone-modal .modal-footer .btn {
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 600;
}

.avatar-preview-wrapper {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto;
    border: 3px dashed var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bs-body-bg);
}

.avatar-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-preview-placeholder {
    font-size: 3rem;
    color: var(--bs-secondary-color);
}

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

.error-message {
    padding: 10px 14px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 8px;
    color: #dc3545;
    font-size: 0.9rem;
}

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
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

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

/* 🆕 Стили для реферального блока */
.referral-card {
    border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.03) 0%, rgba(var(--bs-primary-rgb), 0.08) 100%);
}

.referral-card .input-group input {
    border-right: none;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.referral-card .input-group .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

/* 🆕 Пароль */
.password-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.password-hint {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    font-weight: normal;
    margin-left: 4px;
}

/* Индикатор сложности пароля */
.password-strength {
    margin-top: 8px;
}

.strength-bars {
    display: flex;
    gap: 4px;
    margin-bottom: 4px;
}

.strength-bar {
    flex: 1;
    height: 4px;
    background: var(--bs-border-color);
    border-radius: 2px;
    transition: all 0.3s ease;
}

.strength-bar.level-1 {
    background: #dc3545;
}

.strength-bar.level-2 {
    background: #ffc107;
}

.strength-bar.level-3 {
    background: #0dcaf0;
}

.strength-bar.level-4 {
    background: #198754;
}

.strength-text {
    font-size: 0.75rem;
    font-weight: 600;
}


/* 🆕 Блок с замком про SMS */
.sms-disabled-notice {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(255, 193, 7, 0.08);
    border: 1px solid rgba(255, 193, 7, 0.25);
    border-radius: 12px;
    margin-top: 16px;
}

.sms-notice-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.sms-notice-content {
    flex: 1;
    min-width: 0;
}

.sms-notice-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--bs-body-color);
    margin-bottom: 3px;
}

.sms-notice-desc {
    font-size: 0.78rem;
    color: var(--bs-secondary-color);
    line-height: 1.4;
}

/* 🆕 Бейдж "без подтверждения" */
.unverified-tag {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-left: 6px;
    padding: 1px 6px;
    background: rgba(255, 193, 7, 0.12);
    color: #d97706;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 600;
    vertical-align: middle;
}

.unverified-tag i {
    font-size: 0.6rem;
}

/* 🆕 Стили для модалки предупреждения выхода */
.missing-items-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.missing-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: rgba(var(--bs-primary-rgb), 0.04);
    border-radius: 12px;
    margin-bottom: 8px;
    transition: background 0.2s ease;
}

.missing-item:last-child {
    margin-bottom: 0;
}

.missing-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.missing-content {
    flex: 1;
    min-width: 0;
}

.missing-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.missing-desc {
    font-size: 0.78rem;
    color: var(--bs-secondary-color);
    line-height: 1.3;
}

.modal-footer.flex-column .btn {
    border-radius: 10px;
}

#securityCheckModal .alert-warning {
    border-radius: 12px;
    border: none;
    font-size: 0.9rem;
    line-height: 1.5;
}
</style>




