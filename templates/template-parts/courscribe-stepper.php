<?php
$steps = [
    'Curriculums Stage',
    'Courses Stage',
    'Modules Stage',
    'Lessons Stage',
    'Teaching Points Stage'
];

// This variable controls which step is currently active.
$currentStep = 1; // Assuming Courses Stage is the current step
?>
<div class="stepper">
    <div class="stepper-container">
        <?php foreach ($steps as $index => $label): ?>
            <div class="step  <?php echo ($index == $currentStep) ? 'active' : ''; ?> <?php echo ($index < $currentStep) ? 'complete' : ''; ?>" data-step="<?php echo $index; ?>">
                <div class="step-icon">
                    <img
                            src="<?php echo ($index <= $currentStep) ? '<?= home_url(); ?>/wp-content/uploads/2024/12/' . ($index + 1) . '.png' : '<?= home_url(); ?>/wp-content/uploads/2024/12/mg' . ($index + 1) . '.png'; ?>"
                            alt="<?php echo $label; ?>"
                            class="icon-img"
                    />
                </div>
                <div class="step-label">
                    <?php echo $label; ?>
                </div>
            </div>
            <?php if ($index < count($steps) - 1): ?>
                <div class="step-connector <?php echo ($index == $currentStep) ? 'active' : ''; ?> <?php echo ($index < $currentStep) ? 'complete' : ''; ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <script>
        jQuery(document).ready(function($) {
            const steps = $('.step');
            const courses = $('.courscribe-courses');
            let currentStep = 1; // Default active step (Courses)

            // Define image URLs for each step and state
            const icons = {
                curriculum: {
                    active: '<?= home_url(); ?>/wp-content/uploads/2024/12/curriculum-active.png',
                    complete: '<?= home_url(); ?>/wp-content/uploads/2024/12/curriculum-complete.png',
                },
                course: {
                    active: '<?= home_url(); ?>/wp-content/uploads/2024/12/course-active.png',
                    complete: '<?= home_url(); ?>/wp-content/uploads/2024/12/course-complete.png',
                    inactive: '<?= home_url(); ?>/wp-content/uploads/2024/12/course-inactive.png'
                },
                module: {
                    active: '<?= home_url(); ?>/wp-content/uploads/2024/12/module-active.png',
                    complete: '<?= home_url(); ?>/wp-content/uploads/2024/12/module-complete.png',
                    inactive: '<?= home_url(); ?>/wp-content/uploads/2024/12/module-inactive.png'
                },
                lesson: {
                    active: '<?= home_url(); ?>/wp-content/uploads/2024/12/lesson-active.png',
                    complete: '<?= home_url(); ?>/wp-content/uploads/2024/12/lesson-complete.png',
                    inactive: '<?= home_url(); ?>/wp-content/uploads/2024/12/lesson-inactive.png'
                },
                teachingPoint: {
                    active: '<?= home_url(); ?>/wp-content/uploads/2024/12/teaching-point-active.png',
                    inactive: '<?= home_url(); ?>/wp-content/uploads/2024/12/teaching-point-inactive.png'
                }
            };

            // Function to dynamically set the icon URL based on the state
            function getStepIcon(stepIndex, state) {
                switch (stepIndex) {
                    case 0: return icons.curriculum[state]; // Curriculum step
                    case 1: return icons.course[state];     // Course step
                    case 2: return icons.module[state];     // Module step
                    case 3: return icons.lesson[state];     // Lesson step
                    case 4: return icons.teachingPoint[state]; // Teaching Points step
                    default: return ''; // Default or unknown step
                }
            }

            // Function to update the active step
            function setCurrentStep(stepIndex) {
                // Update classes and icons for each step
                steps.each(function(index, step) {
                    const $step = $(step);
                    $step.removeClass('active complete inactive'); // Remove all classes

                    let state = 'inactive'; // Default to inactive state
                    if (index < stepIndex) {
                        state = 'complete'; // Mark previous steps as complete
                        $step.addClass('complete');
                    } else if (index == stepIndex) {
                        state = 'active';   // Mark the current step as active
                        $step.addClass('active');
                    } else {
                        $step.addClass('inactive'); // Future steps are inactive
                    }

                    // Update the icon based on the state
                    const $img = $step.find('img');
                    $img.attr('src', getStepIcon(index, state)); // Set the new image URL
                });

                currentStep = stepIndex;
                updateUIForStep(stepIndex); // Call the function to update UI content
            }

            // Add click event listeners for steps
            steps.each(function(index, step) {
                $(step).on('click', function() {
                    if (index >= 1) { // Prevent clicking on Curriculum stage (always complete)
                        setCurrentStep(index);
                    }
                });
            });

            // Function to dynamically change UI content based on step
            function updateUIForStep(stepIndex) {
                console.log("Current Step is: ", stepIndex);
                if(stepIndex === 1){
                    courses.addClass('course-stage')
                }
                // Add logic here to change UI based on the current step, e.g., display courses, modules, etc.
            }
            // Next and Previous button event listeners
            $('#courscribe-nextBtn').on('click', function() {
                if (currentStep < totalSteps) {
                    setCurrentStep(currentStep + 1); // Move to the next step
                }
            });

            $('#courscribe-prevBtn').on('click', function() {
                if (currentStep > 0) {
                    setCurrentStep(currentStep - 1); // Move to the previous step
                }
            });


            // Initialize with the default active step
            setCurrentStep(currentStep);
        });
    </script>
</div>
