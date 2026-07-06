<template>
    <div class="kanban-settings">
        <!-- HEADER -->
        <div class="settings-header">
            <div class="header-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="header-content">
                <h3 class="header-title">🎯 Интеграция с KanbanCRM</h3>
                <p class="header-subtitle">
                    Автоматическая синхронизация заказов с вашей CRM-доской
                </p>
            </div>
            <div class="header-status" :class="{ active: settings.enabled }">
                <span class="status-dot"></span>
                {{ settings.enabled ? 'Активно' : 'Отключено' }}
            </div>
        </div>

        <!-- TOGGLE -->
        <div class="toggle-section">
            <label class="toggle-switch">
                <input type="checkbox" v-model="settings.enabled"/>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <i class="fa-solid fa-power-off"></i>
                    Включить интеграцию
                </span>
            </label>
        </div>

        <!-- SETTINGS FORM -->
        <Transition name="fade-slide">
            <div v-if="settings.enabled" class="settings-body">
                <!-- Base URL -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-link"></i>
                        Base URL API
                    </label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-globe input-icon"></i>
                        <input
                            type="url"
                            v-model="settings.base_url"
                            placeholder="https://crm.mypwa.ru/api/v1"
                            class="form-input"
                        />
                    </div>
                    <small class="form-hint">Адрес вашего KanbanCRM API</small>
                </div>

                <!-- Token -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-key"></i>
                        API Token
                    </label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-shield-halved input-icon"></i>
                        <input
                            :type="showToken ? 'text' : 'password'"
                            v-model="settings.token"
                            placeholder="kb_..."
                            class="form-input"
                        />
                        <button
                            type="button"
                            class="input-action"
                            @click="showToken = !showToken"
                            :title="showToken ? 'Скрыть' : 'Показать'"
                        >
                            <i :class="showToken ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                    <small class="form-hint">
                        Токен начинается с <code>kb_</code>
                    </small>
                </div>

                <!-- Board UUID -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-table-columns"></i>
                        UUID доски
                    </label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-fingerprint input-icon"></i>
                        <input
                            type="text"
                            v-model="settings.board_uuid"
                            placeholder="928e6e06-b9b0-4cca-a45c-0926ba7539f6"
                            class="form-input"
                        />
                    </div>
                    <small class="form-hint">Уникальный идентификатор доски в CRM</small>
                </div>

                <!-- Thread -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-list-ol"></i>
                        Колонка для заказов (thread)
                    </label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-hashtag input-icon"></i>
                        <input
                            type="number"
                            v-model.number="settings.order_thread"
                            min="0"
                            placeholder="0"
                            class="form-input"
                        />
                    </div>
                    <small class="form-hint">
                        Порядковый номер колонки: <strong>0</strong> — первая, <strong>1</strong> — вторая и т.д.
                    </small>
                </div>

                <!-- Auto Create Client -->
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="settings.auto_create_client"/>
                        <span class="checkbox-custom">
                            <i class="fa-solid fa-check"></i>
                        </span>
                        <span class="checkbox-text">
                            <strong>Автоматически создавать клиента</strong>
                            <small>Если клиент не найден по телефону — создать нового</small>
                        </span>
                    </label>
                </div>

                <!-- TEST CONNECTION -->
                <div class="test-section">
                    <button
                        class="btn-test"
                        @click="testConnection"
                        :disabled="testing || !isFormValid"
                    >
                        <span v-if="testing" class="btn-spinner"></span>
                        <i v-else class="fa-solid fa-plug"></i>
                        {{ testing ? 'Проверка подключения...' : '🔌 Проверить подключение' }}
                    </button>

                    <Transition name="fade">
                        <div v-if="testResult" :class="['test-result', testResult.success ? 'success' : 'error']">
                            <div class="test-result-icon">
                                <i :class="testResult.success ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                            </div>
                            <div class="test-result-content">
                                <div class="test-result-title">
                                    {{ testResult.success ? 'Подключение успешно' : 'Ошибка подключения' }}
                                </div>
                                <div class="test-result-message">{{ testResult.message }}</div>
                            </div>
                            <button class="test-result-close" @click="testResult = null">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>

        <!-- FOOTER -->
        <div class="settings-footer">
            <button class="btn-save" @click="save" :disabled="saving">
                <span v-if="saving" class="btn-spinner"></span>
                <i v-else class="fa-solid fa-floppy-disk"></i>
                {{ saving ? 'Сохранение...' : '💾 Сохранить настройки' }}
            </button>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        tenant: Object
    },
    data() {
        return {
            settings: this.tenant.settings?.kanban || {
                enabled: false,
                base_url: 'https://crm.mypwa.ru/api/v1',
                token: '',
                board_uuid: '',
                order_thread: 0,
                auto_create_client: true,
            },
            testing: false,
            saving: false,
            testResult: null,
            showToken: false,
        }
    },
    computed: {
        isFormValid() {
            return this.settings.base_url &&
                this.settings.token &&
                this.settings.board_uuid
        }
    },
    async mounted() {
        try {
            await this.initForms();
        } catch (error) {
            console.error('Ошибка загрузки:', error);
        }
    },

    methods: {
        async initForms() {
            const tenant = window.Tenant;
            if (!tenant) return;

            const settings = tenant.settings
            if (!settings) return;

            try {

                this.settings = settings?.kanban || {
                    enabled: false,
                    base_url: 'https://crm.mypwa.ru/api/v1',
                    token: '',
                    board_uuid: '',
                    order_thread: 0,
                    auto_create_client: true,
                }

            } catch (error) {
                console.error('Ошибка загрузки PWA настроек:', error);
            }

        },
        async save() {
            if (!this.isFormValid) {
                alert('⚠️ Заполните все обязательные поля')
                return
            }

            this.saving = true
            try {
                await axios.post(`/admin/tenant-settings/pwa`, {
                    kanban: this.settings
                })
                this.showNotification('✅ Настройки успешно сохранены', 'success')
            } catch (e) {
                this.showNotification('❌ Ошибка сохранения: ' + e.message, 'error')
            } finally {
                this.saving = false
            }
        },
        async testConnection() {
            this.testing = true
            this.testResult = null
            try {
                const {data} = await axios.post('/test-kanban', {
                    settings: this.settings
                })
                this.testResult = {
                    success: true,
                    message: `Найдено ${data.boards_count} досок в KanbanCRM`
                }
            } catch (e) {
                this.testResult = {
                    success: false,
                    message: e.response?.data?.error || e.message || 'Не удалось подключиться'
                }
            } finally {
                this.testing = false
            }
        },
        showNotification(message, type) {
            // Можно заменить на вашу систему уведомлений
            this.$notify?.({title: 'Настройка CRM', text: message, type: type});
        }
    }
}
</script>

<style scoped>
/* === ПЕРЕМЕННЫЕ === */
.kanban-settings {
    --primary: #667eea;
    --primary-dark: #5568d3;
    --secondary: #764ba2;
    --success: #10b981;
    --success-light: #d1fae5;
    --error: #ef4444;
    --error-light: #fee2e2;
    --warning: #f59e0b;
    --text: #212529;
    --text-muted: #6c757d;
    --border: #e9ecef;
    --bg: #ffffff;
    --bg-soft: #f8f9fa;
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
}

/* === КОНТЕЙНЕР === */
.kanban-settings {
    background: var(--bg);
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
}

/* === HEADER === */
.settings-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
}

.header-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-md);
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.header-content {
    flex: 1;
    min-width: 0;
}

.header-title {
    margin: 0 0 4px 0;
    font-size: 20px;
    font-weight: 700;
}

.header-subtitle {
    margin: 0;
    font-size: 13px;
    opacity: 0.9;
}

.header-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.header-status.active {
    background: rgba(16, 185, 129, 0.3);
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #adb5bd;
}

.header-status.active .status-dot {
    background: var(--success);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
    }
    50% {
        box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
    }
}

/* === TOGGLE === */
.toggle-section {
    padding: 20px 24px;
    background: var(--bg-soft);
    border-bottom: 1px solid var(--border);
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    user-select: none;
}

.toggle-switch input {
    display: none;
}

.toggle-slider {
    position: relative;
    width: 48px;
    height: 26px;
    background: #dee2e6;
    border-radius: 13px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-sm);
}

.toggle-switch input:checked + .toggle-slider {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
}

.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(22px);
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
}

.toggle-label i {
    color: var(--primary);
}

/* === BODY === */
.settings-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* === FORM GROUP === */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
}

.form-label i {
    color: var(--primary);
    font-size: 12px;
}

/* === INPUT === */
.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 14px;
    color: #adb5bd;
    font-size: 13px;
    pointer-events: none;
    z-index: 1;
}

.form-input {
    width: 100%;
    padding: 12px 14px 12px 40px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 14px;
    color: var(--text);
    background: var(--bg);
    transition: all 0.2s;
    outline: none;
}

.form-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-input::placeholder {
    color: #adb5bd;
}

.input-action {
    position: absolute;
    right: 8px;
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: #6c757d;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.input-action:hover {
    background: var(--bg-soft);
    color: var(--primary);
}

.form-hint {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0;
    padding-left: 2px;
}

.form-hint code {
    background: var(--bg-soft);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    color: var(--primary);
    font-family: 'Courier New', monospace;
}

/* === CHECKBOX === */
.checkbox-group {
    padding: 16px;
    background: var(--bg-soft);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    user-select: none;
}

.checkbox-label input {
    display: none;
}

.checkbox-custom {
    width: 22px;
    height: 22px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    background: var(--bg);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
    margin-top: 2px;
}

.checkbox-custom i {
    font-size: 12px;
    color: white;
    opacity: 0;
    transform: scale(0);
    transition: all 0.2s;
}

.checkbox-label input:checked + .checkbox-custom {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-color: transparent;
}

.checkbox-label input:checked + .checkbox-custom i {
    opacity: 1;
    transform: scale(1);
}

.checkbox-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.checkbox-text strong {
    font-size: 14px;
    color: var(--text);
}

.checkbox-text small {
    font-size: 12px;
    color: var(--text-muted);
}

/* === TEST SECTION === */
.test-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 8px;
}

.btn-test {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: var(--bg);
    color: var(--primary);
    border: 2px solid var(--primary);
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-test:hover:not(:disabled) {
    background: var(--primary);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-test:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* === TEST RESULT === */
.test-result {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: var(--radius-sm);
    border: 1px solid;
}

.test-result.success {
    background: var(--success-light);
    border-color: var(--success);
    color: #065f46;
}

.test-result.error {
    background: var(--error-light);
    border-color: var(--error);
    color: #991b1b;
}

.test-result-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.test-result.success .test-result-icon {
    color: var(--success);
}

.test-result.error .test-result-icon {
    color: var(--error);
}

.test-result-content {
    flex: 1;
    min-width: 0;
}

.test-result-title {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 2px;
}

.test-result-message {
    font-size: 13px;
    opacity: 0.9;
}

.test-result-close {
    width: 28px;
    height: 28px;
    border: none;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.test-result-close:hover {
    background: rgba(0, 0, 0, 0.1);
}

/* === FOOTER === */
.settings-footer {
    padding: 20px 24px;
    background: var(--bg-soft);
    border-top: 1px solid var(--border);
}

.btn-save {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-save:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* === SPINNER === */
.btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

.btn-test .btn-spinner {
    border-color: rgba(102, 126, 234, 0.3);
    border-top-color: var(--primary);
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* === АНИМАЦИИ === */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* === АДАПТИВ === */
@media (max-width: 640px) {
    .settings-header {
        padding: 20px;
        flex-wrap: wrap;
    }

    .header-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }

    .header-title {
        font-size: 17px;
    }

    .header-status {
        width: 100%;
        justify-content: center;
    }

    .settings-body {
        padding: 20px;
    }

    .settings-footer {
        padding: 16px 20px;
    }
}
</style>
