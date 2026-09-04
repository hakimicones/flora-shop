<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flora_Admin_Shipping {

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }

        self::handle_actions();

        $db       = Flora_DB::get_instance();
        $tab      = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'wilayas'; // phpcs:ignore WordPress.Security.NonceVerification
        $wilayas  = $db->get_wilayas();
        $rates    = $db->get_all_shipping_rates();

        $wilaya_map = array();
        foreach ( $wilayas as $w ) {
            $wilaya_map[ $w->code ] = $w;
        }

        include FLORA_SHOP_PATH . 'admin/views/shipping.php';
    }

    private static function handle_actions() {
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        $db           = Flora_DB::get_instance();

        if ( 'save_wilaya' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_wilaya' );
            $data = array(
                'code'      => absint( $_POST['code'] ),
                'name'      => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'name_ar'   => isset( $_POST['name_ar'] ) ? Flora_Helpers::sanitize_text( $_POST['name_ar'] ) : '',
                'latitude'  => isset( $_POST['latitude'] ) && '' !== $_POST['latitude'] ? Flora_Helpers::sanitize_float( $_POST['latitude'] ) : null,
                'longitude' => isset( $_POST['longitude'] ) && '' !== $_POST['longitude'] ? Flora_Helpers::sanitize_float( $_POST['longitude'] ) : null,
            );
            $db->insert_wilaya( $data );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=wilayas&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'delete_wilaya' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_delete_wilaya' );
            $db->delete_wilaya( absint( $_POST['wilaya_code'] ) );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=wilayas&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'save_commune' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_commune' );
            $data = array(
                'post_code'   => Flora_Helpers::sanitize_text( $_POST['post_code'] ),
                'name'        => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'name_ar'     => isset( $_POST['name_ar'] ) ? Flora_Helpers::sanitize_text( $_POST['name_ar'] ) : '',
                'daira'       => isset( $_POST['daira'] ) ? Flora_Helpers::sanitize_text( $_POST['daira'] ) : '',
                'daira_ar'    => isset( $_POST['daira_ar'] ) ? Flora_Helpers::sanitize_text( $_POST['daira_ar'] ) : '',
                'wilaya_code' => absint( $_POST['wilaya_code'] ),
                'latitude'    => isset( $_POST['latitude'] ) && '' !== $_POST['latitude'] ? Flora_Helpers::sanitize_float( $_POST['latitude'] ) : null,
                'longitude'   => isset( $_POST['longitude'] ) && '' !== $_POST['longitude'] ? Flora_Helpers::sanitize_float( $_POST['longitude'] ) : null,
            );
            $db->insert_commune( $data );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=communes&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'delete_commune' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_delete_commune' );
            $db->delete_commune( absint( $_POST['commune_id'] ) );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=communes&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'save_rate' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_rate' );
            $data = array(
                'wilaya_code' => absint( $_POST['wilaya_code'] ),
                'commune_id'  => absint( $_POST['commune_id'] ),
                'base_fee'    => Flora_Helpers::sanitize_float( $_POST['base_fee'] ),
                'per_kg_fee'  => Flora_Helpers::sanitize_float( $_POST['per_kg_fee'] ),
            );
            $db->upsert_shipping_rate( $data );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=rates&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'delete_rate' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_delete_rate' );
            $db->delete_shipping_rate( absint( $_POST['rate_id'] ) );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=rates&flora_notice=shipping_saved' ) );
            exit;
        }
    }
}
