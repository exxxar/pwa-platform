<template>
    <div class="partner-card" :class="{ 'is-self': isSelf }" @click="$emit('select', partner)">
        <!-- Бейдж "Основной партнер" -->
        <div v-if="isSelf" class="self-badge">
            <i class="fa-solid fa-star"></i> Наше заведение
        </div>

        <!-- Изображение -->
        <div class="card-image">
            <img
                v-lazy="partner.image || partner.logo || 'https://via.placeholder.com/400x250?text=No+Image'"
                :alt="partner.name"
                loading="lazy"
            >
            <div class="card-overlay">
                <button class="select-btn">
                    <i class="fa-solid fa-arrow-right"></i> Смотреть меню
                </button>
            </div>
        </div>

        <!-- Контент -->
        <div class="card-content">
            <h3 class="partner-name">{{ partner.name }}</h3>

            <div class="partner-meta">
                <div class="meta-item" v-if="partner.address">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>{{ partner.address }}</span>
                </div>
                <div class="meta-item" v-if="partner.delivery_time">
                    <i class="fa-solid fa-clock"></i>
                    <span>{{ partner.delivery_time }} мин</span>
                </div>
            </div>

            <p class="partner-description" v-if="partner.description">
                {{ partner.description }}
            </p>
        </div>
    </div>
</template>

<script>
export default {
    name: "PartnerCard",
    props: {
        partner: { type: Object, required: true },
        isSelf: { type: Boolean, default: false }
    },
    emits: ['select']
}
</script>

<style lang="scss" scoped>
.partner-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.04);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;

    &:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        border-color: rgba(255, 122, 0, 0.2);

        .card-overlay {
            opacity: 1;
        }

        .card-image img {
            transform: scale(1.05);
        }
    }

    &.is-self {
        border: 2px solid var(--primary);
        box-shadow: 0 10px 30px rgba(255, 122, 0, 0.15);
    }
}

.self-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    background: var(--primary);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(255, 122, 0, 0.3);
}

.card-image {
    position: relative;
    height: 200px; /* Фиксированная высота для единообразия */
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .card-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(2px);
    }

    .select-btn {
        background: white;
        color: var(--dark);
        border: none;
        padding: 12px 24px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transform: translateY(10px);
        transition: all 0.3s ease;

        .partner-card:hover & {
            transform: translateY(0);
        }

        &:hover {
            background: var(--primary);
            color: white;
        }
    }
}

.card-content {
    padding: 24px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.partner-name {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--dark);
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.partner-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: var(--gray);

    i {
        color: var(--primary);
        width: 16px;
        text-align: center;
    }
}

.partner-description {
    font-size: 0.9rem;
    color: var(--gray);
    line-height: 1.5;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2; /* Обрезаем после 2 строк */
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-top: auto; /* Прижимает к низу, если карточки разной высоты */
}
</style>
