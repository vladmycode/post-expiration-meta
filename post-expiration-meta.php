<?php
/**
 * Plugin Name: Post Expiration Meta
 * Description: Adds "expiration_date" meta-field ("yyyy-mm-dd hh:mm:ss") for posts and exposes it to REST API.
 * Version:     1.0.0
 * Author:      Vlad
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    register_post_meta('post', 'expiration_date', [
        'show_in_rest' => true,
        'single'       => true,
        'type'         => 'string',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
});

