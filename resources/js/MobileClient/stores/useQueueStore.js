import { defineStore } from 'pinia';
import axios from 'axios';

export const useQueueStore = defineStore('queue', {
    state: () => ({
        // Комбинированный статус: браузерный + аксиос
        isOffline: !navigator.onLine,
        isProcessing: false,
        tasks: JSON.parse(localStorage.getItem('offline_queue') || '[]'),
    }),

    getters: {
        tasksCount: (state) => state.tasks.length,
        hasTasks: (state) => state.tasks.length > 0,
    },

    actions: {
        setOffline(status) {
            this.isOffline = status;
        },

        saveToStorage() {
            try {
                // FormData нельзя сериализовать в JSON, поэтому фильтруем или обрабатываем
                const serializableTasks = this.tasks.map(task => {
                    const safeConfig = { ...task.config };
                    if (safeConfig.data instanceof FormData) {
                        // Для FormData сохраняем только текстовые поля, файлы теряются при перезагрузке
                        // В реальном приложении файлы лучше загружать в S3/хранилище ДО отправки сообщения
                        const formDataObj = {};
                        for (let [key, value] of safeConfig.data.entries()) {
                            if (typeof value === 'string') formDataObj[key] = value;
                        }
                        safeConfig.data = formDataObj;
                        safeConfig._wasFormData = true;
                    }
                    return { ...task, config: safeConfig };
                });
                localStorage.setItem('offline_queue', JSON.stringify(serializableTasks));
            } catch (e) {
                console.error('Ошибка сохранения очереди:', e);
            }
        },

        addTask(config, meta = {}) {
            const task = {
                id: `${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                timestamp: new Date().toISOString(),
                type: meta.type || 'request',
                title: meta.title || 'Сетевой запрос',
                config: {
                    url: config.url,
                    method: config.method,
                    data: config.data,
                    headers: config.headers,
                    params: config.params,
                }
            };

            this.tasks.push(task);
            this.saveToStorage();
            this.setOffline(true); // Принудительно ставим офлайн
        },

        removeTask(taskId) {
            this.tasks = this.tasks.filter(t => t.id !== taskId);
            this.saveToStorage();
        },

        clearQueue() {
            this.tasks = [];
            this.saveToStorage();
        },

        async processQueue() {
            if (this.isProcessing || this.tasks.length === 0) return;
            this.isProcessing = true;

            // Делаем копию, чтобы не мутировать массив во время итерации
            const tasksToProcess = [...this.tasks];

            for (const task of tasksToProcess) {
                try {
                    // Восстанавливаем FormData, если нужно
                    if (task.config._wasFormData) {
                        const formData = new FormData();
                        Object.entries(task.config.data).forEach(([k, v]) => formData.append(k, v));
                        task.config.data = formData;
                    }

                    await axios.request(task.config);
                    // Если успешно - удаляем из очереди
                    this.removeTask(task.id);
                } catch (error) {
                    // Если снова ошибка сети - прерываем цикл, ждем следующего онлайна
                    if (!error.response || error.code === 'ERR_NETWORK') {
                        this.setOffline(true);
                        break;
                    }
                    // Если сервер вернул 4xx/5xx (например, токен истек), удаляем задачу, чтобы не спамить
                    this.removeTask(task.id);
                }
            }
            this.isProcessing = false;
        }
    }
});
