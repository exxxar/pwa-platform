<template>
    <transition name="modal-fade">
        <div class="modal-overlay" @click.self="$emit('close')">
            <div class="modal-container">
                <div class="modal-header">
                    <h3>{{ config.title }}</h3>
                    <button class="close-btn" @click="$emit('close')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="privacy-content" v-html="config.content"></div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "ShopPrivacyModal",
    props: {
        config: Object,
    },
    emits: ['close'],
};
</script>

<style lang="scss" scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: white;
    border-radius: 24px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);

    h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }
}

.close-btn {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray);
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
}

.privacy-content {
    line-height: 1.8;
    color: var(--dark);

    h4 {
        margin-top: 1.5rem;
        margin-bottom: 0.8rem;
    }

    p {
        margin-bottom: 1rem;
    }
}

.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}
</style>
