import { ref } from 'vue'

// Глобальное состояние уведомлений
const notifications = ref([])
let nextId = 1

export function useNotifications() {
    /**
     * Добавить уведомление
     */
    const addNotification = (type, message, duration = 5000) => {
        const id = nextId++
        const notification = {
            id,
            type, // 'success', 'error', 'warning', 'info'
            message,
            duration,
        }

        notifications.value.push(notification)

        // Автоматическое удаление через duration
        if (duration > 0) {
            setTimeout(() => {
                removeNotification(id)
            }, duration)
        }

        return id
    }

    /**
     * Удалить уведомление
     */
    const removeNotification = (id) => {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index !== -1) {
            notifications.value.splice(index, 1)
        }
    }

    /**
     * Показать успешное уведомление
     */
    const success = (message, duration = 5000) => {
        return addNotification('success', message, duration)
    }

    /**
     * Показать уведомление об ошибке
     */
    const error = (message, duration = 5000) => {
        return addNotification('error', message, duration)
    }

    /**
     * Показать предупреждение
     */
    const warning = (message, duration = 5000) => {
        return addNotification('warning', message, duration)
    }

    /**
     * Показать информационное уведомление
     */
    const info = (message, duration = 5000) => {
        return addNotification('info', message, duration)
    }

    return {
        notifications,
        addNotification,
        removeNotification,
        success,
        error,
        warning,
        info,
    }
}
