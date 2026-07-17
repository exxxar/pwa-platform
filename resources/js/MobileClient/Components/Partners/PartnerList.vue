<template>
    <div class="partners-page pb-5">

        <!-- ========================================== -->
        <!-- 🆕 HERO СЕКЦИЯ С АНИМАЦИЕЙ -->
        <!-- ========================================== -->
        <div class="partners-hero">
            <!-- Анимированный фон -->
            <div class="hero-bg">
                <div class="aurora-layer"></div>
                <div class="aurora-layer delay-1"></div>
                <div class="aurora-layer delay-2"></div>
            </div>

            <!-- Плавающие фигуры -->
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
                <div class="shape shape-4"></div>
                <div class="shape shape-5"></div>
            </div>

            <!-- Сетка точек -->
            <div class="dot-grid"></div>

            <!-- Контент -->
            <div class="hero-content">
                <!-- Анимированная иконка -->
                <div class="hero-icon-wrapper">
                    <div class="hero-icon-ring"></div>
                    <div class="hero-icon-ring ring-2"></div>
                    <div class="hero-icon">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <!-- Декоративные искры -->
                    <div class="sparkle sparkle-1"></div>
                    <div class="sparkle sparkle-2"></div>
                    <div class="sparkle sparkle-3"></div>
                </div>

                <!-- Заголовок с градиентом -->
                <h2 class="hero-title">
                    <span class="title-word word-1">Заведения-партнеры</span>
                    <span class="title-decoration"></span>
                </h2>

                <!-- Подзаголовок -->
                <p class="hero-subtitle">
                    Найдите лучшие магазины и сервисы рядом с вами
                </p>

                <!-- Статистика -->
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-value">
                            <span class="stat-number">{{ filteredPartners.length }}</span>
                        </div>
                        <div class="stat-label">
                            {{ pluralize(filteredPartners.length, 'заведение', 'заведения', 'заведений') }}
                        </div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <i class="fa-solid fa-location-dot stat-icon"></i>
                        </div>
                        <div class="stat-label">Рядом с вами</div>
                    </div>
                </div>

                <!-- Переключатель вида -->
                <div class="view-switcher">
                    <button
                        class="view-btn"
                        :class="{ 'active': viewMode === 'list' }"
                        @click="setViewMode('list')"
                        title="Список"
                    >
                        <i class="fa-solid fa-list"></i>
                    </button>
                    <button
                        class="view-btn"
                        :class="{ 'active': viewMode === 'grid' }"
                        @click="setViewMode('grid')"
                        title="Карточки"
                    >
                        <i class="fa-solid fa-grip"></i>
                    </button>
                </div>
            </div>

            <div class="hero-bottom-fade"></div>
        </div>

        <div class="container px-3">

            <!-- ========================================== -->
            <!-- ФИЛЬТРЫ -->
            <!-- ========================================== -->
            <div class="filters-wrapper">
                <!-- Поиск -->
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="search-input"
                        placeholder="Поиск заведений..."
                    >
                    <button
                        v-if="searchQuery"
                        class="search-clear"
                        @click="searchQuery = ''"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Переключатель: Все / Избранное -->
                <div class="filter-tabs">
                    <button
                        class="filter-tab"
                        :class="{ 'active': filter === 'all' }"
                        @click="filter = 'all'"
                    >
                        <i class="fa-solid fa-globe"></i>
                        <span>Все</span>
                        <span class="tab-count">{{ activePartners.length }}</span>
                    </button>
                    <button
                        class="filter-tab"
                        :class="{ 'active': filter === 'favorites' }"
                        @click="filter = 'favorites'"
                    >
                        <i class="fa-solid fa-heart"></i>
                        <span>Избранное</span>
                        <span class="tab-count">{{ favoritePartners.length }}</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ЗАГРУЗКА -->
            <!-- ========================================== -->
            <div v-if="isLoading" class="loading-state">
                <div v-for="i in 4" :key="i" class="skeleton-card">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-line w-60"></div>
                        <div class="skeleton-line w-40"></div>
                        <div class="skeleton-line w-80"></div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ПУСТОЕ СОСТОЯНИЕ -->
            <!-- ========================================== -->
            <div v-else-if="filteredPartners.length === 0" class="empty-state">
                <div class="empty-icon-wrapper">
                    <div class="empty-icon">
                        <i :class="filter === 'favorites' ? 'fa-solid fa-heart' : 'fa-solid fa-handshake'"></i>
                    </div>
                </div>
                <h5 class="empty-title">
                    {{ searchQuery
                    ? 'Ничего не найдено'
                    : filter === 'favorites'
                        ? 'Нет избранных'
                        : 'Нет партнёров' }}
                </h5>
                <p class="empty-text">
                    {{ searchQuery
                    ? `По запросу "${searchQuery}" партнёров не найдено`
                    : filter === 'favorites'
                        ? 'Добавьте партнёров в избранное, нажав на сердечко'
                        : 'Партнёры скоро появятся' }}
                </p>
                <button v-if="searchQuery || filter === 'favorites'" class="empty-btn" @click="resetFilters">
                    <i class="fa-solid fa-rotate-left me-2"></i>
                    Сбросить фильтры
                </button>
            </div>

            <!-- ========================================== -->
            <!-- СПИСОК ПАРТНЁРОВ -->
            <!-- ========================================== -->
            <div v-else class="partners-list">

                <!-- "Свой" партнёр (если включено) -->
                <div v-if="settings.partners?.display_self && filter === 'all' && !searchQuery" class="self-partner-section">
                    <div class="section-label">
                        <i class="fa-solid fa-star"></i>
                        <span>Основной партнёр</span>
                    </div>
                    <PartnerCard
                        :partner="settings.partners"
                        :is-self="true"
                        :view-mode="viewMode"
                        @select="selectPartner"
                    />
                </div>

                <!-- Остальные партнёры -->
                <div class="partners-grid" :class="{ 'grid-view': viewMode === 'grid' }">
                    <PartnerCard
                        v-for="partner in filteredPartners"
                        :key="partner.id"
                        :partner="partner"
                        :is-favorite="isFavorite(partner.id)"
                        :view-mode="viewMode"
                        @select="selectPartner"
                        @toggle-favorite="toggleFavorite"
                    />
                </div>

            </div>

        </div>
    </div>
</template>

<script>
import { usePartnersStore } from "@/MobileClient/stores/Shop/partners.js";
import PartnerCard from "@/MobileClient/Components/Partners/PartnerCard.vue";

export default {
    name: "PartnerList",

    components: {
        PartnerCard,
    },

    emits: ['select'],

    setup() {
        const partnerStore = usePartnersStore();
        return { partnerStore };
    },

    data() {
        return {
            partnerList: [],
            isLoading: false,
            searchQuery: '',
            filter: 'all',
            viewMode: 'list', // 🆕 'list' | 'grid'
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        favoriteIds() {
            return this.self?.settings?.fav_partners || [];
        },

        activePartners() {
            return (this.partnerList || []).filter(p => p.is_active);
        },

        favoritePartners() {
            return this.activePartners.filter(p => this.favoriteIds.includes(p.id));
        },

        filteredPartners() {
            let list = this.filter === 'favorites' ? this.favoritePartners : this.activePartners;

            if (this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase();
                list = list.filter(p =>
                    p.name?.toLowerCase().includes(query) ||
                    p.address?.toLowerCase().includes(query) ||
                    p.description?.toLowerCase().includes(query)
                );
            }

            return [...list].sort((a, b) => {
                const aFav = this.favoriteIds.includes(a.id);
                const bFav = this.favoriteIds.includes(b.id);

                if (aFav && !bFav) return -1;
                if (!aFav && bFav) return 1;

                return (b.order_position || 0) - (a.order_position || 0);
            });
        },
    },

    mounted() {
        this.loadViewMode();
        this.loadInitialData();
    },

    methods: {
        // 🆕 Загрузка режима отображения из localStorage
        loadViewMode() {
            const saved = localStorage.getItem('partners_view_mode');
            if (saved === 'list' || saved === 'grid') {
                this.viewMode = saved;
            }
        },

        // 🆕 Установка режима отображения
        setViewMode(mode) {
            if (this.viewMode === mode) return;
            this.viewMode = mode;
            localStorage.setItem('partners_view_mode', mode);
        },

        async loadInitialData() {
            if (this.tenant?.partners?.length > 0) {
                this.partnerList = this.tenant.partners;
            } else {
                await this.loadPartners();
            }
        },

        async loadPartners(pageIndex = 0) {
            this.isLoading = true;
            try {
                await this.partnerStore.loadPartners({
                    dataObject: {},
                    page: pageIndex,
                });
                this.partnerList = this.partnerStore.getPartners || [];
            } catch (error) {
                console.error('Ошибка загрузки партнёров:', error);
            } finally {
                this.isLoading = false;
            }
        },

        selectPartner(partner) {
            this.$emit('select', partner);
        },

        async toggleFavorite(partnerId) {
            try {
                await this.partnerStore.togglePartnerFavorite({ partner_id: partnerId });
                await this.loadPartners();
            } catch (error) {
                console.error('Ошибка изменения избранного:', error);
            }
        },

        isFavorite(partnerId) {
            return this.favoriteIds.includes(partnerId);
        },

        resetFilters() {
            this.searchQuery = '';
            this.filter = 'all';
        },

        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
    },
};
</script>

<style scoped>


/* ==========================================
   🆕 HERO С АНИМАЦИЕЙ
   ========================================== */
.partners-hero {
    position: relative;
    min-height: 420px;
    padding: 60px 24px 100px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    color: white;
    text-align: center;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

/*  Aurora-фон (переливающийся градиент) */
.hero-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.hero-bottom-fade {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 120px;
    background:
        linear-gradient(
            to bottom,
            transparent 0%,
            rgba(255, 255, 255, 0.3) 50%,
            var(--bs-body-bg, #ffffff) 100%
        );
    pointer-events: none;
}

.aurora-layer {
    position: absolute;
    inset: -50%;
    background: conic-gradient(
        from 0deg at 50% 50%,
        #667eea 0deg,
        #764ba2 60deg,
        #f093fb 120deg,
        #f5576c 180deg,
        #4facfe 240deg,
        #667eea 360deg
    );
    opacity: 0.4;
    animation: auroraRotate 20s linear infinite;
    filter: blur(60px);
}

.aurora-layer.delay-1 {
    animation-duration: 25s;
    animation-direction: reverse;
    opacity: 0.3;
    background: conic-gradient(
        from 180deg at 50% 50%,
        #43e97b 0deg,
        #38f9d7 90deg,
        #fa709a 180deg,
        #fee140 270deg,
        #43e97b 360deg
    );
}

.aurora-layer.delay-2 {
    animation-duration: 30s;
    opacity: 0.2;
    background: conic-gradient(
        from 90deg at 50% 50%,
        #a8edea 0deg,
        #fed6e3 120deg,
        #d299c2 240deg,
        #fef9d7 360deg
    );
}

@keyframes auroraRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* 🆕 Плавающие фигуры */
.floating-shapes {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.shape {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.shape-1 {
    width: 120px;
    height: 120px;
    top: 10%;
    left: 10%;
    animation: float1 15s ease-in-out infinite;
}

.shape-2 {
    width: 80px;
    height: 80px;
    top: 60%;
    right: 15%;
    border-radius: 20%;
    animation: float2 18s ease-in-out infinite;
    background: rgba(255, 193, 7, 0.15);
}

.shape-3 {
    width: 60px;
    height: 60px;
    bottom: 20%;
    left: 20%;
    animation: float3 12s ease-in-out infinite;
    background: rgba(255, 255, 255, 0.15);
}

.shape-4 {
    width: 100px;
    height: 100px;
    top: 20%;
    right: 25%;
    border-radius: 30%;
    animation: float1 20s ease-in-out infinite reverse;
    background: rgba(16, 185, 129, 0.15);
}

.shape-5 {
    width: 40px;
    height: 40px;
    top: 50%;
    left: 50%;
    animation: float2 10s ease-in-out infinite;
}

@keyframes float1 {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

@keyframes float2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-40px, 30px) scale(1.2); }
}

@keyframes float3 {
    0%, 100% { transform: translate(0, 0); }
    25% { transform: translate(20px, -20px); }
    50% { transform: translate(40px, 0); }
    75% { transform: translate(20px, 20px); }
}

/* 🆕 Сетка точек */
.dot-grid {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.15) 1px, transparent 1px);
    background-size: 30px 30px;
    opacity: 0.5;
}

/* 🆕 Контент */
.hero-content {
    position: relative;
    z-index: 10;
    max-width: 600px;
    animation: contentFadeIn 0.8s ease-out;
}

@keyframes contentFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 🆕 Анимированная иконка */
.hero-icon-wrapper {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto 24px;
    animation: iconAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s backwards;
}

@keyframes iconAppear {
    from {
        opacity: 0;
        transform: scale(0.5) rotate(-180deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

.hero-icon {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(255, 255, 255, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.2),
        inset 0 1px 1px rgba(255, 255, 255, 0.3);
    animation: iconPulse 3s ease-in-out infinite;
}

@keyframes iconPulse {
    0%, 100% {
        transform: scale(1);
        box-shadow:
            0 8px 32px rgba(0, 0, 0, 0.2),
            inset 0 1px 1px rgba(255, 255, 255, 0.3);
    }
    50% {
        transform: scale(1.05);
        box-shadow:
            0 12px 40px rgba(0, 0, 0, 0.3),
            inset 0 1px 1px rgba(255, 255, 255, 0.4);
    }
}

.hero-icon-ring {
    position: absolute;
    inset: -10px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3);
    animation: ringExpand 3s ease-out infinite;
}

.hero-icon-ring.ring-2 {
    animation-delay: 1.5s;
}

@keyframes ringExpand {
    0% {
        transform: scale(0.8);
        opacity: 1;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

/* 🆕 Искры */
.sparkle {
    position: absolute;
    width: 6px;
    height: 6px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
    animation: sparkleAnim 2s ease-in-out infinite;
}

.sparkle-1 {
    top: 10%;
    left: 10%;
    animation-delay: 0s;
}

.sparkle-2 {
    top: 20%;
    right: 15%;
    animation-delay: 0.7s;
}

.sparkle-3 {
    bottom: 15%;
    left: 20%;
    animation-delay: 1.4s;
}

@keyframes sparkleAnim {
    0%, 100% {
        opacity: 0;
        transform: scale(0);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
}

/* 🆕 Заголовок */
.hero-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 12px;
    position: relative;
    display: inline-block;
    animation: titleSlideIn 0.8s ease-out 0.4s backwards;
}

@keyframes titleSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.title-word {
    background: linear-gradient(135deg, #ffffff 0%, #fef3c7 50%, #ffffff 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: textShine 3s linear infinite;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

@keyframes textShine {
    to {
        background-position: 200% center;
    }
}

.title-decoration {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, transparent, #fbbf24, transparent);
    border-radius: 2px;
    animation: decorationGrow 1s ease-out 0.8s backwards;
}

@keyframes decorationGrow {
    from {
        width: 0;
        opacity: 0;
    }
    to {
        width: 60px;
        opacity: 1;
    }
}

/* 🆕 Подзаголовок */
.hero-subtitle {
    font-size: 1.1rem;
    opacity: 0.95;
    margin-bottom: 32px;
    line-height: 1.5;
    animation: subtitleFadeIn 0.8s ease-out 0.6s backwards;
}

@keyframes subtitleFadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 0.95;
        transform: translateY(0);
    }
}

/* 🆕 Статистика */
.hero-stats {
    display: inline-flex;
    align-items: center;
    gap: 24px;
    padding: 16px 32px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    margin-bottom: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    animation: statsAppear 0.8s ease-out 0.8s backwards;
}

@keyframes statsAppear {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-value {
    display: flex;
    align-items: center;
    gap: 6px;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-icon {
    font-size: 1.4rem;
    color: #fbbf24;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
    font-weight: 500;
}

.stat-divider {
    width: 1px;
    height: 32px;
    background: rgba(255, 255, 255, 0.3);
}

/* 🆕 Переключатель вида */
.view-switcher {
    display: inline-flex;
    gap: 4px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 4px;
    border-radius: 12px;
    animation: switcherAppear 0.6s ease-out 1s backwards;
}

@keyframes switcherAppear {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.view-btn {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);

&:hover {
     color: white;
     background: rgba(255, 255, 255, 0.1);
     transform: scale(1.1);
 }

&.active {
     background: white;
     color: #667eea;
     box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
     transform: scale(1.05);
 }
}

/* 🆕 Волны снизу */
.hero-waves {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 80px;
    pointer-events: none;
}

.hero-waves svg {
    width: 100%;
    height: 100%;
}

.hero-waves .wave {
    fill: var(--bs-body-bg, #ffffff);
    animation: waveMove 10s ease-in-out infinite;
}

.hero-waves .wave:nth-child(2) {
    animation-delay: -2.5s;
    animation-duration: 12s;
}

.hero-waves .wave:nth-child(3) {
    animation-delay: -5s;
    animation-duration: 15s;
}

@keyframes waveMove {
    0%, 100% {
        transform: translateX(0);
    }
    50% {
        transform: translateX(-25px);
    }
}

/* ==========================================
   🆕 АДАПТИВ
   ========================================== */
@media (max-width: 768px) {
    .partners-hero {
        min-height: 380px;
        padding: 50px 20px 90px;
    }

    .hero-title {
        font-size: 2rem;
    }

    .hero-subtitle {
        font-size: 1rem;
    }

    .hero-icon-wrapper {
        width: 80px;
        height: 80px;
    }

    .hero-icon {
        font-size: 2rem;
    }

    .hero-stats {
        padding: 12px 20px;
        gap: 16px;
    }

    .stat-number {
        font-size: 1.5rem;
    }

    .shape-1, .shape-4 {
        display: none;
    }
}

@media (max-width: 480px) {
    .partners-hero {
        min-height: 340px;
        padding: 40px 16px 80px;
    }

    .hero-title {
        font-size: 1.6rem;
    }

    .hero-stats {
        flex-direction: column;
        gap: 12px;
        padding: 16px;
    }

    .stat-divider {
        width: 40px;
        height: 1px;
    }
}

/* ==========================================
   ОСТАЛЬНЫЕ СТИЛИ (без изменений)
   ========================================== */
.filters-wrapper {
    margin-top: -40px;
    position: relative;
    z-index: 20;
    margin-bottom: 20px;
}

.search-box {
    display: flex;
    align-items: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 0 14px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.search-box:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.search-icon {
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
    margin-right: 10px;
}

.search-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 14px 0;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    outline: none;
}

.search-input::placeholder {
    color: var(--bs-secondary-color);
}

.search-clear {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bs-border-color);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-clear:hover {
    background: var(--bs-primary);
    color: white;
}

.filter-tabs {
    display: flex;
    gap: 8px;
    background: var(--bs-secondary-bg, #f5f5f5);
    padding: 4px;
    border-radius: 14px;
}

.filter-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-tab:hover:not(.active) {
    color: var(--bs-body-color);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.filter-tab.active {
    background: var(--bs-body-bg);
    color: var(--bs-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.tab-count {
    padding: 2px 8px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.filter-tab.active .tab-count {
    background: var(--bs-primary);
    color: white;
}

.loading-state {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-card {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
}

.skeleton-image {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background: linear-gradient(90deg,
    var(--bs-secondary-bg) 0%,
    var(--bs-border-color) 50%,
    var(--bs-secondary-bg) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-line {
    height: 12px;
    border-radius: 6px;
    background: linear-gradient(90deg,
    var(--bs-secondary-bg) 0%,
    var(--bs-border-color) 50%,
    var(--bs-secondary-bg) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-line.w-40 { width: 40%; }
.skeleton-line.w-60 { width: 60%; }
.skeleton-line.w-80 { width: 80%; }

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.empty-state {
    text-align: center;
    padding: 60px 24px;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.empty-icon-wrapper {
    margin-bottom: 20px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto;
}

.empty-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 24px;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    background: var(--bs-primary);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.partners-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.self-partner-section {
    margin-bottom: 8px;
}

.section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.section-label i {
    color: #ffc107;
}

.partners-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.partners-grid.grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

@media (max-width: 576px) {
    .filter-tab {
        font-size: 0.8rem;
        padding: 8px 10px;
    }

    .filter-tab span:not(.tab-count) {
        display: none;
    }

    .partners-grid.grid-view {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 577px) and (max-width: 768px) {
    .partners-grid.grid-view {
        grid-template-columns: repeat(2, 1fr);
    }
}


/* 🆕 ПЕРЕКЛЮЧАТЕЛЬ ВИДА */
.view-switcher {
    display: flex;
    gap: 4px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 4px;
    border-radius: 10px;
}

.view-btn {
    width: 32px;
    height: 32px;
    border-radius: 7px;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.view-btn:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
}

.view-btn.active {
    background: white;
    color: var(--bs-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* ==========================================
   ФИЛЬТРЫ
   ========================================== */
.filters-wrapper {
    margin-top: -20px;
    position: relative;
    z-index: 2;
    margin-bottom: 20px;
}

.search-box {
    display: flex;
    align-items: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 0 14px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.search-box:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.search-icon {
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
    margin-right: 10px;
}

.search-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 14px 0;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    outline: none;
}

.search-input::placeholder {
    color: var(--bs-secondary-color);
}

.search-clear {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bs-border-color);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-clear:hover {
    background: var(--bs-primary);
    color: white;
}

.filter-tabs {
    display: flex;
    gap: 8px;
    background: var(--bs-secondary-bg, #f5f5f5);
    padding: 4px;
    border-radius: 14px;
}

.filter-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-tab:hover:not(.active) {
    color: var(--bs-body-color);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.filter-tab.active {
    background: var(--bs-body-bg);
    color: var(--bs-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.tab-count {
    padding: 2px 8px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.filter-tab.active .tab-count {
    background: var(--bs-primary);
    color: white;
}

/* ==========================================
   ЗАГРУЗКА
   ========================================== */
.loading-state {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-card {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
}

.skeleton-image {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background: linear-gradient(90deg,
    var(--bs-secondary-bg) 0%,
    var(--bs-border-color) 50%,
    var(--bs-secondary-bg) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-line {
    height: 12px;
    border-radius: 6px;
    background: linear-gradient(90deg,
    var(--bs-secondary-bg) 0%,
    var(--bs-border-color) 50%,
    var(--bs-secondary-bg) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-line.w-40 { width: 40%; }
.skeleton-line.w-60 { width: 60%; }
.skeleton-line.w-80 { width: 80%; }

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
.empty-state {
    text-align: center;
    padding: 60px 24px;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.empty-icon-wrapper {
    margin-bottom: 20px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto;
}

.empty-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 24px;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    background: var(--bs-primary);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

/* ==========================================
   СПИСОК ПАРТНЁРОВ
   ========================================== */
.partners-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.self-partner-section {
    margin-bottom: 8px;
}

.section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.section-label i {
    color: #ffc107;
}

/* 🆕 Сетка партнёров: список по умолчанию */
.partners-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* 🆕 Grid-режим: сетка карточек */
.partners-grid.grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.4rem;
    }

    .hero-subtitle-row {
        flex-direction: column;
        gap: 12px;
    }

    .filter-tab {
        font-size: 0.8rem;
        padding: 8px 10px;
    }

    .filter-tab span:not(.tab-count) {
        display: none;
    }

    /* На мобильных grid в одну колонку */
    .partners-grid.grid-view {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 577px) and (max-width: 768px) {
    .partners-grid.grid-view {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
