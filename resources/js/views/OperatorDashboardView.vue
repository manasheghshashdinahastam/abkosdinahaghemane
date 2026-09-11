<script setup>
import { computed, onMounted, ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Skeleton from 'primevue/skeleton';
import { useRouter } from 'vue-router';
import { useAdminStore } from '../stores/useAdminStore';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('OperatorDashboard');
const router = useRouter();
const adminStore = useAdminStore();
const loading = ref(false);
const refreshing = ref(false);
const reviewLoadingId = ref(null);
const error = ref('');
const stats = computed(() => adminStore.stats || { pending_ads_count: 0, pending_kyc_count: 0, today_approved_ads: 0, today_rejected_ads: 0, recent_pending_ads: [] });
const numberFormatter = new Intl.NumberFormat('fa-IR');
const formatMoney = (value) => value ? `${numberFormatter.format(value)} میلیون تومان` : 'ثبت نشده';
const formatExactDate = (value) => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';
function formatRelativeTime(ad) {
    const rawDate = ad.created_at_iso || ad.created_at;
    if (!rawDate) return '-';
    const parsedDate = new Date(rawDate);
    if (Number.isNaN(parsedDate.getTime())) return 'تاریخ نامشخص';
    const minutes = Math.max(0, Math.floor((Date.now() - parsedDate.getTime()) / 60000));
    if (minutes < 60) return minutes < 1 ? 'همین حالا' : `${numberFormatter.format(minutes)} دقیقه پیش`;
    if (minutes < 1440) return `${numberFormatter.format(Math.floor(minutes / 60))} ساعت پیش`;
    return formatExactDate(parsedDate);
}

async function loadStats() {
    loading.value = !adminStore.statsLoaded;
    refreshing.value = adminStore.statsLoaded;
    error.value = '';
    try { await adminStore.loadOperatorStats(); } catch (exception) { error.value = 'دریافت آمار لحظه‌ای انجام نشد.'; logger.error('loadStats', 'Operator stats request failed', logException(exception)); }
    finally { loading.value = false; refreshing.value = false; }
}

async function openReview(ad) {
    if (reviewLoadingId.value) return;
    reviewLoadingId.value = ad.id;
    try {
        await router.push({ name: 'admin.ads.pending', query: { reviewId: ad.id } });
    } finally {
        reviewLoadingId.value = null;
    }
}

onMounted(loadStats);
</script>

<template>
    <section class="operator-dashboard">
        <header class="operator-dashboard__hero">
            <div class="operator-dashboard__hero-copy">
                <span class="operator-dashboard__kicker">OPERATION CONTROL CENTER</span>
                <h1>میز کار و داشبورد عملیات</h1>
                <p class="operator-dashboard__welcome"><span class="operator-dashboard__user-badge">{{ adminStore.user?.name || 'اپراتور' }}</span><span class="operator-dashboard__welcome-copy">خوش‌آمدید <b>•</b> وضعیت لحظه‌ای صف‌های بررسی سامانه مستروام</span></p>
            </div>
            <button class="refresh-button" type="button" :disabled="refreshing" @click="loadStats"><i :class="refreshing ? 'pi pi-spin pi-refresh' : 'pi pi-refresh'"></i><span>به‌روزرسانی</span></button>
        </header>
        <p v-if="error" class="operator-error" role="alert"><i class="pi pi-exclamation-triangle"></i>{{ error }}</p>
        <div v-if="loading" class="operator-kpis"><Skeleton v-for="index in 4" :key="index" height="126px" borderRadius="12px" /></div>
        <div v-else class="operator-kpis">
            <article class="operator-kpi operator-kpi--amber"><div class="operator-kpi__icon"><i class="pi pi-file-edit"></i></div><div><span>آگهی‌های در انتظار بررسی</span><strong>{{ stats.pending_ads_count }}</strong><button type="button" @click="router.push({ name: 'admin.ads.pending' })">مشاهده صف <i class="pi pi-arrow-left"></i></button></div></article>
            <article class="operator-kpi operator-kpi--blue"><div class="operator-kpi__icon"><i class="pi pi-id-card"></i></div><div><span>مدارک هویتی منتظر تایید</span><strong>{{ stats.pending_kyc_count }}</strong><button type="button" @click="router.push({ name: 'admin.kyc' })">صف احراز هویت <i class="pi pi-arrow-left"></i></button></div></article>
            <article class="operator-kpi operator-kpi--green"><div class="operator-kpi__icon"><i class="pi pi-check-circle"></i></div><div><span>آگهی تایید شده امروز</span><strong>{{ stats.today_approved_ads }}</strong><small>به‌روزرسانی از دیتابیس</small></div></article>
            <article class="operator-kpi operator-kpi--red"><div class="operator-kpi__icon"><i class="pi pi-times-circle"></i></div><div><span>آگهی رد شده امروز</span><strong>{{ stats.today_rejected_ads }}</strong><small>به‌روزرسانی از دیتابیس</small></div></article>
        </div>
        <div class="operator-workspace">
            <section class="operator-queue">
                <div class="section-heading"><div><span>صف سریع بررسی</span><h2>۵ آگهی اخیر در انتظار</h2></div><RouterLink :to="{ name: 'admin.ads.pending' }">مشاهده همه <i class="pi pi-arrow-left"></i></RouterLink></div>
                <div class="quick-table-card"><DataTable :value="stats.recent_pending_ads" dataKey="id" responsiveLayout="scroll"><Column header="عنوان و ثبت‌کننده" style="min-width: 190px"><template #body="slotProps"><div class="ad-summary"><strong>{{ slotProps.data.title }}</strong><span><i class="pi pi-user"></i>{{ slotProps.data.user?.name || 'کاربر' }}</span></div></template></Column><Column header="بانک"><template #body="slotProps"><span class="bank-badge"><i class="pi pi-building-columns"></i>{{ slotProps.data.bank?.name || '-' }}</span></template></Column><Column header="مبلغ وام"><template #body="slotProps"><div class="loan-amount"><strong>{{ formatMoney(slotProps.data.loan_amount).replace(' میلیون تومان', '') }}</strong><small>تومان</small></div></template></Column><Column header="ثبت"><template #body="slotProps"><span class="time-cell" :title="formatExactDate(slotProps.data.created_at_iso)">{{ formatRelativeTime(slotProps.data) }}</span></template></Column><Column header="عملیات"><template #body="slotProps"><button class="table-action" type="button" :disabled="reviewLoadingId === slotProps.data.id" @click="openReview(slotProps.data)"><i :class="reviewLoadingId === slotProps.data.id ? 'pi pi-spin pi-spinner' : 'pi pi-eye'"></i>{{ reviewLoadingId === slotProps.data.id ? 'در حال بازکردن...' : 'بررسی' }} <i v-if="reviewLoadingId !== slotProps.data.id" class="pi pi-arrow-left"></i></button></template></Column><template #empty><div class="queue-empty"><i class="pi pi-check-circle"></i><span>صف بررسی خالی است.</span></div></template></DataTable></div>
            </section>
            <aside class="operator-notes"><div class="section-heading"><div><span>مرکز اعلان</span><h2>یادداشت‌های اپراتوری</h2></div><i class="pi pi-bell"></i></div><div class="note"><i class="pi pi-shield"></i><p><strong>امنیت فعال است</strong><br />تمام عملیات بررسی با شناسه کاربری شما ثبت می‌شود.</p></div><div class="note"><i class="pi pi-clock"></i><p><strong>اولویت بررسی</strong><br />پرونده‌های قدیمی‌تر را ابتدا بررسی کنید.</p></div><div class="note note--muted"><i class="pi pi-info-circle"></i><p>آمارها با هر بار ورود یا رفرش دستی از دیتابیس خوانده می‌شوند.</p></div></aside>
        </div>
    </section>
</template>

<style scoped>
.operator-dashboard { max-width: 1280px; margin: 0 auto; }.operator-dashboard__hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 28px; }.operator-dashboard__hero-copy { min-width: 0; }.operator-dashboard__kicker { color: #a62626; font-family: ui-monospace, monospace; font-size: 9px; letter-spacing: .12em; }.operator-dashboard h1 { margin: 7px 0 8px; color: #0f172a; font-size: 27px; font-weight: 900; line-height: 1.45; }.operator-dashboard__welcome { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin: 0; color: #64748b; font-size: 12px; line-height: 2; }.operator-dashboard__user-badge { padding: 4px 10px; border-radius: 8px; background: #f1f5f9; color: #334155; font-size: 11px; font-weight: 800; line-height: 1.5; white-space: nowrap; }.operator-dashboard__welcome-copy { color: #64748b; }.operator-dashboard__welcome-copy b { padding: 0 4px; color: #a62626; }.refresh-button { display: inline-flex; align-items: center; gap: 8px; flex: 0 0 auto; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; color: #475569; cursor: pointer; font: inherit; font-size: 11px; box-shadow: 0 3px 12px #0f172a0a; }.refresh-button:disabled { cursor: wait; opacity: .65; }.operator-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; margin-bottom: 26px; }.operator-kpi { display: flex; min-height: 126px; align-items: flex-start; gap: 13px; padding: 18px; border: 1px solid; border-radius: 12px; }.operator-kpi__icon { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border-radius: 10px; font-size: 16px; }.operator-kpi span { display: block; font-size: 11px; }.operator-kpi strong { display: block; margin: 7px 0 4px; font-size: 27px; }.operator-kpi button { padding: 0; border: 0; background: transparent; cursor: pointer; font: inherit; font-size: 10px; }.operator-kpi button i { margin-right: 4px; font-size: 9px; }.operator-kpi small { font-size: 9px; }.operator-kpi--amber { border-color: #fde68a; background: #fffbeb; color: #92400e; }.operator-kpi--amber .operator-kpi__icon { background: #fef3c7; color: #b45309; }.operator-kpi--amber button { color: #b45309; }.operator-kpi--blue { border-color: #bfdbfe; background: #eff6ff; color: #1e40af; }.operator-kpi--blue .operator-kpi__icon { background: #dbeafe; color: #2563eb; }.operator-kpi--blue button { color: #2563eb; }.operator-kpi--green { border-color: #bbf7d0; background: #f0fdf4; color: #166534; }.operator-kpi--green .operator-kpi__icon { background: #dcfce7; color: #16a34a; }.operator-kpi--red { border-color: #fecaca; background: #fef2f2; color: #991b1b; }.operator-kpi--red .operator-kpi__icon { background: #fee2e2; color: #dc2626; }.operator-workspace { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); align-items: start; gap: 24px; }.operator-queue { grid-column: span 8 / span 8; min-width: 0; padding: 20px; border: 1px solid rgba(229, 231, 235, .8); border-radius: 16px; background: #fff; box-shadow: 0 3px 12px #0f172a0a; }.operator-notes { grid-column: span 4 / span 4; min-width: 0; padding: 20px; border: 1px solid rgba(229, 231, 235, .8); border-radius: 16px; background: #fff; box-shadow: 0 3px 12px #0f172a0a; }.section-heading { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; }.section-heading span { color: #a62626; font-size: 10px; font-weight: 700; }.section-heading h2 { margin: 4px 0 0; color: #0f172a; font-size: 16px; }.section-heading > a { color: #a62626; font-size: 10px; text-decoration: none; }.section-heading > a i { margin-right: 4px; }.section-heading > i { color: #a62626; }.quick-table-card { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 12px; }.quick-table-card :deep(.p-datatable) { font-size: 11px; }.quick-table-card :deep(.p-datatable-thead > tr > th) { padding: 12px 13px; border-bottom: 1px solid #f3f4f6; background: #f9fafb; color: #6b7280; font-size: 10px; font-weight: 700; text-align: right; }.quick-table-card :deep(.p-datatable-tbody > tr) { transition: background-color .18s; }.quick-table-card :deep(.p-datatable-tbody > tr:hover) { background: #f8fafc; }.quick-table-card :deep(.p-datatable-tbody > tr > td) { padding: 13px; border-bottom: 1px solid #f3f4f6; color: #475569; }.ad-summary strong, .ad-summary span { display: block; }.ad-summary strong { overflow: hidden; color: #111827; font-size: 11px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }.ad-summary span { margin-top: 4px; color: #9ca3af; font-size: 9px; }.ad-summary span i { margin-left: 4px; font-size: 9px; }.bank-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 9px; border-radius: 6px; background: #f1f5f9; color: #334155; font-size: 9px; font-weight: 700; white-space: nowrap; }.bank-badge i { color: #64748b; }.loan-amount strong, .loan-amount small { display: block; }.loan-amount strong { color: #1f2937; font-size: 12px; }.loan-amount small { margin-top: 3px; color: #9ca3af; font-size: 9px; }.time-cell { color: #64748b; font-size: 9px; white-space: nowrap; }.table-action { display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border: 0; border-radius: 9px; background: #fef2f2; color: #a62626; cursor: pointer; font: inherit; font-size: 10px; font-weight: 800; box-shadow: 0 2px 6px #991b1b0d; transition: background .18s, color .18s; }.table-action:hover { background: #a62626; color: #fff; }.table-action i { font-size: 9px; }.queue-empty { padding: 35px 10px; color: #94a3b8; text-align: center; }.queue-empty i { display: block; margin-bottom: 8px; color: #22c55e; font-size: 27px; }.queue-empty span { font-size: 11px; }.note { display: flex; gap: 11px; padding: 13px 0; border-bottom: 1px solid #f1f5f9; }.note > i { color: #a62626; font-size: 14px; }.note p { margin: 0; color: #64748b; font-size: 10px; line-height: 1.9; }.note strong { color: #334155; }.note--muted { border: 0; color: #94a3b8; }.note--muted > i { color: #94a3b8; }.operator-error { padding: 10px 13px; border-radius: 8px; background: #fef2f2; color: #b91c1c; font-size: 11px; }
@media (max-width: 1050px) { .operator-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }.operator-queue, .operator-notes { grid-column: 1 / -1; } } @media (max-width: 600px) { .operator-dashboard__hero { align-items: flex-start; flex-direction: column; }.operator-dashboard h1 { font-size: 22px; }.operator-dashboard__welcome { align-items: flex-start; flex-direction: column; gap: 5px; }.operator-kpis { grid-template-columns: 1fr; }.operator-queue, .operator-notes { padding: 14px; } }
</style>
