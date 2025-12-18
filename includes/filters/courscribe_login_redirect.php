<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Redirect after login for studio_admin
add_filter( 'login_redirect', 'courscribe_login_redirect', 10, 3 );

function courscribe_login_redirect( $redirect_to, $request, $user ) {
    // Only handle studio_admin users
    if ( ! isset( $user->roles ) || ! is_object($user) || ! in_array( 'studio_admin', $user->roles ) ) {
        error_log( 'Courscribe: Login redirect - User ' . (is_object($user) ? $user->ID : 'Invalid User Object') . ' is not studio_admin or invalid user object.' );
        return $redirect_to;
    }

    $user_id = $user->ID;
    $onboarding_step = get_user_meta( $user_id, '_courscribe_onboarding_step', true );
    $first_login = get_user_meta( $user_id, '_courscribe_first_login', true );
    $has_studio = courscribe_user_has_studio( $user_id );
    $tribe_selected = get_user_meta( $user_id, '_courscribe_tribe_selected', true );

    error_log( "Courscribe: Premium login redirect - User {$user_id}, Step: {$onboarding_step}, First login: {$first_login}, Has studio: " . ($has_studio ? 'yes' : 'no') . ", Tribe selected: " . ($tribe_selected ? 'yes' : 'no') );

    // For first-time users or incomplete onboarding, redirect to welcome page
    if ( $first_login !== 'completed' || (!$onboarding_step && !$has_studio) ) {
        $welcome_page_id = get_option( 'courscribe_welcome_page' );
        if ( $welcome_page_id && ( $welcome_url = get_permalink( $welcome_page_id ) ) ) {
            // Set initial onboarding step if not set
            if ( !$onboarding_step ) {
                update_user_meta( $user_id, '_courscribe_onboarding_step', 'welcome' );
            }
            
            error_log( "Courscribe: Redirecting user {$user_id} to premium welcome page: {$welcome_url}" );
            return $welcome_url;
        }
        
        // Fallback to old flow if welcome page not set
        $create_studio_page_id = get_option( 'courscribe_create_studio_page' );
        if ( !$has_studio && $create_studio_page_id && ( $create_studio_url = get_permalink( $create_studio_page_id ) ) ) {
            error_log( "Courscribe: Fallback - Redirecting user {$user_id} to Create Studio page: {$create_studio_url}" );
            return $create_studio_url;
        }
    }

    // For users in the middle of onboarding, redirect to welcome page
    if ( in_array( $onboarding_step, ['welcome', 'pricing', 'studio'] ) && $onboarding_step !== 'complete' ) {
        $welcome_page_id = get_option( 'courscribe_welcome_page' );
        if ( $welcome_page_id && ( $welcome_url = get_permalink( $welcome_page_id ) ) ) {
            error_log( "Courscribe: Redirecting user {$user_id} to continue onboarding at step: {$onboarding_step}" );
            return $welcome_url;
        }
    }

    // Default to studio page for completed onboarding
    $studio_page_id = get_option( 'courscribe_studio_page' );
    if ( $studio_page_id && ( $studio_url = get_permalink( $studio_page_id ) ) ) {
        error_log( "Courscribe: Redirecting user {$user_id} to studio page (onboarding complete)" );
        return $studio_url;
    }

    error_log( "Courscribe: No valid redirect found for user {$user_id}, falling back to default: {$redirect_to}" );
    return $redirect_to;
}


