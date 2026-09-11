<script setup>
import { computed, ref } from 'vue';
import Password from 'primevue/password';
import { useRouter } from 'vue-router';
import { authService } from '../services';
import { useAuthStore } from '../stores/useAuthStore';
import { useAdminStore } from '../stores/useAdminStore';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdminLogin');
const router = useRouter();
const authStore = useAuthStore();
const adminStore = useAdminStore();
const username = ref(localStorage.getItem('admin_remembered_username') || '');
const password = ref('');
const rememberMe = ref(false);
const loading = ref(false);
const error = ref('');
const canSubmit = computed(() => Boolean(username.value.trim() && password.value && !loading.value));

async function submit() {
    if (!canSubmit.value) return;
    loading.value = true;
    error.value = '';
    try {
        const data = await authService.adminLogin(username.value.trim(), password.value);
        authStore.setSession(data.token, data.user);
        if (rememberMe.value) localStorage.setItem('admin_remembered_username', username.value.trim());
        else localStorage.removeItem('admin_remembered_username');
        await adminStore.loadProfile();
        logger.info('submit', 'Admin login succeeded', { userId: data.user?.id, rememberMe: rememberMe.value });
        await router.push({ name: 'admin.dashboard' });
    } catch (exception) {
        error.value = exception.response?.data?.message || 'ورود به پنل مدیریت انجام نشد.';
        logger.warn('submit', 'Admin login failed', logException(exception));
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="admin-login" dir="rtl">
        <div class="admin-login__glow admin-login__glow--top" aria-hidden="true"></div>
        <div class="admin-login__glow admin-login__glow--bottom" aria-hidden="true"></div>
        <div class="admin-login__grid" aria-hidden="true"></div>
        <section class="admin-login__card" aria-labelledby="admin-login-title">
            <header class="admin-login__header">
                <RouterLink class="admin-login__logo" to="/" aria-label="بازگشت به مستروام"><span class="admin-login__logo-mark">م</span><span>مستروام</span></RouterLink>
                <span class="admin-login__eyebrow">Enterprise Admin Portal</span>
                <h1 id="admin-login-title">ورود به پنل مدیریت مستروام</h1>
                <p>سامانه تبادل امتیاز وام <b>•</b> دسترسی امن سازمانی</p>
            </header>
            <form class="admin-login__form" @submit.prevent="submit">
                <div class="admin-login__field">
                    <label for="admin-username">شماره موبایل یا ایمیل</label>
                    <div class="admin-login__input-wrap">
                        <i class="pi pi-user" aria-hidden="true"></i>
                        <input id="admin-username" v-model.trim="username" autocomplete="username" dir="ltr" placeholder="09120000001 یا admin@mestroam.ir" required />
                    </div>
                </div>
                <div class="admin-login__field">
                    <label for="admin-password">رمز عبور</label>
                    <div class="admin-login__password-wrap">
                        <i class="pi pi-lock" aria-hidden="true"></i>
                        <Password id="admin-password" v-model="password" inputClass="admin-login__password-input" toggleMask :feedback="false" autocomplete="current-password" placeholder="رمز عبور خود را وارد کنید" />
                    </div>
                </div>
                <div class="admin-login__options">
                    <label class="admin-login__remember"><input v-model="rememberMe" type="checkbox" /><span>مرا به خاطر بسپار</span></label>
                    <a href="#" @click.prevent>فراموشی رمز عبور؟</a>
                </div>
                <p v-if="error" class="admin-login__error" role="alert"><i class="pi pi-exclamation-circle"></i>{{ error }}</p>
                <button class="admin-login__submit" type="submit" :disabled="!canSubmit"><i :class="loading ? 'pi pi-spin pi-spinner' : 'pi pi-arrow-left'"></i>{{ loading ? 'در حال ورود امن...' : 'ورود به پنل' }}</button>
            </form>
            <footer class="admin-login__security"><i class="pi pi-lock" aria-hidden="true"></i><span>این بخش مختص مدیران مجاز است. تمامی فعالیت‌ها در این سامانه لاگ و رصد می‌شوند.</span></footer>
        </section>
    </main>
</template>

<style scoped>
.admin-login { position: relative; display: flex; min-height: 100vh; align-items: center; justify-content: center; overflow: hidden; padding: 24px 16px; background: #020617; color: #fff; }
.admin-login::before { position: absolute; inset: 0; background: radial-gradient(circle at center, rgba(166, 38, 38, .08), transparent 43%); content: ''; pointer-events: none; }
.admin-login__grid { position: absolute; inset: 0; opacity: .38; background-image: linear-gradient(rgba(148, 163, 184, .055) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, .055) 1px, transparent 1px); background-size: 48px 48px; mask-image: radial-gradient(ellipse at center, #000 0%, transparent 75%); pointer-events: none; }
.admin-login__glow { position: absolute; width: 500px; height: 500px; border-radius: 999px; background: rgba(166, 38, 38, .2); filter: blur(82px); pointer-events: none; }.admin-login__glow--top { top: -150px; right: -120px; }.admin-login__glow--bottom { bottom: -250px; left: -170px; opacity: .55; }
.admin-login__card { position: relative; z-index: 1; width: 100%; max-width: 430px; padding: 32px; border: 1px solid rgba(30, 41, 59, .8); border-radius: 24px; background: rgba(15, 23, 42, .8); box-shadow: 0 25px 70px rgba(0, 0, 0, .5); backdrop-filter: blur(22px); }
.admin-login__header { text-align: center; }.admin-login__logo { display: inline-flex; align-items: center; gap: 10px; color: #f8fafc; font-size: 18px; font-weight: 900; text-decoration: none; }.admin-login__logo-mark { display: grid; width: 44px; height: 44px; place-items: center; border: 1px solid rgba(248, 113, 113, .35); border-radius: 13px; background: linear-gradient(135deg, #b83232, #701717); box-shadow: 0 10px 26px rgba(166, 38, 38, .28); }.admin-login__eyebrow { width: fit-content; margin: 25px auto 14px; padding: 5px 9px; border: 1px solid rgba(148, 163, 184, .18); border-radius: 5px; color: #94a3b8; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 9px; letter-spacing: .08em; text-transform: uppercase; }.admin-login__header h1 { margin: 0; color: #fff; font-size: 21px; font-weight: 900; line-height: 1.7; }.admin-login__header p { margin: 4px 0 0; color: #94a3b8; font-size: 11px; }.admin-login__header p b { padding: 0 3px; color: #b83232; }
.admin-login__form { display: grid; gap: 18px; margin-top: 30px; }.admin-login__field label { display: block; margin-bottom: 8px; color: #cbd5e1; font-size: 11px; font-weight: 700; }.admin-login__input-wrap, .admin-login__password-wrap { position: relative; display: flex; align-items: center; }.admin-login__input-wrap > i, .admin-login__password-wrap > i { position: absolute; right: 14px; z-index: 1; color: #64748b; font-size: 13px; }.admin-login__input-wrap input { box-sizing: border-box; width: 100%; padding: 12px 40px 12px 14px; border: 1px solid rgba(71, 85, 105, .8); border-radius: 12px; outline: 0; background: rgba(30, 41, 59, .5); color: #fff; font: inherit; font-size: 12px; transition: border-color .2s, box-shadow .2s; }.admin-login__input-wrap input::placeholder, .admin-login__password-input::placeholder { color: #64748b; }.admin-login__input-wrap input:focus { border-color: #a62626; box-shadow: 0 0 0 3px rgba(166, 38, 38, .2); }
.admin-login__password-wrap :deep(.p-password) { width: 100%; }.admin-login__password-wrap :deep(.p-password-input) { box-sizing: border-box; width: 100%; padding: 12px 40px 12px 44px; border: 1px solid rgba(71, 85, 105, .8); border-radius: 12px; outline: 0; background: rgba(30, 41, 59, .5); color: #fff; font: inherit; font-size: 12px; transition: border-color .2s, box-shadow .2s; }.admin-login__password-wrap :deep(.p-password-input:focus) { border-color: #a62626; box-shadow: 0 0 0 3px rgba(166, 38, 38, .2); }.admin-login__password-wrap :deep(.p-password-toggle-mask-icon) { right: auto; left: 14px; color: #64748b; }
.admin-login__options { display: flex; align-items: center; justify-content: space-between; color: #94a3b8; font-size: 11px; }.admin-login__options a { color: #c08484; text-decoration: none; }.admin-login__options a:hover { color: #fca5a5; }.admin-login__remember { display: flex; align-items: center; gap: 7px; cursor: pointer; }.admin-login__remember input { width: 14px; height: 14px; margin: 0; accent-color: #a62626; }.admin-login__error { display: flex; align-items: center; gap: 7px; margin: -4px 0 0; color: #fca5a5; font-size: 11px; }.admin-login__submit { display: flex; align-items: center; justify-content: center; gap: 9px; width: 100%; padding: 13px; border: 0; border-radius: 12px; background: linear-gradient(90deg, #a62626, #801a1a); color: #fff; cursor: pointer; font: inherit; font-size: 13px; font-weight: 800; box-shadow: 0 12px 28px rgba(69, 10, 10, .4); transition: transform .15s, filter .2s; }.admin-login__submit:hover:not(:disabled) { filter: brightness(1.14); }.admin-login__submit:active:not(:disabled) { transform: scale(.99); }.admin-login__submit:disabled { cursor: not-allowed; opacity: .55; }.admin-login__security { display: flex; align-items: flex-start; gap: 8px; margin-top: 25px; padding-top: 18px; border-top: 1px solid rgba(51, 65, 85, .55); color: #64748b; font-size: 10px; line-height: 1.9; }.admin-login__security i { margin-top: 3px; color: #64748b; }
@media (max-width: 480px) { .admin-login { padding: 16px 12px; }.admin-login__card { padding: 25px 20px; border-radius: 20px; }.admin-login__header h1 { font-size: 18px; } }
@media (prefers-reduced-motion: reduce) { .admin-login__submit { transition: none; } }
</style>
