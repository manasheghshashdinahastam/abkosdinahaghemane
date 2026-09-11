import { useAdminStore } from '../stores/useAdminStore';

export const canDirective = {
    mounted(element, binding) {
        const store = useAdminStore();
        const allowed = store.can(binding.value);
        if (!allowed) {
            element.setAttribute('disabled', 'true');
            element.setAttribute('aria-disabled', 'true');
            element.classList.add('v-can-disabled');
        }
    },
};