<template>
    <div class="admin-user-details-page">
        <!-- ========================================== -->
        <!-- ШАПКА СТРАНИЦЫ -->
        <!-- ========================================== -->
        <div class="page-header">
            <router-link to="/admin/clients" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> К списку клиентов
            </router-link>

            <div class="header-user-info" v-if="user && !loading">
                <div class="user-avatar-large" :class="getStatusClass">
                    {{ getInitials(user.name) }}
                </div>
                <div class="user-main-data">
                    <h1 class="user-name">{{ user.name || 'Без имени' }}</h1>
                    <div class="user-meta">
                        <span class="meta-id">ID: {{ user.id }}</span>
                        <span class="meta-separator">·</span>
                        <span class="meta-phone" v-if="user.phone">{{ user.phone }}</span>
                    </div>
                    <div class="user-badges">
                        <span class="badge" :class="{ 'badge-success': user.is_active, 'badge-danger': !user.is_active }">
                            {{ user.is_active ? 'Активен' : 'Неактивен' }}
                        </span>
                        <span v-if="user.is_vip" class="badge badge-warning">
                            <i class="fa-solid fa-crown me-1"></i> VIP
                        </span>
                        <span v-if="user.blocked_at" class="badge badge-danger">
                            <i class="fa-solid fa-ban me-1"></i> Заблокирован
                        </span>
                    </div>
                </div>
            </div>

            <div class="header-actions">
                <button v-if="!isEditing && user?.dialog_id" @click="openChat" class="btn-action secondary">
                    <i class="fa-solid fa-comment"></i> Написать
                </button>
                <button v-if="!isEditing" @click="toggleEditMode" class="btn-action primary">
                    <i class="fa-solid fa-pen"></i> Редактировать
                </button>
                <template v-else>
                    <button @click="cancelEdit" class="btn-action secondary" :disabled="isSaving">
                        Отмена
                    </button>
                    <button @click="saveChanges" class="btn-action primary" :disabled="isSaving || !hasChanges">
                        <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                        <span v-else><i class="fa-solid fa-check"></i> Сохранить</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА -->
        <!-- ========================================== -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Загрузка профиля пользователя...</p>
        </div>

        <!-- ========================================== -->
        <!-- РЕЖИМ ПРОСМОТРА -->
        <!-- ========================================== -->
        <div v-else-if="user && !isEditing" class="content-grid">
            <!-- Левая колонка -->
            <div class="column-left">
                <!-- Контакты -->
                <div class="info-card">
                    <h3 class="card-title"><i class="fa-solid fa-address-book"></i> Контактная информация</h3>
                    <div class="info-list">
                        <!-- Телефон -->
                        <div class="info-item">
                            <i class="fa-solid fa-phone"></i>
                            <div>
                                <span class="label">Телефон</span>
                                <span class="value" :class="{ 'value-empty': !user.phone }">
                    {{ user.phone || 'Не указано' }}
                </span>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="info-item">
                            <i class="fa-solid fa-envelope"></i>
                            <div>
                                <span class="label">Email</span>
                                <span class="value" :class="{ 'value-empty': !user.email }">
                    {{ user.email || 'Не указано' }}
                </span>
                            </div>
                        </div>

                        <!-- День рождения -->
                        <div class="info-item">
                            <i class="fa-solid fa-cake-candles"></i>
                            <div>
                                <span class="label">День рождения</span>
                                <span class="value" :class="{ 'value-empty': !user.birthday }">
                    {{ user.birthday ? formatDate(user.birthday) : 'Не указано' }}
                </span>
                            </div>
                        </div>

                        <!-- Пол -->
                        <div class="info-item">
                            <i class="fa-solid fa-venus-mars"></i>
                            <div>
                                <span class="label">Пол</span>
                                <span class="value" :class="{ 'value-empty': !user.sex }">
                    {{ user.sex ? getSexLabel(user.sex) : 'Не указано' }}
                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Статистика -->
                <div class="info-card">
                    <h3 class="card-title"><i class="fa-solid fa-chart-simple"></i> Статистика</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon referrals"><i class="fa-solid fa-users"></i></div>
                            <div class="stat-info">
                                <div class="stat-value">{{ user.referrals_count || 0 }}</div>
                                <div class="stat-label">Рефералов</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon friends"><i class="fa-solid fa-heart"></i></div>
                            <div class="stat-info">
                                <div class="stat-value">{{ user.friends_count || 0 }}</div>
                                <div class="stat-label">Друзей</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon earnings"><i class="fa-solid fa-coins"></i></div>
                            <div class="stat-info">
                                <div class="stat-value">{{ formatPrice(user.total_referral_earnings) }}</div>
                                <div class="stat-label">Заработано</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon cashback"><i class="fa-solid fa-wallet"></i></div>
                            <div class="stat-info">
                                <div class="stat-value">{{ formatPrice(user.cashback_balance) }}</div>
                                <div class="stat-label">Кэшбэк</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Реферальная ссылка -->
                <div class="info-card" v-if="user.referral_link">
                    <h3 class="card-title"><i class="fa-solid fa-link"></i> Реферальная ссылка</h3>
                    <div class="referral-link">
                        <input type="text" :value="user.referral_link" readonly class="link-input" ref="referralInput">
                        <button class="copy-btn" @click="copyReferralLink">
                            <i class="fa-solid fa-copy"></i> {{ copied ? 'Скопировано!' : 'Копировать' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Правая колонка -->
            <div class="column-right">
                <!-- Статусы -->
                <div class="info-card">
                    <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Статусы аккаунта</h3>
                    <div class="status-grid">
                        <div class="status-item" :class="{ 'is-active': user.is_active }">
                            <i :class="user.is_active ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'"></i>
                            <span>{{ user.is_active ? 'Активен' : 'Заблокирован' }}</span>
                        </div>
                        <div class="status-item" :class="{ 'is-vip': user.is_vip }">
                            <i class="fa-solid fa-crown"></i>
                            <span>{{ user.is_vip ? 'VIP клиент' : 'Обычный' }}</span>
                        </div>
                    </div>
                    <div v-if="user.is_vip && user.vip_expires_at" class="vip-info">
                        <i class="fa-solid fa-calendar"></i> VIP до: {{ formatDate(user.vip_expires_at) }}
                    </div>
                    <div v-if="user.blocked_at" class="blocked-info">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Заблокирован</strong>
                            <p>{{ formatDate(user.blocked_at) }} <span v-if="user.blocked_message">: {{ user.blocked_message }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Настройки -->
                <div class="info-card" v-if="user.settings">
                    <h3 class="card-title"><i class="fa-solid fa-sliders"></i> Настройки</h3>
                    <div class="settings-list">
                        <div class="setting-item">
                            <span>Рассылка от бота</span>
                            <span class="setting-value" :class="user.settings.need_bot_mailing ? 'active' : 'inactive'">
                                {{ user.settings.need_bot_mailing ? 'Включена' : 'Выключена' }}
                            </span>
                        </div>
                        <div class="setting-item" v-if="user.settings.coffee">
                            <span>Кофейная программа</span>
                            <span class="setting-value">{{ user.settings.coffee.count || 0 }} / 7</span>
                        </div>
                        <div class="setting-item" v-if="user.settings.current_promocodes?.length">
                            <span>Активные промокоды</span>
                            <span class="setting-value">{{ user.settings.current_promocodes.length }} шт.</span>
                        </div>
                    </div>
                </div>

                <!-- Адреса -->
                <div class="info-card" v-if="user.addresses?.length">
                    <h3 class="card-title"><i class="fa-solid fa-location-dot"></i> Адреса ({{ user.addresses.length }})</h3>
                    <div class="addresses-list">
                        <div v-for="address in user.addresses" :key="address.id" class="address-card" :class="{ 'is-default': address.id === user.default_address }">
                            <i class="fa-solid fa-house"></i>
                            <div class="address-info">
                                <div class="address-title">
                                    {{ address.title || 'Адрес' }}
                                    <span v-if="address.id === user.default_address" class="default-badge">Основной</span>
                                </div>
                                <div class="address-text">{{ address.address }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Даты -->
                <div class="info-card">
                    <h3 class="card-title"><i class="fa-solid fa-clock"></i> Даты</h3>
                    <div class="dates-list">
                        <div class="date-item">
                            <span class="label">Регистрация</span>
                            <span class="value">{{ formatDateTime(user.created_at) }}</span>
                        </div>
                        <div class="date-item">
                            <span class="label">Последнее обновление</span>
                            <span class="value">{{ formatDateTime(user.updated_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- РЕЖИМ РЕДАКТИРОВАНИЯ -->
        <!-- ========================================== -->
        <div v-else-if="isEditing && user" class="edit-mode-container">
            <div class="edit-tabs">
                <button type="button" class="edit-tab" :class="{ 'is-active': activeTab === 'main' }" @click="activeTab = 'main'">
                    <i class="fa-solid fa-user"></i> <span>Основное</span>
                </button>
                <button type="button" class="edit-tab" :class="{ 'is-active': activeTab === 'status' }" @click="activeTab = 'status'">
                    <i class="fa-solid fa-toggle-on"></i> <span>Статусы</span>
                </button>
                <button type="button" class="edit-tab" :class="{ 'is-active': activeTab === 'roles' }" @click="activeTab = 'roles'">
                    <i class="fa-solid fa-user-tag"></i> <span>Роли</span>
                </button>
                <button type="button" class="edit-tab" :class="{ 'is-active': activeTab === 'security' }" @click="activeTab = 'security'">
                    <i class="fa-solid fa-shield-halved"></i> <span>Безопасность</span>
                </button>
            </div>

            <div class="edit-content">
                <!-- ТАБ: ОСНОВНОЕ -->
                <div v-if="activeTab === 'main'" class="tab-panel">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fa-solid fa-id-card"></i> Личные данные</h4>
                        <div class="form-field">
                            <label>Имя <span class="required">*</span></label>
                            <input type="text" v-model="form.name" :class="{ 'has-error': errors.name }">
                            <span v-if="errors.name" class="field-error">{{ errors.name }}</span>
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label>Телефон</label>
                                <input type="tel" v-model="form.phone" @input="formatPhone">
                            </div>
                            <div class="form-field">
                                <label>Email</label>
                                <input type="email" v-model="form.email" :class="{ 'has-error': errors.email }">
                                <span v-if="errors.email" class="field-error">{{ errors.email }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label>Дата рождения</label>
                                <input type="date" v-model="form.birthday" :max="maxBirthday">
                            </div>
                            <div class="form-field">
                                <label>Пол</label>
                                <select v-model="form.sex">
                                    <option value="">Не указан</option>
                                    <option value="male">Мужской</option>
                                    <option value="female">Женский</option>
                                    <option value="other">Другой</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ТАБ: СТАТУСЫ -->
                <div v-if="activeTab === 'status'" class="tab-panel">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fa-solid fa-circle-check"></i> Статусы аккаунта</h4>
                        <div class="toggle-card">
                            <div class="toggle-info">
                                <div class="toggle-icon active"><i class="fa-solid fa-user-check"></i></div>
                                <div><h5>Активный аккаунт</h5><p>Пользователь может пользоваться сервисом</p></div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" v-model="form.is_active">
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-card" :class="{ 'is-vip': form.is_vip }">
                            <div class="toggle-info">
                                <div class="toggle-icon vip"><i class="fa-solid fa-crown"></i></div>
                                <div><h5>VIP статус</h5><p>Расширенные возможности и привилегии</p></div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" v-model="form.is_vip">
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                        <transition name="fade">
                            <div v-if="form.is_vip" class="vip-settings">
                                <div class="form-field">
                                    <label>Дата окончания VIP</label>
                                    <input type="date" v-model="form.vip_expires_at" :min="minVipDate">
                                    <span class="field-hint">Оставьте пустым для бессрочного VIP</span>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- ТАБ: РОЛИ -->
                <div v-if="activeTab === 'roles'" class="tab-panel">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fa-solid fa-user-tag"></i> Роли и доступы</h4>
                        <div v-if="loadingRoles" class="roles-loading">
                            <div class="spinner small"></div> <span>Загружаем роли...</span>
                        </div>
                        <div v-else>
                            <p class="roles-hint">Выберите роли для этого пользователя.</p>
                            <div class="roles-list">
                                <div v-for="role in availableRoles" :key="role.id" class="role-chip" :class="{ 'is-selected': form.role_ids.includes(role.id) }" @click="toggleRole(role.id)">
                                    <div class="role-icon"><i class="fa-solid" :class="getRoleIcon(role.name)"></i></div>
                                    <div class="role-info">
                                        <span class="role-name">{{ role.label }}</span>
                                        <span class="role-count">{{ role.permissions_count || 0 }} прав</span>
                                    </div>
                                    <div class="role-check"><i v-if="form.role_ids.includes(role.id)" class="fa-solid fa-check"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ТАБ: БЕЗОПАСНОСТЬ -->
                <div v-if="activeTab === 'security'" class="tab-panel">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fa-solid fa-ban"></i> Блокировка</h4>
                        <div class="toggle-card" :class="{ 'is-danger': form.blocked }">
                            <div class="toggle-info">
                                <div class="toggle-icon danger"><i class="fa-solid fa-user-slash"></i></div>
                                <div><h5>Заблокировать пользователя</h5><p>Пользователь не сможет пользоваться сервисом</p></div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" v-model="form.blocked">
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                        <transition name="fade">
                            <div v-if="form.blocked" class="block-settings">
                                <div class="form-field">
                                    <label>Причина блокировки</label>
                                    <textarea v-model="form.blocked_message" rows="3" placeholder="Укажите причину блокировки..."></textarea>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="empty-state">
            <i class="fa-solid fa-user-slash"></i>
            <p>Пользователь не найден</p>
            <router-link to="/admin/clients" class="btn-back">Вернуться к списку</router-link>
        </div>
    </div>
</template>

<script>
import { useClients } from '@/MobileClient/Composables/useClients.js'; // Или useUsers, в зависимости от вашей архитектуры
import axios from 'axios';

export default {
    name: 'AdminUserDetails',

    setup() {
        const clients = useClients();
        return { ...clients }; // Предполагаем, что здесь есть loadReceiverUserData и updateUser
    },

    data() {
        return {
            user: null,
            loading: true,
            isEditing: false,
            isSaving: false,
            loadingRoles: false,
            copied: false,
            activeTab: 'main',

            form: {
                name: '', email: '', phone: '', birthday: '', sex: '',
                is_active: true, is_vip: false, vip_expires_at: '',
                blocked: false, blocked_message: '', role_ids: []
            },
            initialForm: {},
            errors: {},
            availableRoles: []
        };
    },

    computed: {
        hasChanges() {
            return JSON.stringify(this.form) !== JSON.stringify(this.initialForm);
        },
        maxBirthday() {
            return new Date().toISOString().split('T')[0];
        },
        minVipDate() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            return tomorrow.toISOString().split('T')[0];
        },
        getStatusClass() {
            if (this.user?.blocked_at) return 'status-blocked';
            if (this.user?.is_active) return 'status-active';
            return 'status-inactive';
        }
    },

    async mounted() {
        await this.loadUserData();
    },

    methods: {
        async loadUserData() {
            this.loading = true;
            try {
                const userId = this.$route.params.id;
                // Адаптируйте имя метода под ваш composable (например, getUserById или loadReceiverUserData)
                await this.loadReceiverUserData(userId);

                if (this.receiverUserData) {
                    this.user = this.receiverUserData;
                    this.initializeForm(this.user);
                }
            } catch (error) {
                console.error('Ошибка загрузки:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить данные', type: 'error' });
            } finally {
                this.loading = false;
            }
        },

        initializeForm(user) {
            this.form = {
                name: user.name || '',
                email: user.email || '',
                phone: user.phone || '',
                birthday: user.birthday ? user.birthday.split(' ')[0] : '',
                sex: user.sex || '',
                is_active: user.is_active ?? true,
                is_vip: user.is_vip ?? false,
                vip_expires_at: user.vip_expires_at ? user.vip_expires_at.split(' ')[0] : '',
                blocked: !!user.blocked_at,
                blocked_message: user.blocked_message || '',
                role_ids: user.roles ? user.roles.map(r => r.id) : [],
            };
            this.initialForm = JSON.parse(JSON.stringify(this.form));
        },

        toggleEditMode() {
            this.isEditing = true;
            this.loadRoles();
        },

        cancelEdit() {
            this.isEditing = false;
            this.initializeForm(this.user); // Сброс изменений
            this.errors = {};
        },

        async loadRoles() {
            if (this.availableRoles.length > 0) return;
            this.loadingRoles = true;
            try {
                const response = await axios.get('/admin/roles');
                this.availableRoles = response.data.data || response.data;
            } catch (error) {
                console.error('Ошибка загрузки ролей:', error);
            } finally {
                this.loadingRoles = false;
            }
        },

        toggleRole(roleId) {
            const index = this.form.role_ids.indexOf(roleId);
            if (index === -1) this.form.role_ids.push(roleId);
            else this.form.role_ids.splice(index, 1);
        },

        getRoleIcon(roleName) {
            const icons = { 'super_admin': 'fa-crown', 'admin': 'fa-user-shield', 'worker': 'fa-user-gear', 'user': 'fa-user', 'delivery': 'fa-motorcycle' };
            return icons[roleName] || 'fa-user-tag';
        },

        formatPhone(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('8')) value = '7' + value.slice(1);
            if (!value.startsWith('7') && value.length > 0) value = '7' + value;
            let formatted = '';
            if (value.length > 0) formatted = '+' + value[0];
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 4) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 7) formatted += '-' + value.slice(7, 9);
            if (value.length >= 9) formatted += '-' + value.slice(9, 11);
            this.form.phone = formatted;
        },

        validate() {
            this.errors = {};
            if (!this.form.name || this.form.name.trim().length < 2) this.errors.name = 'Имя должно содержать минимум 2 символа';
            if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) this.errors.email = 'Некорректный email';
            return Object.keys(this.errors).length === 0;
        },

        async saveChanges() {
            if (!this.validate()) {
                this.$notify?.({ title: 'Ошибка', text: 'Проверьте правильность заполнения полей', type: 'error' });
                return;
            }

            this.isSaving = true;
            try {
                const data = {
                    name: this.form.name, email: this.form.email || null, phone: this.form.phone || null,
                    birthday: this.form.birthday || null, sex: this.form.sex || null,
                    is_active: this.form.is_active, is_vip: this.form.is_vip,
                    vip_expires_at: this.form.vip_expires_at || null, role_ids: this.form.role_ids
                };

                // Адаптируйте имя метода под ваш composable (например, updateUser)
                await this.updateUser(this.user.id, data);

                if (this.form.blocked !== !!this.user.blocked_at) {
                    await this.toggleBlock(this.user.id, { block: this.form.blocked, message: this.form.blocked_message });
                }

                this.$notify?.({ title: 'Успешно', text: 'Профиль обновлён', type: 'success' });
                this.isEditing = false;
                await this.loadUserData(); // Перезагружаем актуальные данные
            } catch (error) {
                console.error('[UserEdit] Ошибка:', error);
                this.$notify?.({ title: 'Ошибка', text: error.response?.data?.message || 'Не удалось сохранить', type: 'error' });
            } finally {
                this.isSaving = false;
            }
        },

        openChat() {
            this.$router.push({ name: 'Chat', params: { userId: this.user.id } });
        },

        async copyReferralLink() {
            try {
                await navigator.clipboard.writeText(this.user.referral_link);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            } catch (err) {
                this.$refs.referralInput.select();
                document.execCommand('copy');
            }
        },

        // Хелперы форматирования
        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        },
        getSexLabel(sex) {
            const labels = { male: 'Мужской', female: 'Женский', other: 'Другой' };
            return labels[sex] || sex;
        },
        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ru-RU', { day: '2-digit', month: 'long', year: 'numeric' });
        },
        formatDateTime(date) {
            if (!date) return '';
            return new Date(date).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(price || 0);
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$bg: #f8fafc;
$card-bg: #ffffff;
$text: #0f172a;
$text-muted: #64748b;
$border: #e2e8f0;

.admin-user-details-page {
    padding: 24px;
    max-width: 1200px;
    margin: 0 auto;
    background: $bg;
    min-height: 100vh;
}

// --- ШАПКА ---
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.btn-back {
    text-decoration: none;
    color: $text-muted;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.2s;

    &:hover { color: $primary; background: rgba($primary, 0.05); }
}

.header-user-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.user-avatar-large {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    font-weight: 700;
    color: white;
    flex-shrink: 0;

    &.status-active { background: linear-gradient(135deg, $success, #059669); }
    &.status-inactive { background: linear-gradient(135deg, $text-muted, #475569); }
    &.status-blocked { background: linear-gradient(135deg, $danger, #dc2626); }
}

.user-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 4px;
}

.user-meta {
    font-size: 0.85rem;
    color: $text-muted;
    margin-bottom: 8px;
    font-family: monospace;
}

.user-badges {
    display: flex;
    gap: 8px;
}

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    &.badge-success { background: rgba($success, 0.1); color: $success; }
    &.badge-warning { background: rgba($warning, 0.1); color: #b45309; }
    &.badge-danger { background: rgba($danger, 0.1); color: $danger; }
}

.header-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    border: none;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;

    &.primary {
        background: $primary;
        color: white;
        &:hover:not(:disabled) { background: $primary-dark; transform: translateY(-1px); }
    }
    &.secondary {
        background: $card-bg;
        color: $text;
        border: 1px solid $border;
        &:hover:not(:disabled) { border-color: $primary; color: $primary; }
    }
    &:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
}

// --- СЕТКА КОНТЕНТА ---
.content-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 24px;
}

@media (max-width: 900px) {
    .content-grid { grid-template-columns: 1fr; }
}

.info-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;

    i { color: $primary; }
}

// Элементы просмотра
.info-list, .settings-list, .dates-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-item, .setting-item, .date-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: $bg;
    border-radius: 10px;

    > i {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba($primary, 0.1);
        color: $primary;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    > div { flex: 1; min-width: 0; }
    .label { display: block; font-size: 0.75rem; color: $text-muted; margin-bottom: 2px; }
    .value { display: block; font-size: 0.9rem; color: $text; font-weight: 600; word-break: break-all; }
}

.setting-item { justify-content: space-between; }
.setting-value { font-weight: 600; &.active { color: $success; } &.inactive { color: $text-muted; } }
.date-item { justify-content: space-between; }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: $bg;
    border-radius: 10px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    &.referrals { background: linear-gradient(135deg, $primary, $primary-dark); }
    &.friends { background: linear-gradient(135deg, $danger, #dc2626); }
    &.earnings { background: linear-gradient(135deg, $success, #059669); }
    &.cashback { background: linear-gradient(135deg, $warning, #d97706); }
}

.stat-info { flex: 1; }
.stat-value { font-size: 1.1rem; font-weight: 800; color: $text; line-height: 1; margin-bottom: 2px; }
.stat-label { font-size: 0.75rem; color: $text-muted; }

.referral-link {
    display: flex;
    gap: 8px;
}
.link-input {
    flex: 1;
    padding: 10px 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: 0.85rem;
    font-family: monospace;
    color: $text;
}
.copy-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    &:hover { background: $primary-dark; }
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
}
.status-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: $bg;
    border-radius: 10px;
    font-size: 0.85rem;
    color: $text-muted;
    &.is-active { background: rgba($success, 0.1); color: $success; }
    &.is-vip { background: rgba($warning, 0.1); color: #b45309; }
}
.vip-info, .blocked-info {
    margin-top: 12px;
    padding: 12px;
    border-radius: 10px;
    display: flex;
    gap: 10px;
    font-size: 0.85rem;
    i { margin-top: 2px; }
}
.vip-info { background: rgba($warning, 0.08); color: $text; i { color: $warning; } }
.blocked-info { background: rgba($danger, 0.08); border: 1px solid rgba($danger, 0.2); i { color: $danger; } strong { color: $danger; display: block; margin-bottom: 4px; } p { margin: 0; color: $text-muted; font-size: 0.8rem; } }

.addresses-list { display: flex; flex-direction: column; gap: 8px; }
.address-card {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    background: $bg;
    border-radius: 10px;
    border: 1px solid $border;
    &.is-default { border-color: $primary; background: rgba($primary, 0.03); }
    > i { color: $primary; margin-top: 2px; }
}
.address-title { font-weight: 600; font-size: 0.9rem; color: $text; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.default-badge { padding: 2px 8px; background: $primary; color: white; border-radius: 6px; font-size: 0.65rem; font-weight: 700; }
.address-text { font-size: 0.8rem; color: $text-muted; }

// --- РЕДАКТИРОВАНИЕ ---
.edit-mode-container {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    overflow: hidden;
}

.edit-tabs {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
    background: $bg;
    border-bottom: 1px solid $border;
}

.edit-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(.is-active) { color: $text; background: rgba($primary, 0.05); }
    &.is-active { background: $card-bg; color: $primary; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); }
}

.edit-content { padding: 24px; }
.tab-panel { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

.form-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid $border;
    i { color: $primary; }
}

.form-field { margin-bottom: 16px; &:last-child { margin-bottom: 0; } }
.form-field label { display: block; font-size: 0.8rem; font-weight: 600; color: $text; margin-bottom: 6px; .required { color: $danger; } }
.form-field input, .form-field select, .form-field textarea {
    width: 100%; padding: 10px 12px; background: $card-bg; border: 1.5px solid $border; border-radius: 10px; font-size: 0.9rem; color: $text; font-family: inherit; transition: all 0.2s;
    &:focus { outline: none; border-color: $primary; box-shadow: 0 0 0 3px rgba($primary, 0.1); }
    &.has-error { border-color: $danger; }
}
.form-field textarea { resize: vertical; min-height: 80px; }
.field-error { display: block; margin-top: 4px; font-size: 0.75rem; color: $danger; }
.field-hint { display: block; margin-top: 4px; font-size: 0.75rem; color: $text-muted; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.toggle-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    margin-bottom: 12px;
    &.is-vip { background: rgba($warning, 0.05); border-color: rgba($warning, 0.3); }
    &.is-danger { background: rgba($danger, 0.05); border-color: rgba($danger, 0.3); }
}
.toggle-info { display: flex; align-items: center; gap: 12px; flex: 1; h5 { margin: 0 0 2px; font-size: 0.9rem; font-weight: 700; } p { margin: 0; font-size: 0.75rem; color: $text-muted; } }
.toggle-icon { width: 40px; height: 40px; border-radius: 10px; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; &.active { background: linear-gradient(135deg, $success, #059669); } &.vip { background: linear-gradient(135deg, $warning, #d97706); } &.danger { background: linear-gradient(135deg, $danger, #dc2626); } }

.switch { position: relative; display: inline-block; width: 48px; height: 28px; flex-shrink: 0;
    input { opacity: 0; width: 0; height: 0; &:checked + .switch-slider { background: $primary; &::before { transform: translateX(20px); } } }
}
.switch-slider { position: absolute; cursor: pointer; inset: 0; background: $border; border-radius: 28px; transition: 0.3s; &::before { position: absolute; content: ''; height: 22px; width: 22px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15); } }

.vip-settings, .block-settings { margin-top: 12px; padding: 16px; background: $card-bg; border-radius: 12px; border: 1px solid $border; }

.roles-loading { display: flex; align-items: center; gap: 10px; padding: 20px; color: $text-muted; }
.spinner { width: 32px; height: 32px; border: 3px solid $border; border-top-color: $primary; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 12px; &.small { width: 20px; height: 20px; border-width: 2px; margin: 0; } }
@keyframes spin { to { transform: rotate(360deg); } }

.roles-hint { font-size: 0.85rem; color: $text-muted; margin: 0 0 16px; }
.roles-list { display: flex; flex-direction: column; gap: 8px; }
.role-chip { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: $card-bg; border: 1.5px solid $border; border-radius: 12px; cursor: pointer; transition: all 0.2s; &:hover { border-color: $primary; background: rgba($primary, 0.02); } &.is-selected { border-color: $primary; background: rgba($primary, 0.08); } }
.role-icon { width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, $primary, $primary-dark); color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.role-info { flex: 1; min-width: 0; }
.role-name { display: block; font-weight: 600; font-size: 0.9rem; color: $text; margin-bottom: 2px; }
.role-count { display: block; font-size: 0.75rem; color: $text-muted; }
.role-check { width: 24px; height: 24px; border-radius: 50%; background: $primary; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; opacity: 0; transition: opacity 0.2s; .is-selected & { opacity: 1; } }

// Общие состояния
.loading-state, .empty-state { text-align: center; padding: 80px 20px; color: $text-muted; p { margin-top: 12px; font-size: 1rem; } }

@media (max-width: 640px) {
    .admin-user-details-page { padding: 16px; }
    .page-header { flex-direction: column; align-items: flex-start; }
    .header-user-info { width: 100%; }
    .header-actions { width: 100%; .btn-action { flex: 1; justify-content: center; } }
    .form-row { grid-template-columns: 1fr; }
    .edit-tab span { display: none; }
}

.value {
    display: block;
    font-size: 0.9rem;
    color: $text;
    font-weight: 600;
    word-break: break-all;

    // 🔥 Новый класс для пустых значений
    &.value-empty {
        color: $text-muted;
        font-weight: 400;
        font-style: italic;
    }
}
</style>
