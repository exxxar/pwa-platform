<template>
    <Teleport to="body">
        <transition name="bottom-sheet">
            <div v-if="isVisible" class="location-modal-backdrop" @click="close">
                <div class="location-modal-sheet" @click.stop>
                    <div class="modal-handle"></div>

                    <div class="modal-header">
                        <h3 class="modal-title">Расположение</h3>
                        <button class="modal-close-btn" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <!-- Адрес заведения -->
                        <div class="address-block">
                            <i class="fa-solid fa-location-dot text-primary"></i>
                            <div>
                                <div class="address-label">Адрес заведения:</div>
                                <span class="address-text">{{ partnerAddress || 'Адрес не указан' }}</span>
                            </div>
                        </div>

                        <!-- 🆕 Поле ввода адреса пользователя -->
                        <div class="user-address-input">
                            <label class="input-label">
                                <i class="fa-solid fa-house"></i>
                                Ваш адрес доставки
                            </label>
                            <div class="input-group">
                                <input
                                    v-model="userAddressInput"
                                    type="text"
                                    class="form-control"
                                    placeholder="Например: Москва, ул. Примерная, д. 1"
                                    @keyup.enter="geocodeAddress"
                                >
                                <button
                                    class="btn-search"
                                    @click="geocodeAddress"
                                    :disabled="isGeocoding || !userAddressInput.trim()"
                                >
                                    <i v-if="isGeocoding" class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <small class="input-hint">Введите адрес, чтобы рассчитать расстояние и стоимость доставки</small>
                        </div>

                        <!-- 🆕 Результат геокодинга -->
                        <div v-if="geocodedAddress" class="geocode-result">
                            <i class="fa-solid fa-check-circle text-success"></i>
                            <div class="geocode-info">
                                <div class="geocode-label">Найден адрес:</div>
                                <div class="geocode-text">{{ geocodedAddress.display_name }}</div>
                            </div>
                            <button class="btn-clear" @click="clearGeocode" title="Очистить">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <!-- 🆕 Результат расчета расстояния -->
                        <div v-if="calculatedDistance !== null" class="distance-result">
                            <div class="distance-icon">
                                <i class="fa-solid fa-route"></i>
                            </div>
                            <div class="distance-info">
                                <div class="distance-label">Расстояние до заведения:</div>
                                <div class="distance-value">{{ calculatedDistance }} км</div>
                                <div v-if="estimatedDeliveryTime" class="delivery-time">
                                    <i class="fa-regular fa-clock"></i>
                                    Примерное время доставки: {{ estimatedDeliveryTime }} мин
                                </div>
                            </div>
                        </div>

                        <!-- Ошибки -->
                        <div v-if="locationError" class="distance-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ locationError }}</span>
                        </div>

                        <!-- Карта (показываем только если есть координаты заведения) -->
                        <div class="map-block" v-if="isValidCoords">
                            <PartnerLocationMap
                                :map-key="mapKey"
                                :lat="parsedLat"
                                :lng="parsedLng"
                                :address="partnerAddress"
                            />
                        </div>
                        <div v-else class="no-coords-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>Координаты не заданы в настройках заведения</span>
                        </div>

                        <!-- Кнопки действий -->
                        <div class="action-buttons">
                            <!-- Кнопка расчета (показывается когда адрес найден, но расстояние еще не посчитано) -->
                            <button
                                v-if="geocodedAddress && calculatedDistance === null && !locationError"
                                class="route-btn calculate-distance"
                                @click="calculateDistance"
                            >
                                <i class="fa-solid fa-calculator"></i>
                                Рассчитать расстояние и доставку
                            </button>

                            <!-- Ссылки на карты -->
                            <div v-if="isValidCoords" class="maps-links">
                                <a
                                    :href="`https://yandex.ru/maps/?rtext=~${parsedLat},${parsedLng}`"
                                    target="_blank"
                                    class="route-btn yandex"
                                >
                                    <i class="fa-solid fa-route"></i> Построить маршрут (Яндекс)
                                </a>
                                <a
                                    :href="`https://maps.google.com/?q=${parsedLat},${parsedLng}`"
                                    target="_blank"
                                    class="route-btn google"
                                >
                                    <i class="fa-brands fa-google"></i> Google Maps
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script>
import PartnerLocationMap from './PartnerLocationMap.vue';

export default {
    name: "PartnerLocationModal",
    components: { PartnerLocationMap },
    props: {
        isVisible: { type: Boolean, default: false },
        partner: { type: Object, default: null },
        mapKey: { type: String, default: 'l7t0HU7CqsgOKgS9rtvU' }
    },
    emits: ['close', 'address-found'], // 🆕 Добавляем emit для передачи адреса

    data() {
        return {
            userAddressInput: '',      // Введенный пользователем адрес
            geocodedAddress: null,     // Результат геокодинга (объект с координатами и полным адресом)
            userCoords: null,          // Координаты найденного адреса {lat, lng}
            calculatedDistance: null,  // Рассчитанное расстояние в км
            estimatedDeliveryTime: null, // Примерное время доставки в минутах
            isGeocoding: false,
            locationError: null,
        };
    },

    watch: {
        isVisible(newVal) {
            if (newVal) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
                this.resetState();
            }
        }
    },

    beforeUnmount() {
        document.body.style.overflow = '';
    },

    computed: {
        partnerAddress() {
            if (!this.partner) return '';
            return this.partner.address || this.partner.settings?.address || 'Адрес не указан';
        },
        rawCoords() {
            if (!this.partner) return '0,0';
            return this.partner.shop_coords || this.partner.settings?.shop_coords || '0,0';
        },
        parsedLat() {
            const coords = this.rawCoords.split(',');
            console.log("parsedLat",coords )
            return parseFloat(coords[0]) || 0;
        },
        parsedLng() {
            const coords = this.rawCoords.split(',');
            console.log("parsedLng",coords )
            return parseFloat(coords[1]) || 0;
        },
        isValidCoords() {
            return this.parsedLat !== 0 && this.parsedLng !== 0;
        }
    },

    methods: {
        close() {
            this.$emit('close');
        },

        resetState() {
            this.userAddressInput = '';
            this.geocodedAddress = null;
            this.userCoords = null;
            this.calculatedDistance = null;
            this.estimatedDeliveryTime = null;
            this.locationError = null;
            this.isGeocoding = false;
        },

        clearGeocode() {
            this.geocodedAddress = null;
            this.userCoords = null;
            this.calculatedDistance = null;
            this.estimatedDeliveryTime = null;
            this.locationError = null;
        },

        // 🆕 Геокодинг адреса (получение координат по адресу)
        async geocodeAddress() {
            if (!this.userAddressInput.trim()) return;

            this.isGeocoding = true;
            this.locationError = null;
            this.geocodedAddress = null;
            this.userCoords = null;
            this.calculatedDistance = null;

            try {
                const query = encodeURIComponent(this.userAddressInput.trim());
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&addressdetails=1&limit=1`);
                const data = await response.json();

                if (!data || data.length === 0) {
                    throw new Error('Адрес не найден. Попробуйте указать более полный адрес.');
                }

                const result = data[0];

                this.geocodedAddress = {
                    display_name: result.display_name,
                    lat: parseFloat(result.lat),
                    lon: parseFloat(result.lon),
                    address: result.address
                };

                this.userCoords = {
                    lat: this.geocodedAddress.lat,
                    lng: this.geocodedAddress.lon
                };

                // Автоматически рассчитываем расстояние после успешного геокодинга
                this.calculateDistance();

                //  Отправляем событие родителю с найденным адресом (для корзины)
                this.$emit('address-found', {
                    address: this.geocodedAddress.display_name,
                    lat: this.userCoords.lat,
                    lng: this.userCoords.lng
                });

            } catch (error) {
                console.error('Geocoding error:', error);
                this.locationError = error.message || 'Ошибка при поиске адреса. Попробуйте позже.';
            } finally {
                this.isGeocoding = false;
            }
        },

        // 🆕 Расчет расстояния между двумя точками
        calculateDistance() {
            if (!this.userCoords || !this.isValidCoords) {
                this.locationError = 'Невозможно рассчитать расстояние: проверьте координаты';
                return;
            }

            // Формула гаверсинусов
            const R = 6371; // Радиус Земли в км
            const dLat = this.deg2rad(this.parsedLat - this.userCoords.lat);
            const dLon = this.deg2rad(this.parsedLng - this.userCoords.lng);

            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(this.deg2rad(this.userCoords.lat)) * Math.cos(this.deg2rad(this.parsedLat)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);

            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c;

            this.calculatedDistance = distance.toFixed(1);

            // 🆕 Примерный расчет времени доставки (средняя скорость 30 км/ч в городе)
            const speedKmh = 30;
            const timeHours = distance / speedKmh;
            this.estimatedDeliveryTime = Math.round(timeHours * 60) + 10; // в минутах

            /*
             * 💡 ЕСЛИ НУЖНО ПОЛУЧИТЬ ЦЕНУ ДОСТАВКИ ЧЕРЕЗ API:
             * Раскомментируйте и адаптируйте:
             *
             * import { useBasket } from '@/MobileClient/composables/useBasket.js';
             * const basket = useBasket();
             *
             * basket.requestDeliveryPriceNew({
             *     address: this.geocodedAddress.display_name,
             *     lat: this.userCoords.lat,
             *     lng: this.userCoords.lng,
             *     partner_id: this.partner.id
             * }).then(response => {
             *     // Обработка ответа с ценой
             * });
             */
        },

        deg2rad(deg) {
            return deg * (Math.PI / 180);
        }
    }
};
</script>

<style scoped>
.location-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.location-modal-sheet {
    width: 100%;
    max-width: 600px;
    background: #FFFFFF;
    border-radius: 24px 24px 0 0;
    padding: 12px 24px 32px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
}

.modal-handle {
    width: 40px;
    height: 4px;
    background: #E0E0E0;
    border-radius: 2px;
    margin: 0 auto 20px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 0;
}

.modal-close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #F5F5F7;
    border: none;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.modal-close-btn:hover {
    background: #E0E0E0;
}

.address-block {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: #F8F9FA;
    border-radius: 12px;
    margin-bottom: 16px;
}

.address-block i {
    color: #FF6B35;
    font-size: 1.2rem;
    margin-top: 2px;
}

.address-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    margin-bottom: 4px;
}

.address-text {
    font-size: 0.95rem;
    color: #333;
    line-height: 1.4;
}

/* 🆕 Стили для поля ввода адреса */
.user-address-input {
    margin-bottom: 16px;
}

.input-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.input-label i {
    color: #FF6B35;
}

.input-group {
    display: flex;
    gap: 8px;
}

.input-group .form-control {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #E0E0E0;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.input-group .form-control:focus {
    outline: none;
    border-color: #FF6B35;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.btn-search {
    padding: 12px 16px;
    background: #FF6B35;
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-search:hover:not(:disabled) {
    background: #e55a2b;
    transform: translateY(-1px);
}

.btn-search:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.input-hint {
    display: block;
    margin-top: 6px;
    font-size: 0.8rem;
    color: #888;
}

/*  Результат геокодинга */
.geocode-result {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(25, 135, 84, 0.08);
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 12px;
    margin-bottom: 16px;
}

.geocode-result i {
    color: #198754;
    font-size: 1.2rem;
    margin-top: 2px;
}

.geocode-info {
    flex: 1;
}

.geocode-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    margin-bottom: 4px;
}

.geocode-text {
    font-size: 0.9rem;
    color: #333;
    line-height: 1.4;
}

.btn-clear {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.1);
    border: none;
    color: #dc3545;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}

.btn-clear:hover {
    background: rgba(220, 53, 69, 0.2);
}

/* 🆕 Результат расчета расстояния */
.distance-result {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(255, 107, 53, 0.08) 0%, rgba(255, 154, 139, 0.08) 100%);
    border: 1px solid rgba(255, 107, 53, 0.2);
    border-radius: 12px;
    margin-bottom: 16px;
}

.distance-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #FF6B35;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.distance-info {
    flex: 1;
}

.distance-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    margin-bottom: 4px;
}

.distance-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #FF6B35;
    margin-bottom: 4px;
}

.delivery-time {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #666;
}

.delivery-time i {
    color: #FF6B35;
}

.distance-error {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px;
    background: rgba(220, 53, 69, 0.08);
    border: 1px solid rgba(220, 53, 69, 0.2);
    border-radius: 12px;
    color: #dc3545;
    margin-bottom: 16px;
    font-size: 0.9rem;
}

.map-block {
    margin-bottom: 20px;
}

.no-coords-warning {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px;
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 12px;
    color: #856404;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.maps-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.route-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.route-btn.calculate-distance {
    background: #1A1A1A;
    color: white;
}
.route-btn.calculate-distance:hover {
    background: #333;
    transform: translateY(-1px);
}

.route-btn.yandex {
    background: #FC3F1D;
    color: white;
}
.route-btn.yandex:hover {
    background: #e03515;
}

.route-btn.google {
    background: #FFFFFF;
    color: #333;
    border: 1px solid #E0E0E0;
}
.route-btn.google:hover {
    background: #F5F5F5;
}

/* Анимации */
.bottom-sheet-enter-active, .bottom-sheet-leave-active {
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.bottom-sheet-enter-from, .bottom-sheet-leave-to {
    opacity: 0;
}
.bottom-sheet-enter-from .location-modal-sheet,
.bottom-sheet-leave-to .location-modal-sheet {
    transform: translateY(100%);
}
</style>
