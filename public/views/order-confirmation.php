<!--
    Confirmation de commande (shortcode [flora_order_confirm]).
    Affiche le récapitulatif de la commande passée (numéro, coordonnées, totaux,
    statut) à partir de l'objet $order, ou un message d'erreur si introuvable.
-->
<div class="flora-shop-wrap flora-confirm-wrap">
    <?php if ( $order ) : ?>
        <!-- Bloc de succès : icône, titre et numéro de commande. -->
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

        <!-- Coordonnées du client enregistrées avec la commande. -->
        <div class="flora-confirm-details">
            <div class="flora-confirm-card">
                <h3><?php esc_html_e( 'Détails de la commande', 'flora-shop' ); ?></h3>
                <table class="flora-confirm-table">
                    <tr><td><?php esc_html_e( 'Nom', 'flora-shop' ); ?></td><td><?php echo esc_html( $order->customer_name ); ?></td></tr>
                    <?php if ( get_option( 'flora_show_order_email', 1 ) ) : ?>
                        <tr><td><?php esc_html_e( 'Email', 'flora-shop' ); ?></td><td><?php echo esc_html( $order->email ); ?></td></tr>
                    <?php endif; ?>
                    <tr><td><?php echo esc_html( Flora_Helpers::ui( __( 'Méthode de livraison', 'flora-shop' ), 'طريقة التوصيل' ) ); ?></td><td><?php echo esc_html( Flora_Helpers::shipping_method_label( $order->shipping_method ) ? Flora_Helpers::shipping_method_label( $order->shipping_method ) : $order->shipping_method ); ?></td></tr>
                    <?php if ( ! empty( $order->address ) ) : ?>
                        <tr><td><?php esc_html_e( 'Adresse', 'flora-shop' ); ?></td><td><?php echo esc_html( $order->address ); ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Totaux : sous-total, remises éventuelles, transport et total final. -->
            <div class="flora-confirm-card">
                <h3><?php esc_html_e( 'Récapitulatif', 'flora-shop' ); ?></h3>
                <table class="flora-confirm-table">
                    <tr><td><?php echo esc_html( Flora_Helpers::ui( __( 'Sous-total', 'flora-shop' ), 'المجموع الفرعي' ) ); ?></td><td><?php echo esc_html( Flora_Helpers::format_price( $order->subtotal ) ); ?></td></tr>
                    <?php if ( $order->discount_total > 0 ) : ?>
                        <tr><td><?php echo esc_html( Flora_Helpers::ui( __( 'Remises', 'flora-shop' ), 'التخفيضات' ) ); ?></td><td>- <?php echo esc_html( Flora_Helpers::format_price( $order->discount_total ) ); ?></td></tr>
                    <?php endif; ?>
                    <tr><td><?php echo esc_html( Flora_Helpers::ui( __( 'Transport', 'flora-shop' ), 'التوصيل' ) ); ?></td><td><?php echo esc_html( Flora_Helpers::format_price( $order->shipping_fee ) ); ?></td></tr>
                    <tr class="flora-total-row"><td><strong><?php echo esc_html( Flora_Helpers::ui( __( 'Total', 'flora-shop' ), 'المجموع' ) ); ?></strong></td><td><strong><?php echo esc_html( Flora_Helpers::format_price( $order->total ) ); ?></strong></td></tr>
                </table>
            </div>
        </div>

        <!-- Statut de la commande et note sur l'email de confirmation. -->
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
        <!-- Bloc d'erreur : le numéro de commande fourni n'a pas été trouvé. -->
        <div class="flora-confirm-error">
            <h2><?php esc_html_e( 'Commande introuvable', 'flora-shop' ); ?></h2>
            <p><?php esc_html_e( 'Nous n\'avons pas pu trouver cette commande. Veuillez vérifier le numéro de commande.', 'flora-shop' ); ?></p>
        </div>
    <?php endif; ?>
</div>
