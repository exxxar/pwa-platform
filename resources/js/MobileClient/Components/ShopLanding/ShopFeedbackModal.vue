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
                    <p class="modal-subtitle">{{ config.subtitle }}</p>
                    <form @submit.prevent="submit">
                        <div class="form-group">
                            <label>{{ config.nameLabel }}</label>
                            <input type="text" v-model="form.name" required>
                        </div>
                        <div class="form-group">
                            <label>{{ config.phoneLabel }}</label>
                            <input type="tel" v-model="form.phone" required>
                        </div>
                        <div class="form-group">
                            <label>{{ config.messageLabel }}</label>
                            <textarea v-model="form.message" rows="4"></textarea>
                        </div>
                        <button type="submit" class="submit-btn">
                            {{ config.submitText }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "ShopFeedbackModal",
    props: {
        config: Object,
    },
    emits: ['close', 'submit'],
    data() {
        return {
            form: {
                name: '',
                phone: '',
                message: '',
            },
        };
    },
    methods: {
        submit() {
            this.$emit('submit', this.form);
            this.form = { name: '', phone: '', message: '' };
        },
    },
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
    max-width: 500px;
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

.modal-subtitle {
    color: var(--gray);
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.2rem;

    label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    input, textarea {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        font-size: 1rem;
        transition: border-color 0.3s ease;

        &:focus {
            outline: none;
            border-color: var(--primary);
        }
    }
}

.submit-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 122, 0, 0.25);
    }
}

.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}
</style>
