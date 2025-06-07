"use strict";

// Class definition
var KTSigninGeneral = function () {
    // Elements
    var form;
    var submitButton;
    var validator;

    var handleValidation = function (e) {
        // Init form validation rules. For more info check the FormValidation plugin's official documentation:
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
    }

    var handleSubmitDemo = function (e) {
        // Handle form submit
        submitButton.addEventListener('click', function (e) {
            // Prevent default button action
            e.preventDefault();

            // Validate form before submit
            if (validator) {
                validator.validate().then(function (status) {
                    console.log('validated!');

                    if (status == 'Valid') {
                        // Show loading indication
                        submitButton.setAttribute('data-kt-indicator', 'on');

                        // Disable button to avoid multiple click
                        submitButton.disabled = true;

                        // Submit form via AJAX
                        submitForm();
                    }
                });
            }
        });
    }

    var submitForm = function() {
        var formData = new FormData(form);

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

                // Enable button
                submitButton.disabled = false;

                if (data.success) {
                    // Show success message
                    Swal.fire({
                        text: data.message,
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            // Redirect to dashboard or specified URL
                            form.querySelector('[name="identifier"]').value = "";
                            form.querySelector('[name="password"]').value = "";

                            // Redirect
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href = "/dashboard";
                            }
                        }
                    });
                } else {
                    // Show error message
                    var errorMessage = data.message || 'An error occurred during login';

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
                }
            })
            .catch(error => {
                console.error('Error:', error);

                // Hide loading indication
                submitButton.removeAttribute('data-kt-indicator');

                // Enable button
                submitButton.disabled = false;

                // Show error message
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
    }

    var showFieldError = function(fieldName, message) {
        var field = form.querySelector('[name="' + fieldName + '"]');
        if (field) {
            field.classList.add('is-invalid');
            var errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }
    }

    var clearErrors = function() {
        var errorElements = form.querySelectorAll('.is-invalid');
        errorElements.forEach(function(element) {
            element.classList.remove('is-invalid');
        });

        var errorDivs = form.querySelectorAll('.invalid-feedback');
        errorDivs.forEach(function(div) {
            div.style.display = 'none';
            div.textContent = '';
        });
    }

    // Public functions
    return {
        // Initialization
        init: function () {
            form = document.querySelector('#kt_sign_in_form');
            submitButton = document.querySelector('#kt_sign_in_submit');

            if (form) {
                handleValidation();
                handleSubmitDemo();
            }
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTSigninGeneral.init();
});