<template>
    <div class="admin-roles-page">

        <!-- ===== ШАПКА ===== -->
        <div class="admin-header">
            <button class="back-btn" @click="$router.back()">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="header-info">
                <h1 class="header-title">Роли и разрешения</h1>
                <p class="header-subtitle">Управление доступом в системе</p>
            </div>
            <button class="create-btn" @click="openCreateModal">
                <i class="fa-solid fa-plus"></i>
                <span class="d-none d-sm-inline">Создать роль</span>
            </button>
        </div>

        <!-- ===== КОНТЕНТ ===== -->
        <div class="admin-content">

            <!-- Загрузка -->
            <div v-if="isLoading" class="loading-state">
                <div class="spinner"></div>
                <p>Загружаем роли и разрешения...</p>
            </div>

            <!-- Сетка ролей -->
            <div v-else-if="hasRoles" class="roles-grid">
                <div
                    v-for="role in sortedRoles"
                    :key="role.id"
                    class="role-card"
                    :class="{ 'is-system': role.name === 'super_admin' || role.name === 'user' }"
                >
                    <div class="role-header">
                        <div class="role-icon" :style="{ background: getRoleColor(role.name) }">
                            <i class="fa-solid" :class="getRoleIcon(role.name)"></i>
                        </div>
                        <div class="role-title-block">
                            <h3 class="role-label">{{ role.label }}</h3>
                            <code class="role-name">{{ role.name }}</code>
                        </div>
                    </div>

                    <div class="role-stats">
                        <div class="stat-item">
                            <i class="fa-solid fa-key"></i>
                            <span>{{ role.permissions_count || 0 }} прав</span>
                        </div>
                        <div class="stat-item">
                            <i class="fa-solid fa-users"></i>
                            <span>{{ role.users_count || 0 }} польз.</span>
                        </div>
                    </div>

                    <div class="role-actions">
                        <button
                            class="action-btn edit"
                            @click="openEditModal(role)"
                            :disabled="role.name === 'super_admin'"
                            title="Редактировать"
                        >
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button
                            class="action-btn delete"
                            @click="handleDelete(role)"
                            :disabled="role.name === 'super_admin' || role.name === 'user'"
                            title="Удалить"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>

                    <div v-if="role.name === 'super_admin' || role.name === 'user'" class="system-badge">
                        Системная
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="empty-state">
                <i class="fa-solid fa-user-shield"></i>
                <h3>Роли не найдены</h3>
                <p>Создайте первую роль, чтобы начать управлять доступом</p>
            </div>
        </div>

        <!-- ===== МОДАЛКА: СОЗДАНИЕ / РЕДАКТИРОВАНИЕ ===== -->
        <transition name="modal-fade">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">

                    <!-- Шапка модалки -->
                    <div class="modal-header">
                        <h3>{{ isEditing ? 'Редактирование роли' : 'Новая роль' }}</h3>
                        <button class="close-btn" @click="closeModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Тело модалки -->
                    <div class="modal-body">

                        <!-- Основные данные -->
                        <div class="form-group">
                            <label>Название (для отображения)</label>
                            <input
                                type="text"
                                v-model="form.label"
                                placeholder="Например: Менеджер склада"
                                @input="autoGenerateName"
                            >
                        </div>

                        <div class="form-group">
                            <label>Системное имя (slug)</label>
                            <input
                                type="text"
                                v-model="form.name"
                                placeholder="Например: warehouse_manager"
                                :disabled="isEditing && form.name === 'super_admin'"
                            >
                            <span class="hint">Используется в коде системы. Только латиница и нижнее подчеркивание.</span>
                        </div>

                        <hr class="divider">

                        <!-- Разрешения -->
                        <div class="permissions-section">
                            <label class="section-label">
                                <i class="fa-solid fa-shield-halved"></i>
                                Разрешения ({{ selectedPermissionsCount }} выбрано)
                            </label>

                            <div class="permissions-grid">
                                <div
                                    v-for="perm in permissions"
                                    :key="perm.id"
                                    class="permission-toggle"
                                    :class="{ 'is-active': form.permission_ids.includes(perm.id) }"
                                    @click="togglePermission(perm.id)"
                                >
                                    <div class="toggle-check">
                                        <i v-if="form.permission_ids.includes(perm.id)" class="fa-solid fa-check"></i>
                                    </div>
                                    <div class="toggle-info">
                                        <span class="perm-label">{{ perm.label }}</span>
                                        <code class="perm-name">{{ perm.name }}</code>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Футер модалки -->
                    <div class="modal-footer">
                        <button class="btn-cancel" @click="closeModal">Отмена</button>
                        <button class="btn-save" @click="handleSave" :disabled="isSaving">
                            <span v-if="isSaving" class="spinner small"></span>
                            <span v-else>Сохранить</span>
                        </button>
                    </div>

                </div>
            </div>
        </transition>

    </div>
</template>

<script>
import { useRoles } from '@/MobileClient/Composables/useRoles.js';

export default {
    name: 'AdminRoles',

    // 1. Подключаем композабл и пробрасываем всё в this
    setup() {
        const rolesApi = useRoles();
        return { ...rolesApi };
    },

    // 2. Локальное состояние компонента
    data() {
        return {
            showModal: false,
            isEditing: false,
            isNameManuallyEdited: false,
            form: {
                id: null,
                label: '',
                name: '',
                permission_ids: [],
            },
        };
    },

    // 3. Локальные вычисляемые свойства
    computed: {
        selectedPermissionsCount() {
            return this.form.permission_ids.length;
        }
    },

    // 4. Инициализация при загрузке
    mounted() {
        this.loadData();
    },

    // 5. Методы компонента
    methods: {
        async loadData() {
            try {
                await Promise.all([
                    this.loadRoles(),
                    this.loadPermissions()
                ]);
            } catch (error) {
                console.error('Ошибка инициализации:', error);
            }
        },

        getRoleColor(roleName) {
            const colors = {
                'super_admin': 'linear-gradient(135deg, #ef4444, #dc2626)',
                'admin': 'linear-gradient(135deg, #3b82f6, #2563eb)',
                'worker': 'linear-gradient(135deg, #10b981, #059669)',
                'user': 'linear-gradient(135deg, #6b7280, #4b5563)',
            };
            return colors[roleName] || 'linear-gradient(135deg, #8b5cf6, #7c3aed)';
        },

        getRoleIcon(roleName) {
            const icons = {
                'super_admin': 'fa-crown',
                'admin': 'fa-user-shield',
                'worker': 'fa-user-gear',
                'user': 'fa-user',
            };
            return icons[roleName] || 'fa-user-tag';
        },

        openCreateModal() {
            this.isEditing = false;
            this.form = { id: null, label: '', name: '', permission_ids: [] };
            this.isNameManuallyEdited = false;
            this.showModal = true;
        },

        openEditModal(role) {
            this.isEditing = true;
            this.form = {
                id: role.id,
                label: role.label,
                name: role.name,
                permission_ids: role.permission_ids || (role.permissions ? role.permissions.map(p => p.id) : [])
            };
            this.isNameManuallyEdited = true;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
        },

        autoGenerateName() {
            if (!this.isNameManuallyEdited && this.form.label) {
                const translit = this.form.label
                    .toLowerCase()
                    .replace(/[^a-z0-9а-яё\s]/g, '')
                    .replace(/\s+/g, '_');
                this.form.name = translit;
            }
        },

        togglePermission(permId) {
            const index = this.form.permission_ids.indexOf(permId);
            if (index === -1) {
                this.form.permission_ids.push(permId);
            } else {
                this.form.permission_ids.splice(index, 1);
            }
        },

        async handleSave() {
            if (!this.form.label || !this.form.name) {
                this.$notify?.({ title: 'Ошибка', text: 'Заполните название и системное имя', type: 'error' });
                return;
            }

            try {
                await this.saveRole(this.form);
                this.$notify?.({ title: 'Успешно', text: 'Роль сохранена', type: 'success' });
                this.closeModal();
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: this.lastError || 'Не удалось сохранить роль',
                    type: 'error'
                });
            }
        },

        async handleDelete(role) {
            if (role.users_count > 0) {
                this.$notify?.({
                    title: 'Невозможно удалить',
                    text: `У этой роли есть ${role.users_count} пользователей. Сначала измените их роли.`,
                    type: 'warning'
                });
                return;
            }

            const confirmed = confirm(`Вы уверены, что хотите удалить роль "${role.label}"?`);
            if (!confirmed) return;

            try {
                await this.deleteRole(role.id);
                this.$notify?.({ title: 'Удалено', text: 'Роль успешно удалена', type: 'success' });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: this.lastError || 'Не удалось удалить роль',
                    type: 'error'
                });
            }
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$bg: #f9fafb;
$card-bg: #ffffff;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;

.admin-roles-page {
    min-height: 100vh;
    background: $bg;
}

/* ===== ШАПКА ===== */
.admin-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    background: $card-bg;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 50;
}

.back-btn, .create-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid $border;
    background: $bg;
    color: $text;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s;
    padding: 0 16px;

    &:hover {
        background: $primary;
        border-color: $primary;
        color: white;
    }
}

.back-btn { padding: 0; width: 40px; }

.header-info { flex: 1; }
.header-title { font-size: 1.2rem; font-weight: 700; margin: 0; color: $text; }
.header-subtitle { font-size: 0.8rem; color: $text-muted; margin: 0; }

/* ===== КОНТЕНТ ===== */
.admin-content {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.loading-state, .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $text-muted;

    .spinner {
        width: 40px; height: 40px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 16px;
    }

    i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
    h3 { font-size: 1.1rem; color: $text; margin: 0 0 8px; }
    p { font-size: 0.9rem; margin: 0; }
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ===== СЕТКА РОЛЕЙ ===== */
.roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

.role-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    position: relative;
    transition: all 0.2s;

    &:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        transform: translateY(-2px);
    }

    &.is-system {
        border-color: rgba($primary, 0.3);
        background: rgba($primary, 0.02);
    }
}

.system-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 4px 8px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 6px;
}

.role-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
}

.role-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.role-title-block {
    flex: 1;
    min-width: 0;
}

.role-label {
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.role-name {
    display: inline-block;
    font-size: 0.7rem;
    font-family: monospace;
    color: $text-muted;
    background: $bg;
    padding: 2px 6px;
    border-radius: 4px;
}

.role-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid $border;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $text-muted;

    i { color: $primary; }
}

.role-actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid $border;
    background: $bg;
    color: $text;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $primary;
        border-color: $primary;
        color: white;
    }

    &.delete:hover:not(:disabled) {
        background: $danger;
        border-color: $danger;
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

/* ===== МОДАЛКА ===== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1030;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: $card-bg;
    border-radius: 20px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid $border;

    h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: $text; }
}

.close-btn {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: none;
    background: $bg;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover { background: $danger; color: white; }
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
}

.form-group {
    margin-bottom: 16px;

    label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;
    }

    input {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid $border;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }

        &:disabled {
            background: $bg;
            color: $text-muted;
        }
    }

    .hint {
        display: block;
        font-size: 0.75rem;
        color: $text-muted;
        margin-top: 4px;
    }
}

.divider {
    border: none;
    border-top: 1px solid $border;
    margin: 24px 0;
}

.permissions-section {
    .section-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        font-weight: 700;
        color: $text;
        margin-bottom: 12px;

        i { color: $primary; }
    }
}

.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 8px;
}

.permission-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: $bg;
    border: 1.5px solid $border;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        background: rgba($primary, 0.02);
    }

    &.is-active {
        border-color: $primary;
        background: rgba($primary, 0.08);
    }
}

.toggle-check {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 2px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: white;
    transition: all 0.2s;

    .is-active & {
        background: $primary;
        border-color: $primary;
    }
}

.toggle-info {
    flex: 1;
    min-width: 0;
}

.perm-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.perm-name {
    display: block;
    font-size: 0.7rem;
    font-family: monospace;
    color: $text-muted;
}

.modal-footer {
    display: flex;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid $border;
    background: $bg;
    border-radius: 0 0 20px 20px;
}

.btn-cancel, .btn-save {
    flex: 1;
    padding: 12px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-cancel {
    background: white;
    border: 1px solid $border;
    color: $text;

    &:hover { border-color: $danger; color: $danger; }
}

.btn-save {
    background: linear-gradient(135deg, $primary, $primary-dark);
    color: white;
    box-shadow: 0 4px 12px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba($primary, 0.4);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.spinner.small {
    width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: inline-block;
}

/* Адаптив */
@media (max-width: 640px) {
    .admin-header { padding: 12px 16px; }
    .create-btn span { display: none; }
    .create-btn { width: 40px; padding: 0; }
    .admin-content { padding: 16px; }
    .roles-grid { grid-template-columns: 1fr; }
    .permissions-grid { grid-template-columns: 1fr; }
    .modal-overlay { padding: 0; align-items: flex-end; }
    .modal-container {
        max-height: 95vh;
        border-radius: 20px 20px 0 0;
    }
}

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>
