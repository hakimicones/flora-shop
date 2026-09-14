<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Promotions » : affiche la liste et le formulaire d'ajout/édition
 * des promotions, et traite la sauvegarde / suppression (POST). Accès restreint
 * à la capability 'manage_options' ; nonce vérifié via Flora_Helpers::verify_nonce().
 *
 * Mécanique « free / percent / amount » : le type de récompense est contraint
 * par une liste blanche (whitelist) et les champs conditionnels sont relus via
 * des gardes isset() afin de tolérer leur absence quand les champs sont
 * désactivés (attribut « disabled » géré par floraPromoToggle() dans la vue) —
 * un champ désactivé n'est jamais envoyé au serveur, ce qui excluait aussi le
 * bug « form control not focusable ».
 */
class Flora_Admin_Promotions {

    public static function render() {
        // Point d'entrée de la page : vérifie la permission, gère le POST puis affiche liste ou formulaire.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }

        // Onglet « Remises catégorie / type / tag » (processus séparé) : délégation à sa classe admin.
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'promos'; // phpcs:ignore WordPress.Security.NonceVerification
        if ( 'catalog' === $tab ) {
            Flora_Admin_Catalog_Discounts::render();
            return;
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
        // Traite les actions POST (save / delete) de la page Promotions.
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        $db           = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_promo' );

            // Lecture des types sélectionnés avec valeur par défaut si le champ n'est pas envoyé
            // (un champ désactivé par floraPromoToggle() n'apparaît pas dans $_POST).
            $trigger_type = isset( $_POST['trigger_type'] ) ? sanitize_text_field( wp_unslash( $_POST['trigger_type'] ) ) : 'product';
            $reward_type  = isset( $_POST['reward_type'] ) ? sanitize_text_field( wp_unslash( $_POST['reward_type'] ) ) : 'free';
            $free_type    = isset( $_POST['free_type'] ) ? sanitize_text_field( wp_unslash( $_POST['free_type'] ) ) : 'product';

            $data = array(
                'trigger_type'       => in_array( $trigger_type, array( 'product', 'pack' ), true ) ? $trigger_type : 'product',
                'trigger_product_id' => absint( isset( $_POST[ 'pack' === $trigger_type ? 'trigger_pack_id' : 'trigger_product_id' ] ) ? $_POST[ 'pack' === $trigger_type ? 'trigger_pack_id' : 'trigger_product_id' ] : 0 ),
                'trigger_qty'        => absint( isset( $_POST['trigger_qty'] ) ? $_POST['trigger_qty'] : 1 ),
                // Whitelist du type de récompense : seules 'free', 'percent' et 'amount' sont
                // acceptées, toute autre valeur retombe sur 'free'.
                'reward_type'        => in_array( $reward_type, array( 'free', 'percent', 'amount' ), true ) ? $reward_type : 'free',
                'free_type'          => in_array( $free_type, array( 'product', 'pack' ), true ) ? $free_type : 'product',
                'free_product_id'    => 'free' === $reward_type ? absint( isset( $_POST[ 'pack' === $free_type ? 'free_pack_id' : 'free_product_id' ] ) ? $_POST[ 'pack' === $free_type ? 'free_pack_id' : 'free_product_id' ] : 0 ) : 0,
                'free_qty'           => 'free' === $reward_type ? absint( isset( $_POST['free_qty'] ) ? $_POST['free_qty'] : 1 ) : 0,
                // Remise à zéro des champs non pertinents selon le type de récompense.
                'discount_percent'   => 'percent' === $reward_type ? Flora_Helpers::sanitize_float( isset( $_POST['discount_percent'] ) ? $_POST['discount_percent'] : 0 ) : 0,
                'discount_amount'    => 'amount' === $reward_type ? Flora_Helpers::sanitize_float( isset( $_POST['discount_amount'] ) ? $_POST['discount_amount'] : 0 ) : 0,
                'limit_per_order'    => absint( isset( $_POST['limit_per_order'] ) ? $_POST['limit_per_order'] : 0 ),
                'start_date'         => sanitize_text_field( isset( $_POST['start_date'] ) ? $_POST['start_date'] : '' ),
                'end_date'           => sanitize_text_field( isset( $_POST['end_date'] ) ? $_POST['end_date'] : '' ),
                'status'             => sanitize_text_field( isset( $_POST['status'] ) ? $_POST['status'] : 'active' ),
                // Personnalisation du message sur la fiche produit / pack (FR / AR) et CSS inline.
                'message_fr'         => isset( $_POST['message_fr'] ) ? sanitize_text_field( wp_unslash( $_POST['message_fr'] ) ) : '',
                'message_ar'         => isset( $_POST['message_ar'] ) ? sanitize_text_field( wp_unslash( $_POST['message_ar'] ) ) : '',
                'message_css'        => isset( $_POST['message_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['message_css'] ) ) : '',
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
            // Suppression : identifiant de la promotion converti en entier (absint).
            $id = absint( $_POST['promo_id'] );
            if ( $id > 0 ) {
                $db->delete_promotion( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-promotions&flora_notice=promo_deleted' ) );
            exit;
        }
    }

    private static function render_list() {
        // Affiche la liste des promotions ; charge produits et packs pour le libellé des cibles.
        $db          = Flora_DB::get_instance();
        $promotions  = $db->get_promotions();
        $all_products = $db->get_products();
        $all_packs    = $db->get_packs( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/promotions-list.php';
    }

    private static function render_form( $action ) {
        // Affiche le formulaire d'ajout / édition ; charge la promotion existante en mode édition.
        $db    = Flora_DB::get_instance();
        $promo = null;

        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            // Lecture GET assainie par absint avant chargement de la promotion.
            $promo = $db->get_promotion( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }

        // Produits et packs disponibles, alimentent les listes de sélection du formulaire.
        $all_products = $db->get_products();
        $all_packs    = $db->get_packs( array( 'status' => '' ) );

        include FLORA_SHOP_PATH . 'admin/views/promotion-form.php';
    }
}
