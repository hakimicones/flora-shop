<?php /* Vue principale du tableau de bord : statistiques globales, graphique de revenus, dernières commandes et produits les plus vendus. */ ?>

<div class="wrap flora-admin">
    <h1><?php esc_html_e( 'Tableau de bord Flora Shop', 'flora-shop' ); ?></h1>

    <?php /* --- Cartes de statistiques (revenus, commandes) --- */ ?>
    <div class="flora-stats-grid">
        <div class="flora-stat-card">
            <h3><?php esc_html_e( 'Revenu total', 'flora-shop' ); ?></h3>
            <p class="flora-stat-number"><?php echo esc_html( Flora_Helpers::format_price( $revenue ) ); ?></p>
        </div>
        <div class="flora-stat-card">
            <h3><?php esc_html_e( 'Revenu aujourd\'hui', 'flora-shop' ); ?></h3>
            <p class="flora-stat-number"><?php echo esc_html( Flora_Helpers::format_price( $today_revenue ) ); ?></p>
        </div>
        <div class="flora-stat-card">
            <h3><?php esc_html_e( 'Total commandes', 'flora-shop' ); ?></h3>
            <p class="flora-stat-number"><?php echo esc_html( $total_orders ); ?></p>
        </div>
        <div class="flora-stat-card">
            <h3><?php esc_html_e( 'Commandes en attente', 'flora-shop' ); ?></h3>
            <p class="flora-stat-number"><?php echo esc_html( $pending_orders ); ?></p>
        </div>
    </div>

    <?php /* --- Colonne principale : graphique + tableau des dernières commandes --- */ ?>
    <div class="flora-dashboard-columns">
        <div class="flora-dashboard-main">
            <div class="flora-card">
                <?php /* Graphique Chart.js affichant les revenus sur 30 jours */ ?>
                <h2><?php esc_html_e( 'Revenus des 30 derniers jours', 'flora-shop' ); ?></h2>
                <canvas id="flora-revenue-chart" height="300"></canvas>
            </div>

        <?php /* --- Tableau des dernières commandes récentes --- */ ?>
            <div class="flora-card">
                <h2><?php esc_html_e( 'Dernières commandes', 'flora-shop' ); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'N° Commande', 'flora-shop' ); ?></th>
                            <th><?php esc_html_e( 'Client', 'flora-shop' ); ?></th>
                            <th><?php esc_html_e( 'Total', 'flora-shop' ); ?></th>
                            <th><?php esc_html_e( 'Statut', 'flora-shop' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'flora-shop' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $recent_orders ) ) : ?>
                            <tr><td colspan="5"><?php esc_html_e( 'Aucune commande.', 'flora-shop' ); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ( $recent_orders as $o ) : ?>
                                <tr>
                                    <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-orders&action=view&id=' . $o->id ) ); ?>"><?php echo esc_html( $o->order_number ); ?></a></td>
                                    <td><?php echo esc_html( $o->customer_name ); ?></td>
                                    <td><?php echo esc_html( Flora_Helpers::format_price( $o->total ) ); ?></td>
                                    <td><span class="flora-status flora-status-<?php echo esc_attr( $o->status ); ?>"><?php echo esc_html( ucfirst( $o->status ) ); ?></span></td>
                                    <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $o->created_at ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php /* --- Colonne latérale : produits les plus vendus --- */ ?>
        <div class="flora-dashboard-sidebar">
            <div class="flora-card">
                <h2><?php esc_html_e( 'Produits les plus vendus', 'flora-shop' ); ?></h2>
                <?php if ( empty( $top_products ) ) : ?>
                    <p><?php esc_html_e( 'Aucune donnée.', 'flora-shop' ); ?></p>
                <?php else : ?>
                    <ul class="flora-top-products">
                        <?php foreach ( $top_products as $tp ) : ?>
                            <li>
                                <span class="flora-tp-name"><?php echo esc_html( $tp->item_name ); ?></span>
                                <span class="flora-tp-qty"><?php printf( esc_html__( '%d vendus', 'flora-shop' ), $tp->total_qty ); ?></span>
                                <span class="flora-tp-revenue"><?php echo esc_html( Flora_Helpers::format_price( $tp->total_revenue ) ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php /* --- Script Chart.js pour le graphique de revenus --- */ ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('flora-revenue-chart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo wp_json_encode( $chart_labels ); ?>,
                    datasets: [{
                        label: '<?php esc_html_e( 'Revenu', 'flora-shop' ); ?>',
                        data: <?php echo wp_json_encode( $chart_revenue ); ?>,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    });
    </script>
</div>
