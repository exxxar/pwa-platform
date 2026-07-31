<template>
    <div class="settings-panel fade-in">
        <div class="panel-header">
            <h3><i class="fa-solid fa-eye"></i> Видимость секций</h3>
            <p>Включайте или отключайте отображение блоков на главной странице. Изменения применяются мгновенно.</p>
        </div>

        <div class="sections-list">
            <div v-for="(isEnabled, key) in sections" :key="key" class="section-row">
                <div class="section-info">
                    <div class="section-icon" :style="{ background: getIconColor(key) }">
                        <i :class="getIcon(key)"></i>
                    </div>
                    <div class="section-text">
                        <span class="section-title">{{ getTitle(key) }}</span>
                        <span class="section-desc">{{ getDesc(key) }}</span>
                    </div>
                </div>

                <label class="toggle-switch">
                    <input type="checkbox" v-model="sections[key]">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TabSections",
    props: {
        sections: { type: Object, required: true }
    },
    methods: {
        getIcon(key) {
            const icons = {
                hero: 'fa-solid fa-image',
                partners: 'fa-solid fa-handshake',
                promotions: 'fa-solid fa-percent',
                delivery: 'fa-solid fa-truck-fast',
                pwaBanner: 'fa-solid fa-mobile-screen',
                loyalty: 'fa-solid fa-gem',
                wheel: 'fa-solid fa-dharmachakra',
                reviews: 'fa-solid fa-star',
                faq: 'fa-solid fa-circle-question',
                reservation: 'fa-solid fa-calendar-check',
                cta: 'fa-solid fa-bullhorn',
                footer: 'fa-solid fa-shoe-prints'
            };
            return icons[key] || 'fa-solid fa-circle';
        },
        getTitle(key) {
            const titles = {
                hero: 'Hero секция (Главный экран)',
                partners: 'Партнеры и выбор партнера',
                promotions: 'Акции и спецпредложения',
                delivery: 'Информация о доставке',
                pwaBanner: 'Баннер установки PWA',
                loyalty: 'Программа лояльности',
                wheel: 'Колесо фортуны',
                reviews: 'Отзывы клиентов',
                faq: 'Часто задаваемые вопросы (FAQ)',
                reservation: 'Бронирование столиков',
                cta: 'Призыв к действию (CTA)',
                footer: 'Футер (Подвал сайта)'
            };
            return titles[key] || key;
        },
        getDesc(key) {
            const descs = {
                hero: 'Главный баннер с заголовком и кнопкой',
                partners: 'Список партнеров и плашка выбранного',
                promotions: 'Блок с текущими акциями заведения',
                delivery: 'Условия, зоны и стоимость доставки',
                pwaBanner: 'Предложение установить приложение',
                loyalty: 'Отображение баллов и статуса клиента',
                wheel: 'Интерактивная игра с призами',
                reviews: 'Слайдер или сетка с отзывами гостей',
                faq: 'Раскрывающийся список вопросов и ответов',
                reservation: 'Форма или виджет бронирования',
                cta: 'Финальный призыв связаться с вами',
                footer: 'Контакты, ссылки и копирайт'
            };
            return descs[key] || '';
        },
        getIconColor(key) {
            // Легкие пастельные фоны для иконок
            const colors = {
                hero: 'rgba(59, 130, 246, 0.1)',
                partners: 'rgba(16, 185, 129, 0.1)',
                promotions: 'rgba(245, 158, 11, 0.1)',
                delivery: 'rgba(99, 102, 241, 0.1)',
                pwaBanner: 'rgba(236, 72, 153, 0.1)',
                loyalty: 'rgba(139, 92, 246, 0.1)',
                wheel: 'rgba(239, 68, 68, 0.1)',
                reviews: 'rgba(234, 179, 8, 0.1)',
                faq: 'rgba(20, 184, 166, 0.1)',
                reservation: 'rgba(59, 130, 246, 0.1)',
                cta: 'rgba(249, 115, 22, 0.1)',
                footer: 'rgba(107, 114, 128, 0.1)'
            };
            return colors[key] || 'rgba(0,0,0,0.05)';
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.settings-panel {
    background: $card-bg;
    border-radius: 16px;
    padding: 28px;
    border: 1px solid $border;
}

.fade-in { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.panel-header {
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid $border;
    h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: $text;
        display: flex;
        align-items: center;
        gap: 10px;
        i { color: $primary; }
    }
    p { font-size: 0.9rem; color: $text-muted; margin: 0; }
}

.sections-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.section-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    transition: all 0.2s;

    &:hover {
        border-color: rgba($primary, 0.3);
        background: #ffffff;
    }
}

.section-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: $primary;
    flex-shrink: 0;
}

.section-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.section-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: $text;
}

.section-desc {
    font-size: 0.8rem;
    color: $text-muted;
}

// Переиспользуем стили переключателя из основного файла
.toggle-switch {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
    input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .toggle-slider {
        position: relative;
        width: 44px;
        height: 24px;
        background: $border;
        border-radius: 12px;
        transition: all 0.3s;
        &::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    }
    input:checked + .toggle-slider {
        background: $primary;
        &::after {
            left: 22px;
        }
    }
}
</style>
