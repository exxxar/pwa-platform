<template>
    <div class="chat-root">

        <!-- ========================================== -->
        <!-- ПЕРЕХОД МЕЖДУ СОСТОЯНИЯМИ -->
        <!-- ========================================== -->
        <transition :name="transitionName" mode="out-in">

            <!-- СПИСОК ЧАТОВ -->
            <div v-if="!currentDialog" key="list" class="chat-view">
                <ChatList @select-dialog="onSelectDialog" />
            </div>

            <!-- ОТКРЫТЫЙ ЧАТ -->
            <div v-else key="chat" class="chat-view">
                <Chat @back="onBackToList" />
            </div>

        </transition>

    </div>
</template>

<script>
import { useChatStore } from '@/MobileClient/stores/Shop/chat';
import ChatList from "@/MobileClient/Components/Chat/ChatList.vue";
import Chat from "@/MobileClient/Components/Chat/Chat.vue";

export default {
    name: "ChatContainer",

    components: {
        ChatList,
        Chat,
    },

    setup() {
        const store = useChatStore();
        return { store };
    },

    data() {
        return {
            transitionName: 'slide-left',
        };
    },

    computed: {
        currentDialog() {
            return this.store.getCurrentDialog;
        },
    },

    methods: {
        // Выбор диалога из списка
        onSelectDialog(dialog) {
            this.transitionName = 'slide-left';
            this.store.setCurrentDialog(dialog);
        },

        // Возврат к списку
        onBackToList() {
            this.transitionName = 'slide-right';
            this.store.closeDialog();
        },
    },
};
</script>

<style scoped>
.chat-root {
    position: relative;
    width: 100%;
    height: 100vh;
    overflow: hidden;
    background: var(--bs-body-bg);
}

.chat-view {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
}

/* ==========================================
   АНИМАЦИИ ПЕРЕХОДОВ
   ========================================== */

/* Вперёд (список → чат): слайд влево */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Вход чата справа */
.slide-left-enter-from {
    transform: translateX(100%);
    opacity: 0.8;
}

.slide-left-enter-to {
    transform: translateX(0);
    opacity: 1;
}

/* Выход списка влево */
.slide-left-leave-from {
    transform: translateX(0);
    opacity: 1;
}

.slide-left-leave-to {
    transform: translateX(-30%);
    opacity: 0;
}

/* Возврат (чат → список): слайд вправо */
.slide-right-enter-from {
    transform: translateX(-30%);
    opacity: 0;
}

.slide-right-enter-to {
    transform: translateX(0);
    opacity: 1;
}

.slide-right-leave-from {
    transform: translateX(0);
    opacity: 1;
}

.slide-right-leave-to {
    transform: translateX(100%);
    opacity: 0.8;
}
</style>
