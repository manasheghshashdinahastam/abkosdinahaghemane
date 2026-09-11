<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { adminNavigation } from '../config/navigation';
import { useAdminStore } from '../stores/useAdminStore';
import { useAuthStore } from '../stores/useAuthStore';
import { authService } from '../services';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdminLayout');
const router = useRouter();
const adminStore = useAdminStore();
const authStore = useAuthStore();
const isLoggingOut = ref(false);
const showLogoutConfirm = ref(false);
const visibleNavigation = computed(() => adminNavigation.filter((item) => !item.requiredPermission || adminStore.can(item.requiredPermission)));
const roleLabels = { super_admin: 'سوپر ادمین', admin: 'مدیر سیستم', financial_manager: 'مدیر مالی', operator: 'اپراتور', auditor: 'حسابرس' };
const currentRoleLabel = computed(() => roleLabels[adminStore.currentRole] || adminStore.currentRole || 'مدیر');
const badgeValue = (item) => item.countKey ? adminStore[item.countKey] : 0;

async function refreshSidebarStats() {
    if (adminStore.can('ads.view') && !adminStore.statsLoaded) {
        try { await adminStore.loadOperatorStats(); } catch (exception) { logger.warn('refreshSidebarStats', 'Sidebar counters unavailable', logException(exception)); }
    }
}

function requestLogout() { showLogoutConfirm.value = true; }

async function logout() {
    if (isLoggingOut.value) return;
    isLoggingOut.value = true;
    try { await authService.logout(); } catch (exception) { logger.error('logout', 'Admin logout failed', logException(exception)); }
    authStore.clear();
    adminStore.clear();
    showLogoutConfirm.value = false;
    await router.push({ name: 'home' });
}

onMounted(async () => {
    if (!adminStore.loaded) {
        try { await adminStore.loadProfile(); } catch (exception) { logger.error('loadProfile', 'Admin profile failed', logException(exception)); }
    }
    await refreshSidebarStats();
});
</script>

<template>
    <div class="admin-shell" dir="rtl">
        <aside class="admin-sidebar">
            <RouterLink class="admin-brand" to="/"><span class="admin-brand__mark">م</span><span>مستروام / مدیریت</span></RouterLink>
            <nav class="admin-nav" aria-label="منوی مدیریت">
                <RouterLink v-for="item in visibleNavigation" :key="item.label" :to="item.to" class="admin-nav__item">
                    <i :class="item.icon" aria-hidden="true"></i><span>{{ item.label }}</span><b v-if="badgeValue(item)" class="admin-nav__badge">{{ badgeValue(item) > 99 ? '99+' : badgeValue(item) }}</b>
                </RouterLink>
            </nav>
            <div class="admin-sidebar__status"><i class="pi pi-shield"></i><span>سیستم‌های امنیتی فعال</span><b></b></div>
        </aside>
        <section class="admin-main">
            <header class="admin-header">
                <div class="admin-header__identity"><div class="admin-avatar">{{ (adminStore.user?.name || 'م').slice(0, 1) }}</div><div><span>پنل مدیریت</span><strong>{{ adminStore.user?.name || 'مدیر سامانه' }}</strong><small>{{ currentRoleLabel }}</small></div></div>
                <div class="admin-header__actions"><button class="admin-icon-button" type="button" aria-label="اعلان‌ها"><i class="pi pi-bell"></i><b></b></button><button class="admin-logout" type="button" :disabled="isLoggingOut" @click="requestLogout"><i class="pi pi-sign-out"></i><span>{{ isLoggingOut ? 'در حال خروج...' : 'خروج' }}</span></button></div>
            </header>
            <main class="admin-content"><RouterView /></main>
        </section>
    </div>
    <div v-if="showLogoutConfirm" class="admin-confirm" role="dialog" aria-modal="true">
        <div class="admin-confirm__box"><i class="pi pi-sign-out"></i><h2>خروج از پنل</h2><p>آیا برای خروج از حساب مدیریت اطمینان دارید؟</p><div><button type="button" @click="showLogoutConfirm = false">انصراف</button><button type="button" @click="logout">تأیید خروج</button></div></div>
    </div>
</template>

<style scoped>
.admin-shell { min-height: 100vh; display: flex; background: #f8fafc; color: #1e293b; }.admin-sidebar { position: relative; display: flex; width: 270px; flex: 0 0 270px; flex-direction: column; padding: 24px 16px; border-left: 1px solid #172333; background: #0f172a; }.admin-brand { display: flex; align-items: center; gap: 10px; margin: 0 8px 34px; color: #f8fafc; font-weight: 800; text-decoration: none; }.admin-brand__mark { display: grid; width: 40px; height: 40px; place-items: center; border: 1px solid #bd5555; border-radius: 10px; background: linear-gradient(135deg, #b83232, #701717); }.admin-nav { display: grid; gap: 5px; }.admin-nav__item { position: relative; display: flex; align-items: center; gap: 12px; min-height: 46px; padding: 11px 13px; border-radius: 8px; color: #94a3b8; font-size: 12px; text-decoration: none; transition: background .2s, color .2s; }.admin-nav__item:hover, .admin-nav__item.router-link-active { background: #1e293b; color: #fff; }.admin-nav__item.router-link-active { border-right: 3px solid #a62626; }.admin-nav__item > i { width: 18px; color: #64748b; text-align: center; }.admin-nav__item.router-link-active > i { color: #fca5a5; }.admin-nav__badge { min-width: 20px; margin-right: auto; padding: 2px 6px; border-radius: 10px; background: #a62626; color: #fff; font-size: 9px; text-align: center; }.admin-sidebar__status { display: flex; align-items: center; gap: 8px; margin: auto 8px 4px; padding-top: 17px; border-top: 1px solid #1e293b; color: #64748b; font-size: 10px; }.admin-sidebar__status b { width: 6px; height: 6px; margin-right: auto; border-radius: 50%; background: #34d399; box-shadow: 0 0 9px #34d399; }.admin-main { flex: 1; min-width: 0; }.admin-header { display: flex; min-height: 78px; align-items: center; justify-content: space-between; padding: 12px 32px; border-bottom: 1px solid #e2e8f0; background: rgba(255, 255, 255, .88); backdrop-filter: blur(12px); }.admin-header__identity, .admin-header__actions { display: flex; align-items: center; gap: 13px; }.admin-header__identity > div:last-child { display: grid; align-items: center; gap: 2px; }.admin-header__identity span, .admin-header__identity small { color: #94a3b8; font-size: 10px; }.admin-header__identity strong { color: #1e293b; font-size: 14px; }.admin-avatar { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 50%; background: #fbe4e4; color: #a62626; font-weight: 800; }.admin-icon-button, .admin-logout { position: relative; display: inline-flex; align-items: center; gap: 7px; border: 0; cursor: pointer; font: inherit; }.admin-icon-button { width: 35px; height: 35px; justify-content: center; border-radius: 50%; background: #f1f5f9; color: #64748b; }.admin-icon-button b { position: absolute; top: 5px; right: 5px; width: 5px; height: 5px; border-radius: 50%; background: #a62626; }.admin-logout { padding: 9px 12px; border: 1px solid #f0d3d3; border-radius: 7px; background: #fff; color: #a62626; font-size: 11px; }.admin-content { min-height: calc(100vh - 78px); padding: 32px; background: rgba(248, 250, 252, .7); }.admin-confirm { position: fixed; inset: 0; z-index: 20; display: grid; place-items: center; background: rgba(2, 6, 23, .5); backdrop-filter: blur(5px); }.admin-confirm__box { width: min(380px, calc(100% - 32px)); padding: 28px; border-radius: 14px; background: #fff; text-align: center; box-shadow: 0 22px 60px #02061755; }.admin-confirm__box > i { color: #a62626; font-size: 24px; }.admin-confirm__box h2 { margin: 10px 0 5px; font-size: 18px; }.admin-confirm__box p { color: #64748b; font-size: 12px; }.admin-confirm__box > div { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }.admin-confirm__box button { padding: 9px 16px; border: 0; border-radius: 7px; cursor: pointer; font: inherit; font-size: 11px; }.admin-confirm__box button:first-child { background: #f1f5f9; color: #475569; }.admin-confirm__box button:last-child { background: #a62626; color: #fff; }
@media (max-width: 760px) { .admin-shell { display: block; }.admin-sidebar { width: auto; padding: 16px; }.admin-brand { margin-bottom: 18px; }.admin-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }.admin-sidebar__status { display: none; }.admin-header { padding: 12px 18px; }.admin-logout span { display: none; }.admin-content { min-height: calc(100vh - 78px); padding: 22px 16px; } }
</style>
