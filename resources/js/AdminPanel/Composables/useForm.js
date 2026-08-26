import { ref, reactive } from 'vue'
import { useNotifications } from './useNotifications'

export function useForm(initialData = {}, options = {}) {
    const notifications = useNotifications()

    // Данные формы
    const form = reactive({ ...initialData })

    // Состояние
    const loading = ref(false)
    const errors = ref({})

    /**
     * Сброс формы
     */
    const reset = () => {
        Object.keys(form).forEach(key => {
            form[key] = initialData[key] !== undefined ? initialData[key] : ''
        })
        errors.value = {}
    }

    /**
     * Установка данных формы
     */
    const setData = (data) => {
        Object.keys(data).forEach(key => {
            if (key in form) {
                form[key] = data[key]
            }
        })
    }

    /**
     * Установка ошибок
     */
    const setErrors = (newErrors) => {
        errors.value = newErrors
    }

    /**
     * Очистка ошибок
     */
    const clearErrors = () => {
        errors.value = {}
    }

    /**
     * Отправка формы
     */
    const submit = async (submitFunction, successMessage = 'Операция выполнена успешно') => {
        loading.value = true
        errors.value = {}

        try {
            const result = await submitFunction(form)

            if (successMessage) {
                notifications.success(successMessage)
            }

            reset()
            return { success: true, data: result }
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors || {}
                notifications.error('Пожалуйста, исправьте ошибки в форме')
            } else {
                const message = error.response?.data?.message || 'Произошла ошибка'
                notifications.error(message)
            }

            return { success: false, error }
        } finally {
            loading.value = false
        }
    }

    return {
        form,
        loading,
        errors,
        reset,
        setData,
        setErrors,
        clearErrors,
        submit,
    }
}
