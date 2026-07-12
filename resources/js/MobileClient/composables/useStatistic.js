import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useStatisticStore } from '@/MobileClient/stores/Shop/statistic.js';

export function useStatistic() {
    const store = useStatisticStore();

    const {
        statistic,
        users,
        orders,
        products,
        cashback_up,
        cashback_down,
        traffics,
        isLoading,
        isTrafficLoading,
        isExporting,
        isHydrated,
        lastError,
    } = storeToRefs(store);

    const hasData = computed(() => store.hasData);
    const preparedCashback = computed(() => store.preparedCashback);
    const totalTraffic = computed(() => store.totalTraffic);

    return {
        // State
        statistic,
        users,
        orders,
        products,
        cashback_up,
        cashback_down,
        traffics,
        isLoading,
        isTrafficLoading,
        isExporting,
        isHydrated,
        lastError,

        // Getters
        hasData,
        preparedCashback,
        totalTraffic,

        // Actions
        loadStatistic: store.loadStatistic,
        loadTraffic: store.loadTraffic,
        exportStatistic: store.exportStatistic,

        $reset: store.$reset,
    };
}
