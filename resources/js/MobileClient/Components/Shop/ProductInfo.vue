<template>
    <transition name="modal-fade">
        <div v-if="isOpen" class="product-modal-overlay" @click.self="closeModal">
            <div class="product-modal-sheet">

                <!-- ========================================== -->
                <!-- ШАПКА С ЗАКРЫТИЕМ -->
                <!-- ========================================== -->
                <div class="sheet-header">
                    <button class="close-btn" @click="closeModal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <button class="share-btn" @click="toggleShareMenu">
                        <i class="fa-solid fa-share-nodes"></i>
                    </button>

                    <!-- Меню "Поделиться" -->
                    <transition name="fade">
                        <div v-if="showShareMenu" class="share-menu">
                            <button class="share-option" @click="shareVia('copy')">
                                <i class="fa-solid fa-link"></i>
                                <span>Скопировать</span>
                            </button>
                            <button class="share-option" @click="shareVia('telegram')">
                                <i class="fa-brands fa-telegram"></i>
                                <span>Telegram</span>
                            </button>
                            <button class="share-option" @click="shareVia('whatsapp')">
                                <i class="fa-brands fa-whatsapp"></i>
                                <span>WhatsApp</span>
                            </button>
                        </div>
                    </transition>
                </div>

                <!-- ========================================== -->
                <!-- КОНТЕНТ (СКРОЛЛИТСЯ) -->
                <!-- 🆕 ДОБАВЛЕН ref="sheetBody" ДЛЯ УПРАВЛЕНИЯ ПРОКРУТКОЙ -->
                <!-- ========================================== -->
                <div class="sheet-body" v-if="item" ref="sheetBody">

                    <!-- ГАЛЕРЕЯ -->
                    <div class="gallery-section">
                        <div class="main-image-wrapper">
                            <img
                                v-if="selectedImage"
                                v-lazy="selectedImage"
                                :alt="item.title || item.name"
                                class="main-image"
                            >
                            <div v-else class="image-placeholder">
                                <i class="fa-solid fa-image"></i>
                                <span>Фото отсутствует</span>
                            </div>

                            <div v-if="discount > 0" class="discount-badge">-{{ discount }}%</div>
                            <div v-if="item.in_stop_list" class="out-of-stock-badge">
                                <i class="fa-solid fa-lock"></i>
                                <span>Нет в наличии</span>
                            </div>
                        </div>

                        <div v-if="normalizedImages.length > 1" class="thumbnails-scroll">
                            <button
                                v-for="(img, index) in normalizedImages"
                                :key="index"
                                class="thumbnail-btn"
                                :class="{ 'active': selectedImage === img }"
                                @click="selectedImage = img"
                            >
                                <img v-lazy="img" :alt="'Фото ' + (index + 1)">
                            </button>
                        </div>
                    </div>

                    <!-- ОСНОВНАЯ ИНФОРМАЦИЯ -->
                    <div class="product-info-section">
                        <h2 class="product-title">{{ item.title || item.name || 'Товар' }}</h2>

                        <div class="price-block">
                            <div class="price-current">{{ formatPrice(calculatedPrice) }}</div>
                            <div v-if="item.old_price > 0 && !item.is_composite" class="price-old">
                                {{ formatPrice(item.old_price) }}
                            </div>
                            <div v-if="discount > 0 && !item.is_composite" class="price-save">
                                Вы экономите {{ formatPrice(item.old_price - item.price) }}
                            </div>
                            <div v-if="hasModifiers" class="price-hint">
                                <i class="fa-solid fa-info-circle"></i>
                                <span>Цена может измениться в зависимости от выбранных опций</span>
                            </div>
                        </div>

                        <div v-if="item.rating" class="rating-block">
                            <div class="rating-stars">
                                <i
                                    v-for="star in 5"
                                    :key="star"
                                    class="fa-solid fa-star"
                                    :class="{ 'filled': star <= Math.round(item.rating) }"
                                ></i>
                            </div>
                            <span class="rating-value">{{ item.rating.toFixed(1) }}</span>
                            <span class="rating-count">
                                ({{ item.reviews_count || 0 }}
                                {{ pluralize(item.reviews_count || 0, 'отзыв', 'отзыва', 'отзывов') }})
                            </span>
                        </div>

                        <div class="quick-tags">
                            <div v-if="item.is_composite" class="quick-tag composite-tag">
                                <i class="fa-solid fa-boxes-stacked"></i>
                                <span>Комплект</span>
                            </div>
                            <div v-if="item.delivery_terms" class="quick-tag delivery-tag">
                                <i class="fa-solid fa-truck"></i>
                                <span>{{ item.delivery_terms }}</span>
                            </div>
                            <div v-if="item.dimension?.weight > 0" class="quick-tag">
                                <i class="fa-solid fa-weight-hanging"></i>
                                <span>{{ item.dimension.weight }} кг</span>
                            </div>
                        </div>
                    </div>




                        <!-- СЕКЦИЯ КОМПОНЕНТОВ -->
                        <div v-if="hasComponents" class="components-section" ref="componentsSection">
                            <div class="section-header">
                                <div class="section-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                                <h6 class="section-title">
                                    Состав комплекта <span class="required-star">*</span>
                                </h6>
                            </div>

                            <div class="components-list">
                                <div
                                    v-for="component in item.components"
                                    :key="component.id"
                                    class="component-card"
                                    :class="{ 'selected': isComponentSelected(component.id) }"
                                >
                                    <div class="component-main" @click="toggleComponent(component)">
                                        <div class="component-checkbox">
                                            <i class="fa-solid" :class="isComponentSelected(component.id) ? 'fa-check-square' : 'fa-square'"></i>
                                        </div>
                                        <div class="component-info">
                                            <div class="component-name">{{ component.name }}</div>
                                            <div class="component-meta">
                                                <span v-if="component.sku" class="component-sku">{{ component.sku }}</span>
                                                <span class="component-price">{{ formatPrice(component.price) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="isComponentSelected(component.id)" class="component-quantity">
                                        <button
                                            class="qty-btn"
                                            @click.stop="updateComponentQuantity(component.id, -1)"
                                            :disabled="getComponentQuantity(component.id) <= 1"
                                        >
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                        <span class="qty-value">{{ getComponentQuantity(component.id) }}</span>
                                        <button
                                            class="qty-btn"
                                            @click.stop="updateComponentQuantity(component.id, 1)"
                                        >
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="components-total">
                                <span class="total-label">Итого за комплект:</span>
                                <span class="total-value">{{ formatPrice(componentsTotal) }}</span>
                            </div>
                        </div>

                    <!-- СЕКЦИЯ ИНГРЕДИЕНТОВ -->
                    <div v-if="hasIngredientGroups" class="ingredients-section" ref="ingredientsSection">
                        <div class="section-header">
                            <div class="section-icon"><i class="fa-solid fa-blender"></i></div>
                            <h6 class="section-title">Дополнительные опции</h6>
                        </div>

                        <div v-for="group in item.ingredient_groups" :key="group.id" class="ingredient-group">
                            <div class="group-header">
                                <h6 class="group-title">{{ group.name }}</h6>
                                <span class="group-rule">{{ getGroupRuleText(group) }}</span>
                            </div>

                            <div class="ingredients-list">
                                <label
                                    v-for="ingredient in group.ingredients"
                                    :key="ingredient.id"
                                    class="ingredient-item"
                                    :class="{
                                            'selected': isIngredientSelected(ingredient.id),
                                            'disabled': isIngredientDisabled(group, ingredient)
                                        }"
                                >
                                    <input
                                        :type="getInputType(group)"
                                        :name="'group_' + group.id"
                                        :checked="isIngredientSelected(ingredient.id)"
                                        :disabled="isIngredientDisabled(group, ingredient)"
                                        @change="toggleIngredient(group, ingredient)"
                                        class="ingredient-checkbox"
                                    />
                                    <div class="ingredient-info">
                                        <span class="ingredient-name">{{ ingredient.name }}</span>
                                        <span v-if="ingredient.extra_price > 0" class="ingredient-price">
                                                +{{ formatPrice(ingredient.extra_price) }}
                                            </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div v-if="selectedIngredientsTotal > 0" class="ingredients-total">
                            <span class="total-label">Доплата за опции:</span>
                            <span class="total-value">+{{ formatPrice(selectedIngredientsTotal) }}</span>
                        </div>
                    </div>


                    <!-- ПАРАМЕТРЫ ТОВАРА -->
                    <div v-if="hasDimensions" class="dimensions-section">
                        <div class="section-header">
                            <div class="section-icon"><i class="fa-solid fa-ruler-combined"></i></div>
                            <h6 class="section-title">Параметры</h6>
                        </div>
                        <div class="dimensions-grid">
                            <div v-if="item.dimension?.width > 0" class="dimension-card">
                                <div class="dimension-icon"><i class="fa-solid fa-arrows-left-right"></i></div>
                                <div class="dimension-info">
                                    <div class="dimension-label">Ширина</div>
                                    <div class="dimension-value">{{ item.dimension.width }} см</div>
                                </div>
                            </div>
                            <div v-if="item.dimension?.height > 0" class="dimension-card">
                                <div class="dimension-icon"><i class="fa-solid fa-arrows-up-down"></i></div>
                                <div class="dimension-info">
                                    <div class="dimension-label">Высота</div>
                                    <div class="dimension-value">{{ item.dimension.height }} см</div>
                                </div>
                            </div>
                            <div v-if="item.dimension?.length > 0" class="dimension-card">
                                <div class="dimension-icon"><i class="fa-solid fa-ruler"></i></div>
                                <div class="dimension-info">
                                    <div class="dimension-label">Длина</div>
                                    <div class="dimension-value">{{ item.dimension.length }} см</div>
                                </div>
                            </div>
                            <div v-if="item.dimension?.weight > 0" class="dimension-card">
                                <div class="dimension-icon"><i class="fa-solid fa-weight-hanging"></i></div>
                                <div class="dimension-info">
                                    <div class="dimension-label">Вес</div>
                                    <div class="dimension-value">{{ item.dimension.weight }} кг</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ХАРАКТЕРИСТИКИ -->
                    <div v-if="hasAttributes" class="attributes-section">
                        <div class="section-header">
                            <div class="section-icon"><i class="fa-solid fa-list-check"></i></div>
                            <h6 class="section-title">Характеристики</h6>
                        </div>
                        <div class="attributes-list">
                            <div v-for="(attr, index) in item.attributes" :key="index" class="attribute-item">
                                <div class="attribute-name">{{ attr.name }}</div>
                                <div class="attribute-value">{{ attr.value }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- TABS (Описание / Отзывы) -->
                    <div class="tabs-section">
                        <div class="pill-tabs">
                            <button
                                class="pill-tab"
                                :class="{ 'active': activeTab === 'description' }"
                                @click="activeTab = 'description'"
                            >
                                <i class="fa-solid fa-align-left"></i>
                                <span>Описание</span>
                            </button>
                            <button
                                class="pill-tab"
                                :class="{ 'active': activeTab === 'reviews' }"
                                @click="switchToReviews"
                            >
                                <i class="fa-solid fa-comments"></i>
                                <span>Отзывы</span>
                                <span v-if="item.reviews_count > 0" class="tab-badge">{{ item.reviews_count }}</span>
                            </button>
                        </div>

                        <div v-if="activeTab === 'description'" class="tab-content">
                            <div v-if="item.description" class="description-text" v-text="item.description"></div>
                            <div v-else class="empty-description">
                                <i class="fa-solid fa-file-lines"></i>
                                <p>Описание пока не добавлено</p>
                            </div>
                        </div>

                        <div v-if="activeTab === 'reviews'" class="tab-content">
                            <div v-if="loadingReviews" class="loading-state">
                                <div class="loading-spinner"></div>
                                <p>Загружаем отзывы...</p>
                            </div>
                            <div v-else-if="reviews.length > 0" class="reviews-list">
                                <div v-for="(review, index) in reviews" :key="review.id || index" class="review-card">
                                    <ReviewCard :need-product="false" v-model="reviews[index]" />
                                </div>
                                <button
                                    v-if="paginate?.current_page < paginate?.last_page"
                                    class="load-more-btn"
                                    @click="loadReviews(paginate.current_page)"
                                >
                                    <span>Показать ещё</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>
                            <div v-else class="empty-reviews">
                                <div class="empty-icon"><i class="fa-solid fa-comment-dots"></i></div>
                                <h6>Отзывов пока нет</h6>
                                <p>Чтобы оставить отзыв, необходимо заказать этот товар.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- FOOTER С КНОПКАМИ ДЕЙСТВИЙ -->
                <!-- ========================================== -->
                <div class="sheet-footer" v-if="item">
                    <template v-if="!item.in_stop_list">
                        <button
                            v-if="checkInCart === 0"
                            class="add-to-cart-btn"
                            :class="{ 'pulse': justAdded }"
                            :disabled="!canProductAction || !isOptionsValid"
                            @click="incProductCart"
                        >
                            <div class="btn-content">
                                <i class="fa-solid fa-cart-plus"></i>
                                <div class="btn-info">
                                    <span class="btn-label">
                                        {{ !isOptionsValid ? 'Выберите компоненты' : 'Добавить в корзину' }}
                                    </span>
                                    <span class="btn-price">{{ formatPrice(calculatedPrice) }}</span>
                                </div>
                            </div>
                        </button>

                        <div v-else class="quantity-stepper">
                            <button class="stepper-btn minus" @click="decProductCart" :disabled="!canProductAction">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <div class="stepper-value">
                                <span class="value-number">{{ checkInCart }}</span>
                                <span class="value-label">в корзине</span>
                            </div>
                            <button class="stepper-btn plus" @click="incProductCart" :disabled="!canProductAction">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </template>

                    <div v-else class="out-of-stock-footer">
                        <i class="fa-solid fa-lock"></i>
                        <span>Товар недоступен для заказа</span>
                    </div>
                </div>

            </div>
        </div>
    </transition>
</template>

<script>
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';
import ReviewCard from "@/MobileClient/Components/Shop/Reviews/ReviewCard.vue";

export default {
    name: "ProductInfo",
    components: { ReviewCard },
    setup() {
        return { basketStore: useBasketStore() };
    },
    data() {
        return {
            isOpen: false,
            item: null,
            selectedImage: null,
            activeTab: 'description',
            loadingReviews: false,
            reviews: [],
            paginate: null,
            sending: false,
            isOnline: navigator.onLine,
            justAdded: false,
            showShareMenu: false,
            selectedIngredients: {},
            selectedComponents: {},
        };
    },
    computed: {
        hasAttributes() {
            return this.item?.attributes && this.item.attributes.length > 0;
        },

        // 🆕 АБСОЛЮТНО БЕЗОПАСНОЕ вычисляемое свойство для изображений
        normalizedImages() {
            if (!this.item || !this.item.images) return [];
            if (!Array.isArray(this.item.images)) return [];

            return this.item.images
                .map(img => {
                    if (!img) return null;
                    if (typeof img === 'string') return img;
                    if (typeof img === 'object') {
                        return img.url || img.src || img.path || null;
                    }
                    return null;
                })
                .filter(url => url && typeof url === 'string' && url.length > 0);
        },

        checkInCart() {
            return this.item ? this.basketStore.inCart(this.item.id) : 0;
        },

        canProductAction() {
            return this.isOnline && !this.sending;
        },

        discount() {
            const old = this.item?.old_price || 0;
            const cur = this.item?.price || 0;
            return old > 0 ? Math.round((1 - cur / old) * 100) : 0;
        },

        hasDimensions() {
            const d = this.item?.dimension;
            if (!d) return false;
            return d.width > 0 || d.height > 0 || d.length > 0 || d.weight > 0;
        },

        productLink() {
            if (!this.item) return '';
            const tenant = window.Tenant;
            return `${window.location.origin}/product/${this.item.id}?tenant=${tenant?.slug || ''}`;
        },

        hasIngredientGroups() {
            return this.item?.ingredient_groups && this.item.ingredient_groups.length > 0;
        },

        // 🆕 Строгая проверка на случай, если is_composite приходит как 1 или "1"
        hasComponents() {
            return Boolean(this.item?.is_composite) &&
                Array.isArray(this.item?.components) &&
                this.item.components.length > 0;
        },

        hasModifiers() {
            return this.hasIngredientGroups || this.hasComponents;
        },

        selectedIngredientsTotal() {
            return Object.values(this.selectedIngredients).reduce((sum, ing) => {
                return sum + (parseFloat(ing.extra_price) || 0);
            }, 0);
        },

        componentsTotal() {
            return Object.values(this.selectedComponents).reduce((sum, { component, quantity }) => {
                return sum + (parseFloat(component.price) || 0) * quantity;
            }, 0);
        },

        calculatedPrice() {
            if (!this.item) return 0;
            let basePrice = parseFloat(this.item.price) || 0;
            if (this.item.is_composite && this.hasComponents) {
                basePrice += this.componentsTotal;
            }
            return basePrice + this.selectedIngredientsTotal;
        },

        // 🆕 Вычисляемое свойство для валидации перед добавлением в корзину
        isOptionsValid() {
            if (!this.item) return false;
            if (!this.hasModifiers) return true;

            // 1. Проверка компонентов
            if (this.hasComponents && Object.keys(this.selectedComponents).length === 0) {
                return false;
            }

            // 2. Проверка ингредиентов по правилам
            if (this.hasIngredientGroups) {
                for (const group of this.item.ingredient_groups) {
                    const rule = group.selection_rule || 'multiple';
                    const isRequired = group.is_required ?? false;
                    const min = group.min_select ?? 0;

                    const selectedCount = group.ingredients.filter(ing => this.isIngredientSelected(ing.id)).length;

                    if (isRequired && selectedCount < min) {
                        return false; // Не выполнено минимальное требование
                    }
                }
            }
            return true;
        },

        validationErrorMessage() {
            if (this.hasComponents && Object.keys(this.selectedComponents).length === 0) {
                return 'Выберите хотя бы один компонент';
            }
            if (this.hasIngredientGroups) {
                for (const group of this.item.ingredient_groups) {
                    const isRequired = group.is_required ?? false;
                    const min = group.min_select ?? 0;
                    const selectedCount = group.ingredients.filter(ing => this.isIngredientSelected(ing.id)).length;

                    if (isRequired && selectedCount < min) {
                        return `Выберите минимум ${min} опцию(и) в группе "${group.name}"`;
                    }
                }
            }
            return 'Заполните обязательные опции';
        }

    },
    mounted() {
        window.addEventListener('online', () => { this.isOnline = true; });
        window.addEventListener('offline', () => { this.isOnline = false; });
        window.addEventListener('product-info-event', this.onProductInfo);
    },
    beforeUnmount() {
        window.removeEventListener('product-info-event', this.onProductInfo);
    },
    methods: {

        getInputType(group) {
            const rule = group.selection_rule || 'multiple';
            return rule === 'single' ? 'radio' : 'checkbox';
        },

        getGroupRuleText(group) {
            const rule = group.selection_rule || 'multiple';
            const min = group.min_select ?? 0;
            const max = group.max_select ?? 999;
            const req = group.is_required ?? false;
            const star = req ? ' *' : '';

            if (rule === 'single') return `Выберите 1 вариант${star}`;
            if (rule === 'all') return 'Добавляется автоматически';
            if (rule === 'optional') return 'Необязательно';
            if (rule === 'multiple') return `Выберите от ${min} до ${max}${star}`;
            return '';
        },

        isIngredientDisabled(group, ingredient) {
            const rule = group.selection_rule || 'multiple';

            // Если правило "Все", они выбраны по умолчанию и заблокированы
            if (rule === 'all') return true;

            // Если достигнут максимум, и этот ингредиент еще не выбран, блокируем его
            if (rule === 'multiple') {
                const max = group.max_select ?? 999;
                const selectedCount = group.ingredients.filter(ing => this.isIngredientSelected(ing.id)).length;
                if (selectedCount >= max && !this.isIngredientSelected(ingredient.id)) {
                    return true;
                }
            }
            return false;
        },

        // 🆕 ПУЛЕНЕПРОБИВАЕМАЯ обработка события открытия
        onProductInfo(event) {
            const detail = event?.detail || {};

            // Умная распаковка: поддерживает и { product: obj, scrollTo: true }, и просто obj
            const productData = detail.product || detail;

            // Защита от пустых или некорректных данных
            if (!productData || typeof productData !== 'object') {
                console.error('ProductInfo: Не удалось получить данные товара', event);
                this.closeModal();
                return;
            }

            // 1. Сначала устанавливаем товар
            this.item = productData;

            // 2. Сбрасываем состояние
            this.activeTab = 'description';
            this.reviews = [];
            this.paginate = null;
            this.justAdded = false;
            this.showShareMenu = false;

            // 3. Инициализируем дефолтные значения (зависит от this.item)
            this.initializeDefaults();

            // 4. Открываем модалку
            this.isOpen = true;
            document.body.style.overflow = 'hidden';

            // 5. Безопасно устанавливаем изображение после обновления DOM
            this.$nextTick(() => {
               console.log("detail", detail)
                this.selectedImage = this.normalizedImages[0] || null;

                // 6. Если запрошена прокрутка, выполняем её
                if (detail.scrollToOptions) {
                    this.scrollToModifiers();
                }
            });
        },

        // 🆕 Отдельный метод для надежной прокрутки внутри контейнера
        // 🆕 НАДЁЖНАЯ прокрутка с использованием getBoundingClientRect
        scrollToModifiers() {

            this.$nextTick(() => {
                // Увеличенная задержка для гарантии завершения анимации и рендеринга
                setTimeout(() => {
                    const body = this.$refs.sheetBody;
                    if (!body) {
                        console.warn('scrollToModifiers: sheetBody не найден');
                        return;
                    }

                    // Определяем целевой элемент: приоритет компонентам, потом ингредиентам
                    let target = null;
                    let targetName = '';

                    if (this.hasComponents && this.$refs.componentsSection) {
                        target = this.$refs.componentsSection;
                        targetName = 'componentsSection';
                    } else if (this.hasIngredientGroups && this.$refs.ingredientsSection) {
                        target = this.$refs.ingredientsSection;
                        targetName = 'ingredientsSection';
                    }

                    if (!target) {
                        console.warn('scrollToModifiers: целевая секция не найдена', {
                            hasComponents: this.hasComponents,
                            hasIngredientGroups: this.hasIngredientGroups,
                            componentsRef: !!this.$refs.componentsSection,
                            ingredientsRef: !!this.$refs.ingredientsSection
                        });
                        return;
                    }

                    console.log(`scrollToModifiers: скроллим к ${targetName}`);

                    // 🆕 Правильный расчёт позиции через getBoundingClientRect
                    const bodyRect = body.getBoundingClientRect();
                    const targetRect = target.getBoundingClientRect();

                    // Вычисляем абсолютную позицию внутри контейнера
                    const scrollTop = body.scrollTop + (targetRect.top - bodyRect.top) - 20;

                    console.log('scrollToModifiers: позиция', {
                        bodyScrollTop: body.scrollTop,
                        targetTop: targetRect.top,
                        bodyTop: bodyRect.top,
                        calculatedScrollTop: scrollTop
                    });

                    // Плавная прокрутка
                    try {
                        body.scrollTo({
                            top: Math.max(0, scrollTop),
                            behavior: 'smooth'
                        });
                    } catch (error) {
                        // Fallback для старых браузеров
                        console.warn('scrollTo с smooth не поддерживается, используем fallback');
                        body.scrollTop = Math.max(0, scrollTop);
                    }
                }, 500); // Увеличили до 500мс для надёжности
            });
        },

        initializeDefaults() {
            this.selectedIngredients = {};
            this.selectedComponents = {};

            if (this.item?.ingredient_groups) {
                this.item.ingredient_groups.forEach(group => {
                    const rule = group.selection_rule || 'multiple';

                    if (group.ingredients) {
                        group.ingredients.forEach(ing => {
                            // Если "Все" или "По умолчанию", выбираем
                            if (rule === 'all' || ing.is_default) {
                                this.selectedIngredients[ing.id] = ing;
                            }
                        });
                    }
                });
            }

            if (this.item?.is_composite && this.item?.components) {
                this.item.components.forEach(comp => {
                    if (comp.is_default || comp.pivot?.is_default) {
                        this.selectedComponents[comp.id] = {
                            component: comp,
                            quantity: comp.pivot?.quantity || 1,
                        };
                    }
                });
            }
        },


        closeModal() {
            this.isOpen = false;
            this.showShareMenu = false;
            document.body.style.overflow = '';
        },

        toggleShareMenu() {
            this.showShareMenu = !this.showShareMenu;
        },

        async shareVia(type) {
            this.showShareMenu = false;
            try {
                if (type === 'copy') {
                    await navigator.clipboard.writeText(this.productLink);
                    this.$notify?.({ title: 'Ссылка', text: 'Ссылка скопирована', type: 'success' });
                } else if (type === 'telegram') {
                    window.open(`https://t.me/share/url?url=${encodeURIComponent(this.productLink)}&text=${encodeURIComponent(this.item.title || this.item.name)}`, '_blank');
                } else if (type === 'whatsapp') {
                    window.open(`https://wa.me/?text=${encodeURIComponent((this.item.title || this.item.name) + ' ' + this.productLink)}`, '_blank');
                }
            } catch (error) {
                console.error('Ошибка шаринга:', error);
            }
        },

        switchToReviews() {
            this.activeTab = 'reviews';
            if (this.reviews.length === 0) {
                this.loadReviews(0);
            }
        },

        async loadReviews(page = 0) {
            this.loadingReviews = true;
            try {
                const resp = await this.basketStore.loadReviewsByProductId({
                    dataObject: { product_id: this.item.id },
                    page,
                    size: 30,
                });
                if (page === 0) {
                    this.reviews = resp.data || [];
                } else {
                    this.reviews = [...this.reviews, ...(resp.data || [])];
                }
                this.paginate = resp.paginate || null;
            } catch (error) {
                console.error('Ошибка загрузки отзывов:', error);
            } finally {
                this.loadingReviews = false;
            }
        },

        isIngredientSelected(ingredientId) {
            return !!this.selectedIngredients[ingredientId];
        },

        toggleIngredient(group, ingredient) {
            const rule = group.selection_rule || 'multiple';

            if (rule === 'single') {
                // Поведение радиокнопки: снимаем выбор со всех в этой группе
                group.ingredients.forEach(ing => {
                    delete this.selectedIngredients[ing.id];
                });

                // Если кликнули на уже выбранный, и он не обязательный, можно снять выбор (опционально)
                // Но для простоты радиокнопки просто устанавливаем новый
                this.selectedIngredients[ingredient.id] = ingredient;
            } else {
                // Поведение чекбокса
                if (this.isIngredientSelected(ingredient.id)) {
                    delete this.selectedIngredients[ingredient.id];
                } else {
                    const max = group.max_select ?? 999;
                    const selectedCount = group.ingredients.filter(ing => this.isIngredientSelected(ing.id)).length;

                    if (selectedCount < max) {
                        this.selectedIngredients[ingredient.id] = ingredient;
                    } else {
                        this.$notify?.({
                            title: 'Внимание',
                            text: `Максимум ${max} вариантов в этой группе`,
                            type: 'warning'
                        });
                        return; // Прерываем, чтобы не добавлять
                    }
                }
            }
            // Триггерим реактивность
            this.selectedIngredients = { ...this.selectedIngredients };
        },

        isComponentSelected(componentId) {
            return !!this.selectedComponents[componentId];
        },

        toggleComponent(component) {
            if (this.isComponentSelected(component.id)) {
                delete this.selectedComponents[component.id];
            } else {
                this.selectedComponents[component.id] = {
                    component,
                    quantity: component.pivot?.quantity || 1,
                };
            }
            this.selectedComponents = { ...this.selectedComponents };
        },

        getComponentQuantity(componentId) {
            return this.selectedComponents[componentId]?.quantity || 1;
        },

        updateComponentQuantity(componentId, delta) {
            if (!this.selectedComponents[componentId]) return;
            const newQuantity = this.selectedComponents[componentId].quantity + delta;
            if (newQuantity < 1) return;
            this.selectedComponents[componentId].quantity = newQuantity;
            this.selectedComponents = { ...this.selectedComponents };
        },

        async incProductCart() {
            if (this.sending) return;

            if (!this.isOptionsValid) {
                this.$notify?.({
                    title: 'Внимание',
                    text: this.validationErrorMessage,
                    type: 'warning',
                });
                this.scrollToModifiers();
                return;
            }

            this.sending = true;
            try {
                const payload = {
                    product_id: this.item.id,
                    selected_ingredients: Object.keys(this.selectedIngredients).map(id => parseInt(id)),
                    selected_components: Object.entries(this.selectedComponents).map(([id, { quantity }]) => ({
                        id: parseInt(id),
                        quantity,
                    })),
                    count: 1,
                };

                if (this.hasModifiers) {
                    await this.basketStore.addProductWithOptions(payload);
                } else {
                    await this.basketStore.addProductToCart(this.item.id);
                }

                this.justAdded = true;
                setTimeout(() => { this.justAdded = false; }, 600);
                this.$notify?.({ title: 'Корзина', text: `«${this.item.title || this.item.name}» добавлен`, type: 'success' });
            } catch (error) {
                console.error('Ошибка добавления:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось добавить товар', type: 'error' });
            } finally {
                this.sending = false;
            }
        },

        async decProductCart() {
            if (this.sending) return;
            this.sending = true;
            try {
                await this.basketStore.removeProductFromCart(this.item.id);
                this.$notify?.({ title: 'Корзина', text: `«${this.item.title || this.item.name}» убран`, type: 'success' });
            } catch (error) {
                console.error('Ошибка удаления:', error);
            } finally {
                this.sending = false;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0);
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
   OVERLAY
   ========================================== */
.product-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

/* ==========================================
   SHEET (Bottom Sheet)
   ========================================== */
.product-modal-sheet {
    width: 100%;
    max-width: 600px;
    max-height: 92vh;
    background: var(--bs-body-bg);
    border-radius: 24px 24px 0 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

@media (min-width: 768px) {
    .product-modal-overlay {
        align-items: center;
        padding: 20px;
    }
    .product-modal-sheet {
        max-height: 85vh;
        border-radius: 24px;
    }
}

/* ==========================================
   ШАПКА
   ========================================== */
.sheet-header {
    position: relative;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    z-index: 10;
}

.close-btn, .share-btn {
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
}

.close-btn:hover { background: #dc3545; color: white; transform: rotate(90deg); }
.share-btn:hover { background: var(--bs-primary); color: white; }

.share-menu {
    position: absolute;
    top: 60px;
    right: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 20;
    min-width: 160px;
}

.share-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: var(--bs-body-color);
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.share-option:hover { background: rgba(var(--bs-primary-rgb), 0.08); color: var(--bs-primary); }
.share-option i { width: 20px; text-align: center; font-size: 1rem; }

/* ==========================================
   ТЕЛО (СКРОЛЛ)
   ========================================== */
.sheet-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.sheet-body::-webkit-scrollbar { width: 4px; }
.sheet-body::-webkit-scrollbar-thumb { background: var(--bs-border-color); border-radius: 2px; }

/* ==========================================
   ГАЛЕРЕЯ
   ========================================== */
.gallery-section { position: relative; }
.main-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    background: var(--bs-secondary-bg);
    overflow: hidden;
}
.main-image { width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s ease; }

.discount-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 14px;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

.out-of-stock-badge {
    position: absolute;
    bottom: 16px;
    left: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
    color: white;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
}

.thumbnails-scroll {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    overflow-x: auto;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
}
.thumbnails-scroll::-webkit-scrollbar { height: 3px; }
.thumbnails-scroll::-webkit-scrollbar-thumb { background: var(--bs-border-color); border-radius: 2px; }

.thumbnail-btn {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    padding: 0;
    background: var(--bs-secondary-bg);
}
.thumbnail-btn:hover { border-color: var(--bs-primary); transform: scale(1.05); }
.thumbnail-btn.active { border-color: var(--bs-primary); box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3); }
.thumbnail-btn img { width: 100%; height: 100%; object-fit: cover; }

/* ==========================================
   ОСНОВНАЯ ИНФОРМАЦИЯ
   ========================================== */
.product-info-section { padding: 20px 16px; }
.product-title { font-size: 1.4rem; font-weight: 700; color: var(--bs-body-color); margin: 0 0 12px 0; line-height: 1.3; }

.price-block { display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
.price-current { font-size: 1.8rem; font-weight: 800; color: var(--bs-primary); line-height: 1; }
.price-old { font-size: 1.1rem; color: var(--bs-secondary-color); text-decoration: line-through; }
.price-save { font-size: 0.85rem; color: #198754; font-weight: 600; padding: 4px 10px; background: rgba(25, 135, 84, 0.1); border-radius: 8px; }
.price-hint { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--bs-secondary-color); width: 100%; margin-top: 4px; }
.price-hint i { font-size: 0.7rem; }

.rating-block { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
.rating-stars { display: flex; gap: 2px; }
.rating-stars i { font-size: 0.9rem; color: var(--bs-border-color); transition: color 0.2s ease; }
.rating-stars i.filled { color: #ffc107; }
.rating-value { font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); }
.rating-count { font-size: 0.8rem; color: var(--bs-secondary-color); }

.quick-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.quick-tag { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(var(--bs-primary-rgb), 0.08); color: var(--bs-primary); border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.quick-tag i { font-size: 0.75rem; }
.composite-tag { background: rgba(111, 66, 193, 0.12); color: #6f42c1; }
.composite-tag i { color: #6f42c1; }
.delivery-tag { background: rgba(255, 193, 7, 0.12); color: #b8860b; }
.delivery-tag i { color: #ffc107; }

/* ==========================================
   🆕 КОМПОНЕНТЫ И ИНГРЕДИЕНТЫ
   ========================================== */
.components-section, .ingredients-section { padding: 0 16px 20px; }
.components-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }

.component-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    transition: all 0.2s ease;
}
.component-card.selected { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), 0.03); }
.component-main { flex: 1; display: flex; align-items: center; gap: 12px; cursor: pointer; }
.component-checkbox { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: var(--bs-border-color); font-size: 1.2rem; flex-shrink: 0; }
.component-card.selected .component-checkbox { color: var(--bs-primary); }
.component-info { flex: 1; min-width: 0; }
.component-name { font-weight: 600; font-size: 0.95rem; color: var(--bs-body-color); margin-bottom: 4px; }
.component-meta { display: flex; gap: 12px; font-size: 0.8rem; color: var(--bs-secondary-color); }
.component-sku { opacity: 0.7; }
.component-price { font-weight: 600; color: var(--bs-primary); }

.component-quantity { display: flex; align-items: center; gap: 8px; background: var(--bs-secondary-bg); border-radius: 8px; padding: 4px; }
.qty-btn { width: 28px; height: 28px; border: none; background: var(--bs-body-bg); color: var(--bs-primary); border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; }
.qty-btn:hover:not(:disabled) { background: var(--bs-primary); color: white; }
.qty-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.qty-value { min-width: 24px; text-align: center; font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); }

.components-total, .ingredients-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 14px;
    border-radius: 10px;
    font-weight: 600;
}
.components-total { background: rgba(var(--bs-primary-rgb), 0.08); }
.ingredients-total { background: rgba(25, 135, 84, 0.08); margin-top: 12px; }
.total-label { color: var(--bs-body-color); font-size: 0.9rem; }
.total-value { font-size: 1.1rem; font-weight: 700; }
.components-total .total-value { color: var(--bs-primary); }
.ingredients-total .total-value { color: #198754; }

.ingredient-group { margin-bottom: 16px; }
.group-header { margin-bottom: 10px; }
.group-title { font-size: 1rem; font-weight: 700; color: var(--bs-body-color); margin: 0; }
.ingredients-list { display: flex; flex-direction: column; gap: 8px; }

.ingredient-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.ingredient-item:hover { border-color: var(--bs-primary); }
.ingredient-item.selected { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), 0.03); }
.ingredient-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: var(--bs-primary); }
.ingredient-info { flex: 1; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.ingredient-name { font-weight: 500; font-size: 0.9rem; color: var(--bs-body-color); }
.ingredient-price { font-weight: 600; font-size: 0.85rem; color: var(--bs-primary); white-space: nowrap; }

/* ==========================================
   ПАРАМЕТРЫ И АТРИБУТЫ
   ========================================== */
.dimensions-section, .attributes-section, .tabs-section { padding: 0 16px 20px; }
.section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.section-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;
}
.section-title { margin: 0; font-weight: 700; font-size: 1rem; color: var(--bs-body-color); }

.dimensions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.dimension-card { display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 12px; }
.dimension-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
.dimension-info { flex: 1; min-width: 0; }
.dimension-label { font-size: 0.7rem; color: var(--bs-secondary-color); text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
.dimension-value { font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); }

.attributes-list { display: flex; flex-direction: column; gap: 8px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 12px; overflow: hidden; }
.attribute-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; gap: 12px; border-bottom: 1px solid var(--bs-border-color); }
.attribute-item:last-child { border-bottom: none; }
.attribute-name { font-size: 0.85rem; color: var(--bs-secondary-color); font-weight: 500; flex-shrink: 0; }
.attribute-value { font-size: 0.9rem; color: var(--bs-body-color); font-weight: 600; text-align: right; flex: 1; }

/* ==========================================
   TABS
   ========================================== */
.pill-tabs { display: flex; gap: 8px; background: var(--bs-secondary-bg); padding: 4px; border-radius: 14px; margin-bottom: 16px; }
.pill-tab {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 14px;
    background: transparent; border: none; border-radius: 10px; color: var(--bs-secondary-color);
    font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;
}
.pill-tab:hover:not(.active) { color: var(--bs-body-color); background: rgba(var(--bs-primary-rgb), 0.05); }
.pill-tab.active { background: var(--bs-body-bg); color: var(--bs-primary); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); }
.tab-badge { padding: 2px 8px; background: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary); border-radius: 10px; font-size: 0.7rem; font-weight: 700; }
.pill-tab.active .tab-badge { background: var(--bs-primary); color: white; }

.tab-content { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

.description-text { font-size: 0.95rem; line-height: 1.6; color: var(--bs-body-color); white-space: pre-wrap; }
.empty-description { text-align: center; padding: 40px 20px; color: var(--bs-secondary-color); }
.empty-description i { font-size: 2rem; opacity: 0.3; margin-bottom: 12px; }
.empty-description p { margin: 0; font-size: 0.9rem; }

.loading-state { text-align: center; padding: 40px 20px; }
.loading-spinner { width: 36px; height: 36px; border: 3px solid var(--bs-border-color); border-top-color: var(--bs-primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 12px; }
@keyframes spin { to { transform: rotate(360deg); } }
.loading-state p { margin: 0; font-size: 0.9rem; color: var(--bs-secondary-color); }

.reviews-list { display: flex; flex-direction: column; gap: 12px; }
.review-card { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 14px; overflow: hidden; }
.load-more-btn {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px;
    background: transparent; border: 2px solid var(--bs-border-color); border-radius: 12px; color: var(--bs-primary);
    font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; margin-top: 8px;
}
.load-more-btn:hover { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), 0.03); }
.load-more-btn i { transition: transform 0.2s ease; }
.load-more-btn:hover i { transform: translateY(2px); }

.empty-reviews { text-align: center; padding: 40px 20px; }
.empty-reviews .empty-icon { width: 64px; height: 64px; border-radius: 50%; background: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 16px; }
.empty-reviews h6 { font-weight: 700; font-size: 1rem; margin-bottom: 8px; color: var(--bs-body-color); }
.empty-reviews p { font-size: 0.85rem; color: var(--bs-secondary-color); margin: 0; line-height: 1.5; }

/* ==========================================
   FOOTER
   ========================================== */
.sheet-footer { padding: 12px 16px; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.05); }

.add-to-cart-btn {
    width: 100%; padding: 14px 20px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none; border-radius: 14px; color: white; cursor: pointer; transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}
.add-to-cart-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4); }
.add-to-cart-btn:active:not(:disabled) { transform: translateY(0); }
.add-to-cart-btn.pulse { animation: addPulse 0.6s ease; }

@keyframes addPulse {
    0% { transform: scale(1); } 30% { transform: scale(1.03); } 60% { transform: scale(0.98); } 100% { transform: scale(1); }
}

/* 🆕 Стили для заблокированной кнопки */
.add-to-cart-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #9ca3af !important;
    box-shadow: none !important;
    transform: none !important;
}

.btn-content { display: flex; align-items: center; gap: 14px; }
.btn-content i { width: 40px; height: 40px; border-radius: 10px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.btn-info { flex: 1; display: flex; flex-direction: column; align-items: flex-start; text-align: left; }
.btn-label { font-size: 0.75rem; opacity: 0.9; line-height: 1; margin-bottom: 4px; }
.btn-price { font-size: 1.2rem; font-weight: 700; line-height: 1; }

.quantity-stepper { display: flex; align-items: center; background: var(--bs-body-bg); border: 2px solid var(--bs-primary); border-radius: 14px; overflow: hidden; }
.stepper-btn { width: 56px; height: 56px; border: none; background: transparent; color: var(--bs-primary); display: flex; align-items: center; justify-content: center; font-size: 1rem; cursor: pointer; transition: all 0.2s ease; }
.stepper-btn:hover:not(:disabled) { background: var(--bs-primary); color: white; }
.stepper-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.stepper-btn.minus { border-right: 1px solid var(--bs-border-color); }
.stepper-btn.plus { border-left: 1px solid var(--bs-border-color); }
.stepper-value { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0 16px; }
.value-number { font-size: 1.3rem; font-weight: 800; color: var(--bs-primary); line-height: 1; }
.value-label { font-size: 0.7rem; color: var(--bs-secondary-color); margin-top: 4px; }

.out-of-stock-footer { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 16px; background: var(--bs-secondary-bg); border: 1px solid var(--bs-border-color); border-radius: 14px; color: var(--bs-secondary-color); font-weight: 600; font-size: 0.95rem; }
.out-of-stock-footer i { font-size: 1rem; }

/* ==========================================
   АНИМАЦИИ И АДАПТИВ
   ========================================== */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 576px) {
    .product-title { font-size: 1.2rem; }
    .price-current { font-size: 1.5rem; }
    .dimensions-grid { grid-template-columns: 1fr; }
    .thumbnail-btn { width: 56px; height: 56px; }
    .btn-price { font-size: 1.1rem; }
}

.image-placeholder { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; background: linear-gradient(135deg, var(--bs-secondary-bg) 0%, var(--bs-body-bg) 100%); color: var(--bs-secondary-color); user-select: none; }
.image-placeholder i { font-size: 3rem; opacity: 0.4; }
.image-placeholder span { font-size: 0.9rem; font-weight: 500; opacity: 0.6; }

/* 🆕 Красная звездочка обязательности */
.required-star { color: #dc3545; font-size: 0.8em; margin-left: 4px; }

/* 🆕 Стили для правил и заблокированных состояний */
.group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.group-title { font-size: 1rem; font-weight: 700; color: var(--bs-body-color); margin: 0; }
.group-rule {
    font-size: 0.8rem;
    color: var(--bs-primary);
    font-weight: 600;
    background: rgba(var(--bs-primary-rgb), 0.08);
    padding: 4px 10px;
    border-radius: 20px;
}

.ingredient-item.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--bs-secondary-bg);
}

.ingredient-item.disabled .ingredient-checkbox {
    cursor: not-allowed;
}

/* Анимация для радио-кнопок, чтобы они выглядели красиво */
.ingredient-checkbox[type="radio"] {
    border-radius: 50%;
}
</style>
