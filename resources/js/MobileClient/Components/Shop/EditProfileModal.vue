<template>
    <div
        class="modal fade"
        id="editProfileModal"
        tabindex="-1"
        aria-labelledby="editProfileModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">

                <!-- ШАПКА СНАРУЖИ FORM -->
                <div class="modal-header">
                    <div class="modal-header-content">
                        <div class="modal-icon">
                            <i class="fa-solid fa-user-pen"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" id="editProfileModalLabel">
                                Редактирование профиля
                            </h5>
                            <small class="text-muted">Заполните информацию о себе</small>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"
                    ></button>
                </div>

                <!-- FORM ОБОРАЧИВАЕТ ТОЛЬКО BODY И FOOTER -->
                <form @submit.prevent="submitProfile" class="d-flex flex-column flex-grow-1">

                    <div class="modal-body">
                        <div class="form-container">

                            <!-- ФИО -->
                            <div class="form-section">
                                <div class="form-floating mb-3">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="edit-name"
                                        v-model="form.name"
                                        placeholder="Иванов Иван Иванович"
                                        required
                                        maxlength="100"
                                    >
                                    <label for="edit-name">
                                        <i class="fa-solid fa-user me-1"></i>
                                        Ф.И.О пользователя
                                    </label>
                                </div>
                            </div>

                            <!-- Контакты -->
                            <div class="form-section">
                                <h6 class="form-section-title">
                                    <i class="fa-solid fa-address-book"></i>
                                    Контактные данные
                                </h6>

                                <div class="form-floating mb-3">
                                    <input
                                        type="tel"
                                        class="form-control"
                                        id="edit-phone"
                                        v-model="form.phone"
                                        placeholder="+7 (999) 123-45-67"
                                        @input="formatPhone"
                                        required
                                    >
                                    <label for="edit-phone">
                                        <i class="fa-solid fa-phone me-1"></i>
                                        Телефон
                                    </label>
                                </div>

                                <div class="form-floating mb-3">
                                    <input
                                        type="email"
                                        class="form-control"
                                        id="edit-email"
                                        v-model="form.email"
                                        placeholder="example@gmail.com"
                                    >
                                    <label for="edit-email">
                                        <i class="fa-solid fa-envelope me-1"></i>
                                        Почта
                                    </label>
                                </div>
                            </div>

                            <!-- Адрес -->
                            <div class="form-section">
                                <h6 class="form-section-title">
                                    <i class="fa-solid fa-location-dot"></i>
                                    Адрес
                                </h6>

                                <div class="form-floating mb-3">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="edit-address"
                                        v-model="form.address"
                                        placeholder="ул. Петрова, 123, кв 45"
                                    >
                                    <label for="edit-address">
                                        <i class="fa-solid fa-house me-1"></i>
                                        Адрес
                                    </label>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-floating">
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="edit-city"
                                                v-model="form.city"
                                                placeholder="Краснодар"
                                            >
                                            <label for="edit-city">
                                                <i class="fa-solid fa-city me-1"></i>
                                                Город
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-floating">
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="edit-country"
                                                v-model="form.country"
                                                placeholder="Россия"
                                            >
                                            <label for="edit-country">
                                                <i class="fa-solid fa-globe me-1"></i>
                                                Страна
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Личные данные -->
                            <div class="form-section">
                                <h6 class="form-section-title">
                                    <i class="fa-solid fa-user-tag"></i>
                                    Личные данные
                                </h6>

                                <div class="form-floating mb-3">
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="edit-birthday"
                                        v-model="form.birthday"
                                        :max="maxBirthdayDate"
                                    >
                                    <label for="edit-birthday">
                                        <i class="fa-solid fa-cake-candles me-1"></i>
                                        Дата рождения
                                    </label>
                                </div>

                                <div class="gender-selector">
                                    <label class="selector-label">
                                        <i class="fa-solid fa-venus-mars me-1"></i>
                                        Пол
                                    </label>
                                    <div class="selector-buttons">
                                        <button
                                            type="button"
                                            class="selector-btn"
                                            :class="{ active: form.sex === true }"
                                            @click="form.sex = true"
                                        >
                                            <i class="fa-solid fa-mars"></i>
                                            Мужской
                                        </button>
                                        <button
                                            type="button"
                                            class="selector-btn"
                                            :class="{ active: form.sex === false }"
                                            @click="form.sex = false"
                                        >
                                            <i class="fa-solid fa-venus"></i>
                                            Женский
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Уведомления -->
                            <div class="form-section">
                                <h6 class="form-section-title">
                                    <i class="fa-solid fa-bell"></i>
                                    Уведомления
                                </h6>

                                <div class="gender-selector">
                                    <label class="selector-label">
                                        Получать рассылки от бота?
                                    </label>
                                    <div class="selector-buttons">
                                        <button
                                            type="button"
                                            class="selector-btn"
                                            :class="{ active: form.meta.need_bot_mailing === true }"
                                            @click="form.meta.need_bot_mailing = true"
                                        >
                                            <i class="fa-solid fa-check"></i>
                                            Да
                                        </button>
                                        <button
                                            type="button"
                                            class="selector-btn"
                                            :class="{ active: form.meta.need_bot_mailing === false }"
                                            @click="form.meta.need_bot_mailing = false"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                            Нет
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Футер внутри form -->
                    <div class="modal-footer">
                        <button
                            type="submit"
                            class="btn-save"
                            :disabled="!isValid || saving"
                        >
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="fa-solid fa-check me-2"></i>
                            {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: "EditProfileModal",

    data() {
        return {
            modal: null,
            saving: false,
            form: {
                id: null,
                name: '',
                phone: '',
                email: '',
                birthday: '',
                city: '',
                country: '',
                address: '',
                sex: null,
                meta: {
                    need_bot_mailing: true,
                },
            },
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        isValid() {
            return (
                this.form.name.trim().length > 0 &&
                this.isValidPhone
            );
        },

        isValidPhone() {
            const digits = this.form.phone.replace(/\D/g, '');
            return digits.length === 11;
        },

        maxBirthdayDate() {
            const today = new Date();
            return today.toISOString().split('T')[0];
        },
    },

    mounted() {
        this.$nextTick(() => {
            if (typeof bootstrap !== 'undefined') {
                this.modal = new bootstrap.Modal(document.getElementById('editProfileModal'));

                // Слушаем открытие модалки для заполнения формы
                document.getElementById('editProfileModal').addEventListener('show.bs.modal', () => {
                    this.prefillForm();
                });
            }
        });
    },

    beforeUnmount() {
        if (this.modal) {
            this.modal.dispose();
        }
    },

    methods: {
        // Публичный метод для открытия модалки
        show() {
            if (this.modal) {
                this.modal.show();
            }
        },

        // Заполнение формы данными пользователя
        prefillForm() {
            if (!this.self) return;

            this.form.id = this.self.id;
            this.form.name = this.self.name || this.self.username || '';
            this.form.phone = this.self.phone || '';
            this.form.email = this.self.email || '';
            this.form.birthday = this.self.birthday || '';
            this.form.city = this.self.city || this.self.default_address?.city || '';
            this.form.country = this.self.country || this.self.default_address?.country || '';
            this.form.address = this.form.address = this.self.address || this.self.default_address?.address || '';
            this.form.sex = this.self.sex ?? null;
            this.form.meta.need_bot_mailing = this.self.meta?.need_bot_mailing ?? true;
        },

        // Форматирование телефона
        formatPhone() {
            let value = this.form.phone.replace(/\D/g, '');

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

            this.form.phone = formatted;
        },

        // Отправка формы
        async submitProfile() {
            if (!this.isValid || this.saving) return;

            this.saving = true;

            try {
                const payload = {
                    id: this.form.id,
                    name: this.form.name.trim(),
                    phone: this.form.phone.trim(),
                    email: this.form.email.trim(),
                    birthday: this.form.birthday || null,
                    city: this.form.city.trim(),
                    country: this.form.country.trim(),
                    address: this.form.address.trim(),
                    sex: this.form.sex,
                    meta: {
                        ...this.form.meta,
                    },
                };

                // TODO: Замени на свой реальный API endpoint
                // const response = await axios.put('/api/user/profile', payload);
                // const updatedUser = response.data.user;

                // Имитация запроса (удали после подключения API)
                await new Promise(resolve => setTimeout(resolve, 1000));
                const updatedUser = { ...this.self, ...payload };

                // Эмитим событие с обновлёнными данными
                this.$emit('saved', updatedUser);

                // Закрываем модалку
                if (this.modal) {
                    this.modal.hide();
                }

            } catch (error) {
                console.error('Ошибка сохранения профиля:', error);

                this.$notify?.({
                    title: "Ошибка",
                    text: error.response?.data?.message || 'Не удалось сохранить данные',
                    type: "error",
                });
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>

<style scoped>
/* Минимальные стили - Bootstrap делает всю работу */

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.modal-title {
    font-weight: 700;
    margin-bottom: 2px;
    color: var(--bs-body-color);
}

/* Контейнер для центрирования контента */
.form-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 16px;
}

/* Секции формы */
.form-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
}

.form-section-title i {
    font-size: 0.9rem;
}

/* Селекторы */
.gender-selector {
    margin-top: 16px;
}

.selector-label {
    display: block;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    margin-bottom: 10px;
    font-weight: 500;
}

.selector-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.selector-btn {
    padding: 12px 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 10px;
    color: var(--bs-body-color);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.selector-btn:hover:not(.active) {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.selector-btn.active {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

/* Футер */
.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
}

.btn-save {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.3);
}

.btn-save:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(var(--bs-primary-rgb), 0.4);
}

.btn-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Фокус инпутов */
.form-control:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.15);
}

.form-floating label i {
    color: var(--bs-primary);
}

/* Адаптив */
@media (max-width: 576px) {
    .modal-header,
    .modal-footer {
        padding-left: 16px;
        padding-right: 16px;
    }

    .selector-buttons {
        gap: 8px;
    }

    .selector-btn {
        padding: 10px 12px;
        font-size: 0.9rem;
    }
}
</style>
