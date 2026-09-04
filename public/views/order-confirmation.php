<div class="flora-shop-wrap flora-confirm-wrap">
    <?php if ( $order ) : ?>
        <div class="flora-confirm-success">
            <span class="dashicons dashicons-yes-alt"></span>
            <h2><?php esc_html_e( 'Commande confirmée !', 'flora-shop' ); ?></h2>
            <p><?php esc_html_e( 'Merci pour votre commande.', 'flora-shop' ); ?></p>
            <p class="flora-order-number">
                <?php
                printf(
                    esc_html__( 'N° de commande : %s', 'flora-shop' ),
                    '<strong>' . esc_html( $order->order_number ) . '</strong>'
                );
                ?>
            </p>
        </div>

        <div class="flora-confirm-details">
            <div class="flora-confirm-card">
                <h3><?php esc_html_e( 'Détails de la commande', 'flora-shop' ); ?></h3>
                <table class="flora-confirm-table">
                    <tr><td><?php esc_html_e( 'Nom', 'flora-shop' ); ?></td><td><?php echo esc_html( $order->customer_name ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Email', 'flora-shop' ); ?></td><td><?php echo esc_html( $order->email ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Adresse', 'flora-shop' ); ?></td><td><?php echo esc_html( $order->address ); ?></td></tr>
                </table>
            </div>

            <div class="flora-confirm-card">
                <h3><?php esc_html_e( 'Récapitulatif', 'flora-shop' ); ?></h3>
                <table class="flora-confirm-table">
                    <tr><td><?php esc_html_e( 'Sous-total', 'flora-shop' ); ?></td><td><?php echo esc_html( Flora_Helpers::format_price( $order->subtotal ) ); ?></td></tr>
                    <?php if ( $order->discount_total > 0 ) : ?>
                        <tr><td><?php esc_html_e( 'Remises', 'flora-shop' ); ?></td><td>- <?php echo esc_html( Flora_Helpers::format_price( $order->discount_total ) ); ?></td></tr>
                    <?php endif; ?>
                    <tr><td><?php esc_html_e( 'Transport', 'flora-shop' ); ?></td><td><?php echo esc_html( Flora_Helpers::format_price( $order->shipping_fee ) ); ?></td></tr>
                    <tr class="flora-total-row"><td><strong><?php esc_html_e( 'Total', 'flora-shop' ); ?></strong></td><td><strong><?php echo esc_html( Flora_Helpers::format_price( $order->total ) ); ?></strong></td></tr>
                </table>
            </div>
        </div>

        <div class="flora-confirm-status">
            <p>
                <?php
                printf(
                    esc_html__( 'Statut : %s', 'flora-shop' ),
                    '<span class="flora-status flora-status-' . esc_attr( $order->status ) . '">' . esc_html( ucfirst( $order->status ) ) . '</span>'
                );
                ?>
            </p>
            <p><?php esc_html_e( 'Vous recevrez un email de confirmation avec les détails de votre commande.', 'flora-shop' ); ?></p>
        </div>

    <?php else : ?>
        <div class="flora-confirm-error">
            <h2><?php esc_html_e( 'Commande introuvable', 'flora-shop' ); ?></h2>
            <p><?php esc_html_e( 'Nous n\'avons pas pu trouver cette commande. Veuillez vérifier le numéro de commande.', 'flora-shop' ); ?></p>
        </div>
    <?php endif; ?>
</div>
