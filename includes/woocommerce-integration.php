<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Create subscription and simple products
function course_scribe_create_subscription_products() {
    if ( get_option( 'course_scribe_products_created_four' ) ) {
        return;
    }

    $plans = [
        [
            'name' => 'CourScribe Basics',
            'slug' => 'courscribe-basics',
            'sku' => 'COURSCRIBE-BASICS',
            'type' => 'free',
            'price' => 0,
            'description' => 'The all-you-need basics for organized course content management and design. Start your free trial. No time limit. Test it out with 1 curriculum. No credit card required.',
            'short_description' => 'Free forever – includes 1 curriculum and all essential tools.',
            'features' => [
                '1 Curriculum',
                '1 Course',
                '3 Modules',
                '3 Lessons',
                '3 Teaching Points',
                '1 Administrator',
                'Unlimited Students',
                'Limited AI Copy',
                'Drag & Drop Editor',
                '0% Transaction Fee',
                'Chat/Email Support',
                'Slide Deck (Watermarked)',
                'CourseView Link',
            ],
        ],
        [
            'name' => 'CourScribe + (Monthly)',
            'slug' => 'courscribe-plus-monthly',
            'sku' => 'COURSCRIBE-PLUS-MONTHLY',
            'type' => 'subscription',
            'price' => 14,
            'interval' => 'month',
            'interval_count' => 1,
            'description' => 'Explore automation to take work off your plate and enhance your designer experience with AI. Everything in the Basics plan plus: One curriculum, unlimited courses, modules, and teaching points, AI content generation, dictation, access to CourseProfit LAB, and more.',
            'short_description' => 'Automation + AI tools for creators.',
            'features' => [
                'Everything in Basics',
                'Unlimited Courses',
                'Unlimited Modules',
                'Unlimited Teaching Points',
                'AI Generated Modules',
                'Voice Dictation',
                '1 Learning Portal (Fee)',
                'CourseProfit LAB Access',
                'Slide Deck (Watermarked)',
                'CourseView Link',
            ],
        ],
        [
            'name' => 'CourScribe + (Yearly)',
            'slug' => 'courscribe-plus-yearly',
            'sku' => 'COURSCRIBE-PLUS-YEARLY',
            'type' => 'subscription',
            'price' => 140,
            'interval' => 'year',
            'interval_count' => 1,
            'description' => 'Same features as CourScribe + Monthly, but billed yearly for savings.',
            'short_description' => 'Save with yearly billing on Plus.',
            'features' => [
                'Everything in Basics',
                'Unlimited Courses',
                'Unlimited Modules',
                'Unlimited Teaching Points',
                'AI Generated Modules',
                'Voice Dictation',
                '1 Learning Portal (Fee)',
                'CourseProfit LAB Access',
                'Slide Deck (Watermarked)',
                'CourseView Link',
            ],
        ],
        [
            'name' => 'CourScribe Pro (Monthly)',
            'slug' => 'courscribe-pro-monthly',
            'sku' => 'COURSCRIBE-PRO-MONTHLY',
            'type' => 'subscription',
            'price' => 37,
            'interval' => 'month',
            'interval_count' => 1,
            'description' => 'Everything in CourScribe Plus plus: unlimited curriculums, client feedback links, unlimited client-based LMPs, premium tools for agencies, 0% transaction fees, AI writing tools, and watermarked slide decks.',
            'short_description' => 'Advanced features for agencies – monthly.',
            'features' => [
                'Everything in Plus',
                'Unlimited Curriculums',
                'Unlimited LMPs (Client-based)',
                'Client Feedback Link',
                'AI Full Course Builder',
                'White-labeled Options',
                'Advanced Templates',
                'Priority Support',
            ],
        ],
        [
            'name' => 'CourScribe Pro (Yearly)',
            'slug' => 'courscribe-pro-yearly',
            'sku' => 'COURSCRIBE-PRO-YEARLY',
            'type' => 'subscription',
            'price' => 370,
            'interval' => 'year',
            'interval_count' => 1,
            'description' => 'All Pro Monthly features, billed yearly for savings.',
            'short_description' => 'Save with yearly billing on Pro.',
            'features' => [
                'Everything in Plus',
                'Unlimited Curriculums',
                'Unlimited LMPs (Client-based)',
                'Client Feedback Link',
                'AI Full Course Builder',
                'White-labeled Options',
                'Advanced Templates',
                'Priority Support',
            ],
        ],
        [
            'name' => 'CourScribe Expert Review',
            'slug' => 'courscribe-expert-review',
            'sku' => 'COURSCRIBE-EXPERT-REVIEW',
            'type' => 'simple',
            'price' => 29,
            'description' => 'Elevate your curriculum with professional feedback from CourScribe’s expert educators. Our administrators provide detailed, actionable insights to refine your course content, structure, and delivery. Perfect for creators seeking to enhance their curriculum’s impact and effectiveness. Receive a comprehensive review within 5 business days.',
            'short_description' => 'Professional curriculum review by CourScribe experts for $29.',
            'features' => [
                'Detailed Feedback Report',
                'Actionable Improvement Suggestions',
                'Content and Structure Analysis',
                'Delivery Optimization Tips',
                'Completed within 5 Business Days',
                'One-Time Purchase',
            ],
        ],
    ];

    foreach ( $plans as $plan ) {
        if ( wc_get_product_id_by_sku( $plan['sku'] ) ) {
            continue; // Product already exists
        }

        $product = new WC_Product_Simple();
        $product->set_name( $plan['name'] );
        $product->set_slug( $plan['slug'] );
        $product->set_sku( $plan['sku'] );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        $product->set_price( $plan['price'] );
        $product->set_regular_price( $plan['price'] );
        $product->set_description( wp_kses_post( $plan['description'] ) );
        $product->set_short_description( wp_kses_post( $plan['short_description'] ) );

        // Set features as attributes
        $attributes = [];
        $attribute = new WC_Product_Attribute();
        $attribute->set_name( 'Features' );
        $attribute->set_options( [ implode( ', ', $plan['features'] ) ] );
        $attribute->set_visible( true );
        $attribute->set_variation( false );
        $attributes[] = $attribute;
        $product->set_attributes( $attributes );

        // Save product
        $product_id = $product->save();

        // Ensure descriptions are saved in wp_posts
        wp_update_post( [
            'ID' => $product_id,
            'post_content' => wp_kses_post( $plan['description'] ),
            'post_excerpt' => wp_kses_post( $plan['short_description'] ),
        ] );

        // Apply WP Swings subscription meta for subscriptions
        if ( $plan['type'] === 'subscription' ) {
            update_post_meta( $product_id, '_wps_sfw_product', 'yes' );
            update_post_meta( $product_id, '_subscription_price', $plan['price'] );
            update_post_meta( $product_id, '_subscription_period', $plan['interval'] );
            update_post_meta( $product_id, '_subscription_period_interval', $plan['interval_count'] );
            update_post_meta( $product_id, '_subscription_length', 0 ); // 0 = indefinite
        }

        error_log( 'Courscribe: Created product ' . $plan['sku'] . ' with ID ' . $product_id );
    }

    update_option( 'course_scribe_products_created_four', true );
}
add_action( 'init', 'course_scribe_create_subscription_products' );

// Update user tier and tribe selection on subscription purchase
add_action( 'woocommerce_subscription_status_updated', 'courscribe_update_tier_on_subscription', 10, 3 );
function courscribe_update_tier_on_subscription( $subscription, $new_status, $old_status ) {
    $user_id = $subscription->get_user_id();
    $items = $subscription->get_items();
    $tier = 'basics';

    foreach ( $items as $item ) {
        $product = $item->get_product();
        $sku = $product->get_sku();
        if ( in_array( $sku, ['COURSCRIBE-PLUS-MONTHLY', 'COURSCRIBE-PLUS-YEARLY'] ) ) {
            $tier = 'plus';
        } elseif ( in_array( $sku, ['COURSCRIBE-PRO-MONTHLY', 'COURSCRIBE-PRO-YEARLY'] ) ) {
            $tier = 'pro';
        }
    }

    if ( in_array( $new_status, [ 'active', 'pending-cancel' ] ) ) {
        update_user_meta( $user_id, '_courscribe_user_tier', $tier );
        update_user_meta( $user_id, '_courscribe_tribe_selected', '1' ); // Mark tribe selection complete
        error_log( 'Courscribe: Updated tier to ' . $tier . ' and marked tribe selected for user ' . $user_id . ' on subscription status ' . $new_status );
    } elseif ( in_array( $new_status, [ 'cancelled', 'expired' ] ) ) {
        update_user_meta( $user_id, '_courscribe_user_tier', 'basics' );
        error_log( 'Courscribe: Downgraded to basics for user ' . $user_id . ' on subscription status ' . $new_status );
    }
}

// Restrict capabilities based on tier
//add_filter( 'user_has_cap', 'courscribe_restrict_by_tier', 20, 4 );
function courscribe_restrict_by_tier( $allcaps, $cap, $args, $user ) {
    $tier = get_user_meta( $user->ID, '_courscribe_user_tier', true ) ?: 'basics';
    $restricted_caps = [
        'edit_crscribe_curriculums',
        'publish_crscribe_curriculums',
        'edit_crscribe_courses',
        'publish_crscribe_courses',
        'edit_dtlms_modules',
        'publish_dtlms_modules',
        'edit_dtlms_lessons',
        'publish_dtlms_lessons',
        'generate_slide_deck',
        'edit_course_document',
    ];

    if ( $tier === 'basics' && in_array( $cap[0], $restricted_caps ) ) {
        $counts = [
            'crscribe_curriculum' => wp_count_posts( 'crscribe_curriculum' )->publish,
            'crscribe_course' => wp_count_posts( 'crscribe_course' )->publish,
        ];
        if ( $counts['crscribe_curriculum'] >= 1 && in_array( $cap[0], [ 'edit_crscribe_curriculums', 'publish_crscribe_curriculums' ] ) ) {
            $allcaps[ $cap[0] ] = false;
        }
        if ( $counts['crscribe_course'] >= 1 && in_array( $cap[0], [ 'edit_crscribe_courses', 'publish_crscribe_courses' ] ) ) {
            $allcaps[ $cap[0] ] = false;
        }
    } elseif ( $tier === 'plus' && in_array( $cap[0], [ 'edit_crscribe_curriculums', 'publish_crscribe_curriculums' ] ) ) {
        $count = wp_count_posts( 'crscribe_curriculum' )->publish;
        if ( $count >= 1 ) {
            $allcaps[ $cap[0] ] = false;
        }
    }

    if ( $tier !== 'pro' && in_array( $cap[0], [ 'generate_slide_deck', 'edit_course_document' ] ) ) {
        $allcaps[ $cap[0] ] = false;
    }

    return $allcaps;
}
?>