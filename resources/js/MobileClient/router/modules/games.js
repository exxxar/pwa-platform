import { defineAsyncComponent } from 'vue';

// ==========================================
// 🎮 ИГРОВЫЕ КОМПОНЕНТЫ (для пользователей)
// ==========================================
const GamesCatalog = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/GamesCatalog.vue'));
const PrizeCardGame = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/PrizeCardGame.vue'));
const SlotMachineGame = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/SlotMachineGame.vue'));
const DailyBonusGame = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/DailyBonusGame.vue'));
const QuizGame = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/QuizGame.vue'));
const ScratchCardGame = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/ScratchCardGame.vue'));
const WheelOfFortune = defineAsyncComponent(() => import('@/MobileClient/Components/Games/WheelOfFortuneClassic.vue'));
const CashbackCardGame = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/CashbackCardGame.vue'));
const TreasureHuntGame = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/TreasureHuntGame.vue'));
const GuessNumberGame = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Games/GuessNumberGame.vue'));

// ==========================================
// 🛠️ ИГРОВЫЕ АДМИНКИ
// ==========================================
const WheelAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Games/WheelAdmin.vue'));
const DailyBonusAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Games/DailyBonusAdmin.vue'));
const CardGameAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Games/CardGameAdmin.vue'));
const ScratchCardAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Games/ScratchCardAdmin.vue'));
const SlotMachineAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Games/SlotMachineAdmin.vue'));
const QuizAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Games/QuizGameAdmin.vue'));
const GuessNumberAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Games/GuessAdmin.vue'));
const TreasureHuntAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Games/TreasureHuntAdmin.vue'));

export default [
    // ==========================================
    // 🎮 ИГРЫ (для пользователей)
    // ==========================================
    { path: '/games', name: 'GamesCatalog', component: GamesCatalog, meta: { auth: true } },
    { path: '/games/card-prizes', name: 'PrizeCardGame', component: PrizeCardGame, meta: { auth: true } },
    { path: '/games/slot-machine', name: 'SlotMachineGame', component: SlotMachineGame, meta: { auth: true, title: 'Слот-машина' } },
    { path: '/games/daily-bonus', name: 'DailyBonusGame', component: DailyBonusGame, meta: { auth: true, title: 'Ежедневный бонус' } },
    { path: '/games/quiz', name: 'QuizGame', component: QuizGame, meta: { auth: true, title: 'Викторина' } },
    { path: '/games/scratch-card', name: 'ScratchCardGame', component: ScratchCardGame, meta: { auth: true, title: 'Скретч-карта' } },
    { path: '/games/wheel', name: 'WheelOfFortune', component: WheelOfFortune, meta: { auth: true } },
    { path: '/games/cards', name: 'CashbackCardGame', component: CashbackCardGame, meta: { auth: true } },
    { path: '/games/guess-number', name: 'GuessNumberGame', component: GuessNumberGame, meta: { auth: true } },
    { path: '/games/treasure-hunt', name: 'TreasureHuntGame', component: TreasureHuntGame, meta: { auth: true } },

    // ==========================================
    // 🛠️ ИГРОВЫЕ АДМИНКИ
    // ==========================================
    {
        path: '/admin/wheel',
        name: 'WheelAdmin',
        component: WheelAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/daily-bonus',
        name: 'DailyBonusAdmin',
        component: DailyBonusAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/card-game',
        name: 'CardGameAdmin',
        component: CardGameAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/scratch-card',
        name: 'ScratchCardAdmin',
        component: ScratchCardAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/slot-machine',
        name: 'SlotMachineAdmin',
        component: SlotMachineAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/quiz',
        name: 'QuizAdmin',
        component: QuizAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/guess-number',
        name: 'GuessNumberAdmin',
        component: GuessNumberAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/treasure-hunt',
        name: 'TreasureHuntAdmin',
        component: TreasureHuntAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
];
