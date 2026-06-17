<template>
    <div class="container py-3 pb-5" v-if="self">

        <!-- Карточка профиля -->
        <ProfileCard v-on:profile-edit="openEditModal"/>

        <!-- Модалка редактирования -->
        <EditProfileModal
            ref="editModal"
            @saved="onProfileSaved"
        />
    </div>

    <!-- Состояние загрузки -->
    <div v-else class="loading-state">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Загрузка...</span>
        </div>
    </div>
</template>

<script>
import ProfileCard from "@/MobileClient/Components/Shop/ProfileCard.vue";

import EditProfileModal from "@/MobileClient/Components/Shop/EditProfileModal.vue";

export default {
    name: "ProfilePage",

    components: {
        ProfileCard,
        EditProfileModal,
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        tenant() {
            return window.Tenant || null;
        },
    },

    methods: {
        openEditModal() {
            if (this.$refs.editModal) {
                this.$refs.editModal.show();
            }
        },


        onProfileSaved(updatedUser) {
            // Обновляем глобальный объект пользователя
            if (window.TenantUser && updatedUser) {
                Object.assign(window.TenantUser, updatedUser);
            }

            this.$notify?.({
                title: "Профиль обновлён",
                text: "Ваши данные успешно сохранены",
                type: "success",
            });
        },
    },
};
</script>

<style scoped>


/* Состояние загрузки */
.loading-state {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
