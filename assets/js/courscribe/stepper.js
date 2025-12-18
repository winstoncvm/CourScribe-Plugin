// assets/js/courscribe/stepper.js
jQuery(document).ready(function ($) {
    const steps = $('.step');
    let currentStep = parseInt($('.step.active').data('step') || 0);
    const totalSteps = steps.length;

    // Ensure site_url is defined
    const siteUrl = window.courscribeAjax && window.courscribeAjax.site_url
        ? window.courscribeAjax.site_url
        : window.location.origin; // Fallback to current origin

    const icons = {
        curriculum: {
            active: siteUrl + '/wp-content/uploads/2024/12/curriculum-active.png',
            complete: siteUrl + '/wp-content/uploads/2024/12/curriculum-active.png',
        },
        course: {
            active: siteUrl + '/wp-content/uploads/2024/12/course-active.png',
            complete: siteUrl + '/wp-content/uploads/2024/12/course-active.png',
            inactive: siteUrl + '/wp-content/uploads/2024/12/course-inactive.png'
        },
        module: {
            active: siteUrl + '/wp-content/uploads/2024/12/module-active.png',
            complete: siteUrl + '/wp-content/uploads/2024/12/module-active.png',
            inactive: siteUrl + '/wp-content/uploads/2024/12/module-inactive.png'
        },
        lesson: {
            active: siteUrl + '/wp-content/uploads/2024/12/lesson-active.png',
            complete: siteUrl + '/wp-content/uploads/2024/12/lesson-active.png',
            inactive: siteUrl + '/wp-content/uploads/2024/12/lesson-inactive.png'
        },
        teachingPoint: {
            active: siteUrl + '/wp-content/uploads/2024/12/teaching-point-active.png',
            inactive: siteUrl + '/wp-content/uploads/2024/12/teaching-point-inactive.png'
        }
    };

    function getStepIcon(stepIndex, state) {
        const fallback = siteUrl + '/wp-content/uploads/2024/12/default.png';
        switch (stepIndex) {
            case 0: return icons.curriculum[state] || fallback;
            case 1: return icons.course[state] || fallback;
            case 2: return icons.module[state] || fallback;
            case 3: return icons.lesson[state] || fallback;
            case 4: return icons.teachingPoint[state] || fallback;
            default: return fallback;
        }
    }

    function setCurrentStep(stepIndex) {
        steps.each(function (index, step) {
            const $step = $(step);
            $step.removeClass('active complete inactive');

            let state = 'inactive';
            if (index < stepIndex) {
                state = 'complete';
                $step.addClass('complete');
            } else if (index == stepIndex) {
                state = 'active';
                $step.addClass('active');
            } else {
                $step.addClass('inactive');
            }

            const $img = $step.find('img');
            $img.attr('src', getStepIcon(index, state));
        });

        $('.step-connector').each(function (index) {
            const $connector = $(this);
            $connector.removeClass('active complete');
            if (index < stepIndex - 1) {
                $connector.addClass('complete');
            } else if (index === stepIndex - 1) {
                $connector.addClass('active');
            }
        });

        currentStep = stepIndex;
        updateUIForStep(stepIndex);
    }

    steps.each(function (index, step) {
        $(step).on('click', function () {
            if (index === 0) {
                window.location.href = document.referrer || siteUrl;
            } else if (index >= 1) {
                setCurrentStep(index);
            }
        });
    });

    const coursesHeader = $('.courscribe-courses-header');
    coursesHeader.each(function () {
        toggleCourseHeader($(this));
    });
    coursesHeader.on('classChange', function () {
        toggleCourseHeader($(this));
    });

    function toggleCourseHeader($header) {
        if ($header.hasClass('active')) {
            $header.find('.the-input-field').show();
            $header.find('.remove-btn').show();
            $header.find('.course-title-span').hide();
        } else {
            $header.find('.the-input-field').hide();
            $header.find('.remove-btn').hide();
            $header.find('.course-title-span').show();
        }
    }

    function updateUIForStep(stepIndex) {
        // Get all content sections
        const courseStageWrapper = $('.course-stage-wrapper');
        const newCoursesWraper = $('.courscribe-courses-premium');
        const courseAccordion = $('.accordion-item.as-course .accordion-collapse');
        const modulesContent = $('.cs-modules-premium-container');
        const lessonsContent = $('.cs-lessons-enhanced');
        const lessonsPremiumContent = $('.cs-lessons-premium'); // Premium lessons
        const teachingPointContent = $('.courscribe-teachingPoints');
    
        // Hide all sections first
        courseStageWrapper.hide();
        modulesContent.hide();
        lessonsContent.hide();
        lessonsPremiumContent.hide(); // Hide premium lessons
        newCoursesWraper.hide();
        teachingPointContent.hide();
        courseAccordion.removeClass('modules-active lessons-active teachingpoints-active');

        switch (stepIndex) {
            case 1:
                // Show courses stage
                courseStageWrapper.show();
                newCoursesWraper.show();
                coursesHeader.addClass('active');
                coursesHeader.trigger('classChange');
                // Show course fields and hide module/lesson specific content
                $('.courscribe-slide-deck-controls').show();
                break;
            case 2:
                // Show modules stage
                courseStageWrapper.show();
                newCoursesWraper.hide();
                coursesHeader.removeClass('active');
                coursesHeader.trigger('classChange');
                courseAccordion.addClass('modules-active');
                modulesContent.show();
                // Hide course-specific controls when in modules view
                $('.courscribe-slide-deck-controls').hide();
                break;
            case 3:
                // Show lessons stage
                courseStageWrapper.show();
                newCoursesWraper.hide();
                coursesHeader.removeClass('active');
                coursesHeader.trigger('classChange');
                courseAccordion.addClass('lessons-active');
                
                // Show premium lessons if available, fallback to regular lessons
                if (lessonsPremiumContent.length > 0) {
                    lessonsPremiumContent.show();
                    
                    // Initialize premium lessons functionality if not already done
                    if (window.CourScribeLessonsPremium && !window.courscribleLessonsPremium) {
                        window.courscribleLessonsPremium = new window.CourScribeLessonsPremium();
                    }
                } else {
                    lessonsContent.show();
                }
                
                $('.courscribe-slide-deck-controls').hide();
                break;
            case 4:
                // Show teaching points stage
                courseStageWrapper.show();
                newCoursesWraper.hide();
                coursesHeader.removeClass('active');
                coursesHeader.trigger('classChange');
                courseAccordion.addClass('teachingpoints-active');
                teachingPointContent.show();
                $('.courscribe-slide-deck-controls').hide();
                break;
        }
        
        // Trigger custom event for step change
        $(document).trigger('courscribe:stepChanged', {
            stepIndex: stepIndex,
            stepName: getStepName(stepIndex)
        });
    }
    
    // Helper function to get step name
    function getStepName(stepIndex) {
        const stepNames = ['curriculum', 'course', 'module', 'lesson', 'teachingPoint'];
        return stepNames[stepIndex] || 'unknown';
    }

    $('#courscribe-nextBtn').on('click', function () {
        if (currentStep < totalSteps - 1) {
            setCurrentStep(currentStep + 1);
        }
    });

    $('#courscribe-prevBtn').on('click', function () {
        if (currentStep > 0) {
            setCurrentStep(currentStep - 1);
        }
    });

    setCurrentStep(currentStep);
});