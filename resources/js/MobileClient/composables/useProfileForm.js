import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useProfileFormStore } from '@/stores/profileForm.js';

/**
 * Composable для работы с профилем пользователя
 */
export function useProfileForm() {
    const store = useProfileFormStore();

    // Реактивные ссылки на состояние
    const {
        profileData,
        isLoading,
        isHydrated,
        isSaving,
        isDirty,
        lastError,
        errors,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const userName = computed(() => store.userName);
    const userPhone = computed(() => store.userPhone);
    const userEmail = computed(() => store.userEmail);
    const userAddress = computed(() => store.userAddress);
    const userBirthday = computed(() => store.userBirthday);
    const userSex = computed(() => store.userSex);
    const userCity = computed(() => store.userCity);
    const userCountry = computed(() => store.userCountry);
    const isProfileComplete = computed(() => store.isProfileComplete);
    const profileCompletionPercent = computed(() => store.profileCompletionPercent);
    const hasUnsavedChanges = computed(() => store.hasUnsavedChanges);

    // ==========================================
    // Безопасные методы
    // ==========================================

    const loadProfile = async () => {
        try {
            return await store.loadProfileFormData();
        } catch (error) {
            console.error('Ошибка загрузки профиля:', error);
            throw error;
        }
    };

    const saveProfile = async (dataObject) => {
        try {
            return await store.saveProfileFormData({ dataObject });
        } catch (error) {
            console.error('Ошибка сохранения профиля:', error);
            throw error;
        }
    };

    const updateLocal = (data) => {
        store.updateLocalProfileData(data);
    };

    const updateField = (field, value) => {
        store.updateField(field, value);
    };

    const discardChanges = () => {
        store.discardChanges();
    };

    return {
        // Состояние
        profileData,
        isLoading,
        isHydrated,
        isSaving,
        isDirty,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры
        userName,
        userPhone,
        userEmail,
        userAddress,
        userBirthday,
        userSex,
        userCity,
        userCountry,
        isProfileComplete,
        profileCompletionPercent,
        hasUnsavedChanges,

        // Методы
        loadProfile,
        saveProfile,
        updateLocal,
        updateField,
        discardChanges,

        // Сброс
        $reset: store.$reset,
    };
}
