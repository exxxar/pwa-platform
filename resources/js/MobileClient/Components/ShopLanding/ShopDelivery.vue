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

                        <!-- Зоны доставки (используем mappedZones) -->
                        <circle
                            v-for="(zone, index) in mappedZones"
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
                        <div v-for="zone in mappedZones" :key="'legend-' + zone.id" class="legend-item">
                            <span class="legend-dot" :style="{ background: zone.borderColor }"></span>
                            <span>{{ zone.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Информация о зонах -->
                <div class="delivery-info">
                    <div
                        v-for="zone in mappedZones"
                        :key="'info-' + zone.id"
                        class="zone-card"
                        :class="{ 'active': activeZone === zone.id }"
                    >
                        <div class="zone-color" :style="{ background: zone.borderColor }"></div>
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

                    <!-- 🆕 Динамические сервисы и преимущества -->
                    <div class="delivery-features" v-if="mappedServices.length > 0">
                        <div
                            v-for="(service, index) in mappedServices"
                            :key="service.id"
                            class="feature"
                        >
                            <i :class="getServiceIcon(service.title, index)"></i>
                            <div>
                                <strong>{{ service.title }}</strong>
                                <span>{{ service.description }}</span>
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

    // Ожидаем данные от родительского компонента (который получает их из API)
    props: {
        zonesData: {
            type: Array,
            default: () => []
        },
        servicesData: {
            type: Array,
            default: () => []
        }
    },

    data() {
        return {
            activeZone: null,

            // 🛡️ Значения по умолчанию (если с бэкенда ничего не пришло)
            defaultZones: [
                { id: 1, name: 'Центр', time: '30-40 мин', price: 'Бесплатно', minOrder: 1000 },
                { id: 2, name: 'Спальные районы', time: '40-60 мин', price: '150 ₽', minOrder: 1500 },
                { id: 3, name: 'Пригород', time: '60-90 мин', price: '300 ₽', minOrder: 2000 }
            ],
            defaultServices: [
                { id: 1, title: 'Термосумки', description: 'Сохраняем температуру блюд' },
                { id: 2, title: 'Бесплатная доставка', description: 'При заказе от 2000 ₽' },
                { id: 3, title: 'Отслеживание', description: 'Курьер на карте в реальном времени' }
            ],

            // 🎨 Визуальные стили для SVG-карты (администратору не нужно думать о радиусах и HEX-кодах)
            zoneVisuals: [
                { radius: 100, color: 'rgba(16, 185, 129, 0.2)', borderColor: '#10b981' }, // Зеленый (Центр)
                { radius: 170, color: 'rgba(59, 130, 246, 0.15)', borderColor: '#3b82f6' }, // Синий (Спальные)
                { radius: 230, color: 'rgba(245, 158, 11, 0.12)', borderColor: '#f59e0b' }, // Оранжевый (Пригород)
                { radius: 280, color: 'rgba(139, 92, 246, 0.12)', borderColor: '#8b5cf6' }, // Фиолетовый (запасной)
                { radius: 330, color: 'rgba(236, 72, 153, 0.12)', borderColor: '#ec4899' }  // Розовый (запасной)
            ]
        };
    },

    computed: {
        // Объединяем данные из пропсов с дефолтными и добавляем визуальные стили для SVG
        mappedZones() {
            const source = (this.zonesData && this.zonesData.length > 0)
                ? this.zonesData
                : this.defaultZones;

            return source.map((zone, index) => {
                // Берем визуальный стиль по индексу, или дефолтный, если зон больше 5
                const visual = this.zoneVisuals[index] || {
                    radius: 100 + (index * 40),
                    color: 'rgba(100, 116, 139, 0.1)',
                    borderColor: '#64748b'
                };

                return {
                    ...zone,
                    radius: visual.radius,
                    color: visual.color,
                    borderColor: visual.borderColor
                };
            });
        },

        // Возвращаем сервисы из пропсов или дефолтные
        mappedServices() {
            return (this.servicesData && this.servicesData.length > 0)
                ? this.servicesData
                : this.defaultServices;
        }
    },

    methods: {
        // Умный подбор иконок: если название совпадает с классикой, берем её, иначе даем красивую запасную
        getServiceIcon(title, index) {
            const lowerTitle = title.toLowerCase();
            if (lowerTitle.includes('термо') || lowerTitle.includes('температур')) return 'fa-solid fa-temperature-low';
            if (lowerTitle.includes('бесплатн') || lowerTitle.includes('подарок')) return 'fa-solid fa-bell-concierge';
            if (lowerTitle.includes('отслежив') || lowerTitle.includes('карт')) return 'fa-solid fa-location-crosshairs';
            if (lowerTitle.includes('упаковк') || lowerTitle.includes('эко')) return 'fa-solid fa-leaf';

            // Запасные иконки, которые будут чередоваться для новых сервисов
            const fallbackIcons = [
                'fa-solid fa-circle-check',
                'fa-solid fa-star',
                'fa-solid fa-shield-halved',
                'fa-solid fa-truck-fast',
                'fa-solid fa-hand-holding-heart'
            ];
            return fallbackIcons[index % fallbackIcons.length];
        }
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
