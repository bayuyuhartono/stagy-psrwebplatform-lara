// Emit the theme's static images so Blade can reference them with Vite::asset().
// Eager URL imports survive tree-shaking; vite.config.js keeps them from being inlined.
import.meta.glob('./assets/images/**', { eager: true, query: '?url', import: 'default' });

/**
 * Demo request form (demo_form block): posts to the public contact API as a
 * "demo" submission and shows the theme's translated messages.
 */
document.querySelectorAll('[data-demo-form]').forEach((form) => {
    const messages = JSON.parse(form.dataset.messages || '{}');
    const success = form.querySelector('[data-demo-success]');
    const error = form.querySelector('[data-demo-error]');
    const submit = form.querySelector('[data-demo-submit]');
    const submitLabel = submit.textContent;
    const requiresRecaptcha = Boolean(form.dataset.recaptchaSiteKey);
    // Browsers draw an empty date input's "dd/mm/yyyy" in the text colour, so it reads as filled in.
    // data-empty mutes it like a placeholder until a date is picked.
    const dateInputs = form.querySelectorAll('input[type="date"]');
    const syncEmptyDates = () => dateInputs.forEach((input) => input.toggleAttribute('data-empty', input.value === ''));
    dateInputs.forEach((input) => ['input', 'change'].forEach((type) => input.addEventListener(type, syncEmptyDates)));
    syncEmptyDates();

    const markInvalid = (fields) => {
        form.querySelectorAll('[aria-invalid]').forEach((input) => input.removeAttribute('aria-invalid'));
        fields.forEach((name) => form.elements.namedItem(name)?.setAttribute('aria-invalid', 'true'));
        if (fields.length) form.elements.namedItem(fields[0])?.focus();
    };

    const showError = (fields, fallback) => {
        const details = fields.map((name) => messages.fields?.[name]).filter(Boolean);
        error.textContent = details.length ? `${messages.error} ${details.join(' ')}` : fallback;
        error.classList.remove('hidden');
        markInvalid(fields);
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        success.classList.add('hidden');
        error.classList.add('hidden');

        const invalid = [...form.elements].filter((input) => input.name && input.willValidate && !input.checkValidity()).map((input) => input.name);
        if (invalid.length) {
            showError(invalid, messages.error);
            return;
        }

        const recaptchaToken = requiresRecaptcha ? window.grecaptcha?.getResponse() ?? '' : null;
        if (requiresRecaptcha && !recaptchaToken) {
            showError([], messages.captcha);
            return;
        }

        const data = new FormData(form);
        const body = { form_type: 'demo', consent: data.get('consent') === '1' };
        ['name', 'company', 'whatsapp', 'email', 'guest_estimate', 'event_date', 'interested_plan', 'message'].forEach((key) => {
            const value = String(data.get(key) ?? '').trim();
            if (value) body[key] = value;
        });
        if (recaptchaToken) body.recaptcha_token = recaptchaToken;

        submit.disabled = true;
        submit.textContent = messages.sending;
        try {
            const response = await fetch(form.dataset.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(body),
            });
            if (response.ok) {
                form.reset();
                syncEmptyDates();
                markInvalid([]);
                window.grecaptcha?.reset();
                success.classList.remove('hidden');
                success.focus?.();
            } else {
                const payload = await response.json().catch(() => ({}));
                showError(Object.keys(payload.errors ?? {}), messages.error);
            }
        } catch {
            showError([], messages.error);
        } finally {
            submit.disabled = false;
            submit.textContent = submitLabel;
        }
    });
});

/**
 * "Copy link" share button on articles.
 */
document.querySelectorAll('[data-copy-link]').forEach((button) => {
    const status = button.parentElement.querySelector('[data-copy-link-status]');
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copyLink);
            if (status) status.textContent = button.dataset.copiedLabel;
        } catch {
            window.prompt('', button.dataset.copyLink);
        }
    });
});
