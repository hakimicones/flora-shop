<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Paramètres » : affiche le formulaire de configuration (devise,
 * seuil de livraison gratuite, remises quantité produits et panier) et traite
 * la sauvegarde via POST. Accès restreint à la capability 'manage_options' ;
 * nonce vérifié via Flora_Helpers::verify_nonce() et champs assainis avant
 * enregistrement dans les options.
 */
class Flora_Admin_Settings {

    public static function render() {
        // Point d'entrée de la page : vérifie la permission, gère le POST puis inclut la vue des réglages.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }

        self::handle_actions();

        $currency                = get_option( 'flora_currency', 'DZD' );
        $free_shipping_threshold = get_option( 'flora_free_shipping_threshold', 0 );
        $product_discounts       = get_option( 'flora_product_discounts', array() );
        $cart_discounts          = get_option( 'flora_cart_discounts', array() );

        $db          = Flora_DB::get_instance();
        $all_products = $db->get_products();

        include FLORA_SHOP_PATH . 'admin/views/settings.php';
    }

    private static function handle_actions() {
        // Traite l'action POST d'enregistrement des paramètres.
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

        if ( 'save_settings' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_settings' );

            // Enregistrement des options simples, valeurs assainies avant écriture.
            update_option( 'flora_currency', Flora_Helpers::sanitize_text( $_POST['currency'] ) );
            update_option( 'flora_free_shipping_threshold', Flora_Helpers::sanitize_float( $_POST['free_shipping_threshold'] ) );

            // Remises produits : tableau revalidé ligne par ligne ; chaque entrée doit
            // fournir product_id, min_qty et percent non vides, convertis en entiers.
            $product_discounts = array();
            if ( ! empty( $_POST['product_discounts'] ) && is_array( $_POST['product_discounts'] ) ) {
                foreach ( $_POST['product_discounts'] as $pd ) {
                    if ( ! empty( $pd['product_id'] ) && ! empty( $pd['min_qty'] ) && ! empty( $pd['percent'] ) ) {
                        $product_discounts[] = array(
                            'product_id' => absint( $pd['product_id'] ),
                            'min_qty'    => absint( $pd['min_qty'] ),
                            'percent'    => absint( $pd['percent'] ),
                        );
                    }
                }
            }
            update_option( 'flora_product_discounts', $product_discounts );

            // Remises panier : même revalidation par ligne ; min_total en float, percent en entier.
            $cart_discounts = array();
            if ( ! empty( $_POST['cart_discounts'] ) && is_array( $_POST['cart_discounts'] ) ) {
                foreach ( $_POST['cart_discounts'] as $cd ) {
                    if ( ! empty( $cd['min_total'] ) && ! empty( $cd['percent'] ) ) {
                        $cart_discounts[] = array(
                            'min_total' => Flora_Helpers::sanitize_float( $cd['min_total'] ),
                            'percent'   => absint( $cd['percent'] ),
                        );
                    }
                }
            }
            update_option( 'flora_cart_discounts', $cart_discounts );

            wp_safe_redirect( admin_url( 'admin.php?page=flora-settings&flora_notice=settings_saved' ) );
            exit;
        }
    }
}
