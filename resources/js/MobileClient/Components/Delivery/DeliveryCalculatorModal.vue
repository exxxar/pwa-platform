<template>
    <transition name="modal-fade">
        <div v-if="isVisible" class="modal-overlay" @click.self="close">
            <div class="modal-container">
                <!-- Шапка -->
                <div class="modal-header">
                    <div class="header-shop-info">
                        <div class="shop-avatar-small">
                            <img v-if="shop?.image" :src="shop.image" :alt="shop.name">
                            <i v-else class="fa-solid fa-store"></i>
                        </div>
                        <div class="shop-header-text">
                            <h3>{{ shop?.name || shop?.title || 'Заведение' }}</h3>
                            <div class="shop-address-badge" :title="shop?.address">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>{{ shop?.address || 'Адрес не указан' }}</span>
                            </div>
                        </div>
                    </div>
                    <button class="modal-close" @click="close"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <!-- 🆕 Блок поиска адреса -->
                    <div class="address-search-block">
                        <label class="form-label">
                            <i class="fa-solid fa-magnifying-glass-location"></i> Поиск адреса доставки
                        </label>
                        <div class="search-input-group">
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="searchAddress"
                                class="form-input"
                                placeholder="Город, улица, дом..."
                                :disabled="isSearching"
                            >
                            <button
                                type="button"
                                class="btn-search"
                                @click="searchAddress"
                                :disabled="isSearching || !searchQuery.trim()"
                            >
                                <span v-if="isSearching" class="spinner-small"></span>
                                <i v-else class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>

                        <!-- 🆕 Чипы ближайших городов (как в вашем примере) -->
                        <div v-if="nearestCitiesList.length > 0" class="city-chips">
                            <button
                                v-for="city in nearestCitiesList"
                                :key="city"
                                type="button"
                                class="city-chip"
                                :class="{ 'active': selectedCity === city }"
                                @click="selectCityForSearch(city)"
                            >
                                {{ city }}
                            </button>
                        </div>
                    </div>

                    <!-- Карта -->
                    <div class="map-selection-block">
                        <label class="form-label">
                            <i class="fa-solid fa-map-location-dot"></i> Точка доставки
                        </label>


                        <MapLocationPicker
                            :shop-coords="shop?.shop_coords || shop?.settings?.shop_coords"
                            :map-key="mapTilerKey"
                            :external-location="externalLocation"
                            @update:location="handleMapLocationUpdate"
                        />

                        <div v-if="selectedAddress" class="selected-address-display">
                            <i class="fa-solid fa-check-circle text-success"></i>
                            <span>{{ selectedAddress }}</span>
                        </div>
                    </div>

                    <!-- Кнопка расчета -->
                    <button
                        class="btn-calculate"
                        @click="calculateDelivery"
                        :disabled="!canCalculate || isCalculating"
                    >
                        <i v-if="isCalculating" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-calculator"></i>
                        <span>{{ isCalculating ? 'Считаем...' : 'Рассчитать стоимость' }}</span>
                    </button>

                    <!-- Результаты расчета -->
                    <transition name="slide-up">
                        <div v-if="calcResult" class="calculation-result">
                            <div class="result-grid">
                                <div class="result-item">
                                    <span class="label">Расстояние</span>
                                    <span class="value"><i class="fa-solid fa-route"></i> {{ calcResult.distance_km }} км</span>
                                </div>
                                <div class="result-item highlight">
                                    <span class="label">Стоимость доставки</span>
                                    <span class="value price">{{ formatPrice(calcResult.delivery_price) }}</span>
                                </div>
                            </div>

                            <div class="breakdown-card" v-if="calcResult.breakdown">
                                <div class="breakdown-row">
                                    <span>Зона:</span>
                                    <span :class="{ 'text-warning': calcResult.is_outside_zones }">
                                        {{ calcResult.breakdown.zone_name }}
                                    </span>
                                </div>
                                <div class="breakdown-row">
                                    <span>Тариф за км:</span>
                                    <span>{{ calcResult.breakdown.price_per_km }} ₽</span>
                                </div>
                                <div class="breakdown-row">
                                    <span>Стоимость км:</span>
                                    <span>{{ calcResult.breakdown.distance_cost }} ₽</span>
                                </div>
                            </div>

                            <!-- Генерация ссылки -->
                            <div class="payment-action-block">
                                <button
                                    v-if="!paymentUrl"
                                    class="btn-generate-link"
                                    @click="generatePaymentLink"
                                    :disabled="isGeneratingLink"
                                >
                                    <i v-if="isGeneratingLink" class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-credit-card"></i>
                                    <span>Сгенерировать ссылку на оплату</span>
                                </button>

                                <div v-else class="payment-success">
                                    <div class="qr-wrapper">
                                        <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(paymentUrl)}`" alt="QR Code">
                                    </div>
                                    <div class="link-box">
                                        <input type="text" :value="paymentUrl" readonly>
                                        <button class="btn-copy" @click="copyLink" :class="{ copied: isCopied }">
                                            <i :class="isCopied ? 'fa-solid fa-check' : 'fa-regular fa-copy'"></i>
                                        </button>
                                    </div>
                                    <button class="btn-reset" @click="resetPayment">
                                        <i class="fa-solid fa-rotate-left"></i> Рассчитать другой адрес
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import axios from 'axios';
import MapLocationPicker from '@/MobileClient/Components/Delivery/MapLocationPicker.vue';

export default {
    name: 'DeliveryCalculatorModal',
    components: {
        MapLocationPicker
    },
    props: {
        isVisible: { type: Boolean, default: false },
        shop: { type: Object, required: true }
    },
    emits: ['close'],
    data() {
        return {
            // 🆕 Поиск адреса
            searchQuery: '',
            selectedCity: '',
            isSearching: false,
            // Координаты и адрес
            selectedAddress: '',
            lat: null,
            lng: null,
            externalLocation: null, // 🆕 Для синхронизации с картой
            // Расчет и оплата
            isCalculating: false,
            calcResult: null,
            isGeneratingLink: false,
            paymentUrl: null,
            isCopied: false,
            mapTilerKey: window.Tenant?.settings?.map_tiler || 'l7t0HU7CqsgOKgS9rtvU'
        };
    },
    computed: {
        canCalculate() {
            return this.lat && this.lng && this.selectedAddress.trim().length > 5;
        },
        // 🆕 Список ближайших городов из настроек магазина
        nearestCitiesList() {
            const rawCities = this.shop?.settings?.nearest_cities
                || window.Tenant?.settings?.nearest_cities
                || '';
            if (!rawCities) return [];
            return rawCities.split(/[,\n]+/).map(c => c.trim()).filter(c => c.length > 0);
        }
    },
    watch: {
        isVisible(newVal) {
            if (newVal) {
                this.resetState();
            }
        }
    },
    methods: {
        close() {
            this.$emit('close');
        },
        resetState() {
            this.searchQuery = '';
            this.selectedCity = '';
            this.selectedAddress = '';
            this.lat = null;
            this.lng = null;
            this.externalLocation = null;
            this.calcResult = null;
            this.paymentUrl = null;
            this.isCopied = false;
        },
        resetPayment() {
            this.calcResult = null;
            this.paymentUrl = null;
            this.isCopied = false;
        },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price || 0);
        },

        // 🆕 Выбор города из чипов (как в MapPickerVector)
        selectCityForSearch(city) {
            if (this.selectedCity === city) {
                this.selectedCity = '';
                return;
            }
            this.selectedCity = city;

            if (!this.searchQuery.trim()) {
                this.searchQuery = city;
            } else if (!this.searchQuery.toLowerCase().includes(city.toLowerCase())) {
                this.searchQuery = `${city}, ${this.searchQuery}`;
            }

            this.searchAddress();
        },

        // 🆕 Поиск адреса через Nominatim (OpenStreetMap)
        async searchAddress() {
            if (!this.searchQuery.trim() || this.isSearching) return;

            this.isSearching = true;
            try {
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&addressdetails=1&limit=1`;
                const res = await fetch(url);
                const data = await res.json();

                if (data && data.length > 0) {
                    const { lat, lon } = data[0];
                    const latNum = parseFloat(lat);
                    const lngNum = parseFloat(lon);

                    // Обновляем состояние
                    this.lat = latNum;
                    this.lng = lngNum;
                    this.selectedAddress = this.formatAddress(data[0].address);

                    // 🆕 Передаем координаты карте — она сама переместит маркер
                    this.externalLocation = { lat: latNum, lng: lngNum };

                    // Сбрасываем предыдущий расчет
                    this.calcResult = null;
                    this.paymentUrl = null;
                } else {
                    this.$notify?.({
                        title: 'Не найдено',
                        text: 'Адрес не найден, попробуйте уточнить запрос',
                        type: 'warning'
                    });
                }
            } catch (error) {
                console.error('Ошибка поиска адреса:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось найти адрес', type: 'error' });
            } finally {
                this.isSearching = false;
            }
        },

        // 🆕 Форматирование адреса из данных Nominatim
        formatAddress(addr) {
            if (!addr) return this.searchQuery;
            const street = [addr.road, addr.house_number].filter(Boolean).join(", ");
            const city = addr.city || addr.town || addr.village || "";
            this.selectedCity = city;
            return [street, city].filter(Boolean).join(", ") || this.searchQuery;
        },

        // Обработчик обновления координат из компонента карты
        handleMapLocationUpdate(locationData) {
            this.lat = locationData.lat;
            this.lng = locationData.lng;
            this.selectedAddress = locationData.address;
            // Обновляем поисковое поле, чтобы оно соответствовало точке на карте
            this.searchQuery = locationData.address;
            this.calcResult = null;
            this.paymentUrl = null;
        },

        // Расчет доставки
        async calculateDelivery() {
            this.isCalculating = true;
            this.calcResult = null;
            this.paymentUrl = null;

            try {
                const response = await axios.post(`/deliveryman/shops/${this.shop.id}/calculate`, {
                    lat: this.lat,
                    lng: this.lng,
                    address: this.selectedAddress
                });

                if (response.data.success) {
                    this.calcResult = response.data.data;
                }
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.error || 'Не удалось рассчитать доставку',
                    type: 'error'
                });
            } finally {
                this.isCalculating = false;
            }
        },

        // Генерация ссылки
        async generatePaymentLink() {
            if (!this.calcResult) return;
            this.isGeneratingLink = true;

            try {
                const response = await axios.post(`/deliveryman/shops/${this.shop.id}/payment-link`, {
                    amount: this.calcResult.delivery_price,
                    description: `Оплата доставки из ${this.shop.name || this.shop.title}`
                });

                if (response.data.success) {
                    this.paymentUrl = response.data.data.url;
                }
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.error || 'Не удалось создать ссылку',
                    type: 'error'
                });
            } finally {
                this.isGeneratingLink = false;
            }
        },

        // Копирование ссылки
        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.paymentUrl);
                this.isCopied = true;
                setTimeout(() => { this.isCopied = false; }, 2000);
            } catch (err) {
                console.error('Не удалось скопировать', err);
            }
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.modal-overlay {
    position: fixed; inset: 0; background: rgba(31, 41, 55, 0.6); backdrop-filter: blur(4px);
    z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-container {
    background: $card-bg; border-radius: 20px; width: 100%; max-width: 500px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2); animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;
}
@keyframes modalSlideUp { from { opacity: 0; transform: translateY(20px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }

.modal-header {
    display: flex; align-items: flex-start; justify-content: space-between; padding: 16px 20px;
    border-bottom: 1px solid $border; flex-shrink: 0;
    .header-shop-info { display: flex; align-items: flex-start; gap: 12px; flex: 1; min-width: 0; }
    .shop-avatar-small {
        width: 48px; height: 48px; border-radius: 12px; background: $bg;
        display: flex; align-items: center; justify-content: center; color: $text-muted; flex-shrink: 0; overflow: hidden;
        img { width: 100%; height: 100%; object-fit: cover; }
    }
    .shop-header-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    h3 { font-size: 1.05rem; font-weight: 700; margin: 0; color: $text; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .shop-address-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.75rem; color: $text-muted; background: rgba($text-muted, 0.08);
        padding: 3px 10px; border-radius: 8px; width: fit-content;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;
        i { color: $primary; font-size: 0.7rem; flex-shrink: 0; }
    }
}
.modal-close {
    width: 36px; height: 36px; border-radius: 50%; background: $bg; border: none; color: $text-muted;
    cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    &:hover { background: $danger; color: white; }
}

.modal-body { padding: 20px; overflow-y: auto; flex: 1; }

// ==========================================
// 🆕 БЛОК ПОИСКА АДРЕСА
// ==========================================
.address-search-block { margin-bottom: 16px; }

.form-label {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.85rem; font-weight: 600; color: $text; margin-bottom: 8px;
    i { color: $primary; }
}

.search-input-group {
    display: flex; gap: 8px;
}

.form-input {
    flex: 1; padding: 12px 14px; border: 1px solid $border; border-radius: 10px;
    font-size: 0.9rem; background: $bg; transition: all 0.2s;
    &:focus { outline: none; border-color: $primary; box-shadow: 0 0 0 3px rgba($primary, 0.1); }
    &:disabled { opacity: 0.6; }
}

.btn-search {
    width: 48px; flex-shrink: 0; border: none; border-radius: 10px;
    background: $primary; color: white; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    &:hover:not(:disabled) { background: $primary-dark; }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

.spinner-small {
    width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

// 🆕 Чипы городов
.city-chips {
    display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px;
}
.city-chip {
    padding: 5px 12px; background: $bg; border: 1px solid $border;
    border-radius: 20px; font-size: 0.75rem; color: $text-muted;
    cursor: pointer; transition: all 0.2s;
    &:hover { border-color: $primary; color: $primary; background: rgba($primary, 0.05); }
    &.active {
        background: $primary; color: white; border-color: $primary;
    }
}

.map-selection-block { margin-bottom: 16px; }

.selected-address-display {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 10px 14px; background: rgba($success, 0.08); border: 1px solid rgba($success, 0.2);
    border-radius: 10px; font-size: 0.85rem; color: $text; line-height: 1.4;
    i { color: $success; margin-top: 2px; flex-shrink: 0; }
}

.btn-calculate {
    width: 100%; padding: 14px; background: $primary; color: white; border: none; border-radius: 12px;
    font-size: 0.95rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all 0.2s; margin-bottom: 20px;
    &:hover:not(:disabled) { background: $primary-dark; transform: translateY(-1px); }
    &:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
}

.calculation-result { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
.result-item {
    background: $bg; padding: 14px; border-radius: 12px; text-align: center;
    &.highlight { background: rgba($success, 0.1); border: 1px solid rgba($success, 0.2); }
    .label { display: block; font-size: 0.75rem; color: $text-muted; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
    .value { display: block; font-size: 1.1rem; font-weight: 700; color: $text;
        &.price { color: $success; font-size: 1.25rem; }
    }
}

.breakdown-card {
    background: $bg; border-radius: 12px; padding: 16px; margin-bottom: 20px;
    .breakdown-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed $border; font-size: 0.85rem;
        &:last-child { border-bottom: none; font-weight: 700; color: $text; padding-top: 10px; margin-top: 4px; border-top: 1px solid $border; }
        span:last-child { color: $text; font-weight: 600; }
    }
}
.text-warning { color: $warning !important; }

.payment-action-block { text-align: center; }
.btn-generate-link {
    width: 100%; padding: 14px; background: $success; color: white; border: none; border-radius: 12px;
    font-size: 0.95rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all 0.2s;
    &:hover:not(:disabled) { background: #059669; transform: translateY(-1px); }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

.payment-success { animation: fadeIn 0.3s ease; }
.qr-wrapper { background: white; padding: 12px; border-radius: 12px; display: inline-block; margin-bottom: 16px; border: 1px solid $border; }
.link-box { display: flex; gap: 8px; background: $bg; padding: 8px; border-radius: 10px; border: 1px solid $border; margin-bottom: 12px;
    input { flex: 1; border: none; background: transparent; font-size: 0.8rem; color: $text-muted; outline: none; min-width: 0; }
    .btn-copy { flex-shrink: 0; width: 36px; height: 36px; border-radius: 8px; border: none; background: $primary; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;
        &.copied { background: $success; }
    }
}
.btn-reset {
    width: 100%; padding: 12px; background: transparent; color: $text-muted; border: 1px solid $border; border-radius: 10px;
    font-size: 0.9rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
    &:hover { background: $bg; color: $text; border-color: $text-muted; }
}

@media (max-width: 640px) {
    .modal-overlay { padding: 0; align-items: flex-end; }
    .modal-container { max-width: 100%; max-height: 95vh; border-radius: 20px 20px 0 0; }
}
</style>
