(function () {
    'use strict';

    function escapeSelector(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/["\\]/g, '\\$&');
    }

    function markControl(control) {
        if (!control || control.type === 'hidden' || control.disabled) {
            return;
        }

        if (control.type === 'radio' || control.type === 'checkbox') {
            const field = control.closest('.xform-field');
            if (field) {
                field.classList.add('is-required-group');
            }
            return;
        }

        control.classList.add('is-required');
    }

    function markConfiguredFields(form) {
        let fields = {};

        try {
            fields = JSON.parse(form.dataset.requiredFields || '{}');
        } catch (error) {
            console.error('Could not read admission required fields.', error);
        }

        Object.keys(fields).forEach(function (fieldName) {
            if (!fields[fieldName]) {
                return;
            }

            const escapedName = escapeSelector(fieldName);
            form.querySelectorAll(
                '[name="' + escapedName + '"], [data-required-field="' + escapedName + '"]'
            ).forEach(markControl);
        });
    }

    function markAllForms(scope) {
        (scope || document).querySelectorAll('form.xform').forEach(function (form) {
            form.querySelectorAll('[required]').forEach(markControl);
            markConfiguredFields(form);
        });
    }

    markAllForms(document);

    window.markAdmissionRequiredFields = markAllForms;
})();
