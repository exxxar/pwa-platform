<template>
    <div class="address-list-container mb-3">

        <!-- ========================================== -->
        <!-- HEADER -->
        <!-- ========================================== -->
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
                <button
                    type="button"
                    class="add-btn"
                    @click="openForm"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>Добавить</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК АДРЕСОВ -->
        <!-- ========================================== -->
        <div class="addresses-wrapper">

            <!-- Пустое состояние -->
            <div v-if="displayedAddresses.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <p class="empty-text">Нет сохранённых адресов</p>
                <button class="empty-btn" @click="openForm">
                    <i class="fa-solid fa-plus me-2"></i>
                    Добавить первый адрес
                </button>
            </div>

            <!-- Карточки адресов -->
            <transition-group name="list" tag="div" class="addresses-list">
                <div
                    v-for="item in displayedAddresses"
                    :key="item.id"
                    class="address-card"
                    :class="{
                        'is-selected': location_id == item.id,
                        'is-default': item.is_default
                    }"
                    @click="selectAddress(item)"
                >
                    <!-- Индикатор выбора -->
                    <div class="address-radio">
                        <div class="radio-outer">
                            <div class="radio-inner"></div>
                        </div>
                    </div>

                    <!-- Контент -->
                    <div class="address-content">
                        <div class="address-title-row">
                            <h6 class="address-title">{{ item.title }}</h6>
                            <span v-if="item.is_default" class="default-badge">
                                <i class="fa-solid fa-star"></i>
                                Основной
                            </span>
                        </div>
                        <p class="address-text">{{ item.address }}</p>

                        <!-- Дополнительные детали (при раскрытии) -->
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

                    <!-- Действия (при раскрытии) -->
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
            </transition-group>

        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ФОРМА АДРЕСА -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            ref="modal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content address-modal">
                    <div class="modal-header">
                        <div class="modal-icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title">Новый адрес</h5>
                            <small class="text-muted">Добавьте адрес доставки</small>
                        </div>
                        <button
                            type="button"
                            class="close-btn"
                            @click="onClose"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <AddressForm
                            @close="onClose"
                            @saved="onSaved"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            ref="removeModal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content confirm-modal">
                    <div class="modal-body text-center py-4">
                        <div class="confirm-icon">
                            <i class="fa-solid fa-trash-can"></i>
                        </div>
                        <h5 class="confirm-title">Удалить адрес?</h5>
                        <p class="confirm-text">
                            Это действие нельзя будет отменить
                        </p>
                        <div class="confirm-actions">
                            <button
                                type="button"
                                class="confirm-btn cancel"
                                data-bs-dismiss="modal"
                            >
                                Отмена
                            </button>
                            <button
                                type="button"
                                class="confirm-btn delete"
                                @click="confirmRemove"
                                :disabled="isRemoving"
                            >
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

    components: {
        AddressForm
    },

    props: {
        address: {
            type: String,
            default: '',
        },
        location_id: {
            type: [String, Number],
            default: '',
        },
    },

    emits: ['update:location_id', 'update:address'],

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
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        settings() {
            return this.self?.settings || {};
        },

        displayedAddresses() {
            if (this.isExpanded) return this.addresses;

            // Если выбран конкретный адрес — показываем его
            if (this.location_id) {
                const selected = this.addresses.find(a => a.id == this.location_id);
                if (selected) return [selected];
            }

            // Иначе показываем основной или первый
            const defaultAddr = this.addresses.find(a => a.is_default);
            return defaultAddr ? [defaultAddr] : this.addresses.slice(0, 1);
        },
    },

    mounted() {
        this.addresses = this.self?.addresses || [];

        this.$nextTick(() => {
            if (typeof bootstrap !== 'undefined') {
                if (this.$refs.modal) {
                    this.modalInstance = new bootstrap.Modal(this.$refs.modal);
                }
                if (this.$refs.removeModal) {
                    this.removeModalInstance = new bootstrap.Modal(this.$refs.removeModal);
                }
            }
        });
    },

    beforeUnmount() {
        if (this.modalInstance) this.modalInstance.dispose();
        if (this.removeModalInstance) this.removeModalInstance.dispose();
    },

    methods: {
        selectAddress(item) {
            window.dispatchEvent(new CustomEvent('change-delivery-address', {
                detail: {
                    address: item.address,
                    lng: item.lng,
                    lat: item.lat,
                    city: item.city,
                },
            }));

            this.$emit('update:address', item.address);
            this.$emit('update:location_id', item.id);
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
            // Обновляем список адресов
            this.addresses = this.store.getAddresses || [];
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
                this.addresses = this.store.getAddresses || [];
                this.removeModalInstance?.hide();

                this.$notify?.({
                    title: 'Адрес',
                    text: 'Адрес успешно удалён',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка удаления адреса:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить адрес',
                    type: 'error',
                });
            } finally {
                this.isRemoving = false;
                this.addressToRemove = null;
            }
        },

        async setDefault(item) {
            try {
                await this.store.setDefaultAddress({ id: item.id });
                this.addresses = this.store.getAddresses || [];

                this.$notify?.({
                    title: 'Адрес',
                    text: 'Адрес установлен как основной',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка установки основного адреса:', error);
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
.address-list-container {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
}

/* ==========================================
   HEADER
   ========================================== */
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

/* ==========================================
   СПИСОК АДРЕСОВ
   ========================================== */
.addresses-wrapper {
    padding: 12px;
}

.addresses-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* ==========================================
   КАРТОЧКА АДРЕСА
   ========================================== */
.address-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
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

/* Radio-индикатор */
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

/* Контент */
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

/* Детали адреса */
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

/* Действия */
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

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
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

/* ==========================================
   МОДАЛКА: ФОРМА АДРЕСА
   ========================================== */
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
    padding: 20px;
}

/* ==========================================
   МОДАЛКА: ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ
   ========================================== */
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

/* ==========================================
   АНИМАЦИИ
   ========================================== */
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

/* Transition-group анимации */
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
   АДАПТИВ
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

    .address-card {
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
}
</style>
