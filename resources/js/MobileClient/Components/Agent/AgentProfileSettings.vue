<template>
    <transition name="modal-fade">
        <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
            <div class="modal-container profile-modal">
                <div class="modal-header">
                    <h3>Настройки профиля и реквизиты</h3>
                    <button class="modal-close" @click="$emit('close')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- 1. Основная информация -->
                    <div class="form-section">
                        <h4 class="section-title">Контактные данные</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>ФИО / Название организации</label>
                                <input type="text" v-model="formData.name" class="form-input"
                                       placeholder="Иванов Иван Иванович">
                            </div>
                            <div class="form-group">
                                <label>Телефон</label>
                                <input type="tel" v-model="formData.phone" class="form-input"
                                       placeholder="+7 (999) 000-00-00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email для счетов и уведомлений</label>
                            <input type="email" v-model="formData.email" class="form-input"
                                   placeholder="agent@example.com">
                        </div>
                    </div>

                    <!-- 2. Юридический статус -->
                    <div class="form-section">
                        <h4 class="section-title">Юридический статус</h4>
                        <div class="legal-types">
                            <div
                                class="legal-card"
                                :class="{ 'is-active': formData.legal_type === 'self_employed' }"
                                @click="formData.legal_type = 'self_employed'"
                            >
                                <i class="fa-solid fa-user"></i>
                                <span>Самозанятый</span>
                            </div>
                            <div
                                class="legal-card"
                                :class="{ 'is-active': formData.legal_type === 'ip' }"
                                @click="formData.legal_type = 'ip'"
                            >
                                <i class="fa-solid fa-briefcase"></i>
                                <span>ИП</span>
                            </div>
                            <div
                                class="legal-card"
                                :class="{ 'is-active': formData.legal_type === 'legal_entity' }"
                                @click="formData.legal_type = 'legal_entity'"
                            >
                                <i class="fa-solid fa-building"></i>
                                <span>Юр. лицо (ООО)</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>ИНН</label>
                                <input type="text" v-model="formData.inn" class="form-input"
                                       placeholder="12 или 10 цифр">
                            </div>
                            <div class="form-group" v-if="formData.legal_type !== 'self_employed'">
                                <label>{{ formData.legal_type === 'ip' ? 'ОГРНИП' : 'ОГРН' }}</label>
                                <input type="text" v-model="formData.ogrn" class="form-input"
                                       placeholder="15 или 13 цифр">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Банковские реквизиты -->
                    <div class="form-section">
                        <h4 class="section-title">Банковские реквизиты</h4>
                        <div class="form-group">
                            <label>Расчетный счет</label>
                            <input type="text" v-model="formData.bank_account" class="form-input" placeholder="20 цифр">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>БИК банка</label>
                                <input type="text" v-model="formData.bik" class="form-input" placeholder="9 цифр">
                            </div>
                            <div class="form-group">
                                <label>Название банка</label>
                                <input type="text" v-model="formData.bank_name" class="form-input"
                                       placeholder="ПАО Сбербанк">
                            </div>
                        </div>
                    </div>

                    <!-- 4. Документы -->
                    <div class="form-section">
                        <h4 class="section-title">Подтверждающие документы</h4>
                        <div class="documents-list">
                            <div v-for="doc in documents" :key="doc.id" class="document-upload-item">
                                <div class="doc-info">
                                    <div class="doc-title">
                                        {{ doc.title }}
                                        <span v-if="doc.required" class="required-badge">Обязательно</span>
                                    </div>
                                    <div class="doc-desc">{{ doc.description }}</div>
                                    <div v-if="doc.fileName" class="doc-file-name">
                                        <i class="fa-solid fa-paperclip"></i> {{ doc.fileName }}
                                    </div>
                                </div>
                                <label class="upload-btn">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span>{{ doc.fileName ? 'Заменить' : 'Загрузить' }}</span>
                                    <input type="file" class="hidden-input" accept=".pdf,.jpg,.png"
                                           @change="handleFileUpload(doc.id, $event)">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn-secondary-modern" @click="$emit('close')">Отмена</button>
                    <button class="btn-primary-modern" @click="saveProfile">
                        <i class="fa-solid fa-check"></i> Сохранить изменения
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "AgentProfileSettings",
    props: {
        isOpen: {type: Boolean, default: false},
        agent: {type: Object, required: true}
    },
    emits: ['close', 'save'],
    data() {
        return {
            formData: {
                name: '',
                phone: '',
                email: '',
                legal_type: 'self_employed', // self_employed, ip, legal_entity
                inn: '',
                ogrn: '',
                bank_account: '',
                bik: '',
                bank_name: ''
            },
            documents: [
                {
                    id: 1,
                    title: 'Справка о постановке на учет (КНД 1122035)',
                    description: 'Для самозанятых',
                    required: true,
                    fileName: null
                },
                {
                    id: 2,
                    title: 'Свидетельство ОГРНИП / ОГРН',
                    description: 'Скан или фото',
                    required: true,
                    fileName: null
                },
                {
                    id: 3,
                    title: 'Карточка предприятия',
                    description: 'С реквизитами и подписью',
                    required: false,
                    fileName: null
                }
            ]
        };
    },
    watch: {
        agent: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.formData = {...this.formData, ...newVal.profile};
                    // Имитация загруженных документов из пропсов
                    if (newVal.profile?.documents) {
                        this.documents = this.documents.map(d => {
                            const saved = newVal.profile.documents.find(s => s.id === d.id);
                            return saved ? {...d, fileName: saved.fileName} : d;
                        });
                    }
                }
            }
        }
    },
    methods: {
        handleFileUpload(docId, event) {
            const file = event.target.files[0];
            if (file) {
                const doc = this.documents.find(d => d.id === docId);
                if (doc) {
                    doc.fileName = file.name;
                    // Здесь можно добавить логику загрузки файла на сервер
                }
            }
        },
        saveProfile() {
            // Базовая валидация
            if (!this.formData.name || !this.formData.inn) {
                this.$notify?.({title: 'Ошибка', text: 'Заполните ФИО и ИНН', type: 'error'});
                return;
            }

            // Собираем итоговые данные
            const payload = {
                ...this.formData,
                documents: this.documents.map(d => ({id: d.id, fileName: d.fileName}))
            };

            this.$emit('save', payload);
        }
    }
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

$primary: #3b82f6;
$success: #10b981;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.profile-modal {
    max-width: 750px;
    max-height: 90vh;
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-section {
    // Стили секции
}

.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 16px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid $border;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;

    label {
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
    }
}

.form-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }
}

// Выбор типа лица
.legal-types {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.legal-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px;
    border: 2px solid $border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    color: $text-muted;

    i {
        font-size: 1.5rem;
    }

    span {
        font-size: 0.85rem;
        font-weight: 600;
    }

    &:hover {
        border-color: $primary;
        color: $primary;
    }

    &.is-active {
        border-color: $primary;
        background: rgba($primary, 0.05);
        color: $primary;
    }
}

.required-badge {
    display: inline-block;
    padding: 2px 6px;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 4px;
    margin-left: 8px;
    text-transform: uppercase;
}

// Загрузка документов
.documents-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.document-upload-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 10px;
    gap: 16px;
}

.doc-info {
    flex: 1;
}

.doc-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: $text;
    margin-bottom: 2px;
}

.doc-desc {
    font-size: 0.8rem;
    color: $text-muted;
    margin-bottom: 4px;
}

.doc-file-name {
    font-size: 0.8rem;
    color: $success;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

.upload-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: white;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    &:hover {
        border-color: $primary;
        color: $primary;
        background: rgba($primary, 0.05);
    }
}

.hidden-input {
    display: none;
}

.modal-footer {
    display: flex;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid $border;
    background: color.scale($bg, $lightness: -1%);

    button {
        flex: 1;
        justify-content: center;
    }
}

.btn-primary-modern, .btn-secondary-modern {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary-modern {
    background: $primary;
    color: white;
}

.btn-primary-modern:hover {
    background: #2563eb;
}

.btn-secondary-modern {
    background: white;
    color: $text;
    border: 1px solid $border;
}

.btn-secondary-modern:hover {
    background: $bg;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .document-upload-item {
        flex-direction: column;
        align-items: flex-start;
    }
    .upload-btn {
        width: 100%;
        justify-content: center;
    }
}

/* ==========================================
   УНИВЕРСАЛЬНЫЕ СТИЛИ ДЛЯ ВСЕХ МОДАЛОК
   (Исправляет прозрачность и отсутствие шапки)
   ========================================== */

// Затемнение фона
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6); // Гарантированно непрозрачный темный фон
    backdrop-filter: blur(6px);      // Красивое размытие заднего плана
    z-index: 9999;                   // Поверх всего, включая хедеры
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

// Само окно модалки
.modal-container {
    background: #ffffff;             // Явный белый фон
    border-radius: 20px;
    width: 100%;
    max-height: 90vh;                // Не выше 90% экрана
    overflow: hidden;                // Обрезает всё, что вылезает за скругления
    display: flex;
    flex-direction: column;          // Вертикальное расположение: шапка, тело, подвал
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    animation: modalSlideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

// Шапка модалки (чтобы не пропадала)
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #ffffff;             // Явный фон шапки
    flex-shrink: 0;                  // Запрещаем шапке сжиматься
}

.modal-header h3 {
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}

// Кнопка закрытия (крестик)
.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f3f4f6;
    border: none;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #ef4444;
    color: white;
    transform: rotate(90deg);
}

// Тело модалки (скроллится, если контента много)
.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;                         // Занимает всё доступное пространство
    background: #ffffff;

    // Стилизация скроллбара внутри модалки
    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-track {
        background: transparent;
    }
    &::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
        &:hover { background: #9ca3af; }
    }
}

// Подвал модалки (с кнопками)
.modal-footer {
    display: flex;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;             // Слегка сероватый фон для отделения от тела
    flex-shrink: 0;                  // Запрещаем подвалу сжиматься
}

// Анимации появления
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

// Адаптив для мобильных
@media (max-width: 640px) {
    .modal-overlay {
        padding: 0;
        align-items: flex-end; // Модалка выезжает снизу на телефоне
    }

    .modal-container {
        border-radius: 20px 20px 0 0;
        max-height: 95vh;
    }

    .modal-footer {
        flex-direction: column-reverse; // Кнопки друг под другом
        button {
            width: 100%;
        }
    }
}
</style>
