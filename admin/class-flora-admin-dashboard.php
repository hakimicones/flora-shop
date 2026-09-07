<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Tableau de bord » : agrège les indicateurs clés (commandes,
 * revenus, produits les plus vendus, dernières commandes) et prépare les séries
 * de données pour le graphique des 30 derniers jours. Accès restreint à la
 * capability 'manage_options' ; aucune écriture POST ici, la page est en lecture.
 */
class Flora_Admin_Dashboard {

    public static function render() {
        // Point d'entrée de la page : vérifie la permission puis prépare et affiche le tableau de bord.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }

        $db = Flora_DB::get_instance();

        $total_orders    = $db->count_orders();
        $pending_orders  = $db->count_orders( 'pending' );
        $revenue         = $db->get_revenue();
        $today_revenue   = $db->get_revenue( current_time( 'Y-m-d' ), current_time( 'Y-m-d' ) );
        $top_products    = $db->get_top_products( 5 );
        $recent_orders   = $db->get_orders( array( 'limit' => 10 ) );
        $chart_data      = $db->get_orders_by_date( 30 );

        // Construction des séries pour le graphique : on aligne les données
        // réelles sur une plage continue des 30 derniers jours (0 si aucun résultat).
        $chart_labels = array();
        $chart_revenue = array();
        $chart_orders = array();
        $date_range = new DatePeriod(
            new DateTime( '-30 days' ),
            new DateInterval( 'P1D' ),
            new DateTime( '+1 day' )
        );

        $chart_by_date = array();
        foreach ( $chart_data as $row ) {
            $chart_by_date[ $row->order_date ] = $row;
        }

        foreach ( $date_range as $date ) {
            $d = $date->format( 'Y-m-d' );
            $chart_labels[] = $date->format( 'd/m' );
            $chart_revenue[] = isset( $chart_by_date[ $d ] ) ? (float) $chart_by_date[ $d ]->daily_revenue : 0;
            $chart_orders[] = isset( $chart_by_date[ $d ] ) ? (int) $chart_by_date[ $d ]->order_count : 0;
        }

        include FLORA_SHOP_PATH . 'admin/views/dashboard.php';
    }
}
