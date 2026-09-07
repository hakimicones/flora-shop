<?php /* Vue du détail d'une commande : infos client, articles commandés, résumé des montants et changement de statut. */ ?>

<div class="wrap flora-admin">
    <h1><?php esc_html_e( 'Détails de la commande', 'flora-shop' ); ?> <?php echo esc_html( $order->order_number ); ?></h1>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-orders' ) ); ?>" class="button">&larr; <?php esc_html_e( 'Retour aux commandes', 'flora-shop' ); ?></a>

    <?php /* --- Colonne principale : informations client + détail des articles --- */ ?>
    <?php /* --- Infos du client (identité, coordonnées, wilaya et commune) --- */ ?>
    <div class="flora-dashboard-columns" style="margin-top:20px;">
        <div class="flora-dashboard-main">
            <div class="flora-card">
                <h2><?php esc_html_e( 'Informations client', 'flora-shop' ); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e( 'Nom', 'flora-shop' ); ?></th><td><?php echo esc_html( $order->customer_name ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Email', 'flora-shop' ); ?></th><td><?php echo esc_html( $order->email ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Téléphone', 'flora-shop' ); ?></th><td><?php echo esc_html( $order->phone ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Adresse', 'flora-shop' ); ?></th><td><?php echo esc_html( $order->address ); ?></td></tr>
                    <tr>
                        <th><?php esc_html_e( 'Wilaya', 'flora-shop' ); ?></th>
                        <td><?php echo isset( $wilaya_map[ $order->wilaya_code ] ) ? esc_html( $wilaya_map[ $order->wilaya_code ]->code . ' - ' . $wilaya_map[ $order->wilaya_code ]->name ) : '—'; ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Commune', 'flora-shop' ); ?></th>
                        <td><?php echo isset( $commune_map[ $order->commune_id ] ) ? esc_html( $commune_map[ $order->commune_id ]->name ) : '—'; ?></td>
                    </tr>
                </table>
            </div>

            <?php /* --- Tableau des articles commandés, avec éventuelle ligne gratuite (promotion) --- */ ?>
            <div class="flora-card">
                <h2><?php esc_html_e( 'Articles commandés', 'flora-shop' ); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Article', 'flora-shop' ); ?></th>
                            <th style="width:100px;"><?php esc_html_e( 'Type', 'flora-shop' ); ?></th>
                            <th style="width:80px;"><?php esc_html_e( 'Qté', 'flora-shop' ); ?></th>
                            <th style="width:120px;"><?php esc_html_e( 'Prix unitaire', 'flora-shop' ); ?></th>
                            <th style="width:120px;"><?php esc_html_e( 'Remise', 'flora-shop' ); ?></th>
                            <th style="width:120px;"><?php esc_html_e( 'Total', 'flora-shop' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $details ) ) : ?>
                            <tr><td colspan="6"><?php esc_html_e( 'Aucun article.', 'flora-shop' ); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ( $details as $d ) :
                                $line_total = $d->quantity * $d->unit_price;
                            ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $d->item_name ); ?>
                                        <?php if ( 'free_item' === $d->item_type ) : ?>
                                            <span class="flora-badge-free"><?php esc_html_e( 'GRATUIT', 'flora-shop' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html( $d->item_type ); ?></td>
                                    <td><?php echo esc_html( $d->quantity ); ?></td>
                                    <td><?php echo esc_html( Flora_Helpers::format_price( $d->unit_price ) ); ?></td>
                                    <td><?php echo $d->discount_applied > 0 ? esc_html( Flora_Helpers::format_price( $d->discount_applied ) ) : '—'; ?></td>
                                    <td><strong><?php echo esc_html( Flora_Helpers::format_price( $line_total ) ); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php /* --- Colonne latérale : résumé des montants, changement de statut et notes --- */ ?>
        <div class="flora-dashboard-sidebar">
            <div class="flora-card">
                <h2><?php esc_html_e( 'Résumé', 'flora-shop' ); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e( 'N° Commande', 'flora-shop' ); ?></th><td><strong><?php echo esc_html( $order->order_number ); ?></strong></td></tr>
                    <tr><th><?php esc_html_e( 'Date', 'flora-shop' ); ?></th><td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $order->created_at ) ) ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Sous-total', 'flora-shop' ); ?></th><td><?php echo esc_html( Flora_Helpers::format_price( $order->subtotal ) ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Remises', 'flora-shop' ); ?></th><td><?php echo esc_html( Flora_Helpers::format_price( $order->discount_total ) ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Transport', 'flora-shop' ); ?></th><td><?php echo esc_html( Flora_Helpers::format_price( $order->shipping_fee ) ); ?></td></tr>
                    <tr><th><strong><?php esc_html_e( 'Total', 'flora-shop' ); ?></strong></th><td><strong><?php echo esc_html( Flora_Helpers::format_price( $order->total ) ); ?></strong></td></tr>
                </table>
            </div>

            <?php /* --- Formulaire de mise à jour du statut (POST sécurisé par nonce) --- */ ?>
            <div class="flora-card">
                <h2><?php esc_html_e( 'Changer le statut', 'flora-shop' ); ?></h2>
                <form method="post">
                    <?php Flora_Helpers::wpnonce_field( 'flora_update_order' ); ?>
                    <input type="hidden" name="flora_action" value="update_status">
                    <input type="hidden" name="order_id" value="<?php echo esc_attr( $order->id ); ?>">
                    <select name="status">
                        <?php
                        $statuses = array(
                            'pending'    => __( 'En attente', 'flora-shop' ),
                            'processing' => __( 'En cours', 'flora-shop' ),
                            'completed'  => __( 'Terminée', 'flora-shop' ),
                            'cancelled'  => __( 'Annulée', 'flora-shop' ),
                            'refunded'   => __( 'Remboursée', 'flora-shop' ),
                            'shipped'    => __( 'Expédiée', 'flora-shop' ),
                        );
                        foreach ( $statuses as $val => $label ) :
                        ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $order->status, $val ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br><br>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Mettre à jour', 'flora-shop' ); ?></button>
                </form>
            </div>

            <?php if ( $order->notes ) : ?>
                <div class="flora-card">
                    <h2><?php esc_html_e( 'Notes', 'flora-shop' ); ?></h2>
                    <p><?php echo nl2br( esc_html( $order->notes ) ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
