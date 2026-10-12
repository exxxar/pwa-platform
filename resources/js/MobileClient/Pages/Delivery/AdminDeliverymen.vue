<template>
    <div class="admin-deliverymen">
        <!-- ========================================== -->
        <!-- ШАПКА -->
        <!-- ========================================== -->
        <div class="admin-header">
            <button class="back-btn" @click="goBack">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="header-info">
                <h1 class="header-title">Курьеры</h1>
                <p class="header-subtitle">Поиск доставщиков и назначение магазинов</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПОИСК И СПИСОК -->
        <!-- ========================================== -->
        <div class="admin-content">
            <!-- Поле поиска -->
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    type="text"
                    v-model="searchQuery"
                    @input="handleSearch"
                    placeholder="Введите имя, телефон, ID или email..."
                    class="search-input"
                >
                <button v-if="searchQuery" class="clear-btn" @click="clearSearch">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- 1. Загрузка -->
            <div v-if="isLoading" class="loading-state">
                <div class="spinner"></div>
                <p>Поиск курьеров...</p>
            </div>

            <!-- 2. Пустой список -->
            <div v-else-if="results.length === 0" class="empty-state">
                <i class="fa-solid fa-user-slash"></i>
                <p>{{ searchQuery ? 'Курьеры не найдены' : 'Список курьеров пуст' }}</p>
            </div>

            <!-- 3. Результаты -->
            <div v-else class="results-list">
                <div
                    v-for="user in results"
                    :key="user.id"
                    class="user-card"
                    @click="openAssignmentModal(user)"
                >
                    <div class="user-avatar" :class="user.is_active ? 'status-active' : 'status-inactive'">
                        {{ getInitials(user.name) }}
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ user.name || 'Без имени' }}</div>
                        <div class="user-meta">
                            <span v-if="user.phone">{{ user.phone }}</span>
                            <span v-if="user.phone && user.id" class="separator">·</span>
                            <span>ID: {{ user.id }}</span>
                        </div>
                    </div>
                    <div class="user-action">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА НАЗНАЧЕНИЯ МАГАЗИНОВ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <DeliverymanEditModal
                    :user="selectedUser"
                    @close="closeModal"
                    @saved="handleSaved"
                />
            </div>
        </transition>
    </div>
</template>

<script>
import axios from 'axios';
import DeliverymanEditModal from '@/MobileClient/Components/Delivery/DeliverymanEditModal.vue';

export default {
    name: "AdminDeliverymen",
    components: {
        DeliverymanEditModal,
    },
    data() {
        return {
            searchQuery: '',
            results: [],
            isLoading: true,
            searchTimeout: null,
            showModal: false,
            selectedUser: null,
        };
    },
    mounted() {
        this.loadDeliverymen();
    },
    methods: {
        /**
         * Универсальный метод загрузки курьеров с учетом новой логики бэкенда
         */
        loadDeliverymen(query = '') {
            if (this.searchTimeout) clearTimeout(this.searchTimeout);
            this.isLoading = true;

            // Debounce: если есть запрос, ждем 400мс, если нет - выполняем сразу
            const delay = query.trim() ? 400 : 0;

            this.searchTimeout = setTimeout(async () => {
                try {
                    const response = await axios.get(`/admin/users/search`, {
                        params: {
                            search: query.trim(),          // 🆕 Бэкенд ожидает 'search', а не 'query'
                            order_by: 'id',                // 🆕 Сортировка по ID (разрешено: id, name, created_at)
                            direction: 'desc',             // 🆕 Направление сортировки (asc или desc)
                            size: 50                       // 🆕 Берем 50 записей, чтобы покрыть поиск без сложной пагинации
                        }
                    });

                    // 🆕 Бэкенд возвращает объект пагинации Laravel, массив пользователей лежит в свойстве .data
                    this.results = response.data.data || [];
                } catch (error) {
                    console.error('Ошибка загрузки курьеров:', error);
                    this.$notify?.({
                        title: 'Ошибка',
                        text: 'Не удалось загрузить список курьеров',
                        type: 'error'
                    });
                    this.results = [];
                } finally {
                    this.isLoading = false;
                }
            }, delay);
        },

        handleSearch() {
            this.loadDeliverymen(this.searchQuery);
        },

        clearSearch() {
            this.searchQuery = '';
            this.loadDeliverymen('');
        },

        openAssignmentModal(user) {
            this.selectedUser = user;
            this.showModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.showModal = false;
            document.body.style.overflow = '';
            setTimeout(() => {
                this.selectedUser = null;
            }, 300);
        },

        handleSaved() {
            this.$notify?.({
                title: 'Успех',
                text: 'Данные курьера успешно обновлены',
                type: 'success'
            });
            this.closeModal();
            // 🆕 Перезагружаем список, чтобы отобразить актуальные данные (например, если изменилась роль)
            this.loadDeliverymen(this.searchQuery);
        },

        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        },

        goBack() {
            this.$router.back();
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$danger: #ef4444;
$bg: #f9fafb;
$card-bg: #ffffff;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;

.admin-deliverymen {
    min-height: 100vh;
    background: $bg;
}

.admin-header {
    display: flex; align-items: center; gap: 14px; padding: 16px 20px;
    background: $card-bg; border-bottom: 1px solid $border;
    position: sticky; top: 0; z-index: 100;
}

.back-btn {
    width: 40px; height: 40px; border-radius: 10px; background: $bg;
    border: 1px solid $border; color: $text; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    &:hover { background: $primary; border-color: $primary; color: white; }
}

.header-info { flex: 1; }
.header-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 2px; color: $text; }
.header-subtitle { font-size: 0.8rem; color: $text-muted; margin: 0; }

.admin-content { padding: 20px; max-width: 800px; margin: 0 auto; }

.search-wrapper {
    position: relative; margin-bottom: 20px;
    .search-icon {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: $text-muted; pointer-events: none;
    }
    .search-input {
        width: 100%; padding: 12px 40px 12px 42px; border: 1px solid $border;
        border-radius: 12px; font-size: 0.95rem; background: $card-bg; color: $text;
        &:focus { outline: none; border-color: $primary; box-shadow: 0 0 0 3px rgba($primary, 0.1); }
    }
    .clear-btn {
        position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
        width: 28px; height: 28px; border-radius: 50%; background: transparent;
        border: none; color: $text-muted; cursor: pointer;
        &:hover { background: rgba($text-muted, 0.1); color: $text; }
    }
}

.loading-state, .empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 60px 20px; color: $text-muted; text-align: center;
    .spinner {
        width: 32px; height: 32px; border: 3px solid $border;
        border-top-color: $primary; border-radius: 50%;
        animation: spin 0.8s linear infinite; margin-bottom: 16px;
    }
    i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
}
@keyframes spin { to { transform: rotate(360deg); } }

.results-list { display: flex; flex-direction: column; gap: 10px; }

.user-card {
    display: flex; align-items: center; gap: 14px; padding: 14px 16px;
    background: $card-bg; border: 1px solid $border; border-radius: 12px;
    cursor: pointer; transition: all 0.2s;
    &:hover { border-color: $primary; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
}

.user-avatar {
    width: 48px; height: 48px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center; font-weight: 700; color: white; flex-shrink: 0;
    &.status-active { background: linear-gradient(135deg, $success, #059669); }
    &.status-inactive { background: linear-gradient(135deg, $text-muted, #475569); }
}

.user-info { flex: 1; min-width: 0; }
.user-name { font-size: 0.95rem; font-weight: 600; color: $text; margin-bottom: 2px; }
.user-meta { font-size: 0.8rem; color: $text-muted; display: flex; gap: 6px; }
.separator { opacity: 0.5; }

.user-action { color: $text-muted; transition: all 0.2s; }
.user-card:hover .user-action { color: $primary; transform: translateX(4px); }

// ==========================================
// МОДАЛКА (Обертка)
// ==========================================
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px); z-index: 1030;
    display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-fade-enter-active { transition: opacity 0.3s ease; }
.modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

@media (max-width: 640px) {
    .admin-header { padding: 12px 16px; }
    .admin-content { padding: 16px; }
    .modal-overlay { padding: 0; align-items: flex-end; }
}
</style>
