<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Packs » : affiche la liste et le formulaire d'ajout/édition des
 * packs, et traite la sauvegarde / suppression (POST) ainsi que l'association
 * des produits au pack (pack_products). Accès restreint à la capability
 * 'manage_options' ; nonce vérifié via Flora_Helpers::verify_nonce().
 */
class Flora_Admin_Packs {

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
        // Traite les actions POST (save / delete) de la page Packs.
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        // Vérification du nonce avant tout traitement d'enregistrement.
        Flora_Helpers::verify_nonce( 'flora_save_pack' );

        $db = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            // Assainissement systématique des champs POST avant écriture en base.
            $data = array(
                'name'        => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'slug'        => sanitize_title( $_POST['name'] ),
                'pack_price'  => Flora_Helpers::sanitize_float( $_POST['pack_price'] ),
                'description' => wp_kses_post( $_POST['description'] ),
                'image_url'   => esc_url_raw( $_POST['image_url'] ),
                'status'      => sanitize_text_field( $_POST['status'] ),
                'sort_order'  => Flora_Helpers::sanitize_number( $_POST['sort_order'] ),
                'category_id' => isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0,
            );

            $id = isset( $_POST['pack_id'] ) ? absint( $_POST['pack_id'] ) : 0;

            if ( $id > 0 ) {
                $db->update_pack( $id, $data );
            } else {
                $id = $db->insert_pack( $data );
            }

            // Enregistrement des étiquettes du pack (tableau d'IDs assaini en entiers).
            $tag_ids = array();
            if ( ! empty( $_POST['tags'] ) && is_array( $_POST['tags'] ) ) {
                foreach ( $_POST['tags'] as $tag_id ) {
                    $tag_ids[] = absint( $tag_id );
                }
            }
            $db->set_item_tags( 'pack', $id, $tag_ids );

            $pack_products = array();
            // Validation stricte des lignes produits du pack : chaque élément doit fournir
            // product_id et quantity non vides, convertis en entiers (absint).
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
            // Suppression : identifiant du pack converti en entier (absint) avant suppression.
            $id = absint( $_POST['pack_id'] );
            if ( $id > 0 ) {
                $db->delete_pack( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-packs&flora_notice=pack_deleted' ) );
            exit;
        }
    }

    private static function render_list() {
        // Affiche la liste des packs (tous statuts confondus) via la vue dédiée.
        $db   = Flora_DB::get_instance();
        $packs = $db->get_packs( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/packs-list.php';
    }

    private static function render_form( $action ) {
        // Affiche le formulaire d'ajout / édition ; charge le pack et ses produits en mode édition.
        $db   = Flora_DB::get_instance();
        $pack = null;
        $pack_products = array();

        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            // Lecture GET assainie par absint avant chargement du pack.
            $pack = $db->get_pack( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
            if ( $pack ) {
                $pack_products = $db->get_pack_products( $pack->id );
            }
        }

        // Liste complète des produits, nécessaire au sélecteur de la vue pack-form.
        $all_products = $db->get_products();

        // Catégories et étiquettes disponibles, et étiquettes déjà rattachées au pack.
        $all_categories = $db->get_categories();
        $all_tags       = $db->get_tags();
        $pack_tags      = array();
        if ( $pack ) {
            $pack_tags = $db->get_item_tags( 'pack', $pack->id );
        }

        include FLORA_SHOP_PATH . 'admin/views/pack-form.php';
    }
}
