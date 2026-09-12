<?php /* Vue de la liste des promotions : tableau des déclencheurs, récompenses, limites et statuts. */ ?>

<div class="wrap flora-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Promotions', 'flora-shop' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Ajouter une promotion', 'flora-shop' ); ?></a>
    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper" style="padding-bottom:0;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Promotions', 'flora-shop' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions&tab=catalog' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Remises catégorie / type / tag', 'flora-shop' ); ?></a>
    </nav>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;"><?php esc_html_e( 'ID', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Déclencheur', 'flora-shop' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Quantité min.', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Récompense', 'flora-shop' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Limite', 'flora-shop' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></th>
                <th style="width:200px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $promotions ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'Aucune promotion configurée.', 'flora-shop' ); ?></td></tr>
            <?php else : ?>
                <?php /* --- Préparation des libellés lisibles pour le déclencheur et la récompense de chaque promotion --- */ ?>
                <?php
                $product_map = array();
                foreach ( $all_products as $ap ) {
                    $product_map[ $ap->id ] = $ap;
                }
                $pack_map = array();
                foreach ( $all_packs as $apk ) {
                    $pack_map[ $apk->id ] = $apk;
                }

                foreach ( $promotions as $pr ) :
                    $trigger_type = $pr->trigger_type ? $pr->trigger_type : 'product';
                    $reward_type  = $pr->reward_type ? $pr->reward_type : 'free';
                    $free_type    = isset( $pr->free_type ) && $pr->free_type ? $pr->free_type : 'product';

                    if ( 'pack' === $trigger_type && isset( $pack_map[ $pr->trigger_product_id ] ) ) {
                        $trigger = $pack_map[ $pr->trigger_product_id ];
                        $trigger_label = '[' . esc_html__( 'Pack', 'flora-shop' ) . '] ' . $trigger->name;
                    } elseif ( isset( $product_map[ $pr->trigger_product_id ] ) ) {
                        $trigger = $product_map[ $pr->trigger_product_id ];
                        $trigger_label = $trigger->name;
                    } else {
                        $trigger_label = esc_html__( 'Inconnu', 'flora-shop' );
                    }

                    if ( 'percent' === $reward_type ) {
                        $reward_label = sprintf( esc_html__( '-%s%%', 'flora-shop' ), $pr->discount_percent );
                    } elseif ( 'amount' === $reward_type ) {
                        $reward_label = sprintf( esc_html__( '-%s', 'flora-shop' ), Flora_Helpers::format_price( $pr->discount_amount ) );
                    } elseif ( 'pack' === $free_type && isset( $pack_map[ $pr->free_product_id ] ) ) {
                        $reward_label = sprintf( esc_html__( 'Pack offert : %s', 'flora-shop' ), $pack_map[ $pr->free_product_id ]->name );
                        if ( $pr->free_qty > 1 ) {
                            $reward_label .= ' × ' . $pr->free_qty;
                        }
                    } elseif ( isset( $product_map[ $pr->free_product_id ] ) ) {
                        $reward_label = sprintf( esc_html__( 'Produit offert : %s', 'flora-shop' ), $product_map[ $pr->free_product_id ]->name );
                        if ( $pr->free_qty > 1 ) {
                            $reward_label .= ' × ' . $pr->free_qty;
                        }
                    } else {
                        $reward_label = esc_html__( 'Inconnu', 'flora-shop' );
                    }
                ?>
                    <?php /* --- Lignes du tableau : une promotion par ligne --- */ ?>
                    <tr>
                        <td><?php echo esc_html( $pr->id ); ?></td>
                        <td><?php echo wp_kses_post( $trigger_label ); ?></td>
                        <td><?php echo esc_html( $pr->trigger_qty ); ?></td>
                        <td><?php echo esc_html( $reward_label ); ?></td>
                        <td><?php echo $pr->limit_per_order > 0 ? esc_html( $pr->limit_per_order ) : esc_html__( 'Illimité', 'flora-shop' ); ?></td>
                        <td>
                            <span class="flora-status flora-status-<?php echo 'active' === $pr->status ? 'publish' : 'draft'; ?>">
                                <?php echo esc_html( ucfirst( $pr->status ) ); ?>
                            </span>
                        </td>
                        <?php /* --- Colonne actions : lien modifier + formulaire de suppression --- */ ?>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions&action=edit&id=' . $pr->id ) ); ?>"><?php esc_html_e( 'Modifier', 'flora-shop' ); ?></a> |
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer cette promotion ?', 'flora-shop' ); ?>');">
                                <?php wp_nonce_field( 'flora_delete_promo', '_flora_nonce' ); ?>
                                <input type="hidden" name="flora_action" value="delete">
                                <input type="hidden" name="promo_id" value="<?php echo esc_attr( $pr->id ); ?>">
                                <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>