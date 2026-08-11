<template>
    <div class="shop-redirect">
        <!-- Индикатор загрузки во время проверки -->
        <div class="redirect-loader">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="redirect-text">Подготавливаем каталог...</p>
        </div>
    </div>
</template>

<script>
import { useProducts } from '@/MobileClient/composables/useProducts.js';

export default {
    name: "ShopContainer",

    setup() {
        const { selectedPartner } = useProducts();
        return { selectedPartner };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        hasPartners() {
            return this.settings?.partners?.is_active || false;
        },
    },

    mounted() {
        this.$nextTick(() => {
            this.redirectToCorrectPage();
        });
    },

    methods: {
        redirectToCorrectPage() {
            // Если режим партнёров включён — редирект на /partners
            if (this.hasPartners) {
                // Если партнёр уже выбран — сразу в каталог товаров
                if (this.selectedPartner) {
                    this.$router.replace({ name: 'ShopMenu' });
                } else {
                    this.$router.replace({ name: 'Partners' });
                }
                return;
            }

            // Если партнёров нет — сразу в каталог товаров
            this.$router.replace({ name: 'ShopMenu' });
        },
    },
};
</script>

<style scoped>
.shop-redirect {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bs-body-bg);
}

.redirect-loader {
    text-align: center;
}

.redirect-text {
    margin-top: 16px;
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
    font-weight: 500;
}
</style>
