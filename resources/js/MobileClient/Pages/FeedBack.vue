<template>
    <div class="feedback-page" v-if="self">
        <!-- Hero-секция -->
        <div class="feedback-hero">
            <div class="hero-bg"></div>
            <div class="hero-particles">
                <span v-for="i in 12" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">💬</div>
                    <div class="hero-sparkle sparkle-2">✨</div>
                </div>
                <h1 class="hero-title">Обратная связь</h1>
                <p class="hero-subtitle">Поделитесь мнением — мы читаем каждое сообщение</p>
            </div>
        </div>

        <div class="container py-4">
            <!-- Информационный блок -->
            <div class="info-card mb-4">
                <div class="info-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="info-content">
                    <h6 class="info-title">Ваш отзыв важен для нас</h6>
                    <p class="info-text">
                        Сообщение увидит только администрация.
                        Среднее время ответа — <strong>2-4 часа</strong>.
                    </p>
                </div>
            </div>

            <!-- Форма -->
            <form @submit.prevent="submitCallback" class="feedback-form">

                <!-- Имя -->
                <div class="form-group mb-3">
                    <div class="form-label-custom">
                        <i class="fa-solid fa-user"></i>
                        <span>Ваше имя</span>
                        <span class="required">*</span>
                    </div>
                    <div class="input-wrapper">
                        <input
                            type="text"
                            v-model="form.name"
                            class="modern-input"
                            placeholder="Как к вам обращаться?"
                            required
                            :disabled="sending"
                        >
                    </div>
                </div>

                <!-- Телефон -->
                <div class="form-group mb-3">
                    <div class="form-label-custom">
                        <i class="fa-solid fa-phone"></i>
                        <span>Номер телефона</span>
                        <span class="required">*</span>
                    </div>
                    <div class="input-wrapper">
                        <input
                            type="tel"
                            v-model="form.phone"
                            class="modern-input"
                            placeholder="+7 (___) ___-__-__"
                            required
                            :disabled="sending"
                            @input="formatPhone"
                        >
                    </div>
                </div>

                <!-- Сообщение -->
                <div class="form-group mb-3">
                    <div class="form-label-custom">
                        <i class="fa-solid fa-message"></i>
                        <span>Текст сообщения</span>
                        <span class="required">*</span>
                    </div>
                    <div class="textarea-wrapper">
                        <textarea
                            v-model="form.message"
                            class="modern-textarea"
                            placeholder="Опишите вашу проблему, предложение или вопрос..."
                            maxlength="1000"
                            required
                            :disabled="sending"
                            @input="autoResize"
                            ref="messageTextarea"
                        ></textarea>
                        <div class="textarea-counter" :class="{ 'warning': form.message.length > 900, 'danger': form.message.length > 980 }">
                            <div class="counter-bar">
                                <div class="counter-fill" :style="{ width: (form.message.length / 1000 * 100) + '%' }"></div>
                            </div>
                            <span class="counter-text">{{ form.message.length }} / 1000</span>
                        </div>
                    </div>
                </div>

                <!-- Загрузка фото -->
                <div class="form-group mb-3">
                    <div class="form-label-custom">
                        <i class="fa-solid fa-camera"></i>
                        <span>Фотографии</span>
                        <span class="optional">до {{ maxPhotos }} шт.</span>
                    </div>

                    <!-- Превью загруженных фото -->
                    <div v-if="form.images.length > 0" class="photo-preview-grid">
                        <transition-group name="photo-list">
                            <div
                                v-for="(img, index) in form.images"
                                :key="img.id"
                                class="photo-preview-item"
                            >
                                <img :src="img.preview" alt="Превью">
                                <div class="photo-overlay">
                                    <button
                                        type="button"
                                        class="remove-photo-btn"
                                        @click="removePhoto(index)"
                                        :disabled="sending"
                                        aria-label="Удалить фото"
                                    >
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    <div class="photo-meta">
                                        <div class="photo-size">{{ formatFileSize(img.size) }}</div>
                                    </div>
                                </div>
                                <div class="photo-number">{{ index + 1 }}</div>
                            </div>
                        </transition-group>

                        <!-- Кнопка "добавить ещё" -->
                        <label
                            v-if="form.images.length < maxPhotos"
                            class="add-more-btn"
                            @dragover.prevent
                            @drop.prevent="onDropPhotos"
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

                    <!-- Область загрузки (drag & drop) -->
                    <label
                        v-else
                        class="upload-area"
                        :class="{ 'is-dragging': isDragging }"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDropPhotos($event); isDragging = false"
                    >
                        <input
                            type="file"
                            accept="image/*"
                            multiple
                            @change="onSelectPhotos"
                            style="display: none;"
                            :disabled="sending"
                        >
                        <div class="upload-icon-wrapper">
                            <div class="upload-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div class="upload-ripple"></div>
                        </div>
                        <div class="upload-text">
                            <strong>Нажмите</strong> или перетащите фото
                        </div>
                        <div class="upload-hint">
                            PNG, JPG до 10 МБ · Максимум {{ maxPhotos }} фото
                        </div>
                    </label>
                </div>

                <!-- Сообщение об ошибке -->
                <transition name="alert-fade">
                    <div v-if="errorMessage" class="alert-message error">
                        <div class="alert-icon">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </div>
                        <div class="alert-text">{{ errorMessage }}</div>
                        <button type="button" class="alert-close" @click="errorMessage = ''">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </transition>

                <!-- Сообщение об успехе -->
                <transition name="alert-fade">
                    <div v-if="successMessage" class="alert-message success">
                        <div class="alert-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div class="alert-text">{{ successMessage }}</div>
                        <button type="button" class="alert-close" @click="successMessage = ''">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </transition>

                <!-- Кнопка отправки внутри формы (резервная) -->
                <button
                    type="button"
                    class="submit-btn-inline"
                    :class="{ 'is-loading': sending }"
                    :disabled="!canSubmit"
                    @click="submitCallback"
                >
                    <transition name="btn-fade" mode="out-in">
                    <span v-if="sending" key="loading" class="btn-content">
                        <span class="spinner"></span>
                        <span>Отправка сообщения...</span>
                    </span>
                                    <span v-else key="default" class="btn-content">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Отправить сообщение</span>
                    </span>
                    </transition>
                </button>
            </form>
        </div>

    </div>

    <!-- Если пользователь не авторизован -->
    <div v-else class="auth-required">
        <div class="auth-card">
            <div class="auth-icon">
                <i class="fa-solid fa-user-lock"></i>
            </div>
            <h4>Необходима авторизация</h4>
            <p>Войдите в аккаунт, чтобы оставить отзыв</p>
            <button @click="goToLogin" class="auth-btn">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Войти</span>
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
            maxFileSize: 10 * 1024 * 1024,
            isDragging: false,
            photoIdCounter: 0,
            form: {
                name: '',
                phone: '',
                message: '',
                images: [], // { id, file, preview, size }
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
                this.form.name.trim().length >= 2 &&
                this.form.phone.replace(/\D/g, '').length >= 11 &&
                this.form.message.trim().length >= 5
                // ❌ УБРАЛИ: && this.form.images.length > 0
            );
        },
    },

    mounted() {
        this.prefillFromProfile();
    },

    beforeUnmount() {
        this.form.images.forEach(img => {
            if (img.preview) URL.revokeObjectURL(img.preview);
        });
    },

    methods: {
        prefillFromProfile() {
            if (!this.self) return;
            this.form.name = this.self.fio_from_telegram || this.self.name || '';

            // Форматируем телефон, если есть
            const phone = this.self.phone || '';
            if (phone) {
                this.form.phone = phone;
                this.formatPhone();
            }
        },

        formatPhone() {
            let value = this.form.phone.replace(/\D/g, '');

            if (value.startsWith('8')) value = '7' + value.slice(1);
            if (!value.startsWith('7') && value.length > 0) value = '7' + value;

            let formatted = '';
            if (value.length > 0) formatted = '+7';
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 5) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 8) formatted += '-' + value.slice(7, 9);
            if (value.length >= 10) formatted += '-' + value.slice(9, 11);

            this.form.phone = formatted;
        },

        autoResize() {
            const textarea = this.$refs.messageTextarea;
            if (!textarea) return;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 300) + 'px';
        },

        onSelectPhotos(event) {
            this.processFiles(Array.from(event.target.files));
            event.target.value = '';
        },

        onDropPhotos(event) {
            const files = Array.from(event.dataTransfer?.files || event.target?.files || []);
            this.processFiles(files);
        },

        processFiles(files) {
            this.errorMessage = '';

            const available = this.maxPhotos - this.form.images.length;
            if (files.length > available) {
                this.errorMessage = `Можно загрузить ещё только ${available} фото`;
                files = files.slice(0, available);
            }

            for (const file of files) {
                if (!file.type.startsWith('image/')) {
                    this.errorMessage = `Файл "${file.name}" не является изображением`;
                    continue;
                }

                if (file.size > this.maxFileSize) {
                    this.errorMessage = `Файл "${file.name}" превышает 10 МБ`;
                    continue;
                }

                const preview = URL.createObjectURL(file);
                this.form.images.push({
                    id: ++this.photoIdCounter,
                    file,
                    preview,
                    size: file.size,
                });
            }
        },

        removePhoto(index) {
            const removed = this.form.images.splice(index, 1)[0];
            if (removed?.preview) {
                URL.revokeObjectURL(removed.preview);
            }
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' Б';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
            return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
        },

        validateForm() {
            if (!this.form.name.trim() || this.form.name.trim().length < 2) {
                this.errorMessage = 'Укажите ваше имя (минимум 2 символа)';
                return false;
            }
            if (this.form.phone.replace(/\D/g, '').length < 11) {
                this.errorMessage = 'Укажите корректный номер телефона';
                return false;
            }
            if (!this.form.message.trim() || this.form.message.trim().length < 5) {
                this.errorMessage = 'Введите текст сообщения (минимум 5 символов)';
                return false;
            }
            // ❌ УБРАЛИ проверку form.images.length === 0
            return true;
        },

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

                this.form.images.forEach(img => {
                    data.append('photos[]', img.file);
                });

                const response = await axios.post('/feedback', data, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                if (response.data?.success) {
                    this.successMessage = response.data.message || 'Сообщение отправлено!';

                    // Очистка формы
                    this.form.images.forEach(img => URL.revokeObjectURL(img.preview));
                    this.form.message = '';
                    this.form.images = [];

                    if (this.$refs.messageTextarea) {
                        this.$refs.messageTextarea.style.height = 'auto';
                    }

                    setTimeout(() => { this.successMessage = ''; }, 6000);
                } else {
                    throw new Error(response.data?.message || 'Ошибка отправки');
                }

            } catch (error) {
                console.error('Ошибка отправки:', error);
                this.errorMessage =
                    error.response?.data?.message ||
                    error.message ||
                    'Произошла ошибка при отправке. Попробуйте ещё раз.';
            } finally {
                this.sending = false;
            }
        },

        goToLogin() {
            this.$router.push({ name: 'Auth' });
        },

        particleStyle(i) {
            const size = Math.random() * 6 + 3;
            return {
                width: `${size}px`,
                height: `${size}px`,
                left: `${Math.random() * 100}%`,
                animationDelay: `${Math.random() * 5}s`,
                animationDuration: `${Math.random() * 8 + 8}s`,
            };
        },
    },
};
</script>

<style scoped>
.feedback-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 140px; /* Отступ под sticky + inline кнопку */
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.feedback-hero {
    position: relative;
    padding: 48px 24px 40px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, #6366f1 50%, #8b5cf6 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.2) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.15) 0%, transparent 50%);
}

.hero-particles {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.particle {
    position: absolute;
    bottom: -10px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 50%;
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% { transform: translateY(0) rotate(0deg); opacity: 0; }
    10% { opacity: 0.8; }
    90% { opacity: 0.8; }
    100% { transform: translateY(-400px) rotate(360deg); opacity: 0; }
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.hero-icon {
    width: 88px;
    height: 88px;
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    animation: heroIconFloat 4s ease-in-out infinite;
}

@keyframes heroIconFloat {
    0%, 100% { transform: translateY(0) rotate(-3deg); }
    50% { transform: translateY(-8px) rotate(3deg); }
}

.hero-sparkle {
    position: absolute;
    font-size: 1.3rem;
    animation: sparkle 2.5s ease-in-out infinite;
}

.sparkle-1 { top: -10px; right: -10px; animation-delay: 0s; }
.sparkle-2 { bottom: -10px; left: -10px; animation-delay: 1s; }

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0.5); }
    50% { opacity: 1; transform: scale(1.2); }
}

.hero-title {
    font-size: 2rem;
    font-weight: 800;
    margin: 0 0 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

/* ==========================================
   ИНФО-КАРТОЧКА
   ========================================== */
.info-card {
    display: flex;
    gap: 14px;
    padding: 18px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
}

.info-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, var(--bs-primary) 0%, #8b5cf6 100%);
}

.info-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, #8b5cf6 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.info-content { flex: 1; }

.info-title {
    margin: 0 0 4px;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.info-text {
    margin: 0;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    line-height: 1.5;
}

.info-text strong {
    color: var(--bs-primary);
    font-weight: 700;
}

/* ==========================================
   ФОРМА
   ========================================== */
.feedback-form {
    padding: 4px 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-label-custom {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.form-label-custom i {
    color: var(--bs-primary);
    font-size: 0.85rem;
}

.form-label-custom .required {
    color: #dc3545;
    font-weight: 700;
}

.form-label-custom .optional {
    margin-left: auto;
    color: var(--bs-secondary-color);
    font-weight: 400;
    font-size: 0.78rem;
}

.input-wrapper,
.textarea-wrapper {
    position: relative;
}

.modern-input,
.modern-textarea {
    width: 100%;
    padding: 14px 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-body-color);
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.25s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.modern-textarea {
    min-height: 120px;
    max-height: 300px;
    resize: none;
    line-height: 1.5;
}

.modern-input:focus,
.modern-textarea:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.modern-input::placeholder,
.modern-textarea::placeholder {
    color: var(--bs-secondary-color);
    opacity: 0.6;
}

/* Счётчик символов */
.textarea-counter {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
    padding: 0 4px;
}

.counter-bar {
    flex: 1;
    height: 4px;
    background: var(--bs-border-color);
    border-radius: 2px;
    overflow: hidden;
}

.counter-fill {
    height: 100%;
    background: linear-gradient(90deg, #198754 0%, #20c997 100%);
    border-radius: 2px;
    transition: width 0.3s ease, background 0.3s ease;
}

.textarea-counter.warning .counter-fill {
    background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);
}

.textarea-counter.danger .counter-fill {
    background: linear-gradient(90deg, #dc3545 0%, #ff6b6b 100%);
}

.counter-text {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    font-variant-numeric: tabular-nums;
    min-width: 55px;
    text-align: right;
}

.textarea-counter.warning .counter-text { color: #ff9800; }
.textarea-counter.danger .counter-text { color: #dc3545; }

/* ==========================================
   ЗАГРУЗКА ФОТО
   ========================================== */
.upload-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 20px;
    background: var(--bs-body-bg);
    border: 2px dashed var(--bs-border-color);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.upload-area:hover,
.upload-area.is-dragging {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
    transform: translateY(-2px);
}

.upload-area.is-dragging {
    border-style: solid;
    border-width: 3px;
}

.upload-icon-wrapper {
    position: relative;
    margin-bottom: 12px;
}

.upload-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}

.upload-area:hover .upload-icon {
    transform: scale(1.1);
    color: #8b5cf6;
}

.upload-ripple {
    position: absolute;
    inset: -10px;
    border-radius: 50%;
    border: 2px solid var(--bs-primary);
    opacity: 0;
    animation: ripple 2s ease-out infinite;
}

@keyframes ripple {
    0% { transform: scale(0.8); opacity: 0.6; }
    100% { transform: scale(1.4); opacity: 0; }
}

.upload-text {
    color: var(--bs-body-color);
    margin-bottom: 6px;
    font-size: 0.95rem;
}

.upload-text strong {
    color: var(--bs-primary);
    font-weight: 700;
}

.upload-hint {
    color: var(--bs-secondary-color);
    font-size: 0.8rem;
}

/* Сетка превью фото */
.photo-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px;
}

.photo-preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.photo-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.photo-preview-item:hover img {
    transform: scale(1.05);
}

.photo-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.5) 0%, transparent 40%, transparent 60%, rgba(0, 0, 0, 0.7) 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 8px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.photo-preview-item:hover .photo-overlay {
    opacity: 1;
}

.remove-photo-btn {
    align-self: flex-end;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.75rem;
    padding: 0;
    backdrop-filter: blur(8px);
}

.remove-photo-btn:hover:not(:disabled) {
    background: #dc3545;
    transform: scale(1.15) rotate(90deg);
}

.photo-meta {
    align-self: stretch;
}

.photo-size {
    background: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 6px;
    text-align: center;
    backdrop-filter: blur(8px);
}

.photo-number {
    position: absolute;
    top: 8px;
    left: 8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

/* Кнопка "добавить ещё" */
.add-more-btn {
    aspect-ratio: 1;
    border: 2px dashed var(--bs-border-color);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    cursor: pointer;
    transition: all 0.25s ease;
    color: var(--bs-secondary-color);
    font-size: 0.85rem;
    background: var(--bs-body-bg);
}

.add-more-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
    transform: translateY(-2px);
}

.add-more-btn i {
    font-size: 1.5rem;
}

/* Анимации списка фото */
.photo-list-enter-active,
.photo-list-leave-active {
    transition: all 0.3s ease;
}
.photo-list-enter-from {
    opacity: 0;
    transform: scale(0.8);
}
.photo-list-leave-to {
    opacity: 0;
    transform: scale(0.8) rotate(-10deg);
}

/* ==========================================
   СООБЩЕНИЯ
   ========================================== */
.alert-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    margin-top: 16px;
    position: relative;
}

.alert-message.error {
    background: rgba(220, 53, 69, 0.08);
    border: 1px solid rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.alert-message.success {
    background: rgba(25, 135, 84, 0.08);
    border: 1px solid rgba(25, 135, 84, 0.2);
    color: #198754;
}

.alert-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: currentColor;
    color: white !important;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.9rem;
}

.alert-message.error .alert-icon { background: #dc3545; }
.alert-message.success .alert-icon { background: #198754; }

.alert-text {
    flex: 1;
    font-size: 0.9rem;
    line-height: 1.5;
    padding-top: 4px;
}

.alert-close {
    background: transparent;
    border: none;
    color: currentColor;
    opacity: 0.6;
    cursor: pointer;
    padding: 4px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}

.alert-close:hover {
    opacity: 1;
    transform: rotate(90deg);
}

.alert-fade-enter-active,
.alert-fade-leave-active {
    transition: all 0.3s ease;
}
.alert-fade-enter-from,
.alert-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* ==========================================
   STICKY КНОПКА ОТПРАВКИ
   ========================================== */
.submit-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px 16px;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
    background: linear-gradient(180deg, transparent 0%, var(--bs-body-bg) 40%);
    z-index: 100;
    pointer-events: none;
}

.submit-bar-inner {
    max-width: 600px;
    margin: 0 auto;
    pointer-events: auto;
}

.submit-btn {
    width: 100%;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, #6366f1 50%, #8b5cf6 100%);
    border: none;
    border-radius: 16px;
    color: white;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 8px 24px rgba(99, 102, 241, 0.35),
        0 2px 8px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s ease;
}

.submit-btn:hover:not(:disabled)::before {
    left: 100%;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow:
        0 12px 32px rgba(99, 102, 241, 0.45),
        0 4px 12px rgba(0, 0, 0, 0.15);
}

.submit-btn:active:not(:disabled) {
    transform: translateY(0);
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--bs-secondary-bg);
    color: var(--bs-secondary-color);
    box-shadow: none;
}

.btn-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-content i {
    font-size: 1rem;
}

.spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.btn-fade-enter-active,
.btn-fade-leave-active {
    transition: all 0.2s ease;
}
.btn-fade-enter-from,
.btn-fade-leave-to {
    opacity: 0;
    transform: translateY(4px);
}

/* ==========================================
   СОСТОЯНИЕ АВТОРИЗАЦИИ
   ========================================== */
.auth-required {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--bs-primary) 0%, #8b5cf6 100%);
    padding: 20px;
}

.auth-card {
    background: white;
    padding: 48px 32px;
    border-radius: 24px;
    text-align: center;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.auth-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, #8b5cf6 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
}

.auth-card h4 {
    color: var(--bs-body-color);
    margin-bottom: 8px;
    font-weight: 700;
}

.auth-card p {
    color: var(--bs-secondary-color);
    margin-bottom: 24px;
}

.auth-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 32px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, #8b5cf6 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
}

.auth-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title { font-size: 1.6rem; }
    .hero-icon { width: 72px; height: 72px; font-size: 2rem; }

    .photo-preview-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* ==========================================
   КНОПКА ОТПРАВКИ ВНУТРИ ФОРМЫ
   ========================================== */
.submit-btn-inline {
    width: 100%;
    padding: 18px 24px;
    margin-top: 24px;
    margin-bottom: 16px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, #6366f1 50%, #8b5cf6 100%);
    border: none;
    border-radius: 16px;
    color: white;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow:
        0 8px 24px rgba(99, 102, 241, 0.35),
        0 2px 8px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
    letter-spacing: 0.3px;
}

.submit-btn-inline::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.25),
        transparent
    );
    transition: left 0.7s ease;
}

.submit-btn-inline:hover:not(:disabled)::before {
    left: 100%;
}

.submit-btn-inline:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow:
        0 12px 32px rgba(99, 102, 241, 0.45),
        0 4px 12px rgba(0, 0, 0, 0.15);
}

.submit-btn-inline:active:not(:disabled) {
    transform: translateY(0);
}

.submit-btn-inline:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    background: var(--bs-secondary-bg);
    color: var(--bs-secondary-color);
    box-shadow: none;
}

.submit-btn-inline.is-loading {
    background: linear-gradient(135deg, var(--bs-primary) 0%, #6366f1 100%);
    opacity: 0.9;
    cursor: wait;
}

.submit-btn-inline .btn-content {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 1;
}

.submit-btn-inline .btn-content i {
    font-size: 1.1rem;
}

/* ==========================================
   УЛУЧШЕННАЯ STICKY КНОПКА
   ========================================== */
.submit-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px 16px;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
    background: linear-gradient(
        180deg,
        rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0) 0%,
        rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.95) 50%,
        rgba(var(--bs-body-bg-rgb, 255, 255, 255), 1) 100%
    );
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 100;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
}

:root[data-bs-theme="dark"] .submit-bar {
    background: linear-gradient(
        180deg,
        rgba(26, 26, 26, 0) 0%,
        rgba(26, 26, 26, 0.95) 50%,
        rgba(26, 26, 26, 1) 100%
    );
}

.submit-bar-inner {
    max-width: 600px;
    margin: 0 auto;
}

.submit-bar .submit-btn {
    box-shadow:
        0 8px 24px rgba(99, 102, 241, 0.4),
        0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
