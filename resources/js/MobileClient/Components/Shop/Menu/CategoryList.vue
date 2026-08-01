<template>
    <div class="category-list album" style="min-height: 100vh;">
        <div class="container g-2">
            <div class="list-group" v-if="categories && categories.length > 0">

                <!-- Все категории -->
                <a
                    href="javascript:void(0)"
                    class="list-group-item list-group-item-action d-flex justify-content-between p-3 fw-bold"
                    @click="$emit('select-category', null)"
                >
                    Все категории товаров
                </a>

                <!-- Комбо-меню -->
                <a
                    v-if="collectionsCount > 0"
                    href="javascript:void(0)"
                    class="list-group-item list-group-item-action d-flex justify-content-between p-3 fw-bold"
                    @click="$emit('select-category', { id: 'combo' })"
                >
                    Комбо-меню
                    <span class="badge text-bg-primary">{{ collectionsCount }}</span>
                </a>

                <!-- 🆕 ИСПРАВЛЕНИЕ: проверяем item.products.length, а не item.count -->
                <template v-for="item in categories" :key="item.id">
                    <a
                        v-if="item.products && item.products.length > 0"
                        href="javascript:void(0)"
                        class="list-group-item list-group-item-action d-flex justify-content-between p-3 align-items-center fw-bold"
                        @click="$emit('select-category', item)"
                    >
                        {{ item.name || 'Не указано' }}
                        <span class="badge text-bg-primary">{{ item.products.length }}</span>
                    </a>
                </template>

            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CategoryList',
    props: {
        categories: { type: Array, default: () => [] },
        collectionsCount: { type: Number, default: 0 },
    },
    emits: ['select-category'],
};
</script>
