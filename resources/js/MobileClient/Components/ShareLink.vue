<template>
    <div class="share-card">
        <div class="share-card-body">

            <!-- Кнопка нативного шеринга -->
            <button
                v-if="canNativeShare"
                class="native-share-btn"
                @click="nativeShare"
            >
                <i class="fa-solid fa-share-nodes"></i>
                <span>Поделиться</span>
            </button>

            <!-- Кнопки соцсетей -->
            <div class="social-buttons">
                <button
                    class="social-btn telegram"
                    @click="shareTelegram"
                    title="Telegram"
                >
                    <i class="fa-brands fa-telegram"></i>
                </button>

                <button
                    class="social-btn vk"
                    @click="shareVk"
                    title="VK"
                >
                    <i class="fa-brands fa-vk"></i>
                </button>

                <button
                    class="social-btn whatsapp"
                    @click="shareWhatsapp"
                    title="WhatsApp"
                >
                    <i class="fa-brands fa-whatsapp"></i>
                </button>

                <button
                    class="social-btn email"
                    @click="shareEmail"
                    title="Почта"
                >
                    <i class="fa-solid fa-envelope"></i>
                </button>
            </div>

            <!-- Разделитель -->
            <div class="share-divider"></div>

            <!-- Поле ссылки -->
            <div class="link-input-group flex-column">
                <input
                    type="text"
                    class="link-input"
                    :value="url"
                    readonly
                >
                <button
                    class="copy-btn"
                    @click="copyLink"
                    :title="copied ? 'Скопировано!' : 'Копировать'"
                >
                    <i :class="copied ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i> Копировать
                </button>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "ShareLinks",

    props: {
        title: {
            type: String,
            default: document.title
        },
        text: {
            type: String,
            default: ""
        },
        url: {
            type: String,
            default: () => window.location.href
        }
    },

    data() {
        return {
            copied: false
        };
    },

    computed: {
        encodedUrl() {
            return encodeURIComponent(this.url);
        },

        encodedTitle() {
            return encodeURIComponent(this.title);
        },

        encodedText() {
            return encodeURIComponent(this.text);
        },

        canNativeShare() {
            return typeof navigator.share === "function";
        }
    },

    methods: {
        open(url) {
            window.open(url, "_blank", "width=700,height=600");
        },

        shareTelegram() {
            this.open(
                `https://t.me/share/url?url=${this.encodedUrl}&text=${this.encodedTitle}`
            );
        },

        shareVk() {
            this.open(
                `https://vk.com/share.php?url=${this.encodedUrl}`
            );
        },

        shareWhatsapp() {
            this.open(
                `https://api.whatsapp.com/send?text=${this.encodedTitle}%20${this.encodedUrl}`
            );
        },

        shareEmail() {
            window.location.href =
                `mailto:?subject=${this.encodedTitle}&body=${this.encodedUrl}`;
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.url);
                this.copied = true;

                // Показываем галочку 2 секунды
                setTimeout(() => {
                    this.copied = false;
                }, 2000);

            } catch (e) {
                console.error('Ошибка копирования:', e);
            }
        },

        async nativeShare() {
            try {
                await navigator.share({
                    title: this.title,
                    text: this.text,
                    url: this.url
                });
            } catch (e) {
                if (e.name !== 'AbortError') {
                    console.error('Ошибка шеринга:', e);
                }
            }
        }
    }
};
</script>

<style scoped>
/* Карточка в стиле футера */
.share-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    position: relative;
}

/* Декоративная полоска сверху */
.share-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
}

.share-card-body {
    padding: 24px;
}

/* Кнопка нативного шеринга */
.native-share-btn {
    width: 100%;
    padding: 14px 20px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.3);
    margin-bottom: 20px;
}

.native-share-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(var(--bs-primary-rgb), 0.4);
}

.native-share-btn:active {
    transform: translateY(0);
}

.native-share-btn i {
    font-size: 1.2rem;
}

/* Кнопки соцсетей */
.social-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin-bottom: 20px;
}

.social-btn {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #ffffff;
    position: relative;
    overflow: hidden;
}

/* Индивидуальные цвета для соцсетей */
.social-btn.telegram {
    background: linear-gradient(135deg, #0088cc 0%, #0077b5 100%);
    box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
}

.social-btn.vk {
    background: linear-gradient(135deg, #4a76a8 0%, #3d6290 100%);
    box-shadow: 0 4px 12px rgba(74, 118, 168, 0.3);
}

.social-btn.whatsapp {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
}

.social-btn.email {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.social-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.social-btn:active {
    transform: translateY(0) scale(1);
}

/* Разделитель */
.share-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.15) 50%, transparent 100%);
    margin: 20px 0;
}

/* Группа ввода ссылки */
.link-input-group {
    display: flex;
    gap: 0;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.link-input-group:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
}

.link-input {
    flex: 1;
    background: transparent;
    border: none;
    color: #e0e0e0;
    padding: 12px 16px;
    font-size: 0.9rem;
    outline: none;
}

.link-input::selection {
    background: var(--bs-primary);
    color: #ffffff;
}

.copy-btn {
    background: rgba(255, 255, 255, 0.05);
    border: none;
    border-left: 1px solid rgba(255, 255, 255, 0.1);
    color: #e0e0e0;
    padding: 12px 18px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.copy-btn:hover {
    background: var(--bs-primary);
    color: #ffffff;
}

.copy-btn i {
    font-size: 1.1rem;
}

/* Анимация для галочки */
.copy-btn i.fa-check {
    animation: checkPop 0.3s ease;
}

@keyframes checkPop {
    0% {
        transform: scale(0);
    }
    70% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

/* Адаптив для светлой темы */
:root[data-bs-theme="light"] .share-card {
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.02) 0%, rgba(0, 0, 0, 0.01) 100%);
    border-color: rgba(0, 0, 0, 0.1);
}



:root[data-bs-theme="light"] .link-input {
    color: #eaeaea;
}

:root[data-bs-theme="light"] .copy-btn {
    color: #eaeaea;
}

:root[data-bs-theme="light"] .share-divider {
    background: linear-gradient(90deg, transparent 0%, rgba(0, 0, 0, 0.1) 50%, transparent 100%);
}
</style>
