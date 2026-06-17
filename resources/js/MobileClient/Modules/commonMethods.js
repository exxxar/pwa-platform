import {useBasketStore} from "@/MobileClient/stores/Shop/basket.js";

let sharedState = {
    spent_time_counter: 0,
    timerId: null
}

// 💰 максимальный кешбек
export function cashbackLimit() {
    const store = useBasketStore()

    const self = window.TenantUser
    const summaryPrice = store.cartTotalPrice || 0
    const tenant = window?.Tenant || {}

    const maxUserCashback = self?.cashback_balance|| 0
    const percent = tenant?.settings?.max_cashback_use_percent || 0

    const calculated = summaryPrice * (percent / 100)

    return Math.round(Math.min(calculated, maxUserCashback))
}

// 🛒 можно ли покупать
export function canBuy() {
    const tenant = window?.Tenant || {}
    const settings = tenant?.settings || {}

    if (!window?.isCorrectSchedule)
        return true

    if (!window.isCorrectSchedule(settings?.schedule))
        return true

    return !!settings?.can_buy_after_closing
}

// ⏱ текущее значение таймера
export function getSpentTimeCounter() {
    return Number(sharedState.spent_time_counter) || 0
}

// 🔁 проверка таймера из localStorage
export function checkTimer() {
    const counter = Number(localStorage.getItem("mypwa_self_product_delivery_counter") || 0)

    if (counter > 0) {
        startTimer(counter)
        return true
    }

    return false
}

// ▶️ запуск таймера
export function startTimer(time = 10) {
    const parsed = Number(time)

    sharedState.spent_time_counter = !isNaN(parsed)
        ? Math.min(parsed, 10)
        : 10

    // ❗ убиваем старый таймер если есть
    if (sharedState.timerId) {
        clearInterval(sharedState.timerId)
    }

    sharedState.timerId = setInterval(() => {

        if (sharedState.spent_time_counter > 0) {
            sharedState.spent_time_counter--
        } else {
            clearInterval(sharedState.timerId)
            sharedState.timerId = null
            sharedState.spent_time_counter = 0
        }

        localStorage.setItem(
            "mypwa_self_product_delivery_counter",
            sharedState.spent_time_counter
        )

        window.dispatchEvent(
            new CustomEvent('trigger-spent-timer', {
                detail: sharedState.spent_time_counter
            })
        )

    }, 1000)
}
