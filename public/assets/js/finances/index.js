'use strict';

const financeComponents = document.querySelectorAll('[data-finance-form-app]');

const parseAmount = (value) => {
    const normalized = value.trim().replace(',', '.');

    return /^\d{1,12}(\.\d{1,2})?$/.test(normalized)
        ? Math.round(Number(normalized) * 100)
        : 0;
};

const calculatePreview = (form) => {
    const amount = (name) => parseAmount(
        form.querySelector(`[name="${name}"]`)?.value || '0',
    );

    return amount('income_amount')
        - amount('variable_expense_amount')
        - amount('fixed_expense_amount')
        - amount('administrative_expense_amount');
};

const formatAmount = (cents, currency) => {
    const amount = Math.abs(cents) / 100;
    const formatted = new Intl.NumberFormat('es', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);

    return `${cents < 0 ? '−' : ''}${currency} ${formatted}`;
};

const enhanceFinanceFormWithoutVue = (root) => {
    const form = root.querySelector('[data-finance-form]');

    if (!form) {
        return;
    }

    const result = form.querySelector('[data-result-preview]');
    const currency = root.dataset.currency || 'USD';
    const updatePreview = () => {
        if (result) {
            result.textContent = formatAmount(calculatePreview(form), currency);
        }
    };

    form.addEventListener('input', updatePreview);
    form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"]');

        if (submit) {
            submit.disabled = true;
            submit.querySelector('[data-submit-default]')?.setAttribute('hidden', '');
            submit.querySelector('[data-submit-progress]')?.removeAttribute('hidden');
        }
    });
    updatePreview();
};

const mountFinanceForm = (root) => {
    const form = root.querySelector('[data-finance-form]');

    if (!form) {
        return;
    }

    if (!window.Vue) {
        enhanceFinanceFormWithoutVue(root);

        return;
    }

    try {
        window.Vue.createApp({
        data() {
            return {
                currency: root.dataset.currency || 'USD',
                previewCents: calculatePreview(form),
                submitting: false,
            };
        },
        computed: {
            formattedPreview() {
                return formatAmount(this.previewCents, this.currency);
            },
        },
        methods: {
            startSubmitting() {
                this.submitting = true;
            },
            updatePreview(event) {
                this.previewCents = calculatePreview(event.currentTarget);
            },
        },
        }).mount(root);
    } catch (error) {
        enhanceFinanceFormWithoutVue(root);
    }
};

financeComponents.forEach(mountFinanceForm);

const activeForm = document.body.dataset.activeForm;

if (activeForm) {
    document.getElementById(activeForm)?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });
}
