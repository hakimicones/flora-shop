<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Catégories » : affiche la liste et le formulaire d'ajout/édition
 * des catégories partagées (produits et packs, une catégorie par article),
 * et traite la sauvegarde / suppression (POST). Accès restreint à la capability
 * 'manage_options' ; nonce vérifié via Flora_Helpers::verify_nonce().
 */
class Flora_Admin_Categories {

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
        // Traite les actions POST (save / delete) de la page Catégories.
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        $db           = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            // Vérification du nonce avant tout traitement d'enregistrement.
            Flora_Helpers::verify_nonce( 'flora_save_category' );

            // Assainissement systématique des champs POST avant écriture en base.
            $data = array(
                'name'        => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'slug'        => sanitize_title( $_POST['name'] ),
                'description' => Flora_Helpers::sanitize_text( $_POST['description'] ),
                'sort_order'  => Flora_Helpers::sanitize_number( $_POST['sort_order'] ),
            );

            $id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;

            if ( $id > 0 ) {
                $db->update_category( $id, $data );
            } else {
                $db->insert_category( $data );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=flora-categories&flora_notice=category_saved' ) );
            exit;
        }

        if ( 'delete' === $post_action ) {
            // Vérification du nonce dédié à la suppression.
            Flora_Helpers::verify_nonce( 'flora_delete_category' );

            // Suppression : identifiant de la catégorie converti en entier (absint).
            $id = absint( $_POST['category_id'] );
            if ( $id > 0 ) {
                // La suppression remet la catégorie à 0 sur les articles qui lui étaient rattachés.
                $db->delete_category( $id );
                $db->clear_category_links( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-categories&flora_notice=category_deleted' ) );
            exit;
        }
    }

    private static function render_list() {
        // Affiche la liste des catégories via la vue dédiée.
        $db         = Flora_DB::get_instance();
        $categories = $db->get_categories();

        include FLORA_SHOP_PATH . 'admin/views/categories-list.php';
    }

    private static function render_form( $action ) {
        // Affiche le formulaire d'ajout / édition ; charge la catégorie existante en mode édition.
        $category = null;
        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $db      = Flora_DB::get_instance();
            // Lecture GET assainie par absint avant chargement de la catégorie.
            $category = $db->get_category( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }

        include FLORA_SHOP_PATH . 'admin/views/category-form.php';
    }
}