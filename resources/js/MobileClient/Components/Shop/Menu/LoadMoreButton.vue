<template>
    <div class="col-12">
        <button
            class="btn btn-outline-light text-primary p-3 my-3 w-100"
            type="button"
            :disabled="isLoading"
            @click="$emit('load-more')"
        >
            <span v-if="!isLoading">
                Загрузить ещё ({{ remaining }})
            </span>
            <span v-else class="d-inline-flex align-items-center">
                Загружаем...
                <span class="spinner-border spinner-border-sm ms-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </span>
            </span>
        </button>
    </div>
</template>

<script>
export default {
    name: 'LoadMoreButton',

    props: {
        remaining: Number,
        isLoading: Boolean,
    },

    emits: ['load-more'],
};
</script>
