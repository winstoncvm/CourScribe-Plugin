<?php
// Path: templates/template-parts/single-curriculum/curriculum-content.php
if (!defined('ABSPATH')) {
    exit;
}

$site_url = home_url();
$steps = [
    'Curriculums Stage',
    'Courses Stage',
    'Modules Stage',
    'Lessons Stage',
    'Teaching Points Stage'
];
$currentStep = 1; // Courses Stage
?>

<div class="courscribe-single-curriculum p-i-2">
    <!-- Stepper -->
    <?php include plugin_dir_path(__FILE__) . 'stepper.php'; ?>

    <!-- Feedback Toggle Button -->
    <?php if (!$permissions->is_client()): ?>
        <button id="courscribe-feedback-toggle" class="courscribe-show-feedback"
            style="position: fixed; top: 100px; right: 10px; z-index: 1000; padding: 10px 20px; background: #E9B56F; color: #231f20; border: none; border-radius: 16px;">
            Show Feedback
        </button>
    <?php endif; ?>

    <!-- Tabs -->
    <?php include plugin_dir_path(__FILE__) . 'tabs.php'; ?>

    <?php if ($curriculum && $curriculum->ID): ?>
        <!-- Curriculum Goal -->
        <div style="background: #222222; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #222222; width: 100%; box-sizing: border-box;">
            <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/Vector.png" alt="Icon" style="width: 24px; height: 24px;">
            <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">Curriculum Goal: </span>
            <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html(get_post_meta($curriculum->ID, '_curriculum_goal', true) ?: 'No goal set'); ?></span>
        </div>

        <!-- Course Stage -->
        <?php include plugin_dir_path(__FILE__) . 'course-stage.php'; ?>

        <!-- Stepper Buttons -->
        <?php if (!$permissions->is_client()): ?>
            <div class="stepper-buttons">
                <button id="courscribe-prevBtn" class="btn courscribe-stepper-prevBtn"><span class="texst">Previous</span></button>
                <button id="courscribe-nextBtn" class="btn courscribe-stepper-nextBtn">Next</button>
                <a href="<?php echo esc_url($site_url . '/preview-curriculum/?curriculum_id=' . $curriculum->ID . '&preview_type=studio-preview') ?>"
                    class="txt-button-one" data-text="Awesome">
                    <span class="actual-text"> Preview Curriculum </span>
                    <span aria-hidden="true" class="hover-text"> Preview Curriculum </span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Modals -->
        <?php if (!$permissions->is_client()): ?>
            <?php
            include plugin_dir_path(__FILE__) . 'modals/new-course-modal.php';
            include plugin_dir_path(__FILE__) . 'modals/input-ai-suggestions-modal.php';
            include plugin_dir_path(__FILE__) . 'modals/courscribe-loader.php';
            include plugin_dir_path(__FILE__) . 'modals/generate-modules.php';
            include plugin_dir_path(__FILE__) . 'modals/generate-courses.php';
            include plugin_dir_path(__FILE__) . 'modals/generate-lessons.php';
            include plugin_dir_path(__FILE__) . 'modals/new-module-modal.php';
            include plugin_dir_path(__FILE__) . 'modals/new-lesson-modal.php';
            include plugin_dir_path(__FILE__) . 'modals/new-teachingPoint-modal.php';
            include plugin_dir_path(__FILE__) . 'modals/offcanvas/edit-document-offcanvas.php';
            include plugin_dir_path(__FILE__) . 'modals/offcanvas/preview-offcanvas.php';
            include plugin_dir_path(__FILE__) . 'modals/offcanvas/field-feedback-offcanvas.php';
            include plugin_dir_path(__FILE__) . 'modals/offcanvas/pdf-editor-modal.php';
            ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-warning">
            <h3>Curriculum Not Found</h3>
            <p>The requested curriculum could not be found or you do not have permission to view it.</p>
        </div>
    <?php endif; ?>
</div>

<style>
<?php if ($permissions->is_client()): ?>
    /* Disable inputs and buttons for clients */
    .courscribe-single-curriculum button:not(.accordion-button):not(.courscribe-close-button),
    .courscribe-single-curriculum input,
    .courscribe-single-curriculum textarea,
    .courscribe-single-curriculum select {
        pointer-events: none !important;
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }
    .courscribe-single-curriculum .accordion-button,
    .courscribe-single-curriculum .scrollable-tabs a {
        pointer-events: auto !important;
        opacity: 1 !important;
        cursor: pointer !important;
    }
    .courscribe-single-curriculum .drag-handle {
        display: none !important;
    }
<?php endif; ?>
</style>