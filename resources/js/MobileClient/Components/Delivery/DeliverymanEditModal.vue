<template>
    <div class="edit-modal" :class="{ 'is-mobile': isMobile }">
        <!-- Заголовок -->
        <div class="edit-header">
            <button class="close-btn" @click="$emit('close')">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="header-info">
                <div class="header-icon">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <div>
                    <h3 class="header-title">Управление курьером</h3>
                    <p class="header-subtitle">{{ user?.name || 'Пользователь' }} (ID: {{ user?.id }})</p>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loadingData" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Загружаем данные...</p>
        </div>

        <!-- Форма -->
        <form v-else @submit.prevent="saveChanges" class="edit-form">
            <!-- Табы -->
            <div class="edit-tabs">
                <button type="button" class="edit-tab" :class="{ 'is-active': activeTab === 'roles' }" @click="activeTab = 'roles'">
                    <i class="fa-solid fa-user-tag"></i>
                    <span>Роли</span>
                </button>
                <button type="button" class="edit-tab" :class="{ 'is-active': activeTab === 'shops' }" @click="activeTab = 'shops'">
                    <i class="fa-solid fa-store"></i>
                    <span>Магазины</span>
                </button>
            </div>

            <!-- Контент табов -->
            <div class="edit-content">
                <!-- ===== ТАБ: РОЛИ ===== -->
                <div v-if="activeTab === 'roles'" class="tab-panel">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fa-solid fa-user-tag"></i> Роли и доступы</h4>

                        <div v-if="loadingRoles" class="roles-loading">
                            <div class="loading-spinner small"></div>
                            <span>Загружаем роли...</span>
                        </div>

                        <div v-else>
                            <p class="roles-hint">Выберите роли для этого пользователя.</p>

                            <div class="roles-list">
                                <div
                                    v-for="role in availableRoles"
                                    :key="role.id"
                                    class="role-chip"
                                    :class="{ 'is-selected': (form.role_ids || []).includes(role.id) }"
                                    @click="toggleRole(role.id)"
                                >
                                    <div class="role-icon">
                                        <i class="fa-solid" :class="getRoleIcon(role.name)"></i>
                                    </div>
                                    <div class="role-info">
                                        <span class="role-name">{{ role.label }}</span>
                                        <span class="role-count">{{ role.permissions_count || 0 }} прав</span>
                                    </div>
                                    <div class="role-check">
                                        <i v-if="(form.role_ids || []).includes(role.id)" class="fa-solid fa-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== ТАБ: МАГАЗИНЫ ===== -->
                <div v-if="activeTab === 'shops'" class="tab-panel">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fa-solid fa-store"></i> Назначенные заведения</h4>

                        <div v-if="loadingShops" class="roles-loading">
                            <div class="loading-spinner small"></div>
                            <span>Загружаем магазины...</span>
                        </div>

                        <div v-else>
                            <p class="roles-hint">Курьер будет видеть заказы только из выбранных заведений.</p>

                            <div class="shops-list-modal">
                                <div
                                    v-for="shop in availableShops"
                                    :key="shop.id"
                                    class="shop-item"
                                    :class="{ 'is-selected': (form.assigned_shops || []).includes(shop.id) }"
                                    @click="toggleShop(shop.id)"
                                >
                                    <div class="shop-checkbox">
                                        <i v-if="(form.assigned_shops || []).includes(shop.id)" class="fa-solid fa-check"></i>
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

                                <div v-if="availableShops.length === 0" class="empty-roles">
                                    <i class="fa-solid fa-store-slash"></i>
                                    <p>Нет доступных магазинов для назначения.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Индикатор изменений -->
            <div v-if="hasChanges" class="changes-indicator">
                <i class="fa-solid fa-circle-info"></i>
                <span>У вас есть несохранённые изменения</span>
            </div>

            <!-- Действия -->
            <div class="edit-actions">
                <button type="button" class="action-btn cancel" @click="$emit('close')" :disabled="isSaving">
                    Отмена
                </button>
                <button type="submit" class="action-btn save" :disabled="isSaving || !hasChanges">
                    <span v-if="isSaving" class="btn-spinner"></span>
                    <i v-else class="fa-solid fa-check"></i>
                    <span>{{ isSaving ? 'Сохранение...' : 'Сохранить изменения' }}</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'DeliverymanEditModal',
    props: {
        user: { type: Object, required: true },
        loading: { type: Boolean, default: false },
    },
    emits: ['close', 'saved'],
    data() {
        return {
            activeTab: 'roles',
            isMobile: window.innerWidth < 640,
            isSaving: false,
            loadingData: true,
            loadingRoles: false,
            loadingShops: false,

            // 🛡️ Явная инициализация массивов, чтобы они никогда не были undefined
            form: {
                role_ids: [],
                assigned_shops: [],
            },
            initialForm: {
                role_ids: [],
                assigned_shops: [],
            },
            availableRoles: [],
            availableShops: [],
        };
    },
    computed: {
        hasRoleChanges() {
            return JSON.stringify(this.form.role_ids || []) !== JSON.stringify(this.initialForm.role_ids || []);
        },
        hasShopChanges() {
            return JSON.stringify(this.form.assigned_shops || []) !== JSON.stringify(this.initialForm.assigned_shops || []);
        },
        hasChanges() {
            return this.hasRoleChanges || this.hasShopChanges;
        },
    },
    watch: {
        user: {
            immediate: true,
            async handler(newUser) {
                if (newUser && newUser.id) {
                    this.initializeForm(newUser);
                    await Promise.all([this.loadRoles(), this.loadShops()]);
                    this.loadingData = false;
                }
            },
        },
    },
    methods: {
        initializeForm(user) {
            // 🛡️ Безопасное извлечение данных с запасными значениями (fallback)
            const userRoles = user?.roles || [];
            const roleIds = userRoles.map(r => r.id);

            const userMeta = user?.meta || {};
            const assignedShops = userMeta.assigned_shops || [];

            this.form = {
                role_ids: roleIds,
                assigned_shops: assignedShops,
            };

            // Глубокое копирование для корректного сравнения
            this.initialForm = JSON.parse(JSON.stringify(this.form));
        },

        async loadRoles() {
            this.loadingRoles = true;
            try {
                const response = await axios.get('/admin/roles');
                this.availableRoles = response.data.data || response.data || [];
            } catch (error) {
                console.error('Ошибка загрузки ролей:', error);
            } finally {
                this.loadingRoles = false;
            }
        },

        async loadShops() {
            this.loadingShops = true;
            try {
                const response = await axios.get('/deliveryman/shops');
                if (response.data && response.data.success) {
                    this.availableShops = response.data.data.shops || [];
                }
            } catch (error) {
                console.error('Ошибка загрузки магазинов:', error);
            } finally {
                this.loadingShops = false;
            }
        },

        toggleRole(roleId) {
            // Если массив вдруг потерялся, восстанавливаем его
            if (!Array.isArray(this.form.role_ids)) this.form.role_ids = [];

            const index = this.form.role_ids.indexOf(roleId);
            if (index === -1) {
                this.form.role_ids.push(roleId);
            } else {
                this.form.role_ids.splice(index, 1);
            }
        },

        toggleShop(shopId) {
            if (!Array.isArray(this.form.assigned_shops)) this.form.assigned_shops = [];

            const index = this.form.assigned_shops.indexOf(shopId);
            if (index === -1) {
                this.form.assigned_shops.push(shopId);
            } else {
                this.form.assigned_shops.splice(index, 1);
            }
        },

        getRoleIcon(roleName) {
            const icons = {
                'super_admin': 'fa-crown',
                'admin': 'fa-user-shield',
                'worker': 'fa-user-gear',
                'user': 'fa-user',
                'deliveryman': 'fa-motorcycle',
                'delivery': 'fa-motorcycle',
            };
            return icons[roleName] || 'fa-user-tag';
        },

        async saveChanges() {
            this.isSaving = true;
            let savedRoles = false;
            let savedShops = false;

            try {
                if (this.hasRoleChanges) {
                    await axios.put(`/admin/users/${this.user.id}/roles`, {
                        role_ids: this.form.role_ids || []
                    });
                    savedRoles = true;
                }

                if (this.hasShopChanges) {
                    await axios.post(`/admin/deliverymen/${this.user.id}/assign-shops`, {
                        shop_ids: this.form.assigned_shops || []
                    });
                    savedShops = true;
                }

                this.initialForm = JSON.parse(JSON.stringify(this.form));

                let successMessage = 'Изменения успешно сохранены';
                if (savedRoles && savedShops) successMessage = 'Роли и магазины успешно обновлены';
                else if (savedRoles) successMessage = 'Роли успешно обновлены';
                else if (savedShops) successMessage = 'Список магазинов успешно обновлен';

                this.$notify?.({ title: 'Успешно', text: successMessage, type: 'success' });
                this.$emit('saved');

            } catch (error) {
                console.error('[DeliverymanEdit] Ошибка сохранения:', error);
                let errorMsg = 'Не удалось сохранить изменения';
                if (error.response?.data?.message) errorMsg = error.response.data.message;
                else if (error.response?.data?.error) errorMsg = error.response.data.error;

                this.$notify?.({ title: 'Ошибка', text: errorMsg, type: 'error' });
            } finally {
                this.isSaving = false;
            }
        },
    },
};
</script>

<style lang="scss" scoped>
/* Стили остаются точно такими же, как в вашем файле */
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$bg: #ffffff;
$bg-secondary: #f9fafb;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;

.edit-modal {
    background: $bg;
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s ease;

    &.is-mobile {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 20px 20px 0 0;
    }
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.edit-header {
    position: relative; padding: 20px 20px 16px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%); color: white;
}

.close-btn {
    position: absolute; top: 12px; right: 12px; width: 32px; height: 32px;
    border-radius: 50%; background: rgba(255, 255, 255, 0.15); border: none;
    color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; transition: all 0.2s; z-index: 2;
    &:hover { background: rgba(255, 255, 255, 0.25); transform: scale(1.1); }
}

.header-info { display: flex; align-items: center; gap: 14px; padding-right: 32px; }
.header-icon {
    width: 48px; height: 48px; border-radius: 14px; background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.header-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 2px; }
.header-subtitle { font-size: 0.85rem; opacity: 0.9; margin: 0; }

.loading-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 80px 20px; color: $text-muted;
    .loading-spinner {
        width: 40px; height: 40px; border: 3px solid $border;
        border-top-color: $primary; border-radius: 50%;
        animation: spin 0.8s linear infinite; margin-bottom: 16px;
        &.small { width: 20px; height: 20px; border-width: 2px; }
    }
}
@keyframes spin { to { transform: rotate(360deg); } }

.edit-form { display: flex; flex-direction: column; flex: 1; overflow: hidden; }

.edit-tabs {
    display: flex; gap: 4px; padding: 12px 16px;
    background: $bg-secondary; border-bottom: 1px solid $border;
}

.edit-tab {
    flex: 1; display: flex; align-items: center; justify-content: center;
    gap: 6px; padding: 10px; background: transparent; border: none;
    border-radius: 10px; color: $text-muted; font-size: 0.85rem;
    font-weight: 600; cursor: pointer; transition: all 0.2s;
    &:hover:not(.is-active) { color: $text; background: rgba($primary, 0.05); }
    &.is-active { background: $bg; color: $primary; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); }
}

.edit-content { flex: 1; overflow-y: auto; padding: 16px; background: $bg-secondary; }
.tab-panel { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

.form-section {
    background: $bg; border: 1px solid $border;
    border-radius: 14px; padding: 16px; margin-bottom: 12px;
}

.section-title {
    display: flex; align-items: center; gap: 8px; font-size: 0.95rem;
    font-weight: 700; color: $text; margin: 0 0 14px;
    padding-bottom: 10px; border-bottom: 1px solid $border;
    i { color: $primary; }
}

.roles-loading { display: flex; align-items: center; gap: 10px; padding: 20px; color: $text-muted; font-size: 0.9rem; }
.roles-hint { font-size: 0.85rem; color: $text-muted; margin: 0 0 16px; line-height: 1.5; }

.roles-list, .shops-list-modal { display: flex; flex-direction: column; gap: 8px; }

.shop-item {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px;
    background: $bg-secondary; border: 1.5px solid $border;
    border-radius: 12px; cursor: pointer; transition: all 0.2s ease;
    &:hover { border-color: $primary; background: rgba($primary, 0.02); }
    &.is-selected { border-color: $success; background: rgba($success, 0.05); }
}

.shop-checkbox {
    width: 22px; height: 22px; border-radius: 6px; border: 2px solid $border;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; color: transparent; transition: all 0.2s; flex-shrink: 0;
    .is-selected & { background: $success; border-color: $success; color: white; }
}

.shop-info { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }

.shop-avatar {
    width: 40px; height: 40px; border-radius: 8px; background: white;
    display: flex; align-items: center; justify-content: center;
    color: $text-muted; font-size: 1rem; flex-shrink: 0; overflow: hidden; border: 1px solid $border;
    img { width: 100%; height: 100%; object-fit: cover; }
}

.shop-details { flex: 1; min-width: 0; }
.shop-details h4 {
    margin: 0 0 2px; font-size: 0.9rem; font-weight: 600; color: $text;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.shop-details p {
    margin: 0; font-size: 0.75rem; color: $text-muted;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.role-chip {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px;
    background: $bg-secondary; border: 1.5px solid $border;
    border-radius: 12px; cursor: pointer; transition: all 0.2s;
    &:hover { border-color: $primary; background: rgba($primary, 0.02); }
    &.is-selected { border-color: $primary; background: rgba($primary, 0.08); }
}

.role-icon, .shop-avatar {
    width: 36px; height: 36px; border-radius: 8px;
    background: linear-gradient(135deg, $primary, $primary-dark);
    color: white; display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0; overflow: hidden;
}

.role-info { flex: 1; min-width: 0; }
.role-name { display: block; font-weight: 600; font-size: 0.9rem; color: $text; margin-bottom: 2px; }
.role-count { display: block; font-size: 0.75rem; color: $text-muted; }

.role-check, .shop-checkbox i {
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; opacity: 0; transition: opacity 0.2s;
    .is-selected & { opacity: 1; }
}

.changes-indicator {
    display: flex; align-items: center; gap: 8px; padding: 10px 16px;
    background: rgba($primary, 0.08); border-top: 1px solid rgba($primary, 0.15);
    font-size: 0.8rem; color: $primary;
}

.edit-actions {
    display: flex; gap: 10px; padding: 14px 16px;
    background: $bg; border-top: 1px solid $border;
}

.action-btn {
    flex: 1; display: flex; align-items: center; justify-content: center;
    gap: 8px; padding: 12px; border: none; border-radius: 10px;
    font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
    &.cancel {
        background: $bg-secondary; color: $text; border: 1px solid $border;
        &:hover:not(:disabled) { border-color: $danger; color: $danger; }
    }
    &.save {
        background: linear-gradient(135deg, $primary, $primary-dark);
        color: white; box-shadow: 0 4px 12px rgba($primary, 0.3);
        &:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba($primary, 0.4); }
    }
    &:disabled { opacity: 0.5; cursor: not-allowed; }
}

.btn-spinner {
    width: 16px; height: 16px; border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite;
}

.empty-roles {
    text-align: center; padding: 30px 20px; color: $text-muted;
    i { font-size: 2rem; margin-bottom: 10px; opacity: 0.5; }
    p { font-size: 0.85rem; margin: 0; }
}

@media (max-width: 640px) {
    .edit-header { padding: 16px 14px; }
    .header-icon { width: 40px; height: 40px; font-size: 1.1rem; }
    .header-title { font-size: 1rem; }
    .edit-tab span { display: none; }
}
</style>
