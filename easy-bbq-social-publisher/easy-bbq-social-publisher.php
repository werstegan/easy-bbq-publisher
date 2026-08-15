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
 * Seed initial presets on activation.
 */
function ebsp_activate_plugin() {
    $defaults = array(
        'starters' => array(
            "Crema de calabacín", "Sancocho ecuatoriano", "Sancocho de ternera", "Sancocho de gallina", "Sopa de menestrón de carne", "Caldo de gallina", "Bolón de verde", "Empanadas colombianas", "Arepas rellenas", "Maduro con queso"
        ),
        'mains' => array(
            "Tallarines salteados de ternera con verduras", "Chaulafán ecuatoriano", "Chaulafán mixto", "Sango de camarón con arroz y maduro frito", "Pescado apanado con arroz y ensalada", "Pollo apanado con arroz y puré de patata", "Chuleta de cerdo con arroz y puré de patata", "Bandeja paisa", "Encebollado de pescado", "Guatita tradicional", "Ceviche / Cebiches mixtos", "Bollo de pescado", "Bollo mixto con queso", "Fritada ecuatoriana", "Hornado tradicional", "Chicharrón con choclo", "Chicharrón con yuca", "Churrasco ecuatoriano", "Arroz con menestra y carne asada", "Arroz con menestra, pollo y huevo", "Arroz con pollo", "Arroz con camarón", "Gambas al ajillo", "Bandera ecuatoriana", "Sango de pescado", "Sango de atún", "Tu carne Easy Barbecue en la mesa", "Picadas para compartir"
        ),
        'drinks' => array(
            "Bebida refrescante de frutas", "Bebida refrescante incluida", "Jugo de mora", "Jugo de lulo", "Jugo de maracuyá", "Jugo de guanábana", "Limonada de coco"
        )
    );

    if ( false === get_option( 'ebsp_presets' ) ) {
        update_option( 'ebsp_presets', $defaults );
    }
}
register_activation_hook( __FILE__, 'ebsp_activate_plugin' );

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
