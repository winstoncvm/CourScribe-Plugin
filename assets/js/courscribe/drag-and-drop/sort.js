// assets/js/courscribe/drag-and-drop/sort.js
jQuery(document).ready(function ($) {
    $("#coursesAccordion").sortable({
        handle: ".drag-handle",
        axis: "y",
        update: function (event, ui) {
            let newOrder = [];
            $("#coursesAccordion .accordion-item").each(function () {
                newOrder.push($(this).data("course-id"));
            });

            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: "POST",
                data: {
                    action: "update_course_order",
                    curriculum_id: courscribeAjax.curriculum_id, // Localized separately
                    order: newOrder
                },
                success: function (response) {
                    if (response.success) {
                        console.log("Course order updated successfully.");
                    } else {
                        console.error("Failed to update course order.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("AJAX error:", textStatus, errorThrown);
                }
            });
        }
    });
    $("#coursesAccordion").disableSelection();

    $(".modules-accordion").each(function () {
        var container = $(this);
        var courseId = container.attr('id').replace('modulesAccordion-', '');

        function updateSortButtons() {
            container.find('.module-item').each(function (index) {
                var upBtn = $(this).find('.sort-up');
                var downBtn = $(this).find('.sort-down');
                upBtn.prop('disabled', index === 0);
                downBtn.prop('disabled', index === container.find('.module-item').length - 1);
            });
        }

        updateSortButtons();

        container.on('click', '.sort-up', function () {
            var item = $(this).closest('.module-item');
            var prevItem = item.prev('.module-item');
            if (prevItem.length) {
                item.insertBefore(prevItem);
                updateSortButtons();
                saveNewOrder();
            }
        });

        container.on('click', '.sort-down', function () {
            var item = $(this).closest('.module-item');
            var nextItem = item.next('.module-item');
            if (nextItem.length) {
                item.insertAfter(nextItem);
                updateSortButtons();
                saveNewOrder();
            }
        });

        function saveNewOrder() {
            let newOrder = [];
            container.find('.module-item').each(function () {
                newOrder.push($(this).data("module-id"));
            });
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: "POST",
                data: {
                    action: "update_module_order",
                    course_id: courseId,
                    order: newOrder
                },
                success: function (response) {
                    if (response.success) {
                        console.log("Module order updated successfully for course " + courseId);
                    } else {
                        console.error("Failed to update module order for course " + courseId);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("AJAX error:", textStatus, errorThrown);
                }
            });
        }
    });

    $(".lessons").each(function () {
        var container = $(this);
        var moduleId = container.attr('id').replace('lessonsSection-', '');

        function updateSortButtons() {
            container.find('.lesson-item').each(function (index) {
                var upBtn = $(this).find('.sort-up');
                var downBtn = $(this).find('.sort-down');
                upBtn.prop('disabled', index === 0);
                downBtn.prop('disabled', index === container.find('.lesson-item').length - 1);
            });
        }

        updateSortButtons();

        container.on('click', '.sort-up', function () {
            var item = $(this).closest('.lesson-item');
            var prevItem = item.prev('.lesson-item');
            if (prevItem.length) {
                item.insertBefore(prevItem);
                updateSortButtons();
                saveNewOrder();
            }
        });

        container.on('click', '.sort-down', function () {
            var item = $(this).closest('.lesson-item');
            var nextItem = item.next('.lesson-item');
            if (nextItem.length) {
                item.insertAfter(nextItem);
                updateSortButtons();
                saveNewOrder();
            }
        });

        function saveNewOrder() {
            let newOrder = [];
            container.find('.lesson-item').each(function () {
                newOrder.push($(this).data("lesson-id"));
            });
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: "POST",
                data: {
                    action: "update_lesson_order",
                    module_id: moduleId,
                    order: newOrder
                },
                success: function (response) {
                    if (response.success) {
                        console.log("Lesson order updated successfully for module " + moduleId);
                    } else {
                        console.error("Failed to update lesson order for module " + moduleId);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("AJAX error:", textStatus, errorThrown);
                }
            });
        }
    });
});