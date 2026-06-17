<template>
    <div class="partners-page pb-5">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="partners-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <h2 class="hero-title">Партнёры</h2>
                <p class="hero-subtitle">
                    {{ filteredPartners.length }}
                    {{ pluralize(filteredPartners.length, 'партнёр', 'партнёра', 'партнёров') }}
                    доступно
                </p>
            </div>
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
                        placeholder="Поиск партнёров..."
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
                        @select="selectPartner"
                    />
                </div>

                <!-- Остальные партнёры -->
                <div class="partners-grid">
                    <PartnerCard
                        v-for="partner in filteredPartners"
                        :key="partner.id"
                        :partner="partner"
                        :is-favorite="isFavorite(partner.id)"
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
            filter: 'all', // 'all' | 'favorites'
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

        // Активные партнёры (исключая "своего")
        activePartners() {
            return (this.partnerList || []).filter(p => p.is_active);
        },

        // Избранные партнёры
        favoritePartners() {
            return this.activePartners.filter(p => this.favoriteIds.includes(p.id));
        },

        // Отфильтрованный список
        filteredPartners() {
            let list = this.filter === 'favorites' ? this.favoritePartners : this.activePartners;

            // Поиск
            if (this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase();
                list = list.filter(p =>
                    p.name?.toLowerCase().includes(query) ||
                    p.address?.toLowerCase().includes(query) ||
                    p.description?.toLowerCase().includes(query)
                );
            }

            // Сортировка: избранные сверху
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
        this.loadInitialData();
    },

    methods: {
        async loadInitialData() {
            // Если есть локальные данные — используем их
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
.partners-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO
   ========================================== */
.partners-hero {
    position: relative;
    padding: 32px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.hero-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0;
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

/* Табы фильтров */
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
   ЗАГРУЗКА (SKELETON)
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

.partners-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.4rem;
    }

    .filter-tab {
        font-size: 0.8rem;
        padding: 8px 10px;
    }

    .filter-tab span:not(.tab-count) {
        display: none;
    }
}
</style>
