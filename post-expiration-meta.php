<?php
/**
 * Plugin Name: Post Expiration Meta
 * Description: Adds "expiration_date" meta-field for posts and exposes it to REST API.
 *              Format: yyyy-mm-dd hh:mm:ss. Must be in the future.
 * Version:     1.2.1
 * Author:      Vlad
 * Text Domain: post-expiration-meta
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validates that the expiration date is in the future and properly formatted.
 *
 * @param string $dateString The date string to validate.
 * @return bool True if valid and in the future, false otherwise.
 */
function pemValidateExpirationDate($dateString) {
    // Check format: yyyy-mm-dd hh:mm:ss
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
        return false;
    }
    
    try {
        $date = new DateTime($dateString);
        // Must be in the future (returns true or false - single exit point)
        return $date->getTimestamp() > time();
    } catch (Exception $e) {
        return false;
    }
}

add_action('init', function () {
    register_post_meta('post', 'expiration_date', [
        'show_in_rest' => true,
        'single'       => true,
        'type'         => 'string',
        'description'  => 'Post expiration date in format: yyyy-mm-dd hh:mm:ss',
        'sanitize_callback' => function ($value) {
            // Allow empty value (no expiration)
            if (empty($value)) {
                return '';
            }
            
            $value = trim($value);
            
            // Validate format and future date
            if (!pemValidateExpirationDate($value)) {
                return '';
            }
            
            return $value;
        },
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
    
    // Add is_expired field to REST API response
    add_filter('rest_prepare_post', function ($response, $post) {
        $expirationDate = get_post_meta($post->ID, 'expiration_date', true);
        
        if ($expirationDate) {
            try {
                $expirationDatetime = new DateTime($expirationDate);
                $now = new DateTime();
                $isExpired = $expirationDatetime->getTimestamp() < $now->getTimestamp();
                
                $response->data['is_expired'] = $isExpired;
            } catch (Exception $e) {
                // If date parsing fails, don't add the field
                error_log('Post Expiration Meta: Invalid date format for post ' . $post->ID);
            }
        }
        
        return $response;
    }, 10, 2);
});
