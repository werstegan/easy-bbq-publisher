<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EBSP_API_Client {

    private function get_api_key() {
        return get_option( 'ebsp_gemini_api_key' );
    }

    public function generate_caption( $data ) {
        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'Gemini API Key is missing.' );
        }

        // Sanitize input fields used to build the prompt
        $day = isset( $data['day'] ) ? sanitize_text_field( $data['day'] ) : '';
        $starter = isset( $data['starter_title'] ) ? sanitize_text_field( $data['starter_title'] ) : '';
        $main1 = isset( $data['main1_title'] ) ? sanitize_text_field( $data['main1_title'] ) : '';
        $main2 = isset( $data['main2_title'] ) ? sanitize_text_field( $data['main2_title'] ) : '';
        $drink = isset( $data['drink'] ) ? sanitize_text_field( $data['drink'] ) : '';
        $price = isset( $data['price'] ) ? sanitize_text_field( $data['price'] ) : '';

        $prompt_lines = array();
        $prompt_lines[] = "Write an engaging and exciting Instagram/TikTok caption for a BBQ restaurant menu. Here is the menu for " . $day . ":";
        $prompt_lines[] = "- Starter: " . $starter;
        $prompt_lines[] = "- Main Course 1: " . $main1;
        $prompt_lines[] = "- Main Course 2: " . $main2;
        $prompt_lines[] = "- Drink: " . $drink;
        $prompt_lines[] = "- Price: " . $price . " CHF";
        $prompt_lines[] = "Include emojis and make it fun!";

        $hashtags = sanitize_text_field( get_option( 'ebsp_default_hashtags', '#easybbq #food #menu' ) );
        $prompt_lines[] = "Use these hashtags: " . $hashtags;

        $prompt = implode( "\n", $prompt_lines );

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . rawurlencode( $api_key );

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt )
                    )
                )
            )
        );

        $response = wp_remote_post( $url, array(
            'headers'     => array( 'Content-Type' => 'application/json' ),
            'body'        => wp_json_encode( $body ),
            'method'      => 'POST',
            'data_format' => 'body',
            'timeout'     => 30
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $http_code = intval( wp_remote_retrieve_response_code( $response ) );
        $raw_body = wp_remote_retrieve_body( $response );
        if ( $http_code < 200 || $http_code >= 300 ) {
            return new WP_Error( 'api_error', 'Caption API returned HTTP ' . $http_code . ': ' . substr( $raw_body, 0, 200 ) );
        }

        $decoded = json_decode( $raw_body, true );
        // Defensive extraction: check a few common shapes
        if ( isset( $decoded['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return $decoded['candidates'][0]['content']['parts'][0]['text'];
        }
        if ( isset( $decoded['output'][0]['text'] ) ) {
            return $decoded['output'][0]['text'];
        }
        if ( isset( $decoded['choices'][0]['message']['content'][0]['text'] ) ) {
            return $decoded['choices'][0]['message']['content'][0]['text'];
        }

        return new WP_Error( 'api_error', 'Could not parse caption from API response. Raw: ' . substr( $raw_body, 0, 400 ) );
    }

    public function generate_image( $prompt ) {
        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'Imagen API Key is missing.' );
        }

        $prompt_safe = sanitize_text_field( $prompt );
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-001:predict?key=' . rawurlencode( $api_key );

        $body = array(
            'instances' => array(
                array(
                    'prompt' => 'High quality food photography, ' . $prompt_safe
                )
            ),
            'parameters' => array(
                'sampleCount' => 1,
                'aspectRatio' => '1:1'
            )
        );

        $response = wp_remote_post( $url, array(
            'headers'     => array( 'Content-Type' => 'application/json' ),
            'body'        => wp_json_encode( $body ),
            'method'      => 'POST',
            'data_format' => 'body',
            'timeout'     => 60
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $http_code = intval( wp_remote_retrieve_response_code( $response ) );
        $raw_body = wp_remote_retrieve_body( $response );
        if ( $http_code < 200 || $http_code >= 300 ) {
            return new WP_Error( 'api_error', 'Image API returned HTTP ' . $http_code . ': ' . substr( $raw_body, 0, 200 ) );
        }

        $decoded = json_decode( $raw_body, true );
        // Defensive checks for a few possible response shapes
        if ( isset( $decoded['predictions'][0]['bytesBase64Encoded'] ) ) {
            return 'data:image/jpeg;base64,' . $decoded['predictions'][0]['bytesBase64Encoded'];
        }
        if ( isset( $decoded['predictions'][0]['b64_json'] ) ) {
            return 'data:image/png;base64,' . $decoded['predictions'][0]['b64_json'];
        }
        if ( isset( $decoded['data'][0]['b64_json'] ) ) {
            return 'data:image/png;base64,' . $decoded['data'][0]['b64_json'];
        }

        return new WP_Error( 'api_error', 'Could not parse image from API response. Raw: ' . substr( $raw_body, 0, 400 ) );
    }
}
