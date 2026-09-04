<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flora_Admin_Promotions {

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }

        self::handle_actions();

        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification

        if ( 'add' === $action || 'edit' === $action ) {
            self::render_form( $action );
        } else {
            self::render_list();
        }
    }

    private static function handle_actions() {
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        $db           = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_promo' );

            $trigger_type = isset( $_POST['trigger_type'] ) ? sanitize_text_field( wp_unslash( $_POST['trigger_type'] ) ) : 'product';
            $reward_type  = isset( $_POST['reward_type'] ) ? sanitize_text_field( wp_unslash( $_POST['reward_type'] ) ) : 'free';
            $free_type    = isset( $_POST['free_type'] ) ? sanitize_text_field( wp_unslash( $_POST['free_type'] ) ) : 'product';

            $data = array(
                'trigger_type'       => in_array( $trigger_type, array( 'product', 'pack' ), true ) ? $trigger_type : 'product',
                'trigger_product_id' => absint( $_POST[ 'pack' === $trigger_type ? 'trigger_pack_id' : 'trigger_product_id' ] ),
                'trigger_qty'        => absint( $_POST['trigger_qty'] ),
                'reward_type'        => in_array( $reward_type, array( 'free', 'percent' ), true ) ? $reward_type : 'free',
                'free_type'          => in_array( $free_type, array( 'product', 'pack' ), true ) ? $free_type : 'product',
                'free_product_id'    => 'free' === $reward_type ? absint( $_POST[ 'pack' === $free_type ? 'free_pack_id' : 'free_product_id' ] ) : 0,
                'free_qty'           => 'free' === $reward_type ? absint( $_POST['free_qty'] ) : 0,
                'discount_percent'   => Flora_Helpers::sanitize_float( isset( $_POST['discount_percent'] ) ? $_POST['discount_percent'] : 0 ),
                'limit_per_order'    => absint( $_POST['limit_per_order'] ),
                'start_date'         => sanitize_text_field( $_POST['start_date'] ),
                'end_date'           => sanitize_text_field( $_POST['end_date'] ),
                'status'             => sanitize_text_field( $_POST['status'] ),
            );

            $id = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;

            if ( $id > 0 ) {
                $db->update_promotion( $id, $data );
            } else {
                $db->insert_promotion( $data );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=flora-promotions&flora_notice=promo_saved' ) );
            exit;
        }

        if ( 'delete' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_delete_promo' );
            $id = absint( $_POST['promo_id'] );
            if ( $id > 0 ) {
                $db->delete_promotion( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-promotions&flora_notice=promo_deleted' ) );
            exit;
        }
    }

    private static function render_list() {
        $db          = Flora_DB::get_instance();
        $promotions  = $db->get_promotions();
        $all_products = $db->get_products();
        $all_packs    = $db->get_packs( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/promotions-list.php';
    }

    private static function render_form( $action ) {
        $db    = Flora_DB::get_instance();
        $promo = null;

        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $promo = $db->get_promotion( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }

        $all_products = $db->get_products();
        $all_packs    = $db->get_packs( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/promotion-form.php';
    }
}
