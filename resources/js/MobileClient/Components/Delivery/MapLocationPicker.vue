<template>
    <div class="map-picker-container">
        <div ref="mapContainer" class="map-view"></div>

        <!-- Кнопка "Моё местоположение" -->
        <button class="geo-btn" @click="useMyLocation" :disabled="isLocating" title="Определить моё местоположение">
            <i v-if="isLocating" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-crosshairs"></i>
        </button>

        <!-- Подсказка -->
        <div class="map-hint">
            <i class="fa-solid fa-hand-pointer"></i> Нажмите на карту, чтобы выбрать точку доставки
        </div>
    </div>
</template>

<script>
import maplibregl from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";

export default {
    name: "MapLocationPicker",
    props: {
        shopCoords: { type: [String, Array, Object], default: "" },
        mapKey: { type: String, default: "l7t0HU7CqsgOKgS9rtvU" },
        externalLocation: { type: Object, default: null }
    },
    emits: ["update:location"],
    data() {
        return {
            map: null,
            deliveryMarker: null,
            shopMarker: null,
            isLocating: false,
            coords: { lat: null, lng: null }
        };
    },
    computed: {
        // 🎯 Надежный парсинг координат заведения
        shopCoordsParsed() {
            if (!this.shopCoords) return { lat: 55.7558, lng: 37.6173 }; // Дефолт (Москва)

            let lat = 55.7558, lng = 37.6173;

            if (typeof this.shopCoords === 'string' && this.shopCoords.includes(',')) {
                const parts = this.shopCoords.split(',');
                lat = parseFloat(parts[0].trim());
                lng = parseFloat(parts[1].trim());
            } else if (Array.isArray(this.shopCoords) && this.shopCoords.length >= 2) {
                lat = parseFloat(this.shopCoords[0]);
                lng = parseFloat(this.shopCoords[1]);
            } else if (typeof this.shopCoords === 'object' && this.shopCoords.lat && this.shopCoords.lng) {
                lat = parseFloat(this.shopCoords.lat);
                lng = parseFloat(this.shopCoords.lng);
            }

            return {
                lat: isNaN(lat) ? 55.7558 : lat,
                lng: isNaN(lng) ? 37.6173 : lng
            };
        }
    },
    watch: {
        externalLocation: {
            handler(newLoc) {
                if (newLoc && newLoc.lat && newLoc.lng) {
                    this.setDeliveryLocation(newLoc.lng, newLoc.lat);
                }
            },
            deep: true
        }
    },
    mounted() {
        this.initMap();
    },
    beforeUnmount() {
        if (this.map) {
            this.map.remove();
        }
    },
    methods: {
        initMap() {
            const center = this.shopCoordsParsed;

            this.map = new maplibregl.Map({
                container: this.$refs.mapContainer,
                style: `https://api.maptiler.com/maps/streets/style.json?key=${this.mapKey}`,
                center: [center.lng, center.lat], // 🎯 Центрируем на заведении
                zoom: 14,
                attributionControl: false
            });

            this.map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

            this.map.on("load", () => {
                this.addShopMarker();
            });

            this.map.on("click", (e) => {
                if (this.isLocating) return;
                const { lng, lat } = e.lngLat;
                this.setDeliveryLocation(lng, lat);
            });
        },

        // 🎯 Добавляем синий маркер заведения
        addShopMarker() {
            if (this.shopMarker) this.shopMarker.remove();

            // Создаем кастомный элемент для маркера магазина
            const el = document.createElement('div');
            el.className = 'shop-marker-icon';
            el.innerHTML = '<i class="fa-solid fa-store"></i>';

            this.shopMarker = new maplibregl.Marker({
                element: el,
                anchor: 'bottom'
            })
                .setLngLat([this.shopCoordsParsed.lng, this.shopCoordsParsed.lat])
                .addTo(this.map);
        },

        // 🎯 Устанавливаем красный маркер доставки
        setDeliveryLocation(lng, lat) {
            this.coords = { lat, lng };

            if (this.deliveryMarker) this.deliveryMarker.remove();

            const el = document.createElement('div');
            el.className = 'delivery-marker-icon';
            el.innerHTML = '<i class="fa-solid fa-location-dot"></i>';

            this.deliveryMarker = new maplibregl.Marker({
                element: el,
                anchor: 'bottom'
            })
                .setLngLat([lng, lat])
                .addTo(this.map);

            this.map.flyTo({ center: [lng, lat], zoom: 16, duration: 1000 });
            this.reverseGeocode(lat, lng);
        },

        async reverseGeocode(lat, lng) {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
                const res = await fetch(url);
                const data = await res.json();

                let address = "Адрес не определен";
                if (data && data.address) {
                    const city = data.address.city || data.address.town || data.address.village || "";
                    const road = data.address.road || "";
                    const house = data.address.house_number || "";
                    address = [city, road, house].filter(Boolean).join(", ");
                }

                this.$emit("update:location", {
                    lat: this.coords.lat,
                    lng: this.coords.lng,
                    address: address
                });
            } catch (error) {
                console.error('Ошибка обратного геокодирования:', error);
            }
        },

        async useMyLocation() {
            this.isLocating = true;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.setDeliveryLocation(position.coords.longitude, position.coords.latitude);
                        this.isLocating = false;
                    },
                    (error) => {
                        console.error('Геолокация недоступна:', error);
                        this.$notify?.({ title: 'Ошибка', text: 'Не удалось определить местоположение', type: 'error' });
                        this.isLocating = false;
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            } else {
                this.isLocating = false;
            }
        }
    }
};
</script>

<style lang="scss" scoped>
.map-picker-container {
    position: relative;
    width: 100%;
    height: 300px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    margin-bottom: 12px;
}

.map-view { width: 100%; height: 100%; }

.geo-btn {
    position: absolute; top: 12px; left: 12px; width: 40px; height: 40px; border-radius: 50%;
    background: white; border: 1px solid #e5e7eb; color: #3b82f6;
    display: flex; align-items: center; justify-content: center; cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 10; transition: all 0.2s;
    &:hover:not(:disabled) { background: #3b82f6; color: white; }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

.map-hint {
    position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(4px);
    padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; color: #4b5563;
    display: flex; align-items: center; gap: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1); white-space: nowrap;
}

// 🎯 Стили для кастомных маркеров MapLibre (глобальные, так как рендерятся вне scoped компонента)
:global(.shop-marker-icon) {
    width: 36px; height: 36px;
    background: #3b82f6; color: white;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    border: 2px solid white;
    i { transform: rotate(45deg); font-size: 1rem; }
}

:global(.delivery-marker-icon) {
    width: 36px; height: 36px;
    background: #ef4444; color: white;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    border: 2px solid white;
    i { transform: rotate(45deg); font-size: 1.1rem; }
}
</style>
