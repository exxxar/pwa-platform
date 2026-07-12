import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/referrals';

export const useReferralsStore = defineStore('referrals', {
    state: () => ({
        // Рефералы
        referralTree: {
            level_1: [],
            level_2: [],
            level_3: [],
            stats: { total: 0, by_level: { 1: 0, 2: 0, 3: 0 }, total_earnings: 0 },
        },
        rewardsHistory: [],
        referralLink: null,
        referralCode: null,

        // Друзья
        friends: [],
        incomingRequests: [],

        // Состояние
        isLoading: false,
        isHydrated: false,
        lastError: null,
    }),

    getters: {
        totalReferrals: (state) => state.referralTree.stats.total,
        totalEarnings: (state) => state.referralTree.stats.total_earnings,
        friendsCount: (state) => state.friends.length,
        pendingRequestsCount: (state) => state.incomingRequests.length,
    },

    actions: {
        // ==========================================
        // РЕФЕРАЛЫ
        // ==========================================

        async loadReferralTree() {
            this.isLoading = true;
            try {
                const response = await axios.get(`${BASE}/tree`);
                this.referralTree = response.data.data;
                this.isHydrated = true;
            } catch (error) {
                console.error('[Referrals] Ошибка загрузки дерева:', error);
                this.lastError = error.message;
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        async loadRewardsHistory() {
            try {
                const response = await axios.get(`${BASE}/rewards`);
                this.rewardsHistory = response.data.data || [];
            } catch (error) {
                console.error('[Referrals] Ошибка загрузки истории:', error);
                throw error;
            }
        },

        async loadReferralLink() {
            try {
                const response = await axios.get(`${BASE}/link`);
                const data = response.data.data;
                this.referralCode = data.code;
                this.referralLink = data.link;
                return data;
            } catch (error) {
                console.error('[Referrals] Ошибка загрузки ссылки:', error);
                throw error;
            }
        },

        // ==========================================
        // ДРУЗЬЯ
        // ==========================================

        async loadFriends() {
            try {
                const response = await axios.get(`${BASE}/friends`);
                this.friends = response.data.data || [];
            } catch (error) {
                console.error('[Referrals] Ошибка загрузки друзей:', error);
                throw error;
            }
        },

        async loadIncomingRequests() {
            try {
                const response = await axios.get(`${BASE}/friends/requests`);
                this.incomingRequests = response.data.data || [];
            } catch (error) {
                console.error('[Referrals] Ошибка загрузки заявок:', error);
                throw error;
            }
        },

        async sendFriendRequest(friendId) {
            try {
                const response = await axios.post(`${BASE}/friends/request`, {
                    friend_id: friendId,
                });
                return response.data;
            } catch (error) {
                console.error('[Referrals] Ошибка отправки заявки:', error);
                throw error;
            }
        },

        async acceptFriendRequest(requestId) {
            try {
                const response = await axios.post(`${BASE}/friends/request/${requestId}/accept`);
                // Обновляем список
                this.incomingRequests = this.incomingRequests.filter(r => r.id !== requestId);
                await this.loadFriends();
                return response.data;
            } catch (error) {
                console.error('[Referrals] Ошибка принятия заявки:', error);
                throw error;
            }
        },

        async rejectFriendRequest(requestId) {
            try {
                const response = await axios.post(`${BASE}/friends/request/${requestId}/reject`);
                this.incomingRequests = this.incomingRequests.filter(r => r.id !== requestId);
                return response.data;
            } catch (error) {
                console.error('[Referrals] Ошибка отклонения заявки:', error);
                throw error;
            }
        },

        async removeFriend(friendId) {
            try {
                const response = await axios.delete(`${BASE}/friends/${friendId}`);
                this.friends = this.friends.filter(f => f.id !== friendId);
                return response.data;
            } catch (error) {
                console.error('[Referrals] Ошибка удаления друга:', error);
                throw error;
            }
        },

        $reset() {
            this.referralTree = { level_1: [], level_2: [], level_3: [], stats: {} };
            this.rewardsHistory = [];
            this.referralLink = null;
            this.referralCode = null;
            this.friends = [];
            this.incomingRequests = [];
            this.isLoading = false;
            this.isHydrated = false;
        },
    },
});
