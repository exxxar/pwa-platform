<template>
    <div class="notification-container" :class="`position-${position}`">
        <transition-group name="notification-stack" tag="div" class="notification-stack">
            <NotificationItem
                v-for="notification in notifications"
                :key="notification.id"
                v-bind="notification"
                @close="removeNotification"
            />
        </transition-group>
    </div>
</template>

<script>
import NotificationItem from './NotificationItem.vue';

export default {
    name: "NotificationContainer",

    components: {
        NotificationItem,
    },

    props: {
        position: {
            type: String,
            default: 'top-right',
            validator: (value) => [
                'top-right',
                'top-left',
                'bottom-right',
                'bottom-left',
                'top-center',
                'bottom-center',
            ].includes(value),
        },
    },

    data() {
        return {
            notifications: [],
            nextId: 1,
        };
    },

    methods: {
        addNotification(options) {
            const id = this.nextId++;
            const notification = {
                id,
                type: 'info',
                title: '',
                duration: 5000,
                closable: true,
                position: this.position,
                ...options,
            };

            this.notifications.push(notification);

            // Ограничение количества уведомлений
            if (this.notifications.length > 5) {
                this.removeNotification(this.notifications[0].id);
            }

            return id;
        },

        removeNotification(id) {
            const index = this.notifications.findIndex(n => n.id === id);
            if (index !== -1) {
                this.notifications.splice(index, 1);
            }
        },

        clearAll() {
            this.notifications = [];
        },
    },
};
</script>

<style scoped>
.notification-container {
    position: fixed;
    z-index: 99999;
    pointer-events: none;
}

.notification-container.position-top-right {
    top: 20px;
    right: 20px;
}

.notification-container.position-top-left {
    top: 20px;
    left: 20px;
}

.notification-container.position-bottom-right {
    bottom: 20px;
    right: 20px;
}

.notification-container.position-bottom-left {
    bottom: 20px;
    left: 20px;
}

.notification-container.position-top-center {
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
}

.notification-container.position-bottom-center {
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
}

.notification-stack {
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: auto;
}

/* Анимация стека */
.notification-stack-enter-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification-stack-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification-stack-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.notification-stack-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

.notification-stack-move {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Адаптив */
@media (max-width: 576px) {
    .notification-container.position-top-right,
    .notification-container.position-top-left,
    .notification-container.position-bottom-right,
    .notification-container.position-bottom-left {
        left: 12px;
        right: 12px;
        transform: none;
    }

    .notification-container.position-top-center,
    .notification-container.position-bottom-center {
        left: 12px;
        right: 12px;
        transform: none;
    }
}
</style>
