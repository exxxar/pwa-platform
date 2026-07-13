<template>
    <div class="notification-widget">
        <!-- Группа кнопок через Grid -->
        <div class="notification-buttons">
            <!-- Основная кнопка: вкл/выкл (2/3 ширины) -->
            <button
                v-if="permission !== 'denied'"
                class="btn-notif-main"
                :class="{ 'is-active': subscription }"
                :disabled="loading"
                @click="handleToggle"
            >
                <span v-if="loading" class="spinner"></span>
                <template v-else>
                    <i :class="subscription ? 'fas fa-bell' : 'fas fa-bell-slash'"></i>
                    <span class="btn-text">
                        {{ subscription ? 'Уведомления включены' : 'Включить уведомления' }}
                    </span>
                </template>
            </button>

            <!-- Заблокировано -->
            <button
                v-else
                class="btn-notif-main is-disabled"
                disabled
            >
                <i class="fas fa-ban"></i>
                <span class="btn-text">Уведомления заблокированы</span>
            </button>

            <!-- Кнопка с информацией (1/3 ширины) -->
            <button
                type="button"
                class="btn-notif-icon"
                @click="openModal"
                title="Подробная информация"
            >
                <i class="fas fa-info-circle"></i>
            </button>
        </div>

        <!-- Кастомная модалка -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                    <div class="modal-container">
                        <!-- Заголовок -->
                        <div class="modal-header">
                            <div class="modal-title">
                                <div class="title-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <h2>Статус уведомлений</h2>
                            </div>
                            <button class="modal-close" @click="closeModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Контент -->
                        <div class="modal-body">
                            <!-- Статус подписки -->
                            <div class="status-card">
                                <div class="status-header">
                                    <i :class="subscription ? 'fas fa-check-circle' : 'fas fa-times-circle'"
                                       :style="{ color: subscription ? '#10b981' : '#ef4444' }"></i>
                                    <span class="status-label">Подписка</span>
                                </div>
                                <p class="status-value">
                                    {{ subscription ? 'Активна' : 'Не активна' }}
                                </p>
                            </div>

                            <!-- Разрешение браузера -->
                            <div class="status-card">
                                <div class="status-header">
                                    <i :class="[permissionIcon]" :style="{ color: permissionColorHex }"></i>
                                    <span class="status-label">Разрешение браузера</span>
                                </div>
                                <p class="status-value">{{ permissionText }}</p>
                            </div>

                            <!-- Endpoint (если есть подписка) -->
                            <div v-if="subscription" class="status-card">
                                <div class="status-header">
                                    <i class="fas fa-link" style="color: #3b82f6"></i>
                                    <span class="status-label">Endpoint</span>
                                </div>
                                <div class="endpoint-box">
                                    <code>{{ subscription.endpoint }}</code>
                                </div>
                            </div>

                            <!-- Предупреждение если заблокировано -->
                            <div v-if="permission === 'denied'" class="warning-card">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Уведомления заблокированы</strong>
                                    <p>Разрешите уведомления в настройках браузера для этого сайта</p>
                                </div>
                            </div>
                        </div>

                        <!-- Футер -->
                        <div class="modal-footer">
                            <button class="btn-secondary" @click="closeModal">Закрыть</button>
                            <button
                                v-if="!subscription && permission !== 'denied'"
                                class="btn-primary"
                                :disabled="loading"
                                @click="handleSubscribe"
                            >
                                <span v-if="loading" class="spinner-small"></span>
                                <template v-else>
                                    <i class="fas fa-bell"></i>
                                    <span>Включить</span>
                                </template>
                            </button>
                            <button
                                v-else-if="subscription"
                                class="btn-danger"
                                :disabled="loading"
                                @click="handleUnsubscribe"
                            >
                                <span v-if="loading" class="spinner-small"></span>
                                <template v-else>
                                    <i class="fas fa-bell-slash"></i>
                                    <span>Отключить</span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePushNotifications } from '@/MobileClient/composables/usePushNotifications';

const { subscription, permission, subscribe, unsubscribe } = usePushNotifications();

const loading = ref(false);
const showModal = ref(false);

const permissionIcon = computed(() => {
    if (permission.value === 'granted') return 'fas fa-check-circle';
    if (permission.value === 'denied') return 'fas fa-ban';
    return 'fas fa-question-circle';
});

const permissionColorHex = computed(() => {
    if (permission.value === 'granted') return '#10b981';
    if (permission.value === 'denied') return '#ef4444';
    return '#6b7280';
});

const permissionText = computed(() => {
    if (permission.value === 'granted') return 'Разрешено';
    if (permission.value === 'denied') return 'Заблокировано';
    return 'Не запрошено';
});

function openModal() {
    showModal.value = true;
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    showModal.value = false;
    document.body.style.overflow = '';
}

async function handleSubscribe() {
    loading.value = true;
    try {
        await subscribe();
    } catch (e) {
        console.error('Ошибка подписки:', e);
    } finally {
        loading.value = false;
    }
}

async function handleUnsubscribe() {
    loading.value = true;
    try {
        await unsubscribe();
    } catch (e) {
        console.error('Ошибка отписки:', e);
    } finally {
        loading.value = false;
    }
}

async function handleToggle() {
    if (subscription.value) {
        await handleUnsubscribe();
    } else {
        await handleSubscribe();
    }
}
</script>

<style scoped>
.notification-widget {
    max-width: 500px;
}

/* Grid для кнопок */
.notification-buttons {
    display: grid;
    grid-template-columns: 4fr 1fr;
    gap: 0;
    border-radius: 12px;
    border: 1px #ffffff solid;


}

/* Основная кнопка */
.btn-notif-main {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 18px 20px;
    background-color: transparent;
    color: #ffffff;
    border-radius: 12px 0px 0px 12px;
    border:none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-notif-main:hover:not(:disabled) {
    background-color: rgba(255, 255, 255, 0.1);
}

.btn-notif-main:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-notif-main.is-active {

    background-color: rgba(16, 185, 129, 0.1);
}

.btn-notif-main.is-active:hover:not(:disabled) {
    background-color: rgba(16, 185, 129, 0.2);
}

.btn-notif-main.is-disabled {
    border-color: #f59e0b;
    color: #f59e0b;
}

.btn-text {
    white-space: nowrap;
}

/* Кнопка иконки */
.btn-notif-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: transparent;
    color: #ffffff;
    border-radius: 0px 12px 12px 0px;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    border-left: 1px #ffffff solid;
}

.btn-notif-icon:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

/* Спиннер */
.spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

.spinner-small {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Модалка */
.modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.modal-container {
    background: #ffffff;
    border-radius: 16px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 24px 0;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.title-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 18px;
}

.modal-title h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #111827;
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #e5e7eb;
    color: #111827;
}

.modal-body {
    padding: 24px;
}

.status-card {
    margin-bottom: 20px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 12px;
}

.status-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.status-header i {
    font-size: 18px;
}

.status-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.status-value {
    margin: 0;
    padding-left: 28px;
    font-size: 14px;
    color: #6b7280;
}

.endpoint-box {
    margin-left: 28px;
    padding: 12px;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.endpoint-box code {
    font-size: 12px;
    color: #374151;
    word-break: break-all;
    font-family: 'Courier New', monospace;
}

.warning-card {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    border-radius: 8px;
    margin-top: 20px;
}

.warning-card i {
    color: #f59e0b;
    font-size: 20px;
    flex-shrink: 0;
}

.warning-card strong {
    display: block;
    color: #92400e;
    margin-bottom: 4px;
}

.warning-card p {
    margin: 0;
    font-size: 13px;
    color: #78350f;
}

.modal-footer {
    display: flex;
    gap: 12px;
    padding: 10px 24px 14px;
    justify-content: center;
    position: sticky;
    bottom: 0;
    width: 100%;
    background: white;
}

.btn-secondary,
.btn-primary,
.btn-danger {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4);
}

.btn-danger {
    background: #ef4444;
    color: #ffffff;
}

.btn-danger:hover:not(:disabled) {
    background: #dc2626;
}

.btn-primary:disabled,
.btn-danger:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Анимация модалки */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
    transform: scale(0.95);
}
</style>
