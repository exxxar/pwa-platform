<template>
    <div class="map-picker-container">
        <div ref="mapContainer" class="map-view"></div>

        <!-- Кнопка "Моё местоположение" поверх карты -->
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
        shopCoords: { type: String, default: "" }, // Координаты заведения "55.75, 37.61"
        mapKey: { type: String, default: "l7t0HU7CqsgOKgS9rtvU" } // Fallback ключ MapTiler
    },
    emits: ["update:location"],
    data() {
        return {
            map: null,
            marker: null,
            isLocating: false,
            coords: { lat: null, lng: null }
        };
    },
    computed: {
        shopCoordsParsed() {
            if (!this.shopCoords) return { lat: 55.7558, lng: 37.6173 };
            const coords = this.shopCoords.split(',');
            return {
                lat: parseFloat(coords[0]) || 55.7558,
                lng: parseFloat(coords[1]) || 37.6173
            };
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
            this.map = new maplibregl.Map({
                container: this.$refs.mapContainer,
                style: `https://api.maptiler.com/maps/streets/style.json?key=${this.mapKey}`,
                center: [this.shopCoordsParsed.lng, this.shopCoordsParsed.lat],
                zoom: 14,
                attributionControl: false
            });

            this.map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

            // Клик по карте для выбора точки доставки
            this.map.on("click", (e) => {
                if (this.isLocating) return;
                const { lng, lat } = e.lngLat;
                this.setLocation(lng, lat);
            });
        },

        async setLocation(lng, lat) {
            this.coords = { lat, lng };

            // Ставим маркер
            if (this.marker) this.marker.remove();
            this.marker = new maplibregl.Marker({ color: "#ef4444" }) // Красный маркер для точки доставки
                .setLngLat([lng, lat])
                .addTo(this.map);

            // Плавный перелет камеры
            this.map.flyTo({ center: [lng, lat], zoom: 16, duration: 1000 });

            // Обратное геокодирование
            await this.reverseGeocode(lat, lng);
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
                        this.setLocation(position.coords.longitude, position.coords.latitude);
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

.map-view {
    width: 100%;
    height: 100%;
}

.geo-btn {
    position: absolute;
    top: 12px;
    left: 12px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    border: 1px solid #e5e7eb;
    color: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 10;
    transition: all 0.2s;
    &:hover:not(:disabled) { background: #3b82f6; color: white; }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

.map-hint {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(4px);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    white-space: nowrap;
}
</style>
