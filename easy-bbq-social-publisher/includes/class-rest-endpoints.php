<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EBSP_REST_Endpoints {

    private $api_client;
    private $media_handler;

    public function __construct() {
        $this->api_client = new EBSP_API_Client();
        $this->media_handler = new EBSP_Media_Handler();
    }

    public function init() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'ebsp/v1', '/generate-caption', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_caption' ),
            'permission_callback' => array( $this, 'check_permission' )
        ) );

        register_rest_route( 'ebsp/v1', '/publish', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'publish_menu' ),
            'permission_callback' => array( $this, 'check_permission' )
        ) );

        register_rest_route( 'ebsp/v1', '/generate-dish-image', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_single_dish_image' ),
            'permission_callback' => array( $this, 'check_permission' )
        ) );

        register_rest_route( 'ebsp/v1', '/generate-batch-image', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_batch_image' ),
            'permission_callback' => array( $this, 'check_permission' )
        ) );

        register_rest_route( 'ebsp/v1', '/presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_presets' ),
            'permission_callback' => array( $this, 'check_permission' )
        ) );

        register_rest_route( 'ebsp/v1', '/presets', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_presets' ),
            'permission_callback' => array( $this, 'check_permission' )
        ) );

        register_rest_route( 'ebsp/v1', '/reset-presets', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'reset_presets' ),
            'permission_callback' => array( $this, 'check_permission' )
        ) );
    }

    public function check_permission() {
        return current_user_can( 'manage_options' );
    }

    public function get_presets( WP_REST_Request $request ) {
        $presets = get_option( 'ebsp_presets', array( 'starters' => array(), 'mains' => array(), 'drinks' => array() ) );
        return new WP_REST_Response( $presets, 200 );
    }

    public function update_presets( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        $presets = array(
            'starters' => array_map('sanitize_text_field', $params['starters'] ?? array()),
            'mains'    => array_map('sanitize_text_field', $params['mains'] ?? array()),
            'drinks'   => array_map('sanitize_text_field', $params['drinks'] ?? array())
        );

        update_option( 'ebsp_presets', $presets );

        return new WP_REST_Response( array( 'message' => 'Presets updated', 'presets' => $presets ), 200 );
    }

    public function reset_presets( WP_REST_Request $request ) {
        $current_presets = get_option( 'ebsp_presets', array( 'starters' => array(), 'mains' => array(), 'drinks' => array() ) );
        $default_presets = EBSP_Admin_Page::get_default_presets();

        $merged_presets = array(
            'starters' => array_values( array_unique( array_merge( $default_presets['starters'], $current_presets['starters'] ) ) ),
            'mains'    => array_values( array_unique( array_merge( $default_presets['mains'], $current_presets['mains'] ) ) ),
            'drinks'   => array_values( array_unique( array_merge( $default_presets['drinks'], $current_presets['drinks'] ) ) )
        );

        update_option( 'ebsp_presets', $merged_presets );

        return new WP_REST_Response( array( 'message' => 'Presets reset and merged', 'presets' => $merged_presets ), 200 );
    }

    public function generate_batch_image( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $dish_name = sanitize_text_field( $params['dish_name'] ?? '' );
        $slug = sanitize_text_field( $params['slug'] ?? '' );

        if ( empty( $dish_name ) || empty( $slug ) ) {
            return new WP_REST_Response( array( 'error' => 'Dish name and slug are required.' ), 400 );
        }

        $prompt = "Top-down commercial food photography of traditional Ecuadorian {$dish_name}, served on a rustic dark plate, professional studio lighting, appetizing, ultra-detailed, isolated centered composition, 8k resolution, photorealistic, solid dark background --no text --no watermark";

        $res = $this->api_client->generate_image( $prompt );

        if ( is_wp_error( $res ) ) {
            return new WP_REST_Response( array( 'error' => $res->get_error_message() ), 500 );
        }

        $base64_data = preg_replace('#^data:image/\w+;base64,#i', '', $res);
        $image_data = base64_decode($base64_data);

        // 1. Write directly to assets folder
        $assets_path = EBSP_PLUGIN_DIR . 'assets/images/dishes/' . $slug . '.png';
        file_put_contents( $assets_path, $image_data );

        // 2. Also upload to Media Library and bind
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $slug . '-' . time() . '.png';
        file_put_contents( $file_path, $image_data );

        $file_array = array(
            'name'     => basename($file_path),
            'type'     => 'image/png',
            'tmp_name' => $file_path,
            'error'    => 0,
            'size'     => filesize($file_path)
        );

        $image_url = $this->media_handler->handle_upload( $file_array );

        if ( file_exists( $file_path ) ) {
            unlink( $file_path );
        }

        if ( ! is_wp_error( $image_url ) ) {
            $custom_images = get_option( 'ebsp_dish_images', array() );
            $custom_images[ $dish_name ] = $image_url;
            update_option( 'ebsp_dish_images', $custom_images );
        }

        return new WP_REST_Response( array( 'image_url' => EBSP_PLUGIN_URL . 'assets/images/dishes/' . $slug . '.png', 'slug' => $slug ), 200 );
    }

    public function generate_single_dish_image( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $dish_name = sanitize_text_field( $params['dish_name'] ?? '' );

        if ( empty( $dish_name ) ) {
            return new WP_REST_Response( array( 'error' => 'Dish name is required.' ), 400 );
        }

        $prompt = "A delicious professional food photograph of {$dish_name}, traditional Ecuadorian presentation, studio lighting, top-down angle, isolated on clean plate, 8k --no text";

        $res = $this->api_client->generate_image( $prompt );

        if ( is_wp_error( $res ) ) {
            return new WP_REST_Response( array( 'error' => $res->get_error_message() ), 500 );
        }

        // $res is expected to be a base64 string like "data:image/jpeg;base64,..."
        $base64_data = preg_replace('#^data:image/\w+;base64,#i', '', $res);
        $image_data = base64_decode($base64_data);

        // Upload to WP Media Library
        $filename = sanitize_file_name($dish_name) . '-' . time() . '.jpg';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        file_put_contents($file_path, $image_data);

        $file_array = array(
            'name'     => $filename,
            'type'     => 'image/jpeg',
            'tmp_name' => $file_path,
            'error'    => 0,
            'size'     => filesize($file_path)
        );

        $image_url = $this->media_handler->handle_upload( $file_array );

        // Delete temporary file
        if ( file_exists( $file_path ) ) {
            unlink( $file_path );
        }

        if ( is_wp_error( $image_url ) ) {
            return new WP_REST_Response( array( 'error' => 'Failed to upload generated image: ' . $image_url->get_error_message() ), 500 );
        }

        // Save mapping with the clean URL, not base64
        $custom_images = get_option( 'ebsp_dish_images', array() );
        $custom_images[ $dish_name ] = $image_url;
        update_option( 'ebsp_dish_images', $custom_images );

        return new WP_REST_Response( array( 'image_url' => $image_url ), 200 );
    }

    // Keep auto-learning hook in the normal publish/render flow later or as a separate call if needed.
    // We will do it inside publish_menu to ensure new typed items are added.

    public function generate_caption( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        $data = array(
            'day'           => sanitize_text_field( $params['day'] ?? '' ),
            'starter_title' => sanitize_text_field( $params['starter_title'] ?? '' ),
            'main1_title'   => sanitize_text_field( $params['main1_title'] ?? '' ),
            'main2_title'   => sanitize_text_field( $params['main2_title'] ?? '' ),
            'drink'         => sanitize_text_field( $params['drink'] ?? '' ),
            'price'         => sanitize_text_field( $params['price'] ?? '' )
        );

        $caption = $this->api_client->generate_caption( $data );

        if ( is_wp_error( $caption ) ) {
            return new WP_REST_Response( array( 'error' => $caption->get_error_message() ), 500 );
        }

        return new WP_REST_Response( array( 'caption' => $caption ), 200 );
    }

    public function publish_menu( WP_REST_Request $request ) {
        $files = $request->get_file_params();
        $params = $request->get_body_params();

        if ( ! isset( $files['image'] ) || ! isset( $files['video'] ) ) {
            return new WP_REST_Response( array( 'error' => 'Missing image or video files.' ), 400 );
        }

        $image_url = $this->media_handler->handle_upload( $files['image'] );
        if ( is_wp_error( $image_url ) ) {
            return new WP_REST_Response( array( 'error' => 'Failed to upload image: ' . $image_url->get_error_message() ), 500 );
        }

        $video_url = $this->media_handler->handle_upload( $files['video'] );
        if ( is_wp_error( $video_url ) ) {
            return new WP_REST_Response( array( 'error' => 'Failed to upload video: ' . $video_url->get_error_message() ), 500 );
        }

        $webhook_url = get_option( 'ebsp_webhook_url' );
        if ( empty( $webhook_url ) ) {
            return new WP_REST_Response( array( 'error' => 'Webhook URL not configured.' ), 500 );
        }

        // Auto-learn new dishes when publishing
        $presets = get_option( 'ebsp_presets', array( 'starters' => array(), 'mains' => array(), 'drinks' => array() ) );
        $updated_presets = false;

        $starter_title = sanitize_text_field( $params['starter_title'] ?? '' );
        if ( !empty($starter_title) && !in_array($starter_title, $presets['starters']) ) { $presets['starters'][] = $starter_title; $updated_presets = true; }

        $main1_title = sanitize_text_field( $params['main1_title'] ?? '' );
        if ( !empty($main1_title) && !in_array($main1_title, $presets['mains']) ) { $presets['mains'][] = $main1_title; $updated_presets = true; }

        $main2_title = sanitize_text_field( $params['main2_title'] ?? '' );
        if ( !empty($main2_title) && !in_array($main2_title, $presets['mains']) ) { $presets['mains'][] = $main2_title; $updated_presets = true; }

        $drink = sanitize_text_field( $params['drink'] ?? '' );
        if ( !empty($drink) && !in_array($drink, $presets['drinks']) ) { $presets['drinks'][] = $drink; $updated_presets = true; }

        if ( $updated_presets ) {
            update_option( 'ebsp_presets', $presets );
        }

        $payload = array(
            'day'        => sanitize_text_field( $params['day'] ?? '' ),
            'caption'    => sanitize_textarea_field( $params['caption'] ?? '' ),
            'image_url'  => $image_url,
            'video_url'  => $video_url,
            'created_at' => gmdate( 'c' )
        );

        $response = wp_remote_post( $webhook_url, array(
            'headers'     => array( 'Content-Type' => 'application/json' ),
            'body'        => wp_json_encode( $payload ),
            'method'      => 'POST',
            'data_format' => 'body',
            'blocking'    => false // Asynchronous
        ) );

        if ( is_wp_error( $response ) ) {
            // Even if webhook fails asynchronously (or setting up request fails), we still uploaded media.
            return new WP_REST_Response( array( 'message' => 'Media uploaded but webhook dispatch failed.' ), 200 );
        }

        return new WP_REST_Response( array( 'message' => 'Published successfully!', 'payload' => $payload ), 200 );
    }
}
