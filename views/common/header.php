<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <meta charset="utf-8" />
    <title><?= empty($title) ? env('WEB_TITLE') : $title; ?></title>
    <meta name="description" content="<?= env('WEB_DESCRIPTION'); ?>" />
    <meta name="keywords" content="<?= env('WEB_KEYWORDS'); ?>" />
    <meta name="viewport" content="<?= env('WEB_VIEWPORT'); ?>" />
    <link rel="shortcut icon" href="<?= env('WEB_ICON'); ?>" />
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    <link href="/assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <?= (basename($_SERVER['PHP_SELF']) == "deal") ? '<link href="assets/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css" />' : '' ; ?>    
    <? include_once(env('APP_ROOT_DIR') . "/static/headerscripts.php"); ?>
    <script src="https://unpkg.com/autonumeric"></script>
    <!--end::Global Stylesheets Bundle-->
</head>
<!--end::Head-->
<!--begin::Body-->

<body 
    id="kt_body" 
    style="<?= $hideBackground ?? env('WEB_BACKGROUND'); ?>" 
    class="<?= $class ?? 'header-fixed header-tablet-and-mobile-fixed toolbar-enabled' ?>">
    <!--[if lt IE 7]>
        <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->