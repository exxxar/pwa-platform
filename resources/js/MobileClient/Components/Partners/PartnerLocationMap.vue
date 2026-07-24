<template>
    <div class="partner-map-wrapper">
        <div ref="mapContainer" class="map-view"></div>
    </div>
</template>

<script>
import maplibregl from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";

export default {
    name: "PartnerLocationMap",
    props: {
        mapKey: { type: String, required: true },
        lat: { type: Number, default: 0 },
        lng: { type: Number, default: 0 },
        address: { type: String, default: '' }
    },
    data() {
        return { map: null, marker: null };
    },
    mounted() {
        this.initMap();
    },
    beforeUnmount() {
        if (this.map) this.map.remove();
    },
    methods: {
        initMap() {
            // ⚠️ ВАЖНО: В вашем исходном коде coords[0] это lng, а coords[1] это lat.
            // Оставляем эту логику для совместимости с вашей БД.
            this.map = new maplibregl.Map({
                container: this.$refs.mapContainer,
                style: `https://api.maptiler.com/maps/streets/style.json?key=${this.mapKey}`,
                center: [this.lng, this.lat],
                zoom: 15,
                interactive: true // Можно двигать карту, но без поиска
            });

            this.map.addControl(new maplibregl.NavigationControl({ showCompass: false }));

            this.map.on("load", () => {
                this.placeMarker();
            });
        },
        placeMarker() {
            if (this.marker) this.marker.remove();
            this.marker = new maplibregl.Marker({ color: "#FF6B35" }) // Оранжевый маркер под стиль
                .setLngLat([this.lng, this.lat])
                .setPopup(new maplibregl.Popup({ offset: 25 }).setText(this.address || 'Заведение'))
                .addTo(this.map);
        }
    }
};
</script>

<style scoped>
.partner-map-wrapper {
    width: 100%;
    height: 300px;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.06);
}
.map-view {
    width: 100%;
    height: 100%;
}
</style>
