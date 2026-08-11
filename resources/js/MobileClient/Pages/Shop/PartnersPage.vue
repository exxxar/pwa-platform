<template>
    <div class="partners-page">
        <PartnersList @select="onPartnerSelect" />
    </div>
</template>

<script>
import PartnersList from "@/MobileClient/Components/Partners/PartnerList.vue";
import { useProducts } from '@/MobileClient/composables/useProducts.js';

export default {
    name: 'PartnersPage',

    components: { PartnersList },

    setup() {
        const { setPartner } = useProducts();
        return { setPartner };
    },

    methods: {
        onPartnerSelect(partner) {
            // Сохраняем выбранного партнёра в store
            this.setPartner(partner);
            // Переходим в каталог товаров этого партнёра
            this.$router.push({ name: 'Catalog' });
        },
    },
};
</script>

<style scoped>
.partners-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}
</style>
