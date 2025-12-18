(function($) {
    // Debounce function to prevent rapid clicks
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize TourGuide.js
    const tg = new tourguide.TourGuideClient({
        progressBar: "#999",
        exitOnEscape: true,
        exitOnClickOutside: false,
        closeButton: true,
        completeOnFinish: false,
        finishButton: '<button class="btn btn-secondary">Skip Tour</button>',
        steps: [
            {
                title: "Welcome to the Curriculum Page (Step 1/8)",
                content: "Let's explore the single curriculum page! Start by clicking the 'Add New Course' button to create a new course manually.",
                target: "#open-course-modal",
                order: 0,
                beforeShow: () => document.querySelector("#open-course-modal") ? true : false
            },
            {
                title: "Generate Courses with AI (Step 2/8)",
                content: "You can also generate courses using AI. Click the 'Generate Courses' button to open the AI form.",
                target: `#courscribe-ai-generate-courses-button-${courscribeTour.curriculumId}`,
                order: 1,
                beforeShow: () => document.querySelector(`#courscribe-ai-generate-courses-button-${courscribeTour.curriculumId}`) ? true : false,
                beforeLeave: () => {
                    return new Promise((resolve) => {
                        $(`#courscribe-ai-generate-courses-button-${courscribeTour.curriculumId}`).click();
                        setTimeout(() => resolve(true), 500); // Wait for offcanvas to open
                    });
                }
            },
            {
                title: "AI Course Tone (Step 3/8)",
                content: "Select the tone for the AI-generated course.",
                target: "#module-tone",
                order: 2,
                beforeShow: () => document.querySelector("#module-tone") ? true : false
            },
            {
                title: "AI Course Audience (Step 4/8)",
                content: "Select the audience for the AI-generated course.",
                target: "#module-audience",
                order: 3,
                beforeShow: () => document.querySelector("#module-audience") ? true : false
            },
            {
                title: "Generate AI Course (Step 5/8)",
                content: "Click 'Generate Course' to create a sample course. Don't worry, we'll delete this sample at the end of the tour.",
                target: ".ai-send-button",
                order: 4,
                beforeShow: () => document.querySelector("#generate_course_submit") ? true : false,
                beforeLeave: () => {
                    return new Promise((resolve) => {
                        $(".ai-send-button").click();
                        setTimeout(() => {
                            // Simulate storing the generated course ID
                            window.sampleCourseId = 'sample-' + Date.now(); // Placeholder
                            resolve(true);
                        }, 7000); // Simulate AJAX delay
                    });
                }
            },
            {
                title: "Stepper: Courses Stage (Step 6/8)",
                content: "The stepper shows your progress. At the 'Courses Stage', you can add or edit courses, like the one we just generated.",
                target: ".step[data-step='1']",
                order: 5,
                beforeShow: () => document.querySelector(".step[data-step='1']") ? true : false
            },
            {
                title: "Stepper: Modules Stage (Step 7/8)",
                content: "In the 'Modules Stage', you can create and organize modules within each course to structure the content.",
                target: ".step[data-step='2']",
                order: 6,
                beforeShow: () => document.querySelector(".step[data-step='2']") ? true : false
            },
            {
                title: "Stepper: Lessons Stage (Step 8/8)",
                content: "At the 'Lessons Stage', you can add detailed lessons within modules, including teaching points and resources.",
                target: ".step[data-step='3']",
                order: 7,
                beforeShow: () => document.querySelector(".step[data-step='3']") ? true : false,
                afterLeave: () => {
                    // Clean up: Delete the sample course
                    if (window.sampleCourseId) {
                        $.ajax({
                            url: courscribeTour.ajaxUrl,
                            method: 'POST',
                            data: {
                                action: 'courscribe_delete_course',
                                nonce: courscribeTour.nonce,
                                course_id: window.sampleCourseId
                            },
                            success: function(response) {
                                if (response.success) {
                                    console.log('Sample course deleted successfully');
                                } else {
                                    console.error('Failed to delete sample course:', response.data.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error deleting sample course:', xhr.responseText);
                            }
                        });
                        window.sampleCourseId = null;
                    }
                }
            }
        ]
    });

    // Handle tour trigger
    $('.courscribe-help-toggle-single').on('click', debounce(function() {
        if (localStorage.getItem('courscribeSingleTourCompleted')) {
            if (!confirm('You’ve completed the single curriculum tour before. Want to take it again?')) {
                return;
            }
        }
        tg.start().then(() => {
            localStorage.setItem('courscribeSingleTourCompleted', 'true');
        });
    }, 300));

    // Custom tour styles
    const style = document.createElement('style');
    style.textContent = `
        .courscribe-help-toggle-single {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: #E4B26F;
            color: #231f20;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            font-size: 24px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        .courscribe-help-toggle-single:hover {
            background: #d9a05b;
        }
        .tg-dialog {
            background: #2a2a2b !important;
            color: #fff !important;
            border-radius: 8px !important;
        }
        .tg-dialog-title {
            color: #E4B26F !important;
        }
        .tg-dialog-content {
            color: #fff !important;
        }
        .tg-dot.active {
            background: #E4B26F !important;
        }
        @media (max-width: 768px) {
            .tg-dialog {
                width: 90% !important;
                margin: 0 auto !important;
            }
        }
    `;
    document.head.appendChild(style);
})(jQuery);