<div class="pcss3t pcss3t-effect-scale pcss3t-theme-1">
<?php
if ($modules) {
    foreach ($modules as $module) {
        ?>
        <!-- tabs -->
            <input type="radio" name="pcss3t" checked  id="tab1"class="tab-content-first">
            <label for="tab1"><span>Module 1<span>Serbian-American inventor</span></span></label>
            <!--/ tabs -->
        <?php
        }
    } else {
        echo '<p>No modules added yet.</p>';
    }
    ?>
</div>
