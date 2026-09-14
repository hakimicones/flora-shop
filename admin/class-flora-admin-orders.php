<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Commandes » : affiche la liste des commandes (avec filtre de
 * statut), la vue de détail d'une commande, et traite le changement de statut
 * d'une commande (POST). Accès restreint à la capability 'manage_options' ;
 * nonce vérifié via Flora_Helpers::verify_nonce().
 */
class Flora_Admin_Orders {

    public static function render() {
        // Point d'entrée de la page : vérifie la permission, gère le POST puis affiche liste ou détail.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }

        self::handle_actions();

        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification

        if ( 'view' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            self::render_detail( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        } else {
            self::render_list();
        }
    }

    private static function handle_actions() {
        // Traite l'action POST de mise à jour du statut d'une commande.
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

        if ( 'update_status' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_update_order' );
            $db   = Flora_DB::get_instance();
            // ID assaini par absint ; statut nettoyé via sanitize_text_field avant mise à jour.
            $id   = absint( $_POST['order_id'] );
            $status = sanitize_text_field( $_POST['status'] );
            $db->update_order( $id, array( 'status' => $status ) );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-orders&action=view&id=' . $id . '&flora_notice=order_updated' ) );
            exit;
        }
    }

    private static function render_list() {
        $db             = Flora_DB::get_instance();
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        $args              = self::get_list_args( $status_filter );
        $total             = $db->count_orders( $args );
        $args['limit']     = Flora_Admin_List::per_page();
        $args['offset']    = Flora_Admin_List::offset();
        $orders            = $db->get_orders( $args );
        $wilaya_map        = self::get_wilaya_map( $db );
        $status_filter     = $status_filter; // conserve pour les nav-tabs.

        include FLORA_SHOP_PATH . 'admin/views/orders-list.php';
    }

    // Export Excel des commandes filtrées (sans pagination).
    public static function export() {
        $db           = Flora_DB::get_instance();
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $args         = self::get_list_args( $status_filter );
        $all          = $db->get_orders( $args );
        $wilayas_map  = self::get_wilaya_map( $db );
        $rows = array();
        foreach ( $all as $o ) {
            $w_name = isset( $wilayas_map[ $o->wilaya_code ] ) ? $wilayas_map[ $o->wilaya_code ]->name : '';
            $rows[] = array(
                $o->order_number,
                $o->customer_name,
                $o->phone,
                $o->email,
                $w_name,
                $o->address,
                (float) $o->total,
                ucfirst( $o->status ),
                date_i18n( 'd/m/Y H:i', strtotime( $o->created_at ) ),
            );
        }
        Flora_Exporter::handle_export( 'flora-commandes.xlsx', array( 'N° Commande', 'Client', 'Téléphone', 'Email', 'Wilaya', 'Adresse', 'Total', 'Statut', 'Date' ), $rows );
    }

    private static function get_wilaya_map( $db ) {
        $wilayas = $db->get_wilayas();
        $map     = array();
        foreach ( $wilayas as $w ) {
            $map[ $w->code ] = $w;
        }
        return $map;
    }

    private static function get_list_args( $status = '' ) {
        $search   = isset( $_GET['flora_s'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        $args = array( 'search' => $search, 'date_from' => $date_from, 'date_to' => $date_to );
        if ( $status ) {
            $args['status'] = $status;
        }
        return $args;
    }

    private static function render_detail( $order_id ) {
        // Affiche la fiche détaillée d'une commande : infos client, lignes, transport.
        $db    = Flora_DB::get_instance();
        // order_id arrive déjà sous forme d'entier (absint) depuis render().
        $order = $db->get_order( $order_id );

        if ( ! $order ) {
            wp_die( esc_html__( 'Commande introuvable.', 'flora-shop' ) );
        }

        $details     = $db->get_order_details( $order_id );
        $wilayas     = $db->get_wilayas();
        $wilaya_map  = array();
        foreach ( $wilayas as $w ) {
            $wilaya_map[ $w->code ] = $w;
        }
        $communes    = $db->get_communes( $order->wilaya_code );
        $commune_map = array();
        foreach ( $communes as $c ) {
            $commune_map[ $c->id ] = $c;
        }

        include FLORA_SHOP_PATH . 'admin/views/order-detail.php';
    }
}
