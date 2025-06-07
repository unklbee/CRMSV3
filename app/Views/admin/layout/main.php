<!DOCTYPE html>
<html lang="id">
<!--begin::Head-->
<head>
    <title>Dashboard</title>
    <meta charset="utf-8"/>
    <meta name="description" content="CRMV3"/>
    <meta name="keywords" content="CRMS"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
    <meta property="og:locale" content="id"/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content="CRMS"/>
    <meta property="og:url" content="https://keenthemes.com/metronic"/>
    <meta property="og:site_name" content="Metronic by Keenthemes"/>
    <link rel="canonical" href="http://preview.keenthemes.comindex.html"/>
    <link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>"/>
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"/>
    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->

    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css"/>
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css"/>
    <!--end::Global Stylesheets Bundle-->
    <script>// Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
</head>
<!--end::Head-->
<!--begin::Body-->
<body id="kt_body" class="aside-enabled">
<!--begin::Theme mode setup on page load-->
<?= view('admin/layout/partials/theme_mode') ?>
<!--end::Theme mode setup on page load-->
<!--begin::Main-->
<!--begin::Root-->
<div class="d-flex flex-column flex-root">
    <!--begin::Page-->
    <div class="page d-flex flex-row flex-column-fluid">
        <!--begin::Aside-->
        <?= view('admin/layout/partials/aside') ?>
        <!--end::Aside-->
        <!--begin::Wrapper-->
        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
            <!--begin::Header-->
            <?= view('admin/layout/partials/header') ?>
            <!--end::Header-->
            <!--begin::Content-->
            <?= $this->renderSection('content') ?>
            <!--end::Content-->
            <!--begin::Footer-->
            <?= view('admin/layout/partials/footer') ?>
            <!--end::Footer-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Page-->
</div>
<!--end::Root-->

<!--end::Main-->
<!--begin::Scrolltop-->
<?= view('admin/layout/partials/scrolltop') ?>
<!--end::Scrolltop-->

<!--begin::Javascript-->
<script>
    // CSRF Configuration
    window.AppConfig = {
        baseUrl: '<?= base_url() ?>',
        csrf: {
            token: '<?= csrf_token() ?>',
            hash: '<?= csrf_hash() ?>',
            headerName: '<?= csrf_header() ?>'
        }
    };
</script>

<script>const hostUrl = "assets/";</script>
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
<script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
<!--end::Global Javascript Bundle-->

<!--begin::Custom Javascript(used for this page only)-->
<?= $this->renderSection('scripts') ?>
<!--end::Custom Javascript-->
<!--end::Javascript-->
</body>
<!--end::Body-->
</html>