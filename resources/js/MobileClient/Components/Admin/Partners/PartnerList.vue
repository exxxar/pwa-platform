<template>
    <div class="partner-list-page">

        <!-- ========================================== -->
        <!-- ШАПКА С ПОИСКОМ И ФИЛЬТРАМИ -->
        <!-- ========================================== -->
        <div class="list-header">
            <div class="header-top">
                <h3 class="header-title">
                    <i class="fa-solid fa-users"></i>
                    Партнёры
                    <span class="header-count">{{ filteredPartners.length }}</span>
                </h3>
                <button class="refresh-btn" @click="refreshPartners" :disabled="isLoading">
                    <i class="fa-solid fa-rotate" :class="{ 'fa-spin': isLoading }"></i>
                </button>
            </div>

            <!-- Поиск -->
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Поиск по названию..."
                    class="search-input"
                >
                <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- 🆕 Фильтры по тегам -->
            <div class="tags-filters-row" v-if="availableTagsWithCount.length > 0">
                <button
                    class="tag-filter-btn"
                    :class="{ 'is-active': activeTag === null }"
                    @click="activeTag = null"
                >
                    <i class="fa-solid fa-tags"></i>
                    <span>Все теги</span>
                </button>
                <button
                    v-for="tagData in availableTagsWithCount"
                    :key="tagData.tag"
                    class="tag-filter-btn"
                    :class="{ 'is-active': activeTag === tagData.tag }"
                    @click="activeTag = activeTag === tagData.tag ? null : tagData.tag"
                >
                    <span class="tag-name">{{ tagData.tag }}</span>
                    <span class="tag-count">{{ tagData.count }}</span>
                </button>
            </div>

            <!-- Фильтры по статусу -->
            <div class="filters-row">
                <button
                    v-for="filter in filters"
                    :key="filter.key"
                    class="filter-btn"
                    :class="{ 'is-active': activeFilter === filter.key }"
                    @click="activeFilter = filter.key"
                >
                    <i :class="filter.icon"></i>
                    <span>{{ filter.label }}</span>
                    <span class="filter-count">{{ filter.count }}</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА (SKELETON) -->
        <!-- ========================================== -->
        <div v-if="isLoading && partners.length === 0" class="skeleton-list">
            <div v-for="i in 3" :key="i" class="skeleton-card">
                <div class="skeleton-icon shimmer"></div>
                <div class="skeleton-content">
                    <div class="skeleton-title shimmer"></div>
                    <div class="skeleton-meta shimmer"></div>
                </div>
                <div class="skeleton-actions">
                    <div class="skeleton-btn shimmer"></div>
                    <div class="skeleton-btn shimmer"></div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ПАРТНЕРОВ -->
        <!-- ========================================== -->
        <div v-else-if="filteredPartners.length > 0" class="partners-list">

            <!-- Группировка: Активные -->
            <div v-if="activePartnersFiltered.length > 0" class="partners-group">
                <div class="group-header">
                    <div class="group-indicator active"></div>
                    <span class="group-title">Активные</span>
                    <span class="group-count">{{ activePartnersFiltered.length }}</span>
                </div>
                <div class="partners-grid">
                    <div
                        v-for="partner in activePartnersFiltered"
                        :key="'active-' + partner.id"
                        class="partner-card active"
                    >
                        <PartnerCard
                            :partner="partner"
                            @edit="selectPartner"
                            @products="selectPartnerForProductObserve"
                            @remove="selectPartnerForRemove"
                            @toggle-active="handleToggleActive"
                        />
                    </div>
                </div>
            </div>

            <!-- Группировка: Неактивные -->
            <div v-if="inactivePartnersFiltered.length > 0" class="partners-group">
                <div class="group-header">
                    <div class="group-indicator inactive"></div>
                    <span class="group-title">Неактивные</span>
                    <span class="group-count">{{ inactivePartnersFiltered.length }}</span>
                </div>
                <div class="partners-grid">
                    <div
                        v-for="partner in inactivePartnersFiltered"
                        :key="'inactive-' + partner.id"
                        class="partner-card inactive"
                    >
                        <PartnerCard
                            :partner="partner"
                            @edit="selectPartner"
                            @products="selectPartnerForProductObserve"
                            @remove="selectPartnerForRemove"
                            @toggle-active="handleToggleActive"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- ПУСТОЕ СОСТОЯНИЕ -->
        <!-- ========================================== -->
        <div v-else class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-handshake"></i>
            </div>
            <h3>Партнёры не найдены</h3>
            <p v-if="searchQuery || activeFilter !== 'all' || activeTag">
                Попробуйте изменить параметры поиска
            </p>
            <p v-else>Добавьте первого партнера</p>
            <button v-if="searchQuery || activeFilter !== 'all' || activeTag" class="reset-filters-btn" @click="resetFilters">
                <i class="fa-solid fa-rotate-left"></i>
                Сбросить фильтры
            </button>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: НАСТРОЙКА ПАРТНЕРА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showConfigModal" class="modal-overlay" @click.self="closeConfigModal">
                <div class="modal-container config-modal">
                    <div class="modal-header">
                        <button class="modal-close" @click="closeConfigModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <div>
                                <h3 class="modal-title">Настройка партнёра</h3>
                                <p class="modal-subtitle">{{ selected?.title }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <ConfigPartnerForm
                            v-if="selected"
                            :initial-data="selected"
                            @success="onConfigSuccess"
                        />
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ТОВАРЫ ПАРТНЕРА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showProductsModal" class="modal-overlay" @click.self="closeProductsModal">
                <div class="modal-container products-modal">
                    <div class="modal-header">
                        <button class="modal-close" @click="closeProductsModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <div class="modal-header-content">
                            <div class="modal-icon products">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <div>
                                <h3 class="modal-title">Товары партнёра</h3>
                                <p class="modal-subtitle">{{ selected?.title }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <PartnerProductList
                            v-if="selected"
                            :partner="selected"
                        />
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showRemoveModal" class="modal-overlay" @click.self="closeRemoveModal">
                <div class="modal-container confirm-modal">
                    <div class="confirm-content">
                        <div class="confirm-icon">
                            <i class="fa-solid fa-trash"></i>
                        </div>
                        <h4>Удалить партнёра?</h4>
                        <p>
                            Партнёр <strong>«{{ selected?.title }}»</strong> будет удалён.
                            Это действие нельзя отменить.
                        </p>
                        <div class="confirm-actions">
                            <button class="btn-cancel" @click="closeRemoveModal">
                                Отмена
                            </button>
                            <button
                                class="btn-danger"
                                @click="removePartner"
                                :disabled="isRemoving"
                            >
                                <span v-if="isRemoving" class="spinner-small"></span>
                                <template v-else>
                                    <i class="fa-solid fa-trash"></i>
                                    Удалить
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>
import { usePartners } from '@/MobileClient/Composables/usePartners.js'
import PartnerCard from '@/MobileClient/Components/Admin/Partners/PartnerCard.vue'
import ConfigPartnerForm from '@/MobileClient/Components/Admin/Partners/ConfigPartnerForm.vue'
import PartnerProductList from '@/MobileClient/Components/Admin/Partners/PartnerProductList.vue'

export default {
    name: 'PartnerList',

    components: {
        PartnerCard,
        ConfigPartnerForm,
        PartnerProductList,
    },

    setup() {
        const partners = usePartners()
        return { ...partners }
    },

    data() {
        return {
            searchQuery: '',
            activeFilter: 'all',
            activeTag: null, // 🆕 Активный тег для фильтрации
            selected: null,
            showConfigModal: false,
            showProductsModal: false,
            showRemoveModal: false,
            isRemoving: false,
        }
    },

    computed: {
        filters() {
            return [
                {
                    key: 'all',
                    label: 'Все',
                    icon: 'fa-solid fa-globe',
                    count: this.partners.length
                },
                {
                    key: 'active',
                    label: 'Активные',
                    icon: 'fa-solid fa-circle-check',
                    count: this.partners.filter(p => p.is_active).length
                },
                {
                    key: 'inactive',
                    label: 'Неактивные',
                    icon: 'fa-solid fa-circle-xmark',
                    count: this.partners.filter(p => !p.is_active).length
                },
            ]
        },

        // 🆕 Получить все уникальные теги с подсчетом количества партнеров
        availableTagsWithCount() {
            const tagCounts = {}

            this.partners.forEach(partner => {
                if (Array.isArray(partner.tags)) {
                    partner.tags.forEach(tag => {
                        tagCounts[tag] = (tagCounts[tag] || 0) + 1
                    })
                }
            })

            // Преобразуем в массив и сортируем по популярности
            return Object.entries(tagCounts)
                .map(([tag, count]) => ({ tag, count }))
                .sort((a, b) => b.count - a.count)
        },

        filteredPartners() {
            let result = [...this.partners]

            // 🆕 Фильтр по тегу
            if (this.activeTag) {
                result = result.filter(p =>
                    Array.isArray(p.tags) && p.tags.includes(this.activeTag)
                )
            }

            // Фильтр по статусу
            if (this.activeFilter === 'active') {
                result = result.filter(p => p.is_active)
            } else if (this.activeFilter === 'inactive') {
                result = result.filter(p => !p.is_active)
            }

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase()
                result = result.filter(p =>
                    p.title?.toLowerCase().includes(query) ||
                    p.description?.toLowerCase().includes(query)
                )
            }

            return result
        },

        activePartnersFiltered() {
            return this.filteredPartners.filter(p => p.is_active)
        },

        inactivePartnersFiltered() {
            return this.filteredPartners.filter(p => !p.is_active)
        },
    },

    async mounted() {
        if (!this.isHydrated) {
            await this.loadPartners()
        }
    },

    methods: {
        // ==========================================
        // ДЕЙСТВИЯ
        // ==========================================

        async refreshPartners() {
            try {
                await this.loadPartners()
                this.$notify?.({
                    title: 'Успех',
                    text: 'Список обновлён',
                    type: 'success',
                })
            } catch (err) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось обновить список',
                    type: 'error',
                })
            }
        },

        async handleToggleActive(partner) {
            try {
                await this.toggleActive(partner.id)
                this.$notify?.({
                    title: 'Успех',
                    text: partner.is_active ? 'Партнёр активирован' : 'Партнёр деактивирован',
                    type: 'success',
                })
            } catch (err) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось изменить статус',
                    type: 'error',
                })
            }
        },

        resetFilters() {
            this.searchQuery = ''
            this.activeFilter = 'all'
            this.activeTag = null // 🆕 Сбрасываем и тег
        },

        // ==========================================
        // МОДАЛКИ
        // ==========================================

        selectPartner(partner) {
            this.selected = { ...partner }
            this.showConfigModal = true
            document.body.style.overflow = 'hidden'
        },

        closeConfigModal() {
            this.showConfigModal = false
            this.selected = null
            document.body.style.overflow = ''
        },

        onConfigSuccess() {
            this.closeConfigModal()
            this.loadPartners()
            this.$notify?.({
                title: 'Успех',
                text: 'Партнёр обновлён',
                type: 'success',
            })
        },

        selectPartnerForProductObserve(partner) {
            this.selected = partner
            this.showProductsModal = true
            document.body.style.overflow = 'hidden'
        },

        closeProductsModal() {
            this.showProductsModal = false
            this.selected = null
            document.body.style.overflow = ''
        },

        selectPartnerForRemove(partner) {
            this.selected = partner
            this.showRemoveModal = true
            document.body.style.overflow = 'hidden'
        },

        closeRemoveModal() {
            this.showRemoveModal = false
            this.selected = null
            document.body.style.overflow = ''
        },

        async removePartner() {
            if (!this.selected) return

            this.isRemoving = true

            try {
                await this.removePartner(this.selected.id)

                this.$notify?.({
                    title: 'Успех',
                    text: 'Партнёр удалён',
                    type: 'success',
                })

                this.closeRemoveModal()
                await this.loadPartners()
            } catch (err) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить партнёра',
                    type: 'error',
                })
            } finally {
                this.isRemoving = false
            }
        },
    },
}
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-danger: #ef4444;
$admin-warning: #f59e0b;

.partner-list-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ШАПКА С ПОИСКОМ
// ==========================================
.list-header {
    background: $admin-card-bg;
    padding: 16px;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: $admin-text;

    i {
        color: $admin-primary;
    }
}

.header-count {
    padding: 2px 10px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 700;
}

.refresh-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $admin-primary;
        color: white;
        border-color: $admin-primary;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

// ==========================================
// ПОИСК
// ==========================================
.search-box {
    position: relative;
    margin-bottom: 12px;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: $admin-text-muted;
    font-size: 0.9rem;
}

.search-input {
    width: 100%;
    padding: 12px 40px 12px 42px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 10px;
    font-size: 0.9rem;
    color: $admin-text;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        background: $admin-card-bg;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }

    &::placeholder {
        color: $admin-text-muted;
    }
}

.search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: $admin-border;
    border: none;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: all 0.2s;

    &:hover {
        background: $admin-danger;
        color: white;
    }
}

// ==========================================
// 🆕 ФИЛЬТРЫ ПО ТЕГАМ
// ==========================================
.tags-filters-row {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 8px;
    margin-bottom: 12px;

    &::-webkit-scrollbar {
        display: none;
    }
}

.tag-filter-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 16px;
    color: $admin-text-muted;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    i {
        font-size: 0.75rem;
    }

    &:hover {
        background: $admin-card-bg;
        border-color: $admin-primary;
        color: $admin-primary;
    }

    &.is-active {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;

        .tag-count {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }
    }
}

.tag-name {
    text-transform: lowercase;
}

.tag-count {
    padding: 1px 6px;
    background: $admin-border;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 700;
}

// ==========================================
// ФИЛЬТРЫ
// ==========================================
.filters-row {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;

    &::-webkit-scrollbar {
        display: none;
    }
}

.filter-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 20px;
    color: $admin-text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    i {
        font-size: 0.85rem;
    }

    &:hover {
        background: $admin-card-bg;
        border-color: $admin-primary;
        color: $admin-primary;
    }

    &.is-active {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;

        .filter-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
    }
}

.filter-count {
    padding: 1px 8px;
    background: $admin-border;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}

// ==========================================
// SKELETON ЗАГРУЗКА
// ==========================================
.skeleton-list {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.skeleton-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: $admin-bg;
    flex-shrink: 0;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-title {
    height: 16px;
    background: $admin-bg;
    border-radius: 4px;
    width: 60%;
}

.skeleton-meta {
    height: 12px;
    background: $admin-bg;
    border-radius: 4px;
    width: 40%;
}

.skeleton-actions {
    display: flex;
    gap: 8px;
}

.skeleton-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
}

.shimmer {
    background: linear-gradient(
            90deg,
            $admin-bg 0%,
            darken($admin-bg, 3%) 50%,
            $admin-bg 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// СПИСОК ПАРТНЕРОВ
// ==========================================
.partners-list {
    padding: 10px;
}

.partners-group {
    margin-bottom: 24px;

    &:last-child {
        margin-bottom: 0;
    }
}

.group-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    padding: 0 4px;
}

.group-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;

    &.active {
        background: $admin-success;
        box-shadow: 0 0 8px rgba($admin-success, 0.4);
    }

    &.inactive {
        background: $admin-text-muted;
    }
}

.group-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-text;
}

.group-count {
    padding: 2px 8px;
    background: $admin-bg;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    color: $admin-text-muted;
}

.partners-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: $admin-text-muted;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba($admin-primary, 0.1);
        color: $admin-primary;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 20px;
    }

    h3 {
        font-size: 1.2rem;
        font-weight: 700;
        color: $admin-text;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        margin-bottom: 24px;
    }
}

.reset-filters-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: $admin-primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: darken($admin-primary, 10%);
    }
}

// ==========================================
// МОДАЛКИ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid $admin-border;
    position: relative;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $admin-bg;
    border: none;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $admin-danger;
        color: white;
    }
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.modal-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, $admin-primary 0%, #2563eb 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;

    &.products {
        background: linear-gradient(135deg, $admin-success 0%, #059669 100%);
    }
}

.modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 2px;
    color: $admin-text;
}

.modal-subtitle {
    font-size: 0.85rem;
    color: $admin-text-muted;
    margin: 0;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

// ==========================================
// ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ
// ==========================================
.confirm-modal {
    max-width: 400px;
}

.confirm-content {
    padding: 32px 24px;
    text-align: center;
}

.confirm-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba($admin-danger, 0.1);
    color: $admin-danger;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 20px;
}

.confirm-content {
    h4 {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: $admin-text;
    }

    p {
        font-size: 0.9rem;
        color: $admin-text-muted;
        margin-bottom: 28px;
        line-height: 1.5;
    }
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

.btn-cancel, .btn-danger {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:active {
        transform: scale(0.98);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-cancel {
    background: $admin-bg;
    color: $admin-text;
    border: 1px solid $admin-border;

    &:hover {
        background: $admin-border;
    }
}

.btn-danger {
    background: $admin-danger;
    color: white;

    &:hover {
        background: darken($admin-danger, 10%);
    }
}

.spinner-small {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.modal-fade-enter-active {
    transition: opacity 0.3s ease;

    .modal-container {
        animation: modalSlideUp 0.3s ease;
    }
}

.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .modal-overlay {
        padding: 0;
    }

    .modal-container {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
}

@media (min-width: 768px) {
    .partners-list {
        max-width: 900px;
        margin: 0 auto;
        padding: 24px;
    }
}
</style>
