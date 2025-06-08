"use strict";

// Class Definition
const KTPasswordReset = function() {
    // Elements
    let form;
    let submitButton;
    let validator;

    // Handle form
    const handleValidation = function() {
        // Init form validation rules
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'email': {
                        validators: {
                            notEmpty: {
                                message: 'Email address is required'
                            },
                            emailAddress: {
                                message: 'The value is not a valid email address'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                    })
                }
            }
        );
    };

    const handleSubmit = function() {
        // Handle form submit
        submitButton.addEventListener('click', function (e) {
            // Prevent default button action
            e.preventDefault();

            // Validate form before submit
            if (validator) {
                validator.validate().then(function (status) {
                    if (status == 'Valid') {
                        // Show loading indication
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        // Submit form via AJAX
                        submitForm();
                    }
                });
            }
        });
    };

    const submitForm = function() {
        const formData = new FormData(form);

        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            formData.append(csrfToken.getAttribute('name'), csrfToken.getAttribute('content'));
        }

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                // Hide loading indication
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;

                if (data.success) {
                    // Show success message
                    showAlert('success', data.message || 'Reset link has been sent to your email!');

                    // Clear form
                    form.reset();

                    // Optionally redirect after delay
                    setTimeout(() => {
                        window.location.href = data.redirect || '/auth/signin';
                    }, 3000);

                } else {
                    // Show error message
                    showAlert('danger', data.message || 'Failed to send reset link. Please try again.');

                    // Update CSRF token if provided
                    if (data.csrf_token && data.csrf_hash) {
                        updateCSRFToken(data.csrf_token, data.csrf_hash);
                    }

                    // Show field errors if any
                    if (data.errors) {
                        showFieldErrors(data.errors);
                    }
                }
            })
            .catch(error => {
                console.error('Password reset error:', error);

                // Hide loading indication
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;

                showAlert('danger', 'Sorry, looks like there are some errors detected, please try again.');
            });
    };

    const showAlert = function(type, message) {
        const alertElement = document.getElementById('alert-message');
        const alertText = document.getElementById('alert-text');

        if (alertElement && alertText) {
            alertElement.className = `alert alert-${type}`;
            alertText.textContent = message;
            alertElement.classList.remove('d-none');

            // Auto hide after 5 seconds
            setTimeout(() => {
                alertElement.classList.add('d-none');
            }, 5000);
        }
    };

    const updateCSRFToken = function(token, hash) {
        // Update CSRF meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            csrfToken.setAttribute('content', hash);
        }

        // Update CSRF hidden input
        const csrfInput = form.querySelector('input[name="' + token + '"]');
        if (csrfInput) {
            csrfInput.value = hash;
        }
    };

    const showFieldErrors = function(errors) {
        // Clear previous errors
        form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });

        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Show new errors
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            const errorDiv = form.querySelector(`#${field}-error`);

            if (input) {
                input.classList.add('is-invalid');
            }

            if (errorDiv) {
                errorDiv.textContent = errors[field];
                errorDiv.style.display = 'block';
            }
        });
    };

    // Public Functions
    return {
        // Initialization
        init: function() {
            form = document.querySelector('#kt_forgot_password_form');
            submitButton = document.querySelector('#kt_forgot_password_submit');

            if (!form || !submitButton) {
                return;
            }

            handleValidation();
            handleSubmit();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function() {
    KTPasswordReset.init();
});