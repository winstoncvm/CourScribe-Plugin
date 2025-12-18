<?php
/* 
Template Name: CourScribe Landing Page 
*/

$site_url = home_url();
$plugin_url = plugin_dir_url(__FILE__ . '../../../');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CourScribe - The premier curriculum development platform. Create studios, collaborate with experts, and build world-class educational content with AI-powered tools.">
    <title>CourScribe - Premium Curriculum Development Platform</title>
    
    <!-- Preload critical assets -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fallback CSS loading (in case wp_enqueue doesn't work) -->
    <link rel="stylesheet" href="<?php echo esc_url($plugin_url); ?>assets/css/landing-premium.css?v=2.0.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    
    <!-- Debug CSS loading -->
    <style>
        /* This will be overridden by the premium CSS if it loads */
        .courscribe-landing {
            background: red !important;
        }
        .courscribe-landing.css-loaded {
            background: transparent !important;
        }
    </style>
    
    <?php wp_head(); ?>
</head>

<body class="courscribe-landing">
    <!-- Header -->
    <header class="landing-header" id="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                    <img src="<?php echo esc_url($plugin_url); ?>assets/images/courscribe/courscribe-logo-v2-orange@2x.png" alt="CourScribe Logo">
                </a>
                
                <nav class="nav-menu">
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="/select-tribe/">Pricing</a>
                    <!-- <a href="#about">About</a> -->
                </nav>
                
                <div class="header-cta">
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(home_url('/studio')); ?>" class="btn btn-ghost">Dashboard</a>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn btn-secondary">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn btn-ghost">Sign In</a>
                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-primary">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Build World-Class Curriculums with AI-Powered Precision</h1>
                    <p class="hero-subtitle">
                        CourScribe is the premier platform for curriculum development. Create studios, collaborate with experts, 
                        and build engaging educational content that transforms learning experiences.
                    </p>
                    
                    <div class="hero-cta">
                        <?php if (!is_user_logged_in()): ?>
                            <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket"></i>
                                Start Building Today
                            </a>
                            <a href="#demo" class="btn btn-secondary btn-lg">
                                <i class="fas fa-play"></i>
                                Watch Demo
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(home_url('/studio')); ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-tachometer-alt"></i>
                                Go to Dashboard
                            </a>
                            <a href="#features" class="btn btn-secondary btn-lg">
                                <i class="fas fa-info-circle"></i>
                                Learn More
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="stat">
                            <span class="stat-number">10K+</span>
                            <span class="stat-label">Curriculums Created</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Educational Studios</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">99.9%</span>
                            <span class="stat-label">Uptime</span>
                        </div>
                    </div>
                </div>
                
                <div class="hero-visual">
                    <img src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/images/courscribe/laptophero.png" alt="CourScribe Dashboard Preview" class="hero-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features section">
        <div class="container">
            <div class="features-header">
                <h2>Everything You Need for Curriculum Excellence</h2>
                <p>
                    Our comprehensive platform provides all the tools educators need to create, manage, 
                    and deliver exceptional learning experiences.
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Studio Collaboration</h3>
                    <p class="feature-description">
                        Create dedicated studios for your educational projects. Invite collaborators, 
                        assign roles, and work together seamlessly on curriculum development.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="feature-title">AI-Powered Content</h3>
                    <p class="feature-description">
                        Leverage advanced AI to generate course outlines, lesson plans, and educational 
                        content. Save time while maintaining quality and consistency.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3 class="feature-title">Structured Learning</h3>
                    <p class="feature-description">
                        Organize content in a clear hierarchy: Studios → Curriculums → Courses → 
                        Modules → Lessons. Perfect for complex educational programs.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Analytics & Insights</h3>
                    <p class="feature-description">
                        Track development progress, collaboration metrics, and content performance. 
                        Make data-driven decisions for your educational programs.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h3 class="feature-title">Export & Share</h3>
                    <p class="feature-description">
                        Export your curriculums to various formats including PDF, presentations, 
                        and LMS-compatible packages for easy distribution.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Enterprise Security</h3>
                    <p class="feature-description">
                        Bank-level security with role-based permissions, audit trails, and 
                        compliance features. Your educational content is always protected.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works section">
        <div class="container">
            <div class="how-it-works-header">
                <h2>Simple Process, Powerful Results</h2>
                <p>
                    Get started with CourScribe in minutes. Our intuitive workflow guides you 
                    from concept to completed curriculum.
                </p>
            </div>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Create Your Studio</h3>
                    <p class="step-description">
                        Set up your educational workspace. Define your brand, invite team members, 
                        and establish your curriculum development environment.
                    </p>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Design Curriculums</h3>
                    <p class="step-description">
                        Use our AI-powered tools to create comprehensive curriculums. Structure 
                        your content with courses, modules, and detailed lesson plans.
                    </p>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Collaborate & Refine</h3>
                    <p class="step-description">
                        Work with your team to review, edit, and perfect your educational content. 
                        Use our built-in feedback and approval systems.
                    </p>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Deploy & Scale</h3>
                    <p class="step-description">
                        Export your finished curriculums or prepare for LMS integration. 
                        Scale your educational programs with confidence.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Transform Your Curriculum Development?</h2>
                <p class="cta-subtitle">
                    Join thousands of educators and institutions already using CourScribe to create 
                    exceptional learning experiences.
                </p>
                
                <?php if (!is_user_logged_in()): ?>
                    <form class="cta-form" id="waitlist-form">
                        <input type="email" class="cta-input" placeholder="Enter your email address" required>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-right"></i>
                            Get Started Free
                        </button>
                    </form>
                    
                    <div class="trust-badges">
                        <div class="trust-badge">
                            <i class="fas fa-lock"></i>
                            <span>Enterprise Security</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-check-circle"></i>
                            <span>14-Day Free Trial</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-credit-card"></i>
                            <span>No Credit Card Required</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="hero-cta">
                        <a href="<?php echo esc_url(home_url('/studio')); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt"></i>
                            Access Your Dashboard
                        </a>
                        <a href="<?php echo esc_url(home_url('/create-studio')); ?>" class="btn btn-secondary btn-lg">
                            <i class="fas fa-plus"></i>
                            Create New Studio
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="footer-logo">
                        <img src="<?php echo esc_url($plugin_url); ?>assets/images/courscribe/courscribe-logo-v2-orange@2x.png" alt="CourScribe Logo" style="height: 32px;">
                        CourScribe
                    </a>
                    <p class="footer-description">
                        The premier platform for curriculum development. Empowering educators 
                        to create exceptional learning experiences with AI-powered tools.
                    </p>
                </div>
                
                <div class="footer-section">
                    <h4>Platform</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#integrations">Integrations</a></li>
                        <li><a href="#api">API</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul class="footer-links">
                        <li><a href="#documentation">Documentation</a></li>
                        <li><a href="#tutorials">Tutorials</a></li>
                        <li><a href="#blog">Blog</a></li>
                        <li><a href="#support">Support</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="#about">About</a></li>
                        <li><a href="#careers">Careers</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#privacy">Privacy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 CourScribe by CVM Worldwide. All rights reserved.</p>
                <div class="social-links">
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Check if CSS loaded properly
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.body;
            const computedStyle = window.getComputedStyle(body);
            if (computedStyle.fontFamily.includes('Inter')) {
                body.classList.add('css-loaded');
                console.log('CourScribe CSS loaded successfully');
            } else {
                console.error('CourScribe CSS failed to load');
            }
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Waitlist form handling
        <?php if (!is_user_logged_in()): ?>
        document.getElementById('waitlist-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Add your waitlist submission logic here
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'courscribe_join_waitlist',
                    email: email,
                    nonce: '<?php echo wp_create_nonce('courscribe_waitlist'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you! You\'ve been added to our waitlist.');
                    this.reset();
                } else {
                    alert(data.data.message || 'Something went wrong. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Something went wrong. Please try again.');
            });
        });
        <?php endif; ?>

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fadeInUp');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.feature-card, .step').forEach(el => {
            observer.observe(el);
        });
    </script>

    <?php wp_footer(); ?>
</body>
</html>