"use strict";

// Class definition
const KTSigninGeneral = function() {
    // Elements
    let form;
    let submitButton;
    let validator;

    const handleSubmit = function(e) {
        // Handle form submit
        validator.validate().then(function (status) {
            if (status === 'Valid') {
                // Show loading indication
                submitButton.setAttribute('data-kt-indicator', 'on');
                submitButton.disabled = true;

                // Submit form via AJAX
                submitForm();
            }
        });
    };

    const submitForm = function() {
        const formData = new FormData(form);

        // Get CSRF token
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
            .then(response => {
                // Hide loading indication
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;

                return response.json();
            })
            .then(data => {
                console.log('Login response:', data); // Debug log

                if (data.success) {
                    // Update CSRF tokens if provided
                    if (data.csrf_token && data.csrf_hash) {
                        updateCSRFTokens(data.csrf_token, data.csrf_hash);
                    }

                    // Show success message
                    Swal.fire({
                        text: data.message || "Login successful!",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            // Redirect to dashboard or specified URL
                            const redirectUrl = data.redirect || '/dashboard';
                            console.log('Redirecting to:', redirectUrl); // Debug log
                            window.location.href = redirectUrl;
                        }
                    });

                    // Alternative: Direct redirect without SweetAlert (uncomment if needed)
                    // setTimeout(() => {
                    //     const redirectUrl = data.redirect || '/dashboard';
                    //     console.log('Redirecting to:', redirectUrl);
                    //     window.location.href = redirectUrl;
                    // }, 1000);

                } else {
                    // Show error message
                    let errorMessage = data.message || 'Login failed. Please try again.';

                    // Add remaining attempts info if available
                    if (data.remaining_attempts !== undefined) {
                        errorMessage += ` (${data.remaining_attempts} attempts remaining)`;
                    }

                    Swal.fire({
                        text: errorMessage,
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });

                    // Update CSRF tokens even on error
                    if (data.csrf_token && data.csrf_hash) {
                        updateCSRFTokens(data.csrf_token, data.csrf_hash);
                    }

                    // Show validation errors if available
                    if (data.errors) {
                        handleValidationErrors(data.errors);
                    }
                }
            })
            .catch(error => {
                console.error('Login error:', error);

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

    const handleValidationErrors = function(errors) {
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });

        // Show new errors
        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);
            const errorDiv = document.querySelector(`#${field}-error`);

            if (input) {
                input.classList.add('is-invalid');
            }

            if (errorDiv) {
                errorDiv.textContent = errors[field];
            }
        });
    };

    // Update CSRF tokens
    const updateCSRFTokens = function(tokenName, tokenValue) {
        // Update meta tag
        let csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            csrfMeta.setAttribute('content', tokenValue);
            csrfMeta.setAttribute('name', tokenName);
        }

        // Update any hidden form inputs
        document.querySelectorAll(`input[name="${tokenName}"]`).forEach(input => {
            input.value = tokenValue;
        });
    };

    // Public functions
    return {
        // Initialization
        init: function() {
            form = document.querySelector('#kt_sign_in_form');
            submitButton = document.querySelector('#kt_sign_in_submit');

            if (!form || !submitButton) {
                console.error('Login form or submit button not found');
                return;
            }

            // Initialize form validation
            validator = FormValidation.formValidation(
                form,
                {
                    fields: {
                        'identifier': {
                            validators: {
                                regexp: {
                                    regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$|^[a-zA-Z0-9_]+$/,
                                    message: 'Please enter a valid email or username',
                                },
                                notEmpty: {
                                    message: 'Email or username is required'
                                }
                            }
                        },
                        'password': {
                            validators: {
                                notEmpty: {
                                    message: 'Password is required'
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

            // Handle form submit
            submitButton.addEventListener('click', function (e) {
                e.preventDefault();
                handleSubmit();
            });

            // Handle Enter key
            form.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleSubmit();
                }
            });
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function() {
    KTSigninGeneral.init();
});