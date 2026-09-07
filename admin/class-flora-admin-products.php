<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Produits » : affiche la liste, le formulaire d'ajout/édition et
 * traite la sauvegarde / suppression des produits (POST). Accès restreint à la
 * capability 'manage_options' ; chaque traitement POST est protégé par un nonce
 * (vérifié via Flora_Helpers::verify_nonce()).
 */
class Flora_Admin_Products {

    public static function render() {
        // Point d'entrée de la page : vérifie la permission, gère le POST puis affiche liste ou formulaire.
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
        // Traite les actions POST (save / delete) de la page Produits.
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        // Vérification du nonce avant tout traitement d'enregistrement.
        Flora_Helpers::verify_nonce( 'flora_save_product' );

        $db = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            // Assainissement systématique des champs POST avant écriture en base.
            $data = array(
                'name'        => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'slug'        => sanitize_title( $_POST['name'] ),
                'description' => wp_kses_post( $_POST['description'] ),
                'price'       => Flora_Helpers::sanitize_float( $_POST['price'] ),
                'weight'      => Flora_Helpers::sanitize_float( $_POST['weight'] ),
                'image_url'   => esc_url_raw( $_POST['image_url'] ),
                'stock_qty'   => Flora_Helpers::sanitize_number( $_POST['stock_qty'] ),
                'stock_status' => $_POST['stock_qty'] > 0 ? 'instock' : 'outofstock',
                'status'      => sanitize_text_field( $_POST['status'] ),
                'sort_order'  => Flora_Helpers::sanitize_number( $_POST['sort_order'] ),
            );

            $id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

            if ( $id > 0 ) {
                $db->update_product( $id, $data );
            } else {
                $db->insert_product( $data );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=flora-products&flora_notice=product_saved' ) );
            exit;
        }

        if ( 'delete' === $post_action ) {
            // Suppression : l'identifiant est converti en entier (absint) pour éviter toute injection.
            $id = absint( $_POST['product_id'] );
            if ( $id > 0 ) {
                $db->delete_product( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-products&flora_notice=product_deleted' ) );
            exit;
        }
    }

    private static function render_list() {
        // Affiche la liste des produits (tous statuts confondus) via la vue dédiée.
        $db      = Flora_DB::get_instance();
        $products = $db->get_products( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/products-list.php';
    }

    private static function render_form( $action ) {
        // Affiche le formulaire d'ajout / édition ; charge le produit existant en mode édition.
        $product = null;
        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $db      = Flora_DB::get_instance();
            // Lecture GET assainie par absint avant chargement du produit.
            $product = $db->get_product( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }

        include FLORA_SHOP_PATH . 'admin/views/product-form.php';
    }
}
