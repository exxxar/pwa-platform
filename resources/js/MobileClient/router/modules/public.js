import { defineAsyncComponent } from 'vue';

const Menu = defineAsyncComponent(() => import('../../Pages/Shop/Menu.vue'));
const Catalog = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Catalog.vue'));
const ShopMenuPage = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/ShopMenu.vue'));
const Contacts = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Contacts.vue'));
const FeedBack = defineAsyncComponent(() => import('@/MobileClient/Pages/FeedBack.vue'));
const AboutDeveloper = defineAsyncComponent(() => import('@/MobileClient/Pages/AboutDeveloper.vue'));
const TapLink = defineAsyncComponent(() => import('@/MobileClient/Pages/TapLink.vue'));
const ReferralsPage = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/ReferralsPage.vue'));
const TableBooking = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Bookings.vue'));
const WheelClassic = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/WheelGame.vue'));
const Coffee = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Coffee.vue'));
const PartnersPage = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/PartnersPage.vue'));

export default [
    { path: '/', redirect: { name: 'Catalog' } },
    { path: '/menu', name: 'Menu', component: Menu, meta: { public: true } },
    { path: '/catalog', name: 'Catalog', component: Catalog, meta: { public: true } },
    { path: '/shop-menu', name: 'ShopMenu', component: ShopMenuPage, meta: { public: true } },
    { path: '/contacts', name: 'Contacts', component: Contacts, meta: { public: true } },
    { path: '/feedback', name: 'FeedBack', component: FeedBack, meta: { public: true } },
    { path: '/booking', name: 'TableBooking', component: TableBooking, meta: { public: true } },
    { path: '/wheel-classic', name: 'WheelClassic', component: WheelClassic, meta: { public: true } },
    { path: '/coffee', name: 'Coffee', component: Coffee, meta: { public: true } },
    { path: '/referral', name: 'ReferralsPage', component: ReferralsPage, meta: { public: true } },
    { path: '/about', name: 'AboutDeveloper', component: AboutDeveloper, meta: { public: true, hideBottomMenu: true } },
    { path: '/taplink/:slug?', name: 'TapLink', component: TapLink, meta: { public: true } },
    { path: '/partners', name: 'Partners', component: PartnersPage, meta: { requiresAuth: true } },
];
