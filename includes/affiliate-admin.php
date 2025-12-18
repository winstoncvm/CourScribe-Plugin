<?php
// CourScribe Affiliate Admin Interface
// Admin dashboard for managing affiliates, commissions, and payouts
if (!defined('ABSPATH')) {
    exit;
}

// Add affiliate management to admin menu
add_action('admin_menu', 'courscribe_add_affiliate_admin_menu');

function courscribe_add_affiliate_admin_menu() {
    add_submenu_page(
        'courscribe_dashboard',
        'Affiliate Management',
        'Affiliates',
        'manage_options',
        'courscribe_affiliates',
        'courscribe_affiliate_admin_page'
    );
}

function courscribe_affiliate_admin_page() {
    global $wpdb;
    
    $current_tab = $_GET['tab'] ?? 'overview';
    
    ?>
    <div class="wrap">
        <h1>Affiliate Management</h1>
        
        <nav class="nav-tab-wrapper">
            <a href="?page=courscribe_affiliates&tab=overview" class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>">Overview</a>
            <a href="?page=courscribe_affiliates&tab=affiliates" class="nav-tab <?php echo $current_tab === 'affiliates' ? 'nav-tab-active' : ''; ?>">Affiliates</a>
            <a href="?page=courscribe_affiliates&tab=commissions" class="nav-tab <?php echo $current_tab === 'commissions' ? 'nav-tab-active' : ''; ?>">Commissions</a>
            <a href="?page=courscribe_affiliates&tab=payouts" class="nav-tab <?php echo $current_tab === 'payouts' ? 'nav-tab-active' : ''; ?>">Payouts</a>
            <a href="?page=courscribe_affiliates&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
        </nav>
        
        <div class="tab-content">
            <?php
            switch ($current_tab) {
                case 'overview':
                    courscribe_affiliate_overview_tab();
                    break;
                case 'affiliates':
                    courscribe_affiliate_list_tab();
                    break;
                case 'commissions':
                    courscribe_affiliate_commissions_tab();
                    break;
                case 'payouts':
                    courscribe_affiliate_payouts_tab();
                    break;
                case 'settings':
                    courscribe_affiliate_settings_tab();
                    break;
            }
            ?>
        </div>
    </div>
    
    <style>
        .affiliate-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .affiliate-stat-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #E4B26F;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .affiliate-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .affiliate-table th,
        .affiliate-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .affiliate-table th {
            background-color: #f9f9f9;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-paid {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .affiliate-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #E4B26F;
            color: white;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
    </style>
    <?php
}

function courscribe_affiliate_overview_tab() {
    global $wpdb;
    
    // Get affiliate statistics
    $total_affiliates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = '_courscribe_affiliate_enabled' AND meta_value = '1'");
    $total_commissions = $wpdb->get_var("SELECT COALESCE(SUM(commission_amount), 0) FROM {$wpdb->prefix}courscribe_affiliate_commissions");
    $pending_commissions = $wpdb->get_var("SELECT COALESCE(SUM(commission_amount), 0) FROM {$wpdb->prefix}courscribe_affiliate_commissions WHERE status = 'pending'");
    $pending_payouts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_affiliate_payouts WHERE status = 'pending'");
    
    ?>
    <div class="affiliate-stats-grid">
        <div class="affiliate-stat-card">
            <div class="stat-number"><?php echo number_format($total_affiliates); ?></div>
            <div class="stat-label">Total Affiliates</div>
        </div>
        
        <div class="affiliate-stat-card">
            <div class="stat-number">$<?php echo number_format($total_commissions, 2); ?></div>
            <div class="stat-label">Total Commissions</div>
        </div>
        
        <div class="affiliate-stat-card">
            <div class="stat-number">$<?php echo number_format($pending_commissions, 2); ?></div>
            <div class="stat-label">Pending Commissions</div>
        </div>
        
        <div class="affiliate-stat-card">
            <div class="stat-number"><?php echo number_format($pending_payouts); ?></div>
            <div class="stat-label">Pending Payouts</div>
        </div>
    </div>
    
    <h3>Recent Activity</h3>
    <?php
    // Get recent commissions
    $recent_commissions = $wpdb->get_results("
        SELECT c.*, u.display_name 
        FROM {$wpdb->prefix}courscribe_affiliate_commissions c 
        LEFT JOIN {$wpdb->users} u ON c.affiliate_id = u.ID 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ");
    
    if ($recent_commissions) {
        echo '<table class="affiliate-table">';
        echo '<thead><tr><th>Date</th><th>Affiliate</th><th>Commission</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($recent_commissions as $commission) {
            echo '<tr>';
            echo '<td>' . date('M j, Y', strtotime($commission->created_at)) . '</td>';
            echo '<td>' . esc_html($commission->display_name) . '</td>';
            echo '<td>$' . number_format($commission->commission_amount, 2) . '</td>';
            echo '<td><span class="status-badge status-' . esc_attr($commission->status) . '">' . esc_html($commission->status) . '</span></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No commission activity yet.</p>';
    }
}

function courscribe_affiliate_list_tab() {
    global $wpdb;
    
    // Handle actions
    if (isset($_POST['action']) && $_POST['action'] === 'enable_affiliate') {
        $user_id = intval($_POST['user_id']);
        $commission_rate = floatval($_POST['commission_rate']);
        courscribe_enable_affiliate($user_id, $commission_rate);
        echo '<div class="notice notice-success"><p>Affiliate enabled successfully!</p></div>';
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'disable_affiliate') {
        $user_id = intval($_POST['user_id']);
        update_user_meta($user_id, '_courscribe_affiliate_enabled', false);
        echo '<div class="notice notice-success"><p>Affiliate disabled successfully!</p></div>';
    }
    
    ?>
    <div style="margin: 20px 0;">
        <h3>Enable New Affiliate</h3>
        <form method="post" style="display: flex; gap: 10px; align-items: end;">
            <div>
                <label>User:</label>
                <select name="user_id" required>
                    <option value="">Select User</option>
                    <?php
                    $users = get_users(['role__in' => ['studio_admin', 'subscriber']]);
                    foreach ($users as $user) {
                        $is_affiliate = get_user_meta($user->ID, '_courscribe_affiliate_enabled', true);
                        if (!$is_affiliate) {
                            echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div>
                <label>Commission Rate (%):</label>
                <input type="number" name="commission_rate" value="30" min="1" max="100" required>
            </div>
            
            <input type="hidden" name="action" value="enable_affiliate">
            <button type="submit" class="button button-primary">Enable Affiliate</button>
        </form>
    </div>
    
    <h3>Current Affiliates</h3>
    <?php
    
    // Get affiliates
    $affiliates = $wpdb->get_results("
        SELECT u.ID, u.display_name, u.user_email, u.user_registered,
               um1.meta_value as affiliate_code,
               um2.meta_value as commission_rate,
               um3.meta_value as total_earnings,
               um4.meta_value as total_referrals
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} ume ON u.ID = ume.user_id AND ume.meta_key = '_courscribe_affiliate_enabled' AND ume.meta_value = '1'
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = '_courscribe_affiliate_code'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = '_courscribe_commission_rate'
        LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = '_courscribe_total_earnings'
        LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = '_courscribe_total_referrals'
        ORDER BY u.user_registered DESC
    ");
    
    if ($affiliates) {
        echo '<table class="affiliate-table">';
        echo '<thead><tr><th>Name</th><th>Email</th><th>Code</th><th>Commission Rate</th><th>Total Earnings</th><th>Referrals</th><th>Joined</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($affiliates as $affiliate) {
            echo '<tr>';
            echo '<td>' . esc_html($affiliate->display_name) . '</td>';
            echo '<td>' . esc_html($affiliate->user_email) . '</td>';
            echo '<td><code>' . esc_html($affiliate->affiliate_code ?: 'Not set') . '</code></td>';
            echo '<td>' . esc_html($affiliate->commission_rate ?: '30') . '%</td>';
            echo '<td>$' . number_format($affiliate->total_earnings ?: 0, 2) . '</td>';
            echo '<td>' . number_format($affiliate->total_referrals ?: 0) . '</td>';
            echo '<td>' . date('M j, Y', strtotime($affiliate->user_registered)) . '</td>';
            echo '<td>';
            echo '<div class="affiliate-actions">';
            echo '<a href="user-edit.php?user_id=' . $affiliate->ID . '" class="btn-small btn-primary">Edit</a>';
            echo '<form method="post" style="display:inline;">';
            echo '<input type="hidden" name="user_id" value="' . $affiliate->ID . '">';
            echo '<input type="hidden" name="action" value="disable_affiliate">';
            echo '<button type="submit" class="btn-small btn-danger" onclick="return confirm(\'Disable this affiliate?\')">Disable</button>';
            echo '</form>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No affiliates found.</p>';
    }
}

function courscribe_affiliate_commissions_tab() {
    global $wpdb;
    
    // Handle actions
    if (isset($_POST['action']) && $_POST['action'] === 'approve_commission') {
        $commission_id = intval($_POST['commission_id']);
        $wpdb->update(
            $wpdb->prefix . 'courscribe_affiliate_commissions',
            ['status' => 'approved'],
            ['id' => $commission_id],
            ['%s'],
            ['%d']
        );
        echo '<div class="notice notice-success"><p>Commission approved!</p></div>';
    }
    
    $status_filter = $_GET['status'] ?? 'all';
    $page = $_GET['paged'] ?? 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    ?>
    <div style="margin: 20px 0;">
        <form method="get">
            <input type="hidden" name="page" value="courscribe_affiliates">
            <input type="hidden" name="tab" value="commissions">
            <select name="status">
                <option value="all" <?php selected($status_filter, 'all'); ?>>All Statuses</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                <option value="approved" <?php selected($status_filter, 'approved'); ?>>Approved</option>
                <option value="paid" <?php selected($status_filter, 'paid'); ?>>Paid</option>
            </select>
            <button type="submit" class="button">Filter</button>
        </form>
    </div>
    
    <?php
    
    $where_clause = $status_filter !== 'all' ? $wpdb->prepare("WHERE c.status = %s", $status_filter) : '';
    
    $commissions = $wpdb->get_results($wpdb->prepare("
        SELECT c.*, u.display_name, u.user_email 
        FROM {$wpdb->prefix}courscribe_affiliate_commissions c 
        LEFT JOIN {$wpdb->users} u ON c.affiliate_id = u.ID 
        $where_clause
        ORDER BY c.created_at DESC 
        LIMIT %d OFFSET %d
    ", $per_page, $offset));
    
    if ($commissions) {
        echo '<table class="affiliate-table">';
        echo '<thead><tr><th>Date</th><th>Affiliate</th><th>Customer</th><th>Order</th><th>Commission</th><th>Rate</th><th>Status</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($commissions as $commission) {
            $customer = get_userdata($commission->customer_id);
            
            echo '<tr>';
            echo '<td>' . date('M j, Y', strtotime($commission->created_at)) . '</td>';
            echo '<td>' . esc_html($commission->display_name) . '<br><small>' . esc_html($commission->user_email) . '</small></td>';
            echo '<td>' . ($customer ? esc_html($customer->display_name) : 'N/A') . '</td>';
            echo '<td>#' . esc_html($commission->order_id) . '</td>';
            echo '<td>$' . number_format($commission->commission_amount, 2) . '</td>';
            echo '<td>' . esc_html($commission->commission_rate) . '%</td>';
            echo '<td><span class="status-badge status-' . esc_attr($commission->status) . '">' . esc_html($commission->status) . '</span></td>';
            echo '<td>';
            
            if ($commission->status === 'pending') {
                echo '<form method="post" style="display:inline;">';
                echo '<input type="hidden" name="commission_id" value="' . $commission->id . '">';
                echo '<input type="hidden" name="action" value="approve_commission">';
                echo '<button type="submit" class="btn-small btn-primary">Approve</button>';
                echo '</form>';
            }
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No commissions found.</p>';
    }
}

function courscribe_affiliate_payouts_tab() {
    global $wpdb;
    
    // Handle actions
    if (isset($_POST['action']) && $_POST['action'] === 'process_payout') {
        $payout_id = intval($_POST['payout_id']);
        $transaction_id = sanitize_text_field($_POST['transaction_id']);
        
        $wpdb->update(
            $wpdb->prefix . 'courscribe_affiliate_payouts',
            [
                'status' => 'completed',
                'processed_date' => current_time('mysql'),
                'transaction_id' => $transaction_id,
                'processed_by' => get_current_user_id()
            ],
            ['id' => $payout_id],
            ['%s', '%s', '%s', '%d'],
            ['%d']
        );
        
        echo '<div class="notice notice-success"><p>Payout processed successfully!</p></div>';
    }
    
    $payouts = $wpdb->get_results("
        SELECT p.*, u.display_name, u.user_email 
        FROM {$wpdb->prefix}courscribe_affiliate_payouts p 
        LEFT JOIN {$wpdb->users} u ON p.affiliate_id = u.ID 
        ORDER BY p.requested_date DESC
    ");
    
    if ($payouts) {
        echo '<table class="affiliate-table">';
        echo '<thead><tr><th>Date</th><th>Affiliate</th><th>Amount</th><th>Method</th><th>Status</th><th>Transaction ID</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($payouts as $payout) {
            echo '<tr>';
            echo '<td>' . date('M j, Y', strtotime($payout->requested_date)) . '</td>';
            echo '<td>' . esc_html($payout->display_name) . '<br><small>' . esc_html($payout->user_email) . '</small></td>';
            echo '<td>$' . number_format($payout->payout_amount, 2) . '</td>';
            echo '<td>' . esc_html(ucfirst($payout->payout_method)) . '</td>';
            echo '<td><span class="status-badge status-' . esc_attr($payout->status) . '">' . esc_html($payout->status) . '</span></td>';
            echo '<td>' . esc_html($payout->transaction_id ?: 'N/A') . '</td>';
            echo '<td>';
            
            if ($payout->status === 'pending') {
                echo '<button type="button" class="btn-small btn-primary" onclick="processPayout(' . $payout->id . ')">Process</button>';
            }
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No payout requests found.</p>';
    }
    
    ?>
    <script>
    function processPayout(payoutId) {
        const transactionId = prompt('Enter transaction ID:');
        if (transactionId) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="action" value="process_payout">
                <input type="hidden" name="payout_id" value="${payoutId}">
                <input type="hidden" name="transaction_id" value="${transactionId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
    <?php
}

function courscribe_affiliate_settings_tab() {
    if (isset($_POST['save_settings'])) {
        update_option('courscribe_affiliate_default_commission', floatval($_POST['default_commission']));
        update_option('courscribe_affiliate_minimum_payout', floatval($_POST['minimum_payout']));
        update_option('courscribe_affiliate_cookie_duration', intval($_POST['cookie_duration']));
        update_option('courscribe_affiliate_auto_approve', $_POST['auto_approve'] === '1');
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    $default_commission = get_option('courscribe_affiliate_default_commission', 30);
    $minimum_payout = get_option('courscribe_affiliate_minimum_payout', 50);
    $cookie_duration = get_option('courscribe_affiliate_cookie_duration', 30);
    $auto_approve = get_option('courscribe_affiliate_auto_approve', false);
    
    ?>
    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row">Default Commission Rate (%)</th>
                <td>
                    <input type="number" name="default_commission" value="<?php echo esc_attr($default_commission); ?>" min="1" max="100" step="0.1">
                    <p class="description">Default commission rate for new affiliates.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Minimum Payout Amount ($)</th>
                <td>
                    <input type="number" name="minimum_payout" value="<?php echo esc_attr($minimum_payout); ?>" min="1" step="0.01">
                    <p class="description">Minimum amount required for payout requests.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Cookie Duration (days)</th>
                <td>
                    <input type="number" name="cookie_duration" value="<?php echo esc_attr($cookie_duration); ?>" min="1" max="365">
                    <p class="description">How long affiliate cookies last for conversion tracking.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Auto-approve Commissions</th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_approve" value="1" <?php checked($auto_approve); ?>>
                        Automatically approve new commissions
                    </label>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="save_settings" class="button-primary" value="Save Settings">
        </p>
    </form>
    <?php
}
?>