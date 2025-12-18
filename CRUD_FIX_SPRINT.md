# CRUD Operations Fix Sprint
**Date**: December 18, 2025
**Priority**: CRITICAL
**Timeline**: Single Sprint (1-2 days)

---

## 🎯 Objective

Fix all CRUD operations for Courses, Modules, Lessons, and Teaching Points in `courscribe_single_curriculum_shortcode.php` to make them bullet-proof and work reliably.

**IMPORTANT**: Do NOT change UI/UX flow - only fix functionality!

---

## 🐛 Critical Issues Identified

### 1. **Course Issues**

#### Problem 1.1: Autosave with undefined fields
```javascript
// ERROR LOG:
data to autosave: {action: 'update_course_ajax', course_id: 143, field: undefined, value: undefined}
Save failed for field undefined: Invalid course ID or field.
```

**Root Cause**: JavaScript is sending `undefined` for field and value
**Location**: Inline JavaScript in shortcode file (around line 1330-1354)
**Fix**: Add proper field detection and validation before AJAX call

#### Problem 1.2: Invalid objectives format
```javascript
// ERROR LOG:
Save failed for field objectives: Invalid objectives format.
```

**Root Cause**: Objectives array not being properly formatted for server
**Fix**: Ensure objectives are serialized correctly before sending

---

### 2. **Module Issues**

#### Problem 2.1: Missing saveObjective method
```javascript
// ERROR LOG:
modules-premium-enhanced.js:378 Uncaught TypeError: this.saveObjective is not a function
```

**Root Cause**: Method `saveObjective` is called but never defined
**Location**: `assets/js/courscribe/modules/modules-premium-enhanced.js:378`
**Fix**: Implement the missing `saveObjective` method

#### Problem 2.2: Module create validation error
```javascript
// ERROR LOG:
create.js:120 Uncaught TypeError: Cannot read properties of undefined (reading 'trim')
```

**Root Cause**: Attempting to trim undefined value
**Location**: `assets/js/courscribe/modules/create.js:120`
**Fix**: Add null/undefined checks before calling `.trim()`

#### Problem 2.3: Edit, Archive, Delete not working
**Status**: Buttons exist but functions are broken or missing
**Fix**: Implement proper AJAX handlers and event listeners

---

### 3. **Lesson Issues**

#### Problem 3.1: Add Lesson buttons not working
**Symptoms**: Both "Add Lesson" and "Add First Lesson" buttons do nothing
**Root Cause**: Missing or broken event listeners
**Fix**: Implement proper event delegation and AJAX handlers

#### Problem 3.2: Add Objective causing page redirect (404)
**Symptoms**: Clicking add objective causes page to redirect to 404
**Root Cause**: Form submission not being prevented
**Fix**: Add `e.preventDefault()` and proper AJAX handling

#### Problem 3.3: Add Activity causing same issue
**Same as Problem 3.2**

#### Problem 3.4: Teaching Points duplicating
**Symptoms**: Teaching points save but appear duplicated
**Root Cause**: Likely double event binding or missing unique ID checks
**Fix**: Ensure single event binding and unique ID validation

#### Problem 3.5: Objective field editing fails
```javascript
// ERROR LOG:
req: {action: 'courscribe_autosave_lesson_field', lesson_id: 151, field_name: 'objective_description'}
{success: false, data: {…}}
```

**Root Cause**: Field name doesn't match server expectations or validation failing
**Fix**: Align field names with server-side expectations

---

### 4. **Asset Loading Issues**

#### Problem 4.1: Missing CSS files
```
courscribe-main.css:1 Failed to load resource: 404 (Not Found)
```

**Root Cause**: File doesn't exist or path is incorrect
**Fix**: Either create the file or remove the enqueue

#### Problem 4.2: Broken path
```
%3C:1 Failed to load resource: 404 (Not Found)
```

**Root Cause**: URL encoding issue or broken path
**Fix**: Identify and fix the broken URL

#### Problem 4.3: Script duplication
```javascript
// ERROR LOG shows 3x initialization:
Initializing CourScribe Modules... (appears 3 times)
CourScribe Enhanced Lessons initialized (appears 3 times)
```

**Root Cause**: Scripts loaded multiple times or event handlers bound multiple times
**Fix**: Implement initialization guards and unbind before rebinding

---

## 🔧 Fix Implementation Plan

### Phase 1: Fix Critical JavaScript Errors (30 minutes)

#### Task 1.1: Fix modules-premium-enhanced.js
**File**: `assets/js/courscribe/modules/modules-premium-enhanced.js`

**Add missing `saveObjective` method**:
```javascript
saveObjective: function(moduleId, objectiveId) {
    console.log('Saving objective for module:', moduleId, 'objective:', objectiveId);

    const $objectiveContainer = $(`[data-module-id="${moduleId}"] [data-objective-id="${objectiveId}"]`).closest('.objective-item');
    if (!$objectiveContainer.length) {
        console.error('Objective container not found');
        return;
    }

    const objective = {
        id: objectiveId,
        thinking_skill: $objectiveContainer.find('[name="thinking_skill"]').val(),
        action_verb: $objectiveContainer.find('[name="action_verb"]').val(),
        description: $objectiveContainer.find('[name="objective_description"]').val()
    };

    // Validate
    if (!objective.thinking_skill || !objective.action_verb || !objective.description) {
        console.warn('Incomplete objective data');
        return;
    }

    // Save via AJAX
    $.ajax({
        url: courscribeAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'courscribe_save_module_objective',
            module_id: moduleId,
            objective_id: objectiveId,
            objective: objective,
            nonce: courscribeAjax.module_generation_nonce
        },
        success: function(response) {
            if (response.success) {
                console.log('Objective saved successfully');
                // Show success toast if available
                if (typeof showToast === 'function') {
                    showToast('success', 'Objective saved');
                }
            } else {
                console.error('Save failed:', response.data);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });
},
```

**Add method to CourScribeModulesEnhanced object** (insert after line 350 or similar):
```javascript
// Add this in the return statement or as part of the module pattern
return {
    init: init,
    saveObjective: saveObjective,  // Add this line
    // ... other methods
};
```

---

#### Task 1.2: Fix modules create.js
**File**: `assets/js/courscribe/modules/create.js`

**Fix trim() error around line 120**:
```javascript
// BEFORE (line 117-120):
$('.objective-item').each(function() {
    objectives.push({
        description: $(this).find('[name="objective_description"]').val().trim(),  // ERROR HERE
    });
});

// AFTER:
$('.objective-item').each(function() {
    const description = $(this).find('[name="objective_description"]').val();
    if (description && description.trim) {  // Check if exists and has trim method
        objectives.push({
            thinking_skill: $(this).find('[name="thinking_skill"]').val() || '',
            action_verb: $(this).find('[name="action_verb"]').val() || '',
            description: description.trim()
        });
    }
});
```

---

### Phase 2: Fix Course CRUD Operations (45 minutes)

#### Task 2.1: Fix Course Autosave
**Location**: Inline `<script>` in `courscribe_single_curriculum_shortcode.php` (around line 1200-1400)

**Current Problem**:
```javascript
// Sending undefined field and value
data: {
    action: 'update_course_ajax',
    course_id: courseId,
    field: undefined,  // ❌
    value: undefined,  // ❌
}
```

**Fix**:
```javascript
// Around line 1245-1360 in shortcode file
$(document).on('input change', '[data-course-id] input, [data-course-id] textarea, [data-course-id] select', function(e) {
    const $field = $(this);
    const courseId = $field.closest('[data-course-id]').data('course-id');
    const fieldName = $field.attr('name') || $field.data('field-name');
    const fieldValue = $field.val();

    // ✅ Validate before sending
    if (!courseId || !fieldName || fieldValue === undefined) {
        console.warn('Invalid field data:', {courseId, fieldName, fieldValue});
        return;
    }

    // Clear existing timeout
    if (courseAutosaveTimeouts[courseId]) {
        clearTimeout(courseAutosaveTimeouts[courseId]);
    }

    // Schedule new save
    courseAutosaveTimeouts[courseId] = setTimeout(function() {
        saveField(courseId, fieldName, fieldValue);
    }, 2000);
});

function saveField(courseId, field, value) {
    // Special handling for objectives
    if (field === 'objectives') {
        const objectives = [];
        $(`[data-course-id="${courseId}"] .objective-item`).each(function() {
            const $item = $(this);
            objectives.push({
                thinking_skill: $item.find('[name="thinking_skill"]').val() || '',
                action_verb: $item.find('[name="action_verb"]').val() || '',
                description: $item.find('[name="objective_description"]').val() || ''
            });
        });
        value = JSON.stringify(objectives);
    }

    console.log('data to autosave:', {
        action: 'update_course_ajax',
        course_id: courseId,
        field: field,
        value: value
    });

    $.ajax({
        url: courscribeAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'update_course_ajax',
            course_id: courseId,
            field: field,
            value: value,
            nonce: courscribeNonce
        },
        success: function(response) {
            if (response.success) {
                console.log(`✅ Saved ${field} successfully`);
                if (typeof showToast === 'function') {
                    showToast('success', `${field} saved`);
                }
            } else {
                console.error(`Save failed for field ${field}:`, response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });
}
```

---

### Phase 3: Fix Module CRUD Operations (60 minutes)

#### Task 3.1: Implement Module Edit
**Add event handler in shortcode inline script**:
```javascript
// Edit Module Button
$(document).on('click', '.edit-module-btn', function(e) {
    e.preventDefault();
    const moduleId = $(this).data('module-id');
    const $moduleCard = $(`[data-module-id="${moduleId}"]`);

    // Toggle edit mode
    $moduleCard.find('.view-mode').hide();
    $moduleCard.find('.edit-mode').show();
});

// Save Module Changes
$(document).on('click', '.save-module-btn', function(e) {
    e.preventDefault();
    const moduleId = $(this).data('module-id');
    const $moduleCard = $(`[data-module-id="${moduleId}"]`);

    const moduleData = {
        title: $moduleCard.find('[name="module_title"]').val(),
        description: $moduleCard.find('[name="module_description"]').val(),
        duration: $moduleCard.find('[name="module_duration"]').val(),
        objectives: []
    };

    // Collect objectives
    $moduleCard.find('.objective-item').each(function() {
        moduleData.objectives.push({
            thinking_skill: $(this).find('[name="thinking_skill"]').val(),
            action_verb: $(this).find('[name="action_verb"]').val(),
            description: $(this).find('[name="objective_description"]').val()
        });
    });

    $.ajax({
        url: courscribeAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'courscribe_update_module',
            module_id: moduleId,
            module_data: moduleData,
            nonce: courscribeAjax.module_generation_nonce
        },
        success: function(response) {
            if (response.success) {
                showToast('success', 'Module updated');
                $moduleCard.find('.edit-mode').hide();
                $moduleCard.find('.view-mode').show();
                // Update display values
                $moduleCard.find('.module-title-display').text(moduleData.title);
                $moduleCard.find('.module-description-display').text(moduleData.description);
            } else {
                showToast('error', response.data.message || 'Update failed');
            }
        }
    });
});
```

#### Task 3.2: Implement Module Archive/Delete
```javascript
// Archive Module
$(document).on('click', '.archive-module-btn', function(e) {
    e.preventDefault();
    const moduleId = $(this).data('module-id');

    if (!confirm('Archive this module?')) return;

    $.ajax({
        url: courscribeAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'courscribe_archive_module',
            module_id: moduleId,
            nonce: courscribeAjax.module_generation_nonce
        },
        success: function(response) {
            if (response.success) {
                showToast('success', 'Module archived');
                $(`[data-module-id="${moduleId}"]`).fadeOut();
            }
        }
    });
});

// Delete Module
$(document).on('click', '.delete-module-btn', function(e) {
    e.preventDefault();
    const moduleId = $(this).data('module-id');

    if (!confirm('Permanently delete this module? This cannot be undone.')) return;

    $.ajax({
        url: courscribeAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'courscribe_delete_module',
            module_id: moduleId,
            nonce: courscribeAjax.module_generation_nonce
        },
        success: function(response) {
            if (response.success) {
                showToast('success', 'Module deleted');
                $(`[data-module-id="${moduleId}"]`).remove();
            }
        }
    });
});
```

---

### Phase 4: Fix Lesson CRUD Operations (60 minutes)

#### Task 4.1: Fix Add Lesson Buttons
```javascript
// Prevent duplicate initialization
var lessonsInitialized = false;

function initLessonHandlers() {
    if (lessonsInitialized) {
        console.log('Lesson handlers already initialized, skipping');
        return;
    }
    lessonsInitialized = true;

    // Add First Lesson Button
    $(document).on('click', '.add-first-lesson-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const moduleId = $(this).data('module-id');
        console.log('Add first lesson clicked for module:', moduleId);

        showAddLessonModal(moduleId);
    });

    // Add Lesson Button
    $(document).on('click', '.add-lesson-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const moduleId = $(this).data('module-id');
        console.log('Add lesson clicked for module:', moduleId);

        showAddLessonModal(moduleId);
    });
}

function showAddLessonModal(moduleId) {
    // Clear previous modal content
    $('#addLessonModal').remove();

    // Create modal HTML
    const modalHTML = `
        <div class="modal fade" id="addLessonModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Lesson</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addLessonForm">
                            <input type="hidden" name="module_id" value="${moduleId}">

                            <div class="mb-3">
                                <label>Lesson Title *</label>
                                <input type="text" name="lesson_title" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label>Lesson Goal</label>
                                <textarea name="lesson_goal" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label>Duration (minutes)</label>
                                <input type="number" name="lesson_duration" class="form-control" value="60">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveLessonBtn">Create Lesson</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    $('#addLessonModal').modal('show');

    // Handle save
    $('#saveLessonBtn').off('click').on('click', function() {
        const formData = $('#addLessonForm').serialize();

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: formData + '&action=courscribe_create_lesson&nonce=' + courscribeAjax.lesson_generation_nonce,
            success: function(response) {
                if (response.success) {
                    showToast('success', 'Lesson created');
                    $('#addLessonModal').modal('hide');
                    location.reload(); // Reload to show new lesson
                } else {
                    showToast('error', response.data.message || 'Failed to create lesson');
                }
            }
        });
    });
}

// Initialize on DOM ready
$(document).ready(function() {
    initLessonHandlers();
});
```

#### Task 4.2: Fix Add Objective/Activity (Prevent Page Redirect)
```javascript
// Add Objective to Lesson
$(document).on('click', '.add-lesson-objective-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const lessonId = $(this).data('lesson-id');
    const $objectivesContainer = $(`[data-lesson-id="${lessonId}"] .objectives-container`);

    const objectiveHTML = `
        <div class="objective-item mb-3" data-objective-id="temp-${Date.now()}">
            <div class="row">
                <div class="col-md-4">
                    <label>Thinking Skill</label>
                    <select name="thinking_skill" class="form-control">
                        <option value="Remember">Remember</option>
                        <option value="Understand">Understand</option>
                        <option value="Apply">Apply</option>
                        <option value="Analyze">Analyze</option>
                        <option value="Evaluate">Evaluate</option>
                        <option value="Create">Create</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Action Verb</label>
                    <input type="text" name="action_verb" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Description</label>
                    <input type="text" name="objective_description" class="form-control">
                </div>
            </div>
            <button class="btn btn-sm btn-danger mt-2 remove-objective-btn">Remove</button>
        </div>
    `;

    $objectivesContainer.append(objectiveHTML);

    return false; // Extra safety to prevent navigation
});

// Add Activity to Lesson
$(document).on('click', '.add-lesson-activity-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const lessonId = $(this).data('lesson-id');
    const $activitiesContainer = $(`[data-lesson-id="${lessonId}"] .activities-container`);

    const activityHTML = `
        <div class="activity-item mb-3" data-activity-id="temp-${Date.now()}">
            <div class="mb-2">
                <label>Activity Title</label>
                <input type="text" name="activity_title" class="form-control">
            </div>
            <div class="mb-2">
                <label>Description</label>
                <textarea name="activity_description" class="form-control" rows="2"></textarea>
            </div>
            <button class="btn btn-sm btn-danger remove-activity-btn">Remove</button>
        </div>
    `;

    $activitiesContainer.append(activityHTML);

    return false; // Extra safety
});

// Remove handlers
$(document).on('click', '.remove-objective-btn', function(e) {
    e.preventDefault();
    $(this).closest('.objective-item').remove();
});

$(document).on('click', '.remove-activity-btn', function(e) {
    e.preventDefault();
    $(this).closest('.activity-item').remove();
});
```

#### Task 4.3: Fix Teaching Points Duplication
```javascript
// Use event delegation with namespace to prevent double binding
$(document).off('input.teachingpoint').on('input.teachingpoint', '[data-lesson-id] .teaching-point-field', function(e) {
    const lessonId = $(this).closest('[data-lesson-id]').data('lesson-id');
    const pointIndex = $(this).closest('.teaching-point-item').data('point-index');

    // Clear existing timeout
    if (teachingPointTimeouts[`${lessonId}_${pointIndex}`]) {
        clearTimeout(teachingPointTimeouts[`${lessonId}_${pointIndex}`]);
    }

    // Schedule save
    teachingPointTimeouts[`${lessonId}_${pointIndex}`] = setTimeout(function() {
        saveTeachingPoint(lessonId, pointIndex);
    }, 2000);
});

function saveTeachingPoint(lessonId, pointIndex) {
    const $pointItem = $(`[data-lesson-id="${lessonId}"] [data-point-index="${pointIndex}"]`);

    const pointData = {
        title: $pointItem.find('[name="point_title"]').val(),
        description: $pointItem.find('[name="point_description"]').val(),
        example: $pointItem.find('[name="point_example"]').val()
    };

    $.ajax({
        url: courscribeAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'courscribe_save_teaching_point',
            lesson_id: lessonId,
            point_index: pointIndex,
            point_data: pointData,
            nonce: courscribeAjax.lesson_generation_nonce
        },
        success: function(response) {
            if (response.success) {
                console.log('Teaching point saved');
            }
        }
    });
}
```

---

### Phase 5: Fix Asset Loading (15 minutes)

#### Task 5.1: Remove Missing CSS
**File**: `templates/curriculums/helpers/class-courscribe-assets.php`

**Remove or fix** (if `courscribe-main.css` doesn't exist):
```php
// Comment out or remove this line if file doesn't exist
// wp_enqueue_style('courscribe-main', ...);
```

#### Task 5.2: Prevent Script Duplication
**Add initialization guards**:
```javascript
// At the top of inline script in shortcode
(function($) {
    'use strict';

    // Prevent multiple initializations
    if (window.courscribeInitialized) {
        console.log('CourScribe already initialized, skipping');
        return;
    }
    window.courscribeInitialized = true;

    // Rest of the code...
})( jQuery);
```

---

### Phase 6: Testing Checklist

#### Course Operations
- [ ] Course title autosave
- [ ] Course goal autosave
- [ ] Add objective to course
- [ ] Edit objective in course
- [ ] Remove objective from course
- [ ] Course objectives save correctly

#### Module Operations
- [ ] Create new module
- [ ] Edit module title
- [ ] Edit module description
- [ ] Add objective to module
- [ ] Edit objective in module
- [ ] Remove objective from module
- [ ] Archive module
- [ ] Delete module
- [ ] Module order (drag-drop if applicable)

#### Lesson Operations
- [ ] Add first lesson (empty module)
- [ ] Add lesson (existing lessons)
- [ ] Edit lesson title
- [ ] Edit lesson goal
- [ ] Add objective to lesson
- [ ] Edit objective in lesson
- [ ] Remove objective from lesson
- [ ] Add activity to lesson
- [ ] Edit activity in lesson
- [ ] Remove activity from lesson
- [ ] Lesson autosave working

#### Teaching Points
- [ ] Add teaching point
- [ ] Edit teaching point (no duplicates)
- [ ] Remove teaching point
- [ ] Teaching points display correctly

#### General
- [ ] No console errors
- [ ] No 404 errors for assets
- [ ] No page redirects when adding items
- [ ] Scripts initialize only once
- [ ] All AJAX calls return proper responses

---

## 📝 Implementation Order

1. ✅ **Start**: Fix JavaScript errors (saveObjective, trim)
2. ✅ **Next**: Fix course autosave
3. ✅ **Next**: Fix module operations
4. ✅ **Next**: Fix lesson operations
5. ✅ **Next**: Fix teaching points
6. ✅ **Final**: Remove asset loading issues and test

---

## 🚨 Critical Rules

1. **DO NOT** change UI/UX
2. **DO NOT** alter the flow requested by owner
3. **DO** add console.log for debugging
4. **DO** validate all data before AJAX
5. **DO** prevent default on all button clicks
6. **DO** use event delegation
7. **DO** add initialization guards
8. **DO** test each fix immediately

---

**Status**: Ready for implementation
**Estimated Time**: 4-6 hours
**Expected Outcome**: 100% working CRUD operations
