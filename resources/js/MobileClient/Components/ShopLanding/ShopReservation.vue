<template>
    <section class="shop-reservation">
        <div class="container">
            <div class="reservation-wrapper">
                <div class="reservation-info">
                    <h2>Забронируйте столик</h2>
                    <p>Уютная атмосфера, быстрая подача и лучшие блюда. Оставьте заявку, и мы подтвердим бронь в течение 15 минут.</p>
                    <ul class="info-list">
                        <li><i class="fa-solid fa-clock"></i> Ежедневно с 10:00 до 23:00</li>
                        <li><i class="fa-solid fa-location-dot"></i> {{ address }}</li>
                        <li><i class="fa-solid fa-phone"></i> {{ phone }}</li>
                    </ul>
                </div>

                <form class="reservation-form" @submit.prevent="submitReservation">
                    <h3>Данные бронирования</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Имя</label>
                            <input type="text" v-model="form.name" required placeholder="Ваше имя">
                        </div>
                        <div class="form-group">
                            <label>Телефон</label>
                            <input type="tel" v-model="form.phone" required placeholder="+7 (___) ___-__-__">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Дата</label>
                            <input type="date" v-model="form.date" required>
                        </div>
                        <div class="form-group">
                            <label>Время</label>
                            <input type="time" v-model="form.time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Количество гостей</label>
                        <select v-model="form.guests">
                            <option v-for="n in 10" :key="n" :value="n">{{ n }} {{ pluralize(n, 'гость', 'гостя', 'гостей') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Пожелания</label>
                        <textarea v-model="form.comment" rows="2" placeholder="Например: столик у окна, детский стульчик"></textarea>
                    </div>
                    <button type="submit" class="submit-btn" :disabled="isSubmitting">
                        <span v-if="isSubmitting" class="spinner"></span>
                        <span v-else>Забронировать столик</span>
                    </button>
                </form>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopReservation",
    props: {
        address: { type: String, default: 'г. Москва, ул. Примерная, 1' },
        phone: { type: String, default: '+7 (999) 123-45-67' }
    },
    data() {
        return {
            isSubmitting: false,
            form: {
                name: '',
                phone: '',
                date: new Date().toISOString().split('T')[0],
                time: '19:00',
                guests: 2,
                comment: ''
            }
        };
    },
    methods: {
        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
        async submitReservation() {
            this.isSubmitting = true;
            // Имитация отправки на сервер
            await new Promise(resolve => setTimeout(resolve, 1000));

            this.$notify?.({
                title: 'Заявка принята!',
                text: 'Мы перезвоним вам для подтверждения брони.',
                type: 'success'
            });

            this.form = { name: '', phone: '', date: new Date().toISOString().split('T')[0], time: '19:00', guests: 2, comment: '' };
            this.isSubmitting = false;
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-reservation { padding: 80px 0; background: var(--dark); color: white; }
.reservation-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
@media (max-width: 992px) { .reservation-wrapper { grid-template-columns: 1fr; gap: 40px; } }

.reservation-info h2 { font-size: 2.5rem; font-weight: 900; margin-bottom: 1rem; }
.reservation-info p { color: rgba(255,255,255,0.7); font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem; }
.info-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 16px; }
.info-list li { display: flex; align-items: center; gap: 12px; font-size: 1.1rem; }
.info-list i { color: var(--primary); width: 24px; text-align: center; }

.reservation-form { background: white; padding: 2rem; border-radius: 24px; color: var(--dark); }
.reservation-form h3 { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 12px; border: 1px solid rgba(0,0,0,0.1); border-radius: 12px;
    font-size: 1rem; background: var(--light);
    &:focus { outline: none; border-color: var(--primary); }
}

.submit-btn {
    width: 100%; background: var(--primary); color: white; border: none; padding: 14px;
    border-radius: 12px; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: 0.3s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    &:hover:not(:disabled) { background: var(--primary-dark); transform: translateY(-2px); }
    &:disabled { opacity: 0.7; cursor: not-allowed; }
}

.spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
