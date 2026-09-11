<script setup>
import { onMounted, ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import apiClient from '../services/apiClient';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdminKyc');
const verifications = ref([]); const loading = ref(false); const error = ref('');
async function load() { loading.value = true; try { const { data } = await apiClient.get('/admin/kyc/pending'); verifications.value = data.data || []; } catch (exception) { error.value = 'دریافت صف احراز هویت انجام نشد.'; logger.error('load', 'Pending KYC failed', logException(exception)); } finally { loading.value = false; } }
onMounted(load);
</script>

<template><section class="ops-page"><header class="ops-page__header"><div><span>عملیات / احراز هویت</span><h1>صف بررسی مدارک هویتی</h1><p>پرونده‌های در انتظار بررسی اپراتور را مشاهده کنید.</p></div><button class="outline-button" type="button" @click="load"><i class="pi pi-refresh"></i>به‌روزرسانی</button></header><p v-if="error" class="ops-error">{{ error }}</p><div class="ops-table"><DataTable :value="verifications" :loading="loading" stripedRows responsiveLayout="scroll" emptyMessage="پرونده‌ای در انتظار بررسی نیست."><Column field="id" header="شماره پرونده" /><Column header="کاربر"><template #body="slotProps"><strong>{{ slotProps.data.user?.name || '-' }}</strong><small>{{ slotProps.data.user?.mobile || '-' }}</small></template></Column><Column header="کد ملی"><template #body="slotProps">{{ slotProps.data.national_code || slotProps.data.user?.national_code || '-' }}</template></Column><Column field="created_at" header="تاریخ ثبت" /><Column header="وضعیت"><template #body><span class="status-wait">در انتظار بررسی</span></template></Column><Column header="عملیات"><template #body="slotProps"><RouterLink class="review-link" :to="{ name: 'admin.verifications', query: { id: slotProps.data.id } }">مشاهده پرونده</RouterLink></template></Column></DataTable></div></section></template>

<style scoped>
.ops-page { max-width: 1280px; margin: 0 auto; }.ops-page__header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 25px; }.ops-page__header span { color: #a62626; font-size: 10px; }.ops-page h1 { margin: 7px 0 4px; font-size: 26px; }.ops-page__header p { margin: 0; color: #64748b; font-size: 12px; }.outline-button { padding: 9px 13px; border: 1px solid #e2e8f0; border-radius: 7px; background: #fff; color: #475569; cursor: pointer; font: inherit; font-size: 11px; }.outline-button i { margin-left: 7px; }.ops-table { padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }.ops-table :deep(.p-datatable) { font-size: 11px; }.ops-table :deep(th) { color: #64748b; font-size: 10px; }.ops-table small, .ops-table strong { display: block; }.ops-table small { margin-top: 4px; color: #94a3b8; font-size: 9px; }.status-wait { padding: 4px 7px; border-radius: 9px; background: #fef3c7; color: #92400e; font-size: 9px; }.review-link { color: #a62626; font-size: 10px; text-decoration: none; }.ops-error { padding: 10px; background: #fef2f2; color: #b91c1c; font-size: 11px; } @media (max-width: 600px) { .ops-page__header { align-items: flex-start; flex-direction: column; gap: 13px; }.ops-table { padding: 10px; } }
</style>
