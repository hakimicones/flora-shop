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
        $show_order_email        = get_option( 'flora_show_order_email', 1 );
        $custom_css              = get_option( 'flora_custom_css', '' );
        $languages               = get_option( 'flora_languages', Flora_Helpers::default_languages() );
        $default_language        = get_option( 'flora_default_language', 'fr' );
        $grid_columns            = get_option( 'flora_grid_columns', 4 );

        // Pages associées à chaque étape de la boutique (IDs résolus par les helpers).
        $flora_pages = array();
        foreach ( Flora_Helpers::flora_pages() as $key => $page ) {
            $flora_pages[ $key ] = Flora_Helpers::get_page_id( $key );
        }

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

            // Nombre de colonnes de la grille boutique : borné entre 2 et 5, défaut 4.
            $grid_columns = absint( $_POST['grid_columns'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if ( $grid_columns < 2 || $grid_columns > 5 ) {
                $grid_columns = 4;
            }
            update_option( 'flora_grid_columns', $grid_columns );

            // Affichage de l'email du client sur la page de confirmation (case à cocher : absent = 0).
            update_option( 'flora_show_order_email', empty( $_POST['show_order_email'] ) ? 0 : 1 );

            // CSS personnalisé : les balises HTML (<style>, </style>, etc.) sont retirées
            // pour empêcher toute échappée de contexte HTML ; le CSS lui-même est conservé.
            update_option( 'flora_custom_css', wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            // Langues : revalidation ligne par ligne (code, libellé, activée) ; on garantit
            // toujours au moins une langue active pour la boutique.
            $raw_languages = array();
            if ( isset( $_POST['languages'] ) && is_array( $_POST['languages'] ) ) {
                foreach ( $_POST['languages'] as $lang ) {
                    $code    = sanitize_title( isset( $lang['code'] ) ? $lang['code'] : '' );
                    $label   = Flora_Helpers::sanitize_text( isset( $lang['label'] ) ? $lang['label'] : '' );
                    $enabled = ! empty( $lang['enabled'] );

                    if ( $code && $label && $enabled ) {
                        $raw_languages[] = array(
                            'code'    => $code,
                            'label'   => $label,
                            'enabled' => 1,
                        );
                    }
                }
            }
            if ( empty( $raw_languages ) ) {
                $raw_languages = Flora_Helpers::default_languages();
            }
            update_option( 'flora_languages', $raw_languages );

            // Langue par défaut : doit rester dans la liste des langues activées ; sinon repli sur 'fr' (ou première langue active).
            $active_codes      = wp_list_pluck( $raw_languages, 'code' );
            $default_language  = sanitize_title( isset( $_POST['default_language'] ) ? $_POST['default_language'] : 'fr' );
            if ( ! in_array( $default_language, $active_codes, true ) ) {
                $default_language = in_array( 'fr', $active_codes, true ) ? 'fr' : $active_codes[0];
            }
            update_option( 'flora_default_language', $default_language );

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

            // Pages : enregistrement des identifiants sélectionnés (assainis en entiers).
            if ( isset( $_POST['flora_page'] ) && is_array( $_POST['flora_page'] ) ) {
                foreach ( Flora_Helpers::flora_pages() as $key => $page ) {
                    $page_id = isset( $_POST['flora_page'][ $key ] ) ? absint( $_POST['flora_page'][ $key ] ) : 0;
                    update_option( 'flora_page_' . $key, $page_id );
                }
            }

            wp_safe_redirect( admin_url( 'admin.php?page=flora-settings&flora_notice=settings_saved' ) );
            exit;
        }
    }
}
