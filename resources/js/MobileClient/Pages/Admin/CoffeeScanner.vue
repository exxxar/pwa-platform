<template>
    <div class="coffee-scanner">
        <div class="scanner-header">
            <h2>Сканер кофейных карт</h2>
            <p class="subtitle">Отсканируйте QR-код клиента</p>
        </div>

        <div class="scanner-container">
            <div id="qr-reader" class="qr-reader"></div>

            <div v-if="scanning" class="scanning-indicator">
                <div class="pulse"></div>
                <span>Сканирование...</span>
            </div>
        </div>

        <div v-if="lastResult" class="result-card" :class="lastResult.success ? 'success' : 'error'">
            <div class="result-icon">
                <i :class="lastResult.success ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
            </div>
            <div class="result-content">
                <h5>{{ lastResult.title }}</h5>
                <p>{{ lastResult.message }}</p>
                <div v-if="lastResult.coffee" class="coffee-info">
                    <span>Прогресс: {{ lastResult.coffee.count }}/{{ maxCups }}</span>
                    <span v-if="lastResult.coffee.total_exchanged">
                        | Всего выдано: {{ lastResult.coffee.total_exchanged }}
                    </span>
                </div>
            </div>
        </div>

        <div class="actions">
            <button @click="startScanning" class="btn-primary" :disabled="scanning">
                <i class="fa-solid fa-camera"></i>
                {{ scanning ? 'Сканирование...' : 'Начать сканирование' }}
            </button>
            <button @click="stopScanning" class="btn-secondary" :disabled="!scanning">
                <i class="fa-solid fa-stop"></i>
                Остановить
            </button>
        </div>

        <div class="recent-scans">
            <h4>Последние сканирования</h4>
            <div v-if="recentScans.length === 0" class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <p>Пока нет сканирований</p>
            </div>
            <div v-else class="scans-list">
                <div v-for="scan in recentScans" :key="scan.id" class="scan-item">
                    <div class="scan-icon" :class="scan.success ? 'success' : 'error'">
                        <i :class="scan.success ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
                    </div>
                    <div class="scan-info">
                        <div class="scan-user">{{ scan.userName }}</div>
                        <div class="scan-action">{{ scan.action }}</div>
                    </div>
                    <div class="scan-time">{{ scan.time }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Html5Qrcode } from 'html5-qrcode';

export default {
    name: 'CoffeeScanner',

    data() {
        return {
            scanning: false,
            html5QrCode: null,
            lastResult: null,
            recentScans: [],
            maxCups: 6,
        };
    },

    async mounted() {
        // Загружаем настройки
        try {
            const response = await axios.get('/api/tenant/settings');
            this.maxCups = response.data?.settings?.coffee?.max || 6;
        } catch (error) {
            console.error('Failed to load settings:', error);
        }
    },

    beforeUnmount() {
        this.stopScanning();
    },

    methods: {
        async startScanning() {
            this.scanning = true;
            this.lastResult = null;

            this.html5QrCode = new Html5Qrcode("qr-reader");

            try {
                await this.html5QrCode.start(
                    { facingMode: "environment" },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    async (decodedText) => {
                        await this.handleQRCode(decodedText);
                    },
                    (errorMessage) => {
                        // Ignore scan errors
                    }
                );
            } catch (error) {
                console.error('Scanner error:', error);
                this.scanning = false;
                this.$notify?.({
                    title: "Ошибка",
                    text: "Не удалось запустить камеру",
                    type: "error",
                });
            }
        },

        async stopScanning() {
            if (this.html5QrCode && this.scanning) {
                try {
                    await this.html5QrCode.stop();
                    this.html5QrCode.clear();
                } catch (error) {
                    console.error('Stop error:', error);
                }
            }
            this.scanning = false;
        },

        async handleQRCode(decodedText) {
            try {
                const data = JSON.parse(decodedText);

                if (!data.action || !data.user_id || !data.timestamp) {
                    throw new Error('Invalid QR format');
                }

                // Проверяем время (5 минут)
                const now = Math.floor(Date.now() / 1000);
                if (Math.abs(now - data.timestamp) > 300) {
                    throw new Error('QR-код истёк');
                }

                let endpoint, result;

                if (data.action === 'mark') {
                    endpoint = '/api/coffee/mark';
                    result = await axios.post(endpoint, {
                        user_id: data.user_id,
                        cup_index: data.cup_index,
                        timestamp: data.timestamp,
                    });
                } else if (data.action === 'exchange') {
                    endpoint = '/api/coffee/exchange';
                    result = await axios.post(endpoint, {
                        user_id: data.user_id,
                        timestamp: data.timestamp,
                    });
                } else {
                    throw new Error('Unknown action');
                }

                // Загружаем информацию о пользователе
                const userResponse = await axios.get(`/api/admin/users/${data.user_id}`);
                const userName = userResponse.data?.name || `User #${data.user_id}`;

                this.lastResult = {
                    success: true,
                    title: data.action === 'mark' ? 'Чашка отмечена!' : 'Кофе выдан!',
                    message: result.data.message,
                    coffee: result.data.coffee,
                };

                this.recentScans.unshift({
                    id: Date.now(),
                    userName,
                    action: data.action === 'mark' ? 'Отметка чашки' : 'Выдача кофе',
                    success: true,
                    time: new Date().toLocaleTimeString(),
                });

                // Ограничиваем историю
                if (this.recentScans.length > 10) {
                    this.recentScans = this.recentScans.slice(0, 10);
                }

                this.$notify?.({
                    title: "Успех",
                    text: result.data.message,
                    type: "success",
                });

            } catch (error) {
                this.lastResult = {
                    success: false,
                    title: 'Ошибка',
                    message: error.response?.data?.error || error.message,
                };

                this.$notify?.({
                    title: "Ошибка",
                    text: error.response?.data?.error || error.message,
                    type: "error",
                });
            }
        },
    },
};
</script>

<style scoped>
.coffee-scanner {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.scanner-header {
    text-align: center;
    margin-bottom: 30px;
}

.scanner-header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--bs-body-color);
    margin-bottom: 8px;
}

.subtitle {
    color: var(--bs-secondary-color);
    font-size: 0.95rem;
}

.scanner-container {
    position: relative;
    margin-bottom: 24px;
    border-radius: 16px;
    overflow: hidden;
    background: var(--bs-secondary-bg);
}

.qr-reader {
    width: 100%;
    min-height: 300px;
}

.scanning-indicator {
    position: absolute;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 20px;
    font-size: 0.9rem;
}

.pulse {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #198754;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}

.result-card {
    display: flex;
    gap: 16px;
    padding: 20px;
    border-radius: 14px;
    margin-bottom: 24px;
    animation: slideIn 0.3s ease-out;
}

.result-card.success {
    background: rgba(25, 135, 84, 0.1);
    border: 1px solid rgba(25, 135, 84, 0.3);
}

.result-card.error {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.result-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.result-card.success .result-icon {
    background: #198754;
    color: white;
}

.result-card.error .result-icon {
    background: #dc3545;
    color: white;
}

.result-content h5 {
    margin: 0 0 4px 0;
    font-weight: 700;
}

.result-content p {
    margin: 0 0 8px 0;
    color: var(--bs-secondary-color);
}

.coffee-info {
    display: flex;
    gap: 12px;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.actions {
    display: flex;
    gap: 12px;
    margin-bottom: 32px;
    flex-direction: column;
}

.btn-primary,
.btn-secondary {
    flex: 1;
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(111, 78, 55, 0.3);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(111, 78, 55, 0.4);
}

.btn-secondary {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
}

.btn-secondary:hover:not(:disabled) {
    background: var(--bs-tertiary-bg);
}

.btn-primary:disabled,
.btn-secondary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.recent-scans {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 20px;
}

.recent-scans h4 {
    margin: 0 0 16px 0;
    font-size: 1.1rem;
    font-weight: 700;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--bs-secondary-color);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 12px;
    opacity: 0.3;
}

.scans-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.scan-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
}

.scan-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.scan-icon.success {
    background: rgba(25, 135, 84, 0.15);
    color: #198754;
}

.scan-icon.error {
    background: rgba(220, 53, 69, 0.15);
    color: #dc3545;
}

.scan-info {
    flex: 1;
    min-width: 0;
}

.scan-user {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 2px;
}

.scan-action {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

.scan-time {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    flex-shrink: 0;
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
</style>
