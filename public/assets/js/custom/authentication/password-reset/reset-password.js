"use strict";

// Class Definition
const KTResetPassword = function() {
    // Elements
    let form;
    let submitButton;
    let validator;
    let passwordMeter;

    // Handle form
    const handleValidation = function() {
        // Init form validation rules
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'password': {
                        validators: {
                            notEmpty: {
                                message: 'The password is required'
                            },
                            stringLength: {
                                min: 8,
                                message: 'The password must be at least 8 characters long'
                            },
                            callback: {
                                message: 'Please enter a strong password',
                                callback: function(input) {
                                    if (input.value.length > 0) {
                                        return validatePassword(input.value);
                                    }
                                }
                            }
                        }
                    },
                    'password_confirm': {
                        validators: {
                            notEmpty: {
                                message: 'The password confirmation is required'
                            },
                            identical: {
                                compare: function() {
                                    return form.querySelector('[name="password"]').value;
                                },
                                message: 'The password and its confirm are not the same'
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

    const handlePasswordMeter = function() {
        // Initialize password meter
        passwordMeter = KTPasswordMeter.getInstance(form.querySelector('[data-kt-password-meter="true"]'));
    };

    const validatePassword = function(password) {
        // At least 8 characters
        if (password.length < 8) {
            return false;
        }

        // At least one uppercase letter
        if (!/[A-Z]/.test(password)) {
            return false;
        }

        // At least one lowercase letter
        if (!/[a-z]/.test(password)) {
            return false;
        }

        // At least one digit
        if (!/\d/.test(password)) {
            return false;
        }

        // At least one special character
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
            return false;
        }

        return true;
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
                    Swal.fire({
                        text: data.message || "Password has been reset successfully!",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            // Redirect to login
                            window.location.href = data.redirect || '/auth/signin';
                        }
                    });

                } else {
                    // Show error message
                    let errorMessage = data.message || 'Password reset failed. Please try again.';

                    Swal.fire({
                        text: errorMessage,
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });

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

                Swal.fire({
                    text: "Sorry, looks like there are some errors detected, please try again.",
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, got it!",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
            });
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
            form = document.querySelector('#kt_reset_password_form');
            submitButton = document.querySelector('#kt_reset_password_submit');

            if (!form || !submitButton) {
                return;
            }

            handleValidation();
            handlePasswordMeter();
            handleSubmit();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function() {
    KTResetPassword.init();
});