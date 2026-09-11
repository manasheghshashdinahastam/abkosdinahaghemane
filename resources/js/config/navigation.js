export const adminNavigation = [
    { label: 'داشبورد', icon: 'pi pi-home', to: { name: 'admin.dashboard' } },
    { label: 'صف بررسی آگهی‌ها', icon: 'pi pi-check-square', to: { name: 'admin.ads.pending' }, requiredPermission: 'ads.view', countKey: 'pendingAdsCount' },
    { label: 'کاربران', icon: 'pi pi-users', to: { name: 'admin.users' }, requiredPermission: 'users.view' },
    { label: 'تراکنش‌ها و تسویه‌ها', icon: 'pi pi-wallet', to: { name: 'admin.finance' }, requiredPermission: 'finance.view' },
    { label: 'احراز هویت و مدارک', icon: 'pi pi-id-card', to: { name: 'admin.kyc' }, requiredPermission: 'users.verify', countKey: 'pendingKycCount' },
    { label: 'تنظیمات', icon: 'pi pi-cog', to: { name: 'admin.settings' }, requiredPermission: 'settings.manage' },
    { label: 'گزارش‌ها', icon: 'pi pi-chart-bar', to: { name: 'admin.reports' }, requiredPermission: 'reports.view' },
];