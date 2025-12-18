# Redundant Files Archive

This folder contains deprecated, backup, and redundant files that are no longer actively used in the CourScribe plugin but are preserved for historical reference.

## Files Moved - December 18, 2025

### Template Backups
- **courscribe_single_curriculum_shortcode_backup.php**
  - Location: `templates/curriculums/shortcodes/`
  - Reason: Backup of single curriculum shortcode - active version exists
  - Date Archived: 2025-12-18

- **modules-premium-backup.php**
  - Location: `templates/template-parts/`
  - Reason: Backup of premium modules template - active version exists
  - Date Archived: 2025-12-18

- **lessons-premium-example.php**
  - Location: `templates/template-parts/`
  - Reason: Example template for lessons - not used in production
  - Date Archived: 2025-12-18

### Legacy JavaScript
- **curriculum.js**
  - Location: `js/`
  - Reason: Legacy JS file - functionality moved to `assets/js/courscribe/curriculums/`
  - Date Archived: 2025-12-18

- **studio.js**
  - Location: `js/`
  - Reason: Legacy JS file - functionality moved to `assets/js/courscribe/studio/`
  - Date Archived: 2025-12-18

---

## Guidelines

### When to Add Files Here
- Backup files created during development
- Legacy code replaced by newer implementations
- Example/demo files not used in production
- Deprecated functionality that may be referenced later

### When to Delete from Redundant
- After 6 months if no issues reported
- If confirmed not needed by development team
- If storage space becomes a concern

### DO NOT Add
- User-generated content
- Configuration files
- Active dependencies

---

## Restoration Instructions

If you need to restore any file:

1. Copy the file from `redundant/` back to its original location
2. Check for any code changes in the active version
3. Test thoroughly before deploying
4. Update this README to reflect restoration

---

**Last Updated**: December 18, 2025
**Maintained By**: Development Team
