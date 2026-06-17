<template>
    <section class="shop-delivery">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">
                    <i class="fa-solid fa-truck-fast"></i> Быстрая доставка
                </span>
                <h2 class="section-title">Доставляем по всему городу</h2>
                <p class="section-subtitle">Проверьте, входите ли вы в зону доставки, и узнайте стоимость</p>
            </div>

            <div class="delivery-wrapper">
                <!-- SVG Карта -->
                <div class="delivery-map">
                    <svg viewBox="0 0 500 500" class="map-svg">
                        <!-- Фоновая сетка -->
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(0,0,0,0.05)" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="500" height="500" fill="url(#grid)"/>

                        <!-- Зоны доставки -->
                        <circle
                            v-for="zone in zones"
                            :key="zone.id"
                            cx="250" cy="250"
                            :r="zone.radius"
                            :fill="zone.color"
                            :stroke="zone.borderColor"
                            stroke-width="2"
                            stroke-dasharray="5,5"
                            class="zone-circle"
                            @mouseenter="activeZone = zone.id"
                            @mouseleave="activeZone = null"
                        />

                        <!-- Метка ресторана -->
                        <g transform="translate(250, 250)">
                            <circle r="20" fill="var(--primary)" class="restaurant-pulse"/>
                            <circle r="12" fill="white"/>
                            <circle r="6" fill="var(--primary)"/>
                        </g>

                        <!-- Подпись -->
                        <text x="250" y="290" text-anchor="middle" fill="var(--dark)" font-size="12" font-weight="700">
                            Наш ресторан
                        </text>
                    </svg>

                    <!-- Легенда -->
                    <div class="map-legend">
                        <div v-for="zone in zones" :key="'legend-' + zone.id" class="legend-item">
                            <span class="legend-dot" :style="{ background: zone.color }"></span>
                            <span>{{ zone.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Информация о зонах -->
                <div class="delivery-info">
                    <div
                        v-for="zone in zones"
                        :key="'info-' + zone.id"
                        class="zone-card"
                        :class="{ 'active': activeZone === zone.id }"
                    >
                        <div class="zone-color" :style="{ background: zone.color }"></div>
                        <div class="zone-details">
                            <h4>{{ zone.name }}</h4>
                            <div class="zone-meta">
                                <div class="meta-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>{{ zone.time }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-ruble-sign"></i>
                                    <span>{{ zone.price }}</span>
                                </div>
                                <div class="meta-item" v-if="zone.minOrder">
                                    <i class="fa-solid fa-basket-shopping"></i>
                                    <span>от {{ zone.minOrder }} ₽</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="delivery-features">
                        <div class="feature">
                            <i class="fa-solid fa-temperature-low"></i>
                            <div>
                                <strong>Термосумки</strong>
                                <span>Сохраняем температуру блюд</span>
                            </div>
                        </div>
                        <div class="feature">
                            <i class="fa-solid fa-bell-concierge"></i>
                            <div>
                                <strong>Бесплатная доставка</strong>
                                <span>При заказе от 2000 ₽</span>
                            </div>
                        </div>
                        <div class="feature">
                            <i class="fa-solid fa-location-crosshairs"></i>
                            <div>
                                <strong>Отслеживание</strong>
                                <span>Курьер на карте в реальном времени</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopDelivery",
    data() {
        return {
            activeZone: null,
            zones: [
                {
                    id: 1,
                    name: 'Центр',
                    radius: 100,
                    color: 'rgba(16, 185, 129, 0.2)',
                    borderColor: '#10b981',
                    time: '30-40 мин',
                    price: 'Бесплатно',
                    minOrder: '1000'
                },
                {
                    id: 2,
                    name: 'Спальные районы',
                    radius: 170,
                    color: 'rgba(59, 130, 246, 0.15)',
                    borderColor: '#3b82f6',
                    time: '40-60 мин',
                    price: '150 ₽',
                    minOrder: '1500'
                },
                {
                    id: 3,
                    name: 'Пригород',
                    radius: 230,
                    color: 'rgba(245, 158, 11, 0.12)',
                    borderColor: '#f59e0b',
                    time: '60-90 мин',
                    price: '300 ₽',
                    minOrder: '2000'
                }
            ]
        };
    }
};
</script>

<style lang="scss" scoped>
.shop-delivery { padding: 80px 0; background: white; }
.section-header { text-align: center; margin-bottom: 3rem; }
.section-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255, 122, 0, 0.1); color: var(--primary);
    padding: 8px 16px; border-radius: 50px; font-weight: 700;
    font-size: 0.9rem; margin-bottom: 1rem;
    border: 1px solid rgba(255, 122, 0, 0.2);
}
.section-title { font-size: 2.5rem; font-weight: 900; margin-bottom: 1rem; color: var(--dark); }
.section-subtitle { font-size: 1.1rem; color: var(--gray); max-width: 600px; margin: 0 auto; }

.delivery-wrapper {
    display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;
    @media (max-width: 992px) { grid-template-columns: 1fr; }
}

.delivery-map {
    background: var(--light); border-radius: 24px; padding: 20px;
    position: relative;
}
.map-svg { width: 100%; height: auto; display: block; }

.zone-circle { transition: all 0.3s ease; cursor: pointer; }
.zone-circle:hover { opacity: 0.8; stroke-width: 3; }

.restaurant-pulse {
    animation: pulse 2s ease-in-out infinite;
    transform-origin: center;
}
@keyframes pulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 0; transform: scale(2); }
}

.map-legend {
    display: flex; justify-content: center; gap: 20px;
    margin-top: 16px; flex-wrap: wrap;
}
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--gray); }
.legend-dot { width: 12px; height: 12px; border-radius: 50%; }

.delivery-info { display: flex; flex-direction: column; gap: 16px; }

.zone-card {
    display: flex; gap: 16px; padding: 20px;
    background: var(--light); border-radius: 16px;
    transition: all 0.3s ease; border: 2px solid transparent;

    &.active { border-color: var(--primary); transform: translateX(8px); }
}
.zone-color { width: 6px; border-radius: 3px; flex-shrink: 0; }
.zone-details { flex: 1; }
.zone-details h4 { font-size: 1.1rem; font-weight: 700; margin-bottom: 10px; color: var(--dark); }
.zone-meta { display: flex; gap: 16px; flex-wrap: wrap; }
.meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.9rem; color: var(--gray); }
.meta-item i { color: var(--primary); }

.delivery-features {
    display: grid; grid-template-columns: 1fr; gap: 12px;
    margin-top: 8px; padding-top: 16px;
    border-top: 1px dashed rgba(0,0,0,0.1);
}
.feature {
    display: flex; gap: 12px; align-items: center;

    i {
        width: 40px; height: 40px; border-radius: 10px;
        background: rgba(255, 122, 0, 0.1); color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }

    strong { display: block; font-size: 0.95rem; color: var(--dark); margin-bottom: 2px; }
    span { font-size: 0.85rem; color: var(--gray); }
}
</style>
