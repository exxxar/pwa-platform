<template>
    <div class="schedule-widget">

        <!-- Заголовок виджета -->
        <div class="schedule-header">
            <div class="header-icon">
                <i class="fa-regular fa-clock"></i>
            </div>
            <span class="header-title">График работы</span>
        </div>

        <!-- Список дней -->
        <div class="schedule-list">
            <div
                v-for="(item, index) in sortedSchedule"
                :key="item.day"
                class="schedule-row"
                :class="{ 'is-today': isToday(item) }"
            >
                <!-- Левая часть: День недели -->
                <div class="day-info">
                    <span class="day-name">{{ item.day }}</span>
                    <span v-if="isToday(item)" class="today-badge">
                        Сегодня
                    </span>
                </div>

                <!-- Правая часть: Время или статус закрытия -->
                <div class="time-info" :class="{ 'is-closed': item.closed }">
                    <template v-if="!item.closed">
                        <i class="fa-solid fa-circle-check status-icon open"></i>
                        <span class="time-range">
                            {{ item.start_at || '—' }} – {{ item.end_at || '—' }}
                        </span>
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-circle-xmark status-icon closed"></i>
                        <span class="closed-text">
                            {{ item.closed_comment || 'Закрыто' }}
                        </span>
                    </template>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "ScheduleList",

    props: {
        schedule: {
            type: Array,
            default: () => [],
        },
    },

    computed: {
        // Надежное определение текущего дня недели на русском
        currentDayName() {
            const days = [
                'Воскресенье', 'Понедельник', 'Вторник', 'Среда',
                'Четверг', 'Пятница', 'Суббота'
            ];
            return days[new Date().getDay()];
        },

        // Сортировка дней в правильном порядке (Пн -> Вс)
        sortedSchedule() {
            const dayOrder = {
                'Понедельник': 1,
                'Вторник': 2,
                'Среда': 3,
                'Четверг': 4,
                'Пятница': 5,
                'Суббота': 6,
                'Воскресенье': 7
            };

            return [...(this.schedule || [])].sort((a, b) => {
                const orderA = dayOrder[a.day] || 8;
                const orderB = dayOrder[b.day] || 8;
                return orderA - orderB;
            });
        },
    },

    methods: {
        isToday(item) {
            return item.day === this.currentDayName;
        },
    },
};
</script>

<style scoped>
/* ==========================================
   КОНТЕЙНЕР ВИДЖЕТА
   ========================================== */
.schedule-widget {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ==========================================
   ЗАГОЛОВОК
   ========================================== */
.schedule-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 20px;
    background: rgba(var(--bs-primary-rgb), 0.04);
    border-bottom: 1px solid var(--bs-border-color);
}

.header-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(var(--bs-primary-rgb), 0.25);
}

.header-title {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

/* ==========================================
   СПИСОК ДНЕЙ
   ========================================== */
.schedule-list {
    padding: 8px 0;
}

.schedule-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.schedule-row:hover {
    background: rgba(var(--bs-primary-rgb), 0.03);
}

/* Выделение текущего дня */
.schedule-row.is-today {
    background: rgba(var(--bs-primary-rgb), 0.06);
    border-left-color: var(--bs-primary);
}

/* ==========================================
   ИНФОРМАЦИЯ О ДНЕ
   ========================================== */
.day-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.day-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.schedule-row.is-today .day-name {
    font-weight: 700;
    color: var(--bs-primary);
}

.today-badge {
    padding: 2px 8px;
    background: var(--bs-primary);
    color: white;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    animation: badgePop 0.3s ease;
}

@keyframes badgePop {
    0% { transform: scale(0); }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* ==========================================
   ИНФОРМАЦИЯ О ВРЕМЕНИ / СТАТУСЕ
   ========================================== */
.time-info {
    display: flex;
    align-items: center;
    gap: 8px;
    text-align: right;
}

.status-icon {
    font-size: 0.8rem;
    flex-shrink: 0;
}

.status-icon.open {
    color: #198754;
}

.status-icon.closed {
    color: #dc3545;
}

.time-range {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    font-variant-numeric: tabular-nums; /* Выравнивание цифр */
}

.closed-text {
    font-size: 0.85rem;
    color: #dc3545;
    font-weight: 500;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .schedule-row {
        padding: 10px 16px;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }

    .time-info {
        width: 100%;
        justify-content: flex-start;
    }

    .day-name {
        font-size: 0.9rem;
    }

    .time-range,
    .closed-text {
        font-size: 0.85rem;
    }
}
</style>
