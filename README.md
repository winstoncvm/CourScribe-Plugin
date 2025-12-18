# CourScribe Plugin

![Version](https://img.shields.io/badge/version-1.2.2-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0+-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)

A comprehensive WordPress plugin for curriculum development and educational content management with AI-powered generation capabilities.

---

## 🎯 Overview

**CourScribe** is a professional curriculum development platform that enables educational institutions and content creators to build, manage, and deliver structured learning programs. With hierarchical content organization and AI integration, CourScribe streamlines the entire curriculum creation process.

---

## ✨ Key Features

### 📚 Hierarchical Content Structure
- **Studios** - Organizational workspace for teams
- **Curriculums** - Learning programs
- **Courses** - Subject areas
- **Modules** - Learning units
- **Lessons** - Class sessions
- **Teaching Points** - Specific concepts

### 🤖 AI-Powered Generation
- Automated course creation with Google Gemini AI
- Intelligent module and lesson generation
- Learning objective suggestions (Bloom's Taxonomy)
- Content enhancement and refinement

### 👥 Collaboration Features
- Role-based access control (Studio Admin, Collaborator, Client)
- Real-time feedback system
- Screenshot annotation tools
- Client review workflows
- Activity logging and version control

### 📊 Subscription Management
- **Basics Tier** - 1 course per curriculum
- **Plus Tier** - 2 courses per curriculum
- **Pro Tier** - Unlimited courses
- WooCommerce integration

### 🎨 Professional Tools
- Rich text editor with formatting
- Drag-and-drop course organization
- Slide deck generation (PowerPoint/RevealJS)
- PDF export capabilities
- Responsive design interface

---

## 📋 Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Server**: Apache/Nginx with mod_rewrite enabled
- **Memory**: 128MB minimum (256MB recommended)

---

## 🚀 Installation

### 1. Upload Plugin
```bash
# Via WordPress Admin
WordPress Admin → Plugins → Add New → Upload Plugin

# Or via FTP
/wp-content/plugins/courscribe/
```

### 2. Activate Plugin
```bash
WordPress Admin → Plugins → Activate "CourScribe"
```

### 3. Configure Settings
```bash
WordPress Admin → CourScribe → Settings
- Set up Google Gemini API key
- Configure subscription tiers
- Customize branding
```

### 4. Flush Rewrite Rules
```bash
WordPress Admin → Settings → Permalinks → Save Changes
```

---

## 📖 Usage

### Creating a Studio
1. Navigate to **CourScribe → Studios**
2. Click **Create New Studio**
3. Enter studio details (name, description)
4. Invite team members
5. Select subscription tier

### Building a Curriculum
1. Go to **Curriculums** within your studio
2. Click **Create Curriculum**
3. Fill in curriculum details:
   - Title
   - Topic
   - Learning Goal
   - Notes (optional)
4. Save curriculum

### Adding Courses
1. Open your curriculum
2. Click **Add Course** or **Generate with AI**
3. For AI generation:
   - Select tone (formal, casual, etc.)
   - Choose audience level (beginner, advanced, etc.)
   - Provide instructions
   - Generate courses
4. Customize generated content

### Creating Modules & Lessons
1. Within each course, add modules
2. Within each module, add lessons
3. Add teaching points to lessons
4. Include objectives, activities, and examples

---

## 🔧 Configuration

### API Integration
```php
// wp-config.php
define('COURSCRIBE_GEMINI_API_KEY', 'your-api-key-here');
```

### Custom Branding
```php
// functions.php
add_filter('courscribe_brand_color', function() {
    return '#E4B26F'; // Your brand color
});
```

### Subscription Limits
```php
// Customize tier limits
add_filter('courscribe_tier_limits', function($limits) {
    $limits['custom_tier'] = [
        'courses' => 5,
        'modules_per_course' => 15,
        'ai_generations' => 100
    ];
    return $limits;
});
```

---

## 📂 Project Structure

```
courscribe/
├── actions/              # AJAX handlers
├── assets/              # CSS, JS, images
├── includes/            # Core functionality
├── templates/           # Frontend templates
├── vendor/             # Composer dependencies
├── redundant/          # Archived code
├── courscribe.php      # Main plugin file
└── Documentation/
    ├── CLAUDE.md                      # Project guidelines
    ├── CLEANUP_SUMMARY.md             # Optimization overview
    ├── CONSOLIDATION_STRATEGY.md      # Template consolidation
    ├── REFACTORING_PLAN.md            # Action handler refactoring
    └── ASSET_OPTIMIZATION_PLAN.md     # Performance improvements
```

---

## 🛡️ Security

### Recent Security Hardening (v1.2.2)
- ✅ SQL injection prevention implemented
- ✅ Nonce verification standardized
- ✅ API key security validated
- ✅ MVP bypass restrictions disabled
- ✅ Input sanitization across all endpoints
- ✅ XSS protection enabled

### Security Best Practices
- All AJAX requests use nonce verification
- Capability-based authorization
- Prepared SQL statements
- Input sanitization with WordPress functions
- Output escaping for all user data

---

## 📊 Performance

### Optimization Status
- **Before**: 24+ HTTP requests, ~800KB assets, 3-4s load time
- **After (planned)**: 12-15 requests, ~400KB assets, 1.5-2s load time
- **Improvement**: 50% reduction across all metrics

### Optimization Plans
See [ASSET_OPTIMIZATION_PLAN.md](ASSET_OPTIMIZATION_PLAN.md) for details.

---

## 🗺️ Roadmap

### Phase 1: Asset Optimization (Weeks 1-2)
- [ ] Fix broken dependencies
- [ ] Implement asset manager class
- [ ] Conditional loading system

### Phase 2: Template Consolidation (Weeks 3-4)
- [ ] Unified generation templates
- [ ] Feature flag system
- [ ] Component extraction

### Phase 3: Action Refactoring (Weeks 5-8)
- [ ] Split large handler files
- [ ] Create shared utilities
- [ ] Improve code organization

### Phase 4: Build Process (Weeks 9-10)
- [ ] Webpack configuration
- [ ] CSS/JS minification
- [ ] Development workflow

### Phase 5: Performance (Weeks 11-12)
- [ ] Lazy loading
- [ ] Resource hints
- [ ] Critical path optimization

---

## 🐛 Troubleshooting

### Common Issues

**Issue**: Curriculum not saving
```bash
Solution: Check file permissions (755 for directories, 644 for files)
```

**Issue**: AI generation fails
```bash
Solution: Verify Gemini API key in Settings → API Configuration
```

**Issue**: Broken styling
```bash
Solution: Clear browser cache and flush WordPress permalinks
```

**Issue**: Permission errors
```bash
Solution: Ensure user has proper role (studio_admin or collaborator)
```

---

## 📝 Changelog

### Version 1.2.2 (2025-12-18)
- **Security**: Disabled MVP bypass for production security
- **Security**: Implemented SQL injection prevention
- **Security**: Standardized nonce verification
- **Cleanup**: Moved backup files to `/redundant/` folder
- **Documentation**: Created comprehensive optimization plans
- **Bug Fix**: Fixed lesson serialization issues
- **Bug Fix**: Resolved objective management errors

### Version 1.1.9
- Added premium generation wizard
- Enhanced module and lesson management
- Improved feedback system
- UI/UX refinements

---

## 🤝 Contributing

This is a proprietary plugin. For bug reports or feature requests, please contact the development team.

---

## 📄 License

**Proprietary** - All rights reserved.
This plugin is proprietary software developed for CourScribe.
Unauthorized copying, modification, or distribution is prohibited.

---

## 👨‍💻 Development Team

- **Lead Developer**: Winston
- **AI Integration**: Google Gemini
- **Framework**: WordPress 6.0+
- **Database**: MySQL/MariaDB

---

## 📧 Support

For support inquiries:
- **Email**: support@courscribe.com
- **Documentation**: [CLAUDE.md](CLAUDE.md)
- **Issues**: Contact development team

---

## 🙏 Acknowledgments

- **WordPress Community** - For the robust CMS platform
- **Google Gemini** - For AI-powered content generation
- **PHPOffice** - For slide deck generation capabilities
- **Bootstrap** - For responsive UI components

---

**Made with ❤️ for educators and curriculum developers**

*Last Updated: December 18, 2025*
*Version: 1.2.2*
