import axios from 'axios'


export async function apiRequest(url, method, payload = {}, options = {}) {

    const data = {
        ...payload,
    }

    // 🔐 параметры (можешь подставить свои)
    const param1 = window.Tenant.id
    const param2 = window.TenantUser.uuid
    // 👉 кодируем в base64
    const encoded = btoa(`${param1}:${param2}`)

    return axios({
        method,
        url,
        data,
        headers: {
            ...(options.headers || {}),
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Integration-Auth': encoded
        },
        ...options
    })
}
