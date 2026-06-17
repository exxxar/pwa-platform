<template>
    <!-- QR-код -->
    <div class="qr-section mt-4">
        <h6 class="section-title">
            <i class="fa-solid fa-qrcode me-2"></i>
            Ваш QR-код
        </h6>
        <div class="qr-wrapper">
            <img :src="qr" class="qr-image" alt="QR-код профиля">
        </div>
    </div>

    <!-- Реферальная ссылка -->
    <button
        type="button"
        @click="copyToClipboard"
        class="btn-referral my-2"
    >
        <i class="fa-solid fa-link me-2"></i>
        Реферальная ссылка
    </button>
</template>
<script>
export default {
    computed: {
        self() {
            return window.TenantUser || null;
        },

        tenant() {
            return window.Tenant || null;
        },

        qr() {
            return `https://api.qrserver.com/v1/create-qr-code/?size=450x450&qzone=2&data=${encodeURIComponent(this.link)}`;
        },

        link() {
            if (!this.self?.telegram_chat_id || !this.tenant?.slug) {
                return 'https://t.me/';
            }
            return `https://t.me/${this.tenant.slug}?start=${btoa('001' + this.self.telegram_chat_id)}`;
        },
    },
    methods:{
        async copyToClipboard() {
            try {
                await navigator.clipboard.writeText(this.link);
                this.$notify?.({
                    title: "Реферальная ссылка",
                    text: "Ссылка успешно скопирована!",
                    type: "success",
                });
            } catch (error) {
                console.error('Ошибка копирования:', error);
                this.$notify?.({
                    title: "Ошибка",
                    text: "Не удалось скопировать ссылку",
                    type: "error",
                });
            }
        },

    }
}
</script>
<style scoped>
.qr-section {
    text-align: center;
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
}

.qr-image {
    width: 200px;
    height: 200px;
    display: block;
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

.btn-referral:hover {
    background: var(--bs-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3);
}

.btn-referral:active {
    transform: translateY(0);
}

</style>
