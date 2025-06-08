<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->
<head>
    <base href="<?= site_url('/') ?>"/>
    <title>Sign Up - Optiontech</title>
    <link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>"/>
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"/>
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css"/>
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css"/>
    <!--end::Global Stylesheets Bundle-->
    <script>// Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
</head>
<body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center">

<script>const defaultThemeMode = "light";
    let themeMode;
    if (document.documentElement) {
        if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
            themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
        } else {
            if (localStorage.getItem("data-bs-theme") !== null) {
                themeMode = localStorage.getItem("data-bs-theme");
            } else {
                themeMode = defaultThemeMode;
            }
        }
        if (themeMode === "system") {
            themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    }</script>

<div class="d-flex flex-column flex-root">
    <style>
        body {
            background-image: url('<?= base_url('assets/media/auth/bg10.jpeg') ?>');
        }

        [data-bs-theme="dark"] body {
            background-image: url('<?= base_url('assets/media/auth/bg10-dark.jpeg') ?>');
        }
    </style>

    <div class="d-flex flex-column flex-lg-row flex-column-fluid">

        <div class="d-flex flex-lg-row-fluid">
            <!--begin::Content-->
            <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
                <!--begin::Image-->
                <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20"
                     src="<?= base_url('assets/media/auth/agency.png') ?>" alt=""/>
                <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20"
                     src="<?= base_url('assets/media/auth/agency-dark.png') ?>" alt=""/>
                <!--end::Image-->
                <!--begin::Title-->
                <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Join Our Platform</h1>
                <!--end::Title-->
                <!--begin::Text-->
                <div class="text-gray-600 fs-base text-center fw-semibold">Create your account to access
                    <a href="#" class="opacity-75-hover text-primary me-1">all our services</a>and manage
                    <br/>your requests efficiently with our
                    <a href="#" class="opacity-75-hover text-primary me-1">professional team</a>
                    <br/>Get started in just a few minutes.
                </div>
                <!--end::Text-->
            </div>
            <!--end::Content-->
        </div>

        <!--begin::Body-->
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-info">
                <?= session()->getFlashdata('message') ?>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
            <!--begin::Wrapper-->
            <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10">
                <!--begin::Content-->
                <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20">

                        <!--begin::Alert-->
                        <div class="alert alert-dismissible d-none" id="alert-message">
                            <div class="alert-text" id="alert-text"></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <!--end::Alert-->

                        <!--begin::Form-->
                        <form class="form w-100" novalidate="novalidate" id="kt_sign_up_form"
                              data-kt-redirect-url="<?= site_url('auth/signin') ?>"
                              action="<?= site_url('auth/processRegister') ?>" method="POST">
                            <?= csrf_field() ?>
                            <!--begin::Heading-->
                            <div class="text-center mb-11">
                                <!--begin::Title-->
                                <h1 class="text-gray-900 fw-bolder mb-3">Create Account</h1>
                                <!--end::Title-->
                                <!--begin::Subtitle-->
                                <div class="text-gray-500 fw-semibold fs-6">Already have an account?
                                    <a href="<?= site_url('auth/signin') ?>" class="link-primary fw-bold">Sign in here</a>
                                </div>
                                <!--end::Subtitle=-->
                            </div>
                            <!--begin::Heading-->

                            <!--begin::Input group=-->
                            <div class="row fv-row mb-7">
                                <!--begin::Col-->
                                <div class="col-xl-6">
                                    <input class="form-control bg-transparent" type="text" placeholder="First Name"
                                           name="first_name" id="first_name" autocomplete="given-name" required/>
                                    <div class="invalid-feedback" id="first_name-error"></div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col-xl-6">
                                    <input class="form-control bg-transparent" type="text" placeholder="Last Name"
                                           name="last_name" id="last_name" autocomplete="family-name" required/>
                                    <div class="invalid-feedback" id="last_name-error"></div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group=-->

                            <!--begin::Input group=-->
                            <div class="fv-row mb-7">
                                <input class="form-control bg-transparent" type="text" placeholder="Username"
                                       name="username" id="username" autocomplete="username" required/>
                                <div class="invalid-feedback" id="username-error"></div>
                            </div>
                            <!--end::Input group=-->

                            <!--begin::Input group=-->
                            <div class="fv-row mb-7">
                                <input class="form-control bg-transparent" type="email" placeholder="Email"
                                       name="email" id="email" autocomplete="email" required/>
                                <div class="invalid-feedback" id="email-error"></div>
                            </div>
                            <!--end::Input group=-->

                            <!--begin::Input group=-->
                            <div class="fv-row mb-7" data-kt-password-meter="true">
                                <!--begin::Wrapper-->
                                <div class="mb-1">
                                    <!--begin::Input wrapper-->
                                    <div class="position-relative mb-3">
                                        <input class="form-control bg-transparent" type="password"
                                               placeholder="Password" name="password" id="password" autocomplete="new-password" required/>
                                        <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2"
                                              data-kt-password-meter-control="visibility">
                                            <i class="ki-duotone ki-eye-slash fs-2"></i>
                                            <i class="ki-duotone ki-eye fs-2 d-none"></i>
                                        </span>
                                    </div>
                                    <!--end::Input wrapper-->
                                    <!--begin::Meter-->
                                    <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                                    </div>
                                    <!--end::Meter-->
                                </div>
                                <!--end::Wrapper-->
                                <!--begin::Hint-->
                                <div class="text-muted">Use 8 or more characters with a mix of letters, numbers & symbols.</div>
                                <!--end::Hint-->
                                <div class="invalid-feedback" id="password-error"></div>
                            </div>
                            <!--end::Input group=-->

                            <!--begin::Input group=-->
                            <div class="fv-row mb-7">
                                <input class="form-control bg-transparent" type="password"
                                       placeholder="Confirm Password" name="password_confirm" id="password_confirm"
                                       autocomplete="new-password" required/>
                                <div class="invalid-feedback" id="password_confirm-error"></div>
                            </div>
                            <!--end::Input group=-->

                            <!--begin::Input group=-->
                            <div class="fv-row mb-7">
                                <input class="form-control bg-transparent" type="tel" placeholder="Phone Number (Optional)"
                                       name="phone" id="phone" autocomplete="tel"/>
                                <div class="invalid-feedback" id="phone-error"></div>
                                <div class="form-text">This field is optional but helps us contact you if needed.</div>
                            </div>
                            <!--end::Input group=-->

                            <!--begin::Accept-->
                            <div class="fv-row mb-8">
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="terms" id="terms" value="1" required/>
                                    <span class="form-check-label fw-semibold text-gray-700 fs-base ms-1">I Accept the
                                        <a href="#" class="ms-1 link-primary">Terms & Conditions</a> and
                                        <a href="#" class="ms-1 link-primary">Privacy Policy</a>
                                    </span>
                                </label>
                                <div class="invalid-feedback" id="terms-error"></div>
                            </div>
                            <!--end::Accept-->

                            <!--begin::Submit button-->
                            <div class="d-grid mb-10">
                                <button type="submit" id="kt_sign_up_submit" class="btn btn-primary">
                                    <!--begin::Indicator label-->
                                    <span class="indicator-label">Create Account</span>
                                    <!--end::Indicator label-->
                                    <!--begin::Indicator progress-->
                                    <span class="indicator-progress">Creating Account...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                    <!--end::Indicator progress-->
                                </button>
                            </div>
                            <!--end::Submit button-->

                            <!--begin::Sign up-->
                            <div class="text-gray-500 text-center fw-semibold fs-6">
                                Already have an Account?
                                <a href="<?= site_url('auth/signin') ?>" class="link-primary fw-semibold">Sign in</a>
                            </div>
                            <!--end::Sign up-->
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Footer-->
                    <div class="d-flex flex-stack">
                        <!--begin::Links-->
                        <div class="d-flex fw-semibold text-primary fs-base gap-5">
                            <a href="<?= site_url('/')?>" target="_blank">Visit Website</a>
                        </div>
                        <!--end::Links-->
                    </div>
                    <!--end::Footer-->
                </div>
                <!--end::Content-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Body-->
    </div>

</div>
<!--end::Root-->
<!--end::Main-->
<!--begin::Javascript-->
<script>const hostUrl = "assets/";</script>
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
<script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
<!--end::Global Javascript Bundle-->
<!--begin::Custom Javascript(used for this page only)-->
<script src="<?= base_url('assets/js/csrf-manager.js') ?>"></script>
<script src="<?= base_url('assets/js/custom/authentication/sign-up/general.js') ?>"></script>
<!--end::Custom Javascript-->
<!--end::Javascript-->
</body>
<!--end::Body-->
</html>