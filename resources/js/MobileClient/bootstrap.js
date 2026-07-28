/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true; // Если используете защиту от CSRF

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });


let deferredPrompt = null

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt = e

    // Показываем кнопку "Установить"
    const modal = new bootstrap.Modal(document.getElementById('installPwaModal'))
    if (modal)
        modal.show()
})

window.installPWA = ()=> {
    if (!deferredPrompt) return

    deferredPrompt.prompt()

    deferredPrompt.userChoice.then(() => {
        deferredPrompt = null

        // Закрываем модалку после установки
        const modal = bootstrap.Modal.getInstance(document.getElementById('installPwaModal'))
        if (modal)
            modal.hide()
    })
}


window.TenantAuth = {
    user: window.TenantUser || null,

    check() {
        return this.user !== null;
    },

    hasRole(role) {
        if (!this.user || !this.user.roles) return false;
        return this.user.roles.some(r => r.name === role);
    },

    hasAnyRole(roles) {
        if (!this.user || !this.user.roles) return false;
        return this.user.roles.some(r => roles.includes(r.name));
    },

    hasPermission(permission) {
        if (!this.user || !this.user.roles) return false;

        return this.user.roles.some(role =>
            role.permissions?.some(p => p.name === permission)
        );
    },

    hasAnyPermission(permissions) {
        if (!this.user || !this.user.roles) return false;

        return this.user.roles.some(role =>
            role.permissions?.some(p => permissions.includes(p.name))
        );
    }
};

