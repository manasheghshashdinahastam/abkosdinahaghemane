<script setup>
import { onMounted, ref } from 'vue';
import Skeleton from 'primevue/skeleton';
import { adService } from '../services';
import apiClient from '../services/apiClient';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdminDashboard');
const ads = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const stats = ref({ pending_ads: 0, daily_transactions: 0 });

async function loadAds() {
	isLoading.value = true;
	try {
		const statsResponse = await apiClient.get('/admin/dashboard');
		stats.value = statsResponse.data?.data || stats.value;
		const response = await adService.getAll();
		ads.value = response.data || [];
		logger.info('onMounted', 'Admin advertisements received', { count: ads.value.length });
		if (!ads.value.length) logger.warn('onMounted', 'No admin advertisements received');
	} catch (exception) {
		errorMessage.value = 'دریافت آگهی‌ها با خطا مواجه شد.';
		logger.error('onMounted', 'Admin advertisements request failed', logException(exception));
	} finally {
		isLoading.value = false;
	}
}

onMounted(loadAds);
</script>

<template><section><span class="dashboard-kicker">مدیریت امن</span><h1>داشبورد ادمین</h1><p>شاخص‌های عملیاتی سامانه بر اساس سطح دسترسی شما.</p><Skeleton v-if="isLoading" height="90px" /><p v-else-if="errorMessage" role="alert">{{ errorMessage }}</p><div v-else class="dashboard-stats"><div><strong>{{ stats.pending_ads }}</strong><span>در انتظار بررسی</span></div><div><strong>{{ stats.daily_transactions }}</strong><span>تراکنش‌های امروز</span></div><div><strong>{{ ads.length }}</strong><span>آگهی دریافت‌شده</span></div></div></section></template>