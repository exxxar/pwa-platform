import { defineAsyncComponent } from 'vue';

const Profile = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Profile.vue'));
const Orders = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Orders.vue'));
const Cashback = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/CashBack.vue'));
const CashbackShop = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/CashbackShop.vue'));
const ChatRoom = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Chat.vue'));
const ChatList = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/DialogList.vue'));
const PromoCode = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/PromoCode.vue'));
const Achievements = defineAsyncComponent(() => import('@/MobileClient/Pages/AchievementsPage.vue'));
const ShopCart = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/ShopCart.vue'));
const GroceryOrder = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/GroceryOrder.vue'));
const CalculatorPage = defineAsyncComponent(() => import('@/MobileClient/Pages/CalculatorPage.vue'));
const CollectionPage = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/CollectionPage.vue'));

// Калькуляторы еды (можно вынести в отдельный файл, если нужно)
const FoodCalculators = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/FoodCalculators.vue'));
const BurgerCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/BurgerCalculator.vue'));
const PizzaCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/PizzaCalculator.vue'));
const CoffeeCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/CoffeeCalculator.vue'));
const WafflesCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/WafflesCalculator.vue'));
const SushiCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/SushiCalculator.vue'));
const PancakesCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/PancakesCalculator.vue'));

export default [
    { path: '/profile', name: 'Profile', component: Profile, meta: { auth: true } },
    { path: '/orders', name: 'Orders', component: Orders, meta: { auth: true } },
    { path: '/cashback', name: 'Cashback', component: Cashback, meta: { auth: true } },
    { path: '/cashback-shop', name: 'CashbackShop', component: CashbackShop, meta: { auth: true, hideBottomMenu: true } },
    { path: '/promo', name: 'PromoCode', component: PromoCode, meta: { auth: true } },
    { path: '/achievements', name: 'Achievements', component: Achievements, meta: { auth: true, title: 'Мои достижения' } },
    { path: '/chat', name: 'ChatList', component: ChatList, meta: { auth: true } },
    { path: '/chat/:id', name: 'ChatRoom', component: ChatRoom, meta: { auth: true, hideBottomMenu: true, hideFooter: true } },
    { path: '/cart', name: 'Cart', component: ShopCart, meta: { hideBottomMenu: true } },
    { path: '/grocery-order', name: 'GroceryOrder', component: GroceryOrder, meta: { auth: true, hideBottomMenu: true, title: 'Заказ продуктов' } },
    { path: '/calculator', name: 'Calculator', component: CalculatorPage, meta: { auth: true, hideBottomMenu: true, title: 'Калькулятор стоимости' } },
    { path: '/collection/:id/:partnerId?', name: 'CollectionPage', component: CollectionPage, meta: { auth: true } },

    // Калькуляторы еды
    { path: '/food-calculators', name: 'FoodCalculators', component: FoodCalculators, meta: { auth: true, title: 'Калькуляторы еды', hideBottomMenu: true } },
    { path: '/food-calculators/burger', name: 'BurgerCalculator', component: BurgerCalculator, meta: { auth: true, title: 'Собери свой бургер', hideBottomMenu: true } },
    { path: '/food-calculators/pizza', name: 'PizzaCalculator', component: PizzaCalculator, meta: { auth: true, title: 'Калькулятор пиццы', hideBottomMenu: true } },
    { path: '/food-calculators/coffee', name: 'CoffeeCalculator', component: CoffeeCalculator, meta: { auth: true, title: 'Калькулятор кофе', hideBottomMenu: true } },
    { path: '/food-calculators/waffles', name: 'WafflesCalculator', component: WafflesCalculator, meta: { auth: true, title: 'Гонконгские вафли', hideBottomMenu: true } },
    { path: '/food-calculators/sushi', name: 'SushiCalculator', component: SushiCalculator, meta: { auth: true, title: 'Суши и роллы', hideBottomMenu: true } },
    { path: '/food-calculators/pancakes', name: 'PancakesCalculator', component: PancakesCalculator, meta: { auth: true, title: 'Блинчики', hideBottomMenu: true } },
];
