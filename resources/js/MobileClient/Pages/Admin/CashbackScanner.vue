<template>
    <div class="scanner-page">
        <!-- Hero -->
        <div class="scanner-hero">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <h2 class="hero-title">Сканер кэшбэка</h2>
                <p class="hero-subtitle">Отсканируйте QR-код клиента</p>
            </div>
        </div>

        <div class="container px-3">
            <!-- Сканер -->
            <div class="scanner-section">
                <div class="scanner-container" :class="{ 'is-scanning': isScanning }">
                    <div id="qr-reader" class="qr-reader"></div>

                    <div v-if="isScanning" class="scanning-overlay">
                        <div class="scan-line"></div>
                        <div class="scan-frame"></div>
                    </div>
                </div>

                <div class="scanner-actions">
                    <button
                        v-if="!isScanning"
                        @click="startScanner"
                        class="btn-start"
                        :disabled="cameraError"
                    >
                        <i class="fa-solid fa-camera"></i>
                        <span>{{ cameraError ? 'Камера недоступна' : 'Начать сканирование' }}</span>
                    </button>
                    <button
                        v-else
                        @click="stopScanner"
                        class="btn-stop"
                    >
                        <i class="fa-solid fa-stop"></i>
                        <span>Остановить</span>
                    </button>
                </div>
            </div>

            <!-- Результат сканирования -->
            <transition name="slide-up">
                <div v-if="foundUser" class="user-card">
                    <div class="user-avatar">
                        <img v-if="foundUser.avatar" :src="foundUser.avatar" :alt="foundUser.name">
                        <i v-else class="fa-solid fa-user"></i>
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ foundUser.name }}</div>
                        <div class="user-phone">{{ foundUser.phone || 'Телефон не указан' }}</div>
                        <div class="user-balance">
                            <i class="fa-solid fa-coins"></i>
                            <span>{{ foundUser.cashback_balance }} баллов</span>
                        </div>
                    </div>
                    <div v-if="foundUser.is_vip" class="vip-badge">
                        <i class="fa-solid fa-crown"></i>
                        VIP
                    </div>
                </div>
            </transition>

            <!-- Форма операции -->
            <transition name="slide-up">
                <div v-if="foundUser" class="operation-section">
                    <h3 class="section-title">
                        <i class="fa-solid fa-coins"></i>
                        Операция с кэшбэком
                    </h3>

                    <!-- Переключатель типа -->
                    <div class="type-toggle">
                        <button
                            :class="{ active: form.type === 'credit' }"
                            @click="form.type = 'credit'"
                        >
                            <i class="fa-solid fa-plus"></i>
                            Начислить
                        </button>
                        <button
                            :class="{ active: form.type === 'debit' }"
                            @click="form.type = 'debit'"
                        >
                            <i class="fa-solid fa-minus"></i>
                            Списать
                        </button>
                    </div>

                    <!-- Быстрые суммы -->
                    <div class="quick-amounts">
                        <button
                            v-for="amount in [50, 100, 200, 500]"
                            :key="amount"
                            @click="form.amount = amount"
                            :class="{ active: form.amount === amount }"
                        >
                            {{ amount }}
                        </button>
                    </div>

                    <!-- Ввод суммы -->
                    <div class="amount-input-wrapper">
                        <input
                            type="number"
                            v-model.number="form.amount"
                            placeholder="Сумма"
                            min="1"
                            :max="form.type === 'debit' ? foundUser.cashback_balance : 10000"
                        >
                        <span class="currency">баллов</span>
                    </div>

                    <!-- Описание -->
                    <div class="description-input">
                        <input
                            type="text"
                            v-model="form.description"
                            :placeholder="form.type === 'credit' ? 'Причина начисления' : 'Причина списания'"
                        >
                    </div>

                    <!-- Кнопка подтверждения -->
                    <button
                        @click="confirmOperation"
                        class="btn-confirm"
                        :class="form.type"
                        :disabled="!form.amount || form.amount <= 0 || isProcessing"
                    >
                        <span v-if="isProcessing" class="spinner"></span>
                        <i v-else :class="form.type === 'credit' ? 'fa-solid fa-plus' : 'fa-solid fa-minus'"></i>
                        <span>{{ form.type === 'credit' ? 'Начислить' : 'Списать' }} {{ form.amount }} баллов</span>
                    </button>
                </div>
            </transition>

            <!-- История операций -->
            <div v-if="recentOperations.length > 0" class="history-section">
                <h3 class="section-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Последние операции
                </h3>
                <div class="operations-list">
                    <div
                        v-for="op in recentOperations"
                        :key="op.id"
                        class="operation-item"
                        :class="op.type"
                    >
                        <div class="op-icon">
                            <i :class="op.type === 'credit' ? 'fa-solid fa-plus' : 'fa-solid fa-minus'"></i>
                        </div>
                        <div class="op-info">
                            <div class="op-name">{{ op.userName }}</div>
                            <div class="op-time">{{ op.time }}</div>
                        </div>
                        <div class="op-amount">
                            {{ op.type === 'credit' ? '+' : '-' }}{{ op.amount }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Кнопка назад -->
        <button class="btn-back" @click="$router.back()">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
    </div>
</template>

<script>
import { Html5Qrcode } from 'html5-qrcode';
import { usePermissions } from '@/MobileClient/Composables/usePermissions.js';

export default {
    name: 'CashbackScanner',

    setup() {
        const { isAdmin } = usePermissions();
        return { isAdmin };
    },

    data() {
        return {
            isScanning: false,
            scanner: null,
            cameraError: false,
            foundUser: null,
            isProcessing: false,
            form: {
                type: 'credit',
                amount: null,
                description: '',
            },
            recentOperations: [],
        };
    },

    mounted() {
        if (!this.isAdmin) {
            this.$router.replace({ name: 'CashBack' });
            return;
        }

        // Загружаем историю из localStorage
        const saved = localStorage.getItem('cashback_scanner_history');
        if (saved) {
            this.recentOperations = JSON.parse(saved);
        }
    },

    beforeUnmount() {
        this.stopScanner();
    },

    methods: {
        async startScanner() {
            this.scanner = new Html5Qrcode("qr-reader");

            try {
                await this.scanner.start(
                    { facingMode: "environment" },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        aspectRatio: 1.0,
                    },
                    async (decodedText) => {
                        await this.handleQRCode(decodedText);
                    },
                    (errorMessage) => {
                        // Игнорируем ошибки сканирования
                    }
                );

                this.isScanning = true;
            } catch (error) {
                console.error('Camera error:', error);
                this.cameraError = true;
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось получить доступ к камере',
                    type: 'error',
                });
            }
        },

        async stopScanner() {
            if (this.scanner && this.isScanning) {
                try {
                    await this.scanner.stop();
                    this.scanner.clear();
                } catch (error) {
                    console.error('Stop error:', error);
                }
            }
            this.isScanning = false;
        },

        async handleQRCode(decodedText) {
            // Останавливаем сканер после успешного сканирования
            await this.stopScanner();

            try {
                // Пытаемся распарсить JSON
                let data;
                try {
                    data = JSON.parse(decodedText);
                } catch {
                    // Если не JSON, считаем что это просто referral_code
                    data = { referral_code: decodedText };
                }

                const referralCode = data.referral_code || data.code || decodedText;

                // Ищем пользователя
                const response = await axios.post('/users/find-by-referral', {
                    referral_code: referralCode,
                });

                if (response.data.success) {
                    this.foundUser = response.data.data;
                    this.form = { type: 'credit', amount: null, description: '' };

                    this.$notify?.({
                        title: 'Пользователь найден',
                        text: `${this.foundUser.name} • ${this.foundUser.cashback_balance} баллов`,
                        type: 'success',
                    });
                }
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Пользователь не найден',
                    type: 'error',
                });
                this.foundUser = null;
            }
        },

        async confirmOperation() {
            if (!this.form.amount || this.form.amount <= 0) return;

            // Проверка для списания
            if (this.form.type === 'debit' && this.form.amount > this.foundUser.cashback_balance) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: `Недостаточно баллов. Доступно: ${this.foundUser.cashback_balance}`,
                    type: 'error',
                });
                return;
            }

            this.isProcessing = true;

            try {
                const response = await axios.post('/users/manage-by-referral', {
                    referral_code: this.foundUser.referral_code,
                    amount: this.form.amount,
                    type: this.form.type,
                    description: this.form.description || (this.form.type === 'credit' ? 'Начисление через QR' : 'Списание через QR'),
                });

                if (response.data.success) {
                    // Обновляем баланс пользователя
                    this.foundUser.cashback_balance = response.data.data.user.cashback_balance;

                    // Добавляем в историю
                    const operation = {
                        id: Date.now(),
                        type: this.form.type,
                        amount: this.form.amount,
                        userName: this.foundUser.name,
                        userId: this.foundUser.id,
                        time: new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }),
                    };

                    this.recentOperations.unshift(operation);
                    if (this.recentOperations.length > 10) {
                        this.recentOperations = this.recentOperations.slice(0, 10);
                    }

                    // Сохраняем в localStorage
                    localStorage.setItem('cashback_scanner_history', JSON.stringify(this.recentOperations));

                    this.$notify?.({
                        title: 'Успешно',
                        text: response.data.message,
                        type: 'success',
                    });

                    // Сбрасываем форму
                    this.form = { type: 'credit', amount: null, description: '' };
                    this.foundUser = null;
                }
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось выполнить операцию',
                    type: 'error',
                });
            } finally {
                this.isProcessing = false;
            }
        },
    },
};
</script>

<style scoped>
.scanner-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 100px;
}

.scanner-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 8px;
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0;
}

.scanner-section {
    margin-top: 24px;
}

.scanner-container {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    background: #000;
    aspect-ratio: 1;
    max-width: 400px;
    margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.qr-reader {
    width: 100%;
    height: 100%;
}

.scanning-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.scan-frame {
    width: 250px;
    height: 250px;
    border: 3px solid rgba(255, 255, 255, 0.8);
    border-radius: 16px;
    position: relative;
}

.scan-frame::before,
.scan-frame::after {
    content: '';
    position: absolute;
    width: 40px;
    height: 40px;
    border: 4px solid var(--bs-primary);
}

.scan-frame::before {
    top: -4px;
    left: -4px;
    border-right: none;
    border-bottom: none;
    border-radius: 16px 0 0 0;
}

.scan-frame::after {
    bottom: -4px;
    right: -4px;
    border-left: none;
    border-top: none;
    border-radius: 0 0 16px 0;
}

.scan-line {
    position: absolute;
    width: 250px;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--bs-primary), transparent);
    animation: scanLine 2s ease-in-out infinite;
    box-shadow: 0 0 20px var(--bs-primary);
}

@keyframes scanLine {
    0%, 100% { transform: translateY(-125px); }
    50% { transform: translateY(125px); }
}

.scanner-actions {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.btn-start,
.btn-stop {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    border: none;
    border-radius: 16px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.btn-start {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
}

.btn-start:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.btn-start:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-stop {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.btn-stop:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
}

/* Карточка пользователя */
.user-card {
    margin-top: 24px;
    padding: 20px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.user-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.user-phone {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    margin-bottom: 6px;
}

.user-balance {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: var(--bs-primary);
    font-weight: 600;
}

.vip-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

/* Форма операции */
.operation-section {
    margin-top: 24px;
    padding: 24px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--bs-body-color);
    margin: 0 0 20px;
}

.section-title i {
    color: var(--bs-primary);
}

.type-toggle {
    display: flex;
    gap: 8px;
    background: var(--bs-tertiary-bg, #f3f4f6);
    padding: 4px;
    border-radius: 14px;
    margin-bottom: 20px;
}

.type-toggle button {
    flex: 1;
    padding: 12px;
    border: none;
    background: transparent;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-secondary-color);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.type-toggle button.active {
    background: var(--bs-body-bg);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.type-toggle button:first-child.active {
    color: #10b981;
}

.type-toggle button:last-child.active {
    color: #ef4444;
}

.quick-amounts {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 16px;
}

.quick-amounts button {
    padding: 12px 8px;
    background: var(--bs-tertiary-bg, #f3f4f6);
    border: 2px solid transparent;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    cursor: pointer;
    transition: all 0.2s;
}

.quick-amounts button:hover {
    border-color: var(--bs-primary);
}

.quick-amounts button.active {
    background: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.amount-input-wrapper {
    position: relative;
    margin-bottom: 12px;
}

.amount-input-wrapper input {
    width: 100%;
    padding: 16px 80px 16px 20px;
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    font-size: 1.2rem;
    font-weight: 700;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
}

.amount-input-wrapper input:focus {
    outline: none;
    border-color: var(--bs-primary);
}

.currency {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
    font-weight: 600;
}

.description-input input {
    width: 100%;
    padding: 14px 20px;
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    font-size: 0.95rem;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    margin-bottom: 20px;
}

.description-input input:focus {
    outline: none;
    border-color: var(--bs-primary);
}

.btn-confirm {
    width: 100%;
    padding: 18px;
    border: none;
    border-radius: 16px;
    font-weight: 700;
    font-size: 1.05rem;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.btn-confirm.credit {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.btn-confirm.debit {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.btn-confirm:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.btn-confirm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* История операций */
.history-section {
    margin-top: 32px;
}

.operations-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.operation-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    transition: all 0.2s;
}

.operation-item:hover {
    border-color: var(--bs-primary);
    transform: translateX(4px);
}

.op-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;
}

.operation-item.credit .op-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.operation-item.debit .op-icon {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.op-info {
    flex: 1;
    min-width: 0;
}

.op-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.op-time {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.op-amount {
    font-weight: 700;
    font-size: 1.1rem;
    white-space: nowrap;
}

.operation-item.credit .op-amount {
    color: #10b981;
}

.operation-item.debit .op-amount {
    color: #ef4444;
}

/* Кнопка назад */
.btn-back {
    position: fixed;
    top: 20px;
    left: 20px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: var(--bs-body-color);
    cursor: pointer;
    transition: all 0.2s;
    z-index: 100;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-back:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

/* Анимации */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

@media (max-width: 576px) {
    .scanner-container {
        max-width: 100%;
    }

    .quick-amounts {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
