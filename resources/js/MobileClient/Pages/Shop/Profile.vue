<template>
    <div class="py-3 pb-5" v-if="self">

        <!-- 🆕 Кнопка переключения режима (видна только если пользователь агент) -->
        <div v-if="isAgent" class="d-flex justify-content-end mb-3 px-3">
            <button
                type="button"
                style="border-radius: 15px;"
                class="btn btn-sm d-flex align-items-center gap-2"
                :class="showAgentMode ? 'btn-primary' : 'btn-outline-primary'"
                @click="showAgentMode = !showAgentMode"
            >
                <span>{{ showAgentMode ? '🔙 Обычный профиль' : '🕶️ Режим агента' }}</span>
            </button>
        </div>

        <!-- 🆕 ВЕТВЛЕНИЕ: Если включен режим агента -->
        <template v-if="isAgent && showAgentMode">
            <AgentProfileCard
                :user="self"
                @edit="openAgentEditModal"
            />

            <AgentEditProfileModal
                ref="agentEditModal"
                :user="self"
                @saved="onProfileSaved"
            />
        </template>

        <!-- 🆕 ВЕТВЛЕНИЕ: Обычный пользователь ИЛИ агент в обычном режиме -->
        <template v-else>
            <ProfileCard @profile-edit="openEditModal"/>

            <EditProfileModal
                ref="editModal"
                @saved="onProfileSaved"
            />
        </template>

    </div>

    <!-- Состояние загрузки -->
    <div v-else class="loading-state">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Загрузка...</span>
        </div>
    </div>
</template>

<script>
// Обычные компоненты
import ProfileCard from "@/MobileClient/Components/Shop/ProfileCard.vue";
import EditProfileModal from "@/MobileClient/Components/Shop/EditProfileModal.vue";

// 🆕 Агентские компоненты
import AgentProfileCard from "@/MobileClient/Components/Agent/AgentProfileCard.vue";
import AgentEditProfileModal from "@/MobileClient/Components/Agent/AgentEditProfileModal.vue";

export default {
    name: "ProfilePage",

    components: {
        ProfileCard,
        EditProfileModal,
        AgentProfileCard,
        AgentEditProfileModal,
    },

    // 🆕 Добавляем локальное состояние для переключения режима
    data() {
        return {
            showAgentMode: false, // По умолчанию показываем обычный профиль
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },
        tenant() {
            return window.Tenant || null;
        },
        // Проверяем, есть ли у пользователя агентский профиль
        isAgent() {
            return !!this.self?.agent_profile && this.isAgentDomain;
        },
        isAgentDomain() {
            return window.location.hostname === 'agents.mypwa.ru';
        }
    },

    methods: {
        openEditModal() {
            this.$refs.editModal?.show();
        },

        openAgentEditModal() {
            this.$refs.agentEditModal?.show();
        },

        onProfileSaved(updatedUser) {
            if (window.TenantUser && updatedUser) {
                // Глубокое слияние, чтобы не потерять вложенные объекты типа agent_profile
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
.loading-state {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
