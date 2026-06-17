<template>
    <section class="pwa-banner">
        <div class="container">
            <div class="banner-content">
                <div class="banner-text">
                    <div class="app-badge">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                        <span>PWA Приложение</span>
                    </div>
                    <h2>Заказывайте ещё удобнее в нашем приложении</h2>
                    <p>Установите приложение на главный экран телефона. Это быстро, не занимает место в памяти и работает даже без интернета.</p>
                    <ul class="app-features">
                        <li><i class="fa-solid fa-check"></i> Push-уведомления о статусе заказа</li>
                        <li><i class="fa-solid fa-check"></i> Эксклюзивные скидки для пользователей приложения</li>
                        <li><i class="fa-solid fa-check"></i> Мгновенный доступ без ввода пароля</li>
                    </ul>
                    <button class="install-btn" @click="installApp">
                        <i class="fa-solid fa-download"></i>
                        Установить приложение
                    </button>
                </div>
                <div class="banner-visual">
                    <div class="phone-mockup">
                        <div class="phone-screen">
                            <div class="screen-header">
                                <i class="fa-solid fa-store"></i>
                                <span>Наш Магазин</span>
                            </div>
                            <div class="screen-content">
                                <div class="mock-item"></div>
                                <div class="mock-item"></div>
                                <div class="mock-item"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopPwaBanner",
    data() {
        return {
            deferredPrompt: null
        };
    },
    mounted() {
        // Логика перехвата события установки PWA (для реального использования)
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
        });
    },
    methods: {
        async installApp() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    this.$notify?.({ title: 'Успех', text: 'Приложение добавлено на главный экран!', type: 'success' });
                }
                this.deferredPrompt = null;
            } else {
                this.$notify?.({
                    title: 'Как установить',
                    text: 'Нажмите "Поделиться" (iOS) или "Меню" (Android) и выберите "На экран «Домой»"',
                    type: 'info'
                });
            }
        }
    }
};
</script>

<style lang="scss" scoped>
.pwa-banner { padding: 80px 0; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); }
.banner-content { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
@media (max-width: 992px) { .banner-content { grid-template-columns: 1fr; text-align: center; } .banner-visual { display: none; } }

.app-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 122, 0, 0.1); color: var(--primary); padding: 8px 16px; border-radius: 50px; font-weight: 700; margin-bottom: 1.5rem; }
.banner-text h2 { font-size: 2.5rem; font-weight: 900; margin-bottom: 1rem; color: var(--dark); }
.banner-text p { font-size: 1.1rem; color: var(--gray); margin-bottom: 2rem; line-height: 1.6; }

.app-features { list-style: none; padding: 0; margin: 0 0 2rem 0; display: flex; flex-direction: column; gap: 12px; text-align: left; }
@media (max-width: 992px) { .app-features { align-items: center; } }
.app-features li { display: flex; align-items: center; gap: 10px; font-weight: 500; }
.app-features i { color: var(--primary); }

.install-btn { background: var(--dark); color: white; border: none; padding: 16px 32px; border-radius: 50px; font-weight: 700; font-size: 1.1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; }
.install-btn:hover { background: var(--primary); transform: translateY(-3px); box-shadow: 0 10px 30px rgba(255, 122, 0, 0.3); }

/* Мокап телефона на CSS */
.phone-mockup { width: 280px; height: 550px; background: var(--dark); border-radius: 40px; padding: 12px; box-shadow: 0 30px 60px rgba(0,0,0,0.2); margin: 0 auto; position: relative; animation: float 6s ease-in-out infinite; }
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
.phone-screen { width: 100%; height: 100%; background: white; border-radius: 30px; overflow: hidden; display: flex; flex-direction: column; }
.screen-header { padding: 16px; background: var(--primary); color: white; display: flex; align-items: center; gap: 8px; font-weight: 700; }
.screen-content { padding: 16px; display: flex; flex-direction: column; gap: 12px; }
.mock-item { height: 60px; background: var(--light); border-radius: 12px; }
</style>
