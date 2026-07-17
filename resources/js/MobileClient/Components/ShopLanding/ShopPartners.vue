<template>
    <section class="shop-partners-section" id="partners-section">
        <div class="container">

            <!-- Чистая, минималистичная шапка -->
            <div class="partners-header">
                <div class="header-icon">
                    <i class="fa-solid fa-store"></i>
                </div>
                <h2 class="header-title">Выберите заведение</h2>
                <p class="header-subtitle">Меню, цены и акции зависят от выбранного партнера</p>

                <!-- Поиск -->
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="search-input"
                        placeholder="Найти по названию или адресу..."
                    >
                    <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Загрузка (скелетоны) -->
            <div v-if="isLoading" class="partners-grid">
                <div v-for="i in 4" :key="i" class="partner-card-skeleton">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-line w-70"></div>
                        <div class="skeleton-line w-90"></div>
                        <div class="skeleton-line w-50"></div>
                    </div>
                </div>
            </div>

            <!-- Сетка партнеров -->
            <div v-else-if="filteredPartners.length > 0" class="partners-grid">
                <PartnerCard
                    v-for="partner in filteredPartners"
                    :key="partner.id"
                    :partner="partner"
                    :is-self="isSelfPartner(partner.id)"
                    @select="handleSelect"
                />
            </div>

            <!-- Пустое состояние -->
            <div v-else class="empty-state">
                <i class="fa-solid fa-store-slash empty-icon"></i>
                <h4>Ничего не найдено</h4>
                <p>Попробуйте изменить поисковый запрос</p>
            </div>

        </div>
    </section>
</template>

<script>
import { usePartnersStore } from "@/MobileClient/stores/Shop/partners.js";
import PartnerCard from "@/MobileClient/Components/ShopLanding/PartnerCard.vue"; // Путь к новой карточке

export default {
    name: "ShopPartners",
    components: { PartnerCard },
    emits: ['select-partner'],

    data() {
        return {
            partnerStore: usePartnersStore(),
            partnerList: [],
            isLoading: false,
            searchQuery: '',
        };
    },

    computed: {
        tenant() {
            return typeof window !== 'undefined' ? window.Tenant : null;
        },
        activePartners() {
            return (this.partnerList || []).filter(p => p.is_active);
        },
        filteredPartners() {
            if (!this.searchQuery.trim()) return this.activePartners;
            const q = this.searchQuery.toLowerCase();
            return this.activePartners.filter(p =>
                p.name?.toLowerCase().includes(q) ||
                p.address?.toLowerCase().includes(q)
            );
        }
    },

    mounted() {
        this.loadInitialData();
    },

    methods: {
        async loadInitialData() {
            if (this.tenant?.partners?.length > 0) {
                this.partnerList = this.tenant.partners;
            } else {
                await this.loadPartners();
            }
        },

        async loadPartners() {
            this.isLoading = true;
            try {
                await this.partnerStore.loadPartners({ dataObject: {}, page: 0 });
                this.partnerList = this.partnerStore.getPartners || [];
            } catch (error) {
                console.error('Ошибка загрузки партнёров:', error);
            } finally {
                this.isLoading = false;
            }
        },

        handleSelect(partner) {
            this.$emit('select-partner', partner);
        },

        isSelfPartner(partnerId) {
            // Проверка, является ли партнер "основным" (если у вас есть такая логика в settings)
            return this.tenant?.settings?.partners?.id === partnerId;
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-partners-section {
    padding: 60px 0 80px;
    background: var(--light, #fffdf8);
}

/* --- ШАПКА --- */
.partners-header {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 50px;
}

.header-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 20px;
    box-shadow: 0 10px 30px rgba(255, 122, 0, 0.2);
}

.header-title {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--dark);
    margin-bottom: 12px;
}

.header-subtitle {
    font-size: 1.1rem;
    color: var(--gray);
    margin-bottom: 32px;
    line-height: 1.5;
}

/* --- ПОИСК --- */
.search-box {
    position: relative;
    max-width: 500px;
    margin: 0 auto;

    .search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
        font-size: 1rem;
    }

    .search-input {
        width: 100%;
        padding: 16px 50px 16px 48px;
        border: 2px solid rgba(0,0,0,0.06);
        border-radius: 16px;
        font-size: 1rem;
        background: white;
        transition: all 0.3s ease;

        &:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 122, 0, 0.1);
        }
    }

    .search-clear {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--light);
        border: none;
        color: var(--gray);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;

        &:hover {
            background: var(--gray);
            color: white;
        }
    }
}

/* --- СЕТКА (3-4 в ряд) --- */
.partners-grid {
    display: grid;
    gap: 24px;
    grid-template-columns: repeat(4, 1fr); /* 4 в ряд на больших экранах */

    @media (max-width: 1200px) {
        grid-template-columns: repeat(3, 1fr); /* 3 в ряд */
    }
    @media (max-width: 900px) {
        grid-template-columns: repeat(2, 1fr); /* 2 в ряд */
    }
    @media (max-width: 576px) {
        grid-template-columns: 1fr; /* 1 в ряд на мобильных */
    }
}

/* --- СКЕЛЕТОНЫ --- */
.partner-card-skeleton {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.04);
}

.skeleton-image {
    height: 180px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-content {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-line {
    height: 14px;
    border-radius: 8px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
.skeleton-line.w-70 { width: 70%; }
.skeleton-line.w-90 { width: 90%; }
.skeleton-line.w-50 { width: 50%; }

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* --- ПУСТОЕ СОСТОЯНИЕ --- */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);

    .empty-icon {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    h4 { font-size: 1.2rem; color: var(--dark); margin-bottom: 8px; }
}
</style>
