<template>
    <div class="message-preview">
        <div class="phone-frame">
            <div class="phone-header">
                <div class="status-bar">
                    <span class="time">{{ currentTime }}</span>
                    <div class="status-icons">
                        <i class="fa-solid fa-signal"></i>
                        <i class="fa-solid fa-wifi"></i>
                        <i class="fa-solid fa-battery-full"></i>
                    </div>
                </div>
                <div class="chat-header">
                    <div class="chat-back">
                        <i class="fa-solid fa-arrow-left"></i>
                    </div>
                    <div class="chat-title">Рассылка</div>
                </div>
            </div>

            <div class="phone-body">
                <div class="chat-messages">
                    <div class="message-bubble">

                        <!-- Медиа -->
                        <div v-if="hasMedia" class="message-media">
                            <!-- Изображения -->
                            <template v-if="imageFiles.length > 0">
                                <div v-if="imageFiles.length === 1" class="single-image">
                                    <img :src="getImagePreview(imageFiles[0])" alt="">
                                </div>
                                <div v-else class="image-grid" :class="`grid-${Math.min(imageFiles.length, 4)}`">
                                    <div
                                        v-for="(img, index) in imageFiles.slice(0, 4)"
                                        :key="index"
                                        class="grid-image"
                                    >
                                        <img :src="getImagePreview(img)" alt="">
                                        <div
                                            v-if="index === 3 && imageFiles.length > 4"
                                            class="more-overlay"
                                        >
                                            +{{ imageFiles.length - 4 }}
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Видео -->
                            <div v-if="videoFile" class="video-preview">
                                <i class="fa-solid fa-play"></i>
                                <span>{{ videoFile.name }}</span>
                            </div>

                            <!-- Аудио -->
                            <div v-if="audioFiles.length > 0" class="audio-preview">
                                <div
                                    v-for="(audio, index) in audioFiles"
                                    :key="index"
                                    class="audio-item"
                                >
                                    <div class="audio-icon">
                                        <i class="fa-solid fa-music"></i>
                                    </div>
                                    <div class="audio-info">
                                        <div class="audio-name">{{ audio.name }}</div>
                                        <div class="audio-wave">
                                            <span v-for="i in 30" :key="i"
                                                  :style="{ height: Math.random() * 20 + 5 + 'px' }"></span>
                                        </div>
                                    </div>
                                    <div class="audio-play">
                                        <i class="fa-solid fa-play"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Текст -->
                        <div v-if="message" class="message-text">
                            {{ message }}
                        </div>

                        <!-- Время -->
                        <div class="message-time">
                            {{ currentTime }}
                            <i class="fa-solid fa-check-double"></i>
                        </div>
                    </div>

                    <!-- Inline-кнопки -->
                    <div v-if="buttons && buttons.length > 0" class="inline-keyboard">
                        <div
                            v-for="(row, rowIndex) in buttons"
                            :key="rowIndex"
                            class="keyboard-row"
                        >
                            <button
                                v-for="(btn, btnIndex) in row"
                                :key="btnIndex"
                                class="keyboard-btn"
                                :class="{ 'is-url': btn.type === 'url' }"
                            >
                                <i v-if="btn.type === 'url'" class="fa-solid fa-arrow-up-right-from-square"></i>
                                {{ btn.text }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-if="isEmpty" class="empty-preview">
            <i class="fa-solid fa-message"></i>
            <p>Заполните форму, чтобы увидеть предпросмотр</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MessagePreview',

    props: {
        message: {
            type: String,
            default: '',
        },
        media: {
            type: Array,
            default: () => [],
        },
        buttons: {
            type: Array,
            default: () => [],
        },
    },

    computed: {
        currentTime() {
            const now = new Date();
            return now.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        imageFiles() {
            return (this.media || []).filter(m =>
                m.type === 'image' || m.type?.startsWith('image/')
            );
        },

        videoFile() {
            return (this.media || []).find(m =>
                m.type === 'video' || m.type?.startsWith('video/')
            );
        },

        audioFiles() {
            return (this.media || []).filter(m =>
                m.type === 'audio' || m.type?.startsWith('audio/')
            );
        },

        hasMedia() {
            return this.imageFiles.length > 0 || this.videoFile || this.audioFiles.length > 0;
        },

        isEmpty() {
            return !this.message && !this.hasMedia && (!this.buttons || this.buttons.length === 0);
        },
    },

    methods: {
        getImagePreview(file) {
            if (!file) return '';
            if (file.preview) return file.preview;
            if (file.url) return file.url;
            if (typeof file === 'string') return file;
            // Создаём превью из File объекта
            return URL.createObjectURL(file);
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.message-preview {
    display: flex;
    justify-content: center;
    padding: 20px;
    background: $bg-secondary;
    border-radius: 14px;
    min-height: 400px;
}

// ==========================================
// РАМКА ТЕЛЕФОНА
// ==========================================
.phone-frame {
    width: 100%;
    max-width: 340px;
    background: #111b21;
    border-radius: 32px;
    overflow: hidden;
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.3),
        inset 0 0 0 2px #2a3942;
    display: flex;
    flex-direction: column;
    max-height: 600px;
}

.phone-header {
    background: #1f2c33;
    border-bottom: 1px solid #2a3942;
}

.status-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 16px;
    font-size: 0.75rem;
    color: #aebac1;
    font-weight: 600;

    .status-icons {
        display: flex;
        gap: 6px;
        font-size: 0.7rem;
    }
}

.chat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
}

.chat-back {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #00a884;
    font-size: 0.9rem;
    cursor: default;
}

.chat-title {
    color: #e9edef;
    font-weight: 600;
    font-size: 0.95rem;
}

.phone-body {
    flex: 1;
    background: #0b141a;
    background-image:
        url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23182229' fill-opacity='0.4'%3E%3Ccircle cx='3' cy='3' r='1'/%3E%3C/g%3E%3C/svg%3E");
    padding: 16px;
    overflow-y: auto;
}

.chat-messages {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

// ==========================================
// ПУЗЫРЬ СООБЩЕНИЯ
// ==========================================
.message-bubble {
    align-self: flex-start;
    max-width: 85%;
    background: #005c4b;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.15);

    // Хвостик
    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: -8px;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 0 8px 8px 0;
        border-color: transparent #005c4b transparent transparent;
    }
}

.message-media {
    display: flex;
    flex-direction: column;
    gap: 2px;

    .single-image {
        img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            display: block;
        }
    }

    .image-grid {
        display: grid;
        gap: 2px;

        &.grid-2 {
            grid-template-columns: 1fr 1fr;
        }

        &.grid-3 {
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;

            .grid-image:first-child {
                grid-row: 1 / 3;
            }
        }

        &.grid-4 {
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
        }
    }

    .grid-image {
        position: relative;
        overflow: hidden;
        aspect-ratio: 1;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .more-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 700;
        }
    }

    .video-preview {
        padding: 20px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;

        i {
            font-size: 1.6rem;
        }

        span {
            flex: 1;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }

    .audio-preview {
        padding: 8px;
        background: rgba(0, 0, 0, 0.2);
    }

    .audio-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 6px;
        margin-bottom: 4px;

        &:last-child {
            margin-bottom: 0;
        }

        .audio-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #00a884;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .audio-info {
            flex: 1;
            min-width: 0;

            .audio-name {
                font-size: 0.75rem;
                color: #e9edef;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-bottom: 4px;
            }

            .audio-wave {
                display: flex;
                align-items: center;
                gap: 1px;
                height: 20px;

                span {
                    flex: 1;
                    background: #00a884;
                    border-radius: 1px;
                    min-width: 2px;
                }
            }
        }

        .audio-play {
            color: #00a884;
            font-size: 0.85rem;
        }
    }
}

.message-text {
    padding: 8px 12px 4px;
    color: #e9edef;
    font-size: 0.9rem;
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
}

.message-time {
    padding: 0 12px 6px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    font-size: 0.7rem;
    color: rgba(233, 237, 239, 0.6);

    i {
        color: #53bdeb;
        font-size: 0.65rem;
    }
}

// ==========================================
// INLINE-КЛАВИАТУРА
// ==========================================
.inline-keyboard {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 6px;
}

.keyboard-row {
    display: flex;
    gap: 4px;
}

.keyboard-btn {
    flex: 1;
    padding: 8px 10px;
    background: rgba(0, 168, 132, 0.15);
    border: 1px solid rgba(0, 168, 132, 0.3);
    border-radius: 6px;
    color: #00a884;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: default;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background 0.2s;

    &.is-url {
        color: #53bdeb;
        background: rgba(83, 189, 235, 0.1);
        border-color: rgba(83, 189, 235, 0.3);
    }

    i {
        font-size: 0.7rem;
    }
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-preview {
    text-align: center;
    padding: 40px 20px;
    color: $text-muted;

    i {
        font-size: 3rem;
        opacity: 0.2;
        margin-bottom: 12px;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .phone-frame {
        max-width: 100%;
        border-radius: 20px;
    }

    .message-preview {
        padding: 12px;
    }
}
</style>
