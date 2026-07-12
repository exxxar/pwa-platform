<template>
    <div class="shop-settings">
        <!-- Шапка -->
        <div class="settings-header">
            <div class="header-left">
                <h2>
                    <i class="fa-solid fa-sliders"></i>
                    Настройки магазина
                </h2>
                <span class="header-hint">Все изменения сохраняются автоматически при нажатии "Сохранить"</span>
            </div>
            <div class="header-actions">
                <button class="btn-secondary" @click="resetToDefaults">
                    <i class="fa-solid fa-rotate-left"></i> Сбросить
                </button>
                <button class="btn-primary" @click="saveSettings" :disabled="isSaving">
                    <span v-if="isSaving" class="spinner"></span>
                    <template v-else>
                        <i class="fa-solid fa-check"></i> Сохранить
                    </template>
                </button>
            </div>
        </div>

        <div class="settings-layout">
            <!-- Боковое меню (вкладки) -->
            <aside class="settings-sidebar">
                <nav class="settings-nav">
                    <button
                        v-for="section in sections"
                        :key="section.id"
                        class="nav-item"
                        :class="{ 'active': activeSection === section.id }"
                        @click="activeSection = section.id"
                    >
                        <i :class="section.icon"></i>
                        <span>{{ section.label }}</span>
                        <span v-if="section.badge" class="nav-badge">{{ section.badge }}</span>
                    </button>
                </nav>
            </aside>

            <!-- Основная область -->
            <main class="settings-content">
                <!-- ==================== ЦВЕТОВАЯ СХЕМА ==================== -->
                <div v-if="activeSection === 'theme'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Цветовая схема</h3>
                        <p>Настройте цвета вашего магазина. Изменения сразу видны в предпросмотре.</p>
                    </div>

                    <!-- Живой предпросмотр -->
                    <div class="theme-preview">
                        <div class="preview-card" :style="previewStyles">
                            <!-- УБРАЛИ ?. ИЗ :style, так как теперь localConfig.theme ВСЕГДА существует -->
                            <div class="preview-header" :style="{ background: `linear-gradient(135deg, ${localConfig.theme.primary}, ${localConfig.theme.primaryLight})` }">
                                <span>Предпросмотр</span>
                            </div>
                            <div class="preview-body">
                                <button class="preview-btn" :style="{ background: `linear-gradient(135deg, ${localConfig.theme.primary}, ${localConfig.theme.primaryLight})` }">
                                    Кнопка
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="color-grid">
                        <div class="color-item">
                            <label>Основной цвет</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="localConfig.theme.primary">
                                <input type="text" v-model="localConfig.theme.primary" class="hex-input">
                            </div>
                            <span class="color-hint">Кнопки, акценты, ссылки</span>
                        </div>
                        <div class="color-item">
                            <label>Тёмный оттенок</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="localConfig.theme.primaryDark">
                                <input type="text" v-model="localConfig.theme.primaryDark" class="hex-input">
                            </div>
                            <span class="color-hint">Hover-эффекты, активные состояния</span>
                        </div>
                        <div class="color-item">
                            <label>Светлый оттенок</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="localConfig.theme.primaryLight">
                                <input type="text" v-model="localConfig.theme.primaryLight" class="hex-input">
                            </div>
                            <span class="color-hint">Градиенты, светлые акценты</span>
                        </div>
                        <div class="color-item">
                            <label>Акцентный цвет</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="localConfig.theme.accent">
                                <input type="text" v-model="localConfig.theme.accent" class="hex-input">
                            </div>
                            <span class="color-hint">Звёзды рейтинга, бейджи</span>
                        </div>
                        <div class="color-item">
                            <label>Тёмный фон</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="localConfig.theme.dark">
                                <input type="text" v-model="localConfig.theme.dark" class="hex-input">
                            </div>
                            <span class="color-hint">Текст, тёмные секции</span>
                        </div>
                        <div class="color-item">
                            <label>Светлый фон</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="localConfig.theme.light">
                                <input type="text" v-model="localConfig.theme.light" class="hex-input">
                            </div>
                            <span class="color-hint">Фон страницы, карточки</span>
                        </div>
                    </div>
                </div>

                <!-- ==================== HERO СЕКЦИЯ ==================== -->
                <div v-if="activeSection === 'hero'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Hero секция</h3>
                        <p>Первый экран, который видят посетители. Сделайте его привлекательным.</p>
                    </div>
                    <div class="form-group">
                        <label>Бейдж сверху</label>
                        <input type="text" v-model="localConfig.hero.badge" placeholder="Например: Мобильный магазин">
                    </div>
                    <div class="form-group">
                        <label>Заголовок <span class="required">*</span></label>
                        <input type="text" v-model="localConfig.hero.title" placeholder="Главный заголовок">
                    </div>
                    <div class="form-group">
                        <label>Подзаголовок</label>
                        <textarea v-model="localConfig.hero.subtitle" rows="3" placeholder="Краткое описание"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Текст кнопки</label>
                        <input type="text" v-model="localConfig.hero.buttonText" placeholder="Например: Смотреть каталог">
                    </div>
                    <div class="form-group">
                        <label>Фоновое изображение</label>
                        <div class="file-upload" :class="{ 'has-file': localConfig.hero.backgroundImage }">
                            <input type="file" ref="heroImageInput" @change="handleHeroImage" accept="image/*" class="file-input">
                            <div v-if="!localConfig.hero.backgroundImage" class="upload-placeholder">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Нажмите или перетащите изображение</span>
                                <small>Рекомендуемый размер: 1920×1080, JPG/PNG/WebP</small>
                            </div>
                            <div v-else class="upload-preview">
                                <img :src="localConfig.hero.backgroundImage" alt="Preview">
                                <button type="button" class="remove-image" @click="removeHeroImage">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== КАТЕГОРИИ ==================== -->
                <div v-if="activeSection === 'categories'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Категории товаров</h3>
                        <p>Управляйте категориями для фильтрации товаров</p>
                    </div>
                    <button class="btn-add" @click="addCategory">
                        <i class="fa-solid fa-plus"></i> Добавить категорию
                    </button>
                    <div class="items-list">
                        <div v-for="(cat, idx) in localConfig.categories" :key="idx" class="item-card">
                            <div class="item-icon-preview"><i :class="cat.icon"></i></div>
                            <div class="item-fields">
                                <input type="text" v-model="cat.name" placeholder="Название категории" class="field-name">
                                <input type="text" v-model="cat.icon" placeholder="fa-solid fa-icon" class="field-icon">
                            </div>
                            <button class="btn-remove" @click="removeCategory(idx)" :disabled="cat.id === 'all'">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ==================== ТОВАРЫ ==================== -->
                <div v-if="activeSection === 'products'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Товары</h3>
                        <p>Добавляйте, редактируйте и удаляйте товары вашего магазина</p>
                    </div>
                    <div class="panel-header-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <input type="text" v-model="productSearch" placeholder="Поиск товара..." class="search-input">
                        </div>
                        <button class="btn-add" @click="addProduct">
                            <i class="fa-solid fa-plus"></i> Добавить товар
                        </button>
                    </div>
                    <div class="items-list products-list">
                        <div v-for="(product, idx) in filteredProductsList" :key="product.id" class="item-card product-item">
                            <div class="product-image-preview">
                                <img v-if="product.image" :src="product.image" :alt="product.name">
                                <div v-else class="no-image"><i class="fa-solid fa-image"></i></div>
                            </div>
                            <div class="item-fields product-fields">
                                <input type="text" v-model="product.name" placeholder="Название товара">
                                <div class="price-row">
                                    <input type="number" v-model.number="product.price" placeholder="Цена">
                                    <input type="number" v-model.number="product.oldPrice" placeholder="Старая цена (опционально)">
                                </div>
                                <div class="meta-row">
                                    <select v-model="product.category">
                                        <option v-for="cat in localConfig.categories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <input type="text" v-model="product.badge" placeholder="Бейдж (Хит, Новинка...)">
                                </div>
                            </div>
                            <div class="item-actions">
                                <label class="btn-upload">
                                    <i class="fa-solid fa-image"></i>
                                    <input type="file" accept="image/*" @change="handleProductImage(product, $event)" hidden>
                                </label>
                                <button class="btn-remove" @click="removeProduct(idx)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="localConfig.items.length === 0" class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <p>Товары не добавлены</p>
                        <button class="btn-add" @click="addProduct">
                            <i class="fa-solid fa-plus"></i> Добавить первый товар
                        </button>
                    </div>
                </div>

                <!-- ==================== ОТЗЫВЫ ==================== -->
                <div v-if="activeSection === 'reviews'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Отзывы клиентов</h3>
                        <p>Добавьте реальные отзывы для повышения доверия</p>
                    </div>
                    <div class="form-group">
                        <label>Заголовок секции</label>
                        <input type="text" v-model="localConfig.reviewsSection.title">
                    </div>
                    <div class="form-group">
                        <label>Подзаголовок секции</label>
                        <input type="text" v-model="localConfig.reviewsSection.subtitle">
                    </div>
                    <button class="btn-add" @click="addReview">
                        <i class="fa-solid fa-plus"></i> Добавить отзыв
                    </button>
                    <div class="items-list">
                        <div v-for="(review, idx) in localConfig.reviews" :key="idx" class="item-card review-item">
                            <div class="review-avatar-preview">
                                <img v-if="review.avatar" :src="review.avatar" :alt="review.name">
                                <div v-else class="no-avatar"><i class="fa-solid fa-user"></i></div>
                            </div>
                            <div class="item-fields review-fields">
                                <input type="text" v-model="review.name" placeholder="Имя клиента">
                                <textarea v-model="review.text" rows="2" placeholder="Текст отзыва"></textarea>
                                <div class="rating-input">
                                    <span>Оценка:</span>
                                    <i v-for="star in 5" :key="star" class="fa-solid fa-star" :class="{ 'filled': star <= review.rating }" @click="review.rating = star"></i>
                                </div>
                            </div>
                            <div class="item-actions">
                                <label class="btn-upload">
                                    <i class="fa-solid fa-image"></i>
                                    <input type="file" accept="image/*" @change="handleReviewAvatar(review, $event)" hidden>
                                </label>
                                <button class="btn-remove" @click="removeReview(idx)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== CTA ==================== -->
                <div v-if="activeSection === 'cta'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Призыв к действию (CTA)</h3>
                        <p>Секция перед футером с призывом связаться</p>
                    </div>
                    <div class="form-group"><label>Заголовок</label><input type="text" v-model="localConfig.cta.title"></div>
                    <div class="form-group"><label>Текст</label><textarea v-model="localConfig.cta.text" rows="3"></textarea></div>
                    <div class="form-group"><label>Текст кнопки</label><input type="text" v-model="localConfig.cta.buttonText"></div>
                </div>

                <!-- ==================== FOOTER ==================== -->
                <div v-if="activeSection === 'footer'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Футер</h3>
                        <p>Контактная информация и ссылки</p>
                    </div>
                    <div class="form-group"><label>Название компании</label><input type="text" v-model="localConfig.footer.companyName"></div>
                    <div class="form-group"><label>Описание</label><textarea v-model="localConfig.footer.description" rows="2"></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Телефон</label><input type="tel" v-model="localConfig.footer.phone"></div>
                        <div class="form-group"><label>Email</label><input type="email" v-model="localConfig.footer.email"></div>
                    </div>
                    <div class="form-group"><label>Адрес</label><input type="text" v-model="localConfig.footer.address"></div>
                    <div class="form-group">
                        <label>Социальные сети</label>
                        <div class="social-list">
                            <div v-for="(social, idx) in localConfig.footer.socialLinks" :key="idx" class="social-item">
                                <input type="text" v-model="social.icon" placeholder="fa-brands fa-telegram">
                                <input type="text" v-model="social.url" placeholder="https://...">
                                <button class="btn-remove" @click="removeSocial(idx)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                            <button class="btn-add-small" @click="addSocial"><i class="fa-solid fa-plus"></i> Добавить соцсеть</button>
                        </div>
                    </div>
                </div>

                <!-- ==================== КОРЗИНА ==================== -->
                <div v-if="activeSection === 'cart'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Корзина</h3>
                        <p>Тексты для корзины и оформления заказа</p>
                    </div>
                    <div class="form-group"><label>Заголовок корзины</label><input type="text" v-model="localConfig.cart.title"></div>
                    <div class="form-group"><label>Текст пустой корзины</label><input type="text" v-model="localConfig.cart.emptyText"></div>
                    <div class="form-group"><label>Текст кнопки оформления</label><input type="text" v-model="localConfig.cart.checkoutText"></div>
                    <div class="form-group"><label>Подпись итоговой суммы</label><input type="text" v-model="localConfig.cart.totalText"></div>
                </div>

                <!-- ==================== ОБРАТНАЯ СВЯЗЬ ==================== -->
                <div v-if="activeSection === 'feedback'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Модалка обратной связи</h3>
                        <p>Форма для связи с клиентами</p>
                    </div>
                    <div class="form-group"><label>Заголовок</label><input type="text" v-model="localConfig.feedbackModal.title"></div>
                    <div class="form-group"><label>Подзаголовок</label><input type="text" v-model="localConfig.feedbackModal.subtitle"></div>
                    <div class="form-group"><label>Подпись поля "Имя"</label><input type="text" v-model="localConfig.feedbackModal.nameLabel"></div>
                    <div class="form-group"><label>Подпись поля "Телефон"</label><input type="text" v-model="localConfig.feedbackModal.phoneLabel"></div>
                    <div class="form-group"><label>Подпись поля "Сообщение"</label><input type="text" v-model="localConfig.feedbackModal.messageLabel"></div>
                    <div class="form-group"><label>Текст кнопки отправки</label><input type="text" v-model="localConfig.feedbackModal.submitText"></div>
                </div>

                <!-- ==================== КОНФИДЕНЦИАЛЬНОСТЬ ==================== -->
                <div v-if="activeSection === 'privacy'" class="settings-panel fade-in">
                    <div class="panel-header">
                        <h3>Политика конфиденциальности</h3>
                        <p>Текст политики, отображаемый в модалке</p>
                    </div>
                    <div class="form-group"><label>Заголовок</label><input type="text" v-model="localConfig.privacyModal.title"></div>
                    <div class="form-group">
                        <label>Текст политики <span class="hint">Поддерживается HTML-разметка</span></label>
                        <textarea v-model="localConfig.privacyModal.content" rows="15" class="code-textarea"></textarea>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script>
export default {
    name: "ShopSettings",

    props: {
        initialConfig: {
            type: Object,
            default: () => ({}), // Делаем необязательным с пустым объектом по умолчанию
        },
    },

    emits: ['save', 'reset'],

    data() {
        return {
            isSaving: false,
            activeSection: 'theme',
            productSearch: '',

            // Инициализируем через безопасное слияние с дефолтами
            localConfig: this.mergeConfig(this.initialConfig),

            sections: [
                { id: 'theme', label: 'Цвета', icon: 'fa-solid fa-palette' },
                { id: 'hero', label: 'Hero секция', icon: 'fa-solid fa-image' },
                { id: 'categories', label: 'Категории', icon: 'fa-solid fa-layer-group', badge: null },
                { id: 'products', label: 'Товары', icon: 'fa-solid fa-box', badge: null },
                { id: 'reviews', label: 'Отзывы', icon: 'fa-solid fa-star' },
                { id: 'cta', label: 'CTA секция', icon: 'fa-solid fa-bullhorn' },
                { id: 'footer', label: 'Футер', icon: 'fa-solid fa-shoe-prints' },
                { id: 'cart', label: 'Корзина', icon: 'fa-solid fa-cart-shopping' },
                { id: 'feedback', label: 'Обратная связь', icon: 'fa-solid fa-envelope' },
                { id: 'privacy', label: 'Конфиденциальность', icon: 'fa-solid fa-shield-halved' },
            ],
        };
    },

    computed: {
        previewStyles() {
            const t = this.localConfig.theme;
            return {
                '--preview-primary': t?.primary || '#ffffff', // Исправлен опечатка #fffff -> #ffffff
                '--preview-light': t?.primaryLight || '#ffffff',
                '--preview-bg': t?.light,
                '--preview-text': t?.dark,
            };
        },

        filteredProductsList() {
            if (!this.productSearch.trim()) return this.localConfig.items;
            const q = this.productSearch.toLowerCase();
            return this.localConfig.items.filter(p => p.name?.toLowerCase().includes(q));
        },
    },

    watch: {
        initialConfig: {
            deep: true,
            handler(newVal) {
                // Пересчитываем локальный конфиг при изменении пропса
                this.localConfig = this.mergeConfig(newVal);
            },
        },
    },

    methods: {
        deepClone(obj) {
            return JSON.parse(JSON.stringify(obj));
        },

        // Возвращает полную структуру конфига с дефолтными значениями
        getDefaultConfig() {
            return {
                theme: { primary: '#3b82f6', primaryDark: '#2563eb', primaryLight: '#60a5fa', accent: '#f59e0b', dark: '#1f2937', light: '#f9fafb' },
                hero: { badge: '', title: '', subtitle: '', buttonText: '', backgroundImage: '' },
                categories: [],
                items: [],
                reviews: [],
                reviewsSection: { title: '', subtitle: '' },
                cta: { title: '', text: '', buttonText: '' },
                footer: { companyName: '', description: '', phone: '', email: '', address: '', socialLinks: [] },
                cart: { title: '', emptyText: '', checkoutText: '', totalText: '' },
                feedbackModal: { title: '', subtitle: '', nameLabel: '', phoneLabel: '', messageLabel: '', submitText: '' },
                privacyModal: { title: '', content: '' }
            };
        },

        // Безопасно сливает переданные данные с дефолтами
        mergeConfig(initial) {
            const defaults = this.getDefaultConfig();
            const clonedInitial = this.deepClone(initial || {});
            const merged = this.deepClone(defaults);

            for (const key in clonedInitial) {
                if (clonedInitial[key] !== undefined && clonedInitial[key] !== null) {
                    // Если это обычный объект (не массив), сливаем его рекурсивно
                    if (typeof clonedInitial[key] === 'object' && !Array.isArray(clonedInitial[key]) && typeof merged[key] === 'object' && !Array.isArray(merged[key])) {
                        merged[key] = { ...merged[key], ...clonedInitial[key] };
                    } else {
                        // Иначе просто перезаписываем (для массивов и примитивов)
                        merged[key] = clonedInitial[key];
                    }
                }
            }
            return merged;
        },

        // ==========================================
        // HERO
        // ==========================================
        handleHeroImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.fileToBase64(file).then(base64 => {
                this.localConfig.hero.backgroundImage = base64;
            });
        },

        removeHeroImage() {
            this.localConfig.hero.backgroundImage = '';
            if (this.$refs.heroImageInput) {
                this.$refs.heroImageInput.value = '';
            }
        },

        // ==========================================
        // КАТЕГОРИИ
        // ==========================================
        addCategory() {
            const newId = 'cat_' + Date.now();
            this.localConfig.categories.push({
                id: newId,
                name: 'Новая категория',
                icon: 'fa-solid fa-tag',
            });
        },

        removeCategory(idx) {
            if (this.localConfig.categories[idx].id === 'all') return;
            if (!confirm('Удалить категорию?')) return;
            this.localConfig.categories.splice(idx, 1);
        },

        // ==========================================
        // ТОВАРЫ
        // ==========================================
        addProduct() {
            const newId = Date.now();
            this.localConfig.items.push({
                id: newId,
                name: 'Новый товар',
                price: 0,
                oldPrice: null,
                category: this.localConfig.categories[0]?.id || 'all',
                image: '',
                badge: '',
            });
        },

        removeProduct(idx) {
            if (!confirm('Удалить товар?')) return;
            const realIdx = this.localConfig.items.findIndex(p => p.id === this.filteredProductsList[idx].id);
            if (realIdx !== -1) {
                this.localConfig.items.splice(realIdx, 1);
            }
        },

        handleProductImage(product, event) {
            const file = event.target.files[0];
            if (!file) return;
            this.fileToBase64(file).then(base64 => {
                product.image = base64;
            });
        },

        // ==========================================
        // ОТЗЫВЫ
        // ==========================================
        addReview() {
            this.localConfig.reviews.push({
                id: Date.now(),
                name: '',
                text: '',
                rating: 5,
                avatar: '',
            });
        },

        removeReview(idx) {
            if (!confirm('Удалить отзыв?')) return;
            this.localConfig.reviews.splice(idx, 1);
        },

        handleReviewAvatar(review, event) {
            const file = event.target.files[0];
            if (!file) return;
            this.fileToBase64(file).then(base64 => {
                review.avatar = base64;
            });
        },

        // ==========================================
        // СОЦСЕТИ
        // ==========================================
        addSocial() {
            this.localConfig.footer.socialLinks.push({
                icon: 'fa-brands fa-link',
                url: '',
            });
        },

        removeSocial(idx) {
            this.localConfig.footer.socialLinks.splice(idx, 1);
        },

        // ==========================================
        // УТИЛИТЫ
        // ==========================================
        fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });
        },

        // ==========================================
        // СОХРАНЕНИЕ / СБРОС
        // ==========================================
        async saveSettings() {
            if (!this.localConfig.hero.title?.trim()) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Заполните заголовок Hero секции',
                    type: 'error',
                });
                this.activeSection = 'hero';
                return;
            }

            this.isSaving = true;

            try {
                await new Promise(resolve => setTimeout(resolve, 800));
                this.$emit('save', this.deepClone(this.localConfig));

                this.$notify?.({
                    title: 'Успех',
                    text: 'Настройки сохранены',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить настройки',
                    type: 'error',
                });
            } finally {
                this.isSaving = false;
            }
        },

        resetToDefaults() {
            if (!confirm('Сбросить все настройки к значениям по умолчанию?')) return;
            this.localConfig = this.mergeConfig(this.initialConfig);
            this.$emit('reset');
            this.$notify?.({
                title: 'Сброшено',
                text: 'Настройки возвращены к значениям по умолчанию',
                type: 'info',
            });
        },
    },
};
</script>



<style lang="scss" scoped>
@use 'sass:color';

// Переменные
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// База
.shop-settings {
    background: $bg;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
}

// Шапка
.settings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: $card-bg;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
    flex-wrap: wrap;
    gap: 16px;
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 4px;

    h2 {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
        color: $text;
        display: flex;
        align-items: center;
        gap: 10px;

        i {
            color: $primary;
        }
    }
}

.header-hint {
    font-size: 0.85rem;
    color: $text-muted;
}

.header-actions {
    display: flex;
    gap: 10px;
}

// Layout
.settings-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

// Sidebar
.settings-sidebar {
    position: sticky;
    top: 100px;
    align-self: start;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}

.settings-nav {
    background: $card-bg;
    border-radius: 16px;
    padding: 8px;
    border: 1px solid $border;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;

    i {
        width: 20px;
        text-align: center;
    }

    &:hover {
        background: rgba($primary, 0.05);
        color: $primary;
    }

    &.active {
        background: $primary;
        color: white;
        font-weight: 600;
    }
}

.nav-badge {
    margin-left: auto;
    padding: 2px 8px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;

    .active & {
        background: rgba(255, 255, 255, 0.25);
    }
}

// Content
.settings-content {
    min-width: 0;
}

.settings-panel {
    background: $card-bg;
    border-radius: 16px;
    padding: 28px;
    border: 1px solid $border;
}

.fade-in {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.panel-header {
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid $border;

    h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0;
    }
}

.panel-header-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

// Формы
.form-group {
    margin-bottom: 18px;

    label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;

        .required {
            color: $danger;
        }

        .hint {
            font-weight: 400;
            color: $text-muted;
            font-size: 0.8rem;
            margin-left: 6px;
        }
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid $border;
        border-radius: 10px;
        font-size: 0.95rem;
        background: $card-bg;
        color: $text;
        transition: all 0.2s;
        font-family: inherit;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }

        &::placeholder {
            color: #9ca3af;
        }
    }

    textarea {
        resize: vertical;
        min-height: 80px;
    }

    .code-textarea {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        line-height: 1.5;
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.search-input {
    width: 100%;
    max-width: 300px;
}

// Цветовая схема
.theme-preview {
    margin-bottom: 24px;
    padding: 20px;
    background: $bg;
    border-radius: 12px;
    border: 1px dashed $border;
}

.preview-card {
    max-width: 300px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background: var(--preview-bg, white);
}

.preview-header {
    padding: 16px;
    color: white;
    font-weight: 700;
    text-align: center;
}

.preview-body {
    padding: 16px;
    text-align: center;
}

.preview-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    cursor: default;
}

.color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.color-item {
    label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;
    }
}

.color-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;

    input[type="color"] {
        width: 44px;
        height: 44px;
        border: 1px solid $border;
        border-radius: 10px;
        cursor: pointer;
        padding: 2px;
        background: $card-bg;
    }

    .hex-input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid $border;
        border-radius: 10px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        text-transform: uppercase;

        &:focus {
            outline: none;
            border-color: $primary;
        }
    }
}

.color-hint {
    display: block;
    font-size: 0.75rem;
    color: $text-muted;
    margin-top: 4px;
}

// Загрузка файлов
.file-upload {
    border: 2px dashed $border;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;

    &:hover {
        border-color: $primary;
        background: rgba($primary, 0.02);
    }

    &.has-file {
        padding: 0;
        border-style: solid;
    }
}

.file-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: $text-muted;

    i {
        font-size: 2rem;
        color: $primary;
    }

    span {
        font-weight: 600;
        color: $text;
    }

    small {
        font-size: 0.8rem;
    }
}

.upload-preview {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 10px;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.remove-image {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $danger;
    }
}

// Списки элементов
.items-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    transition: all 0.2s;

    &:hover {
        border-color: rgba($primary, 0.3);
    }
}

.item-icon-preview {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.item-fields {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;

    input, textarea, select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid $border;
        border-radius: 8px;
        font-size: 0.9rem;
        background: $card-bg;

        &:focus {
            outline: none;
            border-color: $primary;
        }
    }
}

.field-name {
    font-weight: 600;
}

.field-icon {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
}

// Товары
.product-item {
    align-items: flex-start;
}

.product-image-preview {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    overflow: hidden;
    background: $card-bg;
    flex-shrink: 0;
    border: 1px solid $border;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .no-image {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: $text-muted;
        font-size: 1.5rem;
    }
}

.product-fields {
    .price-row, .meta-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
}

.item-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex-shrink: 0;
}

// Отзывы
.review-item {
    align-items: flex-start;
}

.review-avatar-preview {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    overflow: hidden;
    background: $card-bg;
    flex-shrink: 0;
    border: 1px solid $border;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .no-avatar {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: $text-muted;
        font-size: 1.3rem;
    }
}

.rating-input {
    display: flex;
    align-items: center;
    gap: 6px;

    span {
        font-size: 0.85rem;
        color: $text-muted;
        margin-right: 4px;
    }

    i {
        color: #ddd;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;

        &.filled {
            color: $warning;
        }

        &:hover {
            transform: scale(1.2);
        }
    }
}

// Соцсети
.social-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.social-item {
    display: grid;
    grid-template-columns: 1fr 2fr auto;
    gap: 8px;
    align-items: center;

    input {
        padding: 8px 12px;
        border: 1px solid $border;
        border-radius: 8px;
        font-size: 0.9rem;

        &:focus {
            outline: none;
            border-color: $primary;
        }
    }
}

// Кнопки
.btn-primary, .btn-secondary, .btn-add, .btn-add-small {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-primary {
    background: $primary;
    color: white;

    &:hover:not(:disabled) {
        background: $primary-dark;
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.btn-secondary {
    background: $bg;
    color: $text;
    border: 1px solid $border;

    &:hover {
        background: color.adjust($bg, $lightness: -3%);
        border-color: $primary;
        color: $primary;
    }
}

.btn-add {
    background: rgba($primary, 0.1);
    color: $primary;
    border: 1px dashed rgba($primary, 0.3);

    &:hover {
        background: rgba($primary, 0.15);
        border-color: $primary;
    }
}

.btn-add-small {
    padding: 8px 14px;
    font-size: 0.85rem;
    background: rgba($primary, 0.05);
    color: $primary;
    border: 1px dashed rgba($primary, 0.3);

    &:hover {
        background: rgba($primary, 0.1);
    }
}

.btn-remove {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid $border;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $danger;
        border-color: $danger;
        color: white;
    }

    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
}

.btn-upload {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid $border;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $primary;
        border-color: $primary;
        color: white;
    }
}

// Пустое состояние
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: $text-muted;

    i {
        font-size: 3rem;
        margin-bottom: 12px;
        opacity: 0.4;
    }

    p {
        margin-bottom: 16px;
    }
}

// Спиннер
.spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// Адаптив
@media (max-width: 1024px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }

    .settings-sidebar {
        position: static;
        max-height: none;
    }

    .settings-nav {
        flex-direction: row;
        overflow-x: auto;
        padding: 6px;

        &::-webkit-scrollbar {
            height: 4px;
        }

        &::-webkit-scrollbar-thumb {
            background: $primary;
            border-radius: 2px;
        }
    }

    .nav-item {
        white-space: nowrap;
        flex-shrink: 0;
    }
}

@media (max-width: 640px) {
    .settings-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;

        button {
            flex: 1;
        }
    }

    .settings-panel {
        padding: 20px 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .color-grid {
        grid-template-columns: 1fr;
    }

    .item-card {
        flex-direction: column;
        align-items: stretch;
    }

    .item-actions {
        flex-direction: row;
        justify-content: flex-end;
    }

    .product-fields .price-row,
    .product-fields .meta-row {
        grid-template-columns: 1fr;
    }

    .social-item {
        grid-template-columns: 1fr;
    }
}
</style>
