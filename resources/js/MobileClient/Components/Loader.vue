<template>
    <div v-if="visible" class="loader-overlay">
        <div class="spinner-border text-primary" role="status"></div>
    </div>
</template>

<script>
export default {
    name: 'Loader',
    props: {
        visible: Boolean
    }
}
</script>

<style scoped>
.loader-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
</style>
