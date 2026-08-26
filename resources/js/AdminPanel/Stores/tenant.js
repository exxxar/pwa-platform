import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useTenantStore = defineStore('tenant', () => {
    // Состояние
    const currentTenant = ref(null)
    const tenants = ref([])

    // Геттеры
    const currentTenantId = computed(() => currentTenant.value?.id || null)
    const currentTenantName = computed(() => currentTenant.value?.name || 'Все тенанты')

    // Действия
    const setCurrentTenant = (tenant) => {
        currentTenant.value = tenant
        if (tenant) {
            localStorage.setItem('current_tenant_id', tenant.id)
        } else {
            localStorage.removeItem('current_tenant_id')
        }
    }

    const setTenants = (newTenants) => {
        tenants.value = newTenants
    }

    const clearCurrentTenant = () => {
        currentTenant.value = null
        localStorage.removeItem('current_tenant_id')
    }

    return {
        currentTenant,
        tenants,
        currentTenantId,
        currentTenantName,
        setCurrentTenant,
        setTenants,
        clearCurrentTenant,
    }
})
