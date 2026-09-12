<template>
    <div class="delivery-calculator-page">
        <!-- Шапка -->
        <div class="page-header">
            <button class="back-btn" @click="$router.back()">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="header-text">
                <h2>Калькулятор доставки</h2>
                <p>Нажмите на заведение для расчета</p>
            </div>
        </div>

        <!-- Состояние загрузки -->
        <div v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p>Загрузка списка заведений...</p>
        </div>

        <!-- Список заведений (стиль PartnerCard) -->
        <div v-else class="shops-list">
            <div
                v-for="shop in shops"
                :key="shop.id"
                class="partner-card"
                @click="openCalculator(shop)"
            >
                <!-- Изображение -->
                <div class="card-image-wrapper">
                    <img
                        v-lazy="shop.image || '/images/partner-placeholder.png'"
                        :alt="shop.name"
                        class="card-image"
                    >
                    <button class="calc-hint-btn" title="Рассчитать доставку">
                        <i class="fa-solid fa-calculator"></i>
                    </button>
                </div>

                <!-- Контент -->
                <div class="card-content">
                    <div class="card-header">
                        <h6 class="card-title">{{ shop.name }}</h6>
                    </div>
                    <p v-if="shop.description" class="card-description">
                        {{ shop.description }}
                    </p>
                    <div class="card-meta">
                        <div v-if="shop.address" class="meta-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>{{ shortAddress(shop.address) }}</span>
                        </div>
                    </div>
                    <div class="card-action">
                        <span>Рассчитать доставку</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-if="shops.length === 0" class="empty-state">
                <i class="fa-solid fa-store-slash"></i>
                <p>Нет доступных заведений для расчета</p>
            </div>
        </div>

        <!-- 🆕 Подключаем модалку -->
        <DeliveryCalculatorModal
            :is-visible="showModal"
            :shop="currentShop"
            @close="showModal = false"
        />
    </div>
</template>

<script>
import axios from 'axios';
import DeliveryCalculatorModal from '@/MobileClient/Components/Delivery/DeliveryCalculatorModal.vue';

export default {
    name: 'DeliveryCalculatorPage',
    components: {
        DeliveryCalculatorModal
    },
    data() {
        return {
            shops: [],
            isLoading: true,
            showModal: false,
            currentShop: null
        };
    },
    mounted() {
        this.fetchShops();
    },
    methods: {
        async fetchShops() {
            this.isLoading = true;
            try {
                // Используем тот же эндпоинт, что и на странице настроек
                const response = await axios.get('/deliveryman/shops');
                if (response.data.success) {
                    this.shops = response.data.data.shops;
                }
            } catch (error) {
                console.error('Ошибка загрузки магазинов:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить список', type: 'error' });
            } finally {
                this.isLoading = false;
            }
        },

        openCalculator(shop) {
            this.currentShop = shop;
            this.showModal = true;
        },

        shortAddress(addr) {
            if (!addr) return '';
            return addr.length > 40 ? addr.slice(0, 40) + '...' : addr;
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.delivery-calculator-page {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 40px;
}

.page-header {
    display: flex; align-items: center; gap: 12px; padding: 16px 20px;
    background: $card-bg; border-bottom: 1px solid $border; position: sticky; top: 0; z-index: 10;
    .back-btn {
        width: 40px; height: 40px; border-radius: 50%; background: $bg; border: 1px solid $border;
        color: $text; cursor: pointer; display: flex; align-items: center; justify-content: center;
        &:hover { background: $border; }
    }
    .header-text { flex: 1; h2 { margin: 0; font-size: 1.15rem; font-weight: 700; color: $text; } p { margin: 2px 0 0; font-size: 0.8rem; color: $text-muted; } }
}

.loading-state, .empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 60px 20px; color: $text-muted; text-align: center;
    .spinner { width: 32px; height: 32px; border: 3px solid $border; border-top-color: $primary; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 16px; }
    i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
}
@keyframes spin { to { transform: rotate(360deg); } }

.shops-list { padding: 16px; display: flex; flex-direction: column; gap: 12px; }

// ==========================================
// СТИЛИ КАРТОЧКИ (PartnerCard)
// ==========================================
.partner-card {
    display: flex; background: $card-bg; border: 1px solid $border; border-radius: 16px;
    overflow: hidden; cursor: pointer; transition: all 0.3s ease;
    &:hover { border-color: rgba($primary, 0.3); transform: translateY(-2px); box-shadow: 0 8px 24px rgba($primary, 0.12); }
}

.card-image-wrapper {
    position: relative; width: 120px; min-height: 140px; flex-shrink: 0; overflow: hidden; background: $bg;
}
.card-image { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
.partner-card:hover .card-image { transform: scale(1.05); }

.calc-hint-btn {
    position: absolute; top: 8px; right: 8px; width: 32px; height: 32px; border-radius: 50%;
    background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(4px); border: none;
    color: $primary; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.2s;
    &:hover { background: $primary; color: white; transform: scale(1.1); }
}

.card-content { flex: 1; padding: 14px; display: flex; flex-direction: column; min-width: 0; }
.card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
.card-title {
    margin: 0; font-weight: 700; font-size: 1rem; color: $text; line-height: 1.2;
    flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.card-description {
    margin: 0 0 10px 0; font-size: 0.8rem; color: $text-muted; line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.card-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
.meta-item { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; color: $text-muted; i { color: $primary; font-size: 0.7rem; } }

.card-action {
    display: flex; align-items: center; justify-content: space-between; margin-top: auto;
    padding-top: 10px; border-top: 1px solid $border; font-size: 0.8rem; font-weight: 600; color: $primary;
    i { font-size: 0.7rem; transition: transform 0.2s ease; }
}
.partner-card:hover .card-action i { transform: translateX(4px); }

@media (max-width: 576px) {
    .card-image-wrapper { width: 110px; min-height: 120px; }
    .card-title { font-size: 0.9rem; }
    .card-description { font-size: 0.75rem; -webkit-line-clamp: 1; }
}
</style>
