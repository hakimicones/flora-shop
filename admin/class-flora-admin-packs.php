<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flora_Admin_Packs {

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
        Flora_Helpers::verify_nonce( 'flora_save_pack' );

        $db = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            $data = array(
                'name'        => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'slug'        => sanitize_title( $_POST['name'] ),
                'pack_price'  => Flora_Helpers::sanitize_float( $_POST['pack_price'] ),
                'description' => wp_kses_post( $_POST['description'] ),
                'image_url'   => esc_url_raw( $_POST['image_url'] ),
                'status'      => sanitize_text_field( $_POST['status'] ),
                'sort_order'  => Flora_Helpers::sanitize_number( $_POST['sort_order'] ),
            );

            $id = isset( $_POST['pack_id'] ) ? absint( $_POST['pack_id'] ) : 0;

            if ( $id > 0 ) {
                $db->update_pack( $id, $data );
            } else {
                $id = $db->insert_pack( $data );
            }

            $pack_products = array();
            if ( ! empty( $_POST['pack_products'] ) && is_array( $_POST['pack_products'] ) ) {
                foreach ( $_POST['pack_products'] as $pp ) {
                    if ( ! empty( $pp['product_id'] ) && ! empty( $pp['quantity'] ) ) {
                        $pack_products[] = array(
                            'product_id' => absint( $pp['product_id'] ),
                            'quantity'   => absint( $pp['quantity'] ),
                        );
                    }
                }
            }
            $db->set_pack_products( $id, $pack_products );

            wp_safe_redirect( admin_url( 'admin.php?page=flora-packs&flora_notice=pack_saved' ) );
            exit;
        }

        if ( 'delete' === $post_action ) {
            $id = absint( $_POST['pack_id'] );
            if ( $id > 0 ) {
                $db->delete_pack( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-packs&flora_notice=pack_deleted' ) );
            exit;
        }
    }

    private static function render_list() {
        $db   = Flora_DB::get_instance();
        $packs = $db->get_packs( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/packs-list.php';
    }

    private static function render_form( $action ) {
        $db   = Flora_DB::get_instance();
        $pack = null;
        $pack_products = array();

        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $pack = $db->get_pack( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
            if ( $pack ) {
                $pack_products = $db->get_pack_products( $pack->id );
            }
        }

        $all_products = $db->get_products();

        include FLORA_SHOP_PATH . 'admin/views/pack-form.php';
    }
}
