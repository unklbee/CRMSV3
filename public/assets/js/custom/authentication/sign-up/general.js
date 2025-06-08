"use strict";

// Class definition
var KTSignupGeneral = function() {
    // Elements
    var form;
    var submitButton;
    var validator;
    var passwordMeter;

    // Handle form
    var handleForm = function(e) {
        // Init form validation rules
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'first_name': {
                        validators: {
                            notEmpty: {
                                message: 'First name is required'
                            },
                            stringLength: {
                                min: 2,
                                max: 50,
                                message: 'First name must be between 2 and 50 characters'
                            },
                            regexp: {
                                regexp: /^[a-zA-Z\s]+$/,
                                message: 'First name can only contain letters and spaces'
                            }
                        }
                    },
                    'last_name': {
                        validators: {
                            notEmpty: {
                                message: 'Last name is required'
                            },
                            stringLength: {
                                min: 2,
                                max: 50,
                                message: 'Last name must be between 2 and 50 characters'
                            },
                            regexp: {
                                regexp: /^[a-zA-Z\s]+$/,
                                message: 'Last name can only contain letters and spaces'
                            }
                        }
                    },
                    'username': {
                        validators: {
                            notEmpty: {
                                message: 'Username is required'
                            },
                            stringLength: {
                                min: 3,
                                max: 50,
                                message: 'Username must be between 3 and 50 characters'
                            },
                            regexp: {
                                regexp: /^[a-zA-Z0-9._-]+$/,
                                message: 'Username can only contain letters, numbers, dots, underscores, and hyphens'
                            }
                        }
                    },
                    'email': {
                        validators: {
                            notEmpty: {
                                message: 'Email address is required'
                            },
                            emailAddress: {
                                message: 'The value is not a valid email address'
                            },
                            stringLength: {
                                max: 100,
                                message: 'Email cannot exceed 100 characters'
                            }
                        }
                    },
                    'password': {
                        validators: {
                            notEmpty: {
                                message: 'The password is required'
                            },
                            stringLength: {
                                min: 8,
                                max: 255,
                                message: 'Password must be at least 8 characters long'
                            },
                            regexp: {
                                regexp: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/,
                                message: 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'
                            }
                        }
                    },
                    'password_confirm': {
                        validators: {
                            notEmpty: {
                                message: 'Please confirm your password'
                            },
                            identical: {
                                compare: function() {
                                    return form.querySelector('[name="password"]').value;
                                },
                                message: 'Password confirmation does not match'
                            }
                        }
                    },
                    'phone': {
                        validators: {
                            // Phone is optional, only validate if not empty
                            callback: {
                                message: 'Please enter a valid phone number',
                                callback: function(input) {
                                    if (input.value === '') {
                                        return true; // Optional field
                                    }
                                    // Basic phone validation - adjust regex as needed
                                    return /^[+]?[\d\s\-\(\)]+$/.test(input.value);
                                }
                            },
                            stringLength: {
                                max: 20,
                                message: 'Phone number cannot exceed 20 characters'
                            }
                        }
                    },
                    'terms': {
                        validators: {
                            choice: {
                                min: 1,
                                message: 'You must accept the terms and conditions'
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
                    }),
                    icon: new FormValidation.plugins.Icon({
                        valid: 'fa fa-check',
                        invalid: 'fa fa-times',
                        validating: 'fa fa-refresh'
                    })
                }
            }
        );

        // Handle form submit
        submitButton.addEventListener('click', function (e) {
            e.preventDefault();

            // Clear previous alerts
            hideAlert();

            validator.validate().then(function (status) {
                if (status == 'Valid') {
                    // Show loading indication
                    submitButton.setAttribute('data-kt-indicator', 'on');
                    submitButton.disabled = true;

                    // Prepare form data
                    var formData = new FormData(form);

                    // Add CSRF token using existing CSRFManager
                    if (window.CSRFManager && typeof window.CSRFManager.addToFormData === 'function') {
                        window.CSRFManager.addToFormData(formData);
                    }

                    // Submit form via AJAX
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
                                // Update CSRF tokens if provided
                                if (data.csrf_token && data.csrf_hash) {
                                    updateCSRFTokens(data.csrf_token, data.csrf_hash);
                                }

                                // Show success message
                                showAlert('success', data.message || 'Registration successful!');

                                // Reset form
                                form.reset();
                                validator.resetForm();

                                // Reset password meter
                                if (passwordMeter) {
                                    passwordMeter.reset();
                                }

                                // Redirect after delay
                                setTimeout(function() {
                                    if (data.redirect) {
                                        window.location.href = data.redirect;
                                    } else {
                                        window.location.href = form.getAttribute('data-kt-redirect-url');
                                    }
                                }, 2000);

                            } else {
                                // Handle validation errors
                                if (data.errors) {
                                    handleValidationErrors(data.errors);
                                }

                                // Show error message
                                showAlert('danger', data.message || 'Registration failed. Please check your input and try again.');

                                // Update CSRF tokens if provided
                                if (data.csrf_token && data.csrf_hash) {
                                    updateCSRFTokens(data.csrf_token, data.csrf_hash);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Registration error:', error);

                            // Hide loading indication
                            submitButton.removeAttribute('data-kt-indicator');
                            submitButton.disabled = false;

                            showAlert('danger', 'An unexpected error occurred. Please try again.');
                        });
                } else {
                    showAlert('warning', 'Please correct the errors below and try again.');
                }
            });
        });

        // Revalidate password confirmation when password changes
        form.querySelector('[name="password"]').addEventListener('input', function() {
            if (validator.getElements().password_confirm.length > 0) {
                validator.revalidateField('password_confirm');
            }
        });
    }

    // Handle password meter
    var handlePasswordMeter = function() {
        if (!passwordMeter) {
            return;
        }

        passwordMeter.on('kt.passwordmeter.score', function(meter) {
            if (meter.getScore() > 50) {
                validator.updateValidatorOption('password', 'callback', 'callback', function() {
                    return meter.getScore() > 50;
                });
            }
        });
    }

    // Show alert
    var showAlert = function(type, message) {
        var alertElement = document.querySelector('#alert-message');
        var alertText = document.querySelector('#alert-text');

        if (alertElement && alertText) {
            alertElement.className = `alert alert-${type} alert-dismissible`;
            alertText.textContent = message;
            alertElement.classList.remove('d-none');

            // Auto hide success alerts
            if (type === 'success') {
                setTimeout(function() {
                    alertElement.classList.add('d-none');
                }, 5000);
            }
        }
    }

    // Hide alert
    var hideAlert = function() {
        var alertElement = document.querySelector('#alert-message');
        if (alertElement) {
            alertElement.classList.add('d-none');
        }
    }

    // Handle validation errors
    var handleValidationErrors = function(errors) {
        // Clear previous errors
        form.querySelectorAll('.invalid-feedback').forEach(function(element) {
            element.textContent = '';
        });
        form.querySelectorAll('.is-invalid').forEach(function(element) {
            element.classList.remove('is-invalid');
        });

        // Display new errors
        Object.keys(errors).forEach(function(field) {
            var input = form.querySelector(`[name="${field}"]`);
            var errorElement = form.querySelector(`#${field}-error`);

            if (input && errorElement) {
                input.classList.add('is-invalid');
                errorElement.textContent = errors[field];
            }
        });
    }

    // Update CSRF tokens using existing CSRFManager
    var updateCSRFTokens = function(tokenName, tokenHash) {
        if (window.CSRFManager && typeof window.CSRFManager.updateToken === 'function') {
            window.CSRFManager.updateToken(tokenName, tokenHash);
        }
    }

    // Public functions
    return {
        // Public functions
        init: function() {
            form = document.querySelector('#kt_sign_up_form');
            submitButton = document.querySelector('#kt_sign_up_submit');
            passwordMeter = KTPasswordMeter.getInstance(form.querySelector('[data-kt-password-meter="true"]'));

            if (!form || !submitButton) {
                console.error('Signup form elements not found');
                return;
            }

            handleForm();
            handlePasswordMeter();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function() {
    KTSignupGeneral.init();
});