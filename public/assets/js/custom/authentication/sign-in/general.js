"use strict";

// Class definition
var KTSigninGeneral = function () {
    // Elements
    var form;
    var submitButton;
    var validator;

    // Wait for CSRFManager to be available
    var waitForCSRFManager = function() {
        return new Promise((resolve) => {
            if (window.CSRFManager && window.CSRFManager.isValid()) {
                resolve(window.CSRFManager);
                return;
            }

            // Check every 100ms for up to 5 seconds
            let attempts = 0;
            const maxAttempts = 50;

            const checkInterval = setInterval(() => {
                attempts++;

                if (window.CSRFManager && window.CSRFManager.isValid()) {
                    clearInterval(checkInterval);
                    resolve(window.CSRFManager);
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    resolve(null);
                }
            }, 100);
        });
    };

    var handleValidation = function () {
        // Init form validation rules
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'identifier': {
                        validators: {
                            notEmpty: {
                                message: 'Username or Email is required'
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

    var handleSubmitDemo = function () {
        // Handle form submit
        submitButton.addEventListener('click', async function (e) {
            // Prevent default button action
            e.preventDefault();

            // Validate form before submit
            if (validator) {
                const status = await validator.validate();

                if (status === 'Valid') {
                    // Show loading indication
                    submitButton.setAttribute('data-kt-indicator', 'on');

                    // Disable button to avoid multiple click
                    submitButton.disabled = true;

                    // Submit form via AJAX
                    await submitForm();
                }
            }
        });
    };

    var submitForm = async function() {
        try {
            // Wait for CSRF Manager to be ready
            const csrfManager = await waitForCSRFManager();

            if (!csrfManager) {
                throw new Error('CSRF Manager not available');
            }

            // Prepare form data
            const formData = new FormData(form);

            // Add CSRF token using manager
            csrfManager.addToFormData(formData);

            // Submit form
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: csrfManager.getHeaders()
            });

            // Check if response is successful
            if (!response.ok) {
                if (response.status === 403) {
                    throw new Error('CSRF_ERROR');
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Parse JSON response
            const data = await response.json();

            // Update CSRF token if provided
            if (data.csrf_token && data.csrf_hash) {
                csrfManager.updateToken(data.csrf_token, data.csrf_hash);
            }

            // Reset UI
            submitButton.removeAttribute('data-kt-indicator');
            submitButton.disabled = false;

            if (data.success) {
                await handleLoginSuccess(data);
            } else {
                handleLoginError(data);
            }

        } catch (error) {

            // Reset UI
            submitButton.removeAttribute('data-kt-indicator');
            submitButton.disabled = false;

            // Handle CSRF errors
            if (error.message === 'CSRF_ERROR') {
                await handleCSRFError();
            } else {
                showGenericError(error.message);
            }
        }
    };

    var handleCSRFError = async function() {

        try {
            const csrfManager = await waitForCSRFManager();
            if (csrfManager) {
                const recovered = await csrfManager.handleCsrfError();

                if (recovered) {

                    // Show user that we're retrying
                    Swal.fire({
                        title: 'Retrying...',
                        text: 'Please wait while we retry your request.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        timer: 1000
                    }).then(() => {
                        // Retry the request
                        submitForm();
                    });

                    return;
                }
            }
        } catch (error) {
            console.error('Failed to recover from CSRF error:', error);
        }

        // If recovery failed, show error
        Swal.fire({
            title: 'Session Expired',
            text: 'Your session has expired. Please refresh the page and try again.',
            icon: 'warning',
            confirmButtonText: 'Refresh Page',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    };

    var handleLoginSuccess = async function(data) {
        // Show success message
        await Swal.fire({
            text: data.message || 'Login successful!',
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });

        // Clear form
        form.querySelector('[name="identifier"]').value = "";
        form.querySelector('[name="password"]').value = "";

        // Redirect
        const redirectUrl = data.redirect || "/dashboard";
        window.location.href = redirectUrl;
    };

    var handleLoginError = function(data) {
        const errorMessage = data.message || 'An error occurred during login';

        // If there are validation errors
        if (data.errors) {
            // Clear previous errors
            clearErrors();

            // Show field-specific errors
            Object.keys(data.errors).forEach(function(field) {
                showFieldError(field, data.errors[field]);
            });
        } else {
            // Show general error
            Swal.fire({
                text: errorMessage,
                icon: "error",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });
        }
    };

    var showGenericError = function(errorMessage) {
        const message = errorMessage || "Sorry, looks like there are some errors detected, please try again.";

        Swal.fire({
            text: message,
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    };

    var showFieldError = function(fieldName, message) {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.add('is-invalid');
            const errorDiv = document.getElementById(`${fieldName}-error`);
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }
    };

    var clearErrors = function() {
        const errorElements = form.querySelectorAll('.is-invalid');
        errorElements.forEach(function(element) {
            element.classList.remove('is-invalid');
        });

        const errorDivs = form.querySelectorAll('.invalid-feedback');
        errorDivs.forEach(function(div) {
            div.style.display = 'none';
            div.textContent = '';
        });
    };

    // Public functions
    return {
        // Initialization
        init: async function () {
            form = document.querySelector('#kt_sign_in_form');
            submitButton = document.querySelector('#kt_sign_in_submit');

            if (form && submitButton) {

                handleValidation();
                handleSubmitDemo();
            }
        }
    };
}();

// On document ready
if (typeof KTUtil !== 'undefined' && KTUtil.onDOMContentLoaded) {
    KTUtil.onDOMContentLoaded(function () {
        KTSigninGeneral.init();
    });
} else {
    // Fallback if KTUtil is not available
    document.addEventListener('DOMContentLoaded', function() {
        KTSigninGeneral.init();
    });
}