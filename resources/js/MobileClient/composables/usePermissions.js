import { computed } from 'vue';

export function usePermissions() {
    // Получаем пользователя из глобального окна (или из вашего Pinia/Vuex стора)
    const user = computed(() => window.TenantUser || null);

    // Проверка конкретной роли
    const hasRole = (roleName) => {
        if (!user.value) return false;
        // Поддержка проверки одной роли или массива ролей
        const roles = Array.isArray(roleName) ? roleName : [roleName];
        return roles.some(role => user.value.role_names?.includes(role));
    };

    // Проверка конкретного права (пермишена)
    const hasPermission = (permissionName) => {
        if (!user.value) return false;
        const permissions = Array.isArray(permissionName) ? permissionName : [permissionName];

        // Супер-админ всегда имеет все права (дополнительная страховка)
        if (user.value.role_names?.includes('super_admin')) return true;

        return permissions.some(perm => user.value.permission_names?.includes(perm));
    };

    // Проверка, является ли админом (супер или локальный)
    const isAdmin = computed(() => {
        return hasRole(['super_admin', 'admin']);
    });

    return {
        user,
        hasRole,
        hasPermission,
        isAdmin,
    };
}
