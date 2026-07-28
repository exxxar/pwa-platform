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
                :class="{ 'active': searchCity === city }"
                @click="selectCityForSearch(city)"
            >
                {{ city }}
            </button>
        </div>

        <div class="input-group mb-2">

            <div
                class="form-floating">
                <input type="text"
                       v-model="searchQuery"
                       @keyup.enter="searchAddress"
                       class="form-control" id="deliveryForm-city"
                       placeholder="Ваш город">
                <label for="deliveryForm-city">Адрес</label>
            </div>

            <button
                type="button"
                class="btn btn-primary" @click="searchAddress">Найти
            </button>
        </div>

        <div class="my-2 small" v-if="findAddress">
            <strong>Адрес:</strong> {{ findAddress }} ({{ coords.lat }}, {{ coords.lng }})
        </div>

        <div ref="map" class="map"></div>


    </div>
</template>

<script>
import maplibregl from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";

export default {
    name: "MapPickerVector",

    props: {
        ruNames: {
            type: Array,
            default: () => []
        },

        address: {
            type: String,
            default: ""
        },
        // ключ MapTiler или OpenMapTiles
        mapKey: {
            type: String,
            required: true
        }
    },
    emits: ["update:address"],
    watch: {
        'searchQuery': {
            handler: function (newValue) {
                localStorage.setItem("mypwa_self_map_tile_search_query", this.searchQuery)
            },
            deep: true
        },
        'findAddress': {
            handler: function (newValue) {
                this.$emit("update:address", this.findAddress || this.searchQuery);
                this.$emit("update:lng", this.coords.lng);
                this.$emit("update:lat", this.coords.lat);
                this.$emit("update:city", this.city);

                localStorage.setItem("mypwa_self_map_tile_search_query",  this.findAddress)

                window.dispatchEvent(new CustomEvent('change-delivery-address', {
                    detail: {
                        address: this.findAddress || this.searchQuery,
                        lng: this.coords.lng,
                        lat: this.coords.lat,
                        city: this.city,

                    }
                }));
            },
            deep: true
        },
    },
    computed: {
        tenant() {
            return window.Tenant
        },
        settings() {
            return this.tenant?.settings || {};
        },

        shopCoordsParsed() {
            const shopCoords = this.tenant?.settings?.shop_coords ?? null

            if (!shopCoords) {
                return {
                    lat: 0,
                    lng: 0
                }
            }

            const coords = shopCoords.split(',')

            const lng= parseFloat(coords[0] ?? 0)
            const lat = parseFloat(coords[1] ?? 0)

            return {
                lat,
                lng
            }
        },
        nearestCitiesList() {
            const rawCities = this.settings.shop?.nearest_cities || this.settings.nearest_cities || '';
            if (!rawCities) return [];
            return rawCities
                .split(/[,\n]+/)
                .map(city => city.trim())
                .filter(city => city.length > 0);
        },
    },
    data() {
        return {
            map: null,
            marker: null,
            searchQuery: "",
            city:"",

            coords: {lat: null, lng: null},
            findAddress: "",

        };
    },

    mounted() {
        this.initMap();

        this.searchQuery = localStorage.getItem("mypwa_self_map_tile_search_query") != null ?
            localStorage.getItem("mypwa_self_map_tile_search_query") : null

    },

    methods: {

        selectCityForSearch(city) {
            // Если уже выбран — снимаем выбор
            if (this.city === city) {
                this.city = '';
                return;
            }

            this.city = city;

            // Если поле поиска пустое — сразу подставляем город
            if (!this.searchQuery.trim()) {
                this.searchQuery = city;
            }
            // Если поле не пустое и город еще не в адресе — добавляем в начало
            else if (!this.searchQuery.toLowerCase().includes(city.toLowerCase())) {
                this.searchQuery = `${city}, ${this.searchQuery}`;
            }

            // Триггерим поиск (если у вас есть метод поиска)
            this.$nextTick(() => {
                this.searchAddress(); // или this.performSearch()
            });
        },

        initMap() {
            this.map = new maplibregl.Map({
                container: this.$refs.map,
                style: `https://api.maptiler.com/maps/streets/style.json?key=${this.mapKey}`,
                center: [this.shopCoordsParsed.lat || 0, this.shopCoordsParsed.lng || 0],
                zoom: 13
            });

            this.map.addControl(new maplibregl.NavigationControl());

            this.map.on("load", () => {
                this.applyRussianLabels();
            });

            this.map.on("click", (e) => {
                const {lng, lat} = e.lngLat;
                this.coords = {lat, lng};
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
                        ["get", "name:ru"], // русский
                        ["get", "name"],    // fallback
                        ["get", "name:uk"],
                        ["get", "name:en"]
                    ]);
                }
            });
        },

        placeMarker(lng, lat) {
            if (this.marker) this.marker.remove();

            this.marker = new maplibregl.Marker({color: "red"})
                .setLngLat([lng, lat])
                .addTo(this.map);


        },

        async searchAddress() {
            if (!this.searchQuery.trim()) return;

            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
                this.searchQuery
            )}&addressdetails=1&limit=1`;

            const res = await fetch(url);
            const data = await res.json();

            if (!data.length) return;

            const {lat, lon} = data[0];

            this.map.flyTo({center: [lon, lat], zoom: 16});
            this.placeMarker(lon, lat);

            this.coords = {lat, lng: lon};
            this.findAddress = this.formatAddress(data[0].address);
        },

        async reverseGeocode(lat, lng) {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;

            const res = await fetch(url);
            const data = await res.json();

            this.findAddress = this.formatAddress(data.address);
        },

        formatAddress(addr) {
            if (!addr) return "";

            const street = [addr.road, addr.house_number].filter(Boolean).join(", ");
            const city = addr.city || addr.town || addr.village || "";

            this.city = city

            console.log("city", this.city)

            let full = [street, city].filter(Boolean).join(", ");

            // Подмена на русский через словарь
            this.ruNames.forEach(item => {
                full = full.replace(item.original, item.ru);
            });


            return full;
        },


    }
};
</script>

<style lang="scss" scoped>
.map {
    width: 100%;
    height: 450px;
    border-radius: 6px;
    overflow: hidden;
}

/* ==========================================
   🆕 ЧИПСЫ ГОРОДОВ ДЛЯ ПОИСКА АДРЕСА
   ========================================== */
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

i {
    color: var(--bs-primary);
    font-size: 0.7rem;
}
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
     box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.15);
 }

&.active {
     background: var(--bs-primary);
     border-color: var(--bs-primary);
     color: white;
     box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.3);
 }

&:active {
     transform: scale(0.97);
 }
}

/* Мобильная адаптация */
@media (max-width: 576px) {
    .search-city-chips {
        gap: 5px;
        padding: 8px 10px;
    }

    .chips-label {
        width: 100%;
        margin-bottom: 4px;
    }

    .search-city-chip {
        padding: 5px 10px;
        font-size: 0.75rem;
    }
}
</style>
