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

        $prompt = "Write an engaging and exciting Instagram/TikTok caption for a BBQ restaurant menu. Here is the menu for " . $data['day'] . ":\n";
        $prompt .= "- Starter: " . $data['starter_title'] . "\n";
        $prompt .= "- Main Course 1: " . $data['main1_title'] . "\n";
        $prompt .= "- Main Course 2: " . $data['main2_title'] . "\n";
        $prompt .= "- Drink: " . $data['drink'] . "\n";
        $prompt .= "- Price: " . $data['price'] . " CHF\n";
        $prompt .= "Include emojis and make it fun!";

        $hashtags = get_option( 'ebsp_default_hashtags', '#easybbq #food #menu' );
        $prompt .= "\nUse these hashtags: " . $hashtags;

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;

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

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return new WP_Error( 'api_error', 'Could not generate caption from API response.' );
    }

    public function generate_image( $prompt ) {
        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'Gemini API Key is missing.' );
        }

        // Assuming Google Generative Language API implementation for Imagen 3:
        // models/imagen-3.0-generate-001
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-001:predict?key=' . $api_key;

        $body = array(
            'instances' => array(
                array(
                    'prompt' => 'High quality food photography, ' . $prompt
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

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['predictions'][0]['bytesBase64Encoded'] ) ) {
            return 'data:image/jpeg;base64,' . $data['predictions'][0]['bytesBase64Encoded'];
        }

        return new WP_Error( 'api_error', 'Could not generate image from API response.' );
    }
}
