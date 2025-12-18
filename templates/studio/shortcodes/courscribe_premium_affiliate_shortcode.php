<?php
// Premium CourScribe Affiliate Dashboard Shortcode
// Complete affiliate management system with tracking, commissions, and analytics
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_premium_affiliate_shortcode($atts) {
    // Check authentication and permissions
    if (!is_user_logged_in()) {
        return courscribe_premium_auth_required();
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Check if user has affiliate access
    $user_roles = $current_user->roles;
    $is_studio_admin = in_array('studio_admin', $user_roles);
    $is_wp_admin = current_user_can('administrator');
    $is_affiliate = get_user_meta($user_id, '_courscribe_affiliate_enabled', true);
    
    if (!$is_studio_admin && !$is_wp_admin && !$is_affiliate) {
        return '<div class="courscribe-no-access">
            <div class="no-access-message">
                <i class="fas fa-lock"></i>
                <h3>Affiliate Access Required</h3>
                <p>Contact support to enable affiliate features for your account.</p>
            </div>
        </div>';
    }

    // Get affiliate data
    $affiliate_data = courscribe_get_affiliate_data($user_id);
    $affiliate_stats = courscribe_get_affiliate_stats($user_id);
    $recent_referrals = courscribe_get_recent_referrals($user_id, 10);
    $commission_history = courscribe_get_commission_history($user_id, 20);
    
    ob_start();
    ?>

    <div class="courscribe-affiliate-dashboard">
        
        <!-- Affiliate Hero Section -->
        <div class="affiliate-hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="dashboard-title">
                        <i class="fas fa-handshake gradient-icon"></i>
                        <span class="gradient-text">Affiliate Dashboard</span>
                    </h1>
                    <p class="dashboard-subtitle">
                        Grow your income by sharing CourScribe with your network
                    </p>
                </div>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="stat-value"><?php echo esc_html($affiliate_stats['total_earnings']); ?></div>
                        <div class="stat-label">Total Earnings</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-value"><?php echo esc_html($affiliate_stats['total_referrals']); ?></div>
                        <div class="stat-label">Referrals</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-value"><?php echo esc_html($affiliate_stats['conversion_rate']); ?>%</div>
                        <div class="stat-label">Conversion Rate</div>
                    </div>
                </div>
            </div>
            
            <div class="affiliate-quick-actions">
                <button class="btn-premium" onclick="generateReferralLink()">
                    <i class="fas fa-link"></i>
                    <span>Generate Link</span>
                </button>
                <button class="btn-secondary" onclick="copyAffiliateCode()">
                    <i class="fas fa-copy"></i>
                    <span>Copy Code</span>
                </button>
                <button class="btn-secondary" onclick="requestPayout()">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Request Payout</span>
                </button>
            </div>
        </div>

        <!-- Affiliate Stats Grid -->
        <div class="affiliate-stats-grid">
            
            <!-- Earnings Overview Card -->
            <div class="affiliate-card earnings-card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="header-content">
                        <h3 class="card-title">Earnings Overview</h3>
                        <p class="card-subtitle">Your commission performance</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="earnings-summary">
                        <div class="earning-item primary">
                            <div class="earning-amount">$<?php echo number_format($affiliate_stats['total_earnings'], 2); ?></div>
                            <div class="earning-label">Total Earned</div>
                            <div class="earning-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+<?php echo $affiliate_stats['earnings_growth']; ?>%</span>
                            </div>
                        </div>
                        
                        <div class="earning-item">
                            <div class="earning-amount">$<?php echo number_format($affiliate_stats['pending_earnings'], 2); ?></div>
                            <div class="earning-label">Pending</div>
                        </div>
                        
                        <div class="earning-item">
                            <div class="earning-amount">$<?php echo number_format($affiliate_stats['paid_earnings'], 2); ?></div>
                            <div class="earning-label">Paid Out</div>
                        </div>
                    </div>
                    
                    <div class="earnings-chart">
                        <canvas id="earnings-chart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Referral Links Card -->
            <div class="affiliate-card referral-links-card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="header-content">
                        <h3 class="card-title">Referral Links</h3>
                        <p class="card-subtitle">Share and track your links</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="referral-code-section">
                        <div class="code-display">
                            <label>Your Affiliate Code</label>
                            <div class="code-input-group">
                                <input type="text" id="affiliate-code" value="<?php echo esc_attr($affiliate_data['affiliate_code']); ?>" readonly>
                                <button class="copy-btn" onclick="copyToClipboard('affiliate-code')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="link-generator">
                        <label>Generate Referral Link</label>
                        <div class="link-builder">
                            <select id="link-type" class="form-control">
                                <option value="pricing">Pricing Page</option>
                                <option value="home">Home Page</option>
                                <option value="register">Registration</option>
                                <option value="demo">Demo Request</option>
                            </select>
                            <button class="btn-primary" onclick="generateCustomLink()">
                                <i class="fas fa-magic"></i>
                                Generate
                            </button>
                        </div>
                        
                        <div class="generated-link" id="generated-link-section" style="display: none;">
                            <div class="link-input-group">
                                <input type="text" id="generated-link" readonly>
                                <button class="copy-btn" onclick="copyToClipboard('generated-link')">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button class="share-btn" onclick="shareLink()">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics Card -->
            <div class="affiliate-card performance-card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="header-content">
                        <h3 class="card-title">Performance Metrics</h3>
                        <p class="card-subtitle">Track your success</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="metric-content">
                                <div class="metric-value"><?php echo number_format($affiliate_stats['total_clicks']); ?></div>
                                <div class="metric-label">Total Clicks</div>
                                <div class="metric-trend positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+12%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="metric-content">
                                <div class="metric-value"><?php echo number_format($affiliate_stats['total_conversions']); ?></div>
                                <div class="metric-label">Conversions</div>
                                <div class="metric-trend positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+8%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="metric-content">
                                <div class="metric-value"><?php echo $affiliate_stats['conversion_rate']; ?>%</div>
                                <div class="metric-label">Conversion Rate</div>
                                <div class="metric-trend neutral">
                                    <i class="fas fa-minus"></i>
                                    <span>0%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="metric-content">
                                <div class="metric-value">$<?php echo number_format($affiliate_stats['avg_commission'], 2); ?></div>
                                <div class="metric-label">Avg Commission</div>
                                <div class="metric-trend positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+5%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Main Content Grid -->
        <div class="affiliate-content-grid">
            
            <!-- Recent Referrals -->
            <div class="affiliate-card recent-referrals-card">
                <div class="card-header">
                    <div class="header-content">
                        <h3 class="card-title">
                            <i class="fas fa-users"></i>
                            Recent Referrals
                        </h3>
                        <p class="card-subtitle">Your latest successful referrals</p>
                    </div>
                    <div class="card-actions">
                        <button class="btn-secondary" onclick="refreshReferrals()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="referrals-table">
                        <?php if (empty($recent_referrals)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-friends"></i>
                                <h4>No Referrals Yet</h4>
                                <p>Start sharing your referral links to see your referrals here.</p>
                                <button class="btn-primary" onclick="generateReferralLink()">
                                    Generate Referral Link
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="referrals-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Date</th>
                                            <th>Plan</th>
                                            <th>Commission</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_referrals as $referral): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-avatar">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <div class="user-details">
                                                            <div class="user-name"><?php echo esc_html($referral['user_name']); ?></div>
                                                            <div class="user-email"><?php echo esc_html($referral['user_email']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo esc_html($referral['date']); ?></td>
                                                <td>
                                                    <span class="plan-badge <?php echo esc_attr($referral['plan']); ?>">
                                                        <?php echo esc_html(ucfirst($referral['plan'])); ?>
                                                    </span>
                                                </td>
                                                <td class="commission-amount">
                                                    $<?php echo number_format($referral['commission'], 2); ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo esc_attr($referral['status']); ?>">
                                                        <?php echo esc_html(ucfirst($referral['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Commission History -->
            <div class="affiliate-card commission-history-card">
                <div class="card-header">
                    <div class="header-content">
                        <h3 class="card-title">
                            <i class="fas fa-receipt"></i>
                            Commission History
                        </h3>
                        <p class="card-subtitle">Track your commission payments</p>
                    </div>
                    <div class="card-actions">
                        <select class="filter-select" id="commission-filter">
                            <option value="all">All Time</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="commission-summary">
                        <div class="summary-item">
                            <div class="summary-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="summary-content">
                                <div class="summary-value">$<?php echo number_format($affiliate_stats['pending_earnings'], 2); ?></div>
                                <div class="summary-label">Pending Commissions</div>
                            </div>
                        </div>
                        
                        <div class="summary-item">
                            <div class="summary-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="summary-content">
                                <div class="summary-value">$<?php echo number_format($affiliate_stats['paid_earnings'], 2); ?></div>
                                <div class="summary-label">Paid Commissions</div>
                            </div>
                        </div>
                        
                        <div class="summary-item">
                            <div class="summary-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="summary-content">
                                <div class="summary-value"><?php echo esc_html($affiliate_stats['next_payout_date']); ?></div>
                                <div class="summary-label">Next Payout</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="commission-list">
                        <?php if (empty($commission_history)): ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <h4>No Commission History</h4>
                                <p>Your commission history will appear here once you start earning.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($commission_history as $commission): ?>
                                <div class="commission-item">
                                    <div class="commission-info">
                                        <div class="commission-date"><?php echo esc_html($commission['date']); ?></div>
                                        <div class="commission-description"><?php echo esc_html($commission['description']); ?></div>
                                    </div>
                                    <div class="commission-amount">
                                        $<?php echo number_format($commission['amount'], 2); ?>
                                    </div>
                                    <div class="commission-status">
                                        <span class="status-badge <?php echo esc_attr($commission['status']); ?>">
                                            <?php echo esc_html(ucfirst($commission['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Marketing Resources -->
        <div class="affiliate-card marketing-resources-card">
            <div class="card-header">
                <div class="header-content">
                    <h3 class="card-title">
                        <i class="fas fa-bullhorn"></i>
                        Marketing Resources
                    </h3>
                    <p class="card-subtitle">Tools to help you promote CourScribe</p>
                </div>
            </div>
            <div class="card-body">
                <div class="resources-grid">
                    
                    <div class="resource-item">
                        <div class="resource-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <div class="resource-content">
                            <h4>Banner Images</h4>
                            <p>High-quality banners for your website or blog</p>
                            <button class="btn-secondary" onclick="downloadBanners()">
                                Download Pack
                            </button>
                        </div>
                    </div>
                    
                    <div class="resource-item">
                        <div class="resource-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="resource-content">
                            <h4>Email Templates</h4>
                            <p>Ready-made email templates for your campaigns</p>
                            <button class="btn-secondary" onclick="viewEmailTemplates()">
                                View Templates
                            </button>
                        </div>
                    </div>
                    
                    <div class="resource-item">
                        <div class="resource-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div class="resource-content">
                            <h4>Social Media Kit</h4>
                            <p>Posts and graphics for social media promotion</p>
                            <button class="btn-secondary" onclick="downloadSocialKit()">
                                Download Kit
                            </button>
                        </div>
                    </div>
                    
                    <div class="resource-item">
                        <div class="resource-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="resource-content">
                            <h4>Product Videos</h4>
                            <p>Promotional videos to showcase CourScribe</p>
                            <button class="btn-secondary" onclick="viewVideos()">
                                View Videos
                            </button>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <!-- Payout Request Modal -->
        <div class="modal-overlay" id="payout-modal-overlay" style="display: none;">
            <div class="modal affiliate-modal" id="payout-modal">
                <div class="modal-header">
                    <div class="modal-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Request Payout</h3>
                    <button class="modal-close" onclick="closePayoutModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="payout-form">
                        <div class="payout-summary">
                            <div class="summary-row">
                                <span>Available Balance:</span>
                                <span class="amount">$<?php echo number_format($affiliate_stats['pending_earnings'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Minimum Payout:</span>
                                <span class="amount">$50.00</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="payout-amount">Payout Amount</label>
                            <input type="number" id="payout-amount" class="form-control" 
                                   min="50" max="<?php echo $affiliate_stats['pending_earnings']; ?>" 
                                   placeholder="Enter amount">
                        </div>
                        
                        <div class="form-group">
                            <label for="payout-method">Payout Method</label>
                            <select id="payout-method" class="form-control">
                                <option value="paypal">PayPal</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="payout-notes">Notes (Optional)</label>
                            <textarea id="payout-notes" class="form-control" rows="3" 
                                      placeholder="Any special instructions..."></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closePayoutModal()">Cancel</button>
                        <button class="btn-primary" onclick="submitPayoutRequest()">
                            <i class="fas fa-paper-plane"></i>
                            Submit Request
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Premium Affiliate Dashboard Styles - Dark Mode -->
    <style>
        .courscribe-affiliate-dashboard {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #231F20;
            color: #FFFFFF;
            min-height: 100vh;
        }

        /* Hero Section */
        .affiliate-hero {
            background: linear-gradient(135deg, #2a2a2b 0%, #353535 100%);
            border: 1px solid rgba(228, 178, 111, 0.2);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .affiliate-hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(228, 178, 111, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 0 10px;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .gradient-icon {
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-text {
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .dashboard-subtitle {
            font-size: 1.1rem;
            color: #B0B0B0;
            margin: 0;
        }

        .hero-stats {
            display: flex;
            gap: 40px;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #E4B26F;
            margin-bottom: 5px;
        }

        .hero-stat .stat-label {
            font-size: 0.9rem;
            color: #B0B0B0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .affiliate-quick-actions {
            display: flex;
            gap: 15px;
        }

        /* Button Styles */
        .btn-premium {
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            color: #231F20;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(228, 178, 111, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 12px 24px;
            border-radius: 12px;
            color: #FFFFFF;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-primary {
            background: linear-gradient(45deg, #F8923E, #F25C3B);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            color: #FFFFFF;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(248, 146, 62, 0.3);
        }

        /* Stats Grid */
        .affiliate-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Cards */
        .affiliate-card {
            background: #2f2f2f;
            border: 1px solid rgba(228, 178, 111, 0.1);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .affiliate-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 40px rgba(228, 178, 111, 0.2);
            border-color: rgba(228, 178, 111, 0.3);
        }

        .card-header {
            padding: 24px 24px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }

        .header-icon i {
            color: #FFFFFF;
            font-size: 1.2rem;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #FFFFFF;
            margin: 0 0 4px;
        }

        .card-subtitle {
            color: #B0B0B0;
            font-size: 0.9rem;
            margin: 0;
        }

        .card-body {
            padding: 24px;
        }

        /* Earnings Card */
        .earnings-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .earning-item {
            flex: 1;
            text-align: center;
            padding: 20px;
            background: #353535;
            border: 1px solid rgba(228, 178, 111, 0.1);
            border-radius: 12px;
        }

        .earning-item.primary {
            background: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%);
            color: #FFFFFF;
        }

        .earning-amount {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .earning-label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 8px;
        }

        .earning-change {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .earning-change.positive {
            color: #10B981;
        }

        .earning-item.primary .earning-change {
            color: rgba(255, 255, 255, 0.9);
        }

        /* Referral Links Card */
        .referral-code-section {
            margin-bottom: 30px;
        }

        .code-display label {
            display: block;
            font-weight: 600;
            color: #FFFFFF;
            margin-bottom: 8px;
        }

        .code-input-group, .link-input-group {
            display: flex;
            gap: 8px;
        }

        .code-input-group input, .link-input-group input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid rgba(228, 178, 111, 0.2);
            border-radius: 8px;
            font-family: monospace;
            font-size: 1rem;
            background: #353535;
            color: #FFFFFF;
        }

        .code-input-group input:focus, .link-input-group input:focus {
            border-color: #E4B26F;
            outline: none;
        }

        .copy-btn, .share-btn {
            padding: 12px 16px;
            background: #E4B26F;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .copy-btn:hover, .share-btn:hover {
            background: #D4A05C;
        }

        .link-generator label {
            display: block;
            font-weight: 600;
            color: #FFFFFF;
            margin-bottom: 8px;
        }

        .link-builder {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .form-control {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid rgba(228, 178, 111, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            background: #353535;
            color: #FFFFFF;
        }

        .form-control:focus {
            border-color: #E4B26F;
            outline: none;
        }

        .generated-link {
            margin-top: 16px;
        }

        /* Performance Metrics */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .metric-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: #353535;
            border: 1px solid rgba(228, 178, 111, 0.1);
            border-radius: 12px;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .metric-icon i {
            color: #FFFFFF;
            font-size: 1.1rem;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 4px;
        }

        .metric-label {
            font-size: 0.9rem;
            color: #B0B0B0;
            margin-bottom: 4px;
        }

        .metric-trend {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .metric-trend.positive {
            color: #10B981;
        }

        .metric-trend.neutral {
            color: #6B7280;
        }

        /* Content Grid */
        .affiliate-content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .referrals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .referrals-table th,
        .referrals-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(228, 178, 111, 0.1);
        }

        .referrals-table th {
            font-weight: 600;
            color: #E0E0E0;
            background: #353535;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: #E4B26F;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
        }

        .user-name {
            font-weight: 600;
            color: #FFFFFF;
        }

        .user-email {
            font-size: 0.9rem;
            color: #B0B0B0;
        }

        .plan-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .plan-badge.basics {
            background: #E5E7EB;
            color: #374151;
        }

        .plan-badge.plus {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .plan-badge.pro {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-badge.paid {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-badge.processing {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .commission-amount {
            font-weight: 700;
            color: #10B981;
        }

        /* Commission History */
        .commission-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: #353535;
            border: 1px solid rgba(228, 178, 111, 0.1);
            border-radius: 12px;
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .summary-icon i {
            color: #FFFFFF;
            font-size: 1.1rem;
        }

        .summary-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 4px;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #B0B0B0;
        }

        .commission-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .commission-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid rgba(228, 178, 111, 0.1);
        }

        .commission-date {
            font-size: 0.9rem;
            color: #B0B0B0;
            margin-bottom: 4px;
        }

        .commission-description {
            font-weight: 600;
            color: #FFFFFF;
        }

        /* Marketing Resources */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .resource-item {
            padding: 24px;
            background: #353535;
            border: 1px solid rgba(228, 178, 111, 0.1);
            border-radius: 12px;
            text-align: center;
        }

        .resource-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .resource-icon i {
            color: #FFFFFF;
            font-size: 1.5rem;
        }

        .resource-item h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #FFFFFF;
            margin: 0 0 8px;
        }

        .resource-item p {
            color: #B0B0B0;
            margin: 0 0 16px;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #B0B0B0;
        }

        .empty-state i {
            font-size: 4rem;
            color: rgba(228, 178, 111, 0.3);
            margin-bottom: 20px;
        }

        .empty-state h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #FFFFFF;
            margin: 0 0 12px;
        }

        .empty-state p {
            margin: 0 0 24px;
            color: #B0B0B0;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .affiliate-modal {
            background: #2f2f2f;
            border: 1px solid rgba(228, 178, 111, 0.2);
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid rgba(228, 178, 111, 0.2);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .modal-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-icon i {
            color: #FFFFFF;
            font-size: 1.2rem;
        }

        .modal-header h3 {
            flex: 1;
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: #FFFFFF;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #B0B0B0;
            cursor: pointer;
            padding: 8px;
        }

        .modal-close:hover {
            color: #FFFFFF;
        }

        .modal-body {
            padding: 24px;
        }

        .payout-summary {
            background: #353535;
            border: 1px solid rgba(228, 178, 111, 0.1);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-row .amount {
            font-weight: 700;
            color: #E4B26F;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #FFFFFF;
            margin-bottom: 8px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .affiliate-stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .courscribe-affiliate-dashboard {
                padding: 15px;
            }

            .affiliate-hero {
                padding: 20px;
            }

            .hero-content {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }

            .hero-stats {
                gap: 20px;
            }

            .dashboard-title {
                font-size: 2rem;
            }

            .affiliate-stats-grid {
                grid-template-columns: 1fr;
            }

            .affiliate-content-grid {
                grid-template-columns: 1fr;
            }

            .affiliate-quick-actions {
                flex-wrap: wrap;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .earnings-summary {
                flex-direction: column;
                gap: 15px;
            }

            .commission-summary {
                flex-direction: column;
                gap: 15px;
            }

            .resources-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation Classes */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .affiliate-card {
            animation: slideIn 0.6s ease-out;
        }

        /* Filter Select */
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        /* No Access State */
        .courscribe-no-access {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }

        .no-access-message {
            text-align: center;
            padding: 40px;
            background: #F8F9FA;
            border-radius: 16px;
            border: 2px dashed #E5E7EB;
        }

        .no-access-message i {
            font-size: 4rem;
            color: #E5E7EB;
            margin-bottom: 20px;
        }

        .no-access-message h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #231F20;
            margin: 0 0 12px;
        }

        .no-access-message p {
            color: #666666;
            margin: 0;
        }
    </style>

    <script>
        // Affiliate Dashboard JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            initializeAffiliateCharts();
        });

        function initializeAffiliateCharts() {
            const ctx = document.getElementById('earnings-chart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Earnings',
                            data: [120, 190, 300, 250, 420, 380],
                            borderColor: '#E4B26F',
                            backgroundColor: 'rgba(228, 178, 111, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        }

        function generateReferralLink() {
            const linkType = document.getElementById('link-type').value;
            generateCustomLink();
        }

        function generateCustomLink() {
            const linkType = document.getElementById('link-type').value;
            const baseUrl = window.location.origin;
            const affiliateCode = document.getElementById('affiliate-code').value;
            
            let targetUrl = '';
            switch(linkType) {
                case 'pricing':
                    targetUrl = baseUrl + '/select-tribe';
                    break;
                case 'home':
                    targetUrl = baseUrl;
                    break;
                case 'register':
                    targetUrl = baseUrl + '/courscribe-register';
                    break;
                case 'demo':
                    targetUrl = baseUrl + '/demo';
                    break;
                default:
                    targetUrl = baseUrl;
            }
            
            const referralLink = targetUrl + '?ref=' + affiliateCode;
            
            document.getElementById('generated-link').value = referralLink;
            document.getElementById('generated-link-section').style.display = 'block';
        }

        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            element.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Show feedback
            const button = element.nextElementSibling;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.style.background = '#10B981';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '#E4B26F';
            }, 2000);
        }

        function copyAffiliateCode() {
            copyToClipboard('affiliate-code');
        }

        function shareLink() {
            const link = document.getElementById('generated-link').value;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Join CourScribe',
                    text: 'Check out CourScribe - the best curriculum development platform!',
                    url: link
                });
            } else {
                copyToClipboard('generated-link');
            }
        }

        function requestPayout() {
            document.getElementById('payout-modal-overlay').style.display = 'flex';
        }

        function closePayoutModal() {
            document.getElementById('payout-modal-overlay').style.display = 'none';
        }

        function submitPayoutRequest() {
            const amount = document.getElementById('payout-amount').value;
            const method = document.getElementById('payout-method').value;
            const notes = document.getElementById('payout-notes').value;
            
            if (!amount || amount < 50) {
                alert('Minimum payout amount is $50');
                return;
            }
            
            // Submit via AJAX
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'courscribe_request_payout',
                    amount: amount,
                    method: method,
                    notes: notes,
                    nonce: '<?php echo wp_create_nonce('courscribe_affiliate_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Payout request submitted successfully!');
                        closePayoutModal();
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }

        function refreshReferrals() {
            location.reload();
        }

        function downloadBanners() {
            window.open('<?php echo plugin_dir_url(__FILE__ . '/../../../') . 'assets/marketing/banners.zip'; ?>', '_blank');
        }

        function viewEmailTemplates() {
            window.open('<?php echo plugin_dir_url(__FILE__ . '/../../../') . 'assets/marketing/email-templates.html'; ?>', '_blank');
        }

        function downloadSocialKit() {
            window.open('<?php echo plugin_dir_url(__FILE__ . '/../../../') . 'assets/marketing/social-kit.zip'; ?>', '_blank');
        }

        function viewVideos() {
            window.open('<?php echo plugin_dir_url(__FILE__ . '/../../../') . 'assets/marketing/videos.html'; ?>', '_blank');
        }

        // Commission filter
        document.addEventListener('change', function(e) {
            if (e.target.id === 'commission-filter') {
                const filter = e.target.value;
                // Implement filter logic here
                console.log('Filtering commissions by:', filter);
            }
        });
    </script>

    <?php
    return ob_get_clean();
}

// Helper functions for affiliate data
function courscribe_get_affiliate_data($user_id) {
    // Validate user ID
    if (!$user_id || !is_numeric($user_id)) {
        error_log('Courscribe Affiliate: Invalid user ID provided: ' . $user_id);
        return array(
            'affiliate_code' => 'INVALID',
            'commission_rate' => 30,
            'payout_method' => 'paypal',
            'payout_email' => 'invalid@example.com'
        );
    }
    
    // Get user data safely
    $user_data = get_userdata($user_id);
    if (!$user_data) {
        error_log('Courscribe Affiliate: User not found: ' . $user_id);
        return array(
            'affiliate_code' => 'NOTFOUND',
            'commission_rate' => 30,
            'payout_method' => 'paypal',
            'payout_email' => 'notfound@example.com'
        );
    }
    
    // Get affiliate code, generate if needed
    $affiliate_code = get_user_meta($user_id, '_courscribe_affiliate_code', true);
    if (!$affiliate_code) {
        $affiliate_code = courscribe_generate_affiliate_code_new($user_id);
    }
    
    // Get commission rate with validation
    $commission_rate = get_user_meta($user_id, '_courscribe_commission_rate', true);
    $commission_rate = ($commission_rate && is_numeric($commission_rate)) ? intval($commission_rate) : 30;
    
    // Validate commission rate range
    if ($commission_rate < 5 || $commission_rate > 50) {
        $commission_rate = 30;
    }
    
    return array(
        'affiliate_code' => sanitize_text_field($affiliate_code),
        'commission_rate' => $commission_rate,
        'payout_method' => sanitize_text_field(get_user_meta($user_id, '_courscribe_payout_method', true) ?: 'paypal'),
        'payout_email' => sanitize_email(get_user_meta($user_id, '_courscribe_payout_email', true) ?: $user_data->user_email)
    );
}

function courscribe_get_mock_affiliate_stats($user_id) {
    return array(
        'total_earnings' => 0.00,
        'pending_earnings' => 0.00,
        'paid_earnings' => 0.00,
        'total_clicks' => 0,
        'total_conversions' => 0,
        'total_referrals' => 0,
        'conversion_rate' => 0,
        'avg_commission' => 0.00,
        'earnings_growth' => 0,
        'next_payout_date' => 'No payouts scheduled'
    );
}

function courscribe_get_affiliate_stats($user_id) {
    global $wpdb;
    
    // Validate user ID
    if (!$user_id || !is_numeric($user_id)) {
        error_log('Courscribe Affiliate Stats: Invalid user ID provided: ' . $user_id);
        return courscribe_get_mock_affiliate_stats(0);
    }
    
    // Get real affiliate tracking data from database
    $commissions_table = $wpdb->prefix . 'courscribe_affiliate_commissions';
    $tracking_table = $wpdb->prefix . 'courscribe_affiliate_tracking';
    $referrals_table = $wpdb->prefix . 'courscribe_affiliate_referrals';
    
    // Check if tables exist first
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $commissions_table));
    
    if (!$table_exists) {
        // Return default/mock data if tables don't exist
        error_log('Courscribe Affiliate Stats: Tables not found, returning mock data for user: ' . $user_id);
        return courscribe_get_mock_affiliate_stats($user_id);
    }
    
    // Get commission totals with error handling
    try {
        $commission_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COALESCE(SUM(commission_amount), 0) as total_earnings,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) as pending_earnings,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END), 0) as paid_earnings,
                COUNT(*) as total_commissions
            FROM $commissions_table 
            WHERE affiliate_id = %d
        ", $user_id));
    } catch (Exception $e) {
        error_log('Courscribe Affiliate Stats: Database error getting commission stats: ' . $e->getMessage());
        $commission_stats = null;
    }
    
    // Get tracking stats with error handling
    try {
        $tracking_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(CASE WHEN converted = 1 THEN 1 END) as total_conversions
            FROM $tracking_table 
            WHERE affiliate_id = %d
        ", $user_id));
    } catch (Exception $e) {
        error_log('Courscribe Affiliate Stats: Database error getting tracking stats: ' . $e->getMessage());
        $tracking_stats = null;
    }
    
    // Get referral count with error handling
    try {
        $total_referrals = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $referrals_table WHERE affiliate_id = %d
        ", $user_id));
    } catch (Exception $e) {
        error_log('Courscribe Affiliate Stats: Database error getting referral count: ' . $e->getMessage());
        $total_referrals = 0;
    }
    
    // If any critical data is missing, return mock data
    if (!$commission_stats && !$tracking_stats) {
        error_log('Courscribe Affiliate Stats: Critical database queries failed, returning mock data for user: ' . $user_id);
        return courscribe_get_mock_affiliate_stats($user_id);
    }
    
    // Calculate conversion rate (with null safety)
    $conversion_rate = ($tracking_stats && $tracking_stats->total_clicks > 0) 
        ? round(($tracking_stats->total_conversions / $tracking_stats->total_clicks) * 100, 1) 
        : 0;
    
    // Calculate average commission (with null safety)
    $avg_commission = ($commission_stats && $commission_stats->total_commissions > 0) 
        ? round($commission_stats->total_earnings / $commission_stats->total_commissions, 2) 
        : 0;
    
    // Calculate earnings growth (month over month)
    $current_month_earnings = $wpdb->get_var($wpdb->prepare("
        SELECT COALESCE(SUM(commission_amount), 0) 
        FROM $commissions_table 
        WHERE affiliate_id = %d 
        AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ", $user_id));
    
    $last_month_earnings = $wpdb->get_var($wpdb->prepare("
        SELECT COALESCE(SUM(commission_amount), 0) 
        FROM $commissions_table 
        WHERE affiliate_id = %d 
        AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)
    ", $user_id));
    
    $earnings_growth = $last_month_earnings > 0 
        ? round((($current_month_earnings - $last_month_earnings) / $last_month_earnings) * 100, 1) 
        : 0;
    
    return array(
        'total_earnings' => floatval(($commission_stats && isset($commission_stats->total_earnings)) ? $commission_stats->total_earnings : 0),
        'pending_earnings' => floatval(($commission_stats && isset($commission_stats->pending_earnings)) ? $commission_stats->pending_earnings : 0),
        'paid_earnings' => floatval(($commission_stats && isset($commission_stats->paid_earnings)) ? $commission_stats->paid_earnings : 0),
        'total_referrals' => intval($total_referrals ?: 0),
        'total_clicks' => intval(($tracking_stats && isset($tracking_stats->total_clicks)) ? $tracking_stats->total_clicks : 0),
        'total_conversions' => intval(($tracking_stats && isset($tracking_stats->total_conversions)) ? $tracking_stats->total_conversions : 0),
        'conversion_rate' => $conversion_rate,
        'avg_commission' => $avg_commission,
        'earnings_growth' => $earnings_growth,
        'next_payout_date' => courscribe_get_next_payout_date()
    );
}

function courscribe_get_recent_referrals($user_id, $limit = 10) {
    global $wpdb;
    
    $commissions_table = $wpdb->prefix . 'courscribe_affiliate_commissions';
    $referrals_table = $wpdb->prefix . 'courscribe_affiliate_referrals';
    
    // Check if tables exist first
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $referrals_table));
    
    if (!$table_exists) {
        // Return empty array if tables don't exist
        return [];
    }
    
    // Get recent referrals with commission data
    $referrals = $wpdb->get_results($wpdb->prepare("
        SELECT 
            u.display_name as user_name,
            u.user_email,
            DATE(r.registration_date) as date,
            COALESCE(um.meta_value, 'basics') as plan,
            COALESCE(c.commission_amount, 0) as commission,
            COALESCE(c.status, 'pending') as status
        FROM $referrals_table r
        LEFT JOIN {$wpdb->users} u ON r.referred_user_id = u.ID
        LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '_courscribe_user_tier'
        LEFT JOIN $commissions_table c ON r.affiliate_id = c.affiliate_id AND r.referred_user_id = c.customer_id
        WHERE r.affiliate_id = %d
        ORDER BY r.registration_date DESC
        LIMIT %d
    ", $user_id, $limit));
    
    $result = array();
    foreach ($referrals as $referral) {
        $result[] = array(
            'user_name' => $referral->user_name ?: 'Unknown User',
            'user_email' => $referral->user_email ?: 'N/A',
            'date' => $referral->date ?: date('Y-m-d'),
            'plan' => $referral->plan ?: 'basics',
            'commission' => floatval($referral->commission ?: 0),
            'status' => $referral->status ?: 'pending'
        );
    }
    
    return $result;
}

function courscribe_get_commission_history($user_id, $limit = 20) {
    global $wpdb;
    
    $commissions_table = $wpdb->prefix . 'courscribe_affiliate_commissions';
    $users_table = $wpdb->users;
    
    // Check if tables exist first
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $commissions_table));
    
    if (!$table_exists) {
        // Return empty array if tables don't exist
        return [];
    }
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            c.id,
            c.commission_amount,
            c.status,
            c.created_at,
            c.order_id,
            cu.display_name as customer_name,
            o.post_excerpt as order_product
        FROM $commissions_table c
        LEFT JOIN $users_table cu ON c.customer_id = cu.ID
        LEFT JOIN {$wpdb->posts} o ON c.order_id = o.ID
        WHERE c.affiliate_id = %d
        ORDER BY c.created_at DESC
        LIMIT %d
    ", $user_id, $limit));
    
    $commissions = array();
    if ($results) {
        foreach ($results as $row) {
            $commissions[] = array(
                'date' => date('Y-m-d', strtotime($row->created_at)),
                'description' => sprintf(
                    'Commission from %s (Order #%d)', 
                    $row->customer_name ?: 'Unknown Customer',
                    $row->order_id ?: 0
                ),
                'amount' => floatval($row->commission_amount),
                'status' => $row->status
            );
        }
    }
    
    return $commissions;
}

function courscribe_generate_affiliate_code_new($user_id) {
    $user = get_userdata($user_id);
    $code = strtoupper(substr($user->user_login, 0, 4) . substr(md5($user_id), 0, 4));
    update_user_meta($user_id, '_courscribe_affiliate_code', $code);
    return $code;
}

function courscribe_calculate_conversion_rate($user_id) {
    $clicks = get_user_meta($user_id, '_courscribe_total_clicks', true) ?: 0;
    $conversions = get_user_meta($user_id, '_courscribe_total_conversions', true) ?: 0;
    
    if ($clicks == 0) return 0;
    return round(($conversions / $clicks) * 100, 1);
}

function courscribe_calculate_avg_commission($user_id) {
    $total_earnings = get_user_meta($user_id, '_courscribe_total_earnings', true) ?: 0;
    $total_conversions = get_user_meta($user_id, '_courscribe_total_conversions', true) ?: 0;
    
    if ($total_conversions == 0) return 0;
    return round($total_earnings / $total_conversions, 2);
}

function courscribe_calculate_earnings_growth($user_id) {
    // Calculate month-over-month growth
    return 15; // Sample 15% growth
}

function courscribe_get_next_payout_date() {
    // Next payout is typically monthly
    $next_month = date('M j, Y', strtotime('first day of next month'));
    return $next_month;
}  

// Register the shortcode
add_shortcode('courscribe_premium_affiliate', 'courscribe_premium_affiliate_shortcode');
?>