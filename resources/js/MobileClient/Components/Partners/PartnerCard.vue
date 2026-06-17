<template>
    <div
        class="partner-card"
        :class="{ 'is-self': isSelf, 'is-favorite': isFavorite }"
        @click="$emit('select', partner)"
    >

        <!-- Изображение -->
        <div class="card-image-wrapper">
            <img
                :src="partner.image || partner.logo || '/images/partner-placeholder.png'"
                :alt="partner.name"
                class="card-image"
            >

            <!-- Бейджи -->
            <div class="card-badges">
                <div v-if="isSelf" class="badge self-badge">
                    <i class="fa-solid fa-star"></i>
                    <span>Основной</span>
                </div>
                <div v-if="partner.is_new" class="badge new-badge">
                    NEW
                </div>
            </div>

            <!-- Кнопка избранного -->
            <button
                class="favorite-btn"
                :class="{ 'active': isFavorite }"
                @click.stop="$emit('toggle-favorite', partner.id)"
            >
                <i :class="isFavorite ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i>
            </button>
        </div>

        <!-- Контент -->
        <div class="card-content">
            <div class="card-header">
                <h6 class="card-title">{{ partner.name }}</h6>
                <div v-if="partner.rating" class="card-rating">
                    <i class="fa-solid fa-star"></i>
                    <span>{{ partner.rating.toFixed(1) }}</span>
                </div>
            </div>

            <p v-if="partner.description" class="card-description">
                {{ partner.description }}
            </p>

            <!-- Мета-информация -->
            <div class="card-meta">
                <div v-if="partner.address" class="meta-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>{{ shortAddress }}</span>
                </div>
                <div v-if="partner.distance" class="meta-item">
                    <i class="fa-solid fa-route"></i>
                    <span>{{ partner.distance.toFixed(1) }} км</span>
                </div>
                <div v-if="partner.extra_charge > 0" class="meta-item charge-item">
                    <i class="fa-solid fa-percent"></i>
                    <span>+{{ partner.extra_charge }}%</span>
                </div>
            </div>

            <!-- Кнопка действия -->
            <div class="card-action">
                <span>Перейти в магазин</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "PartnerCard",

    props: {
        partner: {
            type: Object,
            required: true,
        },
        isSelf: {
            type: Boolean,
            default: false,
        },
        isFavorite: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['select', 'toggle-favorite'],

    computed: {
        shortAddress() {
            const addr = this.partner.address || '';
            if (addr.length <= 40) return addr;
            return addr.slice(0, 40) + '...';
        },
    },
};
</script>

<style scoped>
.partner-card {
    display: flex;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.partner-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.12);
}

.partner-card.is-self {
    border-color: rgba(255, 193, 7, 0.3);
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.05) 0%, transparent 100%);
}

.partner-card.is-favorite {
    border-color: rgba(220, 53, 69, 0.2);
}

/* ==========================================
   ИЗОБРАЖЕНИЕ
   ========================================== */
.card-image-wrapper {
    position: relative;
    width: 120px;
    min-height: 140px;
    flex-shrink: 0;
    overflow: hidden;
    background: var(--bs-secondary-bg);
}

.card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.partner-card:hover .card-image {
    transform: scale(1.05);
}

/* Бейджи */
.card-badges {
    position: absolute;
    top: 8px;
    left: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.self-badge {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #1a1a1a;
}

.new-badge {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
}

/* Кнопка избранного */
.favorite-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.favorite-btn:hover {
    transform: scale(1.15);
    color: #dc3545;
}

.favorite-btn.active {
    color: #dc3545;
    background: white;
    animation: heartBeat 0.4s ease;
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(0.9); }
    75% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.card-content {
    flex: 1;
    padding: 14px;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 6px;
}

.card-title {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
    line-height: 1.2;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.card-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: rgba(255, 193, 7, 0.15);
    color: #b8860b;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.card-rating i {
    font-size: 0.7rem;
    color: #ffc107;
}

.card-description {
    margin: 0 0 10px 0;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Мета-информация */
.card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.meta-item i {
    color: var(--bs-primary);
    font-size: 0.7rem;
}

.charge-item {
    padding: 2px 8px;
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border-radius: 6px;
    font-weight: 600;
}

.charge-item i {
    color: #dc3545;
}

/* Кнопка действия */
.card-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid var(--bs-border-color-translucent);
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-primary);
}

.card-action i {
    font-size: 0.7rem;
    transition: transform 0.2s ease;
}

.partner-card:hover .card-action i {
    transform: translateX(4px);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .card-image-wrapper {
        width: 100px;
        min-height: 120px;
    }

    .card-title {
        font-size: 0.9rem;
    }

    .card-description {
        font-size: 0.75rem;
        -webkit-line-clamp: 1;
    }

    .card-meta {
        gap: 6px;
    }

    .meta-item {
        font-size: 0.7rem;
    }
}
</style>
