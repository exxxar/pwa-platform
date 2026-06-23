<template>
    <div class="skeleton-loader" :class="[`type-${type}`, { 'is-animated': animated }]">

        <!-- Тип: Карточка товара -->
        <template v-if="type === 'product'">
            <div v-for="i in count" :key="i" class="skeleton-product">
                <div class="skeleton-image shimmer"></div>
                <div class="skeleton-content">
                    <div class="skeleton-line title shimmer" style="width: 80%;"></div>
                    <div class="skeleton-line subtitle shimmer" style="width: 60%;"></div>
                    <div class="skeleton-line price shimmer" style="width: 40%;"></div>
                    <div class="skeleton-line button shimmer"></div>
                </div>
            </div>
        </template>

        <!-- Тип: Список -->
        <template v-else-if="type === 'list'">
            <div v-for="i in count" :key="i" class="skeleton-list-item">
                <div class="skeleton-avatar shimmer"></div>
                <div class="skeleton-list-content">
                    <div class="skeleton-line title shimmer" style="width: 70%;"></div>
                    <div class="skeleton-line subtitle shimmer" style="width: 50%;"></div>
                </div>
            </div>
        </template>

        <!-- Тип: Профиль пользователя -->
        <template v-else-if="type === 'profile'">
            <div class="skeleton-profile">
                <div class="skeleton-profile-header">
                    <div class="skeleton-avatar large shimmer"></div>
                    <div class="skeleton-profile-info">
                        <div class="skeleton-line title shimmer" style="width: 60%;"></div>
                        <div class="skeleton-line subtitle shimmer" style="width: 40%;"></div>
                        <div class="skeleton-line subtitle shimmer" style="width: 50%;"></div>
                    </div>
                </div>
                <div class="skeleton-profile-stats">
                    <div v-for="i in 3" :key="i" class="skeleton-stat">
                        <div class="skeleton-line shimmer" style="width: 50px; height: 24px;"></div>
                        <div class="skeleton-line shimmer" style="width: 70px; height: 12px; margin-top: 6px;"></div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Тип: Hero-секция -->
        <template v-else-if="type === 'hero'">
            <div class="skeleton-hero">
                <div class="skeleton-line badge shimmer" style="width: 180px; height: 32px; border-radius: 50px;"></div>
                <div class="skeleton-line title shimmer" style="width: 90%; height: 48px;"></div>
                <div class="skeleton-line title shimmer" style="width: 70%; height: 48px;"></div>
                <div class="skeleton-line subtitle shimmer" style="width: 60%; height: 20px; margin-top: 16px;"></div>
                <div class="skeleton-line button shimmer" style="width: 200px; height: 50px; border-radius: 50px; margin-top: 24px;"></div>
            </div>
        </template>

        <!-- Тип: Произвольный текст -->
        <template v-else-if="type === 'text'">
            <div v-for="i in count" :key="i" class="skeleton-text-block">
                <div class="skeleton-line shimmer" :style="{ width: getLineWidth(i) }"></div>
            </div>
        </template>

        <!-- Тип: Карточка (универсальная) -->
        <template v-else-if="type === 'card'">
            <div v-for="i in count" :key="i" class="skeleton-card">
                <div class="skeleton-card-header">
                    <div class="skeleton-avatar small shimmer"></div>
                    <div class="skeleton-card-title">
                        <div class="skeleton-line shimmer" style="width: 120px;"></div>
                        <div class="skeleton-line shimmer" style="width: 80px; height: 10px; margin-top: 4px;"></div>
                    </div>
                </div>
                <div class="skeleton-card-body">
                    <div class="skeleton-line shimmer" style="width: 100%;"></div>
                    <div class="skeleton-line shimmer" style="width: 90%;"></div>
                    <div class="skeleton-line shimmer" style="width: 70%;"></div>
                </div>
            </div>
        </template>

        <!-- Тип: Таблица -->
        <template v-else-if="type === 'table'">
            <div class="skeleton-table">
                <div class="skeleton-table-header">
                    <div v-for="i in 4" :key="'h'+i" class="skeleton-line shimmer" style="height: 16px;"></div>
                </div>
                <div v-for="i in count" :key="'r'+i" class="skeleton-table-row">
                    <div v-for="j in 4" :key="j" class="skeleton-line shimmer" style="height: 14px;"></div>
                </div>
            </div>
        </template>

    </div>
</template>

<script>
export default {
    name: "SkeletonLoader",
    props: {
        type: {
            type: String,
            default: 'text',
            validator: (v) => ['text', 'card', 'list', 'product', 'profile', 'hero', 'table'].includes(v),
        },
        count: {
            type: Number,
            default: 3,
        },
        animated: {
            type: Boolean,
            default: true,
        },
    },
    methods: {
        // Для текстового типа — разная ширина строк для реалистичности
        getLineWidth(index) {
            const widths = ['100%', '95%', '88%', '92%', '75%', '85%'];
            return widths[(index - 1) % widths.length];
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$skeleton-base: #e5e7eb;
$skeleton-shine: #f3f4f6;
$border-radius: 8px;

// ==========================================
// БАЗА
// ==========================================
.skeleton-loader {
    width: 100%;
}

// ==========================================
// SHIMMER-ЭФФЕКТ (анимация блеска)
// ==========================================
.shimmer {
    background: linear-gradient(
            90deg,
            $skeleton-base 0%,
            $skeleton-shine 50%,
            $skeleton-base 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
}

.is-animated .shimmer {
    animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// УНИВЕРСАЛЬНЫЕ ЛИНИИ
// ==========================================
.skeleton-line {
    height: 14px;
    border-radius: 4px;
    margin-bottom: 8px;

    &.title {
        height: 18px;
    }

    &.subtitle {
        height: 12px;
        opacity: 0.7;
    }

    &.button {
        height: 40px;
        border-radius: 10px;
        margin-top: 12px;
    }

    &.badge {
        height: 32px;
        border-radius: 50px;
    }
}

// ==========================================
// ТИП: КАРТОЧКА ТОВАРА
// ==========================================
.skeleton-product {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 16px;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.skeleton-image {
    width: 100%;
    aspect-ratio: 1;
}

.skeleton-content {
    padding: 16px;
}

// ==========================================
// ТИП: СПИСОК
// ==========================================
.skeleton-list-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    background: white;
    border-radius: 12px;
    margin-bottom: 10px;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.skeleton-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    flex-shrink: 0;

    &.large {
        width: 72px;
        height: 72px;
    }

    &.small {
        width: 36px;
        height: 36px;
    }
}

.skeleton-list-content {
    flex: 1;
}

// ==========================================
// ТИП: ПРОФИЛЬ
// ==========================================
.skeleton-profile {
    background: white;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.skeleton-profile-header {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.skeleton-profile-info {
    flex: 1;
}

.skeleton-profile-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.skeleton-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 10px;
}

// ==========================================
// ТИП: HERO
// ==========================================
.skeleton-hero {
    padding: 40px 20px;
    text-align: center;
    background: white;
    border-radius: 16px;
}

// ==========================================
// ТИП: ТЕКСТ
// ==========================================
.skeleton-text-block {
    margin-bottom: 12px;
}

// ==========================================
// ТИП: КАРТОЧКА (УНИВЕРСАЛЬНАЯ)
// ==========================================
.skeleton-card {
    background: white;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.skeleton-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.skeleton-card-title {
    flex: 1;
}

.skeleton-card-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

// ==========================================
// ТИП: ТАБЛИЦА
// ==========================================
.skeleton-table {
    background: white;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.skeleton-table-header {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    padding: 14px 16px;
    background: #f9fafb;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.skeleton-table-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);

    &:last-child {
        border-bottom: none;
    }
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 640px) {
    .skeleton-profile-stats {
        grid-template-columns: 1fr;
    }

    .skeleton-table-header,
    .skeleton-table-row {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
