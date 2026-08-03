<!-- UpdateToast.vue -->
<script setup>
import { useAppUpdater } from '@/MobileClient/composables/useAppUpdater';
const { showUpdateToast, forceUpdate } = useAppUpdater();
</script>

<template>
    <Transition name="slide-up">
        <div v-if="showUpdateToast" class="update-toast">
            <div class="toast-content">
                <i class="fa-solid fa-cloud-arrow-down"></i>
                <div>
                    <strong>Доступно обновление</strong>
                    <p>Загрузите новую версию для корректной работы.</p>
                </div>
            </div>
            <button @click="forceUpdate" class="btn-update">Обновить</button>
        </div>
    </Transition>
</template>

<style scoped>
.update-toast {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--bg-secondary, #1e1e1e);
    color: var(--text, #fff);
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 16px;
    max-width: 90vw;
    width: 400px;
    border: 1px solid var(--primary, #ff8a00);
}
.toast-content { display: flex; gap: 12px; align-items: center; flex: 1; }
.toast-content i { font-size: 24px; color: var(--primary, #ff8a00); }
.toast-content p { margin: 4px 0 0; font-size: 0.85rem; opacity: 0.8; }
.btn-update {
    background: var(--primary, #ff8a00);
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.4s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translate(-50%, 100px); opacity: 0; }
</style>
