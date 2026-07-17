<template>
    <section class="shop-categories" ref="section" v-if="hasCategories">
        <div class="container">
            <div class="categories-wrapper">
                <!-- Кнопка "Все" фиксированная слева -->
                <button class="show-all-btn" @click="showModal = true">
                    <i class="fa-solid fa-grip"></i>
                    <span>Все</span>
                </button>

                <!-- 🆕 SWIPER СЛАЙДЕР -->
                <div class="swiper-container">
                    <swiper
                        :modules="modules"
                        :slides-per-view="'auto'"
                        :space-between="12"
                        :navigation="{
                            nextEl: '.swiper-button-next-custom',
                            prevEl: '.swiper-button-prev-custom',
                            disabledClass: 'swiper-button-disabled'
                        }"
                        :breakpoints="{
                            320: { slidesPerView: 'auto', spaceBetween: 8 },
                            768: { slidesPerView: 'auto', spaceBetween: 12 }
                        }"
                        class="categories-swiper"
                    >
                        <!-- Кнопка "Все товары" -->
                        <swiper-slide class="category-slide">
                            <button
                                class="category-btn"
                                :class="{ 'active': activeId === 'all' }"
                                @click="scrollToCategory('all')"
                            >
                                <i class="fa-solid fa-grid-2"></i>
                                <span>Все товары</span>
                            </button>
                        </swiper-slide>

                        <!-- Категории -->
                        <swiper-slide
                            v-for="cat in categoriesWithProducts"
                            :key="cat.id"
                            class="category-slide"
                        >
                            <button
                                class="category-btn"
                                :class="{ 'active': activeId === cat.id }"
                                @click="scrollToCategory(cat.id)"
                            >
                                <i :class="cat.icon || 'fa-solid fa-utensils'"></i>
                                <span>{{ cat.name }}</span>
                            </button>
                        </swiper-slide>
                    </swiper>

                    <!-- 🆕 Кастомные стрелки навигации -->
                    <button class="swiper-button-prev-custom">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button class="swiper-button-next-custom">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Модальное окно -->
        <ShopCategoriesModal
            v-if="showModal"
            :categories="shopStore.categories"
            @close="showModal = false"
            @select="handleModalSelect"
        />
    </section>
</template>

<script>
import { useShopLandingStore } from '@/MobileClient/stores/ShopLanding/shop';
import ShopCategoriesModal from './ShopCategoriesModal.vue';

// 🆕 Импорт Swiper и модулей
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation } from 'swiper/modules';

// 🆕 Импорт стилей Swiper
import 'swiper/css';
import 'swiper/css/navigation';

export default {
    name: "ShopCategories",
    components: {
        Swiper,
        SwiperSlide,
        ShopCategoriesModal
    },
    data() {
        return {
            shopStore: useShopLandingStore(),
            activeId: 'all',
            showModal: false,
            modules: [Navigation], // 🆕 Регистрируем модуль навигации
        }
    },
    computed: {
        categoriesWithProducts() {
            return this.shopStore.categories.filter(cat => cat.products && cat.products.length > 0);
        },
        hasCategories() {
            return this.categoriesWithProducts.length > 0;
        }
    },
    methods: {
        scrollToCategory(categoryId) {
            this.activeId = categoryId;
            this.shopStore.activeCategoryId = categoryId;

            if (categoryId === 'all') {
                const target = document.getElementById('shop-products-section');
                if (target) {
                    window.scrollTo({ top: target.offsetTop - 120, behavior: 'smooth' });
                }
                return;
            }

            const target = document.getElementById(`category-block-${categoryId}`);
            if (target) {
                window.scrollTo({ top: target.offsetTop - 120, behavior: 'smooth' });
            }
        },

        handleModalSelect(categoryId) {
            this.showModal = false;
            setTimeout(() => {
                this.scrollToCategory(categoryId);
            }, 100);
        },

        handleScroll() {
            const categories = this.categoriesWithProducts;

            for (let i = categories.length - 1; i >= 0; i--) {
                const cat = categories[i];
                const element = document.getElementById(`category-block-${cat.id}`);
                if (element) {
                    const rect = element.getBoundingClientRect();
                    if (rect.top <= 150) {
                        this.activeId = cat.id;
                        this.shopStore.activeCategoryId = cat.id;
                        return;
                    }
                }
            }

            if (window.scrollY < 400) {
                this.activeId = 'all';
                this.shopStore.activeCategoryId = 'all';
            }
        }
    },
    mounted() {
        window.addEventListener('scroll', this.handleScroll, { passive: true });
    },
    beforeUnmount() {
        window.removeEventListener('scroll', this.handleScroll);
    }
};
</script>

<style lang="scss" scoped>
.shop-categories {
    padding: 16px 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    position: sticky;
    top: 60px;
    z-index: 99;
}

.categories-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.show-all-btn {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0.7rem 1.2rem;
    background: white;
    border: 2px solid var(--primary, #ff7a00);
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    color: var(--primary, #ff7a00);
    transition: all 0.2s ease;
    white-space: nowrap;

    &:hover {
        background: var(--primary, #ff7a00);
        color: white;
    }
}

/* 🆕 КОНТЕЙНЕР SWIPER */
.swiper-container {
    flex: 1;
    position: relative;
    min-width: 0;
}

.categories-swiper {
    width: 100%;
    padding: 0 40px; /* Отступы для стрелок */
}

.category-slide {
    width: auto !important; /* Слайды по содержимому */
}

.category-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.4rem;
    background: var(--light, #f8f9fa);
    border: 2px solid transparent;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    color: var(--dark, #222);

    i {
        font-size: 1rem;
        transition: color 0.3s ease;
    }

    &:hover {
        border-color: var(--primary, #ff7a00);
        color: var(--primary, #ff7a00);
        i { color: var(--primary, #ff7a00); }
    }

    &.active {
        background: linear-gradient(135deg, var(--primary, #ff7a00) 0%, var(--primary-light, #ffb300) 100%);
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.3);
        i { color: white; }
    }
}

/* 🆕 КАСТОМНЫЕ СТРЕЛКИ НАВИГАЦИИ */
.swiper-button-prev-custom,
.swiper-button-next-custom {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: white;
    border: 2px solid rgba(0, 0, 0, 0.08);
    color: var(--dark, #222);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

    &:hover:not(.swiper-button-disabled) {
        background: var(--primary, #ff7a00);
        color: white;
        border-color: var(--primary, #ff7a00);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 6px 16px rgba(255, 122, 0, 0.3);
    }

    i {
        font-size: 0.9rem;
    }
}

.swiper-button-prev-custom {
    left: 0;
}

.swiper-button-next-custom {
    right: 0;
}

/* 🆕 Скрываем стрелки, если нечего скроллить */
.swiper-button-disabled {
    opacity: 0;
    pointer-events: none;
}

@media (max-width: 576px) {
    .show-all-btn span {
        display: none;
    }

    .show-all-btn {
        padding: 0.7rem;
    }

    .categories-swiper {
        padding: 0 32px; /* Меньше отступы на мобильных */
    }

    .swiper-button-prev-custom,
    .swiper-button-next-custom {
        width: 32px;
        height: 32px;

        i {
            font-size: 0.8rem;
        }
    }
}
</style>
