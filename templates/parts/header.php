// templates/parts/header.php
?>
<div class="courscribe-wrapper">
    <link rel="stylesheet" href="<?php echo esc_url(plugins_url('assets/css/dashboard.css', dirname(__FILE__, 3))); ?>">
    <div class="courscribe-header">
        <!-- Add your header content -->
    </div>

    <?php
    // templates/parts/quick-stats.php
    ?>
    <div class="quick-stats">
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['curriculums']); ?></span>
            <span class="stat-label">Curriculums</span>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['courses']); ?></span>
            <span class="stat-label">Courses</span>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['team_members']); ?></span>
            <span class="stat-label">Team Members</span>
        </div>
    </div>