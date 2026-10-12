<template>
    <div class="modal-container assignment-modal">
        <!-- Шапка -->
        <div class="modal-header">
            <div>
                <h3>Назначение магазинов</h3>
                <p class="modal-subtitle" v-if="user">
                    Курьер: <strong>{{ user.name || 'Без имени' }}</strong> (ID: {{ user.id }})
                </p>
            </div>
            <button class="modal-close" @click="$emit('close')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-body">
            <!-- Загрузка -->
            <div v-if="isLoading" class="loading-state">
                <div class="spinner"></div>
                <p>Загрузка списка магазинов...</p>
            </div>

            <!-- Список магазинов -->
            <div v-else class="shops-list">
                <div
                    v-for="shop in shops"
                    :key="shop.id"
                    class="shop-item"
                    :class="{ 'is-assigned': isAssigned(shop.id) }"
                    @click="toggleShop(shop.id)"
                >
                    <div class="shop-checkbox">
                        <i v-if="isAssigned(shop.id)" class="fa-solid fa-check"></i>
                    </div>
                    <div class="shop-info">
                        <div class="shop-avatar">
                            <img v-if="shop.image" :src="shop.image" :alt="shop.name">
                            <i v-else class="fa-solid fa-store"></i>
                        </div>
                        <div class="shop-details">
                            <h4>{{ shop.name }}</h4>
                            <p>{{ shop.address || 'Адрес не указан' }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="shops.length === 0" class="empty-state">
                    <i class="fa-solid fa-store-slash"></i>
                    <p>Нет доступных магазинов для назначения</p>
                </div>
            </div>
        </div>

        <!-- Футер -->
        <div class="modal-footer">
            <button class="btn-secondary" @click="$emit('close')">Отмена</button>
            <button class="btn-primary" @click="saveAssignment" :disabled="isSaving || !hasChanges">
                <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                <span v-else><i class="fa-solid fa-check"></i> Сохранить</span>
            </button>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'DeliverymanShopAssignmentModal',
    props: {
        user: { type: Object, required: true }
    },
    emits: ['close', 'saved'],
    data() {
        return {
            shops: [],
            assignedIds: [],
            initialAssignedIds: [],
            isLoading: true,
            isSaving: false,
        };
    },
    watch: {
        // 🆕 Следим за объектом user. Если он обновился извне,
        // пересчитываем выбранные магазины
        user: {
            handler(newUser) {
                if (newUser) {
                    const userAssigned = newUser?.meta?.assigned_shops || [];
                    this.assignedIds = [...userAssigned];
                    this.initialAssignedIds = [...userAssigned];
                }
            },
            deep: true
        }
    },
    computed: {
        hasChanges() {
            const current = [...this.assignedIds].sort().join(',');
            const initial = [...this.initialAssignedIds].sort().join(',');
            return current !== initial;
        }
    },
    async mounted() {
        await this.fetchShops();
    },
    methods: {
        async fetchShops() {
            this.isLoading = true;
            try {
                // Получаем все активные магазины (этот эндпоинт должен отдавать все магазины для админа)
                const response = await axios.get('/deliveryman/shops');
                if (response.data.success) {
                    this.shops = response.data.data.shops;

                    // Берем уже назначенные магазины из meta пользователя
                    const userAssigned = this.user?.meta?.assigned_shops || [];
                    this.assignedIds = [...userAssigned];
                    this.initialAssignedIds = [...userAssigned];
                }
            } catch (error) {
                console.error('Ошибка загрузки магазинов:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить список магазинов', type: 'error' });
            } finally {
                this.isLoading = false;
            }
        },

        toggleShop(shopId) {
            const index = this.assignedIds.indexOf(shopId);
            if (index === -1) {
                this.assignedIds.push(shopId);
            } else {
                this.assignedIds.splice(index, 1);
            }
        },

        isAssigned(shopId) {
            return this.assignedIds.includes(shopId);
        },

        async saveAssignment() {
            this.isSaving = true;
            try {
                const response = await axios.post(`/admin/deliverymen/${this.user.id}/assign-shops`, {
                    shop_ids: this.assignedIds
                });

                if (response.data.success) {
                    this.initialAssignedIds = [...this.assignedIds];

                    // 🆕 Передаем обновленный массив ID родительскому компоненту
                    this.$emit('saved', this.assignedIds);
                }
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.error || 'Не удалось сохранить назначения',
                    type: 'error'
                });
            } finally {
                this.isSaving = false;
            }
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.modal-container {
    background: $card-bg; border-radius: 20px; width: 100%; max-width: 600px;
    max-height: 85vh; display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2); animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}
@keyframes modalSlideUp { from { opacity: 0; transform: translateY(20px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }

.modal-header {
    display: flex; align-items: flex-start; justify-content: space-between; padding: 20px 24px;
    border-bottom: 1px solid $border; flex-shrink: 0;
    h3 { font-size: 1.15rem; font-weight: 700; margin: 0 0 4px; color: $text; }
    .modal-subtitle { font-size: 0.85rem; color: $text-muted; margin: 0; }
}

.modal-close {
    width: 36px; height: 36px; border-radius: 50%; background: $bg; border: none; color: $text-muted;
    cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;
    &:hover { background: #ef4444; color: white; transform: rotate(90deg); }
}

.modal-body { padding: 24px; overflow-y: auto; flex: 1; }

.loading-state, .empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 40px 20px; color: $text-muted; text-align: center;
    .spinner { width: 32px; height: 32px; border: 3px solid $border; border-top-color: $primary; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 16px; }
    i { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.3; }
}
@keyframes spin { to { transform: rotate(360deg); } }

.shops-list { display: flex; flex-direction: column; gap: 10px; }

.shop-item {
    display: flex; align-items: center; gap: 14px; padding: 12px 16px;
    background: $bg; border: 2px solid $border; border-radius: 12px;
    cursor: pointer; transition: all 0.2s ease;
    &:hover { border-color: rgba($primary, 0.3); background: white; }
    &.is-assigned {
        border-color: $success; background: rgba($success, 0.03);
        .shop-checkbox { background: $success; border-color: $success; color: white; }
    }
}

.shop-checkbox {
    width: 24px; height: 24px; border-radius: 50%; border: 2px solid $border;
    display: flex; align-items: center; justify-content: center; font-size: 0.75rem;
    transition: all 0.2s; flex-shrink: 0;
}

.shop-info { display: flex; align-items: center; gap: 14px; flex: 1; min-width: 0; }
.shop-avatar {
    width: 44px; height: 44px; border-radius: 10px; background: white;
    display: flex; align-items: center; justify-content: center; color: $text-muted;
    font-size: 1.1rem; flex-shrink: 0; overflow: hidden; border: 1px solid $border;
    img { width: 100%; height: 100%; object-fit: cover; }
}
.shop-details { flex: 1; min-width: 0; }
.shop-details h4 { margin: 0 0 2px; font-size: 0.95rem; font-weight: 600; color: $text; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.shop-details p { margin: 0; font-size: 0.8rem; color: $text-muted; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.modal-footer {
    display: flex; gap: 12px; padding: 16px 24px; border-top: 1px solid $border; background: $bg; flex-shrink: 0;
    button { flex: 1; }
}

.btn-primary, .btn-secondary {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 18px; border-radius: 10px; font-size: 0.9rem; font-weight: 600;
    border: none; cursor: pointer; transition: all 0.2s;
    &:disabled { opacity: 0.5; cursor: not-allowed; }
}

.btn-primary {
    background: $primary; color: white;
    &:hover:not(:disabled) { background: $primary-dark; transform: translateY(-1px); }
}

.btn-secondary {
    background: $card-bg; color: $text; border: 1px solid $border;
    &:hover:not(:disabled) { background: $bg; border-color: $primary; color: $primary; }
}

@media (max-width: 640px) {
    .modal-container { max-width: 100%; max-height: 95vh; border-radius: 20px 20px 0 0; }
}
</style>
