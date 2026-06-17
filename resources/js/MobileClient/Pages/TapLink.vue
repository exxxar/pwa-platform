<template>
    <div class="taplink-page min-vh-100 d-flex flex-column align-items-center py-5 px-3"
         :style="{ background: gradientBackground }">

        <!-- Состояние загрузки -->
        <div v-if="isLoading" class="text-center mt-5">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>

        <!-- Контент -->
        <div v-else class="w-100" style="max-width: 480px;">

            <!-- Профиль -->
            <div class="text-center mb-4 fade-in">
                <div class="profile-avatar mb-3 mx-auto shadow-lg">
                    <img v-if="profile.avatar" :src="profile.avatar" alt="Avatar" class="rounded-circle w-100 h-100 object-fit-cover">
                    <i v-else class="fa-solid fa-store fa-3x text-white"></i>
                </div>
                <h2 class="fw-bold text-white mb-1">{{ profile.name }}</h2>
                <p class="text-white-50 mb-3">{{ profile.description }}</p>

                <!-- Кнопка "Поделиться" (работает на мобильных) -->
                <button v-if="canShare" @click="shareProfile" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="fa-solid fa-share-nodes me-1"></i> Поделиться
                </button>
            </div>

            <!-- Список ссылок -->
            <div class="links-list d-flex flex-column gap-3">
                <a
                    v-for="(link, index) in links"
                    :key="link.id"
                    :href="link.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="link-card text-decoration-none fade-in-up"
                    :style="{ animationDelay: `${index * 0.1}s` }"
                >
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <!-- Иконка ссылки -->
                            <div class="icon-box" :style="{ backgroundColor: link.icon_bg || 'rgba(255,255,255,0.1)' }">
                                <i :class="link.icon || 'fa-solid fa-link'" class="text-white"></i>
                            </div>
                            <span class="fw-semibold text-dark">{{ link.title }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </div>
                </a>
            </div>

            <!-- Футер -->
            <div class="text-center mt-5 text-white-50 small">
                <p class="mb-0">© {{ new Date().getFullYear() }} {{ profile.name }}</p>
                <p class="mb-0" style="font-size: 0.7rem;">Работает на PWA Store</p>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const isLoading = ref(true);
const profile = ref({ name: '', description: '', avatar: null, theme_color: '#ff8a00' });
const links = ref([]);
const canShare = ref(!!navigator.share);

// Градиентный фон на основе цвета темы
const gradientBackground = computed(() => {
    const color = profile.value.theme_color || '#ff8a00';
    return `linear-gradient(135deg, ${color} 0%, ${adjustColor(color, -40)} 100%)`;
});

// Загрузка данных
onMounted(async () => {
    try {
        // Берем slug из роута (например, /taplink/:slug)
        const slug = route.params.slug || window.Tenant?.slug;

        const response = await fetch(`/api/tenant/${slug}/taplink`);
        if (!response.ok) throw new Error('Ошибка загрузки');

        const data = await response.json();
        profile.value = data.tenant;
        links.value = data.links;
    } catch (error) {
        console.error('TapLink error:', error);
    } finally {
        isLoading.value = false;
    }
});

// Функция шеринга (нативная для мобильных)
const shareProfile = async () => {
    try {
        await navigator.share({
            title: profile.value.name,
            text: profile.value.description,
            url: window.location.href,
        });
    } catch (err) {
        console.log('Share canceled or failed', err);
    }
};

// Вспомогательная функция для затемнения цвета в градиенте
function adjustColor(color, amount) {
    return '#' + color.replace(/^#/, '').replace(/../g, color => ('0'+Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2));
}
</script>

<style scoped>
.taplink-page {
    min-height: 100vh;
}

.profile-avatar {
    width: 96px;
    height: 96px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
}

.link-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 16px 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.link-card:active {
    transform: scale(0.97);
    background: #ffffff;
}

.link-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.icon-box {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

/* Анимации появления */
.fade-in {
    animation: fadeIn 0.6s ease-out;
}

.fade-in-up {
    opacity: 0;
    animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
