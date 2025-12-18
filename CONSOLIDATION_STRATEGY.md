# Template Consolidation Strategy
**Date**: December 18, 2025
**Author**: Development Team
**Status**: Planning Phase

---

## Overview

This document outlines the strategy to consolidate duplicate template files in the CourScribe plugin, reducing maintenance burden and improving code organization.

---

## Current State Analysis

### 1. Generation Templates (6 files → 3 files)

#### Current Structure
```
templates/template-parts/
├── generate-courses.php (81 lines)          # Basic version
├── generate-courses-premium.php (463 lines) # Premium version
├── generate-modules.php (74 lines)          # Basic version
├── generate-modules-premium.php (276 lines) # Premium version
├── generate-lessons.php (82 lines)          # Basic version
└── generate-lessons-premium.php (438 lines) # Premium version
```

#### Key Differences
- **Basic**: Simple offcanvas with tone, audience, count, and instructions
- **Premium**: Wizard-based interface with 4 steps, templates, previews, advanced options

#### Usage
- Basic versions included in `courscribe_single_curriculum_shortcode.php:569-571`
- Premium versions used conditionally based on subscription tier

---

### 2. Module Templates (3 files → 1 file)

#### Current Structure
```
templates/template-parts/
├── modules.php (1,166 lines)                    # Basic version
├── modules-premium.php (1,461 lines)            # Premium version
└── modules-premium-clean.php (135 lines)        # Simplified version
```

#### Usage
- Both `modules.php` and `modules-premium.php` included in `courscribe.php:212-213`
- Likely feature-flag driven within the codebase

#### Key Differences
- Premium version includes advanced AI generation features
- Premium has enhanced drag-drop and bulk operations
- Both share ~80% of base structure

---

### 3. Lesson Templates (3 files → 1 file)

#### Current Structure
```
templates/template-parts/
├── lessons.php (1,135 lines)                        # Basic version
├── lessons-premium.php (1,604 lines)                # Premium version
└── lessons-premium-enhanced.php (3,186 lines)       # Enhanced premium version
```

#### Usage
- Both `lessons.php` and `lessons-premium-enhanced.php` included in `courscribe.php:214-215`
- `lessons-premium.php` appears to be an intermediate version

#### Key Differences
- Enhanced version has teaching points, activities, objectives management
- Premium adds AI content generation
- Basic has simpler lesson structure

---

## Consolidation Strategy

### Phase 1: Template Function Abstraction (Recommended Approach)

Instead of maintaining separate files, use a **template loader pattern** with feature flags.

#### New Structure
```
templates/template-parts/
├── generation/
│   ├── courses.php           # Unified course generation
│   ├── modules.php           # Unified module generation
│   └── lessons.php           # Unified lesson generation
├── content/
│   ├── modules.php           # Unified module display
│   └── lessons.php           # Unified lesson display
└── helpers/
    ├── class-template-loader.php
    └── class-feature-flags.php
```

---

### Phase 2: Implementation Approach

#### Step 1: Create Template Loader Class

**File**: `templates/helpers/class-template-loader.php`

```php
<?php
/**
 * Template Loader with Feature Flags
 *
 * Loads appropriate template based on subscription tier and feature flags
 */
class Courscribe_Template_Loader {

    /**
     * Load generation template with tier-appropriate features
     *
     * @param string $type courses|modules|lessons
     * @param int $curriculum_id
     * @param string $tier basics|plus|pro
     */
    public static function load_generation_template($type, $curriculum_id, $tier = 'basics') {
        $premium_enabled = in_array($tier, ['plus', 'pro']);

        $args = [
            'curriculum_id' => $curriculum_id,
            'premium' => $premium_enabled,
            'tier' => $tier,
            'features' => self::get_tier_features($tier)
        ];

        $template_path = COURSCRIBE_PLUGIN_PATH . "templates/template-parts/generation/{$type}.php";

        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Get features available for tier
     */
    private static function get_tier_features($tier) {
        $features = [
            'basics' => [
                'ai_generation' => true,
                'wizard_interface' => false,
                'templates' => false,
                'bulk_operations' => false,
                'advanced_options' => false
            ],
            'plus' => [
                'ai_generation' => true,
                'wizard_interface' => true,
                'templates' => true,
                'bulk_operations' => true,
                'advanced_options' => false
            ],
            'pro' => [
                'ai_generation' => true,
                'wizard_interface' => true,
                'templates' => true,
                'bulk_operations' => true,
                'advanced_options' => true
            ]
        ];

        return $features[$tier] ?? $features['basics'];
    }
}
```

---

#### Step 2: Unified Template Structure

**Example**: `templates/template-parts/generation/courses.php`

```php
<?php
// Unified course generation template with feature flags
if (!defined('ABSPATH')) exit;

$premium = $args['premium'] ?? false;
$features = $args['features'] ?? [];
$curriculum_id = $args['curriculum_id'] ?? 0;
?>

<div id="generateCoursesOffcanvas" class="offcanvas offcanvas-end">
    <div class="offcanvas-header mt-6">
        <h5><?php echo $premium ? 'AI Course Generator' : 'Generate Courses'; ?></h5>
        <button class="courscribe-close-button btn-close" data-bs-dismiss="offcanvas">
            <span class="X"></span>
            <span class="Y"></span>
        </button>
    </div>

    <div class="offcanvas-body">
        <?php if ($features['wizard_interface']): ?>
            <!-- Premium: Wizard Interface -->
            <?php include 'components/generation-wizard.php'; ?>
        <?php else: ?>
            <!-- Basic: Simple Form -->
            <?php include 'components/generation-simple-form.php'; ?>
        <?php endif; ?>

        <?php if ($features['templates']): ?>
            <!-- Premium: Template Library -->
            <?php include 'components/template-library.php'; ?>
        <?php endif; ?>

        <!-- Common: Generation Results -->
        <div id="courscribe-generated-courses" class="mt-4">
            <!-- Dynamic results -->
        </div>

        <?php if ($features['bulk_operations']): ?>
            <!-- Premium: Bulk Operations -->
            <div class="bulk-actions">
                <button id="select-all">Select All</button>
                <button id="bulk-edit">Bulk Edit</button>
            </div>
        <?php endif; ?>
    </div>
</div>
```

---

#### Step 3: Update Main Shortcode Files

**File**: `templates/curriculums/shortcodes/courscribe_single_curriculum_shortcode.php`

**Before** (lines 569-571):
```php
include $templates_root . '/template-parts/generate-courses.php';
include $templates_root . '/template-parts/generate-modules.php';
include $templates_root . '/template-parts/generate-lessons.php';
```

**After**:
```php
// Load unified templates with tier-based features
$studio_id = get_post_meta($curriculum_id, '_studio_id', true);
$tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';

Courscribe_Template_Loader::load_generation_template('courses', $curriculum_id, $tier);
Courscribe_Template_Loader::load_generation_template('modules', $curriculum_id, $tier);
Courscribe_Template_Loader::load_generation_template('lessons', $curriculum_id, $tier);
```

---

#### Step 4: Update Main Plugin File

**File**: `courscribe.php`

**Before** (lines 212-215):
```php
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/modules.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/modules-premium.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/lessons.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/lessons-premium-enhanced.php';
```

**After**:
```php
// Load template loader
require_once plugin_dir_path(__FILE__) . 'templates/helpers/class-template-loader.php';

// Load unified content templates (these will use feature flags internally)
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/content/modules.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/content/lessons.php';
```

---

### Phase 3: Component Extraction

Break down large templates into reusable components:

```
templates/template-parts/components/
├── generation-wizard.php              # Premium wizard interface
├── generation-simple-form.php         # Basic generation form
├── template-library.php               # Premium template selection
├── content-preview-editor.php         # Content preview/editing
├── bulk-operations.php                # Bulk action controls
└── ai-options-panel.php              # Advanced AI configuration
```

---

## Migration Path

### Immediate Actions (Week 1)
1. ✅ Move backup files to `/redundant/`
2. Create `class-template-loader.php`
3. Create unified `generation/courses.php`
4. Test with all three tiers (basics, plus, pro)

### Short-term (Week 2-3)
1. Consolidate `generate-modules.php` files
2. Consolidate `generate-lessons.php` files
3. Update shortcode includes
4. Comprehensive testing across tiers

### Medium-term (Week 4-6)
1. Consolidate `modules.php` templates
2. Consolidate `lessons.php` templates
3. Extract components for reusability
4. Update `courscribe.php` includes

### Testing Checkpoints
- [ ] Basics tier: Simple forms work correctly
- [ ] Plus tier: Wizard interface loads properly
- [ ] Pro tier: All advanced features functional
- [ ] No regression in existing functionality
- [ ] Assets load correctly for all tiers
- [ ] AJAX handlers work with new structure

---

## Benefits

### Code Reduction
- **Before**: 10,101 lines across 12 files
- **After**: ~4,000 lines across 6 files
- **Reduction**: ~60% less code to maintain

### Maintainability
- Single source of truth for each feature
- Easier bug fixes (fix once, applies to all tiers)
- Clear feature flag system
- Better code organization

### Performance
- Fewer file includes
- Conditional loading of premium features
- Reduced server resource usage

### Developer Experience
- Clear separation of concerns
- Easy to understand tier differences
- Consistent patterns across templates
- Better documentation

---

## Deprecation Schedule

### Immediate Deprecation (Don't Delete Yet)
Move to `/redundant/templates/deprecated/`:
- `lessons-premium.php` (intermediate version, replaced by enhanced)
- `modules-premium-clean.php` (simplified version, rarely used)

### Phase 1 Deprecation (After Consolidation)
After generating unified generation templates:
- `generate-courses.php`
- `generate-courses-premium.php`
- `generate-modules.php`
- `generate-modules-premium.php`
- `generate-lessons.php`
- `generate-lessons-premium.php`

### Phase 2 Deprecation (After Content Consolidation)
After consolidating content templates:
- `modules.php`
- `modules-premium.php`
- `lessons.php`
- `lessons-premium-enhanced.php`

---

## Rollback Strategy

If issues arise during consolidation:

1. **Keep old files**: Don't delete original files immediately
2. **Feature flag toggle**: Add `COURSCRIBE_USE_LEGACY_TEMPLATES` constant
3. **Gradual migration**: Enable new system for Pro tier first, then Plus, then Basics
4. **Testing period**: 30 days of production testing before deletion
5. **Backup location**: All deprecated files in `/redundant/` for 6 months

---

## Alternative Approach: Minimal Changes

If the loader pattern is too complex, a simpler approach:

### Option B: Smart Includes

**File**: `templates/helpers/template-includes.php`

```php
<?php
function courscribe_include_generation_template($type, $curriculum_id) {
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    $premium = in_array($tier, ['plus', 'pro']);

    $suffix = $premium ? '-premium' : '';
    $file = "generate-{$type}{$suffix}.php";

    include COURSCRIBE_PLUGIN_PATH . "templates/template-parts/{$file}";
}
```

**Pros**: Minimal code changes, keeps existing files
**Cons**: Still maintains duplicate files, doesn't solve root problem

---

## Recommendation

**Proceed with Phase 1 (Template Loader Pattern)** for these reasons:

1. **Long-term maintainability**: Single source of truth
2. **Scalability**: Easy to add new tiers or features
3. **Code quality**: Professional architecture pattern
4. **Testing**: Easier to test with clear separation
5. **Documentation**: Self-documenting code structure

**Start with**: Generation templates (lowest risk, highest impact)
**Timeline**: 2-3 weeks for full consolidation
**Risk**: Low (keep old files as backup)

---

## Next Steps

1. Review and approve this strategy
2. Create `class-template-loader.php`
3. Build first unified template (`generation/courses.php`)
4. Test thoroughly with all three tiers
5. Iterate and refine based on findings
6. Roll out to remaining templates

---

**Status**: Awaiting approval
**Last Updated**: December 18, 2025
