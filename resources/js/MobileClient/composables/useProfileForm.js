import { storeToRefs } from 'pinia';
import { useProfileFormStore } from '@/MobileClient/stores/Shop/profileForm.js';

/**
 * Composable для работы с профилем пользователя
 */
export function useProfileForm() {
    const store = useProfileFormStore();

    // ✅ ИСПРАВЛЕНИЕ: Извлекаем ВСЁ (и состояние, и геттеры) через storeToRefs.
    // Pinia автоматически превратит геттеры в реактивные ref-ссылки.
    // Ручные computed() здесь не нужны, импортировать 'computed' из 'vue' больше не требуется.
    const {
        // --- Состояние (State) ---
        profileData,
        isLoading,
        isHydrated,
        isSaving,
        isDirty,
        lastError,
        errors,
        lastSyncAt,

        // --- Геттеры (Getters) ---
        // Pinia сама обеспечит их реактивность при деструктуризации через storeToRefs
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
    } = storeToRefs(store);

    // ==========================================
    // Безопасные методы (Actions) с обработкой ошибок
    // ==========================================

    const loadProfile = async () => {
        try {
            return await store.loadProfileFormData();
        } catch (error) {
            console.error('[useProfileForm] Ошибка загрузки профиля:', error);
            throw error;
        }
    };

    const saveProfile = async (dataObject) => {
        try {
            return await store.saveProfileFormData({ dataObject });
        } catch (error) {
            console.error('[useProfileForm] Ошибка сохранения профиля:', error);
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

    // ==========================================
    // Возврат API композабла
    // ==========================================
    return {
        // Состояние (Refs)
        profileData,
        isLoading,
        isHydrated,
        isSaving,
        isDirty,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры (Refs)
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

        // Экшены (Actions)
        loadProfile,
        saveProfile,
        updateLocal,
        updateField,
        discardChanges,

        // Утилита для сброса стора
        $reset: store.$reset,
    };
}
