"use strict";

// Class Definition
const KTSigninGeneral = function() {
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
                    'identifier': {
                        validators: {
                            notEmpty: {
                                message: 'Username or Email is required'
                            },
                            stringLength: {
                                min: 3,
                                max: 100,
                                message: 'Username/Email must be between 3 and 100 characters'
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
    };

    const handleSubmitDemo = function() {
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

                        // Disable submit button
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
            .then(response => response.json())
            .then(data => {
                // Hide loading indication
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;

                if (data.success) {
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
                            // Redirect to dashboard
                            window.location.href = data.redirect || '/dashboard';
                        }
                    });
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
            form = document.querySelector('#kt_sign_in_form');
            submitButton = document.querySelector('#kt_sign_in_submit');

            if (!form || !submitButton) {
                return;
            }

            handleValidation();
            handleSubmitDemo();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function() {
    KTSigninGeneral.init();
});