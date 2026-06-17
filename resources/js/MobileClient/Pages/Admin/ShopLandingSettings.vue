<template>
    <ShopSettings
        :initial-config="shopConfig"
        @save="handleSave"
        @reset="handleReset"
    />
</template>

<script>
import ShopSettings from '@/MobileClient/Components/Admin/ShopLanding/ShopSettings.vue';

export default {
    components: { ShopSettings },
    data() {
        return {
            shopConfig: { /* начальная конфигурация */ }
        };
    },
    methods: {
        async handleSave(updatedConfig) {
            // Сохранение в БД
            await this.$store.dispatch('saveShopSettings', updatedConfig);
            this.shopConfig = updatedConfig;
        },
        handleReset() {
            // Возврат к дефолтным настройкам
        }
    }
}
</script>
