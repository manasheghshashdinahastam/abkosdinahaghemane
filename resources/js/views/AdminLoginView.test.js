import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { adminLogin, setSession, loadProfile, push } = vi.hoisted(() => ({
    adminLogin: vi.fn(),
    setSession: vi.fn(),
    loadProfile: vi.fn(),
    push: vi.fn(),
}));

vi.mock('../services', () => ({
    authService: { adminLogin },
}));
vi.mock('../stores/useAuthStore', () => ({
    useAuthStore: () => ({ setSession }),
}));
vi.mock('../stores/useAdminStore', () => ({
    useAdminStore: () => ({ loadProfile }),
}));
vi.mock('primevue/password', () => ({
    default: {
        name: 'Password',
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template: '<input class="password-stub" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    },
}));
vi.mock('vue-router', () => ({
    useRouter: () => ({ push }),
    RouterLink: { template: '<a><slot /></a>' },
}));

import AdminLoginView from './AdminLoginView.vue';

describe('AdminLoginView', () => {
    beforeEach(() => {
        localStorage.clear();
        adminLogin.mockReset();
        setSession.mockReset();
        loadProfile.mockReset();
        push.mockReset();
    });

    function mountView() {
        return mount(AdminLoginView, {
            global: {
                stubs: { RouterLink: { template: '<a><slot /></a>' } },
            },
        });
    }

    it('submits username and password and navigates after login', async () => {
        adminLogin.mockResolvedValue({ token: 'admin-token', user: { id: 7 } });
        loadProfile.mockResolvedValue({});
        const wrapper = mountView();

        await wrapper.find('#admin-username').setValue('admin@mestroam.ir');
        await wrapper.find('.password-stub').setValue('Admin@123456');
        await wrapper.find('form').trigger('submit');

        expect(adminLogin).toHaveBeenCalledWith('admin@mestroam.ir', 'Admin@123456');
        expect(setSession).toHaveBeenCalledWith('admin-token', { id: 7 });
        expect(loadProfile).toHaveBeenCalledOnce();
        expect(push).toHaveBeenCalledWith({ name: 'admin.dashboard' });
    });

    it('shows loading state while the login request is pending', async () => {
        let resolveLogin;
        adminLogin.mockReturnValue(new Promise((resolve) => { resolveLogin = resolve; }));
        const wrapper = mountView();

        await wrapper.find('#admin-username').setValue('09120000001');
        await wrapper.find('.password-stub').setValue('Admin@123456');
        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.admin-login__submit').text()).toContain('در حال ورود امن');
        expect(wrapper.find('.admin-login__submit').attributes('disabled')).toBeDefined();

        resolveLogin({ token: 'token', user: { id: 1 } });
        await wrapper.vm.$nextTick();
    });
});
