<template>
    <div class="address-list-container mb-3">
        <!-- HEADER -->
        <div class="address-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="header-info">
                    <h6 class="header-title">Мои адреса</h6>
                    <p class="header-subtitle">
                        {{ addresses.length }}
                        {{ pluralize(addresses.length, 'адрес', 'адреса', 'адресов') }}
                    </p>
                </div>
            </div>
            <div class="header-actions">
                <button
                    v-if="addresses.length > 1"
                    type="button"
                    class="toggle-btn"
                    :class="{ 'expanded': isExpanded }"
                    @click="toggle"
                    :title="isExpanded ? 'Свернуть' : 'Показать все'"
                >
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <button type="button" class="add-btn" @click="openForm">
                    <i class="fa-solid fa-plus"></i>
                    <span>Добавить</span>
                </button>
            </div>
        </div>

        <!-- СПИСОК АДРЕСОВ -->
        <div class="addresses-wrapper">
            <div v-if="displayedAddresses.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <p class="empty-text">Нет сохранённых адресов</p>
                <button type="button" class="empty-btn" @click="openForm">
                    <i class="fa-solid fa-plus me-2"></i>
                    Добавить первый адрес
                </button>
            </div>

            <transition-group name="list" tag="div" class="addresses-list">
                <div
                    v-for="item in displayedAddresses"
                    :key="item.id"
                    class="address-card"
                    :data-address-id="item.id"
                    :class="{
        'is-selected': modelValue?.id === item.id,
        'is-default': item.is_default
    }"
                    @click="selectAddress(item)"
                >
                    <!-- 🎯 ВНУТРЕННИЙ КОНТЕЙНЕР -->
                    <div class="address-card-inner">
                        <div class="address-radio">
                            <div class="radio-outer">
                                <div class="radio-inner"></div>
                            </div>
                        </div>

                        <div class="address-content">
                            <div class="address-title-row">
                                <h6 class="address-title">{{ item.title }}</h6>
                                <span v-if="item.is_default" class="default-badge">
                    <i class="fa-solid fa-star"></i>
                    Основной
                </span>
                            </div>
                            <p class="address-text">{{ item.address }}</p>

                            <transition name="slide-down">
                                <div v-if="isExpanded" class="address-details">
                                    <div v-if="item.city" class="detail-item">
                                        <i class="fa-solid fa-city"></i>
                                        <span>{{ item.city }}</span>
                                    </div>
                                    <div v-if="item.street" class="detail-item">
                                        <i class="fa-solid fa-road"></i>
                                        <span>{{ item.street }}</span>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <transition name="fade">
                            <div v-if="isExpanded" class="address-actions">
                                <button
                                    v-if="!item.is_default"
                                    type="button"
                                    class="action-btn set-default-btn"
                                    @click.stop="setDefault(item)"
                                    title="Сделать основным"
                                >
                                    <i class="fa-solid fa-star"></i>
                                </button>
                                <button
                                    type="button"
                                    class="action-btn remove-btn"
                                    @click.stop="remove(item.id)"
                                    title="Удалить адрес"
                                >
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </transition>
                    </div>

                    <!-- 🎯 SWIPE-ТРЕК (теперь отдельный элемент снизу карточки) -->
                    <div
                        v-if=" !isExpanded && modelValue?.id === item.id"
                        class="swipe-track"
                        @mousedown="onSwipeStart($event, item)"
                        @touchstart="onSwipeStart($event, item)"
                        @click.stop
                    >
                        <div class="swipe-track-bg">
                            <div class="swipe-progress" :style="{ width: swipeProgress + '%' }"></div>
                            <div class="swipe-hint" :class="{ 'hidden': swipeProgress > 10 }">
                                <i class="fa-solid fa-arrow-right-long"></i>
                                <span>Проведите для подтверждения</span>
                            </div>
                            <div class="swipe-success-text" :class="{ 'visible': swipeProgress > 70 }">
                                <i class="fa-solid fa-check"></i>
                                <span>Адрес выбран!</span>
                            </div>
                        </div>
                        <div
                            class="swipe-thumb"
                            :class="{
                'dragging': isDragging,
                'completed': swipeProgress >= 100
            }"
                            :style="{ transform: `translateX(${swipeTranslate}px)` }"
                        >
                            <i :class="swipeProgress >= 100 ? 'fa-solid fa-check' : 'fa-solid fa-chevron-right'"></i>
                        </div>
                    </div>
                </div>
            </transition-group>
        </div>

        <!-- МОДАЛКИ -->
        <div class="modal fade" ref="modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content address-modal">
                    <div class="modal-header">
                        <div class="modal-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title">Новый адрес</h5>
                            <small class="text-muted">Добавьте адрес доставки</small>
                        </div>
                        <button type="button" class="close-btn" @click="onClose">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <AddressForm @close="onClose" @saved="onSaved" />
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" ref="removeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content confirm-modal">
                    <div class="modal-body text-center py-4">
                        <div class="confirm-icon"><i class="fa-solid fa-trash-can"></i></div>
                        <h5 class="confirm-title">Удалить адрес?</h5>
                        <p class="confirm-text">Это действие нельзя будет отменить</p>
                        <div class="confirm-actions">
                            <button type="button" class="confirm-btn cancel" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="confirm-btn delete" @click="confirmRemove" :disabled="isRemoving">
                                <span v-if="isRemoving" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="fa-solid fa-trash-can me-2"></i>
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import AddressForm from "./AddressForm.vue";
import { useAddressesStore } from "@/MobileClient/stores/Shop/addresses.js";

export default {
    name: "AddressList",
    components: { AddressForm },

    props: {
        modelValue: {
            type: Object,
            default: null
        }
    },

    emits: ['update:modelValue'],

    setup() {
        const store = useAddressesStore();
        return { store };
    },

    data() {
        return {
            addresses: [],
            isExpanded: false,
            modalInstance: null,
            removeModalInstance: null,
            addressToRemove: null,
            isRemoving: false,

            // Swipe state
            isDragging: false,
            swipeTranslate: 0,
            swipeProgress: 0,
            swipeStartX: 0,
            swipeTrackWidth: 0,
            swipeThreshold: 0.7,
            currentSwipeItem: null,
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        displayedAddresses() {
            if (this.isExpanded) return this.addresses;

            if (this.modelValue?.id) {
                const selected = this.addresses.find(a => a.id === this.modelValue.id);
                if (selected) return [selected];
            }

            const defaultAddr = this.addresses.find(a => a.is_default);
            return defaultAddr ? [defaultAddr] : this.addresses.slice(0, 1);
        },
    },

    mounted() {
        this.addresses = this.self?.addresses || [];
        this.$nextTick(() => {
            if (typeof bootstrap !== 'undefined') {
                if (this.$refs.modal) this.modalInstance = new bootstrap.Modal(this.$refs.modal);
                if (this.$refs.removeModal) this.removeModalInstance = new bootstrap.Modal(this.$refs.removeModal);
            }
        });
    },

    beforeUnmount() {
        if (this.modalInstance) this.modalInstance.dispose();
        if (this.removeModalInstance) this.removeModalInstance.dispose();
    },

    methods: {
        async selectAddress(item) {
            if (this.modelValue?.id === item.id) {
                return;
            }

            // 🎯 ПРОВЕРКА: список развёрнут и карточка не в видимой области?
            const wasExpanded = this.isExpanded;
            const cardElement = document.querySelector(`[data-address-id="${item.id}"]`);

            if (wasExpanded && cardElement) {
                // 1. СНАЧАЛА скроллим к карточке (плавный скролл)
                const cardRect = cardElement.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const isCardOutOfView = cardRect.top < 80 || cardRect.bottom > viewportHeight - 80;

                if (isCardOutOfView) {
                    cardElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                }

                // 2. НЕБОЛЬШАЯ ЗАДЕРЖКА перед сворачиванием (чтобы скролл успел отобразиться)
                await new Promise(resolve => setTimeout(resolve, 250));
            }

            // 3. ТЕПЕРЬ обновляем modelValue
            this.$emit('update:modelValue', item);

            // 4. Flash-анимация на выбранной карточке
            this.$nextTick(() => {
                const selectedCard = document.querySelector(`.address-card.is-selected`);
                if (selectedCard) {
                    selectedCard.classList.add('flash-reload');
                    setTimeout(() => selectedCard.classList.remove('flash-reload'), 600);
                }
            });

            // 5. СВОРАЧИВАЕМ список (если был развёрнут) — с задержкой
            if (wasExpanded) {
                await new Promise(resolve => setTimeout(resolve, 150));
                this.isExpanded = false;
            }

            // 6. Диспатчим событие для пересчёта доставки
            window.dispatchEvent(new CustomEvent('change-delivery-address', {
                detail: {
                    address: item.address,
                    lng: item.lng,
                    lat: item.lat,
                    city: item.city,
                    location_id: item.id,
                },
            }));
        },

        toggle() {
            this.isExpanded = !this.isExpanded;
        },

        openForm() {
            this.modalInstance?.show();
        },

        onClose() {
            this.modalInstance?.hide();
        },

        async onSaved() {
            this.modalInstance?.hide();

            const freshAddresses = this.store.getAddresses;
            if (freshAddresses && freshAddresses.length > 0) {
                this.addresses = freshAddresses;
                if (this.self) this.self.addresses = freshAddresses;
            }

            if (this.modelValue?.id) {
                const updated = this.addresses.find(a => a.id === this.modelValue.id);
                if (updated) this.$emit('update:modelValue', updated);
            }
        },

        remove(id) {
            this.addressToRemove = id;
            this.removeModalInstance?.show();
        },

        async confirmRemove() {
            if (!this.addressToRemove) return;
            this.isRemoving = true;
            try {
                await this.store.removeAddress({ id: this.addressToRemove });

                this.addresses = this.addresses.filter(addr => addr.id !== this.addressToRemove);

                if (this.self && this.self.addresses) {
                    this.self.addresses = this.addresses;
                }

                this.removeModalInstance?.hide();

                if (this.modelValue?.id === this.addressToRemove) {
                    this.$emit('update:modelValue', null);
                }

                this.$notify?.({ title: 'Адрес', text: 'Адрес успешно удалён', type: 'success' });
            } catch (error) {
                console.error('Ошибка удаления адреса:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось удалить адрес', type: 'error' });
            } finally {
                this.isRemoving = false;
                this.addressToRemove = null;
            }
        },

        async setDefault(item) {
            try {
                await this.store.setDefaultAddress({ id: item.id });

                this.addresses = this.addresses.map(addr => ({
                    ...addr,
                    is_default: addr.id === item.id
                }));

                if (this.self) this.self.addresses = this.addresses;

                this.$notify?.({ title: 'Адрес', text: 'Адрес установлен как основной', type: 'success' });
            } catch (error) {
                console.error('Ошибка установки основного адреса:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось обновить адрес', type: 'error' });
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

        onSwipeStart(e, item) {
            this.currentSwipeItem = item;
            this.isDragging = true;

            const track = e.currentTarget;
            this.swipeTrackWidth = track.offsetWidth - 56;

            const point = e.touches ? e.touches[0] : e;
            this.swipeStartX = point.clientX;

            document.body.style.overflow = 'hidden';

            if (e.type === 'touchstart') {
                document.addEventListener('touchmove', this.onSwipeMove, { passive: false });
                document.addEventListener('touchend', this.onSwipeEnd);
            } else {
                document.addEventListener('mousemove', this.onSwipeMove);
                document.addEventListener('mouseup', this.onSwipeEnd);
            }

            e.preventDefault();
        },

        onSwipeMove(e) {
            if (!this.isDragging) return;

            const point = e.touches ? e.touches[0] : e;
            const deltaX = point.clientX - this.swipeStartX;

            const clampedDelta = Math.max(0, deltaX);

            let translate;
            if (clampedDelta >= this.swipeTrackWidth) {
                const overflow = clampedDelta - this.swipeTrackWidth;
                translate = this.swipeTrackWidth + overflow * 0.3;
            } else {
                translate = clampedDelta;
            }

            this.swipeTranslate = translate;
            this.swipeProgress = Math.min(100, (translate / this.swipeTrackWidth) * 100);

            if (e.cancelable && e.type === 'touchmove') {
                e.preventDefault();
            }
        },

        onSwipeEnd() {
            if (!this.isDragging) return;

            document.removeEventListener('touchmove', this.onSwipeMove);
            document.removeEventListener('touchend', this.onSwipeEnd);
            document.removeEventListener('mousemove', this.onSwipeMove);
            document.removeEventListener('mouseup', this.onSwipeEnd);
            document.body.style.overflow = '';

            const completed = this.swipeProgress >= this.swipeThreshold * 100;

            if (completed && this.currentSwipeItem) {
                const item = this.currentSwipeItem;
                this.swipeTranslate = this.swipeTrackWidth;
                this.swipeProgress = 100;

                // 🎯 Показываем "успех" короткое время, потом плавно возвращаем
                setTimeout(() => {
                    // ❌ УБРАЛИ: this.$emit('update:modelValue', null) — именно он вызывал скачки!

                    // ✅ Вместо этого — просто диспатчим событие повторно
                    // Это и есть "перезагрузка" для расчёта доставки
                    window.dispatchEvent(new CustomEvent('change-delivery-address', {
                        detail: {
                            address: item.address,
                            lng: item.lng,
                            lat: item.lat,
                            city: item.city,
                            location_id: item.id,
                            _retrigger: Date.now(), // 🆕 добавляем timestamp чтобы событие гарантированно обработалось
                        },
                    }));

                    // 🎨 Flash-анимация на карточке
                    const selectedCard = document.querySelector(`[data-address-id="${item.id}"]`);
                    if (selectedCard) {
                        selectedCard.classList.add('flash-reload');
                        setTimeout(() => selectedCard.classList.remove('flash-reload'), 600);
                    }

                    // ✅ Уведомление о том, что расчёт обновлён
                    this.$notify?.({
                        title: 'Адрес подтверждён',
                        text: 'Расчёт доставки обновлён',
                        type: 'success',
                    });

                    // 🎯 Плавный возврат ползунка в начало
                    this.resetSwipe();
                }, 500); // 500ms — чтобы пользователь успел увидеть "Адрес выбран!"
            } else {
                // Не дотянули — плавно возвращаем
                this.resetSwipe();
            }

            this.isDragging = false;
        },

        resetSwipe() {
            this.swipeTranslate = 0;
            this.swipeProgress = 0;
            this.currentSwipeItem = null;
        },
    },
};
</script>

<style scoped lang="scss">
.address-list-container {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
}

.address-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    border-bottom: 1px solid var(--bs-border-color);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.header-info {
    display: flex;
    flex-direction: column;
}

.header-title {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.header-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.toggle-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.toggle-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.toggle-btn.expanded i {
    transform: rotate(180deg);
}

.toggle-btn i {
    transition: transform 0.3s ease;
}

.add-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.25);
}

.add-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(var(--bs-primary-rgb), 0.35);
}

.add-btn i {
    font-size: 0.8rem;
}

.addresses-wrapper {
    padding: 12px;
}

.addresses-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* 🎯 ИСПРАВЛЕННАЯ КАРТОЧКА АДРЕСА */
.address-card {
    display: flex;
    flex-direction: column; /* Контейнер для карточки + swipe */
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden; /* Важно для swipe-track */
}

.address-card:hover {
    border-color: rgba(var(--bs-primary-rgb), 0.3);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.08);
}

.address-card.is-selected {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.15);
}

.address-card.is-default {
    border-color: rgba(25, 135, 84, 0.3);
}

.address-card.is-default.is-selected {
    border-color: var(--bs-primary);
}

/* 🎯 ВНУТРЕННИЙ КОНТЕЙНЕР (radio + content + actions) */
.address-card-inner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    position: relative;
}

.address-radio {
    flex-shrink: 0;
    padding-top: 2px;
}

.radio-outer {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.address-card.is-selected .radio-outer {
    border-color: var(--bs-primary);
}

.radio-inner {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--bs-primary);
    transform: scale(0);
    transition: transform 0.2s ease;
}

.address-card.is-selected .radio-inner {
    transform: scale(1);
}

.address-content {
    flex: 1;
    min-width: 0;
}

.address-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    flex-wrap: wrap;
}

.address-title {
    margin: 0;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.default-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
}

.default-badge i {
    font-size: 0.6rem;
}

.address-text {
    margin: 0;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    line-height: 1.4;
}

.address-details {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed var(--bs-border-color);
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.detail-item i {
    color: var(--bs-primary);
    font-size: 0.7rem;
}

.address-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex-shrink: 0;
}

.action-btn {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.set-default-btn:hover {
    background: rgba(25, 135, 84, 0.1);
    border-color: #198754;
    color: #198754;
}

.remove-btn:hover {
    background: rgba(220, 53, 69, 0.1);
    border-color: #dc3545;
    color: #dc3545;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 16px;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    background: var(--bs-primary);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.address-modal {
    border-radius: 16px;
    border: none;
    overflow: hidden;
}

.address-modal .modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
    display: flex;
    align-items: center;
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.address-modal .modal-title {
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
}

.close-btn:hover {
    background: #dc3545;
    color: white;
    transform: rotate(90deg);
}

.address-modal .modal-body {
    padding: 20px 10px;
}

.confirm-modal {
    border-radius: 20px;
    border: none;
}

.confirm-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;
}

.confirm-title {
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.confirm-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 24px;
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

.confirm-btn {
    flex: 1;
    padding: 12px 16px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.confirm-btn.cancel {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
}

.confirm-btn.cancel:hover {
    background: var(--bs-border-color);
}

.confirm-btn.delete {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.confirm-btn.delete:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(220, 53, 69, 0.4);
}

.confirm-btn.delete:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 100px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateX(-20px);
}

.list-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

.list-move {
    transition: transform 0.3s ease;
}

/* ==========================================
   🎯 SWIPE-TO-SELECT (ИСПРАВЛЕНО)
   ========================================== */
.swipe-track {
    position: relative;
    height: 48px;
    width: 100%;
    overflow: hidden;
    user-select: none;
    -webkit-user-select: none;
    touch-action: none;
    cursor: grab;
    border-top: 1px solid var(--bs-border-color);
}

.swipe-track:active {
    cursor: grabbing;
}

.swipe-track-bg {
    position: relative;
    height: 100%;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.15) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.swipe-progress {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    background: linear-gradient(90deg,
    rgba(var(--bs-primary-rgb), 0.15) 0%,
    rgba(var(--bs-primary-rgb), 0.35) 70%,
    rgba(25, 135, 84, 0.4) 100%);
    transition: none;
    pointer-events: none;
}

.swipe-hint {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--bs-primary);
    font-size: 0.85rem;
    font-weight: 600;
    opacity: 1;
    transition: opacity 0.2s ease;
    pointer-events: none;
    z-index: 1;
}

.swipe-hint.hidden {
    opacity: 0;
}

.swipe-hint i {
    font-size: 0.9rem;
    animation: swipeHintPulse 1.5s ease-in-out infinite;
}

@keyframes swipeHintPulse {
    0%, 100% {
        transform: translateX(0);
        opacity: 0.7;
    }
    50% {
        transform: translateX(6px);
        opacity: 1;
    }
}

.swipe-success-text {
    position: absolute;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #198754;
    font-size: 0.9rem;
    font-weight: 700;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
    z-index: 1;
}

.swipe-success-text.visible {
    opacity: 1;
    transform: scale(1);
}

.swipe-success-text i {
    font-size: 1rem;
}

.swipe-thumb {
    position: absolute;
    left: 0;
    top: 0;
    width: 56px;
    height: 48px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    box-shadow:
        0 4px 12px rgba(var(--bs-primary-rgb), 0.35),
        0 2px 4px rgba(0, 0, 0, 0.1);
    /* 🎯 ВАЖНО: более медленный transition для плавного возврата */
    transition:
        transform 0.45s cubic-bezier(0.25, 1, 0.5, 1),
        background 0.3s ease,
        box-shadow 0.3s ease;
    z-index: 2;
    pointer-events: none;
}

/* Во время перетаскивания — БЕЗ transition (мгновенный отклик) */
.swipe-thumb.dragging {
    transition: none;
    box-shadow:
        0 8px 20px rgba(var(--bs-primary-rgb), 0.45),
        0 4px 8px rgba(0, 0, 0, 0.15);
}

.swipe-thumb.completed {
    background: linear-gradient(135deg, #198754 0%, #157347 100%);
    box-shadow:
        0 8px 20px rgba(25, 135, 84, 0.5),
        0 4px 8px rgba(0, 0, 0, 0.15);
    transform: scale(1.05);
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.swipe-thumb.dragging {
    transition: none;
    box-shadow:
        0 8px 20px rgba(var(--bs-primary-rgb), 0.45),
        0 4px 8px rgba(0, 0, 0, 0.15);
}

.swipe-thumb.completed {
    background: linear-gradient(135deg, #198754 0%, #157347 100%);
    box-shadow:
        0 8px 20px rgba(25, 135, 84, 0.5),
        0 4px 8px rgba(0, 0, 0, 0.15);
    transform: scale(1.05);
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes addressFlash {
    0% {
        background: rgba(25, 135, 84, 0.3);
        transform: scale(1);
    }
    50% {
        background: rgba(25, 135, 84, 0.15);
        transform: scale(1.02);
    }
    100% {
        background: transparent;
        transform: scale(1);
    }
}

.address-card.is-selected.flash-reload {
    animation: addressFlash 0.6s ease-out;
}

/* ==========================================
   🎯 АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .address-header {
        padding: 12px;
    }

    .header-icon {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }

    .header-title {
        font-size: 0.9rem;
    }

    .add-btn span {
        display: none;
    }

    .add-btn {
        padding: 8px 10px;
    }

    .address-card-inner {
        padding: 12px;
        gap: 10px;
    }

    .address-title {
        font-size: 0.9rem;
    }

    .address-text {
        font-size: 0.8rem;
    }

    .action-btn {
        width: 30px;
        height: 30px;
        font-size: 0.75rem;
    }

    .swipe-track {
        height: 44px;
    }

    .swipe-thumb {
        width: 50px;
        height: 44px;
        /* 🎯 transition сохраняем и для мобилок */
        transition:
            transform 0.45s cubic-bezier(0.25, 1, 0.5, 1),
            background 0.3s ease,
            box-shadow 0.3s ease;
    }

    .swipe-thumb.dragging {
        transition: none;
    }

    .swipe-hint {
        font-size: 0.8rem;
        gap: 8px;
    }
}
</style>
