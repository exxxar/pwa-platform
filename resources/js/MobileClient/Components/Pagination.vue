<template>
    <!-- Проверяем наличие пагинации и что страниц больше одной -->
    <nav v-if="hasPagination" class="mt-4">
        <ul class="pagination justify-content-center mb-3">

            <!-- Первая страница -->
            <li class="page-item">
                <button
                    type="button"
                    @click="first"
                    :disabled="pagination.meta.current_page === 1"
                    class="page-link rounded-xs color-white shadow-xl border-0"
                    :class="{ 'bg-gray2-dark': pagination.meta.current_page === 1, 'bg-black': pagination.meta.current_page > 1 }"
                >
                    <i class="fa-solid fa-angles-left"></i>
                </button>
            </li>

            <!-- Предыдущая страница -->
            <li class="page-item">
                <button
                    type="button"
                    @click="prevPage"
                    :disabled="pagination.meta.current_page === 1"
                    class="page-link rounded-xs color-white shadow-xl border-0"
                    :class="{ 'bg-gray2-dark': pagination.meta.current_page === 1, 'bg-black': pagination.meta.current_page > 1 }"
                >
                    <i class="fa fa-angle-left"></i>
                </button>
            </li>

            <!-- Числовые ссылки на страницы (раньше этот блок отсутствовал, хотя логика для него была) -->
            <li
                v-for="link in numericLinks"
                :key="link.label"
                class="page-item"
            >
                <button
                    type="button"
                    @click="page(link.label)"
                    :disabled="link.active"
                    class="page-link rounded-xs shadow-xl border-0"
                    :class="{ 'bg-gray2-dark': link.active, 'bg-black': !link.active, 'color-white': true }"
                    v-html="link.label"
                ></button>
            </li>

            <!-- Следующая страница -->
            <li class="page-item">
                <button
                    type="button"
                    @click="nextPage"
                    :disabled="pagination.meta.current_page === pagination.meta.last_page"
                    class="page-link rounded-xs color-white shadow-xl border-0"
                    :class="{ 'bg-gray2-dark': pagination.meta.current_page === pagination.meta.last_page, 'bg-black': pagination.meta.current_page < pagination.meta.last_page }"
                >
                    <i class="fa fa-angle-right"></i>
                </button>
            </li>

            <!-- Последняя страница -->
            <li class="page-item">
                <button
                    type="button"
                    @click="last"
                    :disabled="pagination.meta.current_page === pagination.meta.last_page"
                    class="page-link rounded-xs color-white shadow-xl border-0"
                    :class="{ 'bg-gray2-dark': pagination.meta.current_page === pagination.meta.last_page, 'bg-black': pagination.meta.current_page < pagination.meta.last_page }"
                >
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            </li>
        </ul>

        <!-- Информация о текущей странице -->
        <p class="text-center mb-3">
            <small style="font-weight: bold;">
                Страница {{ pagination.meta.current_page }} из {{ pagination.meta.last_page }}
            </small>
        </p>
    </nav>
</template>

<script>
export default {
    // Добавлена валидация пропсов для надежности
    props: {
        pagination: {
            type: Object,
            required: true
        }
    },
    computed: {
        // Надежная проверка: пагинация есть только если страниц больше 1
        hasPagination() {
            return this.pagination &&
                this.pagination.meta &&
                this.pagination.meta.last_page > 1;
        },
        // Фильтруем только числовые ссылки, исключая служебные "Previous" и "Next" от Laravel
        numericLinks() {
            if (!this.pagination || !this.pagination.meta || !this.pagination.meta.links) {
                return [];
            }
            return this.pagination.meta.links.filter(link => {
                const label = String(link.label);
                // Исключаем стандартные текстовые метки Laravel
                return !label.includes('Previous') &&
                    !label.includes('Next') &&
                    !label.includes('&laquo;') &&
                    !label.includes('&raquo;') &&
                    link.url !== null;
            });
        }
    },
    methods: {
        first() {
            this.$emit('pagination_page', 1);
        },
        last() {
            this.$emit('pagination_page', this.pagination.meta.last_page);
        },
        nextPage() {
            if (this.pagination.meta.current_page < this.pagination.meta.last_page) {
                this.$emit('pagination_page', this.pagination.meta.current_page + 1);
            }
        },
        prevPage() {
            if (this.pagination.meta.current_page > 1) {
                this.$emit('pagination_page', this.pagination.meta.current_page - 1);
            }
        },
        page(index) {
            // Преобразуем в число на случай, если label пришел строкой
            const pageNum = parseInt(index, 10);

            window.scrollTo({
                top: 10,
                behavior: "smooth"
            });

            this.$emit('pagination_page', pageNum);
        }
    }
}
</script>

<style scoped>
/* Добавлен scoped, чтобы стили не влияли на другие пагинации в проекте */
.page-item {
    height: 100%;
}

/* Опционально: убираем стандартный аутлайн Bootstrap при фокусе, если он мешает вашему дизайну */
.page-link:focus {
    box-shadow: none;
}
</style>
