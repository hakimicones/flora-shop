<?php /* Vue de la liste des commandes : onglets de filtre par statut, puis tableau des commandes. */ ?>

<div class="wrap flora-admin">
    <h1><?php esc_html_e( 'Commandes', 'flora-shop' ); ?></h1>

    <?php /* --- Filtres par statut (toutes, en attente, en cours, terminées) --- */ ?>
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-orders' ) ); ?>" class="nav-tab <?php echo empty( $status_filter ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Toutes', 'flora-shop' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-orders&status=pending' ) ); ?>" class="nav-tab <?php echo 'pending' === $status_filter ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'En attente', 'flora-shop' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-orders&status=processing' ) ); ?>" class="nav-tab <?php echo 'processing' === $status_filter ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'En cours', 'flora-shop' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-orders&status=completed' ) ); ?>" class="nav-tab <?php echo 'completed' === $status_filter ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Terminées', 'flora-shop' ); ?></a>
    </h2>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:150px;"><?php esc_html_e( 'N° Commande', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Client', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Email', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Wilaya', 'flora-shop' ); ?></th>
                <th style="width:120px;"><?php esc_html_e( 'Total', 'flora-shop' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></th>
                <th style="width:180px;"><?php esc_html_e( 'Date', 'flora-shop' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Action', 'flora-shop' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php /* --- Lignes du tableau : une commande par ligne, avec lien vers le détail --- */ ?>
            <?php if ( empty( $orders ) ) : ?>
                <tr><td colspan="8"><?php esc_html_e( 'Aucune commande trouvée.', 'flora-shop' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $orders as $o ) :
                    $w_name = isset( $wilaya_map[ $o->wilaya_code ] ) ? $wilaya_map[ $o->wilaya_code ]->name : '—';
                ?>
                    <tr>
                        <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-orders&action=view&id=' . $o->id ) ); ?>"><?php echo esc_html( $o->order_number ); ?></a></td>
                        <td><?php echo esc_html( $o->customer_name ); ?></td>
                        <td><?php echo esc_html( $o->email ); ?></td>
                        <td><?php echo esc_html( $w_name ); ?></td>
                        <td><strong><?php echo esc_html( Flora_Helpers::format_price( $o->total ) ); ?></strong></td>
                        <td>
                            <span class="flora-status flora-status-<?php echo esc_attr( $o->status ); ?>">
                                <?php echo esc_html( ucfirst( $o->status ) ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $o->created_at ) ) ); ?></td>
                        <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-orders&action=view&id=' . $o->id ) ); ?>"><?php esc_html_e( 'Voir', 'flora-shop' ); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
