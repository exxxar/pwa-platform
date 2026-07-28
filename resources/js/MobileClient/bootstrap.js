import axios from 'axios';

window.axios = axios;
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content;

window.TenantAuth = {
    user: window.TenantUser || null,
    check() { return this.user !== null; },
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
        return this.user.roles.some(role => role.permissions?.some(p => p.name === permission));
    },
    hasAnyPermission(permissions) {
        if (!this.user || !this.user.roles) return false;
        return this.user.roles.some(role => role.permissions?.some(p => permissions.includes(p.name)));
    }
};
