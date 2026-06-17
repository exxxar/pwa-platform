<template>
    <div class="agent-dashboard">
        <!-- HERO СЕКЦИЯ -->
        <div class="agent-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-header">
                    <div class="agent-info">
                        <div class="agent-avatar">{{ agentInitials }}</div>
                        <div class="agent-details">
                            <h1 class="agent-name">{{ agent.name || 'Агент' }}</h1>
                            <div class="agent-status" :class="'status-' + agent.status">
                                <span class="status-dot"></span>
                                <span>{{ statusText }}</span>
                            </div>
                        </div>
                    </div>
                    <button class="settings-btn" @click="openProfileSettings" title="Настройки профиля">
                        <i class="fa-solid fa-gear"></i>
                    </button>
                </div>

                <!-- Ключевые метрики -->
                <div class="metrics-grid">
                    <div class="metric-card" @click="activeTab = 'finance'">
                        <div class="metric-icon"><i class="fa-solid fa-wallet"></i></div>
                        <div class="metric-info">
                            <div class="metric-label">Баланс</div>
                            <div class="metric-value">{{ formatPrice(agent.balance) }}</div>
                        </div>
                    </div>
                    <div class="metric-card" @click="activeTab = 'tenants'">
                        <div class="metric-icon"><i class="fa-solid fa-rotenant"></i></div>
                        <div class="metric-info">
                            <div class="metric-label">Создано приложений</div>
                            <div class="metric-value">{{ agent.tenant_count }}</div>
                        </div>
                    </div>
                    <div class="metric-card" @click="activeTab = 'finance'">
                        <div class="metric-icon"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="metric-info">
                            <div class="metric-label">Заработано</div>
                            <div class="metric-value">{{ formatPrice(agent.total_earned) }}</div>
                        </div>
                    </div>
                    <div class="metric-card" @click="activeTab = 'tenants'">
                        <div class="metric-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="metric-info">
                            <div class="metric-label">Клиентов</div>
                            <div class="metric-value">{{ agent.clients_count }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- НАВИГАЦИЯ -->
        <div class="nav-wrapper">
            <div class="nav-tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    class="nav-tab"
                    :class="{ 'is-active': activeTab === tab.id }"
                    @click="activeTab = tab.id"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.label }}</span>
                    <span v-if="tab.badge" class="nav-badge">{{ tab.badge }}</span>
                </button>
            </div>
        </div>

        <!-- КОНТЕНТ (Динамические компоненты) -->
        <div class="dashboard-content">
            <AgentOverview
                v-if="activeTab === 'overview'"
                :agent="agent"
                @create-tenant="createNewTenant"
                @create-invoice="openInvoiceModal"
                @open-all-transactions="openAllTransactionsModal"
                :activities="recentActivities"
                :notifications="notifications"
            />

            <AgentTenants
                v-if="activeTab === 'tenants'"
                :tenants="tenants"
                @create-tenant="createNewTenant"
                @edit-tenant="editTenant"
                @view-tenant="viewTenant"
                @delete-tenant="deleteTenant"
            />

            <AgentIncomeForecast
                v-if="activeTab === 'forecast'"
                :agent="agent"
            />

            <AgentFinance
                v-if="activeTab === 'finance'"
                :agent="agent"
                :transactions="transactions"
                @payout-requested="handlePayout"
                @open-invoice="openInvoiceModal"
                @invoice-created="handleInvoice"
            />

            <AgentDocuments
                v-if="activeTab === 'documents'"
                :documents="requiredDocuments"
                :verification-status="verificationStatus"
                @document-uploaded="handleDocumentUpload"
            />

            <AgentMarketing
                v-if="activeTab === 'marketing'"
                :agent="agent"
                :categories="marketingCategories"
                @download="downloadMaterial"
                @copy-referral="copyReferralLink"
            />


            <!-- ========================================== -->
            <!-- МОДАЛКА: НАСТРОЙКИ ПРОФИЛЯ -->
            <!-- ========================================== -->
            <AgentProfileSettings
                :is-open="showProfileModal"
                :agent="agent"
                @close="showProfileModal = false"
                @save="handleProfileSave"
            />

            <!-- ========================================== -->
            <!-- ГЛОБАЛЬНАЯ МОДАЛКА: ВЫСТАВИТЬ СЧЁТ -->
            <!-- ========================================== -->
            <transition name="modal-fade">
                <div v-if="showInvoiceModal" class="modal-overlay" @click.self="showInvoiceModal = false">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3>Выставить счёт клиенту</h3>
                            <button class="modal-close" @click="showInvoiceModal = false">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Название клиента / Компания</label>
                                    <input type="text" v-model="invoiceForm.client_name" class="form-input" placeholder="Иван Иванов или ООО 'Ромашка'">
                                </div>
                                <div class="form-group">
                                    <label>Email клиента</label>
                                    <input type="email" v-model="invoiceForm.client_email" class="form-input" placeholder="client@example.com">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Тип услуги</label>
                                    <select v-model="invoiceForm.service_type" class="form-input">
                                        <option value="bot">Создание бота</option>
                                        <option value="setup">Настройка и интеграция</option>
                                        <option value="support">Техническая поддержка</option>
                                        <option value="other">Другое</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Сумма счёта, ₽</label>
                                    <input type="number" v-model.number="invoiceForm.amount" class="form-input" placeholder="10000" min="1">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Описание услуги (для счёта)</label>
                                <textarea v-model="invoiceForm.description" class="form-input textarea" rows="3" placeholder="Например: Разработка Telegram-бота для автоматизации заявок..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-secondary-modern" @click="showInvoiceModal = false">Отмена</button>
                            <button class="btn-primary-modern" @click="submitInvoice" :disabled="!invoiceForm.client_name || !invoiceForm.amount">
                                <i class="fa-solid fa-paper-plane"></i> Отправить счёт
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- ========================================== -->
            <!-- ГЛОБАЛЬНАЯ МОДАЛКА: ВСЕ ОПЕРАЦИИ -->
            <!-- ========================================== -->
            <transition name="modal-fade">
                <div v-if="showAllTransactionsModal" class="modal-overlay" @click.self="showAllTransactionsModal = false">
                    <div class="modal-container transactions-modal">
                        <div class="modal-header">
                            <h3>Все финансовые операции</h3>
                            <button class="modal-close" @click="showAllTransactionsModal = false">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Фильтры внутри модалки -->
                            <div class="modal-filters">
                                <button
                                    class="filter-chip"
                                    :class="{ 'is-active': modalTransactionFilter === 'all' }"
                                    @click="modalTransactionFilter = 'all'"
                                >
                                    Все
                                </button>
                                <button
                                    class="filter-chip"
                                    :class="{ 'is-active': modalTransactionFilter === 'income' }"
                                    @click="modalTransactionFilter = 'income'"
                                >
                                    <i class="fa-solid fa-arrow-down" style="color: #10b981;"></i> Начисления
                                </button>
                                <button
                                    class="filter-chip"
                                    :class="{ 'is-active': modalTransactionFilter === 'payout' }"
                                    @click="modalTransactionFilter = 'payout'"
                                >
                                    <i class="fa-solid fa-arrow-up" style="color: #ef4444;"></i> Выплаты
                                </button>
                            </div>

                            <!-- Список операций -->
                            <div class="modal-transactions-list">
                                <div v-if="filteredModalTransactions.length === 0" class="empty-modal-state">
                                    <i class="fa-solid fa-receipt"></i>
                                    <p>Операций не найдено</p>
                                </div>

                                <div
                                    v-for="tx in filteredModalTransactions"
                                    :key="tx.id"
                                    class="modal-transaction-item"
                                >
                                    <div class="tx-icon" :class="'type-' + tx.type">
                                        <i :class="tx.icon"></i>
                                    </div>
                                    <div class="tx-info">
                                        <div class="tx-title">{{ tx.title }}</div>
                                        <div class="tx-date">{{ tx.date }}</div>
                                    </div>
                                    <div class="tx-amount" :class="tx.amountClass">
                                        {{ tx.amount }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-secondary-modern w-100" @click="showAllTransactionsModal = false">
                                Закрыть
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>


    </div>
</template>

<script>
import AgentOverview from '@/MobileClient/Components/Agent/AgentOverview.vue';
import AgentTenants from '@/MobileClient/Components/Agent/AgentTenants.vue';
import AgentFinance from '@/MobileClient/Components/Agent/AgentFinance.vue';
import AgentDocuments from '@/MobileClient/Components/Agent/AgentDocuments.vue';
import AgentMarketing from '@/MobileClient/Components/Agent/AgentMarketing.vue';
import AgentIncomeForecast from '@/MobileClient/Components/Agent/AgentIncomeForecast.vue';
import AgentProfileSettings from '@/MobileClient/Components/Agent/AgentProfileSettings.vue';
export default {
    name: "AgentDashboard",
    components: {
        AgentOverview,
        AgentTenants,
        AgentFinance,
        AgentDocuments,
        AgentMarketing,
        AgentProfileSettings,
        AgentIncomeForecast,
    },
    data() {
        return {
            activeTab: 'overview',

            showProfileModal: false,
            // Глобальное состояние
            agent: {
                name: 'Александр Петров',
                status: 'verified',
                balance: 45200,
                pending_balance: 12000,
                total_earned: 187500,
                tenant_count: 12,
                clients_count: 9,
                referrals_count: 156,
            },
            tabs: [
                {id: 'overview', label: 'Обзор', icon: 'fa-solid fa-chart-pie'},
                {id: 'tenants', label: 'Мои приложения', icon: 'fa-solid fa-rotenant', badge: 12},
                { id: 'forecast', label: 'Прогноз', icon: 'fa-solid fa-chart-line' },
                {id: 'finance', label: 'Финансы', icon: 'fa-solid fa-wallet'},
                {id: 'documents', label: 'Документы', icon: 'fa-solid fa-folder-open'},
                {id: 'marketing', label: 'Маркетинг', icon: 'fa-solid fa-bullhorn'},
            ],

            // Данные для передачи в дочерние компоненты
            tenants: [
                {
                    id: 1,
                    name: 'Бот для кофейни "Арома"',
                    client_name: 'Иван Иванов',
                    status: 'active',
                    statusText: 'Активен',
                    icon: 'fa-solid fa-mug-hot',
                    color: 'linear-gradient(135deg, #8b4513 0%, #d2691e 100%)',
                    created_at: '15.06.2026',
                    earnings: 15000
                },
                {
                    id: 2,
                    name: 'Магазин цветов "Флора"',
                    client_name: 'Мария Сидорова',
                    status: 'active',
                    statusText: 'Активен',
                    icon: 'fa-solid fa-seedling',
                    color: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                    created_at: '10.06.2026',
                    earnings: 22000
                },
                {
                    id: 3,
                    name: 'Салон красоты "Гламур"',
                    client_name: 'Елена Козлова',
                    status: 'draft',
                    statusText: 'Черновик',
                    icon: 'fa-solid fa-spa',
                    color: 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)',
                    created_at: '05.06.2026',
                    earnings: 0
                },
            ],
            transactions: [
                {
                    id: 1,
                    type: 'income',
                    icon: 'fa-solid fa-plus',
                    title: 'Оплата от Иванова И.И.',
                    date: '15 июня 2026',
                    amount: '+15 000 ₽',
                    amountClass: 'income'
                },
                {
                    id: 2,
                    type: 'payout',
                    icon: 'fa-solid fa-arrow-up',
                    title: 'Вывод на карту',
                    date: '12 июня 2026',
                    amount: '−30 000 ₽',
                    amountClass: 'expense'
                },
            ],
            recentActivities: [],
            notifications: [
                {id: 1, type: 'success', icon: 'fa-solid fa-circle-check', text: 'Документы успешно верифицированы'},
            ],
            requiredDocuments: [
                {
                    id: 1,
                    title: 'Паспорт',
                    description: 'Разворот с фото и прописка',
                    icon: 'fa-solid fa-id-card',
                    required: true,
                    uploaded: true,
                    uploaded_at: '10.05.2026'
                },
                {
                    id: 2,
                    title: 'СНИЛС',
                    description: 'Страховое свидетельство',
                    icon: 'fa-solid fa-shield-halved',
                    required: true,
                    uploaded: false
                },
            ],
            marketingCategories: [
                {
                    id: 1,
                    title: 'Презентации',
                    description: 'Материалы для встреч',
                    icon: 'fa-regular fa-file-powerpoint',
                    color: 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
                    materials: [{
                        id: 101,
                        title: 'Презентация продукта',
                        size: '2.4 МБ',
                        url: '/marketing/presentation.pdf'
                    }]
                },
                {
                    id: 2,
                    title: 'Договора',
                    description: 'Шаблон договора',
                    icon: 'fa-solid fa-file-invoice-dollar',
                    color: 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
                    materials: [{
                        id: 102,
                        title: 'Шаблон договора',
                        size: '12.4 МБ',
                        url: '/marketing/document.doc'
                    }]
                }
            ],
            verificationStatus: 'partial',

            showInvoiceModal: false, // <-- Добавляем это
            showAllTransactionsModal: false,
            modalTransactionFilter: 'all', // 'all', 'income', 'payout'
            invoiceForm: {           // <-- Добавляем это
                client_name: '',
                client_email: '',
                service_type: 'bot',
                amount: null,
                description: '',
            },
        };
    },
    computed: {
        filteredModalTransactions() {
            if (this.modalTransactionFilter === 'all') return this.transactions;
            return this.transactions.filter(t => t.type === this.modalTransactionFilter);
        },
        agentInitials() {
            return this.agent.name ? this.agent.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : 'А';
        },
        statusText() {
            const statuses = {pending: 'На проверке', verified: 'Верифицирован', suspended: 'Приостановлен'};
            return statuses[this.agent.status] || 'Неизвестно';
        }
    },
    mounted() {
        this.recentActivities = this.transactions.slice(0, 5);
    },
    methods: {
        openProfileSettings() {
            this.showProfileModal = true;
        },

        handleProfileSave(updatedProfileData) {
            // Обновляем данные агента в главном состоянии
            this.agent = {
                ...this.agent,
                profile: updatedProfileData,
                name: updatedProfileData.name // Обновляем имя для шапки
            };

            this.showProfileModal = false;

            this.$notify?.({
                title: 'Успех',
                text: 'Данные профиля и реквизиты сохранены',
                type: 'success'
            });
        },
        openAllTransactionsModal() {
            this.modalTransactionFilter = 'all'; // Сбрасываем фильтр при открытии
            this.showAllTransactionsModal = true;
        },
        openInvoiceModal() {

            this.invoiceForm = {
                client_name: '',
                client_email: '',
                service_type: 'bot',
                amount: null,
                description: '',
            };
            this.showInvoiceModal = true;
        },

        // Метод отправки счёта
        async submitInvoice() {
            if (!this.invoiceForm.client_name || !this.invoiceForm.amount) {
                this.$notify?.({ title: 'Ошибка', text: 'Заполните название клиента и сумму', type: 'error' });
                return;
            }

            try {
                // TODO: await this.$store.dispatch('createInvoice', this.invoiceForm);
                await new Promise(resolve => setTimeout(resolve, 800)); // Имитация запроса

                this.$notify?.({
                    title: 'Успех',
                    text: `Счёт на ${this.formatPrice(this.invoiceForm.amount)} отправлен клиенту`,
                    type: 'success'
                });

                this.showInvoiceModal = false;
            } catch (err) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось создать счёт', type: 'error' });
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(price || 0);
        },

        // Методы-обработчики событий от дочерних компонентов
        createNewTenant() {
            this.$notify?.({title: 'Создание', text: 'Переход к мастеру', type: 'info'});
        },
        editTenant(tenant) {
            this.$notify?.({title: 'Редактирование', text: `Настройки: ${tenant.name}`, type: 'info'});
        },
        viewTenant(tenant) {
            this.$notify?.({title: 'Просмотр', text: `Открываем: ${tenant.name}`, type: 'info'});
        },
        deleteTenant(tenantId) {
            this.tenants = this.tenants.filter(b => b.id !== tenantId);
            this.agent.tenant_count = this.tenants.length;
            this.$notify?.({title: 'Успех', text: 'Бот удалён', type: 'success'});
        },
        handlePayout(data) {
            this.agent.balance -= data.amount;
            this.$notify?.({
                title: 'Выплата',
                text: `Заявка на ${this.formatPrice(data.amount)} принята`,
                type: 'success'
            });
        },
        handleInvoice(data) {
            this.$notify?.({
                title: 'Счёт',
                text: `Счёт на ${this.formatPrice(data.amount)} отправлен`,
                type: 'success'
            });
        },
        handleDocumentUpload(docId) {
            const doc = this.requiredDocuments.find(d => d.id === docId);
            if (doc) {
                doc.uploaded = true;
                doc.uploaded_at = new Date().toLocaleDateString('ru-RU');
                this.verificationStatus = 'verified'; // Упрощенная логика для примера
                this.$notify?.({title: 'Успех', text: 'Документ загружен', type: 'success'});
            }
        },
        downloadMaterial(material) {
            this.$notify?.({title: 'Скачивание', text: `Файл "${material.title}" скачивается`, type: 'success'});
        },
        copyReferralLink() {
            this.$notify?.({title: 'Успех', text: 'Ссылка скопирована в буфер обмена', type: 'success'});
        }
    }
};
</script>

<style lang="scss" scoped>

@use 'sass:color';
// ==========================================
// ЦВЕТОВЫЕ ПЕРЕМЕННЫЕ (Design System)
// ==========================================
$primary: #3b82f6;        // Основной синий
$primary-light: #60a5fa;  // Светло-синий
$primary-dark: #2563eb;   // Тёмно-синий

$success: #10b981;        // Зелёный (успех, продления)
$danger: #ef4444;         // Красный (ошибка, удаление)
$warning: #f59e0b;        // Оранжевый/Жёлтый (предупреждение)

$purple: #8b5cf6;         // Фиолетовый (прогноз, тренды)
$pink: #ec4899;           // Розовый (акценты)

$text: #1f2937;           // Основной текст (тёмно-серый)
$text-muted: #6b7280;     // Второстепенный текст (серый)

$border: #e5e7eb;         // Цвет границ и разделителей
$bg: #f9fafb;             // Цвет фона страницы (очень светлый серый)
$card-bg: #ffffff;        // Цвет фона карточек и модалок (белый)

/* Базовые стили для Hero и Навигации (сокращенно, возьмите из предыдущего ответа) */
.agent-dashboard {
    min-height: 100vh;
    background: #f9fafb;
    padding-bottom: 40px;
}

.agent-hero {
    position: relative;
    padding: 32px 24px 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
}

.hero-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-tenanttom: 28px;
}

.agent-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.agent-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 700;
}

.agent-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.agent-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    opacity: 0.95;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 8px #4ade80;
}

.settings-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}

.settings-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: rotate(45deg);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.metric-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    cursor: pointer;
    transition: 0.2s;
}

.metric-card:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: translateY(-2px);
}

.metric-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.metric-info {
    flex: 1;
}

.metric-label {
    font-size: 0.75rem;
    opacity: 0.85;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-value {
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1;
}

.nav-wrapper {
    background: white;
    border-tenanttom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.nav-tabs {
    display: flex;
    gap: 4px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 8px 24px;
    overflow-x: auto;
}

.nav-tabs::-webkit-scrollbar {
    display: none;
}

.nav-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    white-space: nowrap;
}

.nav-tab:hover {
    background: rgba(59, 130, 246, 0.05);
    color: #3b82f6;
}

.nav-tab.is-active {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.nav-badge {
    padding: 2px 8px;
    background: #3b82f6;
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.dashboard-content {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 24px;
}

// ==========================================
// МОДАЛЬНЫЕ ОКНА (ГЛОБАЛЬНЫЕ)
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(31, 41, 55, 0.6); // Темный полупрозрачный фон
    backdrop-filter: blur(4px);        // Размытие фона под модалкой
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: $card-bg;
    border-radius: 20px;
    width: 100%;
    max-width: 550px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid $border;

    h3 {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
        color: $text;
    }
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;

    &:hover {
        background: $danger;
        color: white;
        transform: rotate(90deg);
    }
}

.modal-body {
    padding: 24px;
    overflow-y: auto;

    // Скроллбар для красоты
    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-track {
        background: transparent;
    }
    &::-webkit-scrollbar-thumb {
        background: $border;
        border-radius: 3px;
    }
}

.modal-footer {
    display: flex;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid $border;
    background: $bg;

    button {
        flex: 1;
    }
}

// ==========================================
// ЭЛЕМЕНТЫ ФОРМЫ В МОДАЛКЕ
// ==========================================
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 16px;

    &:last-child {
        margin-bottom: 0;
    }

    label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;
    }
}

.form-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.9rem;
    background: $card-bg;
    color: $text;
    transition: all 0.2s;
    font-family: inherit;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }

    &::placeholder {
        color:  color.adjust($text-muted, $lightness: 10%);
    }

    &.textarea {
        resize: vertical;
        min-height: 80px;
    }
}

// ==========================================
// КНОПКИ (Дублируем для надежности, если их нет в глобальных стилях)
// ==========================================
.btn-primary-modern, .btn-secondary-modern {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-primary-modern {
    background: $primary;
    color: white;

    &:hover:not(:disabled) {
        background:  color.adjust($primary, $lightness: -5%);
        transform: translateY(-1px);
    }
}

.btn-secondary-modern {
    background: $card-bg;
    color: $text;
    border: 1px solid $border;

    &:hover:not(:disabled) {
        background: $bg;
        border-color: $primary;
        color: $primary;
    }
}

// ==========================================
// АНИМАЦИИ ПОЯВЛЕНИЯ/ИСЧЕЗНОВЕНИЯ
// ==========================================
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВ ДЛЯ МОДАЛОК
// ==========================================
@media (max-width: 640px) {
    .modal-overlay {
        padding: 0;
        align-items: flex-end; // Модалка выезжает снизу на мобильных
    }

    .modal-container {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 20px 20px 0 0;
        animation: modalSlideUpMobile 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes modalSlideUpMobile {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }

    .form-row {
        grid-template-columns: 1fr; // Поля в одну колонку на телефоне
        gap: 0;
    }

    .modal-footer {
        flex-direction: column-reverse; // Кнопка "Отправить" сверху на мобильном
        gap: 8px;
    }
}

/* ==========================================
   МОДАЛКА: ВСЕ ОПЕРАЦИИ
   ========================================== */
.transactions-modal {
    max-width: 700px; // Делаем её чуть шире, чем модалку счёта
}

.modal-filters {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid $border;
    flex-wrap: wrap;
}

.filter-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 20px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        color: $primary;
        background: rgba($primary, 0.05);
    }

    &.is-active {
        background: $primary;
        border-color: $primary;
        color: white;
    }
}

.modal-transactions-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 400px;
    overflow-y: auto;
    padding-right: 8px; // Место для скроллбара

    // Кастомный скроллбар
    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-track {
        background: $bg;
        border-radius: 3px;
    }
    &::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
        &:hover { background: #9ca3af; }
    }
}

.modal-transaction-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    background: $bg;
    border-radius: 12px;
    transition: background 0.2s;

    &:hover {
        background: color.scale($bg, $lightness: -2%);
    }
}

.tx-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;

    &.type-income {
        background: rgba($success, 0.1);
        color: $success;
    }

    &.type-payout {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.tx-info {
    flex: 1;
    min-width: 0;
}

.tx-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: $text;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tx-date {
    font-size: 0.8rem;
    color: $text-muted;
}

.tx-amount {
    font-weight: 700;
    font-size: 1rem;
    white-space: nowrap;

    &.income {
        color: $success;
    }

    &.expense {
        color: $danger;
    }
}

.empty-modal-state {
    text-align: center;
    padding: 40px 20px;
    color: $text-muted;

    i {
        font-size: 2.5rem;
        margin-bottom: 12px;
        opacity: 0.4;
    }

    p {
        font-size: 0.95rem;
        margin: 0;
    }
}

.w-100 {
    width: 100%;
}


</style>
