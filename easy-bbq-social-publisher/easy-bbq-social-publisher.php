<?php
/**
 * Plugin Name: Easy BBQ Social Publisher
 * Description: Generates 9:16 promotional menu flyers and video snippets for Instagram Reels and TikTok.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: easy-bbq-social-publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'EBSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EBSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EBSP_PLUGIN_VERSION', '1.0.0' );

// Include necessary classes.
require_once EBSP_PLUGIN_DIR . 'includes/class-admin-page.php';
require_once EBSP_PLUGIN_DIR . 'includes/class-api-client.php';
require_once EBSP_PLUGIN_DIR . 'includes/class-media-handler.php';
require_once EBSP_PLUGIN_DIR . 'includes/class-rest-endpoints.php';

/**
 * Initialize the plugin.
 */
function ebsp_init_plugin() {
    $admin_page = new EBSP_Admin_Page();
    $admin_page->init();

    $rest_endpoints = new EBSP_REST_Endpoints();
    $rest_endpoints->init();
}
add_action( 'plugins_loaded', 'ebsp_init_plugin' );
