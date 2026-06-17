<template>
    <div class="feedback-page" v-if="self">
        <div class="container py-3">

            <!-- Заголовок -->
            <div class="feedback-header text-center mb-4">
                <div class="feedback-icon mx-auto mb-3">
                    <i class="fa-solid fa-comment-dots"></i>
                </div>
                <h2 class="fw-bold mb-2">Обратная связь</h2>
                <p class="text-muted mb-0">
                    Мы ценим ваше мнение и обязательно ответим
                </p>
            </div>

            <!-- Информационный блок -->
            <div class="info-card mb-4">
                <div class="info-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="info-content">
                    <h6 class="mb-1 fw-bold">Ваш отзыв анонимен</h6>
                    <p class="mb-0 small">
                        Сообщение увидит только администрация.
                        Публикация в канале — без ваших персональных данных.
                    </p>
                </div>
            </div>

            <!-- Форма -->
            <form @submit.prevent="submitCallback" class="feedback-form">

                <!-- Имя -->
                <div class="form-floating mb-3">
                    <input
                        type="text"
                        v-model="form.name"
                        class="form-control"
                        id="feedback-name"
                        placeholder="Ваше имя"
                        required
                        :disabled="sending"
                    >
                    <label for="feedback-name">
                        <i class="fa-solid fa-user me-1"></i> Ваше имя
                        <span class="text-danger">*</span>
                    </label>
                </div>

                <!-- Телефон -->
                <div class="form-floating mb-3">
                    <input
                        type="tel"
                        v-model="form.phone"
                        class="form-control"
                        id="feedback-phone"
                        placeholder="+7 (999) 123-45-67"
                        required
                        :disabled="sending"
                        @input="formatPhone"
                    >
                    <label for="feedback-phone">
                        <i class="fa-solid fa-phone me-1"></i> Номер телефона
                        <span class="text-danger">*</span>
                    </label>
                </div>

                <!-- Сообщение -->
                <div class="form-floating mb-2">
                    <textarea
                        v-model="form.message"
                        class="form-control"
                        id="feedback-message"
                        placeholder="Текст сообщения"
                        style="height: 150px; resize: none;"
                        maxlength="1000"
                        required
                        :disabled="sending"
                    ></textarea>
                    <label for="feedback-message">
                        <i class="fa-solid fa-message me-1"></i> Текст сообщения
                        <span class="text-danger">*</span>
                    </label>
                </div>

                <!-- Счётчик символов -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        Опишите проблему или предложение
                    </small>
                    <small :class="{'text-danger': form.message.length > 900}">
                        {{ form.message.length }} / 1000
                    </small>
                </div>

                <!-- Загрузка фото -->
                <div class="photo-section mb-3">
                    <h6 class="section-title">
                        <i class="fa-solid fa-camera me-2"></i>
                        Прикрепить фотографию
                        <small class="text-muted fw-normal">(до 10 МБ)</small>
                    </h6>

                    <!-- Превью загруженных фото -->
                    <div v-if="form.images.length > 0" class="photo-preview-grid mb-3">
                        <div
                            v-for="(img, index) in form.images"
                            :key="index"
                            class="photo-preview-item"
                        >
                            <img :src="img.preview" alt="Превью">
                            <button
                                type="button"
                                class="remove-photo-btn"
                                @click="removePhoto(index)"
                                :disabled="sending"
                                aria-label="Удалить фото"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <div class="photo-size">
                                {{ formatFileSize(img.size) }}
                            </div>
                        </div>

                        <!-- Кнопка добавить ещё -->
                        <label
                            v-if="form.images.length < maxPhotos"
                            class="add-more-btn"
                        >
                            <i class="fa-solid fa-plus"></i>
                            <span>Ещё</span>
                            <input
                                type="file"
                                accept="image/*"
                                multiple
                                @change="onSelectPhotos"
                                style="display: none;"
                                :disabled="sending"
                            >
                        </label>
                    </div>

                    <!-- Кнопка загрузки (если нет фото) -->
                    <label v-else class="upload-area">
                        <input
                            type="file"
                            accept="image/*"
                            multiple
                            @change="onSelectPhotos"
                            style="display: none;"
                            :disabled="sending"
                        >
                        <div class="upload-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="upload-text">
                            <strong>Нажмите для выбора</strong> или перетащите файл
                        </div>
                        <div class="upload-hint">
                            PNG, JPG до 10 МБ · Максимум {{ maxPhotos }} фото
                        </div>
                    </label>
                </div>

                <!-- Сообщение об ошибке -->
                <div v-if="errorMessage" class="error-alert mb-3">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    {{ errorMessage }}
                </div>

                <!-- Сообщение об успехе -->
                <div v-if="successMessage" class="success-alert mb-3">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    {{ successMessage }}
                </div>

            </form>
        </div>

        <!-- Sticky кнопка отправки -->
        <div class="submit-bar">
            <button
                type="button"
                class="submit-btn"
                :disabled="!canSubmit"
                @click="submitCallback"
            >
                <span v-if="sending" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fa-solid fa-paper-plane me-2"></i>
                {{ submitButtonText }}
            </button>
        </div>
    </div>

    <!-- Если пользователь не авторизован -->
    <div v-else class="container py-5 text-center">
        <div class="empty-state">
            <i class="fa-solid fa-user-slash fa-3x text-muted mb-3"></i>
            <h4>Необходима авторизация</h4>
            <p class="text-muted">Войдите в аккаунт, чтобы оставить отзыв</p>
            <button @click="goToLogin" class="btn btn-primary px-4">
                <i class="fa-solid fa-right-to-bracket me-2"></i>
                Войти
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "FeedbackPage",

    data() {
        return {
            sending: false,
            errorMessage: '',
            successMessage: '',
            maxPhotos: 3,
            maxFileSize: 10 * 1024 * 1024, // 10 МБ
            objectUrls: [], // Для освобождения URL.createObjectURL
            form: {
                name: '',
                phone: '',
                message: '',
                images: [], // { file, preview }
            },
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        canSubmit() {
            return (
                !this.sending &&
                this.form.name.trim() !== '' &&
                this.form.phone.trim() !== '' &&
                this.form.message.trim() !== '' &&
                this.form.images.length > 0
            );
        },

        submitButtonText() {
            if (this.sending) return 'Отправка...';
            return 'Отправить сообщение';
        },
    },

    mounted() {
        this.prefillFromProfile();
    },

    beforeUnmount() {
        // Освобождаем все Object URL для предотвращения утечек памяти
        this.objectUrls.forEach(url => URL.revokeObjectURL(url));
        this.objectUrls = [];
    },

    methods: {
        // Заполнение формы данными из профиля
        prefillFromProfile() {
            if (!this.self) return;
            this.form.name = this.self.fio_from_telegram || this.self.name || '';
            this.form.phone = this.self.phone || '';
        },

        // Форматирование телефона (простая маска)
        formatPhone() {
            let value = this.form.phone.replace(/\D/g, '');

            // Если начинается с 8, заменяем на 7
            if (value.startsWith('8')) {
                value = '7' + value.slice(1);
            }
            // Если не начинается с 7, добавляем
            if (!value.startsWith('7') && value.length > 0) {
                value = '7' + value;
            }

            // Форматируем: +7 (XXX) XXX-XX-XX
            let formatted = '';
            if (value.length > 0) formatted = '+7';
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 5) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 8) formatted += '-' + value.slice(7, 9);
            if (value.length >= 10) formatted += '-' + value.slice(9, 11);

            this.form.phone = formatted;
        },

        // Выбор фотографий
        onSelectPhotos(event) {
            const files = Array.from(event.target.files);
            this.errorMessage = '';

            // Проверка лимита
            if (this.form.images.length + files.length > this.maxPhotos) {
                this.errorMessage = `Можно загрузить максимум ${this.maxPhotos} фото`;
                return;
            }

            for (const file of files) {
                // Проверка типа
                if (!file.type.startsWith('image/')) {
                    this.errorMessage = `Файл "${file.name}" не является изображением`;
                    continue;
                }

                // Проверка размера
                if (file.size > this.maxFileSize) {
                    this.errorMessage = `Файл "${file.name}" превышает 10 МБ`;
                    continue;
                }

                // Создаём превью
                const preview = URL.createObjectURL(file);
                this.objectUrls.push(preview);
                this.form.images.push({ file, preview });
            }

            // Сбрасываем input, чтобы можно было выбрать тот же файл
            event.target.value = '';
        },

        // Удаление фото
        removePhoto(index) {
            const removed = this.form.images.splice(index, 1)[0];
            if (removed?.preview) {
                URL.revokeObjectURL(removed.preview);
                this.objectUrls = this.objectUrls.filter(url => url !== removed.preview);
            }
        },

        // Форматирование размера файла
        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' Б';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
            return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
        },

        // Валидация формы
        validateForm() {
            if (!this.form.name.trim()) {
                this.errorMessage = 'Укажите ваше имя';
                return false;
            }
            if (!this.form.phone.trim() || this.form.phone.replace(/\D/g, '').length < 11) {
                this.errorMessage = 'Укажите корректный номер телефона';
                return false;
            }
            if (!this.form.message.trim()) {
                this.errorMessage = 'Введите текст сообщения';
                return false;
            }
            if (this.form.images.length === 0) {
                this.errorMessage = 'Прикрепите хотя бы одну фотографию';
                return false;
            }
            return true;
        },

        // Отправка формы
        async submitCallback() {
            this.errorMessage = '';
            this.successMessage = '';

            if (!this.validateForm()) return;

            this.sending = true;

            try {
                const data = new FormData();
                data.append('name', this.form.name.trim());
                data.append('phone', this.form.phone.trim());
                data.append('message', this.form.message.trim());

                // Добавляем все фото
                this.form.images.forEach(img => {
                    data.append('photos[]', img.file);
                });

                // TODO: Замени на свой API или Pinia action
                // await axios.post('/api/feedback', data, {
                //     headers: { 'Content-Type': 'multipart/form-data' }
                // });

                // Имитация запроса (удали после подключения API)
                await new Promise(resolve => setTimeout(resolve, 1500));

                // Успех
                this.successMessage = 'Спасибо! Ваше сообщение успешно отправлено.';

                // Сброс формы (кроме имени и телефона)
                this.form.message = '';
                this.form.images.forEach(img => {
                    URL.revokeObjectURL(img.preview);
                });
                this.objectUrls.forEach(url => URL.revokeObjectURL(url));
                this.objectUrls = [];
                this.form.images = [];

                // Убираем сообщение об успехе через 5 секунд
                setTimeout(() => {
                    this.successMessage = '';
                }, 5000);

            } catch (error) {
                console.error('Ошибка отправки:', error);
                this.errorMessage =
                    error.response?.data?.message ||
                    'Произошла ошибка при отправке. Попробуйте ещё раз.';
            } finally {
                this.sending = false;
            }
        },

        goToLogin() {
            this.$router.push({ name: 'Auth' });
        },
    },
};
</script>

<style scoped>
.feedback-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 100px; /* Отступ под sticky кнопку */
}

/* Заголовок */
.feedback-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3);
}

.feedback-icon i {
    font-size: 2rem;
    color: white;
}

/* Информационный блок */
.info-card {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.03) 100%);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
    border-radius: 12px;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-content p {
    color: var(--bs-secondary-color);
    line-height: 1.5;
}

/* Заголовок секции */
.section-title {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: var(--bs-body-color);
    margin-bottom: 12px;
}

/* Область загрузки фото */
.upload-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 20px;
    background: var(--bs-body-bg);
    border: 2px dashed var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.upload-area:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.upload-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 12px;
}

.upload-text {
    color: var(--bs-body-color);
    margin-bottom: 6px;
}

.upload-hint {
    color: var(--bs-secondary-color);
    font-size: 0.8rem;
}

/* Сетка превью фото */
.photo-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
}

.photo-preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 10px;
    overflow: hidden;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
}

.photo-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-photo-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.75rem;
    padding: 0;
}

.remove-photo-btn:hover {
    background: #dc3545;
    transform: scale(1.1);
}

.photo-size {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    color: white;
    font-size: 0.7rem;
    padding: 8px 6px 4px;
    text-align: center;
}

/* Кнопка "добавить ещё" */
.add-more-btn {
    aspect-ratio: 1;
    border: 2px dashed var(--bs-border-color);
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--bs-secondary-color);
    font-size: 0.85rem;
}

.add-more-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.add-more-btn i {
    font-size: 1.5rem;
}

/* Сообщения об ошибках и успехе */
.error-alert,
.success-alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    animation: slideIn 0.3s ease;
}

.error-alert {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #dc3545;
}

.success-alert {
    background: rgba(25, 135, 84, 0.1);
    border: 1px solid rgba(25, 135, 84, 0.3);
    color: #198754;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Sticky кнопка отправки */
.submit-bar {
    position: sticky;
    bottom: 72px;

    padding: 12px 16px;

    z-index: 1000;

}

.submit-btn {
width: 100%;
padding: 14px 20px;
background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
border: none;
border-radius: 12px;
color: white;
font-size: 1rem;
font-weight: 600;
cursor: pointer;
transition: all 0.3s ease;
display: flex;
align-items: center;
justify-content: center;
box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.3);
}

.submit-btn:hover:not(:disabled) {
transform: translateY(-2px);
box-shadow: 0 8px 25px rgba(var(--bs-primary-rgb), 0.4);
}

.submit-btn:disabled {
opacity: 0.5;
cursor: not-allowed;
}

/* Пустое состояние (не авторизован) */
.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-state i {
    opacity: 0.3;
}

/* Адаптив для тёмной темы */
:root[data-bs-theme="dark"] .submit-bar {
    background: rgba(26, 26, 26, 0.95);
}
</style>
