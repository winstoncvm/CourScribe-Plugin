# Action Handlers Refactoring Plan
**Date**: December 18, 2025
**Status**: Planning Phase
**Priority**: High

---

## Problem Statement

The `courscribe-course-actions.php` file has grown to **3,433 lines** and contains mixed responsibilities:
- Course CRUD operations
- Course objectives management
- Course logging
- Rich text editor integration
- AI content generation
- Slide deck generation (PowerPoint)
- Module activity logging (wrong file!)

This violates the Single Responsibility Principle and makes maintenance difficult.

---

## Current File Analysis

### courscribe-course-actions.php (3,433 lines)

#### Functions Identified (43 total):

**Course CRUD Operations** (10 functions):
1. `add_course_to_curriculum()` - Line 29
2. `update_course_order_callback()` - Line 135
3. `save_new_course()` - Line 227
4. `courscribe_handle_course_update()` - Line 346
5. `handle_delete_course()` - Line 552
6. `create_course_ajax_handler()` - Line 2364
7. `update_course_ajax_handler()` - Line 2587
8. `archive_course_ajax_handler()` - Line 2792
9. `delete_course_ajax_handler()` - Line 2858
10. `unarchive_course_ajax_handler()` - Line 3173

**Course Objectives** (2 functions):
1. `update_objective_title()` - Line 128 (DEPRECATED)
2. `delete_objective()` - Line 483

**Course Logging** (4 functions):
1. `courscribe_get_course_logs()` - Line 617
2. `get_course_logs_ajax_handler()` - Line 2927
3. `format_course_log_changes()` - Line 3026
4. `restore_course_from_log_ajax_handler()` - Line 3063

**AI Generation** (5 functions):
1. `courscribe_generate_courses()` - Line 660
2. `handle_get_ai_suggestions()` - Line 2268
3. `parse_gemini_response()` - Line 2334
4. `handle_courscribe_get_ai_suggestions()` - Line 3240
5. `courscribe_parse_ai_suggestions()` - Line 3354

**Rich Text Editor** (3 functions):
1. `courscribe_check_richtexteditor_content()` - Line 904
2. `courscribe_get_the_course_data()` - Line 927
3. `courscribe_save_richtexteditor_content()` - Line 1042

**Slide Deck Generation** (9 functions):
1. `create_revealjs_preview()` - Line 1129
2. `courscribe_generate_test_slide()` - Line 1379
3. `get_course_data()` - Line 1494
4. `polish_text_with_gemini()` - Line 1536
5. `add_top_right_info()` - Line 1605
6. `create_course_slides()` - Line 1624
7. `create_module_slides()` - Line 1794
8. `create_lesson_slides()` - Line 2066
9. Various helper functions for PowerPoint generation

**Misplaced Functions** (1 function):
1. `courscribe_log_module_activity_actions()` - Line 3413 (SHOULD BE IN MODULE FILE!)

---

## Refactoring Strategy

### New File Structure

```
actions/
├── courses/
│   ├── course-crud.php              # CRUD operations (500 lines)
│   ├── course-objectives.php        # Objectives management (200 lines)
│   ├── course-logging.php           # Activity logging (300 lines)
│   ├── course-ai-generation.php     # AI content generation (400 lines)
│   ├── course-rich-editor.php       # Rich text editor handlers (300 lines)
│   └── course-slide-generation.php  # PowerPoint/RevealJS (1,200 lines)
├── modules/
│   └── [move module logging here]
├── lessons/
│   └── [consolidate lesson handlers]
└── shared/
    ├── class-activity-logger.php    # Shared logging functionality
    ├── class-ai-helper.php          # Shared AI utilities
    └── class-tier-validator.php     # Shared tier checking
```

---

## Phase 1: Extract Slide Generation (Lowest Risk)

### Step 1.1: Create `course-slide-generation.php`

**New File**: `actions/courses/course-slide-generation.php`

**Functions to move** (9 functions, ~1,200 lines):
- `create_revealjs_preview()`
- `courscribe_generate_test_slide()`
- `get_course_data()`
- `polish_text_with_gemini()`
- `add_top_right_info()`
- `create_course_slides()`
- `create_module_slides()`
- `create_lesson_slides()`
- All related helper functions

**AJAX Hooks**:
```php
add_action('wp_ajax_generate_test_slide', 'courscribe_generate_test_slide');
```

**Dependencies**:
- PHPOffice/PHPPresentation
- Google Gemini API
- Course/Module/Lesson data retrieval

---

### Step 1.2: Create `course-rich-editor.php`

**New File**: `actions/courses/course-rich-editor.php`

**Functions to move** (3 functions, ~300 lines):
- `courscribe_check_richtexteditor_content()`
- `courscribe_get_the_course_data()`
- `courscribe_save_richtexteditor_content()`

**AJAX Hooks**:
```php
add_action('wp_ajax_courscribe_check_richtexteditor', 'courscribe_check_richtexteditor_content');
add_action('wp_ajax_courscribe_get_the_course_data', 'courscribe_get_the_course_data');
add_action('wp_ajax_courscribe_save_richtexteditor', 'courscribe_save_richtexteditor_content');
```

---

## Phase 2: Extract Core Functionality

### Step 2.1: Create `course-crud.php`

**New File**: `actions/courses/course-crud.php`

**Functions to move** (10 functions, ~800 lines):
- All create/update/delete/archive operations
- Course order management
- Tier validation for course limits

**AJAX Hooks**:
```php
add_action('wp_ajax_add_course_to_curriculum', 'add_course_to_curriculum');
add_action('wp_ajax_create_course_ajax', 'create_course_ajax_handler');
add_action('wp_ajax_update_course_ajax', 'update_course_ajax_handler');
add_action('wp_ajax_archive_course', 'archive_course_ajax_handler');
add_action('wp_ajax_unarchive_course', 'unarchive_course_ajax_handler');
add_action('wp_ajax_delete_course', 'delete_course_ajax_handler');
add_action('wp_ajax_update_course_order', 'update_course_order_callback');
add_action('wp_ajax_save_new_course', 'save_new_course');
add_action('wp_ajax_update_course', 'courscribe_handle_course_update');
```

---

### Step 2.2: Create `course-logging.php`

**New File**: `actions/courses/course-logging.php`

**Functions to move** (4 functions, ~300 lines):
- `courscribe_get_course_logs()`
- `get_course_logs_ajax_handler()`
- `format_course_log_changes()`
- `restore_course_from_log_ajax_handler()`

**AJAX Hooks**:
```php
add_action('wp_ajax_courscribe_get_course_logs', 'courscribe_get_course_logs');
add_action('wp_ajax_get_course_logs', 'get_course_logs_ajax_handler');
add_action('wp_ajax_restore_course_from_log', 'restore_course_from_log_ajax_handler');
```

---

### Step 2.3: Create `course-ai-generation.php`

**New File**: `actions/courses/course-ai-generation.php`

**Functions to move** (5 functions, ~400 lines):
- `courscribe_generate_courses()`
- `handle_get_ai_suggestions()`
- `parse_gemini_response()`
- `handle_courscribe_get_ai_suggestions()`
- `courscribe_parse_ai_suggestions()`

**AJAX Hooks**:
```php
add_action('wp_ajax_courscribe_generate_courses', 'courscribe_generate_courses');
add_action('wp_ajax_get_ai_suggestions', 'handle_get_ai_suggestions');
add_action('wp_ajax_courscribe_get_ai_suggestions', 'handle_courscribe_get_ai_suggestions');
```

---

### Step 2.4: Create `course-objectives.php`

**New File**: `actions/courses/course-objectives.php`

**Functions to move** (2 functions, ~100 lines):
- `delete_objective()` (keep functional one)
- Remove `update_objective_title()` (deprecated)

**AJAX Hooks**:
```php
add_action('wp_ajax_delete_objective', 'delete_objective');
```

---

## Phase 3: Create Shared Utilities

### Step 3.1: Create `class-activity-logger.php`

**New File**: `actions/shared/class-activity-logger.php`

```php
<?php
/**
 * Centralized Activity Logging
 */
class Courscribe_Activity_Logger {

    /**
     * Log course activity
     */
    public static function log_course_activity($course_id, $action, $changes = [], $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return $wpdb->insert(
            $wpdb->prefix . 'courscribe_course_log',
            [
                'course_id' => $course_id,
                'user_id' => $user_id,
                'action' => $action,
                'changes' => wp_json_encode($changes),
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Log module activity
     */
    public static function log_module_activity($module_id, $action, $changes = [], $user_id = null) {
        // Move from courscribe-course-actions.php line 3413
        // Implementation here
    }

    /**
     * Get activity logs
     */
    public static function get_logs($entity_type, $entity_id, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . "courscribe_{$entity_type}_log";

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$entity_type}_id = %d ORDER BY timestamp DESC LIMIT %d",
            $entity_id,
            $limit
        ));
    }
}
```

---

### Step 3.2: Create `class-tier-validator.php`

**New File**: `actions/shared/class-tier-validator.php`

```php
<?php
/**
 * Subscription Tier Validation
 */
class Courscribe_Tier_Validator {

    private static $tier_limits = [
        'basics' => ['courses' => 1, 'modules_per_course' => 5, 'ai_generations' => 10],
        'plus' => ['courses' => 2, 'modules_per_course' => 10, 'ai_generations' => 50],
        'pro' => ['courses' => -1, 'modules_per_course' => -1, 'ai_generations' => -1]
    ];

    /**
     * Check if user can add course to curriculum
     */
    public static function can_add_course($curriculum_id) {
        $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
        $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';

        $course_count = self::get_course_count($curriculum_id);
        $limit = self::$tier_limits[$tier]['courses'];

        if ($limit === -1) {
            return ['allowed' => true];
        }

        if ($course_count >= $limit) {
            return [
                'allowed' => false,
                'message' => "Your {$tier} plan allows only {$limit} course(s). Upgrade to add more."
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Get current course count for curriculum
     */
    private static function get_course_count($curriculum_id) {
        return count(get_posts([
            'post_type' => 'crscribe_course',
            'post_status' => 'publish',
            'meta_query' => [[
                'key' => '_curriculum_id',
                'value' => $curriculum_id,
                'compare' => '='
            ]],
            'fields' => 'ids'
        ]));
    }

    /**
     * Get tier limits
     */
    public static function get_tier_limits($tier) {
        return self::$tier_limits[$tier] ?? self::$tier_limits['basics'];
    }
}
```

---

### Step 3.3: Create `class-ai-helper.php`

**New File**: `actions/shared/class-ai-helper.php`

```php
<?php
/**
 * AI Integration Utilities
 */
class Courscribe_AI_Helper {

    /**
     * Parse Gemini API response
     */
    public static function parse_response($response, $format = 'json') {
        // Move common parsing logic here
        // Currently duplicated in multiple files
    }

    /**
     * Format prompt for course generation
     */
    public static function format_course_prompt($curriculum_data, $options = []) {
        // Centralized prompt formatting
    }

    /**
     * Validate AI response structure
     */
    public static function validate_ai_response($response, $expected_structure = []) {
        // Validation logic
    }
}
```

---

## Phase 4: Update Main Loader

### Update `courscribe.php`

**Current** (around line 200):
```php
require_once plugin_dir_path(__FILE__) . 'actions/courscribe-course-actions.php';
```

**New**:
```php
// Load shared utilities
require_once plugin_dir_path(__FILE__) . 'actions/shared/class-activity-logger.php';
require_once plugin_dir_path(__FILE__) . 'actions/shared/class-tier-validator.php';
require_once plugin_dir_path(__FILE__) . 'actions/shared/class-ai-helper.php';

// Load course handlers
require_once plugin_dir_path(__FILE__) . 'actions/courses/course-crud.php';
require_once plugin_dir_path(__FILE__) . 'actions/courses/course-objectives.php';
require_once plugin_dir_path(__FILE__) . 'actions/courses/course-logging.php';
require_once plugin_dir_path(__FILE__) . 'actions/courses/course-ai-generation.php';
require_once plugin_dir_path(__FILE__) . 'actions/courses/course-rich-editor.php';
require_once plugin_dir_path(__FILE__) . 'actions/courses/course-slide-generation.php';
```

---

## Implementation Timeline

### Week 1: Low-Risk Extractions
- [x] Day 1-2: Create `course-slide-generation.php` and test
- [x] Day 3-4: Create `course-rich-editor.php` and test
- [x] Day 5: Integration testing

### Week 2: Core CRUD Operations
- [ ] Day 1-2: Create `course-crud.php`
- [ ] Day 3: Create `course-logging.php`
- [ ] Day 4-5: Testing and bug fixes

### Week 3: AI and Utilities
- [ ] Day 1-2: Create `course-ai-generation.php`
- [ ] Day 3: Create `course-objectives.php`
- [ ] Day 4-5: Create shared utility classes

### Week 4: Integration and Cleanup
- [ ] Day 1-2: Update `courscribe.php` includes
- [ ] Day 3: Move original file to `/redundant/`
- [ ] Day 4-5: Comprehensive testing

---

## Testing Strategy

### Unit Testing Checklist
- [ ] Each AJAX endpoint responds correctly
- [ ] Nonce verification works
- [ ] Permission checks function properly
- [ ] Database operations execute without errors
- [ ] Activity logging captures all changes
- [ ] Tier validation prevents unauthorized operations

### Integration Testing
- [ ] Full curriculum creation workflow
- [ ] Course CRUD operations from frontend
- [ ] AI generation features
- [ ] Slide deck generation
- [ ] Rich text editor functionality
- [ ] Activity log restoration

### Browser Testing
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile browsers

---

## Rollback Plan

1. **Keep original file**: Don't delete `courscribe-course-actions.php` immediately
2. **Feature flag**: Add `COURSCRIBE_USE_REFACTORED_HANDLERS` constant
3. **Conditional loading**: Load old or new files based on flag
4. **Monitoring period**: 30 days of production use before permanent deletion
5. **Backup location**: Move to `/redundant/actions/` after successful migration

---

## Benefits

### Code Maintainability
- **Before**: 3,433 lines in one file
- **After**: ~500 lines per file (6 files + 3 utility classes)
- **Improvement**: 85% reduction in file size

### Developer Experience
- Clear separation of concerns
- Easy to locate specific functionality
- Reduced merge conflicts
- Better code organization
- Easier onboarding for new developers

### Performance
- Conditional loading of slide generation (only when needed)
- Shared utilities reduce code duplication
- Cleaner class autoloading

### Testing
- Isolated functionality easier to test
- Unit tests for each component
- Reduced test complexity

---

## Dependencies

### External Libraries
- PHPOffice/PHPPresentation (slide generation)
- Google Gemini API (AI features)
- WordPress hooks and database

### Internal Dependencies
- Custom post types (courses, modules, lessons)
- Custom database tables (logging)
- Meta data structure

---

## Risk Assessment

### Low Risk (Extract First)
✅ Slide generation (self-contained)
✅ Rich text editor (minimal dependencies)

### Medium Risk
⚠️ CRUD operations (core functionality)
⚠️ Logging (used across multiple files)

### High Risk (Test Thoroughly)
⚠️⚠️ AI generation (complex integrations)
⚠️⚠️ Shared utilities (affects multiple files)

---

## Success Criteria

- [ ] All AJAX endpoints function identically
- [ ] No JavaScript console errors
- [ ] No PHP errors in logs
- [ ] All features work across subscription tiers
- [ ] Activity logs capture correctly
- [ ] Performance remains consistent or improves
- [ ] Code passes WordPress coding standards

---

## Next Steps

1. Review and approve this refactoring plan
2. Create `actions/courses/` directory structure
3. Start with Phase 1 (slide generation extraction)
4. Implement with thorough testing at each step
5. Document changes in CHANGELOG.md

---

**Status**: Awaiting approval for implementation
**Last Updated**: December 18, 2025
**Estimated Completion**: 4 weeks
