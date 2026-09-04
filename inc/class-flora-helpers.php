<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flora_Helpers {

    public static function format_price( $amount ) {
        $currency = get_option( 'flora_currency', 'DZD' );
        return number_format( (float) $amount, 2, ',', ' ' ) . ' ' . $currency;
    }

    public static function sanitize_text( $input ) {
        return sanitize_text_field( wp_unslash( $input ) );
    }

    public static function sanitize_email( $input ) {
        return sanitize_email( wp_unslash( $input ) );
    }

    public static function sanitize_number( $input ) {
        return absint( $input );
    }

    public static function sanitize_float( $input ) {
        return (float) $input;
    }

    public static function generate_order_number() {
        $prefix = 'FL';
        $date   = date( 'Ymd' );
        $last   = (int) get_option( 'flora_last_order_seq_' . $date, 0 );
        $seq    = $last + 1;
        update_option( 'flora_last_order_seq_' . $date, $seq );
        return sprintf( '%s-%s-%04d', $prefix, $date, $seq );
    }

    public static function get_page_url( $slug ) {
        $page = get_page_by_path( $slug );
        if ( $page ) {
            return get_permalink( $page->ID );
        }
        return '';
    }

    public static function get_admin_notice( $message, $type = 'success' ) {
        return '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
    }

    public static function wpnonce_field( $action, $name = '_flora_nonce' ) {
        return wp_nonce_field( $action, $name );
    }

    public static function verify_nonce( $action, $name = '_flora_nonce' ) {
        if ( ! isset( $_POST[ $name ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $name ] ) ), $action ) ) {
            wp_die( esc_html__( 'Erreur de sécurité. Veuillez réessayer.', 'flora-shop' ) );
        }
    }
}
