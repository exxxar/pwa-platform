<template>
    <div class="admin-clients">

        <!-- ========================================== -->
        <!-- ШАПКА -->
        <!-- ========================================== -->
        <div class="admin-header">
            <button class="back-btn" @click="goBack">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="header-info">
                <h1 class="header-title">Клиенты</h1>
                <p class="header-subtitle">Управление пользователями</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПОИСК ПОЛЬЗОВАТЕЛЕЙ -->
        <!-- ========================================== -->
        <div class="admin-content">
            <UserSearchForm
                :selected-bot-user="selectedUser"
                @select="openUserProfile"
            />
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА ПРОФИЛЯ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div
                v-if="showProfileModal"
                class="modal-overlay"
                @click.self="closeProfileModal"
            >
                <UserProfileModal
                    :user="selectedUser"
                    :loading="isLoadingClient"
                    @close="closeProfileModal"
                    @message="openChat"
                />
            </div>
        </transition>

    </div>
</template>

<script>
import UserSearchForm from "@/MobileClient/Components/Admin/Clients/Users.vue";
import UserProfileModal from "@/MobileClient/Components/Admin/Clients/UserProfileModal.vue";
import { useClients } from '@/MobileClient/Composables/useClients.js';

export default {
    name: "AdminClients",

    components: {
        UserSearchForm,
        UserProfileModal,
    },

    setup() {
        const clients = useClients();
        return { ...clients };
    },

    data() {
        return {
            selectedUser: null,
            showProfileModal: false,
        };
    },

    mounted() {
        this.loadUserFromUrl();
    },

    methods: {
        /**
         * Загрузка пользователя из URL
         */
        loadUserFromUrl() {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const userId = urlParams.get('user');

                if (userId) {
                    const id = parseInt(userId);
                    if (!isNaN(id)) {
                        this.openUserProfile({ id });
                    }
                }
            } catch (error) {
                console.error('Ошибка парсинга URL:', error);
            }
        },

        /**
         * Открытие профиля пользователя
         */
        async openUserProfile(user) {
            this.selectedUser = user;
            this.showProfileModal = true;

            // Блокируем скролл
            document.body.style.overflow = 'hidden';

            // Загружаем полные данные
            try {
                await this.loadReceiverUserData(user.id);

                // Обновляем данные пользователя
                if (this.receiverUserData) {
                    this.selectedUser = {
                        ...user,
                        ...this.receiverUserData,
                    };
                }
            } catch (error) {
                console.error('Ошибка загрузки профиля:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить данные пользователя',
                    type: 'error',
                });
            }
        },

        /**
         * Закрытие модалки
         */
        closeProfileModal() {
            this.showProfileModal = false;
            document.body.style.overflow = '';

            // Очищаем данные через небольшую задержку (для анимации)
            setTimeout(() => {
                this.selectedUser = null;
                this.clearClient();
            }, 300);
        },

        /**
         * Открытие чата с пользователем
         */
        openChat(user) {
            this.closeProfileModal();
            this.$router.push({
                name: 'Chat',
                params: { userId: user.id }
            });
        },

        /**
         * Возврат назад
         */
        goBack() {
            this.$router.back();
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$bg: #f9fafb;
$card-bg: #ffffff;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;

.admin-clients {
    min-height: 100vh;
    background: $bg;
}

.admin-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    background: $card-bg;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
}

.back-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: $bg;
    border: 1px solid $border;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;

    &:hover {
        background: $primary;
        border-color: $primary;
        color: white;
    }
}

.header-info {
    flex: 1;
}

.header-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 2px;
    color: $text;
}

.header-subtitle {
    font-size: 0.8rem;
    color: $text-muted;
    margin: 0;
}

.admin-content {
    padding: 16px;
    max-width: 800px;
    margin: 0 auto;
}

// ==========================================
// МОДАЛКА
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-fade-enter-active {
    transition: opacity 0.3s ease;
}

.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@media (max-width: 640px) {
    .admin-header {
        padding: 12px 16px;
    }

    .admin-content {
        padding: 12px;
    }

    .modal-overlay {
        padding: 0;
        align-items: flex-end;
    }
}
</style>
