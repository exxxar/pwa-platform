<template>
    <div v-if="settings" class="category-slider-container">

        <!-- ========================================== -->
        <!-- СЛАЙДЕР КАТЕГОРИЙ -->
        <!-- ========================================== -->
        <div v-if="categories.length > 0 || collections.length > 0" class="slider-wrapper">

            <!-- Кнопка поиска -->
            <button
                type="button"
                class="search-btn"
                @click="openCategoryModal"
                title="Поиск по категориям"
            >
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            <!-- Горизонтальный скролл категорий -->
            <div class="categories-scroll">

                <!-- Комбо-меню -->
                <button
                    v-if="collections.length > 0"
                    type="button"
                    class="category-pill combo-pill"
                    @click="selectCategory({ id: 'combo' })"
                >
                    <i class="fa-solid fa-burger"></i>
                    <span class="pill-text">Комбо-меню</span>
                    <span class="pill-badge combo-badge">{{ collections.length }}</span>
                </button>

                <template v-for="item in categories">
                    <button
                        v-if="item.products?.length>0"
                        :key="item.id"
                        type="button"
                        class="category-pill"
                        @click="selectCategory(item)"
                    >
                        <i class="fa-solid fa-tag"></i>
                        <span class="pill-text">{{ item.name || 'Не указано' }}</span>
                        <span class="pill-badge">{{ item.products?.length || 0 }}</span>
                    </button>
                </template>
                <!-- Категории -->


            </div>
        </div>

        <!-- Слот для кнопки "Назад" -->
        <slot name="back-btn"></slot>

        <!-- ========================================== -->
        <!-- МОДАЛКА ПОИСКА -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            id="categorySearchModal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
                <div class="modal-content search-modal">

                    <!-- Шапка -->
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="modal-title">Поиск категорий</h5>
                                <small class="text-muted">
                                    {{ categories.length }}
                                    {{ pluralize(categories.length, 'категория', 'категории', 'категорий') }}
                                </small>
                            </div>
                            <button
                                type="button"
                                class="close-btn"
                                data-bs-dismiss="modal"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Тело -->
                    <div class="modal-body">

                        <!-- Поиск -->
                        <div class="search-box">
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="search-input"
                                    placeholder="Введите название товара или категории..."
                                    autofocus
                                    @input="onSearchInput"
                                >
                                <button
                                    v-if="searchQuery"
                                    type="button"
                                    class="search-clear"
                                    @click="clearSearch"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Результаты поиска -->
                        <div v-if="searchQuery && filteredCategories.length === 0" class="no-results">
                            <div class="no-results-icon">
                                <i class="fa-solid fa-face-frown"></i>
                            </div>
                            <p class="no-results-text">
                                По запросу "<strong>{{ searchQuery }}</strong>" ничего не найдено
                            </p>
                        </div>

                        <!-- Комбо-меню (если есть и нет поиска) -->
                        <div v-if="collections.length > 0 && !searchQuery" class="categories-section">
                            <div class="section-label">
                                <i class="fa-solid fa-burger"></i>
                                <span>Комбо-меню</span>
                            </div>
                            <button
                                type="button"
                                class="category-list-item combo-item"
                                @click="selectCategory({ id: 'combo' })"
                            >
                                <div class="item-icon combo-icon">
                                    <i class="fa-solid fa-burger"></i>
                                </div>
                                <div class="item-content">
                                    <div class="item-title">Комбо-меню</div>
                                    <div class="item-subtitle">Специальные предложения</div>
                                </div>
                                <div class="item-badge combo-badge">
                                    {{ collections.length }}
                                </div>
                                <i class="fa-solid fa-chevron-right item-arrow"></i>
                            </button>
                        </div>

                        <!-- Все категории -->
                        <div class="categories-section">
                            <div class="section-label">
                                <i class="fa-solid fa-layer-group"></i>
                                <span>
                                    {{ searchQuery ? 'Результаты поиска' : 'Все категории' }}
                                </span>
                            </div>

                            <div class="categories-list">
                                <button
                                    v-for="item in filteredCategories"
                                    :key="item.id"
                                    type="button"
                                    class="category-list-item"
                                    @click="selectCategory(item)"
                                >
                                    <div class="item-icon">
                                        <i class="fa-solid fa-tag"></i>
                                    </div>
                                    <div class="item-content">
                                        <div class="item-title">{{ item.name || 'Не указано' }}</div>
                                        <div class="item-subtitle">
                                            {{ item.products?.length || 0 }}
                                            {{ pluralize(item.products?.length || 0, 'товар', 'товара', 'товаров') }}
                                        </div>
                                    </div>
                                    <div class="item-badge">
                                        {{ item.products?.length || 0 }}
                                    </div>
                                    <i class="fa-solid fa-chevron-right item-arrow"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "CategorySlider",

    props: {
        settings: {
            type: Object,
            default: null,
        },
        categories: {
            type: Array,
            default: () => [],
        },
        collections: {
            type: Array,
            default: () => [],
        },
    },

    emits: ['search', 'select'],

    data() {
        return {
            searchQuery: '',
            categoryModal: null,
        };
    },

    computed: {
        // Фильтрация категорий по поиску
        filteredCategories() {
            if (!this.searchQuery.trim()) return this.categories;

            const query = this.searchQuery.toLowerCase();
            return this.categories.filter(cat => {
                // Поиск по названию категории
                if (cat.name?.toLowerCase().includes(query)) return true;

                // Поиск по товарам в категории
                return cat.products?.some(product =>
                    product.name?.toLowerCase().includes(query)
                );
            });
        },
    },

    mounted() {
        this.$nextTick(() => {
            if (typeof bootstrap !== 'undefined') {
                const modalEl = document.getElementById('categorySearchModal');
                if (modalEl) {
                    this.categoryModal = new bootstrap.Modal(modalEl, {
                        backdrop: false,
                    });
                }
            }
        });
    },

    beforeUnmount() {
        if (this.categoryModal) {
            this.categoryModal.dispose();
        }
    },

    methods: {
        openCategoryModal() {
            this.categoryModal?.show();
            // Фокус на поле поиска после открытия
            this.$nextTick(() => {
                const input = document.querySelector('.search-input');
                if (input) input.focus();
            });
        },

        onSearchInput() {
            // Live-поиск без кнопки
            // Можно добавить debounce при необходимости
        },

        clearSearch() {
            this.searchQuery = '';
        },

        findProducts() {
            this.$emit('search', this.searchQuery);
            this.categoryModal?.hide();
        },

        selectCategory(item) {
            this.$emit('select', item);
            this.categoryModal?.hide();

            // Скролл к якорю
            let anchorId;
            if (!item) {
                anchorId = 'all-categories';
            } else if (item.id === 'combo') {
                anchorId = 'combo-menu';
            } else {
                anchorId = `category-${item.id}`;
            }

            const el = document.getElementById(anchorId);
            if (el) {
                el.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
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
.category-slider-container {
    width: 100%;
}

/* ==========================================
   СЛАЙДЕР КАТЕГОРИЙ
   ========================================== */
.slider-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    //background: var(--bs-body-bg);
    //border: 1px solid var(--bs-border-color);
    //border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

/* Кнопка поиска */
.search-btn {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.search-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(var(--bs-primary-rgb), 0.4);
}

.search-btn:active {
    transform: scale(0.95);
}

/* Горизонтальный скролл */
.categories-scroll {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 4px 0;
    flex: 1;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

.categories-scroll::-webkit-scrollbar {
    height: 4px;
}

.categories-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.categories-scroll::-webkit-scrollbar-thumb {
    background: var(--bs-border-color);
    border-radius: 2px;
}

/* Pill-кнопки категорий */
.category-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 20px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    flex-shrink: 0;
}

.category-pill:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
    transform: translateY(-1px);
}

.category-pill:active {
    transform: translateY(0);
}

.category-pill i {
    font-size: 0.8rem;
    color: var(--bs-primary);
}

.pill-text {
    font-weight: 600;
}

.pill-badge {
    padding: 2px 8px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
}

/* Комбо-меню */
.combo-pill {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
    border-color: rgba(255, 193, 7, 0.3);
    color: #b8860b;
}

.combo-pill i {
    color: #ffc107;
}

.combo-pill .pill-badge {
    background: rgba(255, 193, 7, 0.2);
    color: #b8860b;
}

.combo-pill:hover {
    border-color: #ffc107;
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 152, 0, 0.1) 100%);
}

/* ==========================================
   МОДАЛКА ПОИСКА
   ========================================== */
.search-modal {
    background: var(--bs-body-bg);
    border: none;
}

.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
    position: sticky;
    top: 0;
    z-index: 10;
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
}

.modal-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.modal-title {
    font-weight: 700;
    margin-bottom: 2px;
    color: var(--bs-body-color);
}

.close-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-body-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.close-btn:hover {
    background: #dc3545;
    color: white;
    transform: rotate(90deg);
}

/* Тело модалки */
.modal-body {
    padding: 20px;
}

/* Поле поиска */
.search-box {
    margin-bottom: 24px;
}

.search-input-wrapper {
    display: flex;
    align-items: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 0 14px;
    transition: all 0.2s ease;
}

.search-input-wrapper:focus-within {
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
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--bs-border-color);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-clear:hover {
    background: var(--bs-primary);
    color: white;
}

/* Секции */
.categories-section {
    margin-bottom: 24px;
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
    margin-bottom: 12px;
    padding: 0 4px;
}

.section-label i {
    color: var(--bs-primary);
}

/* Список категорий */
.categories-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.category-list-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    width: 100%;
}

.category-list-item:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
    transform: translateX(4px);
}

.category-list-item:active {
    transform: translateX(2px);
}

/* Комбо-меню в списке */
.combo-item {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.05) 0%, transparent 100%);
    border-color: rgba(255, 193, 7, 0.2);
}

.combo-item:hover {
    border-color: #ffc107;
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
}

.item-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.category-list-item:hover .item-icon {
    background: var(--bs-primary);
    color: white;
    transform: scale(1.05);
}

.combo-icon {
    background: rgba(255, 193, 7, 0.15);
    color: #b8860b;
}

.combo-item:hover .combo-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
}

.item-content {
    flex: 1;
    min-width: 0;
}

.item-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-subtitle {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.item-badge {
    padding: 4px 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.combo-badge {
    background: rgba(255, 193, 7, 0.2);
    color: #b8860b;
}

.item-arrow {
    color: var(--bs-secondary-color);
    font-size: 0.75rem;
    opacity: 0;
    transform: translateX(-4px);
    transition: all 0.2s ease;
}

.category-list-item:hover .item-arrow {
    opacity: 1;
    transform: translateX(0);
}

/* Нет результатов */
.no-results {
    text-align: center;
    padding: 60px 20px;
}

.no-results-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
}

.no-results-text {
    font-size: 0.95rem;
    color: var(--bs-secondary-color);
    margin: 0;
    line-height: 1.5;
}

.no-results-text strong {
    color: var(--bs-primary);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .slider-wrapper {
        padding: 8px;
    }

    .search-btn {
        width: 40px;
        height: 40px;
    }

    .category-pill {
        padding: 6px 12px;
        font-size: 0.8rem;
    }

    .category-pill i {
        font-size: 0.75rem;
    }

    .modal-header {
        padding: 12px 16px;
    }

    .modal-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .modal-title {
        font-size: 1rem;
    }

    .category-list-item {
        padding: 12px;
        gap: 12px;
    }

    .item-icon {
        width: 40px;
        height: 40px;
        font-size: 0.9rem;
    }

    .item-title {
        font-size: 0.9rem;
    }
}
</style>
