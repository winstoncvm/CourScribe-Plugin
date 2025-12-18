<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Curriculum Header Component
 * Handles CSS/JS dependencies and page header elements
 */
function courscribe_render_curriculum_header($site_url) {
    ?>
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/tabs.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.1.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/studio.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/nucleo-icons.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/nucleo-svg.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/soft-ui-dashboard.css?v=1.0.7">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@recogito/annotorious@2.7.12/dist/annotorious.min.css">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@recogito/annotorious@2.7.12/dist/annotorious.min.js"></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/popper.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/perfect-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/smooth-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js?v=1.0.7" defer></script>
    <script src="<?php echo $site_url; ?>/wp-content/plugins/courscribe-dashboard/assets/js/pdfme.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="http://SortableJS.github.io/Sortable/Sortable.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-sortablejs@latest/jquery-sortable.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@nightvisi0n/pdfme-generator@1.0.14-12/dist/index.min.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/froala-editor@latest/css/froala_editor.pkgd.min.css' rel='stylesheet' type='text/css' />
    <script type='text/javascript' src='https://cdn.jsdelivr.net/npm/froala-editor@latest/js/froala_editor.pkgd.min.js'></script>
    <script type="text/javascript" src='<?php echo $site_url; ?>/wp-content/plugins/courscribe/assets/js/richtexteditor/rte.js'></script>
    <script>
    RTE_DefaultConfig.url_base = '<?php echo $site_url; ?>/wp-content/plugins/courscribe/assets/js/richtexteditor'
    </script>
    <script type="text/javascript" src='<?php echo $site_url; ?>/wp-content/plugins/courscribe/assets/js/richtexteditor/plugins/all_plugins.js'></script>
    <script src="https://unpkg.com/@sjmc11/tourguidejs/dist/tour.js" crossorigin="anonymous" referrerpolicy="no-referrer" type="module"></script>
    <link rel="stylesheet" href="https://unpkg.com/@sjmc11/tourguidejs/dist/css/tour.min.css">

    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/courscribe/feedback.js"></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/courscribe/html2canvas.js" defer></script>
    <?php
}

/**
 * Render Custom Styles for Curriculum Page
 */
function courscribe_render_curriculum_styles() {
    ?>
    <style>
        .tg-dialog {background-color:#2a2a2b}
        .tg-dialog {background-color:#2a2a2b}
        /* Floating Help Button Styles */
        .courscribe-help-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #665442;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            font-size: 24px;
            z-index: 1000;
            transition: background-color 0.3s;
        }
        .courscribe-help-toggle:hover {
            background-color: #E4B26F;
        }
        /* Tour Guide Custom Styles */
        .tg-dialog {
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #2a2a2b 0%, #353535 100%);
            color: #fff;
        }
        .tg-dialog .tg-dialog-title {
            color: #E4B26F;
            font-weight: 600;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .tg-dialog .tg-dialog-body {
            line-height: 1.6;
            color: #e0e0e0;
        }
        .tg-dialog .tg-dialog-footer {
            border-top: 1px solid #444;
            padding-top: 15px;
            margin-top: 15px;
        }
        .tg-dialog .tg-dialog-btn {
            background: linear-gradient(90deg, #F8923E 3.57%, #F25C3B 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        .tg-dialog .tg-dialog-btn:hover {
            opacity: 0.9;
        }
        .tg-dialog .tg-dialog-btn.tg-dialog-btn-secondary {
            background: transparent;
            border: 1px solid #666;
            color: #ccc;
        }
        .tg-dialog .tg-dialog-close {
            color: #999;
            font-size: 18px;
        }
        .tg-dialog .tg-dialog-close:hover {
            color: #fff;
        }
        .tg-overlay {
            background: rgba(0, 0, 0, 0.7);
        }
        .tg-highlight {
            border: 2px solid #E4B26F !important;
            box-shadow: 0 0 0 4px rgba(228, 178, 111, 0.3) !important;
        }
        .tg-dialog .tg-dialog-dots>span.tg-dot {
            background: #666;
            border: none;
        }
        .tg-dialog .tg-dialog-dots>span.tg-dot svg {
            fill: #fff;
        }
        .tg-dialog .tg-dialog-dots>span.tg-dot.tg-dot-active {
            background: #E4B26F;
        }
    </style>
    <?php
}

/**
 * Render Help Button
 */
function courscribe_render_help_button() {
    ?>
    <!-- Floating Help Button -->
    <button class="courscribe-help-toggle-single" title="Start Guided Tour">
        <i class="fa fa-question"></i>
    </button>
    <?php
}