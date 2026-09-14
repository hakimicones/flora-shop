<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Remises par catégorie / type / tag » (onglet de la page Promotions) :
 * liste, formulaire d'ajout/édition et traitement POST (enregistrer / supprimer).
 * Accès restreint à la capability 'manage_options' ; nonce vérifié via Flora_Helpers::verify_nonce().
 *
 * Chaque règle cible une catégorie, un type d'article (produit ou pack) ou une étiquette
 * et offre une remise (%, montant fixe ou article offert) dès qu'une quantité d'articles
 * éligibles est atteinte dans le panier. La mécanique de formulaire suit celle des promotions :
 * whitelist des types, gardes isset() pour les champs désactivés par le toggle JS.
 */
class Flora_Admin_Catalog_Discounts {

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
        $db          = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_catalog_discount' );

            $scope      = isset( $_POST['target_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['target_scope'] ) ) : 'category';
            $reward_type = isset( $_POST['reward_type'] ) ? sanitize_text_field( wp_unslash( $_POST['reward_type'] ) ) : 'percent';
            $free_type  = isset( $_POST['free_type'] ) ? sanitize_text_field( wp_unslash( $_POST['free_type'] ) ) : 'product';

            $scope = in_array( $scope, array( 'category', 'type', 'tag' ), true ) ? $scope : 'category';

            // Cible : identifiant de catégorie / étiquette, ou type d'article (produit/pack) pour la portée « type ».
            $target_id = 0;
            $item_type = 'both';
            if ( 'type' === $scope ) {
                $target_type = isset( $_POST['target_type'] ) ? sanitize_text_field( wp_unslash( $_POST['target_type'] ) ) : 'product';
                $item_type   = in_array( $target_type, array( 'product', 'pack' ), true ) ? $target_type : 'product';
            } else {
                $target_id = absint( isset( $_POST['catalog_target_id'] ) ? $_POST['catalog_target_id'] : 0 );
                $item_scope = isset( $_POST['item_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['item_scope'] ) ) : 'both';
                $item_type = in_array( $item_scope, array( 'product', 'pack', 'both' ), true ) ? $item_scope : 'both';
            }

            $data = array(
                'scope'            => $scope,
                'target_id'        => $target_id,
                'item_type'        => $item_type,
                'trigger_qty'      => absint( isset( $_POST['trigger_qty'] ) ? $_POST['trigger_qty'] : 1 ),
                // Whitelist du type de récompense : seules 'free', 'percent' et 'amount' sont acceptées.
                'reward_type'      => in_array( $reward_type, array( 'free', 'percent', 'amount' ), true ) ? $reward_type : 'percent',
                'free_type'        => in_array( $free_type, array( 'product', 'pack' ), true ) ? $free_type : 'product',
                'free_product_id'  => 'free' === $reward_type ? absint( isset( $_POST[ 'pack' === $free_type ? 'free_pack_id' : 'free_product_id' ] ) ? $_POST[ 'pack' === $free_type ? 'free_pack_id' : 'free_product_id' ] : 0 ) : 0,
                'free_qty'         => 'free' === $reward_type ? absint( isset( $_POST['free_qty'] ) ? $_POST['free_qty'] : 1 ) : 0,
                // Remise à zéro des champs non pertinents selon le type de récompense.
                'discount_percent' => 'percent' === $reward_type ? Flora_Helpers::sanitize_float( isset( $_POST['discount_percent'] ) ? $_POST['discount_percent'] : 0 ) : 0,
                'discount_amount'  => 'amount' === $reward_type ? Flora_Helpers::sanitize_float( isset( $_POST['discount_amount'] ) ? $_POST['discount_amount'] : 0 ) : 0,
                'limit_per_order'  => absint( isset( $_POST['limit_per_order'] ) ? $_POST['limit_per_order'] : 0 ),
                'start_date'       => sanitize_text_field( isset( $_POST['start_date'] ) ? $_POST['start_date'] : '' ),
                'end_date'         => sanitize_text_field( isset( $_POST['end_date'] ) ? $_POST['end_date'] : '' ),
                'status'           => sanitize_text_field( isset( $_POST['status'] ) ? $_POST['status'] : 'active' ),
                // Personnalisation du message sur la fiche produit / pack (FR / AR) et CSS inline.
                'message_fr'       => isset( $_POST['message_fr'] ) ? sanitize_text_field( wp_unslash( $_POST['message_fr'] ) ) : '',
                'message_ar'       => isset( $_POST['message_ar'] ) ? sanitize_text_field( wp_unslash( $_POST['message_ar'] ) ) : '',
                'message_css'      => isset( $_POST['message_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['message_css'] ) ) : '',
            );

            $id = isset( $_POST['discount_id'] ) ? absint( $_POST['discount_id'] ) : 0;

            if ( $id > 0 ) {
                $db->update_catalog_discount( $id, $data );
            } else {
                $db->insert_catalog_discount( $data );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=flora-promotions&tab=catalog&flora_notice=catalog_discount_saved' ) );
            exit;
        }

        if ( 'delete' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_delete_catalog_discount' );
            $id = absint( $_POST['discount_id'] );
            if ( $id > 0 ) {
                $db->delete_catalog_discount( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-promotions&tab=catalog&flora_notice=catalog_discount_deleted' ) );
            exit;
        }
    }

    private static function render_list() {
        $db          = Flora_DB::get_instance();
        $discounts   = $db->get_catalog_discounts();
        $categories  = $db->get_categories();
        $tags        = $db->get_tags();
        $all_products = $db->get_products();
        $all_packs    = $db->get_packs( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/catalog-discounts-list.php';
    }

    private static function render_form( $action ) {
        $db         = Flora_DB::get_instance();
        $discount   = null;

        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $discount = $db->get_catalog_discount( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }

        $categories = $db->get_categories();
        $tags       = $db->get_tags();
        $all_products = $db->get_products();
        $all_packs    = $db->get_packs( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/catalog-discount-form.php';
    }
}