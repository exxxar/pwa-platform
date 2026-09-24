<template>
    <div class="hero-slider-wrapper mt-3" v-if="slides.length > 0">
        <!-- ========================================== -->
        <!-- СЛАЙДЕР -->
        <!-- ========================================== -->
        <div
            class="hero-slider"
            ref="sliderRef"
            @scroll.passive="onScroll"
            @touchstart.passive="stopAutoplay"
            @touchend.passive="startAutoplay"
            @mouseenter="stopAutoplay"
            @mouseleave="startAutoplay"
        >
            <!-- СЛАЙД 1: ТЕКУЩИЙ HERO -->
            <div class="hero-slide slide-hero">
                <div class="hero-orb orb-1"></div>
                <div class="hero-orb orb-2"></div>
                <div class="hero-images">
                    <div class="food-image food-image-1"><img v-lazy="heroImages.image1" alt="Food 1"></div>
                    <div class="food-image food-image-2"><img v-lazy="heroImages.image2" alt="Food 2"></div>
                    <div class="food-image food-image-3"><img v-lazy="heroImages.image3" alt="Food 3"></div>
                    <div class="food-image food-image-4"><img v-lazy="heroImages.image4" alt="Food 4"></div>
                </div>
                <div class="hero-content">
                    <h2 class="hero-title">{{ heroSettings.title }}</h2>
                    <p class="hero-subtitle">{{ heroSettings.subtitle }}</p>
                </div>
            </div>

            <!-- СЛАЙДЫ 2+: РЕКЛАМНЫЕ БЛОКИ -->
            <div
                v-for="(slide, i) in promoSlides"
                :key="slide.ad.id || i"
                class="hero-slide slide-promo"
                @click="openPromo(slide)"
            >
                <img class="promo-bg" v-lazy="getAdImage(slide.ad, i)" :alt="slide.ad.title">
                <div class="promo-overlay"></div>

                <span v-if="slide.ad.badge" class="promo-badge">{{ slide.ad.badge }}</span>

                <div class="promo-bottom">
                    <div class="promo-partner">
                        <i :class="slide.source === 'global' ? 'fa-solid fa-bullhorn' : 'fa-solid fa-store'"></i>
                        {{ slide.source === 'global'
                        ? 'Специальное предложение'
                        : (slide.partner?.title || slide.partner?.name) }}
                    </div>
                    <h3 class="promo-title">{{ slide.ad.title }}</h3>
                    <p class="promo-text">{{ slide.ad.short_text }}</p>
                    <button class="promo-btn" @click.stop="openPromo(slide)">
                        {{ slide.ad.button_text || 'Подробнее' }}
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- СТРЕЛКИ + ТОЧКИ -->
        <div class="slider-controls" v-if="slides.length > 1">
            <button class="slider-arrow" :disabled="activeIndex === 0" @click="goToSlide(activeIndex - 1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="slider-dots">
                <button
                    v-for="(s, i) in slides"
                    :key="i"
                    class="dot"
                    :class="{ active: i === activeIndex }"
                    @click="goToSlide(i)"
                ></button>
            </div>
            <button class="slider-arrow" :disabled="activeIndex === slides.length - 1" @click="goToSlide(activeIndex + 1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- BOTTOM SHEET: РЕКЛАМНАЯ МОДАЛКА -->
        <!-- ========================================== -->
        <transition name="bottom-sheet">
            <div v-if="showPromoModal && selectedPromo" class="promo-modal-backdrop" @click="closePromo">
                <div class="promo-modal-sheet" @click.stop>
                    <div class="modal-handle"></div>

                    <div class="promo-modal-image">
                        <img :src="getAdImage(selectedPromo.ad, 0)" :alt="selectedPromo.ad.title">
                        <span v-if="selectedPromo.ad.badge" class="promo-badge">{{ selectedPromo.ad.badge }}</span>
                        <button class="promo-modal-close" @click="closePromo">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="promo-modal-body">
                        <div class="promo-modal-partner">
                            <i class="fa-solid fa-store"></i>
                            {{ selectedPromo.partner?.title || selectedPromo.partner?.name }}
                        </div>
                        <h3 class="promo-modal-title">{{ selectedPromo.ad.title }}</h3>
                        <p class="promo-modal-text">
                            {{ selectedPromo.ad.full_text || selectedPromo.ad.short_text }}
                        </p>
                    </div>

                    <div class="promo-modal-footer">
                        <button class="promo-modal-btn" @click="promoAction">
                            <i :class="modalActionIcon"></i>
                            {{ modalButtonText }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    name: 'HeroPromoSlider',
    props: {
        heroSettings: { type: Object, default: () => ({}) },
        heroImages: { type: Object, default: () => ({}) },
        partners: { type: Array, default: () => [] },
        autoplay: { type: Boolean, default: true },
    },
    emits: ['select-partner'],
    data() {
        return {
            activeIndex: 0,
            showPromoModal: false,
            selectedPromo: null,
            autoplayTimer: null,
            globalAds: [],
        };
    },
    computed: {
        // Собираем активные рекламы из всех партнёров
        promoSlides() {
            const list = [];

            // 1) Глобальная реклама (с бэка)
            this.globalAds.forEach(ad => {
                if (ad.is_active === false) return;
                list.push({
                    ad,
                    partner: null,
                    source: 'global',
                    sortPos: ad.position ?? 0,
                });
            });

            // 2) Реклама партнёров (из конфига)
            (this.partners || []).forEach(partner => {
                this.getPartnerAds(partner).forEach(ad => {
                    if (ad.is_active === false) return;
                    list.push({
                        ad,
                        partner,
                        source: 'partner',
                        sortPos: ad.order_position ?? partner.order_position ?? 999,
                    });
                });
            });

            // Объединяем и сортируем
            return list.sort((a, b) => a.sortPos - b.sortPos);
        },
        // 1-й слайд hero + рекламы
        slides() {
            return [{ type: 'hero' }, ...this.promoSlides];
        },
        modalButtonText() {
            const ad = this.selectedPromo?.ad;
            if (!ad) return '';
            return ad.action_type === 'url'
                ? (ad.button_text || 'Перейти')
                : 'Перейти к заведению';
        },
        modalActionIcon() {
            return this.selectedPromo?.ad?.action_type === 'url'
                ? 'fa-solid fa-arrow-up-right-from-square'
                : 'fa-solid fa-store';
        },
    },
    mounted() {
        this.loadGlobalAds();
        this.startAutoplay();
    },
    beforeUnmount() {
        this.stopAutoplay();
    },
    watch: {
        showPromoModal(val) {
            val ? this.stopAutoplay() : this.startAutoplay();
        },
    },
    methods: {
        async loadGlobalAds() {
            try {
                // Используем мобильный API вместо админского
                const { useMobileApi } = await import('@/MobileClient/composables/useMobileApi.js')
                const mobileApi = useMobileApi()

                const payload = await mobileApi.get('/promotions')

                // Мобильный API возвращает { ads: [...] } с полем source
                this.globalAds = (payload.ads || payload.items || []).filter(ad => {
                    // Пропускаем неактивные
                    if (ad.is_active === false || ad.is_active === '0' || ad.is_active === 0) {
                        return false
                    }

                    // Если есть поле source - фильтруем по нему
                    if (ad.source) {
                        return ad.source === 'global'
                    }

                    // Если source нет (админский API) - берём все
                    return true
                })

                console.log('✅ Загружено реклам:', this.globalAds.length)
            } catch (e) {
                console.warn('❌ Не удалось загрузить рекламу:', e)
                this.globalAds = []
            }
        },
        getPartnerAds(partner) {
            let config = partner?.config;
            if (typeof config === 'string') {
                try { config = JSON.parse(config); } catch (e) { config = {}; }
            }
            const ads = config?.ads || partner?.ads;
            return Array.isArray(ads) ? ads : [];
        },

        // 🎯 Картинка: реклама → настройки системы → дефолты
        getAdImage(ad, index) {
            if (ad.image) return ad.image;
            const sys = this.systemImages;
            if (sys.length) return sys[index % sys.length];
            const defaults = [
                '/images/fastoran/pizza.png',
                '/images/fastoran/burger.png',
                '/images/fastoran/suschi.png',
                '/images/fastoran/khachapuri.png',
            ];
            return defaults[index % defaults.length];
        },
        get systemImages() {
            const tenant = window.Tenant || {};
            const ui = tenant?.meta?.partners?.ui || tenant?.settings?.partners?.ui || {};
            const hero = ui.hero || {};
            return [
                ...(Array.isArray(ui.promo_images) ? ui.promo_images : []),
                hero.bg_image_1, hero.bg_image_2, hero.bg_image_3, hero.bg_image_4,
            ].filter(Boolean);
        },

        // ---------- СЛАЙДЕР ----------
        onScroll() {
            const el = this.$refs.sliderRef;
            if (!el) return;
            this.activeIndex = Math.round(el.scrollLeft / el.clientWidth);
        },
        goToSlide(i) {
            const el = this.$refs.sliderRef;
            if (!el || i < 0 || i >= this.slides.length) return;
            el.scrollTo({ left: i * el.clientWidth, behavior: 'smooth' });
        },
        startAutoplay() {
            if (!this.autoplay || this.slides.length < 2) return;
            this.stopAutoplay();
            this.autoplayTimer = setInterval(() => {
                const next = (this.activeIndex + 1) % this.slides.length;
                this.goToSlide(next);
            }, 6000);
        },
        stopAutoplay() {
            if (this.autoplayTimer) clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        },

        // ---------- МОДАЛКА ----------
        openPromo(slide) {
            this.selectedPromo = slide;
            this.showPromoModal = true;
            document.body.style.overflow = 'hidden';
        },
        closePromo() {
            this.showPromoModal = false;
            document.body.style.overflow = '';
        },
        promoAction() {
            const { ad, partner } = this.selectedPromo || {};
            this.closePromo();
            if (!ad) return;
            if (ad.action_type === 'url' && ad.action_value) {
                window.open(ad.action_value, '_blank');
            } else {
                this.$emit('select-partner', partner);
            }
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;

.hero-slider-wrapper {
    position: relative;
    margin: 0 16px 16px;
}

// ==========================================
// СЛАЙДЕР
// ==========================================
.hero-slider {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    border-radius: 24px;
    &::-webkit-scrollbar { display: none; }
    scrollbar-width: none;
}

.hero-slide {
    position: relative;
    flex: 0 0 100%;
    min-width: 100%;
    height: 360px;
    scroll-snap-align: start;
    overflow: hidden;
    border-radius: 24px;
    @media (max-width: 768px) { height: 320px; }
}

// --- Слайд HERO (текущее содержимое) ---
.slide-hero {
    background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    text-align: center;
}

.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
    &.orb-1 { width: 220px; height: 220px; background: #3b82f6; top: -80px; left: -80px; }
    &.orb-2 { width: 200px; height: 200px; background: #8b5cf6; bottom: -60px; right: -60px; }
}

.hero-images {
    position: relative;
    width: 100%;
    max-width: 420px;
    height: 150px;
    margin-bottom: 16px;
    z-index: 1;
}

.food-image {
    position: absolute;
    border-radius: 16px;
    overflow: hidden;
    border: 3px solid #fff;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
    img { width: 100%; height: 100%; object-fit: cover; }
    &.food-image-1 { width: 100px; height: 100px; top: 0; left: 8%; transform: rotate(-6deg); }
    &.food-image-2 { width: 120px; height: 120px; top: 14px; left: 34%; z-index: 2; }
    &.food-image-3 { width: 100px; height: 100px; top: 0; right: 8%; transform: rotate(6deg); }
    &.food-image-4 { width: 86px; height: 86px; bottom: 0; left: 50%; transform: translateX(-50%) rotate(3deg); z-index: 3; }
}

.hero-content { position: relative; z-index: 2; }
.hero-title { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0 0 8px; line-height: 1.2; }
.hero-subtitle { font-size: 0.95rem; color: #64748b; margin: 0; }

// --- Слайд PROMO ---
.slide-promo {
    cursor: pointer;
    background: #eef2ff;
}

.promo-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
    .slide-promo:active & { transform: scale(1.04); }
}

.promo-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0) 35%, rgba(0,0,0,0.78) 100%);
}

.promo-badge {
    position: absolute;
    top: 14px;
    left: 14px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
    color: #fff;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4);
    z-index: 2;
}

.promo-bottom {
    position: absolute;
    left: 0; right: 0; bottom: 0;
    padding: 16px;
    z-index: 2;
    color: #fff;
}

.promo-partner {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    opacity: 0.85;
    margin-bottom: 4px;
    i { font-size: 0.7rem; }
}

.promo-title {
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0 0 6px;
    line-height: 1.2;
}

// 🎯 небольшой текст внизу, обрезается (2 строки)
.promo-text {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 0.85rem;
    line-height: 1.4;
    opacity: 0.9;
    margin: 0 0 12px;
}

.promo-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #fff;
    color: $primary-dark;
    border: none;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
    transition: transform 0.2s;
    &:active { transform: scale(0.96); }
}

// ==========================================
// КОНТРОЛЫ
// ==========================================
.slider-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    margin-top: 12px;
}

.slider-arrow {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.08);
    background: #fff;
    color: $primary;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.2s;
    &:disabled { opacity: 0.35; cursor: not-allowed; }
    &:not(:disabled):hover { background: $primary; color: #fff; }
    @media (max-width: 768px) { display: none; }
}

.slider-dots {
    display: flex;
    gap: 6px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 10px;
    border: none;
    padding: 0;
    background: rgba(0,0,0,0.15);
    cursor: pointer;
    transition: all 0.3s;
    &.active { width: 22px; background: $primary; }
}

// ==========================================
// BOTTOM SHEET МОДАЛКА
// ==========================================
.promo-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1100;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.promo-modal-sheet {
    background: #fff;
    width: 100%;
    max-width: 600px;
    max-height: 88vh;
    border-radius: 24px 24px 0 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.25);
}

.modal-handle {
    width: 40px;
    height: 4px;
    background: #d1d5db;
    border-radius: 2px;
    margin: 10px auto 6px;
    flex-shrink: 0;
}

.promo-modal-image {
    position: relative;
    height: 200px;
    flex-shrink: 0;
    img { width: 100%; height: 100%; object-fit: cover; }
    .promo-badge { top: 12px; left: 12px; }
}

.promo-modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(6px);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
}

.promo-modal-body {
    padding: 16px 20px;
    overflow-y: auto;
    flex: 1;
}

.promo-modal-partner {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $primary;
    margin-bottom: 6px;
}

.promo-modal-title {
    font-size: 1.3rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 10px;
}

.promo-modal-text {
    font-size: 0.92rem;
    line-height: 1.6;
    color: #475569;
    white-space: pre-line;
    margin: 0;
}

.promo-modal-footer {
    padding: 12px 20px calc(12px + env(safe-area-inset-bottom));
    border-top: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.promo-modal-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: #fff;
    border: none;
    border-radius: 14px;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba($primary, 0.35);
    &:active { transform: scale(0.98); }
}

.bottom-sheet-enter-active,
.bottom-sheet-leave-active {
    transition: opacity 0.3s ease;
    .promo-modal-sheet { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
}
.bottom-sheet-enter-from,
.bottom-sheet-leave-to {
    opacity: 0;
    .promo-modal-sheet { transform: translateY(100%); }
}
</style>
