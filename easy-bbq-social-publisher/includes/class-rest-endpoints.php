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

        register_rest_route( 'ebsp/v1', '/generate-images', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_images' ),
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

    public function generate_images( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Auto-learn new dishes
        $presets = get_option( 'ebsp_presets', array( 'starters' => array(), 'mains' => array(), 'drinks' => array() ) );
        $updated_presets = false;

        $starter_title = sanitize_text_field( $params['starter_title'] ?? '' );
        if ( !empty($starter_title) && !in_array($starter_title, $presets['starters']) ) {
            $presets['starters'][] = $starter_title;
            $updated_presets = true;
        }

        $main1_title = sanitize_text_field( $params['main1_title'] ?? '' );
        if ( !empty($main1_title) && !in_array($main1_title, $presets['mains']) ) {
            $presets['mains'][] = $main1_title;
            $updated_presets = true;
        }

        $main2_title = sanitize_text_field( $params['main2_title'] ?? '' );
        if ( !empty($main2_title) && !in_array($main2_title, $presets['mains']) ) {
            $presets['mains'][] = $main2_title;
            $updated_presets = true;
        }

        $drink = sanitize_text_field( $params['drink'] ?? '' );
        if ( !empty($drink) && !in_array($drink, $presets['drinks']) ) {
            $presets['drinks'][] = $drink;
            $updated_presets = true;
        }

        if ( $updated_presets ) {
            update_option( 'ebsp_presets', $presets );
        }

        $images = array(
            'starter' => '',
            'main1'   => '',
            'main2'   => ''
        );

        if ( !empty( $params['starter_prompt'] ) ) {
            $res = $this->api_client->generate_image( sanitize_text_field( $params['starter_prompt'] ) );
            if ( !is_wp_error( $res ) ) {
                $images['starter'] = $res;
            }
        }

        if ( !empty( $params['main1_prompt'] ) ) {
            $res = $this->api_client->generate_image( sanitize_text_field( $params['main1_prompt'] ) );
            if ( !is_wp_error( $res ) ) {
                $images['main1'] = $res;
            }
        }

        if ( !empty( $params['main2_prompt'] ) ) {
            $res = $this->api_client->generate_image( sanitize_text_field( $params['main2_prompt'] ) );
            if ( !is_wp_error( $res ) ) {
                $images['main2'] = $res;
            }
        }

        return new WP_REST_Response( array( 'images' => $images ), 200 );
    }

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
