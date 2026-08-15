<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EBSP_Media_Handler {

    public function handle_upload( $file_array ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $upload_overrides = array( 'test_form' => false );

        $movefile = wp_handle_upload( $file_array, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            $attachment = array(
                'guid'           => $movefile['url'],
                'post_mime_type' => $movefile['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attach_id = wp_insert_attachment( $attachment, $movefile['file'] );
            if ( ! is_wp_error( $attach_id ) ) {
                $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                return $movefile['url'];
            } else {
                return new WP_Error( 'attachment_error', 'Could not create attachment.' );
            }
        } else {
            return new WP_Error( 'upload_error', $movefile['error'] );
        }
    }
}
