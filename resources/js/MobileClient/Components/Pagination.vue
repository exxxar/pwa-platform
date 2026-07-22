<template>
    <nav v-if="hasPagination" class="custom-pagination">
        <div class="pagination-controls">
            <!-- Первая страница -->
            <button
                type="button"
                @click="first"
                :disabled="currentPage === 1"
                class="page-btn"
                title="В начало"
            >
                <i class="fa-solid fa-angles-left"></i>
            </button>


            <!-- Предыдущая страница -->
            <button
                type="button"
                @click="prevPage"
                :disabled="currentPage === 1"
                class="page-btn"
                title="Назад"
            >
                <i class="fa-solid fa-angle-left"></i>
            </button>

            <!-- Числовые ссылки -->
            <div class="page-numbers">
                <button
                    v-for="link in numericLinks"
                    :key="link.label"
                    type="button"
                    @click="page(link.label)"
                    :disabled="link.active"
                    class="page-number"
                    :class="{ 'is-active': link.active }"
                    v-html="link.label"
                ></button>
            </div>

            <!-- Следующая страница -->
            <button
                type="button"
                @click="nextPage"
                :disabled="currentPage === lastPage"
                class="page-btn"
                title="Вперед"
            >
                <i class="fa-solid fa-angle-right"></i>
            </button>

            <!-- Последняя страница -->
            <button
                type="button"
                @click="last"
                :disabled="currentPage === lastPage"
                class="page-btn"
                title="В конец"
            >
                <i class="fa-solid fa-angles-right"></i>
            </button>
        </div>

        <!-- Информация о странице -->
        <p class="pagination-info">
            Страница <strong>{{ currentPage }}</strong> из <strong>{{ lastPage }}</strong>
            <span v-if="pagination.total"> (всего: {{ pagination.total }})</span>
        </p>
    </nav>
</template>

<script>
export default {
    name: 'Pagination',

    props: {
        // Ожидаем плоский объект пагинации Laravel (как в вашем JSON)
        pagination: {
            type: Object,
            required: true
        }
    },

    computed: {
        // Строго приводим к числам, чтобы избежать "1" + 1 = "11"
        currentPage() {
            return Number(this.pagination?.current_page || 1);
        },
        lastPage() {
            return Number(this.pagination?.last_page || 1);
        },
        hasPagination() {
            return this.lastPage > 1;
        },
        numericLinks() {
            if (!this.pagination?.links) return [];

            return this.pagination.links.filter(link => {
                const label = String(link.label);
                // Исключаем ТОЛЬКО текстовые навигационные ссылки.
                // Цифры оставляем, даже если url === null (это текущая страница, она будет просто неактивна).
                const isNavText = label.includes('Previous') ||
                    label.includes('Next') ||
                    label.includes('&laquo;') ||
                    label.includes('&raquo;');
                return !isNavText;
            });
        }
    },

    methods: {
        first() {
            this.$emit('pagination_page', 1);
        },
        last() {
            this.$emit('pagination_page', this.lastPage);
        },
        prevPage() {
            console.log("prevPage", this.currentPage - 1)
            if (this.currentPage > 1) {
                this.$emit('pagination_page', this.currentPage - 1);
            }
        },
        nextPage() {
            console.log("nextPage", this.currentPage + 1)
            if (this.currentPage < this.lastPage) {
                this.$emit('pagination_page', this.currentPage + 1);
            }
        },
        page(index) {
            const pageNum = parseInt(index, 10);
            if (isNaN(pageNum)) return;

            // Плавная прокрутка наверх
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });

            this.$emit('pagination_page', pageNum);
        }
    }
}
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;

.custom-pagination {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid $border;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.page-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid $border;
    border-radius: 8px;
    background: white;
    color: $text;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        border-color: $primary;
        color: $primary;
        background: rgba($primary, 0.05);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        background: $bg;
    }
}

.page-numbers {
    display: flex;
    align-items: center;
    gap: 4px;
}

.page-number {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: $text;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(.is-active) {
        background: $bg;
        color: $primary;
    }

    &.is-active {
        background: $primary;
        color: white;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba($primary, 0.3);
        cursor: default;
    }
}

.pagination-info {
    font-size: 0.8rem;
    color: $text-muted;
    margin: 0;
    text-align: center;

    strong {
        color: $text;
        font-weight: 600;
    }
}

// Адаптив для мобильных
@media (max-width: 480px) {
    .page-btn {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    .page-number {
        min-width: 32px;
        height: 32px;
        font-size: 0.85rem;
        padding: 0 6px;
    }
}
</style>
