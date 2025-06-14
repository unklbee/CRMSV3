<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->
<head>
    <base href="<?= site_url('/') ?>"/>
    <title>Optiontech</title>
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
                <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Fast, Efficient and Productive</h1>
                <!--end::Title-->
                <!--begin::Text-->
                <div class="text-gray-600 fs-base text-center fw-semibold">In this kind of post,
                    <a href="#" class="opacity-75-hover text-primary me-1">the blogger</a>introduces a person they’ve
                    interviewed
                    <br/>and provides some background information about
                    <a href="#" class="opacity-75-hover text-primary me-1">the interviewee</a>and their
                    <br/>work following this is a transcript of the interview.
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
                        <!--begin::Form-->
                        <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form"
                              data-kt-redirect-url="index.html" action="<?= site_url('auth/processLogin') ?>" method="POST">
                            <?= csrf_field() ?>
                            <!--begin::Heading-->
                            <div class="text-center mb-11">
                                <!--begin::Title-->
                                <h1 class="text-gray-900 fw-bolder mb-3">Masuk</h1>
                                <!--end::Title-->
                                <!--begin::Subtitle-->
                                <div class="text-gray-500 fw-semibold fs-6">Your Social Campaigns</div>
                                <!--end::Subtitle=-->
                            </div>
                            <!--begin::Heading-->
                            <!--begin::Input group=-->
                            <div class="fv-row mb-8">
                                <input type="text" class="form-control bg-transparent" id="identifier"
                                        name="identifier" placeholder="Enter your username or email" required>
                                <div class="invalid-feedback" id="identifier-error"></div>
                            </div>
                            <!--end::Input group=-->
                            <div class="fv-row mb-3">
                                <input type="password" class="form-control bg-transparent"
                                        id="password" name="password" laceholder="Enter your password" required>
                                <div class="invalid-feedback" id="password-error"></div>
                            </div>
                            <!--end::Input group=-->

                            <!--begin::Wrapper-->
                            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                                <!--begin::Link-->
                                <a href="<?= base_url('auth/forgot-password') ?>" class="link-primary">Forgot
                                    Password ?</a>
                                <!--end::Link-->
                            </div>
                            <!--end::Wrapper-->
                            <!--begin::Submit button-->
                            <div class="d-grid mb-10">
                                <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                                    <!--begin::Indicator label-->
                                    <span class="indicator-label">Sign In</span>
                                    <!--end::Indicator label-->
                                    <!--begin::Indicator progress-->
                                    <span class="indicator-progress">Tunggu ya...
											<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    <!--end::Indicator progress-->
                                </button>
                            </div>
                            <!--end::Submit button-->
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Footer-->
                    <div class="d-flex flex-stack">
                        <!--begin::Links-->
                        <div class="d-flex fw-semibold text-primary fs-base gap-5">
                            <a href="<?= site_url('/')?>" target="_blank">Kunjungi Website</a>
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
<script src="<?= base_url('assets/js/custom/authentication/sign-in/general.js') ?>"></script>

<!--end::Custom Javascript-->
<!--end::Javascript-->
</body>
<!--end::Body-->
</html>