import { defineStore } from 'pinia';
import apiClient from '../services/apiClient';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdminStore');

export const useAdminStore = defineStore('admin', {
    state: () => ({ user: null, roles: [], permissions: [], stats: null, loaded: false, statsLoaded: false }),
    getters: {
        hasRole: (state) => (role) => state.roles.includes(role),
        can: (state) => (permission) => state.roles.includes('super_admin') || state.permissions.includes(permission),
        currentRole: (state) => state.roles[0] || null,
        pendingAdsCount: (state) => state.stats?.pending_ads_count || 0,
        pendingKycCount: (state) => state.stats?.pending_kyc_count || 0,
    },
    actions: {
        async loadProfile() {
            try {
                const { data } = await apiClient.get('/admin/me');
                this.user = data.user;
                this.roles = data.roles || [];
                this.permissions = data.permissions || [];
                this.loaded = true;
                localStorage.setItem('auth_user', JSON.stringify({ ...data.user, roles: this.roles, permissions: this.permissions }));
                logger.info('loadProfile', 'Admin permissions loaded', { userId: this.user?.id, roleCount: this.roles.length, permissionCount: this.permissions.length });
                return data;
            } catch (exception) {
                logger.error('loadProfile', 'Admin permissions could not be loaded', logException(exception));
                throw exception;
            }
        },
        async loadOperatorStats() {
            try {
                const { data } = await apiClient.get('/admin/dashboard/operator-stats');
                this.stats = data;
                this.statsLoaded = true;
                logger.info('loadOperatorStats', 'Operator dashboard stats loaded', {
                    pendingAds: data.pending_ads_count, pendingKyc: data.pending_kyc_count,
                });
                return data;
            } catch (exception) {
                logger.error('loadOperatorStats', 'Operator dashboard stats failed', logException(exception));
                throw exception;
            }
        },
        clear() {
            this.$reset();
            logger.info('clear', 'Admin permissions cleared');
        },
    },
});