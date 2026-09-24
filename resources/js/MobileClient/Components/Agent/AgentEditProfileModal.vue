<template>
    <div class="modal fade agent-modal" tabindex="-1" ref="modalElement" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <!-- Шапка модалки -->
                <div class="modal-header">
                    <div class="header-icon-wrapper">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="modal-title fw-bold mb-0">Редактирование профиля</h5>
                        <small class="text-muted">Обновите личные данные и реквизиты для выплат</small>
                    </div>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Тело модалки -->
                <div class="modal-body">
                    <form @submit.prevent="submitForm">

                        <!-- Секция 1: Личные данные -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="fa-solid fa-id-card me-2"></i>Личные данные
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Имя / Название организации</label>
                                    <input type="text" class="form-control modern-input" v-model="form.name" required placeholder="Иванов Иван Иванович">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Телефон</label>
                                    <input type="tel" class="form-control modern-input" v-model="form.phone" placeholder="+7 (999) 000-00-00">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Email для уведомлений</label>
                                    <input type="email" class="form-control modern-input" v-model="form.email" placeholder="agent@example.com">
                                </div>
                            </div>
                        </div>

                        <hr class="section-divider">

                        <!-- Секция 2: Агентские реквизиты -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="fa-solid fa-building-columns me-2"></i>Реквизиты для выплат
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Юридический статус</label>
                                    <select class="form-select modern-input" v-model="form.legal_type">
                                        <option value="self_employed">Самозанятый</option>
                                        <option value="ip">Индивидуальный предприниматель (ИП)</option>
                                        <option value="legal_entity">Юридическое лицо (ООО)</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">ИНН <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control modern-input" v-model="form.inn" required placeholder="10 или 12 цифр" maxlength="12">
                                </div>
                                <div class="col-md-6" v-if="form.legal_type !== 'self_employed'">
                                    <label class="form-label">{{ form.legal_type === 'ip' ? 'ОГРНИП' : 'ОГРН' }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control modern-input" v-model="form.ogrn" placeholder="13 или 15 цифр" maxlength="15">
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label">Расчетный счет <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control modern-input" v-model="form.bank_account" placeholder="20 цифр" required maxlength="20">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">БИК <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control modern-input" v-model="form.bik" placeholder="9 цифр" required maxlength="9">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Название банка <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control modern-input" v-model="form.bank_name" placeholder="Например: ПАО Сбербанк" required>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Подвал модалки -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-modern" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary-modern" @click="submitForm" :disabled="isLoading">
                        <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="fa-solid fa-check me-2"></i>
                        Сохранить изменения
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap';
import axios from 'axios';

export default {
    name: "AgentEditProfileModal",
    props: {
        user: { type: Object, required: true }
    },
    emits: ['saved'],
    data() {
        return {
            modalInstance: null,
            isLoading: false,
            form: {
                name: '',
                phone: '',
                email: '',
                legal_type: 'self_employed',
                inn: '',
                ogrn: '',
                bank_account: '',
                bik: '',
                bank_name: ''
            }
        }
    },
    mounted() {
        this.modalInstance = new Modal(this.$refs.modalElement, {
            backdrop: 'static', // Запрет закрытия по клику вне модалки (опционально, для важных форм)
            keyboard: true
        });
    },
    methods: {
        show() {
            const agent = this.user.agent_profile || {};
            this.form = {
                name: this.user.name || '',
                phone: this.user.phone || '',
                email: this.user.email || '',
                legal_type: agent.legal_type || 'self_employed',
                inn: agent.inn || '',
                ogrn: agent.ogrn || '',
                bank_account: agent.bank_account || '',
                bik: agent.bik || '',
                bank_name: agent.bank_name || ''
            };
            this.modalInstance.show();
        },
        async submitForm() {
            this.isLoading = true;
            try {
                // Очищаем данные от пробелов перед отправкой
                const payload = {
                    ...this.form,
                    inn: this.form.inn.replace(/\s/g, ''),
                    bank_account: this.form.bank_account.replace(/\s/g, ''),
                    bik: this.form.bik.replace(/\s/g, ''),
                    ogrn: this.form.ogrn.replace(/\s/g, ''),
                };

                const response = await axios.put('/agent/profile', payload);

                if (response.data.success) {
                    this.modalInstance.hide();

                    const updatedUser = {
                        ...this.user,
                        name: this.form.name,
                        phone: this.form.phone,
                        email: this.form.email,
                        agent_profile: {
                            ...this.user.agent_profile,
                            legal_type: this.form.legal_type,
                            inn: this.form.inn,
                            ogrn: this.form.ogrn,
                            bank_account: this.form.bank_account,
                            bik: this.form.bik,
                            bank_name: this.form.bank_name,
                            verification_status: response.data.data.verification_status || 'partial'
                        }
                    };

                    this.$emit('saved', updatedUser);
                }
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                const msg = error.response?.data?.message || 'Не удалось сохранить данные. Проверьте формат реквизитов.';
                this.$notify?.({ title: "Ошибка", text: msg, type: "error" });
            } finally {
                this.isLoading = false;
            }
        }
    }
}
</script>

<style scoped>
/* ==========================================
   ОСНОВНЫЕ СТИЛИ МОДАЛКИ
   ========================================== */
.agent-modal .modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    background: #ffffff;
}

/* Шапка */
.agent-modal .modal-header {
    background: linear-gradient(to right, #f8fafc, #ffffff);
    border-bottom: 1px solid #e2e8f0;
    padding: 24px 32px;
    align-items: center;
}

.header-icon-wrapper {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.agent-modal .modal-title {
    color: #1e293b;
    font-size: 1.1rem;
}

.agent-modal .modal-header small {
    font-size: 0.8rem;
    color: #64748b;
}

.btn-close-custom {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 1.1rem;
}

.btn-close-custom:hover {
    background: #fee2e2;
    color: #ef4444;
    transform: rotate(90deg);
}

/* Тело */
.agent-modal .modal-body {
    padding: 32px;
    max-height: 70vh;
    overflow-y: auto;
}

/* Скроллбар для тела модалки */
.agent-modal .modal-body::-webkit-scrollbar {
    width: 6px;
}
.agent-modal .modal-body::-webkit-scrollbar-track {
    background: transparent;
}
.agent-modal .modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

/* Секции формы */
.form-section {
    margin-bottom: 8px;
}

.section-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
}

.section-title i {
    color: #3b82f6;
}

.section-divider {
    border: 0;
    border-top: 1px dashed #e2e8f0;
    margin: 32px 0;
}

/* Поля ввода */
.form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}

.modern-input {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.95rem;
    color: #1e293b;
    background-color: #ffffff;
    transition: all 0.2s ease;
}

.modern-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
    background-color: #ffffff;
}

.modern-input::placeholder {
    color: #94a3b8;
}

.modern-input:disabled {
    background-color: #f8fafc;
    cursor: not-allowed;
}

/* Подвал */
.agent-modal .modal-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 20px 32px;
    gap: 12px;
}

.btn-light-modern {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.btn-light-modern:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1e293b;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
    color: #ffffff;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}

.btn-primary-modern:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-primary-modern:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* Адаптив для мобильных */
@media (max-width: 576px) {
    .agent-modal .modal-dialog {
        margin: 0;
        max-height: 100vh;
    }

    .agent-modal .modal-content {
        border-radius: 20px 20px 0 0;
        max-height: 95vh;
    }

    .agent-modal .modal-header,
    .agent-modal .modal-body,
    .agent-modal .modal-footer {
        padding-left: 20px;
        padding-right: 20px;
    }

    .agent-modal .modal-body {
        max-height: 60vh;
    }
}

/* ==========================================
   АДАПТИВ ДЛЯ МОБИЛЬНЫХ (ПРИЖАТИЕ К НИЗУ)
   ========================================== */
@media (max-width: 576px) {
    /* 1. Переопределяем центрирование Bootstrap и прижимаем к низу */
    .agent-modal .modal-dialog {
        margin: 0 !important;
        max-height: 100vh;
        display: flex;
        align-items: flex-end !important; /* Ключевое свойство */
    }

    /* 2. Скругляем только верхние углы и задаем максимальную высоту */
    .agent-modal .modal-content {
        border-radius: 24px 24px 0 0 !important;
        max-height: 90vh; /* Оставляем 10% экрана сверху, чтобы было видно затемнение */
        margin-bottom: 0;
        border-bottom: none;
    }

    /* 3. Уменьшаем боковые отступы для экономии места на телефоне */
    .agent-modal .modal-header,
    .agent-modal .modal-body,
    .agent-modal .modal-footer {
        padding-left: 20px !important;
        padding-right: 20px !important;
    }

    .agent-modal .modal-header {
        padding-top: 20px !important;
    }

    /* 4. Настраиваем скролл внутри тела модалки */
    .agent-modal .modal-body {
        max-height: 65vh; /* Оптимальная высота для прокрутки контента */
    }

    /* 5. Делаем кнопки на всю ширину и друг под другом для удобства нажатия пальцем */
    .agent-modal .modal-footer {
        flex-direction: column-reverse;
        gap: 10px;
        padding-bottom: max(20px, env(safe-area-inset-bottom)); /* Учет челки/полоски iPhone */
    }

    .agent-modal .modal-footer .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
