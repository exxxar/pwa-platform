<template>
    <div class="map-container">
        <div v-if="nearestCitiesList.length > 0" class="search-city-chips">
            <span class="chips-label">
                <i class="fa-solid fa-magnifying-glass-location"></i> Город:
            </span>
            <button
                v-for="city in nearestCitiesList"
                :key="city"
                type="button"
                class="search-city-chip"
                :class="{ 'active': city === selectedCity }"
                @click="selectCityForSearch(city)"
            >
                {{ city }}
            </button>
        </div>

        <div class="input-group mb-2">
            <div class="form-floating flex-grow-1">
                <input
                    type="text"
                    v-model="searchQuery"
                    @keyup.enter="searchAddress"
                    class="form-control"
                    id="deliveryForm-city"
                    placeholder="Ваш адрес"
                    :disabled="isSearching"
                >
                <label for="deliveryForm-city">Адрес</label>
            </div>

            <button
                type="button"
                class="btn btn-primary"
                @click="searchAddress"
                :disabled="isSearching"
            >
                <span v-if="isSearching" class="spinner-border spinner-border-sm"></span>
                <span v-else>Найти</span>
            </button>
        </div>

        <div class="my-2 small text-muted" v-if="findAddress && !isSearching">
            <i class="fa-solid fa-location-dot text-primary"></i>
            <strong>{{ findAddress }}</strong>
        </div>

        <div ref="map" class="map"></div>
    </div>
</template>

<script>
import maplibregl from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";

export default {
    name: "MapPickerVector", // ⚠️ Убедитесь, что в AddressForm вы импортируете именно этот компонент, а не YandexMapPicker

    props: {
        ruNames: { type: Array, default: () => [] },
        address: { type: String, default: "" },
        mapKey: { type: String, required: true }
    },

    emits: ["update:address", "update:lat", "update:lng", "update:city"],

    data() {
        return {
            map: null,
            marker: null,
            searchQuery: "",
            selectedCity: "", // Переименовано для ясности
            coords: { lat: null, lng: null },
            findAddress: "",
            isSearching: false, // 🆕 Флаг защиты от двойных кликов
        };
    },

    computed: {
        tenant() { return window.Tenant; },
        settings() { return this.tenant?.settings || {}; },

        shopCoordsParsed() {
            const shopCoords = this.tenant?.settings?.shop_coords ?? null;
            if (!shopCoords) return { lat: 55.7558, lng: 37.6173 }; // Дефолт (Москва), если координат нет

            const coords = shopCoords.split(',');
            return {
                lng: parseFloat(coords[0]) || 37.6173, // MapLibre использует [lng, lat]
                lat: parseFloat(coords[1]) || 55.7558
            };
        },

        nearestCitiesList() {
            const rawCities = this.settings.shop?.nearest_cities || this.settings.nearest_cities || '';
            if (!rawCities) return [];
            return rawCities.split(/[,\n]+/).map(c => c.trim()).filter(c => c.length > 0);
        },
    },

    mounted() {
        this.initMap();
        const savedQuery = localStorage.getItem("mypwa_self_map_tile_search_query");
        if (savedQuery) {
            this.searchQuery = savedQuery;
        }
    },

    beforeUnmount() {
        if (this.map) {
            this.map.remove();
        }
    },

    methods: {
        // 🆕 ЕДИНЫЙ метод для обновления родителя и хранилища. Вызывается ОДИН раз.
        emitChanges() {
            const finalAddress = this.findAddress || this.searchQuery;

            this.$emit("update:address", finalAddress);
            this.$emit("update:lng", this.coords.lng);
            this.$emit("update:lat", this.coords.lat);
            this.$emit("update:city", this.selectedCity);

            localStorage.setItem("mypwa_self_map_tile_search_query", finalAddress);

            window.dispatchEvent(new CustomEvent('change-delivery-address', {
                detail: {
                    address: finalAddress,
                    lng: this.coords.lng,
                    lat: this.coords.lat,
                    city: this.selectedCity,
                }
            }));
        },

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

        initMap() {
            this.map = new maplibregl.Map({
                container: this.$refs.map,
                style: `https://api.maptiler.com/maps/streets/style.json?key=${this.mapKey}`,
                center: [this.shopCoordsParsed.lng, this.shopCoordsParsed.lat],
                zoom: 13
            });

            this.map.addControl(new maplibregl.NavigationControl());

            this.map.on("load", () => {
                this.applyRussianLabels();
            });

            this.map.on("click", (e) => {
                if (this.isSearching) return; // Блокировка при активном поиске

                const { lng, lat } = e.lngLat;
                this.coords = { lat, lng };
                this.placeMarker(lng, lat);
                this.reverseGeocode(lat, lng);
            });
        },

        applyRussianLabels() {
            const layers = this.map.getStyle().layers;
            layers.forEach(layer => {
                if (layer.type === "symbol" && layer.layout && layer.layout["text-field"]) {
                    this.map.setLayoutProperty(layer.id, "text-field", [
                        "coalesce",
                        ["get", "name:ru"],
                        ["get", "name"],
                        ["get", "name:uk"],
                        ["get", "name:en"]
                    ]);
                }
            });
        },

        placeMarker(lng, lat) {
            if (this.marker) this.marker.remove();
            this.marker = new maplibregl.Marker({ color: "red" })
                .setLngLat([lng, lat])
                .addTo(this.map);
        },

        async searchAddress() {
            if (!this.searchQuery.trim() || this.isSearching) return;

            this.isSearching = true;
            try {
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&addressdetails=1&limit=1`;
                const res = await fetch(url);
                const data = await res.json();

                if (data && data.length > 0) {
                    const { lat, lon } = data[0];
                    this.map.flyTo({ center: [lon, lat], zoom: 16 });
                    this.placeMarker(lon, lat);
                    this.coords = { lat: parseFloat(lat), lng: parseFloat(lon) };
                    this.findAddress = this.formatAddress(data[0].address);

                    this.emitChanges(); // 🆕 Вызываем ОДИН раз
                } else {
                    this.$notify?.({ title: 'Не найдено', text: 'Адрес не найден, попробуйте уточнить запрос', type: 'warning' });
                }
            } catch (error) {
                console.error('Ошибка поиска адреса:', error);
            } finally {
                this.isSearching = false;
            }
        },

        async reverseGeocode(lat, lng) {
            this.isSearching = true;
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
                const res = await fetch(url);
                const data = await res.json();

                if (data && data.address) {
                    this.findAddress = this.formatAddress(data.address);
                    this.emitChanges(); // 🆕 Вызываем ОДИН раз
                }
            } catch (error) {
                console.error('Ошибка обратного геокодирования:', error);
            } finally {
                this.isSearching = false;
            }
        },

        formatAddress(addr) {
            if (!addr) return "";
            const street = [addr.road, addr.house_number].filter(Boolean).join(", ");
            const city = addr.city || addr.town || addr.village || "";
            this.selectedCity = city;

            let full = [street, city].filter(Boolean).join(", ");

            this.ruNames.forEach(item => {
                full = full.replace(item.original, item.ru);
            });

            return full || this.searchQuery;
        },
    }
};
</script>

<style lang="scss" scoped>
.map {
    width: 100%;
    height: 450px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--bs-border-color);
}

.search-city-chips {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
    padding: 10px 12px;
    background: rgba(var(--bs-primary-rgb, 102, 126, 234), 0.04);
    border: 1px solid rgba(var(--bs-primary-rgb, 102, 126, 234), 0.1);
    border-radius: 12px;
}

.chips-label {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-right: 4px;

    i { color: var(--bs-primary); font-size: 0.7rem; }
}

.search-city-chip {
    padding: 6px 12px;
    background: var(--bs-body-bg);
    border: 1.5px solid var(--bs-border-color);
    border-radius: 16px;
    color: var(--bs-body-color);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;

    &:hover {
        border-color: var(--bs-primary);
        color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.05);
        transform: translateY(-1px);
    }

    &.active {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
        color: white;
        box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.3);
    }

    &:active { transform: scale(0.97); }
}

@media (max-width: 576px) {
    .search-city-chips { gap: 5px; padding: 8px 10px; }
    .chips-label { width: 100%; margin-bottom: 4px; }
    .search-city-chip { padding: 5px 10px; font-size: 0.75rem; }
}
</style>
