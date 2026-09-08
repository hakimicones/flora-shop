<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Étiquettes » : affiche la liste et le formulaire d'ajout/édition
 * des étiquettes (tags) attribuables aux produits et packs, et traite la
 * sauvegarde / suppression (POST). L'association des étiquettes aux articles
 * se fait dans les formulaires Produit et Pack. Accès restreint à la capability
 * 'manage_options' ; nonce vérifié via Flora_Helpers::verify_nonce().
 */
class Flora_Admin_Tags {

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
        // Traite les actions POST (save / delete) de la page Étiquettes.
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        $db           = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            // Vérification du nonce avant tout traitement d'enregistrement.
            Flora_Helpers::verify_nonce( 'flora_save_tag' );

            // Assainissement systématique des champs POST avant écriture en base.
            $data = array(
                'name' => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'slug' => sanitize_title( $_POST['name'] ),
            );

            $id = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;

            if ( $id > 0 ) {
                $db->update_tag( $id, $data );
            } else {
                $db->insert_tag( $data );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=flora-tags&flora_notice=tag_saved' ) );
            exit;
        }

        if ( 'delete' === $post_action ) {
            // Vérification du nonce dédié à la suppression.
            Flora_Helpers::verify_nonce( 'flora_delete_tag' );

            // Suppression : identifiant de l'étiquette converti en entier (absint).
            $id = absint( $_POST['tag_id'] );
            if ( $id > 0 ) {
                // La suppression retire également les liaisons des articles vers cette étiquette.
                $db->delete_tag( $id );
                $db->clear_tag_links( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-tags&flora_notice=tag_deleted' ) );
            exit;
        }
    }

    private static function render_list() {
        // Affiche la liste des étiquettes via la vue dédiée.
        $db   = Flora_DB::get_instance();
        $tags = $db->get_tags();

        include FLORA_SHOP_PATH . 'admin/views/tags-list.php';
    }

    private static function render_form( $action ) {
        // Affiche le formulaire d'ajout / édition ; charge l'étiquette existante en mode édition.
        $tag = null;
        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $db = Flora_DB::get_instance();
            // Lecture GET assainie par absint avant chargement de l'étiquette.
            $tag = $db->get_tag( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }

        include FLORA_SHOP_PATH . 'admin/views/tag-form.php';
    }
}