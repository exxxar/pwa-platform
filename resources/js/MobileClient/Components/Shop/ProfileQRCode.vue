<template>
    <div class="qr-section mt-4" v-if="displayLink">
        <h6 class="section-title">
            <i class="fa-solid fa-qrcode me-2"></i>
            Ваш QR-код для приглашения
        </h6>

        <div class="qr-wrapper">
            <img :src="qrCodeUrl" class="qr-image" alt="QR-код реферальной ссылки">
        </div>

        <p class="text-muted small mt-3 mb-2 text-center">
            Отсканируйте код или поделитесь ссылкой ниже
        </p>

        <button
            type="button"
            @click="copyToClipboard"
            class="btn-referral mb-2"
            :disabled="isCopying"
        >
            <i :class="isCopying ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-link'" class="me-2"></i>
            {{ isCopying ? 'Копируем...' : 'Скопировать реферальную ссылку' }}
        </button>
    </div>
</template>

<script>
export default {
    name: "ProfileQRCode",
    props: {
        // Родительский компонент (ProfilePage) передаст сюда готовую ссылку с бэкенда
        referralLink: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            isCopying: false
        };
    },
    computed: {
        self() {
            return window.TenantUser || null;
        },

        // Если ссылка не передана пропом, пытаемся составить её из referral_code пользователя
        displayLink() {
            if (this.referralLink) {
                return this.referralLink;
            }
            // Фоллбэк, если вдруг проп не передан, но код есть в объекте пользователя
            if (this.self?.referral_code) {
                return `${window.location.origin}/pwa?ref=${this.self.referral_code}`;
            }
            return null;
        },

        qrCodeUrl() {
            if (!this.displayLink) return '';
            // Генерируем QR-код. Добавлены bgcolor и color для гарантии читаемости
            return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&qzone=1&bgcolor=ffffff&color=000000&data=${encodeURIComponent(this.displayLink)}`;
        }
    },
    methods: {
        async copyToClipboard() {
            if (!this.displayLink) return;

            this.isCopying = true;
            try {
                await navigator.clipboard.writeText(this.displayLink);
                // Сообщаем родителю об успехе, чтобы он показал уведомление
                this.$emit('copied');
            } catch (error) {
                console.error('Ошибка копирования:', error);
                // Фоллбэк для старых браузеров или WebView
                const textArea = document.createElement("textarea");
                textArea.value = this.displayLink;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand("copy");
                document.body.removeChild(textArea);
                this.$emit('copied');
            } finally {
                setTimeout(() => {
                    this.isCopying = false;
                }, 1000);
            }
        }
    }
};
</script>

<style scoped>
.qr-section {
    text-align: center;
    animation: fadeIn 0.5s ease;
}

.section-title {
    color: var(--bs-body-color);
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-title i {
    color: var(--bs-primary);
}

.qr-wrapper {
    display: inline-block;
    padding: 16px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.qr-wrapper:hover {
    transform: scale(1.02);
}

.qr-image {
    width: 200px;
    height: 200px;
    display: block;
    border-radius: 8px;
}

/* Кнопка реферальной ссылки */
.btn-referral {
    width: 100%;
    padding: 14px 20px;
    background: transparent;
    border: 2px solid var(--bs-primary);
    border-radius: 12px;
    color: var(--bs-primary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-referral:hover:not(:disabled) {
    background: var(--bs-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3);
}

.btn-referral:active:not(:disabled) {
    transform: translateY(0);
}

.btn-referral:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
