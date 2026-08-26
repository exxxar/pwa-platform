import { ref, reactive, watch } from 'vue'
import { useApi } from './useApi'

export function useTable(fetchUrl, options = {}) {
    const api = useApi()

    // Состояние таблицы
    const data = ref([])
    const loading = ref(false)
    const error = ref(null)

    // Параметры запроса
    const params = reactive({
        page: 1,
        per_page: options.perPage || 15,
        sort_by: options.sortBy || 'created_at',
        sort_dir: options.sortDir || 'desc',
        search: '',
        ...options.defaultFilters,
    })

    // Метаданные пагинации
    const pagination = reactive({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
    })

    /**
     * Загрузка данных
     */
    const fetchData = async () => {
        loading.value = true
        error.value = null

        try {
            const response = await api.get(fetchUrl, params)

            // Если ответ содержит пагинацию
            if (response.data && response.meta) {
                data.value = response.data
                Object.assign(pagination, response.meta)
            }
            // Если ответ - массив
            else if (Array.isArray(response)) {
                data.value = response
            }
            // Если ответ содержит data
            else if (response.data) {
                data.value = response.data
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Ошибка загрузки данных'
            console.error('Ошибка загрузки:', err)
        } finally {
            loading.value = false
        }
    }

    /**
     * Изменение страницы
     */
    const setPage = (page) => {
        params.page = page
        fetchData()
    }

    /**
     * Изменение количества элементов на странице
     */
    const setPerPage = (perPage) => {
        params.per_page = perPage
        params.page = 1
        fetchData()
    }

    /**
     * Изменение сортировки
     */
    const setSort = (sortBy, sortDir = 'asc') => {
        params.sort_by = sortBy
        params.sort_dir = sortDir
        fetchData()
    }

    /**
     * Установка фильтра
     */
    const setFilter = (key, value) => {
        params[key] = value
        params.page = 1
        fetchData()
    }

    /**
     * Сброс фильтров
     */
    const resetFilters = () => {
        Object.keys(params).forEach(key => {
            if (!['page', 'per_page', 'sort_by', 'sort_dir'].includes(key)) {
                params[key] = options.defaultFilters?.[key] || ''
            }
        })
        params.page = 1
        fetchData()
    }

    /**
     * Поиск
     */
    const search = (query) => {
        params.search = query
        params.page = 1
        fetchData()
    }

    // Автоматическая загрузка при инициализации
    if (options.autoFetch !== false) {
        fetchData()
    }

    return {
        data,
        loading,
        error,
        params,
        pagination,
        fetchData,
        setPage,
        setPerPage,
        setSort,
        setFilter,
        resetFilters,
        search,
    }
}
